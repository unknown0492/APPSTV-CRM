<?php

//namespace CRMProduct;

class Product {
    
    private $productID;                                  // Product ID in the CRM system 
    private $sku;                                        //  Unique identification code for Stock Keeping in the Company 
    private $gtin;                                       //  Universally unique barcode for the product 
    private $title;                                      //  Store Listing Title for the product 
    private $name;                                       //  Name for the product 
    private $description;                                //  
    private $picture;                                    // 
    private $weight;                                     // 
    private $height;                                     // 
    private $width;                                      // 
    private $depth;                                      // 
    private $price;                                      // The listing/selling price of the product
    private $taxIncluded;                                //  Is the tax included in the product price ? 
    private $physicalProduct;                            //  Whether its a product or a service 
    private $inventory;                                  // The total stock of this product with the Company | No more in use
    private $hasSN;                                      // Whether this product has a Serial Number   1 | 0
    
    
    public const DB_FIELD_PRODUCT_ID                 = "product_id";
    public const DB_FIELD_SKU                        = "sku";
    public const DB_FIELD_GTIN                       = "gtin";
    public const DB_FIELD_TITLE                      = "title";
    public const DB_FIELD_NAME                       = "name";
    public const DB_FIELD_DESCRIPTION                = "description";
    public const DB_FIELD_PICTURE                    = "picture";
    public const DB_FIELD_WEIGHT                     = "weight";
    public const DB_FIELD_HEIGHT                     = "height";
    public const DB_FIELD_WIDTH                      = "width";
    public const DB_FIELD_DEPTH                      = "depth";
    public const DB_FIELD_PRICE                      = "price";
    public const DB_FIELD_TAX_INCLUDED               = "tax_included";
    public const DB_FIELD_PHYSICAL_PRODUCT           = "physical_product";
    public const DB_FIELD_INVENTORY                  = "inventory";         // No more in use
    public const DB_FIELD_HAS_SERIAL_NUMBER          = "has_sn";         
    
    /**
     * Default constructor. 
     * Use this constructor in order to create a Product Object to store it in the Database
     */
    function __construct(){}
    
    /**
     * Generate a Unique Product ID
     * Note: This does not create a DB entry yet for the Product ID
     * 
     * @return String Uniquely generated order id for the CRM
     */
    public static function generateProductID(){
        $date = new DateTime( "now", new DateTimeZone( "+0800" ) );
        $year         = $date->format( 'y' );
        $month        = $date->format( 'm' );
        $day          = $date->format( 'd' );
        $product_id   = $month . $day . $year . generateRandomNumber( 4 );
        return $product_id;
    }
    
