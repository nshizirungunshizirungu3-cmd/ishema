<?php
/**
 * @var array $categories @var array $products @var array $tables @var array $waiters
 * @var array|null $order
 */
$pageTitle = $order ? t('common.edit') . ' ' . t('restaurant.order_management') : t('common.create') . ' ' . t('restaurant.order_management');
ob_start();
$catProducts = [];
foreach ($products as $p) { $catProducts[(int)($p['category_id'] ?? 0)][] = $p; }
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card" style="max-width:900px;">
    <div class="card-header">
        <div class="card-title">🧾 <?= $order ? e(t('common.edit')) : e(t('common.create')) ?> — <?= e(t('restaurant.order_management')) ?></div>
        <a href="/orders" class="btn btn-outline btn-sm" style="margin-left:auto;">← <?= e(t('common.back')) ?></a>
    </div>
    <form method="POST" action="<?= $order ? url('/orders/create') : url('/orders/create') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.table_label')) ?></label>
                <select name="table_id" class="form-select">
                    <option value="0">— <?= e(t('common.none')) ?> —</option>
                    <?php foreach ($tables as $t): ?>
                        <option value="<?= (int)$t['id'] ?>">#<?= e($t['table_number']) ?><?= $t['name'] ? ' — ' . e($t['name']) : '' ?> (<?= (int)$t['seats'] ?> 🪑)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.waiter_assigned')) ?></label>
                <select name="waiter_user_id" class="form-select">
                    <option value="0">— <?= e(t('restaurant.automatic')) ?> —</option>
                    <?php foreach ($waiters as $w): ?>
                        <option value="<?= (int)$w['user_id'] ?>"><?= e($w['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.guest_name')) ?></label>
                <input type="text" name="guest_name" class="form-control" value="<?= e($order['guest_name'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.guest_phone')) ?></label>
                <input type="tel" name="guest_phone" class="form-control" value="<?= e($order['guest_phone'] ?? '') ?>">
            </div>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:18px 0;">
        <h3 class="mb-3">🍽️ <?= e(t('restaurant.order_items')) ?></h3>
        <div class="form-group">
            <label class="form-label"><?= e(t('restaurant.product_category')) ?></label>
            <select id="product-category" class="form-select">
                <option value="0"><?= e(t('common.all')) ?></option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('common.products') )?></label>
            <select id="product-select" class="form-select">
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" data-cat="<?= (int)($p['category_id'] ?? 0) ?>" data-price="<?= e((string)$p['price']) ?>" data-name="<?= e($p['name']) ?>">
                        <?= e($p['name']) ?> — <?= e(formatMoney((float)$p['price'])) ?> (<?= (float)$p['stock_quantity'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-outline btn-sm mt-2" onclick="addItem()"><?= e(t('common.add_new')) ?></button>
        </div>

        <table class="table" id="items-table">
            <thead><tr>
                <th><?= e(t('restaurant.product_name')) ?></th>
                <th><?= e(t('common.quantity')) ?></th>
                <th><?= e(t('restaurant.unit_price')) ?></th>
                <th><?= e(t('restaurant.line_total')) ?></th>
                <th><?= e(t('restaurant.order_notes')) ?></th>
                <th></th>
            </tr></thead>
            <tbody id="items-body"></tbody>
        </table>
        <div class="d-flex justify-between items-baseline mb-3">
            <span class="fw-700"><?= e(t('common.total')) ?></span>
            <span class="s-value" id="order-total"><?= e(formatMoney(0)) ?></span>
        </div>

        <div class="form-group">
            <label class="form-label"><?= e(t('restaurant.order_notes')) ?></label>
            <textarea name="notes" class="form-control" rows="2"><?= e($order['notes'] ?? '') ?></textarea>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.create')) ?> 🧾</button>
            <a href="/orders" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>

<script>
var itemIndex = 0;
function addItem() {
    var sel = document.getElementById('product-select');
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) { return; }
    var pid = opt.value;
    var name = opt.dataset.name;
    var price = parseFloat(opt.dataset.price) || 0;
    if (document.querySelector('input[name="item_product_ids[]"][value="' + pid + '"]')) { return; }
    var tbody = document.getElementById('items-body');
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td class="fw-700">' + name + '<input type="hidden" name="item_product_ids[]" value="' + pid + '"></td>' +
        '<td><input type="number" name="item_quantities[]" value="1" min="1" step="0.01" class="form-control item-qty" style="width:90px;"></td>' +
        '<td class="text-muted">' + price.toFixed(0) + '</td>' +
        '<td class="fw-700 item-line">' + price.toFixed(0) + '</td>' +
        '<td><input type="text" name="item_notes[]" class="form-control" style="width:150px;" placeholder="..."></td>' +
        '<td><button type="button" class="btn btn-sm btn-danger" onclick="this.closest(\'tr\').remove(); recalcOrder()">✕</button></td>';
    tbody.appendChild(tr);
    tr.querySelector('.item-qty').addEventListener('input', recalcOrder);
    recalcOrder();
}
function recalcOrder() {
    var total = 0;
    document.querySelectorAll('#items-body tr').forEach(function (tr) {
        var qty = parseFloat(tr.querySelector('.item-qty').value) || 0;
        var price = parseFloat(tr.querySelector('.item-qty').closest('tr').children[2].textContent) || 0;
        var line = qty * price;
        tr.querySelector('.item-line').textContent = line.toFixed(0);
        total += line;
    });
    document.getElementById('order-total').textContent = total.toFixed(0) + ' RWF';
}
document.getElementById('product-category').addEventListener('change', function () {
    var cat = this.value;
    document.querySelectorAll('#product-select option').forEach(function (opt) {
        opt.style.display = (cat === '0' || opt.value === '' || opt.dataset.cat === cat) ? '' : 'none';
    });
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
