$( document ).ready(function(){
    
    // Password Fields comparison function
    checkIfPasswordAndReEnteredPasswordMatches();
    
    // Initialize the Update Password Button with its functionality
    initUpdatePasswordButton();
   
    
});

/**
 * Initialize the Update Password Button and produce its working
 * 
 */
function initUpdatePasswordButton(){
    var e_submit_uppdate_password   = getElementByID( 'submit_update_password' );
    var form                        = getElementByID( 'form_password_reset' );
    var e_password                  = getElementByID( 'password' );
    var e_retype_password           = getElementByID( 'retype_password' );
        
    form.on( 'submit', function( e ){
        e.preventDefault();
        
        var parsley_password           = e_password.parsley();
        var parsley_retype_password    = e_retype_password.parsley();
        
        if( !parsley_password.isValid() ){
            showNotification( "error", "bottomRight", "Please check the Password and try again !", 3000, 1 );
            return;
        }
        if( !parsley_retype_password.isValid() ){
            showNotification( "error", "bottomRight", "Please check the Re-entered Password and try again !", 3000, 1 );
            return;
        }
        
        // Show Loading Animation on the Button
        e_submit_uppdate_password.attr( 'data-kt-indicator', 'on' );
        disableFormElement( e_submit_uppdate_password );
        
        var val_password            = e_password.val();
        var val_retype_password     = e_retype_password.val();
        
        var data = {
            what_do_you_want: "scodezy_update_password_on_reset_page",
            password: val_password,
            retype_password: val_retype_password
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );
                
                // Show Loading Animation on the Button
                e_submit_uppdate_password.attr( 'data-kt-indicator', 'off' );
                enableFormElement( e_submit_uppdate_password  );
                
                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    if( jSon[ 'info' ] instanceof object ){
                        var data = jSon[ 'info' ];
                        showActionSweetAlert( data.info, "error", "Okay", "btn btn-primary", function(){
                            redirect( data.url );
                        });
                    }
                    else{                    
                        showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                    }
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];
                    
                    showActionSweetAlert( data.info, "success", "Okay", "btn btn-primary", function(){
                        redirect( data.url );
                    });
                    
                    return;
                }
                
            }
        });        
        
    });
    
    
    
}


/**
 * This function will compare the passwords and display an error if 
 * the comparison fails
 */
function checkIfPasswordAndReEnteredPasswordMatches(){
    var e_password             = getElementByID( 'password' );
    var e_retype_password      = getElementByID( 'retype_password' );
    var e_error_div            = getElementByID( 'input_retype_password_error' );
    
    e_retype_password.on( 'blur', function(){
        var val_password           = e_password.val();
        var val_retype_password    = e_retype_password.val();
        
        if( val_password != val_retype_password ){
            e_error_div.text( 'The password and re-entered password does not match !' );
        }
        else{
            e_error_div.text( '' );
        }
        
    });
}

/**
 * Check if the user id is available as soon as the user types it
 */
function checkIfUserIDIsAvailable(){
    
    var e_user_id                        = getElementByID( 'user_id' );
    var e_user_id_availability_msg       = getElementByID( 'user_id_availability_msg' );
    
    e_user_id.on( 'keyup', function(){
        e_user_id_availability_msg.text( '' ); 
        e_user_id_availability_msg.removeClass( 'text-danger text-success' ); 
    });
    
    e_user_id.on( 'blur', function(){
        
        var val_user_id     = e_user_id.val();
        if( val_user_id.length < 3 ){
            return;
        }
        
        var parsley_user_id = e_user_id.parsley();
        
        if( !parsley_user_id.isValid() ){
            return;
        }
        
        var data = {
            what_do_you_want: 'scodezy_is_user_id_available',
            user_id: val_user_id
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                ////console.log( returned_data );
                
                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 0 );                    
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];
                    e_user_id_availability_msg.text( data.info );
                    
                    if( data.is_available == "0" ){                        
                        e_user_id_availability_msg.addClass( 'text-danger' );
                    }
                    else if( data.is_available == "1" ){
                        e_user_id_availability_msg.addClass( 'text-success' );
                    }

                    return;
                }
            }
        });
        
    });
    
    

    
}


