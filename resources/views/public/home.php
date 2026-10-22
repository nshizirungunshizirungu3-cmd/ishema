<?php
/** @var array $stats */
$pageTitle = t('common.home');
ob_start();
?>
<section class="hero">
    <div class="inner">
        <h1><?= e(t('public.hero_title')) ?></h1>
        <p><?= e(t('public.hero_subtitle')) ?></p>
        <div class="hero-cta">
            <a href="/contact" class="btn btn-light btn-lg" data-open-demo>📋 <?= e(t('public.hero_cta_demo')) ?></a>
            <a href="/packages" class="btn btn-outline-light btn-lg"><?= e(t('public.hero_cta_start')) ?> →</a>
        </div>
        <div class="hero-stats">
            <div><div class="hs-value"><?= e(number_format($stats['businesses'])) ?></div><div class="hs-label"><?= e(t('public.hero_stats_businesses')) ?></div></div>
            <div><div class="hs-value"><?= e(number_format($stats['orders'])) ?></div><div class="hs-label"><?= e(t('public.hero_stats_orders')) ?></div></div>
            <div><div class="hs-value"><?= e($stats['uptime']) ?></div><div class="hs-label"><?= e(t('public.hero_stats_uptime')) ?></div></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="inner">
        <div class="section-head">
            <h2><?= e(t('public.benefits_title')) ?></h2>
            <p><?= e(t('public.benefits_subtitle')) ?></p>
        </div>
        <div class="feature-grid">
            <div class="feature-card"><div class="f-icon">⚡</div><h3><?= e(t('public.benefit_1_title')) ?></h3><p><?= e(t('public.benefit_1_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">🏨</div><h3><?= e(t('public.benefit_2_title')) ?></h3><p><?= e(t('public.benefit_2_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">💳</div><h3><?= e(t('public.benefit_3_title')) ?></h3><p><?= e(t('public.benefit_3_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">🌍</div><h3><?= e(t('public.benefit_4_title')) ?></h3><p><?= e(t('public.benefit_4_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">📊</div><h3><?= e(t('public.benefit_5_title')) ?></h3><p><?= e(t('public.benefit_5_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">🔐</div><h3><?= e(t('public.benefit_6_title')) ?></h3><p><?= e(t('public.benefit_6_desc')) ?></p></div>
        </div>
    </div>
</section>

<section class="section alt">
    <div class="inner">
        <div class="section-head">
            <h2><?= e(t('public.features_title')) ?></h2>
            <p><?= e(t('public.features_subtitle')) ?></p>
        </div>
        <div class="feature-grid">
            <div class="feature-card"><div class="f-icon">🍽️</div><h3><?= e(t('public.feature_restaurant')) ?></h3><p><?= e(t('public.feature_restaurant_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">🏨</div><h3><?= e(t('public.feature_hotel')) ?></h3><p><?= e(t('public.feature_hotel_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">📦</div><h3><?= e(t('public.feature_inventory')) ?></h3><p><?= e(t('public.feature_inventory_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">👥</div><h3><?= e(t('public.feature_staff')) ?></h3><p><?= e(t('public.feature_staff_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">💳</div><h3><?= e(t('public.feature_payment')) ?></h3><p><?= e(t('public.feature_payment_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">📈</div><h3><?= e(t('public.feature_reports')) ?></h3><p><?= e(t('public.feature_reports_desc')) ?></p></div>
        </div>
    </div>
</section>

<section class="section" style="background:linear-gradient(135deg,var(--light-blue),#fff);">
    <div class="inner" style="text-align:center;">
        <h2 style="font-size:30px;font-weight:800;color:var(--navy);margin:0 0 14px;"><?= e(t('public.hero_title')) ?></h2>
        <p style="color:var(--gray);max-width:520px;margin:0 auto 30px;"><?= e(t('public.hero_subtitle')) ?></p>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;">
            <a href="/contact" class="btn btn-primary btn-lg" data-open-demo><?= e(t('public.hero_cta_demo')) ?></a>
            <a href="/packages" class="btn btn-outline btn-lg"><?= e(t('public.hero_cta_start')) ?></a>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
