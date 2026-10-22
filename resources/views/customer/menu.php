<?php
/**
 * @var array $business @var array|null $table @var string $token
 * @var array $categories @var array $products @var array $cart @var string $lang
 */
$pageTitle = t('customer.our_menu');
$navActive = '';
ob_start();
$cartCount = 0;
$cartTotal = 0.0;
foreach ($cart as $item) {
    $cartCount += (int)$item['quantity'];
    $cartTotal += (float)$item['quantity'] * (float)$item['price'];
}
?>
<div style="max-width:1100px;margin:32px auto;">
    <div class="d-flex justify-between items-center mb-3" style="flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="margin:0;"><?= e($business['name']) ?></h2>
            <div class="text-muted text-sm">
                🍽️ <?= e(t('customer.table_number_label')) ?>: <?= $table ? e($table['table_number']) : '—' ?>
                · 🌐 <?= e(strtoupper($lang)) ?>
            </div>
        </div>
        <div class="lang-select">
            🌐
            <select id="language-select">
                <?php foreach (\App\Core\Translator::LANGS as $code => $label): ?>
                    <option value="<?= e($code) ?>"<?= $code === $lang ? ' selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <?php if (empty($categories) && empty($products)): ?>
        <div class="empty-state"><div class="es-icon">🍽️</div><div class="es-title"><?= e(t('customer.menu_unavailable')) ?></div></div>
    <?php else: ?>
        <div class="d-flex gap-2 mb-3" style="flex-wrap:wrap;" id="category-tabs">
            <button class="btn btn-primary btn-sm cat-tab active" data-cat="all"><?= e(t('common.all')) ?></button>
            <?php foreach ($categories as $cat): ?>
                <button class="btn btn-outline btn-sm cat-tab" data-cat="<?= (int)$cat['id'] ?>"><?= e($cat['name']) ?></button>
            <?php endforeach; ?>
        </div>

        <div class="grid-2" id="product-grid" style="align-items:start;">
            <div>
                <?php if (empty($products)): ?>
                    <div class="empty-state"><div class="es-icon">🍽️</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <?php if (!(int)$p['is_available'] || (float)$p['stock_quantity'] <= 0): continue; endif; ?>
                        <div class="card mb-3 product-card" data-category="<?= (int)($p['category_id'] ?? 0) ?>">
                            <div class="card-body d-flex justify-between items-center" style="gap:14px;">
                                <div style="flex:1;">
                                    <div class="fw-700"><?= e($p['name']) ?></div>
                                    <?php if (!empty($p['description'])): ?>
                                        <div class="text-muted text-sm"><?= e(\App\Core\Security::truncate((string)$p['description'], 120)) ?></div>
                                    <?php endif; ?>
                                    <div class="text-muted text-sm mt-1">
                                        💰 <?= e(formatMoney((float)$p['price'])) ?>
                                        <?php if ((float)$p['stock_quantity'] <= 10): ?>
                                            · <span class="badge badge-warning">⚠ <?= e(t('customer.out_of_stock')) === 'Out of stock' ? e(t('restaurant.low_stock')) : e(t('restaurant.low_stock')) ?> (<?= (int)$p['stock_quantity'] ?>)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm add-cart" data-id="<?= (int)$p['id'] ?>" data-name="<?= e($p['name']) ?>" data-price="<?= e((string)$p['price']) ?>">
                                    ＋ <?= e(t('customer.add_to_cart')) ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div>
                <div class="card" style="position:sticky;top:16px;">
                    <div class="card-header">
                        <div class="card-title">🛒 <?= e(t('customer.your_cart')) ?> (<span id="cart-count"><?= $cartCount ?></span>)</div>
                        <?php if ($cartCount > 0): ?>
                            <button class="btn btn-outline btn-sm" id="clear-cart">🗑 <?= e(t('customer.remove_from_cart')) ?></button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($cartCount === 0): ?>
                            <div class="empty-state" style="padding:20px;"><div class="es-icon">🛒</div><div class="es-title"><?= e(t('customer.cart_empty')) ?></div></div>
                        <?php else: ?>
                            <div id="cart-items">
                                <?php foreach ($cart as $pid => $item): ?>
                                    <div class="d-flex justify-between items-center mb-2" style="gap:10px;">
                                        <div style="flex:1;">
                                            <div class="fw-700 text-sm"><?= e($item['name']) ?></div>
                                            <div class="text-muted text-xs"><?= e(formatMoney((float)$item['price'])) ?> <?= e(t('customer.each')) ?></div>
                                        </div>
                                        <div class="d-flex items-center gap-2">
                                            <button class="btn btn-outline btn-sm qty-btn" data-id="<?= (int)$pid ?>" data-delta="-1">−</button>
                                            <span class="fw-700" id="qty-<?= (int)$pid ?>"><?= (int)$item['quantity'] ?></span>
                                            <button class="btn btn-outline btn-sm qty-btn" data-id="<?= (int)$pid ?>" data-delta="1">＋</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <hr style="border:none;border-top:1px solid var(--border);">
                            <div class="d-flex justify-between items-baseline">
                                <span class="fw-700"><?= e(t('common.total')) ?></span>
                                <span class="s-value" id="cart-total"><?= e(formatMoney($cartTotal)) ?></span>
                            </div>
                            <a href="<?= e(url('/qr/' . $token . '/checkout')) ?>" class="btn btn-primary" style="width:100%;margin-top:14px;">
                                <?= e(t('customer.review_order')) ?> →
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
var QR_TOKEN = <?= json_encode($token) ?>;
var CART_BASE = '/qr/' + encodeURIComponent(QR_TOKEN);

