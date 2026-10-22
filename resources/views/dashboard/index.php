<?php
/**
 * @var array $business
 * @var array $overview
 * @var array $notifications
 * @var int $unread
 */
$pageTitle = t('common.dashboard');
ob_start();
?>
<?php $gen = \App\Core\Session::flash('generated_password'); if ($gen): ?>
    <div class="alert alert-info">ðŸ”‘ <?= e(t('admin.generated_password')) ?>: <strong class="mono"><?= e($gen) ?></strong>
        (<?= e(\App\Core\Session::flash('owner_email') ?? '') ?>) â€” <?= e(t('common.save')) ?>!</div>
<?php endif; ?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">âœ… <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">âš ï¸ <?= e($error) ?></div>
<?php endif; ?>

<?php if (in_array($business['type'], ['RESTAURANT', 'HOTEL_AND_RESTAURANT'], true) && isset($overview['restaurant'])):
    $r = $overview['restaurant']; ?>
    <div class="stat-grid">
        <div class="stat-card"><div class="s-icon green">ðŸ’°</div><div><div class="s-value"><?= e(formatMoney($r['orders']['revenue'])) ?></div><div class="s-label"><?= e(t('restaurant.daily_sales')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">ðŸ§¾</div><div><div class="s-value"><?= (int)$r['orders']['today'] ?></div><div class="s-label"><?= e(t('restaurant.total_orders')) ?> (<?= e(t('common.today')) ?>)</div></div></div>
        <div class="stat-card"><div class="s-icon orange">â³</div><div><div class="s-value"><?= (int)$r['orders']['active'] ?></div><div class="s-label"><?= e(t('restaurant.active_orders')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon navy">âœ…</div><div><div class="s-value"><?= (int)$r['orders']['completed'] ?></div><div class="s-label"><?= e(t('restaurant.completed_orders')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">ðŸª‘</div><div><div class="s-value"><?= (int)$r['tables']['available'] ?>/<?= (int)$r['tables']['total'] ?></div><div class="s-label"><?= e(t('restaurant.available_tables')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">ðŸ“¦</div><div><div class="s-value"><?= (int)$r['products'] ?></div><div class="s-label"><?= e(t('common.total_products')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">âš ï¸</div><div><div class="s-value"><?= (int)$r['low_stock'] ?></div><div class="s-label"><?= e(t('restaurant.low_stock')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon navy">ðŸ‘¥</div><div><div class="s-value"><?= (int)$r['staff_on_duty'] ?></div><div class="s-label"><?= e(t('restaurant.staff_on_duty')) ?></div></div></div>
    </div>
<?php endif; ?>

<?php if (in_array($business['type'], ['HOTEL', 'HOTEL_AND_RESTAURANT'], true) && isset($overview['hotel'])):
    $h = $overview['hotel']; ?>
    <div class="stat-grid">
        <div class="stat-card"><div class="s-icon blue">ðŸ›ï¸</div><div><div class="s-value"><?= (int)$h['rooms']['available'] ?>/<?= (int)$h['rooms']['total'] ?></div><div class="s-label"><?= e(t('hotel.available_rooms')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">ðŸ§</div><div><div class="s-value"><?= (int)$h['rooms']['occupied'] ?></div><div class="s-label"><?= e(t('hotel.occupied_rooms')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon navy">ðŸ“…</div><div><div class="s-value"><?= (int)$h['bookings']['inHouse'] ?></div><div class="s-label"><?= e(t('hotel.checkins_today')) ?>: <?= (int)$h['bookings']['todayIn'] ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">ðŸšª</div><div><div class="s-value"><?= (int)$h['bookings']['todayOut'] ?></div><div class="s-label"><?= e(t('hotel.checkouts_today')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">ðŸ’°</div><div><div class="s-value"><?= e(formatMoney($h['bookings']['revenue'])) ?></div><div class="s-label"><?= e(t('common.revenue')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">â³</div><div><div class="s-value"><?= e(formatMoney($h['bookings']['pendingPayments'])) ?></div><div class="s-label"><?= e(t('hotel.pending_payments')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">ðŸ§¹</div><div><div class="s-value"><?= (int)$h['housekeeping']['pending'] ?></div><div class="s-label"><?= e(t('hotel.housekeeping_management')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon navy">â­</div><div><div class="s-value"><?= e(number_format((float)$overview['reviews']['overall'], 1)) ?></div><div class="s-label"><?= e(t('common.rating')) ?> (<?= (int)$overview['reviews']['total'] ?>)</div></div></div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title"><?= e(t('common.overview')) ?></div>
            <div class="card-sub"><?= e($business['name']) ?> Â· <?= e(t('admin.business_type_' . strtolower($business['type']))) ?></div>
        </div>
        <?php if (isset($overview['subscription']) && $overview['subscription']):
            $sub = $overview['subscription'];
            $daysLeft = max(0, (int)((strtotime((string)$sub['expires_at']) - time()) / 86400));
        ?>
            <div class="alert <?= $daysLeft <= 7 ? 'alert-warning' : 'alert-info' ?> mb-0" style="margin-left:auto;">
                ðŸ“‹ <?= e($overview['subscription']['package']['name'] ?? t('common.subscription')) ?> â€”
                <?= $daysLeft > 0 ? $daysLeft . ' ' . e(t('admin.days_remaining')) : e(t('common.expired')) ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-3" style="flex-wrap:wrap;">
        <?php if (in_array($business['type'], ['RESTAURANT', 'HOTEL_AND_RESTAURANT'], true)): ?>
            <a href="/orders/create" class="btn btn-primary">âž• <?= e(t('common.add')) ?> <?= e(t('common.order')) ?></a>
            <a href="/orders/kitchen" class="btn btn-outline">ðŸ”¥ <?= e(t('restaurant.kitchen_queue')) ?></a>
            <a href="/orders/complaints" class="btn btn-outline">âš ï¸ <?= e('Complaints') ?></a>
        <?php endif; ?>
        <?php if (in_array($business['type'], ['HOTEL', 'HOTEL_AND_RESTAURANT'], true)): ?>
            <a href="/hotel/bookings/create" class="btn btn-primary">âž• <?= e(t('hotel.create_booking')) ?></a>
            <a href="/hotel/housekeeping" class="btn btn-outline">ðŸ§¹ <?= e(t('hotel.housekeeping_management')) ?></a>
        <?php endif; ?>
        <a href="/payments" class="btn btn-outline">ðŸ’³ <?= e(t('common.payments')) ?></a>
    </div>
</div>

<div class="form-row">
    <div class="card">
        <div class="card-header"><div class="card-title">ðŸ“ˆ <?= e(t('common.reports')) ?></div></div>
        <div class="chart-box" id="sales-chart"></div>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">ðŸ”” <?= e(t('common.notifications')) ?></div></div>
        <?php if (empty($notifications)): ?>
            <div class="empty-state" style="padding:24px;"><div class="es-title"><?= e(t('common.no_notifications')) ?></div></div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="notif-item<?= $n['is_read'] ? '' : ' unread' ?>" style="border-bottom:1px solid var(--border);padding:10px 4px;">
                    <div class="dot"></div>
                    <div>
                        <div class="nt-title"><?= e($n['title']) ?></div>
                        <?php if ($n['message']): ?><div class="nt-msg"><?= e($n['message']) ?></div><?php endif; ?>
                        <div class="nt-time"><?= e(formatDate($n['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$salesTrend = \App\Services\ReportService::salesTrend((int)$business['id'], 14);
$labels = array_map(fn($d) => substr((string)$d['day'], 5), $salesTrend);
$values = array_map(fn($d) => (float)$d['revenue'], $salesTrend);
$data = [];
foreach ($labels as $i => $lbl) {
    $data[] = ['label' => $lbl, 'value' => $values[$i]];
}
$payload = json_encode($data);
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        ISHEMA.renderBarChart('sales-chart', <?= $payload ?>, 'label', 'value');
    });
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
