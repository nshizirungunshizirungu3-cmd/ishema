<?php
/** @var array $tasks @var array $rooms @var array $housekeepers @var string $status */
$pageTitle = t('hotel.housekeeping_management');
ob_start();
$statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
$types = ['cleaning', 'maintenance', 'inspection'];
$priorities = ['low', 'medium', 'high', 'urgent'];
$housekeeperMap = [];
foreach ($housekeepers as $hk) { $housekeeperMap[(int)$hk['user_id']] = (string)($hk['name'] ?? ''); }
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
        <div class="card-title">🧹 <?= e(t('hotel.housekeeping_management')) ?> (<?= count($tasks) ?>)</div>
        <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="openTaskModal()"><?= e(t('common.add_new')) ?></button>
    </div>
    <form method="GET" action="/hotel/housekeeping" class="d-flex gap-2 mb-3">
        <select name="status" class="form-select" style="width:auto;">
            <option value="all"><?= e(t('common.all')) ?> <?= e(t('common.status')) ?>es</option>
            <?php foreach ($statuses as $s): ?>
                <option value="<?= e($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= e(t('hotel.cleaning_status') === 'hotel.cleaning_status' ? ucfirst(str_replace('_', ' ', $s)) : t('hotel.cleaning_status')) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.filter')) ?></button>
    </form>
    <?php if (empty($tasks)): ?>
        <div class="empty-state"><div class="es-icon">🧹</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('hotel.room_number')) ?></th>
                    <th><?= e(t('common.type')) ?></th>
                    <th><?= e(t('common.priority')) ?></th>
                    <th><?= e(t('hotel.assign_to')) ?></th>
                    <th><?= e(t('common.notes')) ?></th>
                    <th><?= e(t('common.status')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td class="fw-700">#<?= e($roomMap[(int)$task['room_id']] ?? '—') ?></td>
                            <td><?= e(t('hotel.' . ((string)$task['type'] === 'maintenance' ? 'maintenance_request' : ((string)$task['type'] === 'cleaning' ? 'cleaning_request' : 'cleaning_request'))) )?></td>
                            <td><?= e(t('common.' . (string)$task['priority'])) ?></td>
                            <td class="text-muted"><?= e($housekeeperMap[(int)$task['assigned_to_user_id']] ?? '—') ?></td>
                            <td class="text-muted text-sm"><?= e((string)($task['notes'] ?? '')) ?></td>
                            <td><?= statusBadge((string)$task['status']) ?></td>
                            <td class="actions">
                                <?php if ((string)$task['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-outline" onclick="taskAction(<?= (int)$task['id'] ?>, 'start')">▶ <?= e(t('common.start') === 'common.start' ? 'Start' : t('common.start')) ?></button>
                                <?php endif; ?>
                                <?php if (in_array((string)$task['status'], ['pending', 'in_progress'], true)): ?>
                                    <button class="btn btn-sm btn-success" onclick="taskAction(<?= (int)$task['id'] ?>, 'complete')">✓ <?= e(t('hotel.complete_task')) ?></button>
                                    <button class="btn btn-sm btn-danger" onclick="taskAction(<?= (int)$task['id'] ?>, 'cancel')">✕</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="taskModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title">🧹 <?= e(t('hotel.cleaning_request')) ?></div>
            <button class="btn btn-outline btn-sm" onclick="closeTaskModal()">✕</button>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.room_number')) ?> <span class="req">*</span></label>
                <select id="task-room" class="form-select">
                    <option value="0">—</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= (int)$r['id'] ?>">#<?= e($r['room_number']) ?> — <?= e((string)($r['status'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.type')) ?></label>
                    <select id="task-type" class="form-select">
                        <?php foreach ($types as $ty): ?>
                            <option value="<?= e($ty) ?>"><?= e($ty === 'cleaning' ? t('hotel.cleaning_request') : ($ty === 'maintenance' ? t('hotel.maintenance_request') : t('hotel.cleaning_status'))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.priority')) ?></label>
                    <select id="task-priority" class="form-select">
                        <?php foreach ($priorities as $pr): ?>
                            <option value="<?= e($pr) ?>"><?= e(t('common.' . $pr)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('hotel.assign_to')) ?></label>
                <select id="task-assignee" class="form-select">
                    <option value="0">—</option>
                    <?php foreach ($housekeepers as $hk): ?>
                        <option value="<?= (int)$hk['user_id'] ?>"><?= e($hk['name'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.notes')) ?></label>
                <textarea id="task-notes" class="form-control" rows="2"></textarea>
            </div>
            <button class="btn btn-primary" style="width:100%;" onclick="saveTask()"><?= e(t('common.save')) ?></button>
        </div>
    </div>
</div>

<script>
function openTaskModal() {
    document.getElementById('task-room').value = '0';
    document.getElementById('task-type').value = 'cleaning';
    document.getElementById('task-priority').value = 'medium';
    document.getElementById('task-assignee').value = '0';
    document.getElementById('task-notes').value = '';
    document.getElementById('taskModal').style.display = 'flex';
}
function closeTaskModal() { document.getElementById('taskModal').style.display = 'none'; }
function saveTask() {
    var data = {
        room_id: parseInt(document.getElementById('task-room').value, 10) || 0,
        type: document.getElementById('task-type').value,
        priority: document.getElementById('task-priority').value,
        assigned_to_user_id: parseInt(document.getElementById('task-assignee').value, 10) || 0,
        notes: document.getElementById('task-notes').value
    };
    if (!data.room_id) { alert('<?= e(t('common.please_fill_required')) ?>'); return; }
    ISHEMA.api('/hotel/housekeeping/create', data, 'POST')
        .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
}
function taskAction(id, action) {
    ISHEMA.api('/hotel/housekeeping/' + id + '/' + action, {}, 'POST')
        .then(function (r) { if (r.ok) { location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
