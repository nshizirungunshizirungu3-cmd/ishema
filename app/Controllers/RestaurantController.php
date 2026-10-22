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
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\RestaurantTable;
use App\Models\Shift;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReportService;

class RestaurantController extends Controller
{
    public function overview(): void
    {
        $business = Tenant::requireBusiness();
        $stats = ReportService::businessOverview((int)$business['id'], (string)$business['type']);
        $salesTrend = ReportService::salesTrend((int)$business['id'], 14);
        echo $this->view('restaurant/overview', [
            'business' => $business,
            'stats' => $stats,
            'salesTrend' => $salesTrend,
        ]);
    }

    // -------------------- PRODUCTS --------------------

    public function products(): void
    {
        $business = Tenant::requireBusiness();
        $categoryId = (int)Security::input('category_id', 0) ?: null;
        $search = (string)Security::input('search', '');
        $q = Product::query()->where('business_id', (int)$business['id'])->whereNull('deleted_at')->orderBy('sort_order')->orderBy('id', 'DESC');
        if ($categoryId) {
            $q->where('category_id', $categoryId);
        }
        if ($search !== '') {
            $q->whereLike('name', $search);
        }
        $products = $q->limit(300)->get();
        $categories = Category::forBusiness((int)$business['id']);
        $lowStock = Product::lowStock((int)$business['id']);
        echo $this->view('restaurant/products', [
            'products' => $products,
            'categories' => $categories,
            'lowStock' => $lowStock,
            'selectedCategory' => $categoryId,
            'search' => $search,
        ]);
    }

    public function createProduct(): void
    {
        $business = Tenant::requireBusiness();
        $categories = Category::forBusiness((int)$business['id']);
        echo $this->view('restaurant/product-form', ['categories' => $categories, 'product' => null]);
    }

