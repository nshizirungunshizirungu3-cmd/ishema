<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Audit;
use App\Core\Database;
use App\Core\NotificationService;
use App\Core\Translator;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderItem;
use App\Models\DeliveryConfirmation;
use App\Models\Product;
use App\Models\RestaurantTable;

/**
 * Restaurant ordering, delivery and receiving-confirmation workflow.
 */
class OrderService
{
    /**
     * Create an order from cart items.
     * @param array $items array of ['product_id'=>int, 'quantity'=>float, 'notes'=>string]
     */
    public static function createOrder(array $data, array $items): array
    {
        Database::beginTransaction();
        try {
            $businessId = (int)$data['business_id'];
            $number = Order::generateNumber($businessId);
            $total = 0.0;

            $orderId = Order::insert([
                'business_id' => $businessId,
                'order_number' => $number,
                'table_id' => $data['table_id'] ?? null,
                'guest_name' => $data['guest_name'] ?? null,
                'guest_phone' => $data['guest_phone'] ?? null,
                'waiter_user_id' => $data['waiter_user_id'] ?? null,
                'customer_user_id' => $data['customer_user_id'] ?? null,
                'source' => $data['source'] ?? 'qr',
                'status' => 'pending',
                'total_amount' => 0,
                'notes' => $data['notes'] ?? null,
                'language' => $data['language'] ?? 'en',
            ]);

            foreach ($items as $item) {
                $product = Product::find((int)$item['product_id']);
                if (!$product || (int)$product['business_id'] !== $businessId) {
                    throw new \RuntimeException('Invalid product in order.');
                }
                $qty = (float)$item['quantity'];
                if ($qty <= 0) {
                    throw new \RuntimeException('Invalid quantity.');
                }
                $lineTotal = $qty * (float)$product['price'];
                $total += $lineTotal;

                OrderItem::insert([
                    'order_id' => $orderId,
                    'business_id' => $businessId,
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'quantity' => $qty,
                    'unit_price' => $product['price'],
                    'line_total' => $lineTotal,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            Order::update($orderId, ['total_amount' => $total]);

            // Update table status
            if (!empty($data['table_id'])) {
                RestaurantTable::update((int)$data['table_id'], ['status' => 'occupied']);
            }

            // Stock out for inventory-tracked products
            foreach ($items as $item) {
                self::decrementStock($businessId, (int)$item['product_id'], (float)$item['quantity'], $orderId);
            }

            Database::commit();

            // Notify waiters and kitchen
            $orderNumber = $number;
            $tableName = '';
            if (!empty($data['table_id'])) {
                $t = RestaurantTable::find((int)$data['table_id']);
                $tableName = $t ? $t['table_number'] : '';
            }
            NotificationService::sendToRole(
                $businessId,
                'WAITER',
                NotificationService::TYPE_ORDER_NEW,
                Translator::t('notifications.notif_order_new'),
                Translator::t('notifications.notif_order_new_msg', ['number' => $orderNumber, 'table' => $tableName ?: '-']),
                ['order_id' => $orderId, 'order_number' => $orderNumber, 'table' => $tableName]
            );
            NotificationService::sendToRole(
                $businessId,
                'KITCHEN_STAFF',
                NotificationService::TYPE_ORDER_NEW,
                Translator::t('notifications.notif_order_new'),
                Translator::t('notifications.notif_order_new_msg', ['number' => $orderNumber, 'table' => $tableName ?: '-']),
                ['order_id' => $orderId, 'order_number' => $orderNumber]
            );

            Audit::record('create', 'order', (string)$orderId, null, ['number' => $number, 'total' => $total], $businessId);
            return Order::withItems($orderId);
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollBack();
            }
            throw $e;
        }
    }

    public static function updateStatus(int $orderId, string $status, ?int $businessId = null, ?int $actorId = null): bool
    {
        $order = Order::find($orderId);
        if (!$order) {
            return false;
        }
        $valid = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'partially_delivered', 'not_received', 'completed', 'cancelled'];
        if (!in_array($status, $valid, true)) {
            return false;
        }

        $changes = ['status' => $status];
        if ($status === 'confirmed') {
            $changes['confirmed_at'] = date('Y-m-d H:i:s');
        }
        if (in_array($status, ['delivered', 'completed'], true)) {
            $changes['completed_at'] = date('Y-m-d H:i:s');
            // Mark any items still pending as fully delivered only if they were delivered
        }
        if ($status === 'cancelled') {
            $changes['cancelled_at'] = date('Y-m-d H:i:s');
            // Restore stock
            self::restoreStockOnCancel($order);
            // Free the table
            if ($order['table_id']) {
                RestaurantTable::update((int)$order['table_id'], ['status' => 'available']);
            }
        }
        if ($status === 'completed') {
            if ($order['table_id']) {
                $t = RestaurantTable::find((int)$order['table_id']);
                if ($t && $t['status'] === 'occupied') {
                    RestaurantTable::update((int)$order['table_id'], ['status' => 'cleaning']);
                }
            }
        }

        Order::update($orderId, $changes);

        NotificationService::send(
            (int)($order['customer_user_id'] ?? 0) ?: (int)($order['waiter_user_id'] ?? 0),
            NotificationService::TYPE_ORDER_STATUS,
            Translator::t('notifications.notif_order_status'),
            Translator::t('notifications.notif_order_status_msg', ['number' => $order['order_number'], 'status' => $status]),
            (int)$order['business_id'],
            ['order_id' => $orderId, 'status' => $status]
        );

        Audit::record('update', 'order', (string)$orderId, ['status' => $order['status']], ['status' => $status], (int)$order['business_id']);
        return true;
    }

    /**
     * Record a delivery of quantities for an order item (waiter action).
     */
    public static function recordDelivery(int $orderItemId, float $quantity, int $deliveredBy, array $context): bool
    {
        $item = OrderItem::find($orderItemId);
        if (!$item) {
            return false;
        }
        if ((float)$item['delivered_quantity'] + $quantity > (float)$item['quantity'] + 0.0001) {
            throw new \RuntimeException('Cannot deliver more than the ordered quantity.');
        }

        OrderDelivery::insert([
            'order_item_id' => $orderItemId,
            'order_id' => $item['order_id'],
            'business_id' => $item['business_id'],
            'delivered_by_user_id' => $deliveredBy,
            'quantity' => $quantity,
            'notes' => $context['notes'] ?? null,
        ]);

        $newDelivered = (float)$item['delivered_quantity'] + $quantity;
        OrderItem::update($orderItemId, [
            'delivered_quantity' => $newDelivered,
            'delivery_status' => $newDelivered >= (float)$item['quantity'] - 0.0001 ? 'full' : 'partial',
        ]);

        self::recomputeOrderStatus((int)$item['order_id']);
        return true;
    }

    /**
     * Customer confirms receipt of quantities for an order item.
     * Returns the confirmation record.
     */
    public static function confirmReceipt(int $orderItemId, float $confirmedQty, ?int $customerUserId, ?string $notes): array
    {
        $item = OrderItem::find($orderItemId);
        if (!$item) {
            throw new \RuntimeException('Order item not found.');
        }
        if ($confirmedQty > (float)$item['quantity'] + 0.0001) {
            throw new \RuntimeException('Confirmed quantity cannot exceed the ordered quantity.');
        }
        if ($confirmedQty > (float)$item['delivered_quantity'] + 0.0001) {
            throw new \RuntimeException('Confirmed quantity cannot exceed the delivered quantity.');
        }

        $ordered = (float)$item['quantity'];
        $status = $confirmedQty >= $ordered - 0.0001 ? 'full' : ($confirmedQty <= 0.0001 ? 'not_received' : 'not_full');

        $id = DeliveryConfirmation::insert([
            'order_item_id' => $orderItemId,
            'order_id' => $item['order_id'],
            'business_id' => $item['business_id'],
            'customer_user_id' => $customerUserId,
            'ordered_quantity' => $ordered,
            'confirmed_quantity' => $confirmedQty,
            'status' => $status,
            'notes' => $notes,
        ]);

        // Update confirmed quantity (keep max cumulative)
        $newConfirmed = max((float)$item['confirmed_quantity'], $confirmedQty);
        OrderItem::update($orderItemId, ['confirmed_quantity' => $newConfirmed]);

        // Notify waiter + manager about missing items
        if ($status !== 'full') {
            $order = Order::find((int)$item['order_id']);
            $table = '';
            if ($order && $order['table_id']) {
                $t = RestaurantTable::find((int)$order['table_id']);
                $table = $t ? $t['table_number'] : '';
            }
            $waiterId = $order ? (int)$order['waiter_user_id'] : 0;
            $missingMsg = Translator::t('notifications.notif_missing_items_msg', ['number' => $order ? $order['order_number'] : '-']);

            $targets = array_filter([$waiterId]);
            NotificationService::sendToMany(
                $targets,
                NotificationService::TYPE_MISSING_ITEMS,
                Translator::t('notifications.notif_missing_items'),
                $missingMsg,
                (int)$item['business_id'],
                ['order_item_id' => $orderItemId, 'order_id' => $item['order_id']]
            );
            // Also notify managers
            NotificationService::sendToRole(
                (int)$item['business_id'],
                'MANAGER',
                NotificationService::TYPE_MISSING_ITEMS,
                Translator::t('notifications.notif_missing_items'),
                $missingMsg,
                ['order_item_id' => $orderItemId, 'order_id' => $item['order_id']]
            );
        }

        self::recomputeOrderStatus((int)$item['order_id']);
        return DeliveryConfirmation::find($id);
    }

    /** Recompute the aggregate order status from item delivery states. */
    public static function recomputeOrderStatus(int $orderId): void
    {
        $items = OrderItem::forOrder($orderId);
        if (empty($items)) {
            return;
        }

        $allFull = true;
        $anyPartial = false;
        $anyNotReceived = false;
        $allNotReceived = true;

        foreach ($items as $it) {
            $ds = $it['delivery_status'];
            if ($ds !== 'full' && $ds !== 'cancelled') {
                $allFull = false;
            }
            if ($ds === 'partial') {
                $anyPartial = true;
            }
            if ($ds === 'not_received') {
                $anyNotReceived = true;
            }
            if ($ds !== 'not_received' && $ds !== 'cancelled') {
                $allNotReceived = false;
            }
        }

        if ($allFull) {
            $newStatus = 'delivered';
        } elseif ($anyPartial) {
            $newStatus = 'partially_delivered';
        } elseif ($allNotReceived) {
            $newStatus = 'not_received';
        } elseif ($anyNotReceived) {
            $newStatus = 'partially_delivered';
        } else {
            return; // still in progress
        }

        $order = Order::find($orderId);
        if ($order && !in_array($order['status'], ['cancelled', 'completed'], true)) {
            Order::update($orderId, [
                'status' => $newStatus,
                'completed_at' => $newStatus === 'delivered' ? date('Y-m-d H:i:s') : null,
            ]);
        }
    }

    /** Request table clearing from the customer. */
    public static function requestTableClearing(int $orderId, ?int $customerUserId, ?string $notes): bool
    {
        $order = Order::find($orderId);
        if (!$order || !$order['table_id']) {
            return false;
        }
        $businessId = (int)$order['business_id'];
        $table = RestaurantTable::find((int)$order['table_id']);
        $tableName = $table ? $table['table_number'] : '-';

        RestaurantTable::update((int)$order['table_id'], ['status' => 'cleaning']);

        $waiterId = (int)$order['waiter_user_id'];
        NotificationService::sendToMany(
            array_filter([$waiterId]),
            NotificationService::TYPE_TABLE_CLEARING,
            Translator::t('notifications.notif_table_clearing'),
            Translator::t('notifications.notif_table_clearing_msg', ['table' => $tableName]),
            $businessId,
            ['order_id' => $orderId, 'table_id' => $order['table_id']]
        );
        NotificationService::sendToRole(
            $businessId,
            'MANAGER',
            NotificationService::TYPE_TABLE_CLEARING,
            Translator::t('notifications.notif_table_clearing'),
            Translator::t('notifications.notif_table_clearing_msg', ['table' => $tableName]),
            ['order_id' => $orderId, 'table_id' => $order['table_id']]
        );

        Audit::record('request_table_clearing', 'order', (string)$orderId, null, ['notes' => $notes], $businessId);
        return true;
    }

    /** Waiter/manager confirms the table has been cleaned. */
    public static function confirmTableCleared(int $orderId, ?int $actorId): bool
    {
        $order = Order::find($orderId);
        if (!$order || !$order['table_id']) {
            return false;
        }
        RestaurantTable::update((int)$order['table_id'], ['status' => 'available']);
        $table = RestaurantTable::find((int)$order['table_id']);
        $tableName = $table ? $table['table_number'] : '-';

        $waiterId = (int)$order['waiter_user_id'];
        NotificationService::sendToMany(
            array_filter([$waiterId, (int)$order['customer_user_id']]),
            NotificationService::TYPE_TABLE_CLEARED,
            Translator::t('notifications.notif_table_cleared'),
            Translator::t('notifications.notif_table_cleared_msg', ['table' => $tableName]),
            (int)$order['business_id'],
            ['order_id' => $orderId]
        );

        Audit::record('table_cleared', 'order', (string)$orderId, null, null, (int)$order['business_id']);
        return true;
    }

    /** Record a customer rating for a completed order. */
    public static function rateOrder(int $orderId, array $rating, ?int $customerUserId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            throw new \RuntimeException('Order not found.');
        }
        $existing = Database::run(
            'SELECT id FROM reviews WHERE business_id = ? AND service_type = "restaurant_order" AND service_id = ? LIMIT 1',
            [(int)$order['business_id'], $orderId]
        )->fetch();

        $data = [
            'business_id' => (int)$order['business_id'],
            'service_type' => 'restaurant_order',
            'service_id' => $orderId,
            'customer_user_id' => $customerUserId,
            'food_quality' => (int)($rating['food_quality'] ?? $rating['overall_rating']),
            'service_rating' => (int)($rating['service_rating'] ?? $rating['overall_rating']),
            'speed_rating' => (int)($rating['speed_rating'] ?? $rating['overall_rating']),
            'overall_rating' => (int)$rating['overall_rating'],
            'comment' => $rating['comment'] ?? null,
        ];

        if ($existing) {
            \App\Models\Review::update((int)$existing['id'], $data);
            $reviewId = (int)$existing['id'];
        } else {
            $reviewId = \App\Models\Review::insert($data);
            // Notify manager
            NotificationService::sendToRole(
                (int)$order['business_id'],
                'MANAGER',
                NotificationService::TYPE_REVIEW,
                Translator::t('notifications.notif_review'),
                Translator::t('notifications.notif_review_msg', ['rating' => (int)$rating['overall_rating']]),
                ['order_id' => $orderId, 'rating' => (int)$rating['overall_rating']]
            );
        }

        // Mark order completed
        if (!in_array($order['status'], ['completed'], true)) {
            Order::update($orderId, ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
        }

        Audit::record('rate', 'review', (string)$reviewId, null, $rating, (int)$order['business_id']);
        return \App\Models\Review::find($reviewId);
    }

    private static function decrementStock(int $businessId, int $productId, float $quantity, int $orderId): void
    {
        $product = Product::find($productId);
        if (!$product || (int)$product['business_id'] !== $businessId) {
            return;
        }
        $newQty = max(0, (float)$product['stock_quantity'] - $quantity);
        Product::update($productId, ['stock_quantity' => $newQty]);
        \App\Models\InventoryMovement::insert([
            'business_id' => $businessId,
            'product_id' => $productId,
            'type' => 'order',
            'quantity' => $quantity,
            'reference' => 'ORDER-' . $orderId,
            'notes' => 'Stock decremented for order',
        ]);

        // Low stock alert
        if ((float)$product['min_stock'] > 0 && $newQty <= (float)$product['min_stock']) {
            $managers = Database::run(
                'SELECT user_id FROM business_users WHERE business_id = ? AND role IN ("MANAGER","BUSINESS_OWNER") AND is_active = 1',
                [$businessId]
            )->fetchAll(\PDO::FETCH_COLUMN);
            NotificationService::sendToMany(
                $managers,
                NotificationService::TYPE_LOW_STOCK,
                Translator::t('notifications.notif_low_stock'),
                Translator::t('notifications.notif_low_stock_msg', ['product' => $product['name']]),
                $businessId,
                ['product_id' => $productId, 'stock' => $newQty]
            );
        }
    }

    private static function restoreStockOnCancel(array $order): void
    {
        $items = OrderItem::forOrder((int)$order['id']);
        foreach ($items as $it) {
            if ((int)$it['product_id'] <= 0) {
                continue;
            }
            $product = Product::find((int)$it['product_id']);
            if ($product) {
                Product::update((int)$it['product_id'], [
                    'stock_quantity' => (float)$product['stock_quantity'] + (float)$it['quantity'],
                ]);
            }
        }
    }
}
