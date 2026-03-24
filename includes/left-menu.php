<?php 
    checkIfLoggedIn();

    // Get the current functionality name in order to make its class Active
    $current = $_SERVER[ 'REQUEST_URI' ];
    $current = explode( "=", $current );
    $isActive = "";
    if( count( $current ) > 1 ){
        $isActive = "current ";
        $current = $current[ 1 ];
    }
    else{
        $current = "";
    }
    

    $site_config = getSiteConfig();
    $site_tagline  = $site_config->site_tagline;
	
    $sql = "Select *, functionality_name from pages, functionalities WHERE (pages.functionality_id=functionalities.functionality_id) ORDER BY page_sequence";
    $result_set = selectQuery( $sql );
    $hierarchy1 = array();
    $hierarchy2 = array();
    $hierarchy3 = array();
    while( ($val = mysqli_fetch_assoc( $result_set )) != NULL ){
        if( $val[ 'hierarchy' ] == "1" ){
            $hierarchy1[ $val[ 'page_id' ] ] = $val;
        }
        else if( $val[ 'hierarchy' ] == "2" ){
            $hierarchy2[ $val[ 'page_id' ] ] = $val;
        }
        else if( $val[ 'hierarchy' ] == "3" ){
            $hierarchy3[ $val[ 'page_id' ] ] = $val;
        }
    }
    
    //print_r( $hierarchy1 );
    //print_r( $hierarchy2 );
    //print_r( $hierarchy3 );
    
    //$pages = array();
    
    foreach ( $hierarchy3 as $key => $value ) {
        if( isset( $hierarchy2[ $value[ 'parent_id' ] ] ) ){
            $hierarchy2[ $value[ 'parent_id' ] ][ 'children' ][ $key ] = $value;
        }
    }
    
    //print_r( $hierarchy2 );

    foreach ( $hierarchy2 as $key => $value ) {
        if( isset( $hierarchy1[ $value[ 'parent_id' ] ] ) ){
            $hierarchy1[ $value[ 'parent_id' ] ][ 'children' ][ $key ] = $value;
        }
    }
    
    //print_r( $hierarchy2 );
    
    //print_r( $hierarchy1 );
    //echo json_encode( $hierarchy1 );
    //print_r( $hierarchy2 );
    //print_r( $hierarchy3 );
    
