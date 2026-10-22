<?php
/** @var array $guest @var array $bookings */
$pageTitle = t('hotel.guest_name_label') . ': ' . e(trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? '')));
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="d-flex justify-between items-center mb-3">
    <div>
        <h2 style="margin:0;"><?= e(trim(($guest['first_name'] ?? '') . ' ' . ($guest['last_name'] ?? ''))) ?></h2>
        <div class="text-muted text-sm">
            📧 <?= e((string)($guest['email'] ?? '—')) ?> · 📞 <?= e((string)($guest['phone'] ?? '—')) ?>
            · 🌍 <?= e((string)($guest['country'] ?? '—')) ?>
        </div>
    </div>
    <a href="/hotel/guests" class="btn btn-outline btn-sm">← <?= e(t('common.back')) ?></a>
</div>

<div class="card mb-3">
    <div class="card-header"><div class="card-title">🪪 <?= e(t('common.details')) ?></div></div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.id_type')) ?></label>
                <div><?= e((string)($guest['id_type'] ?? '—')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.id_number')) ?></label>
                <div class="mono"><?= e((string)($guest['id_number'] ?? '—')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.address')) ?></label>
                <div><?= e((string)($guest['address'] ?? '—')) ?></div>
            </div>
        </div>
        <?php if (!empty($guest['notes'])): ?>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.notes')) ?></label>
                <div class="text-muted"><?= e($guest['notes']) ?></div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">📅 <?= e(t('hotel.booking_history')) ?> (<?= count($bookings) ?>)</div>
        <a href="/hotel/bookings/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('hotel.create_booking')) ?></a>
    </div>
    <?php if (empty($bookings)): ?>
        <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr>
                <th><?= e(t('hotel.booking_reference')) ?></th>
                <th><?= e(t('hotel.room_number')) ?></th>
                <th><?= e(t('hotel.check_in_date')) ?></th>
                <th><?= e(t('hotel.check_out_date')) ?></th>
                <th><?= e(t('common.amount')) ?></th>
                <th><?= e(t('common.status')) ?></th>
                <th><?= e(t('common.actions')) ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td class="mono fw-700"><?= e($b['booking_reference']) ?></td>
                        <td>#<?= e((string)($b['room_number'] ?? '—')) ?></td>
                        <td class="text-sm"><?= e($b['check_in_date']) ?></td>
                        <td class="text-sm"><?= e($b['check_out_date']) ?></td>
                        <td class="fw-700"><?= e(formatMoney((float)$b['total_amount'])) ?></td>
                        <td><?= statusBadge((string)$b['status']) ?></td>
                        <td class="actions">
                            <a href="/hotel/bookings/<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline">👁 <?= e(t('common.view')) ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
