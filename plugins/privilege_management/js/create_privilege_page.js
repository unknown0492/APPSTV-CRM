// app.js
// create angular app
var createPrivilegePageValidation = angular.module( 'createPrivilegePageValidation', [] );

// create angular controller
createPrivilegePageValidation.controller('createPrivilegePageController', function($scope) {
	
	$scope.is_page 		= "0";
	$scope.plugin_id 	= "none";
	$scope.privilege_group_id = "0";
	
	// function to submit the form after all validation has occurred            
	$scope.submitForm = function( isValid, event ) {
		event.preventDefault();
		
		// Validate is_page field
		if( $scope.is_page == "" ){
			showNotification( "error", "bottmRight", "Please choose if the Privilege is a Page or only a Functionality !", 5000, 1 );
			return;
		}
		
		// Validate if plugin_id is selected
		if( $scope.plugin_id == "none" ){
			showNotification( "error", "bottmRight", "Please choose a Plugin !", 5000, 1 );
			return;
		}
		
		// Validate if privilege_group_id is selected
		if( $scope.privilege_group_id == "none" ){
			showNotification( "error", "bottmRight", "Please choose a Privilege Group !", 5000, 1 );
			return;
		}
		
		// check to make sure the form is completely valid
	    if ( ! isValid ) {
	      showModal( "error", "There arer some errors in your form !" );
	      return;
	    }
	    
	    console.log(  $( '#form_create_privilege' ).serialize() );
	    $.ajax({
	    	url:'webservice.php',
	    	type:'POST',
	    	data:  $( '#form_create_privilege' ).serialize(),
	    	success: function( returned_data ){
	    		// console.log( returned_data );
	    		
	    		var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
	    		if( jSon == false ) return;
	    		
	    		jSon = jSon[ 0 ];
	    		if( jSon[ 'type' ] == "error" ){
	    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 )
	    			return;
	    		}
    			if( jSon[ 'type' ] == "success" ){
    				showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 )
	    			$( '#reset_form_create_privilege' ).click();
    				return;
    			}
	    		
	    	}
	    });
	};
  
  $scope.submitPrivilegeGroup = function( isValid, event ){
	  event.preventDefault();
	  
	  
	  
	  $.ajax({
		  url:'webservice.php',
		  type:'POST',
		  data: $( '#form_create_privilege_group' ).serialize(),
		  success: function( returned_data ){
			  // console.log( returned_data );
			  
			  var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
	    		if( jSon == false ) return;
	    		
	    		jSon = jSon[ 0 ];
	    		if( jSon[ 'type' ] == "error" ){
	    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 )
	    			return;
	    		}
	  			if( jSon[ 'type' ] == "success" ){
	  				showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 )
		    		$( '#reset_form_create_privilege_group' ).click();
	  				return;
	  			}
			  
		  }
	  });
  };
  
  $scope.createPrivilege = function(){
	  $( '#createPrivilegeForm' ).addClass( 'hidden' );
	  $( '#createPrivilegeGroupForm' ).addClass( 'hidden' );
	  $( '#createPrivilegeForm' ).removeClass( 'hidden' );
  };
  
  $scope.createPrivilegeGroup = function(){
	  $( '#createPrivilegeForm' ).addClass( 'hidden' );
	  $( '#createPrivilegeGroupForm' ).addClass( 'hidden' );
	  $( '#createPrivilegeGroupForm' ).removeClass( 'hidden' );
  }

});