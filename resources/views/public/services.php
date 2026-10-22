<?php
$pageTitle = t('common.services');
$navActive = 'services';
ob_start();
?>
<section class="section" style="padding-top:50px;">
    <div class="inner">
        <div class="section-head">
            <h2><?= e(t('public.services_title')) ?></h2>
            <p><?= e(t('public.services_subtitle')) ?></p>
        </div>
        <div class="feature-grid">
            <div class="feature-card"><div class="f-icon">🏨</div><h3><?= e(t('public.feature_hotel')) ?></h3><p><?= e(t('public.feature_hotel_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">🍽️</div><h3><?= e(t('public.feature_restaurant')) ?></h3><p><?= e(t('public.feature_restaurant_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">📱</div><h3>QR <?= e(t('common.ordering') ?? 'Ordering') ?></h3><p><?= e(t('public.benefit_1_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">📅</div><h3><?= e(t('hotel.booking_management')) ?></h3><p><?= e(t('public.benefit_2_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">📦</div><h3><?= e(t('public.feature_inventory')) ?></h3><p><?= e(t('public.feature_inventory_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">👥</div><h3><?= e(t('public.feature_staff')) ?></h3><p><?= e(t('public.feature_staff_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">💳</div><h3><?= e(t('public.feature_payment')) ?></h3><p><?= e(t('public.feature_payment_desc')) ?></p></div>
            <div class="feature-card"><div class="f-icon">📈</div><h3><?= e(t('public.feature_reports')) ?></h3><p><?= e(t('public.feature_reports_desc')) ?></p></div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
