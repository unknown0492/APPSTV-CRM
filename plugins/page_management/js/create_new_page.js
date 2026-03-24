$( document ).ready(function(){
	$('#functionality_id').selectpicker( 'refresh' );
});


var pageApp = angular.module('pageApp', []);

pageApp.controller( 'pageCtrl', function( $scope ) {
  
	$scope.submitPageGroup = function( isValid, event ){
	  event.preventDefault();
	  
	  if ( ! isValid ) {
	  	showNotification( "error", "bottomRight", "Please check your form fields again !", 5000, 1 );
	  	return;
	  }
	  
	  var fsa = new FormSubmitAnimator( "submit_form_create_page_group" );
	  fsa.showLoading();
	  
	  $.ajax({
		  url : 'webservice.php',
		  type : 'POST',
		  data : $( '#form_create_page_group' ).serialize(),
		  success: function( returned_data ){
			  console.log( returned_data );
			  fsa.hideLoading();
			  
			  var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
			  if( jSon == false ) return;
			  jSon = jSon[ 0 ];
			  
			  if( jSon[ 'type' ] == 'error' ){
				  showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
				  return;
			  }
			  if( jSon[ 'type' ] == 'success' ){
				  showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
				  $( '#reset_form_create_page_group' ).click();
				  
				  return;
			  }
		  }
	  });
  };
  
  $scope.submitForm = function( isValid, event ) {
		event.preventDefault();
		
		var is_visible = ( $( '#is_visible' ).bootstrapSwitch( 'state' ) )?"1":"0";
		
		//to check dropdown is  selected for page group name
		 var page_group_id_value = $( '#page_group_id' ).val();
		 if( ( page_group_id_value == null ) || ( page_group_id_value == "" ) || ( page_group_id_value == "none" ) ){
			 showNotification( "error", "bottomRight", "You have not selected any Page Group. Please select one and try again !", 5000, 1 );
			 return;
		 }
		 
		//to check dropdown is selected for functionality
		 var functionality_id_value = $( '#functionality_id' ).val();
		 if( ( functionality_id_value == null ) || ( functionality_id_value == "" )  || ( functionality_id_value == "none" ) ){
			 showNotification( "error", "bottomRight", "You have not selected any Functionality. Please select one and try again !", 5000, 1 );
			 return;
		 }

		 //to check dropdown is selected for plugin
		 var plugin_id = $( '#plugin_id' ).val();
		 if( ( plugin_id == null ) || ( plugin_id == "" ) || ( plugin_id == "none" ) ){
			 showNotification( "error", "bottomRight", "You have not selected any Plugin. Please select one and try again !", 5000, 1 );
			 return;
		 }
		 
	    // check to make sure the form is completely valid
	    if ( ! isValid ) {
	      showNotification( "error", "bottomRight", "Please check your form fields again !", 5000, 1 );
	      return;
	    }
	    
	    var fsa = new FormSubmitAnimator( "submit_form_create_page" );
		fsa.showLoading();
  
	    $.ajax({
	    	url:'webservice.php',
	    	type:'POST',
	    	data:  $( '#form_create_page' ).serialize() + "&is_visible="+is_visible,
	    	success: function( returned_data ){
	    		console.log( returned_data );
	    		
	    		fsa.hideLoading();
	    		
	    		var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
				if( jSon == false ) return;
				jSon = jSon[ 0 ];
				  
				if( jSon[ 'type' ] == 'error' ){
					showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
					return;
				}
				if( jSon[ 'type' ] == 'success' ){
					showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
					$( '#reset_form_create_page' ).click();
					$( '#functionality_id' ).selectpicker( 'refresh' );
					  
					return;
				}
	    	}
	    });
	};
  

});