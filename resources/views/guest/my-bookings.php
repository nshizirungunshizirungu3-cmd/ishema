<?php
/** @var array $bookings */
$pageTitle = t('hotel.my_bookings') ?? 'My bookings';
ob_start();
?>
<div style="max-width:860px;margin:40px auto;">
    <h2 class="mb-3">📋 <?= e(t('hotel.my_bookings') ?? 'My bookings') ?></h2>
    <?php if (empty($bookings)): ?>
        <div class="empty-state"><div class="es-icon">📅</div><div class="es-title"><?= e(t('common.no_records') ?? 'No bookings yet') ?></div></div>
    <?php else: ?>
        <?php foreach ($bookings as $bk): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <div class="card-title mono"><?= e($bk['booking_reference']) ?></div>
                    <?= statusBadge((string)$bk['status']) ?>
                </div>
                <div class="card-body d-flex justify-between items-center" style="flex-wrap:wrap;gap:12px;">
                    <div>
                        <div class="fw-700">🏢 <?= e($bk['business_name'] ?? '—') ?></div>
                        <div class="text-muted text-sm">
                            📅 <?= e($bk['check_in_date']) ?> → <?= e($bk['check_out_date']) ?>
                            · 🌙 <?= (int)$bk['nights'] ?>
                            · 💰 <?= e(formatMoney((float)$bk['total_amount'])) ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline btn-sm" onclick="guestAction(<?= (int)$bk['id'] ?>, 'request-checkout')">📤 <?= e(t('hotel.request_checkout') ?? 'Request check-out') ?></button>
                        <button class="btn btn-primary btn-sm" onclick="guestReview(<?= (int)$bk['id'] ?>)">⭐ <?= e(t('common.rate') ?? 'Rate') ?></button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<script>
function guestAction(bookingId, action) {
    if (action === 'request-checkout') {
        if (!confirm('<?= e(t('hotel.confirm_checkout_request') ?? 'Request check-out? Reception will be notified.') ?>')) { return; }
        ISHEMA.api('/my-bookings/' + bookingId + '/request-checkout', {}, 'POST')
            .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || 'Requested')); } else { alert(r.error || 'Error'); } });
    }
}
function guestReview(bookingId) {
    var rating = prompt('<?= e(t('reviews.overall_rating') ?? 'Overall rating') ?> (1-5)', '5');
    if (rating === null) { return; }
    var r = parseInt(rating, 10);
    if (r < 1 || r > 5) { alert('<?= e(t('customer.invalid_rating') ?? 'Invalid rating') ?>'); return; }
    var comment = prompt('<?= e(t('common.comment') ?? 'Comment') ?>', '');
    ISHEMA.api('/my-bookings/' + bookingId + '/review', {overall_rating: r, comment: comment || ''}, 'POST')
        .then(function (res) { if (res.ok) { ISHEMA.toast('⭐ ' + (res.message || 'Thanks!')); location.reload(); } else { alert(res.error || 'Error'); } });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
