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
	});
	
	
	
	$( '#form_view_roles' ).on( 'submit', function( e ){
		e.preventDefault();
		
		// alert( 'aa' );
		var role_id = $( '#role_id' ).val();
		if( ( role_id == "" ) || ( role_id == null ) ){
			showNotification( "error", "bottomRight", "Please select a Role Name to update", 5000, 1 );
			return;
		}
		
		var privileges_val = $( '.privileges' ).val();
		var privs = new Array();
		$.each( $( '.privileges' ), function(){
			if( $( this ).is( ":checked" ) ){
				privs.push( $( this ).val() );
			}
		});
		
		if( privs.length == 0 ){
			showNotification( "error", "bottomRight", "You have not selected any privilege for this Role. Please select few and try again !", 5000, 1 );
			return;
		}
		
		disableIt( 'submit_form_view_roles' );
		disableIt( 'delete_form_view_roles' );
		
		$.ajax({
			url	 : 'webservice.php',
			type : 'POST',
			data : { 'role_id' : role_id, 'privileges[]' : privs, 'what_do_you_want' : 'update_role' },
			success : function( returned_data ){
				// console.log( returned_data );
				enableIt( 'submit_form_view_roles' );
				enableIt( 'delete_form_view_roles' );
				
				var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
	    		if( jSon == false ) return;
	    		
	    		jSon = jSon[ 0 ];
	    		if( jSon[ 'type' ] == "error" ){
					showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
					return;
				}
				if( jSon[ 'type' ] == "success" ){
					showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
					return;
				}
			
			}
		});
	});
	
});

function getPrivileges(){
	post( 'adminpanel.php?what_do_you_want=view_roles_page', { role_id_selected : $( '#role_id' ).val() } );
}

var role_id = "";
function deleteRole(){
	role_id = $( '#role_id' ).val();
	
	if( ( role_id == "" ) || ( role_id == null ) ){
		showNotification( "error", "bottomRight", "Please select a Role !", 5000, 1 );
		return;
	}
	var c = confirm( "Are you sure you want to delete this role ? The users who possess this role will be reverted back to default role" );
	
	if( c ){
		yesClicked( "" );
	}
	
}


function yesClicked( forr ){
	disableIt( 'submit_form_view_roles' );
	disableIt( 'delete_form_view_roles' );
	
	$.ajax({
		url  : 'webservice.php',
		type : 'POST',
		data : { 'what_do_you_want' : 'delete_role', 'role_id' : role_id },
		success : function( returned_data ){
			// console.log( returned_data );
			enableIt( 'submit_form_view_roles' );
			enableIt( 'delete_form_view_roles' );
			
			var jSon = parseJSONWithError( returned_data, "Server side Script Error. Please contact the Administrator !" );
    		if( jSon == false ) return;
    		
    		jSon = jSon[ 0 ];
    		if( jSon[ 'type' ] == "error" ){
    			showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
    			return;
			}
			if( jSon[ 'type' ] == "success" ){
				alert( jSon[ 'info' ] );
				refreshPage();
				return;
			}
		
		}
	});
}

function noClicked( forr ){
	$( '#modal-confirm' ).removeClass( 'md-show' );
}