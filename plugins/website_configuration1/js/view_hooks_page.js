$( document ).ready( function(){
	$( '#view-hooks-table' ).DataTable({});
});

var _for = "";
var _thees = "";
var _id = "";

function deleteHook( id, thees ){
	console.log('function is called success');
	showConfirmModal( "Confirm", "Are you sure you want to delete this hook ?" );
	_for = "delete_hook";
	_thees = thees;
	_id = id;
}

function editHook( id, thees ){
	console.log('edit function is called success');
	post( 'adminpanel.php?what_do_you_want=edit_hooks_page', { 'id' : id } );
}

function yesClicked( forr ){
	if( _for == "delete_hook" ){
		$( _thees ).attr( 'disabled', "disabled" );
		
		$.ajax({
			url  : 'webservice.php',
			type : 'POST',
			data :  { 'what_do_you_want': 'delete_hook', 'id' : _id },
			success : function( returned_data ){
				console.log( returned_data );
				var jSon = $.parseJSON( returned_data );
				
				$( _thees ).removeAttr( 'disabled' );
				
				$.each( jSon, function(){
					if( this[ 'type' ] == "error" ){
						showModal( "error", this[ 'info' ] );
						return;
					}
					if( this[ 'type' ] == "success" ){
						showModal( "success", this[ 'info' ] );
						$( _thees ).attr( 'disabled', "disabled" );
						$( _thees ).parent().parent().remove();
						return;
					}
				});
			}
		});
	}
}

function noClicked( forr ){
	$( '#modal-confirm' ).removeClass( 'md-show' );
}
