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
    tab : 'overview',                                          // Tab shown in the case window
    gallery : null,                                            // Photos / videos of the open case (SCMedia)
    NOTE_TYPES : [ 'note', 'status', 'service', 'close', 'reopen' ],   // Timeline lines shown on the Notes tab (the rest is in History)
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
        SC.gallery = new SCMedia( $( '#sc_case_media' ), {
            target : function () { return { case_id: SC.current.case.id }; },
            onChange : function ( media ) { $( '[data-tab-n="media"]' ).text( media.length ); }
        } );
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
        var $body = $( '#sc_case_modal' );
        $body.on( 'click', '[data-sc-tab]', function () { SC.showTab( $( this ).attr( 'data-sc-tab' ) ); } );
        $body.on( 'hide.bs.modal', function ( e ) {
            if ( e.target.id === 'sc_case_modal' && SC.gallery.busy() ) {
                e.preventDefault();
                SC.showTab( 'media' );
                Swal.fire( { icon: 'warning', title: 'Photos / videos not uploaded yet', text: 'Upload them (or remove them) before closing the case.', buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
            }
        } );
        $body.on( 'click', '[data-sc-action]', function () { SC.caseAction( $( this ).attr( 'data-sc-action' ), $( this ) ); } );
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
        if ( SC.gallery.busy() && SC.current && SC.current.case.id !== caseId ) {
            SC.showTab( 'media' );
            showSimpleToast( 'warning', 'Upload (or remove) the waiting photos / videos first.' );
            return;
        }
        SC.post( { what_do_you_want: 'appstv_case_get', case_id: caseId } ).then( function ( info ) {
            if ( !SC.current || SC.current.case.id !== caseId ) { SC.tab = 'overview'; }
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
     * @description Draws the open case: header (number, status, customer, actions), key tiles, tabs
     * @returns {void}
     */
    renderCase : function () {
        var d = SC.current, c = d.case;
        var closed = ( c.status === 'closed' );
        var due = SC.dueInfo( c, d.today );

        // ── Header ──
        var actions = '<button type="button" class="btn btn-sm btn-light" data-sc-action="history"><i class="fa-solid fa-clock-rotate-left"></i><span class="d-none d-md-inline ms-1">History</span></button>';
        if ( c.status === 'open' || c.status === 'scheduled' ) { actions += '<button type="button" class="btn btn-sm btn-light-warning" data-sc-action="visited"><i class="fa-solid fa-check"></i><span class="ms-1">Mark as visited</span></button>'; }
        if ( !closed ) { actions += '<button type="button" class="btn btn-sm btn-success" data-sc-action="close"><i class="fa-solid fa-flag-checkered"></i><span class="ms-1">Close case</span></button>'; }
        if ( closed || c.status === 'visited' ) { actions += '<button type="button" class="btn btn-sm btn-light-primary" data-sc-action="reopen"><i class="fa-solid fa-rotate-left"></i><span class="ms-1">Reopen</span></button>'; }
        var context = '';
        if ( c.kind === 'main' ) { context = d.tv_cases.length + ' TV cases'; }
        if ( c.kind === 'tv' ) { context = 'TV case ' + c.tv_index + ' of <button type="button" class="btn btn-link p-0 fs-6 align-baseline" data-sc-action="open-case" data-open-id="' + c.parent_case_id + '">SC-' + c.parent_case_id + '</button>'; }
        $( '#sc_case_head' ).html(
            '<div class="sc-head-main">'
            + '<div class="d-flex flex-wrap align-items-center gap-2"><h2 class="sc-head-no mb-0" id="sc_case_label">' + SC.esc( c.label ) + '</h2>'
            + '<span class="badge ' + SC.STATUS_BADGE[ c.status ] + ' fs-7">' + SC.STATUS[ c.status ] + ( closed && c.outcome ? ' · ' + SC.OUTCOME[ c.outcome ] : '' ) + '</span>'
            + ( due.badge ? '<span class="badge ' + due.badgeClass + ' fs-7">' + SC.esc( due.badge ) + '</span>' : '' ) + '</div>'
            + '<div class="sc-head-customer">' + SC.esc( c.customer_name ) + ( context ? '<span class="sc-head-context">' + context + '</span>' : '' ) + '</div>'
            + '</div>'
            + '<div class="sc-head-actions">' + actions
            + '<button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-1" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fs-2"></i></button></div>' );

        // ── Key tiles ──
        var tel = function ( phone ) { return phone ? '<a class="sc-tile-link" href="tel:' + SC.esc( phone.replace( /[^0-9+]/g, '' ) ) + '"><i class="fa-solid fa-phone me-1"></i>' + SC.esc( phone ) + '</a>' : ''; };
        var tiles = '';
        tiles += SC.tileHtml( 'fa-user', 'Person who complained',
            '<div id="scd_contact_view"><div class="sc-tile-value">' + ( SC.esc( c.contact_name ) || '—' ) + '</div>'
            + ( c.contact_position ? '<div class="sc-tile-sub">' + SC.esc( c.contact_position ) + '</div>' : '' )
            + '<div class="sc-tile-links">' + tel( c.contact_phone ) + ( c.contact_email ? '<a class="sc-tile-link" href="mailto:' + SC.esc( c.contact_email ) + '"><i class="fa-regular fa-envelope me-1"></i>E-mail</a>' : '' ) + '</div></div>'
            + '<div id="scd_contact_edit" class="d-none">'
            + '<input type="text" id="scd_contact_name" class="form-control form-control-sm form-control-solid mb-2" maxlength="200" placeholder="Name" value="' + SC.esc( c.contact_name ) + '"/>'
            + '<input type="tel" id="scd_contact_phone" class="form-control form-control-sm form-control-solid mb-2" maxlength="50" placeholder="Phone" value="' + SC.esc( c.contact_phone ) + '"/>'
            + '<div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-light" data-sc-action="cancel-edit">Cancel</button><button type="button" class="btn btn-sm btn-primary" data-sc-action="save-contact">Save</button></div></div>',
            closed ? '' : '<button type="button" class="btn btn-link btn-sm p-0" data-sc-action="edit-contact">Edit</button>' );

        var visit = '<div id="scd_visit_view">';
        if ( c.visit_to ) {
            visit += '<div class="sc-tile-value">' + SC.esc( SC.showDate( c.visit_from ) ) + ( c.visit_to !== c.visit_from ? ' – ' + SC.esc( SC.showDate( c.visit_to ) ) : '' ) + '</div>';
        }
        visit += ( due.text ? '<div class="sc-tile-sub ' + due.textClass + '">' + SC.esc( due.text ) + '</div>' : '' )
              + ( c.visit_date ? '<div class="sc-tile-sub"><i class="fa-regular fa-calendar-check me-1"></i>Planned: ' + SC.esc( SC.showDate( c.visit_date ) ) + ( c.visit_time ? ' ' + SC.esc( c.visit_time ) : '' ) + '</div>' : '' )
              + ( !c.visit_to && !c.visit_date ? '<div class="sc-tile-value text-muted">Not planned</div>' : '' ) + '</div>'
              + '<div id="scd_visit_edit" class="d-none"><div class="d-flex gap-2 mb-2">'
              + '<input type="date" id="scd_visit_date" class="form-control form-control-sm form-control-solid" value="' + SC.esc( c.visit_date ) + '" aria-label="Visit date"/>'
              + '<input type="time" id="scd_visit_time" class="form-control form-control-sm form-control-solid sc-time" value="' + SC.esc( c.visit_time ) + '" aria-label="Visit time"/></div>'
              + '<input type="hidden" id="scd_assigned" value="' + SC.esc( c.assigned_user_id ) + '"/>'
              + '<div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-light" data-sc-action="cancel-edit">Cancel</button><button type="button" class="btn btn-sm btn-primary" data-sc-action="save-visit">Save</button></div></div>';
        tiles += SC.tileHtml( 'fa-calendar-days', 'Visit', visit, closed ? '' : '<button type="button" class="btn btn-link btn-sm p-0" data-sc-action="edit-visit">' + ( c.visit_date ? 'Change date' : 'Plan the visit' ) + '</button>', due.tileClass );

        var staff = ( c.staff && c.staff.length ) ? c.staff.map( function ( s ) { return s.name || s.user_id; } ) : ( SC.staffName( c.assigned_user_id ) ? [ SC.staffName( c.assigned_user_id ) ] : [] );
        tiles += SC.tileHtml( 'fa-user-gear', 'Assigned to', staff.length ? '<div class="sc-tile-value">' + SC.esc( staff.join( ', ' ) ) + '</div>' : '<div class="sc-tile-value text-muted">Nobody yet</div>', '' );

        var tvsText = c.kind === 'tv' ? 'This TV' : c.tv_count + ' TV' + ( c.tv_count > 1 ? 's' : '' );
        tiles += SC.tileHtml( 'fa-shield-halved', 'Warranty',
            '<div class="sc-tile-value">' + SC.esc( SCW.WARRANTY[ c.warranty_status ] || 'Not known' ) + '</div>'
            + '<div class="sc-tile-sub">' + SC.esc( tvsText ) + ( c.models.length ? ' · ' + SC.esc( SC.modelsShort( c.models ) ) : '' ) + '</div>', '' );
        $( '#sc_case_tiles' ).html( tiles );

        // ── Tabs ──
        var tvCount = c.kind === 'main' ? d.tv_cases.length : d.tvs.length;
        var notes = d.notes.filter( function ( n ) { return SC.NOTE_TYPES.indexOf( n.type ) !== -1; } );
        var tabs = [ [ 'overview', 'Overview', null ], [ 'tvs', 'TVs', tvCount ], [ 'media', 'Photos & videos', d.media.length ], [ 'notes', 'Notes', notes.length ] ];
        $( '#sc_case_tabs' ).html( $.map( tabs, function ( t ) {
            return '<button type="button" class="sc-tab' + ( SC.tab === t[ 0 ] ? ' is-active' : '' ) + '" role="tab" aria-selected="' + ( SC.tab === t[ 0 ] ) + '" data-sc-tab="' + t[ 0 ] + '">'
                 + SC.esc( t[ 1 ] ) + ( t[ 2 ] !== null ? '<span class="sc-tab-n" data-tab-n="' + t[ 0 ] + '">' + t[ 2 ] + '</span>' : '' ) + '</button>';
        } ).join( '' ) );
        SC.showTab( SC.tab );

        // Overview: problem, complaint, customer
        var chips = c.problem_types.length ? '<div class="sc-tags mb-3">' + $.map( c.problem_types, function ( p ) { return '<span class="sc-problem-chip">' + SC.esc( SCW.PROBLEMS[ p ] || p ) + '</span>'; } ).join( '' ) + '</div>' : '';
        var overview = '<div class="sc-card-box"><div class="sc-box-head"><div class="sc-box-title">Problem</div>' + ( closed ? '' : '<button type="button" class="btn btn-link btn-sm p-0" data-sc-action="edit-problem">Edit</button>' ) + '</div>'
            + chips + '<div id="scd_problem_view" class="sc-problem-text sc-pre">' + SC.esc( c.problem ) + '</div>'
            + '<div id="scd_problem_edit" class="d-none"><textarea id="scd_problem" class="form-control form-control-solid mb-2" rows="4" maxlength="5000">' + SC.esc( c.problem ) + '</textarea>'
            + '<div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-light" data-sc-action="cancel-edit">Cancel</button><button type="button" class="btn btn-sm btn-primary" data-sc-action="save-problem">Save</button></div></div></div>';
        var facts = [];
        if ( c.complained_on ) {
            facts.push( [ 'Complained', SC.esc( SC.showDate( c.complained_on.substr( 0, 10 ) ) + ' ' + c.complained_on.substr( 11, 5 ) + ' SGT' )
                + ( c.channels.length ? ' · by ' + SC.esc( $.map( c.channels, function ( ch ) { return SCW.CHANNELS[ ch ] || ch; } ).join( ', ' ) ) : '' ) ] );
        }
        facts.push( [ 'Customer', SC.esc( c.customer_name ) + ( c.customer_mobile ? ' · ' + tel( c.customer_mobile ) : '' ) + ( c.customer_email ? ' · <a href="mailto:' + SC.esc( c.customer_email ) + '">' + SC.esc( c.customer_email ) + '</a>' : '' ) ] );
        if ( c.models.length ) { facts.push( [ 'TV models', SC.esc( $.map( c.models, function ( m ) { return m.name; } ).join( ', ' ) ) ] ); }
        facts.push( [ 'Opened', SC.esc( ( c.created_by || '—' ) + ', ' + SC.showStamp( c.created_on ) ) ] );
        if ( closed ) { facts.push( [ 'Closed', SC.esc( ( c.closed_by || '—' ) + ', ' + SC.showStamp( c.closed_on ) ) ] ); }
        overview += '<div class="sc-card-box"><div class="sc-box-head"><div class="sc-box-title">Details</div></div><div class="sc-facts">'
            + $.map( facts, function ( f ) { return '<div class="sc-fact-k">' + f[ 0 ] + '</div><div class="sc-fact-v">' + f[ 1 ] + '</div>'; } ).join( '' ) + '</div></div>';
        $( '#sc_pane_overview' ).html( overview );

        // TVs
        var tvsHtml = SC.tvCasesHtml( d );
        if ( c.kind !== 'main' ) {
            tvsHtml += '<div class="sc-card-box"><div class="sc-box-head"><div class="sc-box-title">' + ( c.kind === 'tv' ? 'This TV' : 'TVs on this case' ) + '</div></div>';
            if ( d.tvs.length === 0 ) { tvsHtml += '<div class="text-muted mb-3">' + ( c.kind === 'tv' ? 'Serial number pending: scan it on site.' : 'No TV on this case yet.' ) + '</div>'; }
            $.each( d.tvs, function ( i, tv ) { tvsHtml += SC.tvHtml( tv, c, closed ); } );
            if ( !closed ) {
                tvsHtml += '<div class="input-group mt-3 sc-add-tv"><input type="text" id="scd_add_serial" class="form-control form-control-solid font-monospace" placeholder="Add a TV: serial number" autocomplete="off" autocapitalize="characters" aria-label="Serial number"/>'
                         + '<button type="button" class="btn btn-light-primary" data-sc-action="add-tv">Add</button>'
                         + '<button type="button" class="btn btn-primary" data-sc-action="scan-tv" aria-label="Scan"><i class="fa-solid fa-camera"></i></button></div>';
            }
            tvsHtml += '</div>';
        }
        $( '#sc_pane_tvs' ).html( tvsHtml );

        // Photos & videos (persistent pane; the gallery is reset only when another case is shown)
        if ( SC.gallery.caseId !== c.id ) {
            SC.gallery.caseId = c.id;
            SC.gallery.queue = [];
            SC.gallery.drawQueue();
        }
        SC.gallery.options.editable = !closed;
        SC.gallery.$box.find( '.scm-add' ).toggleClass( 'd-none', closed );
        SC.gallery.setMedia( d.media );

        // Notes
        var icons = { note: 'fa-comment', status: 'fa-check', service: 'fa-screwdriver-wrench', close: 'fa-flag-checkered', reopen: 'fa-rotate-left' };
        var notesHtml = '<div class="sc-card-box"><textarea id="scd_note" class="form-control form-control-solid mb-2" rows="2" maxlength="5000" placeholder="Add a note: call log, findings, parts needed…" aria-label="Note"></textarea>'
            + '<div class="text-end"><button type="button" class="btn btn-sm btn-primary" data-sc-action="add-note">Add note</button></div></div>';
        if ( notes.length === 0 ) { notesHtml += '<div class="text-muted text-center py-6">No notes yet.</div>'; }
        notesHtml += '<div class="sc-timeline">';
        $.each( notes, function ( i, n ) {
            notesHtml += '<div class="sc-tl-item sc-tl-' + SC.esc( n.type ) + '"><div class="sc-tl-icon"><i class="fa-solid ' + ( icons[ n.type ] || 'fa-circle' ) + '"></i></div>'
                + '<div class="sc-tl-body"><div class="sc-pre">' + SC.esc( n.note ) + '</div><div class="text-muted fs-8 mt-1">' + SC.esc( n.by || 'Someone' ) + ' · ' + SC.esc( SC.showStamp( n.on ) ) + '</div></div></div>';
        } );
        $( '#sc_pane_notes' ).html( notesHtml + '</div>' );
    },

    /**
     * @description HTML of one key tile
     * @param {string} icon - Font Awesome icon (fa-...)
     * @param {string} title - Small title
     * @param {string} body - Inner HTML
     * @param {string} action - Link / button HTML next to the title ('' = none)
     * @param {string} [extraClass] - is-danger / is-warning / is-ok
     * @returns {string}
     */
    tileHtml : function ( icon, title, body, action, extraClass ) {
        return '<div class="sc-tile ' + ( extraClass || '' ) + '"><div class="sc-tile-head"><span class="sc-tile-title"><i class="fa-solid ' + icon + ' me-2"></i>' + SC.esc( title ) + '</span>' + action + '</div>'
             + '<div class="sc-tile-body">' + body + '</div></div>';
    },

    /**
     * @description Where the case stands against its visit window / planned visit
     * @param {object} c - The case
     * @param {string} today - 'YYYY-MM-DD' (Singapore)
     * @returns {object} badge, badgeClass (header), text, textClass (tile), tileClass
     */
    dueInfo : function ( c, today ) {
        var none = { badge: '', badgeClass: '', text: '', textClass: '', tileClass: '' };
        if ( c.status === 'closed' || c.status === 'visited' ) { return none; }
        var days = function ( a, b ) { return Math.round( ( new Date( b + 'T00:00:00Z' ) - new Date( a + 'T00:00:00Z' ) ) / 86400000 ); };
        var end = c.visit_date || c.visit_to;
        if ( !end ) { return none; }
        if ( end < today ) {
            var late = days( end, today );
            return { badge: 'Visit deadline missed', badgeClass: 'badge-danger', text: 'Deadline missed ' + late + ' day' + ( late > 1 ? 's' : '' ) + ' ago', textClass: 'text-danger fw-semibold', tileClass: 'is-danger' };
        }
        var start = c.visit_date || c.visit_from;
        if ( start > today ) {
            var wait = days( today, start );
            return { badge: '', badgeClass: '', text: 'Starts in ' + wait + ' day' + ( wait > 1 ? 's' : '' ), textClass: 'text-muted', tileClass: '' };
        }
        var left = days( today, end );
        return { badge: left === 0 ? 'Visit due today' : 'In the visit window', badgeClass: 'badge-light-warning', text: left === 0 ? 'Last day today' : left + ' day' + ( left > 1 ? 's' : '' ) + ' left',
                 textClass: 'text-warning fw-semibold', tileClass: 'is-warning' };
    },

    /**
     * @description Short names of the models ("VQ 43", VQ 55"")
     * @param {Array} models - [ { product_id, name } ]
     * @returns {string}
     */
    modelsShort : function ( models ) {
        return $.map( models, function ( m ) {
            if ( m.product_id === 'unsure' ) { return 'model not sure'; }
            var x = ( SCW.models || [] ).filter( function ( y ) { return y.product_id === m.product_id; } )[ 0 ];
            return x && x.size ? x.series + ' ' + x.size + '"' : m.name;
        } ).join( ', ' );
    },

    /**
     * @description Shows one tab of the case window
     * @param {string} tab - overview | tvs | media | notes
     * @returns {void}
     */
    showTab : function ( tab ) {
        SC.tab = tab;
        $( '#sc_case_tabs .sc-tab' ).each( function () {
            var on = $( this ).attr( 'data-sc-tab' ) === tab;
            $( this ).toggleClass( 'is-active', on ).attr( 'aria-selected', on );
        } );
        $( '#sc_case_modal .sc-pane' ).each( function () { $( this ).toggleClass( 'd-none', $( this ).attr( 'data-pane' ) !== tab ); } );
    },

    /**
     * @description Shows who changed what and when (the case, its TV cases and the draft it came from)
     * @returns {void}
     */
    showHistory : function () {
        var c = SC.current.case;
        SC.post( { what_do_you_want: 'appstv_case_history_get', case_id: c.id } ).then( function ( info ) {
            Swal.fire( { title: 'History of ' + SC.esc( c.label ), html: '<div class="text-start sc-history sc-history-tall">' + SCH.listHtml( info.data.history ) + '</div>', width: 820,
                         confirmButtonText: 'Close', buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
        } ).catch( SC.toastError );
    },

    /**
     * @description HTML of the TV cases of a main case (or of the main case and the other TV cases, for a TV case)
     * @param {object} d - Data of appstv_case_get
     * @returns {string} '' when the case has no TV cases
     */
    tvCasesHtml : function ( d ) {
        var c = d.case;
        if ( !d.tv_cases || !d.tv_cases.length ) { return ''; }
        var html = '<div class="sc-card-box"><div class="sc-box-head"><div class="sc-box-title">' + ( c.kind === 'tv' ? 'Main case and its TV cases' : 'One case per TV' ) + '</div>'
                 + '<span class="text-muted fs-7">Open a TV case to scan its serial and record the service</span></div>';
        if ( c.kind === 'tv' ) {
            html += '<button type="button" class="sc-tv-case" data-sc-action="open-case" data-open-id="' + c.parent_case_id + '"><span class="fw-bold">SC-' + c.parent_case_id + '</span><span class="text-muted fs-7">Main case</span></button>';
        }
        $.each( d.tv_cases, function ( i, t ) {
            var current = ( t.id === c.id );
            html += '<button type="button" class="sc-tv-case' + ( current ? ' is-current' : '' ) + '" data-sc-action="open-case" data-open-id="' + t.id + '"' + ( current ? ' disabled' : '' ) + '>'
                 + '<span class="d-flex align-items-center gap-3"><span class="fw-bold">' + SC.esc( t.label ) + '</span><span class="font-monospace text-muted fs-7">' + SC.esc( t.serials || 'Serial pending' ) + '</span></span>'
                 + '<span class="d-flex gap-1 flex-wrap justify-content-end"><span class="badge badge-light">' + SC.esc( SCW.WARRANTY[ t.warranty_status ] || t.warranty_status ) + '</span>'
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
        if ( action === 'history' ) { SC.showHistory(); return; }
        if ( action === 'cancel-edit' ) { SC.renderCase(); return; }
        if ( action === 'edit-visit' ) { $( '#scd_visit_view' ).addClass( 'd-none' ); $( '#scd_visit_edit' ).removeClass( 'd-none' ); $el.addClass( 'd-none' ); }
        if ( action === 'edit-contact' ) { $( '#scd_contact_view' ).addClass( 'd-none' ); $( '#scd_contact_edit' ).removeClass( 'd-none' ); $el.addClass( 'd-none' ); }
        if ( action === 'save-contact' ) { update( { contact_name: $( '#scd_contact_name' ).val(), contact_phone: $( '#scd_contact_phone' ).val() } ).catch( function () {} ); }
        if ( action === 'edit-problem' ) { $( '#scd_problem_view' ).addClass( 'd-none' ); $( '#scd_problem_edit' ).removeClass( 'd-none' ); $el.addClass( 'd-none' ); }
        if ( action === 'save-problem' ) { update( { problem: $( '#scd_problem' ).val() } ).catch( function () {} ); }
        if ( action === 'save-visit' ) {
            if ( !$( '#scd_visit_date' ).val() ) { $( '#scd_visit_time' ).val( '' ); }
            update( { assigned_user_id: $( '#scd_assigned' ).val(), visit_date: $( '#scd_visit_date' ).val(), visit_time: $( '#scd_visit_time' ).val() } ).catch( function () {} );
        }
        if ( action === 'visited' ) { update( { status: 'visited' } ).catch( function () {} ); }
        if ( action === 'reopen' ) {
            showConfirmSweetAlert( 'Reopen ' + SC.esc( c.label ) + '?', 'The case becomes active again.', 'question', 'Reopen', 'btn btn-primary', 'Cancel', 'btn btn-light',
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
            title : 'Close ' + SC.esc( c.label ),
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
