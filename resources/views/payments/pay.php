<?php
/** @var array $business @var string $payableType @var int $payableId @var float $amount @var string $title @var array|null $pending */
$pageTitle = t('payments.pay') ?? 'Make payment';
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div style="max-width:720px;margin:40px auto;">
    <div class="card">
        <div class="card-header"><div class="card-title">💳 <?= e(t('payments.pay') ?? 'Make payment') ?></div></div>
        <div class="card-body">
            <div class="d-flex justify-between items-center mb-3" style="padding:14px;background:var(--bg-soft);border-radius:12px;">
                <div>
                    <div class="fw-700"><?= e($title) ?></div>
                    <div class="text-muted text-sm"><?= e($business['name']) ?></div>
                </div>
                <div class="s-value"><?= e(formatMoney($amount)) ?></div>
            </div>

            <?php if ($pending): ?>
                <div class="alert alert-warning">
                    ⏳ <?= e(t('payments.pending_exists') ?? 'A pending payment exists') ?>:
                    <span class="mono"><?= e($pending['reference']) ?></span>
                    (<?= e($pending['method']) ?>)
                </div>
            <?php endif; ?>

            <h3 class="mb-3">📱 <?= e(t('payments.mobile_money') ?? 'Mobile money') ?></h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('payments.method') ?? 'Method') ?></label>
                    <select id="pay-method" class="form-select">
                        <option value="mtn_momo">🟡 MTN MoMo</option>
                        <option value="airtel_money">🔴 Airtel Money</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">📞 <?= e(t('payments.payer_phone') ?? 'Payer phone') ?></label>
                    <input type="tel" name="payer_phone" id="payer-phone" class="form-control" placeholder="+250...">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">👤 <?= e(t('payments.payer_name') ?? 'Payer name') ?></label>
                <input type="text" name="payer_name" id="payer-name" class="form-control">
            </div>
            <button class="btn btn-primary" id="initiate-pay" style="width:100%;">
                📲 <?= e(t('payments.initiate') ?? 'Initiate payment') ?>
            </button>

            <hr style="border:none;border-top:1px solid var(--border);margin:24px 0;">
            <h3 class="mb-3">🏦 <?= e(t('payments.other_methods') ?? 'Other methods') ?></h3>
            <div class="d-flex gap-2">
                <button class="btn btn-outline" data-method="bank_transfer" style="flex:1;">🏦 <?= e(t('payments.bank_transfer') ?? 'Bank transfer') ?></button>
                <button class="btn btn-outline" data-method="visa_card" style="flex:1;">💳 <?= e(t('payments.visa_card') ?? 'Visa card') ?></button>
                <button class="btn btn-outline" data-method="cash" style="flex:1;">💵 <?= e(t('payments.cash') ?? 'Cash') ?></button>
            </div>

            <div id="payment-result" style="display:none;" class="mt-3">
                <div class="alert alert-info">
                    <div class="fw-700">🧾 <?= e(t('payments.reference') ?? 'Reference') ?>: <span class="mono" id="result-ref"></span></div>
                    <div class="text-sm" id="result-message"></div>
                    <div class="mt-2">
                        <input type="text" id="verify-ref" class="form-control" placeholder="<?= e(t('payments.enter_provider_ref') ?? 'Enter provider transaction reference to verify') ?>">
                        <button class="btn btn-outline btn-sm mt-2" id="verify-btn">🔍 <?= e(t('payments.verify') ?? 'Verify payment') ?></button>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <a href="<?= e(url('/payments')) ?>">📋 <?= e(t('payments.history') ?? 'Payment history') ?></a>
            </div>
        </div>
    </div>
</div>
<script>
var PAYABLE = {type: <?= json_encode($payableType) ?>, id: <?= (int)$payableId ?>};
var AMOUNT = <?= json_encode($amount) ?>;

function initiatePayment(method) {
    var phone = document.getElementById('payer-phone') ? document.getElementById('payer-phone').value : '';
    var name = document.getElementById('payer-name') ? document.getElementById('payer-name').value : '';
    var btn = document.getElementById('initiate-pay');
    if (btn) { btn.disabled = true; btn.textContent = '⏳ <?= e(t('payments.processing') ?? 'Processing...') ?>'; }
    ISHEMA.api('/payments/initiate', {
        payable_type: PAYABLE.type,
        payable_id: PAYABLE.id,
        method: method,
        payer_phone: phone,
        payer_name: name
    }, 'POST').then(function (r) {
        if (btn) { btn.disabled = false; btn.textContent = '📲 <?= e(t('payments.initiate') ?? 'Initiate payment') ?>'; }
        if (!r.ok) { alert(r.error || 'Error'); return; }
        var result = document.getElementById('payment-result');
        var refEl = document.getElementById('result-ref');
        var msgEl = document.getElementById('result-message');
        var verifyRef = document.getElementById('verify-ref');
        if (result && refEl && msgEl) {
            result.style.display = 'block';
            refEl.textContent = (r.payment && r.payment.reference) || '';
            msgEl.textContent = r.message || '';
            if (verifyRef) { verifyRef.value = (r.payment && r.payment.reference) || ''; }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('initiate-pay');
    if (btn) {
        btn.addEventListener('click', function () {
            var sel = document.getElementById('pay-method');
            initiatePayment(sel ? sel.value : 'mtn_momo');
        });
    }
    document.querySelectorAll('[data-method]').forEach(function (b) {
        b.addEventListener('click', function () { initiatePayment(b.dataset.method); });
    });
    var verifyBtn = document.getElementById('verify-btn');
    if (verifyBtn) {
        verifyBtn.addEventListener('click', function () {
            var input = document.getElementById('verify-ref');
            var ref = input ? input.value.trim() : '';
            if (!ref) { alert('<?= e(t('payments.enter_reference') ?? 'Enter a reference') ?>'); return; }
            ISHEMA.api('/payments/verify', {reference: ref}, 'POST').then(function (r) {
                if (r.ok) {
                    ISHEMA.toast('✅ ' + (r.message || '<?= e(t('payments.payment_success') ?? 'Payment successful') ?>'));
                    setTimeout(function () { location.reload(); }, 800);
                } else {
                    alert(r.error || 'Error');
                }
            });
        });
    }
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
