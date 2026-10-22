<?php
/** @var array $orders @var array $items */
$pageTitle = t('restaurant.kitchen') ?? 'Kitchen';
ob_start();
?>
<?php if (empty($orders)): ?>
    <div class="empty-state"><div class="es-icon">🔥</div><div class="es-title"><?= e(t('restaurant.kitchen_empty') ?? 'Kitchen is clear') ?></div><p><?= e(t('restaurant.kitchen_empty_desc') ?? 'No orders currently being prepared.') ?></p></div>
<?php else: ?>
    <?php
    $itemsByOrder = [];
    foreach ($items as $it) {
        $itemsByOrder[(int)$it['order_id']][] = $it;
    }
    ?>
    <div class="stat-grid">
        <?php foreach ($orders as $o): ?>
            <?php $oid = (int)$o['id']; ?>
            <div class="card" style="border-top:4px solid var(--warning);">
                <div class="card-header">
                    <div class="card-title">#<?= e($o['order_number']) ?> <?= !empty($o['table_number']) ? '<span class="badge badge-info">🍽️ #' . e($o['table_number']) . '</span>' : '' ?></div>
                    <?= statusBadge((string)$o['status']) ?>
                </div>
                <div class="card-body">
                    <div class="text-muted text-xs mb-2">🕐 <?= e(formatDate($o['ordered_at'])) ?><?= $o['guest_name'] ? ' · 👤 ' . e($o['guest_name']) : '' ?></div>
                    <?php foreach ($itemsByOrder[$oid] ?? [] as $it): ?>
                        <div class="d-flex justify-between items-center" style="padding:8px 0;border-bottom:1px solid var(--border);">
                            <div>
                                <div class="fw-700 text-sm"><?= e($it['product_name']) ?></div>
                                <div class="text-muted text-xs"><?= e($it['notes'] ?? '') ?></div>
                            </div>
                            <div class="d-flex items-center gap-2">
                                <span class="fw-700">×<?= e((string)$it['quantity']) ?></span>
                                <?php if ($it['delivery_status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-primary" onclick="kitchenStartItem(<?= (int)$it['id'] ?>)">▶ <?= e(t('restaurant.start') ?? 'Start') ?></button>
                                <?php else: ?>
                                    <span class="badge badge-<?= $it['delivery_status'] === 'full' ? 'active' : 'warning' ?>"><?= e($it['delivery_status']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-sm btn-success" onclick="kitchenSetStatus(<?= $oid ?>, 'preparing')">🔥 <?= e(t('restaurant.mark_preparing') ?? 'Mark preparing') ?></button>
                        <button class="btn btn-sm btn-primary" onclick="kitchenSetStatus(<?= $oid ?>, 'ready')">✅ <?= e(t('restaurant.mark_ready') ?? 'Mark ready') ?></button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<script>
function kitchenSetStatus(orderId, status) {
    ISHEMA.api('/orders/' + orderId + '/status', {status: status}, 'POST')
        .then(function (r) { if (r.ok) { location.reload(); } else { alert(r.error || 'Error'); } });
}
function kitchenStartItem(itemId) {
    ISHEMA.api('/orders/items/' + itemId + '/delivery', {quantity: 0}, 'POST')
        .then(function (r) { location.reload(); });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
