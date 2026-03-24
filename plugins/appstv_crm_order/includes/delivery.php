<?php

class PrepareDeliveryOrder{
    
    private $id;                                     // AI Primary key for the table
    //private $prepareDeliveryOrderID;                 // Unique ID for each entry
    private $orderID;                                // 
    private $createdAt;                              // 
    private $userID;                                 // 
    private $remarks;                                // Some text to make a note for this preparation
    
    public const DB_FIELD_ID                                   = "id";
    //public const DB_FIELD_PREPARE_DELIVERY_ORDER_ID            = "prepare_delivery_order_id";
    public const DB_FIELD_ORDER_ID                             = "order_id";
    public const DB_FIELD_CREATED_AT                           = "created_at";
    public const DB_FIELD_USER_ID                              = "user_id";
    public const DB_FIELD_REMARKS                              = "remarks";
            
    /**
     * Creates an object of the PrepareDeliveryOrder class
     * 
     * @param type $param "new" species auto generate of the prepare_delivery_order_id
     */
    /*
    public function __construct( $param ) {
        if( $param === "new" ){            
            $this->setPrepareDeliveryOrderID( self::generatePrepareDeliveryOrderID() );
        }
    }
    */
    public function __construct() {
    }
    
    /**
     * Generate a Unique ID for the prepare_delivery_order table entry
     * 
     * @return string
     */
    /*
    public static function generatePrepareDeliveryOrderID(){
        $date       = new DateTime( "now", new DateTimeZone( "+0800" ) );
        $year       = $date->format( 'y' );
        $month      = $date->format( 'm' );
        $day        = $date->format( 'd' );
        $prepare_delivery_order_id   = $year . $month . $day . generateRandomNumber( 5 );
        
        return $prepare_delivery_order_id;
    }
    */
    
    /**
     * Creates an entry in the DB using the object of the class
     * 
     * @return null|$this
     */
    public function createPrepareDeliveryOrder(){
        /*
        // If the $prepare_delivery_order_id was not generated and set into the object before calling this function, then automtaically generate it
        if( $this->getPrepareDeliveryOrderID() === NULL ){
            $this->setPrepareDeliveryOrderID( self::generatePrepareDeliveryOrderID() );
        }*/
        
        //$prepare_delivery_order_id       = $this->getPrepareDeliveryOrderID();
        $order_id                        = $this->getOrderID();
        $user_id                         = $this->getUserID();
        $created_at                      = create_iso_8601_datetime();
        $remarks                        = $this->getRemarks();
        
        //$sql = "INSERT INTO `prepare_delivery_order` (`prepare_delivery_order_id`, `order_id`, `created_at`, `user_id`, `remarks`) "
        $sql = "INSERT INTO `prepare_delivery_order` (`order_id`, `created_at`, `user_id`, `remarks`) "
                           . "VALUES ("
                //. "'$prepare_delivery_order_id', "
                . "'$order_id', "
                . "'$created_at', "
                . "'$user_id', "
                . "'$remarks' ); ";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return $this;
        }
        return NULL;
    }
    
    /**
     * Reads the PrepareDeliveryOrder entry into an object using the $orderID
     * 
     * @param type $orderID
     * @return null|$this NULL when the orderID is null or does not exist.
     */
    public function readPrepareDeliveryOrder( $orderID, $userID = NULL ){
        if( $orderID === NULL ){
            return NULL;
        }
        
        // user id restriction
        $sql_user_id_where = " AND (user_id='$userID')";
        if( $userID === NULL ){
            $sql_user_id_where = '';
        } 
        
        $sql = "SELECT * FROM prepare_delivery_order WHERE (order_id='$orderID') $sql_user_id_where";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return NULL;
        }
        
        $val = mysqli_fetch_assoc( $result_set );
        
        $this->setId( $val[ self::DB_FIELD_ID ] );
        //$this->setPrepareDeliveryOrderID( $val[ self::DB_FIELD_PREPARE_DELIVERY_ORDER_ID ] );
        $this->setOrderID( $val[ self::DB_FIELD_ORDER_ID ] );
        $this->setCreatedAt( $val[ self::DB_FIELD_CREATED_AT ] );
        $this->setUserID( $val[ self::DB_FIELD_USER_ID ] );
        $this->setRemarks( $val[ self::DB_FIELD_REMARKS ] );
        
        // $this->set( $val[ self::DB_FIELD_ ] );
        
        return $this;
    }
    
    /**
     * Deletes the preparation from the table
     * 
     * @param type $orderID
     * @return bool
     */
    public function deletePrepareDeliveryOrder( $orderID ){
        if( $orderID === NULL ){
            return false;
        }
        
        // Order ID should exist
        $sql = "SELECT order_id FROM prepare_delivery_order WHERE order_id='$orderID'";
        $result_set = selectQuery( $sql );
        if( mysqli_num_rows( $result_set ) === 0 ){
            return false;
        }
        
        // Delete the entry
        $sql = "DELETE FROM prepare_delivery_order WHERE order_id='$orderID'";
        $rows = deleteQuery( $sql );
        if( $rows > 0 ){
            return true;
        }
        return false;
    }
    
    
    public function getId() {
        return $this->id;
    }

    public function getOrderID() {
        return $this->orderID;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function getRemarks() {
        return $this->remarks;
    }

    public function setId($id): void {
        $this->id = $id;
    }

    public function setOrderID($orderID): void {
        $this->orderID = $orderID;
    }

    public function setCreatedAt($createdAt): void {
        $this->createdAt = $createdAt;
    }

    public function setUserID($userID): void {
        $this->userID = $userID;
    }

    public function setRemarks($remarks): void {
        $this->remarks = $remarks;
    }

}

