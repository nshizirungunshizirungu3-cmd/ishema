<?php
/** @var array $packages @var string $billing */
$pageTitle = t('common.packages') ?? 'Packages';
$navActive = 'packages';
ob_start();
?>
<section class="section" style="padding-top:50px;">
    <div class="inner">
        <div class="section-head">
            <h2><?= e(t('public.packages_title')) ?></h2>
            <p><?= e(t('public.packages_subtitle')) ?></p>
        </div>
        <div style="text-align:center;margin-bottom:34px;">
            <div class="lang-select" style="display:inline-flex;">
                <a href="?billing=monthly" class="btn btn-sm <?= $billing === 'monthly' ? 'btn-primary' : 'btn-outline' ?>"><?= e(t('public.billing_monthly')) ?></a>
                <a href="?billing=yearly" class="btn btn-sm <?= $billing === 'yearly' ? 'btn-primary' : 'btn-outline' ?>"><?= e(t('public.billing_yearly')) ?></a>
            </div>
        </div>
        <?php if (empty($packages)): ?>
            <div class="empty-state"><div class="es-icon">📦</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:26px;align-items:start;">
            <?php $i = 0; foreach ($packages as $package): $i++;
                $price = $billing === 'yearly' ? (float)$package['price_yearly'] : (float)$package['price_monthly'];
                $features = json_decode((string)$package['features'], true) ?: [];
                $modules = json_decode((string)$package['modules'], true) ?: [];
                $popular = (int)$package['is_default'] === 1 || $i === 2;
            ?>
            <div class="package-card<?= $popular ? ' popular' : '' ?>">
                <?php if ($popular): ?><div class="popular-tag"><?= e(t('public.package_popular')) ?></div><?php endif; ?>
                <div class="p-name"><?= e($package['name']) ?></div>
                <div class="p-price"><?= e(number_format($price, 0)) ?> <span><?= e($billing === 'yearly' ? t('public.per_year') : t('public.per_month')) ?></span></div>
                <div class="p-desc">
                    <?= e(t('public.package_employees')) ?>: <?= (int)$package['max_employees'] ?> ·
                    <?= e(t('public.package_tables')) ?>: <?= (int)$package['max_tables'] ?> ·
                    <?= e(t('public.package_products')) ?>: <?= (int)$package['max_products'] ?>
                </div>
                <ul class="p-features">
                    <?php if ((int)$package['max_rooms'] > 0): ?>
                        <li><?= e(t('public.package_rooms')) ?>: <?= (int)$package['max_rooms'] ?></li>
                    <?php endif; ?>
                    <?php if ((int)$package['max_branches'] > 1): ?>
                        <li><?= e(t('public.package_branches')) ?>: <?= (int)$package['max_branches'] ?></li>
                    <?php endif; ?>
                    <?php if ($package['has_reports']): ?>
                        <li><?= e(t('public.package_reports')) ?></li>
                    <?php endif; ?>
                    <?php foreach ($features as $f): ?>
                        <li><?= e($f) ?></li>
                    <?php endforeach; ?>
                    <li><?= e(t('public.package_support')) ?>: <?= e(t('common.' . $package['support_level'] ?? $package['support_level'])) ?></li>
                </ul>
                <a href="/contact" class="btn btn-<?= $popular ? 'primary' : 'outline' ?> btn-block" data-open-demo><?= e(t('public.choose_package')) ?></a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
