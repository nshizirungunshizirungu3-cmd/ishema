<!DOCTYPE html>
<html lang="<?= e(\App\Core\Translator::locale()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(\App\Core\CSRF::token()) ?>">
    <title><?= e(t('auth.select_business')) ?> Â· ISHEMA</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:480px;">
        <div class="auth-brand">
            <div class="a-logo">I</div>
            <h2>ISHEMA</h2>
            <p><?= e(t('auth.select_business')) ?></p>
        </div>
        <form method="POST" action="<?= url('/select-business') ?>">
            <?= \App\Core\CSRF::field() ?>
            <?php foreach ($choices as $c): ?>
                <label style="display:flex;align-items:center;gap:12px;padding:14px;border:1px solid var(--border);border-radius:10px;margin-bottom:10px;cursor:pointer;">
                    <input type="radio" name="business_id" value="<?= (int)$c['business_id'] ?>" required style="accent-color:var(--primary);">
                    <div>
                        <div style="font-weight:700;"><?= e($c['name']) ?></div>
                        <div class="text-muted text-sm"><?= e(t('admin.business_type_' . strtolower($c['type']))) ?></div>
                    </div>
                </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary btn-block"><?= e(t('common.continue')) ?></button>
        </form>
    </div>
</div>
</body>
</html>
