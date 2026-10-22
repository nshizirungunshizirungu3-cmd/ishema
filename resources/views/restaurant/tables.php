<?php
/** @var array $tables @var array $waiters @var array $qrPaths */
$pageTitle = t('restaurant.table_management');
ob_start();
$waiterMap = [];
foreach ($waiters as $w) { $waiterMap[(int)$w['user_id']] = (string)($w['name'] ?? ''); }
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🍽️ <?= e(t('restaurant.table_management')) ?> (<?= count($tables) ?>)</div>
        <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="openTableModal()"><?= e(t('restaurant.add_table')) ?></button>
    </div>
    <?php if (empty($tables)): ?>
        <div class="empty-state"><div class="es-icon">🍽️</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('restaurant.table_number')) ?></th>
                    <th><?= e(t('restaurant.table_name')) ?></th>
                    <th><?= e(t('restaurant.seats')) ?></th>
                    <th><?= e(t('restaurant.floor_label')) ?></th>
                    <th><?= e(t('restaurant.table_status')) ?></th>
                    <th><?= e(t('restaurant.assign_waiter')) ?></th>
                    <th><?= e(t('restaurant.table_qr')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($tables as $t): ?>
                        <tr>
                            <td class="fw-700">#<?= e($t['table_number']) ?></td>
                            <td><?= e((string)($t['name'] ?? '—')) ?></td>
                            <td>🪑 <?= (int)$t['seats'] ?></td>
                            <td class="text-muted"><?= e((string)($t['floor'] ?? '—')) ?></td>
                            <td><?= statusBadge((string)$t['status']) ?></td>
                            <td class="text-muted"><?= e($waiterMap[(int)$t['waiter_user_id']] ?? '—') ?></td>
                            <td>
                                <?php if (isset($qrPaths[(int)$t['id']])): ?>
                                    <img src="<?= e($qrPaths[(int)$t['id']]) ?>" style="width:56px;height:56px;" alt="QR"
                                        title="<?= e(t('common.scan_qr')) ?>">
                                    <div class="mt-1">
                                        <a href="/restaurant/tables/<?= (int)$t['id'] ?>/qr/download" class="btn btn-sm btn-outline">⬇ <?= e(t('common.download_qr')) ?></a>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="actions">
                                <button class="btn btn-sm btn-outline" onclick='openTableModal(<?= json_encode($t) ?>)'>✏️ <?= e(t('common.edit')) ?></button>
                                <button class="btn btn-sm btn-danger" onclick="ISHEMA.confirmDelete('/restaurant/tables/<?= (int)$t['id'] ?>/delete', '<?= e(t('common.delete_confirm')) ?>')">🗑</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="tableModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title" id="table-modal-title"><?= e(t('restaurant.add_table')) ?></div>
            <button class="btn btn-outline btn-sm" onclick="closeTableModal()">✕</button>
        </div>
        <div class="card-body">
            <form id="table-form" method="POST">
                <?= \App\Core\CSRF::field() ?>
                <input type="hidden" name="id" id="tbl-id">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.table_number')) ?> <span class="req">*</span></label>
                        <input type="text" name="table_number" id="tbl-number" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.table_name')) ?></label>
                        <input type="text" name="name" id="tbl-name" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.seats')) ?></label>
                        <input type="number" name="seats" id="tbl-seats" class="form-control" value="2" min="1">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.floor_label')) ?></label>
                        <input type="text" name="floor" id="tbl-floor" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.table_status')) ?></label>
                        <select name="status" id="tbl-status" class="form-select">
                            <option value="available"><?= e(t('restaurant.status_available')) ?></option>
                            <option value="occupied"><?= e(t('restaurant.status_occupied')) ?></option>
                            <option value="cleaning"><?= e(t('restaurant.status_cleaning')) ?></option>
                            <option value="reserved"><?= e(t('restaurant.status_reserved')) ?></option>
                            <option value="out_of_service"><?= e(t('restaurant.status_out_of_service')) ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.assign_waiter')) ?></label>
                        <select name="waiter_user_id" id="tbl-waiter" class="form-select">
                            <option value="0">—</option>
                            <?php foreach ($waiters as $w): ?>
                                <option value="<?= (int)$w['user_id'] ?>"><?= e($w['name'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.sort_by') === 'common.sort_by' ? 'Sort order' : 'Sort order') ?></label>
                    <input type="number" name="sort_order" id="tbl-sort" class="form-control" value="0">
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"><?= e(t('common.save')) ?></button>
            </form>
        </div>
    </div>
</div>

<script>
function openTableModal(data) {
    var form = document.getElementById('table-form');
    document.getElementById('tbl-id').value = data && data.id ? data.id : '';
    form.table_number.value = data && data.table_number ? data.table_number : '';
    form.name.value = data && data.name ? data.name : '';
    form.seats.value = data && data.seats ? data.seats : 2;
    form.floor.value = data && data.floor ? data.floor : '';
    form.status.value = data && data.status ? data.status : 'available';
    form.waiter_user_id.value = data && data.waiter_user_id ? data.waiter_user_id : 0;
    form.sort_order.value = data && data.sort_order ? data.sort_order : 0;
    form.action = data && data.id ? '/restaurant/tables/' + data.id + '/update' : '/restaurant/tables/create';
    document.getElementById('table-modal-title').textContent = data && data.id ? '<?= e(t('restaurant.edit_table')) ?>' : '<?= e(t('restaurant.add_table')) ?>';
    document.getElementById('tableModal').style.display = 'flex';
}
function closeTableModal() { document.getElementById('tableModal').style.display = 'none'; }
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
