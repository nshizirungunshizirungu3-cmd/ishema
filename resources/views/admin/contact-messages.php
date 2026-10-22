<?php
/** @var array $messages */
$pageTitle = t('admin.contact_messages');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><div class="card-title">📬 <?= e(t('admin.contact_messages')) ?> (<?= count($messages) ?>)</div></div>
    <?php if (empty($messages)): ?>
        <div class="empty-state"><div class="es-icon">📬</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('common.name')) ?></th>
                    <th><?= e(t('common.email')) ?></th>
                    <th><?= e(t('common.subject')) ?></th>
                    <th><?= e(t('common.message')) ?></th>
                    <th><?= e(t('common.created_at')) ?></th>
                    <th><?= e(t('common.status') )?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($messages as $m): ?>
                        <tr<?= (int)$m['is_read'] ? '' : ' class="fw-700"' ?>>
                            <td><?= e($m['name']) ?></td>
                            <td class="text-muted"><?= e($m['email']) ?><?= !empty($m['phone']) ? '<br><span class="text-sm">📞 ' . e($m['phone']) . '</span>' : '' ?></td>
                            <td><?= e((string)($m['subject'] ?? '—')) ?><?= !empty($m['business_type']) ? '<br><span class="badge badge-info">' . e($m['business_type']) . '</span>' : '' ?></td>
                            <td class="text-sm" style="max-width:340px;"><?= e($m['message']) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($m['created_at'])) ?></td>
                            <td><?= (int)$m['is_read'] ? '<span class="badge badge-active">● ' . e(t('admin.read')) . '</span>' : '<span class="badge badge-inactive">● ' . e(t('admin.unread')) . '</span>' ?></td>
                            <td class="actions">
                                <?php if (!(int)$m['is_read']): ?>
                                    <button class="btn btn-sm btn-outline" onclick="ISHEMA.api('/admin/contact-messages/<?= (int)$m['id'] ?>/read', {}).then(r => location.reload())">✓ <?= e(t('admin.read')) ?></button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/admin/contact-messages/<?= (int)$m['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
                            </td>
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
