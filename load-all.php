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

display_php_errors();

?>