function refreshCart(data) {
    if (!data || !data.cart) { return; }
    var count = 0, total = 0;
    data.cart.items.forEach(function (it) { count += parseInt(it.quantity, 10) || 0; total += (parseFloat(it.price) * (parseFloat(it.quantity) || 0)); });
    var cc = document.getElementById('cart-count');
    if (cc) { cc.textContent = count; }
    var ct = document.getElementById('cart-total');
    if (ct) { ct.textContent = total.toFixed(0) + ' RWF'; }
}

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.add-cart').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ISHEMA.api(CART_BASE + '/add-to-cart', {product_id: btn.dataset.id, quantity: 1}, 'POST')
                .then(function (r) { if (r.ok) { refreshCart(r); ISHEMA.toast('✅ ' + btn.dataset.name); } else { alert(r.error || 'Error'); } })
                .catch(function () { alert('Error'); });
        });
    });
    document.querySelectorAll('.qty-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var cur = parseInt((document.getElementById('qty-' + btn.dataset.id) || {}).textContent || '0', 10);
            var next = Math.max(0, cur + (parseInt(btn.dataset.delta, 10) || 0));
            ISHEMA.api(CART_BASE + '/update-cart', {product_id: btn.dataset.id, quantity: next}, 'POST')
                .then(function (r) { if (r.ok) { refreshCart(r); if (next === 0) { location.reload(); } } else { alert(r.error || 'Error'); } })
                .catch(function () { alert('Error'); });
        });
    });
    var clr = document.getElementById('clear-cart');
    if (clr) {
        clr.addEventListener('click', function () {
            ISHEMA.api(CART_BASE + '/clear-cart', {}, 'POST').then(function () { location.reload(); });
        });
    }
    document.querySelectorAll('.cat-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.cat-tab').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var cat = tab.dataset.cat;
            document.querySelectorAll('.product-card').forEach(function (card) {
                card.style.display = (cat === 'all' || card.dataset.category === cat) ? '' : 'none';
            });
        });
    });
    var langSel = document.getElementById('language-select');
    if (langSel) {
        langSel.addEventListener('change', function () { window.location.href = '/language/' + langSel.value + '?next=<?= e(urlencode('/qr/' . $token)) ?>'; });
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
