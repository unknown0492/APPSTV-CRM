<?php



// include QUICKBOOKS_KEYS_PATH;
// include QUICKBOOKS_CONSTANTS_PATH;

require_once LIB_PATH . FILE_SEPARATOR . 'QuickBooksSDK' . FILE_SEPARATOR . "vendor/autoload.php";
use QuickBooksOnline\API\Core\ServiceContext;
use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\PlatformService\PlatformService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Invoice;


/**
 * Call this function in the URL through webservice.php
 * This function has been bypassed in the webservice.php file so it can be accessed without the AccessToken and Authorization
 * 
 * This function will redirect to another function named
 * 
 */
function authenticate_quickbooks_app(){
    // Procedure: https://developer.intuit.com/app/developer/qbo/docs/develop/sdks-and-samples-collections/php
    
    $config = include( QUICKBOOKS_KEYS_PATH );
    
    @session_start();

    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $config['client_id'],
        'ClientSecret' =>  $config['client_secret'],
        'RedirectURI' => $config['oauth_redirect_uri'],
        'scope' => $config['oauth_scope'],
        'baseUrl' => "development"
    ));
    
    $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();

    // Get the Authorization URL from the SDK
    $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
    
    header( 'Location: ' . $authUrl );
}


function generate_quickbooks_tokens(){
    $config = include( QUICKBOOKS_KEYS_PATH );
    
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $config['client_id'],
        'ClientSecret' =>  $config['client_secret'],
        'RedirectURI' => $config['oauth_redirect_uri'],
        'scope' => $config['oauth_scope'],
        'baseUrl' => "production"
    ));

    //$OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
    $output = array();
    $uri = $_SERVER[ 'QUERY_STRING' ];
    $urlArr = parse_url( $uri );
    // print_r($urlArr);
    parse_str($urlArr['path'], $output);

    //print_r($output);
    $OAuth2LoginHelper  = $dataService->getOAuth2LoginHelper();
    $accessToken        = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken($output['code'], $output['realmId']);
    
    setConfigurationValue( QUICKBOOKS_ACCESS_TOKEN_KEY, $accessToken->getAccessToken() );
    setConfigurationValue( QUICKBOOKS_REFRESH_TOKEN_KEY, $accessToken->getRefreshToken() );
    setConfigurationValue( QUICKBOOKS_QBO_REALM_ID, $accessToken->getRealmID() );
    
    //$accessToken->
    $dataService->updateOAuth2Token( $accessToken );      // before every call to the api, it is a must to call this method to set the accessToken in the $dataService object
    
    //$_SESSION['sessionAccessToken'] = $accessToken;
    send_json_mime_type_header();
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Tokens have been stored in the DB" );
}


function test_quickbooks_api_call(){
    
    //display_php_errors();
    /*
    $config = include( QUICKBOOKS_KEYS_PATH );
    
    $accessTokenKey     = getConfigurationValue( QUICKBOOKS_ACCESS_TOKEN_KEY );
    $refreshTokenKey    = getConfigurationValue( QUICKBOOKS_REFRESH_TOKEN_KEY );
    $QBORealmID         = getConfigurationValue( QUICKBOOKS_REFRESH_TOKEN_KEY );
    
    if( ($accessTokenKey === NULL) || ($refreshTokenKey === NULL) || ($QBORealmID === NULL) ){
        //send_json_mime_type_header();
        echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please re run the QuickBooks authentication API from CRM" );
        return;
    }
    
    $dataService = DataService::Configure(array(
        'auth_mode'       => 'oauth2',
        'ClientID'        => $config['client_id'],
        'ClientSecret'    => $config['client_secret'],
        'accessTokenKey'  => $accessTokenKey,
        'refreshTokenKey' => $refreshTokenKey,
        'QBORealmID'      => $QBORealmID,
        'baseUrl'         => "production"
    ));
     */
    $dataService = getQuickBooksDataService();

    $dataService->setLogLocation( QUICKBOOKS_LOGS_PATH );
    
    $OAuth2LoginHelper   = $dataService->getOAuth2LoginHelper();
    $accessToken         = $OAuth2LoginHelper->refreshToken();
    /*
    $error               = $OAuth2LoginHelper->getLastError();
    if( $error ) {
        echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        echo "The Response message is: " . $error->getResponseBody() . "\n";
        return;
    }
    */
    $error = $OAuth2LoginHelper->getLastError();
    if ( $error ){
        echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        echo "The Response message is: " . $error->getResponseBody() . "\n";
        return;
    }
    // before every call to the api, it is a must to call this method to update the expired access token automatically
    $dataService->updateOAuth2Token( $accessToken );
    
    print_r( $dataService->getCompanyInfo() );
    
}


