const loadingEl = document.createElement("div");
var _e_settings_tab_navbar      = getElementByClass( 'settings-tab-navbar' );
var _e_profile_details_edit     = getElementByClass( 'profile_details_edit' );
var _e_btn_edit_profile         = getElementByID( 'btn_edit_profile' );
var _e_btn_cancel_edit_profile  = getElementByID( 'btn_cancel_edit_profile' );
var _e_btn_save_profile_details = getElementByID( 'btn_save_profile_details' );
var _e_profile_details_view     = getElementByClass( 'profile_details_view' );
var _e_profile_details_edit     = getElementByClass( 'profile_details_edit' );
var profileDetails = "";

var verified_badge      = '<span class="badge badge-success" id="email_badge_v">Verified</span>';
var not_verified_badge  = '<span class="badge badge-danger" id="email_badge_nv">Not Verified</span>';

// View Profile Details
var e_user_id_v;
var e_fname_v;
var e_lname_v;
var e_nickname_v;

// Edit Profile Details
var e_user_id_e;
var e_fname_e;
var e_lname_e;
var e_nickname_e;

// View and Update Contact Information
var e_email_v;
var e_phone_v;
var e_email_badge_v;
var _e_div_contact_email_v;
var _e_div_contact_email_e;
var e_email_e;
var e_password_e;
var e_btn_update_email;
var e_btn_cancel_update_email;
var _e_div_change_email_button;
var e_btn_change_email;

$( document ).ready(function(){
    
    initDOMElements();
    
    hideAllSettingsBlock();
    
    loadAllSettings();
    
    initSettingsTabClick();
    
    initEditProfileListener();
    
    changeEmailListeners();
    
    changePhoneListeners();
    
    initPhoneUpdateOtpModal();
    
    requestOTPAgain();
    
    verifyOTP();
    
    /*
    var btn_change_password = convertToAnimatedButton( getElementByClass( 'btn_change_password' ) );
    btn_change_password.on( 'click', function(){  
        showLoadingOnButton( btn_change_password );
        
        setTimeout(function(){
            hideLoadingOnButton( btn_change_password );
        }, 3000 );
    });
    */
   
    changePasswordListeners();
});

function update_password(){
    var btn_submit              = getElementByID( 'btn_update_password' );
    var btn_cancel              = getElementByID( 'btn_cancel_update_password' );
    
    var e_current_password      = getElementByID( 'p_password_e' );
    var e_new_password          = getElementByID( 'p_new_password_e' );
    var e_confirm_password      = getElementByID( 'p_confirm_password_e' );
    
    var parsley_current_password    = e_current_password.parsley();
    var parsley_new_password        = e_new_password.parsley();
    var parsley_confirm_password    = e_confirm_password.parsley();

    if( !parsley_current_password.isValid() ){
        showSimpleToast( "error", "Current password is invalid !" );
        return;
    }
    if( !parsley_new_password.isValid() ){
        showSimpleToast( "error", "New password is invalid !" );
        return;
    }
    if( !parsley_confirm_password.isValid() ){
        showSimpleToast( "error", "Confirm new password is invalid !" );
        return;
    }
    
    var current_password_val    = e_current_password.val();
    var new_password_val        = e_new_password.val();
    var confirm_password_val    = e_confirm_password.val();

    
    if( new_password_val != confirm_password_val ){
        showSimpleToast( "error", "New password and confirm password does not match !" );
        return;
    }

    var data = {
        what_do_you_want: 'scodezy_update_self_password',
        current_password: current_password_val,
        new_password: new_password_val,
        confirm_password: confirm_password_val
    };


    // Show Loading Animation on the Button
    showLoadingOnButton( btn_submit );

    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        success: function( returned_data ){
            //console.log( returned_data );

            // Hide Loading Animation on the Button
            hideLoadingOnButton( btn_submit );

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

                var jsonInfo        = jSon[ 'info' ];
                var message         = jsonInfo.message;
                //var data            = jsonInfo.data;

                showSimpleSweetAlert( message, "success", "Okay", "btn btn-primary" );

                btn_cancel.trigger( 'click' );

                return;
            }

        }
    });
}

