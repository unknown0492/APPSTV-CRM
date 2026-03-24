var pgDeleteCM, prDeleteCM;// = new ConfirmModal( "Confirm", "Are you sure you want to delete this privilege group ? The privileges present in this group will be moved to the \"Other Privileges\" group ", "Yes", "No" );
var table;
$( document ).ready( function(){
	pgDeleteCM = new ConfirmModal( "Confirm", "Are you sure you want to delete this privilege group ? The privileges present in this group will be moved to the \"Other Privileges\" group ", "Yes", "No" );
	prDeleteCM = new ConfirmModal( "Confirm", "Are you sure you want to delete this privilege ? Deleting the privilege will also delete the corresponding functionality. We recommend you to use the Edit Privilege, in case you want to edit the privilege/functionality information !", "Yes", "No" );

	table = $( '#view-privilege-table' ).DataTable( {
	    retrieve: true,
	});
	// changePrivilegeGroup( 'selected_privilege_group_id' );
	
	

});

$( window ).load(function(){
	/*console.log( "loaded" );
	changePrivilegeGroup( 'selected_privilege_group_id' );*/
});

var _for = "";
var _thees = "";
var _privilege_id = "";
var _privilege_group_id = "";

function deletePrivilege( privilege_id, thees ){
	prDeleteCM.showConfirm();
	prDeleteCM.positiveFunction = function(){
		deletePR( privilege_id, thees );
	}
}

function deletePrivilegeGroup( thees ){
	pgDeleteCM.showConfirm();
	pgDeleteCM.positiveFunction = function(){
		deletePG();
	}
}

function editPrivilege( privilege_id, thees ){
	// post( 'adminpanel.html?what_do_you_want=edit_privilege_page', { 'privilege_id' : privilege_id } );
	
	$( '#edit-privilege-modal' ).modal( 'show' );
	
	// Show Loading
	$( '.privileges-loading' ).removeClass( 'hidden' );
	$( '.privileges-loaded' ).addClass( 'hidden' );
	
	
	// Get privilege information
	$.ajax({
		url : 'webservice.php',
		type : 'POST',
		data : { what_do_you_want : 'get_privilege_information', 'privilege_id' : privilege_id },
		success : function( returned_data ){
			console.log( returned_data );
			
			var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
    		if( jSon == false ){
    			// Hide Modal
    			$( '#edit-privilege-modal' ).modal( 'hide' );
    			return;
    		}
    		
    		jSon = jSon[ 0 ];
    		if( jSon[ 'type' ] == "error" ){
    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
    			
    			// Hide Modal
    			$( '#edit-privilege-modal' ).modal( 'hide' );
    			
    			return;
    		}
			if( jSon[ 'type' ] == "success" ){
				
				var json = $.parseJSON( jSon[ 'info' ] );
				json = json[ 0 ];
				
				$( '#privilege_group_id' ).val( json[ 'privilege_group_id' ] );
				$( '#privilege_group_id' ).selectpicker( 'refresh' );
				
				$( '#is_page' ).val( json[ 'is_page' ] );
				$( '#is_page' ).selectpicker( 'refresh' );
				
				$( '#plugin_id' ).val( json[ 'plugin_id' ] );
				$( '#plugin_id' ).selectpicker( 'refresh' );
				
				$( '#privilege_name' ).val( json[ 'privilege_name' ] );
				$( '#privilege_description' ).val( json[ 'privilege_description' ] );
				$( '#functionality_name' ).val( json[ 'functionality_name' ] );
				$( '#functionality_description' ).val( json[ 'functionality_description' ] );
				
				// Hide Loading
				$( '.privileges-loading' ).addClass( 'hidden' );
				$( '.privileges-loaded' ).removeClass( 'hidden' );
				
				global_scope.$apply(function(){
					
				});
				
				return;
			}
		}
	});
	
}

