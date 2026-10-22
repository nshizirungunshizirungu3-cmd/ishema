<?php
/** @var string|null $message @var string|null $error */
$pageTitle = t('common.contact_us') ?? 'Contact';
$navActive = 'contact';
ob_start();
?>
<section class="section" style="padding-top:50px;">
    <div class="inner">
        <div class="section-head">
            <h2><?= e(t('public.contact_title')) ?></h2>
            <p><?= e(t('public.contact_subtitle')) ?></p>
        </div>
        <div style="max-width:640px;margin:0 auto;">
            <?php if ($message): ?><div class="alert alert-success">✅ <?= e($message) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= e($error) ?></div><?php endif; ?>
            <div class="card">
                <form method="POST" action="/contact/send">
                    <?= \App\Core\CSRF::field() ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?= e(t('public.contact_name')) ?> <span class="req">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= e(t('public.contact_email')) ?> <span class="req">*</span></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><?= e(t('public.contact_phone')) ?></label>
                            <input type="tel" name="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label"><?= e(t('public.contact_business_type')) ?></label>
                            <select name="business_type" class="form-select">
                                <option value="HOTEL"><?= e(t('admin.business_type_hotel')) ?></option>
                                <option value="RESTAURANT"><?= e(t('admin.business_type_restaurant')) ?></option>
                                <option value="HOTEL_AND_RESTAURANT"><?= e(t('admin.business_type_both')) ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.subject')) ?></label>
                        <input type="text" name="subject" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e(t('common.message')) ?> <span class="req">*</span></label>
                        <textarea name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg"><?= e(t('public.contact_send')) ?></button>
                </form>
            </div>
            <div class="info-panel mt-4">
                <div class="d-flex gap-4" style="flex-wrap:wrap;">
                    <div>📧 <strong>hello@ishema.com</strong></div>
                    <div>📞 <strong>+250 788 000 000</strong></div>
                    <div>📍 <strong>Kigali, Rwanda</strong></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Demo modal -->
<div class="modal-backdrop" id="demo-modal">
    <div class="modal">
        <div class="modal-head">
            <h4><?= e(t('public.demo_modal_title')) ?></h4>
            <button class="btn btn-sm btn-outline" onclick="ISHEMA.closeModal('demo-modal')">✕</button>
        </div>
        <form method="POST" action="/demo/request">
            <?= \App\Core\CSRF::field() ?>
            <div class="modal-body">
                <p class="text-muted text-sm mb-3"><?= e(t('public.demo_modal_desc')) ?></p>
                <div class="form-group">
                    <label class="form-label"><?= e(t('public.contact_name')) ?> <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('public.contact_email')) ?> <span class="req">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('public.contact_phone')) ?></label>
                    <input type="tel" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label"><?= e(t('common.message')) ?></label>
                    <textarea name="message" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" class="btn btn-outline" onclick="ISHEMA.closeModal('demo-modal')"><?= e(t('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= e(t('public.contact_send')) ?></button>
            </div>
        </form>
    </div>
</div>
<?php
$content = ob_get_clean();
include VIEW_PATH . 'layouts/guest.php';
