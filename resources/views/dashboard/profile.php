<?php
/** @var array $user */
$pageTitle = t('common.profile');
ob_start();
?>
<?php if ($message): ?><div class="alert alert-success">âœ… <?= e($message) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger">âš ï¸ <?= e($error) ?></div><?php endif; ?>

<div class="card" style="max-width:640px;">
    <div class="card-header"><div class="card-title">ðŸ‘¤ <?= e(t('common.profile')) ?></div></div>
    <form method="POST" action="/profile/update">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.full_name')) ?> <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($user['name']) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.phone')) ?></label>
                <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.email')) ?></label>
                <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.language')) ?></label>
                <select name="language" class="form-select">
                    <?php foreach (\App\Core\Translator::LANGS as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= $code === $user['language'] ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('common.role')) ?></label>
            <input type="text" class="form-control" value="<?= e(t('common.' . strtolower((string)$user['role']))) ?>" disabled>
        </div>
        <button type="submit" class="btn btn-primary"><?= e(t('common.save_changes')) ?></button>
    </form>
</div>

<div class="card" style="max-width:640px;">
    <div class="card-header"><div class="card-title">ðŸ” <?= e(t('auth.change_password') ?? 'Change Password') ?></div></div>
    <form id="password-form">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-group">
            <label class="form-label"><?= e(t('auth.current_password')) ?> <span class="req">*</span></label>
            <input type="password" name="current_password" class="form-control" required id="cur-pass">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('auth.new_password')) ?> <span class="req">*</span></label>
                <input type="password" name="new_password" class="form-control" required minlength="8" id="new-pass">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('auth.confirm_password')) ?> <span class="req">*</span></label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8" id="conf-pass">
            </div>
        </div>
        <button type="submit" class="btn btn-primary"><?= e(t('common.update')) ?></button>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('password-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var cur = document.getElementById('cur-pass').value;
            var nw = document.getElementById('new-pass').value;
            var cf = document.getElementById('conf-pass').value;
            if (nw !== cf) { ISHEMA.toast('<?= e(t('auth.passwords_match')) ?>', 'error'); return; }
            ISHEMA.api('/profile/password', { current_password: cur, new_password: nw, confirm_password: cf })
                .then(function (res) {
                    if (res.ok) { ISHEMA.toast(res.message); form.reset(); }
                    else { ISHEMA.toast(res.error, 'error'); }
                });
        });
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
