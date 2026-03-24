<?php

class Order{
    
    private $orderID;                               // Unique order id to identify the order in the CRM
    private $customerID;                            // Foreign key from the customers table customer_id
    private $orderSource;                           // The store identifier from which the order has been placed (shopify / shop / lazada , etc) Foreign Key of sources->source_id 
    private $createdAt;                             // Time at which the order is placed 
    private $orderStatus;                           // The different stages an order can go through in the CRM system -> pending, processing, awaiting payment, shipped, on hold, refunded, failed, out for delivery, declined, returned, confirmed, disputed, delivered, completed 
    private $isCancelled;                           // CRM column to denote if this order stands cancelled
    private $currency;                              // Currency of order transaction 
    private $contactEmail;                          // Contact email associated with the order 
    private $email;                                 // Contact email of the person who placed the order
    private $financialStatus;                       // paid / 
    private $fulfillmentStatus;                     // 
    private $processedAt;                           // The timestamp at which the order is processed 
    private $products;                              // The products that are in this order. Store in the form of JSON 
    private $shippingInformation;                   // The shipping information received in the form of JSON
    private $shippingMethodName;                    // The name of the shipping method chosen for the delivery 
    private $totalProductsPrice;                    // This is the total sum of the prices of the products as per their listed price 
    private $subtotalPrice;                         // The total price of the products checked out 
    private $taxesIncluded;                         // Whether the taxes are included in this order 
    private $test;                                  // true/false (Whether it is a test order or a real order) 
    private $totalPrice;                            // Amount, Final Total price of the products including taxes minus discounts 
    private $totalDiscounts;                        // Amount, total discount amount for the order 
    private $totalShippingPrice;                    // Amount, the total price for shipping the product 
    private $totalShippingTax;                      // Amount, the total tax on the shipping price
    private $totalTax;                              // Amount, the total amount of taxes charged on this order 
    private $totalWeight;                           // Figure in grams 
    private $updatedAt;                             // The time when the order was updated. Whenever the order is updated, this timestamp will change 
    
    public const DB_FIELD_ORDER_ID                      = "order_id";
    public const DB_FIELD_CUSTOMER_ID                   = "customer_id";
    public const DB_FIELD_ORDER_SOURCE                  = "order_source";
    public const DB_FIELD_CREATED_AT                    = "created_at";
    public const DB_FIELD_ORDER_STATUS                  = "order_status";
    public const DB_FIELD_IS_CANCELLED                  = "is_cancelled";
    public const DB_FIELD_CURRENCY                      = "currency";
    public const DB_FIELD_CONTACT_EMAIL                 = "contact_email";
    public const DB_FIELD_EMAIL                         = "email";
    public const DB_FIELD_FINANCIAL_STATUS              = "financial_status";
    public const DB_FIELD_FULFILLMENT_STATUS            = "fulfillment_status";
    public const DB_FIELD_PROCESSED_AT                  = "processed_at";
    public const DB_FIELD_PRODUCTS                      = "products";
    public const DB_FIELD_SHIPPING_INFORMATION          = "shipping_information";
    public const DB_FIELD_SHIPPING_METHOD_NAME          = "shipping_method_name";
    public const DB_FIELD_TOTAL_PRODUCTS_PRICE          = "total_products_price";
    public const DB_FIELD_SUBTOTAL_PRICE                = "subtotal_price";
    public const DB_FIELD_TAXES_INCLUDED                = "taxes_included";
    public const DB_FIELD_TEST                          = "test";
    public const DB_FIELD_TOTAL_PRICE                   = "total_price";
    public const DB_FIELD_TOTAL_DISCOUNTS               = "total_discounts";
    public const DB_FIELD_TOTAL_SHIPPING_PRICE          = "total_shipping_price";
    public const DB_FIELD_TOTAL_SHIPPING_TAX            = "total_shipping_tax";
    public const DB_FIELD_TOTAL_TAX                     = "total_tax";
    public const DB_FIELD_TOTAL_WEIGHT                  = "total_weight";
    public const DB_FIELD_UPDATED_AT                    = "updated_at";
    
    public const ORDER_STATUS_PROCESSED                 = "processed";
    public const ORDER_STATUS_CONFIRMED                 = "confirmed";
    public const ORDER_STATUS_PREPARED                  = "prepared";
    public const ORDER_STATUS_UNPREPARED                = "unprepared";
    public const ORDER_STATUS_PREPARED_UNDELIVERED      = "prepared_undelivered";
    public const ORDER_STATUS_DELIVERED                 = "delivered";
    public const ORDER_STATUS_PARTIALLY_DELIVERED       = "partially_delivered";
    public const ORDER_STATUS_CANCELLED                 = "cancelled";
    
    /**
     * Default constructor. 
     * Use this constructor in order to create an Order Object to store it in the Database
     */
    function __construct(){
        
    }
    
