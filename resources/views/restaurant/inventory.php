<?php
/** @var array $products @var array $suppliers @var array $movements */
$pageTitle = t('restaurant.inventory_management');
ob_start();
$productMap = [];
foreach ($products as $p) { $productMap[(int)$p['id']] = (string)$p['name']; }
$supplierMap = [];
foreach ($suppliers as $s) { $supplierMap[(int)$s['id']] = (string)$s['name']; }
$typeLabels = ['in' => t('restaurant.stock_in'), 'out' => t('restaurant.stock_out'), 'adjustment' => t('restaurant.stock_adjustment'), 'waste' => t('common.low')];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title">📦 <?= e(t('restaurant.inventory_management')) ?></div>
            <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="openMovementModal()"><?= e(t('restaurant.stock_in')) ?> / <?= e(t('restaurant.stock_out')) ?></button>
        </div>
        <?php if (empty($products)): ?>
            <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th><?= e(t('restaurant.product_name')) ?></th><th><?= e(t('restaurant.product_stock')) ?></th><th><?= e(t('restaurant.min_stock')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="fw-700"><?= e($p['name']) ?></td>
                            <td>
                                <?= (float)$p['stock_quantity'] ?>
                                <?php if ((float)$p['stock_quantity'] <= (float)$p['min_stock']): ?>
                                    <span class="badge badge-warning">⚠ <?= e(t('restaurant.low_stock')) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= (float)$p['min_stock'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">🚚 <?= e(t('common.suppliers')) ?> (<?= count($suppliers) ?>)</div>
            <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="openSupplierModal()"><?= e(t('restaurant.add_supplier')) ?></button>
        </div>
        <?php if (empty($suppliers)): ?>
            <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
        <?php else: ?>
            <div class="table-wrap"><table class="table">
                <thead><tr><th><?= e(t('restaurant.supplier_name')) ?></th><th><?= e(t('restaurant.contact_person')) ?></th><th><?= e(t('common.phone')) ?></th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($suppliers as $s): ?>
                        <tr>
                            <td class="fw-700"><?= e($s['name']) ?></td>
                            <td class="text-muted"><?= e((string)($s['contact_person'] ?? '—')) ?></td>
                            <td class="text-muted"><?= e((string)($s['phone'] ?? '—')) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline" onclick='openSupplierModal(<?= json_encode($s) ?>)'>✏️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><div class="card-title">📜 <?= e(t('restaurant.movement_history')) ?> (<?= count($movements) ?>)</div></div>
    <?php if (empty($movements)): ?>
        <div class="empty-state" style="padding:18px;"><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap"><table class="table">
            <thead><tr>
                <th><?= e(t('common.date')) ?></th>
                <th><?= e(t('restaurant.product_name')) ?></th>
                <th><?= e(t('restaurant.movement_type')) ?></th>
                <th><?= e(t('common.quantity')) ?></th>
                <th><?= e(t('restaurant.reference_label')) ?></th>
                <th><?= e(t('common.supplier')) ?></th>
                <th><?= e(t('common.notes')) ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($movements as $m): ?>
                    <tr>
                        <td class="text-muted text-sm"><?= e(formatDate($m['created_at'])) ?></td>
                        <td class="fw-700"><?= e($productMap[(int)$m['product_id']] ?? '—') ?></td>
                        <td>
                            <?php $mt = (string)$m['type']; ?>
                            <?php if ($mt === 'in'): ?><span class="badge badge-active">＋ <?= e(t('restaurant.stock_in')) ?></span>
                            <?php elseif ($mt === 'out'): ?><span class="badge badge-info">− <?= e(t('restaurant.stock_out')) ?></span>
                            <?php elseif ($mt === 'adjustment'): ?><span class="badge badge-warning">± <?= e(t('restaurant.stock_adjustment')) ?></span>
                            <?php else: ?><span class="badge badge-inactive">✕ <?= e($mt) ?></span><?php endif; ?>
                        </td>
                        <td class="fw-700"><?= (float)$m['quantity'] ?></td>
                        <td class="mono text-muted"><?= e((string)($m['reference'] ?? '—')) ?></td>
                        <td class="text-muted"><?= e($supplierMap[(int)$m['supplier_id']] ?? '—') ?></td>
                        <td class="text-muted text-sm"><?= e((string)($m['notes'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table></div>
    <?php endif; ?>
</div>

<div id="movementModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title"><?= e(t('restaurant.stock_adjustment')) ?></div>
            <button class="btn btn-outline btn-sm" onclick="closeMovementModal()">✕</button>
        </div>
        <div class="card-body">
            <form id="movement-form">
                <div class="form-group">
                    <label class="form-label"><?= e(t('restaurant.product_name')) ?> <span class="req">*</span></label>
                    <select name="product_id" class="form-select" required>
                        <option value="0">—</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> (<?= (float)$p['stock_quantity'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.movement_type')) ?></label>
                        <select name="type" class="form-select">
                            <option value="in"><?= e(t('restaurant.stock_in')) ?></option>
                            <option value="out"><?= e(t('restaurant.stock_out')) ?></option>
                            <option value="adjustment"><?= e(t('restaurant.stock_adjustment')) ?></option>
                            <option value="waste"><?= e(t('common.low')) ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.quantity')) ?> <span class="req">*</span></label>
                        <input type="number" step="0.01" min="0.01" name="quantity" class="form-control" value="1" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.reference_label')) ?></label>
                        <input type="text" name="reference" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.supplier')) ?></label>
                        <select name="supplier_id" class="form-select">
                            <option value="0">—</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.notes') )?></label>
                    <input type="text" name="notes" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"><?= e(t('common.save')) ?></button>
            </form>
        </div>
    </div>
</div>

<div id="supplierModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title" id="supplier-modal-title"><?= e(t('restaurant.add_supplier')) ?></div>
            <button class="btn btn-outline btn-sm" onclick="closeSupplierModal()">✕</button>
        </div>
        <div class="card-body">
            <form id="supplier-form">
                <input type="hidden" name="id" id="sup-id">
                <div class="form-group">
                    <label class="form-label"><?= e(t('restaurant.supplier_name')) ?> <span class="req">*</span></label>
                    <input type="text" name="name" id="sup-name" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.contact_person')) ?></label>
                        <input type="text" name="contact_person" id="sup-contact" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.phone')) ?></label>
                        <input type="tel" name="phone" id="sup-phone" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.email')) ?></label>
                        <input type="email" name="email" id="sup-email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.address')) ?></label>
                        <input type="text" name="address" id="sup-address" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="d-flex items-center gap-2">
                        <input type="checkbox" name="is_active" id="sup-active" value="1" checked>
                        <span class="text-sm"><?= e(t('common.active')) ?></span>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"><?= e(t('common.save')) ?></button>
            </form>
        </div>
    </div>
</div>

<script>
function openMovementModal() { document.getElementById('movementModal').style.display = 'flex'; }
function closeMovementModal() { document.getElementById('movementModal').style.display = 'none'; }
document.getElementById('movement-form').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var f = ev.target;
    ISHEMA.api('/restaurant/inventory/movement', {
        product_id: parseInt(f.product_id.value, 10) || 0,
        type: f.type.value,
        quantity: parseFloat(f.quantity.value) || 0,
        reference: f.reference.value,
        supplier_id: parseInt(f.supplier_id.value, 10) || 0,
        notes: f.notes.value
    }, 'POST')
    .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
    .catch(function () { alert('Error'); });
});
function openSupplierModal(data) {
    var f = document.getElementById('supplier-form');
    document.getElementById('sup-id').value = data && data.id ? data.id : '';
    f.name.value = data && data.name ? data.name : '';
    f.contact_person.value = data && data.contact_person ? data.contact_person : '';
    f.phone.value = data && data.phone ? data.phone : '';
    f.email.value = data && data.email ? data.email : '';
    f.address.value = data && data.address ? data.address : '';
    document.getElementById('sup-active').checked = data ? !!data.is_active : true;
    f.action = data && data.id ? '/restaurant/inventory/suppliers/' + data.id + '/update' : '';
    document.getElementById('supplier-modal-title').textContent = data && data.id ? '<?= e(t('restaurant.edit_supplier')) ?>' : '<?= e(t('restaurant.add_supplier')) ?>';
    document.getElementById('supplierModal').style.display = 'flex';
}
function closeSupplierModal() { document.getElementById('supplierModal').style.display = 'none'; }
document.getElementById('supplier-form').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var f = ev.target;
    var id = document.getElementById('sup-id').value;
    var data = {
        name: f.name.value,
        contact_person: f.contact_person.value,
        phone: f.phone.value,
        email: f.email.value,
        address: f.address.value,
        is_active: document.getElementById('sup-active').checked ? 1 : 0
    };
    if (!data.name) { alert('<?= e(t('common.please_fill_required')) ?>'); return; }
    var url = id ? '/restaurant/inventory/suppliers/' + id + '/update' : '/restaurant/inventory/suppliers/create';
    ISHEMA.api(url, data, 'POST')
        .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
