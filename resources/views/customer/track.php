<?php
/**
 * @var array $order @var array $business @var array|null $table
 * @var array $items @var array $deliveries @var array $confirmations
 * @var string $phone @var bool $canConfirm @var array|null $review
 */
$pageTitle = t('customer.track_order') . ' ' . e($order['order_number']);
$navActive = '';
ob_start();
$statusKey = 'status_' . (string)$order['status'];
$statusLabel = t('customer.' . $statusKey);
if ($statusLabel === 'customer.' . $statusKey) { $statusLabel = ucfirst((string)$order['status']); }
?>
<div style="max-width:860px;margin:32px auto;">
    <div class="d-flex justify-between items-center mb-3" style="flex-wrap:wrap;gap:12px;">
        <div>
            <h2 style="margin:0;">🧾 <?= e(t('customer.order_details')) ?></h2>
            <div class="text-muted text-sm">
                #<?= e($order['order_number']) ?> · 🏢 <?= e($business['name']) ?>
                · 🍽️ <?= e(t('customer.table_number_label')) ?>: <?= $table ? e($table['table_number']) : '—' ?>
            </div>
        </div>
        <span class="badge badge-info" id="order-status"><?= e($statusLabel) ?></span>
    </div>

    <div class="card mb-3">
        <div class="card-header"><div class="card-title">📈 <?= e(t('customer.order_progress')) ?></div></div>
        <div class="card-body">
            <div class="d-flex gap-2" style="flex-wrap:wrap;">
                <?php
                $steps = ['pending', 'confirmed', 'preparing', 'ready', 'delivered'];
                $current = array_search((string)$order['status'], $steps, true);
                if ($current === false) { $current = in_array((string)$order['status'], ['completed', 'partially_delivered'], true) ? 4 : 0; }
                foreach ($steps as $i => $s): ?>
                    <div class="text-center" style="flex:1;min-width:90px;">
                        <div class="fw-700 text-sm" style="color:<?= $i <= $current ? 'var(--primary)' : 'var(--text-muted)' ?>;">
                            <?= $i <= $current ? '●' : '○' ?> <?= e(t('customer.status_' . $s)) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><div class="card-title">🍽️ <?= e(t('restaurant.order_items')) ?></div></div>
        <div class="card-body">
            <?php foreach ($items as $item): ?>
                <?php
                $del = $deliveries[(int)$item['id']] ?? null;
                $conf = $confirmations[(int)$item['id']] ?? null;
                ?>
                <div class="mb-3" style="padding-bottom:14px;border-bottom:1px solid var(--border);">
                    <div class="d-flex justify-between items-center">
                        <div class="fw-700"><?= e($item['product_name']) ?></div>
                        <div class="fw-700"><?= e(formatMoney((float)$item['line_total'])) ?></div>
                    </div>
                    <div class="text-muted text-sm">
                        <?= e(t('customer.ordered_quantity_label')) ?>: <?= (float)$item['quantity'] ?>
                        · <?= e(t('customer.delivered_quantity_label')) ?>: <?= (float)($item['delivered_quantity'] ?? 0) ?>
                        · <?= e(t('customer.qty')) ?>: <?= e(formatMoney((float)$item['unit_price'])) ?>
                        <?php if (!empty($item['notes'])): ?>· 📝 <?= e($item['notes']) ?><?php endif; ?>
                    </div>
                    <?php if ($del): ?>
                        <div class="text-muted text-xs mt-1">🚚 <?= e(t('restaurant.delivered_at')) ?>: <?= e(formatDate($del['delivered_at'] ?? '')) ?></div>
                    <?php endif; ?>
                    <?php if ($conf): ?>
                        <div class="text-muted text-xs mt-1">✅ <?= e(t('customer.confirm_received')) ?>: <?= (float)$conf['quantity_received'] ?> · <?= e(formatDate($conf['confirmed_at'] ?? '')) ?></div>
                    <?php endif; ?>
                    <?php if ($canConfirm && !in_array((string)$order['status'], ['completed', 'cancelled'], true) && (float)($item['confirmed_quantity'] ?? 0) < (float)$item['quantity']): ?>
                        <button class="btn btn-outline btn-sm mt-2" onclick="confirmItem(<?= (int)$item['id'] ?>, <?= (float)$item['quantity'] ?>)">
                            ✅ <?= e(t('customer.confirm_receipt')) ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="d-flex justify-between items-baseline mt-2">
                <span class="fw-700"><?= e(t('common.total')) ?></span>
                <span class="s-value"><?= e(formatMoney((float)$order['total_amount'])) ?></span>
            </div>
        </div>
    </div>

    <?php if ($canConfirm && !in_array((string)$order['status'], ['completed', 'cancelled'], true)): ?>
        <div class="card mb-3">
            <div class="card-header"><div class="card-title">🧹 <?= e(t('customer.request_table_clearing')) ?></div></div>
            <div class="card-body">
                <button class="btn btn-outline" onclick="requestClearing()"><?= e(t('customer.request_table_clearing')) ?></button>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($canConfirm && in_array((string)$order['status'], ['completed', 'delivered', 'partially_delivered'], true) && !$review): ?>
        <div class="card mb-3">
            <div class="card-header"><div class="card-title">⭐ <?= e(t('customer.rate_service')) ?></div></div>
            <div class="card-body">
                <form id="rate-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?= e(t('customer.rate_overall')) ?> (1-5)</label>
                            <select name="overall_rating" class="form-select">
                                <option value="5">⭐⭐⭐⭐⭐</option>
                                <option value="4">⭐⭐⭐⭐</option>
                                <option value="3">⭐⭐⭐</option>
                                <option value="2">⭐⭐</option>
                                <option value="1">⭐</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.comment')) ?></label>
                        <textarea name="comment" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><?= e(t('customer.submit_rating')) ?></button>
                </form>
            </div>
        </div>
    <?php elseif ($review): ?>
        <div class="card mb-3">
            <div class="card-header"><div class="card-title">⭐ <?= e(t('customer.rate_service')) ?></div></div>
            <div class="card-body">
                <div class="fw-700"><?= str_repeat('⭐', (int)$review['overall_rating']) ?> <?= (int)$review['overall_rating'] ?>/5</div>
                <?php if (!empty($review['comment'])): ?><p class="text-muted text-sm"><?= e($review['comment']) ?></p><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex gap-2">
        <a href="/order/<?= e($order['order_number']) ?>/receipt" class="btn btn-outline">🧾 <?= e(t('customer.view_receipt')) ?></a>
        <button class="btn btn-outline" onclick="window.location.reload()">🔄 <?= e(t('common.refresh') === 'common.refresh' ? 'Refresh' : 'Refresh') ?></button>
    </div>
