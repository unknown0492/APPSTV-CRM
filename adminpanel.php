<?php
    include( "./includes/header.php" );	
    //display_php_errors();
    
    // Fetch Tokens into 2 global unique variables to be used across the entire webservice.php files
    $globalAccessToken  = NULL;
    $globalRefreshToken = NULL;
    if( isset( $_COOKIE[ TOKEN_NAME ] ) ){
        $globalAccessToken = $_COOKIE[ TOKEN_NAME ];
    }
    else if( isset( $_REQUEST[ TOKEN_NAME ] ) ){
        $globalAccessToken = $_REQUEST[ TOKEN_NAME ];
    }
    if( isset( $_COOKIE[ REFRESH_TOKEN_NAME ] ) ){
        $globalRefreshToken = $_COOKIE[ REFRESH_TOKEN_NAME ];
    }
    else if( isset( $_REQUEST[ REFRESH_TOKEN_NAME ] ) ){
        $globalRefreshToken = $_REQUEST[ REFRESH_TOKEN_NAME ];
    }
    checkIfLoggedIn();
?>

<!DOCTYPE html>
<html lang="en">
    <!--begin::Head-->
    <head>
        <?php
            include( "./includes/meta-tags.php" );			
	?>
        
        <!-- Link tags starts here -->
	<?php
            include( './includes/css.php' );
	?>
	<!-- Link tags ends here  -->
	
        <?php include './includes/header-scripts.php'; ?>
        
        <?php 
            $url_params = getUrlParameters( $_SERVER[ 'REQUEST_URI' ] );
            
            /*
            * Pages belonging to adminpanel.html, retrieve from the Database
            * 
            */
            $sql = "Select page_name, page_title from pages";
            $result_set = selectQuery( $sql );
            $admin_pages = array();
            while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
                array_push( $admin_pages, $val->page_name );
            }

            $page_name = @$url_params[ 'what_do_you_want' ];

            // Get the Plugin-Name from the current_page_name
            $sql = "SELECT b.plugin_name FROM pages a, plugins b WHERE a.page_name='$page_name' AND (b.plugin_id=a.plugin_id)";
            $result_set = selectQuery( $sql );
            if( mysqli_num_rows( $result_set ) == 0 ){
                //exit( 'No Such Page !' );
                // redirect( "404error.php" );
            }
            else{
                $val = mysqli_fetch_object( $result_set );
                $plugin_name = $val->plugin_name;
            }
        ?>
        
    </head>
    <!--end::Head-->
    
    <!--begin::Body-->
    <body class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
	
        <!--begin::Main-->
        <!--begin::Root-->
        <div class="d-flex flex-column flex-root">
            <!--begin::Page-->
            <div class="page d-flex flex-row flex-column-fluid">
                <?php
                include INC_PATH . "/left-menu.php"
                ?>
                
                <!--begin::Wrapper-->
                <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                    <?php
                    include INC_PATH . "/top-right-menu.php"
                    ?>

                    <!--begin::Content-->
                    <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                        <?php
                        include INC_PATH . "/breadcrumbs.php";
                        
                        
                        
                        ?>
                        
                        
                        <?php  
                            if( $url_params == false ){ ?>
                                <h4 class="text-center"><?=$site_tagline ?></h4> <br />
                            <?php 
                            }
                            else if( in_array( $page_name, $admin_pages ) ){ 
                                include "plugins/$plugin_name/pages/" . $page_name . '.php'; 
                            }
                            else{
                                echo "Invalid Choice";
                            }
                        ?>
                        
                    </div>
                    <!--end::Content-->
                    <?php
                    include INC_PATH . "/copyright-footer.php"
                    ?>
                </div>
                <!--end::Wrapper-->
            </div>
            <!--end::Page-->
        </div>
        <!--end::Root-->

        <!--end::Main-->
        <!--begin::Scrolltop-->
        <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
            <i class="ki-outline ki-arrow-up"></i>
        </div>
        <!--end::Scrolltop-->

    </body>
    <!--end::Body-->
</html>