function deletePG(){
	// alert( 'inside here' );
	disableIt( 'delete_privilege_group_button' );
	
	$.ajax({
		url  : 'webservice.php',
		type : 'POST',
		data :  { 'what_do_you_want': 'delete_privilege_group', 'privilege_group_id' : $( '#selected_privilege_group_id' ).val() },
		success : function( returned_data ){
			// console.log( returned_data );
			var jSon = parseJSONWithError( returned_data, "Server Side error encountered !" );
			if( ! jSon ) return;
			
			enableIt( 'delete_privilege_group_button' );
			
			jSon = jSon[ 0 ];
			
			if( jSon[ 'type' ] == "error" ){
				showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 0 );
				return;
			}
			if( jSon[ 'type' ] == "success" ){
				showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 0 );
				//$( "#selected_privilege_group_id>option[value='"+ $( '#selected_privilege_group_id' ).val() +"']" ).remove();
				$( '#selected_privilege_group_id' ).val( '0' );
				// angular.element( $( '#edit_privilege_ctrl' ) ).scope().selected_privilege_group_id = "0";
				// angular.element( $( '#selected_privilege_group_id' ) ).scope().$apply();
				//$( '#selected_privilege_group_id' ).change();
				// angular.element( $( '#edit_privilege_ctrl' ) ).scope().changePrivilegeGroup( 'selected_privilege_group_id' );
				setTimeout( function(){
					refreshPage();
				}, 2000 );
				return;
			}
		
		}
	});
}

function deletePR( privilege_id, thees ){
	$( thees ).attr( 'disabled', "disabled" );
	
	$.ajax({
		url  : 'webservice.php',
		type : 'POST',
		data :  { 'what_do_you_want': 'delete_privilege', 'privilege_id' : privilege_id },
		success : function( returned_data ){
			// console.log( returned_data );
			
			
			
			var jSon = parseJSONWithError( returned_data, "Server side error !" );
			if( jSon == false ) return;
			
			jSon = jSon[ 0 ];
			
			$( thees ).removeAttr( 'disabled' );
			
				if( jSon[ 'type' ] == "error" ){
					showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 0 );
					return;
				}
				if( jSon[ 'type' ] == "success" ){
					showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 0 );
					
					// $( thees ).attr( 'disabled', "disabled" );
					// $( thees ).parent().parent().remove();
					
					table.row( $( '#view-privilege-table tr[data-item-id="'+ privilege_id +'"]' ) ).remove();
					$( '#view-privilege-table tr[data-item-id="'+ privilege_id +'"]' ).remove();
					return;
				}
			
		}
	});
}

function showTableLoading(){
	$( '.table-loading' ).removeClass( 'hidden' );
	$( '#view-privilege-table' ).addClass( 'hidden' );
}

function hideTableLoading(){
	$( '.table-loading' ).addClass( 'hidden' );
	$( '#view-privilege-table' ).removeClass( 'hidden' );
}

