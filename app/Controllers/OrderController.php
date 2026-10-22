<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Tenant;
use App\Core\Translator;
use App\Models\DeliveryConfirmation;
use App\Models\Order;
use App\Models\OrderDelivery;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function list(): void
    {
        $business = Tenant::requireBusiness();
        $status = Security::input('status', 'all');
        $search = (string)Security::input('search', '');
        $q = Order::query()->where('business_id', (int)$business['id'])->orderBy('id', 'DESC');
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        if ($search !== '') {
            $q->whereLike('order_number', $search);
        }
        $orders = $q->limit(200)->get();
        $tables = RestaurantTable::forBusiness((int)$business['id']);
        $waiters = \App\Models\User::businessUsers((int)$business['id'], 'WAITER');
        echo $this->view('restaurant/orders', [
            'orders' => $orders,
            'status' => $status,
            'search' => $search,
            'tables' => $tables,
            'waiters' => $waiters,
        ]);
    }

    public function create(): void
    {
        $business = Tenant::requireBusiness();
        $categories = \App\Models\Category::forBusiness((int)$business['id']);
        $products = \App\Models\Product::forBusiness((int)$business['id'], null, true);
        $tables = RestaurantTable::forBusiness((int)$business['id']);
        $waiters = \App\Models\User::businessUsers((int)$business['id'], 'WAITER');
        echo $this->view('restaurant/order-form', [
            'categories' => $categories,
            'products' => $products,
            'tables' => $tables,
            'waiters' => $waiters,
            'order' => null,
        ]);
    }

    public function store(): void
    {
        $business = Tenant::requireBusiness();
        $tableId = (int)Security::input('table_id', 0) ?: null;
        $waiterId = (int)Security::input('waiter_user_id', 0) ?: null;
        $productIds = (array)Security::input('product_ids', []);
        $quantities = (array)Security::input('quantities', []);
        $notes = (array)Security::input('item_notes', []);

        if ($tableId === null && (string)Security::input('guest_name', '') === '') {
            Session::flash('error', 'Select a table or enter guest details.');
            $this->redirect('/orders/create');
            return;
        }
        if (empty($productIds)) {
            Session::flash('error', 'Add at least one product to the order.');
            $this->redirect('/orders/create');
            return;
        }

        $items = [];
        foreach ($productIds as $i => $productId) {
            $productId = (int)$productId;
            if ($productId <= 0) {
                continue;
            }
            $qty = (float)($quantities[$i] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }
            $items[] = [
                'product_id' => $productId,
                'quantity' => $qty,
                'notes' => (string)($notes[$i] ?? ''),
            ];
        }

        if (empty($items)) {
            Session::flash('error', 'Add at least one product to the order.');
            $this->redirect('/orders/create');
            return;
        }

        try {
            $order = OrderService::createOrder([
                'business_id' => (int)$business['id'],
                'table_id' => $tableId,
                'guest_name' => (string)Security::input('guest_name', ''),
                'guest_phone' => (string)Security::input('guest_phone', ''),
                'waiter_user_id' => $waiterId,
                'source' => 'staff',
                'notes' => (string)Security::input('notes', ''),
                'language' => Translator::locale(),
            ], $items);
            Session::flash('message', Translator::t('restaurant.order_created') . ' #' . $order['order_number']);
            $this->redirect('/orders/' . (int)$order['id']);
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/orders/create');
        }
    }

    public function show(int $id): void
    {
        $business = Tenant::requireBusiness();
        $order = Order::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$order) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/orders');
            return;
        }
        $items = OrderItem::forOrder($id);
        $deliveries = [];
        $confirmations = [];
        foreach ($items as $item) {
            $deliveries[(int)$item['id']] = OrderDelivery::forItem((int)$item['id']);
            $confirmations[(int)$item['id']] = DeliveryConfirmation::forItem((int)$item['id']);
        }
        $waiters = \App\Models\User::businessUsers((int)$business['id'], 'WAITER');
        echo $this->view('restaurant/order-view', [
            'order' => $order,
            'items' => $items,
            'deliveries' => $deliveries,
            'confirmations' => $confirmations,
            'waiters' => $waiters,
        ]);
    }

    public function updateStatus(int $id): void
    {
        $business = Tenant::requireBusiness();
        $order = Order::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$order) {
            $this->json(['ok' => false, 'error' => 'Not found.'], 404);
            return;
        }
        $status = (string)Security::input('status', '');
        $waiter = (int)Security::input('waiter_user_id', 0);
        if ($waiter > 0) {
            Order::update($id, ['waiter_user_id' => $waiter]);
        }
        try {
            OrderService::updateStatus($id, $status, (int)$business['id'], \App\Core\Auth::id());
            $this->json(['ok' => true, 'message' => Translator::t('restaurant.order_updated'), 'status' => $status]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Waiter records delivery of item quantities. */
    public function recordDelivery(int $id): void
    {
        $business = Tenant::requireBusiness();
        $item = OrderItem::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$item) {
            $this->json(['ok' => false, 'error' => 'Not found.'], 404);
            return;
        }
        $quantity = (float)Security::input('quantity', 0);
        $notes = (string)Security::input('notes', '');
        if ($quantity <= 0) {
            $this->json(['ok' => false, 'error' => 'Enter a quantity.'], 422);
            return;
        }
        try {
            OrderService::recordDelivery($id, $quantity, (int)\App\Core\Auth::id(), ['notes' => $notes]);
            $this->json(['ok' => true, 'message' => 'Delivery recorded.']);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Kitchen queue: orders currently being prepared. */
    public function kitchen(): void
    {
        $business = Tenant::requireBusiness();
        $orders = Database::run(
            'SELECT o.*, rt.table_number FROM orders o
             LEFT JOIN restaurant_tables rt ON rt.id = o.table_id
             WHERE o.business_id = ? AND o.status IN ("pending","confirmed","preparing")
             ORDER BY o.ordered_at ASC',
            [(int)$business['id']]
        )->fetchAll();
        $orderIds = array_map(fn($o) => (int)$o['id'], $orders);
        $items = [];
        if (!empty($orderIds)) {
            $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
            $items = Database::run(
                'SELECT * FROM order_items WHERE order_id IN (' . $placeholders . ') ORDER BY id',
                $orderIds
            )->fetchAll();
        }
        echo $this->view('restaurant/kitchen', ['orders' => $orders, 'items' => $items]);
    }

    /** Unresolved customer complaints (not_full / not_received). */
    public function complaints(): void
    {
        $business = Tenant::requireBusiness();
        $complaints = DeliveryConfirmation::openComplaints((int)$business['id']);
        echo $this->view('restaurant/complaints', ['complaints' => $complaints]);
    }

    /** Waiter confirms table cleared. */
    public function confirmCleared(int $id): void
    {
        $business = Tenant::requireBusiness();
        $order = Order::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$order) {
            $this->json(['ok' => false, 'error' => 'Not found.'], 404);
            return;
        }
        OrderService::confirmTableCleared($id, \App\Core\Auth::id());
        $this->json(['ok' => true, 'message' => 'Table marked as cleaned.']);
    }

    /** Print order receipt (thermal-friendly view). */
    public function receipt(int $id): void
    {
        $business = Tenant::requireBusiness();
        $order = Order::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$order) {
            $this->json(['ok' => false], 404);
            return;
        }
        $items = OrderItem::forOrder($id);
        echo $this->view('restaurant/receipt', ['order' => $order, 'items' => $items, 'business' => $business], false);
    }
}
