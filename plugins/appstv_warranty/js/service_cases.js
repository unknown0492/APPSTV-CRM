"use strict";

/* =============================================
   service_cases.js (v1.0.26: visits - Finish visit, needs another visit, Visits tab)
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
    today : '',                                                // Today in Singapore (from the list), for the card strips
    tab : 'overview',                                          // Tab shown in the case window
    gallery : null,                                            // Photos / videos of the open case (SCMedia)
    NOTE_TYPES : [ 'note', 'status', 'service', 'close', 'reopen', 'part' ],   // Timeline lines shown on the Notes tab (the rest is in History)
    listTimer : null,                                          // Pending list reload (search typing)
    listSeq : 0,                                               // Number of the latest list request (older answers dropped)
    STATUS : { open: 'Open', scheduled: 'Visit scheduled', visited: 'Visited', revisit: 'Needs another visit', closed: 'Closed' },
    STATUS_BADGE : { open: 'badge-light-primary', scheduled: 'badge-light-info', visited: 'badge-sc-amber', revisit: 'badge-sc-revisit', closed: 'badge-light' },
    // Why another visit is needed (1.0.26, same keys as APPSTV_CASE_REVISIT_REASONS)
    REVISIT_REASONS : { part: 'Spare part needed', tools: 'More tools needed', not_available: 'Customer or room not available', senior: 'Needs a senior technician', other: 'Other' },
    PART_STATES : { needed: 'Needed', ordered: 'Ordered', arrived: 'Arrived' },
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
        if ( status === 'Active' || status === 'Active (Extended)' ) { return 'badge-sc-ok'; }
        if ( status === 'Expired' ) { return 'badge-sc-bad'; }
        return 'badge-sc-amber';
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
        $( '#sc_list' ).on( 'keydown', '[data-case-id]', function ( e ) { if ( e.key === 'Enter' || e.key === ' ' ) { e.preventDefault(); SC.openCase( +$( this ).attr( 'data-case-id' ) ); } } );

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
                SC.today = d.today;
                $.each( d.counts, function ( k, v ) { $( '#sc_count_' + k ).text( v ); } );
                $( '[data-sc-chip="overdue"]' ).toggleClass( 'is-zero', d.counts.overdue === 0 );
                $( '[data-sc-chip="today"]' ).toggleClass( 'is-zero', d.counts.today === 0 );
                $( '#sc_list' ).html( $.map( d.cases, SC.cardHtml ).join( '' ) );
                $( '#sc_empty' ).toggleClass( 'd-none', d.cases.length > 0 );
            } ).catch( SC.toastError );
    },

    /**
     * @description HTML of one case card: status strip (what needs attention), customer and complainant, problem,
     *              footer (visit dates, staff, TVs, photos, notes)
     * @param {object} c - Case from appstv_case_list
     * @returns {string}
     */
    cardHtml : function ( c ) {
        var strip = SC.cardStrip( c );
        var dates = '';
        if ( c.visit_date ) { dates = SC.showDate( c.visit_date ) + ( c.visit_time ? ' ' + c.visit_time : '' ); }
        else if ( c.visit_to ) { dates = SC.shortRange( c.visit_from, c.visit_to ); }
        var staff = ( c.staff_names && c.staff_names.length ? c.staff_names.join( ', ' ) : c.assigned_name ) || 'Not assigned';
        var tvs = c.tv_case_count ? c.tv_case_count + ' TV cases' : ( c.tv_count > 1 ? c.tv_count + ' TVs' : ( c.serials ? '1 TV' : '' ) );
        var chips = $.map( ( c.problem_types || [] ).slice( 0, 3 ), function ( p ) { return '<span class="scc-chip">' + SC.esc( SCW.PROBLEMS[ p ] || p ) + '</span>'; } ).join( '' )
                  + ( ( c.problem_types || [] ).length > 3 ? '<span class="scc-chip scc-chip-more">+' + ( c.problem_types.length - 3 ) + '</span>' : '' );
        return '<div class="col-12 col-md-6 col-xxl-4">'
            + '<div class="scc" data-case-id="' + c.id + '" role="button" tabindex="0" aria-label="' + SC.esc( ( c.label || 'SC-' + c.id ) + ', ' + c.customer_name + ', ' + strip.text ) + '">'
            + '<div class="scc-strip scc-strip-' + strip.tone + '"><i class="fa-solid ' + strip.icon + ' me-2"></i><span class="text-truncate">' + SC.esc( strip.text ) + '</span></div>'
            + '<div class="scc-body">'
            + '  <div class="scc-top"><span class="scc-no">' + SC.esc( c.label || 'SC-' + c.id ) + '</span>'
            + '    <span class="badge ' + SC.STATUS_BADGE[ c.status ] + '">' + SC.STATUS[ c.status ] + '</span></div>'
            + '  <div class="scc-customer">' + SC.esc( c.customer_name || '(no customer)' ) + '</div>'
            + ( c.contact_name || c.contact_phone ? '<div class="scc-contact">' + SC.esc( [ c.contact_name, c.contact_position ].filter( Boolean ).join( ' · ' ) )
                + ( c.contact_phone ? '<span class="scc-phone"><i class="fa-solid fa-phone me-1"></i>' + SC.esc( c.contact_phone ) + '</span>' : '' ) + '</div>' : '' )
            + ( chips ? '<div class="scc-chips">' + chips + '</div>' : '' )
            + '  <div class="scc-problem">' + SC.esc( c.problem ) + '</div>'
            + '</div>'
            + '<div class="scc-foot">'
            + '  <span title="Visit"><i class="fa-regular fa-calendar"></i>' + SC.esc( dates || 'Not planned' ) + '</span>'
            + '  <span class="scc-staff" title="Assigned to"><i class="fa-solid fa-user"></i>' + SC.esc( staff ) + '</span>'
            + ( tvs ? '<span title="TVs"><i class="fa-solid fa-tv"></i>' + SC.esc( tvs ) + '</span>' : '' )
            + ( c.media_count ? '<span title="Photos / videos"><i class="fa-regular fa-image"></i>' + c.media_count + '</span>' : '' )
            + ( c.note_count ? '<span title="Notes"><i class="fa-regular fa-comment"></i>' + c.note_count + '</span>' : '' )
            + '</div></div></div>';
    },

    /**
     * @description Status strip of a card: the one thing to know first
     * @param {object} c - Case from appstv_case_list
     * @returns {object} { tone: danger | warning | info | success | muted, icon, text }
     */
    cardStrip : function ( c ) {
        if ( c.status === 'closed' ) { return { tone: 'success', icon: 'fa-flag-checkered', text: 'Closed' + ( c.outcome ? ' · ' + SC.OUTCOME[ c.outcome ] : '' ) }; }
        if ( c.status === 'visited' ) { return { tone: 'muted', icon: 'fa-check', text: 'Visited · close the case when done' }; }
        var due = SC.dueInfo( c, SC.today );
        var why = c.last_visit ? SC.revisitWhy( c.last_visit, true ) : '';
        if ( c.status === 'revisit' ) {
            if ( due.tileClass === 'is-danger' ) { return { tone: 'danger', icon: 'fa-triangle-exclamation', text: 'Next visit not planned · ' + c.revisit_wait + ' days' + ( why ? ' · ' + why : '' ) }; }
            return { tone: 'revisit', icon: 'fa-rotate', text: 'Needs another visit' + ( why ? ' · ' + why : '' ) };
        }
        var next = c.visit_count ? 'Visit ' + ( c.visit_count + 1 ) : 'Visit';
        if ( due.tileClass === 'is-danger' ) { return { tone: 'danger', icon: 'fa-triangle-exclamation', text: next + ' deadline missed · ' + due.text.replace( 'Deadline missed ', '' ) }; }
        if ( c.revisit_tv_count ) { return { tone: 'revisit', icon: 'fa-rotate', text: c.revisit_tv_count + ' TV case' + ( c.revisit_tv_count > 1 ? 's need' : ' needs' ) + ' another visit' }; }
        if ( due.tileClass === 'is-warning' ) { return { tone: 'warning', icon: 'fa-clock', text: ( c.visit_count ? next + ' · ' : '' ) + due.badge + ' · ' + due.text }; }
        if ( due.text ) { return { tone: 'info', icon: 'fa-calendar-day', text: next + ' ' + due.text.toLowerCase() }; }
        return { tone: 'muted', icon: 'fa-calendar-xmark', text: next + ' not planned yet' };
    },

    /**
     * @description Why another visit is needed, short: "Spare part needed: T-Con board (ordered)"
     * @param {object} v - A visit (reason, reason_text, part_name, part_status)
     * @param {boolean} [withState] - Add the state of the spare part
     * @returns {string} '' when the visit fixed it
     */
    revisitWhy : function ( v, withState ) {
        if ( !v || v.result !== 'revisit' ) { return ''; }
        var text = v.reason_text || SC.REVISIT_REASONS[ v.reason ] || '';
        if ( v.part_name ) { text += ': ' + v.part_name + ( withState && v.part_status ? ' (' + ( SC.PART_STATES[ v.part_status ] || v.part_status ).toLowerCase() + ')' : '' ); }
        return text;
    },

    /**
     * @description Short date range: "10–11 Nov", "30 Nov – 2 Dec", one date when equal
     * @param {string} from - 'YYYY-MM-DD'
     * @param {string} to - 'YYYY-MM-DD'
     * @returns {string}
     */
    shortRange : function ( from, to ) {
        var M = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
        var p = function ( ymd ) { return { d: +ymd.substr( 8, 2 ), m: M[ +ymd.substr( 5, 2 ) - 1 ], y: ymd.substr( 0, 4 ) }; };
        var a = p( from || to ), b = p( to || from );
        if ( from === to || !from ) { return b.d + ' ' + b.m; }
        if ( a.m === b.m && a.y === b.y ) { return a.d + '–' + b.d + ' ' + b.m; }
        return a.d + ' ' + a.m + ' – ' + b.d + ' ' + b.m;
    },

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
        c.revisit_wait = d.revisit_wait;                       // For dueInfo (the list sends it per case)
        c.revisit_days = d.revisit_days;
        var visits = d.visits || [];
        var nextNo = visits.length + 1;                        // Number of the visit to plan / finish next
        var due = SC.dueInfo( c, d.today );

        // ── Header ──
        var actions = '<button type="button" class="btn btn-sm btn-light" data-sc-action="history"><i class="fa-solid fa-clock-rotate-left"></i><span class="d-none d-md-inline ms-1">History</span></button>';
        if ( c.status === 'open' || c.status === 'scheduled' || c.status === 'revisit' ) {
            actions += '<button type="button" class="btn btn-sm sc-btn-finish" data-sc-action="finish-visit"><i class="fa-solid fa-person-walking-arrow-right"></i><span class="ms-1">Finish visit' + ( nextNo > 1 ? ' ' + nextNo : '' ) + '</span></button>';
        }
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
            closed ? '' : '<button type="button" class="btn btn-sm sc-pill" data-sc-action="edit-contact"><i class="fa-solid fa-pen"></i>Edit</button>' );

        var visit = '<div id="scd_visit_view">';
        if ( c.status === 'revisit' && visits.length ) {
            visit += '<div class="sc-tile-value">Visit ' + nextNo + ' not planned</div>'
                   + '<div class="sc-tile-sub">' + SC.esc( SC.revisitWhy( visits[ 0 ], true ) ) + '</div>';
        } else if ( nextNo > 1 && !closed && c.status !== 'visited' ) {
            visit += '<div class="sc-tile-sub fw-semibold">Visit ' + nextNo + '</div>';
        }
        if ( c.visit_to ) {
            visit += '<div class="sc-tile-value">' + SC.esc( SC.showDate( c.visit_from ) ) + ( c.visit_to !== c.visit_from ? ' – ' + SC.esc( SC.showDate( c.visit_to ) ) : '' ) + '</div>';
        }
        visit += ( due.text ? '<div class="sc-tile-sub ' + due.textClass + '">' + SC.esc( due.text ) + '</div>' : '' )
              + ( c.visit_date ? '<div class="sc-tile-sub"><i class="fa-regular fa-calendar-check me-1"></i>Planned: ' + SC.esc( SC.showDate( c.visit_date ) ) + ( c.visit_time ? ' ' + SC.esc( c.visit_time ) : '' ) + '</div>' : '' )
              + ( !c.visit_to && !c.visit_date && c.status !== 'revisit' ? '<div class="sc-tile-value text-muted">Not planned</div>' : '' )
              + ( c.status === 'visited' && visits.length ? '<div class="sc-tile-sub text-success fw-semibold"><i class="fa-solid fa-check me-1 text-success"></i>Visit ' + visits[ 0 ].visit_no + ' fixed it</div>' : '' ) + '</div>'
              + '<div id="scd_visit_edit" class="d-none"><div class="d-flex gap-2 mb-2">'
              + '<input type="date" id="scd_visit_date" class="form-control form-control-sm form-control-solid" value="' + SC.esc( c.visit_date ) + '" aria-label="Visit date"/>'
              + '<input type="time" id="scd_visit_time" class="form-control form-control-sm form-control-solid sc-time" value="' + SC.esc( c.visit_time ) + '" aria-label="Visit time"/></div>'
              + '<input type="hidden" id="scd_assigned" value="' + SC.esc( c.assigned_user_id ) + '"/>'
              + '<div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-light" data-sc-action="cancel-edit">Cancel</button><button type="button" class="btn btn-sm btn-primary" data-sc-action="save-visit">Save</button></div></div>';
        var planLabel = c.visit_date ? 'Change date' : ( nextNo > 1 ? 'Plan visit ' + nextNo : 'Plan the visit' );
        tiles += SC.tileHtml( 'fa-calendar-days', nextNo > 1 && !closed && c.status !== 'visited' ? 'Next visit' : 'Visit', visit,
                              closed || c.status === 'visited' ? '' : '<button type="button" class="btn btn-sm sc-pill" data-sc-action="edit-visit"><i class="fa-regular fa-calendar-plus"></i>' + planLabel + '</button>', due.tileClass );

        var staff = ( c.staff && c.staff.length ) ? c.staff.map( function ( s ) { return s.name || s.user_id; } ) : ( SC.staffName( c.assigned_user_id ) ? [ SC.staffName( c.assigned_user_id ) ] : [] );
        tiles += SC.tileHtml( 'fa-user-gear', 'Assigned to', staff.length ? '<div class="sc-tile-value">' + SC.esc( staff.join( ', ' ) ) + '</div>' : '<div class="sc-tile-value text-muted">Nobody yet</div>',
                              d.can_edit_staff && !closed ? '<button type="button" class="btn btn-sm sc-pill" data-sc-action="edit-staff"><i class="fa-solid fa-user-pen"></i>Change</button>' : '', '' );

        var tvsText = c.kind === 'tv' ? 'This TV' : c.tv_count + ' TV' + ( c.tv_count > 1 ? 's' : '' );
        tiles += SC.tileHtml( 'fa-shield-halved', 'Warranty',
            '<div class="sc-tile-value">' + SC.esc( SCW.WARRANTY[ c.warranty_status ] || 'Not known' ) + '</div>'
            + '<div class="sc-tile-sub">' + SC.esc( tvsText ) + ( c.models.length ? ' · ' + SC.esc( SC.modelsShort( c.models ) ) : '' ) + '</div>', '' );
        $( '#sc_case_tiles' ).html( tiles );

        // ── Tabs ──
        var tvCount = c.kind === 'main' ? d.tv_cases.length : d.tvs.length;
        var notes = d.notes.filter( function ( n ) { return SC.NOTE_TYPES.indexOf( n.type ) !== -1; } );
        var tabs = [ [ 'overview', 'Overview', null ], [ 'visits', 'Visits', visits.length ], [ 'tvs', 'TVs', tvCount ], [ 'media', 'Photos & videos', d.media.length ], [ 'notes', 'Notes', notes.length ] ];
        $( '#sc_case_tabs' ).html( $.map( tabs, function ( t ) {
            return '<button type="button" class="sc-tab' + ( SC.tab === t[ 0 ] ? ' is-active' : '' ) + '" role="tab" aria-selected="' + ( SC.tab === t[ 0 ] ) + '" data-sc-tab="' + t[ 0 ] + '">'
                 + SC.esc( t[ 1 ] ) + ( t[ 2 ] !== null ? '<span class="sc-tab-n" data-tab-n="' + t[ 0 ] + '">' + t[ 2 ] + '</span>' : '' ) + '</button>';
        } ).join( '' ) );
        SC.showTab( SC.tab );

        // Overview: problem, complaint, customer
        var chips = c.problem_types.length ? '<div class="sc-tags mb-3">' + $.map( c.problem_types, function ( p ) { return '<span class="sc-problem-chip">' + SC.esc( SCW.PROBLEMS[ p ] || p ) + '</span>'; } ).join( '' ) + '</div>' : '';
        var overview = '<div class="sc-card-box"><div class="sc-box-head"><div class="sc-box-title">Problem</div>' + ( closed ? '' : '<button type="button" class="btn btn-sm sc-pill" data-sc-action="edit-problem"><i class="fa-solid fa-pen"></i>Edit</button>' ) + '</div>'
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

        // Visits
        $( '#sc_pane_visits' ).html( SC.visitsHtml( d, closed ) );

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
        var icons = { note: 'fa-comment', status: 'fa-check', service: 'fa-screwdriver-wrench', close: 'fa-flag-checkered', reopen: 'fa-rotate-left', part: 'fa-box' };
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
        if ( c.status === 'revisit' ) {
            // Needs another visit, no date yet: red after revisit_days days of waiting (1.0.26)
            var wait = +c.revisit_wait || 0;
            var waited = wait === 0 ? 'Waiting since today' : 'Waiting ' + wait + ' day' + ( wait > 1 ? 's' : '' ) + ' for a date';
            if ( wait > ( +c.revisit_days || 3 ) ) {
                return { badge: 'Next visit not planned', badgeClass: 'badge-danger', text: waited, textClass: 'text-danger fw-semibold', tileClass: 'is-danger' };
            }
            return { badge: '', badgeClass: '', text: waited, textClass: 'sc-text-revisit fw-semibold', tileClass: 'is-revisit' };
        }
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
        return { badge: left === 0 ? 'Visit due today' : 'In the visit window', badgeClass: 'badge-sc-window', text: left === 0 ? 'Last day today' : left + ' day' + ( left > 1 ? 's' : '' ) + ' left',
                 textClass: 'sc-text-window fw-semibold', tileClass: 'is-warning' };
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
        var labels = {};                                   // case id => label, to name the TV case of a line
        $.each( SC.current.tv_cases || [], function ( i, t ) { labels[ t.id ] = t.label; } );
        if ( c.parent_case_id ) { labels[ c.parent_case_id ] = 'SC-' + c.parent_case_id; }
        SC.post( { what_do_you_want: 'appstv_case_history_get', case_id: c.id } ).then( function ( info ) {
            Swal.fire( { title: 'History of ' + SC.esc( c.label ), html: '<div class="text-start sc-history sc-history-tall">' + SCH.listHtml( info.data.history, labels, c.id ) + '</div>', width: 820,
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
     * @description HTML of the Visits tab: the planned next visit, then every trip (newest first) with its result,
     *              reason, spare part (Needed / Ordered / Arrived buttons) and note
     * @param {object} d - Data of appstv_case_get
     * @param {boolean} closed - True when the case is closed
     * @returns {string}
     */
    visitsHtml : function ( d, closed ) {
        var c = d.case, visits = d.visits || [];
        var e = SC.esc;
        var html = '';
        var nextNo = visits.length + 1;
        if ( !closed && c.status !== 'visited' ) {
            var when = c.visit_date ? SC.showDate( c.visit_date ) + ( c.visit_time ? ' ' + c.visit_time : '' )
                     : ( c.visit_to ? SC.showDate( c.visit_from ) + ( c.visit_to !== c.visit_from ? ' – ' + SC.showDate( c.visit_to ) : '' ) : '' );
            html += '<div class="sc-visit sc-visit-next"><div class="sc-visit-no">' + nextNo + '</div><div class="sc-visit-body">'
                  + '<div class="sc-visit-head"><span class="sc-visit-title">Visit ' + nextNo + '</span>'
                  + ( when ? '<span class="badge badge-light-info">Planned · ' + e( when ) + '</span>' : '<span class="badge badge-light">Not planned yet</span>' ) + '</div>'
                  + '<div class="d-flex flex-wrap gap-2 mt-2">'
                  + '<button type="button" class="btn btn-sm sc-pill" data-sc-action="edit-visit-tab"><i class="fa-regular fa-calendar-plus"></i>' + ( c.visit_date ? 'Change date' : 'Plan visit ' + nextNo ) + '</button>'
                  + '<button type="button" class="btn btn-sm sc-pill sc-pill-finish" data-sc-action="finish-visit"><i class="fa-solid fa-person-walking-arrow-right"></i>Finish visit ' + nextNo + '</button></div>'
                  + '</div></div>';
        }
        if ( visits.length === 0 ) {
            return html + '<div class="text-muted text-center py-6">No visits yet. When the technician is back from the site, use <b>Finish visit</b>.</div>';
        }
        $.each( visits, function ( i, v ) {
            var fixed = v.result === 'fixed';
            var staff = $.map( v.staff, function ( s ) { return s.name || s.user_id; } ).join( ', ' );
            html += '<div class="sc-visit ' + ( fixed ? 'is-fixed' : 'is-revisit' ) + '"><div class="sc-visit-no">' + v.visit_no + '</div><div class="sc-visit-body">'
                  + '<div class="sc-visit-head"><span class="sc-visit-title">Visit ' + v.visit_no + ' · ' + e( SC.showDate( v.visited_on ) ) + '</span>'
                  + ( fixed ? '<span class="badge badge-sc-ok"><i class="fa-solid fa-check me-1"></i>Fixed</span>'
                            : '<span class="badge badge-sc-revisit"><i class="fa-solid fa-rotate me-1"></i>Needs another visit</span>' ) + '</div>'
                  + ( staff ? '<div class="sc-visit-line"><i class="fa-solid fa-user-gear"></i>' + e( staff ) + '</div>' : '' );
            if ( !fixed ) {
                html += '<div class="sc-visit-line"><i class="fa-solid fa-circle-question"></i>' + e( v.reason_text || SC.REVISIT_REASONS[ v.reason ] || '' ) + '</div>';
            }
            if ( v.part_name ) {
                html += '<div class="sc-visit-part"><span><i class="fa-solid fa-box me-2"></i>Spare part: <b>' + e( v.part_name ) + '</b></span><span class="sc-part-steps" role="group" aria-label="Spare part state">';
                $.each( SC.PART_STATES, function ( key, label ) {
                    var on = ( v.part_status || 'needed' ) === key;
                    html += closed ? ( on ? '<span class="badge badge-light-primary">' + label + '</span>' : '' )
                         : '<button type="button" class="sc-part-step' + ( on ? ' is-on' : '' ) + '" data-sc-action="part" data-visit-id="' + v.id + '" data-part-status="' + key + '" aria-pressed="' + on + '">' + label + '</button>';
                } );
                html += '</span>' + ( v.part_status_on && v.part_status_by ? '<span class="text-muted fs-8 w-100">' + e( SC.PART_STATES[ v.part_status ] ) + ' · ' + e( v.part_status_by ) + ', ' + e( SC.showStamp( v.part_status_on ) ) + '</span>' : '' ) + '</div>';
            }
            if ( v.note ) { html += '<div class="sc-hl-quote sc-pre">' + e( v.note ) + '</div>'; }
            html += '<div class="text-muted fs-8 mt-2">Recorded by ' + e( v.by || 'someone' ) + ', ' + e( SC.showStamp( v.on ) ) + '</div></div></div>';
        } );
        return html;
    },

    /**
     * @description Asks how the visit ended and records it: fixed, or needs another visit (reason, part, note, next date)
     * @returns {void}
     */
    finishVisit : function () {
        var d = SC.current, c = d.case;
        var no = ( d.visits || [] ).length + 1;
        var team = ( c.staff && c.staff.length ) ? c.staff.map( function ( s ) { return s.user_id; } ) : ( c.assigned_user_id ? [ c.assigned_user_id ] : [] );
        if ( team.length === 0 && SC.staffName( SC.me ) ) { team = [ SC.me ]; }
        var today = SCW.singaporeNow().date;
        var people = SC.staff.concat( ( c.staff || [] ).filter( function ( s ) { return !SC.staffName( s.user_id ); } ) );   // + case staff who lost the permission (1.0.29)
        var staff = $.map( people, function ( s ) {
            return '<label class="scv-check"><input type="checkbox" class="form-check-input" name="scv_staff" value="' + SC.esc( s.user_id ) + '"' + ( team.indexOf( s.user_id ) !== -1 ? ' checked' : '' ) + '/>'
                 + '<span>' + SC.esc( s.name ) + ( s.user_id === SC.me ? ' (me)' : '' ) + '</span></label>';
        } ).join( '' );
        var reasons = $.map( SC.REVISIT_REASONS, function ( label, key ) {
            return '<label class="scv-check"><input type="radio" class="form-check-input" name="scv_reason" value="' + key + '"/><span>' + SC.esc( label ) + '</span></label>';
        } ).join( '' );
        var html = '<div class="text-start scv-form">'
            + '<div class="row g-3 mb-4"><div class="col-sm-5"><label class="form-label required" for="scv_date">Date of the visit</label>'
            + '<input type="date" id="scv_date" class="form-control form-control-solid" value="' + today + '" max="' + today + '"/></div>'
            + '<div class="col-sm-7"><label class="form-label required">Who went</label><div class="scv-checks">' + staff + '</div></div></div>'
            + '<label class="form-label required">Result</label>'
            + '<div class="scv-results" role="radiogroup">'
            + '<label class="scv-result is-fixed"><input type="radio" name="scv_result" value="fixed"/><i class="fa-solid fa-circle-check"></i><span><b>Fixed / done</b><small>Nothing more to do at the site</small></span></label>'
            + '<label class="scv-result is-revisit"><input type="radio" name="scv_result" value="revisit"/><i class="fa-solid fa-rotate"></i><span><b>Needs another visit</b><small>Parts, tools, nobody there</small></span></label></div>'
            + '<div id="scv_revisit" class="d-none mt-4">'
            + '<label class="form-label required">Why another visit?</label><div class="scv-checks scv-checks-col mb-3">' + reasons + '</div>'
            + '<div id="scv_part_box" class="d-none mb-3"><label class="form-label required" for="scv_part">Which spare part?</label>'
            + '<input type="text" id="scv_part" class="form-control form-control-solid" maxlength="200" placeholder="e.g. T-Con board, power board, remote"/></div>'
            + '<label class="form-label" for="scv_next_date">Plan visit ' + ( no + 1 ) + ' now <span class="text-muted fw-normal">(optional)</span></label>'
            + '<div class="d-flex gap-2 mb-3"><input type="date" id="scv_next_date" class="form-control form-control-solid flex-grow-1" min="' + today + '"/>'
            + '<input type="time" id="scv_next_time" class="form-control form-control-solid sc-time" aria-label="Time of the next visit"/></div></div>'
            + '<label class="form-label mt-4" id="scv_note_label" for="scv_note">Note <span class="text-muted fw-normal">(what was done, what was found)</span></label>'
            + '<textarea id="scv_note" class="form-control form-control-solid" rows="3" maxlength="2000"></textarea>'
            + '<div class="form-text mt-3"><i class="fa-regular fa-image me-1"></i>Photos from the site: add them on the <b>Photos &amp; videos</b> tab.</div></div>';

        Swal.fire( {
            title : 'Finish visit ' + no + ' of ' + SC.esc( c.label ),
            html : html,
            width : 640,
            heightAuto : false,
            showCancelButton : true,
            confirmButtonText : 'Save visit',
            buttonsStyling : false,
            focusConfirm : false,
            customClass : { popup: 'scv-popup', confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' },
            didOpen : function ( popup ) {
                var $p = $( popup );
                $p.on( 'change', 'input[name="scv_result"]', function () {
                    var revisit = $p.find( 'input[name="scv_result"]:checked' ).val() === 'revisit';
                    $p.find( '.scv-result' ).each( function () { $( this ).toggleClass( 'is-on', $( this ).find( 'input' ).prop( 'checked' ) ); } );
                    $p.find( '#scv_revisit' ).toggleClass( 'd-none', !revisit );
                } );
                $p.on( 'change', 'input[name="scv_reason"]', function () {
                    var reason = $p.find( 'input[name="scv_reason"]:checked' ).val();
                    $p.find( '#scv_part_box' ).toggleClass( 'd-none', reason !== 'part' );
                    $p.find( '#scv_note_label' ).toggleClass( 'required', reason === 'other' );
                    if ( reason === 'part' ) { $p.find( '#scv_part' ).trigger( 'focus' ); }
                } );
            },
            preConfirm : function () {
                var $p = $( Swal.getPopup() );
                var result = $p.find( 'input[name="scv_result"]:checked' ).val();
                var reason = $p.find( 'input[name="scv_reason"]:checked' ).val() || '';
                var team = $p.find( 'input[name="scv_staff"]:checked' ).map( function () { return this.value; } ).get();
                var data = { what_do_you_want: 'appstv_case_visit_finish', case_id: c.id, visited_on: $p.find( '#scv_date' ).val(), staff: JSON.stringify( team ),
                             result: result || '', reason: reason, part_name: $.trim( $p.find( '#scv_part' ).val() ), note: $.trim( $p.find( '#scv_note' ).val() ),
                             next_date: $p.find( '#scv_next_date' ).val(), next_time: $p.find( '#scv_next_date' ).val() ? $p.find( '#scv_next_time' ).val() : '' };
                if ( !data.visited_on ) { Swal.showValidationMessage( 'Enter the date of the visit.' ); return false; }
                if ( team.length === 0 ) { Swal.showValidationMessage( 'Tick who went to the site.' ); return false; }
                if ( !result ) { Swal.showValidationMessage( 'Choose the result: fixed, or needs another visit.' ); return false; }
                if ( result === 'revisit' && !reason ) { Swal.showValidationMessage( 'Choose why another visit is needed.' ); return false; }
                if ( result === 'revisit' && reason === 'part' && !data.part_name ) { Swal.showValidationMessage( 'Write which spare part is needed.' ); return false; }
                if ( result === 'revisit' && reason === 'other' && !data.note ) { Swal.showValidationMessage( 'Write why another visit is needed (Note).' ); return false; }
                return SC.post( data ).catch( function ( message ) { Swal.showValidationMessage( message ); return false; } );
            }
        } ).then( function ( r ) {
            if ( r.isConfirmed && r.value ) {
                showSimpleToast( 'success', r.value.message );
                SC.tab = 'visits';
                SC.reloadCase();
            }
        } );
    },

    /**
     * @description Changes the staff of the open case (1.0.29): visit technicians can be added, anyone removed (one
     *              must stay). A main case and its TV cases change together. Each person added gets an e-mail.
     * @returns {void}
     */
    editStaff : function () {
        var d = SC.current, c = d.case;
        var now = ( c.staff && c.staff.length ) ? c.staff : ( c.assigned_user_id ? [ { user_id: c.assigned_user_id, name: SC.staffName( c.assigned_user_id ), technician: true } ] : [] );
        var onCase = now.map( function ( s ) { return s.user_id; } );
        var row = function ( s, note ) {
            var on = onCase.indexOf( s.user_id ) !== -1;
            return '<label class="scv-check scs-person' + ( on ? ' is-on' : '' ) + '"><input type="checkbox" class="form-check-input" name="scs_staff" value="' + SC.esc( s.user_id ) + '"' + ( on ? ' checked' : '' ) + '/>'
                 + '<span><span class="fw-semibold">' + SC.esc( s.name || s.user_id ) + ( s.user_id === SC.me ? ' (me)' : '' ) + '</span>'
                 + ( note ? '<span class="scs-note">' + SC.esc( note ) + '</span>' : '' ) + '</span></label>';
        };
        var html = $.map( SC.staff, function ( s ) { return row( s, '' ); } ).join( '' );
        // On the case but no longer a visit technician: can stay or be removed, not added again
        html += $.map( now.filter( function ( s ) { return !SC.staffName( s.user_id ); } ), function ( s ) { return row( s, 'No longer a visit technician: can stay, but cannot be added again once removed' ); } ).join( '' );
        var scope = c.kind === 'main' ? 'This changes the main case and all its ' + ( d.tv_cases || [] ).length + ' TV cases.'
                  : ( c.kind === 'tv' ? 'This changes main case SC-' + c.parent_case_id + ' and all its TV cases.' : '' );
        Swal.fire( {
            title : 'Staff of ' + SC.esc( c.label ),
            html : '<div class="text-start scv-form">'
                 + ( scope ? '<div class="scs-scope"><i class="fa-solid fa-layer-group"></i><span>' + SC.esc( scope ) + '</span></div>' : '' )
                 + '<div class="scs-list">' + ( html || '<div class="text-muted">No visit technicians yet. Give a role the “visit technician” permission in Role Management.</div>' ) + '</div>'
                 + '<div class="form-text mt-3"><i class="fa-regular fa-envelope me-1"></i>People you add get an e-mail with the case details.</div></div>',
            width : 560,
            heightAuto : false,
            showCancelButton : true,
            confirmButtonText : 'Save staff',
            buttonsStyling : false,
            focusConfirm : false,
            customClass : { popup: 'scv-popup', confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' },
            didOpen : function ( popup ) {
                $( popup ).on( 'change', 'input[name="scs_staff"]', function () { $( this ).closest( '.scs-person' ).toggleClass( 'is-on', this.checked ); } );
            },
            preConfirm : function () {
                var team = $( Swal.getPopup() ).find( 'input[name="scs_staff"]:checked' ).map( function () { return this.value; } ).get();
                if ( team.length === 0 ) { Swal.showValidationMessage( 'At least one person must stay on the case.' ); return false; }
                return SC.post( { what_do_you_want: 'appstv_case_staff_set', case_id: c.id, staff: JSON.stringify( team ) } )
                    .catch( function ( message ) { Swal.showValidationMessage( message ); return false; } );
            }
        } ).then( function ( r ) {
            if ( r.isConfirmed && r.value ) {
                showSimpleToast( r.value.data && r.value.data.changed === false ? 'info' : 'success', r.value.message );
                SC.reloadCase();
                SC.loadList();
            }
        } );
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
            html += '<span class="badge badge-sc-amber">Not in the system</span></div>';
        } else {
            html += '<span class="badge ' + SC.warrantyBadge( ch.warranty_status ) + '">' + SC.esc( ch.warranty_status ) + '</span>';
            if ( tv.pwrp_id ) { html += '<span class="badge badge-sc-ok">Service recorded on this case</span>'; }
            html += '</div><div class="text-muted fs-7">' + SC.esc( ch.product_name );
            if ( ch.warranty_expiry ) { html += ' · warranty until ' + SC.esc( SC.showDate( ch.warranty_expiry ) ) + ( ch.extended_expiry ? ', extended until ' + SC.esc( SC.showDate( ch.extended_expiry ) ) : '' ); }
            html += '</div>';
            if ( ch.role === 'replacement' ) { html += '<div class="fs-7 text-muted">Replacement TV under the warranty of the original TV</div>'; }
            if ( ch.customer_id && ch.customer_id !== c.customer_id ) { html += '<div class="fs-7 sc-text-amber">Sold to ' + SC.esc( ch.customer_name ) + ', not to this customer</div>'; }
            if ( !ch.is_current ) { html += '<div class="fs-7 sc-text-amber">Already replaced by ' + SC.esc( ch.current_serial_number ) + '</div>'; }
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
        if ( action === 'finish-visit' ) { SC.finishVisit(); }
        if ( action === 'edit-staff' ) { SC.editStaff(); }
        if ( action === 'edit-visit-tab' ) {                  // From the Visits tab: open the date editor of the Visit tile
            $( '#scd_visit_view' ).addClass( 'd-none' );
            $( '#scd_visit_edit' ).removeClass( 'd-none' );
            $( '[data-sc-action="edit-visit"]' ).addClass( 'd-none' );
            $( '#scd_visit_date' ).trigger( 'focus' );
            $( '#sc_case_tiles' )[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
        }
        if ( action === 'part' ) {
            if ( $el.hasClass( 'is-on' ) ) { return; }
            SC.caseCall( { what_do_you_want: 'appstv_case_visit_part', visit_id: $el.attr( 'data-visit-id' ), part_status: $el.attr( 'data-part-status' ) } ).catch( function () {} );
        }
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
