<?php
/**
 * @var array $business @var array $rooms @var array $types
 * @var string $checkIn @var string $checkOut @var string|null $message @var string|null $error
 */
$pageTitle = t('hotel.book_now') . ' — ' . e($business['name']);
$navActive = '';
ob_start();
?>
<div style="max-width:900px;margin:32px auto;">
    <div class="text-center mb-3">
        <h2 style="margin:0;">🏨 <?= e($business['name']) ?></h2>
        <p class="text-muted">📍 <?= e(trim(($business['address'] ?? '') . ' ' . ($business['city'] ?? '') . ' ' . ($business['country'] ?? ''))) ?></p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= e($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
    <?php endif; ?>

    <div class="card mb-3">
        <div class="card-header"><div class="card-title">📅 <?= e(t('hotel.check_availability')) ?></div></div>
        <div class="card-body">
            <form method="GET" action="<?= e(url('/book/' . $business['business_id'])) ?>" class="d-flex gap-2" style="flex-wrap:wrap;">
                <input type="hidden" name="room" value="<?= e((string)\App\Core\Security::input('room', '')) ?>">
                <div class="form-group" style="flex:1;min-width:160px;">
                    <label class="form-label"><?= e(t('hotel.check_in_date')) ?></label>
                    <input type="date" name="check_in" class="form-control" value="<?= e($checkIn) ?>">
                </div>
                <div class="form-group" style="flex:1;min-width:160px;">
                    <label class="form-label"><?= e(t('hotel.check_out_date')) ?></label>
                    <input type="date" name="check_out" class="form-control" value="<?= e($checkOut) ?>">
                </div>
                <div class="form-group" style="align-self:flex-end;">
                    <button class="btn btn-primary" type="submit">🔍 <?= e(t('hotel.check_availability')) ?></button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($rooms)): ?>
        <div class="empty-state"><div class="es-icon">🛏️</div><div class="es-title"><?= e(t('hotel.no_rooms_available')) ?></div></div>
    <?php else: ?>
        <h3 class="mb-3">🛏️ <?= e(t('hotel.available_rooms_result')) ?>: <?= count($rooms) ?></h3>
        <div class="stat-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="card" style="border-top:4px solid var(--primary);">
                    <div class="card-header">
                        <div class="card-title">🛏️ #<?= e($room['room_number']) ?></div>
                        <span class="badge badge-active">● <?= e(t('hotel.available_rooms')) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="fw-700"><?= e($room['type_name'] ?? '—') ?></div>
                        <div class="text-muted text-sm mb-2">
                            🧑‍🤝‍🧑 <?= (int)($room['capacity'] ?? 1) ?> <?= e(t('common.guests_count')) ?>
                            · 🌙 <?= e(formatMoney((float)$room['price'])) ?>/<?= e(t('common.per_night')) ?>
                        </div>
                        <?php if (!empty($room['description'])): ?>
                            <p class="text-muted text-sm"><?= e(\App\Core\Security::truncate((string)$room['description'], 120)) ?></p>
                        <?php endif; ?>
                        <button class="btn btn-primary" style="width:100%;" onclick="selectRoom(<?= (int)$room['id'] ?>, <?= json_encode((string)$room['room_number']) ?>)">
                            <?= e(t('hotel.book_now')) ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div id="bookModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title"><?= e(t('hotel.create_booking')) ?> — <span id="modal-room"></span></div>
            <button class="btn btn-outline btn-sm" onclick="closeBookModal()">✕</button>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= e(url('/book/' . $business['business_id'])) ?>">
                <?= \App\Core\CSRF::field() ?>
                <input type="hidden" name="room_id" id="f-room">
                <input type="hidden" name="check_in_date" value="<?= e($checkIn) ?>">
                <input type="hidden" name="check_out_date" value="<?= e($checkOut) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('hotel.first_name')) ?> <span class="req">*</span></label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('hotel.last_name')) ?> <span class="req">*</span></label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.email')) ?></label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.phone')) ?></label>
                        <input type="tel" name="phone" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('hotel.country')) ?></label>
                        <input type="text" name="country" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('hotel.adults')) ?> / <?= e(t('hotel.children')) ?></label>
                        <div class="d-flex gap-2">
                            <input type="number" name="adults" class="form-control" value="1" min="1">
                            <input type="number" name="children" class="form-control" value="0" min="0">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.special_requests')) ?></label>
                    <textarea name="special_requests" class="form-control" rows="2"></textarea>
                </div>
                <div class="text-muted text-sm mb-3">
                    📅 <?= e($checkIn) ?> → <?= e($checkOut) ?>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"><?= e(t('hotel.book_now')) ?></button>
            </form>
        </div>
    </div>
</div>

<script>
function selectRoom(roomId, roomNumber) {
    document.getElementById('f-room').value = roomId;
    document.getElementById('modal-room').textContent = '#' + roomNumber;
    document.getElementById('bookModal').style.display = 'flex';
}
function closeBookModal() {
    document.getElementById('bookModal').style.display = 'none';
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
