<?php
/** @var array $users @var string $role */
$pageTitle = t('admin.users_menu');
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
        <div class="card-title">👥 <?= e(t('admin.users_menu')) ?></div>
        <a href="/admin/users/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('admin.create_user')) ?></a>
    </div>
    <form method="GET" action="/admin/users" class="d-flex gap-2 mb-3">
        <select name="role" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('admin.roles')) ?></option>
            <?php
            $roles = ['SUPER_ADMIN', 'BUSINESS_OWNER', 'MANAGER', 'RECEPTIONIST', 'WAITER', 'KITCHEN_STAFF', 'CASHIER', 'HOUSEKEEPING', 'CUSTOMER'];
            foreach ($roles as $r): ?>
                <option value="<?= e($r) ?>"<?= $role === $r ? ' selected' : '' ?>><?= e(t('admin.staff_role_' . strtolower($r))) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.filter')) ?></button>
    </form>
    <?php if (empty($users)): ?>
        <div class="empty-state"><div class="es-icon">👥</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('common.name')) ?></th>
                    <th><?= e(t('common.email')) ?></th>
                    <th><?= e(t('admin.role')) ?></th>
                    <th><?= e(t('common.language')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="fw-700"><?= e($u['name']) ?></td>
                            <td class="text-muted"><?= e($u['email']) ?></td>
                            <td><span class="badge badge-info"><?= e(t('admin.staff_role_' . strtolower((string)$u['role']))) ?></span></td>
                            <td class="text-muted text-sm"><?= e(strtoupper((string)$u['language'])) ?></td>
                            <td><?= (int)$u['is_active'] ? '<span class="badge badge-active">● ' . e(t('common.active')) . '</span>' : '<span class="badge badge-inactive">● ' . e(t('common.inactive')) . '</span>' ?></td>
                            <td class="actions">
                                <a href="/admin/users/<?= (int)$u['id'] ?>/edit" class="btn btn-sm btn-outline">✏️ <?= e(t('common.edit')) ?></a>
                                <button class="btn btn-sm <?= (int)$u['is_active'] ? 'btn-outline' : 'btn-success' ?>" onclick="ISHEMA.api('/admin/users/<?= (int)$u['id'] ?>/toggle-status', {}).then(r => location.reload())">
                                    <?= (int)$u['is_active'] ? '⏸ ' . e(t('common.deactivate')) : '▶ ' . e(t('common.activate')) ?>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/admin/users/<?= (int)$u['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
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
