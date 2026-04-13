<?php

class ProductWarrantyReplacement {
    
    private $id;                                     // AI Primary Key
    private $pwrpID;                                 // Product Warranty Replacement ID - 10 character unique string
    private $isRepairable;                           // Is the product repairable (0 or 1)
    private $psnwID;                                 // Foreign Key - Product SN Warranty ID
    private $dateOfService;                          // Date of Service
    private $newInvoiceID;                           // New Invoice ID for replacement
    private $newProductSnID;                         // New Product SN ID for replacement
    private $remarks;                                // Additional remarks
    
    public const DB_FIELD_ID                        = "id";
    public const DB_FIELD_PWRP_ID                   = "pwrp_id";
    public const DB_FIELD_IS_REPAIRABLE             = "is_repairable";
    public const DB_FIELD_PSNW_ID                   = "psnw_id";
    public const DB_FIELD_DATE_OF_SERVICE           = "date_of_service";
    public const DB_FIELD_NEW_INVOICE_ID            = "new_invoice_id";
    public const DB_FIELD_NEW_PRODUCT_SN_ID         = "new_product_sn_id";
    public const DB_FIELD_REMARKS                   = "remarks";
    
    /**
     * Default constructor.
     * Generates a unique pwrp_id and validates it against the database
     */
    function __construct(){
        $this->pwrpID = $this->generateUniquePwrpID();
    }
    
    /**
     * Generate a Unique PWRP ID with format: dd{milliseconds}XXXX
     * First 6 digits: today's date (dd) + milliseconds (4 digits)
     * Last 4 digits: random unique number
     * 
     * @return String Uniquely generated pwrp id
     */
    private function generateUniquePwrpID(){
        $maxAttempts = 100;
        $attempt = 0;
        
        while($attempt < $maxAttempts){
            $date = new DateTime("now", new DateTimeZone("+0800"));
            $milliseconds = sprintf("%04d", (int)(fmod(microtime(true), 1) * 10000));
            $prefix = $date->format('d') . $milliseconds;
            $suffix = rand(1000, 9999);
            
            $pwrp_id = $prefix . $suffix;
            
            // Check if this pwrp_id already exists in database
            if(!$this->pwrpIDExists($pwrp_id)){
                return $pwrp_id;
            }
            
            $attempt++;
        }
        
        // If we couldn't generate unique ID after max attempts, throw exception
        throw new Exception("Unable to generate unique PWRP ID after $maxAttempts attempts");
    }
    
