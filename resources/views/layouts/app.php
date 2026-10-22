<?php
/** @var string $content Main view output */
/** @var string $view Current view name */

$user = \App\Core\Auth::user();
$business = null;
try {
    if ($user && $user['role'] !== 'SUPER_ADMIN') {
        $business = \App\Core\Tenant::current();
    }
} catch (\Throwable $e) {
    $business = null;
}
$unread = $user ? \App\Core\NotificationService::unreadCount((int)$user['id']) : 0;
$locale = \App\Core\Translator::locale();
$pageTitle = $pageTitle ?? t('common.dashboard');

$isSuperAdmin = $user && $user['role'] === 'SUPER_ADMIN';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// Build menu based on role + business type
$menu = [];
if ($isSuperAdmin) {
    $menu[] = ['icon' => '📊', 'label' => t('common.dashboard'), 'href' => '/admin', 'prefix' => '/admin'];
    $menu[] = ['icon' => '🏢', 'label' => t('admin.businesses_menu'), 'href' => '/admin/businesses', 'prefix' => '/admin/businesses'];
    $menu[] = ['icon' => '📦', 'label' => t('admin.manage_packages'), 'href' => '/admin/packages', 'prefix' => '/admin/packages'];
    $menu[] = ['icon' => '👥', 'label' => t('admin.manage_users'), 'href' => '/admin/users', 'prefix' => '/admin/users'];
    $menu[] = ['icon' => '🎥', 'label' => t('admin.manage_tutorials'), 'href' => '/admin/tutorials', 'prefix' => '/admin/tutorials'];
    $menu[] = ['icon' => '✉️', 'label' => t('admin.contact_messages'), 'href' => '/admin/contact-messages', 'prefix' => '/admin/contact-messages', 'badge' => \App\Models\ContactMessage::unreadCount()];
    $menu[] = ['icon' => '💳', 'label' => t('admin.subscriptions'), 'href' => '/admin/subscriptions', 'prefix' => '/admin/subscriptions'];
    $menu[] = ['icon' => '📜', 'label' => t('admin.audit_logs'), 'href' => '/admin/audit-logs', 'prefix' => '/admin/audit-logs'];
    $menu[] = ['icon' => '⚙️', 'label' => t('admin.platform_settings'), 'href' => '/admin/settings', 'prefix' => '/admin/settings'];
} elseif ($business) {
    $type = (string)$business['type'];
    $menu[] = ['icon' => '📊', 'label' => t('common.dashboard'), 'href' => '/dashboard', 'prefix' => '/dashboard'];
    if (in_array($type, ['RESTAURANT', 'HOTEL_AND_RESTAURANT'], true)) {
        $menu[] = ['icon' => '🍽️', 'label' => t('restaurant.restaurant_overview'), 'href' => '/restaurant', 'prefix' => '/restaurant'];
        $menu[] = ['icon' => '🥘', 'label' => t('common.products'), 'href' => '/restaurant/products', 'prefix' => '/restaurant/products'];
        $menu[] = ['icon' => '🗂️', 'label' => t('common.categories'), 'href' => '/restaurant/categories', 'prefix' => '/restaurant/categories'];
        $menu[] = ['icon' => '🪑', 'label' => t('common.tables'), 'href' => '/restaurant/tables', 'prefix' => '/restaurant/tables'];
        $menu[] = ['icon' => '🧾', 'label' => t('common.orders'), 'href' => '/orders', 'prefix' => '/orders', 'badge' => \App\Models\Order::where('business_id', (int)$business['id'])->whereIn('status', ['pending', 'confirmed'])->count()];
        $menu[] = ['icon' => '🔥', 'label' => t('restaurant.kitchen_queue'), 'href' => '/orders/kitchen', 'prefix' => '/orders/kitchen'];
        $menu[] = ['icon' => '📦', 'label' => t('common.inventory'), 'href' => '/restaurant/inventory', 'prefix' => '/restaurant/inventory'];
        $menu[] = ['icon' => '👨‍🍳', 'label' => t('common.staff'), 'href' => '/restaurant/staff', 'prefix' => '/restaurant/staff'];
    }
    if (in_array($type, ['HOTEL', 'HOTEL_AND_RESTAURANT'], true)) {
        $menu[] = ['icon' => '🏨', 'label' => t('hotel.hotel_overview'), 'href' => '/hotel', 'prefix' => '/hotel'];
        $menu[] = ['icon' => '🛏️', 'label' => t('common.rooms'), 'href' => '/hotel/rooms', 'prefix' => '/hotel/rooms'];
        $menu[] = ['icon' => '🏷️', 'label' => t('hotel.room_types'), 'href' => '/hotel/room-types', 'prefix' => '/hotel/room-types'];
        $menu[] = ['icon' => '📅', 'label' => t('common.bookings'), 'href' => '/hotel/bookings', 'prefix' => '/hotel/bookings', 'badge' => \App\Models\Booking::where('business_id', (int)$business['id'])->where('status', 'confirmed')->count()];
        $menu[] = ['icon' => '🧑‍🤝‍🧑', 'label' => t('common.guests'), 'href' => '/hotel/guests', 'prefix' => '/hotel/guests'];
        $menu[] = ['icon' => '🧹', 'label' => t('hotel.housekeeping_management'), 'href' => '/hotel/housekeeping', 'prefix' => '/hotel/housekeeping', 'badge' => \App\Models\Housekeeping::where('business_id', (int)$business['id'])->where('status', 'pending')->count()];
        $menu[] = ['icon' => '🛎️', 'label' => t('common.services'), 'href' => '/hotel/services', 'prefix' => '/hotel/services'];
    }
    $menu[] = ['icon' => '💳', 'label' => t('common.payments'), 'href' => '/payments', 'prefix' => '/payments'];
    $menu[] = ['icon' => '📈', 'label' => t('common.reports'), 'href' => in_array($type, ['HOTEL', 'HOTEL_AND_RESTAURANT'], true) ? '/hotel/reports' : '/restaurant/reports', 'prefix' => '/hotel/reports'];
    $menu[] = ['icon' => '📋', 'label' => t('common.subscription'), 'href' => '/dashboard/subscription', 'prefix' => '/dashboard/subscription'];
}