function changePasswordListeners(){
    var div_change_pasword      = getElementByID( 'div_change_password' );
    var div_p_password_e        = getElementByID( 'div_p_password_e' );
    var div_p_password_v        = getElementByID( 'p_password_v' );
    var btn_change_password     = getElementByID( 'btn_change_password' );
    
    var btn_submit              = getElementByID( 'btn_update_password' );
    var btn_cancel              = getElementByID( 'btn_cancel_update_password' );
    
    var e_current_password      = getElementByID( 'p_password_e' );
    var e_new_password          = getElementByID( 'p_new_password_e' );
    var e_confirm_password      = getElementByID( 'p_confirm_password_e' );
    
    btn_submit.on( 'click', function(){
        
        update_password();
        
    });
    
    btn_cancel.on( 'click', function(){
        var scodezyForm     = new ScodezyForm();
        scodezyForm.initForm( '#form_change_self_password' );

        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        hide( div_p_password_e );
        show( div_p_password_v );
        show( div_change_pasword );
        
    });
    
    btn_change_password.on( 'click', function(){
        var scodezyForm     = new ScodezyForm();
        scodezyForm.initForm( '#form_change_self_password' );

        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        hide( div_change_pasword );
        hide( div_p_password_v );
        show( div_p_password_e );
    });
    
    /*
    e_btn_change_email.on( 'click', function(){
        var scodezyForm     = new ScodezyForm();
        scodezyForm.initForm( '#form_update_email' );

        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        hide( _e_div_change_email_button );
        hide( _e_div_contact_email_v );
        
        show( _e_div_contact_email_e );
        
        //e_email_e.val( profileDetails.email );
        e_email_e.val( '' );
    });
    
    e_btn_cancel_update_email.on( 'click', function(){
        var scodezyForm     = new ScodezyForm();
        scodezyForm.initForm( '#form_update_email' );

        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        hide( _e_div_contact_email_e );
        
        show( _e_div_contact_email_v );
        show( _e_div_change_email_button );
        
        //e_email_e.val( profileDetails.email );
        e_email_e.val( '' );
    });
    
    e_btn_update_email.on( 'click', function(){
        update_self_email();
    });
    */
}

function verifyOTP(){
    var btn_cancel_update_phone = getElementByID( 'btn_cancel_update_phone' );
    var btn_submit              = getElementByID( 'btn_submit_phone_update_otp' );
    var btn_cancel              = getElementByID( 'btn_cancel_phone_update_otp' );
    var modal                   = $( '#modal_otp' );
    var otp_invalid_msg         = getElementByClass( 'otp-invalid' );
    var otp_phone_update        = getElementByID( 'otp_phone_update' );
    var e_div_phone_title_v     = getElementByID( 'phone_title_v' );
    var e_span_country_code_v   = getElementByID( 'country_code_v' );
    var e_span_phone_v          = getElementByID( 'phone_v' );
    
    btn_cancel.on( 'click', function(){
        modal.modal( 'hide' );        
    });
    
    btn_submit.on( 'click', function(){
        
        var otp_val = otp_phone_update.val();
        
        if( otp_val == "" ){
            showSimpleToast( "error", "Please enter a valid OTP" );
            return;
        }
        
        var data = {
            what_do_you_want: 'scodezy_verify_update_self_phone_otp',
            otp: otp_val
        };
        
        otp_invalid_msg.text( '' );
        hide( otp_invalid_msg );
        
        // Show Loading Animation on the Button
        showLoadingOnButton( btn_submit );
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );

                // Hide Loading Animation on the Button
                hideLoadingOnButton( btn_submit );

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    // showSimpleToast( "error", jSon[ 'info' ] );
                    // Show error dialog
                    otp_invalid_msg.text( jSon[ 'info' ] );
                    show( otp_invalid_msg );
                    //showActionSweetAlert( jSon[ 'info' ], "error", false, "Okay", "btn btn-primary", function(){} );
                    //btn_cancel.trigger( 'click' );
                    //btn_cancel_update_phone.trigger( 'click' );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var jsonInfo        = jSon[ 'info' ];
                    var message         = jsonInfo.message;
                    var data            = jsonInfo.data;
                    
                    showSimpleSweetAlert( message, "success", "Okay", "btn btn-primary" );
                    
                    modal.modal( 'hide' );
                    btn_cancel.trigger( 'click' );
                    btn_cancel_update_phone.trigger( 'click' );

                    //profileDetails[ 'phone' ] = data.phone;
                    //profileDetails[ 'country_code' ] = data.country_code;
                    //profileDetails[ 'phone_update_verification_status' ] = data._phone_update_verification_status;
                    
                    if( data.country_code == "" ){
                        e_span_country_code_v.text( "" );
                    }
                    else{
                        e_span_country_code_v.text( '+' + data.country_code + " " );
                    }
                    
                    if( data.phone == "" ){
                        e_span_phone_v.text( "Please add mobile number" );
                    }
                    else{
                        e_span_phone_v.text( data.phone );
                        // Only add a badge if one does not exist already
                        if( e_div_phone_title_v.find( 'span' ).length == 0 )
                            e_div_phone_title_v.append( " " + verified_badge );
                    }


                    //startOtpRequestTimer();
                    
                    //showSimpleToast( "success", info );

                    return;
                }

            }
        });
        
    });
}

