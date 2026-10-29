"use strict";

/* =============================================
   website_configuration.js (Scodezy base feature, gen3 1.0.56; tabs 1.0.58)
   Administrator > Website Configuration, in tabs: General (identity, support contact), Branding (logos, favicon),
   Sign-in pages (background, per-page titles / headings / logos), Admin panel (tab titles, dashboard heading,
   footer links, footer CSS), Sign-in options, Maintenance (maintenance mode, logs, PHP errors) and Social sign-in
   (future scope). Each tab saves on its own; images are saved as soon as they are uploaded.
   ============================================= */

var WC = {

    data : null,                                               // Last answer of scodezy_get_website_configuration
    uploadKey : '',                                            // Image setting the file picker is open for
    tabKey : 'wc_active_tab',                                  // localStorage key of the last open tab

    /**
     * @description Binds the page events and loads the settings
     * @returns {void}
     */
    init : function () {
        $( '[data-wc-save]' ).on( 'click', function () { WC.save( $( this ).attr( 'data-wc-save' ), $( this ) ); } );
        $( '#wc_copyright_year, #wc_copyright_owner, #wc_copyright_url' ).on( 'input', WC.previewCopyright );
        $( '#wc_tab_title_format, #wc_dashboard_heading, #wc_site_name' ).on( 'input', WC.previewTabTitle );
        $( '#wc_maintenance_mode' ).on( 'change', WC.showMaintenanceState );

        // Images (Branding, page logos, background image)
        $( '#wc_sections' ).on( 'click', '[data-wc-upload]', function () {
            WC.uploadKey = $( this ).attr( 'data-wc-upload' );
            $( '#wc_file' ).val( '' ).trigger( 'click' );
        } );
        $( '#wc_sections' ).on( 'click', '[data-wc-path]', function () { WC.savePath( $( this ).attr( 'data-wc-path' ), $( this ) ); } );
        $( '#wc_sections' ).on( 'click', '[data-wc-remove]', function () { WC.removeImage( $( this ).attr( 'data-wc-remove' ), $( this ) ); } );
        $( '#wc_file' ).on( 'change', WC.upload );

        // Footer links
        $( '#wc_footer_link_add' ).on( 'click', function () { WC.addFooterLink( '', '' ); } );
        $( '#wc_footer_links' ).on( 'click', '[data-wc-link-remove]', function () {
            $( this ).closest( '.wc-link-row' ).remove();
            WC.toggleAddLink();
        } );

        // Social: a removed secret cannot be typed at the same time
        $( '#wc_social' ).on( 'change', '[data-wc-clear]', function () {
            $( '#wc_' + $( this ).attr( 'data-wc-clear' ) + '_client_secret' ).prop( 'disabled', $( this ).is( ':checked' ) );
        } );

        // Remember the open tab
        $( '#wc_tabs a[data-bs-toggle="tab"]' ).on( 'shown.bs.tab', function () {
            try { localStorage.setItem( WC.tabKey, $( this ).attr( 'href' ) ); } catch ( e ) { /* Storage blocked: nothing to remember */ }
        } );
        WC.load( true );
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
        var isForm = ( typeof FormData !== 'undefined' && data instanceof FormData );   // File uploads
        return new Promise( function ( resolve, reject ) {
            $.ajax( {
                url : getWebservice(), type : 'POST', data : data,
                processData : !isForm, contentType : isForm ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
                success : function ( returned_data ) {
                    var jSon = parseJSONWithError( returned_data, 'Server Side error occurred ! Contact Technical Support...' );
                    if ( jSon === false ) { reject( 'Server error. Please try again.' ); return; }
                    jSon = jSon[ 0 ];
                    var info = jSon[ 'info' ];
                    if ( jSon[ 'type' ] === 'success' ) { resolve( info ); return; }
                    reject( ( info && typeof info === 'object' && info[ 'message' ] ) ? info[ 'message' ] : String( info ) );
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
        Swal.fire( { icon: 'error', title: 'Not saved', text: message, buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
    },

    /**
     * @description '1' / '0' of a switch
     * @param {string} selector - The checkbox
     * @returns {string}
     */
    flag : function ( selector ) {
        return $( selector ).is( ':checked' ) ? '1' : '0';
    },

    // ── Load / draw ─────────────────────────────────────────────

    /**
     * @description Loads the settings and fills every tab
     * @param {boolean} first - true on page load (opens the last used tab)
     * @returns {void}
     */
    load : function ( first ) {
        WC.post( { what_do_you_want: 'scodezy_get_website_configuration' } ).then( function ( info ) {
            var d = info.data;
            WC.data = d;

            // General
            $( '#wc_site_name' ).val( d.identity.site_name );
            $( '#wc_tagline' ).val( d.identity.tagline );
            $( '#wc_copyright_year' ).val( d.identity.copyright_year );
            $( '#wc_copyright_owner' ).val( d.identity.copyright_owner );
            $( '#wc_copyright_url' ).val( d.identity.copyright_url );
            $( '#wc_domain_name' ).val( d.identity.domain_name );
            $( '#wc_protocol' ).val( d.identity.protocol === 'http' ? 'http' : 'https' );
            $( '#wc_support_email' ).val( d.support.email );
            $( '#wc_support_phone' ).val( d.support.phone );
            WC.previewCopyright();

            // Branding
            $( '#wc_images' ).html( $.map( d.images, function ( img ) { return WC.imageRow( img.key, img.label, img.value, false ); } ).join( '' ) );

            // Sign-in pages
            $( '#wc_bg_gradient, #wc_bg_gradient_label' ).toggleClass( 'd-none', !d.background.gradient_available );   // Only websites with the gradient files (1.0.62)
            $( 'input[name="wc_bg_type"][value="' + d.background.type + '"]' ).prop( 'checked', true );
            $( '#wc_bg_color_value' ).val( d.background.color );
            $( '#wc_bg_image_box' ).html( WC.imageRow( 'signin_background_image', 'Background image', d.background.image, true, true ) );
            WC.drawSigninPages();

            // Admin panel
            $( '#wc_tab_title_format' ).val( d.admin.tab_title_format );
            $( '#wc_dashboard_heading' ).val( d.admin.dashboard_heading );
            $( '#wc_footer_css' ).val( d.admin.footer_css );
            $( '#wc_footer_links' ).empty();
            $.each( d.admin.footer_links, function ( i, l ) { WC.addFooterLink( l.label, l.url ); } );
            WC.toggleAddLink();
            WC.previewTabTitle();

            // Profiles (1.0.61)
            $( '#wc_display_default' ).html( $.map( d.profiles.display_options, function ( label, key ) {
                return '<option value="' + WC.esc( key ) + '">' + WC.esc( label ) + '</option>';
            } ).join( '' ) ).val( d.profiles.display_default );
            $( '#wc_display_user_choice' ).prop( 'checked', d.profiles.display_user_choice );
            $( '#wc_avatar_upload' ).prop( 'checked', d.profiles.avatar_upload );
            $( 'input[name="wc_email_method"][value="' + d.profiles.email_method + '"]' ).prop( 'checked', true );
            $( '#wc_email_code_minutes' ).val( d.profiles.email_code_minutes );

            // Sign-in options
            $( '#wc_allow_forgot_password' ).prop( 'checked', d.signin.allow_forgot_password );
            $( '#wc_is_registration_open' ).prop( 'checked', d.signin.is_registration_open );
            $( '#wc_allow_email_credentials' ).prop( 'checked', d.signin.allow_email_credentials );
            $( '#wc_activation_required' ).prop( 'checked', d.signin.activation_required );
            $( '#wc_activation_minutes' ).val( d.signin.activation_minutes );

            // Maintenance
            $( '#wc_maintenance_mode' ).prop( 'checked', d.maintenance_mode.on );
            $( '#wc_maintenance_message' ).val( d.maintenance_mode.message ).attr( 'placeholder', d.maintenance_mode[ 'default' ] );
            WC.showMaintenanceState();
            $( '#wc_retention' ).html( $.map( d.maintenance.retention, function ( r ) {
                return '<div class="row align-items-center mb-3"><label class="col-7 col-form-label fw-semibold" for="wc_r_' + WC.esc( r.key ) + '">'
                     + WC.esc( r.label ) + ' kept for (days)</label><div class="col-5"><input type="number" class="form-control form-control-solid" min="1" max="3650"'
                     + ' id="wc_r_' + WC.esc( r.key ) + '" data-wc-retention="' + WC.esc( r.key ) + '" value="' + WC.esc( r.value ) + '" /></div></div>';
            } ).join( '' ) );
            $( '#wc_display_php_errors' ).prop( 'checked', d.maintenance.display_php_errors );

            WC.drawSocial();
            $( '#wc_loading' ).addClass( 'd-none' );
            $( '#wc_sections' ).removeClass( 'd-none' );

            if ( first ) {
                var tab = null;                                // Last used tab
                try { tab = localStorage.getItem( WC.tabKey ); } catch ( e ) { tab = null; }
                if ( tab && $( '#wc_tabs a[href="' + tab + '"]' ).length ) {
                    bootstrap.Tab.getOrCreateInstance( $( '#wc_tabs a[href="' + tab + '"]' )[ 0 ] ).show();
                }
            }
        } ).catch( function ( message ) {
            $( '#wc_loading' ).text( message );
        } );
    },

    /**
     * @description HTML of an image setting: preview, path / address, Save, Upload (and Remove for optional ones)
     * @param {string} key - configurations key
     * @param {string} label - Name shown
     * @param {string} value - Current path / address
     * @param {boolean} optional - Can be removed (the page then falls back)
     * @param {boolean} compact - Without the path field (background image)
     * @returns {string}
     */
    imageRow : function ( key, label, value, optional, compact ) {
        var dark = ( key === 'admin_page_logo' || key === 'login_page_logo' || key.indexOf( 'signin_' ) === 0 );   // Shown on a dark background
        var k = WC.esc( key );
        var preview = '<div class="wc-image-preview' + ( dark ? ' wc-dark' : '' ) + '">'
                    + ( value ? '<img src="' + WC.esc( value ) + '?v=' + Date.now() + '" alt="" />' : '<span class="text-muted fs-8">' + ( optional ? 'Not set' : 'None' ) + '</span>' ) + '</div>';
        var buttons = '<button type="button" class="btn btn-sm btn-primary" data-wc-upload="' + k + '"><i class="fa-solid fa-upload me-1"></i>Upload</button>'
                    + ( optional && value ? '<button type="button" class="btn btn-sm btn-light-danger" data-wc-remove="' + k + '" title="Remove"><i class="fa-solid fa-trash"></i></button>' : '' );
        if ( compact ) {
            return '<div class="wc-image wc-image-compact">' + preview + '<div class="d-flex gap-2">' + buttons + '</div></div>';
        }
        return '<div class="wc-image">' + preview
             + '<div class="wc-image-body">'
             + '<div class="fw-semibold mb-2">' + WC.esc( label ) + '</div>'
             + '<div class="input-group input-group-sm">'
             + '<input type="text" class="form-control form-control-solid" id="wc_img_' + k + '" value="' + WC.esc( value ) + '"'
             + ' placeholder="' + ( key === '_email_template_logo' ? 'https://…' : ( optional ? 'Empty = the common sign-in logo' : 'images/logo.png or https://…' ) ) + '" />'
             + '<button type="button" class="btn btn-light-primary" data-wc-path="' + k + '" title="Save this path">Save</button>'
             + '</div><div class="d-flex gap-2 mt-2">' + buttons + '</div></div></div>';
    },

    /**
     * @description Draws the four sign-in page cards
     * @returns {void}
     */
    drawSigninPages : function () {
        $( '#wc_signin_pages' ).html( $.map( WC.data.signin_pages, function ( p ) {
            var id = WC.esc( p.id );
            return '<div class="col-12 col-xl-6"><div class="wc-page-card">'
                 + '<div class="fw-bold fs-5 mb-4">' + WC.esc( p.label ) + '</div>'
                 + '<div class="row g-3">'
                 + '<div class="col-12 col-md-6"><label class="form-label fs-7 required" for="wc_sp_' + id + '_title">Browser tab title</label>'
                 + '<input type="text" class="form-control form-control-sm form-control-solid" id="wc_sp_' + id + '_title" maxlength="60" value="' + WC.esc( p.title ) + '" placeholder="' + WC.esc( p.defaults.title ) + '" /></div>'
                 + '<div class="col-12 col-md-6"><label class="form-label fs-7 required" for="wc_sp_' + id + '_heading">Heading</label>'
                 + '<input type="text" class="form-control form-control-sm form-control-solid" id="wc_sp_' + id + '_heading" maxlength="80" value="' + WC.esc( p.heading ) + '" placeholder="' + WC.esc( p.defaults.heading ) + '" /></div>'
                 + '<div class="col-12"><label class="form-label fs-7" for="wc_sp_' + id + '_subheading">Sub-heading</label>'
                 + '<input type="text" class="form-control form-control-sm form-control-solid" id="wc_sp_' + id + '_subheading" maxlength="200" value="' + WC.esc( p.subheading ) + '" placeholder="' + WC.esc( p.defaults.subheading ) + '" /></div>'
                 + '</div>'
                 + WC.imageRow( 'signin_' + p.id + '_logo', 'Own logo', p.logo, true )
                 + '</div></div>';
        } ).join( '' ) );
    },

    /**
     * @description Draws the social sign-in provider tiles
     * @returns {void}
     */
    drawSocial : function () {
        var icons = { google: 'fa-google text-danger', facebook: 'fa-facebook text-primary', instagram: 'fa-instagram text-danger', whatsapp: 'fa-whatsapp text-success' };   // Brand icons
        $( '#wc_social' ).html( $.map( WC.data.social, function ( s ) {
            var id = WC.esc( s.id );
            return '<div class="col-12 col-md-6 col-xxl-3"><div class="wc-social">'
                 + '<div class="d-flex align-items-center justify-content-between mb-4">'
                 + '<div class="fw-bold fs-5"><i class="fa-brands ' + ( icons[ s.id ] || 'fa-circle' ) + ' fs-3 me-2"></i>' + WC.esc( s.label ) + '</div>'
                 + '<div class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="wc_' + id + '_enabled"' + ( s.enabled ? ' checked' : '' ) + ' /></div></div>'
                 + '<label class="form-label fs-7" for="wc_' + id + '_client_id">App / Client ID</label>'
                 + '<input type="text" class="form-control form-control-sm form-control-solid mb-3" id="wc_' + id + '_client_id" maxlength="500" autocomplete="off" value="' + WC.esc( s.client_id ) + '" />'
                 + '<label class="form-label fs-7" for="wc_' + id + '_client_secret">App / Client secret</label>'
                 + '<input type="password" class="form-control form-control-sm form-control-solid" id="wc_' + id + '_client_secret" maxlength="500" autocomplete="new-password"'
                 + ' placeholder="' + ( s.secret_saved ? '•••••• saved - type to replace' : 'Not set' ) + '" />'
                 + ( s.secret_saved ? '<label class="form-check form-check-sm form-check-custom form-check-solid mt-2"><input class="form-check-input" type="checkbox" data-wc-clear="' + id + '" id="wc_' + id + '_clear_secret" />'
                                    + '<span class="form-check-label fs-8 text-muted">Remove the saved secret</span></label>' : '' )
                 + '</div></div>';
        } ).join( '' ) );
    },

    /**
     * @description Adds a footer link row
     * @param {string} label - Link text
     * @param {string} url - Link address
     * @returns {void}
     */
    addFooterLink : function ( label, url ) {
        $( '#wc_footer_links' ).append( '<div class="wc-link-row d-flex gap-2 mb-2">'
            + '<input type="text" class="form-control form-control-sm form-control-solid" data-wc-link="label" maxlength="40" placeholder="Label" value="' + WC.esc( label ) + '" style="max-width: 160px;" />'
            + '<input type="text" class="form-control form-control-sm form-control-solid" data-wc-link="url" maxlength="300" placeholder="https://, mailto:, tel: or #" value="' + WC.esc( url ) + '" />'
            + '<button type="button" class="btn btn-sm btn-icon btn-light-danger flex-shrink-0" data-wc-link-remove="1" title="Remove"><i class="fa-solid fa-xmark"></i></button></div>' );
        WC.toggleAddLink();
    },

    /**
     * @description Hides "Add link" at 4 links
     * @returns {void}
     */
    toggleAddLink : function () {
        $( '#wc_footer_link_add' ).toggleClass( 'd-none', $( '#wc_footer_links .wc-link-row' ).length >= 4 );
    },

    /**
     * @description Updates the copyright preview
     * @returns {void}
     */
    previewCopyright : function () {
        $( '#wc_copyright_preview' ).text( $.trim( $( '#wc_copyright_year' ).val() ) + ' © ' + $.trim( $( '#wc_copyright_owner' ).val() ) + '. All rights reserved.' );
    },

    /**
     * @description Updates the browser tab title preview (for the Users page)
     * @returns {void}
     */
    previewTabTitle : function () {
        var format = $( '#wc_tab_title_format' ).val() || '';      // e.g. {page} | {site}
        $( '#wc_tab_title_preview' ).text( format.split( '{page}' ).join( 'Users' ).split( '{site}' ).join( $.trim( $( '#wc_site_name' ).val() ) ) );
    },

    /**
     * @description Shows whether maintenance mode is on
     * @returns {void}
     */
    showMaintenanceState : function () {
        var on = $( '#wc_maintenance_mode' ).is( ':checked' );   // Switch state (saved or not)
        $( '#wc_maintenance_state' ).html( on ? '<span class="text-warning fw-semibold">On after saving: other users are signed out of the panel.</span>' : 'Off: everyone can use the website.' );
    },

    // ── Save ────────────────────────────────────────────────────

    /**
     * @description Saves one tab
     * @param {string} section - identity, signin_pages, admin, signin, maintenance or social
     * @param {jQuery} $btn - The Save button
     * @returns {void}
     */
    save : function ( section, $btn ) {
        var data = { what_do_you_want: 'scodezy_save_website_configuration', section: section };   // Request
        if ( section === 'identity' ) {
            $.each( [ 'site_name', 'tagline', 'copyright_year', 'copyright_owner', 'copyright_url', 'domain_name', 'protocol', 'support_email', 'support_phone' ], function ( i, k ) {
                data[ k ] = $.trim( $( '#wc_' + k ).val() );
            } );
            if ( data.domain_name !== WC.data.identity.domain_name || data.protocol !== WC.data.identity.protocol ) {
                WC.confirm( 'Change the website address?', 'Links in emails will use <b>' + WC.esc( data.protocol + '://' + data.domain_name ) + '</b>.', function () { WC.send( data, $btn ); } );
                return;
            }
        }
        if ( section === 'signin_pages' ) {
            $.each( WC.data.signin_pages, function ( i, p ) {
                $.each( [ 'title', 'heading', 'subheading' ], function ( j, f ) { data[ p.id + '_' + f ] = $.trim( $( '#wc_sp_' + p.id + '_' + f ).val() ); } );
            } );
            data.background_type = $( 'input[name="wc_bg_type"]:checked' ).val() || 'none';
            data.background_color = $( '#wc_bg_color_value' ).val();
        }
        if ( section === 'admin' ) {
            var links = [];                                    // Footer links in screen order
            $( '#wc_footer_links .wc-link-row' ).each( function () {
                links.push( { label: $.trim( $( this ).find( '[data-wc-link="label"]' ).val() ), url: $.trim( $( this ).find( '[data-wc-link="url"]' ).val() ) } );
            } );
            data.footer_links = JSON.stringify( links );
            data.footer_css = $( '#wc_footer_css' ).val();
            data.tab_title_format = $.trim( $( '#wc_tab_title_format' ).val() );
            data.dashboard_heading = $.trim( $( '#wc_dashboard_heading' ).val() );
        }
        if ( section === 'profiles' ) {
            data.display_default = $( '#wc_display_default' ).val();
            data.display_user_choice = WC.flag( '#wc_display_user_choice' );
            data.avatar_upload = WC.flag( '#wc_avatar_upload' );
            data.email_method = $( 'input[name="wc_email_method"]:checked' ).val() || 'link';
            data.email_code_minutes = $.trim( $( '#wc_email_code_minutes' ).val() );
        }
        if ( section === 'signin' ) {
            $.each( [ 'allow_forgot_password', 'is_registration_open', 'allow_email_credentials', 'activation_required' ], function ( i, k ) {
                data[ k ] = WC.flag( '#wc_' + k );
            } );
            data.activation_minutes = $.trim( $( '#wc_activation_minutes' ).val() );
        }
        if ( section === 'maintenance' ) {
            $( '[data-wc-retention]' ).each( function () { data[ $( this ).attr( 'data-wc-retention' ) ] = $.trim( $( this ).val() ); } );
            data.display_php_errors = WC.flag( '#wc_display_php_errors' );
            data.maintenance_mode = WC.flag( '#wc_maintenance_mode' );
            data.maintenance_message = $.trim( $( '#wc_maintenance_message' ).val() );
            if ( data.maintenance_mode === '1' && !WC.data.maintenance_mode.on ) {
                WC.confirm( 'Turn maintenance mode on?', 'Only Administrators can sign in and use the panel until you turn it off. TVs and OPS servers keep working.', function () { WC.send( data, $btn ); } );
                return;
            }
        }
        if ( section === 'social' ) {
            $.each( WC.data.social, function ( i, s ) {
                data[ s.id + '_enabled' ] = WC.flag( '#wc_' + s.id + '_enabled' );
                data[ s.id + '_client_id' ] = $.trim( $( '#wc_' + s.id + '_client_id' ).val() );
                data[ s.id + '_client_secret' ] = $( '#wc_' + s.id + '_clear_secret' ).is( ':checked' ) ? '' : $.trim( $( '#wc_' + s.id + '_client_secret' ).val() );
                data[ s.id + '_clear_secret' ] = WC.flag( '#wc_' + s.id + '_clear_secret' );
            } );
        }
        WC.send( data, $btn );
    },

    /**
     * @description Asks before a save with a wide effect
     * @param {string} title - Dialog title
     * @param {string} html - Dialog text (HTML, escaped by the caller)
     * @param {function} fn - Runs on Yes
     * @returns {void}
     */
    confirm : function ( title, html, fn ) {
        Swal.fire( {
            title : title, html : html, icon : 'warning', showCancelButton : true, confirmButtonText : 'Yes, save', cancelButtonText : 'Go back',
            buttonsStyling : false, customClass : { confirmButton: 'btn btn-warning', cancelButton: 'btn btn-light' }
        } ).then( function ( r ) { if ( r.isConfirmed ) { fn(); } } );
    },

    /**
     * @description Sends a save request and reloads the settings on success
     * @param {object} data - Request
     * @param {jQuery} $btn - Button showing the progress
     * @returns {void}
     */
    send : function ( data, $btn ) {
        showLoadingOnButton( $btn );
        WC.post( data ).then( function ( info ) {
            hideLoadingOnButton( $btn );
            showSimpleToast( 'success', info.message );
            WC.load( false );
        } ).catch( function ( message ) { hideLoadingOnButton( $btn ); WC.fail( message ); } );
    },

    // ── Images ──────────────────────────────────────────────────

    /**
     * @description Uploads the picked image for WC.uploadKey
     * @returns {void}
     */
    upload : function () {
        var file = this.files && this.files[ 0 ];                 // Picked file
        if ( !file || WC.uploadKey === '' ) { return; }
        if ( file.size > 2 * 1024 * 1024 ) { WC.fail( 'The file is larger than 2 MB.' ); return; }
        var form = new FormData();
        form.append( 'what_do_you_want', 'scodezy_upload_website_image' );
        form.append( 'image_key', WC.uploadKey );
        form.append( 'image', file );
        WC.imageRequest( form, $( '[data-wc-upload="' + WC.uploadKey + '"]' ) );
    },

    /**
     * @description Saves a typed path / address for an image setting
     * @param {string} key - configurations key
     * @param {jQuery} $btn - The Save button
     * @returns {void}
     */
    savePath : function ( key, $btn ) {
        WC.imageRequest( { what_do_you_want: 'scodezy_upload_website_image', image_key: key, path: $.trim( $( '#wc_img_' + key ).val() ) }, $btn );
    },

    /**
     * @description Removes an optional image (the page falls back to the common logo / colour)
     * @param {string} key - configurations key
     * @param {jQuery} $btn - The Remove button
     * @returns {void}
     */
    removeImage : function ( key, $btn ) {
        WC.imageRequest( { what_do_you_want: 'scodezy_upload_website_image', image_key: key, path: '' }, $btn );
    },

    /**
     * @description Sends an image request, then reloads the settings
     * @param {object|FormData} data - Request
     * @param {jQuery} $btn - Button to disable meanwhile
     * @returns {void}
     */
    imageRequest : function ( data, $btn ) {
        $btn.prop( 'disabled', true );
        WC.post( data ).then( function ( info ) {
            $btn.prop( 'disabled', false );
            showSimpleToast( 'success', info.message );
            WC.load( false );
        } ).catch( function ( message ) { $btn.prop( 'disabled', false ); WC.fail( message ); } );
    }
};

$( document ).ready( function () {
    WC.init();
} );
