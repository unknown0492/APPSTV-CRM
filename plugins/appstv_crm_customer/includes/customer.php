<?php


class Customer {
    
    private $customerID;                                                // --> This is the CRM customer ID 
    private $customerSource;                                            // --> The source system on which this customer exist. Foreign Key of sources->source_id
    private $userID;							// --> Foreign Key from Users Table
    private $title;
    private $firstName;
    private $middleName;
    private $lastName;
    private $primaryEmail;
    private $alternateEmail;
    private $createdAt;
    private $updatedAt;
    private $state;
    private $primaryMobile;
    private $alternateMobile;
    private $currency;
    
    public const DB_FIELD_CUSTOMER_ID           = "customer_id";
    public const DB_FIELD_CUSTOMER_SOURCE       = "customer_source";
    public const DB_FIELD_USER_ID               = "user_id";
    public const DB_FIELD_TITLE                 = "title";
    public const DB_FIELD_FIRST_NAME            = "first_name";
    public const DB_FIELD_MIDDLE_NAME           = "middle_name";
    public const DB_FIELD_LAST_NAME             = "last_name";
    public const DB_FIELD_PRIMARY_EMAIL         = "primary_email";
    public const DB_FIELD_ALTERNATE_EMAIL       = "alternate_email";
    public const DB_FIELD_CREATED_AT            = "created_at";
    public const DB_FIELD_UPDATED_AT            = "updated_at";
    public const DB_FIELD_STATE                 = "state";
    public const DB_FIELD_PRIMARY_MOBILE        = "primary_mobile";
    public const DB_FIELD_ALTERNATE_MOBILE      = "alternate_mobile";
    public const DB_FIELD_CURRENCY              = "currency";
    
    /**
     * Default constructor. 
     * Use this constructor in order to create a Customer Object to store it in the Database
     */
    function __construct(){
        
    }
    
    /**
     * Generate a Unique Customer ID
     * Note: This does not create a DB entry yet for the Customer ID
     * 
     * @return String Uniquely generated customer id for the CRM
     */
    public static function generateCustomerID(){
        //$this->customerID = generateUniqueID( "cr" );
        //return generateUniqueID( "cr" );
        $date = new DateTime( "now", new DateTimeZone( "+0800" ) );
        $year       = $date->format( 'y' );
        $month      = $date->format( 'm' );
        $day        = $date->format( 'd' );
        $customer_id   = $day . $month . $year . generateRandomNumber( 4 );
        return $customer_id;
    }
    