?>
                <input type="hidden" id="current_page_name" value="<?=$page_name ?>" />
                <!--begin::Aside-->
                <div id="kt_aside" class="aside aside-dark aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_mobile_toggle">
                    <!--begin::Brand-->
                    <div class="aside-logo flex-column-auto" id="kt_aside_logo">
                        <!--begin::Logo-->
                        <a href="<?=WEBSITE_ADMINPANEL_URL ?>">
                            <img alt="Logo" src="assets/scodezy/media/logo/logo-admin.png" class="logo" style="" />
                        </a>
                        <!--end::Logo-->
                        <!--begin::Aside toggler-->
                        <div id="kt_aside_toggle" class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle me-n2" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="aside-minimize">
                            <i class="ki-outline ki-double-left fs-1 rotate-180"></i>
                        </div>
                        <!--end::Aside toggler-->
                    </div>
                    <!--end::Brand-->
                    
                    <!--begin::Aside menu-->
                    <div class="aside-menu flex-column-fluid">
                        <!--begin::Aside Menu-->
                        <div class="hover-scroll-overlay-y" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer" data-kt-scroll-wrappers="#kt_aside_menu" data-kt-scroll-offset="0">
                            <!--begin::Menu-->
                            <div class="menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500" id="kt_aside_menu1" data-kt-menu="true">
                                
                                <!--begin:Menu item-->
                                <div class="menu-item" data-page-name="dashboard">
                                    
                                    <!--begin:Menu link-->
                                    <a class="menu-link <?=($current=="")?'active':'' ?>" href="<?=WEBSITE_ADMINPANEL_URL ?>">
                                        <span class="menu-icon">
                                            <i class="ki-outline ki-element-11 fs-2"></i>
                                        </span>
                                        <span class="menu-title">Dashboard</span>
                                    </a>
                                    <!--end:Menu link-->
                                    
                                </div>
                                <!--end:Menu item-->
                                <?php 
                                foreach ( $hierarchy1 as $page_id => $value ) {
                                    if( $value[ 'visible' ] == "0" || !hasAuthorization( $value[ 'functionality_name' ] ) )
                                        continue;
                                ?>
                                <!--begin:Menu item-->
                                <div class="menu-item pt-5">
                                    <!--begin:Menu content-->
                                    <div class="menu-content">
                                        <span class="menu-heading fw-bold text-uppercase fs-7"><?=$value[ 'page_title' ] ?></span>
                                    </div>
                                    <!--end:Menu content-->
                                </div>
                                <!--end:Menu item-->
                                    <?php 
                                    // If Hierarcy-2 has children
                                    if( isset( $value[ 'children' ] ) && (count($value[ 'children' ]) > 0) ){                                        
                                    $hierarchy2 = $value[ 'children' ];
                                    foreach ( $hierarchy2 as $k2 => $v2 ) {
                                        if( $v2[ 'visible' ] == "0" )
                                            continue;
                                     // echo json_encode( $hierarchy2 );
                                    //echo "Checking for Page ID - " . $v2[ 'page_id' ] . "\n";
                                        if( isset( $v2[ 'children' ] ) && (count($v2[ 'children' ]) > 0) ){
                                    ?>
                                    <!--begin:Menu item-->
                                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                                        <!--begin:Menu link-->
                                        <span class="menu-link">
                                            <span class="menu-icon">
                                                <i class="<?=$v2[ 'icon' ] ?>"></i>
                                            </span>
                                            <span class="menu-title"><?=$v2[ 'page_title' ] ?></span>
                                            <span class="menu-arrow"></span>
                                        </span>
                                        <!--end:Menu link-->
                                    
                                        <?php 
                                        
                                        
                                            $hierarchy3 = $v2[ 'children' ];
                                            foreach ( $hierarchy3 as $k3 => $v3 ){
                                                if( $v3[ 'visible' ] == "0" )
                                                    continue;
                                            
                                        ?>
                                            <!--begin:Hierarchy-3 Menu sub-->
                                            <div class="menu-sub menu-sub-accordion">
                                                <!--begin:Menu item-->
                                                <div class="menu-item" data-page-name="<?=$v3[ 'page_name' ] ?>">
                                                    <!--begin:Menu link-->
                                                    <a class="menu-link" href="<?=WEBSITE_PROTOCOL ?>://<?=$localDomainName . "/" . PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH ?>?what_do_you_want=<?=$v3[ 'page_name' ] ?>">
                                                        <span class="menu-bullet">
                                                            <span class="bullet bullet-dot"></span>
                                                        </span>
                                                        <span class="menu-title"><?=$v3[ 'page_title' ] ?></span>
                                                    </a>
                                                    <!--end:Menu link-->
                                                </div>
                                                <!--end:Menu item-->
                                            </div>
                                            <!--end:Hierarchy-3 Menu sub-->
                                        <?php 
                                            }
                                        ?>
                                        </div>
                                        <!--end:Menu item-->    
                                        <?php
                                        }
                                        else{
                                            // If Hierarchy-2 Does not have children, then Hierarchy-2 should be a Link
                                            //echo "abab";
                                        ?>
                                        <!--begin:Hierarchy-2 Menu item-->
                                        <div class="menu-item" data-page-name="<?=$v2[ 'page_name' ] ?>">
                                            <!--begin:Menu link-->
                                            <a class="menu-link" href="<?=WEBSITE_PROTOCOL ?>://<?=$localDomainName . "/" . PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH ?>?what_do_you_want=<?=$v2[ 'page_name' ] ?>">
                                                <span class="menu-icon">
                                                    <i class="<?=$v2[ 'icon' ] ?>"></i>
                                                </span>
                                                <span class="menu-title"><?=$v2[ 'page_title' ] ?></span>
                                            </a>
                                            <!--end:Menu link-->
                                        </div>
                                        <!--end:Hierarchy-2 Menu item-->
                                        <?php                                    
                                        }
                                        ?>
                                    
                                    <?php 
                                    }
                                    }
                                    
                                }
                                ?>
                            </div>                        
                            <!--end::Menu-->
                        </div>
                        <!--end::Aside menu-->
                    </div>
                    <!--end::Aside menu-->
                    
                    <!--begin::Footer-->
                    <div class="aside-footer flex-column-auto pb-7 px-5" id="kt_aside_footer">
                        <a href="#" class="btn btn-custom btn-primary w-100" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-dismiss-="click" title="Awaiting Development">
                            <span class="btn-label">Docs</span>
                            <i class="ki-outline ki-document btn-icon fs-2"></i>
                        </a>
                    </div>
                    <!--end::Footer-->
                    
                </div>
                <!--end::Aside-->