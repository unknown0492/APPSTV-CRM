$( document ).ready(function(){
    ////console.log( 'ready !' );
    
    // Clear the Refresh Token from LocalStorage, because if the sign_out happens purely through php, the RTN will remain in localStorage
    removeDataFromLocalStorage( RTN );
    
    // Initialize the Login Button with its functionality
    initLoginButton();
    
});

/**
 * Initialize the Login Button and produce its working
 * 
 */
function initLoginButton(){
    var submitButton           = getElementByID( 'submit_login' );
    var form                   = getElementByID( 'form_login' );
    var user_id                = getElementByID( 'user_id' );
    var password               = getElementByID( 'password' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_login' );
   
    form.on( 'submit', function( e ){
        e.preventDefault();
        
        
        
        var parsley_user_id     = user_id.parsley();
        var parsley_password    = password.parsley();
        
        if( !parsley_user_id.isValid() ){
            showNotification( "error", "bottomRight", "Please check the User ID and try again !", 3000, 1 );
            return;
        }
        if( !parsley_password.isValid() ){
            showNotification( "error", "bottomRight", "Please check the Password and try again !", 3000, 1 );
            return;
        }
        
        // Show Loading Animation on the Button
        submitButton.attr( 'data-kt-indicator', 'on' );
        disableFormElement( submitButton );
        
        var val_user_id         = user_id.val();
        var val_password        = password.val();
        
        var data = {
            what_do_you_want: "scodezy_login",
            user_id: val_user_id,
            password: val_password
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                console.log( returned_data );
                
                // Show Loading Animation on the Button
                submitButton.attr( 'data-kt-indicator', 'off' );
                enableFormElement( submitButton );
                
                scodezyForm.getParsleyForm().reset();
                scodezyForm.getForm().trigger( "reset" ); 
                
                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    //showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );                    
                    showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];
                    //var refresh_token_name = data.refresh_token_name;
                    storeDataIntoLocalStorage( RTN, data[ RTN ] );
                    
                    showSimpleSweetAlert( data.info, "success", "", "" );
                    setTimeout(function(){
                        redirect( data.adminpanel_url );
                    }, 2500 );

                    return;
                }
                
                
                
            }
        });
        

        
    });
    
    
    
}

