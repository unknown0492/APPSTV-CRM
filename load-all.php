<?php


define( "DIRNAME_INCLUDES", "includes" );
define( "DIRNAME_CONFIGURATIONS", "configurations" );
define( "DIRNAME_LIBRARY", "library" );
define( "DIRNAME_PLUGINS", "plugins" );
define( "DIRNAME_TEMPLATES", "templates" );
define( "DIRNAME_DATA", "data" );


define( "BASE_PATH", dirname( __FILE__ ) );


define( "INC_PATH", BASE_PATH . "/" . DIRNAME_INCLUDES );


define( "CON_PATH", BASE_PATH . "/" . DIRNAME_CONFIGURATIONS );


define( "LIB_PATH", BASE_PATH . "/" . DIRNAME_LIBRARY );


define( "PLU_PATH", BASE_PATH . "/" . DIRNAME_PLUGINS );


define( "TPL_PATH", BASE_PATH . "/" . DIRNAME_TEMPLATES );


define( "DAT_PATH", BASE_PATH . "/" . DIRNAME_DATA );


require_once CON_PATH . '/config.php';
require_once LIB_PATH . '/regexp.php';
require_once INC_PATH . '/constants.php';
require_once INC_PATH . '/scodezy-constants.php';
require_once LIB_PATH . '/Classes/MyRegex.php';
require_once LIB_PATH . '/Classes/FormHelper.php';
require_once LIB_PATH . '/custom_global_functions_includes.php';
require_once LIB_PATH . '/custom_functions.php';
require_once LIB_PATH . '/functions.php';
require_once LIB_PATH . '/site_settings_functions.php';         // Website Configuration settings (1.0.58)


// Include all plugin /includes and /functions folder files
$pluginsDirPath = PLU_PATH . "/";
$pluginsDirectory = scandir( $pluginsDirPath );
$includedDirectories = array( "includes", "functions" );
$include_paths      = array();
$functions_paths    = array();
for( $i = 0 ; $i < count( $pluginsDirectory ) ; $i++ ){
    if( ($pluginsDirectory[ $i ] == "..") || ($pluginsDirectory[ $i ] == ".") ){
        continue;
    }
        
    if( is_dir( $pluginsDirPath . $pluginsDirectory[ $i ] ) ){
        $pluginsDirContent = scandir( $pluginsDirPath . $pluginsDirectory[ $i ] );        
        foreach ( $includedDirectories as $dirToInclude ) {
            if( in_array( $dirToInclude, $pluginsDirContent ) ){
                $dirPath = $pluginsDirPath . $pluginsDirectory[ $i ] . FILE_SEPARATOR . $dirToInclude . FILE_SEPARATOR;
                //echo $dirPath . "\n<br />";
                if( is_dir( $dirPath ) ){
                    $phpFiles = scandir( $dirPath );
                    for( $j = 0 ; $j < count( $phpFiles ) ; $j++ ){
                        $path = $dirPath . $phpFiles[ $j ];
                        if( is_dir( $path ) || str_ends_with( $path, "backup" ) ){
                            continue;
                        }
                        include_once $dirPath . $phpFiles[ $j ];
                    }
                }
            }
        }
    }
}

// 1.0.41: PHP errors and notices go to the PHP error log, never into pages or API answers. Shown here since the
// first version, they broke every JSON answer that calls QuickBooks once the site ran PHP 8.5 (the QuickBooks SDK
// prints a "Deprecated: curl_close()" notice) and showed internals to visitors. Still shown on a local PC.
if( in_array( isset( $_SERVER[ 'SERVER_NAME' ] ) ? $_SERVER[ 'SERVER_NAME' ] : '', array( 'localhost', '127.0.0.1' ), true ) ){
    display_php_errors();
}
else{
    ini_set( 'display_errors', '0' );
    ini_set( 'display_startup_errors', '0' );
    ini_set( 'log_errors', '1' );
    error_reporting( E_ALL );
}

?>