</div>

<script>
var ORDER_NUMBER = <?= json_encode($order['order_number']) ?>;
var PHONE = <?= json_encode($phone) ?>;

function pollStatus() {
    ISHEMA.api('/order/' + encodeURIComponent(ORDER_NUMBER) + '/poll', {}, 'GET')
        .then(function (r) {
            if (!r.ok) { return; }
            var el = document.getElementById('order-status');
            if (el) { el.textContent = r.status; }
        })
        .catch(function () {});
}
setInterval(pollStatus, 8000);

function confirmItem(itemId, qty) {
    var quantity = parseFloat(prompt('<?= e(t('customer.received_quantity')) ?>', String(qty)));
    if (isNaN(quantity) || quantity <= 0) { return; }
    ISHEMA.api('/order/confirm-receipt', {order_item_id: itemId, quantity: quantity, phone: PHONE}, 'POST')
        .then(function (r) {
            if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); }
            else { alert(r.error || 'Error'); }
        })
        .catch(function () { alert('Error'); });
}

function requestClearing() {
    ISHEMA.api('/order/request-clearing', {order_id: <?= (int)$order['id'] ?>, phone: PHONE}, 'POST')
        .then(function (r) {
            if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); }
            else { alert(r.error || 'Error'); }
        })
        .catch(function () { alert('Error'); });
}

document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('rate-form');
    if (form) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            ISHEMA.api('/order/rate', {
                order_id: <?= (int)$order['id'] ?>,
                overall_rating: parseInt(form.overall_rating.value, 10),
                food_quality: parseInt(form.overall_rating.value, 10),
                service_rating: parseInt(form.overall_rating.value, 10),
                speed_rating: parseInt(form.overall_rating.value, 10),
                comment: form.comment.value,
                phone: PHONE
            }, 'POST')
            .then(function (r) {
                if (r.ok) { ISHEMA.toast('⭐ ' + (r.message || '')); location.reload(); }
                else { alert(r.error || 'Error'); }
            })
            .catch(function () { alert('Error'); });
        });
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
