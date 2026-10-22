<?php
/** @var array $booking @var array $business @var array $guest @var array $services */
$pageTitle = $booking['booking_reference'];
ob_start();
?>
<div style="max-width:760px;margin:40px auto;">
    <div class="d-flex justify-between items-center mb-3">
        <div>
            <h2 class="mono" style="margin:0;"><?= e($booking['booking_reference']) ?></h2>
            <div class="text-muted text-sm">🏢 <?= e($business['name']) ?></div>
        </div>
        <?= statusBadge((string)$booking['status']) ?>
    </div>

    <div class="stat-grid">
        <div class="stat-card"><div class="s-icon blue">📥</div><div><div class="s-value"><?= e($booking['check_in_date']) ?></div><div class="s-label"><?= e(t('hotel.check_in') ?? 'Check-in') ?></div></div></div>
        <div class="stat-card"><div class="s-icon navy">📤</div><div><div class="s-value"><?= e($booking['check_out_date']) ?></div><div class="s-label"><?= e(t('hotel.check_out') ?? 'Check-out') ?></div></div></div>
        <div class="stat-card"><div class="s-icon green">🌙</div><div><div class="s-value"><?= (int)$booking['nights'] ?></div><div class="s-label"><?= e(t('hotel.nights') ?? 'Nights') ?></div></div></div>
        <div class="stat-card"><div class="s-icon orange">💰</div><div><div class="s-value"><?= e(formatMoney((float)$booking['total_amount'])) ?></div><div class="s-label"><?= e(t('common.total') ?? 'Total') ?></div></div></div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">🧑‍🤝‍🧑 <?= e(t('hotel.guest_details') ?? 'Guest details') ?></div></div>
        <div class="card-body">
            <div class="fw-700"><?= e($guest['first_name'] . ' ' . $guest['last_name']) ?></div>
            <div class="text-muted text-sm">
                📞 <?= e($guest['phone'] ?? '—') ?> · 📧 <?= e($guest['email'] ?? '—') ?>
                · 🌍 <?= e($guest['country'] ?? '—') ?>
                · 🧑‍🤝‍🧑 <?= (int)$booking['adults'] ?><?= (int)$booking['children'] > 0 ? ' +' . (int)$booking['children'] . ' ' . e(t('hotel.children') ?? 'children') : '' ?>
            </div>
        </div>
    </div>

    <?php if ($services): ?>
        <div class="card">
            <div class="card-header"><div class="card-title">🛎️ <?= e(t('hotel.services') ?? 'Services') ?></div></div>
            <?php foreach ($services as $svc): ?>
                <div class="d-flex justify-between items-center" style="padding:9px 0;border-bottom:1px solid var(--border);">
                    <div>
                        <div class="fw-700 text-sm"><?= e($svc['title']) ?></div>
                        <div class="text-muted text-xs"><?= e($svc['service_type']) ?></div>
                    </div>
                    <?= statusBadge((string)$svc['status']) ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <a href="<?= e(url('/book/' . $business['business_id'])) ?>" class="btn btn-outline">📅 <?= e(t('hotel.book_another') ?? 'Book another stay') ?></a>
        <a href="<?= e(url('/my-bookings')) ?>" class="btn btn-primary">📋 <?= e(t('hotel.my_bookings') ?? 'My bookings') ?></a>
    </div>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
