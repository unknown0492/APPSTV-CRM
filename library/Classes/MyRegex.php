<?php
	class MyRegex{
		
		/* function __construct( $fieldType, $fieldParams, $containerText ){
			$this->fieldType = $fieldType;
			$this->fieldParams = $fieldParams;
			$this->containerText = $containerText;
		} */
		
		/**
		 *
		 * Retrieves all validation variables and regex expressions
		 *
		 * @return Array of Validation variables, type and regex expressions
		 *
		 */
		function getAllValidationConstants(){
			require( dirname( __DIR__ ) . "/library/config.php" );
		
			global $validation_array;
		
			return $validation_array;
		}
		
		/**
		 *
		 * Returns the REGEX expression for the supplied Validation Constant
		 *
		 * @param String Validation Constant
		 *
		 * @return String REGEX pattern for the supplied Validation Constant
		 *
		 *
		 */
		function getValidationRegex( $VLDTN_CONSTANT ){
			$arr = getAllValidationConstants();
		
			return $arr[ $VLDTN_CONSTANT ][ 'REGEX' ];
		}
		
		/**
		 *
		 * Returns the Error Message for the supplied Validation Constant
		 *
		 * @param String Validation Constant
		 *
		 * @return String Error Message for the supplied Validation Constant
		 *
		 *
		 */
		function getValidationErrMsg( $VLDTN_CONSTANT ){
			$arr = getAllValidationConstants();
		
			return $arr[ $VLDTN_CONSTANT ][ 'ERR_MSG' ];
		}
		
	}
?>