    /**
     * Creates a DB row from the Product Object
     * 
     * @return bool|null True when the DB entry is successfully created. Null otherwise
     */
    public function createProduct(){
        // If the order id was not generated and set into the object before calling this function, then automtaically generate the order id
        if( $this->getProductID() === NULL ){
            $this->setProductID( self::generateProductID() );
        }
        
        $product_id                   = $this->getProductID();
        $sku                          = $this->getSku();
        $gtin                         = $this->getGtin();
        $title                        = $this->getTitle();
        $name                         = $this->getName();
        $description                  = $this->getDescription();
        $picture                      = $this->getPicture();
        $weight                       = $this->getWeight();
        $height                       = $this->getHeight();
        $width                        = $this->getWidth();
        $depth                        = $this->getDepth();
        $price                        = $this->getPrice();
        $tax_included                 = $this->getTaxIncluded();
        $physical_product             = $this->getPhysicalProduct();
        //$inventory                    = $this->getInventory();
        $has_sn                       = $this->getHasSN();
        
        $sql = "INSERT INTO `products` (`product_id`, `sku`, `gtin`, `title`, `name`, `description`, `picture`, `weight`, `height`, `width`, `depth`, `price`, `tax_included`, `physical_product`, `has_sn`) "
                           . "VALUES ("
                . "'$product_id', "
                . "'$sku', "
                . "'$gtin', "
                . "'$title', "
                . "'$name', "
                . "'$description', "
                . "'$picture', "
                . "'$weight', "
                . "'$height', "
                . "'$width', "
                . "'$depth', "
                . "'$price', "
                . "'$tax_included', "
                . "'$physical_product', "
                . "'$has_sn' ); ";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
    }
    
    
    /**
     * Query the products table and fetch the Product Data into a Product Object
     * 
     * @param type $productID
     */
    public function readProduct( $productID ){
        if( $productID === NULL ){
            return NULL;
        }
        
        $sql = "SELECT * FROM products WHERE product_id='$productID'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        
        $val = mysqli_fetch_assoc( $result_set );
        
        $this->setProductID( $val[ self::DB_FIELD_PRODUCT_ID ] );
        
        $this->setSku( $val[ self::DB_FIELD_SKU ] );
        
        $this->setGtin( $val[ self::DB_FIELD_GTIN ] );
        
        $this->setTitle( $val[ self::DB_FIELD_TITLE ] );
        
        $this->setName( $val[ self::DB_FIELD_NAME ] );
        
        $this->setDescription( $val[ self::DB_FIELD_DESCRIPTION ] );
        
        $this->setPicture( $val[ self::DB_FIELD_PICTURE ] );
        
        $this->setWeight( $val[ self::DB_FIELD_WEIGHT ] );
        
        $this->setHeight( $val[ self::DB_FIELD_HEIGHT ] );
        
        $this->setWidth( $val[ self::DB_FIELD_WIDTH ] );
        
        $this->setDepth( $val[ self::DB_FIELD_DEPTH ] );
        
        $this->setPrice( $val[ self::DB_FIELD_PRICE ] );
        
        $this->setTaxIncluded( $val[ self::DB_FIELD_TAX_INCLUDED ] );
        
        $this->setPhysicalProduct( $val[ self::DB_FIELD_PHYSICAL_PRODUCT ] );
        
        $this->setHasSN( $val[ self::DB_FIELD_HAS_SERIAL_NUMBER ] );
        
        //$this->setInventory( $val[ self::DB_FIELD_INVENTORY ] );
        
        // $this->set( $val[ self::DB_FIELD_ ] );
        
        return $this;
    }
    
    /**
     * Query the products table and fetch the Product Data into a Product Object
     * 
     * @param type $sku
     */
    public function readProductUsingSKU( $sku ){
        if( $sku === NULL ){
            return NULL;
        }
        
        // Retrieve product_id using SKU
        $sql = "SELECT * FROM products WHERE sku='$sku'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        
        $val = mysqli_fetch_assoc( $result_set );
        $product_id = $val[ 'product_id' ];
        
        return $this->readProduct( $product_id );
        
    }
    
    public function updateProduct(){
        if( ($this->getProductID() === "") || ($this->getProductID() === NULL) ){            
            return false;
        }
        // These fields not to be updated
        $product_id           = $this->getProductID();
        
        // These fields are to be updated
        $sku                  = $this->getSku();
        $gtin                 = $this->getGtin();
        $title                = $this->getTitle();
        $name                 = $this->getName();
        $description          = $this->getDescription();
        $picture              = $this->getPicture();
        $weight               = $this->getWeight();
        $height               = $this->getHeight();
        $width                = $this->getWidth();
        $depth                = $this->getDepth();
        $price                = $this->getPrice();
        $tax_included         = $this->getTaxIncluded();
        $physical_product     = $this->getPhysicalProduct();
        //$inventory            = $this->getInventory();
        $has_sn               = $this->getHasSN();
        
        
        
        $update_params = "";
        if( $sku !== NULL ){
            $update_params .= "`" . self::DB_FIELD_SKU . "`='$sku', ";
        }
        if( $gtin !== NULL ){
            $update_params .= "`" . self::DB_FIELD_GTIN . "`='$gtin', ";
        }
        if( $title !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TITLE . "`='$title', ";
        }
        if( $name !== NULL ){
            $update_params .= "`" . self::DB_FIELD_NAME . "`='$name', ";
        }
        if( $description !== NULL ){
            $update_params .= "`" . self::DB_FIELD_DESCRIPTION . "`='$description', ";
        }
        if( $picture !== NULL ){
            $update_params .= "`" . self::DB_FIELD_PICTURE . "`='$picture', ";
        }
        if( $weight !== NULL ){
            $update_params .= "`" . self::DB_FIELD_WEIGHT . "`='$weight', ";
        }
        if( $height !== NULL ){
            $update_params .= "`" . self::DB_FIELD_HEIGHT . "`='$height', ";
        }
        if( $width !== NULL ){
            $update_params .= "`" . self::DB_FIELD_WIDTH . "`='$width', ";
        }
        if( $depth !== NULL ){
            $update_params .= "`" . self::DB_FIELD_DEPTH . "`='$depth', ";
        }
        if( $price !== NULL ){
            $update_params .= "`" . self::DB_FIELD_PRICE . "`='$price', ";
        }
        if( $tax_included !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TAX_INCLUDED . "`='$tax_included', ";
        }
        if( $physical_product !== NULL ){
            $update_params .= "`" . self::DB_FIELD_PHYSICAL_PRODUCT . "`='$physical_product', ";
        }
        /*if( $inventory !== NULL ){
            $update_params .= "`" . self::DB_FIELD_INVENTORY . "`='$inventory', ";
        }*/
        if( $has_sn !== NULL ){
            $update_params .= "`" . self::DB_FIELD_HAS_SERIAL_NUMBER . "`='$has_sn', ";
        }
        
        /*
        if( !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ . "`='', ";
        }
        */
        $update_params = rtrim( $update_params, ", " );
        
        // Update only those columns that are NOT NULL
        $sql = "UPDATE products SET $update_params WHERE product_id='$product_id'";
        $rows = updateQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
        
    }
    
