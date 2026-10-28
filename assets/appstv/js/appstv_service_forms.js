"use strict";

/* =============================================
   appstv_service_forms.js (v1.0.7)
   Shared forms of the Warranty and Service Cases pages: Record service (repair / replacement) and
   Add extended warranty. They open in SweetAlert and call appstv_record_service / appstv_add_extended_warranty.
   Needs: jQuery, SweetAlert2, core.js (getWebservice, parseJSONWithError), appstv_scanner.js, and
   window.APPSTV_PRODUCTS ( [ { product_id, name, sku, has_sn } ] ) for the replacement model list.
   ============================================= */

var AppstvServiceForms = {

    /**
     * @description Escapes text for HTML
     * @param {*} value - Any value
     * @returns {string} HTML-safe text
     */
    esc : function ( value ) {
        return $( '<div>' ).text( value === null || value === undefined ? '' : String( value ) ).html();
    },

    /**
     * @description Today as 'YYYY-MM-DD' in the browser's time
     * @returns {string}
     */
    today : function () {
        var d = new Date();
        return d.getFullYear() + '-' + String( d.getMonth() + 1 ).padStart( 2, '0' ) + '-' + String( d.getDate() ).padStart( 2, '0' );
    },

    /**
     * @description Formats 'YYYY-MM-DD' as dd/mm/yyyy
     * @param {string} ymd - Date
     * @returns {string}
     */
    showDate : function ( ymd ) {
        if ( !ymd ) { return ''; }
        var p = String( ymd ).substring( 0, 10 ).split( '-' );
        return p[ 2 ] + '/' + p[ 1 ] + '/' + p[ 0 ];
    },

    /**
     * @description Posts to the webservice; resolves with info of a success answer, rejects with a message
     * @param {object} data - Request data (with what_do_you_want)
     * @returns {Promise<object>}
     */
    post : function ( data ) {
        return new Promise( function ( resolve, reject ) {
            $.ajax( {
                url : getWebservice(),
                type : 'POST',
                data : data,
                success : function ( returned_data ) {
                    var jSon = parseJSONWithError( returned_data, 'Server Side error occurred ! Contact Technical Support...' );
                    if ( jSon === false ) { reject( 'Server error. Please try again.' ); return; }
                    jSon = jSon[ 0 ];
                    var info = jSon[ 'info' ];
                    if ( jSon[ 'type' ] === 'success' ) { resolve( info ); return; }
                    reject( ( info && typeof info === 'object' && info[ 'message' ] ) ? info[ 'message' ] : String( info ) );
                },
                error : function () { reject( 'Could not reach the server. Check the connection and try again.' ); }
            } );
        } );
    },

    /**
     * @description Opens the Record service form for one TV
     * @param {object} tv - { product_sn_id, serial_number, product_id, product_name, case_id (optional), values (optional, refill) }
     * @param {function} onDone - Called with the server's info after a successful save
     * @param {function} onCancel - Called when the form is closed without saving (optional)
     * @returns {void}
     */
    recordService : function ( tv, onDone, onCancel ) {
        var f = AppstvServiceForms;
        var v = tv.values || { type: 'repair', date: f.today(), new_serial_number: '', new_product_id: tv.product_id || '', ref_no: '', remarks: '' };
        var models = '';
        $.each( window.APPSTV_PRODUCTS || [], function ( i, p ) {
            if ( !p.has_sn && p.product_id !== v.new_product_id ) { return; }
            models += '<option value="' + f.esc( p.product_id ) + '"' + ( p.product_id === v.new_product_id ? ' selected' : '' ) + '>'
                    + f.esc( p.name ) + ( p.product_id === tv.product_id ? ' (same model)' : '' ) + '</option>';
        } );

        var html = '<div class="text-start appstv-service-form">'
            + '<div class="mb-4 text-muted">TV <b class="text-gray-800 font-monospace">' + f.esc( tv.serial_number ) + '</b>' + ( tv.product_name ? ' · ' + f.esc( tv.product_name ) : '' ) + '</div>'
            + '<div class="btn-group w-100 mb-4" role="group">'
            + '  <input type="radio" class="btn-check" name="sf_type" id="sf_type_repair" value="repair"' + ( v.type === 'repair' ? ' checked' : '' ) + '/>'
            + '  <label class="btn btn-outline btn-outline-primary btn-active-primary" for="sf_type_repair">Repair</label>'
            + '  <input type="radio" class="btn-check" name="sf_type" id="sf_type_replacement" value="replacement"' + ( v.type === 'replacement' ? ' checked' : '' ) + '/>'
            + '  <label class="btn btn-outline btn-outline-primary btn-active-primary" for="sf_type_replacement">Replacement</label>'
            + '</div>'
            + '<div class="row g-3">'
            + '  <div class="col-6"><label class="form-label required">Date of service</label><input type="date" id="sf_date" class="form-control form-control-solid" max="' + f.today() + '" value="' + f.esc( v.date ) + '"/></div>'
            + '  <div class="col-6"><label class="form-label">Ref No. (service invoice)</label><input type="text" id="sf_ref_no" class="form-control form-control-solid" maxlength="50" value="' + f.esc( v.ref_no ) + '"/></div>'
            + '  <div class="col-12 sf-replacement' + ( v.type === 'replacement' ? '' : ' d-none' ) + '">'
            + '    <label class="form-label required">Replacement TV serial number</label>'
            + '    <div class="input-group"><input type="text" id="sf_new_serial" class="form-control form-control-solid font-monospace" autocomplete="off" autocapitalize="characters" value="' + f.esc( v.new_serial_number ) + '"/>'
            + '    <button type="button" class="btn btn-primary" id="sf_scan"><i class="fa-solid fa-camera"></i></button></div>'
            + '  </div>'
            + '  <div class="col-12 sf-replacement' + ( v.type === 'replacement' ? '' : ' d-none' ) + '"><label class="form-label">Replacement TV model</label><select id="sf_new_model" class="form-select form-select-solid">' + models + '</select></div>'
            + '  <div class="col-12"><label class="form-label required">What was done</label><textarea id="sf_remarks" class="form-control form-control-solid" rows="3" maxlength="2000" placeholder="e.g. Changed motherboard / Replaced with a new TV">' + f.esc( v.remarks ) + '</textarea></div>'
            + '</div></div>';

        var read = function () {
            return {
                type : $( 'input[name="sf_type"]:checked' ).val(),
                date : $( '#sf_date' ).val(),
                ref_no : $.trim( $( '#sf_ref_no' ).val() ),
                new_serial_number : AppstvScanner.cleanSerial( $( '#sf_new_serial' ).val() ) || $.trim( $( '#sf_new_serial' ).val() ),
                new_product_id : $( '#sf_new_model' ).val() || '',
                remarks : $.trim( $( '#sf_remarks' ).val() )
            };
        };

        Swal.fire( {
            title : 'Record service',
            html : html,
            showCancelButton : true,
            confirmButtonText : 'Save',
            buttonsStyling : false,
            focusConfirm : false,
            allowOutsideClick : false,
            customClass : { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' },
            didOpen : function () {
                $( 'input[name="sf_type"]' ).on( 'change', function () {
                    $( '.sf-replacement' ).toggleClass( 'd-none', $( this ).val() !== 'replacement' );
                } );
                $( '#sf_scan' ).on( 'click', function () {
                    AppstvScanner.open( { title: 'Scan the replacement TV', continuous: false, onScan: function ( serial ) { $( '#sf_new_serial' ).val( serial ); } } );
                } );
            },
            preConfirm : function () {
                var values = read();
                if ( !values.date ) { Swal.showValidationMessage( 'Enter the date of service.' ); return false; }
                if ( values.type === 'replacement' && !values.new_serial_number ) { Swal.showValidationMessage( 'Enter or scan the replacement TV serial number.' ); return false; }
                if ( !values.remarks ) { Swal.showValidationMessage( 'Describe what was done.' ); return false; }
                return values;
            }
        } ).then( function ( result ) {
            if ( !result.isConfirmed ) { if ( onCancel ) { onCancel(); } return; }
            f.sendService( tv, result.value, false, onDone, onCancel );
        } );
    },

    /**
     * @description Sends the service form; asks to confirm a duplicate replacement serial, re-opens the form on errors
     * @param {object} tv - The TV (see recordService)
     * @param {object} values - Form values
     * @param {boolean} confirmDuplicate - True after the user confirmed a duplicate serial
     * @param {function} onDone - Success callback
     * @param {function} onCancel - Cancel callback
     * @returns {void}
     */
    sendService : function ( tv, values, confirmDuplicate, onDone, onCancel ) {
        var f = AppstvServiceForms;
        var service = $.extend( { product_sn_id: tv.product_sn_id, case_id: tv.case_id || 0, confirm_duplicate: confirmDuplicate ? 1 : 0 }, values );
        Swal.fire( { title: 'Saving…', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } } );

        f.post( { what_do_you_want: 'appstv_record_service', service: JSON.stringify( service ) } ).then( function ( info ) {
            Swal.fire( { icon: 'success', title: info.message, text: info.data.summary, buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } )
                .then( function () { onDone( info ); } );
        } ).catch( function ( message ) {
            var again = $.extend( {}, tv, { values: values } );
            if ( message.indexOf( 'DUPLICATE:' ) === 0 ) {
                Swal.fire( {
                    icon : 'warning', title : 'Serial number already used', text : message.substring( 10 ),
                    showCancelButton : true, confirmButtonText : 'Yes, separate TV', cancelButtonText : 'Back to the form',
                    buttonsStyling : false, customClass : { confirmButton: 'btn btn-warning', cancelButton: 'btn btn-light' }
                } ).then( function ( r ) {
                    if ( r.isConfirmed ) { f.sendService( tv, values, true, onDone, onCancel ); } else { f.recordService( again, onDone, onCancel ); }
                } );
                return;
            }
            Swal.fire( { icon: 'error', title: 'Not saved', text: message, confirmButtonText: 'Back to the form', buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } )
                .then( function () { f.recordService( again, onDone, onCancel ); } );
        } );
    },

    /**
     * @description Opens the Add extended warranty form for one TV
     * @param {object} tv - { product_sn_id, serial_number, warranty_expiry ('YYYY-MM-DD'), case_id (optional) }
     * @param {function} onDone - Called with the server's info after a successful save
     * @param {function} onCancel - Called when closed without saving (optional)
     * @returns {void}
     */
    addExtended : function ( tv, onDone, onCancel ) {
        var f = AppstvServiceForms;
        var years = '';
        for ( var y = 1; y <= 5; y++ ) { years += '<option value="' + y + '"' + ( y === 2 ? ' selected' : '' ) + '>+' + y + ' year' + ( y > 1 ? 's' : '' ) + '</option>'; }
        var html = '<div class="text-start">'
            + '<div class="mb-4 text-muted">TV <b class="text-gray-800 font-monospace">' + f.esc( tv.serial_number ) + '</b> · warranty ends ' + f.esc( f.showDate( tv.warranty_expiry ) ) + '</div>'
            + '<label class="form-label required">Extend by</label><select id="sf_years" class="form-select form-select-solid mb-2">' + years + '</select>'
            + '<div class="form-text mb-4" id="sf_ext_end"></div>'
            + '<label class="form-label">Remarks</label><input type="text" id="sf_ext_remarks" class="form-control form-control-solid" maxlength="1000" placeholder="e.g. Paid, invoice 13250"/>'
            + '</div>';
        var showEnd = function () {
            if ( !tv.warranty_expiry ) { return; }
            var p = tv.warranty_expiry.split( '-' );
            var end = new Date( Date.UTC( +p[ 0 ] + +$( '#sf_years' ).val(), +p[ 1 ] - 1, +p[ 2 ] ) ).toISOString().substring( 0, 10 );
            $( '#sf_ext_end' ).text( 'Extended warranty from ' + f.showDate( tv.warranty_expiry ) + ' to ' + f.showDate( end ) );
        };

        Swal.fire( {
            title : 'Add extended warranty',
            html : html,
            showCancelButton : true,
            confirmButtonText : 'Save',
            buttonsStyling : false,
            focusConfirm : false,
            customClass : { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' },
            didOpen : function () { $( '#sf_years' ).on( 'change', showEnd ); showEnd(); },
            preConfirm : function () {
                return f.post( { what_do_you_want: 'appstv_add_extended_warranty', product_sn_id: tv.product_sn_id, years: $( '#sf_years' ).val(),
                                 remarks: $.trim( $( '#sf_ext_remarks' ).val() ), case_id: tv.case_id || 0 } )
                    .catch( function ( message ) { Swal.showValidationMessage( message ); return false; } );
            }
        } ).then( function ( result ) {
            if ( !result.isConfirmed || !result.value ) { if ( onCancel ) { onCancel(); } return; }
            Swal.fire( { icon: 'success', title: result.value.message, buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } )
                .then( function () { onDone( result.value ); } );
        } );
    }
};
