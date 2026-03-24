<?php
	class FormHelper{
		
		private $formName;
		
		function __construct( $formName ){
			$this->formName = $formName;
		}
		
		function getFormName(){
			return $this->formName;
		}
		
	}
	
	class FormField{
		
		private  $fieldType, $fieldParams, $containerText;
		
		 function __construct( $fieldType, $fieldParams, $containerText ){
			$this->fieldType = $fieldType;
			$this->fieldParams = $fieldParams;
			$this->containerText = $containerText;
		} 
		
		function generateField(){
			$class = "";
			$str = "";
			$ng_pattern = "";
			$form_name = "";
			$mr = new MyRegex();
			
			if( $this->fieldParams != NULL ){
				//echo "hii";
				if( isset( $this->fieldParams[ 'ng-pattern' ] ) ){
					$ng_pattern = $this->fieldParams[ 'ng-pattern' ];
					$this->fieldParams[ 'ng-pattern' ] = $mr->getValidationRegex( $this->fieldParams[ 'ng-pattern' ] );
				}
				foreach ( $this->fieldParams as $key=>$value ){
					// if( $key == "class" )
					//	$class = $value;
					// if( $key == 'ng-pattern' )
						
					//if( $key == "ng-pattern" )
					$str .= "$key=\"$value\" ";
					/*if( $key == "form-name" )
						$form_name = $value; */
					//echo $str;
				}
				
				
			}
			
			$html = "";
				
			if( $this->fieldType == "label" ){
				$html = "<label $str>".$this->containerText."</label>";
				// return;
			}
			
			else if( $this->fieldType == "text" ){
				$html = "<input type=\"text\" $str />";
				// return;
			}
			
			else if( $this->fieldType == "password" ){
				$html = "<input type=\"password\" $str />";
				// return;
			}
			
			else if( $this->fieldType == "hidden" ){
				$html = "<input type=\"hidden\" $str />";
				// return;
			}
			
			else if( $this->fieldType == "button" ){
				$html = "<button type=\"button\" $str>". $this->containerText ."</button>";
				// return;
			}
			
			else if( $this->fieldType == "submit" ){
				$html = "<button type=\"submit\" $str>". $this->containerText ."</button>";
				// return;
			}
			
			else if( $this->fieldType == "reset" ){
				$html = "<button type=\"reset\" $str>". $this->containerText ."</button>";
				// return;
			}
			
			else if( $this->fieldType == "textarea" ){
				$html = "<textarea $str {layout}>".$this->containerText."</textarea>";
				//return;
			}
			
			
			$ngValidated = isset( $this->fieldParams[ 'ngValidated'] ) && ( $this->fieldParams[ 'ngValidated'] == "true" );
			$isRequired = isset( $this->fieldParams[ 'required' ] ) && ( $this->fieldParams[ 'required' ] == "required" );
			
			if( $ngValidated || $isRequired ){
				
				$anim = "";
				if( $ngValidated ){
					$anim = sprintf( 'ng-class="(%s.%s.$touched && %s.%s.$invalid)?\'animated shake border-red\':\'\'"',
							$this->fieldParams[ 'form-name' ], $this->fieldParams[ 'name' ], $this->fieldParams[ 'form-name' ], $this->fieldParams[ 'name' ] );
					
					$html = $this->setValidationAnimation( $this->fieldType, $html, $anim );
				}
				
				if( $ngValidated && $isRequired ){
					$html .= sprintf( '<span ng-show="%s.%s.$touched && %s.%s.$error.required" class="help-block font-red">%s</span>',
						$this->fieldParams[ 'form-name' ], $this->fieldParams[ 'name' ], $this->fieldParams[ 'form-name' ], $this->fieldParams[ 'name' ],
						'This is a required field and cannot be empty' );
				}
				
				if( $ngValidated ){
					$html .= sprintf( '<span ng-show="%s.%s.$error.pattern" class="help-block font-red">%s</span>',
						$this->fieldParams[ 'form-name' ], $this->fieldParams[ 'name' ],
						$mr->getValidationErrMsg( $ng_pattern ) );
				}
				
				// return;
			}
			
			echo $html;
		}
		
		function setValidationAnimation( $element, $html, $anim ){
			switch( $element ){
				case "textarea":
				case "label":
				case "button":
					$html = str_replace( "{layout}", $anim . " {layout}", $html );
				case "text":
				case "password":
					$html = str_replace( "/>", $anim . " />", $html );
				
			}
			return $html;
		}
		
		function setContainerText( $containerText ){
			$this->containerText = $containerText;
		}
                
                function getParam( $paramName ){
			return $this->fieldParams[ $paramName ];
		}
		
	}

?>