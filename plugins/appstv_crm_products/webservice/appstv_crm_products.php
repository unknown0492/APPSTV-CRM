<?php

//include_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_products/includes" . FILE_SEPARATOR . "constants.php";
//include_once PLU_PATH . FILE_SEPARATOR . "appstv_crm_products/includes" . FILE_SEPARATOR . "product.php";

//use CRMProduct\Product;

//use Shopify\Rest\Admin2024_10\Product;
use Shopify\Utils;

use QuickBooksOnline\API\Core\ServiceContext;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\PlatformService\PlatformService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Invoice;

/**
 * Get all the products from the table `products`
 */
function get_crm_products(){
    checkAuthorizationForFunction( __FUNCTION__ );
    
    $responseData = array();
    send_json_mime_type_header();
    
    $sql = "SELECT "
            . "id, "
            . "product_id, "
            . "sku, "
            . "name, "
            . "physical_product, "
            . "price "
        . "FROM products "
            . "ORDER BY id DESC";
    $result_set = selectQuery( $sql );
    
    // No orders exist yet
    if( mysqli_num_rows( $result_set ) == 0 ){
        $responseData[ 'message' ] = "No products exist";
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $responseData );
        return;
    }
    
    // Prepare orders array to send as the response
    $products = array();
    while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
        array_push( $products, $val );
    }
    
    $responseData[ 'message' ] = 'Products have been retrieved';
    $responseData[ 'data' ] = $products;
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
}

