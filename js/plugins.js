$( document ).ready( function(){
	$( '#view-plugins-table' ).DataTable({});
});

var _for = "";
var _thees = "";
var _plugin_name = "";

function deletePlugin( plugin_name, thees ){
	showConfirmModal( "Confirm", "Are you sure you want to delete this plugin ? All the files and the data associated with this plugin will be lost !", "Yes", "No" );
	_for = "delete_plugin";
	_thees = thees;
	_plugin_name = plugin_name;
}

function yesClicked( forr ){
	if( _for == "delete_plugin" ){
		// $( _thees ).attr( 'disabled', "disabled" );
		
		$.ajax({
			url  : 'webservice.php',
			type : 'POST',
			data :  { 'what_do_you_want': 'delete_plugin', 'plugin_name' : _plugin_name },
			success : function( returned_data ){
				console.log( returned_data );
				var jSon = $.parseJSON( returned_data );
				
				$( _thees ).removeAttr( 'disabled' );
				
				$.each( jSon, function(){
					if( this[ 'type' ] == "error" ){
						showModal( "error", this[ 'info' ] );
						$( _thees ).removeAttr( 'disabled' );
						return;
					}
					if( this[ 'type' ] == "success" ){
						showModal( "success", this[ 'info' ] );
						$( _thees ).attr( 'disabled', "disabled" );
						setTimeout( function(){
							refreshPage();
						}, 3000 );
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
