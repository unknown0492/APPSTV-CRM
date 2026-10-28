"use strict";

/* =============================================
   record_sale.js (v1.0.6)
   Plugin: appstv_crm_order - Record Sale page
   One sale = customer + invoice + product lines (TV model + serial numbers). The form is kept in RS.state,
   saved as a draft on the server while it is filled (appstv_sale_save_draft), and saved for real in one
   transaction (appstv_sale_save). Serial number rules are the same as the server's appstv_sale_plan().
   ============================================= */

var RS = {

    MAX_SERIALS : 3000,                                        // Most TVs one sale may hold (same as the server)
    DRAFT_DELAY_MS : 3000,                                     // Draft is saved this long after the last change
    DRAFT_DELAY_SERIAL_MS : 800,                               // ...or this long after a serial number is added
    CHECK_DELAY_MS : 400,                                      // Serial numbers are checked in batches after this pause

    state : null,                                              // The whole form (this is what the draft holds)
    records : {},                                              // serial -> existing records from the server (cache)
    pendingCheck : {},                                         // serial -> true while waiting to be checked
    statuses : {},                                             // line key -> array of { status, note } per serial
    warningCount : 0,                                          // Serial numbers with a warning in the whole sale
    warningSignature : '',                                     // The warnings the confirmation tick was given for
    draftReady : false,                                        // False until the saved draft was loaded or declined
    draftTimer : null,                                         // Pending draft save timeout
    draftSaving : false,                                       // True while a draft save request runs
    draftAgain : false,                                        // A change arrived while a draft save was running
    dirty : false,                                             // Form changed since the last draft save
    saving : false,                                            // True while the sale is being saved
    checkTimer : null,                                         // Pending serial check timeout
    invoiceTimer : null,                                       // Pending invoice check timeout
    searchTimer : null,                                        // Pending customer search timeout
    searchSeq : 0,                                             // Number of the latest customer search (older answers are dropped)
    invoiceInfo : null,                                        // Result of the last invoice check: { invoice_no, list }
    fileLineKey : null,                                        // Line that receives the file being uploaded
    pickers : {},                                              // flatpickr instances by field name

    /**
     * @description Returns an empty form
     * @returns {object} A new state
     */
    emptyState : function () {
        return {
            v : 1,
            customer_mode : 'existing',
            customer : null,                                   // { customer_id, name, person }
            new_customer : { company_name: '', first_name: '', last_name: '', primary_email: '', primary_mobile: '' },
            invoice_no : '',
            invoice_date : '',
            warranty_start : '',
            warranty_start_touched : false,                    // True once the delivery date was changed by hand
            warranty_months : 36,
            extended_years : 0,
            remarks : '',
            add_to_existing : false,
            confirm_warnings : false,
            next_key : 2,
            lines : [ { key: 1, product_id: '', serials: [] } ]
        };
    },

    /**
     * @description Starts the page: binds events, draws an empty form, then offers the saved draft if there is one
     * @returns {void}
     */
    init : function () {
        RS.state = RS.emptyState();
        RS.initPickers();
        RS.bindEvents();
        RS.renderAll();
        RS.loadDraft();
    },

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * @description Escapes text for HTML
     * @param {*} value - Any value
     * @returns {string} HTML-safe text
     */
    esc : function ( value ) {
        return $( '<div>' ).text( value === null || value === undefined ? '' : String( value ) ).html();
    },

    /**
     * @description Posts to the webservice and hands the parsed answer to the callbacks
     * @param {object|FormData} data - Request data (must hold what_do_you_want)
     * @param {function} onSuccess - Called with info of a success answer
     * @param {function} onError - Called with a message for an error answer or a failed request
     * @returns {jqXHR} The request
     */
    call : function ( data, onSuccess, onError ) {
        var isForm = ( data instanceof FormData );             // File uploads go as multipart
        return $.ajax( {
            url : getWebservice(),
            type : 'POST',
            data : data,
            processData : !isForm,
            contentType : isForm ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
            success : function ( returned_data ) {
                var jSon = parseJSONWithError( returned_data, 'Server Side error occurred ! Contact Technical Support...' );
                if ( jSon === false ) { onError( 'Server error. Please try again.' ); return; }
                jSon = jSon[ 0 ];
                if ( jSon[ 'type' ] === 'success' ) { onSuccess( jSon[ 'info' ] ); return; }
                var info = jSon[ 'info' ];
                onError( ( info && typeof info === 'object' && info[ 'message' ] ) ? info[ 'message' ] : String( info ) );
            },
            error : function () {
                onError( 'Could not reach the server. Check the connection and try again.' );
            }
        } );
    },

    /**
     * @description Formats 'YYYY-MM-DD' as dd/mm/yyyy
     * @param {string} ymd - The date
     * @returns {string} Display date, '' when empty
     */
    showDate : function ( ymd ) {
        if ( !ymd ) { return ''; }
        var p = ymd.split( '-' );
        return p[ 2 ] + '/' + p[ 1 ] + '/' + p[ 0 ];
    },

    /**
     * @description Adds months (or years) to a 'YYYY-MM-DD' date the same way PHP's modify('+N months') does
     * @param {string} ymd - Start date
     * @param {number} months - Months to add
     * @returns {string} The new date as 'YYYY-MM-DD'
     */
    addMonths : function ( ymd, months ) {
        var p = ymd.split( '-' );
        var d = new Date( Date.UTC( +p[ 0 ], +p[ 1 ] - 1 + months, +p[ 2 ] ) );   // Day overflow rolls over, like PHP
        return d.toISOString().substring( 0, 10 );
    },

    /**
     * @description Finds a product line by key
     * @param {number} key - Line key
     * @returns {object|null} The line
     */
    line : function ( key ) {
        for ( var i = 0; i < RS.state.lines.length; i++ ) {
            if ( RS.state.lines[ i ].key === key ) { return RS.state.lines[ i ]; }
        }
        return null;
    },

    /**
     * @description Finds a product by ID
     * @param {string} productId - products.product_id
     * @returns {object|null} The product
     */
    product : function ( productId ) {
        for ( var i = 0; i < RS_PRODUCTS.length; i++ ) {
            if ( RS_PRODUCTS[ i ].product_id === productId ) { return RS_PRODUCTS[ i ]; }
        }
        return null;
    },

    /**
     * @description Number of serial numbers in the form
     * @returns {number} Count
     */
    totalSerials : function () {
        var total = 0;
        $.each( RS.state.lines, function ( i, l ) { total += l.serials.length; } );
        return total;
    },

    /**
     * @description True when the form holds anything worth keeping
     * @returns {boolean}
     */
    hasData : function () {
        var s = RS.state;
        var nc = s.new_customer;
        return RS.totalSerials() > 0 || s.customer !== null || s.invoice_no !== '' || s.remarks !== ''
            || nc.company_name !== '' || nc.first_name !== '' || nc.last_name !== '' || nc.primary_email !== '' || nc.primary_mobile !== '';
    },

    // ── Events ──────────────────────────────────────────────────

    /**
     * @description Creates the date pickers (dates kept as Y-m-d, shown as d/m/Y)
     * @returns {void}
     */
    initPickers : function () {
        $.each( [ 'invoice_date', 'warranty_start' ], function ( i, field ) {
            RS.pickers[ field ] = flatpickr( '#rs_' + field, {
                dateFormat : 'Y-m-d',
                altInput : true,
                altFormat : 'd/m/Y',
                allowInput : false,
                onChange : function ( dates, text ) { RS.onDateChange( field, text ); }
            } );
        } );
    },

    /**
     * @description Binds every event of the page
     * @returns {void}
     */
    bindEvents : function () {
        // Customer
        $( 'input[name="rs_customer_mode"]' ).on( 'change', function () {
            RS.state.customer_mode = $( this ).val();
            RS.renderCustomer();
            RS.checkInvoice();
            RS.changed( false );
        } );
        $( '#rs_customer_search' ).on( 'input', function () {
            clearTimeout( RS.searchTimer );
            var q = $( this ).val().trim();
            RS.searchTimer = setTimeout( function () { RS.searchCustomers( q ); }, 300 );
        } );
        $( '#rs_customer_results' ).on( 'click', '[data-customer-index]', function () {
            var c = $( '#rs_customer_results' ).data( 'customers' )[ +$( this ).attr( 'data-customer-index' ) ];
            RS.chooseCustomer( c );
        } );
        $( document ).on( 'click', function ( e ) {
            if ( $( e.target ).closest( '#rs_customer_search_box' ).length === 0 ) { $( '#rs_customer_results' ).addClass( 'd-none' ); }
        } );
        $( '#rs_customer_change' ).on( 'click', function () {
            RS.state.customer = null;
            RS.renderCustomer();
            RS.checkInvoice();
            RS.changed( false );
            $( '#rs_customer_search' ).val( '' ).trigger( 'focus' );
        } );
        $( '#rs_new_box' ).on( 'input', '[data-rs-field]', function () {
            RS.state.new_customer[ $( this ).attr( 'data-rs-field' ) ] = $( this ).val();
            RS.changed( false );
        } );
        $( '#rs_nc_company_name' ).on( 'blur', function () { RS.checkNewCustomerName(); } );
        $( '#rs_nc_match' ).on( 'click', '[data-use-customer]', function ( e ) {
            e.preventDefault();
            var c = $( '#rs_nc_match' ).data( 'customer' );
            $( '#rs_mode_existing' ).prop( 'checked', true );
            RS.state.customer_mode = 'existing';
            RS.chooseCustomer( c );
        } );

        // Invoice and warranty
        $( '#rs_invoice_no' ).on( 'input', function () {
            RS.state.invoice_no = $( this ).val().trim();
            RS.state.add_to_existing = false;
            clearTimeout( RS.invoiceTimer );
            RS.invoiceTimer = setTimeout( RS.checkInvoice, 500 );
            RS.changed( false );
        } );
        $( '#rs_invoice_info' ).on( 'change', '#rs_add_to_existing', function () {
            RS.state.add_to_existing = $( this ).is( ':checked' );
            RS.changed( false );
        } );
        $( '#rs_warranty_months' ).on( 'change', function () {
            RS.state.warranty_months = +$( this ).val();
            RS.renderWarrantyDates();
            RS.changed( false );
        } );
        $( '#rs_extended_years' ).on( 'change', function () {
            RS.state.extended_years = +$( this ).val();
            RS.renderWarrantyDates();
            RS.changed( false );
        } );
        $( '#rs_remarks' ).on( 'input', function () {
            RS.state.remarks = $( this ).val();
            RS.changed( false );
        } );

        // Product lines
        $( '#rs_add_line' ).on( 'click', function () {
            RS.state.lines.push( { key: RS.state.next_key++, product_id: '', serials: [] } );
            RS.renderLines();
            RS.changed( false );
        } );
        var $lines = $( '#rs_lines' );
        $lines.on( 'change', '.rs-product', function () {
            RS.line( +$( this ).closest( '.rs-line' ).attr( 'data-line-key' ) ).product_id = $( this ).val() || '';
            RS.refreshStatuses();
            RS.changed( false );
        } );
        $lines.on( 'click', '[data-rs-action]', function () {
            var key = +$( this ).closest( '.rs-line' ).attr( 'data-line-key' );
            var action = $( this ).attr( 'data-rs-action' );
            if ( action === 'add' ) {
                var $input = $( this ).closest( '.rs-line' ).find( '.rs-sn-input' );
                RS.addSerials( key, AppstvScanner.splitMany( $input.val() ) );
                $input.val( '' ).trigger( 'focus' );
            }
            if ( action === 'camera' ) { RS.openCamera( key ); }
            if ( action === 'file' ) { RS.fileLineKey = key; $( '#rs_file_input' ).val( '' ).trigger( 'click' ); }
            if ( action === 'remove-sn' ) { RS.removeSerial( key, +$( this ).attr( 'data-index' ) ); }
            if ( action === 'clear' ) { RS.clearLine( key ); }
            if ( action === 'remove-line' ) { RS.removeLine( key ); }
        } );
        $( '#rs_file_input' ).on( 'change', function () {
            if ( this.files && this.files[ 0 ] ) { RS.readFile( RS.fileLineKey, this.files[ 0 ] ); }
        } );

        // Save bar
        $( '#rs_confirm_warnings' ).on( 'change', function () {
            RS.state.confirm_warnings = $( this ).is( ':checked' );
            RS.changed( false );
        } );
        $( '#rs_save' ).on( 'click', RS.save );
        $( '#rs_start_over' ).on( 'click', RS.startOver );

        // Leaving the page: warn while there is unsaved data, and push the latest draft out
        $( window ).on( 'beforeunload', function ( e ) {
            if ( RS.draftReady && RS.hasData() ) {
                RS.flushDraft();
                e.preventDefault();
                e.originalEvent.returnValue = '';
                return '';
            }
        } );
        $( document ).on( 'visibilitychange', function () {
            if ( document.visibilityState === 'hidden' ) { RS.flushDraft(); }
        } );
        $( window ).on( 'pagehide', RS.flushDraft );
    },

    // ── Customer ────────────────────────────────────────────────

    /**
     * @description Searches customers and shows the matches under the search box
     * @param {string} q - Search text
     * @returns {void}
     */
    searchCustomers : function ( q ) {
        var $results = $( '#rs_customer_results' );
        if ( q.length < 2 ) { $results.addClass( 'd-none' ); return; }
        var seq = ++RS.searchSeq;
        RS.call( { what_do_you_want: 'appstv_sale_search_customers', q: q }, function ( info ) {
            if ( seq !== RS.searchSeq ) { return; }
            var list = info[ 'data' ] || [];
            var html = '';
            $.each( list, function ( i, c ) {
                var sub = [ c.person, c.primary_email, c.primary_mobile, 'ID ' + c.customer_id ].filter( function ( x ) { return x; } ).join( ' · ' );
                html += '<div class="rs-dropdown-item" data-customer-index="' + i + '"><div class="fw-semibold">' + RS.esc( c.name || '(no name)' )
                     + '</div><div class="text-muted fs-8">' + RS.esc( sub ) + '</div></div>';
            } );
            if ( list.length === 0 ) { html = '<div class="rs-dropdown-empty">No customer found. Use "New customer" to add one.</div>'; }
            $results.data( 'customers', list ).html( html ).removeClass( 'd-none' );
        }, function ( message ) {
            if ( seq === RS.searchSeq ) { showSimpleToast( 'error', message ); }
        } );
    },

    /**
     * @description Uses a customer from the search results
     * @param {object} c - { customer_id, name, person, primary_email, primary_mobile }
     * @returns {void}
     */
    chooseCustomer : function ( c ) {
        RS.state.customer = { customer_id: c.customer_id, name: c.name, person: c.person || '',
                              sub: [ c.primary_email, c.primary_mobile, 'ID ' + c.customer_id ].filter( function ( x ) { return x; } ).join( ' · ' ) };
        $( '#rs_customer_results' ).addClass( 'd-none' );
        RS.renderCustomer();
        RS.checkInvoice();
        RS.changed( false );
    },

    /**
     * @description Warns when a new customer's company name already exists, and offers to use that customer
     * @returns {void}
     */
    checkNewCustomerName : function () {
        var name = RS.state.new_customer.company_name.trim();
        var $match = $( '#rs_nc_match' ).addClass( 'd-none' );
        if ( name.length < 2 ) { return; }
        RS.call( { what_do_you_want: 'appstv_sale_search_customers', q: name }, function ( info ) {
            var same = ( info[ 'data' ] || [] ).filter( function ( c ) { return ( c.name || '' ).toLowerCase() === name.toLowerCase(); } );
            if ( same.length === 0 ) { return; }
            $match.data( 'customer', same[ 0 ] )
                .html( 'A customer named "' + RS.esc( same[ 0 ].name ) + '" already exists. <a href="#" data-use-customer="1">Use that customer</a>' )
                .removeClass( 'd-none' );
        }, function () {} );
    },

    // ── Invoice and warranty ────────────────────────────────────

    /**
     * @description Keeps the dates in the state; the delivery date follows the invoice date until changed by hand
     * @param {string} field - 'invoice_date' or 'warranty_start'
     * @param {string} value - 'YYYY-MM-DD' or ''
     * @returns {void}
     */
    onDateChange : function ( field, value ) {
        RS.state[ field ] = value;
        if ( field === 'warranty_start' ) { RS.state.warranty_start_touched = ( value !== '' ); }
        if ( field === 'invoice_date' && !RS.state.warranty_start_touched ) {
            RS.state.warranty_start = value;
            RS.pickers.warranty_start.setDate( value || null, false );
        }
        RS.renderWarrantyDates();
        RS.changed( false );
    },

    /**
     * @description Looks the invoice number up and shows whether it is new, the customer's own or someone else's
     * @returns {void}
     */
    checkInvoice : function () {
        var no = RS.state.invoice_no;
        if ( no === '' ) { RS.invoiceInfo = null; RS.renderInvoiceInfo(); return; }
        RS.call( { what_do_you_want: 'appstv_sale_check_invoice', invoice_no: no }, function ( info ) {
            if ( no !== RS.state.invoice_no ) { return; }
            RS.invoiceInfo = { invoice_no: no, list: info[ 'data' ] || [] };
            RS.renderInvoiceInfo();
        }, function () {} );
    },

    /**
     * @description What the invoice number means for this sale
     * @returns {string} 'new', 'same' (customer's own existing invoice), 'other' (someone else's) or 'unknown'
     */
    invoiceSituation : function () {
        if ( RS.invoiceInfo === null || RS.invoiceInfo.invoice_no !== RS.state.invoice_no ) { return 'unknown'; }
        var list = RS.invoiceInfo.list;
        if ( list.length === 0 ) { return 'new'; }
        var mine = ( RS.state.customer_mode === 'existing' && RS.state.customer !== null ) ? RS.state.customer.customer_id : null;
        for ( var i = 0; i < list.length; i++ ) {
            if ( mine !== null && list[ i ].customer_id === mine ) { return 'same'; }
        }
        return 'other';
    },

    // ── Product lines and serial numbers ────────────────────────

    /**
     * @description Adds serial numbers to a product line (cleaned; invalid ones reported)
     * @param {number} key - Line key
     * @param {string[]} rawList - Raw values
     * @returns {number} How many were added
     */
    addSerials : function ( key, rawList ) {
        var line = RS.line( key );
        if ( line === null ) { return 0; }
        var added = 0, invalid = 0, repeated = [], full = false;
        var inLine = {};                                       // Serials already in this line
        $.each( line.serials, function ( i, sn ) { inLine[ sn ] = true; } );

        $.each( rawList, function ( i, raw ) {
            var sn = AppstvScanner.cleanSerial( raw );
            if ( sn === '' ) { invalid++; return; }
            if ( RS.totalSerials() >= RS.MAX_SERIALS ) { full = true; return false; }
            if ( inLine[ sn ] ) { repeated.push( sn ); }
            inLine[ sn ] = true;
            line.serials.push( sn );
            if ( RS.records[ sn ] === undefined ) { RS.pendingCheck[ sn ] = true; }
            added++;
        } );

        if ( invalid > 0 ) { showSimpleToast( 'warning', invalid + ' value(s) skipped: not a serial number (letters and digits, up to 50).' ); }
        if ( repeated.length > 0 ) { showSimpleToast( 'warning', repeated.slice( 0, 3 ).join( ', ' ) + ( repeated.length > 3 ? ' and more' : '' ) + ' already in this list - added again and marked as repeated.' ); }
        if ( full ) { showSimpleToast( 'error', 'One sale can hold up to ' + RS.MAX_SERIALS + ' TVs. Save this sale and start another.' ); }

        if ( added > 0 ) {
            RS.refreshStatuses();
            RS.scheduleCheck();
            RS.changed( true );
        }
        return added;
    },

    /**
     * @description Removes one serial number from a line
     * @param {number} key - Line key
     * @param {number} index - Position in line.serials
     * @returns {void}
     */
    removeSerial : function ( key, index ) {
        RS.line( key ).serials.splice( index, 1 );
        RS.refreshStatuses();
        RS.changed( true );
    },

    /**
     * @description Empties a line after confirmation
     * @param {number} key - Line key
     * @returns {void}
     */
    clearLine : function ( key ) {
        var line = RS.line( key );
        if ( line.serials.length === 0 ) { return; }
        showConfirmSweetAlert( 'Clear the list?', 'Remove all ' + line.serials.length + ' serial number(s) of this TV model?', 'warning',
            'Clear', 'btn btn-danger', 'Cancel', 'btn btn-light', function () {
                line.serials = [];
                RS.refreshStatuses();
                RS.changed( true );
            } );
    },

    /**
     * @description Removes a whole product line (confirmation when it holds serial numbers)
     * @param {number} key - Line key
     * @returns {void}
     */
    removeLine : function ( key ) {
        var line = RS.line( key );
        var drop = function () {
            RS.state.lines = RS.state.lines.filter( function ( l ) { return l.key !== key; } );
            if ( RS.state.lines.length === 0 ) { RS.state.lines.push( { key: RS.state.next_key++, product_id: '', serials: [] } ); }
            RS.renderLines();
            RS.refreshStatuses();
            RS.changed( true );
        };
        if ( line.serials.length === 0 ) { drop(); return; }
        showConfirmSweetAlert( 'Remove this TV model?', 'Its ' + line.serials.length + ' serial number(s) will be removed from the sale.', 'warning',
            'Remove', 'btn btn-danger', 'Cancel', 'btn btn-light', drop );
    },

    /**
     * @description Opens the camera scanner in continuous mode for a line
     * @param {number} key - Line key
     * @returns {void}
     */
    openCamera : function ( key ) {
        var line = RS.line( key );
        var product = RS.product( line.product_id );
        AppstvScanner.open( {
            title : 'Scan TVs' + ( product ? ' - ' + product.name : '' ),
            continuous : true,
            onScan : function ( serial ) {
                return RS.addSerials( key, [ serial ] ) > 0;
            },
            onClose : function ( count ) {
                if ( count > 0 ) { showSimpleToast( 'success', count + ' serial number(s) scanned.' ); }
            }
        } );
    },

    /**
     * @description Uploads a serial number file and adds the chosen column to a line
     * @param {number} key - Line key
     * @param {File} file - The .xlsx / .csv / .txt file
     * @returns {void}
     */
    readFile : function ( key, file ) {
        var formData = new FormData();                         // Multipart body for the upload
        formData.append( 'what_do_you_want', 'appstv_sale_read_serial_file' );
        formData.append( 'sn_file', file, file.name );
        showLoadingSweetAlert( 'Reading file', 'Please wait while the serial numbers are read', false );

        RS.call( formData, function ( info ) {
            hideLoadingSweetAlert();
            var columns = info[ 'data' ][ 'columns' ];
            if ( columns.length === 1 ) {
                RS.addFromColumn( key, columns[ 0 ] );
                return;
            }
            var options = {};                                  // Column choices for the dialog
            var best = 0;                                      // Column suggested first: the one named like "serial"
            $.each( columns, function ( i, c ) {
                options[ i ] = c.header + ' (' + c.values.length + ' values, e.g. ' + c.values.slice( 0, 2 ).join( ', ' ) + ')';
                if ( /serial|s\/?n\b/i.test( c.header ) ) { best = i; }
            } );
            Swal.fire( {
                title : 'Which column has the serial numbers?',
                input : 'select',
                inputOptions : options,
                inputValue : String( best ),
                showCancelButton : true,
                confirmButtonText : 'Add these',
                buttonsStyling : false,
                customClass : { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' }
            } ).then( function ( result ) {
                if ( result.isConfirmed ) { RS.addFromColumn( key, columns[ +result.value ] ); }
            } );
        }, function ( message ) {
            hideLoadingSweetAlert();
            showSimpleSweetAlert( message, 'error', 'Close', 'btn btn-danger' );
        } );
    },

    /**
     * @description Adds the values of one file column to a line and reports how many were added
     * @param {number} key - Line key
     * @param {object} column - { header, values }
     * @returns {void}
     */
    addFromColumn : function ( key, column ) {
        var added = RS.addSerials( key, column.values );
        showSimpleToast( 'success', added + ' serial number(s) added from the file.' );
    },

    /**
     * @description Checks the waiting serial numbers on the server after a short pause (batched)
     * @returns {void}
     */
    scheduleCheck : function () {
        clearTimeout( RS.checkTimer );
        RS.checkTimer = setTimeout( RS.runCheck, RS.CHECK_DELAY_MS );
    },

    /**
     * @description Sends the waiting serial numbers to the server and stores their records
     * @returns {void}
     */
    runCheck : function () {
        var batch = Object.keys( RS.pendingCheck ).slice( 0, RS.MAX_SERIALS );
        if ( batch.length === 0 ) { return; }
        RS.call( { what_do_you_want: 'appstv_sale_check_serials', serials: JSON.stringify( batch ) }, function ( info ) {
            var found = info[ 'data' ] || {};
            $.each( batch, function ( i, sn ) {
                RS.records[ sn ] = found[ sn ] || [];
                delete RS.pendingCheck[ sn ];
            } );
            RS.refreshStatuses();
            if ( Object.keys( RS.pendingCheck ).length > 0 ) { RS.scheduleCheck(); }
        }, function ( message ) {
            showSimpleToast( 'error', 'Serial numbers could not be checked: ' + message );
            RS.checkTimer = setTimeout( RS.runCheck, 5000 );   // Try again shortly
        } );
    },

    /**
     * @description Works out the status of every serial number (same rules as the server's appstv_sale_plan) and redraws
     * @returns {void}
     */
    refreshStatuses : function () {
        var seen = {};                                         // serial -> times met
        var reused = {};                                       // product_sn_id -> taken by an earlier serial
        var warnings = 0;
        var warningKeys = [];                                  // "line:serial:status" of every warning
        RS.statuses = {};

        $.each( RS.state.lines, function ( li, line ) {
            var list = [];
            $.each( line.serials, function ( i, sn ) {
                seen[ sn ] = ( seen[ sn ] || 0 ) + 1;
                var st = { status: 'checking', note: '' };
                var records = RS.records[ sn ];
                if ( records !== undefined ) {
                    st.status = 'new';
                    for ( var r = 0; r < records.length; r++ ) {
                        var rec = records[ r ];
                        if ( rec.free && rec.product_id === line.product_id && !reused[ rec.product_sn_id ] ) {
                            st.status = 'stock';
                            reused[ rec.product_sn_id ] = true;
                            break;
                        }
                    }
                    if ( seen[ sn ] > 1 ) {
                        st.status = 'repeat';
                        st.note = 'Repeated in this sale';
                    } else if ( st.status === 'new' ) {
                        var sold = records.filter( function ( x ) { return !x.free; } );
                        if ( sold.length > 0 ) {
                            st.status = 'sold';
                            st.note = 'Already sold to ' + sold.map( function ( x ) { return x.sold_to; } ).join( '; ' );
                        } else if ( records.length > 0 ) {
                            st.status = 'other_model';
                            st.note = 'In stock as ' + records.map( function ( x ) { return x.product_name || 'another model'; } ).join( ', ' ) + ' - check the model';
                        }
                    }
                } else if ( seen[ sn ] > 1 ) {
                    st.status = 'repeat';
                    st.note = 'Repeated in this sale';
                }
                if ( st.status === 'repeat' || st.status === 'sold' || st.status === 'other_model' ) {
                    warnings++;
                    warningKeys.push( line.key + ':' + sn + ':' + st.status );
                }
                list.push( st );
            } );
            RS.statuses[ line.key ] = list;
        } );

        RS.warningCount = warnings;
        var signature = warningKeys.join( '|' );               // Which serials have which warning
        if ( signature !== RS.warningSignature ) {             // The confirmation covers one exact set of warnings
            RS.state.confirm_warnings = false;
            RS.warningSignature = signature;
        }
        RS.renderLineLists();
        RS.renderSummary();
    },

    // ── Rendering ───────────────────────────────────────────────

    /**
     * @description Draws the whole form from the state
     * @returns {void}
     */
    renderAll : function () {
        var s = RS.state;
        $( '#rs_mode_' + s.customer_mode ).prop( 'checked', true );
        $( '#rs_new_box [data-rs-field]' ).each( function () { $( this ).val( s.new_customer[ $( this ).attr( 'data-rs-field' ) ] || '' ); } );
        $( '#rs_invoice_no' ).val( s.invoice_no );
        RS.pickers.invoice_date.setDate( s.invoice_date || null, false );
        RS.pickers.warranty_start.setDate( s.warranty_start || null, false );
        $( '#rs_warranty_months' ).val( String( s.warranty_months ) );
        $( '#rs_extended_years' ).val( String( s.extended_years ) );
        $( '#rs_remarks' ).val( s.remarks );
        $( '#rs_nc_match' ).addClass( 'd-none' );
        RS.renderCustomer();
        RS.renderWarrantyDates();
        RS.renderInvoiceInfo();
        RS.renderLines();
        RS.refreshStatuses();
    },

    /**
     * @description Shows the existing / new customer part
     * @returns {void}
     */
    renderCustomer : function () {
        var s = RS.state;
        $( '#rs_existing_box' ).toggleClass( 'd-none', s.customer_mode !== 'existing' );
        $( '#rs_new_box' ).toggleClass( 'd-none', s.customer_mode !== 'new' );
        $( '#rs_customer_search_box' ).toggleClass( 'd-none', s.customer !== null );
        $( '#rs_customer_selected' ).toggleClass( 'd-none', s.customer === null );
        if ( s.customer !== null ) {
            $( '#rs_customer_selected_name' ).text( s.customer.name || '(no name)' );
            $( '#rs_customer_selected_sub' ).text( [ s.customer.person, s.customer.sub ].filter( function ( x ) { return x; } ).join( ' · ' ) );
        }
    },

    /**
     * @description Shows when the warranty and the extended warranty end
     * @returns {void}
     */
    renderWarrantyDates : function () {
        var s = RS.state;
        if ( !s.warranty_start ) {
            $( '#rs_warranty_end_text' ).text( 'Choose the delivery date to see the end date' );
            $( '#rs_extended_end_text' ).text( '' );
            return;
        }
        var end = RS.addMonths( s.warranty_start, s.warranty_months );
        $( '#rs_warranty_end_text' ).text( 'Ends ' + RS.showDate( end ) );
        $( '#rs_extended_end_text' ).text( s.extended_years > 0 ? 'Ends ' + RS.showDate( RS.addMonths( end, 12 * s.extended_years ) ) : '' );
    },

    /**
     * @description Shows what the invoice number means (new / add to existing / someone else's)
     * @returns {void}
     */
    renderInvoiceInfo : function () {
        var $box = $( '#rs_invoice_info' );
        var situation = RS.invoiceSituation();
        if ( situation === 'unknown' ) { $box.addClass( 'd-none' ).empty(); return; }
        var list = RS.invoiceInfo.list;
        var html = '';
        if ( situation === 'new' ) {
            html = '<div class="text-muted fs-7"><i class="fa-solid fa-circle-check text-success"></i> New invoice number</div>';
        } else if ( situation === 'same' ) {
            var inv = list.filter( function ( x ) { return x.customer_id === RS.state.customer.customer_id; } )[ 0 ];
            html = '<div class="alert alert-warning py-3 mb-0"><div class="fw-semibold">Invoice ' + RS.esc( RS.state.invoice_no ) + ' already exists for this customer ('
                 + inv.tv_count + ' TV(s), dated ' + RS.esc( RS.showDate( inv.invoice_date ) ) + ').</div>'
                 + '<label class="form-check form-check-custom form-check-solid form-check-sm mt-2"><input class="form-check-input" type="checkbox" id="rs_add_to_existing"'
                 + ( RS.state.add_to_existing ? ' checked' : '' ) + ' /><span class="form-check-label">Add these TVs to the existing invoice (its date stays as it is)</span></label></div>';
        } else {
            var owners = list.map( function ( x ) { return x.customer_name || 'a customer without a name'; } ).join( ', ' );
            html = '<div class="alert alert-danger py-3 mb-0">Invoice ' + RS.esc( RS.state.invoice_no ) + ' belongs to ' + RS.esc( owners )
                 + '. Check the invoice number' + ( RS.state.customer_mode === 'new' ? ' (a new customer needs a new invoice number).' : ' or the customer.' ) + '</div>';
        }
        $box.html( html ).removeClass( 'd-none' );
    },

    /**
     * @description Draws the product line cards (model select, entry row, list)
     * @returns {void}
     */
    renderLines : function () {
        var $lines = $( '#rs_lines' );
        $lines.find( '.rs-product' ).each( function () { if ( $( this ).data( 'select2' ) ) { $( this ).select2( 'destroy' ); } } );

        var options = '<option value=""></option>';
        var group = null;
        $.each( RS_PRODUCTS, function ( i, p ) {
            var g = p.has_sn ? 'TV models' : 'Other products';
            if ( g !== group ) { options += ( group !== null ? '</optgroup>' : '' ) + '<optgroup label="' + g + '">'; group = g; }
            options += '<option value="' + RS.esc( p.product_id ) + '">' + RS.esc( p.name ) + ( p.sku ? ' (' + RS.esc( p.sku ) + ')' : '' ) + '</option>';
        } );
        options += ( group !== null ? '</optgroup>' : '' );

        var html = '';
        $.each( RS.state.lines, function ( i, line ) {
            html += '<div class="rs-line" data-line-key="' + line.key + '">'
                  + '  <div class="rs-line-head">'
                  + '    <div class="rs-line-model"><select class="form-select form-select-solid rs-product" data-placeholder="Choose the TV model">' + options + '</select></div>'
                  + '    <button type="button" class="btn btn-icon btn-sm btn-light-danger" data-rs-action="remove-line" title="Remove this TV model"><i class="fa-solid fa-trash"></i></button>'
                  + '  </div>'
                  + '  <div class="rs-entry">'
                  + '    <input type="text" class="form-control form-control-solid rs-sn-input" placeholder="Type or scan serial number, press Enter" autocomplete="off" autocorrect="off" autocapitalize="characters" spellcheck="false" enterkeyhint="done" />'
                  + '    <div class="rs-entry-buttons">'
                  + '      <button type="button" class="btn btn-light-primary" data-rs-action="add"><i class="fa-solid fa-plus"></i><span class="rs-btn-text"> Add</span></button>'
                  + '      <button type="button" class="btn btn-primary" data-rs-action="camera"><i class="fa-solid fa-camera"></i><span class="rs-btn-text"> Camera</span></button>'
                  + '      <button type="button" class="btn btn-light-info" data-rs-action="file"><i class="fa-solid fa-file-arrow-up"></i><span class="rs-btn-text"> File</span></button>'
                  + '    </div>'
                  + '  </div>'
                  + '  <div class="rs-line-summary"></div>'
                  + '  <div class="rs-sn-list"></div>'
                  + '</div>';
        } );
        $lines.html( html );

        $lines.find( '.rs-line' ).each( function () {
            var key = +$( this ).attr( 'data-line-key' );
            var $select = $( this ).find( '.rs-product' );
            $select.val( RS.line( key ).product_id );
            $select.select2( { placeholder: 'Choose the TV model', width: '100%' } );
            AppstvScanner.bindInput( $( this ).find( '.rs-sn-input' ), function ( values ) { RS.addSerials( key, values ); } );
        } );
        $( '.rs-line [data-rs-action="remove-line"]' ).toggleClass( 'd-none', RS.state.lines.length === 1 );
        RS.renderLineLists();
    },

    /**
     * @description Draws the serial number list and counts of every line (newest first)
     * @returns {void}
     */
    renderLineLists : function () {
        var labels = { checking: 'Checking…', 'new': 'New', stock: 'In stock', repeat: 'Repeated', sold: 'Already sold', other_model: 'Other model' };
        var badges = { checking: 'badge-light', 'new': 'badge-light-primary', stock: 'badge-light-success', repeat: 'badge-light-warning', sold: 'badge-light-warning', other_model: 'badge-light-warning' };

        $.each( RS.state.lines, function ( li, line ) {
            var $line = $( '.rs-line[data-line-key="' + line.key + '"]' );
            if ( $line.length === 0 ) { return; }
            var statuses = RS.statuses[ line.key ] || [];
            var counts = { checking: 0, 'new': 0, stock: 0, warn: 0 };
            var html = '';
            for ( var i = line.serials.length - 1; i >= 0; i-- ) {
                var st = statuses[ i ] || { status: 'checking', note: '' };
                if ( st.status === 'checking' || st.status === 'new' || st.status === 'stock' ) { counts[ st.status ]++; } else { counts.warn++; }
                var isWarn = ( badges[ st.status ] === 'badge-light-warning' );
                html += '<div class="rs-sn' + ( isWarn ? ' is-warning' : '' ) + '">'
                      + '<span class="rs-sn-no">' + ( i + 1 ) + '</span>'
                      + '<span class="rs-sn-text">' + RS.esc( line.serials[ i ] ) + '</span>'
                      + '<span class="badge ' + badges[ st.status ] + '">' + labels[ st.status ] + '</span>'
                      + ( st.note ? '<span class="rs-sn-note">' + RS.esc( st.note ) + '</span>' : '' )
                      + '<button type="button" class="btn btn-icon btn-sm btn-active-light-danger rs-sn-remove" data-rs-action="remove-sn" data-index="' + i + '" title="Remove"><i class="fa-solid fa-xmark"></i></button>'
                      + '</div>';
            }
            $line.find( '.rs-sn-list' ).html( html ).toggleClass( 'd-none', line.serials.length === 0 );

            var parts = [];
            if ( counts[ 'new' ] ) { parts.push( counts[ 'new' ] + ' new' ); }
            if ( counts.stock ) { parts.push( counts.stock + ' in stock' ); }
            if ( counts.warn ) { parts.push( '<span class="text-warning fw-bold">' + counts.warn + ' warning(s)</span>' ); }
            if ( counts.checking ) { parts.push( counts.checking + ' checking' ); }
            var summary = line.serials.length === 0 ? '' :
                '<span class="fw-bold">' + line.serials.length + ' TV(s)</span>' + ( parts.length ? ' · ' + parts.join( ' · ' ) : '' )
                + ' <button type="button" class="btn btn-link btn-sm p-0 ms-2 text-danger" data-rs-action="clear">Clear all</button>';
            $line.find( '.rs-line-summary' ).html( summary ).toggleClass( 'd-none', line.serials.length === 0 );
        } );
    },

    /**
     * @description Updates the total badge, the save bar summary and the warning confirmation
     * @returns {void}
     */
    renderSummary : function () {
        var total = RS.totalSerials();
        $( '#rs_total_badge' ).text( total + ' TV' + ( total === 1 ? '' : 's' ) );
        $( '#rs_summary' ).text( total === 0 ? 'No TVs added yet' : total + ' TV(s) ready to save' + ( RS.warningCount ? ', ' + RS.warningCount + ' with warnings' : '' ) );
        $( '#rs_warning_confirm_box' ).toggleClass( 'd-none', RS.warningCount === 0 );
        $( '#rs_confirm_warnings_label' ).text( 'I checked the ' + RS.warningCount + ' warning(s): they are different TVs and must be saved as separate TVs (same serial number from the manufacturer).' );
        $( '#rs_confirm_warnings' ).prop( 'checked', RS.state.confirm_warnings );
    },

    // ── Draft ───────────────────────────────────────────────────

    /**
     * @description Notes a change and schedules the draft save
     * @param {boolean} soon - True to save sooner (a serial number was added or removed)
     * @returns {void}
     */
    changed : function ( soon ) {
        RS.dirty = true;
        if ( !RS.draftReady ) { return; }
        clearTimeout( RS.draftTimer );
        RS.draftTimer = setTimeout( RS.saveDraft, soon ? RS.DRAFT_DELAY_SERIAL_MS : RS.DRAFT_DELAY_MS );
        $( '#rs_draft_status' ).text( 'Unsaved changes…' );
    },

    /**
     * @description Saves the form as the user's draft (or deletes the draft when the form is empty)
     * @returns {void}
     */
    saveDraft : function () {
        if ( !RS.draftReady || !RS.dirty ) { return; }
        if ( RS.draftSaving ) { RS.draftAgain = true; return; }
        RS.draftSaving = true;
        RS.dirty = false;

        var empty = !RS.hasData();
        var data = empty ? { what_do_you_want: 'appstv_sale_discard_draft' }
                         : { what_do_you_want: 'appstv_sale_save_draft', draft_data: JSON.stringify( RS.state ), serial_count: RS.totalSerials() };
        RS.call( data, function () {
            RS.draftSaving = false;
            var time = new Date().toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit' } );
            $( '#rs_draft_status' ).text( empty ? '' : 'Draft saved at ' + time + ' - you can leave and continue later' );
            if ( RS.draftAgain || RS.dirty ) { RS.draftAgain = false; RS.dirty = true; RS.saveDraft(); }
        }, function ( message ) {
            RS.draftSaving = false;
            RS.dirty = true;
            $( '#rs_draft_status' ).text( 'Draft not saved (' + message + '). Retrying…' );
            clearTimeout( RS.draftTimer );
            RS.draftTimer = setTimeout( RS.saveDraft, 10000 );
        } );
    },

    /**
     * @description Sends the latest draft right away when the page is being left (sendBeacon survives the unload)
     * @returns {void}
     */
    flushDraft : function () {
        if ( !RS.draftReady || !RS.dirty || !RS.hasData() || !navigator.sendBeacon ) { return; }
        var body = new FormData();                             // Same fields as saveDraft()
        body.append( 'what_do_you_want', 'appstv_sale_save_draft' );
        body.append( 'draft_data', JSON.stringify( RS.state ) );
        body.append( 'serial_count', String( RS.totalSerials() ) );
        if ( navigator.sendBeacon( getWebservice(), body ) ) {
            RS.dirty = false;
            clearTimeout( RS.draftTimer );
        }
    },

    /**
     * @description Loads the user's draft and asks whether to continue it or discard it
     * @returns {void}
     */
    loadDraft : function () {
        RS.call( { what_do_you_want: 'appstv_sale_get_draft' }, function ( info ) {
            var draft = info[ 'data' ];
            var saved = null;
            try { saved = draft ? JSON.parse( draft.draft_data ) : null; } catch ( e ) { saved = null; }
            if ( saved === null || !saved.lines ) { RS.draftReady = true; if ( RS.hasData() ) { RS.changed( false ); } return; }

            var when = new Date( draft.updated_on );
            var whenText = isNaN( when.getTime() ) ? '' : ' from ' + when.toLocaleString( [], { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' } );
            Swal.fire( {
                icon : 'question',
                title : 'Continue your unfinished sale?',
                html : 'You have an unfinished sale' + RS.esc( whenText ) + ' with <b>' + draft.serial_count + '</b> serial number(s)'
                     + ( saved.invoice_no ? ' (invoice ' + RS.esc( saved.invoice_no ) + ')' : '' ) + '.',
                showCancelButton : true,
                confirmButtonText : 'Continue',
                cancelButtonText : 'Discard it',
                allowOutsideClick : false,
                allowEscapeKey : false,
                buttonsStyling : false,
                customClass : { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light-danger' }
            } ).then( function ( result ) {
                if ( result.isConfirmed ) {
                    RS.state = $.extend( RS.emptyState(), saved );
                    RS.state.confirm_warnings = false;         // Warnings are confirmed again after resuming
                    $.each( RS.state.lines, function ( i, l ) {
                        $.each( l.serials, function ( j, sn ) { if ( RS.records[ sn ] === undefined ) { RS.pendingCheck[ sn ] = true; } } );
                    } );
                    RS.draftReady = true;
                    RS.renderAll();
                    RS.checkInvoice();
                    RS.scheduleCheck();
                    $( '#rs_draft_status' ).text( 'Draft restored' );
                } else {
                    RS.discardDraft();
                }
            } );
        }, function ( message ) {
            RS.draftReady = true;                              // Draft is optional: the form still works
            $( '#rs_draft_status' ).text( 'Drafts are unavailable right now (' + message + ')' );
        } );
    },

    /**
     * @description Deletes the draft on the server and keeps the current form
     * @returns {void}
     */
    discardDraft : function () {
        RS.call( { what_do_you_want: 'appstv_sale_discard_draft' }, function () {
            RS.draftReady = true;
            $( '#rs_draft_status' ).text( '' );
            if ( RS.hasData() ) { RS.changed( false ); }
        }, function ( message ) {
            RS.draftReady = true;
            showSimpleToast( 'error', 'The draft could not be discarded: ' + message );
        } );
    },

    /**
     * @description Empties the form (after confirmation) and deletes the draft
     * @returns {void}
     */
    startOver : function () {
        if ( !RS.hasData() ) { return; }
        showConfirmSweetAlert( 'Start over?', 'The form will be emptied and the unfinished sale (' + RS.totalSerials() + ' serial numbers) deleted.', 'warning',
            'Start over', 'btn btn-danger', 'Cancel', 'btn btn-light', function () {
                RS.resetForm();
                RS.call( { what_do_you_want: 'appstv_sale_discard_draft' }, function () {}, function () {} );
            } );
    },

    /**
     * @description Clears the form to a new empty sale (draft state untouched)
     * @returns {void}
     */
    resetForm : function () {
        clearTimeout( RS.draftTimer );
        RS.state = RS.emptyState();
        RS.invoiceInfo = null;
        RS.dirty = false;
        RS.renderAll();
        $( '#rs_customer_search' ).val( '' );
        $( '#rs_draft_status' ).text( '' );
    },

    // ── Save ────────────────────────────────────────────────────

    /**
     * @description Checks the form on the page, asks for confirmation and saves the sale
     * @returns {void}
     */
    save : function () {
        if ( RS.saving ) { return; }
        var s = RS.state;
        var errors = [];

        if ( s.customer_mode === 'existing' && s.customer === null ) { errors.push( 'Choose the customer (or switch to New customer).' ); }
        if ( s.customer_mode === 'new' && s.new_customer.company_name.trim() === '' && s.new_customer.first_name.trim() === '' ) { errors.push( 'Enter the new customer\'s company name or first name.' ); }
        if ( s.invoice_no === '' ) { errors.push( 'Enter the invoice number.' ); }
        if ( !s.invoice_date ) { errors.push( 'Choose the invoice date.' ); }
        if ( !s.warranty_start ) { errors.push( 'Choose the delivery date (warranty start).' ); }
        var total = RS.totalSerials();
        if ( total === 0 ) { errors.push( 'Add the serial numbers of the TVs sold.' ); }
        $.each( s.lines, function ( i, l ) {
            if ( l.serials.length > 0 && !l.product_id ) { errors.push( 'Choose the TV model of list ' + ( i + 1 ) + '.' ); }
        } );
        if ( Object.keys( RS.pendingCheck ).length > 0 ) { errors.push( 'Serial numbers are still being checked. Wait a moment and save again.' ); RS.scheduleCheck(); }

        var situation = RS.invoiceSituation();
        if ( situation === 'other' ) { errors.push( 'The invoice number belongs to another customer.' ); }
        if ( situation === 'same' && !s.add_to_existing ) { errors.push( 'The invoice already exists for this customer: tick "Add these TVs to the existing invoice", or use another number.' ); }
        if ( situation === 'unknown' && s.invoice_no !== '' ) { RS.checkInvoice(); errors.push( 'The invoice number is still being checked. Save again in a moment.' ); }
        if ( RS.warningCount > 0 && !s.confirm_warnings ) { errors.push( RS.warningCount + ' serial number(s) have warnings: check them and tick the confirmation above the Save button.' ); }

        if ( errors.length > 0 ) {
            Swal.fire( { icon: 'warning', title: 'Please check', html: '<ul class="text-start">' + errors.map( function ( e ) { return '<li>' + RS.esc( e ) + '</li>'; } ).join( '' ) + '</ul>',
                         buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
            return;
        }

        var customerName = s.customer_mode === 'existing' ? s.customer.name : ( s.new_customer.company_name || ( s.new_customer.first_name + ' ' + s.new_customer.last_name ).trim() );
        var stock = 0;
        $.each( RS.statuses, function ( k, list ) { $.each( list, function ( i, st ) { if ( st.status === 'stock' ) { stock++; } } ); } );
        var html = '<div class="text-start">'
                 + '<div><b>' + total + '</b> TV(s) for <b>' + RS.esc( customerName ) + '</b>' + ( s.customer_mode === 'new' ? ' (new customer)' : '' ) + '</div>'
                 + '<div>Invoice <b>' + RS.esc( s.invoice_no ) + '</b>' + ( situation === 'same' ? ' (existing)' : '' ) + ', dated ' + RS.esc( RS.showDate( s.invoice_date ) ) + '</div>'
                 + '<div>Warranty ' + RS.esc( RS.showDate( s.warranty_start ) ) + ' to ' + RS.esc( RS.showDate( RS.addMonths( s.warranty_start, s.warranty_months ) ) )
                 + ( s.extended_years > 0 ? ', extended +' + s.extended_years + ' year(s)' : '' ) + '</div>'
                 + '<div class="text-muted fs-7 mt-2">' + ( total - stock ) + ' new TV record(s), ' + stock + ' from stock' + ( RS.warningCount ? ', ' + RS.warningCount + ' confirmed warning(s)' : '' ) + '</div>'
                 + '</div>';

        showConfirmSweetAlert( 'Save this sale?', html, 'question', 'Save', 'btn btn-primary', 'Cancel', 'btn btn-light', RS.sendSave );
    },

    /**
     * @description Sends the sale to the server and shows the result
     * @returns {void}
     */
    sendSave : function () {
        var s = RS.state;
        var sale = {
            customer_mode : s.customer_mode,
            customer_id : s.customer ? s.customer.customer_id : '',
            new_customer : s.new_customer,
            invoice_no : s.invoice_no,
            invoice_date : s.invoice_date,
            warranty_start : s.warranty_start,
            warranty_months : s.warranty_months,
            extended_years : s.extended_years,
            remarks : s.remarks,
            add_to_existing : s.add_to_existing ? 1 : 0,
            confirm_warnings : s.confirm_warnings ? 1 : 0,
            lines : s.lines.filter( function ( l ) { return l.serials.length > 0; } ).map( function ( l ) { return { product_id: l.product_id, serials: l.serials }; } )
        };

        RS.saving = true;
        clearTimeout( RS.draftTimer );
        var $btn = $( '#rs_save' );
        showLoadingOnButton( $btn );

        RS.call( { what_do_you_want: 'appstv_sale_save', sale: JSON.stringify( sale ) }, function ( info ) {
            RS.saving = false;
            hideLoadingOnButton( $btn );
            var d = info[ 'data' ];
            RS.resetForm();                                    // The server deleted the draft with the sale
            Swal.fire( {
                icon : 'success',
                title : 'Sale saved',
                html : '<b>' + d.tv_count + '</b> TV(s) saved on invoice <b>' + RS.esc( d.invoice_no ) + '</b>' + ( d.invoice_created ? '' : ' (existing invoice)' )
                     + '.<br/>' + d.new_records + ' new TV record(s), ' + d.stock_records + ' from stock'
                     + ( d.extended ? ', ' + d.extended + ' extended warranty(ies)' : '' ) + '.<br/>Warranty ends ' + RS.esc( RS.showDate( d.warranty_end ) ) + '.',
                confirmButtonText : 'Record another sale',
                buttonsStyling : false,
                customClass : { confirmButton: 'btn btn-primary' }
            } );
        }, function ( message ) {
            RS.saving = false;
            hideLoadingOnButton( $btn );
            RS.changed( false );                               // Keep the draft up to date
            Swal.fire( { icon: 'error', title: 'Not saved', text: message, buttonsStyling: false, customClass: { confirmButton: 'btn btn-danger' } } );
            RS.checkInvoice();
        } );
    }
};

$( document ).ready( function () {
    RS.init();
} );
