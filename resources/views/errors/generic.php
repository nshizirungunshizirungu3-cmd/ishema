<div class="empty-state" style="padding:80px 20px;">
    <div class="es-icon">⚠️</div>
    <div class="es-title"><?= e($code ?? 'Error') ?></div>
    <p><?= e($message ?: t('common.something_wrong')) ?></p>
    <a href="<?= url('/') ?>" class="btn btn-primary"><?= e(t('errors.go_home')) ?></a>
</div>
