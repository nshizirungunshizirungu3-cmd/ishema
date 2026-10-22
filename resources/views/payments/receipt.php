<?php
/** @var array $payment @var array $business @var array $transactions */
ob_start();
?>
<div style="max-width:480px;margin:0 auto;font-family:monospace;">
    <div style="text-align:center;border-bottom:2px dashed #333;padding-bottom:10px;margin-bottom:10px;">
        <div style="font-size:20px;font-weight:bold;"><?= e($business['name']) ?></div>
        <div style="font-size:12px;"><?= e($business['address'] ?? '') ?><?= $business['phone'] ? ' · ' . e($business['phone']) : '' ?></div>
        <div style="margin-top:6px;font-weight:bold;"><?= e(t('payments.payment_receipt') ?? 'PAYMENT RECEIPT') ?></div>
        <div class="mono"><?= e($payment['reference']) ?></div>
        <div style="font-size:12px;"><?= e(formatDate($payment['paid_at'] ?? $payment['created_at'])) ?></div>
    </div>
    <div style="font-size:13px;margin-bottom:8px;">
        🧾 <?= e(t('payments.for') ?? 'For') ?>: <?= e($payment['payable_type']) ?> #<?= (int)$payment['payable_id'] ?><br>
        💳 <?= e(t('payments.method') ?? 'Method') ?>: <?= e($payment['method']) ?><?= $payment['provider'] ? ' (' . e($payment['provider']) . ')' : '' ?><br>
        👤 <?= e($payment['payer_name'] ?? '—') ?><?= $payment['payer_phone'] ? ' · ' . e($payment['payer_phone']) : '' ?><br>
        📎 <?= e(t('payments.provider_reference') ?? 'Provider reference') ?>: <?= e($payment['provider_reference'] ?? '—') ?>
    </div>
    <div style="border-top:1px dashed #333;border-bottom:1px dashed #333;padding:8px 0;margin-bottom:8px;">
        <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:bold;">
            <span><?= e(t('common.amount') ?? 'Amount') ?>:</span>
            <span><?= e(formatMoney((float)$payment['amount'], (string)$payment['currency'])) ?></span>
        </div>
        <div style="text-align:right;font-size:13px;">
            <?= e(t('payments.status') ?? 'Status') ?>: <?= e(strtoupper((string)$payment['status'])) ?>
        </div>
    </div>
    <?php if (!empty($transactions)): ?>
        <div style="font-size:11px;margin-bottom:8px;">
            <div style="font-weight:bold;"><?= e(t('payments.transaction_log') ?? 'Transaction log') ?>:</div>
            <?php foreach ($transactions as $tx): ?>
                <div>· <?= e(formatDate($tx['created_at'])) ?> [<?= e($tx['provider'] ?? '') ?>] <?= e($tx['status'] ?? '') ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div style="text-align:center;margin-top:12px;font-size:12px;">
        — <?= e(t('payments.thank_you') ?? 'Thank you for your payment') ?> —<br>
        <?= e($business['name']) ?>
    </div>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
