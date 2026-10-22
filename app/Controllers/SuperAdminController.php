<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Router;
use App\Core\Security;
use App\Core\Session;
use App\Core\Translator;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\ContactMessage;
use App\Models\Package;
use App\Models\SettingsModel;
use App\Models\Subscription;
use App\Models\Tutorial;
use App\Models\User;
use App\Services\BusinessService;
use App\Services\ReportService;

class SuperAdminController extends Controller
{
    public function index(): void
    {
        $stats = ReportService::platformStats();
        echo $this->view('admin/index', ['stats' => $stats]);
    }

    // -------------------- BUSINESSES --------------------

    public function businesses(): void
    {
        $type = Security::input('type', 'all');
        $status = Security::input('status', 'all');
        $q = Business::query()->whereNull('deleted_at')->orderBy('id', 'DESC');
        if ($type !== 'all') {
            $q->where('type', $type);
        }
        if ($status !== 'all') {
            $q->where('status', $status);
        }
        $businesses = $q->limit(200)->get();
        echo $this->view('admin/businesses', ['businesses' => $businesses, 'type' => $type, 'status' => $status]);
    }

    public function createBusiness(): void
    {
        $packages = Package::active();
        $owners = User::where('role', 'BUSINESS_OWNER')->whereNull('deleted_at')->orderBy('name')->get();
        echo $this->view('admin/business-form', [
            'packages' => $packages,
            'owners' => $owners,
            'business' => null,
        ]);
    }

    public function storeBusiness(): void
    {
        $name = (string)Security::input('name', '');
        $type = (string)Security::input('type', 'HOTEL');
        if (!in_array($type, ['HOTEL', 'RESTAURANT', 'HOTEL_AND_RESTAURANT'], true)) {
            $type = 'HOTEL';
        }
        if ($name === '') {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect('/admin/businesses/create');
            return;
        }

        try {
            $result = BusinessService::createBusiness([
                'name' => $name,
                'type' => $type,
                'email' => Security::input('email'),
                'phone' => Security::input('phone'),
                'address' => Security::input('address'),
                'city' => Security::input('city'),
                'country' => Security::input('country'),
                'description' => Security::input('description'),
                'status' => in_array(Security::input('status', 'active'), ['active', 'pending', 'suspended'], true) ? Security::input('status', 'active') : 'active',
                'owner_name' => Security::input('owner_name'),
                'owner_email' => Security::input('owner_email'),
                'owner_phone' => Security::input('owner_phone'),
                'package_id' => (int)Security::input('package_id', 0) ?: null,
                'billing_cycle' => Security::input('billing_cycle', 'monthly'),
                'starts_at' => Security::input('starts_at') ?: date('Y-m-d H:i:s'),
                'expires_at' => Security::input('expires_at') ?: date('Y-m-d H:i:s', strtotime('+1 month')),
            ], !empty(Security::input('owner_email')));

            Session::flash('message', Translator::t('admin.business_created'));
            if (isset($result['password'])) {
                Session::flash('generated_password', $result['password']);
                Session::flash('owner_email', $result['owner']['email']);
            }
            $this->redirect('/admin/businesses');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/admin/businesses/create');
        }
    }

