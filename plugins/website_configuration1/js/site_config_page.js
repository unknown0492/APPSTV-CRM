$( document ).ready( function(){
	
	var options = {
		form_name : "form_upload_login_page_logo",
		allowed_file_types : [ "image/png", "image/jpg", "image/gif", "image/jpeg" ],
		max_file_size	  : 500000, // 500KB
		file_param_name   : 'file',
		extra_params	  : { what_do_you_want : 'upload_logo_image', file_name : "login-page-logo" },
		url 			  : 'webservice.php',
		onResponse : function( response ){
			var jSon = parseJSONWithError( response, "JSON error occurred ! Please contact the Administrator !" );
	 		if( jSon == false ) return;
			jSon = jSon[ 0 ];
	 	
 			if( jSon[ 'type' ] == "error" ){
 				alert( jSon[ 'info' ] );
 				return;
 			}
 			if( jSon[ 'type' ] == "success" ){
 				var json = $.parseJSON( jSon[ 'info' ] );
 				//json = json[ 0 ];
 				
 		    	var path = json[ 'image_path' ];//"images/logo/horizontal-cms-logo.png";
 		    	var file_name = json[ 'file_name' ];
 		    	//console.log( '.img-' + options.file_name + "----" + path );
 		    	$( '.img-' + file_name ).attr( 'src', path + "?"+(new Date()).getTime() );
 				
 				return;
 			}
			
		},
		
	};
	$( '#login_page_logo' ).SCAjaxFileUpload( options );
	// console.log( options );
	
	var options1 = $.extend( true, {}, options ); // clone the options object
	options1.form_name = "form_upload_admin_page_logo";
	options1.extra_params.file_name = "admin-page-logo";
	
	$( '#admin_page_logo' ).SCAjaxFileUpload( options1 );
	
	
	// Extra Options
	
	$( '#form_extra_options' ).on( 'submit', function( e ){
		e.preventDefault();
		
		console.log( e.originalEvent );
		
		
		
	});
	
	$( '.extra-option-switch' ).on( 'switchChange.bootstrapSwitch', function( e, state ){
		/*$( '#form_extra_options' ).trigger( 'submit' );*/
		// console.log(  );
		
		var value = (state)?"1":0;
		
		$.ajax({
			url : 'webservice.php',
			type : 'POST',
			data : { what_do_you_want : 'update_extra_options', update_what : $( this ).val(), 'value' : value },
			success : function( returned_data ){
				console.log( returned_data );
			}
			
		});
	});
	
});

var options = {
	url : "webservice.php?what_do_you_want=upload_logo_image&image_type=login_logo_image",
	paramName: "file", // The name that will be used to transfer the file
	maxFilesize: 2, // MB
	success : function( f, response ){
		console.log( f );
		console.log( response );
	},
	//previewTemplate : $( '.dropzone' ).html(),
	//previewsContainer : "login_page_logo",
	maxFiles : 1
	
}


// create angular app
var siteConfigApp = angular.module( 'siteConfigApp', [] );

// create angular controller
siteConfigApp.controller( 'siteConfigCtrl', function( $scope ) {
	
	//to show values in input types store it in scope variables
	$scope.site_name		= $( '#site_name' ).val();
	$scope.site_tagline		= $( '#site_tagline' ).val();
	$scope.site_root_path		= $( '#site_root_path' ).val();
	//$scope.site_logo_image	= $( '#site_logo_image' ).val();
	// $scope.protocol			= $( '#protocol' ).val();
	
	//console.log($( '#site_name' ).val());
	
  // function to submit the form after all validation has occurred            
	$scope.saveSiteConfig = function( isValid, event ) {
		event.preventDefault();
		
		//to check dropdown is selected for protocol
		var protocol_value = $( '.protocol:checked' ).val();
		
		if( ( protocol_value == null ) || ( protocol_value == "" ) ){
			showNotification( "error", "bottomRight", "You have not selected any Protocol. Please select one and try again !", 5000, 1 );
			return;
		}
		 
	    // check to make sure the form is completely valid
	    if ( ! isValid ) {
	    	showNotification( "error", "bottomRight", "Please fill the form fields properly before updating !", 5000, 1 );
			return;
	    }
	    
	    //console.log( $( '#form_general_config' ).serialize() );
	    //return;
	    
	    $.ajax({
	    	url:'webservice.php',
	    	type:'POST',
	    	data:  $( '#form_general_config' ).serialize() + "&what_do_you_want=update_site_general_config" ,
	    	success: function( returned_data ){
	    		console.log( returned_data );
	    		
	    		var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
	    		if( jSon == false ) return;
	    		
	    		jSon = jSon[ 0 ];
	    		if( jSon[ 'type' ] == "error" ){
	    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 )
	    			return;
	    		}
    			if( jSon[ 'type' ] == "success" ){
    				showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 )
	    			
    				return;
    			}
	    	}
	    }); //ajax ends here
	};	//submit function ends here
 });	//controller ends here