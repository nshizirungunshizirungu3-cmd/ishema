<div class="empty-state" style="padding:80px 20px;">
    <div class="es-icon">⚠️</div>
    <div class="es-title"><?= e(t('errors.error_500')) ?></div>
    <p><?= e($message ?: t('errors.error_500_msg')) ?></p>
    <a href="<?= url('/') ?>" class="btn btn-primary"><?= e(t('errors.go_home')) ?></a>
</div>
