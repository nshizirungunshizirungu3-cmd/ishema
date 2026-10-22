<?php
/** @var array $logs */
$pageTitle = t('admin.audit_logs');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><div class="card-title">📜 <?= e(t('admin.audit_logs')) ?> (<?= count($logs) ?>)</div></div>
    <?php if (empty($logs)): ?>
        <div class="empty-state"><div class="es-icon">📜</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('common.created_at')) ?></th>
                    <th><?= e(t('admin.user')) ?></th>
                    <th><?= e(t('admin.business_name')) ?></th>
                    <th><?= e(t('admin.action')) ?></th>
                    <th><?= e(t('admin.entity')) ?></th>
                    <th><?= e(t('admin.ip_address')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-muted text-sm"><?= e(formatDate($log['created_at'])) ?></td>
                            <td class="fw-700"><?= e((string)($log['user_name'] ?? '—')) ?></td>
                            <td><?= e((string)($log['business_name'] ?? '—')) ?></td>
                            <td><span class="badge badge-info"><?= e((string)$log['action']) ?></span></td>
                            <td class="text-muted"><?= e((string)($log['entity'] ?? '—')) ?><?= $log['entity_id'] !== null ? ' <span class="mono">#' . e((string)$log['entity_id']) . '</span>' : '' ?></td>
                            <td class="mono text-muted text-sm"><?= e((string)($log['ip_address'] ?? '—')) ?></td>
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
