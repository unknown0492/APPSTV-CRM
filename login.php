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
        <title>Sign In | <?=$site_config->site_name ?></title>
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
        <link href="assets/scodezy/css/login.css" rel="stylesheet" type="text/css" />
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
                            <form class="form w-100" id="form_login" name="form_login" method="POST" data-parsley-validate>
                                <!--begin::Heading-->
                                <div class="text-center mb-11">
                                    <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
                                    <div class="text-gray-500 fw-semibold fs-6">Account Management Panel</div>
                                </div>
                                <!--end::Heading-->

                                <!--begin::Input group=-->
                                <div class="fv-row mb-8">
                                    <input type="text" placeholder="User ID" name="user_id" id="user_id" autocomplete="on" class="form-control bg-transparent"
                                    	   required="required"
					   data-parsley-required="true"
                                           data-parsley-required-message="Please enter the User ID"
					   data-parsley-errors-container="#input_user_id_error" 
					   data-parsley-trigger="keyup" />
                                    <div id="input_user_id_error" class="scodezy-error-msg"></div>
                                </div>
                                <!--end::Input group=-->

                                <!--begin::Input group=-->
                                <div class="fv-row mb-3">
                                    <input type="password" placeholder="Password" name="password" id="password" autocomplete="off" class="form-control bg-transparent"
                                           required="required"
					   data-parsley-required="true"
                                           data-parsley-required-message="Please enter the Password"
					   data-parsley-errors-container="#input_password_error" 
					   data-parsley-trigger="keyup" />
                                    <div id="input_password_error" class="scodezy-error-msg"></div>
                                </div>
                                <!--end::Input group=-->

                                <?php if (isForgotPasswordOptionEnabled()) { ?>
                                <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                    <div></div>
                                    <a href="forgot-password.php" class="link-primary">Forgot Password ?</a>
                                </div>
                                <?php } ?>

                                <!--begin::Submit button-->
                                <div class="d-grid mb-10">
                                    <button type="submit" id="submit_login" class="btn btn-primary">
                                        <!--begin::Indicator label-->
                                        <span class="indicator-label">Sign In</span>
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
                                <div class="text-gray-500 text-center fw-semibold fs-6">Not a Member yet? 
                                    <a href="sign-up.php" class="link-primary">Sign up</a>
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
        
        <!--begin::Global Javascript Bundle(mandatory for all pages)-->
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="assets/js/scripts.bundle.js"></script>
        <script src="assets/scodezy/js/parsley/parsley.js"></script>
        <script src="assets/scodezy/js/noty/noty.js"></script>
        <script src="assets/scodezy/js/core.js"></script>
        <!--end::Global Javascript Bundle-->
        
        <!--begin::Custom Javascript(used for this page only)-->
        <script src="assets/scodezy/js/login/login.js"></script>
        <!-- <script src="assets/js/custom/authentication/sign-in/general.js"></script> -->
        <!--end::Custom Javascript-->
        
        <!--end::Javascript-->
    </body>
    <!--end::Body-->
</html>