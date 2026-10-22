<?php
/** @var array $tutorials */
$pageTitle = t('admin.manage_tutorials');
ob_start();
$cats = ['hotel', 'restaurant', 'owner', 'employee'];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🎥 <?= e(t('admin.manage_tutorials')) ?></div>
        <a href="/admin/tutorials/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('admin.create_tutorial')) ?></a>
    </div>
    <?php if (empty($tutorials)): ?>
        <div class="empty-state"><div class="es-icon">🎥</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('admin.title')) ?></th>
                    <th><?= e(t('admin.category')) ?></th>
                    <th><?= e(t('common.language')) ?></th>
                    <th><?= e(t('common.duration')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('common.actions') )?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($tutorials as $tut): ?>
                        <tr>
                            <td class="fw-700"><?= e($tut['title']) ?></td>
                            <td>
                                <?php if (in_array((string)$tut['category'], $cats, true)): ?>
                                    <?= e(t('public.tutorial_category_' . (string)$tut['category'])) ?>
                                <?php else: ?>
                                    <?= e(ucfirst((string)$tut['category'])) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted text-sm"><?= e(strtoupper((string)$tut['language'])) ?></td>
                            <td class="text-muted"><?= e((string)($tut['duration'] ?? '—')) ?></td>
                            <td><?= (int)$tut['is_active'] ? '<span class="badge badge-active">● ' . e(t('common.active')) . '</span>' : '<span class="badge badge-inactive">● ' . e(t('common.inactive')) . '</span>' ?></td>
                            <td class="actions">
                                <a href="/admin/tutorials/<?= (int)$tut['id'] ?>/edit" class="btn btn-sm btn-outline">✏️ <?= e(t('common.edit')) ?></a>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/admin/tutorials/<?= (int)$tut['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
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
