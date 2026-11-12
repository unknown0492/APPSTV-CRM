"use strict";

/* =============================================
   service_cases.js (v1.0.21)
   Plugin: appstv_warranty - Service Cases page
   List (chips + filters, due visits first), Case details (complaint, staff, visit window, TV cases, TVs with
   Record service / Extended warranty, notes timeline, change history). New cases are opened with the wizard
   (service_case_wizard.js, SCW). Deep links: ?case=<id>, ?draft=<id>, ?new_case_tv=<product_sn_id>.
   ============================================= */

var SC = {

    REFRESH_MS : 60000,                                        // List refresh while the page is visible and no window is open

    staff : [],                                                // [ { user_id, name } ] that cases can be assigned to
    me : '',                                                   // user_id of the signed-in user
    current : null,                                            // Data of the open case (appstv_case_get)
    listTimer : null,                                          // Pending list reload (search typing)
    listSeq : 0,                                               // Number of the latest list request (older answers dropped)
    STATUS : { open: 'Open', scheduled: 'Visit scheduled', visited: 'Visited', closed: 'Closed' },
    STATUS_BADGE : { open: 'badge-light-primary', scheduled: 'badge-light-info', visited: 'badge-light-warning', closed: 'badge-light' },
    OUTCOME : { repaired: 'Repaired', replaced: 'Replaced', no_fault: 'No fault found', not_covered: 'Not covered' },

    /**
     * @description Starts the page
     * @returns {void}
     */
    init : function () {
        SC.bindEvents();
        SC.post( { what_do_you_want: 'appstv_case_staff' } ).then( function ( info ) {
            SC.staff = info.data.staff;
            SC.me = info.data.me;
            SC.fillStaffSelects();
            SC.loadList();
            SC.openFromUrl();
        } ).catch( SC.toastError );

        setInterval( function () {
            if ( document.visibilityState === 'visible' && $( '.modal.show' ).length === 0 && !Swal.isVisible() ) { SC.loadList(); }
        }, SC.REFRESH_MS );
    },

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * @description Escapes text for HTML
     * @param {*} value - Any value
     * @returns {string}
     */
    esc : function ( value ) { return AppstvServiceForms.esc( value ); },

    /**
     * @description Posts to the webservice (promise)
     * @param {object} data - Request data
     * @returns {Promise<object>} info of the answer
     */
    post : function ( data ) { return AppstvServiceForms.post( data ); },

    /**
     * @description Shows an error message as a toast
     * @param {string} message - Text
     * @returns {void}
     */
    toastError : function ( message ) { showSimpleToast( 'error', message ); },

    /**
     * @description dd/mm/yyyy of a 'YYYY-MM-DD...' date
     * @param {string} ymd - Date
     * @returns {string}
     */
    showDate : function ( ymd ) { return AppstvServiceForms.showDate( ymd ); },

    /**
     * @description Short local date and time of an ISO 8601 timestamp
     * @param {string} iso - Timestamp
     * @returns {string}
     */
    showStamp : function ( iso ) {
        var d = new Date( iso );
        return isNaN( d.getTime() ) ? ( iso || '' ) : d.toLocaleString( [], { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' } );
    },

    /**
     * @description Name of a staff member
     * @param {string} userId - users.user_id
     * @returns {string}
     */
    staffName : function ( userId ) {
        for ( var i = 0; i < SC.staff.length; i++ ) { if ( SC.staff[ i ].user_id === userId ) { return SC.staff[ i ].name; } }
        return '';
    },

    /**
     * @description Options HTML of the staff list
     * @param {string} selected - user_id to select
     * @returns {string}
     */
    staffOptions : function ( selected ) {
        var html = '<option value="">Nobody yet</option>';
        $.each( SC.staff, function ( i, s ) {
            html += '<option value="' + SC.esc( s.user_id ) + '"' + ( s.user_id === selected ? ' selected' : '' ) + '>' + SC.esc( s.name ) + ( s.user_id === SC.me ? ' (me)' : '' ) + '</option>';
        } );
        return html;
    },

    /**
     * @description Fills the staff selects of the filter and the new case form
     * @returns {void}
     */
    fillStaffSelects : function () {
        $( '.sc-staff-select' ).html( SC.staffOptions( '' ) );
        var $filter = $( '#sc_assigned' );
        $.each( SC.staff, function ( i, s ) { $filter.append( $( '<option>' ).val( s.user_id ).text( s.name ) ); } );
    },

    /**
     * @description Warranty status badge class
     * @param {string} status - Active / Active (Extended) / Expired / No Warranty Found
     * @returns {string}
     */
    warrantyBadge : function ( status ) {
        if ( status === 'Active' || status === 'Active (Extended)' ) { return 'badge-light-success'; }
        if ( status === 'Expired' ) { return 'badge-light-danger'; }
        return 'badge-light-warning';
    },

    // ── Events ──────────────────────────────────────────────────

    /**
     * @description Binds every event of the page
     * @returns {void}
     */
    bindEvents : function () {
        // List filters
        $( '#sc_status, #sc_assigned' ).on( 'change', SC.loadList );
        $( '#sc_q' ).on( 'input', function () { clearTimeout( SC.listTimer ); SC.listTimer = setTimeout( SC.loadList, 350 ); } );
        $( '[data-sc-chip]' ).on( 'click', function () {
            var chip = $( this ).attr( 'data-sc-chip' );
            $( '#sc_status' ).val( chip === 'mine' ? 'active' : chip );
            $( '#sc_assigned' ).val( chip === 'mine' ? 'me' : '' );
            SC.loadList();
        } );
        $( '#sc_list' ).on( 'click', '[data-case-id]', function () { SC.openCase( +$( this ).attr( 'data-case-id' ) ); } );

        // New case: the wizard
        $( '#sc_new_case' ).on( 'click', function () { SCW.openNew(); } );

        // Case details (rebuilt on every load, so delegated)
        var $body = $( '#sc_case_body' );
        $body.on( 'click', '[data-sc-action]', function () { SC.caseAction( $( this ).attr( 'data-sc-action' ), $( this ) ); } );
        $body.on( 'change input', '#scd_assigned, #scd_visit_date, #scd_visit_time', function () { $( '#scd_save_visit' ).removeClass( 'd-none' ); } );
        $body.on( 'keydown', '#scd_add_serial', function ( e ) { if ( e.key === 'Enter' ) { e.preventDefault(); SC.caseAction( 'add-tv', $( this ) ); } } );
        $( '#sc_case_modal' ).on( 'hidden.bs.modal', function () {
            SC.current = null;
            SC.setUrl( null );
            SC.loadList();
        } );
    },

    /**
     * @description Keeps ?case=<id> in the address bar so the case can be shared or reopened after a refresh
     * @param {number|null} caseId - Open case, null for none
     * @returns {void}
     */
    setUrl : function ( caseId ) {
        var url = new URL( window.location.href );
        url.searchParams.delete( 'new_case_tv' );
        if ( caseId ) { url.searchParams.set( 'case', caseId ); } else { url.searchParams.delete( 'case' ); }
        window.history.replaceState( null, '', url.toString() );
    },

    /**
     * @description Opens what the address asks for: a case (?case=) or a new case for a TV (?new_case_tv=)
     * @returns {void}
     */
    openFromUrl : function () {
        var params = new URL( window.location.href ).searchParams;
        if ( params.get( 'case' ) ) { SC.openCase( +params.get( 'case' ) ); return; }
        if ( params.get( 'draft' ) ) { SCW.openDraft( +params.get( 'draft' ), SCW.readBackup() ); return; }
        if ( params.get( 'new_case_tv' ) ) {
            // From the Warranty page: a new case for that TV (customer from its sale, serial kept for the case)
            SC.post( { what_do_you_want: 'appstv_case_find_tv', product_sn_id: params.get( 'new_case_tv' ) } ).then( function ( info ) {
                var tv = info.data.tvs[ 0 ];
                SCW.openNew( function () {
                    if ( !tv ) { return; }
                    if ( tv.customer_id ) {
                        SCW.setCustomer( { customer_id: tv.customer_id, name: tv.customer_name, sub: 'From the sale of TV ' + tv.serial_number + ( tv.invoice_no ? ' (invoice ' + tv.invoice_no + ')' : '' ) } );
                    }
                    SCW.answers.serials = [ { product_sn_id: tv.product_sn_id, serial_number: tv.serial_number, label: tv.product_name + ' · ' + tv.warranty_status } ];
                    if ( tv.warranty_status === 'Active' || tv.warranty_status === 'Active (Extended)' ) { SCW.answers.warranty = 'under'; }
                    if ( tv.warranty_status === 'Expired' ) { SCW.answers.warranty = 'out'; }
                    if ( tv.product_id ) { SCW.answers.models = [ tv.product_id ]; }
                    SCW.renderTvs();
                    SCW.markDirty();
                } );
            } ).catch( SC.toastError );
        }
    },

    // ── List ────────────────────────────────────────────────────

    /**
     * @description Loads the cases for the current filters and draws them
     * @returns {void}
     */
    loadList : function () {
        var seq = ++SC.listSeq;
        SC.post( { what_do_you_want: 'appstv_case_list', status: $( '#sc_status' ).val(), assigned: $( '#sc_assigned' ).val(), q: $( '#sc_q' ).val().trim() } )
            .then( function ( info ) {
                if ( seq !== SC.listSeq ) { return; }
                var d = info.data;
                $.each( d.counts, function ( k, v ) { $( '#sc_count_' + k ).text( v ); } );
                $( '[data-sc-chip="overdue"]' ).toggleClass( 'is-zero', d.counts.overdue === 0 );
                $( '[data-sc-chip="today"]' ).toggleClass( 'is-zero', d.counts.today === 0 );
                $( '#sc_list' ).html( $.map( d.cases, SC.cardHtml ).join( '' ) );
                $( '#sc_empty' ).toggleClass( 'd-none', d.cases.length > 0 );
            } ).catch( SC.toastError );
    },

    /**
     * @description HTML of one case card
     * @param {object} c - Case from appstv_case_list
     * @returns {string}
     */
    cardHtml : function ( c ) {
        var due = '';
        if ( !c.visit_date && c.visit_to ) {
            // Visit window (wizard cases)
            var wl = { overdue: 'Visit deadline missed', today: 'Visit window', upcoming: 'Visit' }[ c.due ] || 'Visit';
            var wc = { overdue: 'badge-danger', today: 'badge-warning', upcoming: 'badge-light-info' }[ c.due ] || 'badge-light';
            due = '<span class="badge ' + wc + '"><i class="fa-regular fa-calendar me-1' + ( c.due === 'overdue' || c.due === 'today' ? ' text-white' : '' ) + '"></i>'
                + wl + ' · ' + SC.esc( SC.showDate( c.visit_from ) ) + ( c.visit_to !== c.visit_from ? ' – ' + SC.esc( SC.showDate( c.visit_to ) ) : '' ) + '</span>';
        }
        if ( c.visit_date ) {
            var label = { overdue: 'Overdue', today: 'Today', upcoming: '' }[ c.due ] || '';
            var cls = { overdue: 'badge-danger', today: 'badge-warning', upcoming: 'badge-light-info' }[ c.due ] || 'badge-light';
            due = '<span class="badge ' + cls + '"><i class="fa-regular fa-calendar me-1' + ( c.due === 'overdue' || c.due === 'today' ? ' text-white' : '' ) + '"></i>'
                + ( label ? label + ' · ' : '' ) + SC.esc( SC.showDate( c.visit_date ) ) + ( c.visit_time ? ' ' + SC.esc( c.visit_time ) : '' ) + '</span>';
        }
        return '<div class="col-12 col-md-6 col-xxl-4">'
            + '<div class="sc-card sc-due-' + ( c.due || 'none' ) + '" data-case-id="' + c.id + '" role="button" tabindex="0">'
            + '  <div class="d-flex flex-wrap gap-2 align-items-center mb-2">'
            + '    <span class="fw-bold text-gray-800">' + SC.esc( c.label || 'SC-' + c.id ) + '</span>'
            + ( c.tv_case_count ? '<span class="badge badge-light">' + c.tv_case_count + ' TV cases</span>' : ( c.tv_count > 1 ? '<span class="badge badge-light">' + c.tv_count + ' TVs</span>' : '' ) )
            + '    <span class="badge ' + SC.STATUS_BADGE[ c.status ] + '">' + SC.STATUS[ c.status ] + ( c.status === 'closed' && c.outcome ? ' · ' + SC.OUTCOME[ c.outcome ] : '' ) + '</span>'
            + '    ' + due
            + '  </div>'
            + '  <div class="fw-bold fs-5 text-gray-900">' + SC.esc( c.customer_name || '(no customer)' ) + '</div>'
            + ( c.contact_name || c.contact_phone ? '<div class="text-muted fs-7">' + SC.esc( [ c.contact_name, c.contact_phone ].filter( Boolean ).join( ' · ' ) ) + '</div>' : '' )
            + '  <div class="sc-card-problem">' + SC.esc( c.problem ) + '</div>'
            + '  <div class="d-flex flex-wrap gap-3 text-muted fs-7 mt-2">'
            + ( c.serials ? '<span class="font-monospace"><i class="fa-solid fa-tv me-1"></i>' + SC.esc( c.serials ) + '</span>' : '' )
            + '    <span><i class="fa-solid fa-user me-1"></i>' + SC.esc( ( c.staff_names && c.staff_names.length ? c.staff_names.join( ', ' ) : c.assigned_name ) || 'Not assigned' ) + '</span>'
            + ( c.note_count ? '<span><i class="fa-regular fa-comment me-1"></i>' + c.note_count + '</span>' : '' )
            + '  </div>'
            + '</div></div>';
    },

    // ── Case details ────────────────────────────────────────────

    /**
     * @description Loads a case and shows it
     * @param {number} caseId - The case
     * @returns {void}
     */
    openCase : function ( caseId ) {
        SC.post( { what_do_you_want: 'appstv_case_get', case_id: caseId } ).then( function ( info ) {
            SC.current = info.data;
            SC.renderCase();
            SC.setUrl( caseId );
            bootstrap.Modal.getOrCreateInstance( document.getElementById( 'sc_case_modal' ) ).show();
        } ).catch( SC.toastError );
    },

    /**
     * @description Reloads the open case (after a change)
     * @returns {void}
     */
    reloadCase : function () {
        if ( SC.current === null ) { return; }
        SC.post( { what_do_you_want: 'appstv_case_get', case_id: SC.current.case.id } ).then( function ( info ) {
            SC.current = info.data;
            SC.renderCase();
        } ).catch( SC.toastError );
    },

    /**
     * @description Draws the open case
     * @returns {void}
     */
    renderCase : function () {
        var d = SC.current, c = d.case;
        var closed = ( c.status === 'closed' );
        $( '#sc_case_title' ).html( SC.esc( c.label || 'SC-' + c.id ) + ' <span class="badge ' + SC.STATUS_BADGE[ c.status ] + ' fs-7 align-middle">' + SC.STATUS[ c.status ]
            + ( closed && c.outcome ? ' · ' + SC.OUTCOME[ c.outcome ] : '' ) + '</span>' );

        var tel = function ( phone ) { return phone ? '<a href="tel:' + SC.esc( phone.replace( /[^0-9+]/g, '' ) ) + '">' + SC.esc( phone ) + '</a>' : ''; };
        var html = '';

        // Status actions
        html += '<div class="d-flex flex-wrap gap-2 mb-5">';
        if ( c.status === 'open' || c.status === 'scheduled' ) { html += '<button type="button" class="btn btn-sm btn-light-warning" data-sc-action="visited"><i class="fa-solid fa-check"></i> Mark as visited</button>'; }
        if ( !closed ) { html += '<button type="button" class="btn btn-sm btn-success" data-sc-action="close"><i class="fa-solid fa-flag-checkered"></i> Close case</button>'; }
        if ( closed || c.status === 'visited' ) { html += '<button type="button" class="btn btn-sm btn-light" data-sc-action="reopen"><i class="fa-solid fa-rotate-left"></i> Reopen</button>'; }
        html += '</div>';

        html += '<div class="row g-5">';

        // Left: customer, problem, visit, TVs
        html += '<div class="col-12 col-lg-7">';
        html += '<div class="sc-section"><div class="sc-section-title">Customer</div>'
             + '<div class="fw-bold fs-5">' + SC.esc( c.customer_name ) + '</div>'
             + '<div class="text-muted fs-7">' + [ tel( c.customer_mobile ), SC.esc( c.customer_email ) ].filter( Boolean ).join( ' · ' ) + '</div>'
             + '<div class="mt-3" id="scd_contact_view"><span class="text-muted">Contact:</span> ' + ( SC.esc( c.contact_name ) || '—' ) + ( c.contact_phone ? ' · ' + tel( c.contact_phone ) : '' )
             + ( closed ? '' : ' <button type="button" class="btn btn-link btn-sm p-0 ms-2" data-sc-action="edit-contact">Edit</button>' ) + '</div>'
             + '<div class="row g-2 mt-1 d-none" id="scd_contact_edit">'
             + '  <div class="col-12 col-md-5"><input type="text" id="scd_contact_name" class="form-control form-control-sm form-control-solid" maxlength="200" placeholder="Contact person" value="' + SC.esc( c.contact_name ) + '"/></div>'
             + '  <div class="col-8 col-md-4"><input type="tel" id="scd_contact_phone" class="form-control form-control-sm form-control-solid" maxlength="50" placeholder="Phone" value="' + SC.esc( c.contact_phone ) + '"/></div>'
             + '  <div class="col-4 col-md-3"><button type="button" class="btn btn-sm btn-primary w-100" data-sc-action="save-contact">Save</button></div></div>'
             + '</div>';

        html += SC.complaintHtml( d );

        html += '<div class="sc-section"><div class="sc-section-title">Problem' + ( closed ? '' : ' <button type="button" class="btn btn-link btn-sm p-0 ms-2" data-sc-action="edit-problem">Edit</button>' ) + '</div>'
             + '<div id="scd_problem_view" class="sc-pre">' + SC.esc( c.problem ) + '</div>'
             + '<div id="scd_problem_edit" class="d-none"><textarea id="scd_problem" class="form-control form-control-solid mb-2" rows="3" maxlength="5000">' + SC.esc( c.problem ) + '</textarea>'
             + '<button type="button" class="btn btn-sm btn-primary" data-sc-action="save-problem">Save</button></div></div>';

        var wizardCase = ( c.staff && c.staff.length > 0 );    // Opened with the wizard: several staff, visit window
        html += '<div class="sc-section"><div class="sc-section-title">Visit</div>';
        if ( wizardCase ) {
            html += '<div class="sc-kv mb-3"><div class="sc-kv-k">Staff</div><div class="sc-kv-v">' + $.map( c.staff, function ( s ) { return SC.esc( s.name || s.user_id ); } ).join( ', ' ) + '</div>'
                 + '<div class="sc-kv-k">Visit window</div><div class="sc-kv-v">' + SC.esc( SC.showDate( c.visit_from ) ) + ( c.visit_to !== c.visit_from ? ' – ' + SC.esc( SC.showDate( c.visit_to ) ) : '' )
                 + ( !closed && c.visit_to && c.visit_to < d.today ? ' <span class="badge badge-danger ms-1">Deadline missed</span>' : '' ) + '</div></div>';
        }
        html += '<div class="row g-3 align-items-end">'
             + ( wizardCase ? '<input type="hidden" id="scd_assigned" value="' + SC.esc( c.assigned_user_id ) + '"/>'
                            : '<div class="col-12 col-md-5"><label class="form-label fs-7">Assigned to</label><select id="scd_assigned" class="form-select form-select-sm form-select-solid"' + ( closed ? ' disabled' : '' ) + '>' + SC.staffOptions( c.assigned_user_id ) + '</select></div>' )
             + '<div class="col-7 col-md-4"><label class="form-label fs-7">Date</label><input type="date" id="scd_visit_date" class="form-control form-control-sm form-control-solid" value="' + SC.esc( c.visit_date ) + '"' + ( closed ? ' disabled' : '' ) + '/></div>'
             + '<div class="col-5 col-md-3"><label class="form-label fs-7">Time</label><input type="time" id="scd_visit_time" class="form-control form-control-sm form-control-solid" value="' + SC.esc( c.visit_time ) + '"' + ( closed ? ' disabled' : '' ) + '/></div>'
             + '<div class="col-12"><button type="button" class="btn btn-sm btn-primary d-none" id="scd_save_visit" data-sc-action="save-visit">Save visit</button></div>'
             + '</div></div>';

        html += SC.tvCasesHtml( d );
        if ( c.kind !== 'main' || d.tvs.length ) {                // A main case's TVs are on its TV cases
        html += '<div class="sc-section"><div class="sc-section-title">TVs</div>';
        if ( d.tvs.length === 0 ) { html += '<div class="text-muted fs-7 mb-3">No TV on this case yet.</div>'; }
        $.each( d.tvs, function ( i, tv ) { html += SC.tvHtml( tv, c, closed ); } );
        if ( !closed ) {
            html += '<div class="input-group input-group-sm mt-3"><input type="text" id="scd_add_serial" class="form-control form-control-solid font-monospace" placeholder="Add a TV: serial number" autocomplete="off" autocapitalize="characters"/>'
                 + '<button type="button" class="btn btn-light-primary" data-sc-action="add-tv">Add</button>'
                 + '<button type="button" class="btn btn-primary" data-sc-action="scan-tv"><i class="fa-solid fa-camera"></i></button></div>';
        }
        html += '</div>';
        }
        html += '</div>';

        // Right: timeline
        html += '<div class="col-12 col-lg-5"><div class="sc-section"><div class="sc-section-title">Notes and history</div>'
             + '<textarea id="scd_note" class="form-control form-control-solid mb-2" rows="2" maxlength="5000" placeholder="Add a note (call log, findings, parts needed…)"></textarea>'
             + '<button type="button" class="btn btn-sm btn-light-primary mb-4" data-sc-action="add-note">Add note</button>'
             + '<div class="sc-timeline">';
        var icons = { created: 'fa-folder-plus', note: 'fa-comment', status: 'fa-check', assign: 'fa-user', visit: 'fa-calendar', tv: 'fa-tv', service: 'fa-screwdriver-wrench', close: 'fa-flag-checkered', reopen: 'fa-rotate-left' };
        $.each( d.notes, function ( i, n ) {
            html += '<div class="sc-tl-item sc-tl-' + SC.esc( n.type ) + '"><div class="sc-tl-icon"><i class="fa-solid ' + ( icons[ n.type ] || 'fa-circle' ) + '"></i></div>'
                 + '<div class="sc-tl-body"><div class="sc-pre">' + SC.esc( n.note ) + '</div><div class="text-muted fs-8">' + SC.esc( n.by || 'Someone' ) + ' · ' + SC.esc( SC.showStamp( n.on ) ) + '</div></div></div>';
        } );
        html += '</div></div>'
             + '<div class="sc-section"><div class="sc-section-title">Change history</div><div class="sc-history">' + SCH.listHtml( d.history ) + '</div></div>'
             + '<div class="text-muted fs-8">Opened by ' + SC.esc( c.created_by || '—' ) + ' on ' + SC.esc( SC.showStamp( c.created_on ) )
             + ( closed ? '<br/>Closed by ' + SC.esc( c.closed_by || '—' ) + ' on ' + SC.esc( SC.showStamp( c.closed_on ) ) : '' ) + '</div>'
             + '</div>';

        html += '</div>';
        $( '#sc_case_body' ).html( html );
    },

    /**
     * @description HTML of the complaint details of a wizard case (complainant, when / how, TVs, models, warranty, problems)
     * @param {object} d - Data of appstv_case_get
     * @returns {string} '' for older cases
     */
    complaintHtml : function ( d ) {
        var c = d.case;
        if ( !c.complained_on ) { return ''; }
        var tags = function ( values, labels ) {
            return '<div class="sc-tags">' + $.map( values, function ( v ) { return '<span class="badge badge-light-primary">' + SC.esc( labels[ v ] || v ) + '</span>'; } ).join( '' ) + '</div>';
        };
        var rows = [
            [ 'Complained by', SC.esc( [ c.contact_name, c.contact_position ].filter( Boolean ).join( ', ' ) )
                + ( c.contact_email ? ' · <a href="mailto:' + SC.esc( c.contact_email ) + '">' + SC.esc( c.contact_email ) + '</a>' : '' ) ],
            [ 'When / how', SC.esc( SC.showDate( c.complained_on.substr( 0, 10 ) ) + ' ' + c.complained_on.substr( 11, 5 ) + ' SGT' ) + ( c.channels.length ? tags( c.channels, SCW.CHANNELS ) : '' ) ],
            [ 'TVs', c.kind === 'tv' ? 'This TV (TV case ' + c.tv_index + ')' : c.tv_count + ( c.kind === 'main' ? ' · one case per TV' : ( c.tv_count > 1 ? ' · one case for all' : '' ) ) ],
            [ 'Models', SC.esc( $.map( c.models, function ( m ) { return m.name; } ).join( ', ' ) ) ],
            [ 'Warranty', SC.esc( SCW.WARRANTY[ c.warranty_status ] || c.warranty_status ) ],
            [ 'Problem', c.problem_types.length ? tags( c.problem_types, SCW.PROBLEMS ) : '' ]
        ];
        return '<div class="sc-section"><div class="sc-section-title">Complaint</div><div class="sc-kv">'
             + $.map( rows, function ( r ) { return r[ 1 ] ? '<div class="sc-kv-k">' + r[ 0 ] + '</div><div class="sc-kv-v">' + r[ 1 ] + '</div>' : ''; } ).join( '' )
             + '</div></div>';
    },

    /**
     * @description HTML of the TV cases of a main case (or of the main case and the other TV cases, for a TV case)
     * @param {object} d - Data of appstv_case_get
     * @returns {string} '' when the case has no TV cases
     */
    tvCasesHtml : function ( d ) {
        var c = d.case;
        if ( !d.tv_cases || !d.tv_cases.length ) { return ''; }
        var html = '<div class="sc-section"><div class="sc-section-title">' + ( c.kind === 'tv' ? 'Main case and TV cases' : 'TV cases' ) + '</div>';
        if ( c.kind === 'tv' ) {
            html += '<button type="button" class="sc-tv-case" data-sc-action="open-case" data-open-id="' + c.parent_case_id + '"><span class="fw-bold">SC-' + c.parent_case_id + '</span><span class="text-muted fs-7">Main case</span></button>';
        }
        $.each( d.tv_cases, function ( i, t ) {
            var current = ( t.id === c.id );
            html += '<button type="button" class="sc-tv-case' + ( current ? ' is-current' : '' ) + '" data-sc-action="open-case" data-open-id="' + t.id + '"' + ( current ? ' disabled' : '' ) + '>'
                 + '<span><span class="fw-bold">' + SC.esc( t.label ) + '</span> <span class="font-monospace text-muted fs-7 ms-2">' + SC.esc( t.serials || 'Serial pending' ) + '</span></span>'
                 + '<span class="d-flex gap-1"><span class="badge badge-light">' + SC.esc( SCW.WARRANTY[ t.warranty_status ] || t.warranty_status ) + '</span>'
                 + '<span class="badge ' + SC.STATUS_BADGE[ t.status ] + '">' + SC.STATUS[ t.status ] + '</span></span></button>';
        } );
        return html + '</div>';
    },

    /**
     * @description HTML of one TV of the open case (warranty, chain, recent service, actions)
     * @param {object} tv - { id, serial_number, product_sn_id, pwrp_id, chain }
     * @param {object} c - The case
     * @param {boolean} closed - True when the case is closed
     * @returns {string}
     */
    tvHtml : function ( tv, c, closed ) {
        var ch = tv.chain;
        var html = '<div class="sc-tv">';
        html += '<div class="d-flex flex-wrap align-items-center gap-2"><span class="font-monospace fw-bold fs-5">' + SC.esc( tv.serial_number ) + '</span>';
        if ( ch === null ) {
            html += '<span class="badge badge-light-warning">Not in the system</span></div>';
        } else {
            html += '<span class="badge ' + SC.warrantyBadge( ch.warranty_status ) + '">' + SC.esc( ch.warranty_status ) + '</span>';
            if ( tv.pwrp_id ) { html += '<span class="badge badge-light-success">Service recorded on this case</span>'; }
            html += '</div><div class="text-muted fs-7">' + SC.esc( ch.product_name );
            if ( ch.warranty_expiry ) { html += ' · warranty until ' + SC.esc( SC.showDate( ch.warranty_expiry ) ) + ( ch.extended_expiry ? ', extended until ' + SC.esc( SC.showDate( ch.extended_expiry ) ) : '' ); }
            html += '</div>';
            if ( ch.role === 'replacement' ) { html += '<div class="fs-7 text-muted">Replacement TV under the warranty of the original TV</div>'; }
            if ( ch.customer_id && ch.customer_id !== c.customer_id ) { html += '<div class="fs-7 text-warning">Sold to ' + SC.esc( ch.customer_name ) + ', not to this customer</div>'; }
            if ( !ch.is_current ) { html += '<div class="fs-7 text-warning">Already replaced by ' + SC.esc( ch.current_serial_number ) + '</div>'; }
            var events = ch.events.slice( -3 );
            if ( events.length ) {
                html += '<div class="sc-tv-events">';
                $.each( events, function ( i, e ) {
                    html += '<div><i class="fa-solid ' + ( e.type === 'repair' ? 'fa-screwdriver-wrench' : 'fa-right-left' ) + ' me-1"></i>' + SC.esc( SC.showDate( e.date ) ) + ' · '
                         + ( e.type === 'repair' ? 'Repair' : 'Replaced by <span class="font-monospace">' + SC.esc( e.new_serial_number ) + '</span>' )
                         + ( e.ref_no ? ' · Ref ' + SC.esc( e.ref_no ) : '' ) + ( e.remarks ? ' · ' + SC.esc( e.remarks ) : '' ) + '</div>';
                } );
                html += '</div>';
            }
        }
        if ( !closed ) {
            html += '<div class="d-flex flex-wrap gap-2 mt-2">';
            if ( ch !== null && ch.psnw_id && ch.is_current ) { html += '<button type="button" class="btn btn-sm btn-primary" data-sc-action="service" data-tv-index="' + SC.current.tvs.indexOf( tv ) + '"><i class="fa-solid fa-screwdriver-wrench"></i> Record service</button>'; }
            if ( ch !== null && ch.psnw_id && ch.warranty_expiry && !ch.has_extended ) { html += '<button type="button" class="btn btn-sm btn-light-success" data-sc-action="extend" data-tv-index="' + SC.current.tvs.indexOf( tv ) + '"><i class="fa-solid fa-shield-halved"></i> Extended warranty</button>'; }
            if ( !tv.pwrp_id ) { html += '<button type="button" class="btn btn-sm btn-light-danger" data-sc-action="remove-tv" data-tv-id="' + tv.id + '"><i class="fa-solid fa-xmark"></i> Remove</button>'; }
            html += '</div>';
        }
        html += '</div>';
        return html;
    },

    /**
     * @description Sends a change of the open case and reloads it
     * @param {object} data - Request data (what_do_you_want, ...)
     * @returns {Promise}
     */
    caseCall : function ( data ) {
        data.case_id = SC.current.case.id;
        return SC.post( data ).then( function ( info ) {
            showSimpleToast( 'success', info.message );
            SC.reloadCase();
            return info;
        } ).catch( function ( message ) {
            Swal.fire( { icon: 'error', title: 'Not saved', text: message, buttonsStyling: false, customClass: { confirmButton: 'btn btn-danger' } } );
            throw message;
        } );
    },

    /**
     * @description Handles a button of the case details
     * @param {string} action - data-sc-action value
     * @param {jQuery} $el - The button
     * @returns {void}
     */
    caseAction : function ( action, $el ) {
        var c = SC.current.case;
        var update = function ( changes ) { return SC.caseCall( { what_do_you_want: 'appstv_case_update', changes: JSON.stringify( changes ) } ); };
        var tv = SC.current.tvs[ +$el.attr( 'data-tv-index' ) ];

        if ( action === 'open-case' ) { SC.openCase( +$el.attr( 'data-open-id' ) ); return; }
        if ( action === 'edit-contact' ) { $( '#scd_contact_view' ).addClass( 'd-none' ); $( '#scd_contact_edit' ).removeClass( 'd-none' ); }
        if ( action === 'save-contact' ) { update( { contact_name: $( '#scd_contact_name' ).val(), contact_phone: $( '#scd_contact_phone' ).val() } ).catch( function () {} ); }
        if ( action === 'edit-problem' ) { $( '#scd_problem_view' ).addClass( 'd-none' ); $( '#scd_problem_edit' ).removeClass( 'd-none' ); }
        if ( action === 'save-problem' ) { update( { problem: $( '#scd_problem' ).val() } ).catch( function () {} ); }
        if ( action === 'save-visit' ) {
            if ( !$( '#scd_visit_date' ).val() ) { $( '#scd_visit_time' ).val( '' ); }
            update( { assigned_user_id: $( '#scd_assigned' ).val(), visit_date: $( '#scd_visit_date' ).val(), visit_time: $( '#scd_visit_time' ).val() } ).catch( function () {} );
        }
        if ( action === 'visited' ) { update( { status: 'visited' } ).catch( function () {} ); }
        if ( action === 'reopen' ) {
            showConfirmSweetAlert( 'Reopen SC-' + c.id + '?', 'The case becomes active again.', 'question', 'Reopen', 'btn btn-primary', 'Cancel', 'btn btn-light',
                function () { update( { status: 'open' } ).catch( function () {} ); } );
        }
        if ( action === 'close' ) { SC.closeCase(); }
        if ( action === 'add-note' ) {
            var note = $( '#scd_note' ).val().trim();
            if ( !note ) { showSimpleToast( 'warning', 'Write the note first.' ); return; }
            SC.caseCall( { what_do_you_want: 'appstv_case_add_note', note: note } ).catch( function () {} );
        }
        if ( action === 'add-tv' ) { SC.addCaseTv( $( '#scd_add_serial' ).val() ); }
        if ( action === 'scan-tv' ) {
            AppstvScanner.open( { title: 'Scan the TV', continuous: false, onScan: function ( serial ) { SC.addCaseTv( serial ); } } );
        }
        if ( action === 'remove-tv' ) {
            showConfirmSweetAlert( 'Remove this TV from the case?', '', 'warning', 'Remove', 'btn btn-danger', 'Cancel', 'btn btn-light',
                function () { SC.caseCall( { what_do_you_want: 'appstv_case_set_tv', remove_id: $el.attr( 'data-tv-id' ) } ).catch( function () {} ); } );
        }
        if ( action === 'service' ) {
            AppstvServiceForms.recordService( { product_sn_id: tv.product_sn_id, serial_number: tv.serial_number, product_id: tv.chain.product_id, product_name: tv.chain.product_name, case_id: c.id },
                function () { SC.reloadCase(); } );
        }
        if ( action === 'extend' ) {
            AppstvServiceForms.addExtended( { product_sn_id: tv.product_sn_id, serial_number: tv.serial_number, warranty_expiry: tv.chain.warranty_expiry, case_id: c.id },
                function () { SC.reloadCase(); } );
        }
    },

    /**
     * @description Adds a TV to the open case: one match is added, several are offered, none can be added as "not in the system"
     * @param {string} raw - Serial number
     * @returns {void}
     */
    addCaseTv : function ( raw ) {
        var serial = AppstvScanner.cleanSerial( raw );
        if ( !serial ) { showSimpleToast( 'warning', 'Enter a serial number (letters and digits).' ); return; }
        var add = function ( productSnId ) {
            SC.caseCall( { what_do_you_want: 'appstv_case_set_tv', product_sn_id: productSnId, serial_number: serial } ).catch( function () {} );
        };
        SC.post( { what_do_you_want: 'appstv_case_find_tv', serial_number: serial } ).then( function ( info ) {
            var tvs = info.data.tvs;
            if ( tvs.length === 1 ) { add( tvs[ 0 ].product_sn_id ); return; }
            if ( tvs.length === 0 ) {
                showConfirmSweetAlert( 'Serial number not found', 'No TV with serial number ' + SC.esc( serial ) + ' is in the system. Add it to the case anyway?', 'question',
                    'Add anyway', 'btn btn-warning', 'Cancel', 'btn btn-light', function () { add( '' ); } );
                return;
            }
            var options = {};
            var mine = '';
            $.each( tvs, function ( i, t ) {
                options[ t.product_sn_id ] = ( t.customer_name || 'No customer' ) + ' · ' + t.product_name + ' · ' + t.warranty_status;
                if ( t.customer_id === SC.current.case.customer_id ) { mine = t.product_sn_id; }
            } );
            Swal.fire( { title: tvs.length + ' TVs carry this serial number', text: 'Which one is it?', input: 'select', inputOptions: options, inputValue: mine,
                         showCancelButton: true, confirmButtonText: 'Add', buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' } } )
                .then( function ( r ) { if ( r.isConfirmed ) { add( r.value ); } } );
        } ).catch( SC.toastError );
    },

    /**
     * @description Asks how the case ended and closes it
     * @returns {void}
     */
    closeCase : function () {
        var c = SC.current.case;
        var html = '<div class="text-start"><label class="form-label required">Outcome</label>'
            + '<select id="scc_outcome" class="form-select form-select-solid mb-4"><option value="">Choose…</option>'
            + '<option value="repaired">Repaired</option><option value="replaced">Replaced</option>'
            + '<option value="no_fault">No fault found</option><option value="not_covered">Not covered by warranty</option></select>'
            + '<div class="form-text mb-4">Repaired / Replaced: record the service on the TV first (Record service).</div>'
            + '<label class="form-label">Closing note</label><textarea id="scc_note" class="form-control form-control-solid" rows="2" maxlength="5000"></textarea></div>';
        Swal.fire( {
            title : 'Close SC-' + c.id,
            html : html,
            showCancelButton : true,
            confirmButtonText : 'Close case',
            buttonsStyling : false,
            focusConfirm : false,
            customClass : { confirmButton: 'btn btn-success', cancelButton: 'btn btn-light' },
            preConfirm : function () {
                var outcome = $( '#scc_outcome' ).val();
                if ( !outcome ) { Swal.showValidationMessage( 'Choose the outcome.' ); return false; }
                return SC.post( { what_do_you_want: 'appstv_case_close', case_id: c.id, outcome: outcome, note: $( '#scc_note' ).val().trim() } )
                    .catch( function ( message ) { Swal.showValidationMessage( message ); return false; } );
            }
        } ).then( function ( r ) {
            if ( r.isConfirmed && r.value ) { showSimpleToast( 'success', r.value.message ); SC.reloadCase(); }
        } );
    }
};

$( document ).ready( function () {
    SC.init();
} );
