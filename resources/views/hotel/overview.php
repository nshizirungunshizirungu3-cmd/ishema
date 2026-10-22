<?php
/** @var array $business @var array $stats @var array $occupancy */
$pageTitle = t('common.dashboard');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="stat-grid">
    <?php if (isset($stats['hotel'])): ?>
        <?php $r = $stats['hotel']['rooms']; ?>
        <?php $b = $stats['hotel']['bookings']; ?>
        <div class="stat-card"><div class="s-icon navy">🛏️</div><div><div class="s-value"><?= (int)$r['total'] ?></div><div class="s-label"><?= e(t('hotel.rooms')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">✅</div><div><div class="s-value"><?= (int)$r['available'] ?></div><div class="s-label"><?= e(t('hotel.available')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">🧍</div><div><div class="s-value"><?= (int)$r['occupied'] ?></div><div class="s-label"><?= e(t('hotel.occupied')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">🧹</div><div><div class="s-value"><?= (int)$r['cleaning'] ?></div><div class="s-label"><?= e(t('hotel.cleaning') ?? 'Cleaning') ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">🔧</div><div><div class="s-value"><?= (int)$r['maintenance'] ?></div><div class="s-label"><?= e(t('hotel.maintenance') ?? 'Maintenance') ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">📅</div><div><div class="s-value"><?= (int)$b['total'] ?></div><div class="s-label"><?= e(t('hotel.bookings')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">🧑‍🤝‍🧑</div><div><div class="s-value"><?= (int)$b['in_house'] ?></div><div class="s-label"><?= e(t('hotel.in_house') ?? 'In house') ?></div></div></div>
        <div class="stat-card"><div class="s-icon navy">📥</div><div><div class="s-value"><?= (int)$b['today_in'] ?></div><div class="s-label"><?= e(t('hotel.arrivals_today') ?? 'Arrivals today') ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">📤</div><div><div class="s-value"><?= (int)$b['today_out'] ?></div><div class="s-label"><?= e(t('hotel.departures_today') ?? 'Departures today') ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">💰</div><div><div class="s-value"><?= e(formatMoney((float)$b['revenue'])) ?></div><div class="s-label"><?= e(t('common.revenue') ?? 'Revenue') ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">🧹</div><div><div class="s-value"><?= (int)$stats['hotel']['housekeeping']['pending'] ?></div><div class="s-label"><?= e(t('hotel.housekeeping')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">⭐</div><div><div class="s-value"><?= e(number_format((float)$stats['reviews']['overall'], 1)) ?></div><div class="s-label"><?= e(t('reviews.rating') ?? 'Rating') ?></div></div></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">📈 <?= e(t('hotel.occupancy_trend') ?? 'Occupancy trend') ?> (8)</div></div>
    <div class="chart-box" id="occupancy-chart"></div>
</div>

<div class="form-row">
    <div class="card">
        <div class="card-header">
            <div class="card-title">📅 <?= e(t('hotel.recent_bookings') ?? 'Recent bookings') ?></div>
            <a href="/hotel/bookings" class="btn btn-outline btn-sm" style="margin-left:auto;">👁 <?= e(t('common.view_all') ?? 'View all') ?></a>
        </div>
        <?php
        $recentBookings = \App\Models\Booking::query()->where('business_id', (int)$business['id'])->orderBy('id', 'DESC')->limit(6)->get();
        ?>
        <?php if (empty($recentBookings)): ?>
            <div class="empty-state" style="padding:20px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
            <?php foreach ($recentBookings as $bk): ?>
                <div class="d-flex justify-between items-center" style="padding:9px 0;border-bottom:1px solid var(--border);">
                    <div>
                        <div class="fw-700 mono"><?= e($bk['booking_reference']) ?></div>
                        <div class="text-muted text-xs"><?= e($bk['check_in_date']) ?> → <?= e($bk['check_out_date']) ?></div>
                    </div>
                    <div class="d-flex items-center gap-2">
                        <span class="fw-700"><?= e(formatMoney((float)$bk['total_amount'])) ?></span>
                        <?= statusBadge((string)$bk['status']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">⚠️ <?= e(t('hotel.housekeeping_pending') ?? 'Pending housekeeping') ?></div></div>
        <?php
        $hkPending = \App\Models\Housekeeping::query()->where('business_id', (int)$business['id'])->where('status', 'pending')->limit(8)->get();
        ?>
        <?php if (empty($hkPending)): ?>
            <div class="empty-state" style="padding:20px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
            <?php foreach ($hkPending as $t): ?>
                <div class="d-flex justify-between items-center" style="padding:9px 0;border-bottom:1px solid var(--border);">
                    <div class="fw-700"><?= e(t('hotel.hk_' . (string)$t['type'])) ?> <?= e(t('hotel.for_room') ?? 'for room') ?> #<?= (int)$t['room_id'] ?></div>
                    <span class="badge badge-<?= $t['priority'] === 'urgent' ? 'danger' : ($t['priority'] === 'high' ? 'warning' : 'info') ?>"><?= e($t['priority']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$occData = [];
foreach ($occupancy as $row) {
    $occData[] = ['label' => 'W' . (int)$row['wk'], 'value' => (float)$row['bookings']];
}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    ISHEMA.renderBarChart('occupancy-chart', <?= json_encode($occData) ?>, 'label', 'value');
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
