<?php
/** @var array $business @var array $types @var array $rooms */
$pageTitle = e($business['name']) . ' — ' . t('hotel.rooms') ?? 'Rooms';
ob_start();
?>
<div style="max-width:1000px;margin:40px auto;">
    <div style="text-align:center;margin-bottom:30px;">
        <h1><?= e($business['name']) ?></h1>
        <p class="text-muted">📍 <?= e($business['address'] ?? '') ?></p>
        <a href="<?= e(url('/book/' . $business['business_id'])) ?>" class="btn btn-primary"><?= e(t('hotel.book_now') ?? 'Book now') ?></a>
    </div>

    <?php if (empty($rooms)): ?>
        <div class="empty-state"><div class="es-icon">🛏️</div><div class="es-title"><?= e(t('hotel.no_rooms_available') ?? 'No rooms available') ?></div></div>
    <?php else: ?>
        <div class="stat-grid">
            <?php foreach ($rooms as $room): ?>
                <div class="card" style="border-top:4px solid var(--primary);">
                    <div class="card-header">
                        <div class="card-title">🛏️ #<?= e($room['room_number']) ?></div>
                        <span class="badge badge-active">● <?= e(t('hotel.available') ?? 'Available') ?></span>
                    </div>
                    <div class="card-body">
                        <div class="fw-700"><?= e($room['type_name'] ?? '—') ?></div>
                        <div class="text-muted text-sm mb-2">
                            🧑‍🤝‍🧑 <?= (int)($room['capacity'] ?? 1) ?> · 🌙 <?= e(formatMoney((float)$room['price'])) ?>/<?= e(t('public.per_night') ?? 'night') ?>
                        </div>
                        <?php if (!empty($room['description'])): ?>
                            <p class="text-muted text-sm"><?= e(\App\Core\Security::truncate((string)$room['description'], 100)) ?></p>
                        <?php endif; ?>
                        <a href="<?= e(url('/book/' . $business['business_id'] . '?room=' . (int)$room['id'])) ?>" class="btn btn-primary" style="width:100%;">
                            <?= e(t('hotel.book_now') ?? 'Book now') ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
