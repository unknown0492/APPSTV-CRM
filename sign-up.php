<?php

require './load-all.php';

if ( isLoggedIn() ) {
    redirect(PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH);
}

$site_config = getSiteConfig();
?>
<html lang="en">
    <!--begin::Head-->
    <head>
        <title>Sign Up | <?=$site_config->site_name ?></title>
        <meta charset="utf-8" />

        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <!-- Favicon - Begin -->
        <link rel="shortcut icon" href="assets/scodezy/media/site/favicon/favicon.ico" />
        <link rel="icon" href="assets/scodezy/media/site/favicon/favicon.png" type="image/png" sizes="256x256" />
        <link rel="apple-touch-icon"  href="assets/scodezy/media/site/favicon/favicon.png" sizes="256x256" />
        <!-- Favicon - End -->
        
        <!--begin::Fonts(mandatory for all pages)-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
        <!--end::Fonts-->

        <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/scodezy/css/core.css" rel="stylesheet" type="text/css" />
        <!--end::Global Stylesheets Bundle-->
        
        <!--begin::Page Level Stylesheets-->
        <link href="assets/scodezy/css/sign-up.css" rel="stylesheet" type="text/css" />
        <!--end::Page Level Stylesheets-->

    </head>
    <!--end::Head-->
    
    <!--begin::Body-->
    <body id="kt_body" class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
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
                            <?php 
                            include INC_PATH . '/login-page-logo.php';
                            ?>
                        </a>
                        <?php 
                        include INC_PATH . '/system-descriptive-string.php';
                        ?>                   
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
                            <form class="form w-100" id="form_sign_up" name="form_sign_up" method="POST" data-parsley-validate>
                                <!--begin::Heading-->
                                <div class="text-center mb-11">
                                    <h1 class="text-gray-900 fw-bolder mb-3">Sign Up</h1>
                                    <div class="text-gray-500 fw-semibold fs-6">Register to access the Administrator Panel</div>
                                    <div class="text-gray-500 fw-semibold fs-6">Fields marked with * are mandatory</div>
                                </div>
                                <!--end::Heading-->
                                
                                <!--begin::Input group=-->
                                <div class="fv-row mb-5">
                                    <input type="text" placeholder="Your first name*" name="first_name" id="first_name" autocomplete="off" class="form-control bg-transparent"
                                    	   required="required"
					   data-parsley-errors-container="#input_first_name_error" 
                                           data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FIRST_NAME' ) ?>"
                                           data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_FIRST_NAME' ) ?>"
					   data-parsley-required="true"
                                           data-parsley-required-message="Please enter your first name"
					   data-parsley-trigger="keyup blur" />
                                    <div id="input_first_name_error" class="scodezy-error-msg"></div>
                                </div>
                                <!--end::Input group=-->
                                
                                <!--begin::Input group=-->
                                <div class="fv-row mb-5">
                                    <input type="text" placeholder="Your last name*" name="last_name" id="last_name" autocomplete="off" class="form-control bg-transparent"
                                    	   required="required"
					   data-parsley-required="true"
                                           data-parsley-required-message="Please enter your last name"
					   data-parsley-errors-container="#input_last_name_error" 
                                           data-parsley-pattern="<?=getValidationRegex( 'VLDTN_LAST_NAME' ) ?>"
                                           data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_LAST_NAME' ) ?>"
					   data-parsley-trigger="keyup blur" />
                                    <div id="input_last_name_error" class="scodezy-error-msg"></div>
                                </div>
                                <!--end::Input group=-->
                                

                                <!--begin::Input group=-->
                                <div class="fv-row mb-5">
                                    <input type="text" placeholder="Choose a username*" name="user_id" id="user_id" autocomplete="off" class="form-control bg-transparent"
                                           style="color: inherit !important"
                                    	   required="required"
					   data-parsley-required="true"
                                           data-parsley-required-message="Please choose a username"
					   data-parsley-errors-container="#input_user_id_error" 
                                           data-parsley-pattern="<?=getValidationRegex( 'VLDTN_USER_ID' ) ?>"
                                           data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_USER_ID' ) ?>"32
					   data-parsley-trigger="keyup blur" />
                                    <div id="user_id_availability_msg" class="mt-1"></div>
                                    <div id="input_user_id_error" class="scodezy-error-msg"></div>
                                </div>
                                <!--end::Input group=-->
                                
                                <!--begin::Input group=-->
                                <div class="fv-row mb-5">
                                    <input type="text" placeholder="Your Email ID*" name="email" id="email" autocomplete="off" class="form-control bg-transparent"
                                    	   required="required"
					   data-parsley-required="true"
                                           data-parsley-required-message="Please enter your Email ID"
					   data-parsley-errors-container="#input_email_error" 
                                           data-parsley-pattern="<?=getValidationRegex( 'VLDTN_EMAIL' ) ?>"
                                           data-parsley-error-message="<?=getValidationErrMsg( 'VLDTN_EMAIL' ) ?>"
					   data-parsley-trigger="keyup blur" />
                                    <div id="input_email_error" class="scodezy-error-msg"></div>
                                </div>
                                <!--end::Input group=-->

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
                                
                                <!--begin::Accept-->
                                <div class="fv-row mb-10">
                                    <label class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="terms" id="terms" value="1" />
                                        <span class="form-check-label fw-semibold text-gray-700 fs-base ms-1">By signing up, I Accept the
                                        <a href="#" class="ms-1 link-primary">Terms</a></span>
                                    </label>
                                </div>
                                <!--end::Accept-->

                                <!--begin::Submit button-->
                                <div class="d-grid mb-10">
                                    <button type="button" id="btn_sign_up" name="btn_sign_up" class="btn btn-primary">
                                        <!--begin::Indicator label-->
                                        <span class="indicator-label">Sign Up</span>
                                        <!--end::Indicator label-->
                                        
                                        <!--begin::Indicator progress-->
                                        <span class="indicator-progress">Please wait... 
                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                        <!--end::Indicator progress-->
                                    </button>
                                </div>
                                <!--end::Submit button-->
                                
                                <?php if (isRegistrationOpen()) { ?>
                                <!--begin::Sign up-->
                                <div class="text-gray-500 text-center fw-semibold fs-6">Already have an account ?
                                    <a href="login.php" class="link-primary">Sign in</a>
                                </div>
                                <!--end::Sign up-->
                                <?php } ?>
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Wrapper-->

                        <!--begin::Footer-->
                        <div class="text-center">
                            <?php include INC_PATH . "/copyright-string.php"; ?>
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
        <script>var hostUrl = "assets/";</script>
        
        <!--begin::Global Javascript Bundle(mandatory for all pages)-->
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="assets/js/scripts.bundle.js"></script>
        <script src="assets/scodezy/js/parsley/parsley.js"></script>
        <script src="assets/scodezy/js/noty/noty.js"></script>
        <script src="assets/scodezy/js/core.js"></script>
        <!--end::Global Javascript Bundle-->
        
        <!--begin::Custom Javascript(used for this page only)-->
        <script src="assets/scodezy/js/login/sign-up.js"></script>
        <!-- <script src="assets/js/custom/authentication/sign-in/general.js"></script> -->
        <!--end::Custom Javascript-->
        
        <!--end::Javascript-->
    </body>
    <!--end::Body-->
</html>