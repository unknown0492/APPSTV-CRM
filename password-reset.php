<?php
session_start();

require './load-all.php';

if( !isForgotPasswordOptionEnabled() ) {
    redirect(PAGE_NAME_LOGIN . WEBSITE_LINK_ENDS_WITH);
    exit();
}


if( !isset( $_GET[ 'code' ] ) ){
    redirect(PAGE_NAME_LOGIN . WEBSITE_LINK_ENDS_WITH);
    exit();
}

$password_reset_code = @$_GET[ 'code' ];

$e_password_reset_code = escape_string( $password_reset_code );

// Check if the password_reset_code is valid
$sql = "SELECT user_id, password_reset_expiry, email FROM users WHERE password_reset_code='$e_password_reset_code'";
$result_set = selectQuery( $sql );
if( ($result_set === NULL) 
        || (mysqli_num_rows( $result_set ) == 0) ){
    alert( "Password Reset Link has been expired, please redo the password reset process again on the Forgot Password Page" );
    redirect( PAGE_LOGIN );
    exit();
}

$val = mysqli_fetch_object( $result_set );
$password_reset_expiry = $val->password_reset_expiry;
$current_time = currentTimeMilliseconds();

if( isPasswordResetValidityExpired( $current_time, $password_reset_expiry ) ){
    alert( "Password Reset Link has been expired, please redo the password reset process again on the Forgot Password Page" );
    redirect( PAGE_LOGIN );
    exit();
}


// Store User ID and password reset code in session for extra safety
$_SESSION[ 'code' ] = $e_password_reset_code;

?>
<html lang="en">
    <!--begin::Head-->
    <head>
        <title>Password Reset | <?=$site_config->site_name ?></title>
        <meta charset="utf-8" />

        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
        <!--begin::Fonts(mandatory for all pages)-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
        <!--end::Fonts-->

        <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/scodezy/css/core.css" rel="stylesheet" type="text/css" />
        <!--end::Global Stylesheets Bundle-->
        
        <!--begin::Page Level Stylesheets-->
        <link href="assets/scodezy/css/password-reset.css" rel="stylesheet" type="text/css" />
        <!--end::Page Level Stylesheets-->

    </head>
    <!--end::Head-->
    
    <!--begin::Body-->
    <body id="" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
        <!--begin::Theme mode setup on page load-->
        <!-- <script>var defaultThemeMode = "light"; var themeMode; if ( document.documentElement ) { if ( document.documentElement.hasAttribute("data-bs-theme-mode")) { themeMode = document.documentElement.getAttribute("data-bs-theme-mode"); } else { if ( localStorage.getItem("data-bs-theme") !== null ) { themeMode = localStorage.getItem("data-bs-theme"); } else { themeMode = defaultThemeMode; } } if (themeMode === "system") { themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"; } document.documentElement.setAttribute("data-bs-theme", themeMode); }</script> -->
        <!--end::Theme mode setup on page load-->
        
        <!--begin::Main-->
        <!--begin::Root-->
        <div class="d-flex flex-column flex-root">
            <!--begin::Authentication - Sign-in -->
            <div class="d-flex flex-column flex-column-fluid flex-lg-row">
                <!-- Begin:: Logo Section -->
                <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">
                    <div class="d-flex flex-center flex-lg-start flex-column">
                        <a href="index.html" class="mb-7">
                            <img alt="Logo" src="assets/scodezy/media/login/custom-3.svg" />
                        </a>
                        <h2 class="text-white fw-normal m-0">Build web applications seamlessly with sCodezy</h2>                        
                    </div>
                </div>
                <!-- End:: Logo Section -->
                
                <!--begin::Aside-->
                <!--begin::Body-->
                <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
                    <!--begin::Card-->
                    <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
                        <!--begin::Wrapper-->
                        <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20">
                            <!--begin::Form-->
                            <form class="form w-100" id="form_password_reset" name="form_password_reset" method="POST" data-parsley-validate>
                                <!--begin::Heading-->
                                <div class="text-center mb-11">
                                    <h1 class="text-gray-900 fw-bolder mb-3">Password Reset</h1>
                                    <div class="text-gray-500 fw-semibold fs-6">Set a new password of your choice</div>
                                </div>
                                <!--end::Heading-->

                                <!--begin::Input group=-->
                                <div class="fv-row mb-5">
                                    <input type="password" placeholder="Your preferred password*" name="password" id="password" autocomplete="off" class="form-control bg-transparent"
                                           required="required"
					   data-parsley-required="true"
                                           data-parsley-required-message="Please enter the Password"
					   data-parsley-errors-container="#input_password_error" 
                                           data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PASSWORD' ) ?>"
                                           data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_PASSWORD' ) ?>"
					   data-parsley-trigger="keyup blur" />
                                    <div id="input_password_error" class="scodezy-error-msg"></div>
                                </div>
                                <!--end::Input group=-->

                                <!--begin::Input group=-->
                                <div class="fv-row mb-10">
                                    <input type="password" placeholder="Re-enter password*" name="retype_password" id="retype_password" autocomplete="off" class="form-control bg-transparent"
                                           required="required"
					   data-parsley-required="true"
                                           data-parsley-required-message="Please re-enter the Password"
					   data-parsley-errors-container="#input_retype_password_error" 
                                           data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PASSWORD' ) ?>"
                                           data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_PASSWORD' ) ?>"
					   data-parsley-trigger="keyup blur" />
                                    <div id="input_retype_password_error" class="scodezy-error-msg"></div>
                                </div>
                                <!--end::Input group=-->

                                <!--begin::Submit button-->
                                <div class="d-grid mb-10">
                                    <button type="submit" id="submit_update_password" name="submit_update_password" class="btn btn-primary">
                                        <!--begin::Indicator label-->
                                        <span class="indicator-label">Update Password</span>
                                        <!--end::Indicator label-->
                                        
                                        <!--begin::Indicator progress-->
                                        <span class="indicator-progress">Please wait... 
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                        <!--end::Indicator progress-->
                                    </button>
                                </div>
                                <!--end::Submit button-->

                                
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Wrapper-->

                        <!--begin::Footer-->
                        <div class="text-center">
                            2024 © sCodezy by <a href="https://silentcoders.net" target="_blank">Silent Coders</a>
                        </div>
                        <!--end::Footer-->
                    </div>
                    <!--end::Card-->
                </div>
                <!--end::Body-->
            </div>
            <!--end::Authentication - Sign-in-->
        </div>
        <!--end::Root-->
        <!--end::Main-->
        
        <!--begin::Javascript-->
        
        <!--begin::Global Javascript Bundle(mandatory for all pages)-->
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="assets/js/scripts.bundle.js"></script>
        <script src="assets/scodezy/js/parsley/parsley.js"></script>
        <script src="assets/scodezy/js/noty/noty.js"></script>
        <script src="assets/scodezy/js/core.js"></script>
        <!--end::Global Javascript Bundle-->
        
        <!--begin::Custom Javascript(used for this page only)-->
        <script src="assets/scodezy/js/login/password-reset.js"></script>
        <!-- <script src="assets/js/custom/authentication/sign-in/general.js"></script> -->
        <!--end::Custom Javascript-->
        
        <!--end::Javascript-->
    </body>
    <!--end::Body-->
</html>