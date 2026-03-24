// app.js
// create angular app
var createUserPageValidation = angular.module('createUserPageValidation', []);

// create angular controller
createUserPageValidation.controller('createUserPageController', function($scope) {
	
	$scope.user_id = "";
	
	//function to submit the form after all validation has occurred            
	$scope.submitForm = function( isValid, event ) {
		event.preventDefault();
		
		//check dropdown is selected for role
		var role_value = $( '#role_id' ).val();
		if( ( role_value == null ) || ( role_value == "" ) ){
			showNotification( "error", "bottomRight", "You have not selected any privilege for this Role. Please select few and try again !" , 
					5000, 0 );
			return;
		}
		
	    // check to make sure the form is completely valid
	    if ( !isValid ) {
	    	showNotification( "error", "bottomRight", "Please check if you have filled all the fields correctly !", 
					5000, 0 );
	      return;
	    }
	    
	    var fsa = new FormSubmitAnimator( 'submit_form_create_user' );
	    fsa.showLoading();
	    
	    $.ajax({
	    	url:'webservice.php',
	    	type:'POST',
	    	data:  $( '#form_create_user' ).serialize(),
	    	success: function( returned_data ){
	    		console.log( returned_data );
	    		fsa.hideLoading();
	    		
	    		var jSon = parseJSONWithError( returned_data, "Server Side Error. Please contact the Administrator !" );
	    		jSon = jSon[ 0 ];
	    		
    			if( jSon[ 'type' ] == "error" ){
    				showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
    				return;
    			}
    			if( jSon[ 'type' ] == "success" ){
    				showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
    				refreshPage();
    				return;
    			}
	    		
	    	}
	    });
    
	};
	
	$scope.userIdChanged = function(){
		$( '#user_id_message' ).removeClass();
		$( '#user_id_message' ).text( '' );
	}
	
	$scope.isUserIdAvailable = function(){
		
		if( $scope.user_id.length < 3 )
			return;
		
		$.ajax({
			url  : 'webservice.php',
			type : 'POST',
			data : { what_do_you_want : 'is_user_id_available', user_id : $scope.user_id },
			success : function( returned_data ){
				console.log( returned_data );
				
				var jSon = parseJSONWithError( returned_data, "Server Side Error. Please contact the Administrator !" );
				if( jSon == false ) return;
				
				jSon = jSon[ 0 ];
				console.log( jSon  );
				
				if( jSon[ 'type' ] == "error" ){
					$( '#user_id_message' ).removeClass();
					$( '#user_id_message' ).addClass( "help-block font-red" );
					$( '#user_id_message' ).text( jSon[ 'info' ] );
					return;
				}
				if( jSon[ 'type' ] == "success" ){
					$( '#user_id_message' ).removeClass();
					$( '#user_id_message' ).addClass( "help-block font-green" );
					$( '#user_id_message' ).text( jSon[ 'info' ] );
					return;
				}
			}
		});
		
	}

});
