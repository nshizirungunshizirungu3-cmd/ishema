<?php
/** @var array|null $business @var array $packages @var array $owners @var array|null $ownerLink @var array|null $subscription */
$pageTitle = $business ? t('admin.edit_business') : t('admin.create_business');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>
<?php if ($pw = \App\Core\Session::flash('generated_password')): ?>
    <div class="alert alert-info">🔑 <?= e(t('admin.generated_password')) ?>: <strong class="mono"><?= e($pw) ?></strong> (<?= e(\App\Core\Session::flash('owner_email') ?? '') ?>)</div>
<?php endif; ?>

<div class="card" style="max-width:860px;">
    <div class="card-header">
        <div class="card-title">🏢 <?= $business ? e(t('admin.edit_business')) : e(t('admin.create_business')) ?></div>
        <?php if ($business): ?>
            <span class="mono text-muted" style="margin-left:12px;"><?= e($business['business_id']) ?></span>
        <?php endif; ?>
    </div>
    <form method="POST" action="<?= $business ? url('/admin/businesses/' . $business['id'] . '/update') : url('/admin/businesses/create') ?>">
        <?= \App\Core\CSRF::field() ?>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.name')) ?> <span class="req">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= e($business['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.business_type')) ?> <span class="req">*</span></label>
                <select name="type" class="form-select">
                    <option value="HOTEL"<?= ($business['type'] ?? '') === 'HOTEL' ? ' selected' : '' ?>><?= e(t('admin.business_type_hotel')) ?></option>
                    <option value="RESTAURANT"<?= ($business['type'] ?? '') === 'RESTAURANT' ? ' selected' : '' ?>><?= e(t('admin.business_type_restaurant')) ?></option>
                    <option value="HOTEL_AND_RESTAURANT"<?= ($business['type'] ?? '') === 'HOTEL_AND_RESTAURANT' ? ' selected' : '' ?>><?= e(t('admin.business_type_both')) ?></option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.email')) ?></label>
                <input type="email" name="email" class="form-control" value="<?= e($business['email'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.phone')) ?></label>
                <input type="tel" name="phone" class="form-control" value="<?= e($business['phone'] ?? '') ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.address')) ?></label>
                <input type="text" name="address" class="form-control" value="<?= e($business['address'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.city')) ?> / <?= e(t('hotel.country')) ?></label>
                <div class="d-flex gap-2">
                    <input type="text" name="city" class="form-control" placeholder="<?= e(t('common.city')) ?>" value="<?= e($business['city'] ?? '') ?>">
                    <input type="text" name="country" class="form-control" placeholder="<?= e(t('common.country')) ?>" value="<?= e($business['country'] ?? '') ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('common.description')) ?></label>
            <textarea name="description" class="form-control" rows="3"><?= e($business['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('admin.business_status')) ?></label>
            <select name="status" class="form-select">
                <option value="active"<?= ($business['status'] ?? 'active') === 'active' ? ' selected' : '' ?>><?= e(t('admin.status_active')) ?></option>
                <option value="pending"<?= ($business['status'] ?? '') === 'pending' ? ' selected' : '' ?>><?= e(t('admin.status_pending')) ?></option>
                <option value="suspended"<?= ($business['status'] ?? '') === 'suspended' ? ' selected' : '' ?>><?= e(t('admin.status_suspended')) ?></option>
            </select>
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:22px 0;">
        <h3 class="mb-3">👤 <?= e(t('admin.assign_owner')) ?></h3>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label"><?= e(t('common.select')) ?> <?= e(t('admin.owner')) ?></label>
                <select name="owner_user_id" class="form-select">
                    <option value="0">—</option>
                    <?php foreach ($owners as $o): ?>
                        <option value="<?= (int)$o['id'] ?>"<?= ($ownerLink && (int)$ownerLink['user_id'] === (int)$o['id']) ? ' selected' : '' ?>>
                            <?= e($o['name']) ?> (<?= e($o['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.or')) ?>: <?= e(t('admin.create_owner')) ?></label>
                <div class="form-row">
                    <input type="text" name="owner_name" class="form-control" placeholder="<?= e(t('common.full_name')) ?>">
                    <input type="email" name="owner_email" class="form-control" placeholder="<?= e(t('common.email')) ?>">
                </div>
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('common.phone')) ?> (<?= e(t('admin.owner')) ?>)</label>
            <input type="tel" name="owner_phone" class="form-control" style="max-width:300px;">
        </div>

        <hr style="border:none;border-top:1px solid var(--border);margin:22px 0;">
        <h3 class="mb-3">📋 <?= e(t('admin.assign_subscription')) ?></h3>
        <div class="form-row-3">
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.subscription_package')) ?></label>
                <select name="package_id" class="form-select">
                    <option value="0">—</option>
                    <?php foreach ($packages as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"<?= ($subscription && (int)$subscription['package_id'] === (int)$p['id']) ? ' selected' : '' ?>>
                            <?= e($p['name']) ?> (<?= e(formatMoney((float)$p['price_monthly'])) ?>/<?= e(t('public.per_month')) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.subscription_start')) ?></label>
                <input type="datetime-local" name="starts_at" class="form-control" value="<?= e($subscription ? date('Y-m-d\TH:i', strtotime((string)$subscription['starts_at'])) : date('Y-m-d\TH:i')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('admin.subscription_expiry')) ?></label>
                <input type="datetime-local" name="expires_at" class="form-control" value="<?= e($subscription ? date('Y-m-d\TH:i', strtotime((string)$subscription['expires_at'])) : date('Y-m-d\TH:i', strtotime('+1 month'))) ?>">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label"><?= e(t('admin.billing_cycle')) ?></label>
            <select name="billing_cycle" class="form-select" style="max-width:220px;">
                <option value="monthly"<?= ($subscription['billing_cycle'] ?? 'monthly') === 'monthly' ? ' selected' : '' ?>><?= e(t('public.billing_monthly')) ?></option>
                <option value="yearly"<?= ($subscription['billing_cycle'] ?? '') === 'yearly' ? ' selected' : '' ?>><?= e(t('public.billing_yearly')) ?></option>
            </select>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            <a href="/admin/businesses" class="btn btn-outline"><?= e(t('common.cancel')) ?></a>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
