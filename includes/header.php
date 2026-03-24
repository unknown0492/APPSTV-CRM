<?php
    require './load-all.php';
    
    // display_php_errors();
    
    $current_page_name = getPageName( WEBSITE_URL_TYPE ); // home

    $sql = "Select page_name from pages";
    $result_set = selectQuery( $sql );
    $pages = array();
    while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
        array_push( $pages, $val->page_name );
    }
    
    if( $current_page_name == "" ){
        // $current_page_name = PAGE_NAME_ADMIN;
        redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH );
    }
    else if( ! in_array( $current_page_name, $pages ) ){
        // $current_page_name = PAGE_NAME_404;
        redirect( PAGE_NAME_LOGIN . WEBSITE_LINK_ENDS_WITH );
    }
	//die();

    $sql = "Select title, description from pages WHERE page_name = '$current_page_name'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) > 0 ){
        $value = mysqli_fetch_object( $result_set );

        $title       = $value->title;
        $description = $value->description;
    }
    else{
        $title = "Page Not Found";
    }
    $url = WEBSITE_DOMAIN_NAME . $current_page_name . WEBSITE_LINK_ENDS_WITH;
    $localDomainName = str_replace( substr( WEBSITE_DOMAIN_NAME, 0, strpos( WEBSITE_DOMAIN_NAME, "/" ) ), $_SERVER[ "HTTP_HOST" ], WEBSITE_DOMAIN_NAME );
    //echo WEBSITE_DOMAIN_NAME . "<br />";
    //echo $localDomainName;
    //print_r( $_SERVER );    
?>