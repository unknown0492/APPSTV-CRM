var registerApp = angular.module( 'registerApp', [] );

registerApp.controller( 'registerCtrl', function( $scope ) {
	
	$scope.submitForm = function( form, event ){
		event.preventDefault();
	  
		// check to make sure the form is completely valid
	    if( form.$invalid ){
	    	alert( 'Please verify all your form fields and try again !' );
	    	return;
	    }
	    
	    $.ajax({
	    	url : 'webservice.php',
	    	type: 'POST',
	    	data: $( '#form_register' ).serializeObject(),
	    	success: function( returned_data ){
	    		console.log( returned_data );
	    		
	    		var jSon = parseJSONWithError( returned_data, "Server side error ! Please contact the Administrator..." );
	    		if( jSon == false ) return;
	    		jSon = jSon[ 0 ];
	    		
	    		if( jSon[ 'type' ] == "error" ){
	    			alert( jSon[ 'info' ] );
    				return;
    			}
    			if( jSon[ 'type' ] == "success" ){
    				alert( jSon[ 'info' ] );
    				window.location.href = "login.php";
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