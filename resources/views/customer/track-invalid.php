<?php
/** Invalid order tracking (guest layout). */
$pageTitle = t('customer.order_not_found') ?? 'Order not found';
ob_start();
?>
<div style="max-width:520px;margin:60px auto;text-align:center;">
    <div style="font-size:64px;">🔍</div>
    <h1><?= e(t('customer.order_not_found') ?? 'Order not found') ?></h1>
    <p class="text-muted"><?= e(t('customer.order_not_found_desc') ?? 'Please check the order number and try again.') ?></p>
    <a href="<?= e(url('/')) ?>" class="btn btn-primary"><?= e(t('common.home') ?? 'Home') ?></a>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
