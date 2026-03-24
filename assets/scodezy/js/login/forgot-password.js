$( document ).ready(function(){
    // //console.log( 'ready !' );
    
    // Initialize the Forgot Password Button with its functionality
    initForgotPasswordButton();
    
});

/**
 * Initialize the Forgot Password Button and produce its working
 * 
 */
function initForgotPasswordButton(){
    var btn_forgot_password    = getElementByID( 'btn_forgot_password' );
    var form                   = getElementByID( 'form_forgot_password' );
    var e_user_id              = getElementByID( 'user_id' );
   
    btn_forgot_password.on( 'click', function( e ){
        e.preventDefault();
        
        var parsley_user_id     = e_user_id.parsley();
        
        if( !parsley_user_id.isValid() ){
            showNotification( "error", "bottomRight", "Please check the User ID and try again !", 3000, 1 );
            return;
        }
        
        // Show Loading Animation on the Button
        btn_forgot_password.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_forgot_password );
        
        var val_user_id         = e_user_id.val();
        
        var data = {
            what_do_you_want: "scodezy_forgot_password",
            user_id: val_user_id
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );
                
                // Show Loading Animation on the Button
                btn_forgot_password.attr( 'data-kt-indicator', 'off' );
                enableFormElement( btn_forgot_password );
                
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
                    
                    showActionSweetAlert( data.info, "success", "Okay", "btn btn-primary", function(){
                        redirect( data.login_url );
                    });
                    
                    return;
                }
                
                
                
            }
        });
        

        
    });
    
    
    
}

