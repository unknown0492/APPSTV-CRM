<?php

class OrderInvoice {
    
    private $id;                                     // AI Primary Key
    private $invoiceID;                              // 10 character unique string
    private $invoiceNo;                              // Invoice Number
    private $invoiceDate;                            // Invoice Date in dd-mm-YYYY format
    
    public const DB_FIELD_ID                = "id";
    public const DB_FIELD_INVOICE_ID        = "invoice_id";
    public const DB_FIELD_INVOICE_NO        = "invoice_no";
    public const DB_FIELD_INVOICE_DATE      = "invoice_date";
    
    /**
     * Default constructor.
     * Generates a unique invoice_id and validates it against the database
     */
    function __construct(){
        $this->invoiceID = $this->generateUniqueInvoiceID();
    }
    
    /**
     * Generate a Unique Invoice ID with format: dd{milliseconds}XXXX
     * First 6 digits: today's date (dd) + milliseconds (4 digits)
     * Last 4 digits: random unique number
     * 
     * @return String Uniquely generated invoice id
     */
    private function generateUniqueInvoiceID(){
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
            
            $invoice_id = $prefix . $suffix;
            
            // Check if this invoice_id already exists in database
            if(!$this->invoiceIDExists($invoice_id)){
                return $invoice_id;
            }
            
            $attempt++;
        }
        
        // If we couldn't generate unique ID after max attempts, throw exception
        throw new Exception("Unable to generate unique invoice ID after $maxAttempts attempts");
    }
    
    /**
     * Check if invoice_id already exists in the database
     * 
     * @param String $invoice_id The invoice ID to check
     * @return Boolean True if exists, False otherwise
     */
    private function invoiceIDExists($invoice_id){
        $sql = "SELECT " . self::DB_FIELD_ID . " FROM order_invoices WHERE " . self::DB_FIELD_INVOICE_ID . "='$invoice_id' LIMIT 1";
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
     * Creates a DB row from the OrderInvoice Object
     * 
     * @return OrderInvoice|null Returns the object on success, null on failure
     */
    public function createInvoice(){
        $invoice_id     = ($this->getInvoiceID() === NULL) ? '' : $this->getInvoiceID();
        $invoice_no     = ($this->getInvoiceNo() === NULL) ? '' : $this->getInvoiceNo();
        $invoice_date   = ($this->getInvoiceDate() === NULL) ? '' : $this->getInvoiceDate();
        
        $sql = "INSERT INTO `order_invoices` (`" . self::DB_FIELD_INVOICE_ID . "`, `" . self::DB_FIELD_INVOICE_NO . "`, `" . self::DB_FIELD_INVOICE_DATE . "`) "
                . "VALUES ('$invoice_id', '$invoice_no', '$invoice_date');";
        
        $rows = insertQuery($sql);
        if($rows > 0){
            return $this;
        }
        return null;
    }
    
    /**
     * Query the order_invoices table and fetch the Invoice Data into an OrderInvoice Object
     * 
     * @param String $invoiceID The invoice ID to read
     * @return OrderInvoice|null Returns the object on success, null if not found
     */
    public function readInvoice($invoiceID){
        if($invoiceID === NULL){
            return null;
        }
        
        $sql = "SELECT * FROM order_invoices WHERE " . self::DB_FIELD_INVOICE_ID . "='$invoiceID'";
        $result_set = selectQuery($sql);
        
        if(mysqli_num_rows($result_set) === 0){
            return null;
        }
        
        $val = mysqli_fetch_assoc($result_set);
        
        $this->setId($val[self::DB_FIELD_ID]);
        $this->setInvoiceID($val[self::DB_FIELD_INVOICE_ID]);
        $this->setInvoiceNo($val[self::DB_FIELD_INVOICE_NO]);
        $this->setInvoiceDate($val[self::DB_FIELD_INVOICE_DATE]);
        
        return $this;
    }
    
    /**
     * Update the OrderInvoice record in the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function updateInvoice(){
        if(($this->getInvoiceID() === "") || ($this->getInvoiceID() === NULL)){
            return false;
        }
        
        $invoice_id     = $this->getInvoiceID();
        $invoice_no     = $this->getInvoiceNo();
        $invoice_date   = $this->getInvoiceDate();
        
        $update_params = "";
        
        if($invoice_no !== NULL){
            $update_params .= "`" . self::DB_FIELD_INVOICE_NO . "`='$invoice_no', ";
        }
        if($invoice_date !== NULL){
            $update_params .= "`" . self::DB_FIELD_INVOICE_DATE . "`='$invoice_date', ";
        }
        
        // Remove trailing comma and space
        $update_params = rtrim($update_params, ", ");
        
        if($update_params === ""){
            return false;
        }
        
        $sql = "UPDATE order_invoices SET $update_params WHERE " . self::DB_FIELD_INVOICE_ID . "='$invoice_id'";
        $rows = updateQuery($sql);
        
        if($rows > 0){
            return true;
        }
        return false;
    }
    
    /**
     * Delete the OrderInvoice record from the database
     * 
     * @return Boolean True on success, False on failure
     */
    public function deleteInvoice(){
        if(($this->getInvoiceID() === "") || ($this->getInvoiceID() === NULL)){
            return false;
        }
        
        $invoice_id = $this->getInvoiceID();
        $sql = "DELETE FROM order_invoices WHERE " . self::DB_FIELD_INVOICE_ID . "='$invoice_id'";
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
    
    public function getInvoiceID(){
        return $this->invoiceID;
    }
    
    public function getInvoiceNo(){
        return $this->invoiceNo;
    }
    
    public function getInvoiceDate(){
        return $this->invoiceDate;
    }
    
    // Setter methods
    public function setId($id): void{
        $this->id = $id;
    }
    
    public function setInvoiceID($invoiceID): void{
        $this->invoiceID = $invoiceID;
    }
    
    public function setInvoiceNo($invoiceNo): void{
        $this->invoiceNo = $invoiceNo;
    }
    
    public function setInvoiceDate($invoiceDate): void{
        $this->invoiceDate = $invoiceDate;
    }
}

?>