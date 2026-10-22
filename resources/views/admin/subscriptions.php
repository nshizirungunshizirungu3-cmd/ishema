<?php
/** @var array $subscriptions */
$pageTitle = t('admin.subscriptions_menu');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><div class="card-title">📋 <?= e(t('admin.subscriptions_menu')) ?> (<?= count($subscriptions) ?>)</div></div>
    <?php if (empty($subscriptions)): ?>
        <div class="empty-state"><div class="es-icon">📋</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('admin.business_name')) ?></th>
                    <th><?= e(t('admin.package_name')) ?></th>
                    <th><?= e(t('common.billing_cycle')) ?></th>
                    <th><?= e(t('common.starts_at')) ?></th>
                    <th><?= e(t('common.expires_at')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('common.created_at')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($subscriptions as $s): ?>
                        <tr>
                            <td class="fw-700"><a href="/admin/businesses/<?= (int)$s['business_id'] ?>/view"><?= e($s['business_name']) ?></a> <span class="mono text-muted text-sm"><?= e($s['business_id']) ?></span></td>
                            <td><?= e((string)($s['package_name'] ?? '—')) ?></td>
                            <td><?= e(t('common.' . ((string)$s['billing_cycle'] === 'yearly' ? 'yearly' : 'monthly'))) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($s['starts_at'])) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($s['expires_at'])) ?></td>
                            <td><?= statusBadge((string)$s['status']) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($s['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