function requestOTPAgain(){
    var modal                   = $( '#modal_otp' );
    var e_otp_resend_info       = getElementByClass( 'otp-resend-info' );
    var e_request_otp_again     = getElementByClass( 'otp-request-again' );
    var otp_invalid_msg         = getElementByClass( 'otp-invalid' );
    var btn_cancel              = getElementByID( 'btn_cancel_phone_update_otp' );
    var btn_cancel_update_phone = getElementByID( 'btn_cancel_update_phone' );    
    var a_request_otp_again     = getElementByClass( 'otp-request-again' );
    
    otp_invalid_msg.text( '' );
    hide( otp_invalid_msg );
    
    a_request_otp_again.on( 'click', function( e ){
        e.preventDefault();
        
        var data = {
            what_do_you_want: 'scodezy_update_self_phone_request_otp'
        };

        // Show Loading Animation
        showLoadingSweetAlert( "Regenerating OTP", "Please wait while the OTP is being generated", false );

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            //processData: false,
            //contentType: false,
            success: function( returned_data ){
                //console.log( returned_data );

                // Hide Loading Animation
                hideLoadingSweetAlert();

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    // showSimpleToast( "error", jSon[ 'info' ] );
                    // Show error dialog
                    showActionSweetAlert( jSon[ 'info' ], "error", false, "Okay", "btn btn-primary", function(){} );
                    btn_cancel.trigger( 'click' );
                    btn_cancel_update_phone.trigger( 'click' );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var jsonInfo        = jSon[ 'info' ];
                    var info            = jsonInfo.info;
                    var data            = jsonInfo.data;

                    hide( e_request_otp_again );
                    show( e_otp_resend_info );

                    startOtpRequestTimer();
                    
                    showSimpleToast( "success", info );

                    return;
                }

            }
        });
        
    });
}

function initPhoneUpdateOtpModal(){
    var modal                   = $( '#modal_otp' );
    var e_otp_resend_info       = getElementByClass( 'otp-resend-info' );
    var e_request_otp_again     = getElementByClass( 'otp-request-again' );
    var input_otp               = $( '.input-otp' );
    var btn_cancel              = getElementByID( 'btn_cancel_phone_update_otp' );
    //modal.modal( 'show' );
    
    btn_cancel.on( 'click', function(){
        hide( e_request_otp_again );
        show( e_otp_resend_info );
        
        modal.hide();
    });
    
    input_otp.on( 'keypress', function( e ){
        ////console.log( e.which );
        ////console.log( String.fromCharCode( e.which ) );
        if( !((e.which >= 48) && (e.which <= 57)) ){       // do not allow anything other than 0 to 9
            e.preventDefault();
        }
        
    });
    
    input_otp.on( 'paste', function( e ){
        var pasteData = e.originalEvent.clipboardData.getData('text');
        
        ////console.log( pasteData );
        
        var input_value = pasteData;
        input_value = pasteData.substring( 0, 5 );
        
        // //console.log( input_value );
        
        // Check the value character by character, if any non-numeric character is found, discard the entire set of characters
        for( i = 0 ; i < input_value.length ; i++ ){
            var char = parseInt( input_value.charAt( i ) );
            ////console.log( char );
            if( isNaN( char ) ){       // do not allow anything other than 0 to 9
                e.preventDefault();
                input_otp.val( '' );
                break;
            }
        }
    });
}

