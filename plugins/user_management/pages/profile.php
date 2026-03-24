<?php 
checkAuthorizationForPage( 'profile' );
?>

<link href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css" rel="stylesheet" type="text/css" />

<!--begin::Post-->
<div class="main-post post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
        <!--begin::Navbar-->
        <div class="card mb-5 mb-xl-10 settings-tab-navbar">
            <div class="card-body pt-0 pb-0">                        
                <!--begin::Navs-->
                <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-5 fw-bold">
                    <!--begin::Nav item-->
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 setting-tabs" data-tabname="overview" href="#">Overview</a>
                    </li>
                    <!--end::Nav item-->
                    <!--begin::Nav item-->
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 setting-tabs" data-tabname="contact" href="#">Contact</a>
                    </li>
                    <!--end::Nav item-->
                    <!--begin::Nav item-->
                    <li class="nav-item mt-2">
                        <a class="nav-link text-active-primary ms-0 me-10 py-5 setting-tabs" data-tabname="security" href="#">Security</a>
                    </li>
                    <!--end::Nav item-->
                </ul>
                <!--begin::Navs-->
            </div>
        </div>
        <!--end::Navbar-->
        
        <div class="card mb-5 mb-xl-10" id="details_card">
            <div class="settings overview" data-tabname="overview">
                <div class="profile_details_view">
                    <!--begin::Card header-->
                    <div class="card-header cursor-pointer">
                        <!--begin::Card title-->
                        <div class="card-title m-0">
                            <h3 class="fw-bold m-0">Profile Details</h3>
                        </div>
                        <!--end::Card title-->
                        <!--begin::Action-->
                        <a href="#" class="btn btn-sm btn-primary align-self-center" id="btn_edit_profile">Edit Profile</a>
                        <!--end::Action-->
                    </div>
                    <!--end::Card header-->
                    <!--begin::Card body-->
                    <div class="card-body p-9">
                        <!--begin::Row-->
                        <div class="row mb-7">
                            <!--begin::Label-->
                            <label class="col-lg-4 fw-semibold text-muted">User ID</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8">
                                <span class="fw-bold fs-6 text-gray-800" id="user_id_v">{user_id}</span>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Row-->
                        <!--begin::Input group-->
                        <div class="row mb-7">
                            <!--begin::Label-->
                            <label class="col-lg-4 fw-semibold text-muted">First Name</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8 fv-row">
                                <span class="fw-semibold text-gray-800 fs-6" id="fname_v">{fname}</span>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="row mb-7">
                            <!--begin::Label-->
                            <label class="col-lg-4 fw-semibold text-muted">Last Name</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8 fv-row">
                                <span class="fw-semibold text-gray-800 fs-6" id="lname_v">{lname}</span>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="row mb-7">
                            <!--begin::Label-->
                            <label class="col-lg-4 fw-semibold text-muted">Nickname</label>
                            <!--end::Label-->
                            <!--begin::Col-->
                            <div class="col-lg-8">
                                <span class="fw-semibold text-gray-800 fs-6" id="nickname_v">{nickname}</span>
                            </div>
                            <!--end::Col-->
                        </div>
                        <!--end::Input group-->

                        
                        
                    </div>
                    <!--end::Card body-->
                </div>
                
                <div class="profile_details_edit">
                    <form name="form_edit_profile_details" id="form_edit_profile_details" method="POST" data-parsley-validate>
                        <!--begin::Card header-->
                        <div class="card-header cursor-pointer">
                            <!--begin::Card title-->
                            <div class="card-title m-0">
                                <h3 class="fw-bold m-0">Profile Details</h3>
                            </div>
                            <!--end::Card title-->
                        </div>
                        <!--end::Card header-->
                        <!--begin::Card body-->
                        <div class="card-body p-9">
                        
                            <!--begin::Input group-->
                            <div class="row mb-6">
                                
                                <input type="text" name="user_id_e" id="user_id_e" class="hidden" value="" />
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">First Name</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <!--begin::Row-->
                                    <div class="row">
                                        <input type="text" name="fname_e" id="fname_e" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" placeholder="Your first name"
                                                required="required"
                                                data-parsley-errors-container="#input_first_name_error" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FIRST_NAME' ) ?>"
                                                data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_FIRST_NAME' ) ?>"
                                                data-parsley-required="true"
                                                data-parsley-required-message="Please enter your first name"
                                                data-parsley-trigger="keyup blur" />
                                        <div id="input_first_name_error" class="scodezy-error-msg"></div>
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Last Name</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <!--begin::Row-->
                                    <div class="row">
                                        <input type="text" name="lname_e" id="lname_e" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" placeholder="Your last name"
                                                required="required"
                                                data-parsley-errors-container="#input_last_name_error" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_LAST_NAME' ) ?>"
                                                data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_LAST_NAME' ) ?>"
                                                data-parsley-required="true"
                                                data-parsley-required-message="Please enter your last name"
                                                data-parsley-trigger="keyup blur" />
                                        <div id="input_last_name_error" class="scodezy-error-msg"></div>
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="row mb-6">
                                <!--begin::Label-->
                                <label class="col-lg-4 col-form-label required fw-semibold fs-6">Nickname</label>
                                <!--end::Label-->
                                <!--begin::Col-->
                                <div class="col-lg-8">
                                    <!--begin::Row-->
                                    <div class="row">
                                        <input type="text" name="nickname_e" id="nickname_e" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" placeholder="Your nickname"
                                                required="required"
                                                data-parsley-errors-container="#input_nick_name_error" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_NICK_NAME' ) ?>"
                                                data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_NICK_NAME' ) ?>"
                                                data-parsley-required="true"
                                                data-parsley-required-message="Please enter your nickname"
                                                data-parsley-trigger="keyup blur" />
                                        <div id="input_nick_name_error" class="scodezy-error-msg"></div>
                                    </div>
                                    <!--end::Row-->
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Input group-->
                       
                        </div>
                        <!--end:: Card Body -->
                        <!--begin::Actions-->
                        <div class="card-footer d-flex justify-content-end py-6 px-9">
                            <button type="button" class="btn btn-light btn-active-light-primary me-2" id="btn_cancel_edit_profile">Cancel</button>
                            <button type="button" class="btn btn-primary" id="btn_save_profile_details">Save Changes</button>
                        </div>
                        <!--end::Actions-->
                    </form>
                </div>
                
            </div>
            
            <div class="settings contact" data-tabname="contact">
                <!--begin::Card header-->
                <div class="card-header cursor-pointer">
                    <!--begin::Card title-->
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Contact Information</h3>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--begin::Card header-->
                <!--begin::Card body-->
                <div class="card-body p-9">
                    <!--begin::Email Address-->
                    <div class="d-flex flex-wrap align-items-center">
                        <!--begin::Label-->
                        <div id="contact_email_v">
                            <div class="fs-6 fw-bold mb-1" id="email_title_v">Email Address</div>
                            <div class="fw-semibold text-gray-600" id="email_v">{email}</div>
                        </div>
                        <!--end::Label-->
                        <!--begin::Edit-->
                        <div id="contact_email_e" class="flex-row-fluid d-none">
                            <!--begin::Form-->
                            <form id="form_update_email" name="form_update_email" class="form" autocomplete="off" data-parsley-validate>
                                <div class="row mb-6">
                                    <div class="col-lg-6 mb-4 mb-lg-0">
                                        <div class="fv-row mb-0">
                                            <label for="emailaddress" class="form-label fs-6 fw-bold mb-3">Enter New Email Address</label>
                                            <input type="email" class="form-control form-control-lg form-control-solid" id="email_e" name="email_e" placeholder="Email Address" required="required" autocomplete="off"
                                                data-parsley-errors-container="#input_email_e_error" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_EMAIL' ) ?>"
                                                data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_EMAIL' ) ?>"
                                                data-parsley-required="true"
                                                data-parsley-required-message="Please enter your email"
                                                data-parsley-trigger="keyup blur" />
                                            <div id="input_email_e_error" class="scodezy-error-msg"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="fv-row mb-0">
                                            <label for="confirmemailpassword" class="form-label fs-6 fw-bold mb-3">Confirm Password</label>
                                            <input type="password" class="form-control form-control-lg form-control-solid" name="password_e" id="password_e" required="required" autocomplete="off"
                                                placeholder="Enter your password"
                                                data-parsley-errors-container="#input_password_e_error" 
                                                data-parsley-required="true"
                                                data-parsley-required-message="Please enter your password"
                                                data-parsley-trigger="keyup blur" />
                                            <div id="input_password_e_error" class="scodezy-error-msg"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <button id="btn_update_email" type="button" class="btn btn-primary me-2 px-6">Update Email</button>
                                    <button id="btn_cancel_update_email" type="button" class="btn btn-color-gray-500 btn-active-light-primary px-6">Cancel</button>
                                </div>
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Edit-->
                        <!--begin::Action-->
                        <div id="div_change_email_button" class="ms-auto">
                            <button class="btn btn-light btn-active-light-primary" id="btn_change_email">Change Email</button>
                        </div>
                        <!--end::Action-->
                    </div>
                    <!--end::Email Address-->

                    <!--begin::Separator-->
                    <div class="separator separator-dashed my-6"></div>
                    <!--end::Separator-->

                    <!--begin::Mobile -->
                    <div class="d-flex flex-wrap align-items-center">
                        <!--begin::Label-->
                        <div id="contact_phone_v">
                            <div class="fs-6 fw-bold mb-1" id="phone_title_v">Mobile</div>
                            <div class="fw-semibold text-gray-600"><span id="country_code_v">+91</span><span id="phone_v">966432 7699</span></div>
                        </div>
                        <!--end::Label-->
                        <!--begin::Edit-->
                        <div id="contact_phone_e" class="flex-row-fluid d-none">
                            <!--begin::Form-->
                            <form id="form_update_phone" name="form_update_phone" class="form" novalidate="novalidate">
                                <div class="row mb-6">
                                    <div class="col-lg-2 mb-4 mb-lg-0">
                                        <div class="fv-row mb-0">
                                            <label for="select_country_code_e" class="form-label fs-6 fw-bold mb-3">Country Code</label>
                                            <select id="select_country_code_e" name="select_country_code_e" class="form-select form-select-lg form-select-solid" data-control="select2">
                                                <option value="65" selected>+65</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="fv-row mb-0">
                                            <label for="phone" class="form-label fs-6 fw-bold mb-3">New Mobile Number</label>
                                            <input type="text" class="form-control form-control-lg form-control-solid" id="phone_e" name="phone_e" placeholder="Enter your mobile number" required="required" autocomplete="off"
                                                data-parsley-errors-container="#input_phone_e_error" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_DIGITS' ) ?>"
                                                data-parsley-error-message="Entered mobile number is invalid"
                                                data-parsley-required="true"
                                                data-parsley-required-message="Please enter your contact number"
                                                data-parsley-trigger="keyup blur" />
                                            <div id="input_phone_e_error" class="scodezy-error-msg"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="fv-row mb-0">
                                            <label for="phone_password_e" class="form-label fs-6 fw-bold mb-3">Confirm Password</label>
                                            <input type="password" class="form-control form-control-lg form-control-solid" name="phone_password_e" id="phone_password_e" required="required" autocomplete="off"
                                                placeholder="Enter your password"
                                                data-parsley-errors-container="#input_phone_password_e_error" 
                                                data-parsley-required="true"
                                                data-parsley-required-message="Please enter your password"
                                                data-parsley-trigger="keyup blur" />
                                            <div id="input_phone_password_e_error" class="scodezy-error-msg"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex">
                                    <button id="btn_update_phone" type="button" class="btn btn-primary me-2 px-6">Update</button>
                                    <button id="btn_cancel_update_phone" type="button" class="btn btn-color-gray-500 btn-active-light-primary px-6">Cancel</button>
                                </div>
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Edit-->
                        <!--begin::Action-->
                        <div id="div_change_phone" class="ms-auto">
                            <button class="btn btn-light btn-active-light-primary" id="btn_change_phone">Change Mobile</button>
                        </div>
                        <!--end::Action-->
                    </div>
                    <!--end::Mobile-->

                </div>
                <!--end::Card body-->
            </div>

            <div class="settings security" data-tabname="security">

                <!--begin::Card header-->
                <div class="card-header cursor-pointer">
                    <!--begin::Card title-->
                    <div class="card-title m-0">
                        <h3 class="fw-bold m-0">Security</h3>
                    </div>
                    <!--end::Card title-->
                </div>
                <!--begin::Card header-->

                <!--begin::Card body-->
                <div class="card-body p-9">
                    <!--begin::Password-->
                    <div class="d-flex flex-wrap align-items-center mb-10">
                        <!--begin::Label-->
                        <div id="p_password_v">
                            <div class="fs-6 fw-bold mb-1">Password</div>
                            <div class="fw-semibold text-gray-600">************</div>
                        </div>
                        <!--end::Label-->
                        <!--begin::Edit-->
                        <div id="div_p_password_e" class="flex-row-fluid d-none">
                            <!--begin::Form-->
                            <form id="form_change_self_password" name="form_change_self_password" class="form" data-parsley-validate>
                                <div class="row mb-1">
                                    <div class="col-lg-4">
                                        <div class="fv-row mb-0">
                                            <label for="p_password_e" class="form-label fs-6 fw-bold mb-3">Current Password</label>
                                            <input type="password" class="form-control form-control-lg form-control-solid" name="p_password_e" id="p_password_e" required="required" autocomplete="off" 
                                                    placeholder="Enter your current password"
                                                    data-parsley-errors-container="#input_p_password_e_error" 
                                                    data-parsley-required="true"
                                                    data-parsley-required-message="Please enter your current password"
                                                    data-parsley-trigger="keyup blur" />
                                            <div id="input_p_password_e_error" class="scodezy-error-msg"></div>
                                        </div>
                                    </div>                                    
                                    <div class="col-lg-4">
                                        <div class="fv-row mb-0">
                                            <label for="p_new_password_e" class="form-label fs-6 fw-bold mb-3">New Password</label>
                                            <input type="password" class="form-control form-control-lg form-control-solid" name="p_new_password_e" id="p_new_password_e" required="required" autocomplete="off" 
                                                    placeholder="Enter new password"
                                                    data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PASSWORD' ) ?>"
                                                    data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_PASSWORD' ) ?>"
                                                    data-parsley-errors-container="#input_p_new_password_e_error" 
                                                    data-parsley-required="true"
                                                    data-parsley-required-message="Please enter new password"
                                                    data-parsley-trigger="keyup blur" />
                                            <div id="input_p_new_password_e_error" class="scodezy-error-msg"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="fv-row mb-0">
                                            <label for="p_confirm_password_e" class="form-label fs-6 fw-bold mb-3">Confirm New Password</label>
                                            <input type="password" class="form-control form-control-lg form-control-solid" name="p_confirm_password_e" id="p_confirm_password_e" required="required" autocomplete="off" 
                                                    placeholder="Confirm new password"
                                                    data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PASSWORD' ) ?>"
                                                    data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_PASSWORD' ) ?>"
                                                    data-parsley-errors-container="#input_p_confirm_password_e_error" 
                                                    data-parsley-required="true"
                                                    data-parsley-required-message="Please confirm your new password"
                                                    data-parsley-trigger="keyup blur" />
                                            <div id="input_p_confirm_password_e_error" class="scodezy-error-msg"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex mt-3" id="div_update_password">
                                    <button id="btn_update_password" type="button" class="btn btn-primary me-2 px-6 animated-button">Update Password</button>
                                    <button id="btn_cancel_update_password" type="button" class="btn btn-color-gray-500 btn-active-light-primary px-6">Cancel</button>
                                </div>
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Edit-->
                        <!--begin::Action-->
                        <div id="div_change_password" class="ms-auto">
                            <button class="btn btn-light btn-active-light-primary animated-button" id="btn_change_password">Change Password</button>
                        </div>
                        <!--end::Action-->
                    </div>
                    <!--end::Password-->




                </div>
                <!--end::Card body-->
            </div>
                       
        </div>

    </div>
    <!--end::Container-->
