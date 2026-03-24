// app.js
// create angular app
var createProfileValidation = angular.module('createProfileValidation', []);

// create angular controller
createProfileValidation.controller('createProfileController', function($scope) {
	$scope.first_name 			 = $( '#first_name' ).val();
	$scope.last_name 			 = $( '#last_name' ).val();
	$scope.nick_name 			 = $( '#nick_name' ).val();
	$scope.user_email 			 = $( '#user_email' ).val();
	$scope.security_question 	 = $( '#security_question' ).val();
	$scope.security_answer	 	 = $( '#security_answer' ).val();
	
	//function to submit the form after all validation has occurred            
	$scope.submitProfileForm = function(isValid,event) {
		event.preventDefault();
    // check to make sure the form is completely valid
    if ( !isValid ) {
      showModal( "error", "Please check if you have filled all the fields correctly !" );
      return;
    }
    
    $.ajax({
    	url:'webservice.php',
    	type:'POST',
    	data:  $( '#form_edit_user_profile' ).serialize(),
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
    					refreshPage();
    				}, 1000 );
    				return;
    			}
    		});
    	}
    });
    
  };


  //function to submit the form after all validation has occurred            
	$scope.submitPasswordForm = function(isValid,event) {
		event.preventDefault();
    // check to make sure the form is completely valid
    if ( !isValid ) {
      showModal( "error", "Please check if you have filled all the fields correctly !" );
      return;
    }
    
    $.ajax({
    	url:'webservice.php',
    	type:'POST',
    	data:  $( '#form_edit_user_password' ).serialize(),
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
    					refreshPage();
    				}, 1000 );
    				return;
    			}
    		});
    	}
    });
    
  };

});

