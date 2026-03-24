<?php

include './load-all.php';
display_php_errors();

$str = "D:\OneDrive\xampp\htdocs\sc_framework_june2024/plugins/appstv_crm_order/data/orders/xxx/invoice/\IPPinvoice_162_1731768371.pdf";
echo basename( $str );

/*
$image = new Imagick();
$image->newImage(1, 1, new ImagickPixel('#ffffff'));
$image->setImageFormat('png');
$pngData = $image->getImagesBlob();
echo strpos($pngData, "\x89PNG\r\n\x1a\n") === 0 ? 'Ok' : 'Failed'; 
*/
//$image_info = getimagesize("F:\\Sohail\\Downloads\\IMG-4830.jpg"); 
//print_r($image_info); 
/*
list($width, $height) = getimagesize("F:\\Sohail\\Downloads\\IMG-4830.jpg");
echo $width . ',';
echo $height;
*/
/*
$date = new DateTime( "now", new DateTimeZone( "+0800" ) );
$day            = $date->format( 'd' );
$millis         = $date->format( 'v' );
$product_sn_id  = $day . $millis . generateRandomNumber( 5 );

echo $millis;
*/
/* 
 // Changing the SKU in products column of orders table
$sql = "SELECT products, order_id FROM orders";
$result_set = selectQuery( $sql );

while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
    $products = $val[ 'products' ];
    $products_arr = json_decode( $products, TRUE );
    foreach ( $products_arr as $key => $product ) {
        if( $product[ 'sku' ] === '00101043' ){
            $product[ 'sku' ] = '101043';
        }
        else if( $product[ 'sku' ] === '00101050' ){
            $product[ 'sku' ] = '101050';
        }
        else if( $product[ 'sku' ] === '00101055' ){
            $product[ 'sku' ] = '101055';
        }
        else if( $product[ 'sku' ] === '00101065' ){
            $product[ 'sku' ] = '101065';
        }
        else if( $product[ 'sku' ] === '00301W4A' ){
            $product[ 'sku' ] = '301001';
        }
        else if( $product[ 'sku' ] === '00301P40' ){
            $product[ 'sku' ] = '301002';
        }
        else if( $product[ 'sku' ] === '00301W01' ){
            $product[ 'sku' ] = '301003';
        }
        
        // Replace u201d
        if( strstr( $product[ 'name' ], 'u201d' ) !== false ){
            $product[ 'name' ] = str_replace( 'u201d', '"', $product[ 'name' ] );
        }
        if( strstr( $product[ 'title' ], 'u201d' ) !== false ){
            $product[ 'title' ] = str_replace( 'u201d', '"', $product[ 'title' ] );
        }
        
        $products_arr[ $key ] = $product; 
    }
    $p = json_encode( $products_arr );
    $p = escape_string( $p );
    
    echo $p . "<br /><br />";
    
    // Update the products back into orders table
    $order_id = $val[ 'order_id' ];
    
    $sql = "UPDATE orders SET products='$p' WHERE order_id='$order_id'";
    updateQuery( $sql );
}
*/

/*
$c = create_iso_8601_datetime();

$millis = convert_iso_8601_datetime_to_millis( $c );

echo $c;
*/
/*
$arr = array(
    "name" => "this is 32\" table"
);

$arr_json = escape_string( '{"name":"this is 32\" TV"}' );

echo $arr_json;

setConfigurationValue( "test_json", $arr_json );

$a = json_decode( $arr_json, true );

var_dump( $a );
*/


/*
echo date('c');

// Create the token payload
$payload = json_encode([
    'user_id' => 'admin',
    'role' => 'admin',
    'exp' => 1593828222,
    'iat' => 1593828222,
    'fname' => "Sohail",
    'lname' => "Shaikh",
    'nickname' => "Administrator"
]);
*/
//$jwt = createStandardJWT($payload);

//echo $jwt;

/*
$keys = generateAsymmetricKeyPair();

$data = "I am a critical piece of data !";

$encryptedData = encryptData( $keys[ 'publicKey' ], $data );

echo "Original data: " . $data . "<br /><br />";
echo "Encrypted data: " . $encryptedData . "<br /><br />";

$base64encryptedData = base64UrlEncode( $encryptedData );

echo "base64 Encrypted data: " . $base64encryptedData . "<br /><br />";

$base64decodedData = base64UrlDecode( $base64encryptedData );

echo "base64decodedData: " . $base64decodedData . "<br /><br />";

$decryptedData = decryptData( $keys[ 'privateKey' ], $base64decodedData );

echo "Decrypted data: " . $decryptedData . "<br /><br />";

echo "Public Key: " . $keys[ 'publicKey' ] . "<br /><br />";
*/

