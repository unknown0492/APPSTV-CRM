<?php

class ProductSNWarranty {
    
    private $id;                                     // AI Primary Key
    private $psnwID;                                 // Product SN Warranty ID - 10 character unique string
    private $productSnID;                            // Foreign Key - Product Serial Number ID
    private $warrantyStartDate;                      // Warranty Start Date
    private $warrantyPeriod;                         // Warranty Period
    private $remarks;                                // Additional remarks
    
    public const DB_FIELD_ID                        = "id";
    public const DB_FIELD_PSNW_ID                   = "psnw_id";
    public const DB_FIELD_PRODUCT_SN_ID             = "product_sn_id";
    public const DB_FIELD_WARRANTY_START_DATE       = "warranty_start_date";
    public const DB_FIELD_WARRANTY_PERIOD           = "warranty_period";
    public const DB_FIELD_REMARKS                   = "remarks";
    
    /**
     * Default constructor.
     * Generates a unique psnw_id and validates it against the database
     */
    function __construct(){
        $this->psnwID = $this->generateUniquePsnwID();
    }
    
    /**
     * Generate a Unique PSNW ID with format: dd{milliseconds}XXXX
     * First 6 digits: today's date (dd) + milliseconds (4 digits)
     * Last 4 digits: random unique number
     * 
     * @return String Uniquely generated psnw id
     */
    private function generateUniquePsnwID(){
        $maxAttempts = 100;
        $attempt = 0;
        
        while($attempt < $maxAttempts){
            $date = new DateTime("now", new DateTimeZone("+0800"));
            $day = $date->format('d');
            
            // Get milliseconds (using microseconds and converting to milliseconds)
            $microtime = microtime(true);
            $milliseconds = sprintf("%04d", ($microtime - floor($microtime)) * 10000);
            
            // First 6 digits: dd + 4-digit milliseconds
            $prefix = $day . $milliseconds;
            
            // Last 4 digits: random number
            $suffix = $this->generateRandomNumber(4);
            
            $psnw_id = $prefix . $suffix;
            
            // Check if this psnw_id already exists in database
            if(!$this->psnwIDExists($psnw_id)){
                return $psnw_id;
            }
            
            $attempt++;
        }
        
        // If we couldn't generate unique ID after max attempts, throw exception
        throw new Exception("Unable to generate unique PSNW ID after $maxAttempts attempts");
    }
    