function startOtpRequestTimer(){
    var modal                   = $( '#modal_otp' );
    var e_otp_resend_info       = getElementByClass( 'otp-resend-info' );
    var e_request_otp_again     = getElementByClass( 'otp-request-again' );
    
    var totalSecondsCount = 10;
    var currentSecondsCount = totalSecondsCount;
    var otpResendTimer = setInterval(function(){
        var e_seconds = modal.find( '.seconds' );
        e_seconds.text( currentSecondsCount );
        if( currentSecondsCount == 0 ){
            clearInterval( otpResendTimer );
            show( e_request_otp_again );
            hide( e_otp_resend_info );
        }
        currentSecondsCount--;
    }, 1000 );
}

function update_self_phone(){
    var modal                   = $( '#modal_otp' );
    var e_otp_resend_info       = getElementByClass( 'otp-resend-info' );
    var e_request_otp_again     = getElementByClass( 'otp-request-again' );
    var e_country_code          = getElementByID( 'select_country_code_e' );
    var e_phone                 = getElementByID( 'phone_e' );
    var e_password              = getElementByID( 'phone_password_e' );
    var btn_update              = getElementByID( 'btn_update_phone' );
    var btn_cancel              = getElementByID( 'btn_cancel_phone_update_otp' );
    var btn_cancel_update_phone = getElementByID( 'btn_cancel_update_phone' );
    var otp_invalid_msg         = getElementByClass( 'otp_invalid_msg' );
    
    var parsley_phone    = e_phone.parsley();
    var parsley_password = e_password.parsley();
    
    if( !parsley_phone.isValid() ){
        showSimpleToast( "error", "Mobile number is invalid" );
        return;
    }
    
    if( !parsley_password.isValid() ){
        showSimpleToast( "error", "Password is invalid" );
        return;
    }
    
    var val_phone           = e_phone.val();
    var val_countey_code    = e_country_code.val();
    var val_password        = e_password.val();
    
    var data = {
        what_do_you_want: 'scodezy_update_self_phone',
        country_code: val_countey_code,
        phone: val_phone,
        password: val_password
    };
    
    // Show Loading Animation on the Button
    showLoadingOnButton( btn_update );
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        //processData: false,
        //contentType: false,
        success: function( returned_data ){
            //console.log( returned_data );
            
            // Hide Loading Animation on the Button
            hideLoadingOnButton( btn_update );
            
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                //showSimpleToast( "error", jSon[ 'info' ] );
                showSimpleSweetAlert( jSon[ 'info' ], "error", "Okay", "btn btn-danger" );
                
                btn_cancel.trigger( 'click' );
                btn_cancel_update_phone.trigger( 'click' );
                
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var jsonInfo        = jSon[ 'info' ];
                var info            = jsonInfo.info;
                var data            = jsonInfo.data;
                
                //profileDetails[ 'phone' ] = data.phone;
                //profileDetails[ 'country_code' ] = data.country_code;
                //profileDetails[ '_phone_update_verification_status' ] = data._phone_update_verification_status;
                
                hide( e_request_otp_again );
                show( e_otp_resend_info );
                
                // Timer for OTP resend
                startOtpRequestTimer();
                
                modal.modal( 'show' );
                otp_invalid_msg.text( '' );
                hide( otp_invalid_msg );
                                
                return;
            }
            
        }
    });
}