    /**
     * Checks whether the Product ID exists in the system
     * 
     * @return bool True if the Product ID is found in the system. False otherwise
     */
    public function exists(){
        $sql = "SELECT product_id FROM products WHERE product_id='{$this->getProductID()}'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set  ) > 0 ){
            $val = mysqli_fetch_object( $result_set );
            if( $val->product_id === $this->getProductID() ){
                return true;
            }
        }
        return false;
    }
    
    public function getHasSN() {
        return $this->hasSN;
    }

    public function setHasSN($hasSN): void {
        $this->hasSN = $hasSN;
    }
    /*
    public function getInventory() {
        return $this->inventory;
    }

    public function setInventory($inventory): void {
        $this->inventory = $inventory;
    }
    */
    public function getProductID() {
        return $this->productID;
    }

    public function getSku() {
        return $this->sku;
    }

    public function getGtin() {
        return $this->gtin;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getName() {
        return $this->name;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getPicture() {
        return $this->picture;
    }

    public function getWeight() {
        return $this->weight;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getDepth() {
        return $this->depth;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getTaxIncluded() {
        return $this->taxIncluded;
    }

    public function getPhysicalProduct() {
        return $this->physicalProduct;
    }

    public function setProductID($productID): void {
        $this->productID = $productID;
    }

    public function setSku($sku): void {
        $this->sku = $sku;
    }

    public function setGtin($gtin): void {
        $this->gtin = $gtin;
    }

    public function setTitle($title): void {
        $this->title = $title;
    }

    public function setName($name): void {
        $this->name = $name;
    }

    public function setDescription($description): void {
        $this->description = $description;
    }

    public function setPicture($picture): void {
        $this->picture = $picture;
    }

    public function setWeight($weight): void {
        $this->weight = $weight;
    }

    public function setHeight($height): void {
        $this->height = $height;
    }

    public function setWidth($width): void {
        $this->width = $width;
    }

    public function setDepth($depth): void {
        $this->depth = $depth;
    }

    public function setPrice($price): void {
        $this->price = $price;
    }

    public function setTaxIncluded($taxIncluded): void {
        $this->taxIncluded = $taxIncluded;
    }

    public function setPhysicalProduct($physicalProduct): void {
        $this->physicalProduct = $physicalProduct;
    }

}

class ProductMeta extends Product{
    
    private $id;
    private $productID;
    private $productMetaKey;
    private $productMetaValue;

    // ProductMeta Entity Database MetaKey    
    public const DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_ID                         = "shopify_product_id";
    public const DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_HANDLE                     = "shopify_product_handle";
    public const DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_TITLE                      = "shopify_product_title";
    public const DB_PRODUCT_META_KEY_SHOPIFY_INVENTORY_COUNT                    = "shopify_inventory_count";
    public const DB_PRODUCT_META_KEY_SHOPIFY_INVENTORY_ITEM_ID                  = "shopify_inventory_item_id";
    public const DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_PRICE                      = "shopify_product_price";
    public const DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_REQUIRE_SHIPPING           = "shopify_product_required_shipping";
    public const DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_TAXABLE                    = "shopify_product_taxable";
    public const DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_TOTAL_DISCOUNT             = "shopify_product_total_discount";
    
    public const DB_PRODUCT_META_KEY_QUICKBOOKS_PRODUCT_ID                      = "quickbooks_product_id";
    public const DB_PRODUCT_META_KEY_QUICKBOOKS_INVENTORY_COUNT                 = "quickbooks_inventory_count";
    //public const DB_PRODUCT_META_KEY_SHOPIFY_           = "";
    
    
    public function __construct() {
        parent::__construct();
    }
    
    
    /**
    * This function will create or update a record in the Product Meta table
    * It will first check if a combination of $product_id and $product_meta_key exist.
    * If exist, it will update the $product_meta_value, else it will create a fresh record
    * 
    * @param string $product_id The product_id of the user for which the record is being created
    * @param string $product_meta_key The unique key for the product_id to identify the purpose of the record
    * @param string $product_meta_value The value for the record
    * @return bool True on successful entry or update. False on failure
    */
    public static function setProductMetaValue( $product_id, $product_meta_key, $product_meta_value ){
        // Check if a combination of $product_id and $product_meta_key already exist
        $sql = "SELECT * FROM product_meta WHERE (product_id='$product_id') AND (product_meta_key='$product_meta_key')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $sql = "UPDATE product_meta SET product_meta_value='$product_meta_value' WHERE ((product_id='$product_id') AND (product_meta_key='$product_meta_key'))";
                $rows = updateQuery( $sql );
                if( $rows > 0 ){
                    return true;
                }
                return false;
            }
        }

        // Fresh insert into the product_meta table
        $sql = "INSERT INTO product_meta( `product_id`, `product_meta_key`, `product_meta_value` ) "
                . "VALUES( '$product_id', '$product_meta_key', '$product_meta_value' )";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
    }
    
    
    /**
    * Remove a record from the DB Table product_meta table for the given $product_id and $product_meta_key
    * 
    * @param string $product_id The product_id of the user for which the record is being deleted
    * @param string $product_meta_key The unique key for the product_id to identify the purpose of the record
    * @return bool True on successful delete. False on failure
    */
    public static function deleteProductMetaValue( $product_id, $product_meta_key ){
        // Check if a combination of $product_id and $product_meta_key already exist
        $sql = "SELECT * FROM product_meta WHERE (product_id='$product_id') AND (product_meta_key='$product_meta_key')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $sql = "DELETE FROM product_meta WHERE ((product_id='$product_id') AND (product_meta_key='$product_meta_key'))";
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
    * Retrieve a record from the DB Table product_meta table for the given $product_id and $product_meta_key
    * 
    * @param string $product_id The product_id of the user for which the record is being retrieved
    * @param string $product_meta_key The unique key for the $product_id to identify the purpose of the record
    * @return mixed value if exists else NULL
    */
    public static function getProductMetaValue( $product_id, $product_meta_key ){
        // Check if a combination of $product_id and $product_meta_key already exist
        $sql = "SELECT * FROM product_meta WHERE (product_id='$product_id') AND (product_meta_key='$product_meta_key')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $val = mysqli_fetch_object( $result_set );
                return $val->product_meta_value;
            }
        }
        return NULL;
    }
    
    
    /**
    * Retrieve a record from the DB Table product_meta table for the given $product_id and $product_meta_value
    * 
    * @param string $product_id The product_id of the user for which the record is being retrieved
    * @param string $product_meta_value The unique key for the $product_id to identify the purpose of the record
    * @return mixed value if exists else NULL
    */
    public static function getProductMetaKey( $product_id, $product_meta_value ){
        // Check if a combination of $product_id and $product_meta_key already exist
        $sql = "SELECT * FROM product_meta WHERE (product_id='$product_id') AND (product_meta_value='$product_meta_value')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $val = mysqli_fetch_object( $result_set );
                return $val->product_meta_key;
            }
        }
        return NULL;
    }
    
    
    /**
    * Retrieve a record from the DB Table product_meta table for the given $product_id and $product_meta_value
    * 
    * @param string $product_meta_key 
    * @param string $product_meta_value 
    * @return mixed value if exists else NULL
    */
    public static function getProductIdFromProductMetaKeyValue( $product_meta_key, $product_meta_value ){
        $sql = "SELECT * FROM product_meta WHERE (product_meta_key='$product_meta_key') AND (product_meta_value='$product_meta_value')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $val = mysqli_fetch_object( $result_set );
                return $val->product_id;
            }
        }
        return NULL;
    }
    
    
    public static function getShopifyProductID( $product_id ){
        return self::getProductMetaValue( $product_id, self::DB_PRODUCT_META_KEY_SHOPIFY_PRODUCT_ID );
    }
    
    public static function getQuickBooksProductID( $product_id ){
        return self::getProductMetaValue( $product_id, self::DB_PRODUCT_META_KEY_QUICKBOOKS_PRODUCT_ID );
    }
}