    /**
     * Check if psnw_id already exists in the database
     * 
     * @param String $psnw_id The PSNW ID to check
     * @return Boolean True if exists, False otherwise
     */
    private function psnwIDExists($psnw_id){
        $sql = "SELECT " . self::DB_FIELD_ID . " FROM product_sn_warranty WHERE " . self::DB_FIELD_PSNW_ID . "='$psnw_id' LIMIT 1";
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
     * Creates a DB row from the ProductSNWarranty Object
     * 
     * @return ProductSNWarranty|null Returns the object on success, null on failure
     */
    public function createWarranty(){
        $psnw_id                = ($this->getPsnwID() === NULL) ? '' : $this->getPsnwID();
        $product_sn_id          = ($this->getProductSnID() === NULL) ? '' : $this->getProductSnID();
        $warranty_start_date    = ($this->getWarrantyStartDate() === NULL) ? '' : $this->getWarrantyStartDate();
        $warranty_period        = ($this->getWarrantyPeriod() === NULL) ? '' : $this->getWarrantyPeriod();
        $remarks                = ($this->getRemarks() === NULL) ? '' : $this->getRemarks();
        
        $sql = "INSERT INTO `product_sn_warranty` (`" . self::DB_FIELD_PSNW_ID . "`, `" . self::DB_FIELD_PRODUCT_SN_ID . "`, `" . self::DB_FIELD_WARRANTY_START_DATE . "`, `" . self::DB_FIELD_WARRANTY_PERIOD . "`, `" . self::DB_FIELD_REMARKS . "`) "
                . "VALUES ('$psnw_id', '$product_sn_id', '$warranty_start_date', '$warranty_period', '$remarks');";
        
        $rows = insertQuery($sql);
        if($rows > 0){
            return $this;
        }
        return null;
    }
    
    /**
     * Query the product_sn_warranty table and fetch the Warranty Data into a ProductSNWarranty Object
     * 
     * @param String $psnwID The PSNW ID to read
     * @return ProductSNWarranty|null Returns the object on success, null if not found
     */
    public function readWarranty($psnwID){
        if($psnwID === NULL){
            return null;
        }
        
        $sql = "SELECT * FROM product_sn_warranty WHERE " . self::DB_FIELD_PSNW_ID . "='$psnwID'";
        $result_set = selectQuery($sql);
        
        if(mysqli_num_rows($result_set) === 0){
            return null;
        }
        
        $val = mysqli_fetch_assoc($result_set);
        
        $this->setId($val[self::DB_FIELD_ID]);
        $this->setPsnwID($val[self::DB_FIELD_PSNW_ID]);
        $this->setProductSnID($val[self::DB_FIELD_PRODUCT_SN_ID]);
        $this->setWarrantyStartDate($val[self::DB_FIELD_WARRANTY_START_DATE]);
        $this->setWarrantyPeriod($val[self::DB_FIELD_WARRANTY_PERIOD]);
        $this->setRemarks($val[self::DB_FIELD_REMARKS]);
        
        return $this;
    }
    
    /**
     * Update the ProductSNWarranty record in the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function updateWarranty(){
        if(($this->getPsnwID() === "") || ($this->getPsnwID() === NULL)){
            return false;
        }
        
        $psnw_id                = $this->getPsnwID();
        $product_sn_id          = $this->getProductSnID();
        $warranty_start_date    = $this->getWarrantyStartDate();
        $warranty_period        = $this->getWarrantyPeriod();
        $remarks                = $this->getRemarks();
        
        $update_params = "";
        
        if($product_sn_id !== NULL){
            $update_params .= "`" . self::DB_FIELD_PRODUCT_SN_ID . "`='$product_sn_id', ";
        }
        if($warranty_start_date !== NULL){
            $update_params .= "`" . self::DB_FIELD_WARRANTY_START_DATE . "`='$warranty_start_date', ";
        }
        if($warranty_period !== NULL){
            $update_params .= "`" . self::DB_FIELD_WARRANTY_PERIOD . "`='$warranty_period', ";
        }
        if($remarks !== NULL){
            $update_params .= "`" . self::DB_FIELD_REMARKS . "`='$remarks', ";
        }
        
        // Remove trailing comma and space
        $update_params = rtrim($update_params, ", ");
        
        if($update_params === ""){
            return false;
        }
        
        $sql = "UPDATE product_sn_warranty SET $update_params WHERE " . self::DB_FIELD_PSNW_ID . "='$psnw_id'";
        $rows = updateQuery($sql);
        
        if($rows > 0){
            return true;
        }
        return false;
    }
    
    /**
     * Delete the ProductSNWarranty record from the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function deleteWarranty(){
        if(($this->getPsnwID() === "") || ($this->getPsnwID() === NULL)){
            return false;
        }
        
        $psnw_id = $this->getPsnwID();
        $sql = "DELETE FROM product_sn_warranty WHERE " . self::DB_FIELD_PSNW_ID . "='$psnw_id'";
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
    
    public function getPsnwID(){
        return $this->psnwID;
    }
    
    public function getProductSnID(){
        return $this->productSnID;
    }
    
    public function getWarrantyStartDate(){
        return $this->warrantyStartDate;
    }
    
    public function getWarrantyPeriod(){
        return $this->warrantyPeriod;
    }
    
    public function getRemarks(){
        return $this->remarks;
    }
    
    // Setter methods
    public function setId($id): void{
        $this->id = $id;
    }
    
    public function setPsnwID($psnwID): void{
        $this->psnwID = $psnwID;
    }
    
    public function setProductSnID($productSnID): void{
        $this->productSnID = $productSnID;
    }
    
    public function setWarrantyStartDate($warrantyStartDate): void{
        $this->warrantyStartDate = $warrantyStartDate;
    }
    
    public function setWarrantyPeriod($warrantyPeriod): void{
        $this->warrantyPeriod = $warrantyPeriod;
    }
    
    public function setRemarks($remarks): void{
        $this->remarks = $remarks;
    }
}

?>