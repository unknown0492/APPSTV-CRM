"use strict";

/* =============================================
   service_case_wizard.js (v1.0.21)
   Plugin: appstv_warranty - "Open a service case" wizard and the Drafts list of the Service Cases page

   Steps (this version): 1 Customer and complainant, 2 Which TVs, 3 The problem, 6 Who goes and when.
   Steps 4 (serial numbers) and 5 (photos / videos) are added by later versions: SCW.STEPS lists the steps shown.

   Drafts: saved on the server a few seconds after a change and on every step change (shared with everyone who
   handles cases); a copy of unsaved answers is kept in this browser (localStorage) in case the connection drops.
   Every save carries the draft's revision; when someone else saved in between, their version is loaded.
   Depends on SC (service_cases.js), AppstvServiceForms, Swal, bootstrap.
   ============================================= */

var SCW = {

    STEPS : [                                                  // Steps shown, in order (id = plan step number)
        { id: 1, title: 'Customer' },
        { id: 2, title: 'TVs' },
        { id: 3, title: 'Problem' },
        { id: 5, title: 'Photos' },
        { id: 6, title: 'Who and when' }
    ],
    AUTOSAVE_MS : 2500,                                        // Save this long after the last change
    BACKUP_KEY : 'appstv_scw_backup',                          // localStorage key of the unsaved copy
    CHANNELS : { call: 'Call', whatsapp: 'WhatsApp', email: 'E-mail', sms: 'Text message' },
    WARRANTY : { under: 'Under warranty', out: 'Out of warranty', mixed: 'With or without warranty', unknown: 'Not known' },
    WARRANTY_HINT : { under: '', out: '', mixed: 'Some of the TVs are under warranty, some are not', unknown: '' },
    PROBLEMS : { no_power: 'No power', no_picture: 'No picture', no_sound: 'No sound', remote: 'Remote', wifi_apps: 'Wi-Fi / apps',
                 broken_screen: 'Broken screen', physical_damage: 'Physical damage', liquid_damage: 'Liquid damage', other: 'Other' },

    models : null,                                             // TV models (appstv_case_models), loaded once
    draftId : 0,                                               // service_case_drafts.id, 0 = not saved yet
    revision : 0,                                              // Revision the answers are based on
    stepIndex : 0,                                             // Position in SCW.STEPS
    answers : null,                                            // Wizard answers (same shape as the server's appstv_case_clean_answers)
    dirty : false,                                             // Changes not saved on the server yet
    saving : null,                                             // Promise of the save in progress
    saveAgain : false,                                         // A change came while saving: save again after it
    saveTimer : null,                                          // Pending autosave
    searchTimer : null,                                        // Pending customer search
    contactsTimer : null,                                      // Pending complainant search
    opening : false,                                           // OPEN CASE in progress
    gallery : null,                                            // Photos / videos of the draft (SCMedia)

    // ── Start ───────────────────────────────────────────────

    /**
     * @description Binds the events of the wizard and the drafts list, loads the drafts
     * @returns {void}
     */
    init : function () {
        SCW.bindEvents();
        SCW.loadDrafts();
        // Uploads go to the draft: it is saved first when it has no number yet
        SCW.gallery = new SCMedia( $( '#scw_media' ), {
            target : function () {
                if ( SCW.draftId ) { return { draft_id: SCW.draftId }; }
                SCW.dirty = true;
                return SCW.saveNow().then( function () {
                    if ( !SCW.draftId ) { throw 'Choose the customer first, so the draft can be saved.'; }
                    return { draft_id: SCW.draftId };
                } );
            },
            onChange : function () { SCW.renderSummary(); }
        } );
        SCW.loadModels().catch( function () { /* Loaded again when the wizard opens */ } );   // Model names for the history lines
    },

    /**
     * @description Empty answers of a new case (complaint date / time = now)
     * @returns {object}
     */
    emptyAnswers : function () {
        var now = SCW.singaporeNow();
        return {
            customer : null,
            complainant : { contact_id: 0, name: '', phone: '', email: '', position: '' },
            complained_date : now.date,
            complained_time : now.time,
            channels : [],
            tv_count : 1,
            split : false,
            models : [],
            warranty : '',
            problem_types : [],
            problem : '',
            serials : [],
            staff : [],
            visit_from : '',
            visit_to : ''
        };
    },

    /**
     * @description Opens the wizard for a new case (offers the unsaved copy of this browser first, when there is one)
     * @param {function} [then] - Called once the wizard shows, e.g. to prefill a TV
     * @returns {void}
     */
    openNew : function ( then ) {
        var backup = SCW.readBackup();
        var start = function ( restore ) {
            if ( restore && backup.draftId ) { SCW.openDraft( backup.draftId, backup ); return; }
            SCW.show( 0, 0, restore ? backup.answers : SCW.emptyAnswers(), restore ? backup.stepIndex : 0 );
            if ( restore ) { SCW.markDirty(); }
            if ( typeof then === 'function' ) { then(); }
        };
        if ( backup === null ) { start( false ); return; }
        Swal.fire( {
            icon : 'question', title : 'Continue the unsaved case?',
            html : 'A case you were filling in on this device was not saved' + ( backup.answers.customer ? ' (<b>' + SC.esc( backup.answers.customer.name ) + '</b>)' : '' ) + '.',
            showCancelButton : true, confirmButtonText : 'Continue it', cancelButtonText : 'Start a new one',
            buttonsStyling : false, customClass : { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light' }
        } ).then( function ( r ) {
            if ( r.isDismissed && r.dismiss !== Swal.DismissReason.cancel ) { return; }
            if ( !r.isConfirmed ) { SCW.clearBackup(); }
            start( r.isConfirmed );
        } );
    },

    /**
     * @description Opens a draft from the server
     * @param {number} draftId - service_case_drafts.id
     * @param {object} [backup] - Unsaved copy of this browser for that draft (used when it is based on the same revision)
     * @returns {void}
     */
    openDraft : function ( draftId, backup ) {
        SC.post( { what_do_you_want: 'appstv_case_draft_get', draft_id: draftId } ).then( function ( info ) {
            var d = info.data.draft;
            if ( backup && backup.draftId === d.draft_id && backup.revision === d.revision ) {
                SCW.show( d.draft_id, d.revision, backup.answers, backup.stepIndex, d.media );
                SCW.markDirty();
                return;
            }
            if ( backup && backup.draftId === d.draft_id ) {
                showSimpleToast( 'info', 'Someone changed this draft after your unsaved changes; their version is shown.' );
            }
            SCW.clearBackupOf( d.draft_id );
            SCW.show( d.draft_id, d.revision, d.answers, SCW.indexOfStep( d.step ), d.media );
            SCW.setSavedText( 'Draft by ' + ( d.created_by || 'someone' ) + ' · last saved ' + SC.showStamp( d.updated_on ) + ( d.updated_by ? ' by ' + d.updated_by : '' ) );
        } ).catch( function ( message ) {
            SCW.clearBackupOf( draftId );
            SC.toastError( message );
            SCW.loadDrafts();
        } );
    },

    /**
     * @description Shows the wizard with the given answers
     * @param {number} draftId - Draft id (0 = new)
     * @param {number} revision - Its revision
     * @param {object} answers - Answers
     * @param {number} stepIndex - Step to show
     * @param {Array} [media] - Photos / videos of the draft
     * @returns {void}
     */
    show : function ( draftId, revision, answers, stepIndex, media ) {
        SCW.draftId = draftId;
        SCW.revision = revision;
        SCW.answers = $.extend( true, SCW.emptyAnswers(), answers );
        SCW.dirty = false;
        SCW.opening = false;
        SCW.setSavedText( draftId ? '' : 'Not saved yet' );
        SCW.gallery.queue = [];
        SCW.gallery.drawQueue();
        SCW.gallery.setMedia( media || [] );
        SCW.loadModels().then( function () {
            SCW.renderAll();
            SCW.goTo( Math.max( 0, Math.min( SCW.STEPS.length - 1, stepIndex || 0 ) ), false );
            bootstrap.Modal.getOrCreateInstance( document.getElementById( 'scw_modal' ) ).show();
        } ).catch( SC.toastError );
    },

    /**
     * @description Loads the TV models once
     * @returns {Promise}
     */
    loadModels : function () {
        if ( SCW.models !== null ) { return Promise.resolve(); }
        return SC.post( { what_do_you_want: 'appstv_case_models' } ).then( function ( info ) { SCW.models = info.data.models; } );
    },

    // ── Events ──────────────────────────────────────────────

    /**
     * @description Binds the events (the wizard markup is fixed; lists inside it are delegated)
     * @returns {void}
     */
    bindEvents : function () {
        var $m = $( '#scw_modal' );

        // Navigation
        $( '#scw_back' ).on( 'click', function () { SCW.goTo( SCW.stepIndex - 1, true ); } );
        $( '#scw_next' ).on( 'click', function () { if ( SCW.checkStep( true ) ) { SCW.goTo( SCW.stepIndex + 1, true ); } } );
        $( '#scw_open' ).on( 'click', SCW.openCase );
        $( '#scw_save_draft' ).on( 'click', function () { SCW.saveAndClose( true ); } );
        $( '#scw_close' ).on( 'click', function () { SCW.saveAndClose( false ); } );
        $m.on( 'click', '[data-scw-go]', function () {
            var target = +$( this ).attr( 'data-scw-go' );
            if ( target < SCW.stepIndex || SCW.checkStep( true ) ) { SCW.goTo( target, true ); }
        } );

        // Step 1: customer
        $( '#scw_customer_search' ).on( 'input', function () {
            clearTimeout( SCW.searchTimer );
            var q = $( this ).val().trim();
            SCW.searchTimer = setTimeout( function () { SCW.searchCustomers( q ); }, 300 );
        } );
        $( '#scw_customer_results' ).on( 'click', '[data-customer-index]', function () {
            SCW.setCustomer( $( '#scw_customer_results' ).data( 'customers' )[ +$( this ).attr( 'data-customer-index' ) ] );
        } );
        $( '#scw_customer_results' ).on( 'click', '[data-scw-add-customer]', function () { SCW.showAddCustomer( true ); } );
        $( '#scw_customer_change' ).on( 'click', function () { SCW.setCustomer( null ); $( '#scw_customer_search' ).val( '' ).trigger( 'focus' ); } );
        $( '#scw_add_customer_open' ).on( 'click', function () { SCW.showAddCustomer( true ); } );
        $( '#scw_add_customer_cancel' ).on( 'click', function () { SCW.showAddCustomer( false ); } );
        $( '#scw_add_customer_save' ).on( 'click', function () { SCW.addCustomer( false ); } );
        $( '#scw_similar' ).on( 'click', '[data-use-similar]', function () {
            SCW.setCustomer( $( '#scw_similar' ).data( 'similar' )[ +$( this ).attr( 'data-use-similar' ) ] );
            SCW.showAddCustomer( false );
        } );
        $( '#scw_similar' ).on( 'click', '[data-add-anyway]', function () { SCW.addCustomer( true ); } );

        // Step 1: complainant
        $( '#scw_c_name' ).on( 'focus input', function () {
            clearTimeout( SCW.contactsTimer );
            var q = $( this ).val().trim();
            SCW.contactsTimer = setTimeout( function () { SCW.suggestContacts( q ); }, 250 );
        } );
        $( '#scw_contact_results' ).on( 'mousedown', '[data-contact-index]', function ( e ) {
            e.preventDefault();                                // Keeps the focus in the name box until it is filled
            SCW.useContact( $( '#scw_contact_results' ).data( 'contacts' )[ +$( this ).attr( 'data-contact-index' ) ] );
        } );
        $( '#scw_c_name' ).on( 'blur', function () { setTimeout( function () { $( '#scw_contact_results' ).addClass( 'd-none' ); }, 150 ); } );
        $( document ).on( 'click', function ( e ) {
            if ( $( e.target ).closest( '#scw_customer_box' ).length === 0 ) { $( '#scw_customer_results' ).addClass( 'd-none' ); }
        } );

        // Every field: copy into the answers, then autosave
        $m.on( 'input change', '[data-scw-field]', function () { SCW.readField( $( this ) ); } );
        $m.on( 'click', '[data-scw-toggle]', function () { SCW.toggle( $( this ).attr( 'data-scw-toggle' ), $( this ).attr( 'data-value' ) ); } );
        $m.on( 'click', '[data-scw-pick]', function () { SCW.pick( $( this ).attr( 'data-scw-pick' ), $( this ).attr( 'data-value' ) ); } );

        // Leaving the wizard with the window's own close / Esc is not possible (static backdrop); browser close keeps the local copy
        $m.on( 'hidden.bs.modal', function () { clearTimeout( SCW.saveTimer ); } );

        // Drafts list
        $( '#sc_drafts_list' ).on( 'click', '[data-draft-continue]', function () { SCW.openDraft( +$( this ).attr( 'data-draft-continue' ), SCW.readBackup() ); } );
        $( '#sc_drafts_list' ).on( 'click', '[data-draft-delete]', function () { SCW.deleteDraft( +$( this ).attr( 'data-draft-delete' ), $( this ).attr( 'data-label' ) ); } );
        $( '#sc_drafts_list' ).on( 'click', '[data-draft-history]', function () { SCW.showDraftHistory( +$( this ).attr( 'data-draft-history' ) ); } );
    },

    // ── Steps ───────────────────────────────────────────────

    /**
     * @description Position of a plan step number in SCW.STEPS (0 when it is not shown)
     * @param {number} stepId - Plan step number (1..6)
     * @returns {number}
     */
    indexOfStep : function ( stepId ) {
        for ( var i = 0; i < SCW.STEPS.length; i++ ) { if ( SCW.STEPS[ i ].id === stepId ) { return i; } }
        return 0;
    },

    /**
     * @description Shows a step; saves the draft when asked (step change)
     * @param {number} index - Position in SCW.STEPS
     * @param {boolean} save - Save the draft now
     * @returns {void}
     */
    goTo : function ( index, save ) {
        if ( index < 0 || index >= SCW.STEPS.length ) { return; }
        SCW.stepIndex = index;
        var id = SCW.STEPS[ index ].id;
        var last = ( index === SCW.STEPS.length - 1 );
        $( '.scw-step' ).addClass( 'd-none' );
        $( '.scw-step[data-step="' + id + '"]' ).removeClass( 'd-none' );
        $( '#scw_step_text' ).text( 'Step ' + ( index + 1 ) + ' of ' + SCW.STEPS.length );
        $( '#scw_progress_bar' ).css( 'width', ( ( index + 1 ) / SCW.STEPS.length * 100 ) + '%' );
        $( '#scw_steps' ).html( $.map( SCW.STEPS, function ( s, i ) {
            var cls = i === index ? 'is-current' : ( i < index ? 'is-done' : '' );
            return '<button type="button" class="scw-dot ' + cls + '" data-scw-go="' + i + '"><span class="scw-dot-n">' + ( i < index ? '<i class="fa-solid fa-check"></i>' : i + 1 ) + '</span>'
                 + '<span class="scw-dot-t">' + SC.esc( s.title ) + '</span></button>';
        } ).join( '' ) );
        $( '#scw_back' ).prop( 'disabled', index === 0 );
        $( '#scw_next' ).toggleClass( 'd-none', last );
        $( '#scw_open' ).toggleClass( 'd-none', !last );
        if ( id === 6 ) { SCW.renderSummary(); }
        $( '#scw_modal .modal-body' ).scrollTop( 0 );
        if ( save && SCW.dirty ) { SCW.saveNow(); }
    },

    /**
     * @description Checks the answers of the shown step before going on
     * @param {boolean} warn - Show what is missing
     * @returns {boolean} True when the step is complete
     */
    checkStep : function ( warn ) {
        var a = SCW.answers;
        var id = SCW.STEPS[ SCW.stepIndex ].id;
        var errors = [];
        if ( id === 1 ) {
            if ( !a.customer ) { errors.push( 'Choose the customer (or add a new one).' ); }
            if ( !a.complainant.name ) { errors.push( 'Enter the name of the person who complained.' ); }
            if ( a.complainant.phone && !/^[0-9+\-() ]{6,20}$/.test( a.complainant.phone ) ) { errors.push( 'The phone number is not valid.' ); }
            if ( a.complainant.email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test( a.complainant.email ) ) { errors.push( 'The e-mail address is not valid.' ); }
            if ( !a.complained_date || !a.complained_time ) { errors.push( 'Enter when they complained.' ); }
            if ( a.channels.length === 0 ) { errors.push( 'Tick how they complained.' ); }
        }
        if ( id === 2 ) {
            if ( !( a.tv_count >= 1 ) ) { errors.push( 'Enter how many TVs have a problem.' ); }
            if ( a.models.length === 0 ) { errors.push( 'Tick the TV model(s), or Not sure.' ); }
            if ( !a.warranty ) { errors.push( 'Choose the warranty answer.' ); }
        }
        if ( id === 3 && !a.problem ) { errors.push( 'Describe the problem in their own words.' ); }
        if ( id === 6 ) {
            if ( a.staff.length === 0 ) { errors.push( 'Assign the case to at least one staff member.' ); }
            if ( !a.visit_from || !a.visit_to ) { errors.push( 'Enter the visit window (from and to).' ); }
            else if ( a.visit_to < a.visit_from ) { errors.push( 'The visit window ends before it starts.' ); }
        }
        if ( errors.length && warn ) {
            Swal.fire( { icon: 'warning', title: 'Please check', html: errors.map( SC.esc ).join( '<br/>' ), buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
        }
        return errors.length === 0;
    },

    // ── Drawing ─────────────────────────────────────────────

    /**
     * @description Draws every step from the answers
     * @returns {void}
     */
    renderAll : function () {
        var a = SCW.answers;
        SCW.showAddCustomer( false );
        $( '#scw_customer_search' ).val( '' );
        SCW.renderCustomer();
        $( '#scw_c_name' ).val( a.complainant.name );
        $( '#scw_c_phone' ).val( a.complainant.phone );
        $( '#scw_c_email' ).val( a.complainant.email );
        $( '#scw_c_position' ).val( a.complainant.position );
        $( '#scw_complained_date' ).val( a.complained_date ).attr( 'max', SCW.todayText() );
        $( '#scw_complained_time' ).val( a.complained_time );
        $( '#scw_tv_count' ).val( a.tv_count );
        $( '#scw_problem' ).val( a.problem );
        $( '#scw_visit_from' ).val( a.visit_from );
        $( '#scw_visit_to' ).val( a.visit_to ).attr( 'min', a.visit_from || '' );
        $( '#scw_channels' ).html( SCW.chipsHtml( 'channels', SCW.CHANNELS, a.channels, { call: 'fa-phone', whatsapp: 'fa-brands fa-whatsapp', email: 'fa-envelope', sms: 'fa-comment-sms' } ) );
        $( '#scw_problem_types' ).html( SCW.chipsHtml( 'problem_types', SCW.PROBLEMS, a.problem_types, {} ) );
        SCW.renderTvs();
        SCW.renderStaff();
    },

    /**
     * @description HTML of toggle chips (several can be ticked)
     * @param {string} field - Answers field (a list)
     * @param {object} labels - value => label
     * @param {Array} ticked - Ticked values
     * @param {object} icons - value => Font Awesome class
     * @returns {string}
     */
    chipsHtml : function ( field, labels, ticked, icons ) {
        return $.map( labels, function ( label, value ) {
            var on = ticked.indexOf( value ) !== -1;
            var icon = icons[ value ] ? '<i class="' + ( icons[ value ].indexOf( 'fa-brands' ) === 0 ? icons[ value ] : 'fa-solid ' + icons[ value ] ) + ' me-2"></i>' : '';
            return '<button type="button" class="scw-chip' + ( on ? ' is-on' : '' ) + '" data-scw-toggle="' + field + '" data-value="' + SC.esc( value ) + '" aria-pressed="' + on + '">'
                 + icon + SC.esc( label ) + '</button>';
        } ).join( '' );
    },

    /**
     * @description Draws the chosen customer, or the search box
     * @returns {void}
     */
    renderCustomer : function () {
        var c = SCW.answers.customer;
        $( '#scw_customer_box' ).toggleClass( 'd-none', c !== null );
        $( '#scw_customer_selected' ).toggleClass( 'd-none', c === null );
        $( '#scw_customer_results' ).addClass( 'd-none' );
        if ( c !== null ) {
            $( '#scw_customer_name' ).text( c.name || '(no name)' );
            $( '#scw_customer_sub' ).text( c.sub || '' );
        }
    },

    /**
     * @description Draws step 2 (number of TVs, one case or one per TV, models, warranty)
     * @returns {void}
     */
    renderTvs : function () {
        var a = SCW.answers;
        var many = a.tv_count > 1;
        $( '[data-scw-pick="many"]' ).each( function () { $( this ).toggleClass( 'is-on', ( $( this ).attr( 'data-value' ) === '1' ) === many ); } );
        $( '#scw_tv_count_box, #scw_split_box' ).toggleClass( 'd-none', !many );
        $( '[data-scw-pick="split"]' ).each( function () { $( this ).toggleClass( 'is-on', ( $( this ).attr( 'data-value' ) === '1' ) === a.split ); } );
        $( '#scw_split_hint' ).text( a.split && many ? 'Case SC-n for everything they share, and SC-n-01 to SC-n-' + ( a.tv_count < 10 ? '0' : '' ) + a.tv_count + ', one per TV.' : '' );

        // Models grouped by series
        var groups = {};
        var order = [];
        $.each( SCW.models || [], function ( i, m ) {
            if ( !groups[ m.series ] ) { groups[ m.series ] = []; order.push( m.series ); }
            groups[ m.series ].push( m );
        } );
        var html = '';
        $.each( order, function ( i, series ) {
            html += '<div class="scw-model-group"><div class="scw-model-series">' + SC.esc( series ) + '</div><div class="scw-chips">';
            $.each( groups[ series ], function ( j, m ) {
                var on = a.models.indexOf( m.product_id ) !== -1;
                html += '<button type="button" class="scw-chip' + ( on ? ' is-on' : '' ) + '" data-scw-toggle="models" data-value="' + SC.esc( m.product_id ) + '" title="' + SC.esc( m.title ) + '" aria-pressed="' + on + '">'
                     + ( m.size ? m.size + '&Prime;' : SC.esc( m.name ) ) + ( /google/i.test( m.title + m.name ) ? ' Google TV' : '' ) + '</button>';
            } );
            html += '</div></div>';
        } );
        var unsure = a.models.indexOf( 'unsure' ) !== -1;
        html += '<div class="scw-model-group"><div class="scw-model-series">&nbsp;</div><div class="scw-chips"><button type="button" class="scw-chip' + ( unsure ? ' is-on' : '' )
             + '" data-scw-toggle="models" data-value="unsure" aria-pressed="' + unsure + '"><i class="fa-regular fa-circle-question me-2"></i>Not sure</button></div></div>';
        $( '#scw_models' ).html( html );

        // Warranty
        $( '#scw_warranty' ).html( $.map( SCW.WARRANTY, function ( label, value ) {
            var disabled = ( value === 'mixed' && !many );
            return '<button type="button" class="scw-option' + ( a.warranty === value ? ' is-on' : '' ) + '" data-scw-pick="warranty" data-value="' + value + '"' + ( disabled ? ' disabled' : '' ) + '>'
                 + '<span class="scw-radio"></span><span><span class="fw-semibold d-block">' + SC.esc( label ) + '</span>'
                 + ( SCW.WARRANTY_HINT[ value ] ? '<span class="text-muted fs-8">' + SC.esc( SCW.WARRANTY_HINT[ value ] ) + ( disabled ? ' (needs more than one TV)' : '' ) + '</span>' : '' )
                 + '</span></button>';
        } ).join( '' ) );
    },

    /**
     * @description Draws the staff chips of the last step
     * @returns {void}
     */
    renderStaff : function () {
        var html = $.map( SC.staff, function ( s ) {
            var on = SCW.answers.staff.indexOf( s.user_id ) !== -1;
            return '<button type="button" class="scw-chip' + ( on ? ' is-on' : '' ) + '" data-scw-toggle="staff" data-value="' + SC.esc( s.user_id ) + '" aria-pressed="' + on + '">'
                 + '<i class="fa-solid fa-user me-2"></i>' + SC.esc( s.name ) + ( s.user_id === SC.me ? ' (me)' : '' ) + '</button>';
        } ).join( '' );
        $( '#scw_staff' ).html( html || '<div class="text-muted fs-7">Nobody can be assigned yet.</div>' );
    },

    /**
     * @description Draws the summary card of the last step
     * @returns {void}
     */
    renderSummary : function () {
        var a = SCW.answers;
        var list = function ( values, labels ) { return $.map( values, function ( v ) { return labels[ v ] || v; } ).join( ', ' ); };
        var models = $.map( a.models, function ( id ) {
            if ( id === 'unsure' ) { return 'Not sure'; }
            var m = ( SCW.models || [] ).filter( function ( x ) { return x.product_id === id; } )[ 0 ];
            return m ? m.series + ' ' + ( m.size ? m.size + '"' : m.name ) : id;
        } ).join( ', ' );
        var c = a.complainant;
        var rows = [
            [ 'Customer', a.customer ? a.customer.name : '' ],
            [ 'Complained', [ c.name, c.position ].filter( Boolean ).join( ', ' ) + ( c.phone ? ' · ' + c.phone : '' ) ],
            [ 'When / how', ( a.complained_date ? SC.showDate( a.complained_date ) + ' ' + a.complained_time : '' ) + ( a.channels.length ? ' · ' + list( a.channels, SCW.CHANNELS ) : '' ) ],
            [ 'TVs', a.tv_count + ( a.tv_count > 1 ? ( a.split ? ' · one case per TV' : ' · one case for all' ) : '' ) + ( a.serials.length ? ' · ' + a.serials.length + ' serial number(s)' : '' ) ],
            [ 'Models', models ],
            [ 'Warranty', SCW.WARRANTY[ a.warranty ] || '' ],
            [ 'Problem', list( a.problem_types, SCW.PROBLEMS ) + ( a.problem_types.length && a.problem ? ' · ' : '' ) + a.problem ],
            [ 'Photos / videos', SCW.gallery.count() ? String( SCW.gallery.count() ) : 'None' ]
        ];
        $( '#scw_summary' ).html( $.map( rows, function ( r ) {
            return '<div class="scw-sum-row"><div class="scw-sum-k">' + SC.esc( r[ 0 ] ) + '</div><div class="scw-sum-v">' + ( r[ 1 ] ? SC.esc( r[ 1 ] ) : '<span class="text-danger">Missing</span>' ) + '</div></div>';
        } ).join( '' ) );
    },

    // ── Answers ─────────────────────────────────────────────

    /**
     * @description Copies a field into the answers (data-scw-field="complainant.name" etc.)
     * @param {jQuery} $el - The input
     * @returns {void}
     */
    readField : function ( $el ) {
        var path = $el.attr( 'data-scw-field' ).split( '.' );
        var value = $el.val();
        if ( path[ 0 ] === 'tv_count' ) {
            value = Math.max( 2, Math.min( 500, parseInt( value, 10 ) || 2 ) );
            if ( $el.is( ':focus' ) && $el.val() === '' ) { return; }   // Let the box be emptied while typing
        }
        if ( path.length === 2 ) {
            SCW.answers[ path[ 0 ] ][ path[ 1 ] ] = value.trim();
            if ( path[ 0 ] === 'complainant' && path[ 1 ] === 'name' ) { SCW.answers.complainant.contact_id = 0; }
        } else {
            SCW.answers[ path[ 0 ] ] = ( typeof value === 'string' ) ? value.trim() : value;
        }
        if ( path[ 0 ] === 'tv_count' ) {
            if ( SCW.answers.tv_count < 2 && SCW.answers.warranty === 'mixed' ) { SCW.answers.warranty = ''; }
            SCW.renderTvs();
        }
        if ( path[ 0 ] === 'visit_from' ) {
            $( '#scw_visit_to' ).attr( 'min', SCW.answers.visit_from );
            if ( !SCW.answers.visit_to || SCW.answers.visit_to < SCW.answers.visit_from ) { SCW.answers.visit_to = SCW.answers.visit_from; $( '#scw_visit_to' ).val( SCW.answers.visit_to ); }
        }
        SCW.markDirty();
    },

    /**
     * @description Ticks / unticks a value of a list answer
     * @param {string} field - channels | problem_types | models | staff
     * @param {string} value - The value
     * @returns {void}
     */
    toggle : function ( field, value ) {
        var list = SCW.answers[ field ];
        var at = list.indexOf( value );
        if ( at === -1 ) { list.push( value ); } else { list.splice( at, 1 ); }
        if ( field === 'models' ) {
            // "Not sure" and real models exclude each other
            if ( value === 'unsure' && at === -1 ) { SCW.answers.models = [ 'unsure' ]; }
            if ( value !== 'unsure' ) { SCW.answers.models = SCW.answers.models.filter( function ( m ) { return m !== 'unsure'; } ); }
            SCW.renderTvs();
        } else {
            var $chip = $( '[data-scw-toggle="' + field + '"][data-value="' + value.replace( /"/g, '' ) + '"]' );
            $chip.toggleClass( 'is-on', at === -1 ).attr( 'aria-pressed', at === -1 );
        }
        SCW.markDirty();
    },

    /**
     * @description Chooses one option (many: one TV or more; split: one case or one per TV; warranty)
     * @param {string} what - many | split | warranty
     * @param {string} value - The option
     * @returns {void}
     */
    pick : function ( what, value ) {
        var a = SCW.answers;
        if ( what === 'many' ) {
            a.tv_count = ( value === '1' ) ? Math.max( 2, a.tv_count ) : 1;
            if ( value !== '1' ) { a.split = false; if ( a.warranty === 'mixed' ) { a.warranty = ''; } }
            $( '#scw_tv_count' ).val( a.tv_count );
            if ( value === '1' ) { setTimeout( function () { $( '#scw_tv_count' ).trigger( 'focus' ).trigger( 'select' ); }, 50 ); }
        }
        if ( what === 'split' ) { a.split = ( value === '1' ); }
        if ( what === 'warranty' ) { a.warranty = value; }
        SCW.renderTvs();
        SCW.markDirty();
    },

    /**
     * @description Date and time now in Singapore (the complaint time is stored as Singapore time, whatever the device's zone)
     * @returns {object} { date: 'YYYY-MM-DD', time: 'HH:MM' }
     */
    singaporeNow : function () {
        // Singapore is UTC+8 all year (no daylight saving)
        var sg = new Date( Date.now() + 8 * 3600000 );
        var pad = function ( n ) { return ( n < 10 ? '0' : '' ) + n; };
        return { date: sg.getUTCFullYear() + '-' + pad( sg.getUTCMonth() + 1 ) + '-' + pad( sg.getUTCDate() ),
                 time: pad( sg.getUTCHours() ) + ':' + pad( sg.getUTCMinutes() ) };
    },

    /**
     * @description Today in Singapore as 'YYYY-MM-DD'
     * @returns {string}
     */
    todayText : function () { return SCW.singaporeNow().date; },

    // ── Customer and complainant ────────────────────────────

    /**
     * @description Searches customers while typing
     * @param {string} q - Search text
     * @returns {void}
     */
    searchCustomers : function ( q ) {
        var $results = $( '#scw_customer_results' );
        if ( q.length < 2 ) { $results.addClass( 'd-none' ); return; }
        SC.post( { what_do_you_want: 'appstv_case_customer_search', q: q } ).then( function ( info ) {
            if ( $( '#scw_customer_search' ).val().trim() !== q ) { return; }   // An older answer
            var list = info.data.customers;
            var html = '';
            $.each( list, function ( i, c ) {
                html += '<div class="sc-dropdown-item" data-customer-index="' + i + '"><div class="fw-semibold">' + SC.esc( c.name || '(no name)' ) + '</div>'
                     + '<div class="text-muted fs-8">' + SC.esc( [ c.person, c.mobile, c.email ].filter( Boolean ).join( ' · ' ) ) + '</div></div>';
            } );
            html += '<div class="sc-dropdown-item scw-dropdown-add" data-scw-add-customer="1"><i class="fa-solid fa-plus me-2"></i>'
                 + ( list.length ? 'Not in the list? Add a new customer' : 'No customer found. Add a new customer' ) + '</div>';
            $results.data( 'customers', list ).html( html ).removeClass( 'd-none' );
        } ).catch( SC.toastError );
    },

    /**
     * @description Sets (or clears) the customer; a new customer's contact person is offered as complainant
     * @param {object|null} c - { customer_id, name, person, mobile, email }
     * @returns {void}
     */
    setCustomer : function ( c ) {
        var before = SCW.answers.customer ? SCW.answers.customer.customer_id : null;
        SCW.answers.customer = c ? { customer_id: c.customer_id, name: c.name, sub: c.sub || [ c.person, c.mobile, c.email ].filter( Boolean ).join( ' · ' ) } : null;
        if ( c && before !== c.customer_id && SCW.answers.complainant.contact_id ) {
            // A complainant picked for another customer does not belong to this one
            SCW.answers.complainant.contact_id = 0;
        }
        SCW.renderCustomer();
        SCW.markDirty();
        if ( c && !SCW.answers.complainant.name ) { setTimeout( function () { $( '#scw_c_name' ).trigger( 'focus' ); }, 50 ); }
    },

    /**
     * @description Shows / hides the "Add new customer" form (prefilled from the search text)
     * @param {boolean} visible - Show it
     * @returns {void}
     */
    showAddCustomer : function ( visible ) {
        $( '#scw_add_customer' ).toggleClass( 'd-none', !visible );
        $( '#scw_add_customer_open' ).toggleClass( 'd-none', visible );
        $( '#scw_similar' ).empty();
        if ( visible ) {
            var q = $( '#scw_customer_search' ).val().trim();
            $( '#scw_nc_company, #scw_nc_first, #scw_nc_last, #scw_nc_mobile, #scw_nc_email' ).val( '' );
            if ( /^[0-9+\-() ]{6,}$/.test( q ) ) { $( '#scw_nc_mobile' ).val( q ); } else if ( q.indexOf( '@' ) !== -1 ) { $( '#scw_nc_email' ).val( q ); } else { $( '#scw_nc_company' ).val( q ); }
            $( '#scw_customer_results' ).addClass( 'd-none' );
            setTimeout( function () { $( '#scw_nc_company' ).trigger( 'focus' ); }, 50 );
        }
    },

    /**
     * @description Adds the new customer; a similar existing customer is offered first
     * @param {boolean} confirm - Add even when similar customers exist
     * @returns {void}
     */
    addCustomer : function ( confirm ) {
        var data = {
            what_do_you_want : 'appstv_case_customer_add',
            company_name : $( '#scw_nc_company' ).val().trim(), first_name : $( '#scw_nc_first' ).val().trim(), last_name : $( '#scw_nc_last' ).val().trim(),
            mobile : $( '#scw_nc_mobile' ).val().trim(), email : $( '#scw_nc_email' ).val().trim(), confirm : confirm ? '1' : '0'
        };
        if ( !data.company_name && !data.first_name ) { showSimpleToast( 'warning', 'Enter the company name or the contact person.' ); return; }
        var $btn = $( '#scw_add_customer_save' );
        showLoadingOnButton( $btn );
        SC.post( data ).then( function ( info ) {
            hideLoadingOnButton( $btn );
            if ( info.data.added ) {
                showSimpleToast( 'success', info.message );
                SCW.setCustomer( info.data.customer );
                SCW.showAddCustomer( false );
                if ( !SCW.answers.complainant.name && info.data.customer.person ) {
                    SCW.useContact( { contact_id: 0, name: info.data.customer.person, phone: info.data.customer.mobile, email: info.data.customer.email, position: '' } );
                }
                return;
            }
            var html = '<div class="alert alert-light-warning py-3 mb-0"><div class="fw-semibold mb-2">' + SC.esc( info.message ) + ' Is it one of these?</div>';
            $.each( info.data.similar, function ( i, c ) {
                html += '<div class="sc-found-tv"><div><div class="fw-semibold">' + SC.esc( c.name ) + '</div><div class="text-muted fs-8">'
                     + SC.esc( [ c.person, c.mobile, c.email ].filter( Boolean ).join( ' · ' ) ) + '</div></div>'
                     + '<button type="button" class="btn btn-sm btn-primary" data-use-similar="' + i + '">Use this one</button></div>';
            } );
            html += '<button type="button" class="btn btn-sm btn-light-warning mt-2" data-add-anyway="1">No, add a new customer</button></div>';
            $( '#scw_similar' ).data( 'similar', info.data.similar ).html( html );
        } ).catch( function ( message ) { hideLoadingOnButton( $btn ); SC.toastError( message ); } );
    },

    /**
     * @description Shows the people who complained before for the chosen customer, filtered by the typed name
     * @param {string} q - Typed name
     * @returns {void}
     */
    suggestContacts : function ( q ) {
        var $results = $( '#scw_contact_results' );
        if ( !SCW.answers.customer ) { $results.addClass( 'd-none' ); return; }
        SC.post( { what_do_you_want: 'appstv_case_contacts', customer_id: SCW.answers.customer.customer_id, q: q } ).then( function ( info ) {
            var list = info.data.contacts;
            if ( !list.length || !$( '#scw_c_name' ).is( ':focus' ) ) { $results.addClass( 'd-none' ); return; }
            var html = '<div class="sc-dropdown-empty py-2">' + ( q ? 'Matching people' : 'Complained before for this customer' ) + '</div>';
            $.each( list, function ( i, c ) {
                html += '<div class="sc-dropdown-item" data-contact-index="' + i + '"><div class="fw-semibold">' + SC.esc( c.name )
                     + ( c.source === 'customer' ? ' <span class="badge badge-light fs-9 ms-1">customer contact</span>' : '' ) + '</div>'
                     + '<div class="text-muted fs-8">' + SC.esc( [ c.position, c.phone, c.email ].filter( Boolean ).join( ' · ' ) ) + '</div></div>';
            } );
            $results.data( 'contacts', list ).html( html ).removeClass( 'd-none' );
        } ).catch( function () { $results.addClass( 'd-none' ); } );
    },

    /**
     * @description Fills the complainant from a suggestion
     * @param {object} c - { contact_id, name, phone, email, position }
     * @returns {void}
     */
    useContact : function ( c ) {
        SCW.answers.complainant = { contact_id: c.contact_id || 0, name: c.name || '', phone: c.phone || '', email: c.email || '', position: c.position || '' };
        $( '#scw_c_name' ).val( c.name );
        $( '#scw_c_phone' ).val( c.phone );
        $( '#scw_c_email' ).val( c.email );
        $( '#scw_c_position' ).val( c.position );
        $( '#scw_contact_results' ).addClass( 'd-none' );
        SCW.markDirty();
    },

    // ── Saving ──────────────────────────────────────────────

    /**
     * @description True when the answers hold something worth a draft (so opening and closing an empty wizard leaves none)
     * @returns {boolean}
     */
    worthSaving : function () {
        var a = SCW.answers;
        return !!( a.customer || a.complainant.name || a.problem || a.models.length || a.problem_types.length || a.serials.length || a.staff.length );
    },

    /**
     * @description Marks a change: keeps the local copy and plans the autosave
     * @returns {void}
     */
    markDirty : function () {
        if ( SCW.answers === null ) { return; }
        SCW.dirty = true;
        SCW.writeBackup();
        if ( SCW.worthSaving() ) { SCW.setSavedText( 'Saving soon…' ); }
        clearTimeout( SCW.saveTimer );
        SCW.saveTimer = setTimeout( SCW.saveNow, SCW.AUTOSAVE_MS );
    },

    /**
     * @description Saves the draft on the server now (one save at a time; a change during a save saves again after it)
     * @returns {Promise} Resolves when the answers are saved (or there was nothing to save)
     */
    saveNow : function () {
        clearTimeout( SCW.saveTimer );
        if ( SCW.saving ) { SCW.saveAgain = true; return SCW.saving; }
        if ( !SCW.dirty || !SCW.worthSaving() || SCW.opening ) { return Promise.resolve(); }
        SCW.dirty = false;
        SCW.setSavedText( 'Saving…' );
        var sentFor = SCW.draftId;
        SCW.saving = SC.post( { what_do_you_want: 'appstv_case_draft_save', draft_id: SCW.draftId, revision: SCW.revision,
                                step: SCW.STEPS[ SCW.stepIndex ].id, answers: JSON.stringify( SCW.answers ) } )
            .then( function ( info ) {
                SCW.saving = null;
                if ( sentFor !== SCW.draftId ) { return; }   // The wizard moved to another draft meanwhile
                var d = info.data;
                if ( d.conflict ) { SCW.onConflict( d.draft ); return; }
                SCW.draftId = d.draft_id;
                SCW.revision = d.revision;
                SCW.setSavedText( 'Draft saved ' + new Date( d.saved_on ).toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit' } ) );
                if ( SCW.dirty ) { SCW.writeBackup(); } else { SCW.clearBackupOf( SCW.draftId ); SCW.clearBackupOf( 0 ); }
                if ( SCW.saveAgain ) { SCW.saveAgain = false; return SCW.saveNow(); }
            } )
            .catch( function ( message ) {
                SCW.saving = null;
                SCW.dirty = true;                              // Try again on the next change / step
                SCW.setSavedText( 'Not saved (' + message + ') · kept on this device' );
                throw message;
            } );
        return SCW.saving;
    },

    /**
     * @description Someone else saved the draft in between: show their version
     * @param {object} draft - The newer draft (appstv_case_draft_item)
     * @returns {void}
     */
    onConflict : function ( draft ) {
        Swal.fire( { icon: 'info', title: 'Someone else changed this draft',
                     html: SC.esc( ( draft.updated_by || 'Another user' ) + ' saved it at ' + SC.showStamp( draft.updated_on ) + '.' ) + '<br/>Their version is shown now; check it and continue.',
                     buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
        SCW.clearBackupOf( draft.draft_id );
        var index = SCW.stepIndex;
        SCW.draftId = draft.draft_id;
        SCW.revision = draft.revision;
        SCW.answers = $.extend( true, SCW.emptyAnswers(), draft.answers );
        SCW.dirty = false;
        SCW.renderAll();
        SCW.goTo( index, false );
        SCW.setSavedText( 'Loaded the version of ' + ( draft.updated_by || 'another user' ) );
    },

    /**
     * @description Saves and closes the wizard
     * @param {boolean} asked - True for the "Save as draft" button (says where to find it)
     * @returns {void}
     */
    saveAndClose : function ( asked ) {
        if ( SCW.gallery.busy() ) {
            Swal.fire( { icon: 'warning', title: 'Photos / videos not uploaded yet', text: 'Upload them (or remove them) on the Photos step before closing.', buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
            SCW.goTo( SCW.indexOfStep( 5 ), false );
            return;
        }
        var close = function () {
            bootstrap.Modal.getOrCreateInstance( document.getElementById( 'scw_modal' ) ).hide();
            SCW.answers = null;
            SCW.loadDrafts();
        };
        if ( !SCW.worthSaving() ) {
            if ( asked ) { showSimpleToast( 'warning', 'Nothing to save yet.' ); return; }
            SCW.clearBackupOf( SCW.draftId );
            close();
            return;
        }
        SCW.dirty = SCW.dirty || asked;
        SCW.saveNow().then( function () {
            if ( SCW.saving ) { return SCW.saving; }
        } ).then( function () {
            showSimpleToast( 'success', 'Draft saved. Find it under Drafts on this page.' );
            close();
        } ).catch( function () {
            showConfirmSweetAlert( 'The draft could not be saved', 'Your answers stay on this device and are offered the next time you open a case. Close anyway?', 'warning',
                'Close', 'btn btn-warning', 'Stay', 'btn btn-light', close );
        } );
    },

    /**
     * @description Text next to the title: "Draft saved 10:42", "Saving…"
     * @param {string} text - Text
     * @returns {void}
     */
    setSavedText : function ( text ) { $( '#scw_saved' ).text( text ); },

    // ── Local copy (this browser) ───────────────────────────

    /**
     * @description Keeps the unsaved answers in this browser
     * @returns {void}
     */
    writeBackup : function () {
        try {
            localStorage.setItem( SCW.BACKUP_KEY, JSON.stringify( { draftId: SCW.draftId, revision: SCW.revision, stepIndex: SCW.stepIndex, answers: SCW.answers, at: Date.now() } ) );
        } catch ( e ) { /* Storage blocked or full: the server autosave still works */ }
    },

    /**
     * @description Reads the unsaved copy of this browser
     * @returns {object|null} { draftId, revision, stepIndex, answers, at }
     */
    readBackup : function () {
        try {
            var b = JSON.parse( localStorage.getItem( SCW.BACKUP_KEY ) || 'null' );
            return ( b && b.answers && typeof b.answers === 'object' ) ? b : null;
        } catch ( e ) { return null; }
    },

    /**
     * @description Forgets the local copy
     * @returns {void}
     */
    clearBackup : function () {
        try { localStorage.removeItem( SCW.BACKUP_KEY ); } catch ( e ) { /* nothing to clear */ }
    },

    /**
     * @description Forgets the local copy when it belongs to a draft
     * @param {number} draftId - Draft id (0 = a case not saved yet)
     * @returns {void}
     */
    clearBackupOf : function ( draftId ) {
        var b = SCW.readBackup();
        if ( b && b.draftId === draftId ) { SCW.clearBackup(); }
    },

    // ── Opening ─────────────────────────────────────────────

    /**
     * @description OPEN CASE: sends the answers; missing answers are shown with their step
     * @returns {void}
     */
    openCase : function () {
        if ( !SCW.checkStep( true ) || SCW.opening ) { return; }
        if ( SCW.gallery.busy() ) {
            Swal.fire( { icon: 'warning', title: 'Photos / videos not uploaded yet', text: 'Upload them (or remove them) first.', buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
            SCW.goTo( SCW.indexOfStep( 5 ), false );
            return;
        }
        var $btn = $( '#scw_open' );
        var send = function () {
            SCW.opening = true;
            showLoadingOnButton( $btn );
            return SC.post( { what_do_you_want: 'appstv_case_create', draft_id: SCW.draftId, revision: SCW.revision, answers: JSON.stringify( SCW.answers ) } );
        };
        clearTimeout( SCW.saveTimer );
        ( SCW.saving || Promise.resolve() ).catch( function () {} ).then( send ).then( function ( info ) {
            SCW.opening = false;
            hideLoadingOnButton( $btn );
            var d = info.data;
            if ( d.conflict ) { SCW.onConflict( d.draft ); return; }
            if ( !d.opened ) {
                var first = d.errors[ 0 ].step;
                Swal.fire( { icon: 'warning', title: 'Some answers are missing', html: $.map( d.errors, function ( e ) { return SC.esc( e.message ); } ).join( '<br/>' ),
                             buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
                SCW.goTo( SCW.indexOfStep( first ), false );
                return;
            }
            SCW.clearBackupOf( SCW.draftId );
            SCW.clearBackupOf( 0 );
            SCW.dirty = false;
            SCW.answers = null;
            bootstrap.Modal.getOrCreateInstance( document.getElementById( 'scw_modal' ) ).hide();
            showSimpleToast( 'success', info.message );
            SCW.loadDrafts();
            SC.loadList();
            SC.openCase( d.case_id );
        } ).catch( function ( message ) {
            SCW.opening = false;
            hideLoadingOnButton( $btn );
            Swal.fire( { icon: 'error', title: 'Not opened', text: message + ' Your answers are kept.', buttonsStyling: false, customClass: { confirmButton: 'btn btn-danger' } } );
        } );
    },

    // ── Drafts list ─────────────────────────────────────────

    /**
     * @description Loads and draws the drafts of cases not opened yet
     * @returns {void}
     */
    loadDrafts : function () {
        SC.post( { what_do_you_want: 'appstv_case_draft_list' } ).then( function ( info ) {
            var drafts = info.data.drafts;
            $( '#sc_drafts' ).toggleClass( 'd-none', drafts.length === 0 );
            $( '#sc_drafts_count' ).text( drafts.length );
            $( '#sc_drafts_list' ).html( $.map( drafts, function ( d ) {
                var step = SCW.STEPS[ SCW.indexOfStep( d.step ) ];
                return '<div class="sc-draft">'
                     + '<div class="sc-draft-main"><div class="fw-bold text-gray-900">' + SC.esc( d.label ) + '</div>'
                     + ( d.problem ? '<div class="text-muted fs-7 sc-draft-problem">' + SC.esc( d.problem ) + '</div>' : '' )
                     + '<div class="text-muted fs-8 mt-1">Started by ' + SC.esc( d.created_by || '—' ) + ' · ' + SC.esc( SC.showStamp( d.created_on ) )
                     + ( d.updated_on !== d.created_on ? ' · last change ' + SC.esc( d.updated_by || '—' ) + ', ' + SC.esc( SC.showStamp( d.updated_on ) ) : '' )
                     + ' · at step “' + SC.esc( step.title ) + '”' + ( d.tv_count > 1 ? ' · ' + d.tv_count + ' TVs' : '' ) + ( d.media ? ' · ' + d.media + ' photo(s) / video(s)' : '' ) + '</div></div>'
                     + '<div class="sc-draft-actions">'
                     + '<button type="button" class="btn btn-sm btn-icon btn-light" data-draft-history="' + d.draft_id + '" title="History" aria-label="History"><i class="fa-solid fa-clock-rotate-left"></i></button>'
                     + '<button type="button" class="btn btn-sm btn-icon btn-light-danger" data-draft-delete="' + d.draft_id + '" data-label="' + SC.esc( d.label ) + '" title="Delete" aria-label="Delete"><i class="fa-solid fa-trash-can"></i></button>'
                     + '<button type="button" class="btn btn-sm btn-primary" data-draft-continue="' + d.draft_id + '">Continue</button>'
                     + '</div></div>';
            } ).join( '' ) );
        } ).catch( function () { /* The list stays as it was */ } );
    },

    /**
     * @description Deletes a draft after confirmation
     * @param {number} draftId - Draft
     * @param {string} label - Its customer
     * @returns {void}
     */
    deleteDraft : function ( draftId, label ) {
        showConfirmSweetAlert( 'Delete this draft?', 'The draft for ' + SC.esc( label ) + ' will be removed for everyone.', 'warning', 'Delete', 'btn btn-danger', 'Cancel', 'btn btn-light',
            function () {
                SC.post( { what_do_you_want: 'appstv_case_draft_delete', draft_id: draftId } ).then( function ( info ) {
                    SCW.clearBackupOf( draftId );
                    showSimpleToast( 'success', info.message );
                    SCW.loadDrafts();
                } ).catch( SC.toastError );
            } );
    },

    /**
     * @description Shows who changed a draft and when
     * @param {number} draftId - Draft
     * @returns {void}
     */
    showDraftHistory : function ( draftId ) {
        SC.post( { what_do_you_want: 'appstv_case_draft_get', draft_id: draftId } ).then( function ( info ) {
            Swal.fire( { title: 'Draft history', html: '<div class="text-start sc-history">' + SCH.listHtml( info.data.draft.history ) + '</div>', width: 640,
                         buttonsStyling: false, customClass: { confirmButton: 'btn btn-primary' } } );
        } ).catch( SC.toastError );
    }
};


/* =============================================
   SCH - change history lines as readable text (drafts and cases)
   ============================================= */
var SCH = {

    FIELDS : { customer: 'Customer', complainant: 'Complainant', complained_date: 'Complaint date', complained_time: 'Complaint time', channels: 'How they complained',
               tv_count: 'Number of TVs', split: 'One case per TV', models: 'TV models', warranty: 'Warranty', warranty_status: 'Warranty',
               problem_types: 'Problem', problem: 'Description', serials: 'Serial numbers', staff: 'Staff', visit_from: 'Visit from', visit_to: 'Visit to' },
    ACTIONS : { draft_created: 'Started the draft', draft_deleted: 'Deleted the draft', opened: 'Opened the case' },

    /**
     * @description Readable value of a history field
     * @param {string} field - Field
     * @param {string|null} raw - Stored value (JSON for lists / objects)
     * @returns {string}
     */
    value : function ( field, raw ) {
        if ( raw === null || raw === '' ) { return '(empty)'; }
        var v = raw;
        if ( /^[\[{]/.test( raw ) ) { try { v = JSON.parse( raw ); } catch ( e ) { v = raw; } }
        if ( field === 'customer' ) { return v && v.name ? v.name : '(none)'; }
        if ( field === 'complainant' ) { return v ? [ v.name, v.position, v.phone, v.email ].filter( Boolean ).join( ', ' ) || '(empty)' : '(empty)'; }
        if ( field === 'serials' ) { return $.isArray( v ) ? ( $.map( v, function ( s ) { return s.serial_number; } ).join( ', ' ) || '(none)' ) : String( v ); }
        if ( /^(visit_from|visit_to|complained_date)$/.test( field ) ) { return SC.showDate( String( v ) ); }
        if ( field === 'split' ) { return v === true || v === 'true' || v === '1' ? 'Yes' : 'No'; }
        if ( field === 'warranty' || field === 'warranty_status' ) { return SCW.WARRANTY[ v ] || String( v ); }
        if ( $.isArray( v ) ) {
            var labels = { channels: SCW.CHANNELS, problem_types: SCW.PROBLEMS }[ field ];
            if ( field === 'staff' ) { return $.map( v, function ( id ) { return SC.staffName( id ) || id; } ).join( ', ' ) || '(none)'; }
            if ( field === 'models' ) {
                return $.map( v, function ( id ) {
                    if ( id === 'unsure' ) { return 'Not sure'; }
                    var m = ( SCW.models || [] ).filter( function ( x ) { return x.product_id === id; } )[ 0 ];
                    return m ? m.series + ' ' + m.size + '"' : id;
                } ).join( ', ' ) || '(none)';
            }
            return $.map( v, function ( x ) { return labels && labels[ x ] ? labels[ x ] : x; } ).join( ', ' ) || '(none)';
        }
        return String( v );
    },

    /**
     * @description HTML of history lines (newest first)
     * @param {Array} lines - [ { action, field, old, new, detail, by, on } ]
     * @returns {string}
     */
    listHtml : function ( lines ) {
        if ( !lines || !lines.length ) { return '<div class="text-muted fs-7">No history yet.</div>'; }
        return $.map( lines, function ( h ) {
            var what;
            if ( h.action === 'changed' ) {
                what = '<b>' + SC.esc( SCH.FIELDS[ h.field ] || h.field ) + '</b>: ' + SC.esc( SCH.value( h.field, h.old ) ) + ' <i class="fa-solid fa-arrow-right-long mx-1 text-muted"></i> ' + SC.esc( SCH.value( h.field, h.new ) );
            } else {
                what = '<b>' + SC.esc( SCH.ACTIONS[ h.action ] || h.action.replace( /_/g, ' ' ) ) + '</b>';
            }
            return '<div class="sc-history-line"><div>' + what + ( h.detail && h.action !== 'draft_created' && h.action !== 'draft_deleted' ? '<div class="text-muted fs-8">' + SC.esc( h.detail ) + '</div>' : '' ) + '</div>'
                 + '<div class="text-muted fs-8 text-nowrap">' + SC.esc( h.by ) + ' · ' + SC.esc( SC.showStamp( h.on ) ) + '</div></div>';
        } ).join( '' );
    }
};

$( document ).ready( function () {
    SCW.init();
} );
