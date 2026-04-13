<?php

class ProductSNExtendedWarranty {
    
    private $id;                                     // AI Primary Key
    private $psnexwID;                               // Product SN Extended Warranty ID - 10 character unique string
    private $psnwID;                                 // Foreign Key - Product SN Warranty ID
    private $extendedWarrantyStartDate;              // Extended Warranty Start Date
    private $extendedWarrantyPeriod;                 // Extended Warranty Period
    private $remarks;                                // Additional remarks
    
    public const DB_FIELD_ID                                = "id";
    public const DB_FIELD_PSNEXW_ID                         = "psnexw_id";
    public const DB_FIELD_PSNW_ID                           = "psnw_id";
    public const DB_FIELD_EXTENDED_WARRANTY_START_DATE      = "extended_warranty_start_date";
    public const DB_FIELD_EXTENDED_WARRANTY_PERIOD          = "extended_warranty_period";
    public const DB_FIELD_REMARKS                           = "remarks";
    
    /**
     * Default constructor.
     * Generates a unique psnexw_id and validates it against the database
     */
    function __construct(){
        $this->psnexwID = $this->generateUniquePsnexwID();
    }
    
    /**
     * Generate a Unique PSNEXW ID with format: dd{milliseconds}XXXX
     * First 6 digits: today's date (dd) + milliseconds (4 digits)
     * Last 4 digits: random unique number
     * 
     * @return String Uniquely generated psnexw id
     */
    private function generateUniquePsnexwID(){
        $maxAttempts = 100;
        $attempt = 0;
        
        while($attempt < $maxAttempts){
            $date = new DateTime("now", new DateTimeZone("+0800"));
            $milliseconds = sprintf("%04d", (int)(fmod(microtime(true), 1) * 10000));
            $prefix = $date->format('d') . $milliseconds;
            $suffix = rand(1000, 9999);
            
            $psnexw_id = $prefix . $suffix;
            
            // Check if this psnexw_id already exists in database
            if(!$this->psnexwIDExists($psnexw_id)){
                return $psnexw_id;
            }
            
            $attempt++;
        }
        
        // If we couldn't generate unique ID after max attempts, throw exception
        throw new Exception("Unable to generate unique PSNEXW ID after $maxAttempts attempts");
    }
    