var app = angular.module( 'edit_privilege_app', [] );
app.controller( 'edit_privilege_ctrl', function( $scope, $compile ){
	
	$scope.is_page 		= "0";
	$scope.plugin_id 	= "none";
	$scope.privilege_group_id = "0";
	$scope.selected_privilege_group_id = "0";
	
	angular.element( document ).ready( function(){
		$scope.changePrivilegeGroup( 'selected_privilege_group_id' );
    });
	
	// function to submit the form after all validation has occurred            
	$scope.submitForm = function( isValid, event ) {
		event.preventDefault();
		
		
		// Validate is_page field
		if( $scope.is_page == "" ){
			showNotification( "error", "bottmRight", "Please choose if the Privilege is a Page or only a Functionality !", 5000, 1 );
			return;
		}
		
		// Validate if plugin_id is selected
		if( $scope.plugin_id == "none" ){
			showNotification( "error", "bottmRight", "Please choose a Plugin !", 5000, 1 );
			return;
		}
		
		// Validate if privilege_group_id is selected
		if( $scope.plugin_id == "none" ){
			showNotification( "error", "bottmRight", "Please choose a Privilege Group !", 5000, 1 );
			return;
		}
		
		// check to make sure the form is completely valid
	    if ( ! isValid ) {
	      showModal( "error", "There arer some errors in your form !" );
	      return;
	    }
    
	};
	

	$scope.editPrivilege = function( privilege_id, thees ){
		// post( 'adminpanel.html?what_do_you_want=edit_privilege_page', { 'privilege_id' : privilege_id } );
		
		$( '#edit-privilege-modal' ).modal( 'show' );
		
		// Show Loading
		$( '.privileges-loading' ).removeClass( 'hidden' );
		$( '.privileges-loaded' ).addClass( 'hidden' );
		
		// Get privilege information
		$.ajax({
			url : 'webservice.php',
			type : 'POST',
			data : { what_do_you_want : 'get_privilege_information', 'privilege_id' : privilege_id },
			success : function( returned_data ){
				// console.log( returned_data );
				
				var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
	    		if( jSon == false ){
	    			// Hide Modal
	    			$( '#edit-privilege-modal' ).modal( 'hide' );
	    			return;
	    		}
	    		
	    		jSon = jSon[ 0 ];
	    		if( jSon[ 'type' ] == "error" ){
	    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
	    			
	    			// Hide Modal
	    			$( '#edit-privilege-modal' ).modal( 'hide' );
	    			
	    			return;
	    		}
				if( jSon[ 'type' ] == "success" ){
					
					var json = $.parseJSON( jSon[ 'info' ] );
					json = json[ 0 ];
					
					$scope.privilege_group_id = json[ 'privilege_group_id' ];
					$( '#privilege_group_id' ).val( json[ 'privilege_group_id' ] );
					// $( '#privilege_group_id' ).selectpicker( 'refresh' );
					
					$scope.is_page = json[ 'is_page' ];
					$( '#is_page' ).val( json[ 'is_page' ] );
					// $( '#is_page' ).selectpicker( 'refresh' );
					
					$scope.plugin_id = json[ 'plugin_id' ];
					$( '#plugin_id' ).val( json[ 'plugin_id' ] );
					// $( '#plugin_id' ).selectpicker( 'refresh' );
					
					
					$scope.privilege_id = json[ 'privilege_id' ];
					$scope.privilege_name = json[ 'privilege_name' ];
					$scope.privilege_description = json[ 'privilege_description' ];
					$scope.functionality_id = json[ 'functionality_id' ];
					$scope.functionality_name = json[ 'functionality_name' ];
					$scope.functionality_description = json[ 'functionality_description' ];
					
					$( '#privilege_id' ).val( json[ 'privilege_id' ] );
					$( '#privilege_name' ).val( json[ 'privilege_name' ] );
					$( '#privilege_description' ).val( json[ 'privilege_description' ] );
					$( '#functionality_id' ).val( json[ 'functionality_id' ] );
					$( '#functionality_name' ).val( json[ 'functionality_name' ] );
					$( '#functionality_description' ).val( json[ 'functionality_description' ] );
					
					// Hide Loading
					$( '.privileges-loading' ).addClass( 'hidden' );
					$( '.privileges-loaded' ).removeClass( 'hidden' );
					
					
					// $scope.$apply();
					
					return;
				}
			}
		});
		
	}
	
	
	
	$scope.changePrivilegeGroup = function( id ){
		var selected_privilege_group_id = $( '#selected_privilege_group_id' ).val();
		// alert( selected_privilege_group_id );
		// post( 'adminpanel.html?what_do_you_want=view_privileges_page', { 'selected_privilege_group_id' : selected_privilege_group_id } );
		
		// Show Loading Icon
		showTableLoading();
		
		// Empty the DataTable
		table.destroy();
		$( '#view-privilege-table > tbody' ).html( "" );
		
		$.ajax({
			url : 'webservice.php',
			type : 'POST',
			data : { what_do_you_want : 'get_privileges_from_selected_group', privilege_group_id : selected_privilege_group_id },
			success : function( returned_data ){
				// console.log( returned_data );
				
				// Hide Loading Icon
				hideTableLoading();
				
				var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
	    		if( jSon == false ) return;
	    		
	    		jSon = jSon[ 0 ];
	    		if( jSon[ 'type' ] == "error" ){
	    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
	    			table = $( '#view-privilege-table' ).DataTable( {
					    retrieve: true,
					});
	    			return;
	    		}
				if( jSon[ 'type' ] == "success" ){
					
					var json = $.parseJSON( jSon[ 'info' ] );
					var count = 1;
					var data = "";
					$.each( json, function(){
						// console.log( this );
						data += "<tr data-item-id=\""+ this[ 'privilege_id' ] +"\">" +
							"<td>" + (count++) + "</td>" + 
							"<td>" + this[ 'privilege_id' ] + "</td>" + 
							"<td>" + this[ 'privilege_name' ] + "</td>" + 
							"<td>" + this[ 'privilege_description' ] + "</td>" + 
							"<td>" + this[ 'functionality_id' ] + "</td>" + 
							"<td>" + this[ 'functionality_name' ] + "</td>" + 
							"<td>" + this[ 'functionality_description' ] + "</td>" + 
							"<td>" + ((this[ 'is_page' ]=="1")?"Yes":"No") + "</td>"  + 
							"<td style=\"text-align: center; width: 100px;\">" + 
								"<button type=\"button\" style=\"margin: 2px;\" class=\"btn red\" ng-click=\"editPrivilege( '"+ this[ 'privilege_id' ] +"', this );\" title=\"Edit Privilege\" alt=\"Edit Privilege\"><i class=\"fa fa-pencil\"> </i></button><button type=\"button\" style=\"margin: 2px;\" class=\"btn green\" onclick=\"javascript:deletePrivilege( '"+ this[ 'privilege_id' ] +"', this );\" title=\"Delete Privilege\" alt=\"Delete Privilege\"><i class=\"fa fa-trash-o\"> </i></button>" + 
							"</td>"
						"</tr>";
						var $data = $( data ).appendTo( '#view-privilege-table tbody' );
						$compile($data)($scope);
						//$( '#view-privilege-table tbody' ).append( data );
						
						
						data = "";
					});
					table = $( '#view-privilege-table' ).DataTable( {
					    retrieve: true,
					});
					
					return;
				}
			}
		});
		
	}
	
	$scope.updatePrivilege = function( form, e ){
		e.preventDefault();
		
		// Validate is_page field
		if( $scope.is_page == "" ){
			showNotification( "error", "bottmRight", "Please choose if the Privilege is a Page or only a Functionality !", 5000, 1 );
			return;
		}
		
		// Validate if plugin_id is selected
		if( $scope.plugin_id == "none" ){
			showNotification( "error", "bottmRight", "Please choose a Plugin !", 5000, 1 );
			return;
		}
		
		// Validate if privilege_group_id is selected
		if( $scope.privilege_group_id == "none" ){
			showNotification( "error", "bottmRight", "Please choose a Privilege Group !", 5000, 1 );
			return;
		}
		
		// check to make sure the form is completely valid
	    if ( form.$invalid ) {
	      showModal( "error", "There arer some errors in your form !" );
	      return;
	    }
		
	    // Show loading
	    $( '#submit_'+form.$name ).hide();
	    $( '.loading-update-privilege' ).removeClass( 'hidden' );
	    
	    $.ajax({
	    	url:'webservice.php',
	    	type:'POST',
	    	data:  $( '#form_edit_privilege' ).serializeObject(),
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
	    			
    				// Hide loading
				    $( '#submit_'+form.$name ).show();
				    $( '.loading-update-privilege' ).addClass( 'hidden' );
    				$( '#edit-privilege-modal' ).modal( 'hide' );
    				
    				$scope.changePrivilegeGroup( '' );
    				
    				return;
    			}
	    		
	    	}
	    });
	}
	
});
