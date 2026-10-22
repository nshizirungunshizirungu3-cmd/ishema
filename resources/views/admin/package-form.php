<?php
/** @var array|null $package */
$pageTitle = $package ? t('admin.edit_package') : t('admin.create_package');
ob_start();
$modules = ['restaurant', 'hotel', 'qr_menu', 'online_booking', 'inventory', 'staff', 'reports', 'payments'];
$featuresList = [
    'Online QR menu', 'Table management', 'Inventory tracking', 'Room booking',
    'Housekeeping', 'Multi-branch', 'Advanced reports', 'Staff scheduling',
    'Customer portal', 'Multi-language', 'Payment integration', 'Email notifications',
];
$pkgModules = is_array($package['modules'] ?? null) ? $package['modules'] : [];
$pkgFeatures = is_array($package['features'] ?? null) ? $package['features'] : [];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:900px;">
    <div class="card-header"><div class="card-title">📦 <?= $package ? e(t('admin.edit_package')) : e(t('admin.create_package')) ?></div></div>
    <form method="POST" action="<?= $package ? url('/admin/packages/' . $package['id'] . '/update') : url('/admin/packages/create') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.name')) ?> <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($package['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.sort_order')) ?></label>
                <input type="number" name="sort_order" class="form-control" value="<?= e((string)($package['sort_order'] ?? 0)) ?>">
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.price_monthly')) ?></label>
                <input type="number" step="0.01" min="0" name="price_monthly" class="form-control" value="<?= e((string)($package['price_monthly'] ?? 0)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.price_yearly')) ?></label>
                <input type="number" step="0.01" min="0" name="price_yearly" class="form-control" value="<?= e((string)($package['price_yearly'] ?? 0)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.support_level')) ?></label>
                <select name="support_level" class="form-select">
                    <option value="email"<?= ($package['support_level'] ?? 'email') === 'email' ? ' selected' : '' ?>><?= e(t('admin.support_email')) ?></option>
                    <option value="priority"<?= ($package['support_level'] ?? '') === 'priority' ? ' selected' : '' ?>><?= e(t('admin.support_priority')) ?></option>
                    <option value="dedicated"<?= ($package['support_level'] ?? '') === 'dedicated' ? ' selected' : '' ?>><?= e(t('admin.support_dedicated')) ?></option>
                </select>
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.max_employees')) ?></label>
                <input type="number" min="0" name="max_employees" class="form-control" value="<?= e((string)($package['max_employees'] ?? 5)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.max_rooms')) ?></label>
                <input type="number" min="0" name="max_rooms" class="form-control" value="<?= e((string)($package['max_rooms'] ?? 0)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.max_tables')) ?></label>
                <input type="number" min="0" name="max_tables" class="form-control" value="<?= e((string)($package['max_tables'] ?? 0)) ?>">
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.max_products')) ?></label>
                <input type="number" min="0" name="max_products" class="form-control" value="<?= e((string)($package['max_products'] ?? 100)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.max_branches')) ?></label>
                <input type="number" min="0" name="max_branches" class="form-control" value="<?= e((string)($package['max_branches'] ?? 1)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.status')) ?></label>
                <select name="status" class="form-select">
                    <option value="active"<?= ($package['status'] ?? 'active') === 'active' ? ' selected' : '' ?>><?= e(t('admin.status_active')) ?></option>
                    <option value="inactive"<?= ($package['status'] ?? '') === 'inactive' ? ' selected' : '' ?>><?= e(t('common.inactive')) ?></option>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">📊 <?= e(t('admin.has_reports')) ?></label>
            <label class="d-flex items-center gap-2">
                <input type="checkbox" name="has_reports" value="1"<?= (int)($package['has_reports'] ?? 1) ? ' checked' : '' ?>>
                <span class="text-sm"><?= e(t('admin.has_reports')) ?></span>
            </label>
        </div>
        <div class="form-group">
            <label class="form-label">🧩 <?= e(t('admin.modules')) ?></label>
            <div class="form-row-3">
                <?php foreach ($modules as $m): ?>
                    <label class="d-flex items-center gap-2">
                        <input type="checkbox" name="modules[]" value="<?= e($m) ?>"<?= in_array($m, $pkgModules, true) ? ' checked' : '' ?>>
                        <span class="text-sm"><?= e($m) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">✨ <?= e(t('admin.features')) ?></label>
            <div class="form-row-3">
                <?php foreach ($featuresList as $f): ?>
                    <label class="d-flex items-center gap-2">
                        <input type="checkbox" name="features[]" value="<?= e($f) ?>"<?= in_array($f, $pkgFeatures, true) ? ' checked' : '' ?>>
                        <span class="text-sm"><?= e($f) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            <a href="/admin/packages" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
