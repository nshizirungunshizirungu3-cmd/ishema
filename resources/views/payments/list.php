<?php
/** @var array $payments @var array $stats @var string $status @var string $method */
$pageTitle = t('common.payments');
ob_start();
$methods = ['mtn', 'airtel', 'bank', 'visa', 'cash'];
$statuses = ['success', 'pending', 'failed', 'refunded'];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="stat-grid">
    <div class="stat-card"><div class="s-icon green">💰</div><div><div class="s-value"><?= e(formatMoney((float)$stats['total'])) ?></div><div class="s-label"><?= e(t('common.revenue')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon orange">⏳</div><div><div class="s-value"><?= e(formatMoney((float)$stats['pending'])) ?></div><div class="s-label"><?= e(t('common.pending')) ?></div></div></div>
    <div class="stat-card"><div class="s-icon red">↩️</div><div><div class="s-value"><?= e(formatMoney((float)$stats['refunded'])) ?></div><div class="s-label"><?= e(t('payments.status_refunded')) ?></div></div></div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">💳 <?= e(t('common.payments')) ?></div>
        <a href="/payments/pay" class="btn btn-primary btn-sm" style="margin-left:auto;">＋ <?= e(t('payments.pay_amount')) ?></a>
    </div>
    <form method="GET" action="/payments" class="d-flex gap-2 mb-3">
        <select name="status" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('common.status')) ?>es</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(t('payments.status_' . $s)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="method" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('payments.payment_method')) ?>s</option>
            <?php foreach ($methods as $m): ?>
                <option value="<?= e($m) ?>"<?= $method === $m ? ' selected' : '' ?>><?= e(t('payments.method_' . $m)) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.filter')) ?></button>
    </form>
    <?php if (empty($payments)): ?>
        <div class="empty-state"><div class="es-icon">💳</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('payments.transaction_reference')) ?></th>
                    <th><?= e(t('payments.payment_for')) ?></th>
                    <th><?= e(t('payments.payment_method')) ?></th>
                    <th><?= e(t('common.amount')) ?></th>
                    <th><?= e(t('payments.payer_name')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('common.created_at')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td class="mono fw-700"><?= e($p['reference']) ?></td>
                            <td>
                                <?= e(t('common.' . ((string)$p['payable_type'] === 'booking' ? 'booking' : ((string)$p['payable_type'] === 'subscription' ? 'subscription' : 'order')))) ?>
                                <span class="mono text-muted">#<?= (int)$p['payable_id'] ?></span>
                            </td>
                            <td><?= e(t('payments.method_' . (string)$p['method'])) ?></td>
                            <td class="fw-700"><?= e(formatMoney((float)$p['amount'], (string)$p['currency'])) ?></td>
                            <td class="text-muted"><?= e((string)($p['payer_name'] ?? '—')) ?></td>
                            <td><?= statusBadge((string)$p['status']) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($p['created_at'])) ?></td>
                            <td class="actions">
                                <a href="/payments/<?= (int)$p['id'] ?>/receipt" class="btn btn-sm btn-outline">🧾 <?= e(t('common.receipt')) ?></a>
                                <?php if ((string)$p['status'] === 'success'): ?>
                                    <button class="btn btn-sm btn-outline" onclick="ISHEMA.confirmDelete('/payments/<?= (int)$p['id'] ?>/refund', '<?= e(t('common.delete_confirm')) ?>')">↩️ <?= e(t('payments.status_refunded')) ?></button>
                                <?php endif; ?>
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
