<?php
/** @var array $types @var array|null $room @var array $images */
$pageTitle = $room ? t('hotel.edit_room') : t('hotel.add_room');
ob_start();
$statuses = ['available', 'occupied', 'reserved', 'cleaning', 'maintenance', 'out_of_service'];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:860px;">
    <div class="card-header">
        <div class="card-title">🛏️ <?= $room ? e(t('hotel.edit_room')) : e(t('hotel.add_room')) ?></div>
        <a href="/hotel/rooms" class="btn btn-outline btn-sm" style="margin-left:auto;">← <?= e(t('common.back')) ?></a>
    </div>
    <form method="POST" action="<?= $room ? url('/hotel/rooms/' . $room['id'] . '/update') : url('/hotel/rooms/create') ?>" enctype="multipart/form-data">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_number')) ?> <span class="req">*</span></label>
                <input type="text" name="room_number" class="form-control" value="<?= e($room['room_number'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_type')) ?></label>
                <select name="room_type_id" class="form-select">
                    <option value="0">—</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= (int)$t['id'] ?>"<?= ($room['room_type_id'] ?? 0) == $t['id'] ? ' selected' : '' ?>>
                            <?= e($t['name']) ?> (<?= e(t('hotel.room_type_capacity')) ?>: <?= (int)$t['capacity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.floor_label')) ?></label>
                <input type="text" name="floor" class="form-control" value="<?= e($room['floor'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_price')) ?></label>
                <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= e((string)($room['price'] ?? 0)) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_status')) ?></label>
                <select name="status" class="form-select">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= e($s) ?>"<?= ($room['status'] ?? 'available') === $s ? ' selected' : '' ?>>
                            <?= e(t('hotel.' . $s . '_rooms') === 'hotel.' . $s . '_rooms' ? ucfirst(str_replace('_', ' ', $s)) : t('hotel.' . $s . '_rooms')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_images')) ?></label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('hotel.room_description')) ?></label>
            <textarea name="description" class="form-control" rows="3"><?= e($room['description'] ?? '') ?></textarea>
        </div>
        <?php if (!empty($images)): ?>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_images')) ?> (<?= count($images) ?>)</label>
                <div class="d-flex gap-2" style="flex-wrap:wrap;">
                    <?php foreach ($images as $img): ?>
                        <div style="position:relative;">
                            <img src="<?= e(asset($img)) ?>" alt="" style="width:110px;height:80px;object-fit:cover;border-radius:8px;">
                            <button type="button" class="btn btn-sm btn-danger" style="position:absolute;top:-8px;right:-8px;"
                                onclick="ISHEMA.api('/hotel/rooms/<?= (int)$room['id'] ?>/remove-image', {path: <?= json_encode($img) ?>}, 'POST').then(r => location.reload())">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            <a href="/hotel/rooms" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
