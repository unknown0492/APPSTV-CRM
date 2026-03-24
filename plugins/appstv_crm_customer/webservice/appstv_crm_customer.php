<?php

//include PLU_PATH . FILE_SEPARATOR . "appstv_crm_customer/includes" . FILE_SEPARATOR . "constants.php";
//include PLU_PATH . FILE_SEPARATOR . "appstv_crm_customer/includes" . FILE_SEPARATOR . "customer.php";


/**
 * This function converts the parameters sent from a Third Party 
 * Source into a format that is acceptable by our CRM System
 * Example: The shopify calls this function with its own sets of parameters.
 * This function will convert those parameters into a set of input that is acceptable by our CRM API function
 * 
 */
function create_customer_third_party(){
    /**
     * 1. Identify the source of the API Call
     * 2. Prepare an appropriate response headers desired by the caller
     * 3. Prepare appropriate response codes desired by the caller
     * 4. Prepare appropriate response desired by the caller
     */
}

/**
 * 
 */
function create_customer(){
    global $customerSources;
    print_r( $_REQUEST );
    
    $title              = request( 'title' );
    $first_name         = request( 'first_name' );
    $middle_name        = request( 'middle_name' );
    $last_name          = request( 'last_name' );
    $suffix             = request( 'suffix' );
    $primary_email      = request( 'primary_email' );
    $alternate_email    = request( 'alternate_email' );
    $currency           = request( 'currency' );
    $primary_mobile     = request( 'primary_mobile' );
    $alternate_mobile   = request( 'alternate_mobile' );
    $primary_phone      = request( 'primary_phone' );
    $alternate_phone    = request( 'alternate_phone' );
    $notes              = request( 'notes' );
    $company            = request( 'company' );
    $shipping_address   = request( 'shipping_address' );    // This is an array
    $billing_address    = request( 'billing_address' );     // This is an array
    $source             = request( 'source' );
    
    // header( 'Connection: Keep-Alive' );  // For shopify WebHooks call
    
    // Required Fields
    validateEmptyString( $first_name, __FUNCTION__, "First name for the customer is required !" );
    
    // Validate Fields
    validate( $title, __FUNCTION__, getValidationRegex( "VLDTN_FIRST_NAME" ), "Title contains invalid characters" );
    validate( $first_name, __FUNCTION__, getValidationRegex( "VLDTN_FIRST_NAME" ), "First Name contains invalid characters" );
    validate( $middle_name, __FUNCTION__, getValidationRegex( "VLDTN_FIRST_NAME" ), "Middle Name contains invalid characters" );
    validate( $last_name, __FUNCTION__, getValidationRegex( "VLDTN_LAST_NAME" ), "Last Name contains invalid characters" );
    validate( $suffix, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "Suffix contains invalid characters" );
    validate( $primary_email, __FUNCTION__, getValidationRegex( "VLDTN_EMAIL" ), "Primary email contains invalid characters" );
    validate( $alternate_email, __FUNCTION__, getValidationRegex( "VLDTN_EMAIL" ), "Alternate email contains invalid characters" );
    validate( $currency, __FUNCTION__, getValidationRegex( "VLDTN_CURRENCY_SHORTCODE" ), "Currency shortcode contains invalid characters" );
    validate( $primary_mobile, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Primary mobile contains invalid characters" );
    validate( $alternate_mobile, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Alternate mobile contains invalid characters" );
    validate( $primary_phone, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Primary phone contains invalid characters" );
    validate( $alternate_phone, __FUNCTION__, getValidationRegex( "VLDTN_DIGITS" ), "Alternate phone contains invalid characters" );
    validate( $notes, __FUNCTION__, getValidationRegex( "VLDTN_CUSTOMER_NOTES" ), "Customer notes contains invalid characters" );
    validate( $company, __FUNCTION__, getValidationRegex( "VLDTN_COMPANY_NAME" ), "Company name contains invalid characters" );
    
    // Source is one of the values from defined values
    if( in_array( $source, $customerSources ) ){
        $source = "Unidentified Source";
    }
    
    // Shipping Address is a breakup of multiple parameters, so take it as an associate-array
    if( $shipping_address !== NULL ){
        $line_1     = $shipping_address[ 'line_1' ];
        $line_2     = $shipping_address[ 'line_2' ];
        $province   = $shipping_address[ 'province' ];
        $city       = $shipping_address[ 'city' ];
        $postcode   = $shipping_address[ 'postcode' ];
        $country    = $shipping_address[ 'country' ];
        
        validate( $line_1, __FUNCTION__, getValidationRegex( "VLDTN_ADDRESS_LINE" ), getValidationErrMsg( "VLDTN_ADDRESS_LINE" ) . " for address line" );
        validate( $line_2, __FUNCTION__, getValidationRegex( "VLDTN_ADDRESS_LINE" ), getValidationErrMsg( "VLDTN_ADDRESS_LINE" ) . " for address line" );
        validate( $province, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "Province contains invalid characters" );
        validate( $city, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "City contains invalid characters" );
        validate( $postcode, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "Postcode contains invalid characters" );
        validate( $country, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "Country contains invalid characters" );
    }
    
    // Billing Address is a breakup of multiple parameters, so take it as an associate-array
    if( $billing_address !== NULL ){
        $line_1     = $billing_address[ 'line_1' ];
        $line_2     = $billing_address[ 'line_2' ];
        $province   = $billing_address[ 'province' ];
        $city       = $billing_address[ 'city' ];
        $postcode   = $billing_address[ 'postcode' ];
        $country    = $billing_address[ 'country' ];
        
        validate( $line_1, __FUNCTION__, getValidationRegex( "VLDTN_ADDRESS_LINE" ), getValidationErrMsg( "VLDTN_ADDRESS_LINE" ) . " for address line" );
        validate( $line_2, __FUNCTION__, getValidationRegex( "VLDTN_ADDRESS_LINE" ), getValidationErrMsg( "VLDTN_ADDRESS_LINE" ) . " for address line" );
        validate( $province, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "Province contains invalid characters" );
        validate( $city, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "City contains invalid characters" );
        validate( $postcode, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "Postcode contains invalid characters" );
        validate( $country, __FUNCTION__, getValidationRegex( "VLDTN_ALPHANUMERIC" ), "Country contains invalid characters" );
    }
    
    
    
}


?>