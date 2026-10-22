<?php
/** @var array $orders @var string $status @var string $search @var array $tables @var array $waiters */
$pageTitle = t('restaurant.order_management');
ob_start();
$statuses = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'partially_delivered', 'completed', 'cancelled'];
$tableMap = [];
foreach ($tables as $t) { $tableMap[(int)$t['id']] = (string)$t['table_number']; }
$waiterMap = [];
foreach ($waiters as $w) { $waiterMap[(int)$w['user_id']] = (string)($w['name'] ?? ''); }
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🧾 <?= e(t('restaurant.order_management')) ?> (<?= count($orders) ?>)</div>
        <div class="d-flex gap-2" style="margin-left:auto;">
            <a href="/orders/create" class="btn btn-primary btn-sm">➕ <?= e(t('common.create')) ?></a>
            <a href="/orders/kitchen" class="btn btn-outline btn-sm">🍳 <?= e(t('restaurant.kitchen_queue')) ?></a>
            <a href="/orders/complaints" class="btn btn-outline btn-sm">⚠️ <?= e(t('customer.complaint_open')) ?></a>
        </div>
    </div>
    <form method="GET" action="/orders" class="d-flex gap-2 mb-3">
        <select name="status" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('common.status')) ?>es</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(t('customer.status_' . $s) === 'customer.status_' . $s ? ucfirst(str_replace('_', ' ', $s)) : t('customer.status_' . $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="search" class="form-control" style="max-width:240px;" placeholder="<?= e(t('restaurant.order_number')) ?>" value="<?= e($search) ?>">
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.filter')) ?></button>
    </form>
    <?php if (empty($orders)): ?>
        <div class="empty-state"><div class="es-icon">🧾</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('restaurant.order_number')) ?></th>
                    <th><?= e(t('restaurant.table_label')) ?></th>
                    <th><?= e(t('restaurant.guest_name')) ?></th>
                    <th><?= e(t('restaurant.waiter_assigned')) ?></th>
                    <th><?= e(t('restaurant.order_source')) ?></th>
                    <th><?= e(t('common.total')) ?></th>
                    <th><?= e(t('restaurant.order_status')) ?></th>
                    <th><?= e(t('restaurant.ordered_at')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td class="mono fw-700"><?= e($o['order_number']) ?></td>
                            <td><?= $o['table_id'] ? '#' . e($tableMap[(int)$o['table_id']] ?? '—') : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <?= e((string)($o['guest_name'] ?? '—')) ?>
                                <?php if (!empty($o['guest_phone'])): ?><div class="text-muted text-xs">📞 <?= e($o['guest_phone']) ?></div><?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e($waiterMap[(int)$o['waiter_user_id']] ?? '—') ?></td>
                            <td>
                                <?php if ((string)$o['source'] === 'qr'): ?><span class="badge badge-info">📱 <?= e(t('restaurant.source_qr')) ?></span>
                                <?php else: ?><span class="badge badge-info">💻 <?= e(t('restaurant.source_staff')) ?></span><?php endif; ?>
                            </td>
                            <td class="fw-700"><?= e(formatMoney((float)$o['total_amount'])) ?></td>
                            <td><?= statusBadge((string)$o['status']) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($o['ordered_at'])) ?></td>
                            <td class="actions">
                                <a href="/orders/<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline">👁 <?= e(t('common.view')) ?></a>
                                <a href="/orders/<?= (int)$o['id'] ?>/receipt" class="btn btn-sm btn-outline">🧾</a>
                                <?php if (in_array((string)$o['status'], ['pending', 'confirmed', 'preparing', 'ready'], true)): ?>
                                    <select class="form-select order-status" data-id="<?= (int)$o['id'] ?>" style="width:auto;">
                                        <option value=""><?= e(t('common.update') )?></option>
                                        <?php foreach ($statuses as $s): ?>
                                            <option value="<?= e($s) ?>"<?= $s === (string)$o['status'] ? ' selected' : '' ?>><?= e(t('customer.status_' . $s) === 'customer.status_' . $s ? ucfirst(str_replace('_', ' ', $s)) : t('customer.status_' . $s)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
document.querySelectorAll('.order-status').forEach(function (sel) {
    sel.addEventListener('change', function () {
        if (!sel.value) { return; }
        ISHEMA.api('/orders/' + sel.dataset.id + '/update-status', {status: sel.value}, 'POST')
            .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); sel.value = ''; } })
            .catch(function () { alert('Error'); sel.value = ''; });
    });
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
