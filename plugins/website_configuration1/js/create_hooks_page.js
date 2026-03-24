// app.js
// create angular app
var createHooksPageValidation = angular.module('createHooksPageValidation', []);

// create angular controller
createHooksPageValidation.controller('createHooksPageController', function($scope) {
  // function to submit the form after all validation has occurred            
	$scope.submitForm = function( isValid,event ) {
		event.preventDefault();
		
	
	    // check to make sure the form is completely valid
	    if ( ! isValid ) {
	      showModal( "error", "Please check your form fields again !" );
	      return;
	    }
	    
	    $.ajax({
	    	url:'webservice.php',
	    	type:'POST',
	    	data:  $( '#form_create_hook' ).serialize(),
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