</div>
<!--end::Post-->

<div class="modal fade" tabindex="-1" id="modal_otp" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" >
    <div class="modal-dialog">
        <div class="modal-content rounded">
            <div class="modal-body p-20 text-center">
                <i class="fa-solid fa-circle-info fs-5x text-info "> </i>
                <p class="modal-otp-title ">One Time Password</p>
                <div class="text-center mt-7">
                    <p class="modal-otp-message">Please enter the OTP received on your mobile number 9109 3387 <br />
                    <input type="text" id="otp_phone_update" name="otp_phone_update" class="form-control w-275px mt-3 input-otp" maxlength="5">
                    <p class="otp-resend-info mt-2">Please wait for <span class="seconds">{seconds}</span> seconds before requesting for a new OTP again</p>
                    <p class="otp-request-again mt-2 hidden d-none">Did not receive the OTP ? <a id="otp_request_again" href="#">Request OTP again</a></p>
                    <p class="otp-invalid mt-2 hidden d-none"></p>
                </div>
            </div>

            <div class="modal-footer">
                <button type="reset" class="btn btn-default" data-bs-dismiss="modal" id="btn_cancel_phone_update_otp">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_submit_phone_update_otp">Submit</button>
            </div>
        </div>
    </div>
</div>


 <script src="assets/scodezy/js/moment/moment.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>