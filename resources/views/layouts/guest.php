<?php
/** Public/guest layout: @var string $content, @var string $view */
$locale = \App\Core\Translator::locale();
$user = \App\Core\Auth::user();
$navActive = $navActive ?? '';
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Core\CSRF::token()) ?>">
    <title><?= e($pageTitle ?? 'ISHEMA') ?> · ISHEMA</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body style="display:flex;flex-direction:column;min-height:100vh;">
<nav class="public-nav">
    <div class="inner">
        <a href="<?= url('/') ?>" class="brand">
            <span class="p-logo">I</span> ISHEMA
        </a>
        <div class="nav-links">
            <a href="<?= url('/services') ?>" class="<?= $navActive === 'services' ? 'active' : '' ?>"><?= e(t('common.services')) ?></a>
            <a href="<?= url('/about') ?>" class="<?= $navActive === 'about' ? 'active' : '' ?>"><?= e(t('common.about') === 'about' ? 'About' : t('footer_about')) ?></a>
            <a href="<?= url('/packages') ?>" class="<?= $navActive === 'packages' ? 'active' : '' ?>"><?= e(t('footer_packages')) ?></a>
            <a href="<?= url('/tutorials') ?>" class="<?= $navActive === 'tutorials' ? 'active' : '' ?>"><?= e(t('footer_tutorials')) ?></a>
            <a href="<?= url('/contact') ?>" class="<?= $navActive === 'contact' ? 'active' : '' ?>"><?= e(t('footer_contact')) ?></a>
        </div>
        <div class="nav-actions">
            <div class="lang-select">
                🌐
                <select id="language-select">
                    <?php foreach (\App\Core\Translator::LANGS as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= $code === $locale ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($user): ?>
                <a href="<?= url($user['role'] === 'SUPER_ADMIN' ? '/admin' : '/dashboard') ?>" class="btn btn-primary btn-sm"><?= e(t('common.dashboard')) ?></a>
            <?php else: ?>
                <a href="<?= url('/login') ?>" class="btn btn-outline btn-sm"><?= e(t('common.login')) ?></a>
                <a href="<?= url('/login') ?>" class="btn btn-primary btn-sm"><?= e(t('common.get_started')) ?></a>
            <?php endif; ?>
        </div>
        <button class="nav-toggle" id="nav-toggle" aria-label="Menu">☰</button>
    </div>
</nav>

<main style="flex:1;">
    <?= $content ?>
</main>

<footer class="footer">
    <div class="inner">
        <div class="f-grid">
            <div>
                <div class="f-brand"><span class="p-logo" style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,var(--primary),var(--secondary));display:inline-flex;align-items:center;justify-content:center;color:#fff;font-weight:800;">I</span> ISHEMA</div>
                <p style="font-size:14px;max-width:320px;"><?= e(t('footer_tagline')) ?></p>
            </div>
            <div>
                <h4><?= e(t('footer_home')) ?></h4>
                <a href="<?= url('/services') ?>"><?= e(t('common.services')) ?></a>
                <a href="<?= url('/about') ?>"><?= e(t('footer_about')) ?></a>
                <a href="<?= url('/packages') ?>"><?= e(t('footer_packages')) ?></a>
            </div>
            <div>
                <h4><?= e(t('footer_tutorials')) ?></h4>
                <a href="<?= url('/tutorials') ?>"><?= e(t('footer_tutorials')) ?></a>
                <a href="<?= url('/contact') ?>"><?= e(t('footer_contact')) ?></a>
            </div>
            <div>
                <h4><?= e(t('common.language')) ?></h4>
                <?php foreach (\App\Core\Translator::LANGS as $code => $label): ?>
                    <a href="<?= url('/language/' . $code) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="f-bottom">
            <span>© <?= date('Y') ?> ISHEMA. <?= e(t('footer_rights')) ?></span>
        </div>
    </div>
</footer>

<div id="toast-container"></div>
<script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