function getQuickBooksDataService(){
    // Get inventory count from QuickBooks
    $config = include( QUICKBOOKS_KEYS_PATH );

    $accessTokenKey     = getConfigurationValue( QUICKBOOKS_ACCESS_TOKEN_KEY );
    $refreshTokenKey    = getConfigurationValue( QUICKBOOKS_REFRESH_TOKEN_KEY );
    $QBORealmID         = getConfigurationValue( QUICKBOOKS_QBO_REALM_ID );

    if( ($accessTokenKey === NULL) || ($refreshTokenKey === NULL) || ($QBORealmID === NULL) ){
        //echo createJSONMessage( GENERAL_ERROR_MESSAGE, __FUNCTION__, "Please re run the QuickBooks authentication API from CRM" );
        return NULL;
    }
    
    $dataService = DataService::Configure(array(
	'auth_mode'       => 'oauth2',
	'ClientID'        => $config['client_id'],
	'ClientSecret'    => $config['client_secret'],
	'accessTokenKey'  => $accessTokenKey,
	'refreshTokenKey' => $refreshTokenKey,
	'QBORealmID'      => $QBORealmID,
	'baseUrl'         => "production"
    ));
    
    
    return $dataService;
}

/**
 * 
 * The access tokens expire every 60 minutes in QuickBooks
 * If the users do not make a call to the QuickBooks API, the accessToken is expired, and the Refresh token can also get expired.
 * 
 * So, we make the below CRON Job, that will be called every 10 minutes, so that the AccesTokenKey and RefreshTokenKey gets renewed and stored in DB every 10 minutes
 * This eradicates the need to check the validity of AccessTokenKey and RefreshTokenKey every time before making API call
 * 
 */
function refresh_tokens_before_they_expire_quickbooks(){
    //display_php_errors();
    // Source: https://github.com/intuit/QuickBooks-V3-PHP-SDK/issues/240
    $dataService = getQuickBooksDataService();

    $dataService->setLogLocation( QUICKBOOKS_LOGS_PATH );

    $OAuth2LoginHelper   = $dataService->getOAuth2LoginHelper();
    //$accessToken         = $OAuth2LoginHelper->refreshToken();
    
    $refreshTokenKey    = getConfigurationValue( QUICKBOOKS_REFRESH_TOKEN_KEY );
    $accessToken        = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken( $refreshTokenKey );
    
    $dataService->updateOAuth2Token( $accessToken );
    
    $newAccessTokenKey      = $accessToken->getAccessToken();
    $newRefreshTokenKey     = $accessToken->getRefreshToken();
    $refreshTokenExpiresAt  = $accessToken->getRefreshTokenExpiresAt();
    
    setConfigurationValue( QUICKBOOKS_ACCESS_TOKEN_KEY, $newAccessTokenKey );
    setConfigurationValue( QUICKBOOKS_REFRESH_TOKEN_KEY, $newRefreshTokenKey );
    setConfigurationValue( QUICKBOOKS_REFRESH_TOKEN_EXPIRES_AT, $refreshTokenExpiresAt );
    
    echo createJSONMessage( GENERAL_SUCCESS_MESSAGE, __FUNCTION__, "Tokens have been stored !" );
}

