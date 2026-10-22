<?php
/** @var array $order @var array $business @var array|null $table @var array $items */
ob_start();
?>
<div style="max-width:420px;margin:0 auto;font-family:monospace;">
    <div style="text-align:center;border-bottom:2px dashed #333;padding-bottom:10px;margin-bottom:10px;">
        <div style="font-size:20px;font-weight:bold;"><?= e($business['name']) ?></div>
        <div style="font-size:12px;"><?= e($business['address'] ?? '') ?><?= $business['phone'] ? ' · ' . e($business['phone']) : '' ?></div>
        <div style="margin-top:6px;font-weight:bold;"><?= e(t('customer.order_receipt') ?? 'ORDER RECEIPT') ?></div>
        <div>#<?= e($order['order_number']) ?></div>
        <div style="font-size:12px;"><?= e(formatDate($order['ordered_at'])) ?></div>
    </div>
    <div style="font-size:13px;margin-bottom:6px;">
        <?php if ($table): ?>🍽️ <?= e(t('common.table') ?? 'Table') ?>: #<?= e($table['table_number']) ?><br><?php endif; ?>
        <?php if (!empty($order['guest_name'])): ?>👤 <?= e($order['guest_name']) ?><br><?php endif; ?>
        <?php if (!empty($order['guest_phone'])): ?>📞 <?= e($order['guest_phone']) ?><br><?php endif; ?>
        📡 <?= e(t('restaurant.source_' . (string)$order['source']) ?? $order['source']) ?>
    </div>
    <div style="border-top:1px dashed #333;border-bottom:1px dashed #333;padding:6px 0;margin-bottom:8px;">
        <?php foreach ($items as $item): ?>
            <div style="display:flex;justify-content:space-between;font-size:13px;">
                <span><?= e((string)$item['quantity']) ?> × <?= e($item['product_name']) ?></span>
                <span><?= e(number_format((float)$item['line_total'], 2)) ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:16px;font-weight:bold;">
        <span><?= e(t('common.total') ?? 'Total') ?>:</span>
        <span><?= e(formatMoney((float)$order['total_amount'])) ?></span>
    </div>
    <div style="text-align:center;margin-top:14px;font-size:12px;">
        — <?= e(t('customer.thank_you') ?? 'Thank you for your order!') ?> —<br>
        <?= e($business['name']) ?>
    </div>
</div>
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
