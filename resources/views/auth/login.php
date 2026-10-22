<!DOCTYPE html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Core\CSRF::token()) ?>">
    <title><?= e(t('auth.login_title')) ?> · ISHEMA</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="a-logo">I</div>
            <h2>ISHEMA</h2>
            <p><?= e(t('auth.login_title')) ?></p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
        <?php endif; ?>
        <?php $msg = \App\Core\Session::flash('message'); if ($msg): ?>
            <div class="alert alert-success">✅ <?= e($msg) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= url('/login') ?>">
            <?= \App\Core\CSRF::field() ?>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.email')) ?></label>
                <input type="email" name="email" class="form-control" required autocomplete="email" autofocus>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.password')) ?></label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg"><?= e(t('auth.login_button')) ?></button>
        </form>
        <div class="auth-links">
            <a href="<?= url('/forgot-password') ?>"><?= e(t('common.forgot_password')) ?></a>
        </div>
    </div>
</div>
</body>
</html>