    /**
     * Generate a Unique Order ID
     * Note: This does not create a DB entry yet for the Order ID
     * 
     * @return String Uniquely generated order id for the CRM
     */
    public static function generateOrderID(){
        $date = new DateTime( "now", new DateTimeZone( "+0800" ) );
        $year       = $date->format( 'y' );
        $month      = $date->format( 'm' );
        $day        = $date->format( 'd' );
        $order_id   = $year . $month . $day . generateRandomNumber( 5 );
        return $order_id;
    }
    
    
    /**
     * Creates a DB row from the Order Object
     * 
     */
    public function createOrder(){
        // If the order id was not generated and set into the object before calling this function, then automtaically generate the order id
        if( $this->getOrderID() === NULL ){
            $this->setOrderID( self::generateOrderID() );
        }
        
        $order_id                        = $this->getOrderID();
        $customer_id                     = $this->getCustomerID();
        $order_source                    = ($this->getOrderSource() === NULL)?'':$this->getOrderSource();                                            
        $created_at                      = ($this->getCreatedAt() === NULL)?'':$this->getCreatedAt();                      
        $order_status                    = ($this->getOrderStatus() === NULL)?'':$this->getOrderStatus();                      
        $is_cancelled                    = ($this->getIsCancelled() === NULL)?'':$this->getIsCancelled();                      
        $currency                        = ($this->getCurrency() === NULL)?'':$this->getCurrency();                                            
        $contact_email                   = ($this->getContactEmail() === NULL)?'':$this->getContactEmail();                                            
        $email                           = ($this->getEmail() === NULL)?'':$this->getEmail();                                            
        $financial_status                = ($this->getFinancialStatus() === NULL)?'':$this->getFinancialStatus();                                            
        $fulfillment_status              = ($this->getFulfillmentStatus() === NULL)?'':$this->getFulfillmentStatus();                                            
        $processed_at                    = ($this->getProcessedAt() === NULL)?'':$this->getProcessedAt();                                            
        $shipping_information            = ($this->getShippingInformation() === NULL)?'':$this->getShippingInformation();                                            
        $products                        = ($this->getProducts() === NULL)?'':$this->getProducts();                                            
        $shipping_method_name            = ($this->getShippingMethodName() === NULL)?'':$this->getShippingMethodName();                                            
        $subtotal_price                  = ($this->getSubtotalPrice() === NULL)?'':$this->getSubtotalPrice();                                            
        $total_products_price            = ($this->getTotalProductsPrice() === NULL)?'':$this->getTotalProductsPrice();                                            
        $taxes_included                  = ($this->getTaxesIncluded() === NULL)?'0':$this->getTaxesIncluded();                                            
        $test                            = ($this->getTest() === NULL)?'0':$this->getTest();                                            
        $total_price                     = ($this->getTotalPrice() === NULL)?'':$this->getTotalPrice();                                            
        $total_discounts                 = ($this->getTotalDiscounts() === NULL)?'':$this->getTotalDiscounts();                                            
        $total_shipping_price            = ($this->getTotalShippingPrice() === NULL)?'':$this->getTotalShippingPrice();                                            
        $total_shipping_tax              = ($this->getTotalShippingTax() === NULL)?'':$this->getTotalShippingTax();                                            
        $total_tax                       = ($this->getTotalTax() === NULL)?'':$this->getTotalTax();                                            
        $total_weight                    = ($this->getTotalWeight() === NULL)?'':$this->getTotalWeight();                                            
        $updated_at                      = ($this->getUpdatedAt() === NULL)?'':$this->getUpdatedAt();                                         
        
        
        $sql = "INSERT INTO `orders` (`order_id`, `customer_id`, `order_source`, `created_at`, `order_status`, `is_cancelled`,`currency`, `contact_email`, `email`, `financial_status`, `fulfillment_status`, `processed_at`, `products`, `shipping_information`, `shipping_method_name`, `total_products_price`, `subtotal_price`, `taxes_included`, `test`, `total_price`, `total_discounts`, `total_shipping_price`, `total_shipping_tax`, `total_tax`, `total_weight`, `updated_at`) "
                           . "VALUES ("
                . "'$order_id', "
                . "'$customer_id', "
                . "'$order_source', "
                . "'$created_at', "
                . "'$order_status', "
                . "'$is_cancelled', "
                . "'$currency', "
                . "'$contact_email', "
                . "'$email', "
                . "'$financial_status', "
                . "'$fulfillment_status', "
                . "'$processed_at', "
                . "'$products', "
                . "'$shipping_information', "
                . "'$shipping_method_name', "
                . "'$total_products_price', "
                . "'$subtotal_price', "
                . "'$taxes_included', "
                . "'$test', "
                . "'$total_price', "
                . "'$total_discounts', "
                . "'$total_shipping_price', "
                . "'$total_shipping_tax', "
                . "'$total_tax', "
                . "'$total_weight', "
                . "'$updated_at' ); ";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return $this;
        }
        return NULL;
    }
    
    /**
     * Query the orders table and fetch the Order Data into a Order Object
     * 
     * @param type $orderID
     */
    public function readOrder( $orderID, $returnAsArray = false ){
        if( $orderID === NULL ){
            return NULL;
        }
        
        $sql = "SELECT * FROM orders WHERE order_id='$orderID'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        
        $val = mysqli_fetch_assoc( $result_set );
        
        $this->setOrderID( $val[ self::DB_FIELD_ORDER_ID ] );
        
        $this->setCustomerID( $val[ self::DB_FIELD_CUSTOMER_ID ] );
        
        $this->setOrderSource( $val[ self::DB_FIELD_ORDER_SOURCE ] );
        
        $this->setCreatedAt( $val[ self::DB_FIELD_CREATED_AT ] );
        
        $this->setOrderStatus( $val[ self::DB_FIELD_ORDER_STATUS ] );
        
        $this->setIsCancelled( $val[ self::DB_FIELD_IS_CANCELLED ] );
        
        $this->setCurrency( $val[ self::DB_FIELD_CURRENCY ] );
        
        $this->setContactEmail( $val[ self::DB_FIELD_CONTACT_EMAIL ] );
        
        $this->setEmail( $val[ self::DB_FIELD_EMAIL ] );
        
        $this->setFinancialStatus( $val[ self::DB_FIELD_FINANCIAL_STATUS ] );
        
        $this->setFulfillmentStatus( $val[ self::DB_FIELD_FULFILLMENT_STATUS ] );
        
        $this->setProcessedAt( $val[ self::DB_FIELD_PROCESSED_AT ] );
        
        $this->setProducts( $val[ self::DB_FIELD_PRODUCTS ] );
        
        $this->setShippingInformation( $val[ self::DB_FIELD_SHIPPING_INFORMATION ] );
        
        $this->setShippingMethodName( $val[ self::DB_FIELD_SHIPPING_METHOD_NAME ] );
        
        $this->setTotalProductsPrice( $val[ self::DB_FIELD_TOTAL_PRODUCTS_PRICE ] );
        
        $this->setSubtotalPrice( $val[ self::DB_FIELD_SUBTOTAL_PRICE ] );
        
        $this->setTaxesIncluded( $val[ self::DB_FIELD_TAXES_INCLUDED ] );
        
        $this->setTest( $val[ self::DB_FIELD_TEST ] );
        
        $this->setTotalPrice( $val[ self::DB_FIELD_TOTAL_PRICE ] );
        
        $this->setTotalDiscounts( $val[ self::DB_FIELD_TOTAL_DISCOUNTS ] );
        
        $this->setTotalShippingPrice( $val[ self::DB_FIELD_TOTAL_SHIPPING_PRICE ] );
        
        $this->setTotalShippingTax( $val[ self::DB_FIELD_TOTAL_SHIPPING_TAX ] );
        
        $this->setTotalTax( $val[ self::DB_FIELD_TOTAL_TAX ] );
        
        $this->setTotalWeight( $val[ self::DB_FIELD_TOTAL_WEIGHT ] );
        
        $this->setUpdatedAt( $val[ self::DB_FIELD_UPDATED_AT ] );
        
        // $this->set( $val[ self::DB_FIELD_ ] );
        if( $returnAsArray )
            return $val;
        else
            return $this;
    }
    
    
    public function updateOrder(){
        if( ($this->getOrderID() === "") || ($this->getOrderID() === NULL) ){
            return false;
        }
        // These fields not to be updated
        $order_id               = $this->getOrderID();
        $customer_id            = $this->getCustomerID();
        
        // These fields are to be updated
        $order_source                = $this->getOrderSource();
        $created_at                  = $this->getCreatedAt();
        $order_status                = $this->getOrderStatus();
        $is_cancelled                = $this->getIsCancelled();
        $currency                    = $this->getCurrency();
        $contact_email               = $this->getContactEmail();
        $email                       = $this->getEmail();
        $financial_status            = $this->getFinancialStatus();
        $fulfillment_status          = $this->getFulfillmentStatus();
        $processed_at                = $this->getProcessedAt();
        $products                    = $this->getProducts();
        $shipping_information        = $this->getShippingInformation();
        $shipping_method_name        = $this->getShippingMethodName();
        $total_products_price        = $this->getTotalProductsPrice();
        $subtotal_price              = $this->getSubtotalPrice();
        $taxes_included              = $this->getTaxesIncluded();
        $test                        = $this->getTest();
        $total_price                 = $this->getTotalPrice();
        $total_discounts             = $this->getTotalDiscounts();
        $total_shipping_price        = $this->getTotalShippingPrice();
        $total_shipping_tax          = $this->getTotalShippingTax();
        $total_tax                   = $this->getTotalTax();
        $total_weight                = $this->getTotalWeight();
        $updated_at                  = $this->getUpdatedAt();
        
        
        $update_params = "";
        if( $order_source !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_SOURCE . "`='$order_source', ";
        }
        if( $order_status !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_STATUS . "`='$order_status', ";
        }
        if( $created_at !== NULL ){
            $update_params .= "`" . self::DB_FIELD_CREATED_AT . "`='$created_at', ";
        }
        if( $is_cancelled !== NULL ){
            $update_params .= "`" . self::DB_FIELD_IS_CANCELLED . "`='$is_cancelled', ";
        }
        if( $currency !== NULL ){
            $update_params .= "`" . self::DB_FIELD_CURRENCY . "`='$currency', ";
        }
        if( $contact_email !== NULL ){
            $update_params .= "`" . self::DB_FIELD_CONTACT_EMAIL . "`='$contact_email', ";
        }
        if( $email !== NULL ){
            $update_params .= "`" . self::DB_FIELD_EMAIL . "`='$email', ";
        }
        if( $financial_status !== NULL ){
            $update_params .= "`" . self::DB_FIELD_FINANCIAL_STATUS . "`='$financial_status', ";
        }
        if( $fulfillment_status !== NULL ){
            $update_params .= "`" . self::DB_FIELD_FULFILLMENT_STATUS . "`='$fulfillment_status', ";
        }
        if( $processed_at !== NULL ){
            $update_params .= "`" . self::DB_FIELD_PROCESSED_AT . "`='$processed_at', ";
        }
        if( $products !== NULL ){
            $update_params .= "`" . self::DB_FIELD_PRODUCTS . "`='$products', ";
        }
        if( $shipping_information !== NULL ){
            $update_params .= "`" . self::DB_FIELD_SHIPPING_INFORMATION . "`='$shipping_information', ";
        }
        if( $shipping_method_name !== NULL ){
            $update_params .= "`" . self::DB_FIELD_SHIPPING_METHOD_NAME . "`='$shipping_method_name', ";
        }
        if( $subtotal_price !== NULL ){
            $update_params .= "`" . self::DB_FIELD_SUBTOTAL_PRICE . "`='$subtotal_price', ";
        }
        if( $total_products_price !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TOTAL_PRODUCTS_PRICE . "`='$total_products_price', ";
        }
        if( $taxes_included !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TAXES_INCLUDED . "`='$taxes_included', ";
        }
        if( $test !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TEST . "`='$test', ";
        }
        if( $total_price !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TOTAL_PRICE . "`='$total_price', ";
        }
        if( $total_discounts !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TOTAL_DISCOUNTS . "`='$total_discounts', ";
        }
        if( $total_shipping_price !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TOTAL_SHIPPING_PRICE . "`='$total_shipping_price', ";
        }
        if( $total_shipping_tax !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TOTAL_SHIPPING_TAX . "`='$total_shipping_tax', ";
        }
        if( $total_tax !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TOTAL_TAX . "`='$total_tax', ";
        }
        if( $total_weight !== NULL ){
            $update_params .= "`" . self::DB_FIELD_TOTAL_WEIGHT . "`='$total_weight', ";
        }
        if( $updated_at !== NULL ){
            $update_params .= "`" . self::DB_FIELD_UPDATED_AT . "`='$updated_at', ";
        }
        /*
        if( !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ . "`='', ";
        }
        */
        $update_params = rtrim( $update_params, ", " );
        
        // Update only those columns that are NOT NULL
        $sql = "UPDATE orders SET $update_params WHERE order_id='$order_id'";
        $rows = updateQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
        
    }
    
    
    public function getTotalProductsPrice() {
        return $this->totalProductsPrice;
    }

    public function setTotalProductsPrice($totalProductsPrice): void {
        $this->totalProductsPrice = $totalProductsPrice;
    }
    
    public function getShippingInformation() {
        return $this->shippingInformation;
    }

    public function getTotalShippingTax() {
        return $this->totalShippingTax;
    }

    public function setShippingInformation($shippingInformation): void {
        if( is_array( $shippingInformation ) )
            $shippingInformation = json_encode( $shippingInformation );
        $this->shippingInformation = $shippingInformation;
    }

    public function setTotalShippingTax($totalShippingTax): void {
        $this->totalShippingTax = $totalShippingTax;
    }

        
    public function getOrderStatus() {
        return $this->orderStatus;
    }

    public function setOrderStatus($orderStatus): void {
        $this->orderStatus = $orderStatus;
    }

        
    public function getIsCancelled() {
        return $this->isCancelled;
    }

    public function setIsCancelled($isCancelled): void {
        $this->isCancelled = $isCancelled;
    }
        
    public function getOrderID() {
        return $this->orderID;
    }

    public function getOrderSource() {
        return $this->orderSource;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function getCurrency() {
        return $this->currency;
    }

    public function getContactEmail() {
        return $this->contactEmail;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getFinancialStatus() {
        return $this->financialStatus;
    }

    public function getFulfillmentStatus() {
        return $this->fulfillmentStatus;
    }

    public function getProcessedAt() {
        return $this->processedAt;
    }

    public function getProducts() {
        return $this->products;
    }

    public function getShippingMethodName() {
        return $this->shippingMethodName;
    }

    public function getSubtotalPrice() {
        return $this->subtotalPrice;
    }

    public function getTaxesIncluded() {
        return $this->taxesIncluded;
    }

    public function getTest() {
        return $this->test;
    }

    public function getTotalPrice() {
        return $this->totalPrice;
    }

    public function getTotalDiscounts() {
        return $this->totalDiscounts;
    }

    public function getTotalShippingPrice() {
        return $this->totalShippingPrice;
    }

    public function getTotalTax() {
        return $this->totalTax;
    }

    public function getTotalWeight() {
        return $this->totalWeight;
    }

    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    public function setOrderID($orderID): void {
        $this->orderID = $orderID;
    }

    public function setOrderSource($orderSource): void {
        $this->orderSource = $orderSource;
    }

    public function setCreatedAt($createdAt): void {
        $this->createdAt = $createdAt;
    }

    public function setCurrency($currency): void {
        $this->currency = $currency;
    }

    public function setContactEmail($contactEmail): void {
        $this->contactEmail = $contactEmail;
    }

    public function setEmail($email): void {
        $this->email = $email;
    }

    public function setFinancialStatus($financialStatus): void {
        $this->financialStatus = $financialStatus;
    }

    public function setFulfillmentStatus($fulfillmentStatus): void {
        $this->fulfillmentStatus = $fulfillmentStatus;
    }

    public function setProcessedAt($processedAt): void {
        $this->processedAt = $processedAt;
    }

    public function setProducts($products): void {
        //if( is_array( $products ) )
            //$products = json_encode( $products );
        $this->products = $products;
    }

    public function setShippingMethodName($shippingMethodName): void {
        //if( is_array( $shippingMethodName ) )
            //$shippingMethodName = json_encode( $shippingMethodName );
        $this->shippingMethodName = $shippingMethodName;
    }

    public function setSubtotalPrice($subtotalPrice): void {
        $this->subtotalPrice = $subtotalPrice;
    }

    public function setTaxesIncluded($taxesIncluded): void {
        if( is_array( $taxesIncluded ) ){
            $taxesIncluded = json_encode( $taxesIncluded );
        }
        else{
            $taxesIncluded = ($taxesIncluded == "true")?"1":"0";
        }
        $this->taxesIncluded = $taxesIncluded;
    }

    public function setTest($test): void {
        $test = ($test == "true")?"1":"0";
        $this->test = $test;
    }

    public function setTotalPrice($totalPrice): void {
        $this->totalPrice = $totalPrice;
    }

    public function setTotalDiscounts($totalDiscounts): void {
        $this->totalDiscounts = $totalDiscounts;
    }

    public function setTotalShippingPrice($totalShippingPrice): void {
        if( is_array( $totalShippingPrice ) )
            $totalShippingPrice = json_encode( $totalShippingPrice );
        $this->totalShippingPrice = $totalShippingPrice;
    }

    public function setTotalTax($totalTax): void {
        $this->totalTax = $totalTax;
    }

    public function setTotalWeight($totalWeight): void {
        $this->totalWeight = $totalWeight;
    }

    public function setUpdatedAt($updatedAt): void {
        $this->updatedAt = $updatedAt;
    }

    public function getCustomerID() {
        return $this->customerID;
    }

    public function setCustomerID($customerID): void {
        $this->customerID = $customerID;
    }



}

class OrderMeta extends Order{
        
    private $id;
    private $orderID;
    private $orderMetaKey;
    private $orderMetaValue;

    // OrderMeta Entity Database MetaKey    
    //public const DB_ORDER_META_KEY_SHOPIFY_           = "";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_ID                              = "shopify_order_id";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_CANCEL_REASON                   = "shopify_order_cancel_reason";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_CANCELLED_AT                    = "shopify_order_cancelled_at";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_NUMBER                          = "shopify_order_number";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_NAME                            = "shopify_order_name";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_CONFIRMED                       = "shopify_order_confirmed";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_STATUS_URL                      = "shopify_order_status_url";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_DISCOUNT_CODES                  = "shopify_order_discount_codes";
    public const DB_ORDER_META_KEY_SHOPIFY_ORDER_PAYMENT_GATEWAY_NAMES           = "shopify_order_payment_gateway_names";

    public const DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_ID                         = "quickbooks_invoice_id";
    public const DB_ORDER_META_KEY_QUICKBOOKS_INVOICE_NUMBER                     = "quickbooks_invoice_number";

    function __construct() {
        parent::__construct();
    }

    /**
    * This function will create or update a record in the Order Meta table
    * It will first check if a combination of $order_id and $order_meta_key exist.
    * If exist, it will update the $order_meta_value, else it will create a fresh record
    * 
    * @param string $order_id The order_id of the user for which the record is being created
    * @param string $order_meta_key The unique key for the order_id to identify the purpose of the record
    * @param string $order_meta_value The value for the record
    * @return bool True on successful entry or update. False on failure
    */
    public static function setOrderMetaValue( $order_id, $order_meta_key, $order_meta_value ){
        // Check if a combination of $order_id and $order_meta_key already exist
        $sql = "SELECT * FROM order_meta WHERE (order_id='$order_id') AND (order_meta_key='$order_meta_key')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $sql = "UPDATE order_meta SET order_meta_value='$order_meta_value' WHERE ((order_id='$order_id') AND (order_meta_key='$order_meta_key'))";
                $rows = updateQuery( $sql );
                if( $rows > 0 ){
                    return true;
                }
                return false;
            }
        }

        // Fresh insert into the order_meta table
        $sql = "INSERT INTO order_meta( `order_id`, `order_meta_key`, `order_meta_value` ) "
                . "VALUES( '$order_id', '$order_meta_key', '$order_meta_value' )";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
    }


    /**
    * Remove a record from the DB Table order_meta table for the given $order_id and $order_meta_key
    * 
    * @param string $order_id The order_id of the user for which the record is being deleted
    * @param string $order_meta_key The unique key for the order_id to identify the purpose of the record
    * @return bool True on successful delete. False on failure
    */
    public static function deleteOrderMetaValue( $order_id, $order_meta_key ){
        // Check if a combination of $order_id and $order_meta_key already exist
        $sql = "SELECT * FROM order_meta WHERE (order_id='$order_id') AND (order_meta_key='$order_meta_key')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $sql = "DELETE FROM order_meta WHERE ((order_id='$order_id') AND (order_meta_key='$order_meta_key'))";
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
    * Retrieve a record from the DB Table order_meta table for the given $order_id and $order_meta_key
    * 
    * @param string $order_id The order_id of the user for which the record is being retrieved
    * @param string $order_meta_key The unique key for the $order_id to identify the purpose of the record
    * @return mixed value if exists else NULL
    */
    public static function getOrderMetaValue( $order_id, $order_meta_key ){
        // Check if a combination of $order_id and $order_meta_key already exist
        $sql = "SELECT * FROM order_meta WHERE (order_id='$order_id') AND (order_meta_key='$order_meta_key')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $val = mysqli_fetch_object( $result_set );
                return $val->order_meta_value;
            }
        }
        return NULL;
    }


    /**
    * Retrieve a record from the DB Table order_meta table for the given $order_id and $order_meta_value
    * 
    * @param string $order_id The order_id of the user for which the record is being retrieved
    * @param string $order_meta_value The unique key for the $order_id to identify the purpose of the record
    * @return mixed value if exists else NULL
    */
    public static function getOrderMetaKey( $order_id, $order_meta_value ){
        // Check if a combination of $order_id and $order_meta_key already exist
        $sql = "SELECT * FROM order_meta WHERE (order_id='$order_id') AND (order_meta_value='$order_meta_value')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $val = mysqli_fetch_object( $result_set );
                return $val->order_meta_key;
            }
        }
        return NULL;
    }


    /**
    * Retrieve a record from the DB Table order_meta table for the given $order_id and $order_meta_value
    * 
    * @param string $order_id The order_id of the user for which the record is being retrieved
    * @param string $order_meta_value The unique key for the $order_id to identify the purpose of the record
    * @return mixed value if exists else NULL
    */
    public static function getOrderIdFromOrderMetaKeyValue( $order_meta_key, $order_meta_value ){
        // 
        $sql = "SELECT * FROM order_meta WHERE (order_meta_key='$order_meta_key') AND (order_meta_value='$order_meta_value')";
        $result_set = selectQuery( $sql );
        if( $result_set !== NULL ){
            if( mysqli_num_rows( $result_set ) > 0 ){
                $val = mysqli_fetch_object( $result_set );
                return $val->order_id;
            }
        }
        return NULL;
    }
}

