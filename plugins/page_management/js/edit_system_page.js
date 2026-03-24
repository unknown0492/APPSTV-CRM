// app.js
// edit angular app
var editPageValidation = angular.module('editPageValidation', []);

// edit angular controller
editPageValidation.controller('editPageController', function($scope) {
  // function to submit the form after all validation has occurred          
	
	$scope.page_group_name = $( '#page_group_name' ).val(  );
	$scope.page_group_icon = $( '#page_group_icon' ).val(  );
	$scope.page_name	   = $( '#page_name' ).val();
	$scope.page_title	   = $( '#page_title' ).val();	
	$scope.icon			   = $( '#icon' ).val();
	$scope.description	   = $( '#description' ).val();
	$scope.tags			   = $( '#tags' ).val();
	$scope.image		   = $( '#image' ).val();
	$scope.content		   = $( '#content' ).val();
	$scope.plugin_name	   = $( '#plugin_name' ).val();
	
	
	$scope.submitForm = function( isValid, event ) {
		event.preventDefault();
		
		//to check dropdown is  selected for page group name
		 var page_group_id_value = $( '#page_group_id' ).val();
		 if( ( page_group_id_value == null ) || ( page_group_id_value == "" ) ){
			 showModal( "error", "You have not selected any Page Group. Please select one and try again !" );
			 return;
		 }
		 
		//to check dropdown is selected for page sequence
		 var page_sequence_value = $( '#page_sequence' ).val();
		 if( ( page_sequence_value == null ) || ( page_sequence_value == "" ) ){
			 showModal( "error", "You have not selected any Page Sequence. Please select one and try again !" );
			 return;
		 }
		 
		//to check dropdown is selected for visibility
		 var visible_value = $( '#visible' ).val();
		 if( ( visible_value == null ) || ( visible_value == "" ) ){
			 showModal( "error", "You have not selected any Visibility. Please select one and try again !" );
			 return;
		 }
		 
		//to check dropdown is selected for functionality
		 var functionality_id_value = $( '#functionality_id' ).val();
		 if( ( functionality_id_value == null ) || ( functionality_id_value == "" ) ){
			 showModal( "error", "You have not selected any Functionality. Please select one and try again !" );
			 return;
		 }
		 
		 //to check dropdown is selected for plugin
		 var plugin_name_value = $( '#plugin_name' ).val();
		 if( ( plugin_name_value == null ) || ( plugin_name_value == "" ) ){
			 showModal( "error", "You have not selected any plugin. Please select one and try again !" );
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
	    	data:  $( '#form_edit_page' ).serialize(),
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
	    					redirect( "adminpanel.php?what_do_you_want=view_system_page" );
	    					//post( "adminpanel.html?what_do_you_want=view_system_page", { selected_page_group_id : $( '#selected_page_group_id' ).val() } );
	    				}, 1000 );
	    				return;
	    			}
	    		});
	    	}
	    });
	};
  
  $scope.submitPageGroup = function( isValid, event ){
	  event.preventDefault();
	  
	 //to check dropdown is  selected for page group
	 var page_group_value = $( '#page_group_sequence' ).val();
	 if( ( page_group_value == null ) || ( page_group_value == "" ) ){
		 showModal( "error", "You have not selected any value for Page Group Sequence. Please select one and try again !" );
		 return;
	 }
	 
	// check to make sure the form is completely valid
	    if ( ! isValid ) {
	      showModal( "error", "Fields contain error/s" );
	      return;
	    }
	    
	    console.log( $( '#form_edit_page_group' ).serialize() );
	  
	  $.ajax({
		  url:'webservice.php',
		  type:'POST',
		  data: $( '#form_edit_page_group' ).serialize(),
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
	    				   redirect( "adminpanel.php?what_do_you_want=view_system_page" );
						  //post( "adminpanel.html?what_do_you_want=view_system_page", { selected_page_group_id : $( '#selected_page_group_id' ).val() } );
	    				}, 2000 );
					  return;
				  }
			  });
		  }
	  });
  };
  
  $scope.editPage = function(){
	  $( '#editPageForm' ).addClass( 'hidden' );
	  $( '#editPageGroupForm' ).addClass( 'hidden' );
	  $( '#editPageForm' ).removeClass( 'hidden' );
  };
  
  $scope.editPageGroup = function(){
	  $( '#editPageForm' ).addClass( 'hidden' );
	  $( '#editPageGroupForm' ).addClass( 'hidden' );
	  $( '#editPageGroupForm' ).removeClass( 'hidden' );
  }

});