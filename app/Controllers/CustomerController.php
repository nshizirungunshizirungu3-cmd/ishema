<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Translator;
use App\Models\Category;
use App\Models\DeliveryConfirmation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\RestaurantTable;
use App\Models\Review;
use App\Models\User;
use App\Services\OrderService;

/**
 * Customer QR ordering portal (public, token-authenticated).
 */
class CustomerController extends Controller
{
    /**
     * Entry point when scanning a table QR code.
     */
    public function qr(string $token): void
    {
        $qr = QrCode::byToken($token);
        if (!$qr) {
            echo $this->view('customer/invalid-qr', [], false);
            return;
        }
        $business = Database::run(
            'SELECT b.* FROM businesses b WHERE b.id = ? AND b.deleted_at IS NULL AND b.status = "active" LIMIT 1',
            [(int)$qr['business_id']]
        )->fetch();
        if (!$business) {
            echo $this->view('customer/invalid-qr', [], false);
            return;
        }
        $table = RestaurantTable::find((int)$qr['table_id']);
        $categories = Category::forBusiness((int)$business['id']);
        $products = Product::forBusiness((int)$business['id'], null, true);

        $cart = Session::get('qr_cart', []);
        $lang = Translator::locale();

        echo $this->view('customer/menu', [
            'business' => $business,
            'table' => $table,
            'token' => $token,
            'categories' => $categories,
            'products' => $products,
            'cart' => $cart,
            'lang' => $lang,
        ], false);
    }