function test_sparse_update_invoice(){
    $dataService = getQuickBooksDataService();
    
    $invoiceObj = QuickBooksOnline\API\Facades\Invoice::create([
        'Id' => 162
    ]);
    
    // Create the directories that are necessary to store Invoice
    $invoiceStorageDirPath      = "appstv_crm_order" . FILE_SEPARATOR . "data" . FILE_SEPARATOR . "orders" . FILE_SEPARATOR . "xxx" . FILE_SEPARATOR . "invoice" . FILE_SEPARATOR;
    $invoiceStorageDirPathAbs   = PLU_PATH . FILE_SEPARATOR . $invoiceStorageDirPath;

    if( !file_exists( $invoiceStorageDirPathAbs ) ){
        if( !mkdir( $invoiceStorageDirPathAbs, 0777, true ) ){
            $responseMessages[] = "Failed to create directories to store invoices. Please report to administrator immediately !";
        }
    }

    $invoiceFileName        = "Invoice_xxx" . ".pdf";
    $invoiceStoragePathAbs  = $invoiceStorageDirPathAbs . FILE_SEPARATOR . $invoiceFileName;

    $downloadedFilePath = $dataService->DownloadPDF( $invoiceObj, $invoiceStorageDirPathAbs );
    //$downloadedFileName = basename( $downloadedFilePath );

    if( file_exists( $invoiceStoragePathAbs ) ){
        @unlink( $invoiceStoragePathAbs );
    }

    rename( $downloadedFilePath, $invoiceStoragePathAbs );

    if( file_exists( $downloadedFilePath ) ){
        @unlink( $downloadedFilePath );
    }
    
    // Send the Invoice to the Customer Billing Address Email using Scodezy
    $siteConfig = getSiteConfig();

    
    $mail = sendMailObject();
    $mail->isHTML( true );
    $mail->setFrom( EMAIL_NOREPLY, 'Invoice | ' . $siteConfig->site_name );
    $mail->AddAddress( 'sohail@excel.com.sg' );
    $mail->addBCC( 'support@appstv.com.sg' );
    $mail->Subject = "Invoice for your order #xxx with APPSTV";

    $message = file_get_contents( "templates/email/myappstv_automatic_invoice_to_customer.php" );

    $mail->Body = $message;
    $mail->addAttachment( $invoiceStoragePathAbs, '', 'base64' );

    if ( !$mail->send() ) {
        $responseMessages[] = "Invoice did not sent to the customer on email ! ";

        // Log here about email not sending out
    }
    
    /*
    $invoiceID = 163;
    $invoiceQuickBooksObject  = $dataService->Query( "SELECT * FROM Invoice where Id='$invoiceID'" );
    $invoiceQuickBooksObject  = reset( $invoiceQuickBooksObject );
    $error = $dataService->getLastError();
    if ( $error ) {
        $responseMessages[] = "No such invoice exist on QuickBooks: " . $error->getResponseBody();
    }
    print_r( $invoiceQuickBooksObject );

    $syncToken = $invoiceQuickBooksObject->SyncToken;

    $invoiceUpdateParams = array(
        "sparse" => 'true',
        "SyncToken" => $syncToken
    );
    
    $invoiceNewLine = array(
        "Description" => "S/N: xxx",
        "DetailType" => "DescriptionOnly"
    );
    
    $existingQBLines    = $invoiceQuickBooksObject->Line;
    $newQBLine          = new QuickBooksOnline\API\Data\IPPLine( $invoiceNewLine );
    
    array_push( $existingQBLines, $newQBLine );
    
    $invoiceQuickBooksObject->Line = $existingQBLines;
    
    $quickbooksInvoiceUpdateObject    = QuickBooksOnline\API\Facades\Invoice::update( $invoiceQuickBooksObject, $invoiceUpdateParams );
    //$quickbooksInvoiceUpdateObject    = QuickBooksOnline\API\Facades\Invoice::update( $invoiceQuickBooksObject, $existingQBLines );
    $resultingInvoiceObj              = $dataService->Update( $quickbooksInvoiceUpdateObject );
    //print_r( $resultingCustomerObj );
    $error                            = $dataService->getLastError();
    if ( $error ) {
        //echo "The Status code is: " . $error->getHttpStatusCode() . "\n";
        //echo "The Helper message is: " . $error->getOAuthHelperError() . "\n";
        //echo "The Response message is: " . $error->getResponseBody() . "\n";

        // Failed to update the Invoice on QuickBooks
        $responseMessages[] = "Failed to update the Invoice on QuickBooks: " . $error->getResponseBody();
        $invoiceUpdated = false;
    }
     * 
     */
}
?>