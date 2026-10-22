<!DOCTYPE html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Core\CSRF::token()) ?>">
    <title><?= e(t('auth.reset_title')) ?> Â· ISHEMA</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="a-logo">I</div>
            <h2>ISHEMA</h2>
            <p><?= e(t('auth.reset_title')) ?></p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger">âš ï¸ <?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= url('/reset-password/' . $token) ?>">
            <?= \App\Core\CSRF::field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="form-group">
                <label class="form-label"><?= e(t('auth.new_password')) ?></label>
                <input type="password" name="password" class="form-control" required minlength="8">
                <div class="form-text"><?= e(t('auth.password_min')) ?></div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('auth.confirm_password')) ?></label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
            </div>
            <button type="submit" class="btn btn-primary btn-block"><?= e(t('auth.reset_button')) ?></button>
        </form>
        <div class="auth-links">
            <a href="<?= url('/login') ?>"><?= e(t('common.login')) ?></a>
        </div>
    </div>
</div>
</body>
</html>
