$( document ).ready( function(){
	
	$( '.roles_tables' ).DataTable({
		"pageLength" : 100,
		"lengthChange" : false,     
		paging: false
	});
	
	$.each( $( '.roles_tables' ), function(){
		var thees = $( this );
		
		thees.find('.group-checkable').change(function () {
            var set = jQuery(this).attr("data-set");
            var checked = jQuery(this).is(":checked");
            jQuery(set).each(function () {
                if (checked) {
                    $(this).prop("checked", true);
                    $(this).parents('tr').addClass("active");
                } else {
                    $(this).prop("checked", false);
                    $(this).parents('tr').removeClass("active");
                }
            });
        });

		thees.on('change', 'tbody tr .checkboxes', function () {
            $(this).parents('tr').toggleClass("active");
        });
		
		//console.log( thees );
		
	});
	
});


// app.js
// create angular app
var createRolePageValidation = angular.module('createRolePageValidation', []);

// create angular controller
createRolePageValidation.controller('createRolePageController', function($scope) {
  // function to submit the form after all validation has occurred            
	
	$scope.role_name = "";
	
	$scope.isFormValidated = function( form_name ){
		
		if( form_name.$invalid )
			return false;
		
		return true;
	}
	
	$scope.createRole = function( form_name ){
		
		if( ! $scope.isFormValidated( form_name ) ){
			showNotification( "error", "bottomRight", "Your form field contains errors. Please correct and submit again !", 5000, 0 );
			return;
		}
		
		var privileges_val = $( '.privileges' ).val();
		var privs = new Array();
		$.each( $( '.privileges' ), function(){
			if( $( this ).is( ":checked" ) ){
				privs.push( $( this ).val() );
			}
		});
		// console.log( privs );
		// return;
		if( privs.length == 0 ){
			showNotification( "error", "bottomRight", "You have not selected any privilege for this Role. Please select few and try again !", 5000, 1 );
			return;
		}
		
		disableIt( 'submit_form_view_roles' );
		
		$.ajax({
			url	 : 'webservice.php',
			type : 'POST',
			data : { 'role_name' : $scope.role_name, 'privileges[]' : privs, 'what_do_you_want' : 'create_role' },
			success : function( returned_data ){
				console.log( returned_data );
				enableIt( 'submit_form_view_roles' );
				
				var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
	    		if( jSon == false ) return;
	    		
	    		jSon = jSon[ 0 ];
	    		// console.log( "info : " );
	    		if( jSon[ 'type' ] == "error" ){
					showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
					/*setTimeout( function(){
						refreshPage();
					}, 5000 );*/
					return;
				}
				if( jSon[ 'type' ] == "success" ){
					showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
					return;
				}
			
			}
		});
	}


});