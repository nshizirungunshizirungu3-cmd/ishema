<?php
/** Invalid QR / closed business (guest layout). */
$pageTitle = t('customer.invalid_qr') ?? 'Invalid QR code';
ob_start();
?>
<div style="max-width:520px;margin:60px auto;text-align:center;">
    <div style="font-size:64px;">📵</div>
    <h1><?= e(t('customer.invalid_qr') ?? 'Invalid QR code') ?></h1>
    <p class="text-muted"><?= e(t('customer.invalid_qr_desc') ?? 'This QR code is no longer valid, or the business is currently closed.') ?></p>
    <a href="<?= e(url('/')) ?>" class="btn btn-primary"><?= e(t('common.home') ?? 'Home') ?></a>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
