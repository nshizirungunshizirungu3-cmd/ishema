<?php
/** @var array $rooms @var array $types @var string $status */
$pageTitle = t('hotel.room_management');
ob_start();
$statuses = ['available', 'occupied', 'reserved', 'cleaning', 'maintenance', 'out_of_service'];
$typeNames = [];
foreach ($types as $t) { $typeNames[(int)$t['id']] = (string)$t['name']; }
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🛏️ <?= e(t('hotel.room_management')) ?> (<?= count($rooms) ?>)</div>
        <a href="/hotel/rooms/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('hotel.add_room')) ?></a>
    </div>
    <form method="GET" action="/hotel/rooms" class="d-flex gap-2 mb-3">
        <select name="status" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('common.status')) ?>es</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(t('hotel.' . $s . '_rooms') === 'hotel.' . $s . '_rooms' ? ucfirst(str_replace('_', ' ', $s)) : t('hotel.' . $s . '_rooms')) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.filter')) ?></button>
    </form>
    <?php if (empty($rooms)): ?>
        <div class="empty-state"><div class="es-icon">🛏️</div><div class="es-title"><?= e(t('common.no_records')) ?></div><p><a href="/hotel/rooms/create"><?= e(t('hotel.add_room')) ?></a></p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('hotel.room_number')) ?></th>
                    <th><?= e(t('hotel.room_type')) ?></th>
                    <th><?= e(t('hotel.floor_label')) ?></th>
                    <th><?= e(t('hotel.room_price')) ?></th>
                    <th><?= e(t('hotel.room_status')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td class="fw-700">#<?= e($room['room_number']) ?></td>
                            <td><?= e($typeNames[(int)$room['room_type_id']] ?? '—') ?></td>
                            <td class="text-muted"><?= e((string)($room['floor'] ?? '—')) ?></td>
                            <td><?= e(formatMoney((float)$room['price'])) ?>/<?= e(t('common.per_night')) ?></td>
                            <td><?= statusBadge((string)$room['status']) ?></td>
                            <td class="actions">
                                <a href="/hotel/rooms/<?= (int)$room['id'] ?>/edit" class="btn btn-sm btn-outline">✏️ <?= e(t('common.edit')) ?></a>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/hotel/rooms/<?= (int)$room['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
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
