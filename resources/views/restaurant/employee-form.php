<?php
/** @var array|null $employee @var array $tables */
$pageTitle = $employee ? t('restaurant.edit_employee') : t('restaurant.add_employee');
ob_start();
$roles = ['MANAGER', 'RECEPTIONIST', 'WAITER', 'KITCHEN_STAFF', 'CASHIER', 'HOUSEKEEPING'];
$roleLabels = [
    'MANAGER' => t('admin.staff_role_manager'),
    'RECEPTIONIST' => t('admin.staff_role_receptionist'),
    'WAITER' => t('admin.staff_role_waiter'),
    'KITCHEN_STAFF' => t('admin.staff_role_kitchen_staff'),
    'CASHIER' => t('admin.staff_role_cashier'),
    'HOUSEKEEPING' => t('admin.staff_role_housekeeping'),
];
$assigned = is_array($employee['assigned_tables'] ?? null) ? $employee['assigned_tables'] : (array)json_decode((string)($employee['assigned_tables'] ?? '[]'), true);
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:760px;">
    <div class="card-header">
        <div class="card-title">👤 <?= $employee ? e(t('restaurant.edit_employee')) : e(t('restaurant.add_employee')) ?></div>
        <a href="/restaurant/staff" class="btn btn-outline btn-sm" style="margin-left:auto;">← <?= e(t('common.back')) ?></a>
    </div>
    <form method="POST" action="<?= $employee ? url('/restaurant/staff/' . $employee['id'] . '/update') : url('/restaurant/staff/create') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.employee_name')) ?> <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($employee['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.email')) ?> <span class="req">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= e($employee['email'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.phone')) ?></label>
                <input type="tel" name="phone" class="form-control" value="<?= e($employee['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.employee_role') )?></label>
                <select name="role" class="form-select">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= e($r) ?>"<?= ($employee['role'] ?? 'WAITER') === $r ? ' selected' : '' ?>><?= e($roleLabels[$r]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('restaurant.assign_tables')) ?></label>
            <div class="form-row-3">
                <?php foreach ($tables as $t): ?>
                    <label class="d-flex items-center gap-2">
                        <input type="checkbox" name="assigned_tables[]" value="<?= (int)$t['id'] ?>"<?= in_array((int)$t['id'], $assigned, true) ? ' checked' : '' ?>>
                        <span class="text-sm">#<?= e($t['table_number']) ?><?= $t['name'] ? ' — ' . e($t['name']) : '' ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            <a href="/restaurant/staff" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
