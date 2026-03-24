$( document ).ready(function(){
    
    // Check for the availability of User ID
    checkIfUserIDIsAvailable();
    
    // Password Fields comparison function
    checkIfPasswordAndReEnteredPasswordMatches();
    
    // Initialize the Sign Up Button with its functionality
    initSignUpButton();
   
    
});

/**
 * Initialize the Sign Up Button and produce its working
 * 
 */
function initSignUpButton(){
    var e_btn_sign_up          = getElementByID( 'btn_sign_up' );
    var form                   = getElementByID( 'form_sign_up' );
    var e_first_name           = getElementByID( 'first_name' );
    var e_last_name            = getElementByID( 'last_name' );
    var e_user_id              = getElementByID( 'user_id' );
    var e_email                = getElementByID( 'email' );
    var e_password             = getElementByID( 'password' );
    var e_retype_password      = getElementByID( 'retype_password' );
    var e_accept_terms         = getElementByID( 'terms' );
    
    // Pre-fill temporary values to the form elements
    ///*
    e_first_name.val( 'aaaa' );
    e_last_name.val( 'aaaa' );
    e_user_id.val( 'aaaa' );
    e_email.val( 'unknown0492@gmail.com' );
    e_password.val( 'aaaa' );
    e_retype_password.val( 'aaaa' );
    //*/
   
    e_btn_sign_up.on( 'click', function(){
        
        var parsley_first_name         = e_first_name.parsley();
        var parsley_last_name          = e_last_name.parsley();
        var parsley_user_id            = e_user_id.parsley();
        var parsley_email              = e_email.parsley();
        var parsley_password           = e_password.parsley();
        var parsley_retype_password    = e_retype_password.parsley();
        
        if( !parsley_first_name.isValid() ){
            showNotification( "error", "bottomRight", "Please check the First Name and try again !", 3000, 1 );
            return;
        }
        if( !parsley_last_name.isValid() ){
            showNotification( "error", "bottomRight", "Please check the Last Name and try again !", 3000, 1 );
            return;
        }
        if( !parsley_user_id.isValid() ){
            showNotification( "error", "bottomRight", "Please check the User ID and try again !", 3000, 1 );
            return;
        }
        if( !parsley_email.isValid() ){
            showNotification( "error", "bottomRight", "Please check the Email and try again !", 3000, 1 );
            return;
        }
        if( !parsley_password.isValid() ){
            showNotification( "error", "bottomRight", "Please check the Password and try again !", 3000, 1 );
            return;
        }
        if( !parsley_retype_password.isValid() ){
            showNotification( "error", "bottomRight", "Please check the Re-entered Password and try again !", 3000, 1 );
            return;
        }
        
        // Accept the terms
        if( !e_accept_terms.is( ':checked' ) ){
            showNotification( "error", "bottomRight", "Please read and accept the Terms !", 3000, 1 );
            return;
        }
        
        // Show Loading Animation on the Button
        e_btn_sign_up.attr( 'data-kt-indicator', 'on' );
        disableFormElement( e_btn_sign_up );
        
        var val_first_name          = e_first_name.val();
        var val_last_name           = e_last_name.val();
        var val_user_id             = e_user_id.val();
        var val_email               = e_email.val();
        var val_password            = e_password.val();
        var val_retype_password     = e_retype_password.val();
        var val_accept_terms        = e_accept_terms.is( ':checked' );
        
        var data = {
            what_do_you_want: "scodezy_sign_up",
            first_name: val_first_name,
            last_name: val_last_name,
            user_id: val_user_id,
            email: val_email,
            password: val_password,
            retype_password: val_retype_password,
            accept_terms: val_accept_terms
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );
                
                // Show Loading Animation on the Button
                e_btn_sign_up .attr( 'data-kt-indicator', 'off' );
                enableFormElement( e_btn_sign_up  );
                
                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];
                    
                    showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
                        redirect( data.login_url ); 
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