class DeliverOrder{
    
    private $id;                                     // AI Primary Key 
    private $orderID;                                //
    private $deliveredAt;                            // Timestamp of delivery
    private $remarks;                                //
    private $status;                                 // delivered | partially_delivered
    private $userID;                                 // User ID of the person who delivered the order
    private $productsDelivered;                      // JSON of the products that have been delivered
    private $productsNotDelivered;                   // JSON of the products that have not been delivered
    private $pictures;                               // JSON of the pictures that have been uploaded during delivery confirmation
    
    
    public const DB_FIELD_ID                                   = "id";
    public const DB_FIELD_ORDER_ID                             = "order_id";
    public const DB_FIELD_DELIVERED_AT                         = "delivered_at";
    public const DB_FIELD_REMARKS                              = "remarks";
    public const DB_FIELD_STATUS                               = "status";
    public const DB_FIELD_USER_ID                              = "user_id";
    public const DB_FIELD_PRODUCTS_DELIVERED                   = "products_delivered";
    public const DB_FIELD_PRODUCTS_NOT_DELIVERED               = "products_not_delivered";
    public const DB_FIELD_PICTURES                             = "pictures";
    
    public const DB_FIELD_STATUS_DELIVERED                     = "delivered";
    public const DB_FIELD_STATUS_PARTIALLY_DELIVERED           = "partially_delivered";
    
    public function __construct() {
    }
    
    public function deliverOrder(){
        $order_id                        = $this->getOrderID();
        $delivered_at                    = create_iso_8601_datetime();
        $remarks                         = $this->getRemarks();
        $status                          = $this->getStatus();
        $user_id                         = $this->getUserID();
        $products_delivered              = $this->getProductsDelivered();
        $products_not_delivered          = $this->getProductsNotDelivered();
        $pictures                        = $this->getPictures();
        
        //$sql = "INSERT INTO `prepare_delivery_order` (`prepare_delivery_order_id`, `order_id`, `created_at`, `user_id`, `remarks`) "
        $sql = "INSERT INTO `deliver_order` (`order_id`, `delivered_at`, `remarks`, `status`, `user_id`, `products_delivered`, `products_not_delivered`, `pictures` ) "
                           . "VALUES ("
                //. "'$prepare_delivery_order_id', "
                . "'$order_id', "
                . "'$delivered_at', "
                . "'$remarks', "
                . "'$status', "
                . "'$user_id', "
                . "'$products_delivered', "
                . "'$products_not_delivered', "
                . "'$pictures' ); ";
        $rows = insertQuery( $sql );
        if( $rows > 0 ){
            return $this;
        }
        return NULL;
    }
    
    public function getProductsDelivered() {
        return $this->productsDelivered;
    }

    public function getProductsNotDelivered() {
        return $this->productsNotDelivered;
    }

    public function getPictures() {
        return $this->pictures;
    }

    public function setProductsDelivered($productsDelivered): void {
        $this->productsDelivered = $productsDelivered;
    }

    public function setProductsNotDelivered($productsNotDelivered): void {
        $this->productsNotDelivered = $productsNotDelivered;
    }

    public function setPictures($pictures): void {
        $this->pictures = $pictures;
    }
    
    public function getId() {
        return $this->id;
    }

    public function getOrderID() {
        return $this->orderID;
    }

    public function getDeliveredAt() {
        return $this->deliveredAt;
    }

    public function getRemarks() {
        return $this->remarks;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function setId($id): void {
        $this->id = $id;
    }

    public function setOrderID($orderID): void {
        $this->orderID = $orderID;
    }

    public function setDeliveredAt($deliveredAt): void {
        $this->deliveredAt = $deliveredAt;
    }

    public function setRemarks($remarks): void {
        $this->remarks = $remarks;
    }

    public function setStatus($status): void {
        $this->status = $status;
    }

    public function setUserID($userID): void {
        $this->userID = $userID;
    }


    
}

?>