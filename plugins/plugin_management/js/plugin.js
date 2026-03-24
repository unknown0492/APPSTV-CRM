// app.js
// create angular app

var PluginValidation = angular.module('PluginValidation', []);

// create angular controller
PluginValidation.controller('PluginController', function($scope) {
	//function to submit the form after all validation has occurred            
	$scope.submitForm = function(isValid,event) {
		event.preventDefault();
	
    // check to make sure the form is completely valid
    if ( !isValid ) {
      showModal( "error", "Please check if you have filled all the fields correctly !" );
      return;
    }
    
    $.ajax({
    	url:'webservice.php',
    	type:'POST',
    	data:  $( '#form_plugin' ).serialize(),
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
    					refreshPage();
    				}, 1000 );
    				return;
    			}
    		});
    	}
    });
    
  };


var _thees = "";
var _plugin_name = "";	

  

});

function editPlugin( plugin_name , thees ){
	console.log( plugin_name );
	
	var param = {
  		'plugin_name' : plugin_name ,
  		'what_do_you_want' : 'get_plugin_data'
  	};

  	$.ajax({
    	url:'webservice.php',
    	type:'POST',
    	data: param,
    	success: function( returned_data ){
    		console.log( returned_data );
    		var jSon = $.parseJSON( returned_data );
    		$.each( jSon , function(){
    			if( this[ 'type' ] == "error" ){
    				showModal( "error", this[ 'info' ] );
    				return;
    			}
    			if( this[ 'type' ] == "success" ){
    				//showModal( "success", this[ 'info' ] );
    				var edit_plugin_name_val = this['info'].plugin_name;
    				var edit_plugin_display_name_val = this['info'].plugin_display_name;
    				$('#edit_plugin_name').val( edit_plugin_name_val );
    				$('#edit_plugin_display_name').val( edit_plugin_display_name_val );
    				return;
    			}
    		});
    	}
    });  

}

function updatePluginDetails(){

    $.ajax({
        url:'webservice.php',
        type:'POST',
        data:  $( '#form_edit_plugin' ).serialize(),
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
                    return;
                }
            });
        }
    });
}

function deletePlugin( plugin_name , thees ){
	showConfirmModal( "Confirm", "Are you sure you want to delete this plugin ? Deleting the plugin will also delete the corresponding functionality. We recommend you to use the Edit Plugin, in case you want to edit the plugin/functionality information ! ", "Yes", "No" );
	_for = "delete_plugin_name";
	_thees = thees;
	_plugin_name = plugin_name;
}

function yesClicked( forr ){
	if( _for == "delete_plugin_name" ){
		$( _thees ).attr( 'disabled', "disabled" );
		
		$.ajax({
			url  : 'webservice.php',
			type : 'POST',
			data :  { 'what_do_you_want': 'delete_plugin_name', 'plugin_name' : _plugin_name },
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