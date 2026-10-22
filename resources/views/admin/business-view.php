<?php
/** @var array $business @var array|null $subscription @var array $staff @var array $overview */
$pageTitle = e($business['name']);
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="d-flex justify-between items-center mb-3">
    <div>
        <h2 style="margin:0;"><?= e($business['name']) ?> <span class="mono text-muted" style="font-size:14px;"><?= e($business['business_id']) ?></span></h2>
        <div class="text-muted text-sm">
            📍 <?= e(trim(($business['address'] ?? '') . ' ' . ($business['city'] ?? '') . ' ' . ($business['country'] ?? ''))) ?: e(t('common.not_specified')) ?>
            · 📧 <?= e($business['email'] ?? '—') ?> · 📞 <?= e($business['phone'] ?? '—') ?>
        </div>
    </div>
    <div class="d-flex items-center gap-2">
        <?= statusBadge((string)$business['status']) ?>
        <span class="badge badge-info"><?= e(t('admin.business_type_' . strtolower((string)$business['type']))) ?></span>
        <a href="/admin/businesses/<?= (int)$business['id'] ?>/edit" class="btn btn-outline btn-sm">✏️ <?= e(t('common.edit')) ?></a>
    </div>
</div>

<?php if ($business['description']): ?>
    <div class="card"><div class="card-body"><?= e($business['description']) ?></div></div>
<?php endif; ?>

<div class="stat-grid">
    <?php if (isset($overview['restaurant'])): ?>
        <?php $r = $overview['restaurant']['orders']; ?>
        <div class="stat-card"><div class="s-icon green">🧾</div><div><div class="s-value"><?= (int)$r['total'] ?></div><div class="s-label"><?= e(t('common.orders')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">⏳</div><div><div class="s-value"><?= (int)$r['pending'] ?></div><div class="s-label"><?= e(t('common.pending')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">🍽️</div><div><div class="s-value"><?= (int)$overview['restaurant']['tables']['total'] ?></div><div class="s-label"><?= e(t('common.tables')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon navy">📦</div><div><div class="s-value"><?= (int)$overview['restaurant']['products'] ?></div><div class="s-label"><?= e(t('common.products')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon red">⚠️</div><div><div class="s-value"><?= (int)$overview['restaurant']['low_stock'] ?></div><div class="s-label"><?= e(t('restaurant.low_stock')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">👥</div><div><div class="s-value"><?= (int)$overview['restaurant']['staff_on_duty'] ?></div><div class="s-label"><?= e(t('restaurant.staff_on_duty')) ?></div></div></div>
    <?php endif; ?>
    <?php if (isset($overview['hotel'])): ?>
        <?php $h = $overview['hotel']; ?>
        <div class="stat-card"><div class="s-icon navy">🛏️</div><div><div class="s-value"><?= (int)$h['rooms']['total'] ?></div><div class="s-label"><?= e(t('hotel.total_rooms')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">✅</div><div><div class="s-value"><?= (int)$h['rooms']['available'] ?></div><div class="s-label"><?= e(t('hotel.available_rooms')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">🧍</div><div><div class="s-value"><?= (int)$h['rooms']['occupied'] ?></div><div class="s-label"><?= e(t('hotel.occupied_rooms')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon blue">📅</div><div><div class="s-value"><?= (int)$h['bookings']['total'] ?></div><div class="s-label"><?= e(t('common.bookings')) ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">🧹</div><div><div class="s-value"><?= (int)$h['housekeeping']['pending'] ?></div><div class="s-label"><?= e(t('common.housekeeping')) ?></div></div></div>
    <?php endif; ?>
    <div class="stat-card"><div class="s-icon green">💰</div><div><div class="s-value"><?= e(formatMoney((float)$overview['payments']['total'], 'RWF')) ?></div><div class="s-label"><?= e(t('common.revenue')) ?></div></div></div>
    <?php if (isset($overview['reviews'])): ?>
        <div class="stat-card"><div class="s-icon blue">⭐</div><div><div class="s-value"><?= e(number_format((float)$overview['reviews']['overall'], 1)) ?></div><div class="s-label"><?= e(t('common.rating')) ?> (<?= (int)$overview['reviews']['total'] ?>)</div></div></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">📋 <?= e(t('common.subscription')) ?></div></div>
    <?php if ($subscription): ?>
        <div class="d-flex justify-between items-center" style="flex-wrap:wrap;gap:12px;">
            <div>
                <div class="fw-700"><?= e($subscription['package']['name'] ?? t('common.subscription')) ?></div>
                <div class="text-muted text-sm">
                    <?= e(t('admin.subscription_start')) ?>: <?= e(formatDate($subscription['starts_at'])) ?>
                    · <?= e(t('admin.subscription_expiry')) ?>: <?= e(formatDate($subscription['expires_at'])) ?>
                    · <?= e(t('admin.billing_cycle')) ?>: <?= e($subscription['billing_cycle']) ?>
                </div>
            </div>
            <?= statusBadge((string)$subscription['status']) ?>
        </div>
    <?php else: ?>
        <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">👥 <?= e(t('admin.staff')) ?> (<?= count($staff) ?>)</div></div>
    <?php if (empty($staff)): ?>
        <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th><?= e(t('common.name')) ?></th><th><?= e(t('common.email')) ?></th><th><?= e(t('admin.role')) ?></th><th><?= e(t('common.status')) ?></th></tr></thead>
            <tbody>
                <?php foreach ($staff as $s): ?>
                    <tr>
                        <td class="fw-700"><?= e($s['name']) ?></td>
                        <td class="text-muted"><?= e($s['email']) ?></td>
                        <td><span class="badge badge-info"><?= e(t('admin.staff_role_' . strtolower((string)$s['role']))) ?></span></td>
                        <td><?= (int)$s['is_active'] ? '<span class="badge badge-active">●</span> ' . e(t('common.active')) : '<span class="badge badge-inactive">●</span> ' . e(t('common.inactive')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