    /**
     * Creates a DB row from the Customer Object
     * 
     */
    public function createCustomer(){
        // If the customer id was not generated and set into the object before calling this function, then automtaically generate the customer id
        if( $this->getCustomerID() === NULL ){
            $this->setCustomerID( self::generateCustomerID() );
        }
        
        $customer_id            = $this->getCustomerID();
        $customer_source        = ($this->getCustomerSource() === NULL)?'':$this->getCustomerSource();                                            
        $user_id                = ($this->getUserID() === NULL)?'':$this->getUserID();
        $title                  = ($this->getTitle() === NULL)?'':$this->getTitle(); 
        $first_name             = ($this->getFirstName() === NULL)?'':$this->getFirstName();
        $middle_name            = ($this->getMiddleName() === NULL)?'':$this->getMiddleName();
        $last_name              = ($this->getLastName() === NULL)?'':$this->getLastName();
        $primary_email          = ($this->getPrimaryEmail() === NULL)?'':$this->getPrimaryEmail();
        $alternate_email        = ($this->getAlternateEmail() === NULL)?'':$this->getAlternateEmail();
        $created_at             = ($this->getCreatedAt() === NULL)?'':$this->getCreatedAt();
        $updated_at             = ($this->getUpdatedAt() === NULL)?'':$this->getUpdatedAt();
        $state                  = ($this->getState() === NULL)?'':$this->getState();
        $primary_mobile         = ($this->getPrimaryMobile() === NULL)?'':$this->getPrimaryMobile();
        $alternate_mobile       = ($this->getAlternateMobile() === NULL)?'':$this->getAlternateMobile();
        $currency               = ($this->getCurrency() === NULL)?'':$this->getCurrency();
        
        $sql = "INSERT INTO `customers` (`customer_id`, `customer_source`, `user_id`, `title`, `first_name`, `middle_name`, `last_name`, `primary_email`, `alternate_email`, `created_at`, `updated_at`, `state`, `primary_mobile`, `alternate_mobile`, `currency`) "
                . "VALUES ( '$customer_id', '$customer_source', '$user_id', '$title', '$first_name', '$middle_name', '$last_name', '$primary_email', '$alternate_email', '$created_at', '$updated_at', '$state', '$primary_mobile', '$alternate_mobile', '$currency' );";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return $this;
        }
        return null;
    }
    
    /**
     * Query the customers table and fetch the Customer Data into a Customer Object
     * 
     * @param type $customerID
     */
    public function readCustomer( $customerID ){
        if( $customerID === NULL ){
            return null;
        }
        
        //$c = new Customer();
        
        $sql = "SELECT * FROM customers WHERE customer_id='$customerID'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return null;
        }
        
        $val = mysqli_fetch_assoc( $result_set );
        
        $this->setCustomerID( $val[ self::DB_FIELD_CUSTOMER_ID ] );
        
        $this->setCustomerSource( $val[ self::DB_FIELD_CUSTOMER_SOURCE ] );
        
        $this->setUserID( $val[ self::DB_FIELD_USER_ID ] );
        
        $this->setTitle( $val[ self::DB_FIELD_TITLE ] );
        
        $this->setFirstName( $val[ self::DB_FIELD_FIRST_NAME ] );
        
        $this->setMiddleName( $val[ self::DB_FIELD_MIDDLE_NAME ] );
        
        $this->setLastName( $val[ self::DB_FIELD_LAST_NAME ] );
        
        $this->setPrimaryEmail( $val[ self::DB_FIELD_PRIMARY_EMAIL ] );
        
        $this->setAlternateEmail( $val[ self::DB_FIELD_ALTERNATE_EMAIL ] );
        
        $this->setCreatedAt( $val[ self::DB_FIELD_CREATED_AT ] );
        
        $this->setUpdatedAt( $val[ self::DB_FIELD_UPDATED_AT ] );
        
        $this->setState( $val[ self::DB_FIELD_STATE ] );
        
        $this->setPrimaryMobile( $val[ self::DB_FIELD_PRIMARY_MOBILE ] );
        
        $this->setAlternateMobile( $val[ self::DB_FIELD_ALTERNATE_MOBILE ] );
        
        $this->setCurrency( $val[ self::DB_FIELD_CURRENCY ] );
        
        return $this;
    }
    
    public function updateCustomer(){
        if( ($this->getCustomerID() === "") || ($this->getCustomerID() === NULL) ){            
            return false;
        }
        // These fields not to be updated
        $customer_id            = $this->getCustomerID();
        $user_id                = $this->getUserID();
        $created_at             = $this->getCreatedAt();
        
        // These fields are to be updated
        $customer_source        = $this->getCustomerSource();                                            
        $title                  = $this->getTitle(); 
        $first_name             = $this->getFirstName();
        $middle_name            = $this->getMiddleName();
        $last_name              = $this->getLastName();
        $primary_email          = $this->getPrimaryEmail();
        $alternate_email        = $this->getAlternateEmail();
        $updated_at             = $this->getUpdatedAt();
        $state                  = $this->getState();
        $primary_mobile         = $this->getPrimaryMobile();
        $alternate_mobile       = $this->getAlternateMobile();
        $currency               = $this->getCurrency();
        
        $update_params = "";
        if( $customer_source !== NULL ){
            $update_params .= "`" . self::DB_FIELD_CUSTOMER_SOURCE . "`='$customer_source', ";
        }
        if( $title !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TITLE . "`='$title', ";
        }
        if( $first_name !== NULL ){
            $update_params .= "`" .  self::DB_FIELD_FIRST_NAME . "`='$first_name', ";
        }
        if( $middle_name !== NULL ){
            $update_params .= "`" . self::DB_FIELD_MIDDLE_NAME . "`='$middle_name', ";
        }
        if( $last_name !== NULL ){
            $update_params .= "`" . self::DB_FIELD_LAST_NAME . "`='$last_name', ";
        }
        if( $primary_email!== NULL ){
            $update_params .= "`" . self::DB_FIELD_PRIMARY_EMAIL . "`='$primary_email', ";
        }
        if( $alternate_email !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ALTERNATE_EMAIL . "`='$alternate_email', ";
        }
        if( $updated_at !== NULL ){
            $update_params .= "`" . self::DB_FIELD_UPDATED_AT . "`='$updated_at', ";
        }
        if( $state !== NULL ){
            $update_params .= "`" . self::DB_FIELD_STATE . "`='$state', ";
        }
        if( $primary_mobile !== NULL ){
            $update_params .= "`" . self::DB_FIELD_PRIMARY_MOBILE . "`='$primary_mobile', ";
        }
        if( $alternate_mobile !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ALTERNATE_MOBILE . "`='$alternate_mobile', ";
        }
        if( $currency !== NULL ){
            $update_params .= "`" . self::DB_FIELD_CURRENCY . "`='$currency', ";
        }
        /*
        if( !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ . "`='', ";
        }
        */
        $update_params = rtrim( $update_params, ", " );
        
        // Update only those columns that are NOT NULL
        $sql = "UPDATE customers SET $update_params WHERE customer_id='$customer_id'";
        $rows = updateQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
        
    }
    
    
    public function getCustomerID() {
        return $this->customerID;
    }

    public function getCustomerSource() {
        return $this->customerSource;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getFirstName() {
        return $this->firstName;
    }

    public function getMiddleName() {
        return $this->middleName;
    }

    public function getLastName() {
        return $this->lastName;
    }

    public function getPrimaryEmail() {
        return $this->primaryEmail;
    }

    public function getAlternateEmail() {
        return $this->alternateEmail;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function getState() {
        return $this->state;
    }

    public function getPrimaryMobile() {
        return $this->primaryMobile;
    }

    public function getAlternateMobile() {
        return $this->alternateMobile;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function setCustomerID($customerID): void {
        $this->customerID = $customerID;
    }

    public function setCustomerSource($customerSource): void {
        $this->customerSource = $customerSource;
    }

    public function setUserID($userID): void {
        $this->userID = $userID;
    }

    public function setTitle($title): void {
        $this->title = $title;
    }

    public function setFirstName($firstName): void {
        $this->firstName = $firstName;
    }

    public function setMiddleName($middleName): void {
        $this->middleName = $middleName;
    }

    public function setLastName($lastName): void {
        $this->lastName = $lastName;
    }

    public function setPrimaryEmail($primaryEmail): void {
        $this->primaryEmail = $primaryEmail;
    }

    public function setAlternateEmail($alternateEmail): void {
        $this->alternateEmail = $alternateEmail;
    }

    public function setCreatedAt($createdAt): void {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt($updatedAt): void {
        $this->updatedAt = $updatedAt;
    }

    public function setState($state): void {
        $this->state = $state;
    }

    public function setPrimaryMobile($primaryMobile): void {
        $this->primaryMobile = $primaryMobile;
    }

    public function setAlternateMobile($alternateMobile): void {
        $this->alternateMobile = $alternateMobile;
    }

    public function setCurrency($currency): void {
        $this->currency = $currency;
    }

    
    
}


class CustomerMeta extends Customer{
    
    private $id;
    private $customerID;
    private $customerMetaKey;
    private $customerMetaValue;
    
    // CustomerMeta Entity Database MetaKey    
    public const DB_CUSTOMER_META_KEY_SHOPIFY_EMAIL_MARKETING_CONSENT           = "shopify_email_marketing_consent";
    public const DB_CUSTOMER_META_KEY_SHOPIFY_CUSTOMER_ID                       = "shopify_customer_id";
    public const DB_CUSTOMER_META_KEY_QUICKBOOKS_CUSTOMER_ID                    = "quickbooks_customer_id";
            
    function __construct() {
        parent::__construct();
    }
    
    /**
    * This function will create or update a record in the Customer Meta table
    * It will first check if a combination of $customer_id and $customer_meta_key exist.
    * If exist, it will update the $customer_meta_value, else it will create a fresh record
    * 
    * @param string $customer_id The customer_id of the user for which the record is being created
    * @param string $customer_meta_key The unique key for the customer_id to identify the purpose of the record
    * @param string $customer_meta_value The value for the record
    * @return bool True on successful entry or update. False on failure
    */
   public static function setCustomerMetaValue( $customer_id, $customer_meta_key, $customer_meta_value ){
       // Check if a combination of $customer_id and $customer_meta_key already exist
       $sql = "SELECT * FROM customer_meta WHERE (customer_id='$customer_id') AND (customer_meta_key='$customer_meta_key')";
       $result_set = selectQuery( $sql );
       if( $result_set !== NULL ){
           if( mysqli_num_rows( $result_set ) > 0 ){
               $sql = "UPDATE customer_meta SET customer_meta_value='$customer_meta_value' WHERE ((customer_id='$customer_id') AND (customer_meta_key='$customer_meta_key'))";
               $rows = updateQuery( $sql );
               if( $rows > 0 ){
                   return true;
               }
               return false;
           }
       }

       // Fresh insert into the customer_meta table
       $sql = "INSERT INTO customer_meta( `customer_id`, `customer_meta_key`, `customer_meta_value` ) "
               . "VALUES( '$customer_id', '$customer_meta_key', '$customer_meta_value' )";
       $rows = insertQuery( $sql );
       if( $rows > 0 ){
           return true;
       }
       return false;
   }

   /**
    * Remove a record from the DB Table customer_meta table for the given $customer_id and $customer_meta_key
    * 
    * @param string $customer_id The customer_id of the user for which the record is being deleted
    * @param string $customer_meta_key The unique key for the customer_id to identify the purpose of the record
    * @return bool True on successful delete. False on failure
    */
   public static function deleteCustomerMetaValue( $customer_id, $customer_meta_key ){
       // Check if a combination of $customer_id and $customer_meta_key already exist
       $sql = "SELECT * FROM customer_meta WHERE (customer_id='$customer_id') AND (customer_meta_key='$customer_meta_key')";
       $result_set = selectQuery( $sql );
       if( $result_set !== NULL ){
           if( mysqli_num_rows( $result_set ) > 0 ){
               $sql = "DELETE FROM customer_meta WHERE ((customer_id='$customer_id') AND (customer_meta_key='$customer_meta_key'))";
               $rows = deleteQuery( $sql );
               if( $rows > 0 ){
                   return true;
               }
               return false;
           }
       }
       return false;
   }

    /**
     * Retrieve a record from the DB Table customer_meta table for the given $customer_id and $customer_meta_key
     * 
     * @param string $customer_id The customer_id of the user for which the record is being retrieved
     * @param string $customer_meta_key The unique key for the $customer_id to identify the purpose of the record
     * @return mixed value if exists else NULL
     */
    public static function getCustomerMetaValue( $customer_id, $customer_meta_key ){
        // Check if a combination of $customer_id and $customer_meta_key already exist
        $sql = "SELECT * FROM customer_meta WHERE (customer_id='$customer_id') AND (customer_meta_key='$customer_meta_key')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $val = mysqli_fetch_object( $result_set );
                return $val->customer_meta_value;
            }
        }
        return NULL;
    }
   
   
   /**
    * Retrieve a record from the DB Table customer_meta table for the given $customer_id and $customer_meta_value
    * 
    * @param string $customer_id The customer_id of the user for which the record is being retrieved
    * @param string $customer_meta_value The unique key for the $customer_id to identify the purpose of the record
    * @return mixed value if exists else NULL
    */
    public static function getCustomerMetaKey( $customer_id, $customer_meta_value ){
        // Check if a combination of $customer_id and $customer_meta_key already exist
        $sql = "SELECT * FROM customer_meta WHERE (customer_id='$customer_id') AND (customer_meta_value='$customer_meta_value')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $val = mysqli_fetch_object( $result_set );
                return $val->customer_meta_key;
            }
        }
        return NULL;
    }
   
   
    /**
     * Retrieve a record from the DB Table customer_meta table for the given $customer_id and $customer_meta_value
     * 
     * @param string $customer_id The customer_id of the user for which the record is being retrieved
     * @param string $customer_meta_value The unique key for the $customer_id to identify the purpose of the record
     * @return mixed value if exists else NULL
     */
     public static function getCustomerIdFromCustomerMetaKeyValue( $customer_meta_key, $customer_meta_value ){
         // 
         $sql = "SELECT * FROM customer_meta WHERE (customer_meta_key='$customer_meta_key') AND (customer_meta_value='$customer_meta_value')";
         $result_set = selectQuery( $sql );
         if( $result_set !== NULL ){
             if( mysqli_num_rows( $result_set ) > 0 ){
                 $val = mysqli_fetch_object( $result_set );
                 return $val->customer_id;
             }
         }
         return NULL;
     }


}
