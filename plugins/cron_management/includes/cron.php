<?php

class CRON{
    
    private $id;                                  // The ID AI Primary Key
    private $functionalityName;                   // The function name under which the logic was executing
    private $status;                              // error, success or warning
    private $executedAt;                          // iso_8601 datetime
    private $result;                              // The output
    
    public function __construct(){
    }
    
    /**
     * Logs the CRON results into the cron_logs table
     * 
     * @param type $params Takes an array as input containing all the parameters
     * @return bool True on successful logging. False otherwise
     */
    public static function log( $params ){
        $functionality_name = '';
        $status = '';
        $executed_at = '';
        $result = '';
        
        if( isset( $params[ 'functionality_name' ] ) ){
            $functionality_name = escape_string( $params[ 'functionality_name' ] );
        }
        if( isset( $params[ 'status' ] ) ){
            $status = escape_string( $params[ 'status' ] );
        }
        if( isset( $params[ 'executed_at' ] ) ){
            $executed_at = escape_string( $params[ 'executed_at' ] );
        }
        if( isset( $params[ 'result' ] ) ){
            $result = escape_string( $params[ 'result' ] );
        }
        
        $sql = "INSERT INTO cron_logs( `functionality_name`, `status`, `executed_at`, `result` ) VALUES( "
                . "'$functionality_name', "
                . "'$status', "
                . "'$executed_at', "
                . "'$result' )";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        
        return false;
    }
}

?>