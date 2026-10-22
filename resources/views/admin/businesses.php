<?php
/** @var array $businesses @var string $type @var string $status */
$pageTitle = t('admin.businesses_menu');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🏢 <?= e(t('admin.businesses_menu')) ?></div>
        <a href="/admin/businesses/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('admin.create_business')) ?></a>
    </div>
    <form method="GET" action="/admin/businesses" class="d-flex gap-2 mb-3">
        <select name="type" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('common.type')) ?>s</option>
            <option value="HOTEL"<?= $type === 'HOTEL' ? ' selected' : '' ?>><?= e(t('admin.business_type_hotel')) ?></option>
            <option value="RESTAURANT"<?= $type === 'RESTAURANT' ? ' selected' : '' ?>><?= e(t('admin.business_type_restaurant')) ?></option>
            <option value="HOTEL_AND_RESTAURANT"<?= $type === 'HOTEL_AND_RESTAURANT' ? ' selected' : '' ?>><?= e(t('admin.business_type_both')) ?></option>
        </select>
        <select name="status" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('common.status')) ?>es</option>
            <option value="active"<?= $status === 'active' ? ' selected' : '' ?>><?= e(t('admin.status_active')) ?></option>
            <option value="pending"<?= $status === 'pending' ? ' selected' : '' ?>><?= e(t('admin.status_pending')) ?></option>
            <option value="suspended"<?= $status === 'suspended' ? ' selected' : '' ?>><?= e(t('admin.status_suspended')) ?></option>
        </select>
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.filter')) ?></button>
    </form>
    <?php if (empty($businesses)): ?>
        <div class="empty-state"><div class="es-icon">🏢</div><div class="es-title"><?= e(t('admin.no_businesses')) ?></div><p><?= e(t('admin.create_first_business')) ?></p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('admin.business_id_label')) ?></th>
                    <th><?= e(t('common.name')) ?></th>
                    <th><?= e(t('common.type')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('common.created_at')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($businesses as $b): ?>
                        <tr>
                            <td class="mono"><?= e($b['business_id']) ?></td>
                            <td class="fw-700"><?= e($b['name']) ?></td>
                            <td><?= e(t('admin.business_type_' . strtolower((string)$b['type']))) ?></td>
                            <td><?= statusBadge((string)$b['status']) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($b['created_at'])) ?></td>
                            <td class="actions">
                                <a href="/admin/businesses/<?= (int)$b['id'] ?>/view" class="btn btn-sm btn-outline">👁 <?= e(t('common.view')) ?></a>
                                <a href="/admin/businesses/<?= (int)$b['id'] ?>/edit" class="btn btn-sm btn-outline">✏️ <?= e(t('common.edit')) ?></a>
                                <button class="btn btn-sm <?= $b['status'] === 'active' ? 'btn-outline' : 'btn-success' ?>" onclick="ISHEMA.api('/admin/businesses/<?= (int)$b['id'] ?>/toggle-status', {}).then(r => location.reload())">
                                    <?= $b['status'] === 'active' ? '⏸ ' . e(t('common.suspend')) : '▶ ' . e(t('common.activate')) ?>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/admin/businesses/<?= (int)$b['id'] ?>/archive', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
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