class OrderAddress extends Order{
    
    private $orderAddressID;            // primary key for the table
    private $orderID;                   // Foreign Key from orders table
    private $addressType;               // billing/shipping
    private $firstName;                 
    private $lastName;
    private $nameOnOrder;               // The name to be printed on the Order Receipt that is pasted on the parcel
    private $contact;                   // The contact number associated with the order
    private $address1;                  // Address Line 1
    private $address2;                  // Address Line 2
    private $city;
    private $state;                     // The state/territory
    private $province;                  // Some countries have province instead of state
    private $zip;
    private $country;
    private $countryCode;               // Alphabetic Country code. Can be used to substitute the phone country code before the contact
    private $latitude;
    private $longitude;
    
    // OrderAddress Entity Database MetaKey    
    //public const DB_ORDER_META_KEY_SHOPIFY_           = "";
    public const DB_FIELD_ORDER_ADDRESS_ID                               = "order_address_id";
    public const DB_FIELD_ORDER_ADDRESS_ORDER_ID                         = "order_id";
    public const DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE                     = "address_type";
    public const DB_FIELD_ORDER_ADDRESS_FIRST_NAME                       = "first_name";
    public const DB_FIELD_ORDER_ADDRESS_LAST_NAME                        = "last_name";
    public const DB_FIELD_ORDER_ADDRESS_NAME_ON_ORDER                    = "name_on_order";
    public const DB_FIELD_ORDER_ADDRESS_CONTACT                          = "contact";
    public const DB_FIELD_ORDER_ADDRESS_ADDRESS1                         = "address1";
    public const DB_FIELD_ORDER_ADDRESS_ADDRESS2                         = "address2";
    public const DB_FIELD_ORDER_ADDRESS_CITY                             = "city";
    public const DB_FIELD_ORDER_ADDRESS_STATE                            = "state";
    public const DB_FIELD_ORDER_ADDRESS_PROVINCE                         = "province";
    public const DB_FIELD_ORDER_ADDRESS_ZIP                              = "zip";
    public const DB_FIELD_ORDER_ADDRESS_COUNTRY                          = "country";
    public const DB_FIELD_ORDER_ADDRESS_COUNTRY_CODE                     = "country_code";
    public const DB_FIELD_ORDER_ADDRESS_LATITUDE                         = "latitude";
    public const DB_FIELD_ORDER_ADDRESS_LONGITUDE                        = "longitude";
    
