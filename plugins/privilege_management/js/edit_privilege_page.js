// app.js
// edit angular app
var editPrivilegePageValidation = angular.module( 'editPrivilegePageValidation', []);

// edit angular controller
editPrivilegePageValidation.controller( 'editPrivilegePageController', function( $scope ) {
	$scope.privilege_group_name = $( '#privilege_group_name' ).val();
	$scope.privilege_group_id	= $( '#privilege_group_id' ).val();
	
	$scope.privilege_id 			 = $( '#privilege_id' ).val();
	$scope.privilege_name 			 = $( '#privilege_name' ).val();
	$scope.privilege_description 	 = $( '#privilege_description' ).val();
	$scope.functionality_id 		 = $( '#functionality_id' ).val();
	$scope.functionality_name 		 = $( '#functionality_name' ).val();
	$scope.functionality_description = $( '#functionality_description' ).val();
	
	console.log( $scope );
	
	// function to submit the form after all validation has occurred            
	$scope.submitForm = function( isValid,event ) {
		event.preventDefault();
		
		//to check dropdown is  selected for privilege group
		var privilege_group_value = $( '#privilege_group_id' ).val();
		if( ( privilege_group_value == null ) || ( privilege_group_value == "" ) ){
			showModal( "error", "You have not selected any Privilege Group. Please select one and try again !" );
			return;
		}
	
	    // check to make sure the form is completely valid
	    if ( ! isValid ) {
	      showModal( "error", "Please check your form fields again !" );
	      return;
	    }
    
	    $.ajax({
	    	url:'webservice.php',
	    	type:'POST',
	    	data:  $( '#form_edit_privilege' ).serialize(),
	    	success: function( returned_data ){
	    		console.log( returned_data );
	    		
	    		$scope.form_edit_privilege_group = returned_data;
	    		
	    		var jSon = $.parseJSON( returned_data );
	    		$.each( jSon , function(){
	    			if( this[ 'type' ] == "error" ){
	    				showModal( "error", this[ 'info' ] );
	    				return;
	    			}
	    			if( this[ 'type' ] == "success" ){
	    				showModal( "success", this[ 'info' ] );
	    				setTimeout( function(){
	    					redirect( "adminpanel.php?what_do_you_want=view_privileges_page" );
	    				}, 1000 );
	    				return;
	    			}
	    		});
	    	}
	    });
	};
  
  $scope.submitPrivilegeGroup = function( isValid, event ){
	  event.preventDefault();
	  
	  $.ajax({
		  url:'webservice.php',
		  type:'POST',
		  data: $( '#form_edit_privilege_group' ).serialize(),
		  success: function( returned_data ){
			  console.log( returned_data );
			  var jSon = $.parseJSON( returned_data );
			  
			  $.each(jSon , function(){
				  if( this[ 'type' ] == 'error' ){
					  showModal( "error", this[ 'info' ] );
					  return;
				  }
				  if( this[ 'type' ] == 'success' ){
					  showModal( "success", this[ 'info' ] );
					  setTimeout( function(){
						  redirect( "adminpanel.php?what_do_you_want=view_privileges_page" );
	    				}, 2000 );
					  return;
				  }
			  });
		  }
	  });
  };
  
  $scope.editPrivilege = function(){
	  $( '#editPrivilegeForm' ).addClass( 'hidden' );
	  $( '#editPrivilegeGroupForm' ).addClass( 'hidden' );
	  $( '#editPrivilegeForm' ).removeClass( 'hidden' );
  };
  
  $scope.editPrivilegeGroup = function(){
	  $( '#editPrivilegeForm' ).addClass( 'hidden' );
	  $( '#editPrivilegeGroupForm' ).addClass( 'hidden' );
	  $( '#editPrivilegeGroupForm' ).removeClass( 'hidden' );
  }
  
  
  $( '#editPrivilegeForm' ).removeClass( 'hidden' );
});