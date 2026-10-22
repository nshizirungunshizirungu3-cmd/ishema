<?php
/** @var array $business @var array|null $subscription @var array $packages @var array $history */
$pageTitle = t('common.subscription');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<?php if ($subscription): ?>
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title"><?= e(t('common.subscription')) ?></div>
                <div class="card-sub"><?= e($subscription['package']['name'] ?? '-') ?></div>
            </div>
            <?= statusBadge((string)$subscription['status']) ?>
        </div>
        <div class="form-row-3">
            <div class="info-panel">
                <div class="text-muted text-sm"><?= e(t('admin.subscription_start')) ?></div>
                <div class="fw-700"><?= e(formatDate($subscription['starts_at'])) ?></div>
            </div>
            <div class="info-panel">
                <div class="text-muted text-sm"><?= e(t('admin.subscription_expiry')) ?></div>
                <div class="fw-700"><?= e(formatDate($subscription['expires_at'])) ?></div>
            </div>
            <div class="info-panel">
                <div class="text-muted text-sm"><?= e(t('common.amount')) ?></div>
                <div class="fw-700"><?= e(formatMoney($subscription['amount'])) ?> / <?= e($subscription['billing_cycle']) ?></div>
            </div>
        </div>
        <?php
        $daysLeft = max(0, (int)((strtotime((string)$subscription['expires_at']) - time()) / 86400));
        if ($subscription['status'] === 'active' && $daysLeft <= 7): ?>
            <div class="alert alert-warning mt-3">⚠️ <?= e(t('admin.subscription_expiry_warning')) ?>: <?= $daysLeft ?> <?= e(t('admin.days_remaining')) ?></div>
        <?php endif; ?>
        <?php if ($subscription['status'] === 'expired'): ?>
            <div class="alert alert-danger">⚠️ <?= e(t('common.expired')) ?>. <?= e(t('common.subscription') . ' ' . t('common.renew') ?? 'Renew your subscription to continue full operations.') ?></div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning">📋 <?= e(t('common.subscription') . ' not found. Contact the platform administrator.') ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><div class="card-title"><?= e(t('admin.manage_packages')) ?></div></div>
    <div class="feature-grid">
        <?php foreach ($packages as $p): ?>
            <div class="package-card">
                <div class="p-name"><?= e($p['name']) ?></div>
                <div class="p-price"><?= e(number_format((float)$p['price_monthly'])) ?> <span><?= e(t('public.per_month')) ?></span></div>
                <ul class="p-features">
                    <li><?= e(t('public.package_employees')) ?>: <?= (int)$p['max_employees'] ?></li>
                    <li><?= e(t('public.package_tables')) ?>: <?= (int)$p['max_tables'] ?></li>
                    <li><?= e(t('public.package_products')) ?>: <?= (int)$p['max_products'] ?></li>
                    <?php if ((int)$p['max_rooms'] > 0): ?><li><?= e(t('public.package_rooms')) ?>: <?= (int)$p['max_rooms'] ?></li><?php endif; ?>
                </ul>
                <div class="text-muted text-xs mb-2">
                    <?= e(t('admin.package_name')) ?>: <?= e($p['name']) ?> ·
                    <?= e(t('public.package_support')) ?>: <?= e($p['support_level']) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <p class="text-muted text-sm mt-2">💡 <?= e(t('common.subscription') . ' upgrades are handled by the platform administrator. Contact support to change packages.') ?></p>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">📜 <?= e(t('common.history')) ?></div></div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th><?= e(t('common.package') ?? 'Package') ?></th>
                <th><?= e(t('admin.billing_cycle')) ?></th>
                <th><?= e(t('admin.subscription_start')) ?></th>
                <th><?= e(t('admin.subscription_expiry')) ?></th>
                <th><?= e(t('common.status')) ?></th>
                <th><?= e(t('common.amount')) ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?= e($h['package']['name'] ?? '-') ?></td>
                        <td><?= e($h['billing_cycle']) ?></td>
                        <td><?= e(formatDate($h['starts_at'])) ?></td>
                        <td><?= e(formatDate($h['expires_at'])) ?></td>
                        <td><?= statusBadge((string)$h['status']) ?></td>
                        <td><?= e(formatMoney($h['amount'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
