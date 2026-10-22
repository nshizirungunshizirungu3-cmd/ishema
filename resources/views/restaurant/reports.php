<?php
/** @var array $business @var array $salesTrend @var array $topProducts @var array $reviews */
$pageTitle = t('common.reports');
ob_start();
$chart = [];
$totalRevenue = 0.0;
$totalOrders = 0;
foreach ($salesTrend as $row) {
    $chart[] = ['day' => date('M d', strtotime((string)$row['day'])), 'revenue' => (float)$row['revenue'], 'orders' => (int)$row['orders']];
    $totalRevenue += (float)$row['revenue'];
    $totalOrders += (int)$row['orders'];
}
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="stat-grid">
    <div class="stat-card"><div class="s-icon green">💰</div><div><div class="s-value"><?= e(formatMoney($totalRevenue)) ?></div><div class="s-label"><?= e(t('restaurant.total_revenue')) ?> (30 <?= e(t('common.daily') === 'common.daily' ? 'days' : 'days') ?>)</div></div></div>
    <div class="stat-card"><div class="s-icon blue">🧾</div><div><div class="s-value"><?= $totalOrders ?></div><div class="s-label"><?= e(t('common.orders')) ?> (30 <?= e(t('common.daily') === 'common.daily' ? 'days' : 'days') ?>)</div></div></div>
    <div class="stat-card"><div class="s-icon orange">🏆</div><div><div class="s-value"><?= count($topProducts) ?></div><div class="s-label"><?= e(t('common.top') === 'common.top' ? 'Top products' : 'Top products') ?></div></div></div>
    <?php if (!empty($reviews)): ?>
        <div class="stat-card"><div class="s-icon blue">⭐</div><div><div class="s-value"><?= e(number_format((float)$reviews['overall'], 1)) ?></div><div class="s-label"><?= e(t('common.rating')) ?> (<?= (int)$reviews['total'] ?>)</div></div></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">📈 <?= e(t('restaurant.daily_sales')) ?></div></div>
    <div class="card-body">
        <div id="salesChart" style="height:280px;"></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><div class="card-title">🏆 <?= e(t('common.top') === 'common.top' ? 'Top products' : 'Top products') ?></div></div>
        <?php if (empty($topProducts)): ?>
            <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th></th><th><?= e(t('restaurant.product_name')) ?></th><th><?= e(t('common.quantity')) ?></th><th><?= e(t('common.revenue')) ?></th></tr></thead>
                <tbody>
                    <?php $rank = 1; foreach ($topProducts as $tp): ?>
                        <tr>
                            <td class="fw-700">#<?= $rank++ ?></td>
                            <td class="fw-700"><?= e($tp['product_name']) ?></td>
                            <td><?= (int)$tp['total_qty'] ?></td>
                            <td class="fw-700"><?= e(formatMoney((float)$tp['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">⭐ <?= e(t('common.ratings')) ?></div></div>
        <?php if (empty($reviews)): ?>
            <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
            <div class="card-body">
                <?php
                $ratingBars = [
                    [t('customer.rate_overall'), (float)$reviews['overall']],
                    [t('customer.rate_food'), (float)$reviews['food_quality']],
                    [t('customer.rate_service'), (float)$reviews['service_rating']],
                    [t('customer.rate_speed'), (float)$reviews['speed_rating']],
                ];
                foreach ($ratingBars as [$label, $value]): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-between text-sm mb-1">
                            <span><?= e($label) ?></span>
                            <span class="fw-700"><?= e(number_format($value, 1)) ?>/5</span>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar" style="width:<?= min(100, ($value / 5) * 100) ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="text-muted text-sm mt-2"><?= (int)$reviews['total'] ?> <?= e(t('common.reviews')) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('salesChart') && typeof ISHEMA !== 'undefined' && typeof ISHEMA.renderBarChart === 'function') {
        ISHEMA.renderBarChart('salesChart', <?= json_encode($chart) ?>, 'day', 'revenue');
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
