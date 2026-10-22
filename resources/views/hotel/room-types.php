<?php
/** @var array $types */
$pageTitle = t('hotel.room_types');
ob_start();
$amenityKeys = ['wifi', 'parking', 'breakfast', 'pool', 'ac', 'tv', 'minibar', 'safe'];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🛏️ <?= e(t('hotel.room_types')) ?> (<?= count($types) ?>)</div>
        <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="openTypeModal()"><?= e(t('common.add_new')) ?></button>
    </div>
    <?php if (empty($types)): ?>
        <div class="empty-state"><div class="es-icon">🛏️</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('hotel.room_type_name')) ?></th>
                    <th><?= e(t('hotel.room_type_capacity')) ?></th>
                    <th><?= e(t('hotel.default_price')) ?></th>
                    <th><?= e(t('hotel.amenities')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($types as $type): ?>
                        <?php $amenities = is_array($type['amenities'] ?? null) ? $type['amenities'] : (array)json_decode((string)($type['amenities'] ?? '[]'), true); ?>
                        <tr>
                            <td class="fw-700"><?= e($type['name']) ?></td>
                            <td>🧑‍🤝‍🧑 <?= (int)$type['capacity'] ?></td>
                            <td><?= e(formatMoney((float)$type['default_price'])) ?>/<?= e(t('common.per_night')) ?></td>
                            <td>
                                <?php if (!empty($amenities)): ?>
                                    <?php foreach ($amenities as $a): ?>
                                        <span class="badge badge-info"><?= e(t('common.amenity_' . (string)$a) === 'common.amenity_' . (string)$a ? $a : t('common.amenity_' . (string)$a)) ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$type['is_active'] ? '<span class="badge badge-active">● ' . e(t('common.active')) . '</span>' : '<span class="badge badge-inactive">● ' . e(t('common.inactive')) . '</span>' ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-outline" onclick='openTypeModal(<?= json_encode($type) ?>)'>✏️ <?= e(t('common.edit')) ?></button>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/hotel/room-types/<?= (int)$type['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="typeModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title" id="type-modal-title"><?= e(t('hotel.add_room_type')) ?></div>
            <button class="btn btn-outline btn-sm" onclick="closeTypeModal()">✕</button>
        </div>
        <div class="card-body">
            <input type="hidden" id="type-id">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_type_name')) ?> <span class="req">*</span></label>
                <input type="text" id="type-name" class="form-control">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.room_type_capacity')) ?></label>
                    <input type="number" id="type-capacity" class="form-control" value="2" min="1">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.default_price')) ?></label>
                    <input type="number" step="0.01" id="type-price" class="form-control" value="0" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.amenities')) ?></label>
                <div class="form-row-3">
                    <?php foreach ($amenityKeys as $a): ?>
                        <label class="d-flex items-center gap-2">
                            <input type="checkbox" class="type-amenity" value="<?= e($a) ?>">
                            <span class="text-sm"><?= e(t('common.amenity_' . $a)) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.description')) ?></label>
                <textarea id="type-description" class="form-control" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label class="d-flex items-center gap-2">
                    <input type="checkbox" id="type-active" checked>
                    <span class="text-sm"><?= e(t('common.active')) ?></span>
                </label>
            </div>
            <button class="btn btn-primary" style="width:100%;" onclick="saveType()"><?= e(t('common.save')) ?></button>
        </div>
    </div>
</div>

<script>
function openTypeModal(data) {
    document.getElementById('type-id').value = data && data.id ? data.id : '';
    document.getElementById('type-name').value = data && data.name ? data.name : '';
    document.getElementById('type-capacity').value = data && data.capacity ? data.capacity : 2;
    document.getElementById('type-price').value = data && data.default_price ? data.default_price : 0;
    document.getElementById('type-description').value = data && data.description ? data.description : '';
    document.getElementById('type-active').checked = data ? !!data.is_active : true;
    document.getElementById('type-modal-title').textContent = data && data.id ? '<?= e(t('hotel.edit_room_type')) ?>' : '<?= e(t('hotel.add_room_type')) ?>';
    document.querySelectorAll('.type-amenity').forEach(function (cb) {
        cb.checked = data && Array.isArray(data.amenities) && data.amenities.indexOf(cb.value) !== -1;
    });
    document.getElementById('typeModal').style.display = 'flex';
}
function closeTypeModal() { document.getElementById('typeModal').style.display = 'none'; }
function saveType() {
    var id = document.getElementById('type-id').value;
    var amenities = [];
    document.querySelectorAll('.type-amenity:checked').forEach(function (cb) { amenities.push(cb.value); });
    var data = {
        name: document.getElementById('type-name').value,
        capacity: parseInt(document.getElementById('type-capacity').value, 10) || 1,
        default_price: parseFloat(document.getElementById('type-price').value) || 0,
        description: document.getElementById('type-description').value,
        amenities: amenities,
        is_active: document.getElementById('type-active').checked ? 1 : 0
    };
    var url = id ? '/hotel/room-types/' + id + '/update' : '/hotel/room-types/create';
    ISHEMA.api(url, data, 'POST')
        .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
