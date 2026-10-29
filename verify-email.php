<?php
/*
 * verify-email.php (Scodezy base, gen3 1.0.61) - the page the "Confirm my email" link opens (email change on My Profile,
 * link method). Public: the user may open it in any browser. The link carries the user and a random 40-character code;
 * a valid, unexpired code applies the change, and the old address gets a notice. Same look as the other sign-in pages.
 */
define( 'SCODEZY_NO_SESSION', true );                           // No PHP session needed here
require './load-all.php';
require_once PLU_PATH . '/user_management/webservice/scodezy_my_profile.php';   // Email change helpers

$site_config = getSiteConfig();
$signin = siteSigninPage( 'login' );                            // Common logo / background of the sign-in pages

$verify_user = isset( $_GET[ 'u' ] ) ? (string) $_GET[ 'u' ] : '';   // users.user_id from the link
$verify_code = isset( $_GET[ 'c' ] ) ? (string) $_GET[ 'c' ] : '';   // Random code from the link
$verify_ok   = false;                                           // Result shown on the page
$verify_text = "This confirmation link is not valid. Please change your email again on My Profile.";

if( preg_match( '/^[A-Za-z0-9_.\-@]{1,100}$/', $verify_user ) && preg_match( '/^[a-f0-9]{40}$/', $verify_code ) ){
    $e_verify_user = escape_string( $verify_user );
    $stored = (string) getUserMetaValue( $e_verify_user, _EMAIL_UPDATE_VERIFICATION_CODE );
    if( $stored !== '' && hash_equals( $stored, $verify_code ) ){
        $reason = profilePendingEmailValid( $verify_user );
        if( $reason !== '' ){
            $verify_text = $reason;
        }
        else{
            $new_email = (string) getUserMetaValue( $e_verify_user, _EMAIL_UPDATE_NEW_EMAIL );
            $error = profileApplyEmailChange( $verify_user );
            $verify_ok = ( $error === '' );
            $verify_text = $verify_ok ? "Your email address is now " . $new_email . "." : $error;
        }
    }
}
?>
<html lang="en">
    <head>
        <title>Email confirmation | <?= htmlspecialchars( $site_config->site_name ) ?></title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="robots" content="noindex" />
        <?php if( is_file( INC_PATH . '/favicon.php' ) ){ include INC_PATH . '/favicon.php'; } ?>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/scodezy/css/core.css" rel="stylesheet" type="text/css" />
        <?php siteSigninHead(); ?>
    </head>
    <body class="auth-bg bgi-size-cover bgi-attachment-fixed bgi-position-center bgi-no-repeat">
        <?php siteSigninBodyTop(); ?>
        <div class="d-flex flex-column flex-root" style="z-index: 9999;">
            <div class="d-flex flex-column flex-column-fluid flex-lg-row">
                <div class="d-flex flex-center w-lg-50 pt-15 pt-lg-0 px-10">
                    <div class="d-flex flex-center flex-lg-start flex-column">
                        <a href="" class="mb-7"><?php include INC_PATH . '/login-page-logo.php'; ?></a>
                        <?php include INC_PATH . '/system-descriptive-string.php'; ?>
                    </div>
                </div>
                <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12 p-lg-20">
                    <div class="bg-body d-flex flex-column align-items-stretch flex-center rounded-4 w-md-600px p-20">
                        <div class="d-flex flex-center flex-column flex-column-fluid px-lg-10 pb-15 pb-lg-20 text-center">
                            <div class="d-flex align-items-center justify-content-center rounded-circle mb-7 <?= $verify_ok ? 'bg-light-success' : 'bg-light-danger' ?>" style="width: 88px; height: 88px;">
                                <i class="fa-solid <?= $verify_ok ? 'fa-envelope-circle-check text-success' : 'fa-envelope-open text-danger' ?>" style="font-size: 36px;"></i>
                            </div>
                            <h1 class="text-gray-900 fw-bolder mb-3"><?= $verify_ok ? 'Email confirmed' : 'Not confirmed' ?></h1>
                            <div class="text-gray-600 fw-semibold fs-6 mb-10"><?= htmlspecialchars( $verify_text ) ?></div>
                            <a href="<?= htmlspecialchars( PAGE_LOGIN ) ?>" class="btn btn-primary">Continue to sign in</a>
                        </div>
                        <div class="text-center">
                            <?php siteSupportLine(); ?>
                            <span class="site-copyright"><?php include INC_PATH . "/copyright-string.php"; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="assets/js/scripts.bundle.js"></script>
        <?php siteSigninBodyBottom(); ?>
    </body>
</html>