    /**
     * Check if psnexw_id already exists in the database
     * 
     * @param String $psnexw_id The PSNEXW ID to check
     * @return Boolean True if exists, False otherwise
     */
    private function psnexwIDExists($psnexw_id){
        $sql = "SELECT " . self::DB_FIELD_ID . " FROM product_sn_extended_warranty WHERE " . self::DB_FIELD_PSNEXW_ID . "='$psnexw_id' LIMIT 1";
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
     * Creates a DB row from the ProductSNExtendedWarranty Object
     * 
     * @return ProductSNExtendedWarranty|null Returns the object on success, null on failure
     */
    public function createExtendedWarranty(){
        $psnexw_id                          = ($this->getPsnexwID() === NULL) ? '' : $this->getPsnexwID();
        $psnw_id                            = ($this->getPsnwID() === NULL) ? '' : $this->getPsnwID();
        $extended_warranty_start_date       = ($this->getExtendedWarrantyStartDate() === NULL) ? '' : $this->getExtendedWarrantyStartDate();
        $extended_warranty_period           = ($this->getExtendedWarrantyPeriod() === NULL) ? '' : $this->getExtendedWarrantyPeriod();
        $remarks                            = ($this->getRemarks() === NULL) ? '' : $this->getRemarks();
        
        $sql = "INSERT INTO `product_sn_extended_warranty` (`" . self::DB_FIELD_PSNEXW_ID . "`, `" . self::DB_FIELD_PSNW_ID . "`, `" . self::DB_FIELD_EXTENDED_WARRANTY_START_DATE . "`, `" . self::DB_FIELD_EXTENDED_WARRANTY_PERIOD . "`, `" . self::DB_FIELD_REMARKS . "`) "
                . "VALUES ('$psnexw_id', '$psnw_id', '$extended_warranty_start_date', '$extended_warranty_period', '$remarks');";
        
        $rows = insertQuery($sql);
        if($rows > 0){
            return $this;
        }
        return null;
    }
    
    /**
     * Query the product_sn_extended_warranty table and fetch the Extended Warranty Data into a ProductSNExtendedWarranty Object
     * 
     * @param String $psnexwID The PSNEXW ID to read
     * @return ProductSNExtendedWarranty|null Returns the object on success, null if not found
     */
    public function readExtendedWarranty($psnexwID){
        if($psnexwID === NULL){
            return null;
        }
        
        $sql = "SELECT * FROM product_sn_extended_warranty WHERE " . self::DB_FIELD_PSNEXW_ID . "='$psnexwID'";
        $result_set = selectQuery($sql);
        
        if(mysqli_num_rows($result_set) === 0){
            return null;
        }
        
        $val = mysqli_fetch_assoc($result_set);
        
        $this->setId($val[self::DB_FIELD_ID]);
        $this->setPsnexwID($val[self::DB_FIELD_PSNEXW_ID]);
        $this->setPsnwID($val[self::DB_FIELD_PSNW_ID]);
        $this->setExtendedWarrantyStartDate($val[self::DB_FIELD_EXTENDED_WARRANTY_START_DATE]);
        $this->setExtendedWarrantyPeriod($val[self::DB_FIELD_EXTENDED_WARRANTY_PERIOD]);
        $this->setRemarks($val[self::DB_FIELD_REMARKS]);
        
        return $this;
    }
    
    /**
     * Update the ProductSNExtendedWarranty record in the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function updateExtendedWarranty(){
        if(($this->getPsnexwID() === "") || ($this->getPsnexwID() === NULL)){
            return false;
        }
        
        $psnexw_id                          = $this->getPsnexwID();
        $psnw_id                            = $this->getPsnwID();
        $extended_warranty_start_date       = $this->getExtendedWarrantyStartDate();
        $extended_warranty_period           = $this->getExtendedWarrantyPeriod();
        $remarks                            = $this->getRemarks();
        
        $update_params = "";
        
        if($psnw_id !== NULL){
            $update_params .= "`" . self::DB_FIELD_PSNW_ID . "`='$psnw_id', ";
        }
        if($extended_warranty_start_date !== NULL){
            $update_params .= "`" . self::DB_FIELD_EXTENDED_WARRANTY_START_DATE . "`='$extended_warranty_start_date', ";
        }
        if($extended_warranty_period !== NULL){
            $update_params .= "`" . self::DB_FIELD_EXTENDED_WARRANTY_PERIOD . "`='$extended_warranty_period', ";
        }
        if($remarks !== NULL){
            $update_params .= "`" . self::DB_FIELD_REMARKS . "`='$remarks', ";
        }
        
        // Remove trailing comma and space
        $update_params = rtrim($update_params, ", ");
        
        if($update_params === ""){
            return false;
        }
        
        $sql = "UPDATE product_sn_extended_warranty SET $update_params WHERE " . self::DB_FIELD_PSNEXW_ID . "='$psnexw_id'";
        $rows = updateQuery($sql);
        
        if($rows > 0){
            return true;
        }
        return false;
    }
    
    /**
     * Delete the ProductSNExtendedWarranty record from the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function deleteExtendedWarranty(){
        if(($this->getPsnexwID() === "") || ($this->getPsnexwID() === NULL)){
            return false;
        }
        
        $psnexw_id = $this->getPsnexwID();
        $sql = "DELETE FROM product_sn_extended_warranty WHERE " . self::DB_FIELD_PSNEXW_ID . "='$psnexw_id'";
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
    
    public function getPsnexwID(){
        return $this->psnexwID;
    }
    
    public function getPsnwID(){
        return $this->psnwID;
    }
    
    public function getExtendedWarrantyStartDate(){
        return $this->extendedWarrantyStartDate;
    }
    
    public function getExtendedWarrantyPeriod(){
        return $this->extendedWarrantyPeriod;
    }
    
    public function getRemarks(){
        return $this->remarks;
    }
    
    // Setter methods
    public function setId($id): void{
        $this->id = $id;
    }
    
    public function setPsnexwID($psnexwID): void{
        $this->psnexwID = $psnexwID;
    }
    
    public function setPsnwID($psnwID): void{
        $this->psnwID = $psnwID;
    }
    
    public function setExtendedWarrantyStartDate($extendedWarrantyStartDate): void{
        $this->extendedWarrantyStartDate = $extendedWarrantyStartDate;
    }
    
    public function setExtendedWarrantyPeriod($extendedWarrantyPeriod): void{
        $this->extendedWarrantyPeriod = $extendedWarrantyPeriod;
    }
    
    public function setRemarks($remarks): void{
        $this->remarks = $remarks;
    }
}

?>