function create_crm_product(){
    //print_r( $_REQUEST );
    //print_r( $_FILES );
    /*
    Array
    (
        [what_do_you_want] => create_crm_product
        [sku] => 111
        [gtin] => 11
        [title] => 11
        [name] => 11
        [description] => 11
        [weight] => 11
        [height] => 11
        [width] => 
        [depth] => 
        [price] => 11
        [tax_included] => 1
        [physical_product] => 0
        [inventory] => 11
    )
    Array
    (
        [picture] => Array
            (
                [name] => image.png
                [full_path] => image.png
                [type] => image/png
                [tmp_name] => C:\xampp\tmp\php3F59.tmp
                [error] => 0
                [size] => 973748
            )

    )
     */
    
    $sku                     = request( 'sku' );
    $gtin                    = request( 'gtin' );
    $title                   = request( 'title' );
    $name                    = request( 'name' );
    $description             = request( 'description' );
    $height                  = request( 'height' );
    $width                   = request( 'width' );
    $depth                   = request( 'depth' );
    $weight                  = request( 'weight' );
    $price                   = request( 'price' );
    $tax_included            = request( 'tax_included' );
    $physical_product        = request( 'physical_product' );
    //$inventory               = request( 'inventory' );        // Because CRM inventory is same as QuickBooks inventory
    
    // Validate for emptiness
    validateEmptyDigitString( $sku, __FUNCTION__, "SKU is required" );
    validateEmptyDigitString( $tax_included, __FUNCTION__, "Price inclusive of tax field is required" );
    validateEmptyDigitString( $physical_product, __FUNCTION__, "Physical product field is required" );
    //validateEmptyDigitString( $inventory, __FUNCTION__, "Inventory count is required" );
    
    validateEmptyString( $title, __FUNCTION__, "Title is required" );
    validateEmptyString( $name, __FUNCTION__, "Product name is required" );
    validateEmptyString( $weight, __FUNCTION__, "Weight is required" );
    validateEmptyString( $price, __FUNCTION__, "Listing price is required" );
    
    // Validate fields
    validate( $sku, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_SKU" ), "SKU is invalid" );
    validate( $gtin, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "GTIN is invalid" );
    validate( $title, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_TITLE" ), "Title is invalid" );
    validate( $name, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_NAME" ), "Product Name is invalid" );
    validate( $description, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_DESCRIPTION" ), "Product Description is invalid" );
    validate( $height, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_DIMENSION" ), "Height is invalid" );
    validate( $width, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_DIMENSION" ), "Width is invalid" );
    validate( $depth, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_DIMENSION" ), "Depth is invalid" );
    validate( $weight, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_WEIGHT" ), "Weight is invalid" );
    validate( $price, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_PRICE" ), "Listing price is invalid" );
    validate( $tax_included, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Price inclusive of tax value is invalid" );
    validate( $physical_product, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Physical product value is invalid" );
    //validate( $inventory, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Inventory count is invalid" );
    
    // Validate Picture
    $picture = '';
    if( isset( $_FILES[ 'picture' ] )){
        // Validate its type
        $picture_type = $_FILES[ 'picture' ][ 'type' ];
        if( ($picture_type!=="image/png") && ($picture_type!=="image/jpeg") ){
            //send_json_mime_type_header();
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Picture should be in jpeg/png format only" );
            return;
        }
        
        if( $_FILES[ 'picture' ][ 'error' ] > 0 ){
            //send_json_mime_type_header();
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Picture has some errors, please try using another picture" );
            return;
        }
        
        // Do the rest of the uploading at the end of the code, because the product would be stored under the directory name which is the product ID
    }
    
    $product_id = NULL;
    // The product id must be unique
    $attempt_count = 0;
    do{        
        $product_id = Product::generateProductID();
        $sql = "SELECT product_id FROM products WHERE product_id='$product_id'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            break;
        }
        if( $attempt_count === 10 ){
            //send_json_mime_type_header();
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to generate a unique product id. Please contact the administrator" );
            break;
            exit();
        }
        $attempt_count++;
    }while( true );
    
    
    $product = new Product();
    //$product = new CRMProduct\Product();
    $product->setProductID( $product_id );
    $product->setSku( $sku );
    $product->setGtin( $gtin );
    $product->setTitle( $title );
    $product->setName( $name );
    $product->setDescription( $description );
    $product->setPicture( $picture );
    $product->setWeight( $weight );
    $product->setHeight( $height );
    $product->setWidth( $width );
    $product->setDepth( $depth );
    $product->setPrice( $price );
    $product->setTaxIncluded( $tax_included );
    $product->setPhysicalProduct( $physical_product );
    //$product->setInventory( $inventory );
    
    if( $product->createProduct() !== true ){
        //send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the product in the system. Please contact the administrator" );
        exit();
    }
    
    $message = 'The product has been created. ';
    
    // Upload the Picture
    if( isset( $_FILES[ 'picture' ] )){
        // Generate the path for the picture
        $dir_abs = PLU_PATH . FILE_SEPARATOR . APPSTV_CRM_PRODUCTS_PRODUCT_IMAGES_DIR_PATH;             // The absolute directory path where the product images would be stored
        
        // Create $dir if not exist
        if( !file_exists( $dir_abs ) ){
            if( !mkdir( $dir_abs, "0755" ) ){
                //send_json_mime_type_header();
                //echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the product images directory. Please contact the administrator" );
                //return;
                $message .= "Failed to create the product images directory. Please contact the administrator";
                goto here;
            }
        }
        
        // Create another directory having the name as product_id
        $product_dir_abs = $dir_abs . FILE_SEPARATOR . $product->getProductID();
        if( !file_exists( $product_dir_abs ) ){
            if( !mkdir( $product_dir_abs, "0755" ) ){
                //send_json_mime_type_header();
                //echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the product directory under the data. Please contact the administrator" );
                //return;
                $message .= "Failed to create the product directory under the data. Please contact the administrator";
                goto here;
            }
        }
        
        // Generate absolute path for the picture to be uploaded
        $file_extension = getFileExtension( $_FILES[ 'picture' ][ 'name' ] );
        $picture_name = "main-picture." . $file_extension;
        $absolute_path_of_picture = $product_dir_abs . FILE_SEPARATOR . $picture_name;
        $relative_path_of_picture = APPSTV_CRM_PRODUCTS_PRODUCT_IMAGES_DIR_PATH . FILE_SEPARATOR . $product->getProductID() . FILE_SEPARATOR . $picture_name;
        
        // Upload the picture
        if( !move_uploaded_file( $_FILES[ 'picture' ][ 'tmp_name' ], $absolute_path_of_picture ) ){
            //send_json_mime_type_header();
            //echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to upload the product picture. Please contact the administrator" );
            //return;
            $message .= "Failed to upload the product picture. Please contact the administrator";
            goto here;
        }
        
        // Store the path of the picture in the DB
        $product->setPicture( $relative_path_of_picture );
        
        if( $product->updateProduct() !== true ){
            //send_json_mime_type_header();
            //echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to update the picture path in the DB. Please contact the administrator" );
            //exit();
            $message .= "Failed to update the picture path in the DB. Please contact the administrator";
            @unlink( $absolute_path_of_picture );
            goto here;
        }
        
    }
    
    here:
    
    $sql = "SELECT "
        . "id, "
        . "product_id, "
        . "sku, "
        . "name, "
        . "physical_product, "
        . "price "
    . "FROM products "
        . "WHERE product_id='{$product->getProductID()}'";
    $result_set = selectQuery( $sql );
    $val = mysqli_fetch_assoc( $result_set );
            
    $responseData = array(
        "message" => $message,
        "data" => $val
    );
        
    // send_json_mime_type_header();
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
        
    
}

function get_crm_product_inventory(){
    $source         = request( 'source' );
    $product_id     = request( 'product_id' );
    
    validateEmptyString( $source, __FUNCTION__, "Source is missing" );
    validateEmptyString( $product_id, __FUNCTION__, "Product ID is missing" );
    
    //validate( $source, __FUNCTION__, getValidationRegex( "VLDTN_" ), "Source is invalid" );
    
    $e_source       = escape_string( $source );
    $e_product_id   = escape_string( $product_id );
    
    // Check if the source is valid
    $sql = "SELECT * FROM sources WHERE source_id='$e_source'";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Source does not exist" );
        return;
    }
    
    $val            = mysqli_fetch_object( $result_set );
    $source_name    = strtolower( $val->source_name );
    $responseData   = array();
    /*
    if( $source_name == "crm" ){
        // Retrieve the inventory count from the products table
        $sql        = "SELECT inventory FROM products WHERE product_id='$product_id'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            //send_json_mime_type_header();
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "Something went wrong. Please report to the administrator" );
            return;
        }
        $val = mysqli_fetch_assoc( $result_set );
        
        $current_inventory_count = $val[ 'inventory' ];
        
        // Retrieve the last updated timestamp from the product_inventory_history table
        $inventory_history = ProductInventoryHistory::getLatestInventoryHistoryForProduct( $product_id );
        if( $inventory_history === NULL ){
            $inventory_history = "NA";
        }
        
        $data = array(
            "current_inventory_count" => $current_inventory_count,
            "inventory_history" => $inventory_history
        );
        
        $responseData = array(
            "message" => "Data has been retrieved",
            "data" => $data
        );
        
    }
    else 
    */ 
    if( $source_name == "shopify" ){
        global $store_name, $store_url, $client_id, $client_secret, $api_version;
        
        // Retrieve the corresponding Shopify product id for the CRM product_id
        $shopify_product_id = ProductMeta::getShopifyProductID( $e_product_id );
        if( $shopify_product_id === NULL ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "CRM Product has not been synced with Shopify Product. Please perform the sync process for this product with shopify before trying to update the inventory" );
            return;
        }
        
        // Get Inventory Count from Shopify
        $url_params = array(
            "fields" => 'variants'
        );
        
        // Docs: https://shopify.dev/docs/api/admin-rest/2024-10/resources/product#get-products-product-id
        $api_url = "https://{$store_url}/admin/api/{$api_version}/products/{$shopify_product_id}.json?" . http_build_query( $url_params );
        
        $output = shopifyPerformCurlRequest([
            'api_url' => $api_url,
            'custom_request' => 'GET'
        ]);
        
        $output_arr = json_decode( $output, true );        
        
        $current_inventory_count    = $output_arr[ 'product' ][ 'variants' ][ 0 ][ SHOPIFY_PRODUCT_INVENTORY_COUNT ];
        $inventory_item_id          = $output_arr[ 'product' ][ 'variants' ][ 0 ][ SHOPIFY_PRODUCT_INVENTORY_ITEM_ID ];
        
        // Update this count into ProductMeta table
        ProductMeta::setProductMetaValue( $e_product_id, ProductMeta::DB_PRODUCT_META_KEY_SHOPIFY_INVENTORY_COUNT, $current_inventory_count );
        ProductMeta::setProductMetaValue( $e_product_id, ProductMeta::DB_PRODUCT_META_KEY_SHOPIFY_INVENTORY_ITEM_ID, $inventory_item_id );
        
    }
    else if( $source_name == "quickbooks" ){
        // Retrieve the corresponding QuickBooks product id for the CRM product_id
        $quickbooks_product_id = ProductMeta::getQuickBooksProductID( $e_product_id );
        if( $quickbooks_product_id === NULL ){
            //send_json_mime_type_header();
            echo createJSONMessage(GENERAL_ERROR_MESSAGE, __FUNCTION__, "CRM Product has not been synced with QuickBooks Product. Please perform the sync process for this product with QuickBooks before trying to update the inventory" );
            return;
        }
        
        $dataService = getQuickBooksDataService();

        $dataService->setLogLocation( QUICKBOOKS_LOGS_PATH );

        $OAuth2LoginHelper   = $dataService->getOAuth2LoginHelper();
        $accessToken         = $OAuth2LoginHelper->refreshToken();
        
        // before every call to the api, it is a must to call this method to set the accessToken in the $dataService object
        $dataService->updateOAuth2Token( $accessToken );
        
        //$items = $dataService->Query( "SELECT Name,QtyOnHand FROM Item WHERE Id='$quickbooks_product_id'");
        $item = \QuickBooksOnline\API\Facades\Item::create([
            "Id" => $quickbooks_product_id
        ]);
        $item_retrieved = $dataService->Retrieve( $item );
        //var_dump( $item_retrieved );
        
        if( !is_object( $item_retrieved ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to retrieve the Inventory Stock Count from QuickBooks" );
            return;
        }
        
        //if( isset( $item_retrieved->QtyOnHand ) ){
            $current_inventory_count = $item_retrieved->QtyOnHand;
            
            // Update this count into ProductMeta table
            ProductMeta::setProductMetaValue( $e_product_id, ProductMeta::DB_PRODUCT_META_KEY_QUICKBOOKS_INVENTORY_COUNT, $current_inventory_count );
        //}
        //print_r( $items );
        
    }
    
    // Retrieve the last updated timestamp from the product_inventory_history table
    $inventory_history = ProductInventoryHistory::getLatestInventoryHistoryForProduct( $e_product_id, $source );
    if( $inventory_history === NULL ){
        $inventory_history = "NA";
    }

    $data = array(
        "current_inventory_count" => $current_inventory_count,
        "inventory_history" => $inventory_history
    );

    $responseData = array(
        "message" => "Data has been retrieved",
        "data" => $data
    );
    
    //send_json_mime_type_header();
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
    return;
}


function update_crm_product_inventory(){
    //display_php_errors();
    //print_r( $_REQUEST );
    $product_id                     = request( 'product_id' );
    $source                         = request( 'source' );
    $current_inventory_count        = request( 'current_inventory_count' );      
    $new_inventory_count            = request( 'new_inventory_count' );
    
    // Validate for emptiness
    validateEmptyString( $product_id, __FUNCTION__, "Product ID is required" );
    validateEmptyString( $source, __FUNCTION__, "Source is required" );
    validateEmptyString( $new_inventory_count, __FUNCTION__, "Additional stock count is required" );
    
    // Validate
    validate( $product_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Product ID is invalid" );
    validate( $new_inventory_count, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS_INC_NEGATIVE" ), "Additional stock count is invalid" );
    
    $e_product_id                = escape_string( $product_id );
    $e_source                    = escape_string( $source );
    $e_current_inventory_count   = escape_string( $current_inventory_count );
    $e_new_inventory_count       = escape_string( $new_inventory_count );
    
    $total_inventory_count = intval( $e_current_inventory_count ) + intval( $e_new_inventory_count );
    if( $total_inventory_count < 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Total inventory stock cannot be less than ZERO" );
        return;
    }
    
    // Check if the Product ID exist in the system
    $product = new Product();
    //$product = new CRMProduct\Product();
    $product->setProductID( $e_product_id );
    
    if( !$product->exists() ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Product does not exist in the system" );
        return;
    }
    //echo "here3";
    // Check if the Source is valid
    $sql = "SELECT source_id, source_name FROM sources WHERE source_id='$e_source'";
    $result_set = selectQuery( $sql );
    $source_name = '';
    if( mysqli_num_rows( $result_set  ) === 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Source does not exist in the system" );
        return;
    }
    else{    
        $val = mysqli_fetch_object( $result_set );
        $source_name = $val->source_name;
        if( $val->source_id !== $e_source ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Source does not exist in the system" );
            return;
        }
    }
    
    $source_name    = strtolower( $source_name );
    
    ob_start();
    get_crm_product_inventory();
    $json_response = ob_get_contents();
    ob_end_clean();
    
    // Get the value of current_inventory_count from $json_response
    $response_array = json_decode( $json_response, true );
    if( $response_array === NULL ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Error occurred. Please try again later" );
        return;
    }
    
    $response_msg = $response_array[ 0 ];
    if( $response_msg[ 'type' ] == GENERAL_ERROR_MESSAGE ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, $response_msg[ 'info' ] );
        return;
    }
    
    $info = $response_msg[ 'info' ];
    
    $current_inventory_count = intval( $info[ 'data' ][ 'current_inventory_count' ] );
    $e_new_inventory_count   = intval( $e_new_inventory_count );
    
    $total_inventory_count = $current_inventory_count + $e_new_inventory_count;
    if( $total_inventory_count < 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Final inventory stock cannot be less than ZERO" );
        return;
    }
    
    if( $source_name === "quickbooks" ){
        // Update into product_meta quickbooks product id table
        $dataService = getQuickBooksDataService();

        $dataService->setLogLocation( QUICKBOOKS_LOGS_PATH );

        $OAuth2LoginHelper   = $dataService->getOAuth2LoginHelper();
        $accessToken         = $OAuth2LoginHelper->refreshToken();
        
        // before every call to the api, it is a must to call this method to set the accessToken in the $dataService object
        $dataService->updateOAuth2Token( $accessToken );
        
        // Retrieve the product_id for QuickBooks product entity
        $quickbooks_product_id = ProductMeta::getProductMetaValue( $e_product_id, ProductMeta::DB_PRODUCT_META_KEY_QUICKBOOKS_PRODUCT_ID );
        
        // Retrieve the ItemData from QuickBooks
        //$items = $dataService->Query( "SELECT Name,QtyOnHand FROM Item WHERE Id='$quickbooks_product_id'");
        $item = \QuickBooksOnline\API\Facades\Item::create([
            "Id" => $quickbooks_product_id
        ]);
        
        $item_retrieved = $dataService->Retrieve( $item );
        //print_r( $item_retrieved );
        
        // This array will contain only those fields that needs to be updated
        $changes_array = array( 
            QUICKBOOKS_PRODUCT_INVENTORY_COUNT => $total_inventory_count 
        );
        
        $item_u = \QuickBooksOnline\API\Facades\Item::update( $item_retrieved, $changes_array );
        $item_updated = $dataService->Update( $item_u );
        
        // Check if the response received is an object
        if( !is_object( $item_updated ) ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "No response from QuickBooks server: " . $item_updated );
            return;            
        }
        
        // Item entity object is received as a response after successful update
        if( isset( $item_updated->QtyOnHand ) ){
            
            // Update this count into ProductMeta table
            ProductMeta::setProductMetaValue( $e_product_id, ProductMeta::DB_PRODUCT_META_KEY_QUICKBOOKS_INVENTORY_COUNT, $total_inventory_count );

            // Update this count in the product_inventory_history table
            ProductInventoryHistory::addToProductInventoryHistory( $e_product_id, $e_source, $e_new_inventory_count );
            
            // Retrieve the last updated timestamp from the product_inventory_history table
            $inventory_history = ProductInventoryHistory::getLatestInventoryHistoryForProduct( $e_product_id, $source );
            if( $inventory_history === NULL ){
                $inventory_history = "NA";
            }
            
            $data = array(
                "new_inventory_count" => $item_updated->QtyOnHand,
                "inventory_history" => $inventory_history
            );

            $responseData = array(
                "message" => "Inventory stock updated successfully on QuickBooks",
                "data" => $data
            );
            
            echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
            return;
            
        }
        
    }
    else if( $source_name === "shopify" ){
        global $store_name, $store_url, $client_id, $client_secret, $api_version;
        
        // Retrieve the corresponding Shopify product id for the CRM product_id
        $shopify_product_id             = ProductMeta::getShopifyProductID( $e_product_id );
        $shopify_inventory_item_id      = ProductMeta::getProductMetaValue( $e_product_id, ProductMeta::DB_PRODUCT_META_KEY_SHOPIFY_INVENTORY_ITEM_ID );
        $shopify_inventory_location_id  = SHOPIFY_LOCATION_ID_SINGAPORE;
        
        if( $shopify_inventory_item_id === NULL ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Shopify inventory item id is missing. Please contact the administrator" );
            return;
        }
        
        // Docs: https://shopify.dev/docs/api/admin-rest/2024-10/resources/inventorylevel
        $api_url = "https://{$store_url}/admin/api/{$api_version}/inventory_levels/set.json";
        
        // POST params for inventory update
        $url_params = array(
            //"available_adjustment" => $total_inventory_count,
            "available" => $total_inventory_count,
            "inventory_item_id" => $shopify_inventory_item_id,
            "location_id" => $shopify_inventory_location_id
        );
        
        $output = shopifyPerformCurlRequest([
            'api_url' => $api_url,
            'url_params' => json_encode( $url_params ),
            'method' => 'POST',
            'custom_request' => 'POST'            
        ]);
        
        $output_arr                 = json_decode( $output, true );        
        $updated_inventory_count    = $output_arr[ 'inventory_level' ][ 'available' ];
        
        // Update this count into ProductMeta table
        ProductMeta::setProductMetaValue( $e_product_id, ProductMeta::DB_PRODUCT_META_KEY_SHOPIFY_INVENTORY_COUNT, $updated_inventory_count );
        
        // Update this count in the product_inventory_history table
        ProductInventoryHistory::addToProductInventoryHistory( $e_product_id, $e_source, $e_new_inventory_count );
        
        // Retrieve the last updated timestamp from the product_inventory_history table
        $inventory_history = ProductInventoryHistory::getLatestInventoryHistoryForProduct( $e_product_id, $e_source );
        if( $inventory_history === NULL ){
            $inventory_history = "NA";
        }
        
        $data = array(
            "new_inventory_count" => $updated_inventory_count,
            "inventory_history" => $inventory_history
        );
        
        $responseData = array(
            "message" => "Inventory stock count updated successfully on shopify",
            "data" => $data
        );
        
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $responseData );
        return;
            
    }
}

function create_product_serial_number(){
    $multiple   = request( 'multiple' );          // Whether to create a single serial number or multiple serial numbers in the sequence. Possible values are 0 | 1
    $product_id = request( 'product_id' );        
    
    // Validate Required Fields
    validateEmptyDigitString( $multiple, __FUNCTION__, "Please specify if you want to create a single serial number or multiple serial numbers" );
    validateEmptyDigitString( $product_id, __FUNCTION__, "Please specify the Product ID for which the Serial Number is being created" );
    
    // Validate the Fields
    validate( $multiple, __FUNCTION__, getValidationRegex( "VLDTN_SINGLE_BINARY" ), "The value of serial number type is not specified correctly" );
    validate( $product_id, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "The Product ID specified is in invalid format" );
    
    // Check if for this product_id, has_sn=1 , only then we can create serial numbers for this product, otherwise give error 
    $sql = "SELECT has_sn FROM products WHERE (product_id='$product_id') AND (has_sn='1')";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set ) === 0 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The specified Product ID either does not exist nor it is of the type whose `has_sn=1`" );
        return;
    }
    
    $e_product_id = escape_string( $product_id );
    
    $created_on = create_iso_8601_datetime();
    
    // Creating single product serial number
    if( $multiple === "0" ){
        $serial_number   = request( 'serial_number' );
        
        // Check if the Serial Number is required
        validateEmptyDigitString( $serial_number, __FUNCTION__, "Please specify a Product Serial Number" );
        
        // Check if the Serial Number is in valid format
        validate( $serial_number, __FUNCTION__, getValidationRegex( "VLDTN_PRODUCT_SERIAL_NUMBER" ), "The Product Serial Number is in invalid format" );
        
        $e_serial_number = escape_string( $serial_number );
        
        // Check if this product serial number already exist
        $productSerialNumber = new ProductSN();
        $productSerialNumber->setProductID( $e_product_id );
        $productSerialNumber->setSerialNumber( $e_serial_number );
        $productSerialNumber->setCreatedOn( $created_on );
        
        if( $productSerialNumber->exists() ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The specified serial number `$serial_number` already exist in the system !" );
            return;
        }
        
        // Create the product serial number in the database
        if( !$productSerialNumber->createProductSerialNumber() ){
            echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Failed to create the serial number in the system !" );
            return;
        }
        
        echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Serial Number has been created in the system !" );
        return;
    }
    
    // Creating a Series of Serial Numbers
    $first_half_constant    = request( 'first_half_constant' );            // The first half constant part of the serial number
    $series_starting_number = request( 'series_starting_number' );         // The first number in the series
    $total_serial_numbers   = request( 'total_serial_numbers' );           // The total count of serial numbers to be created starting from the series_starting_number
    
    // Validation of Input Data
    validateEmptyString( $first_half_constant, __FUNCTION__, "First half constant part of the Serial Number is required" );
    validate( $first_half_constant, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "The first half constant of the serial number should only contain numbers" );
    
    validateEmptyDigitString( $series_starting_number, __FUNCTION__, "The series starting number for the Serial Number is required" );
    validate( $series_starting_number, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "The series starting number for the serial number should only contain numbers" );
    
    validateEmptyDigitString( $total_serial_numbers, __FUNCTION__, "The value of total serial numbers to be created is required" );
    validate( $total_serial_numbers, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "The value of total serial numbers should be a number" );
    
    $totalLengthOfSerialNumber  = 14;
    $first_half_length          = strlen( $first_half_constant );
    if( $first_half_length >= 14 ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The first half constant's length should be less than 14 characters !" );
        return;
    } 
    
    $remaining_length               = $totalLengthOfSerialNumber - $first_half_length;
    $series_starting_number_length  = strlen( $series_starting_number );
    if( $series_starting_number_length > $remaining_length ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "The series starting number `$series_starting_number` cannot be accomodated within the 14 character limit of the Serial Number" );
        return;
    }
    
    // Check Maximum serial numbers possible
    $series_starting_number             = intval( $series_starting_number );
    $max_serial_nos_possible            = getLargestXDigitNumber( $remaining_length );
    $practically_possible_serial_nos    = $max_serial_nos_possible - $series_starting_number+1;
    
    if( $practically_possible_serial_nos < $total_serial_numbers ){
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Only $practically_possible_serial_nos serial numbers can be created as per the given input, whereas you have requested to create a total of $total_serial_numbers numbers. Please change your input and try again" );
        return;
    }
    
    // Create the Serial Numbers
    $created_serial_nos = 0;
    $already_existing_serial_nos = array();
    for( $i = $series_starting_number ; $i < ($series_starting_number + $total_serial_numbers) ; $i++  ){
        $serial_number = $first_half_constant . appendZeros( $i, $remaining_length );
        //echo $serial_number . "\n";
        $productSerialNo = new ProductSN();
        $productSerialNo->setProductID( $e_product_id );
        $productSerialNo->setSerialNumber( $serial_number );
        $productSerialNo->setCreatedOn( $created_on );
        
        if( !$productSerialNo->exists() ){
            $productSerialNo->createProductSerialNumber();
            $created_serial_nos++;
        }
        else{
            array_push( $already_existing_serial_nos, $serial_number );
        }
        
    }
        
    $total_serial_numbers_created = $created_serial_nos;
    
    $data = array(
        "total_serial_numbers_created" => $total_serial_numbers_created,
        "already_existing_serial_nos" => $already_existing_serial_nos
    );
    
    $response = array(
        "message" => "Product Serial Numbers have been created",
        "data" => $data
    );
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, $response );
    return;
    
}

?>