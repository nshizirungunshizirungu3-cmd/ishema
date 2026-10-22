<?php
/** @var array $guests @var string $search */
$pageTitle = t('hotel.guest_management');
ob_start();
?>
<?php if ($msg = \App\Core\Session::flash('message')): ?>
    <div class="alert alert-success">✅ <?= e($msg) ?></div>
<?php endif; ?>
<?php if ($error = \App\Core\Session::flash('error')): ?>
    <div class="alert alert-danger">⚠️ <?= e($error) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">🧍 <?= e(t('hotel.guest_management')) ?> (<?= count($guests) ?>)</div>
        <button class="btn btn-primary btn-sm" style="margin-left:auto;" onclick="openGuestModal()"><?= e(t('hotel.add_guest')) ?></button>
    </div>
    <form method="GET" action="/hotel/guests" class="d-flex gap-2 mb-3">
        <input type="search" name="search" class="form-control" style="max-width:320px;" placeholder="<?= e(t('common.search_placeholder')) ?>" value="<?= e($search) ?>">
        <button class="btn btn-outline" type="submit">🔍 <?= e(t('common.search')) ?></button>
    </form>
    <?php if (empty($guests)): ?>
        <div class="empty-state"><div class="es-icon">🧍</div><div class="es-title"><?= e(t('common.no_records')) ?></div></div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th><?= e(t('hotel.guest_name_label')) ?></th>
                    <th><?= e(t('common.email')) ?></th>
                    <th><?= e(t('common.phone')) ?></th>
                    <th><?= e(t('hotel.id_number')) ?></th>
                    <th><?= e(t('hotel.country')) ?></th>
                    <th><?= e(t('common.actions')) ?></th>
                </tr></thead>
                <tbody>
                    <?php foreach ($guests as $g): ?>
                        <tr>
                            <td class="fw-700"><?= e(trim(($g['first_name'] ?? '') . ' ' . ($g['last_name'] ?? ''))) ?></td>
                            <td class="text-muted"><?= e((string)($g['email'] ?? '—')) ?></td>
                            <td class="text-muted"><?= e((string)($g['phone'] ?? '—')) ?></td>
                            <td class="mono text-muted"><?= e((string)($g['id_number'] ?? '—')) ?></td>
                            <td class="text-muted"><?= e((string)($g['country'] ?? '—')) ?></td>
                            <td class="actions">
                                <a href="/hotel/guests/<?= (int)$g['id'] ?>" class="btn btn-sm btn-outline">👁 <?= e(t('common.view')) ?></a>
                                <button class="btn btn-sm btn-outline" onclick='openGuestModal(<?= json_encode($g) ?>)'>✏️</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div id="guestModal" class="modal" style="display:none;">
    <div class="modal-card">
        <div class="card-header">
            <div class="card-title" id="guest-modal-title"><?= e(t('hotel.add_guest')) ?></div>
            <button class="btn btn-outline btn-sm" onclick="closeGuestModal()">✕</button>
        </div>
        <div class="card-body">
            <input type="hidden" id="guest-id">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.first_name')) ?> <span class="req">*</span></label>
                    <input type="text" id="guest-first" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.last_name')) ?> <span class="req">*</span></label>
                    <input type="text" id="guest-last" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.email')) ?></label>
                    <input type="email" id="guest-email" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.phone')) ?></label>
                    <input type="tel" id="guest-phone" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.id_type')) ?></label>
                    <select id="guest-id-type" class="form-select">
                        <option value="passport">Passport</option>
                        <option value="national_id">National ID</option>
                        <option value="drivers_license">Driver's License</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.id_number')) ?></label>
                    <input type="text" id="guest-id-number" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label"><?= e(t('hotel.country')) ?></label>
                    <input type="text" id="guest-country" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.address')) ?></label>
                    <input type="text" id="guest-address" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?= e(t('common.notes')) ?></label>
                <textarea id="guest-notes" class="form-control" rows="2"></textarea>
            </div>
            <button class="btn btn-primary" style="width:100%;" onclick="saveGuest()"><?= e(t('common.save')) ?></button>
        </div>
    </div>
</div>

<script>
function openGuestModal(data) {
    document.getElementById('guest-id').value = data && data.id ? data.id : '';
    document.getElementById('guest-first').value = data && data.first_name ? data.first_name : '';
    document.getElementById('guest-last').value = data && data.last_name ? data.last_name : '';
    document.getElementById('guest-email').value = data && data.email ? data.email : '';
    document.getElementById('guest-phone').value = data && data.phone ? data.phone : '';
    document.getElementById('guest-id-type').value = data && data.id_type ? data.id_type : 'passport';
    document.getElementById('guest-id-number').value = data && data.id_number ? data.id_number : '';
    document.getElementById('guest-country').value = data && data.country ? data.country : '';
    document.getElementById('guest-address').value = data && data.address ? data.address : '';
    document.getElementById('guest-notes').value = data && data.notes ? data.notes : '';
    document.getElementById('guest-modal-title').textContent = data && data.id ? '<?= e(t('hotel.edit_guest')) ?>' : '<?= e(t('hotel.add_guest')) ?>';
    document.getElementById('guestModal').style.display = 'flex';
}
function closeGuestModal() { document.getElementById('guestModal').style.display = 'none'; }
function saveGuest() {
    var id = document.getElementById('guest-id').value;
    var data = {
        first_name: document.getElementById('guest-first').value,
        last_name: document.getElementById('guest-last').value,
        email: document.getElementById('guest-email').value,
        phone: document.getElementById('guest-phone').value,
        id_type: document.getElementById('guest-id-type').value,
        id_number: document.getElementById('guest-id-number').value,
        country: document.getElementById('guest-country').value,
        address: document.getElementById('guest-address').value,
        notes: document.getElementById('guest-notes').value
    };
    if (!data.first_name || !data.last_name) { alert('<?= e(t('common.please_fill_required')) ?>'); return; }
    var url = id ? '/hotel/guests/' + id + '/update' : '/hotel/guests/create';
    ISHEMA.api(url, data, 'POST')
        .then(function (r) { if (r.ok) { ISHEMA.toast('✅ ' + (r.message || '')); location.reload(); } else { alert(r.error || 'Error'); } })
        .catch(function () { alert('Error'); });
}
</script>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/app.php';
