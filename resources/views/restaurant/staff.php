<?php
/** @var array $staff @var array $shifts @var array $assignments @var string $today */
$pageTitle = t('restaurant.staff_management');
ob_start();
$shiftMap = [];
foreach ($shifts as $s) { $shiftMap[(int)$s['id']] = (string)$s['name']; }
$assignmentMap = [];
foreach ($assignments as $a) { $assignmentMap[(int)$a['business_user_id']] = $a; }
$validRoles = ['MANAGER', 'RECEPTIONIST', 'WAITER', 'KITCHEN_STAFF', 'CASHIER', 'HOUSEKEEPING'];
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>
<?php if ($pw = \App\Core\Session::flash('generated_password')): ?>
    <div class="alert alert-info">🔑 <?= e(t('admin.generated_password')) ?>: <strong class="mono"><?= e($pw) ?></strong> (<?= e(\App\Core\Session::flash('owner_email') ?? '') ?>)</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">👥 <?= e(t('restaurant.staff_management')) ?> (<?= count($staff) ?>)</div>
        <a href="/restaurant/staff/create" class="btn btn-primary btn-sm" style="margin-left:auto;">➕ <?= e(t('restaurant.add_employee')) ?></a>
    </div>
    <?php if (empty($staff)): ?>
        <div class="empty-state"><div class="es-icon">👥</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('restaurant.employee_name')) ?></th>
                    <th><?= e(t('restaurant.employee_role')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('restaurant.assign_shift')) ?></th>
                    <th><?= e(t('restaurant.assign_tables')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($staff as $s): ?>
                        <?php $assignedTables = is_array($s['assigned_tables'] ?? null) ? $s['assigned_tables'] : (array)json_decode((string)($s['assigned_tables'] ?? '[]'), true); ?>
                        <tr>
                            <td class="fw-700">
                                <?= e($s['name']) ?>
                                <div class="text-muted text-xs">📧 <?= e($s['email']) ?><?= !empty($s['phone']) ? ' · 📞 ' . e($s['phone']) : '' ?></div>
                            </td>
                            <td><span class="badge badge-info"><?= e(t('admin.staff_role_' . strtolower((string)$s['role']) ) )?></span></td>
                            <td><?= (int)$s['is_active'] ? '<span class="badge badge-active">● ' . e(t('common.active')) . '</span>' : '<span class="badge badge-inactive">● ' . e(t('common.inactive')) . '</span>' ?></td>
                            <td class="text-muted text-sm">
                                <?php if (isset($assignmentMap[(int)$s['id']])): ?>
                                    <?= e($shiftMap[(int)$assignmentMap[(int)$s['id']]['shift_id']] ?? '—') ?>
                                    <div class="text-xs"><?= e((string)$today) ?></div>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="text-muted text-sm"><?= count($assignedTables) > 0 ? count($assignedTables) . ' ' . e(t('restaurant.table_management') === 'x' ? 'tables' : t('common.tables')) : '—' ?></td>
                            <td class="actions">
                                <a href="/restaurant/staff/<?= (int)$s['id'] ?>/edit" class="btn btn-sm btn-outline">✏️ <?= e(t('common.edit')) ?></a>
                                <button class="btn btn-sm <?= (int)$s['is_active'] ? 'btn-outline' : 'btn-success' ?>" onclick="ISHEMA.api('/restaurant/staff/<?= (int)$s['id'] ?>/toggle', {}).then(r => location.reload())">
                                    <?= (int)$s['is_active'] ? '⏸ ' . e(t('common.deactivate')) : '▶ ' . e(t('common.activate')) ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <div class="card-title">⏰ <?= e(t('common.shifts')) ?> (<?= count($shifts) ?>)</div>
        </div>
        <div class="card-body">
            <?php if (empty($shifts)): ?>
                <div class="text-muted"><?= e(t('common.no_records')) ?></div>
            <?php else: ?>
                <?php foreach ($shifts as $s): ?>
                    <div class="d-flex justify-between items-center mb-2">
                        <div class="fw-700"><?= e($s['name']) ?></div>
                        <div class="text-muted text-sm">🕐 <?= e((string)$s['start_time']) ?> → <?= e((string)$s['end_time']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <hr style="border:none;border-top:1px solid var(--border);">
            <form id="shift-form" class="d-flex gap-2">
                <input type="text" name="name" class="form-control" placeholder="<?= e(t('restaurant.shift_name')) ?>" required style="flex:1;">
                <input type="time" name="start_time" class="form-control" value="08:00" required>
                <input type="time" name="end_time" class="form-control" value="17:00" required>
                <button type="submit" class="btn btn-primary">＋</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">📅 <?= e(t('restaurant.assign_shift')) ?></div>
        </div>
        <div class="card-body">
            <form id="assign-form">
                <div class="form-group">
                    <label class="form-label"><?= e(t('restaurant.employee_name')) ?> <span class="req">*</span></label>
                    <select name="business_user_id" class="form-select" required>
                        <option value="0">—</option>
                        <?php foreach ($staff as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['role']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><?= e(t('restaurant.shift_name') )?></label>
                        <select name="shift_id" class="form-select">
                            <option value="0">—</option>
                            <?php foreach ($shifts as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= e($s['name']) ?> (<?= e((string)$s['start_time']) ?>–<?= e((string)$s['end_time']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.date')) ?></label>
                        <input type="date" name="assignment_date" class="form-control" value="<?= e($today) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;"><?= e(t('common.assign')) ?></button>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('shift-form').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var f = ev.target;
    ISHEMA.api('/restaurant/shifts/create', {name: f.name.value, start_time: f.start_time.value, end_time: f.end_time.value}, 'POST')
        .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
});
document.getElementById('assign-form').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var f = ev.target;
    ISHEMA.api('/restaurant/shifts/assign', {
        business_user_id: parseInt(f.business_user_id.value, 10) || 0,
        shift_id: parseInt(f.shift_id.value, 10) || 0,
        assignment_date: f.assignment_date.value
    }, 'POST')
    .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
    .catch(function () { alert('Error'); });
});
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
