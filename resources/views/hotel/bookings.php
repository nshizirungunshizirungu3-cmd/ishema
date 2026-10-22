<?php
/** @var array $bookings @var string $status */
$pageTitle = t('hotel.booking_management');
ob_start();
$statuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];
$payStatuses = ['pending', 'paid', 'partial', 'refunded'];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">📅 <?= e(t('hotel.booking_management')) ?> (<?= count($bookings) ?>)</div>
        <a href="/hotel/bookings/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('hotel.create_booking')) ?></a>
    </div>
    <form method="GET" action="/hotel/bookings" class="d-flex gap-2 mb-3">
        <select name="status" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('common.status')) ?>es</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(t('hotel.' . $s) === 'hotel.' . $s ? ucfirst(str_replace('_', ' ', $s)) : t('hotel.' . $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.filter')) ?></button>
    </form>
    <?php if (empty($bookings)): ?>
        <div class="empty-state"><div class="es-icon">📅</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('hotel.booking_reference')) ?></th>
                    <th><?= e(t('hotel.guest_name_label')) ?></th>
                    <th><?= e(t('hotel.room_number')) ?></th>
                    <th><?= e(t('hotel.check_in_date')) ?></th>
                    <th><?= e(t('hotel.check_out_date')) ?></th>
                    <th><?= e(t('common.amount')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('hotel.payment_status')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <td class="mono fw-700"><?= e($b['booking_reference']) ?></td>
                            <td>
                                <?= e(trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? ''))) ?: '—' ?>
                                <?php if (!empty($b['phone'])): ?><div class="text-muted text-xs">📞 <?= e($b['phone']) ?></div><?php endif; ?>
                            </td>
                            <td>#<?= e((string)($b['room_number'] ?? '—')) ?></td>
                            <td class="text-sm"><?= e($b['check_in_date']) ?></td>
                            <td class="text-sm"><?= e($b['check_out_date']) ?></td>
                            <td class="fw-700"><?= e(formatMoney((float)$b['total_amount'])) ?></td>
                            <td><?= statusBadge((string)$b['status']) ?></td>
                            <td>
                                <?php $ps = (string)($b['payment_status'] ?? 'pending'); ?>
                                <?php if ($ps === 'paid'): ?><span class="badge badge-active">● <?= e(t('hotel.payment_paid')) ?></span>
                                <?php elseif ($ps === 'partial'): ?><span class="badge badge-warning">● <?= e(t('hotel.payment_partial')) ?></span>
                                <?php elseif ($ps === 'refunded'): ?><span class="badge badge-inactive">● <?= e(t('hotel.payment_refunded')) ?></span>
                                <?php else: ?><span class="badge badge-info">● <?= e(t('hotel.payment_pending')) ?></span><?php endif; ?>
                            </td>
                            <td class="actions">
                                <a href="/hotel/bookings/<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline">👁 <?= e(t('common.view')) ?></a>
                                <a href="/hotel/bookings/<?= (int)$b['id'] ?>/edit" class="btn btn-sm btn-outline">✏️</a>
                                <?php if ((string)$b['status'] === 'confirmed'): ?>
                                    <button class="btn btn-sm btn-success" onclick="bookingAction(<?= (int)$b['id'] ?>, 'checkin')"><?= e(t('hotel.check_in')) ?></button>
                                <?php endif; ?>
                                <?php if ((string)$b['status'] === 'checked_in'): ?>
                                    <button class="btn btn-sm btn-primary" onclick="bookingAction(<?= (int)$b['id'] ?>, 'checkout')"><?= e(t('hotel.check_out')) ?></button>
                                <?php endif; ?>
                                <?php if (!in_array((string)$b['status'], ['checked_out', 'cancelled'], true)): ?>
                                    <button class="btn btn-sm btn-danger" onclick="bookingAction(<?= (int)$b['id'] ?>, 'cancel')">✕</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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