    /** AJAX: add product to cart. */
    public function addToCart(): void
    {
        $productId = (int)Security::input('product_id', 0);
        $quantity = (float)Security::input('quantity', 1);
        $token = (string)Security::input('token', '');

        if ($productId <= 0 || $quantity <= 0) {
            $this->json(['ok' => false, 'error' => Translator::t('customer.invalid_quantity')], 422);
            return;
        }
        $product = Product::find($productId);
        if (!$product || !$product['is_available'] || $product['deleted_at'] !== null) {
            $this->json(['ok' => false, 'error' => Translator::t('customer.unavailable_item')], 422);
            return;
        }
        if ((float)$product['stock_quantity'] <= 0) {
            $this->json(['ok' => false, 'error' => Translator::t('customer.out_of_stock')], 422);
            return;
        }

        $cart = Session::get('qr_cart', []);
        $key = (string)$productId;
        if (isset($cart[$key])) {
            $newQty = (float)$cart[$key]['quantity'] + $quantity;
            if ($newQty > (float)$product['stock_quantity']) {
                $this->json(['ok' => false, 'error' => Translator::t('customer.max_reached')], 422);
                return;
            }
            $cart[$key]['quantity'] = $newQty;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'quantity' => $quantity,
            ];
        }
        Session::set('qr_cart', $cart);
        $this->json(['ok' => true, 'cart' => $this->cartSummary($cart)]);
    }

    public function updateCart(): void
    {
        $productId = (int)Security::input('product_id', 0);
        $quantity = (float)Security::input('quantity', 0);
        $cart = Session::get('qr_cart', []);
        $key = (string)$productId;
        if ($quantity <= 0) {
            unset($cart[$key]);
        } else {
            $product = Product::find($productId);
            if ($product && $quantity > (float)$product['stock_quantity']) {
                $this->json(['ok' => false, 'error' => Translator::t('customer.max_reached')], 422);
                return;
            }
            $cart[$key]['quantity'] = $quantity;
        }
        Session::set('qr_cart', $cart);
        $this->json(['ok' => true, 'cart' => $this->cartSummary($cart)]);
    }

    public function clearCart(): void
    {
        Session::delete('qr_cart');
        $this->json(['ok' => true]);
    }

    private function cartSummary(array $cart): array
    {
        $count = 0;
        $total = 0.0;
        foreach ($cart as $item) {
            $count += (int)$item['quantity'];
            $total += (float)$item['quantity'] * (float)$item['price'];
        }
        return ['count' => $count, 'total' => $total, 'items' => array_values($cart)];
    }

    /** Checkout form step. */
    public function checkout(string $token): void
    {
        $qr = QrCode::byToken($token);
        if (!$qr) {
            echo $this->view('customer/invalid-qr', [], false);
            return;
        }
        $cart = Session::get('qr_cart', []);
        if (empty($cart)) {
            Session::flash('error', Translator::t('customer.min_one'));
            header('Location: ' . Router::url('/qr/' . $token));
            exit;
        }
        $business = Database::run('SELECT * FROM businesses WHERE id = ? LIMIT 1', [(int)$qr['business_id']])->fetch();
        $table = RestaurantTable::find((int)$qr['table_id']);

        // Find available waiters for this business
        $waiters = \App\Models\User::businessUsers((int)$business['id'], 'WAITER');
        $cartData = $this->cartSummary($cart);

        echo $this->view('customer/checkout', [
            'business' => $business,
            'table' => $table,
            'token' => $token,
            'waiters' => $waiters,
            'cart' => $cartData,
        ], false);
    }

    /** Place the order. */
    public function placeOrder(string $token): void
    {
        $qr = QrCode::byToken($token);
        if (!$qr) {
            $this->json(['ok' => false, 'error' => Translator::t('customer.invalid_qr')], 400);
            return;
        }
        $cart = Session::get('qr_cart', []);
        if (empty($cart)) {
            $this->json(['ok' => false, 'error' => Translator::t('customer.min_one')], 422);
            return;
        }

        $business = Database::run(
            'SELECT * FROM businesses WHERE id = ? AND status = "active" AND deleted_at IS NULL LIMIT 1',
            [(int)$qr['business_id']]
        )->fetch();
        if (!$business) {
            $this->json(['ok' => false, 'error' => Translator::t('customer.restaurant_closed')], 422);
            return;
        }

        $guestName = (string)Security::input('guest_name', '');
        $guestPhone = (string)Security::input('guest_phone', '');
        $waiterMode = (string)Security::input('waiter_mode', 'auto');
        $waiterId = (int)Security::input('waiter_user_id', 0) ?: null;
        $notes = (string)Security::input('order_notes', '');

        if ($guestName === '') {
            $this->json(['ok' => false, 'error' => 'Please enter your name.'], 422);
            return;
        }

        // Automatic waiter assignment: least-loaded active waiter
        if ($waiterMode === 'auto' || !$waiterId) {
            $waiterId = $this->autoAssignWaiter((int)$business['id']);
        }

        // Create or find a customer account by phone for order tracking
        $customerUserId = null;
        if ($guestPhone !== '') {
            $existing = Database::run(
                'SELECT u.id FROM users u
                 JOIN business_users bu ON bu.user_id = u.id
                 WHERE u.phone = ? AND u.role = "CUSTOMER" AND bu.business_id = ? AND u.deleted_at IS NULL
                 LIMIT 1',
                [$guestPhone, (int)$business['id']]
            )->fetch();
            if ($existing) {
                $customerUserId = (int)$existing['id'];
            } else {
                $customerUserId = User::insert([
                    'name' => $guestName,
                    'email' => 'guest-' . uniqid() . '@ishema.local',
                    'phone' => $guestPhone,
                    'password' => password_hash(\App\Core\Security::randomString(16), PASSWORD_DEFAULT),
                    'role' => 'CUSTOMER',
                    'is_active' => 1,
                ]);
                \App\Models\BusinessUser::insert([
                    'user_id' => $customerUserId,
                    'business_id' => (int)$business['id'],
                    'role' => 'CUSTOMER',
                    'is_active' => 1,
                ]);
            }
        }

        $items = [];
        foreach ($cart as $entry) {
            $items[] = [
                'product_id' => (int)$entry['product_id'],
                'quantity' => (float)$entry['quantity'],
                'notes' => '',
            ];
        }

        try {
            $order = OrderService::createOrder([
                'business_id' => (int)$business['id'],
                'table_id' => (int)$qr['table_id'],
                'guest_name' => $guestName,
                'guest_phone' => $guestPhone,
                'waiter_user_id' => $waiterId,
                'customer_user_id' => $customerUserId,
                'source' => 'qr',
                'notes' => $notes,
                'language' => Translator::locale(),
            ], $items);

            Session::delete('qr_cart');

            $this->json([
                'ok' => true,
                'order' => [
                    'id' => (int)$order['id'],
                    'order_number' => $order['order_number'],
                    'total_amount' => (float)$order['total_amount'],
                    'table_number' => $table ? $table['table_number'] : null,
                ],
                'redirect' => Router::url('/order/' . $order['order_number'] . '/' . ($guestPhone !== '' ? '?phone=' . urlencode($guestPhone) : '')),
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    private function autoAssignWaiter(int $businessId): ?int
    {
        $waiters = \App\Models\User::businessUsers($businessId, 'WAITER');
        if (empty($waiters)) {
            return null;
        }
        // Pick the waiter with the fewest active orders
        $best = null;
        $bestLoad = PHP_INT_MAX;
        foreach ($waiters as $w) {
            $load = (int)Database::run(
                'SELECT COUNT(*) AS c FROM orders
                 WHERE business_id = ? AND waiter_user_id = ? AND status IN ("pending","confirmed","preparing","ready")',
                [$businessId, (int)$w['user_id']]
            )->fetch()['c'];
            if ($load < $bestLoad) {
                $bestLoad = $load;
                $best = (int)$w['user_id'];
            }
        }
        return $best;
    }

    /** Track order page. */
    public function track(string $orderNumber): void
    {
        $order = Order::query()->where('order_number', $orderNumber)->first();
        if (!$order) {
            echo $this->view('customer/track-invalid', [], false);
            return;
        }
        $phone = (string)Security::input('phone', '');
        $business = Database::run('SELECT * FROM businesses WHERE id = ? LIMIT 1', [(int)$order['business_id']])->fetch();
        $table = $order['table_id'] ? RestaurantTable::find((int)$order['table_id']) : null;
        $items = OrderItem::forOrder((int)$order['id']);
        $deliveries = [];
        $confirmations = [];
        foreach ($items as $item) {
            $deliveries[(int)$item['id']] = \App\Models\OrderDelivery::forItem((int)$item['id']);
            $confirmations[(int)$item['id']] = DeliveryConfirmation::forItem((int)$item['id']);
        }
        $canConfirm = $this->canAccess($order, $phone);
        $review = Review::query()
            ->where('business_id', (int)$order['business_id'])
            ->where('service_type', 'restaurant_order')
            ->where('service_id', (int)$order['id'])
            ->first();

        echo $this->view('customer/track', [
            'order' => $order,
            'business' => $business,
            'table' => $table,
            'items' => $items,
            'deliveries' => $deliveries,
            'confirmations' => $confirmations,
            'phone' => $phone,
            'canConfirm' => $canConfirm,
            'review' => $review,
        ], false);
    }

    private function canAccess(array $order, string $phone): bool
    {
        // Access via phone match or authenticated customer ownership
        if ($phone !== '' && $phone === (string)$order['guest_phone']) {
            return true;
        }
        $user = \App\Core\Auth::user();
        if ($user && (int)$order['customer_user_id'] === (int)$user['id']) {
            return true;
        }
        return false;
    }

    /** AJAX: confirm receipt for an order item. */
    public function confirmReceipt(): void
    {
        $orderItemId = (int)Security::input('order_item_id', 0);
        $quantity = (float)Security::input('quantity', 0);
        $notes = (string)Security::input('notes', '');

        $item = OrderItem::find($orderItemId);
        if (!$item) {
            $this->json(['ok' => false, 'error' => 'Item not found.'], 404);
            return;
        }
        $order = Order::find((int)$item['order_id']);
        if (!$order) {
            $this->json(['ok' => false, 'error' => 'Order not found.'], 404);
            return;
        }
        if (!$this->canAccess($order, (string)Security::input('phone', ''))) {
            $this->json(['ok' => false, 'error' => 'Access denied.'], 403);
            return;
        }
        if ($quantity > (float)$item['quantity'] + 0.0001) {
            $this->json(['ok' => false, 'error' => Translator::t('customer.max_reached')], 422);
            return;
        }

        try {
            $confirmation = OrderService::confirmReceipt(
                $orderItemId,
                $quantity,
                \App\Core\Auth::id(),
                $notes !== '' ? $notes : null
            );
            $this->json([
                'ok' => true,
                'message' => Translator::t('customer.received_confirmed'),
                'status' => $confirmation['status'],
            ]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** AJAX: request table clearing. */
    public function requestClearing(): void
    {
        $orderId = (int)Security::input('order_id', 0);
        $order = Order::find($orderId);
        if (!$order) {
            $this->json(['ok' => false, 'error' => 'Order not found.'], 404);
            return;
        }
        if (!$this->canAccess($order, (string)Security::input('phone', ''))) {
            $this->json(['ok' => false, 'error' => 'Access denied.'], 403);
            return;
        }
        $ok = OrderService::requestTableClearing($orderId, \App\Core\Auth::id(), (string)Security::input('notes', ''));
        if ($ok) {
            $this->json(['ok' => true, 'message' => Translator::t('customer.table_clearing_requested')]);
        }
        $this->json(['ok' => false, 'error' => 'Unable to process request.'], 422);
    }

    /** AJAX: submit rating. */
    public function rate(): void
    {
        $orderId = (int)Security::input('order_id', 0);
        $overall = (int)Security::input('overall_rating', 0);
        $order = Order::find($orderId);
        if (!$order) {
            $this->json(['ok' => false, 'error' => 'Order not found.'], 404);
            return;
        }
        if (!$this->canAccess($order, (string)Security::input('phone', ''))) {
            $this->json(['ok' => false, 'error' => 'Access denied.'], 403);
            return;
        }
        if ($overall < 1 || $overall > 5) {
            $this->json(['ok' => false, 'error' => 'Invalid rating.'], 422);
            return;
        }
        try {
            OrderService::rateOrder($orderId, [
                'food_quality' => (int)Security::input('food_quality', $overall),
                'service_rating' => (int)Security::input('service_rating', $overall),
                'speed_rating' => (int)Security::input('speed_rating', $overall),
                'overall_rating' => $overall,
                'comment' => (string)Security::input('comment', ''),
            ], \App\Core\Auth::id());
            $this->json(['ok' => true, 'message' => Translator::t('customer.thank_you_message')]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Polling endpoint for live order status. */
    public function pollStatus(string $orderNumber): void
    {
        $order = Order::query()->where('order_number', $orderNumber)->first();
        if (!$order) {
            $this->json(['ok' => false], 404);
            return;
        }
        $items = OrderItem::forOrder((int)$order['id']);
        $summary = [];
        foreach ($items as $item) {
            $summary[] = [
                'id' => (int)$item['id'],
                'product_name' => $item['product_name'],
                'quantity' => (float)$item['quantity'],
                'delivered_quantity' => (float)$item['delivered_quantity'],
                'confirmed_quantity' => (float)$item['confirmed_quantity'],
                'delivery_status' => $item['delivery_status'],
            ];
        }
        $this->json([
            'ok' => true,
            'status' => $order['status'],
            'items' => $summary,
        ]);
    }

    /** View printable receipt. */
    public function receipt(string $orderNumber): void
    {
        $order = Order::query()->where('order_number', $orderNumber)->first();
        if (!$order) {
            echo $this->view('customer/track-invalid', [], false);
            return;
        }
        $business = Database::run('SELECT * FROM businesses WHERE id = ? LIMIT 1', [(int)$order['business_id']])->fetch();
        $table = $order['table_id'] ? RestaurantTable::find((int)$order['table_id']) : null;
        $items = OrderItem::forOrder((int)$order['id']);
        echo $this->view('customer/receipt', [
            'order' => $order,
            'business' => $business,
            'table' => $table,
            'items' => $items,
        ], false);
    }
}
