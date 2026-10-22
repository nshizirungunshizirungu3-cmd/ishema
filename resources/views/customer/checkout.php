<?php
/**
 * @var array $business @var array|null $table @var string $token
 * @var array $waiters @var array $cart {count,total,items}
 */
$pageTitle = t('customer.checkout') === 'checkout' ? t('customer.review_order') : t('customer.review_order');
$navActive = '';
ob_start();
?>
<div style="max-width:860px;margin:32px auto;">
    <h2 class="mb-3">🛒 <?= e(t('customer.review_order')) ?></h2>
    <div class="text-muted text-sm mb-3">
        🏢 <?= e($business['name']) ?> · 🍽️ <?= e(t('customer.table_number_label')) ?>: <?= $table ? e($table['table_number']) : '—' ?>
    </div>

    <?php if (empty($cart['items'])): ?>
        <div class="empty-state"><div class="es-icon">🛒</div><div class="es-title"><?= e(t('customer.cart_empty')) ?></div>
            <a href="<?= e(url('/qr/' . $token)) ?>" class="btn btn-primary"><?= e(t('common.back')) ?></a>
        </div>
    <?php else: ?>
        <div class="card mb-3">
            <div class="card-header"><div class="card-title">📋 <?= e(t('customer.order_summary')) ?> (<?= (int)$cart['count'] ?> <?= e(t('customer.items')) ?>)</div></div>
            <div class="card-body">
                <?php foreach ($cart['items'] as $item): ?>
                    <div class="d-flex justify-between items-center mb-2">
                        <div>
                            <div class="fw-700"><?= e($item['name']) ?></div>
                            <div class="text-muted text-xs"><?= e(t('customer.qty')) ?>: <?= (int)$item['quantity'] ?> · <?= e(formatMoney((float)$item['price'])) ?> <?= e(t('customer.each')) ?></div>
                        </div>
                        <div class="fw-700"><?= e(formatMoney((float)$item['price'] * (float)$item['quantity'])) ?></div>
                    </div>
                <?php endforeach; ?>
                <hr style="border:none;border-top:1px solid var(--border);">
                <div class="d-flex justify-between items-baseline">
                    <span class="fw-700"><?= e(t('common.total')) ?></span>
                    <span class="s-value"><?= e(formatMoney((float)$cart['total'])) ?></span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">👤 <?= e(t('common.details')) ?></div></div>
            <div class="card-body">
                <form id="place-order-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?= e(t('customer.your_name')) ?> <span class="req">*</span></label>
                            <input type="text" name="guest_name" id="guest_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= e(t('customer.your_phone')) ?></label>
                            <input type="tel" name="guest_phone" id="guest_phone" class="form-control" placeholder="+250...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('customer.select_waiter_label')) ?></label>
                        <select name="waiter_user_id" class="form-select">
                            <option value=""><?= e(t('customer.auto_assign_waiter')) ?></option>
                            <?php foreach ($waiters as $w): ?>
                                <option value="<?= (int)$w['user_id'] ?>"><?= e($w['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('customer.order_notes')) ?></label>
                        <textarea name="order_notes" class="form-control" rows="3" placeholder="<?= e(t('customer.order_notes_placeholder')) ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" id="submit-order" style="width:100%;">
                        ✅ <?= e(t('customer.place_order')) ?> — <?= e(formatMoney((float)$cart['total'])) ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('place-order-form');
    if (!form) { return; }
    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        var btn = document.getElementById('submit-order');
        btn.disabled = true;
        btn.textContent = '⏳ <?= e(t('customer.placing_order')) ?>';
        var data = {
            guest_name: form.guest_name.value.trim(),
            guest_phone: form.guest_phone.value.trim(),
            waiter_user_id: form.waiter_user_id.value || 0,
            order_notes: form.order_notes.value.trim()
        };
        if (!data.guest_name) { alert('<?= e(t('customer.your_name')) ?>'); btn.disabled = false; return; }
        ISHEMA.api('/qr/<?= e($token) ?>/place-order', data, 'POST')
            .then(function (r) {
                if (r.ok && r.order) {
                    ISHEMA.toast('✅ <?= e(t('customer.order_placed')) ?>');
                    window.location.href = r.redirect || '/order/' + r.order.order_number;
                } else {
                    alert(r.error || '<?= e(t('common.something_wrong')) ?>');
                    btn.disabled = false;
                    btn.textContent = '✅ <?= e(t('customer.place_order')) ?>';
                }
            })
            .catch(function () { btn.disabled = false; btn.textContent = '✅ <?= e(t('customer.place_order')) ?>'; });
    });
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
