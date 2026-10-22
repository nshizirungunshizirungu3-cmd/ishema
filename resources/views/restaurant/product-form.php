<?php
/** @var array $categories @var array|null $product */
$pageTitle = $product ? t('restaurant.edit_product') : t('restaurant.add_product');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:860px;">
    <div class="card-header">
        <div class="card-title">🍽️ <?= $product ? e(t('restaurant.edit_product')) : e(t('restaurant.add_product')) ?></div>
        <a href="/restaurant/products" class="btn btn-outline btn-sm" style="margin-left:auto;">← <?= e(t('common.back')) ?></a>
    </div>
    <form method="POST" action="<?= $product ? url('/restaurant/products/' . $product['id'] . '/update') : url('/restaurant/products/create') ?>" enctype="multipart/form-data">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.product_name')) ?> <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($product['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.product_category')) ?></label>
                <select name="category_id" class="form-select">
                    <option value="0">—</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"<?= ($product['category_id'] ?? 0) == $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.product_price')) ?> <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?= e((string)($product['price'] ?? 0)) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.cost_price')) ?></label>
                <input type="number" step="0.01" min="0" name="cost_price" class="form-control" value="<?= e((string)($product['cost_price'] ?? 0)) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.product_stock')) ?></label>
                <input type="number" step="0.01" min="0" name="stock_quantity" class="form-control" value="<?= e((string)($product['stock_quantity'] ?? 0)) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.min_stock')) ?></label>
                <input type="number" step="0.01" min="0" name="min_stock" class="form-control" value="<?= e((string)($product['min_stock'] ?? 0)) ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.barcode')) ?></label>
                <input type="text" name="barcode" class="form-control" value="<?= e($product['barcode'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.product_image')) ?></label>
                <input type="file" name="image" class="form-control" accept="image/*">
            </div>
        </div>
        <?php if ($product && !empty($product['image'])): ?>
            <div class="form-group">
                <img src="<?= e(asset($product['image'])) ?>" style="width:140px;height:100px;object-fit:cover;border-radius:8px;" alt="">
            </div>
        <?php endif; ?>
        <div class="form-group">
            <label class="form-label"><?= e(t('restaurant.product_description')) ?></label>
            <textarea name="description" class="form-control" rows="3"><?= e($product['description'] ?? '') ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.is_available')) ?></label>
                <label class="d-flex items-center gap-2">
                    <input type="checkbox" name="is_available" value="1"<?= !isset($product) || (int)$product['is_available'] ? ' checked' : '' ?>>
                    <span class="text-sm"><?= e(t('restaurant.is_available')) ?></span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.sort_by') === 'common.sort_by' ? 'Sort order' : 'Sort order') ?></label>
                <input type="number" name="sort_order" class="form-control" value="<?= e((string)($product['sort_order'] ?? 0)) ?>">
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            <a href="/restaurant/products" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
