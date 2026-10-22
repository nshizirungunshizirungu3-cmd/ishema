<?php
/** @var array $categories */
$pageTitle = t('restaurant.category_management');
ob_start();
$typeLabels = ['food' => t('restaurant.type_food'), 'drinks' => t('restaurant.type_drinks'), 'desserts' => t('restaurant.type_desserts'), 'breakfast' => t('restaurant.type_breakfast'), 'other' => t('restaurant.type_other')];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🏷️ <?= e(t('restaurant.category_management')) ?> (<?= count($categories) ?>)</div>
        <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="openCatModal()"><?= e(t('restaurant.add_category')) ?></button>
    </div>
    <?php if (empty($categories)): ?>
        <div class="empty-state"><div class="es-icon">🏷️</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th></th>
                    <th><?= e(t('restaurant.category_name')) ?></th>
                    <th><?= e(t('restaurant.category_type')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td><span style="display:inline-block;width:16px;height:16px;border-radius:4px;background:<?= e((string)($c['color'] ?? '#1056A0')) ?>;"></span></td>
                            <td class="fw-700"><?= e($c['name']) ?>
                                <?php if (!(int)$c['is_active']): ?><span class="badge badge-inactive">● <?= e(t('common.inactive')) ?></span><?php endif; ?>
                            </td>
                            <td><?= e($typeLabels[(string)$c['type']] ?? e($c['type'])) ?></td>
                            <td class="actions">
                                <button class="btn btn-sm btn-outline" onclick='openCatModal(<?= json_encode($c) ?>)'>✏️ <?= e(t('common.edit')) ?></button>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/restaurant/categories/<?= (int)$c['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="catModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title" id="cat-modal-title"><?= e(t('restaurant.add_category')) ?></div>
            <button class="btn btn-outline btn-sm" onclick="closeCatModal()">✕</button>
        </div>
        <div class="card-body">
            <input type="hidden" id="cat-id">
            <div class="form-group">
                <label class="form-label"><?= e(t('restaurant.category_name')) ?> <span class="req">*</span></label>
                <input type="text" id="cat-name" class="form-control">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('restaurant.category_type')) ?></label>
                    <select id="cat-type" class="form-select">
                        <?php foreach ($typeLabels as $k => $label): ?>
                            <option value="<?= e($k) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('restaurant.category_color')) ?></label>
                    <input type="color" id="cat-color" class="form-control" value="#1056A0" style="height:38px;padding:2px;">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.sort_by') === 'common.sort_by' ? 'Sort order' : 'Sort order') ?></label>
                <input type="number" id="cat-sort" class="form-control" value="0">
            </div>
            <div class="form-group">
                <label class="d-flex items-center gap-2">
                    <input type="checkbox" id="cat-active" checked>
                    <span class="text-sm"><?= e(t('common.active')) ?></span>
                </label>
            </div>
            <button class="btn btn-primary" style="width:100%;" onclick="saveCat()"><?= e(t('common.save')) ?></button>
        </div>
    </div>
</div>

<script>
function openCatModal(data) {
    document.getElementById('cat-id').value = data && data.id ? data.id : '';
    document.getElementById('cat-name').value = data && data.name ? data.name : '';
    document.getElementById('cat-type').value = data && data.type ? data.type : 'other';
    document.getElementById('cat-color').value = data && data.color ? data.color : '#1056A0';
    document.getElementById('cat-sort').value = data && data.sort_order ? data.sort_order : 0;
    document.getElementById('cat-active').checked = data ? !!data.is_active : true;
    document.getElementById('cat-modal-title').textContent = data && data.id ? '<?= e(t('restaurant.edit_category')) ?>' : '<?= e(t('restaurant.add_category')) ?>';
    document.getElementById('catModal').style.display = 'flex';
}
function closeCatModal() { document.getElementById('catModal').style.display = 'none'; }
function saveCat() {
    var id = document.getElementById('cat-id').value;
    var data = {
        name: document.getElementById('cat-name').value,
        type: document.getElementById('cat-type').value,
        color: document.getElementById('cat-color').value,
        sort_order: parseInt(document.getElementById('cat-sort').value, 10) || 0,
        is_active: document.getElementById('cat-active').checked ? 1 : 0
    };
    if (!data.name) { alert('<?= e(t('common.please_fill_required')) ?>'); return; }
    var url = id ? '/restaurant/categories/' + id + '/update' : '/restaurant/categories/create';
    ISHEMA.api(url, data, 'POST')
        .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