$avatar = $user && $user['avatar'] ? '<img src="' . url($user['avatar']) . '" style="width:34px;height:34px;border-radius:50%;object-fit:cover;">' : '<div class="avatar">' . strtoupper(e(mb_substr((string)$user['name'], 0, 1))) . '</div>';
$roleLabel = t('common.' . strtolower((string)$user['role']));
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Core\CSRF::token()) ?>">
    <title><?= e($pageTitle) ?> · ISHEMA</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><rect width='100' height='100' rx='20' fill='%231056A0'/><text x='50' y='68' font-size='52' text-anchor='middle' fill='white' font-family='Arial' font-weight='bold'>I</text></svg>">
</head>
<body>
<?php if ($user): ?>
<div class="sidebar" id="sidebar">
    <div class="brand">
        <div class="logo">I</div>
        <div>
            <span class="name">ISHEMA</span>
            <span class="sub"><?= e(t('common.platform')) ?></span>
        </div>
    </div>
    <?php if ($business): ?>
    <div class="business-card">
        <div class="biz-name"><?= e($business['name']) ?></div>
        <div class="biz-type"><?= e(t('admin.business_type_' . strtolower($business['type']))) ?></div>
    </div>
    <?php endif; ?>
    <nav>
        <?php
        $sections = [];
        foreach ($menu as $item) {
            echo navLink($item['href'], $item['icon'], $item['label'], str_starts_with($uri, $item['prefix']) || ($item['prefix'] === '/admin' && $uri === '/admin'), $item['badge'] ?? null);
        }
        ?>
    </nav>
    <div style="padding: 12px; border-top: 1px solid rgba(255,255,255,0.08);">
        <a href="<?= url('/profile') ?>" class="nav-link"><span class="icon">👤</span><span><?= e(t('common.profile')) ?></span></a>
        <a href="<?= url('/logout') ?>" class="nav-link"><span class="icon">🚪</span><span><?= e(t('common.logout')) ?></span></a>
    </div>
</div>
<?php endif; ?>

<div class="main">
    <?php if ($user): ?>
    <div class="topbar">
        <button class="menu-toggle" id="menu-toggle" aria-label="Menu">☰</button>
        <div class="page-title"><?= e($pageTitle) ?></div>
        <div class="spacer"></div>
        <div class="lang-select">
            🌐
            <select id="language-select">
                <?php foreach (\App\Core\Translator::LANGS as $code => $label): ?>
                    <option value="<?= e($code) ?>"<?= $code === $locale ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="notif-wrap">
            <button class="notif-btn" aria-label="Notifications">
                🔔
                <span class="count" style="display:<?= $unread > 0 ? 'flex' : 'none' ?>"><?= (int)$unread ?></span>
            </button>
            <div class="notif-dropdown">
                <div class="empty-state" style="padding:24px;"><div class="es-title"><?= e(t('common.loading')) ?></div></div>
            </div>
        </div>
        <div class="user-chip">
            <?= $avatar ?>
            <div>
                <div class="u-name"><?= e($user['name']) ?></div>
                <div class="u-role"><?= e($roleLabel) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="content">
        <?= $content ?>
    </div>
</div>

<div class="overlay" id="overlay"></div>
<div id="toast-container"></div>

<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
