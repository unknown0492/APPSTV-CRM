var loginApp = angular.module( 'loginApp', [] );

loginApp.controller( 'loginCtrl', function($scope) {
	
	$scope.submitForm = function( event ) {
		event.preventDefault();
	  
		// check to make sure the form is completely valid
	    var user_id = $( '#user_id' ).val();
	    var password = $( '#password' ).val();
	    
	    $.ajax({
	    	url : 'webservice.php',
	    	type: 'POST',
	    	data: { 'user_id' : user_id, 'password' : password, 'what_do_you_want' : 'login' },
	    	success: function( returned_data ){
	    		console.log( returned_data );
	    		var jSon = $.parseJSON( returned_data );
	    		$.each( jSon, function(){
	    			if( this[ 'type' ] == "error" ){
	    				alert( this[ 'info' ] );
	    				refreshPage();
	    				return;
	    			}
	    			if( this[ 'type' ] == "success" ){
	    				window.location.href = "adminpanel.php";
	    				return;
	    			}
	    		});
	    	}
	    });
    
	};
  
  $scope.email = "";
  
  $scope.openForgotPasswordModal = function(){
	  
	  //$( '#modal-forgot-password' ).modal( 'toggle' );
	  
	  $( '.password_reset_form' ).removeClass( 'hidden' );
	  $( '.password_reset_success_message' ).addClass( 'hidden' );
	  $scope.email = "";
	  
  }
  
  $scope.resetPassword = function( f ){
	  
	  if( f.$invalid ){
		  showNotification( "error", "centerBottom", "Email ID is Invalid !", 5000, 1 );
		  return;
	  }
	  
	  var btn_reset 	= $( '#btn_form_forgot_password' );
	  var btn_val 		= btn_reset.html();
	  btn_reset.attr( 'disabled', 'disabled' );
	  btn_reset.html( 'Please wait...' );
	  
	  // return;
	  $.ajax({
		  url : 'webservice.php',
		  type : 'POST',
		  data : $( '#'+f.$name ).serialize(),
		  success : function( returned_data ){
			  console.log( returned_data );
			  
			  btn_reset.removeAttr( 'disabled' );
			  btn_reset.html( btn_val );
			  
			  jSon = parseJSON( returned_data );
			  
			  if( jSon === false ){
				  showNotification( "error", "bottomRight", "Server side script error. Please contact the Administrator !", 10000, 1 );
				  console.log( returned_data );
				  return;
			  }
			  //console.log( jSon );
			  jSon = jSon[ 0 ];
			  //console.log( jSon[ 'type' ] );
			  
			  if( jSon[ 'type' ] == "error" ){
				  showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
				  return;
			  }
			  if( jSon[ 'type' ] == "success" ){
				  // showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
				  $( '.password_reset_form' ).addClass( 'hidden' );
				  $( '.password_reset_success_message' ).removeClass( 'hidden' );
				  return;
			  }
			  
		  }
	  });
	  
  }

});