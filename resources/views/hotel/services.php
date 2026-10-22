<?php
/** @var array $services @var array $rooms @var array $guests */
$pageTitle = t('hotel.hotel_services');
ob_start();
$serviceTypes = ['room_service', 'restaurant_order', 'laundry', 'transport', 'other'];
$guestMap = [];
foreach ($guests as $g) { $guestMap[(int)$g['id']] = trim((string)($g['first_name'] ?? '') . ' ' . (string)($g['last_name'] ?? '')); }
$roomMap = [];
foreach ($rooms as $r) { $roomMap[(int)$r['id']] = (string)$r['room_number']; }
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🛎️ <?= e(t('hotel.hotel_services')) ?> (<?= count($services) ?>)</div>
        <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="openServiceModal()"><?= e(t('common.add_new')) ?></button>
    </div>
    <?php if (empty($services)): ?>
        <div class="empty-state"><div class="es-icon">🛎️</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('hotel.service_title')) ?></th>
                    <th><?= e(t('common.type')) ?></th>
                    <th><?= e(t('hotel.guest_name_label')) ?></th>
                    <th><?= e(t('hotel.room_number')) ?></th>
                    <th><?= e(t('common.quantity')) ?></th>
                    <th><?= e(t('common.amount')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('hotel.requested_at')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($services as $s): ?>
                        <tr>
                            <td class="fw-700"><?= e($s['title']) ?></td>
                            <td><span class="badge badge-info"><?= e($s['service_type']) ?></span></td>
                            <td><?= e($guestMap[(int)$s['guest_id']] ?? '—') ?></td>
                            <td class="text-muted"><?= e($roomMap[(int)$s['room_id']] ?? '—') ?></td>
                            <td><?= (int)$s['quantity'] ?></td>
                            <td class="fw-700"><?= e(formatMoney((float)$s['total_amount'])) ?></td>
                            <td><?= statusBadge((string)$s['status']) ?></td>
                            <td class="text-muted text-sm"><?= e(formatDate($s['created_at'])) ?></td>
                            <td class="actions">
                                <?php if ((string)$s['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-outline" onclick="serviceAction(<?= (int)$s['id'] ?>, 'start')">▶</button>
                                <?php endif; ?>
                                <?php if (in_array((string)$s['status'], ['pending', 'in_progress'], true)): ?>
                                    <button class="btn btn-sm btn-success" onclick="serviceAction(<?= (int)$s['id'] ?>, 'complete')">✓</button>
                                    <button class="btn btn-sm btn-danger" onclick="serviceAction(<?= (int)$s['id'] ?>, 'cancel')">✕</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="serviceModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title">🛎️ <?= e(t('hotel.service_request')) ?></div>
            <button class="btn btn-outline btn-sm" onclick="closeServiceModal()">✕</button>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.service_title')) ?> <span class="req">*</span></label>
                <input type="text" id="svc-title" class="form-control">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.type')) ?></label>
                    <select id="svc-type" class="form-select">
                        <?php foreach ($serviceTypes as $st): ?>
                            <option value="<?= e($st) ?>"><?= e($st === 'room_service' ? t('hotel.room_service') : ($st === 'laundry' ? t('hotel.laundry') : ($st === 'transport' ? t('hotel.transport') : ($st === 'restaurant_order' ? t('restaurant.order_management') : t('hotel.other_service'))))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.guest_name_label')) ?></label>
                    <select id="svc-guest" class="form-select">
                        <option value="0">—</option>
                        <?php foreach ($guests as $g): ?>
                            <option value="<?= (int)$g['id'] ?>"><?= e(trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.quantity')) ?></label>
                    <input type="number" id="svc-qty" class="form-control" value="1" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.price')) ?> (<?= e(t('common.per') === 'common.per' ? 'unit' : 'unit') ?>)</label>
                    <input type="number" step="0.01" id="svc-price" class="form-control" value="0" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.description')) ?></label>
                <textarea id="svc-desc" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.notes')) ?></label>
                <input type="text" id="svc-notes" class="form-control">
            </div>
            <button class="btn btn-primary" style="width:100%;" onclick="saveService()"><?= e(t('common.save')) ?></button>
        </div>
    </div>
</div>

<script>
function openServiceModal() {
    document.getElementById('svc-title').value = '';
    document.getElementById('svc-type').value = 'room_service';
    document.getElementById('svc-guest').value = '0';
    document.getElementById('svc-qty').value = '1';
    document.getElementById('svc-price').value = '0';
    document.getElementById('svc-desc').value = '';
    document.getElementById('svc-notes').value = '';
    document.getElementById('serviceModal').style.display = 'flex';
}
function closeServiceModal() { document.getElementById('serviceModal').style.display = 'none'; }
function saveService() {
    var data = {
        title: document.getElementById('svc-title').value,
        service_type: document.getElementById('svc-type').value,
        guest_id: parseInt(document.getElementById('svc-guest').value, 10) || 0,
        quantity: parseInt(document.getElementById('svc-qty').value, 10) || 1,
        unit_price: parseFloat(document.getElementById('svc-price').value) || 0,
        description: document.getElementById('svc-desc').value,
        notes: document.getElementById('svc-notes').value
    };
    if (!data.title) { alert('<?= e(t('common.please_fill_required')) ?>'); return; }
    ISHEMA.api('/hotel/services/create', data, 'POST')
        .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
}
function serviceAction(id, action) {
    ISHEMA.api('/hotel/services/' + id + '/' + action, {}, 'POST')
        .then(function (r) { if (r.ok) { location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