function changePhoneListeners(){
    var btn_change_phone            = getElementByID( 'btn_change_phone' );
    var e_div_change_phone          = getElementByID( 'div_change_phone' );
    var e_div_view_phone            = getElementByID( 'contact_phone_v' );
    var e_div_edit_phone            = getElementByID( 'contact_phone_e' );
    var e_country_code              = getElementByID( 'select_country_code_e' );
    var e_phone                     = getElementByID( 'phone_e' );
    var btn_cancel_update_phone     = getElementByID( 'btn_cancel_update_phone' );
    var btn_update_phone            = getElementByID( 'btn_update_phone' );
    var otp_invalid_msg             = getElementByClass( 'otp_invalid_msg' );
    
    btn_change_phone.on( 'click', function(){
        var scodezyForm     = new ScodezyForm();
        scodezyForm.initForm( '#form_update_phone' );

        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        hide( e_div_change_phone );
        hide( e_div_view_phone );
        
        show( e_div_edit_phone );
        
        //e_email_e.val( profileDetails.email );
        e_country_code.val( '65' );
        e_country_code.trigger( 'change.select2' );
        e_phone.val( '' );
    });
    
    btn_cancel_update_phone.on( 'click', function(){
        var scodezyForm     = new ScodezyForm();
        scodezyForm.initForm( '#form_update_phone' );

        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        show( e_div_change_phone );
        
        show( e_div_view_phone );
        hide( e_div_edit_phone );
        
        //e_email_e.val( profileDetails.email );
        e_country_code.val( '65' );
        e_country_code.trigger( 'change.select2' );
        e_phone.val( '' );
    });
    
    btn_update_phone.on( 'click', function(){
        update_self_phone();
    });
}

function update_self_email(){
    var e_email_title_v = getElementByID( 'email_title_v' );
    
    var parsley_email    = e_email_e.parsley();
    var parsley_password = e_password_e.parsley();
    
    if( !parsley_email.isValid() ){
        showSimpleToast( "error", "Email is invalid" );
        return;
    }
    
    if( !parsley_password.isValid() ){
        showSimpleToast( "error", "Password is invalid" );
        return;
    }
    
    var val_email      = e_email_e.val();
    var val_user_id    = e_user_id_e.val();
    var val_password   = e_password_e.val();
    
    var data = {
        what_do_you_want: 'scodezy_update_self_email',
        user_id: val_user_id,
        email: val_email,
        password: val_password
    };
    
    // Show Loading Animation on the Button
    e_btn_update_email.attr( 'data-kt-indicator', 'on' );
    disableFormElement( e_btn_update_email );
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        //processData: false,
        //contentType: false,
        success: function( returned_data ){
            //console.log( returned_data );
            
            // Hide Loading Animation on the Button
            e_btn_update_email.attr( 'data-kt-indicator', 'off' );
            enableFormElement( e_btn_update_email );
            
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){                    
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                showSimpleToast( "error", jSon[ 'info' ] );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var jsonInfo        = jSon[ 'info' ];
                var info            = jsonInfo.info;
                var data            = jsonInfo.data;
                
                profileDetails[ 'email' ] = data.email;
                //profileDetails[ '_email_update_verification_status' ] = data._email_update_verification_status;
                
                // Empty the password field
                e_password_e.val( '' );
                e_btn_cancel_update_email.trigger( 'click' );
                
                // Show the updated email under contact tab with an UnVerified flag besides it
                e_email_v.text( profileDetails.email );
                if( e_email_title_v.find( 'span' ).length == 0 )
                    e_email_title_v.append( " " + verified_badge );
                
                /*
                hide( _e_div_contact_email_e );
        
                show( _e_div_contact_email_v );
                show( _e_div_change_email_button );
                */
                //console.log( info );
                
                showActionSweetAlert( info, "success", true, "Okay", "btn btn-primary", function(){});
                //showSimpleToast( "success", info );
                
                return;
            }
            
        }
    });
}

function changeEmailListeners(){
    e_btn_change_email.on( 'click', function(){
        var scodezyForm     = new ScodezyForm();
        scodezyForm.initForm( '#form_update_email' );

        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        hide( _e_div_change_email_button );
        hide( _e_div_contact_email_v );
        
        show( _e_div_contact_email_e );
        
        //e_email_e.val( profileDetails.email );
        e_email_e.val( '' );
    });
    
    e_btn_cancel_update_email.on( 'click', function(){
        var scodezyForm     = new ScodezyForm();
        scodezyForm.initForm( '#form_update_email' );

        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        hide( _e_div_contact_email_e );
        
        show( _e_div_contact_email_v );
        show( _e_div_change_email_button );
        
        //e_email_e.val( profileDetails.email );
        e_email_e.val( '' );
    });
    
    e_btn_update_email.on( 'click', function(){
        update_self_email();
    });
}

