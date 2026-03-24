<?php
	
    // Validation Configurations
    $validation_array = array(
        /* Generic Validations */
        "VLDTN_DIGITS" => array( "TYPE" => "text", "REGEX" => "/^[0-9]*$/" , "ERR_MSG" => "Should be a Digit from 0 to 9" ),
        "VLDTN_DIGITS_INC_NEGATIVE" => array( "TYPE" => "text", "REGEX" => "/^-?[0-9]*$/" , "ERR_MSG" => "Should be a Digit from 0 to 9" ),
        "VLDTN_ICON" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-]*$/" , "ERR_MSG" => "Compliant with only fontawesome.io icon names" ),
        "VLDTN_ALPHANUMERIC" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9]*$/" , "ERR_MSG" => "Should contain alphabets and digits only" ),
        "VLDTN_ALPHANUMERIC_SPACE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 ]*$/" , "ERR_MSG" => "Should contain alphabets and digits only" ),
        "VLDTN_SINGLE_BINARY" => array( "TYPE" => "text", "REGEX" => "/^[0-1]$/" , "ERR_MSG" => "Should be a single binary number" ),
        //"VLDTN_URL" => array( "TYPE" => "text", "REGEX" => '_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!10(?:\.\d{1,3}){3})(?!127(?:\.\d{1,3}){3})(?!169\.254(?:\.\d{1,3}){2})(?!192\.168(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,})))(?::\d{2,5})?(?:/[^\s]*)?$_iuS', "ERR_MSG" => "URL entered is invalid" ),

        /* Plugin Management Related Validations */
        "VLDTN_PLUGIN_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-z0-9\_]*$/", "ERR_MSG" => "Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed" ),
        "VLDTN_PLUGIN_ALIAS" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9\s]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed" ),
        "VLDTN_PLUGIN_VERSION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9\s\.]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and dots (.) are allowed" ),
        
        /* User Management Related Validations */
        "VLDTN_USER_ID" => array( "TYPE" => "text", "REGEX" => "/^[a-z0-9\_]{3,25}$/" , "ERR_MSG" => "User ID can contain lowercase alphabets a to z, digits 0 to 9 and an underscore _ and min. 3, max. 20 characters" ),
        "VLDTN_PASSWORD" => array( "TYPE" => "password", "REGEX" => "/^[a-zA-Z0-9\_\@\#]{3,20}$/", "ERR_MSG" => "Password should be minimum 3 characters, maximum 20 characters, can contain lowercase and uppercase alphabets and digits 0 to 9 and special characters underscore, @ and #" ),
        //"VLDTN_EMAIL" => array( "TYPE" => "email", "REGEX" => "/^[a-z]+[a-z0-9._\-]+@[a-z\-]+\.[a-z.]{2,10}$/", "ERR_MSG" => "Email ID is Invalid" ),
        "VLDTN_EMAIL" => array( "TYPE" => "email", "REGEX" => "/^([a-zA-Z0-9+._-]+@[a-zA-Z0-9._-]+\.[a-zA-Z0-9_-]+)$/", "ERR_MSG" => "Email ID is Invalid" ),

        /* Privilege Management Related Validations */
        "VLDTN_PRIVILEGE_GROUP_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_PRIVILEGE_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-z0-9\-\_]*$/", "ERR_MSG" => "Only lowercase alphabets, digits 0 to 9 and characters - _ are allowed" ),
        "VLDTN_FUNCTIONALITY_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-z0-9\-\_]*$/", "ERR_MSG" => "Only lowercase alphabets, digits 0 to 9 and characters - _ are allowed" ),
        "VLDTN_PRIVILEGE_DESCRIPTION" => array( "TYPE" => "textarea", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`]*$/", "ERR_MSG" => "Some special characters are not allowed" ),
        "VLDTN_FUNCTIONALITY_DESCRIPTION" => array( "TYPE" => "textarea", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`]*$/", "ERR_MSG" => "Some special characters are not allowed" ),

        /* Role Related Validations */
        "VLDTN_ROLE_ID" => array( "TYPE" => "text", "REGEX" => "/^[0-9]*$/", "ERR_MSG" => "Only digits are allowed" ),
        "VLDTN_ROLE_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9\s]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed" ),
        "VLDTN_ROLE_SLUG" => array( "TYPE" => "text", "REGEX" => "/^[a-z0-9\_]*$/", "ERR_MSG" => "Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed" ),

        /* User Related Validations */
        "VLDTN_FIRST_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z \'\-]*$/", "ERR_MSG" => "Only lowercase & uppercase letters, spaces, apostrophe and a hyphen are allowed" ),
        "VLDTN_LAST_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z \'\-]*$/", "ERR_MSG" => "Only lowercase & uppercase letters, spaces, apostrophe and a hyphen are allowed" ),
        "VLDTN_NICK_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \'\[\]\(\)\-\<\>\?\{\}\@\#\$\*\+\,\,\s ]*$/", "ERR_MSG" => "Some special characters are not allowed for Nickname" ),

        /* Page Management Related Validations */
        "VLDTN_PAGE_GROUP_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_PAGE_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-z0-9\-\_]*$/", "ERR_MSG" => "Only lowercase alphabets, digits 0 to 9 and characters - _ are allowed" ),
        "VLDTN_PAGE_TITLE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`]*$/", "ERR_MSG" => "Some special characters are not allowed" ),
        "VLDTN_PAGE_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`\\n\\r]*$/", "ERR_MSG" => "Some special characters are not allowed" ),
        "VLDTN_PAGE_TAGS" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 ,\-\_]*$/", "ERR_MSG" => "Only lowercase alphabets, digits 0 to 9 and characters - _ , are allowed" ),
        "VLDTN_PAGE_CONTENT" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`]*$/", "ERR_MSG" => "Some special characters are not allowed" ),
        "VLDTN_PAGE_HIERARCHY" => array( "TYPE" => "text", "REGEX" => "/^[1-3]$/", "ERR_MSG" => "Some special characters are not allowed" ),

        /* Site Config Related Validation */
        "VLDTN_SITE_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_SITE_TAGLINE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),

        /* Launcher Config Related Validation */
        "VLDTN_LAUNCHER_CONFIG_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_LAUNCHER_CONFIG_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_LAUNCHER_ITEM_NAME" => array( "TYPE" => "text", "REGEX" => "/^.*$/", "ERR_MSG" => "All characters are allowed" ),
        "VLDTN_LAUNCHER_ITEM_PACKAGE_NAME" => array( "TYPE" => "text", "REGEX" => "/^.*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),

        /* Digital Signage Config Related Validation */
        "VLDTN_DS_CONFIG_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_DS_CONFIG_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),

        /* Preinstall Config Related Validation */
        "VLDTN_PREINSTALL_CONFIG_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_PREINSTALL_CONFIG_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_PREINSTALL_APP_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_PREINSTALL_APP_PACKAGE_NAME" => array( "TYPE" => "text", "REGEX" => "/^.*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_PREINSTALL_APP_VERSION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_PREINSTALL_APP_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),

        /* Ticker And Hotel Logo Related Validation */
        "VLDTN_TICKER_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \,\'\-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . , ' are allowed" ),
        "VLDTN_TICKER_TEXT" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \,\'\-\_\.\(\)\[\]\:\;\<\>\?\/\!\@\#\$\%\&\*\+\\t\\n\\r]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . , ' are allowed" ),
        "VLDTN_LOGO_ALIAS" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \,\'\-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . , ' are allowed" ),

        /* OTA Related Validation */
        "VLDTN_OTA_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_FIRMWARE_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_FIRMWARE_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`]*$/", "ERR_MSG" => "Some special characters are not allowed" ),

        /* TV Channel File Validation */
        "VLDTN_TVCH_FILE_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_TVCH_FILE_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\`\t\n]*$/", "ERR_MSG" => "Some special characters are not allowed" ),

        /* APPS TV Settings Related Validation */
        "VLDTN_REBOOT_TIME" => array( "TYPE" => "text", "REGEX" => "/^[0-9]{2}:[0-9]{2}$/", "ERR_MSG" => "Should be in the format HH:MM. Example 04:30" ),
        "VLDTN_CMS_IP" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9\-\.:\/]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9 and special characters - . / are allowed" ),
        "VLDTN_SUB_DIRECTORY" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \/]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9 and special character / are allowed" ),
        "VLDTN_COUNTRY" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z ]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets and white spaces are allowed" ),
        "VLDTN_LANGUAGE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z ]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets and white spaces are allowed" ),
        "VLDTN_LOCATION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z ]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets and white spaces are allowed" ),
        "VLDTN_TIMEZONE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z\/]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets and special character / is allowed" ),
        "VLDTN_WEBSERVICE_PATH" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9\_\-\.:\/]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9 and special characters _ - . / are allowed" ),
        "VLDTN_LANGUAGE_ALIAS" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits, white spaces and characters - _ ( ) [ ] are allowed" ),
        "VLDTN_LANGUAGE_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z ]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets and white spaces are allowed" ),
        "VLDTN_LANGUAGE_SHORTCODE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets are allowed" ),

        /* URL Management Related Validations */
        "VLDTN_URL_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9_]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9 and character _ are allowed" ),
        "VLDTN_URL_ALIAS" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9 and characters _ - . ( ) are allowed" ),

        /* MAC ADDRESS Validaiton*/
        "VLDTN_MAC_ADDRESS" => array( "TYPE" => "text", "REGEX" => "/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/", "ERR_MSG" => "Mac address is invalid !" ),

        /* IPTV Configuration */
        "VLDTN_IPTV_CONFIG_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        "VLDTN_IPTV_CONFIG_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\\t\\n]*$/", "ERR_MSG" => "Some special characters are not allowed" ),

        /* Welcome Page MGMT  */
        "VLDTN_WELCOME_MSG" => array( "TYPE" => "text", "REGEX" => "/^[^\\\><'\"]*$/", "ERR_MSG" => "Some special characters are not allowed" ),
        
        /* OTP 5-Digits */
        "VLDTN_OTP_NUMERIC" => array( "TYPE" => "text", "REGEX" => "/^[0-9]*$/", "ERR_MSG" => "OTP is invalid" ),
        
        /* Currency Shortcode */
        "VLDTN_CURRENCY_SHORTCODE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z]*$/", "ERR_MSG" => "Currency shortcode is invalid" ),
        
        /* APPSTV Customer Notes */
        "VLDTN_CUSTOMER_NOTES" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\'\:\/\?\,\<\>\*\&\%\$\#\@\!\\t\\n]*$/", "ERR_MSG" => "Some special characters are not allowed" ),
        
        /* APPSTV Customer Company Name */
        "VLDTN_COMPANY_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]]*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . are allowed" ),
        
        /* APPSTV Customer Address Line */
        "VLDTN_ADDRESS_LINE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\#\']*$/", "ERR_MSG" => "Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and characters ( ) [ ] - _ . ' # are allowed" ),
        
        /* APPSTV CRM Products */
        "VLDTN_PRODUCT_SKU" => array( "TYPE" => "text", "REGEX" => "/^[A-Z0-9]*$/", "ERR_MSG" => "SKU can only contain uppercase letters and numerical digits" ),
        "VLDTN_PRODUCT_NAME" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\#\\x27\\x22\”]*$/", "ERR_MSG" => "Certain special characters are not allowed for product name" ),
        "VLDTN_PRODUCT_TITLE" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\#\\x27\\x22\”]*$/", "ERR_MSG" => "Certain special characters are not allowed for product title" ),
        "VLDTN_PRODUCT_DESCRIPTION" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\\x27\\x22\”\:\/\?\,\<\>\*\&\%\$\#\@\!\\t\\n]*$/", "ERR_MSG" => "Certain special characters are not allowed for product description" ),
        "VLDTN_PRODUCT_DIMENSION" => array( "TYPE" => "text", "REGEX" => "/^[(\d*\.)?\d+]*$/", "ERR_MSG" => "The product dimension is invalid" ),
        "VLDTN_PRODUCT_WEIGHT" => array( "TYPE" => "text", "REGEX" => "/^[(\d*\.)?\d+]*$/", "ERR_MSG" => "The product weight is invalid" ),
        "VLDTN_PRODUCT_PRICE" => array( "TYPE" => "text", "REGEX" => "/^[(\d*\.)?\d+]*$/", "ERR_MSG" => "The product price is invalid" ),
        "VLDTN_PRODUCT_SERIAL_NUMBER" => array( "TYPE" => "text", "REGEX" => "/^[\d]{14}$/", "ERR_MSG" => "The product serial number is invalid" ),
        
        /* APPSTV CRM ORDERS */
        "VLDTN_PREPARE_ORDER_REMARKS" => array( "TYPE" => "text", "REGEX" => "/^[a-zA-Z0-9 \-\_\.\(\)\[\]\\x27\\x22\”\:\/\?\,\<\>\*\&\%\$\#\@\!\\t\\n]*$/", "ERR_MSG" => "Certain special characters are not allowed for remarks" ),
    );
    
    // Escaping single and double quotes in regex: \x27 is single quotes, \x22 is double quotes
?>