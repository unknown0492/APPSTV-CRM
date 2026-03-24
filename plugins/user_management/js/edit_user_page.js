// app.js
// edit angular app
var editUserPageValidation = angular.module( 'editUserPageValidation', [] );

// edit angular controller
editUserPageValidation.controller( 'editUserPageController', function( $scope ) {
	//function to check username exist to be taken by the user
	$scope.hidemessage = function(){
		$( '#message' ).addClass( 'hidden' );
	}
	
	$scope.user_id  	= $( '#user_id' ).val();
	$scope.fname		= $( '#fname' ).val();
	$scope.lname		= $( '#lname' ).val();
	$scope.email		= $( '#email' ).val();
	$scope.nickname 	= $( '#nickname' ).val();

	$scope.checkUsername = function(){
		if( $( '#user_id' ).val().length > 2 ){
			$.ajax({
				url: 'webservice.php',
				type:'POST',
				data: "user_id=" + $( '#user_id' ).val() + "&what_do_you_want=is_user_id_available",
				success:function( returned_data ){
					console.log( returned_data );
					var jSon = $.parseJSON( returned_data );
					$.each( jSon, function(){
						if( this['type'] == 'error' ){
							$( '#message' ).removeClass( 'hidden' );
							$( '#message' ).css( "color", "red" );
							$( '#message' ).text( this[ 'info' ] );
							return;
						}
						if( this['type']  =='success' ){
							$( '#message' ).removeClass( 'hidden' );
							$( '#message' ).text( this[ 'info' ] );
							$( '#message' ).css( "color" , "green" );
							return;
						}
					});
				}
			});
		}
	}
	
	//function to submit the form after all validation has occurred            
	$scope.submitForm = function(isValid,event) {
		event.preventDefault();
	//check dropdown is selected for role
	var role_value = $( '#role_id' ).val();
	if( role_value == null || role_value == "" ){
		showModal( "error", "You have not selected any privilege for this Role. Please select few and try again !" );
		return;
	}

	$scope.change_password 		= $( '#change_password' ).val();
	$scope.confirm_password 	= $( '#confirm_password' ).val();
						
	if( $scope.confirm_password != $scope.change_password && $scope.confirm_password != null && $scope.change_password != null ){
		showModal( "error", "Confirm Password does not match Change Password" );
		return;	
	}

    // check to make sure the form is completely valid
    if ( !isValid ) {
      showModal( "error", "Please check if you have filled all the fields correctly !" );
      return;
    }
    
    $.ajax({
    	url:'webservice.php',
    	type:'POST',
    	data:  $( '#form_edit_user' ).serialize(),
    	success: function( returned_data ){
    		console.log( returned_data );
    		
    		var jSon = $.parseJSON( returned_data );
    		$.each( jSon , function(){
    			if( this[ 'type' ] == "error" ){
    				showModal( "error", this[ 'info' ] );
    				return;
    			}
    			if( this[ 'type' ] == "success" ){
    				showModal( "success", this[ 'info' ] );
    				setTimeout( function(){
    					redirect( 'adminpanel.php?what_do_you_want=view_users_page' );
    				}, 1000 );
    				return;
    			}
    		});
    	}
    });
    
  };

});