//echo getSiteRootPath( DIRNAME_DATA );


//session_start();
//print_r( $_SESSION );
//print_r( $_SERVER );


//HTTP_USER_AGENT = Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0
    


//$regex = "/^$|^([a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`\\n\\r]*)$/";
/*
$regex = "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`\\n\\r]*$/";
$str = "a+#";
$str = "";

if (!preg_match($regex, $str)) {
    echo "FAILED";
}

validate( $str, "", $regex, "error");

function validate($str, $for, $regex, $err_msg) {
    if( $str === NULL ){  // When the URL parameter is missing
        echo "e1";
        exit();
    }
    if (!preg_match($regex, $str)) {
        echo "e2";
        exit();
    }
}
*/
/*
$str = request( "test" );

function request( $var ){
    if( !isset( $_REQUEST[ $var ] ) ){
        return NULL;
    }
    echo trim( $_REQUEST[ $var ] );
}
 * 
 */

/*
$json = '{"43":{"page_id":"43","page_name":"administrator","parent_id":"-1","hierarchy":"1","page_sequence":"1","icon":"fa fa-wrench","visible":"1","page_title":"Administrator","functionality_id":"9","page_group_id":"0","title":"","description":"","tags":"","image":"","content":"","plugin_id":"6","children":{"42":{"page_id":"42","page_name":"roles","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"fa fa-wrench","visible":"1","page_title":"Roles","functionality_id":"9","page_group_id":"0","title":"","description":"","tags":"","image":"","content":"","plugin_id":"6","children":{"2":{"page_id":"2","page_name":"create_role_page","parent_id":"42","hierarchy":"3","page_sequence":"1","icon":"fa fa-smile-o","visible":"1","page_title":"Create New Role","functionality_id":"9","page_group_id":"0","title":"Create New Role","description":"","tags":"","image":"","content":"","plugin_id":"6"},"48":{"page_id":"48","page_name":"manage_pages1","parent_id":"42","hierarchy":"3","page_sequence":"1","icon":"a","visible":"0","page_title":"b","functionality_id":"3","page_group_id":"0","title":"b","description":"","tags":"","image":"","content":"","plugin_id":"2"},"3":{"page_id":"3","page_name":"view_roles_page","parent_id":"42","hierarchy":"3","page_sequence":"2","icon":"fa fa-smile-o","visible":"1","page_title":"View\/Edit Roles","functionality_id":"10","page_group_id":"0","title":"View or Edit Roles","description":"","tags":"","image":"","content":"","plugin_id":"6"}}},"44":{"page_id":"44","page_name":"users","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"fa fa-wrench","visible":"1","page_title":"Users","functionality_id":"9","page_group_id":"0","title":"","description":"","tags":"","image":"","content":"","plugin_id":"6","children":{"8":{"page_id":"8","page_name":"create_user_page","parent_id":"44","hierarchy":"3","page_sequence":"7","icon":"fa fa-smile-o","visible":"1","page_title":"Create New User","functionality_id":"2","page_group_id":"0","title":"Create New User","description":"","tags":"","image":"","content":"","plugin_id":"5"},"9":{"page_id":"9","page_name":"view_users_page","parent_id":"44","hierarchy":"3","page_sequence":"8","icon":"fa fa-smile-o","visible":"1","page_title":"View\/Edit Users","functionality_id":"4","page_group_id":"0","title":"View\/Edit Users","description":"","tags":"","image":"","content":"","plugin_id":"5"}}},"45":{"page_id":"45","page_name":"pages","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"fa fa-wrench","visible":"1","page_title":"Pages","functionality_id":"9","page_group_id":"0","title":"","description":"","tags":"","image":"","content":"","plugin_id":"6","children":{"11":{"page_id":"11","page_name":"create_new_page","parent_id":"45","hierarchy":"3","page_sequence":"10","icon":"fa fa-smile-o","visible":"1","page_title":"Create New Page","functionality_id":"42","page_group_id":"0","title":"Create New Page","description":"","tags":"","image":"","content":"","plugin_id":"2"},"13":{"page_id":"13","page_name":"view_system_page","parent_id":"45","hierarchy":"3","page_sequence":"11","icon":"fa fa-smile-o","visible":"1","page_title":"View\/Edit Pages","functionality_id":"43","page_group_id":"0","title":"View\/Edit Pages","description":"","tags":"","image":"","content":"","plugin_id":"2"}}},"46":{"page_id":"46","page_name":"functionalities","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"fa fa-wrench","visible":"1","page_title":"Functionalities","functionality_id":"9","page_group_id":"0","title":"","description":"","tags":"","image":"","content":"","plugin_id":"6","children":{"5":{"page_id":"5","page_name":"create_privilege_page","parent_id":"46","hierarchy":"3","page_sequence":"4","icon":"fa fa-smile-o","visible":"1","page_title":"Create Privilege","functionality_id":"6","page_group_id":"0","title":"Create Privilege","description":"","tags":"","image":"","content":"","plugin_id":"4"},"6":{"page_id":"6","page_name":"view_privileges_page","parent_id":"46","hierarchy":"3","page_sequence":"5","icon":"fa fa-smile-o","visible":"1","page_title":"View\/Edit Privileges","functionality_id":"7","page_group_id":"0","title":"View or Edit Privileges","description":"","tags":"","image":"","content":"","plugin_id":"4"}}},"47":{"page_id":"47","page_name":"manage_pages","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"fa fa-smile-o","visible":"1","page_title":"Manage Pages","functionality_id":"9","page_group_id":"0","title":"Manage Pages","description":"","tags":"","image":"","content":"","plugin_id":"2","children":{"51":{"page_id":"51","page_name":"c","parent_id":"47","hierarchy":"3","page_sequence":"1","icon":"c","visible":"0","page_title":"c","functionality_id":"2","page_group_id":"0","title":"c","description":"","tags":"","image":"","content":"","plugin_id":"2"}}},"49":{"page_id":"49","page_name":"manage1","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"s","visible":"0","page_title":"a1","functionality_id":"2","page_group_id":"0","title":"a","description":"","tags":"","image":"","content":"","plugin_id":"2","children":{"50":{"page_id":"50","page_name":"asdasd","parent_id":"49","hierarchy":"3","page_sequence":"1","icon":"asd","visible":"0","page_title":"asd1","functionality_id":"2","page_group_id":"0","title":"asd","description":"","tags":"","image":"","content":"","plugin_id":"2"}}},"52":{"page_id":"52","page_name":"d","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"d","visible":"1","page_title":"d","functionality_id":"2","page_group_id":"0","title":"d","description":"","tags":"","image":"","content":"","plugin_id":"4"},"53":{"page_id":"53","page_name":"aere","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"a","visible":"1","page_title":"a2","functionality_id":"3","page_group_id":"0","title":"a","description":"","tags":"","image":"","content":"","plugin_id":"3"},"54":{"page_id":"54","page_name":"asd","parent_id":"43","hierarchy":"2","page_sequence":"1","icon":"asd","visible":"1","page_title":"asd","functionality_id":"2","page_group_id":"0","title":"asd","description":"","tags":"","image":"","content":"","plugin_id":"2"}}}}';
$hierarchy1 = json_decode( $json, true );


foreach ( $hierarchy1 as $page_id => $value ) {
?>
<?=$value[ 'page_title' ] ?><br />
    
    <?php 
    // If Hierarcy-2 has children
    if( isset( $value[ 'children' ] ) && (count($value[ 'children' ]) > 0) ){
    $hierarchy2 = $value[ 'children' ];
    foreach ( $hierarchy2 as $k2 => $v2 ) {
     // echo json_encode( $hierarchy2 );
    //echo "Checking for Page ID - " . $v2[ 'page_id' ] . "\n";
        if( isset( $v2[ 'children' ] ) && (count($v2[ 'children' ]) > 0) ){
    ?>
     - <?=$v2[ 'page_title' ] ?><br />

        <?php 

        $hierarchy3 = $v2[ 'children' ];
        foreach ( $hierarchy3 as $k3 => $v3 ){

        ?>
             - - <?=$v3[ 'page_title' ] ?><br />
        <?php 
            }
        }
        else{
            // If Hierarchy-2 Does not have children, then Hierarchy-2 should be a Link
            //echo "abab";
        ?>
        
         - <?=$v2[ 'page_title' ] ?><br />
        <?php                                    
        }
        ?>

    <?php 
    }
    }

}
 * 
 */
?>