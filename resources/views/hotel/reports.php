<?php
/** @var array $business @var array $occupancy @var array $topRooms @var array $reviews */
$pageTitle = t('hotel.reports') ?? 'Reports';
ob_start();
$totalOccRevenue = 0.0;
foreach ($occupancy as $occ) {
    $totalOccRevenue += (float)($occ['revenue'] ?? 0);
}
?>
<div class="stat-grid">
    <div class="stat-card"><div class="s-icon green">💰</div><div><div class="s-value"><?= e(formatMoney($totalOccRevenue)) ?></div><div class="s-label"><?= e(t('common.revenue') ?? 'Revenue') ?> (12w)</div></div></div>
    <div class="stat-card"><div class="s-icon blue">⭐</div><div><div class="s-value"><?= e(number_format((float)$reviews['overall'], 1)) ?></div><div class="s-label"><?= e(t('reviews.overall_rating') ?? 'Overall rating') ?></div></div></div>
    <div class="stat-card"><div class="s-icon navy">🛏️</div><div><div class="s-value"><?= e(number_format((float)$reviews['service'], 1)) ?></div><div class="s-label"><?= e(t('reviews.service_rating') ?? 'Service rating') ?></div></div></div>
    <div class="stat-card"><div class="s-icon green">💬</div><div><div class="s-value"><?= (int)$reviews['total'] ?></div><div class="s-label"><?= e(t('reviews.total') ?? 'Total reviews') ?></div></div></div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">📈 <?= e(t('hotel.occupancy_trend') ?? 'Occupancy trend') ?> (12w)</div></div>
    <div class="chart-box" id="hotel-occ-chart"></div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">🏆 <?= e(t('hotel.top_rooms') ?? 'Top rooms by bookings') ?></div></div>
    <?php if (empty($topRooms)): ?>
        <div class="empty-state"><div class="es-icon">🏆</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th>#</th>
                    <th>🛏️</th>
                    <th>🏷️</th>
                    <th><?= e(t('hotel.booking_count') ?? 'Bookings') ?></th>
                    <th><?= e(t('common.revenue') ?? 'Revenue') ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($topRooms as $i => $tr): ?>
                        <tr>
                            <td class="fw-700"><?= $i + 1 ?></td>
                            <td class="fw-700">#<?= e($tr['room_number']) ?></td>
                            <td class="text-muted text-sm"><?= e($tr['type_name'] ?? '—') ?></td>
                            <td><?= (int)$tr['bookings'] ?></td>
                            <td class="fw-700"><?= e(formatMoney((float)$tr['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$occData = [];
foreach ($occupancy as $occ) {
    $occData[] = ['label' => 'W' . (int)$occ['wk'], 'value' => (float)$occ['bookings']];
}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    ISHEMA.renderBarChart('hotel-occ-chart', <?= json_encode($occData) ?>, 'label', 'value');
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