    /**
     * Check if pwrp_id already exists in the database
     * 
     * @param String $pwrp_id The PWRP ID to check
     * @return Boolean True if exists, False otherwise
     */
    private function pwrpIDExists($pwrp_id){
        $sql = "SELECT " . self::DB_FIELD_ID . " FROM product_warranty_replacements WHERE " . self::DB_FIELD_PWRP_ID . "='$pwrp_id' LIMIT 1";
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
     * Creates a DB row from the ProductWarrantyReplacement Object
     * 
     * @return ProductWarrantyReplacement|null Returns the object on success, null on failure
     */
    public function createReplacement(){
        $pwrp_id                = ($this->getPwrpID() === NULL) ? '' : $this->getPwrpID();
        $is_repairable          = ($this->getIsRepairable() === NULL) ? 0 : $this->getIsRepairable();
        $psnw_id                = ($this->getPsnwID() === NULL) ? '' : $this->getPsnwID();
        $date_of_service        = ($this->getDateOfService() === NULL) ? '' : $this->getDateOfService();
        $new_invoice_id         = ($this->getNewInvoiceID() === NULL) ? '' : $this->getNewInvoiceID();
        $new_product_sn_id      = ($this->getNewProductSnID() === NULL) ? '' : $this->getNewProductSnID();
        $remarks                = ($this->getRemarks() === NULL) ? '' : $this->getRemarks();
        
        $sql = "INSERT INTO `product_warranty_replacements` (`" . self::DB_FIELD_PWRP_ID . "`, `" . self::DB_FIELD_IS_REPAIRABLE . "`, `" . self::DB_FIELD_PSNW_ID . "`, `" . self::DB_FIELD_DATE_OF_SERVICE . "`, `" . self::DB_FIELD_NEW_INVOICE_ID . "`, `" . self::DB_FIELD_NEW_PRODUCT_SN_ID . "`, `" . self::DB_FIELD_REMARKS . "`) "
                . "VALUES ('$pwrp_id', '$is_repairable', '$psnw_id', '$date_of_service', '$new_invoice_id', '$new_product_sn_id', '$remarks');";
        
        $rows = insertQuery($sql);
        if($rows > 0){
            return $this;
        }
        return null;
    }
    
    /**
     * Query the product_warranty_replacements table and fetch the Replacement Data into a ProductWarrantyReplacement Object
     * 
     * @param String $pwrpID The PWRP ID to read
     * @return ProductWarrantyReplacement|null Returns the object on success, null if not found
     */
    public function readReplacement($pwrpID){
        if($pwrpID === NULL){
            return null;
        }
        
        $sql = "SELECT * FROM product_warranty_replacements WHERE " . self::DB_FIELD_PWRP_ID . "='$pwrpID'";
        $result_set = selectQuery($sql);
        
        if(mysqli_num_rows($result_set) === 0){
            return null;
        }
        
        $val = mysqli_fetch_assoc($result_set);
        
        $this->setId($val[self::DB_FIELD_ID]);
        $this->setPwrpID($val[self::DB_FIELD_PWRP_ID]);
        $this->setIsRepairable($val[self::DB_FIELD_IS_REPAIRABLE]);
        $this->setPsnwID($val[self::DB_FIELD_PSNW_ID]);
        $this->setDateOfService($val[self::DB_FIELD_DATE_OF_SERVICE]);
        $this->setNewInvoiceID($val[self::DB_FIELD_NEW_INVOICE_ID]);
        $this->setNewProductSnID($val[self::DB_FIELD_NEW_PRODUCT_SN_ID]);
        $this->setRemarks($val[self::DB_FIELD_REMARKS]);
        
        return $this;
    }
    
    /**
     * Update the ProductWarrantyReplacement record in the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function updateReplacement(){
        if(($this->getPwrpID() === "") || ($this->getPwrpID() === NULL)){
            return false;
        }
        
        $pwrp_id                = $this->getPwrpID();
        $is_repairable          = $this->getIsRepairable();
        $psnw_id                = $this->getPsnwID();
        $date_of_service        = $this->getDateOfService();
        $new_invoice_id         = $this->getNewInvoiceID();
        $new_product_sn_id      = $this->getNewProductSnID();
        $remarks                = $this->getRemarks();
        
        $update_params = "";
        
        if($is_repairable !== NULL){
            $update_params .= "`" . self::DB_FIELD_IS_REPAIRABLE . "`='$is_repairable', ";
        }
        if($psnw_id !== NULL){
            $update_params .= "`" . self::DB_FIELD_PSNW_ID . "`='$psnw_id', ";
        }
        if($date_of_service !== NULL){
            $update_params .= "`" . self::DB_FIELD_DATE_OF_SERVICE . "`='$date_of_service', ";
        }
        if($new_invoice_id !== NULL){
            $update_params .= "`" . self::DB_FIELD_NEW_INVOICE_ID . "`='$new_invoice_id', ";
        }
        if($new_product_sn_id !== NULL){
            $update_params .= "`" . self::DB_FIELD_NEW_PRODUCT_SN_ID . "`='$new_product_sn_id', ";
        }
        if($remarks !== NULL){
            $update_params .= "`" . self::DB_FIELD_REMARKS . "`='$remarks', ";
        }
        
        // Remove trailing comma and space
        $update_params = rtrim($update_params, ", ");
        
        if($update_params === ""){
            return false;
        }
        
        $sql = "UPDATE product_warranty_replacements SET $update_params WHERE " . self::DB_FIELD_PWRP_ID . "='$pwrp_id'";
        $rows = updateQuery($sql);
        
        if($rows > 0){
            return true;
        }
        return false;
    }
    
    /**
     * Delete the ProductWarrantyReplacement record from the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function deleteReplacement(){
        if(($this->getPwrpID() === "") || ($this->getPwrpID() === NULL)){
            return false;
        }
        
        $pwrp_id = $this->getPwrpID();
        $sql = "DELETE FROM product_warranty_replacements WHERE " . self::DB_FIELD_PWRP_ID . "='$pwrp_id'";
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
    
    public function getPwrpID(){
        return $this->pwrpID;
    }
    
    public function getIsRepairable(){
        return $this->isRepairable;
    }
    
    public function getPsnwID(){
        return $this->psnwID;
    }
    
    public function getDateOfService(){
        return $this->dateOfService;
    }
    
    public function getNewInvoiceID(){
        return $this->newInvoiceID;
    }
    
    public function getNewProductSnID(){
        return $this->newProductSnID;
    }
    
    public function getRemarks(){
        return $this->remarks;
    }
    
    // Setter methods
    public function setId($id): void{
        $this->id = $id;
    }
    
    public function setPwrpID($pwrpID): void{
        $this->pwrpID = $pwrpID;
    }
    
    public function setIsRepairable($isRepairable): void{
        $this->isRepairable = $isRepairable;
    }
    
    public function setPsnwID($psnwID): void{
        $this->psnwID = $psnwID;
    }
    
    public function setDateOfService($dateOfService): void{
        $this->dateOfService = $dateOfService;
    }
    
    public function setNewInvoiceID($newInvoiceID): void{
        $this->newInvoiceID = $newInvoiceID;
    }
    
    public function setNewProductSnID($newProductSnID): void{
        $this->newProductSnID = $newProductSnID;
    }
    
    public function setRemarks($remarks): void{
        $this->remarks = $remarks;
    }
}

?>