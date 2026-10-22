<?php
/** @var array $services */
$pageTitle = t('hotel.my_requests') ?? 'My service requests';
ob_start();
?>
<div style="max-width:860px;margin:40px auto;">
    <h2 class="mb-3">🛎️ <?= e(t('hotel.my_requests') ?? 'My service requests') ?></h2>
    <?php if (empty($services)): ?>
        <div class="empty-state"><div class="es-icon">🛎️</div><div class="es-title"><?= e(t('common.no_records') ?? 'No requests yet') ?></div></div>
    <?php else: ?>
        <?php foreach ($services as $svc): ?>
            <div class="card mb-2">
                <div class="card-body d-flex justify-between items-center" style="flex-wrap:wrap;gap:10px;">
                    <div>
                        <div class="fw-700"><?= e($svc['title']) ?></div>
                        <div class="text-muted text-sm">
                            🏢 <?= e($svc['business_name'] ?? '—') ?>
                            · 📅 <?= e(formatDate($svc['requested_at'])) ?>
                            <?= $svc['description'] ? ' · 📝 ' . e($svc['description']) : '' ?>
                        </div>
                    </div>
                    <div class="d-flex items-center gap-2">
                        <span class="badge badge-info"><?= e($svc['service_type']) ?></span>
                        <?= statusBadge((string)$svc['status']) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
