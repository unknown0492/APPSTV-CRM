<?php
/*
 * Page error card of the admin panel (Scodezy base feature, 1.0.10) - shown in the content area, so the menu and
 * the top bar stay. adminpanel.php includes it with:
 *     $scodezy_page_error_code  403 = the role may not open the page (checkAuthorizationForPage), 404 = no such page
 *     $page_name                The requested page (what_do_you_want)
 */
$scodezy_page_error_title = '';                                  // Title of the requested page, when it exists
if( $scodezy_page_error_code === 403 && isset( $page_name ) && $page_name !== '' ){
    $e_error_page = escape_string( (string) $page_name );
    $error_page_rs = selectQuery( "SELECT page_title FROM pages WHERE page_name = '$e_error_page' LIMIT 1" );
    $error_page_row = $error_page_rs ? mysqli_fetch_assoc( $error_page_rs ) : null;
    $scodezy_page_error_title = $error_page_row ? (string) $error_page_row[ 'page_title' ] : '';
}
$scodezy_page_error_denied = ( $scodezy_page_error_code === 403 );  // Access denied (else: page not found)
?>
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-xxl">
        <div class="card">
            <div class="card-body d-flex flex-column align-items-center text-center py-15 px-5">
                <div class="d-flex align-items-center justify-content-center rounded-circle mb-7 <?= $scodezy_page_error_denied ? 'bg-light-danger' : 'bg-light-warning' ?>" style="width: 96px; height: 96px;">
                    <i class="fa-solid <?= $scodezy_page_error_denied ? 'fa-lock text-danger' : 'fa-compass text-warning' ?>" style="font-size: 40px;"></i>
                </div>
                <div class="text-muted fw-semibold fs-7 mb-2"><?= $scodezy_page_error_denied ? 'Error 403' : 'Error 404' ?></div>
                <h1 class="fw-bolder text-gray-900 mb-4"><?= $scodezy_page_error_denied ? 'Access denied' : 'Page not found' ?></h1>
                <div class="fs-5 text-gray-600 mb-10" style="max-width: 520px;">
                    <?php if( $scodezy_page_error_denied ){ ?>
                        You don't have permission to view
                        <?= $scodezy_page_error_title !== '' ? '<b>' . htmlspecialchars( $scodezy_page_error_title ) . '</b>' : 'this page' ?>.
                        If you need it for your work, ask your administrator to give your role access.
                    <?php } else { ?>
                        The page you are looking for does not exist or has been moved. Check the address, or use the menu.
                    <?php } ?>
                </div>
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="adminpanel.php" class="btn btn-primary"><i class="fa-solid fa-house me-2"></i>Back to Dashboard</a>
                    <a href="adminpanel.php" class="btn btn-light" onclick="if( window.history.length > 1 ){ window.history.back(); return false; }"><i class="fa-solid fa-arrow-left me-2"></i>Go back</a>
                </div>
            </div>
        </div>
    </div>
</div>
