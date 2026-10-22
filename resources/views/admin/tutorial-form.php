<?php
/** @var array|null $tutorial */
$pageTitle = $tutorial ? t('admin.edit_tutorial') : t('admin.create_tutorial');
ob_start();
$cats = ['hotel', 'restaurant', 'owner', 'employee', 'general'];
$langs = \App\Core\Translator::LANGS;
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:820px;">
    <div class="card-header"><div class="card-title">🎥 <?= $tutorial ? e(t('admin.edit_tutorial')) : e(t('admin.create_tutorial')) ?></div></div>
    <form method="POST" action="<?= $tutorial ? url('/admin/tutorials/' . $tutorial['id'] . '/update') : url('/admin/tutorials/create') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.title')) ?> <span class="req">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= e($tutorial['title'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.category') )?></label>
                <select name="category" class="form-select">
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= e($c) ?>"<?= ($tutorial['category'] ?? 'general') === $c ? ' selected' : '' ?>>
                            <?= $c === 'general' ? e(ucfirst($c)) : e(t('public.tutorial_category_' . $c)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.video_url')) ?></label>
                <input type="url" name="video_url" class="form-control" placeholder="https://..." value="<?= e($tutorial['video_url'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.thumbnail')) ?></label>
                <input type="text" name="thumbnail" class="form-control" value="<?= e($tutorial['thumbnail'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.duration')) ?></label>
                <input type="text" name="duration" class="form-control" placeholder="12:34" value="<?= e($tutorial['duration'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.language')) ?></label>
                <select name="language" class="form-select">
                    <?php foreach ($langs as $code => $label): ?>
                        <option value="<?= e($code) ?>"<?= ($tutorial['language'] ?? 'en') === $code ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('common.description')) ?></label>
            <textarea name="description" class="form-control" rows="4"><?= e($tutorial['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.sort_order')) ?></label>
                <input type="number" name="sort_order" class="form-control" value="<?= e((string)($tutorial['sort_order'] ?? 0)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.status')) ?></label>
                <label class="d-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1"<?= !isset($tutorial) || (int)$tutorial['is_active'] ? ' checked' : '' ?>>
                    <span class="text-sm"><?= e(t('common.active')) ?></span>
                </label>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            <a href="/admin/tutorials" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
