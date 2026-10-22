<?php
/** @var string $error */
$pageTitle = t('hotel.find_booking');
$navActive = '';
ob_start();
?>
<div style="max-width:560px;margin:60px auto;">
    <div class="card">
        <div class="card-header"><div class="card-title">🔍 <?= e(t('hotel.find_booking')) ?></div></div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
            <?php endif; ?>
            <form method="GET" action="<?= e(url('/booking/lookup')) ?>">
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.booking_reference')) ?> <span class="req">*</span></label>
                    <input type="text" name="reference" class="form-control" placeholder="BK-XXXXXXXX" required>
                </div>
                <div class="form-group">
                    <label class="form-label">📧 <?= e(t('common.email')) ?> <?= e(t('common.or')) ?> 📞 <?= e(t('common.phone')) ?> <span class="req">*</span></label>
                    <input type="text" name="key" class="form-control" required>
                </div>
                <div class="text-muted text-sm mb-3"><?= e(t('hotel.booking_reference_or_email')) ?></div>
                <button type="submit" class="btn btn-primary" style="width:100%;">🔍 <?= e(t('hotel.find_booking')) ?></button>
            </form>
            <hr style="border:none;border-top:1px solid var(--border);">
            <div class="text-center">
                <a href="/" class="btn btn-outline"><?= e(t('common.back')) ?></a>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
