<?php
/** @var array|null $user */
$pageTitle = $user ? t('admin.edit_user') : t('admin.create_user');
ob_start();
$roles = ['BUSINESS_OWNER', 'MANAGER', 'RECEPTIONIST', 'WAITER', 'KITCHEN_STAFF', 'CASHIER', 'HOUSEKEEPING', 'CUSTOMER'];
$langs = \App\Core\Translator::LANGS;
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:760px;">
    <div class="card-header"><div class="card-title">👤 <?= $user ? e(t('admin.edit_user')) : e(t('admin.create_user')) ?></div></div>
    <form method="POST" action="<?= $user ? url('/admin/users/' . $user['id'] . '/update') : url('/admin/users/create') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.full_name')) ?> <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.email')) ?> <span class="req">*</span></label>
                <input type="email" name="email" class="form-control" value="<?= e($user['email'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.phone')) ?></label>
                <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.role')) ?></label>
                <select name="role" class="form-select">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= e($r) ?>"<?= ($user['role'] ?? 'CUSTOMER') === $r ? ' selected' : '' ?>><?= e(t('admin.staff_role_' . strtolower($r))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.language')) ?></label>
                <select name="language" class="form-select">
                    <?php foreach ($langs as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= ($user['language'] ?? 'en') === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.status')) ?></label>
                <label class="d-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1"<?= !isset($user) || (int)$user['is_active'] ? ' checked' : '' ?>>
                    <span class="text-sm"><?= e(t('common.active')) ?></span>
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">🔑 <?= e(t('common.password')) ?> <?= $user ? '<span class="text-muted text-xs">(' . e(t('common.leave_blank')) . ')</span>' : '<span class="text-muted text-xs">(' . e(t('common.optional')) . ')</span>' ?></label>
            <input type="password" name="password" class="form-control" autocomplete="new-password">
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            <a href="/admin/users" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
