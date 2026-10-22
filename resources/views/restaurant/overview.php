<?php
/** @var array $business @var array $stats @var array $salesTrend */
$pageTitle = t('common.dashboard');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="stat-grid">
    <?php if (isset($stats['restaurant'])): ?>
        <?php $o = $stats['restaurant']['orders']; ?>
        <div class="stat-card"><div class="s-icon green">🧾</div><div><div class="s-value"><?= (int)$o['total'] ?></div><div class="s-label"><?= e(t('restaurant.orders')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">⏳</div><div><div class="s-value"><?= (int)$o['pending'] ?></div><div class="s-label"><?= e(t('common.pending')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">🔥</div><div><div class="s-value"><?= (int)$o['today'] ?></div><div class="s-label"><?= e(t('restaurant.today') ?? 'Today') ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">💰</div><div><div class="s-value"><?= e(formatMoney((float)$o['revenue'])) ?></div><div class="s-label"><?= e(t('common.revenue') ?? 'Revenue') ?></div></div></div>
        <div class="stat-card"><div class="s-icon navy">🍽️</div><div><div class="s-value"><?= (int)$stats['restaurant']['tables']['total'] ?></div><div class="s-label"><?= e(t('restaurant.tables')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">🪑</div><div><div class="s-value"><?= (int)$stats['restaurant']['tables']['occupied'] ?></div><div class="s-label"><?= e(t('restaurant.occupied') ?? 'Occupied') ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">📦</div><div><div class="s-value"><?= (int)$stats['restaurant']['products'] ?></div><div class="s-label"><?= e(t('restaurant.products')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon red">⚠️</div><div><div class="s-value"><?= (int)$stats['restaurant']['low_stock'] ?></div><div class="s-label"><?= e(t('restaurant.low_stock')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">👥</div><div><div class="s-value"><?= (int)$stats['restaurant']['staff_on_duty'] ?></div><div class="s-label"><?= e(t('restaurant.staff_on_duty')) ?></div></div></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">📈 <?= e(t('restaurant.sales_trend') ?? 'Sales trend') ?> (14)</div>
        <a href="/restaurant/reports" class="btn btn-outline btn-sm" style="margin-left:auto;">📊 <?= e(t('restaurant.reports') ?? 'Reports') ?></a>
    </div>
    <div class="chart-box" id="sales-chart"></div>
</div>

<div class="form-row">
    <div class="card">
        <div class="card-header"><div class="card-title">⏳ <?= e(t('restaurant.recent_orders') ?? 'Recent orders') ?></div>
            <a href="/orders" class="btn btn-outline btn-sm" style="margin-left:auto;">👁 <?= e(t('common.view_all') ?? 'View all') ?></a>
        </div>
        <?php
        $recentOrders = \App\Models\Order::query()->where('business_id', (int)$business['id'])->orderBy('id', 'DESC')->limit(6)->get();
        ?>
        <?php if (empty($recentOrders)): ?>
            <div class="empty-state" style="padding:20px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
            <?php foreach ($recentOrders as $ord): ?>
                <div class="d-flex justify-between items-center" style="padding:9px 0;border-bottom:1px solid var(--border);">
                    <div>
                        <div class="fw-700">#<?= e($ord['order_number']) ?></div>
                        <div class="text-muted text-xs"><?= e(formatDate($ord['ordered_at'])) ?></div>
                    </div>
                    <div class="d-flex items-center gap-2">
                        <span class="fw-700"><?= e(formatMoney((float)$ord['total_amount'])) ?></span>
                        <?= statusBadge((string)$ord['status']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="card">
        <div class="card-header"><div class="card-title">⚠️ <?= e(t('restaurant.low_stock')) ?></div></div>
        <?php
        $lowStockItems = \App\Models\Product::lowStock((int)$business['id']);
        ?>
        <?php if (empty($lowStockItems)): ?>
            <div class="empty-state" style="padding:20px;"><div class="es-title"><?= e(t('restaurant.stock_ok') ?? 'All stock levels OK') ?></div></div>
        <?php else: ?>
            <?php foreach ($lowStockItems as $p): ?>
                <div class="d-flex justify-between items-center" style="padding:9px 0;border-bottom:1px solid var(--border);">
                    <div class="fw-700"><?= e($p['name']) ?></div>
                    <span class="badge badge-danger"><?= e((string)$p['stock_quantity']) ?> / <?= e((string)$p['min_stock']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php
$trendData = [];
foreach ($salesTrend as $row) {
    $trendData[] = ['label' => substr((string)$row['day'], 5), 'value' => (float)$row['revenue']];
}
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    ISHEMA.renderBarChart('sales-chart', <?= json_encode($trendData) ?>, 'label', 'value');
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
