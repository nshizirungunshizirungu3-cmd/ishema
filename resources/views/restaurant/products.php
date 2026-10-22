<?php
/** @var array $products @var array $categories @var array $lowStock @var int|null $selectedCategory @var string $search */
$pageTitle = t('restaurant.product_management');
ob_start();
$catNames = [];
foreach ($categories as $c) { $catNames[(int)$c['id']] = (string)$c['name']; }
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<?php if (!empty($lowStock)): ?>
    <div class="alert alert-warning">
        ⚠️ <strong><?= e(t('restaurant.low_stock_alert')) ?>:</strong>
        <?php foreach ($lowStock as $lp): ?>
            <?= e($lp['name']) ?> (<?= (int)$lp['stock_quantity'] ?>)<?= $lp !== end($lowStock) ? ', ' : '' ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🍽️ <?= e(t('restaurant.product_management')) ?> (<?= count($products) ?>)</div>
        <a href="/restaurant/products/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('restaurant.add_product')) ?></a>
    </div>
    <form method="GET" action="/restaurant/products" class="d-flex gap-2 mb-3">
        <select name="category_id" class="form-select" style="width:auto;">
            <option value="0"><?= e(t('common.all')) ?> <?= e(t('restaurant.product_category')) ?>s</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id'] ?>"<?= $selectedCategory === (int)$c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="search" class="form-control" style="max-width:280px;" placeholder="<?= e(t('common.search_placeholder')) ?>" value="<?= e($search) ?>">
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.filter')) ?></button>
    </form>
    <?php if (empty($products)): ?>
        <div class="empty-state"><div class="es-icon">🍽️</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th></th>
                    <th><?= e(t('restaurant.product_name')) ?></th>
                    <th><?= e(t('restaurant.product_category')) ?></th>
                    <th><?= e(t('restaurant.product_price')) ?></th>
                    <th><?= e(t('restaurant.cost_price')) ?></th>
                    <th><?= e(t('restaurant.product_stock')) ?></th>
                    <th><?= e(t('restaurant.is_available')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?php if (!empty($p['image'])): ?><img src="<?= e(asset($p['image'])) ?>" style="width:44px;height:44px;object-fit:cover;border-radius:8px;" alt=""><?php endif; ?></td>
                            <td class="fw-700">
                                <?= e($p['name']) ?>
                                <?php if (!empty($p['barcode'])): ?><div class="mono text-muted text-xs"><?= e($p['barcode']) ?></div><?php endif; ?>
                            </td>
                            <td><?= e($catNames[(int)$p['category_id']] ?? '—') ?></td>
                            <td class="fw-700"><?= e(formatMoney((float)$p['price'])) ?></td>
                            <td class="text-muted"><?= e(formatMoney((float)$p['cost_price'])) ?></td>
                            <td>
                                <?= (float)$p['stock_quantity'] ?>
                                <?php if ((float)$p['stock_quantity'] <= (float)$p['min_stock']): ?>
                                    <span class="badge badge-warning">⚠ <?= e(t('restaurant.low_stock')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$p['is_available'] ? '<span class="badge badge-active">● ' . e(t('common.yes')) . '</span>' : '<span class="badge badge-inactive">● ' . e(t('common.no')) . '</span>' ?></td>
                            <td class="actions">
                                <a href="/restaurant/products/<?= (int)$p['id'] ?>/edit" class="btn btn-sm btn-outline">✏️ <?= e(t('common.edit')) ?></a>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/restaurant/products/<?= (int)$p['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