class ProductInventoryHistory extends Product{
    
    private $productID;                                  // The ID of the product in the CRM system
    private $sourceID;                                  // The source_id of the source store
    private $inventory;                                  // The count of the items in the inventory
    private $updatedAt;                                  // The time at which the inventory count was updated by the user
    
    // Database Column Names
    public const DB_FIELD_PRODUCT_ID            = "product_id";
    public const DB_FIELD_SOURCE_ID             = "source_id";
    public const DB_FIELD_INVENTORY             = "inventory";
    public const DB_FIELD_UPDATED_AT            = "updated_at";
    
    public function __construct() {
        parent::__construct();
    }
    
    
    /**
     * Add an entry to the product_inventory_history table whenever the product inventory is restocked/updated
     * 
     * @param string $product_id The product_id of the product whose inventory has been updated
     * @param string $inventory The new count for the stock of the product (Not the sum value of old + new)
     * @return boolean true on successful insert, false on failure
     */
    public static function addToProductInventoryHistory( $product_id, $source_id, $inventory ){
        $iso_8601_datetime = date( 'c' );
        $sql = "INSERT INTO product_inventory_history( `product_id`, `source_id`, `inventory`, `updated_at` ) "
                . "VALUES( '$product_id', '$source_id', '$inventory', '$iso_8601_datetime' );";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
    }
    
