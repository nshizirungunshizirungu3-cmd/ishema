<?php
/** @var array $stats */
$pageTitle = t('admin.admin_title');
ob_start();
$regs = [];
foreach ($stats['registrations'] as $r) {
    $regs[] = ['day' => date('M d', strtotime((string)$r['day'])), 'total' => (int)$r['total']];
}
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="stat-grid">
    <div class="stat-card"><div class="s-icon navy">🏢</div><div><div class="s-value"><?= (int)$stats['businesses']['total'] ?></div><div class="s-label"><?= e(t('admin.stats_businesses')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon blue">🏨</div><div><div class="s-value"><?= (int)$stats['businesses']['hotels'] ?></div><div class="s-label"><?= e(t('admin.stats_hotels')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon green">🍽️</div><div><div class="s-value"><?= (int)$stats['businesses']['restaurants'] ?></div><div class="s-label"><?= e(t('admin.stats_restaurants')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon orange">👤</div><div><div class="s-value"><?= (int)$stats['owners'] ?></div><div class="s-label"><?= e(t('admin.stats_owners')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon orange">👥</div><div><div class="s-value"><?= (int)$stats['managers'] ?></div><div class="s-label"><?= e(t('admin.stats_managers')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon green">🧑‍💼</div><div><div class="s-value"><?= (int)$stats['employees'] ?></div><div class="s-label"><?= e(t('admin.stats_employees')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon blue">🧑‍🤝‍🧑</div><div><div class="s-value"><?= (int)$stats['customers'] ?></div><div class="s-label"><?= e(t('admin.stats_customers')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon green">📋</div><div><div class="s-value"><?= (int)$stats['subscriptions']['active'] ?></div><div class="s-label"><?= e(t('admin.stats_active_subs')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon red">⏳</div><div><div class="s-value"><?= (int)$stats['subscriptions']['expired'] ?></div><div class="s-label"><?= e(t('admin.stats_expired_subs')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon green">💰</div><div><div class="s-value"><?= e(formatMoney((float)$stats['revenue']['successful'], (string)$stats['revenue']['currency'])) ?></div><div class="s-label"><?= e(t('admin.stats_revenue')) ?></div></div></div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">📈 <?= e(t('common.recent_registrations')) ?></div></div>
    <div class="card-body">
        <div id="regChart" style="height:240px;"></div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title">🏢 <?= e(t('common.recent')) ?> <?= e(t('admin.stats_businesses')) ?></div>
            <a href="/admin/businesses" class="btn btn-outline btn-sm" style="margin-left:auto;"><?= e(t('common.view_all')) ?></a>
        </div>
        <?php if (empty($stats['recent_businesses'])): ?>
            <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('admin.no_businesses')) ?></div><p><?= e(t('admin.create_first_business')) ?></p></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th><?= e(t('admin.business_id_label')) ?></th><th><?= e(t('common.name')) ?></th><th><?= e(t('common.type')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('common.created_at')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($stats['recent_businesses'] as $b): ?>
                        <tr>
                            <td class="mono text-muted"><?= e($b['business_id']) ?></td>
                            <td class="fw-700"><a href="/admin/businesses/<?= (int)$b['id'] ?>/view"><?= e($b['name']) ?></a></td>
                            <td><?= e(t('admin.business_type_' . strtolower((string)$b['type']))) ?></td>
                            <td><?= statusBadge((string)$b['status']) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($b['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">📊 <?= e(t('admin.subscription_analytics')) ?></div></div>
        <?php if (empty($stats['package_analytics'])): ?>
            <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th><?= e(t('admin.package_name')) ?></th><th><?= e(t('admin.subscribers')) ?></th><th><?= e(t('admin.price_monthly')) ?></th><th><?= e(t('admin.price_yearly')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($stats['package_analytics'] as $pa): ?>
                        <tr>
                            <td class="fw-700"><?= e($pa['name']) ?></td>
                            <td><?= (int)$pa['subs'] ?></td>
                            <td><?= e(formatMoney((float)$pa['price_monthly'])) ?></td>
                            <td><?= e(formatMoney((float)$pa['price_yearly'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('regChart') && typeof ISHEMA !== 'undefined' && typeof ISHEMA.renderBarChart === 'function') {
        ISHEMA.renderBarChart('regChart', <?= json_encode($regs) ?>, 'day', 'total');
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
