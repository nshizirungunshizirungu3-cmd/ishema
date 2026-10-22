<?php
/** @var array $order @var array $items @var array $deliveries @var array $confirmations @var array $waiters */
$pageTitle = '#' . $order['order_number'];
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="d-flex justify-between items-center mb-3">
    <div>
        <h2 style="margin:0;" class="mono">#<?= e($order['order_number']) ?></h2>
        <div class="text-muted text-sm">
            📅 <?= e(formatDate($order['ordered_at'])) ?>
            · 📡 <?= e(t('restaurant.source_' . (string)$order['source']) ?? $order['source']) ?>
            · 👤 <?= e($order['guest_name'] ?? t('common.guest') ?? 'Guest') ?><?= $order['guest_phone'] ? ' (' . e($order['guest_phone']) . ')' : '' ?>
        </div>
    </div>
    <div class="d-flex items-center gap-2">
        <?= statusBadge((string)$order['status']) ?>
        <a href="/orders/<?= (int)$order['id'] ?>/receipt" target="_blank" class="btn btn-outline btn-sm">🖨 <?= e(t('common.print') ?? 'Print') ?></a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="s-icon green">💰</div><div><div class="s-value"><?= e(formatMoney((float)$order['total_amount'])) ?></div><div class="s-label"><?= e(t('common.total') ?? 'Total') ?></div></div></div>
    <div class="stat-card"><div class="s-icon navy">🍽️</div><div><div class="s-value"><?= !empty($order['table_number']) ? '#' . e($order['table_number']) : '—' ?></div><div class="s-label"><?= e(t('restaurant.table') ?? 'Table') ?></div></div></div>
    <div class="stat-card"><div class="s-icon blue">👨‍🍳</div><div><div class="s-value" style="font-size:18px;"><?= e($order['waiter_name'] ?? '—') ?></div><div class="s-label"><?= e(t('restaurant.waiter') ?? 'Waiter') ?></div></div></div>
    <div class="stat-card"><div class="s-icon orange">📦</div><div><div class="s-value"><?= count($items) ?></div><div class="s-label"><?= e(t('restaurant.item_count') ?? 'Items') ?></div></div></div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">📦 <?= e(t('restaurant.items') ?? 'Items') ?></div>
        <div class="d-flex gap-2" style="margin-left:auto;">
            <select id="order-status-select" class="form-select" style="width:auto;">
                <?php
                $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'partially_delivered', 'completed', 'cancelled'];
                foreach ($statuses as $st): ?>
                    <option value="<?= e($st) ?>"<?= $order['status'] === $st ? ' selected' : '' ?>><?= e(t('restaurant.status_' . $st) ?? $st) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary btn-sm" id="order-status-save"><?= e(t('common.update_status') ?? 'Update status') ?></button>
        </div>
    </div>
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th><?= e(t('common.product') ?? 'Product') ?></th>
                <th><?= e(t('common.qty') ?? 'Qty') ?></th>
                <th><?= e(t('restaurant.unit_price') ?? 'Unit price') ?></th>
                <th><?= e(t('common.line_total') ?? 'Line total') ?></th>
                <th>📦 <?= e(t('restaurant.delivery') ?? 'Delivery') ?></th>
                <th>✅ <?= e(t('restaurant.confirmation') ?? 'Customer confirmation') ?></th>
                <th>📝</th>
                <th>📦➕</th>
            </tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <?php $iid = (int)$item['id']; ?>
                    <tr>
                        <td class="fw-700"><?= e($item['product_name']) ?></td>
                        <td><?= e((string)$item['quantity']) ?></td>
                        <td><?= e(formatMoney((float)$item['unit_price'])) ?></td>
                        <td class="fw-700"><?= e(formatMoney((float)$item['line_total'])) ?></td>
                        <td>
                            <?= statusBadge((string)$item['delivery_status']) ?>
                            <div class="text-muted text-xs">
                                <?php $dList = $deliveries[$iid] ?? []; ?>
                                <?php if (!empty($dList)): ?>
                                    <?php foreach ($dList as $d): ?>
                                        +<?= e((string)$d['quantity']) ?><?= $d['status'] !== 'delivered' ? ' (' . e($d['status']) . ')' : '' ?>,
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php $cList = $confirmations[$iid] ?? []; ?>
                            <?php if (!empty($cList)): ?>
                                <?php foreach ($cList as $c): ?>
                                    <span class="badge <?= $c['status'] === 'full' ? 'badge-active' : ($c['status'] === 'not_received' ? 'badge-danger' : 'badge-warning') ?>">
                                        <?= e((string)$c['confirmed_quantity']) ?>/<?= e((string)$c['ordered_quantity']) ?> <?= e($c['status']) ?>
                                    </span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted text-sm" style="max-width:200px;"><?= e($item['notes'] ?? '') ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-outline" onclick="recordDelivery(<?= $iid ?>, <?= e((string)$item['quantity']) ?>)">📦➕</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex gap-2">
    <button class="btn btn-outline" onclick="ISHEMA.api('/orders/<?= (int)$order['id'] ?>/cleared', {}).then(r => { if (r.ok) ISHEMA.toast('✅ ' + (r.message || 'Table cleared')); })">🧹 <?= e(t('restaurant.mark_table_cleared') ?? 'Mark table cleared') ?></button>
    <button class="btn btn-danger" onclick="ISHEMA.confirmDelete('/orders/<?= (int)$order['id'] ?>/cancel', '<?= e(t('restaurant.cancel_order_confirm') ?? 'Cancel this order?') ?>')">✖ <?= e(t('restaurant.cancel_order') ?? 'Cancel order') ?></button>
</div>

<script>
function recordDelivery(itemId, orderedQty) {
    var qty = prompt('<?= e(t('restaurant.delivered_qty') ?? 'Delivered quantity') ?>', orderedQty);
    if (qty === null) { return; }
    ISHEMA.api('/orders/items/' + itemId + '/delivery', {quantity: parseFloat(qty)}, 'POST')
        .then(function (r) {
            if (r.ok) { location.reload(); } else { alert(r.error || 'Error'); }
        });
}
document.addEventListener('DOMContentLoaded', function () {
    var saveBtn = document.getElementById('order-status-save');
    var sel = document.getElementById('order-status-select');
    if (saveBtn && sel) {
        saveBtn.addEventListener('click', function () {
            ISHEMA.api('/orders/<?= (int)$order['id'] ?>/status', {status: sel.value}, 'POST')
                .then(function (r) {
                    if (r.ok) { location.reload(); } else { alert(r.error || 'Error'); }
                });
        });
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
