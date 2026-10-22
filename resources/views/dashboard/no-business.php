<?php
$pageTitle = t('common.dashboard');
ob_start();
?>
<div class="empty-state">
    <div class="es-icon">🏢</div>
    <div class="es-title"><?= e(t('common.business')) ?> — <?= e(t('common.access_denied') ?? 'No business assigned') ?></div>
    <p><?= e(t('common.no_permission_desc')) ?></p>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