    /**
     * Retrieve the latest value of inventory vs updated_at combination in the form of array, for the given product_id
     * 
     * @param type $product_id The product_id of the CRM products table
     * @return mixed NULL when the value does not exist. Array containing inventory vs updated_at pair 
     */
    public static function getLatestInventoryHistoryForProduct( $product_id, $source_id ){
        $sql = "SELECT inventory, source_id, updated_at FROM product_inventory_history WHERE ((product_id='$product_id') AND (source_id='$source_id')) ORDER BY id DESC LIMIT 0,1";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        $val = mysqli_fetch_assoc( $result_set );
        return $val;
    }
    
}

class ProductSN extends Product{
    
    private $id;                                  // The AI Primary Key for the table
    private $productSnID;                         // The Unique Key for the table
    private $productID;                           // The Product ID from the products table
    private $serialNumber;                        // The actual serial number of the product
    private $createdOn;                           // DateTime in iso 8601 format of the day when the serial nos are created in the DB
    private $allottedToCustomer;                  // Denotes whether the serial number has been allotted to a customer or not
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Generate a Unique Product SN ID
     * Note: This does not create a DB entry yet
     * 
     * @return String Uniquely generated id
     */
    public static function generateProductSnID(){
        $date = new DateTime( "now", new DateTimeZone( "+0800" ) );
        $day            = $date->format( 'd' );
        $millis         = $date->format( 'v' );
        $product_sn_id  = $day . $millis . generateRandomNumber( 5 );
        return $product_sn_id;
    }
    
    public static function generateOrderProductSnID(){
        $date = new DateTime( "now", new DateTimeZone( "+0800" ) );
        $day            = $date->format( 'd' );
        $millis         = $date->format( 'v' );
        $order_product_sn_id  = $day . $millis . generateRandomNumber( 5 );
        return $order_product_sn_id;
    }
    
    /**
     * Creates a DB row from the ProductSN Object
     * 
     * @return bool|null True when the DB entry is successfully created. Null otherwise
     */
    public function createProductSerialNumber(){
        // If the product_sn_id id was not generated and set into the object before calling this function, then automtaically generate the product_sn_id
        if( $this->getProductSnID() === NULL ){
            $this->setProductSnID( self::generateProductSnID() );
        }
        if( $this->getCreatedOn() === NULL ){
            $this->setCreatedOn( create_iso_8601_datetime() );
        }
        
        $product_sn_id                   = $this->getProductSnID();
        $product_id                      = $this->getProductID();
        $product_serial_number           = $this->getSerialNumber();
        $created_on                      = $this->getCreatedOn();
        
        $sql = "INSERT INTO `product_sn` (`product_sn_id`, `product_id`, `serial_number`, `created_on`) "
                           . "VALUES ("
                . "'$product_sn_id', "
                . "'$product_id', "
                . "'$product_serial_number', "
                . "'$created_on' )";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
    }
    
