<?php 
$name = "";
$email = " - - ";
/*
if( isset( $_SESSION[ 'fname' ] ) ){
    if( $_SESSION[ 'fname' ] !== "" )
        $name = $_SESSION[ 'fname' ] . " ";
}
if( isset( $_SESSION[ 'lname' ] ) ){
    if( $_SESSION[ 'lname' ] !== "" )
        $name .= $_SESSION[ 'lname' ];
}
if( trim( $name ) === "" ){
    $name = "Guest";
}
*/

if( !isValidJWT( $globalAccessToken ) ){
    sign_out();
    exit();
}

$payload = getJWTPayload( $_COOKIE[ TOKEN_NAME ] );
//print_r( $payload );
if( isset( $payload[ 'fname' ] ) ){
    if( $payload[ 'fname' ] !== "" )
        $name = $payload[ 'fname' ] . " ";
}
if( isset( $payload[ 'lname' ] ) ){
    if( $payload[ 'lname' ] !== "" )
        $name .= $payload[ 'lname' ];
}
if( trim( $name ) === "" ){
    $name = "Guest";
}


if( isset( $_SESSION[ 'email' ] ) ){
    if( $_SESSION[ 'email' ] !== "" )
        $email = $_SESSION[ 'email' ];
}

?>
                    <!--begin::Header-->
                    <div id="kt_header" style="" class="header align-items-stretch">
                        <!--begin::Container-->
                        <div class="container-fluid d-flex align-items-stretch justify-content-between">
                            <!--begin::Aside mobile toggle-->
                            <div class="d-flex align-items-center d-lg-none ms-n4 me-1" title="Show aside menu">
                                <div class="btn btn-icon btn-active-color-white" id="kt_aside_mobile_toggle">
                                    <i class="ki-outline ki-burger-menu fs-1"></i>
                                </div>
                            </div>
                            <!--end::Aside mobile toggle-->
                            <!--begin::Mobile logo-->
                            <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
                                <a href="index.html" class="d-lg-none">
                                    <img alt="Logo" src="assets/media/logos/demo13-small.svg" class="h-25px" />
                                </a>
                            </div>
                            <!--end::Mobile logo-->
                            <!--begin::Wrapper-->
                            <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">

                                <!--begin::Navbar-->
                                <div class="d-flex align-items-stretch" id="kt_header_nav"></div>
                                <!--end::Navbar-->

                                <!--begin::Toolbar wrapper-->
                                <div class="topbar d-flex align-items-stretch flex-shrink-0">

                                    <!--begin::User-->
                                    <div class="d-flex align-items-stretch" id="kt_header_user_menu_toggle">
                                        <!--begin::Menu wrapper-->
                                        <div class="topbar-item cursor-pointer symbol px-3 px-lg-5 me-n3 me-lg-n5 symbol-30px symbol-md-35px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" data-kt-menu-flip="bottom">
                                            <img src="assets/scodezy/media/avatar/myprofile.png" alt="metronic" />
                                        </div>
                                        <!--begin::User account menu-->
                                        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-3">
                                                <div class="menu-content d-flex align-items-center px-3">
                                                    <!--begin::Avatar-->
                                                    <div class="symbol symbol-50px me-5">
                                                        <img alt="Logo" src="assets/scodezy/media/avatar/myprofile.png" />
                                                    </div>
                                                    <!--end::Avatar-->
                                                    <!--begin::Username-->
                                                    <div class="d-flex flex-column">
                                                        <div class="fw-bold d-flex flex-column overflow-wrap-break-word fs-5" style="max-width: 175px"><?=$name ?></div>
                                                        <a href="#" class="fw-semibold text-muted text-hover-primary fs-7 overflow-wrap-anywhere"><?=$email ?></a>
                                                    </div>
                                                    <!--end::Username-->
                                                </div>
                                            </div>
                                            <!--end::Menu item-->
                                            <!--begin::Menu separator-->
                                            <div class="separator my-2"></div>
                                            <!--end::Menu separator-->
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-5">
                                                <a href="<?=WEBSITE_ADMINPANEL_URL ?>?what_do_you_want=profile" class="menu-link px-5">My Profile</a>
                                            </div>
                                            <!--end::Menu item-->
                                            <!--begin::Menu item-->
                                            <div class="menu-item px-5">
                                                <a href="#" id="btn_sign_out" class="menu-link px-5">Sign Out</a>
                                            </div>
                                            <!--end::Menu item-->
                                        </div>
                                        <!--end::User account menu-->
                                        <!--end::Menu wrapper-->
                                    </div>
                                    <!--end::User -->
                                    <!--begin::Heaeder menu toggle-->
                                    <div class="d-flex align-items-stretch d-lg-none px-3 me-n3" title="Show header menu">
                                        <div class="topbar-item" id="kt_header_menu_mobile_toggle">
                                            <i class="ki-outline ki-burger-menu-2 fs-1"></i>
                                        </div>
                                    </div>
                                    <!--end::Heaeder menu toggle-->
                                </div>
                                <!--end::Toolbar wrapper-->
                            </div>
                            <!--end::Wrapper-->
                        </div>
                        <!--end::Container-->
                    </div>
                    <!--end::Header-->
                    
                    <script type="text/javascript">
                    var btn_sign_out = getElementByID( 'btn_sign_out' );
                    btn_sign_out.on( 'click', function( e ){
                        e.preventDefault();
                        scodezy_sign_out();     // Defined in core.js
                    });
                    </script>