    // OrderAddress Entity Database MetaKey Values
    public const DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_BILLING             = "billing_address";
    public const DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_SHIPPING            = "shipping_address";
    

    function __construct() {
        parent::__construct();
    }
    
    /**
     * Generate a Unique Order Address ID
     * Note: This does not create a DB entry yet for the Order Address ID
     * 
     * @return String Uniquely generated order address id for the CRM
     */
    public static function generateOrderAddressID(){
        return generateUniqueID( "os" );
    }

    
    /**
    * Creates a DB row from the Order Address Object
    * 
    */
    public function createOrderAddress(){
        // If the order address id was not generated and set into the object before calling this function, then automtaically generate the order address id
        if( $this->getOrderAddressID() === NULL ){
            $this->setOrderAddressID( self::generateOrderAddressID() );
        }
        
        $order_address_id       = $this->getOrderAddressID();
        $order_id               = $this->getOrderID();
        
        $address_type           = ($this->getAddressType() === NULL)?'':$this->getAddressType();
        $first_name             = ($this->getFirstName() === NULL)?'':$this->getFirstName();
        $last_name              = ($this->getLastName() === NULL)?'':$this->getLastName();
        $name_on_order          = ($this->getNameOnOrder() === NULL)?'':$this->getNameOnOrder();
        $contact                = ($this->getContact() === NULL)?'':$this->getContact();
        $address1               = ($this->getAddress1() === NULL)?'':$this->getAddress1();
        $address2               = ($this->getAddress2() === NULL)?'':$this->getAddress2();
        $city                   = ($this->getCity() === NULL)?'':$this->getCity();
        $state                  = ($this->getState() === NULL)?'':$this->getState();
        $province               = ($this->getProvince() === NULL)?'':$this->getProvince();
        $zip                    = ($this->getZip() === NULL)?'':$this->getZip();
        $country                = ($this->getCountry() === NULL)?'':$this->getCountry();
        $country_code           = ($this->getCountryCode() === NULL)?'':$this->getCountryCode();
        $latitude               = ($this->getLatitude() === NULL)?'':$this->getLatitude();
        $longitude              = ($this->getLongitude() === NULL)?'':$this->getLongitude();
        
        
        $sql = "INSERT INTO `order_address` (`order_address_id`, `order_id`, `address_type`, `first_name`, `last_name`, `name_on_order`, `contact`, `address1`, `address2`, `city`, `state`, `province`, `zip`, `country`, `country_code`, `latitude`, `longitude`) "
                . "VALUES ( "
                . "'$order_address_id', "
                . "'$order_id', "
                . "'$address_type', "
                . "'$first_name', "
                . "'$last_name', "
                . "'$name_on_order', "
                . "'$contact', "
                . "'$address1', "
                . "'$address2', "
                . "'$city', "
                . "'$state', "
                . "'$province', "
                . "'$zip', "
                . "'$country', "
                . "'$country_code', "
                . "'$latitude', "
                . "'$longitude'"
                . ");";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return $this;
        }
        return NULL;
    }
    
    
    /**
     * Query the order address table and fetch the Order Address Data into a Order Object
     * 
     * @param type $orderAddressID
     */
    public function readOrderAddress( $orderAddressID ){
        if( $orderAddressID === NULL ){
            return NULL;
        }
        
        $sql = "SELECT * FROM order_address WHERE order_address_id='$orderAddressID'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        
        $val = mysqli_fetch_assoc( $result_set );
        
        $this->setOrderAddressID( $val[ self::DB_FIELD_ORDER_ADDRESS_ID ] );
        
        $this->setOrderID( $val[ self::DB_FIELD_ORDER_ID ] );
        
        $this->setAddressType( $val[ self::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE ] );
        
        $this->setFirstName( $val[ self::DB_FIELD_ORDER_ADDRESS_FIRST_NAME ] );
        
        $this->setLastName( $val[ self::DB_FIELD_ORDER_ADDRESS_LAST_NAME ] );
        
        $this->setNameOnOrder( $val[ self::DB_FIELD_ORDER_ADDRESS_NAME_ON_ORDER ] );
        
        $this->setContact( $val[ self::DB_FIELD_ORDER_ADDRESS_CONTACT ] );
        
        $this->setAddress1( $val[ self::DB_FIELD_ORDER_ADDRESS_ADDRESS1 ] );
        
        $this->setAddress2( $val[ self::DB_FIELD_ORDER_ADDRESS_ADDRESS2 ] );
        
        $this->setCity( $val[ self::DB_FIELD_ORDER_ADDRESS_CITY ] );
        
        $this->setState( $val[ self::DB_FIELD_ORDER_ADDRESS_STATE ] );
        
        $this->setProvince( $val[ self::DB_FIELD_ORDER_ADDRESS_PROVINCE ] );
        
        $this->setZip( $val[ self::DB_FIELD_ORDER_ADDRESS_ZIP ] );
        
        $this->setCountry( $val[ self::DB_FIELD_ORDER_ADDRESS_COUNTRY ] );
        
        $this->setCountryCode( $val[ self::DB_FIELD_ORDER_ADDRESS_COUNTRY_CODE ] );
        
        $this->setLatitude( $val[ self::DB_FIELD_ORDER_ADDRESS_LATITUDE ] );
        
        $this->setLongitude( $val[ self::DB_FIELD_ORDER_ADDRESS_LONGITUDE ] );
        
        // $this->set( $val[ self::DB_FIELD_ ] );
        
        return $this;
    }
    
    /**
     * Query the order address table using the $orderID and retrieve all the 
     * 
     * @param type $orderID
     */
    public function readAllOrderAddressForOrderID( $orderID ){
        if( $orderID === NULL ){
            return NULL;
        }
        
        $sql = "SELECT * FROM order_address WHERE order_id='$orderID'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        
        $orderAddresses = array();
        
        while( ( $val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
            $orderAddress = new OrderAddress();
            
            $orderAddress->setOrderAddressID( $val[ self::DB_FIELD_ORDER_ADDRESS_ID ] );
        
            $orderAddress->setOrderID( $val[ self::DB_FIELD_ORDER_ID ] );

            $orderAddress->setAddressType( $val[ self::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE ] );

            $orderAddress->setFirstName( $val[ self::DB_FIELD_ORDER_ADDRESS_FIRST_NAME ] );

            $orderAddress->setLastName( $val[ self::DB_FIELD_ORDER_ADDRESS_LAST_NAME ] );

            $orderAddress->setNameOnOrder( $val[ self::DB_FIELD_ORDER_ADDRESS_NAME_ON_ORDER ] );

            $orderAddress->setContact( $val[ self::DB_FIELD_ORDER_ADDRESS_CONTACT ] );

            $orderAddress->setAddress1( $val[ self::DB_FIELD_ORDER_ADDRESS_ADDRESS1 ] );

            $orderAddress->setAddress2( $val[ self::DB_FIELD_ORDER_ADDRESS_ADDRESS2 ] );

            $orderAddress->setCity( $val[ self::DB_FIELD_ORDER_ADDRESS_CITY ] );

            $orderAddress->setState( $val[ self::DB_FIELD_ORDER_ADDRESS_STATE ] );

            $orderAddress->setProvince( $val[ self::DB_FIELD_ORDER_ADDRESS_PROVINCE ] );

            $orderAddress->setZip( $val[ self::DB_FIELD_ORDER_ADDRESS_ZIP ] );

            $orderAddress->setCountry( $val[ self::DB_FIELD_ORDER_ADDRESS_COUNTRY ] );
            
            $orderAddress->setCountryCode( $val[ self::DB_FIELD_ORDER_ADDRESS_COUNTRY_CODE ] );

            $orderAddress->setLatitude( $val[ self::DB_FIELD_ORDER_ADDRESS_LATITUDE ] );

            $orderAddress->setLongitude( $val[ self::DB_FIELD_ORDER_ADDRESS_LONGITUDE ] );
            
            array_push( $orderAddresses, $orderAddress );
        }
        
        return $orderAddresses;
        
    }
    
    public function readOrderAddressBilling(){
        $order_id = $this->getOrderID();
        
        if( $order_id === NULL ){
            return NULL;
        }
        
        $sql = "SELECT * FROM order_address WHERE (order_id='$order_id') AND (address_type='". self::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE_BILLING ."')";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        $val = mysqli_fetch_object( $result_set );
        $order_address_id = $val->order_address_id;
        
        return $this->readOrderAddress( $order_address_id );
    }
    
    public function updateOrderAddress(){
        if( ($this->getOrderAddressID() === "") || ($this->getOrderAddressID() === NULL) ){            
            return false;
        }
        // These fields not to be updated
        $order_address_id            = $this->getOrderAddressID();
        $order_id                    = $this->getOrderID();
        
        // These fields are to be updated
        $address_type       = $this->getAddressType();
        $first_name         = $this->getFirstName();
        $last_name          = $this->getLastName();
        $name_on_order      = $this->getNameOnOrder();
        $contact            = $this->getContact();
        $address1           = $this->getAddress1();
        $address2           = $this->getAddress2();
        $city               = $this->getCity();
        $state              = $this->getState();
        $province           = $this->getProvince();
        $zip                = $this->getZip();
        $country            = $this->getCountry();
        $country_code       = $this->getCountryCode();
        $latitude           = $this->getLatitude();
        $longitude          = $this->getLongitude();
        
        
        $update_params = "";
        if( $address_type !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_ADDRESS_TYPE . "`='$address_type', ";
        }
        if( $first_name !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_FIRST_NAME . "`='$first_name', ";
        }
        if( $last_name !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_LAST_NAME . "`='$last_name', ";
        }
        if( $name_on_order !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_NAME_ON_ORDER . "`='$name_on_order', ";
        }
        if( $contact !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_CONTACT . "`='$contact', ";
        }
        if( $address1 !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_ADDRESS1 . "`='$address1', ";
        }
        if( $address2 !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_ADDRESS2 . "`='$address2', ";
        }
        if( $city !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_CITY . "`='$city', ";
        }
        if( $state !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_STATE . "`='$state', ";
        }
        if( $province !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_PROVINCE . "`='$province', ";
        }
        if( $zip !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_ZIP . "`='$zip', ";
        }
        if( $country !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_COUNTRY . "`='$country', ";
        }
        if( $country_code !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_COUNTRY_CODE . "`='$country_code', ";
        }
        if( $latitude !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_LATITUDE . "`='$latitude', ";
        }
        if( $longitude !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ORDER_ADDRESS_LONGITUDE . "`='$longitude', ";
        }
        
        
        /*
        if( !== NULL ){
            $update_params .= "`" . self::DB_FIELD_ . "`='', ";
        }
        */
        $update_params = rtrim( $update_params, ", " );
        
        // Update only those columns that are NOT NULL
        $sql = "UPDATE order_ADDRESS SET $update_params WHERE order_address_id='$order_address_id'";
        $rows = updateQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
        
    }
    
    public function getCountryCode() {
        return $this->countryCode;
    }

    public function setCountryCode($countryCode): void {
        $this->countryCode = $countryCode;
    }
    
    public function getOrderAddressID() {
        return $this->orderAddressID;
    }

    public function getOrderID() {
        return $this->orderID;
    }

    public function getAddressType() {
        return $this->addressType;
    }

    public function getFirstName() {
        return $this->firstName;
    }

    public function getLastName() {
        return $this->lastName;
    }

    public function getNameOnOrder() {
        return $this->nameOnOrder;
    }

    public function getContact() {
        return $this->contact;
    }

    public function getAddress1() {
        return $this->address1;
    }

    public function getAddress2() {
        return $this->address2;
    }

    public function getCity() {
        return $this->city;
    }

    public function getState() {
        return $this->state;
    }

    public function getProvince() {
        return $this->province;
    }

    public function getZip() {
        return $this->zip;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getLatitude() {
        return $this->latitude;
    }

    public function getLongitude() {
        return $this->longitude;
    }

    public function setOrderAddressID($orderAddressID): void {
        $this->orderAddressID = $orderAddressID;
    }

    public function setOrderID($orderID): void {
        $this->orderID = $orderID;
    }

    public function setAddressType($addressType): void {
        $this->addressType = $addressType;
    }

    public function setFirstName($firstName): void {
        $this->firstName = $firstName;
    }

    public function setLastName($lastName): void {
        $this->lastName = $lastName;
    }

    public function setNameOnOrder($nameOnOrder): void {
        $this->nameOnOrder = $nameOnOrder;
    }

    public function setContact($contact): void {
        $this->contact = $contact;
    }

    public function setAddress1($address1): void {
        $this->address1 = $address1;
    }

    public function setAddress2($address2): void {
        $this->address2 = $address2;
    }

    public function setCity($city): void {
        $this->city = $city;
    }

    public function setState($state): void {
        $this->state = $state;
    }

    public function setProvince($province): void {
        $this->province = $province;
    }

    public function setZip($zip): void {
        $this->zip = $zip;
    }

    public function setCountry($country): void {
        $this->country = $country;
    }

    public function setLatitude($latitude): void {
        $this->latitude = $latitude;
    }

    public function setLongitude($longitude): void {
        $this->longitude = $longitude;
    }
}

class OrderStatusHistory{
    
    private $id;                                  // The AI Primary key for the table
    private $order_id;                            // The Foreign Key of orders table
    private $user_id;                             // The user_id of the person who has updated the order
    private $order_status;                        // The status of the order that is being updated
    private $created_at;                          // The timestamp when the entry is created
    
    public const DB_FIELD_ORDER_STATUS_HISTORY_KEY_ORDER_STATUS_DELIVERED               = "delivered";
    public const DB_FIELD_ORDER_STATUS_HISTORY_KEY_ORDER_STATUS_PARTIALLY_DELIVERED     = "partially_delivered";
    public const DB_FIELD_ORDER_STATUS_HISTORY_KEY_ORDER_STATUS_PROCESSED               = "processed";
    public const DB_FIELD_ORDER_STATUS_HISTORY_KEY_ORDER_STATUS_CANCELLED               = "cancelled";
    
    /**
     * Create an entry for order_status_history
     * 
     * @param type $order_id
     * @param type $user_id
     * @param type $order_status
     * @return bool
     */
    public static function addToOrderStatusHistory( $order_id, $user_id, $order_status ){
        if( ($order_id === NULL) || ($order_id == '') ){
            return false;
        }
        $created_at = create_iso_8601_datetime();
        $sql = "INSERT INTO order_status_history( `order_id`, `user_id`, `order_status`, `created_at` ) "
                . "VALUES( '$order_id', '$user_id', '$order_status', '$created_at' )";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
    }
    
    /**
     * 
     * Retrieve the complete order status history for the order
     * 
     * @param type $order_id
     * @return array NULL if the order_id does not exist. Array containing all the order_status_history entries
     */
    public static function getOrderStatusHistory( $order_id ){
        if( ($order_id === NULL) || ($order_id == '') ){
            return NULL;
        }
        
        $sql = "SELECT * FROM order_status_history WHERE order_id='$order_id'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        $order_status_history = array();
        while( ($val = mysqli_fetch_assoc( $result_set ) ) !== NULL ){
            $order_status_history[] = $val;
        }
    }
    
    public function getId() {
        return $this->id;
    }

    public function getOrder_id() {
        return $this->order_id;
    }

    public function getUser_id() {
        return $this->user_id;
    }

    public function getOrder_status() {
        return $this->order_status;
    }

    public function getCreated_at() {
        return $this->created_at;
    }

    public function setId($id): void {
        $this->id = $id;
    }

    public function setOrder_id($order_id): void {
        $this->order_id = $order_id;
    }

    public function setUser_id($user_id): void {
        $this->user_id = $user_id;
    }

    public function setOrder_status($order_status): void {
        $this->order_status = $order_status;
    }

    public function setCreated_at($created_at): void {
        $this->created_at = $created_at;
    }


}

class OrderProductSerialNumber{
    
    private $id;                      // The AI Primary key for the order_product_sn table
    private $orderID;                 // The order_id from the orders table
    private $productID;               // The product_id from the products table
    private $productSnID;             // The product_sn_id from the product_sn table
    
    public function __construct() {
    }
    
    
    
    public function getId() {
        return $this->id;
    }

    public function getOrderID() {
        return $this->orderID;
    }

    public function getProductID() {
        return $this->productID;
    }

    public function getProductSnID() {
        return $this->productSnID;
    }

    public function setId($id): void {
        $this->id = $id;
    }

    public function setOrderID($orderID): void {
        $this->orderID = $orderID;
    }

    public function setProductID($productID): void {
        $this->productID = $productID;
    }

    public function setProductSnID($productSnID): void {
        $this->productSnID = $productSnID;
    }


    
}

?>