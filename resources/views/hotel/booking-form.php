<?php
/**
 * @var array $business @var array|null $booking @var array|null $guest
 * @var array $rooms @var array $types @var array $availableRooms
 */
$pageTitle = $booking ? t('hotel.edit_booking') : t('hotel.create_booking');
ob_start();
$statuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'];
$payStatuses = ['pending', 'paid', 'partial', 'refunded'];
$idTypes = ['passport', 'national_id', 'drivers_license', 'other'];
$roomList = [];
if ($booking) {
    $roomList[(int)$booking['room_id']] = (string)($booking['room_number'] ?? ('#' . $booking['room_id']));
}
$sourceRooms = $booking ? $availableRooms : $rooms;
foreach ($sourceRooms as $r) {
    $roomList[(int)$r['id']] = (string)$r['room_number'];
}
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:900px;">
    <div class="card-header">
        <div class="card-title">📅 <?= $booking ? e(t('hotel.edit_booking')) : e(t('hotel.create_booking')) ?></div>
        <?php if ($booking): ?>
            <span class="mono text-muted" style="margin-left:12px;"><?= e($booking['booking_reference']) ?></span>
        <?php endif; ?>
        <a href="/hotel/bookings" class="btn btn-outline btn-sm" style="margin-left:auto;">← <?= e(t('common.back')) ?></a>
    </div>
    <form method="POST" action="<?= $booking ? url('/hotel/bookings/' . $booking['id'] . '/update') : url('/hotel/bookings/create') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.check_in_date')) ?> <span class="req">*</span></label>
                <input type="date" name="check_in_date" id="check_in_date" class="form-control" value="<?= e($booking['check_in_date'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.check_out_date')) ?> <span class="req">*</span></label>
                <input type="date" name="check_out_date" id="check_out_date" class="form-control" value="<?= e($booking['check_out_date'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <button type="button" class="btn btn-outline btn-sm" onclick="checkAvailability()"><?= e(t('hotel.check_availability')) ?></button>
            <span id="avail-result" class="text-muted text-sm" style="margin-left:10px;"></span>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('hotel.room_assignment')) ?> <span class="req">*</span></label>
            <select name="room_id" id="room_id" class="form-select" required>
                <option value="0">—</option>
                <?php foreach ($roomList as $rid => $rnum): ?>
                    <option value="<?= (int)$rid ?>"<?= ($booking['room_id'] ?? 0) == $rid ? ' selected' : '' ?>>
                        #<?= e($rnum) ?><?= $booking && (int)$rid === (int)$booking['room_id'] ? ' (' . e(t('common.current')) . ')' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($guest): ?>
            <input type="hidden" name="guest_id" value="<?= (int)$guest['id'] ?>">
        <?php endif; ?>
        <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">
        <h3 class="mb-3">🧍 <?= e(t('hotel.guest_registration')) ?></h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.first_name')) ?> <span class="req">*</span></label>
                <input type="text" name="first_name" class="form-control" value="<?= e($guest['first_name'] ?? ($booking['first_name'] ?? '')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.last_name')) ?> <span class="req">*</span></label>
                <input type="text" name="last_name" class="form-control" value="<?= e($guest['last_name'] ?? ($booking['last_name'] ?? '')) ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.email')) ?></label>
                <input type="email" name="email" class="form-control" value="<?= e($guest['email'] ?? ($booking['email'] ?? '')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.phone')) ?></label>
                <input type="tel" name="phone" class="form-control" value="<?= e($guest['phone'] ?? ($booking['phone'] ?? '')) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.id_type')) ?></label>
                <select name="id_type" class="form-select">
                    <?php foreach ($idTypes as $it): ?>
                        <option value="<?= e($it) ?>"<?= ($guest['id_type'] ?? '') === $it ? ' selected' : '' ?>><?= e(ucfirst(str_replace('_', ' ', $it))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.id_number')) ?></label>
                <input type="text" name="id_number" class="form-control" value="<?= e($guest['id_number'] ?? ($booking['id_number'] ?? '')) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('hotel.country')) ?></label>
            <input type="text" name="country" class="form-control" value="<?= e($guest['country'] ?? ($booking['country'] ?? '')) ?>">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.adults')) ?></label>
                <input type="number" name="adults" class="form-control" value="<?= e((string)($booking['adults'] ?? 1)) ?>" min="1">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.children')) ?></label>
                <input type="number" name="children" class="form-control" value="<?= e((string)($booking['children'] ?? 0)) ?>" min="0">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_price')) ?> (/<?= e(t('common.per_night')) ?>)</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?= e((string)($booking['price_per_night'] ?? ($booking['total_amount'] / max(1, (int)($booking['nights'] ?? 1))))) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.nights')) ?></label>
                <input type="number" name="nights" class="form-control" value="<?= e((string)($booking['nights'] ?? 1)) ?>" min="1">
            </div>
        </div>
        <?php if ($booking): ?>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.status')) ?></label>
                    <select name="status" class="form-select">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= e($s) ?>"<?= ($booking['status'] ?? '') === $s ? ' selected' : '' ?>><?= e(t('hotel.' . $s) === 'hotel.' . $s ? ucfirst(str_replace('_', ' ', $s)) : t('hotel.' . $s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.payment_status')) ?></label>
                    <select name="payment_status" class="form-select">
                        <?php foreach ($payStatuses as $ps): ?>
                            <option value="<?= e($ps) ?>"<?= ($booking['payment_status'] ?? 'pending') === $ps ? ' selected' : '' ?>><?= e(t('hotel.payment_' . $ps)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label class="form-label"><?= e(t('hotel.special_requests')) ?></label>
            <textarea name="special_requests" class="form-control" rows="2"><?= e($booking['special_requests'] ?? '') ?></textarea>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            <a href="/hotel/bookings" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>

<script>
function checkAvailability() {
    var ci = document.getElementById('check_in_date').value;
    var co = document.getElementById('check_out_date').value;
    if (!ci || !co) { return; }
    var result = document.getElementById('avail-result');
    result.textContent = '...';
    ISHEMA.api('/hotel/bookings/check-availability', {check_in_date: ci, check_out_date: co}, 'POST')
        .then(function (r) {
            if (r.ok) {
                result.textContent = '✅ ' + r.rooms.length + ' <?= e(t('hotel.available_rooms_result')) ?> · ' + r.nights + ' <?= e(t('hotel.nights')) ?>';
                var sel = document.getElementById('room_id');
                sel.innerHTML = '<option value="0">—</option>';
                r.rooms.forEach(function (room) {
                    var opt = document.createElement('option');
                    opt.value = room.id;
                    opt.textContent = '#' + room.room_number + (room.type_name ? ' — ' + room.type_name : '');
                    sel.appendChild(opt);
                });
            } else {
                result.textContent = '⚠️ ' + (r.error || '');
            }
        })
        .catch(function () { result.textContent = ''; });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
