<?php
/** @var array $tutorials @var string $category @var string $search */
$pageTitle = t('public.tutorials_title') ?? 'Tutorials';
$navActive = 'tutorials';
ob_start();
?>
<section class="section" style="padding-top:50px;">
    <div class="inner">
        <div class="section-head">
            <h2><?= e(t('public.tutorials_title')) ?></h2>
            <p><?= e(t('public.tutorials_subtitle')) ?></p>
        </div>
        <div style="max-width:560px;margin:0 auto 30px;">
            <form method="GET" action="/tutorials" class="d-flex gap-2">
                <input type="search" name="search" class="form-control" placeholder="<?= e(t('public.tutorials_search')) ?>" value="<?= e($search) ?>">
                <button class="btn btn-primary" type="submit">🔍</button>
            </form>
        </div>
        <div style="text-align:center;margin-bottom:30px;">
            <a href="/tutorials" class="cat-chip<?= $category === 'all' ? ' active' : '' ?>"><?= e(t('public.tutorial_category_all')) ?></a>
            <a href="/tutorials?category=hotel" class="cat-chip<?= $category === 'hotel' ? ' active' : '' ?>"><?= e(t('public.tutorial_category_hotel')) ?></a>
            <a href="/tutorials?category=restaurant" class="cat-chip<?= $category === 'restaurant' ? ' active' : '' ?>"><?= e(t('public.tutorial_category_restaurant')) ?></a>
            <a href="/tutorials?category=owner" class="cat-chip<?= $category === 'owner' ? ' active' : '' ?>"><?= e(t('public.tutorial_category_owner')) ?></a>
            <a href="/tutorials?category=employee" class="cat-chip<?= $category === 'employee' ? ' active' : '' ?>"><?= e(t('public.tutorial_category_employee')) ?></a>
        </div>
        <?php if (empty($tutorials)): ?>
            <div class="empty-state"><div class="es-icon">🎥</div><div class="es-title"><?= e(t('common.no_results')) ?></div></div>
        <?php else: ?>
        <div class="feature-grid">
            <?php foreach ($tutorials as $tut): ?>
                <div class="feature-card">
                    <div style="background:var(--light-blue);border-radius:10px;height:150px;display:flex;align-items:center;justify-content:center;font-size:44px;margin-bottom:14px;">▶️</div>
                    <h3><?= e($tut['title']) ?></h3>
                    <p><?= e(mb_substr((string)$tut['description'], 0, 120)) ?><?= mb_strlen((string)$tut['description']) > 120 ? '…' : '' ?></p>
                    <div class="d-flex justify-between items-center mt-2">
                        <span class="badge bg-info"><?= e(t('public.tutorial_category_' . $tut['category'])) ?></span>
                        <?php if ($tut['duration']): ?><span class="text-muted text-xs">⏱ <?= e($tut['duration']) ?></span><?php endif; ?>
                    </div>
                    <?php if (!empty($tut['video_url'])): ?>
                        <a href="<?= e($tut['video_url']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm mt-3">▶ <?= e(t('common.watch') ?? 'Watch') ?></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