function update_profile_details(){
    var parsley_fname       = e_fname_e.parsley();
    var parsley_lname       = e_lname_e.parsley();
    var parsley_nickname    = e_nickname_e.parsley();
    
    if( !parsley_fname.isValid() ){
        showSimpleToast( "error", "First name is invalid" );
        return;
    }
    
    if( !parsley_lname.isValid() ){
        showSimpleToast( "error", "Last name is invalid" );
        return;
    }
    
    if( !parsley_nickname.isValid() ){
        showSimpleToast( "error", "Nickname is invalid" );
        return;
    }
    
    
    var val_user_id     = e_user_id_e.val();
    var val_fname       = e_fname_e.val();
    var val_lname       = e_lname_e.val();
    var val_nickname    = e_nickname_e.val();
    
    var data = {
        what_do_you_want: 'scodezy_update_self_profile_details',
        user_id: val_user_id,
        fname: val_fname,
        lname: val_lname,
        nickname: val_nickname
    };
    
    // Show Loading Animation on the Button
    _e_btn_save_profile_details.attr( 'data-kt-indicator', 'on' );
    disableFormElement( _e_btn_save_profile_details );
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        //processData: false,
        //contentType: false,
        success: function( returned_data ){
            //console.log( returned_data );
            
            // Hide Loading Animation on the Button
            _e_btn_save_profile_details.attr( 'data-kt-indicator', 'off' );
            enableFormElement( _e_btn_save_profile_details );
            
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){                    
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                showSimpleToast( "error", jSon[ 'info' ] );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var jsonInfo        = jSon[ 'info' ];
                var info            = jsonInfo.info;
                var data            = jsonInfo.data;
               profileDetails      = data;
                
                e_user_id_v.text( data.user_id );
                e_fname_v.text( data.fname );
                e_lname_v.text( data.lname );
                e_nickname_v.text( data.nickname );
                e_email_v.text( data.email );
                //e_phone_v.text( data.phone );
                
                e_fname_e.val( data.fname );
                e_lname_e.val( data.lname );
                e_nickname_e.val( data.nickname );
                
                hide( _e_profile_details_edit );
                show( _e_profile_details_view );
                
                showSimpleToast( "success", info );
                
                return;
            }
            
        }
    });
}

function initEditProfileListener(){
    _e_btn_edit_profile.on( 'click', function( e ){
        e.preventDefault();
        
        hide( _e_profile_details_view );
        show( _e_profile_details_edit );
        
        
    });
    
    _e_btn_cancel_edit_profile.on( 'click', function(){
        
        e_fname_e.val( profileDetails.fname );
        e_lname_e.val( profileDetails.lname );
        e_nickname_e.val( profileDetails.nickname );
        
        hide( _e_profile_details_edit );
        show( _e_profile_details_view );
        
    });
    
    _e_btn_save_profile_details.on( 'click', function(){
        update_profile_details();
    });
}

function initSettingsTabClick(){
    $( '.setting-tabs' ).on( 'click', function( e ){
        e.preventDefault();
        
        $( '.setting-tabs' ).removeClass( 'active' );
        $( this ).addClass( 'active' );
        
        var tabName = $( this ).data( 'tabname' );
        $.each( $( '.settings' ), function( i, v ){
            hide( $( v ) );        
        });
        show( $( '.settings[data-tabname="'+ tabName +'"]' ) );
        
    });
}

