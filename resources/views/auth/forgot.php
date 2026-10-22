<!DOCTYPE html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Core\CSRF::token()) ?>">
    <title><?= e(t('auth.forgot_title')) ?> Â· ISHEMA</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="a-logo">I</div>
            <h2>ISHEMA</h2>
            <p><?= e(t('auth.forgot_title')) ?></p>
        </div>
        <?php if ($sent): ?>
            <div class="alert alert-success">âœ… <?= e(t('auth.reset_link_sent')) ?></div>
            <?php $dev = \App\Core\Session::flash('dev_token'); if ($dev): ?>
                <div class="alert alert-info">ðŸ”§ Dev token: <span class="mono"><?= e($dev) ?></span></div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger">âš ï¸ <?= e($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= url('/forgot-password') ?>">
            <?= \App\Core\CSRF::field() ?>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.email')) ?></label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <p class="form-text"><?= e(t('auth.forgot_description')) ?></p>
            <button type="submit" class="btn btn-primary btn-block"><?= e(t('auth.forgot_button')) ?></button>
        </form>
        <div class="auth-links">
            <a href="<?= url('/login') ?>"><?= e(t('common.login')) ?></a>
        </div>
    </div>
</div>
</body>
</html>
