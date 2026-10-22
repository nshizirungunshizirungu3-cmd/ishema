<?php
/** @var array $settings @var string|null $message */
$pageTitle = t('admin.platform_settings');
ob_start();
$langs = \App\Core\Translator::LANGS;
?>
<?php if ($message): ?>
    <div class="alert alert-success">✅ <?= e($message) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:860px;">
    <div class="card-header"><div class="card-title">⚙️ <?= e(t('admin.platform_settings')) ?></div></div>
    <form method="POST" action="/admin/settings/save">
        <?= \App\Core\CSRF::field() ?>
        <h3 class="mb-3">🌐 <?= e(t('admin.website_content')) ?></h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.site_name')) ?></label>
                <input type="text" name="site_name" class="form-control" value="<?= e($settings['site_name']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.site_tagline')) ?></label>
                <input type="text" name="site_tagline" class="form-control" value="<?= e($settings['site_tagline']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('public.contact_email')) ?></label>
                <input type="email" name="contact_email" class="form-control" value="<?= e($settings['contact_email']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('public.contact_phone')) ?></label>
                <input type="tel" name="contact_phone" class="form-control" value="<?= e($settings['contact_phone']) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.address')) ?></label>
                <input type="text" name="contact_address" class="form-control" value="<?= e($settings['contact_address']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.default_language')) ?></label>
                <select name="default_language" class="form-select">
                    <?php foreach ($langs as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= $settings['default_language'] === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('admin.registration_open')) ?></label>
            <label class="d-flex items-center gap-2">
                <input type="checkbox" name="registration_open" value="1"<?= $settings['registration_open'] ? ' checked' : '' ?>>
                <span class="text-sm"><?= e(t('admin.registration_open')) ?></span>
            </label>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:22px 0;">
        <h3 class="mb-3">💳 <?= e(t('admin.payment_providers')) ?></h3>
        <div class="alert alert-info text-sm">💡 <?= e(t('payments.integration_note')) ?></div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.mtn_api_key')) ?></label>
                <input type="password" name="mtn_api_key" class="form-control" value="<?= e($settings['mtn_api_key']) ?>" autocomplete="off">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.airtel_api_key')) ?></label>
                <input type="password" name="airtel_api_key" class="form-control" value="<?= e($settings['airtel_api_key']) ?>" autocomplete="off">
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save_changes')) ?></button>
            <a href="/admin" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