    public function editBusiness(int $id): void
    {
        $business = Business::find($id);
        if (!$business || $business['deleted_at'] !== null) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/admin/businesses');
            return;
        }
        $packages = Package::active();
        $owners = User::where('role', 'BUSINESS_OWNER')->whereNull('deleted_at')->orderBy('name')->get();
        $ownerLink = BusinessUser::query()->where('business_id', $id)->where('role', 'BUSINESS_OWNER')->first();
        $subscription = Subscription::currentForBusiness($id);
        echo $this->view('admin/business-form', [
            'business' => $business,
            'packages' => $packages,
            'owners' => $owners,
            'ownerLink' => $ownerLink,
            'subscription' => $subscription,
        ]);
    }

    public function updateBusiness(int $id): void
    {
        $business = Business::find($id);
        if (!$business) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/admin/businesses');
            return;
        }
        $type = (string)Security::input('type', $business['type']);
        if (!in_array($type, ['HOTEL', 'RESTAURANT', 'HOTEL_AND_RESTAURANT'], true)) {
            $type = $business['type'];
        }
        Business::update($id, [
            'name' => (string)Security::input('name', $business['name']),
            'type' => $type,
            'email' => Security::input('email'),
            'phone' => Security::input('phone'),
            'address' => Security::input('address'),
            'city' => Security::input('city'),
            'country' => Security::input('country'),
            'description' => Security::input('description'),
            'status' => in_array(Security::input('status'), ['active', 'pending', 'suspended'], true) ? Security::input('status') : $business['status'],
        ]);

        // Assign/reassign owner
        $ownerUserId = (int)Security::input('owner_user_id', 0);
        if ($ownerUserId > 0) {
            // Remove previous owner links for this business
            Database::run(
                'UPDATE business_users SET is_active = 0 WHERE business_id = ? AND role = "BUSINESS_OWNER"',
                [$id]
            );
            $existing = BusinessUser::link($ownerUserId, $id);
            if ($existing) {
                BusinessUser::update((int)$existing['id'], ['is_active' => 1, 'role' => 'BUSINESS_OWNER']);
            } else {
                BusinessUser::insert([
                    'user_id' => $ownerUserId,
                    'business_id' => $id,
                    'role' => 'BUSINESS_OWNER',
                    'permissions' => json_encode(['*']),
                    'is_active' => 1,
                ]);
            }
        }

        // Subscription assignment
        $packageId = (int)Security::input('package_id', 0);
        if ($packageId > 0) {
            BusinessService::assignSubscription(
                $id,
                $packageId,
                (string)Security::input('starts_at', date('Y-m-d H:i:s')),
                (string)Security::input('expires_at', date('Y-m-d H:i:s', strtotime('+1 month'))),
                (string)Security::input('billing_cycle', 'monthly')
            );
        }

        Session::flash('message', Translator::t('admin.business_updated'));
        $this->redirect('/admin/businesses');
    }

    public function toggleBusinessStatus(int $id): void
    {
        $business = Business::find($id);
        if (!$business) {
            $this->json(['ok' => false], 404);
            return;
        }
        $newStatus = $business['status'] === 'active' ? 'suspended' : 'active';
        BusinessService::setStatus($id, $newStatus);
        $this->json(['ok' => true, 'status' => $newStatus]);
    }

    public function archiveBusiness(int $id): void
    {
        BusinessService::archive($id);
        Session::flash('message', Translator::t('admin.business_deleted'));
        $this->redirect('/admin/businesses');
    }

    public function viewBusiness(int $id): void
    {
        $business = Business::find($id);
        if (!$business) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/admin/businesses');
            return;
        }
        $subscription = Subscription::currentForBusiness($id);
        $staff = BusinessUser::queryAs('bu')
            ->select('bu.*, u.name, u.email')
            ->join('users u', 'u.id = bu.user_id')
            ->where('bu.business_id', $id)
            ->where('bu.is_active', 1)
            ->orderBy('bu.role')
            ->limit(50)
            ->get();
        $overview = ReportService::businessOverview($id, (string)$business['type']);
        echo $this->view('admin/business-view', [
            'business' => $business,
            'subscription' => $subscription,
            'staff' => $staff,
            'overview' => $overview,
        ]);
    }

    // -------------------- PACKAGES --------------------

    public function packages(): void
    {
        $packages = Package::query()->orderBy('sort_order')->orderBy('id')->get();
        echo $this->view('admin/packages', ['packages' => $packages]);
    }

    public function createPackage(): void
    {
        echo $this->view('admin/package-form', ['package' => null]);
    }

    public function editPackage(int $id): void
    {
        $package = Package::find($id);
        if (!$package) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/admin/packages');
            return;
        }
        echo $this->view('admin/package-form', ['package' => $package]);
    }

    public function storePackage(): void
    {
        $this->savePackage(0);
    }

    public function updatePackage(int $id): void
    {
        $this->savePackage($id);
    }

    private function savePackage(int $id): void
    {
        $name = (string)Security::input('name', '');
        $slug = \App\Core\Security::slug($name);
        if ($slug === '') {
            $slug = 'pkg-' . uniqid();
        }
        $data = [
            'name' => $name,
            'slug' => $slug,
            'price_monthly' => (float)Security::input('price_monthly', 0),
            'price_yearly' => (float)Security::input('price_yearly', 0),
            'max_employees' => (int)Security::input('max_employees', 5),
            'max_rooms' => (int)Security::input('max_rooms', 0),
            'max_tables' => (int)Security::input('max_tables', 0),
            'max_products' => (int)Security::input('max_products', 100),
            'max_branches' => (int)Security::input('max_branches', 1),
            'has_reports' => Security::input('has_reports') ? 1 : 0,
            'support_level' => in_array(Security::input('support_level', 'email'), ['email', 'priority', 'dedicated'], true) ? Security::input('support_level', 'email') : 'email',
            'status' => Security::input('status') === 'active' ? 'active' : 'inactive',
            'sort_order' => (int)Security::input('sort_order', 0),
            'modules' => json_encode((array)Security::input('modules', [])),
            'features' => json_encode(array_values(array_filter((array)Security::input('features', [])))),
        ];
        if ($id === 0) {
            $data['is_default'] = 0;
            Package::insert($data);
            Session::flash('message', Translator::t('admin.package_saved'));
            $this->redirect('/admin/packages');
            return;
        }
        Package::update($id, $data);
        Session::flash('message', Translator::t('admin.package_saved'));
        $this->redirect('/admin/packages');
    }

    public function deletePackage(int $id): void
    {
        $used = Database::run('SELECT COUNT(*) AS c FROM subscriptions WHERE package_id = ?', [$id])->fetch()['c'];
        if ((int)$used === 0) {
            Package::delete($id);
            $this->json(['ok' => true]);
        }
        $this->json(['ok' => false, 'error' => 'Package is used by existing subscriptions.'], 400);
    }

    // -------------------- USERS --------------------

    public function users(): void
    {
        $role = Security::input('role', 'all');
        $q = User::query()->whereNull('deleted_at')->orderBy('id', 'DESC');
        if ($role !== 'all') {
            $q->where('role', $role);
        }
        $users = $q->limit(200)->get();
        echo $this->view('admin/users', ['users' => $users, 'role' => $role]);
    }

    public function createUser(): void
    {
        echo $this->view('admin/user-form', ['user' => null]);
    }

    public function editUser(int $id): void
    {
        $user = User::find($id);
        if (!$user || $user['deleted_at'] !== null) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/admin/users');
            return;
        }
        echo $this->view('admin/user-form', ['user' => $user]);
    }

    public function storeUser(): void
    {
        $this->saveUser(0);
    }

    public function updateUser(int $id): void
    {
        $this->saveUser($id);
    }

    private function saveUser(int $id): void
    {
        $name = (string)Security::input('name', '');
        $email = (string)Security::input('email', '');
        $role = (string)Security::input('role', 'CUSTOMER');
        if ($name === '' || !Security::isValidEmail($email)) {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect($id === 0 ? '/admin/users/create' : '/admin/users/' . $id . '/edit');
            return;
        }
        $validRoles = ['BUSINESS_OWNER', 'MANAGER', 'RECEPTIONIST', 'WAITER', 'KITCHEN_STAFF', 'CASHIER', 'HOUSEKEEPING', 'CUSTOMER'];
        if (!in_array($role, $validRoles, true)) {
            $role = 'CUSTOMER';
        }
        $data = [
            'name' => $name,
            'email' => mb_strtolower($email),
            'phone' => Security::input('phone'),
            'role' => $role,
            'language' => in_array(Security::input('language', 'en'), array_keys(\App\Core\Translator::LANGS), true) ? Security::input('language', 'en') : 'en',
            'is_active' => Security::input('is_active') ? 1 : 0,
        ];
        $password = (string)Security::input('password', '');
        if ($password !== '') {
            if (strlen($password) < 8) {
                Session::flash('error', Translator::t('auth.password_min'));
                $this->redirect($id === 0 ? '/admin/users/create' : '/admin/users/' . $id . '/edit');
                return;
            }
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        if ($id === 0) {
            $existing = User::findByEmail($data['email']);
            if ($existing) {
                Session::flash('error', 'A user with this email already exists.');
                $this->redirect('/admin/users/create');
                return;
            }
            $data['password'] = $data['password'] ?? password_hash(Security::randomString(12), PASSWORD_DEFAULT);
            User::insert($data);
            Session::flash('message', Translator::t('admin.user_created'));
            $this->redirect('/admin/users');
            return;
        }
        User::update($id, $data);
        Session::flash('message', Translator::t('admin.user_updated'));
        $this->redirect('/admin/users');
    }

    public function toggleUserStatus(int $id): void
    {
        $user = User::find($id);
        if (!$user) {
            $this->json(['ok' => false], 404);
        }
        $newStatus = $user['is_active'] ? 0 : 1;
        User::update($id, ['is_active' => $newStatus]);
        $this->json(['ok' => true, 'is_active' => $newStatus]);
    }

    // -------------------- TUTORIALS --------------------

    public function tutorials(): void
    {
        $items = Tutorial::query()->orderBy('sort_order')->orderBy('id')->limit(200)->get();
        echo $this->view('admin/tutorials', ['tutorials' => $items]);
    }

    public function createTutorial(): void
    {
        echo $this->view('admin/tutorial-form', ['tutorial' => null]);
    }

    public function editTutorial(int $id): void
    {
        $tutorial = Tutorial::find($id);
        if (!$tutorial) {
            Session::flash('error', Translator::t('common.no_records'));
            $this->redirect('/admin/tutorials');
            return;
        }
        echo $this->view('admin/tutorial-form', ['tutorial' => $tutorial]);
    }

    public function storeTutorial(): void
    {
        $this->saveTutorial(0);
    }

    public function updateTutorial(int $id): void
    {
        $this->saveTutorial($id);
    }

    private function saveTutorial(int $id): void
    {
        $title = (string)Security::input('title', '');
        if ($title === '') {
            Session::flash('error', Translator::t('common.please_fill_required'));
            $this->redirect($id === 0 ? '/admin/tutorials/create' : '/admin/tutorials/' . $id . '/edit');
            return;
        }
        $slug = \App\Core\Security::slug($title);
        $data = [
            'title' => $title,
            'slug' => $slug !== '' ? $slug : 'tutorial-' . uniqid(),
            'category' => in_array(Security::input('category', 'general'), ['hotel', 'restaurant', 'owner', 'employee', 'general'], true) ? Security::input('category', 'general') : 'general',
            'language' => in_array(Security::input('language', 'en'), array_keys(\App\Core\Translator::LANGS), true) ? Security::input('language', 'en') : 'en',
            'video_url' => (string)Security::input('video_url', ''),
            'thumbnail' => (string)Security::input('thumbnail', ''),
            'description' => (string)Security::input('description', ''),
            'duration' => (string)Security::input('duration', ''),
            'is_active' => Security::input('is_active') ? 1 : 0,
            'sort_order' => (int)Security::input('sort_order', 0),
        ];
        if ($id === 0) {
            Tutorial::insert($data);
        } else {
            Tutorial::update($id, $data);
        }
        Session::flash('message', Translator::t('admin.tutorial_saved'));
        $this->redirect('/admin/tutorials');
    }

    public function deleteTutorial(int $id): void
    {
        Tutorial::delete($id);
        $this->json(['ok' => true]);
    }

    // -------------------- CONTACT MESSAGES --------------------

    public function contactMessages(): void
    {
        $messages = ReportService::contactMessages();
        echo $this->view('admin/contact-messages', ['messages' => $messages]);
    }

    public function readContactMessage(int $id): void
    {
        $msg = ContactMessage::find($id);
        if ($msg) {
            ContactMessage::update($id, ['is_read' => 1]);
        }
        $this->json(['ok' => true]);
    }

    public function deleteContactMessage(int $id): void
    {
        ContactMessage::delete($id);
        $this->json(['ok' => true]);
    }

    // -------------------- AUDIT LOGS & SETTINGS --------------------

    public function auditLogs(): void
    {
        $logs = ReportService::auditTrail(null, 200);
        echo $this->view('admin/audit-logs', ['logs' => $logs]);
    }

    public function settings(): void
    {
        $settings = [
            'site_name' => SettingsModel::get('site_name', null, 'ISHEMA'),
            'site_tagline' => SettingsModel::get('site_tagline', null, 'Hospitality management, simplified.'),
            'contact_email' => SettingsModel::get('contact_email', null, 'hello@ishema.com'),
            'contact_phone' => SettingsModel::get('contact_phone', null, '+250 788 000 000'),
            'contact_address' => SettingsModel::get('contact_address', null, 'Kigali, Rwanda'),
            'default_language' => SettingsModel::get('default_language', null, 'en'),
            'registration_open' => SettingsModel::get('registration_open', null, true),
            'mtn_api_key' => getenv('MTN_API_KEY') ?: '',
            'airtel_api_key' => getenv('AIRTEL_API_KEY') ?: '',
        ];
        $message = Session::flash('message');
        echo $this->view('admin/settings', ['settings' => $settings, 'message' => $message]);
    }

    public function saveSettings(): void
    {
        $keys = ['site_name', 'site_tagline', 'contact_email', 'contact_phone', 'contact_address', 'default_language'];
        foreach ($keys as $key) {
            if (Security::input($key) !== null) {
                SettingsModel::set($key, (string)Security::input($key, ''), null);
            }
        }
        SettingsModel::set('registration_open', Security::input('registration_open') ? true : false, null);
        Session::flash('message', Translator::t('common.record_updated'));
        $this->redirect('/admin/settings');
    }

    // -------------------- SUBSCRIPTION MANAGEMENT --------------------

    public function subscriptions(): void
    {
        $subs = Database::run(
            'SELECT s.*, b.name AS business_name, b.business_id, p.name AS package_name
             FROM subscriptions s
             JOIN businesses b ON b.id = s.business_id
             LEFT JOIN packages p ON p.id = s.package_id
             WHERE b.deleted_at IS NULL
             ORDER BY s.id DESC LIMIT 200'
        )->fetchAll();
        echo $this->view('admin/subscriptions', ['subscriptions' => $subs]);
    }
}
