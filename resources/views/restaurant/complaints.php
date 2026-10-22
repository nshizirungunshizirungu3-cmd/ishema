<?php
/** @var array $complaints */
$pageTitle = t('customer.complaint_open');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">⚠️ <?= e(t('customer.complaint_open')) ?> (<?= count($complaints) ?>)</div>
    </div>
    <?php if (empty($complaints)): ?>
        <div class="empty-state"><div class="es-icon">✅</div><div class="es-title"><?= e(t('common.no_records')) ?></div><p><?= e(t('common.all') === 'All' ? 'All deliveries confirmed.' : 'All deliveries confirmed.') ?></p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('restaurant.order_number')) ?></th>
                    <th><?= e(t('restaurant.table_label')) ?></th>
                    <th><?= e(t('restaurant.product_name')) ?></th>
                    <th><?= e(t('customer.ordered_quantity_label')) ?></th>
                    <th><?= e(t('customer.received_quantity')) ?></th>
                    <th><?= e(t('customer.missing_items')) ?></th>
                    <th><?= e(t('customer.confirm_received')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($complaints as $c): ?>
                        <?php $missing = max(0, (float)$c['quantity'] - (float)$c['quantity_received']); ?>
                        <tr>
                            <td class="mono fw-700"><?= e($c['order_number']) ?></td>
                            <td><?= $c['table_number'] ? '#' . e($c['table_number']) : '—' ?></td>
                            <td class="fw-700"><?= e($c['product_name']) ?></td>
                            <td><?= (float)$c['quantity'] ?></td>
                            <td><?= (float)$c['quantity_received'] ?></td>
                            <td><span class="badge badge-danger">−<?= $missing ?></span></td>
                            <td>
                                <?php if ((string)$c['status'] === 'partial'): ?>
                                    <span class="badge badge-warning">● <?= e(t('customer.status_partially_delivered')) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-inactive">● <?= e(t('customer.status_not_received')) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="alert alert-info text-sm">💡 <?= e(t('customer.missing_items_notified')) ?></div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
