<?php

class InvoiceProductSN {
    
    private $id;                                     // AI Primary Key
    private $ipsnID;                                 // Invoice Product SN ID - 10 character unique string
    private $invoiceID;                              // Foreign Key - Invoice ID
    private $productSnID;                            // Foreign Key - Product Serial Number ID
    
    public const DB_FIELD_ID                        = "id";
    public const DB_FIELD_IPSN_ID                   = "ipsn_id";
    public const DB_FIELD_INVOICE_ID                = "invoice_id";
    public const DB_FIELD_PRODUCT_SN_ID             = "product_sn_id";
    
    /**
     * Default constructor.
     * Generates a unique ipsn_id and validates it against the database
     */
    function __construct(){
        $this->ipsnID = $this->generateUniqueIpsnID();
    }
    
    /**
     * Generate a Unique IPSN ID with format: dd{milliseconds}XXXX
     * First 6 digits: today's date (dd) + milliseconds (4 digits)
     * Last 4 digits: random unique number
     * 
     * @return String Uniquely generated ipsn id
     */
    private function generateUniqueIpsnID(){
        $maxAttempts = 100;
        $attempt = 0;
        
        while($attempt < $maxAttempts){
            $date = new DateTime("now", new DateTimeZone("+0800"));
            $milliseconds = sprintf("%04d", (int)(fmod(microtime(true), 1) * 10000));
            $prefix = $date->format('d') . $milliseconds;
            $suffix = rand(1000, 9999);
            
            $ipsn_id = $prefix . $suffix;
            
            // Check if this ipsn_id already exists in database
            if(!$this->ipsnIDExists($ipsn_id)){
                return $ipsn_id;
            }
            
            $attempt++;
        }
        
        // If we couldn't generate unique ID after max attempts, throw exception
        throw new Exception("Unable to generate unique IPSN ID after $maxAttempts attempts");
    }
    
    /**
     * Check if ipsn_id already exists in the database
     * 
     * @param String $ipsn_id The IPSN ID to check
     * @return Boolean True if exists, False otherwise
     */
    private function ipsnIDExists($ipsn_id){
        $sql = "SELECT " . self::DB_FIELD_ID . " FROM invoice_product_sn WHERE " . self::DB_FIELD_IPSN_ID . "='$ipsn_id' LIMIT 1";
        $result_set = selectQuery($sql);
        
        if(mysqli_num_rows($result_set) > 0){
            return true;
        }
        return false;
    }
    
    /**
     * Generate random number with specified length
     * 
     * @param int $length Length of random number
     * @return String Random number as string
     */
    private function generateRandomNumber($length){
        $min = pow(10, $length - 1);
        $max = pow(10, $length) - 1;
        return (string)rand($min, $max);
    }
    
    /**
     * Creates a DB row from the InvoiceProductSN Object
     * 
     * @return InvoiceProductSN|null Returns the object on success, null on failure
     */
    public function createInvoiceProductSN(){
        $ipsn_id        = ($this->getIpsnID() === NULL) ? '' : $this->getIpsnID();
        $invoice_id     = ($this->getInvoiceID() === NULL) ? '' : $this->getInvoiceID();
        $product_sn_id  = ($this->getProductSnID() === NULL) ? '' : $this->getProductSnID();
        
        $sql = "INSERT INTO `invoice_product_sn` (`" . self::DB_FIELD_IPSN_ID . "`, `" . self::DB_FIELD_INVOICE_ID . "`, `" . self::DB_FIELD_PRODUCT_SN_ID . "`) "
                . "VALUES ('$ipsn_id', '$invoice_id', '$product_sn_id');";
        
        $rows = insertQuery($sql);
        if($rows > 0){
            return $this;
        }
        return null;
    }
    
    /**
     * Query the invoice_product_sn table and fetch the data into an InvoiceProductSN Object
     * 
     * @param String $ipsnID The IPSN ID to read
     * @return InvoiceProductSN|null Returns the object on success, null if not found
     */
    public function readInvoiceProductSN($ipsnID){
        if($ipsnID === NULL){
            return null;
        }
        
        $sql = "SELECT * FROM invoice_product_sn WHERE " . self::DB_FIELD_IPSN_ID . "='$ipsnID'";
        $result_set = selectQuery($sql);
        
        if(mysqli_num_rows($result_set) === 0){
            return null;
        }
        
        $val = mysqli_fetch_assoc($result_set);
        
        $this->setId($val[self::DB_FIELD_ID]);
        $this->setIpsnID($val[self::DB_FIELD_IPSN_ID]);
        $this->setInvoiceID($val[self::DB_FIELD_INVOICE_ID]);
        $this->setProductSnID($val[self::DB_FIELD_PRODUCT_SN_ID]);
        
        return $this;
    }
    
    /**
     * Update the InvoiceProductSN record in the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function updateInvoiceProductSN(){
        if(($this->getIpsnID() === "") || ($this->getIpsnID() === NULL)){
            return false;
        }
        
        $ipsn_id        = $this->getIpsnID();
        $invoice_id     = $this->getInvoiceID();
        $product_sn_id  = $this->getProductSnID();
        
        $update_params = "";
        
        if($invoice_id !== NULL){
            $update_params .= "`" . self::DB_FIELD_INVOICE_ID . "`='$invoice_id', ";
        }
        if($product_sn_id !== NULL){
            $update_params .= "`" . self::DB_FIELD_PRODUCT_SN_ID . "`='$product_sn_id', ";
        }
        
        // Remove trailing comma and space
        $update_params = rtrim($update_params, ", ");
        
        if($update_params === ""){
            return false;
        }
        
        $sql = "UPDATE invoice_product_sn SET $update_params WHERE " . self::DB_FIELD_IPSN_ID . "='$ipsn_id'";
        $rows = updateQuery($sql);
        
        if($rows > 0){
            return true;
        }
        return false;
    }
    
    /**
     * Delete the InvoiceProductSN record from the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function deleteInvoiceProductSN(){
        if(($this->getIpsnID() === "") || ($this->getIpsnID() === NULL)){
            return false;
        }
        
        $ipsn_id = $this->getIpsnID();
        $sql = "DELETE FROM invoice_product_sn WHERE " . self::DB_FIELD_IPSN_ID . "='$ipsn_id'";
        $rows = deleteQuery($sql);
        
        if($rows > 0){
            return true;
        }
        return false;
    }
    
    // Getter methods
    public function getId(){
        return $this->id;
    }
    
    public function getIpsnID(){
        return $this->ipsnID;
    }
    
    public function getInvoiceID(){
        return $this->invoiceID;
    }
    
    public function getProductSnID(){
        return $this->productSnID;
    }
    
    // Setter methods
    public function setId($id): void{
        $this->id = $id;
    }
    
    public function setIpsnID($ipsnID): void{
        $this->ipsnID = $ipsnID;
    }
    
    public function setInvoiceID($invoiceID): void{
        $this->invoiceID = $invoiceID;
    }
    
    public function setProductSnID($productSnID): void{
        $this->productSnID = $productSnID;
    }
}

?>