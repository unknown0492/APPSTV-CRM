<?php
checkAuthorizationForPage( 'profile' );

// My Profile (redesigned 1.0.61) - drawn by profile.js; same tabs and width as Website Configuration
$prf_js  = dirname( __DIR__ ) . '/js/' . basename( __FILE__, ".php" ) . '.js';
$prf_css = dirname( __DIR__ ) . '/css/' . basename( __FILE__, ".php" ) . '.css';
?>
<link href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css?v=<?= @filemtime( $prf_css ) ?>" rel="stylesheet" type="text/css" />

<!--begin::My Profile-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-fluid">

        <div class="text-center text-muted py-15" id="prf_loading">
            <span class="spinner-border spinner-border-sm align-middle me-2"></span> Loading…
        </div>

        <div class="d-none" id="prf_page">
            <!-- Who -->
            <div class="card mb-6">
                <div class="card-body d-flex flex-wrap align-items-center gap-6 py-7">
                    <div class="prf-avatar prf-avatar-lg" id="prf_head_avatar"></div>
                    <div class="flex-grow-1">
                        <div class="fs-2 fw-bold text-gray-900" id="prf_head_name"></div>
                        <div class="text-muted fw-semibold"><span id="prf_head_user_id"></span> &middot; <span id="prf_head_email"></span></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="px-9 pt-7">
                    <ul class="nav nav-tabs prf-tabs fs-6" id="prf_tabs" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#prf_tab_profile"><i class="fa-solid fa-user me-2"></i>Profile</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#prf_tab_picture"><i class="fa-solid fa-image-portrait me-2"></i>Picture</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#prf_tab_contact"><i class="fa-solid fa-address-book me-2"></i>Contact</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#prf_tab_security"><i class="fa-solid fa-shield-halved me-2"></i>Security</a></li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">

                        <!-- Profile -->
                        <div class="tab-pane fade show active" id="prf_tab_profile" role="tabpanel">
                            <div class="mw-700px">
                                <div class="row g-4">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">User ID</label>
                                        <input type="text" class="form-control form-control-solid" id="prf_user_id" disabled />
                                    </div>
                                    <div class="col-12 col-md-6"></div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label required" for="prf_fname">First name</label>
                                        <input type="text" class="form-control form-control-solid" id="prf_fname" maxlength="100" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label required" for="prf_lname">Last name</label>
                                        <input type="text" class="form-control form-control-solid" id="prf_lname" maxlength="100" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="prf_nickname">Nickname</label>
                                        <input type="text" class="form-control form-control-solid" id="prf_nickname" maxlength="100" />
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-6">
                                    <button type="button" class="btn btn-primary" id="prf_save_details">
                                        <span class="indicator-label">Save details</span>
                                        <span class="indicator-progress">Please wait… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </div>

                                <div class="separator my-8"></div>
                                <h4 class="fw-bold mb-1">Name next to your picture</h4>
                                <div class="text-muted fs-7 mb-4" id="prf_display_hint"></div>
                                <div class="row align-items-center">
                                    <div class="col-12 col-md-6 mb-3 mb-md-0"><select class="form-select form-select-solid" id="prf_display_field"></select></div>
                                    <div class="col-12 col-md-6 text-muted fs-7">Shows as: <span class="fw-bold text-gray-800" id="prf_display_preview"></span></div>
                                </div>
                            </div>
                        </div>

                        <!-- Picture -->
                        <div class="tab-pane fade" id="prf_tab_picture" role="tabpanel">
                            <div class="d-flex flex-wrap align-items-center gap-6 mb-8">
                                <div class="prf-avatar prf-avatar-xl" id="prf_picture_current"></div>
                                <div>
                                    <div class="fw-bold fs-5 mb-1">Your picture</div>
                                    <div class="text-muted fs-7 mb-4">It appears at the top right of every page. Pick a cartoon, use your initials, or upload your own.</div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-light-primary" id="prf_use_initials"><i class="fa-solid fa-font me-1"></i>Use my initials</button>
                                        <button type="button" class="btn btn-sm btn-primary d-none" id="prf_upload_btn"><i class="fa-solid fa-upload me-1"></i>Upload a picture</button>
                                    </div>
                                    <div class="form-text d-none" id="prf_upload_hint">PNG, JPG or WEBP, up to 2 MB. A square picture looks best.</div>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                                <h4 class="fw-bold mb-0">Cartoon avatars</h4>
                                <div class="btn-group" role="group" id="prf_gender_filter">
                                    <input type="radio" class="btn-check" name="prf_gender" id="prf_gender_all" value="all" checked />
                                    <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-light-primary" for="prf_gender_all">All</label>
                                    <input type="radio" class="btn-check" name="prf_gender" id="prf_gender_male" value="male" />
                                    <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-light-primary" for="prf_gender_male"><i class="fa-solid fa-person me-1"></i>Male</label>
                                    <input type="radio" class="btn-check" name="prf_gender" id="prf_gender_female" value="female" />
                                    <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-light-primary" for="prf_gender_female"><i class="fa-solid fa-person-dress me-1"></i>Female</label>
                                </div>
                            </div>
                            <div class="prf-avatar-grid" id="prf_avatar_grid"></div>
                        </div>

                        <!-- Contact -->
                        <div class="tab-pane fade" id="prf_tab_contact" role="tabpanel">
                            <div class="mw-700px">
                                <h4 class="fw-bold mb-1">Email address</h4>
                                <div class="text-muted fs-7 mb-4">Used for sign-in help and notices. A new address must be confirmed before it is used.</div>
                                <div class="fs-5 fw-semibold mb-4"><i class="fa-solid fa-envelope text-primary me-2"></i><span id="prf_email_current"></span></div>
                                <div id="prf_email_pending"></div>
                                <div id="prf_email_form">
                                    <div class="row g-4">
                                        <div class="col-12 col-md-6">
                                            <label class="form-label" for="prf_new_email">New email address</label>
                                            <input type="email" class="form-control form-control-solid" id="prf_new_email" maxlength="100" autocomplete="off" />
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="form-label" for="prf_email_password">Your password</label>
                                            <input type="password" class="form-control form-control-solid" id="prf_email_password" maxlength="20" autocomplete="current-password" />
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button type="button" class="btn btn-primary" id="prf_change_email">
                                            <span class="indicator-label">Change email</span>
                                            <span class="indicator-progress">Please wait… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                        </button>
                                    </div>
                                </div>

                                <div class="separator my-8"></div>
                                <h4 class="fw-bold mb-1">Mobile number</h4>
                                <div class="text-muted fs-7 mb-4">A one-time code is emailed to you to confirm a new number.</div>
                                <div class="fs-5 fw-semibold mb-4"><i class="fa-solid fa-mobile-screen text-primary me-2"></i><span id="prf_phone_current"></span></div>
                                <div class="row g-4">
                                    <div class="col-4 col-md-2">
                                        <label class="form-label" for="prf_country_code">Code</label>
                                        <div class="input-group"><span class="input-group-text">+</span><input type="text" class="form-control form-control-solid" id="prf_country_code" maxlength="4" inputmode="numeric" /></div>
                                    </div>
                                    <div class="col-8 col-md-4">
                                        <label class="form-label" for="prf_phone">New mobile number</label>
                                        <input type="text" class="form-control form-control-solid" id="prf_phone" maxlength="15" inputmode="numeric" />
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label" for="prf_phone_password">Your password</label>
                                        <input type="password" class="form-control form-control-solid" id="prf_phone_password" maxlength="20" autocomplete="current-password" />
                                    </div>
                                </div>
                                <div class="d-flex justify-content-end mt-4">
                                    <button type="button" class="btn btn-primary" id="prf_change_phone">
                                        <span class="indicator-label">Change mobile</span>
                                        <span class="indicator-progress">Please wait… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Security -->
                        <div class="tab-pane fade" id="prf_tab_security" role="tabpanel">
                            <div class="mw-700px">
                                <h4 class="fw-bold mb-1">Change password</h4>
                                <div class="text-muted fs-7 mb-5">3 to 20 characters: letters, digits and _ @ #. You get an email after every change.</div>
                                <div class="mb-4">
                                    <label class="form-label" for="prf_pw_current">Current password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-solid" id="prf_pw_current" maxlength="20" autocomplete="current-password" />
                                        <button type="button" class="btn btn-light" data-prf-eye="prf_pw_current" title="Show / hide"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label" for="prf_pw_new">New password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-solid" id="prf_pw_new" maxlength="20" autocomplete="new-password" />
                                        <button type="button" class="btn btn-light" data-prf-eye="prf_pw_new" title="Show / hide"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="prf-strength flex-grow-1"><div class="prf-strength-bar" id="prf_strength_bar"></div></div>
                                    <span class="fs-7 fw-semibold text-muted text-nowrap" id="prf_strength_text">&nbsp;</span>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label" for="prf_pw_confirm">Confirm new password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control form-control-solid" id="prf_pw_confirm" maxlength="20" autocomplete="new-password" />
                                        <button type="button" class="btn btn-light" data-prf-eye="prf_pw_confirm" title="Show / hide"><i class="fa-solid fa-eye"></i></button>
                                    </div>
                                    <div class="form-text text-danger d-none" id="prf_pw_mismatch">The passwords do not match.</div>
                                </div>
                                <label class="form-check form-check-custom form-check-solid mb-6">
                                    <input class="form-check-input" type="checkbox" id="prf_sign_out_others" checked />
                                    <span class="form-check-label">Sign out all my other devices and browsers</span>
                                </label>
                                <div class="d-flex justify-content-end">
                                    <button type="button" class="btn btn-primary" id="prf_change_password">
                                        <span class="indicator-label">Change password</span>
                                        <span class="indicator-progress">Please wait… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::My Profile-->

<!-- Mobile number: one-time code -->
<div class="modal fade" tabindex="-1" id="prf_otp_modal" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-10 text-center">
                <i class="fa-solid fa-envelope-open-text fs-3x text-primary mb-4"></i>
                <div class="fs-3 fw-bold mb-2">Enter the code</div>
                <div class="text-muted mb-6" id="prf_otp_text">A one-time code was emailed to you.</div>
                <input type="text" class="form-control form-control-solid text-center fs-2 fw-bold mx-auto mw-200px" id="prf_otp" maxlength="5" inputmode="numeric" autocomplete="one-time-code" />
                <div class="mt-4 fs-7"><a href="#" id="prf_otp_resend">Send the code again</a> <span class="text-muted" id="prf_otp_wait"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="prf_otp_submit">Confirm</button>
            </div>
        </div>
    </div>
</div>

<input type="file" class="d-none" id="prf_file" accept=".png,.jpg,.jpeg,.webp,image/png,image/jpeg,image/webp" />

<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js?v=<?= @filemtime( $prf_js ) ?>"></script>
