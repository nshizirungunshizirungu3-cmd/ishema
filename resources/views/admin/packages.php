<?php
/** @var array $packages */
$pageTitle = t('admin.packages_menu');
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
        <div class="card-title">📦 <?= e(t('admin.packages_menu')) ?></div>
        <a href="/admin/packages/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('admin.create_package')) ?></a>
    </div>
    <div class="stat-grid">
        <?php foreach ($packages as $p): ?>
            <div class="card" style="border-top:4px solid var(--primary);">
                <div class="card-header">
                    <div class="card-title"><?= e($p['name']) ?> <?= (int)$p['is_default'] ? '<span class="badge badge-info">' . e(t('common.default')) . '</span>' : '' ?></div>
                    <?= (int)$p['status'] === 1 || $p['status'] === 'active' ? '<span class="badge badge-active">● ' . e(t('admin.status_active')) . '</span>' : '<span class="badge badge-inactive">● ' . e(t('common.inactive')) . '</span>' ?>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-between items-baseline mb-2">
                        <div>
                            <div class="s-value"><?= e(formatMoney((float)$p['price_monthly'])) ?></div>
                            <div class="text-muted text-xs"><?= e(t('public.per_month')) ?></div>
                        </div>
                        <div class="text-right">
                            <div class="fw-700"><?= e(formatMoney((float)$p['price_yearly'])) ?></div>
                            <div class="text-muted text-xs"><?= e(t('public.per_year')) ?></div>
                        </div>
                    </div>
                    <ul class="feature-list">
                        <li>👥 <?= (int)$p['max_employees'] ?> <?= e(t('admin.max_employees')) ?></li>
                        <?php if ((int)$p['max_rooms'] > 0): ?><li>🛏️ <?= (int)$p['max_rooms'] ?> <?= e(t('common.rooms')) ?></li><?php endif; ?>
                        <?php if ((int)$p['max_tables'] > 0): ?><li>🍽️ <?= (int)$p['max_tables'] ?> <?= e(t('common.tables')) ?></li><?php endif; ?>
                        <li>📦 <?= (int)$p['max_products'] ?> <?= e(t('common.products')) ?></li>
                        <li>🏢 <?= (int)$p['max_branches'] ?> <?= e(t('admin.max_branches')) ?></li>
                        <li>🎓 <?= e(t('admin.support_' . (string)$p['support_level'])) ?></li>
                    </ul>
                    <?php if (!empty($p['features']) && is_array($p['features'])): ?>
                        <hr style="border:none;border-top:1px solid var(--border);">
                        <?php foreach ($p['features'] as $f): ?>
                            <div class="text-sm">✓ <?= e((string)$f) ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <div class="d-flex gap-2 mt-3">
                        <a href="/admin/packages/<?= (int)$p['id'] ?>/edit" class="btn btn-outline btn-sm">✏️ <?= e(t('common.edit')) ?></a>
                        <button class="btn btn-danger btn-sm" onclick="ISHEMA.confirmDelete('/admin/packages/<?= (int)$p['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑 <?= e(t('common.delete')) ?></button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