    public function exists(){
        $product_serial_number  = $this->getSerialNumber();
        $product_id             = $this->getProductID();
        
        if( $product_serial_number === NULL ){
            return false;
        }
        if( $product_id === NULL ){
            return false;
        }
        
        $sql            = "SELECT * FROM product_sn WHERE (serial_number='$product_serial_number') AND (product_id='$product_id')";
        $result_set     = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return false;
        }
        return true;
    }
    
    public function markSerialNumberAsAllotted(){
        $product_sn_id  = $this->getProductSnID();
        /*
        $product_id     = $this->getProductID();
        
        $product_id_sql = '';
        if( $product_id !== NULL ){
            $product_id_sql = " AND (product_id='$product_id')";
        }
        */
        if( $product_sn_id === NULL ){
            return false;
        }
        
        //$sql = "UPDATE product_sn SET allotted_to_customer='1' WHERE (product_sn_id='$product_sn_id') $product_id_sql";
        $sql = "UPDATE product_sn SET allotted_to_customer='1' WHERE (product_sn_id='$product_sn_id')";
        $rows = updateQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
    }
    
    public function readProductSerialNumber(){
        $product_serial_number  = $this->getSerialNumber();
        $product_id             = $this->getProductID();
        
        if( $product_serial_number === NULL ){
            return NULL;
        }
        if( $product_id === NULL ){
            return NULL;
        }
        
        $sql            = "SELECT * FROM product_sn WHERE (serial_number='$product_serial_number') AND (product_id='$product_id')";
        $result_set     = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        $val = mysqli_fetch_assoc( $result_set );
        
        $this->setProductSnID( $val[ 'product_sn_id' ] );
        $this->setCreatedOn( $val[ 'created_on' ] );
        $this->setAllottedToCustomer( $val[ 'allotted_to_customer' ] );
        
        return true;
    }
    
    public function isAllottedToCustomer(){
        $product_serial_number  = $this->getSerialNumber();
        $product_id             = $this->getProductID();
        
        if( $product_serial_number === NULL ){
            return false;
        }
        if( $product_id === NULL ){
            return false;
        }
        
        $sql            = "SELECT allotted_to_customer FROM product_sn WHERE (serial_number='$product_serial_number') AND (product_id='$product_id')";
        $result_set     = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return false;
        }
        $val = mysqli_fetch_assoc( $result_set );
        
        if( $val[ 'allotted_to_customer' ] == '1' ){
            return true;
        }
        return false;
    }
    
    public function addToOrderProductSerialNumber( $order_id ){
        $product_sn_id          = $this->getProductSnID();
        $product_id             = $this->getProductID();
        
        if( $order_id === NULL ){
            return false;
        }
        if( $product_sn_id === NULL ){
            return false;
        }
        if( $product_id === NULL ){
            return false;
        }
        
        $orders_product_sn_id = self::generateOrderProductSnID();
        
        $sql            = "INSERT INTO order_product_sn( `orders_product_sn_id`, `order_id`, `product_id`, `product_sn_id` ) "
                . "VALUES( '$orders_product_sn_id', '$order_id', '$product_id', '$product_sn_id' )";
        $rows           = insertQuery( $sql );
        if( $rows === 0 ){
            return false;
        }
        return true;
    }
    
    public function getAllottedToCustomer() {
        return $this->allottedToCustomer;
    }

    public function setAllottedToCustomer($allottedToCustomer): void {
        $this->allottedToCustomer = $allottedToCustomer;
    }
    
    public function getCreatedOn() {
        return $this->createdOn;
    }

    public function setCreatedOn($createdOn): void {
        $this->createdOn = $createdOn;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getProductSnID() {
        return $this->productSnID;
    }

    public function getProductID() {
        return $this->productID;
    }

    public function getSerialNumber() {
        return $this->serialNumber;
    }

    public function setId($id): void {
        $this->id = $id;
    }

    public function setProductSnID($productSnID): void {
        $this->productSnID = $productSnID;
    }

    public function setProductID($productID): void {
        $this->productID = $productID;
    }

    public function setSerialNumber($serialNumber): void {
        $this->serialNumber = $serialNumber;
    }


    
}
?>