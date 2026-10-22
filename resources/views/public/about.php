<?php
$pageTitle = t('footer_about');
$navActive = 'about';
ob_start();
?>
<section class="section" style="padding-top:50px;">
    <div class="inner">
        <div class="section-head">
            <h2><?= e(t('public.about_title')) ?></h2>
        </div>
        <div class="card" style="max-width:860px;margin:0 auto;">
            <div class="form-row" style="margin-bottom:26px;">
                <div class="info-panel">
                    <h3 style="margin:0 0 8px;color:var(--primary);">🎯 <?= e(t('public.about_mission_title')) ?></h3>
                    <p style="margin:0;"><?= e(t('public.about_mission')) ?></p>
                </div>
                <div class="info-panel" style="background:#E5FBF4;border-color:#B8F0DA;">
                    <h3 style="margin:0 0 8px;color:var(--emerald-dark);">🔮 <?= e(t('public.about_vision_title')) ?></h3>
                    <p style="margin:0;"><?= e(t('public.about_vision')) ?></p>
                </div>
            </div>
            <h3 class="mb-3">📌 <?= e(t('public.about_objectives_title')) ?></h3>
            <div class="feature-grid">
                <div class="feature-card"><h3>1. <?= e(t('public.about_objective_1')) ?></h3></div>
                <div class="feature-card"><h3>2. <?= e(t('public.about_objective_2')) ?></h3></div>
                <div class="feature-card"><h3>3. <?= e(t('public.about_objective_3')) ?></h3></div>
                <div class="feature-card"><h3>4. <?= e(t('public.about_objective_4')) ?></h3></div>
            </div>
            <h3 class="mt-4 mb-3">⚙️ <?= e(t('public.about_capabilities_title')) ?></h3>
            <div class="feature-grid">
                <div class="feature-card"><div class="f-icon">🔀</div><h3><?= e(t('common.multi_tenant') ?? 'Multi-tenant') ?></h3><p><?= e(t('public.benefit_6_desc')) ?></p></div>
                <div class="feature-card"><div class="f-icon">🌍</div><h3><?= e(t('public.benefit_4_title')) ?></h3><p><?= e(t('public.benefit_4_desc')) ?></p></div>
                <div class="feature-card"><div class="f-icon">🔒</div><h3><?= e(t('common.security') ?? 'Security') ?></h3><p>RBAC, CSRF, XSS, SQL injection protection, audit logs.</p></div>
                <div class="feature-card"><div class="f-icon">📱</div><h3><?= e(t('common.responsive') ?? 'Responsive') ?></h3><p><?= e(t('public.feature_reports_desc')) ?></p></div>
            </div>
        </div>
    </div>
</section>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