function loadAllSettings(){
    var e_div_phone_title_v        = getElementByID( 'phone_title_v' );
    var e_span_country_code_v      = getElementByID( 'country_code_v' );
    var e_span_phone_v             = getElementByID( 'phone_v' );
    var e_div_email_title_v        = getElementByID( 'email_title_v' );
    var e_email_v                  = getElementByID( 'email_v' );
    /*
    var e_user_id_v         = getElementByID( 'user_id_v' );
    var e_fname_v           = getElementByID( 'fname_v' );
    var e_lname_v           = getElementByID( 'lname_v' );
    var e_nickname_v        = getElementByID( 'nickname_v' );
    var e_phone_v           = getElementByID( 'phone_v' );
    */
   
    //showPageDataLoading();
    showLoadingSweetAlert( "Loading", "Please wait while the profile information is being retrieved", false );
    
    var data = {
        what_do_you_want: 'scodezy_get_self_profile_settings'
    };
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        //processData: false,
        //contentType: false,
        success: function( returned_data ){
            //console.log( returned_data );
            
            hideLoadingSweetAlert();
            
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){                    
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                //showSimpleToast( "error", jSon[ 'info' ] );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var jsonInfo        = jSon[ 'info' ];
                var info            = jsonInfo.info;
                var data            = jsonInfo.data;
                profileDetails      = data;
                
                e_user_id_v.text( data.user_id );
                e_fname_v.text( data.fname );
                e_lname_v.text( data.lname );
                e_nickname_v.text( data.nickname );
                
                if( e_div_email_title_v.find( 'span' ).length == 0 )
                    e_div_email_title_v.append( " " + verified_badge );
                e_email_v.text( data.email );
                
                if( data.country_code == "" ){
                    e_span_country_code_v.text( "" );
                }
                else{
                    e_span_country_code_v.text( '+' + data.country_code + " " );
                }

                if( data.phone == "" ){
                    e_span_phone_v.text( "Please add mobile number" );
                }
                else{
                    e_span_phone_v.text( data.phone );
                    if( e_div_phone_title_v.find( 'span' ).length == 0 )
                        e_div_phone_title_v.append( " " + verified_badge );
                }
                
                
                e_user_id_e.val( data.user_id );
                e_fname_e.val( data.fname );
                e_lname_e.val( data.lname );
                e_nickname_e.val( data.nickname );
                
                // Activate the Overview Tab
                show( _e_settings_tab_navbar );
                $( '.setting-tabs[data-tabname="overview"]' ).addClass( 'active' );
                show( $( '.settings[data-tabname="overview"]' ) );
                
                //showSimpleToast( "success", data );
                
                return;
            }
            
        }
    });
}

function hideAllSettingsBlock(){
    
    hide( _e_settings_tab_navbar );
    hide( _e_profile_details_edit );
    
    $.each( $( '.setting-tabs' ), function( i, v ){
        $( v ).removeClass( 'active' );        
    });
    
    $.each( $( '.settings' ), function( i, v ){
        hide( $( v ) );        
    });
}

function initDOMElements(){
    e_user_id_v         = getElementByID( 'user_id_v' );
    e_fname_v           = getElementByID( 'fname_v' );
    e_lname_v           = getElementByID( 'lname_v' );
    e_nickname_v        = getElementByID( 'nickname_v' );
    
    e_user_id_e         = getElementByID( 'user_id_e' );
    e_fname_e           = getElementByID( 'fname_e' );
    e_lname_e           = getElementByID( 'lname_e' );
    e_nickname_e        = getElementByID( 'nickname_e' );
    
    e_email_v = getElementByID( 'email_v' );
    e_email_v = getElementByID( 'email_v' );
    e_email_badge_v = getElementByID( 'email_badge_v' );
    _e_div_contact_email_e = getElementByID( 'contact_email_e' );
    _e_div_contact_email_v = getElementByID( 'contact_email_v' );
    e_email_e = getElementByID( 'email_e' );
    e_password_e = getElementByID( 'password_e' );
    e_btn_update_email = getElementByID( 'btn_update_email' );
    e_btn_cancel_update_email = getElementByID( 'btn_cancel_update_email' );
    _e_div_change_email_button = getElementByID( 'div_change_email_button' );
    e_btn_change_email = getElementByID( 'btn_change_email' );
}





function showPageDataLoading(){
    var e_loading = $( '.main-post' );
    e_loading.prepend( loadingEl );
    loadingEl.classList.add( "page-loader" );
    loadingEl.classList.add( "section-loader" );
    loadingEl.classList.add( "flex-column" );
    loadingEl.innerHTML = `
        <span class="spinner-border text-primary" role="status"></span>
        <span class="text-gray-800 fs-6 fw-semibold mt-5">Loading...</span>
    `;

    // Show page loading
    //KTApp.showPageLoading();
    

}

function hidePageDataLoading(){
    KTApp.hidePageLoading();
    loadingEl.remove();
}