    public function editProduct(int $id): void
    {
        $business = Tenant::requireBusiness();
        $product = Product::query()->where('id', $id)->where('business_id', (int)$business['id'])->whereNull('deleted_at')->first();
        if (!$product) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/restaurant/products');
            return;
        }
        $categories = Category::forBusiness((int)$business['id']);
        echo $this->view('restaurant/product-form', ['categories' => $categories, 'product' => $product]);
    }

    public function storeProduct(): void
    {
        $this->saveProduct(0);
    }

    public function updateProduct(int $id): void
    {
        $this->saveProduct($id);
    }

    private function saveProduct(int $id): void
    {
        $business = Tenant::requireBusiness();
        $name = (string)Security::input('name', '');
        if ($name === '') {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect($id === 0 ? '/restaurant/products/create' : '/restaurant/products/' . $id . '/edit');
            return;
        }

        $image = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $image = Security::moveImageUpload($_FILES['image'], 'products');
            } catch (\Throwable $e) {
                Session::flash('error', $e->getMessage());
                $this->redirect($id === 0 ? '/restaurant/products/create' : '/restaurant/products/' . $id . '/edit');
                return;
            }
        }

        // Package limit check
        $this->enforceProductLimit((int)$business['id']);

        $data = [
            'category_id' => (int)Security::input('category_id', 0) ?: null,
            'name' => $name,
            'description' => (string)Security::input('description', ''),
            'price' => (float)Security::input('price', 0),
            'cost_price' => (float)Security::input('cost_price', 0),
            'stock_quantity' => (float)Security::input('stock_quantity', 0),
            'min_stock' => (float)Security::input('min_stock', 0),
            'barcode' => (string)Security::input('barcode', '') ?: null,
            'is_available' => Security::input('is_available') ? 1 : 0,
            'sort_order' => (int)Security::input('sort_order', 0),
        ];
        if ($image) {
            $data['image'] = $image;
        }

        if ($id === 0) {
            $data['business_id'] = (int)$business['id'];
            Product::insert($data);
            Session::flash('message', Translator::t('common.create_success'));
        } else {
            $product = Product::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
            if (!$product) {
                Session::flash('error', Translator::t('common.no_records'));
                $this->redirect('/restaurant/products');
                return;
            }
            Product::update($id, $data);
            Session::flash('message', Translator::t('common.update_success'));
        }
        $this->redirect('/restaurant/products');
    }

    public function deleteProduct(int $id): void
    {
        $business = Tenant::requireBusiness();
        $product = Product::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if ($product) {
            Product::softDelete($id);
            $this->json(['ok' => true]);
        }
        $this->json(['ok' => false, 'error' => Translator::t('common.no_records')], 404);
    }

    private function enforceProductLimit(int $businessId): void
    {
        $sub = \App\Models\Subscription::currentForBusiness($businessId);
        if (!$sub) {
            return;
        }
        $package = \App\Models\Package::find((int)$sub['package_id']);
        if (!$package) {
            return;
        }
        $limit = (int)$package['max_products'];
        if ($limit > 0) {
            $count = Product::countForBusiness($businessId);
            if ($count >= $limit) {
                throw new \RuntimeException('Your package allows a maximum of ' . $limit . ' products. Upgrade to add more.');
            }
        }
    }

    // -------------------- CATEGORIES --------------------

    public function categories(): void
    {
        $business = Tenant::requireBusiness();
        $categories = Category::query()->where('business_id', (int)$business['id'])->orderBy('sort_order')->orderBy('id')->get();
        echo $this->view('restaurant/categories', ['categories' => $categories]);
    }

    public function storeCategory(): void
    {
        $this->saveCategory(0);
    }

    public function updateCategory(int $id): void
    {
        $this->saveCategory($id);
    }

    private function saveCategory(int $id): void
    {
        $business = Tenant::requireBusiness();
        $name = (string)Security::input('name', '');
        if ($name === '') {
            $this->json(['ok' => false, 'error' => Translator::t('common.please_fill_required')], 422);
            return;
        }
        $slug = \App\Core\Security::slug($name);
        if (Category::slugExists((int)$business['id'], $slug, $id ?: null)) {
            $this->json(['ok' => false, 'error' => 'A category with this name already exists.'], 422);
            return;
        }
        $data = [
            'business_id' => (int)$business['id'],
            'name' => $name,
            'slug' => $slug ?: 'cat-' . uniqid(),
            'type' => in_array(Security::input('type', 'other'), ['food', 'drinks', 'desserts', 'breakfast', 'other'], true) ? Security::input('type', 'other') : 'other',
            'color' => (string)Security::input('color', '#1056A0'),
            'sort_order' => (int)Security::input('sort_order', 0),
            'is_active' => Security::input('is_active') ? 1 : 0,
        ];
        if ($id === 0) {
            Category::insert($data);
        } else {
            Category::update($id, $data);
        }
        $this->json(['ok' => true, 'message' => Translator::t('common.record_saved')]);
    }

    public function deleteCategory(int $id): void
    {
        $business = Tenant::requireBusiness();
        $category = Category::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if (!$category) {
            $this->json(['ok' => false, 'error' => 'Not found.'], 404);
            return;
        }
        $used = Product::query()->where('category_id', $id)->whereNull('deleted_at')->count();
        if ($used > 0) {
            $this->json(['ok' => false, 'error' => 'This category contains products. Move them first.'], 400);
            return;
        }
        Category::delete($id);
        $this->json(['ok' => true]);
    }

    // -------------------- TABLES --------------------

    public function tables(): void
    {
        $business = Tenant::requireBusiness();
        $tables = RestaurantTable::forBusiness((int)$business['id']);
        $waiters = User::businessUsers((int)$business['id'], 'WAITER');
        // Generate QR codes for all tables
        $qrPaths = [];
        foreach ($tables as $t) {
            $qr = QrCode::ensureForTable((int)$business['id'], (int)$t['id']);
            $qrPaths[(int)$t['id']] = \App\Core\QRService::png($this->qrPayload($qr['token']));
        }
        echo $this->view('restaurant/tables', [
            'tables' => $tables,
            'waiters' => $waiters,
            'qrPaths' => $qrPaths,
        ]);
    }

    private function qrPayload(string $token): string
    {
        $base = rtrim((string)(getenv('APP_URL') ?: 'http://localhost/ishema/public'), '/');
        return $base . '/qr/' . $token;
    }

    public function storeTable(): void
    {
        $this->saveTable(0);
    }

    public function updateTable(int $id): void
    {
        $this->saveTable($id);
    }

    private function saveTable(int $id): void
    {
        $business = Tenant::requireBusiness();
        $tableNumber = (string)Security::input('table_number', '');
        if ($tableNumber === '') {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect('/restaurant/tables');
            return;
        }
        $waiterId = (int)Security::input('waiter_user_id', 0) ?: null;

        $this->enforceTableLimit((int)$business['id']);

        $data = [
            'business_id' => (int)$business['id'],
            'table_number' => $tableNumber,
            'name' => (string)Security::input('name', ''),
            'seats' => (int)Security::input('seats', 2),
            'floor' => (string)Security::input('floor', ''),
            'waiter_user_id' => $waiterId,
            'status' => in_array(Security::input('status', 'available'), ['available', 'occupied', 'cleaning', 'reserved', 'out_of_service'], true) ? Security::input('status', 'available') : 'available',
            'sort_order' => (int)Security::input('sort_order', 0),
        ];

        // Unique table number within business
        $dup = Database::run(
            'SELECT id FROM restaurant_tables WHERE business_id = ? AND table_number = ?' . ($id ? ' AND id <> ?' : '') . ' LIMIT 1',
            array_merge([(int)$business['id'], $tableNumber], $id ? [$id] : [])
        )->fetch();
        if ($dup) {
            Session::flash('error', 'A table with this number already exists.');
            $this->redirect('/restaurant/tables');
            return;
        }

        if ($id === 0) {
            $id = RestaurantTable::insert($data);
            QrCode::ensureForTable((int)$business['id'], $id);
            Session::flash('message', Translator::t('common.create_success'));
        } else {
            $existing = RestaurantTable::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
            if (!$existing) {
                Session::flash('error', Translator::t('common.no_records'));
                $this->redirect('/restaurant/tables');
                return;
            }
            RestaurantTable::update($id, $data);
            Session::flash('message', Translator::t('common.update_success'));
        }
        $this->redirect('/restaurant/tables');
    }

    private function enforceTableLimit(int $businessId): void
    {
        $sub = \App\Models\Subscription::currentForBusiness($businessId);
        if (!$sub) {
            return;
        }
        $package = \App\Models\Package::find((int)$sub['package_id']);
        if (!$package) {
            return;
        }
        $limit = (int)$package['max_tables'];
        if ($limit > 0) {
            $count = RestaurantTable::where('business_id', $businessId)->where('is_active', 1)->count();
            if ($count >= $limit) {
                throw new \RuntimeException('Your package allows a maximum of ' . $limit . ' tables. Upgrade to add more.');
            }
        }
    }

    public function deleteTable(int $id): void
    {
        $business = Tenant::requireBusiness();
        $table = RestaurantTable::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if ($table) {
            RestaurantTable::update($id, ['is_active' => 0]);
            $this->json(['ok' => true]);
        }
        $this->json(['ok' => false, 'error' => 'Not found.'], 404);
    }

    public function downloadQr(int $tableId): void
    {
        $business = Tenant::requireBusiness();
        $qr = QrCode::forTable((int)$business['id'], $tableId);
        if (!$qr) {
            $this->json(['ok' => false], 404);
            return;
        }
        $path = \App\Core\QRService::png($this->qrPayload($qr['token']));
        $full = \App\Core\Router::url($path);
        header('Location: ' . $full);
        exit;
    }

    // -------------------- STAFF --------------------

    public function staff(): void
    {
        $business = Tenant::requireBusiness();
        $staff = User::businessUsers((int)$business['id']);
        $shifts = Shift::forBusiness((int)$business['id']);
        $assignments = \App\Models\StaffAssignment::forDate((int)$business['id'], date('Y-m-d'));
        echo $this->view('restaurant/staff', [
            'staff' => $staff,
            'shifts' => $shifts,
            'assignments' => $assignments,
            'today' => date('Y-m-d'),
        ]);
    }

    public function createEmployee(): void
    {
        $business = Tenant::requireBusiness();
        $tables = RestaurantTable::forBusiness((int)$business['id']);
        echo $this->view('restaurant/employee-form', ['employee' => null, 'tables' => $tables]);
    }

    public function editEmployee(int $id): void
    {
        $business = Tenant::requireBusiness();
        $link = Database::run(
            'SELECT bu.*, u.name, u.email, u.phone FROM business_users bu JOIN users u ON u.id = bu.user_id
             WHERE bu.id = ? AND bu.business_id = ? LIMIT 1',
            [$id, (int)$business['id']]
        )->fetch();
        if (!$link) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/restaurant/staff');
            return;
        }
        $tables = RestaurantTable::forBusiness((int)$business['id']);
        echo $this->view('restaurant/employee-form', ['employee' => $link, 'tables' => $tables]);
    }

    public function storeEmployee(): void
    {
        $this->saveEmployee(0);
    }

    public function updateEmployee(int $id): void
    {
        $this->saveEmployee($id);
    }

    private function saveEmployee(int $id): void
    {
        $business = Tenant::requireBusiness();
        $name = (string)Security::input('name', '');
        $email = (string)Security::input('email', '');
        $role = (string)Security::input('role', 'WAITER');
        if ($name === '' || !Security::isValidEmail($email)) {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect($id === 0 ? '/restaurant/staff/create' : '/restaurant/staff/' . $id . '/edit');
            return;
        }
        $validRoles = ['MANAGER', 'RECEPTIONIST', 'WAITER', 'KITCHEN_STAFF', 'CASHIER', 'HOUSEKEEPING'];
        if (!in_array($role, $validRoles, true)) {
            $role = 'WAITER';
        }

        $this->enforceEmployeeLimit((int)$business['id']);

        Database::beginTransaction();
        try {
            if ($id === 0) {
                $password = \App\Core\Security::randomString(10);
                $userId = User::insert([
                    'name' => $name,
                    'email' => mb_strtolower($email),
                    'phone' => Security::input('phone'),
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'role' => $role,
                    'is_active' => 1,
                ]);
                $assignedTables = (array)Security::input('assigned_tables', []);
                BusinessUser::insert([
                    'user_id' => $userId,
                    'business_id' => (int)$business['id'],
                    'role' => $role,
                    'permissions' => json_encode(['*']),
                    'assigned_tables' => json_encode(array_map('intval', $assignedTables)),
                    'is_active' => 1,
                ]);
                $linkId = (int)Database::lastId();

                \App\Core\NotificationService::send(
                    $userId,
                    \App\Core\NotificationService::TYPE_STAFF_ACCOUNT,
                    'Account created',
                    'Your staff account has been created for ' . $business['name'] . '. Password: ' . $password,
                    (int)$business['id'],
                    ['business_id' => (int)$business['id']]
                );
                $devPassword = $password;
            } else {
                $link = Database::run(
                    'SELECT bu.id, bu.user_id FROM business_users bu WHERE bu.id = ? AND bu.business_id = ? LIMIT 1',
                    [$id, (int)$business['id']]
                )->fetch();
                if (!$link) {
                    throw new \RuntimeException('Employee not found.');
                }
                User::update((int)$link['user_id'], [
                    'name' => $name,
                    'email' => mb_strtolower($email),
                    'phone' => Security::input('phone'),
                ]);
                $assignedTables = (array)Security::input('assigned_tables', []);
                Database::run(
                    'UPDATE business_users SET role = ?, assigned_tables = ? WHERE id = ?',
                    [$role, json_encode(array_map('intval', $assignedTables)), $id]
                );
                $linkId = $id;
            }
            Database::commit();
            Session::flash('message', Translator::t('common.record_saved'));
            if (isset($devPassword)) {
                Session::flash('generated_password', $devPassword);
                Session::flash('owner_email', $email);
            }
            $this->redirect('/restaurant/staff');
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollBack();
            }
            Session::flash('error', $e->getMessage());
            $this->redirect($id === 0 ? '/restaurant/staff/create' : '/restaurant/staff/' . $id . '/edit');
        }
    }

    private function enforceEmployeeLimit(int $businessId): void
    {
        $sub = \App\Models\Subscription::currentForBusiness($businessId);
        if (!$sub) {
            return;
        }
        $package = \App\Models\Package::find((int)$sub['package_id']);
        if (!$package) {
            return;
        }
        $limit = (int)$package['max_employees'];
        if ($limit > 0) {
            $count = (int)Database::run(
                'SELECT COUNT(*) AS c FROM business_users WHERE business_id = ? AND is_active = 1',
                [$businessId]
            )->fetch()['c'];
            if ($count >= $limit) {
                throw new \RuntimeException('Your package allows a maximum of ' . $limit . ' employees. Upgrade to add more.');
            }
        }
    }

    public function toggleEmployee(int $id): void
    {
        $business = Tenant::requireBusiness();
        $link = Database::run(
            'SELECT id, is_active FROM business_users WHERE id = ? AND business_id = ? LIMIT 1',
            [$id, (int)$business['id']]
        )->fetch();
        if (!$link) {
            $this->json(['ok' => false], 404);
            return;
        }
        $newStatus = $link['is_active'] ? 0 : 1;
        Database::run('UPDATE business_users SET is_active = ? WHERE id = ?', [$newStatus, $id]);
        $this->json(['ok' => true, 'is_active' => $newStatus]);
    }

    // -------------------- SHIFTS --------------------

    public function storeShift(): void
    {
        $business = Tenant::requireBusiness();
        $name = (string)Security::input('name', '');
        if ($name === '') {
            $this->json(['ok' => false, 'error' => 'Name required.'], 422);
            return;
        }
        Shift::insert([
            'business_id' => (int)$business['id'],
            'name' => $name,
            'start_time' => (string)Security::input('start_time', '08:00:00'),
            'end_time' => (string)Security::input('end_time', '17:00:00'),
        ]);
        $this->json(['ok' => true, 'message' => Translator::t('common.record_saved')]);
    }

    public function assignShift(): void
    {
        $business = Tenant::requireBusiness();
        $staffId = (int)Security::input('business_user_id', 0);
        $shiftId = (int)Security::input('shift_id', 0);
        $tableId = (int)Security::input('table_id', 0) ?: null;
        $date = (string)Security::input('assignment_date', date('Y-m-d'));
        if ($staffId <= 0) {
            $this->json(['ok' => false, 'error' => 'Select staff member.'], 422);
            return;
        }
        \App\Models\StaffAssignment::insert([
            'business_id' => (int)$business['id'],
            'business_user_id' => $staffId,
            'shift_id' => $shiftId ?: null,
            'table_id' => $tableId,
            'assignment_date' => $date,
            'notes' => (string)Security::input('notes', ''),
        ]);
        $this->json(['ok' => true, 'message' => Translator::t('common.record_saved')]);
    }

    // -------------------- INVENTORY --------------------

    public function inventory(): void
    {
        $business = Tenant::requireBusiness();
        $products = Product::forBusiness((int)$business['id']);
        $suppliers = Supplier::forBusiness((int)$business['id']);
        $movements = InventoryMovement::query()
            ->where('business_id', (int)$business['id'])
            ->orderBy('id', 'DESC')
            ->limit(100)
            ->get();
        echo $this->view('restaurant/inventory', [
            'products' => $products,
            'suppliers' => $suppliers,
            'movements' => $movements,
        ]);
    }

    public function stockMovement(): void
    {
        $business = Tenant::requireBusiness();
        $productId = (int)Security::input('product_id', 0);
        $type = (string)Security::input('type', 'in');
        $quantity = (float)Security::input('quantity', 0);
        $reference = (string)Security::input('reference', '');
        $supplierId = (int)Security::input('supplier_id', 0) ?: null;
        $notes = (string)Security::input('notes', '');

        if ($productId <= 0 || $quantity <= 0 || !in_array($type, ['in', 'out', 'adjustment', 'waste'], true)) {
            $this->json(['ok' => false, 'error' => 'Invalid movement data.'], 422);
            return;
        }

        $product = Product::query()->where('id', $productId)->where('business_id', (int)$business['id'])->whereNull('deleted_at')->first();
        if (!$product) {
            $this->json(['ok' => false, 'error' => 'Product not found.'], 404);
            return;
        }

        Database::beginTransaction();
        try {
            $newQty = (float)$product['stock_quantity'];
            if ($type === 'in' || $type === 'adjustment') {
                $newQty += $quantity;
            } else {
                if ($quantity > $newQty) {
                    throw new \RuntimeException('Not enough stock. Available: ' . $newQty);
                }
                $newQty -= $quantity;
            }
            Product::update($productId, ['stock_quantity' => $newQty]);
            InventoryMovement::insert([
                'business_id' => (int)$business['id'],
                'product_id' => $productId,
                'type' => $type,
                'quantity' => $quantity,
                'reference' => $reference !== '' ? $reference : null,
                'supplier_id' => $supplierId,
                'notes' => $notes !== '' ? $notes : null,
                'user_id' => \App\Core\Auth::id(),
            ]);
            Database::commit();
            $this->json(['ok' => true, 'message' => Translator::t('common.record_saved'), 'new_quantity' => $newQty]);
        } catch (\Throwable $e) {
            if (Database::inTransaction()) {
                Database::rollBack();
            }
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    public function storeSupplier(): void
    {
        $this->saveSupplier(0);
    }

    public function updateSupplier(int $id): void
    {
        $this->saveSupplier($id);
    }

    private function saveSupplier(int $id): void
    {
        $business = Tenant::requireBusiness();
        $name = (string)Security::input('name', '');
        if ($name === '') {
            $this->json(['ok' => false, 'error' => 'Name required.'], 422);
            return;
        }
        $data = [
            'business_id' => (int)$business['id'],
            'name' => $name,
            'contact_person' => (string)Security::input('contact_person', ''),
            'phone' => (string)Security::input('phone', ''),
            'email' => (string)Security::input('email', ''),
            'address' => (string)Security::input('address', ''),
            'is_active' => Security::input('is_active') ? 1 : 0,
        ];
        if ($id === 0) {
            Supplier::insert($data);
        } else {
            Supplier::update($id, $data);
        }
        $this->json(['ok' => true, 'message' => Translator::t('common.record_saved')]);
    }

    public function deleteSupplier(int $id): void
    {
        $business = Tenant::requireBusiness();
        $supplier = Supplier::query()->where('id', $id)->where('business_id', (int)$business['id'])->first();
        if ($supplier) {
            Supplier::update($id, ['is_active' => 0]);
            $this->json(['ok' => true]);
        }
        $this->json(['ok' => false, 'error' => 'Not found.'], 404);
    }

    // -------------------- REPORTS --------------------

    public function reports(): void
    {
        $business = Tenant::requireBusiness();
        $salesTrend = ReportService::salesTrend((int)$business['id'], 30);
        $topProducts = Database::run(
            'SELECT oi.product_name, SUM(oi.quantity) AS total_qty, SUM(oi.line_total) AS revenue
             FROM order_items oi JOIN orders o ON o.id = oi.order_id
             WHERE oi.business_id = ? AND o.status NOT IN ("cancelled")
             GROUP BY oi.product_id ORDER BY total_qty DESC LIMIT 10',
            [(int)$business['id']]
        )->fetchAll();
        $reviews = \App\Models\Review::average((int)$business['id']);
        echo $this->view('restaurant/reports', [
            'business' => $business,
            'salesTrend' => $salesTrend,
            'topProducts' => $topProducts,
            'reviews' => $reviews,
        ]);
    }
}
