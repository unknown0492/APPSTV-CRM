// app.js
// create angular app
var passwordValidation = angular.module('passwordValidation', []);

// create angular controller
passwordValidation.controller('passwordController', function($scope) {
	
	//function to submit the form after all validation has occurred            
	$scope.submitForm = function( form, event ) {
		event.preventDefault();
		
		console.log( form );
		
	  // check to make sure the form is completely valid
	  if ( form.$invalid ) {
	    alert( 'Please check all your fields properly before submitting !' );
	    return;
	  }
	  
	  var password_new 				= $( '#password_new' ).val();
	  var password_new_repeat		= $( '#password_new_repeat' ).val();
	  if( password_new != password_new_repeat ){
		  $( '#form_password_new_message' ).removeClass( "hidden" );
		  //$( '#reset_title' ).html( "Error" );
		  $( '#reset_message' ).html( "Repeat Password should be same Password." );
		  return;
	  }
	  
	  /*var btn_reset 	= $( '#btn_form_password_new' );
	  var btn_val 		= btn_reset.html();
	  btn_reset.attr( 'disabled', 'disabled' );
	  btn_reset.html( 'Please wait...' );*/
	  
	  var fsa = new FormSubmitAnimator( 'btn_form_password_new' );
	  fsa.showLoading();
	  
	  $.ajax({
	  	url : 'webservice.php',
	  	type: 'POST',
	  	data: $( '#form_password_new' ).serialize(),
	  	success: function( returned_data ){
	  		console.log( returned_data );
	  		
	  		/*btn_reset.removeAttr( 'disabled' );
			btn_reset.html( btn_val );*/
	  		fsa.hideLoading();
			  
			var jSon = parseJSONWithError( returned_data, "Server side script error. Please contact the Administrator !" );
			if( !jSon ){ return; }
	  		jSon = jSon[ 0 ];
			
	  		if( jSon[ 'type' ] == "error" ){
  				showNotification( jSon[ 'type' ], "bottomRight", jSon[ 'info' ], 5000, 1 );
  				refreshPage();
  				return;
  			}
  			if( jSon[ 'type' ] == "success" ){
  				$( '#reset_form_password_new' ).click();
  				alert( 'Password has been reset successfully. You will now be redirected to the login page.' );
  				redirect( "login.php" );
  				return;
  			}
	  		
	  	}
	  });
	  
	};

});