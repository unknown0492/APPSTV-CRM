// app.js
// edit angular app
var editHooksPageValidation = angular.module( 'editHooksPageValidation', []);

// edit angular controller
editHooksPageValidation.controller( 'editHooksPageController', function( $scope ) {
	$scope.id 		 			= $( '#id' ).val();
	console.log($scope.id);
	$scope.hook_name 			= $( '#hook_name' ).val();
	console.log($scope.hook_name);
	$scope.hook_description 	= $( '#hook_description' ).val();
	console.log($scope.hook_description);
	$scope.hook_content 	 	= $( '#hook_content' ).val();
	console.log($scope.hook_content);
	$scope.hook_content_meta 	= $( '#hook_content_meta' ).val();
	console.log($scope.hook_content_meta);
	
	console.log( $scope );
	
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
	    	data:  $( '#form_edit_hooks' ).serialize(),
	    	success: function( returned_data ){
	    		console.log( returned_data );
	    		
	    		$scope.form_edit_hook = returned_data;
	    		
	    		var jSon = $.parseJSON( returned_data );
	    		$.each( jSon , function(){
	    			if( this[ 'type' ] == "error" ){
	    				showModal( "error", this[ 'info' ] );
	    				return;
	    			}
	    			if( this[ 'type' ] == "success" ){
	    				showModal( "success", this[ 'info' ] );
	    				setTimeout( function(){
	    					redirect( "adminpanel.php?what_do_you_want=view_hooks_page" );
	    				}, 1000 );
	    				return;
	    			}
	    		});
	    	}
	    });
	};
});