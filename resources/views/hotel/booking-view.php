<?php
/** @var array $booking @var array $services @var array $payments */
$pageTitle = t('hotel.booking_reference') . ' ' . e($booking['booking_reference']);
ob_start();
$payStatuses = ['pending' => 'badge-info', 'paid' => 'badge-active', 'partial' => 'badge-warning', 'refunded' => 'badge-inactive'];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="d-flex justify-between items-center mb-3" style="flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="margin:0;" class="mono"><?= e($booking['booking_reference']) ?></h2>
        <div class="text-muted text-sm">
            🛏️ #<?= e((string)($booking['room_number'] ?? '—')) ?>
            <?php if (!empty($booking['room_type_name'])): ?>· <?= e($booking['room_type_name']) ?><?php endif; ?>
            · 📅 <?= e($booking['check_in_date']) ?> → <?= e($booking['check_out_date']) ?>
            · 🌙 <?= (int)$booking['nights'] ?> <?= e(t('hotel.nights_label')) ?>
        </div>
    </div>
    <div class="d-flex items-center gap-2">
        <?= statusBadge((string)$booking['status']) ?>
        <?php if ((string)$booking['status'] === 'confirmed'): ?>
            <button class="btn btn-success btn-sm" onclick="bookingAction(<?= (int)$booking['id'] ?>, 'checkin')"><?= e(t('hotel.check_in')) ?></button>
        <?php endif; ?>
        <?php if ((string)$booking['status'] === 'checked_in'): ?>
            <button class="btn btn-primary btn-sm" onclick="bookingAction(<?= (int)$booking['id'] ?>, 'checkout')"><?= e(t('hotel.check_out')) ?></button>
        <?php endif; ?>
        <?php if (!in_array((string)$booking['status'], ['checked_out', 'cancelled'], true)): ?>
            <button class="btn btn-danger btn-sm" onclick="bookingAction(<?= (int)$booking['id'] ?>, 'cancel')">✕ <?= e(t('hotel.cancel_booking')) ?></button>
        <?php endif; ?>
        <a href="/hotel/bookings/<?= (int)$booking['id'] ?>/edit" class="btn btn-outline btn-sm">✏️ <?= e(t('common.edit')) ?></a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><div class="card-title">🧍 <?= e(t('hotel.guest_name_label')) ?></div></div>
        <div class="card-body">
            <div class="fw-700" style="font-size:18px;"><?= e(trim(($booking['first_name'] ?? '') . ' ' . ($booking['last_name'] ?? ''))) ?></div>
            <?php if (!empty($booking['email'])): ?><div class="text-muted text-sm">📧 <?= e($booking['email']) ?></div><?php endif; ?>
            <?php if (!empty($booking['phone'])): ?><div class="text-muted text-sm">📞 <?= e($booking['phone']) ?></div><?php endif; ?>
            <?php if (!empty($booking['id_number'])): ?><div class="text-muted text-sm">🪪 <?= e($booking['id_type'] ?? '') ?>: <?= e($booking['id_number']) ?></div><?php endif; ?>
            <?php if (!empty($booking['country'])): ?><div class="text-muted text-sm">🌍 <?= e($booking['country']) ?></div><?php endif; ?>
            <hr style="border:none;border-top:1px solid var(--border);">
            <div class="text-sm">
                🧑‍🤝‍🧑 <?= (int)($booking['adults'] ?? 0) ?> <?= e(t('hotel.adults')) ?>, <?= (int)($booking['children'] ?? 0) ?> <?= e(t('hotel.children')) ?><?php if (isset($booking['capacity'])): ?> · 🛏️ <?= e(t('hotel.room_type_capacity')) ?>: <?= (int)$booking['capacity'] ?><?php endif; ?>
            </div>
            <?php if (!empty($booking['special_requests'])): ?>
                <div class="text-muted text-sm mt-2">📝 <?= e($booking['special_requests']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">💰 <?= e(t('common.payment')) ?></div></div>
        <div class="card-body">
            <div class="d-flex justify-between items-center mb-2">
                <span class="fw-700"><?= e(t('common.total')) ?></span>
                <span class="s-value"><?= e(formatMoney((float)$booking['total_amount'])) ?></span>
            </div>
            <?php $ps = (string)($booking['payment_status'] ?? 'pending'); ?>
            <span class="badge <?= $payStatuses[$ps] ?? 'badge-info' ?>">● <?= e(t('hotel.payment_' . $ps)) ?></span>
            <?php if (!empty($payments)): ?>
                <hr style="border:none;border-top:1px solid var(--border);">
                <?php foreach ($payments as $p): ?>
                    <div class="d-flex justify-between text-sm mb-1">
                        <span class="mono"><?= e($p['reference']) ?></span>
                        <span><?= e(formatMoney((float)$p['amount'])) ?> · <?= statusBadge((string)$p['status']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <a href="/payments/pay?payable_type=booking&payable_id=<?= (int)$booking['id'] ?>" class="btn btn-primary btn-sm mt-3">💳 <?= e(t('payments.pay_amount')) ?></a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">🛎️ <?= e(t('hotel.hotel_services')) ?> (<?= count($services) ?>)</div>
        <a href="/hotel/services" class="btn btn-outline btn-sm" style="margin-left:auto;">＋ <?= e(t('common.add_new')) ?></a>
    </div>
    <?php if (empty($services)): ?>
        <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr><th><?= e(t('hotel.service_request')) ?></th><th><?= e(t('common.details') )?></th><th><?= e(t('common.quantity')) ?></th><th><?= e(t('common.amount')) ?></th><th><?= e(t('common.status')) ?></th><th><?= e(t('hotel.requested_at')) ?></th></tr></thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td class="fw-700"><?= e($s['title']) ?> <span class="badge badge-info"><?= e($s['service_type']) ?></span></td>
                        <td class="text-muted text-sm"><?= e((string)($s['description'] ?? '')) ?></td>
                        <td><?= (int)$s['quantity'] ?></td>
                        <td><?= e(formatMoney((float)$s['total_amount'])) ?></td>
                        <td><?= statusBadge((string)$s['status']) ?></td>
                        <td class="text-muted text-sm"><?= e(formatDate($s['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>

<script>
function bookingAction(id, action) {
    var labels = {checkin: '<?= e(t('hotel.check_in')) ?>', checkout: '<?= e(t('hotel.check_out')) ?>', cancel: 'Cancel booking?'};
    if (!confirm(labels[action] || 'Confirm?')) { return; }
    ISHEMA.api('/hotel/bookings/' + id + '/' + action, {}, 'POST')
        .then(function (r) { if (r.ok) { location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
