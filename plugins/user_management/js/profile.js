"use strict";

/* =============================================
   profile.js - My Profile (redesigned 1.0.61)
   Tabs: Profile (names, name next to the picture), Picture (initials / cartoon avatars / own upload),
   Contact (email change confirmed by link or code - chosen on Website Configuration; mobile number with the
   emailed one-time code), Security (password with strength meter, show / hide, sign out other devices).
   Everything works on the signed-in user (the server never takes a user_id from here).
   ============================================= */

var PRF = {

    data : null,                                               // Last answer of scodezy_get_my_profile
    otpTimer : null,                                           // Countdown before the mobile code can be sent again
    tabKey : 'prf_active_tab',                                 // localStorage key of the last open tab

    /**
     * @description Binds the page events and loads the profile
     * @returns {void}
     */
    init : function () {
        $( '#prf_save_details' ).on( 'click', PRF.saveDetails );
        $( '#prf_display_field' ).on( 'change', PRF.saveDisplay );
        $( '#prf_use_initials' ).on( 'click', function () { PRF.setAvatar( { type: 'initials' } ); } );
        $( '#prf_upload_btn' ).on( 'click', function () { $( '#prf_file' ).val( '' ).trigger( 'click' ); } );
        $( '#prf_file' ).on( 'change', PRF.uploadAvatar );
        $( 'input[name="prf_gender"]' ).on( 'change', PRF.drawAvatars );
        $( '#prf_avatar_grid' ).on( 'click', '[data-prf-avatar]', function () { PRF.setAvatar( { type: 'theme', file: $( this ).attr( 'data-prf-avatar' ) } ); } );

        $( '#prf_change_email' ).on( 'click', PRF.changeEmail );
        $( '#prf_email_pending' ).on( 'click', '[data-prf-email]', function () { PRF.emailAction( $( this ).attr( 'data-prf-email' ), $( this ) ); } );
        $( '#prf_change_phone' ).on( 'click', PRF.changePhone );
        $( '#prf_otp_submit' ).on( 'click', PRF.confirmPhone );
        $( '#prf_otp_resend' ).on( 'click', function ( e ) { e.preventDefault(); PRF.resendPhoneCode(); } );

        $( '[data-prf-eye]' ).on( 'click', function () {
            var $input = $( '#' + $( this ).attr( 'data-prf-eye' ) );   // Password field of this eye button
            var show = $input.attr( 'type' ) === 'password';
            $input.attr( 'type', show ? 'text' : 'password' );
            $( this ).find( 'i' ).toggleClass( 'fa-eye', !show ).toggleClass( 'fa-eye-slash', show );
        } );
        $( '#prf_pw_new' ).on( 'input', PRF.showStrength );
        $( '#prf_pw_new, #prf_pw_confirm' ).on( 'input', PRF.showMismatch );
        $( '#prf_change_password' ).on( 'click', PRF.changePassword );

        $( '#prf_tabs a[data-bs-toggle="tab"]' ).on( 'shown.bs.tab', function () {
            try { localStorage.setItem( PRF.tabKey, $( this ).attr( 'href' ) ); } catch ( e ) { /* Storage blocked: nothing to remember */ }
        } );
        PRF.load( true );
    },

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * @description Escapes text for HTML
     * @param {*} value - Any value
     * @returns {string}
     */
    esc : function ( value ) {
        return $( '<div>' ).text( value === null || value === undefined ? '' : String( value ) ).html();
    },

    /**
     * @description Posts to the webservice (promise): resolves with info of a success answer, rejects with a message
     * @param {object|FormData} data - Request data
     * @returns {Promise<object>}
     */
    post : function ( data ) {
        var isForm = ( typeof FormData !== 'undefined' && data instanceof FormData );   // File upload
        return new Promise( function ( resolve, reject ) {
            $.ajax( {
                url : getWebservice(), type : 'POST', data : data,
                processData : !isForm, contentType : isForm ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                success : function ( returned_data ) {
                    var jSon = parseJSONWithError( returned_data, 'Server Side error occurred ! Contact Technical Support...' );
                    if ( jSon === false ) { reject( 'Server error. Please try again.' ); return; }
                    jSon = jSon[ 0 ];
                    var info = jSon[ 'info' ];
                    if ( jSon[ 'type' ] === 'success' ) { resolve( ( info && typeof info === 'object' ) ? info : { message: info } ); return; }
                    reject( ( info && typeof info === 'object' ) ? ( info[ 'message' ] || info[ 'info' ] || 'Not done.' ) : String( info ) );
                },
                error : function () { reject( 'Could not reach the server.' ); }
            } );
        } );
    },

    /**
     * @description Shows an error dialog
     * @param {string} message - Text
     * @returns {void}
     */
    fail : function ( message ) {
        Swal.fire( { icon: 'error', title: 'Not done', text: message, buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
    },

    /**
     * @description Runs a request with a button showing its progress; reloads the profile on success
     * @param {object|FormData} data - Request
     * @param {jQuery} $btn - Button
     * @param {function} [after] - Called with the answer before the reload
     * @returns {void}
     */
    run : function ( data, $btn, after ) {
        if ( $btn ) { showLoadingOnButton( $btn ); }
        PRF.post( data ).then( function ( info ) {
            if ( $btn ) { hideLoadingOnButton( $btn ); }
            if ( info.message ) { showSimpleToast( 'success', info.message ); }
            if ( after ) { after( info ); }
            PRF.load( false );
        } ).catch( function ( message ) { if ( $btn ) { hideLoadingOnButton( $btn ); } PRF.fail( message ); } );
    },

    /**
     * @description HTML inside a round avatar: the picture, or the initials
     * @param {object} avatar - { type, url }
     * @param {string} initials - Fallback letters
     * @returns {string}
     */
    avatarHtml : function ( avatar, initials ) {
        if ( avatar && avatar.url ) { return '<img src="' + PRF.esc( avatar.url ) + '" alt="" />'; }
        return PRF.esc( initials );
    },

    // ── Load / draw ─────────────────────────────────────────────

    /**
     * @description Loads the profile and fills every tab
     * @param {boolean} first - true on page load (opens the last used tab)
     * @returns {void}
     */
    load : function ( first ) {
        PRF.post( { what_do_you_want: 'scodezy_get_my_profile' } ).then( function ( info ) {
            var d = info.data;
            PRF.data = d;

            // Header + top-right corner of the page
            $( '#prf_head_avatar, #prf_picture_current' ).html( PRF.avatarHtml( d.avatar, d.initials ) );
            $( '#prf_head_name' ).text( d.display.text );
            $( '#prf_head_user_id' ).text( d.user_id );
            $( '#prf_head_email' ).text( d.email );
            $( '.site-user-avatar' ).html( PRF.avatarHtml( d.avatar, d.initials ) );
            $( '.site-user-name' ).text( d.display.text );

            // Profile
            $( '#prf_user_id' ).val( d.user_id );
            $( '#prf_fname' ).val( d.fname );
            $( '#prf_lname' ).val( d.lname );
            $( '#prf_nickname' ).val( d.nickname );
            var options = '<option value="">Website default (' + PRF.esc( d.display.options[ d.display[ 'default' ] ] || '' ) + ')</option>';
            $.each( d.display.options, function ( key, label ) { options += '<option value="' + PRF.esc( key ) + '">' + PRF.esc( label ) + '</option>'; } );
            $( '#prf_display_field' ).html( options ).val( d.display.own ).prop( 'disabled', !d.display.user_choice );
            $( '#prf_display_hint' ).text( d.display.user_choice ? 'Choose what appears beside your picture at the top right.' : 'Set by the website administrator.' );
            $( '#prf_display_preview' ).text( d.display.text );

            // Picture
            $( '#prf_upload_btn, #prf_upload_hint' ).toggleClass( 'd-none', !d.avatar_upload_allowed );
            PRF.drawAvatars();

            // Contact
            $( '#prf_email_current' ).text( d.email || 'Not set' );
            PRF.drawEmailPending();
            $( '#prf_phone_current' ).text( d.phone ? ( '+' + d.country_code + ' ' + d.phone ) : 'Not set' );
            if ( !$( '#prf_country_code' ).val() ) { $( '#prf_country_code' ).val( d.country_code || '65' ); }

            $( '#prf_loading' ).addClass( 'd-none' );
            $( '#prf_page' ).removeClass( 'd-none' );
            if ( first ) {
                var tab = null;                                // Last used tab
                try { tab = localStorage.getItem( PRF.tabKey ); } catch ( e ) { tab = null; }
                if ( tab && $( '#prf_tabs a[href="' + tab + '"]' ).length ) {
                    bootstrap.Tab.getOrCreateInstance( $( '#prf_tabs a[href="' + tab + '"]' )[ 0 ] ).show();
                }
            }
        } ).catch( function ( message ) { $( '#prf_loading' ).text( message ); } );
    },

    /**
     * @description Draws the cartoon avatars for the chosen gender filter
     * @returns {void}
     */
    drawAvatars : function () {
        if ( !PRF.data ) { return; }
        var gender = $( 'input[name="prf_gender"]:checked' ).val();   // all, male or female
        var current = ( PRF.data.avatar.type === 'theme' ) ? PRF.data.avatar.value : '';
        $( '#prf_avatar_grid' ).html( $.map( PRF.data.theme_avatars, function ( a ) {
            if ( gender !== 'all' && a.gender !== gender ) { return ''; }
            return '<button type="button" class="prf-avatar-option' + ( a.file === current ? ' selected' : '' ) + '" data-prf-avatar="' + PRF.esc( a.file ) + '" title="Use this avatar">'
                 + '<img src="assets/media/svg/avatars/' + PRF.esc( a.file ) + '" alt="" loading="lazy" /></button>';
        } ).join( '' ) );
    },

    /**
     * @description Shows the pending email change (link: wait for the link; code: type the code) with Resend / Cancel
     * @returns {void}
     */
    drawEmailPending : function () {
        var p = PRF.data.email_pending;                        // { email, method } or null
        $( '#prf_email_form' ).toggleClass( 'd-none', !!p );
        if ( !p ) { $( '#prf_email_pending' ).empty(); return; }
        var body = ( p.method === 'code' )
            ? '<div class="mb-3">Type the 6-digit code sent to <b>' + PRF.esc( p.email ) + '</b>.</div>'
              + '<div class="d-flex flex-wrap gap-2 align-items-center mb-3"><input type="text" class="form-control form-control-solid text-center fw-bold fs-4 mw-200px" id="prf_email_code" maxlength="6" inputmode="numeric" autocomplete="one-time-code" />'
              + '<button type="button" class="btn btn-primary" data-prf-email="confirm"><span class="indicator-label">Confirm</span><span class="indicator-progress">Please wait… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span></button></div>'
            : '<div class="mb-3">Open the link sent to <b>' + PRF.esc( p.email ) + '</b> to confirm. Your email changes after that.</div>';
        $( '#prf_email_pending' ).html( '<div class="notice bg-light-primary rounded border-primary border border-dashed p-5 mb-4">'
            + '<div class="fw-bold mb-2"><i class="fa-solid fa-hourglass-half text-primary me-2"></i>Change waiting for confirmation</div>' + body
            + '<div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-light-primary" data-prf-email="resend">' + ( p.method === 'code' ? 'Send the code again' : 'Send the link again' ) + '</button>'
            + '<button type="button" class="btn btn-sm btn-light-danger" data-prf-email="cancel">Cancel the change</button></div></div>' );
    },

    // ── Profile ─────────────────────────────────────────────────

    /**
     * @description Saves first name, last name and nickname
     * @returns {void}
     */
    saveDetails : function () {
        var data = { what_do_you_want: 'scodezy_update_self_profile_details', fname: $.trim( $( '#prf_fname' ).val() ), lname: $.trim( $( '#prf_lname' ).val() ), nickname: $.trim( $( '#prf_nickname' ).val() ) };
        if ( data.fname === '' || data.lname === '' ) { showSimpleToast( 'error', 'First and last name are required.' ); return; }
        PRF.run( data, $( '#prf_save_details' ) );
    },

    /**
     * @description Saves what appears next to the picture
     * @returns {void}
     */
    saveDisplay : function () {
        PRF.run( { what_do_you_want: 'scodezy_set_self_display_name', field: $( '#prf_display_field' ).val() }, null );
    },

    // ── Picture ─────────────────────────────────────────────────

    /**
     * @description Saves the avatar choice (initials or a cartoon)
     * @param {object} choice - { type, file }
     * @returns {void}
     */
    setAvatar : function ( choice ) {
        PRF.run( $.extend( { what_do_you_want: 'scodezy_set_self_avatar' }, choice ), null );
    },

    /**
     * @description Uploads the picked picture
     * @returns {void}
     */
    uploadAvatar : function () {
        var file = this.files && this.files[ 0 ];                 // Picked file
        if ( !file ) { return; }
        if ( file.size > 2 * 1024 * 1024 ) { PRF.fail( 'The picture is larger than 2 MB.' ); return; }
        var form = new FormData();
        form.append( 'what_do_you_want', 'scodezy_set_self_avatar' );
        form.append( 'type', 'upload' );
        form.append( 'image', file );
        PRF.run( form, null );
    },

    // ── Contact ─────────────────────────────────────────────────

    /**
     * @description Requests an email change (password checked on the server)
     * @returns {void}
     */
    changeEmail : function () {
        var email = $.trim( $( '#prf_new_email' ).val() );     // New address
        var password = $( '#prf_email_password' ).val();       // Current password
        if ( email === '' || password === '' ) { showSimpleToast( 'error', 'Enter the new email and your password.' ); return; }
        PRF.run( { what_do_you_want: 'scodezy_update_self_email', email: email, password: password }, $( '#prf_change_email' ), function () {
            $( '#prf_new_email, #prf_email_password' ).val( '' );
        } );
    },

    /**
     * @description Resend / cancel / confirm (code) of the pending email change
     * @param {string} action - resend, cancel or confirm
     * @param {jQuery} $btn - The button
     * @returns {void}
     */
    emailAction : function ( action, $btn ) {
        if ( action === 'resend' ) { PRF.run( { what_do_you_want: 'scodezy_resend_self_email_verification' }, null ); }
        if ( action === 'cancel' ) { PRF.run( { what_do_you_want: 'scodezy_cancel_self_email_update' }, null ); }
        if ( action === 'confirm' ) {
            var code = $.trim( $( '#prf_email_code' ).val() );  // 6 digits from the email
            if ( !/^\d{6}$/.test( code ) ) { showSimpleToast( 'error', 'Type the 6-digit code from the email.' ); return; }
            PRF.run( { what_do_you_want: 'scodezy_confirm_self_email_code', code: code }, $btn );
        }
    },

    /**
     * @description Requests a mobile number change; the one-time code is emailed
     * @returns {void}
     */
    changePhone : function () {
        var data = { what_do_you_want: 'scodezy_update_self_phone', country_code: $.trim( $( '#prf_country_code' ).val() ), phone: $.trim( $( '#prf_phone' ).val() ), password: $( '#prf_phone_password' ).val() };
        if ( !/^\d{1,4}$/.test( data.country_code ) || !/^\d{5,15}$/.test( data.phone ) ) { showSimpleToast( 'error', 'Enter the country code and the mobile number (digits only).' ); return; }
        if ( data.password === '' ) { showSimpleToast( 'error', 'Enter your password.' ); return; }
        var $btn = $( '#prf_change_phone' );
        showLoadingOnButton( $btn );
        PRF.post( data ).then( function ( info ) {
            hideLoadingOnButton( $btn );
            $( '#prf_phone_password' ).val( '' );
            $( '#prf_otp_text' ).text( info.message || info.info || 'A one-time code was emailed to you.' );
            $( '#prf_otp' ).val( '' );
            PRF.startOtpWait();
            bootstrap.Modal.getOrCreateInstance( document.getElementById( 'prf_otp_modal' ) ).show();
        } ).catch( function ( message ) { hideLoadingOnButton( $btn ); PRF.fail( message ); } );
    },

    /**
     * @description Confirms the new mobile number with the emailed code
     * @returns {void}
     */
    confirmPhone : function () {
        var otp = $.trim( $( '#prf_otp' ).val() );             // Code from the email
        if ( !/^\d{4,6}$/.test( otp ) ) { showSimpleToast( 'error', 'Type the code from the email.' ); return; }
        PRF.post( { what_do_you_want: 'scodezy_verify_update_self_phone_otp', otp: otp } ).then( function ( info ) {
            bootstrap.Modal.getOrCreateInstance( document.getElementById( 'prf_otp_modal' ) ).hide();
            $( '#prf_phone' ).val( '' );
            showSimpleToast( 'success', info.message || 'Mobile number updated.' );
            PRF.load( false );
        } ).catch( PRF.fail );
    },

    /**
     * @description Sends the mobile code again (after the wait)
     * @returns {void}
     */
    resendPhoneCode : function () {
        if ( PRF.otpTimer ) { return; }
        PRF.post( { what_do_you_want: 'scodezy_update_self_phone_request_otp' } ).then( function ( info ) {
            showSimpleToast( 'success', info.message || 'A new code was sent.' );
            PRF.startOtpWait();
        } ).catch( PRF.fail );
    },

    /**
     * @description 60-second wait before "Send the code again" works
     * @returns {void}
     */
    startOtpWait : function () {
        var left = 60;                                         // Seconds to wait
        clearInterval( PRF.otpTimer );
        $( '#prf_otp_resend' ).addClass( 'text-muted pe-none' );
        $( '#prf_otp_wait' ).text( '(in ' + left + ' s)' );
        PRF.otpTimer = setInterval( function () {
            left--;
            $( '#prf_otp_wait' ).text( left > 0 ? '(in ' + left + ' s)' : '' );
            if ( left <= 0 ) {
                clearInterval( PRF.otpTimer );
                PRF.otpTimer = null;
                $( '#prf_otp_resend' ).removeClass( 'text-muted pe-none' );
            }
        }, 1000 );
    },

    // ── Security ────────────────────────────────────────────────

    /**
     * @description Strength of a password: 0 (empty) to 4 (strong)
     * @param {string} pw - Password
     * @returns {number}
     */
    strength : function ( pw ) {
        if ( !pw ) { return 0; }
        var score = 0;                                         // One point per quality
        if ( pw.length >= 8 ) { score++; }
        if ( pw.length >= 12 ) { score++; }
        if ( /[a-z]/.test( pw ) && /[A-Z]/.test( pw ) ) { score++; }
        if ( /\d/.test( pw ) ) { score++; }
        if ( /[_@#]/.test( pw ) ) { score++; }
        return Math.max( 1, Math.min( 4, score ) );
    },

    /**
     * @description Updates the strength bar under the new password
     * @returns {void}
     */
    showStrength : function () {
        var levels = [ [ '0%', '', '&nbsp;' ], [ '25%', '#f1416c', 'Weak' ], [ '50%', '#f6a609', 'Fair' ], [ '75%', '#1b84ff', 'Good' ], [ '100%', '#17c653', 'Strong' ] ];   // width, colour, label
        var l = levels[ PRF.strength( $( '#prf_pw_new' ).val() ) ];
        $( '#prf_strength_bar' ).css( { width: l[ 0 ], backgroundColor: l[ 1 ] } );
        $( '#prf_strength_text' ).html( l[ 2 ] ).css( 'color', l[ 1 ] || '' );
    },

    /**
     * @description Shows "do not match" while the confirmation differs
     * @returns {void}
     */
    showMismatch : function () {
        var c = $( '#prf_pw_confirm' ).val();                  // Confirmation typed so far
        $( '#prf_pw_mismatch' ).toggleClass( 'd-none', c === '' || c === $( '#prf_pw_new' ).val() );
    },

    /**
     * @description Changes the password (optionally signing out the other devices)
     * @returns {void}
     */
    changePassword : function () {
        var data = {
            what_do_you_want : 'scodezy_update_self_password',
            current_password : $( '#prf_pw_current' ).val(),
            new_password : $( '#prf_pw_new' ).val(),
            confirm_password : $( '#prf_pw_confirm' ).val(),
            sign_out_others : $( '#prf_sign_out_others' ).is( ':checked' ) ? '1' : '0'
        };
        if ( data.current_password === '' || data.new_password === '' ) { showSimpleToast( 'error', 'Enter your current and new password.' ); return; }
        if ( !/^[a-zA-Z0-9_@#]{3,20}$/.test( data.new_password ) ) { showSimpleToast( 'error', 'New password: 3 to 20 characters, letters, digits and _ @ # only.' ); return; }
        if ( data.new_password !== data.confirm_password ) { showSimpleToast( 'error', 'The new passwords do not match.' ); return; }
        PRF.run( data, $( '#prf_change_password' ), function ( info ) {
            if ( info.refresh_token && info.refresh_token_name ) { storeDataIntoLocalStorage( info.refresh_token_name, info.refresh_token ); }   // This browser stays signed in
            $( '#prf_pw_current, #prf_pw_new, #prf_pw_confirm' ).val( '' );
            PRF.showStrength();
            PRF.showMismatch();
        } );
    }
};

$( document ).ready( function () {
    PRF.init();
} );
