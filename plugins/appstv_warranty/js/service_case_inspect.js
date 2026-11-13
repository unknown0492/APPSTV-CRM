"use strict";

/* =============================================
   service_case_inspect.js (v1.0.32)
   Plugin: appstv_warranty - inspection of each TV on site (TVs tab of the case window)

   SCI draws, inside the TV card, the answered steps (each can be changed) and the step to answer now:
     warranty (only when the serial gives none) > outside damage > paid repair? > can it be opened here >
     liquid inside > repair on site
   Every step is saved at once (appstv_case_inspect); the server sends back the next step and the result, so the
   rules live in one place. Damage / working photos are added in the step (camera or files, the crop editor) and
   belong to the TV and the step. Only one TV is inspected at a time.
   1.0.33: "To be taken to the office" -> the customer signs the consent (SGN, below) -> "At the office".
   ============================================= */

var SCI = {
    tvId : null,                                               // service_case_tvs.id being inspected (panel open)
    view : null,                                               // Step being changed (null = the next step)
    draft : {},                                                // Answer being made: { value, kinds, note, reason, part_name }
    gallery : null,                                            // SCMedia of the photo step shown
    photos : {},                                               // Photos uploaded since the case was loaded: { 'tv|purpose': [ media ] }
    saving : false,                                            // A step is being sent

    PURPOSE : { outside: 'outside_damage', inside: 'inside_liquid', repair: 'working' },
    STEP_TITLES : { warranty: 'Warranty', outside: 'Outside check', paid: 'Paid repair', open_here: 'Open the TV here', inside: 'Inside check', repair: 'Repair on site' },
    RESULT_BADGE : { not_covered: 'badge-sc-bad', declined: 'badge-sc-bad', repaired: 'badge-sc-ok', waiting_part: 'badge-sc-amber',
                     to_office: 'badge-sc-window', at_office: 'badge-sc-office', revisit: 'badge-sc-amber' },

    /**
     * @description The inspection of a TV of the open case
     * @param {number} tvId - service_case_tvs.id
     * @returns {object|null}
     */
    of : function ( tvId ) {
        var list = ( SC.current && SC.current.inspections ) || {};
        return list[ String( tvId ) ] || null;
    },

    /**
     * @description Badge of an inspection result ('' when not inspected)
     * @param {object|null} x - Inspection
     * @returns {string} HTML
     */
    badge : function ( x ) {
        if ( !x ) { return ''; }
        if ( !x.result ) { return '<span class="badge badge-light-primary">Inspection started</span>'; }
        return '<span class="badge ' + ( SCI.RESULT_BADGE[ x.result ] || 'badge-light' ) + '">' + SC.esc( x.result_text ) + '</span>';
    },

    /**
     * @description Badge of a result key (TV cases list of a main case)
     * @param {string|null} result - Result key, '' = started, null = not inspected
     * @returns {string} HTML
     */
    resultBadge : function ( result ) {
        if ( result === null || result === undefined ) { return ''; }
        var texts = ( SC.current && SC.current.inspect_results ) || {};
        if ( result === '' ) { return '<span class="badge badge-light-primary">Inspection started</span>'; }
        return '<span class="badge ' + ( SCI.RESULT_BADGE[ result ] || 'badge-light' ) + '">' + SC.esc( texts[ result ] || result ) + '</span>';
    },

    /**
     * @description The part of a TV card that belongs to the inspection: service charge, Inspect button, panel
     * @param {object} tv - TV of the case ({ id, chain, ... })
     * @param {boolean} closed - The case is closed
     * @returns {string} HTML
     */
    cardHtml : function ( tv, closed ) {
        var d = SC.current, x = SCI.of( tv.id );
        var html = '';
        if ( x && x.charge ) {
            var done = ( x.charge === 'collected' );
            html += '<div class="sci-charge' + ( done ? ' is-done' : '' ) + '"><i class="fa-solid ' + ( done ? 'fa-circle-check' : 'fa-hand-holding-dollar' ) + '"></i>'
                  + '<span>' + ( done ? 'Service charge collected' + ( x.charge_by ? ' by ' + SC.esc( x.charge_by ) : '' ) + ( x.charge_on ? ', ' + SC.esc( SC.showStamp( x.charge_on ) ) : '' )
                                      : 'Service charge to collect' + ( x.warranty === 'out' ? ' (out of warranty)' : ' (paid repair)' ) ) + '</span>'
                  + ( d.can_inspect && !closed ? '<button type="button" class="btn btn-sm btn-light" data-sci="charge" data-tv-id="' + tv.id + '" data-collected="' + ( done ? 0 : 1 ) + '">'
                                                 + ( done ? 'Undo' : 'Mark collected' ) + '</button>' : '' ) + '</div>';
        }
        // 1.0.33: the customer's consent before the TV is taken / the signed consent
        if ( x && x.result === 'to_office' && !x.consent ) {
            html += '<div class="sci-strip is-sign"><i class="fa-solid fa-signature"></i><span>The customer signs the consent before the TV is taken to the office</span>'
                  + ( SCI.canOpen( tv, closed ) ? '<button type="button" class="btn btn-sm btn-primary" data-sci="consent" data-tv-id="' + tv.id + '">Customer signs</button>' : '' ) + '</div>';
        }
        if ( x && x.consent ) {
            var k = x.consent;
            html += '<div class="sci-strip is-office"><i class="fa-solid fa-building"></i><span>At the office · consent signed by ' + SC.esc( ( k.title ? k.title + ' ' : '' ) + k.signer_name )
                  + ', ' + SC.esc( SGN.sgt( k.signed_on ) ) + ' · taken by ' + SC.esc( k.staff_name ) + '</span>'
                  + '<button type="button" class="btn btn-sm btn-light" data-sci="view-consent" data-tv-id="' + tv.id + '"><i class="fa-solid fa-print"></i> View / print</button></div>';
        }
        if ( !SCI.canOpen( tv, closed ) && x && SCI.tvId !== tv.id ) {
            html += '<div class="sci-summary">' + SCI.summaryHtml( x, false ) + '</div>';
        }
        html += '<div class="sci-panel" id="sci_panel_' + tv.id + '"></div>';
        return html;
    },

    /**
     * @description The TV's inspection can be opened (permission, case open, the TV not replaced already)
     * @param {object} tv - TV of the case
     * @param {boolean} closed - The case is closed
     * @returns {boolean}
     */
    canOpen : function ( tv, closed ) {
        return !!SC.current.can_inspect && !closed && ( tv.chain === null || tv.chain.is_current );
    },

    /**
     * @description The Inspect button of a TV card (first in its button row; '' when it cannot be opened)
     * @param {object} tv - TV of the case
     * @param {boolean} closed - The case is closed
     * @returns {string} HTML
     */
    buttonHtml : function ( tv, closed ) {
        if ( !SCI.canOpen( tv, closed ) || SCI.tvId === tv.id ) { return ''; }
        var x = SCI.of( tv.id );
        var label = !x ? 'Inspect' : ( x.result ? 'View inspection' : 'Continue inspection' );
        return '<button type="button" class="btn btn-sm sc-btn-finish" data-sci="open" data-tv-id="' + tv.id + '"><i class="fa-solid fa-magnifying-glass"></i> ' + label + '</button>';
    },

    /**
     * @description Binds the events (once, on the case window)
     * @returns {void}
     */
    bind : function () {
        var $m = $( '#sc_case_modal' );
        $m.on( 'click', '[data-sci="open"]', function () { SCI.open( +$( this ).attr( 'data-tv-id' ) ); } );
        $m.on( 'click', '[data-sci="close"]', function () { SCI.close(); } );
        $m.on( 'click', '[data-sci="charge"]', function () { SCI.charge( +$( this ).attr( 'data-tv-id' ), $( this ).attr( 'data-collected' ) ); } );
        $m.on( 'click', '[data-sci="change"]', function () { SCI.change( $( this ).attr( 'data-step' ) ); } );
        $m.on( 'click', '[data-sci="back"]', function () { SCI.view = null; SCI.render(); } );
        $m.on( 'click', '[data-sci="save"]', function () { SCI.save(); } );
        $m.on( 'click', '[data-sci-pick]', function () {
            SCI.draft.value = $( this ).attr( 'data-value' );
            SCI.render( true );
        } );
        $m.on( 'click', '[data-sci-kind]', function () {
            var k = $( this ).attr( 'data-sci-kind' ), kinds = SCI.draft.kinds || [];
            SCI.draft.kinds = kinds.indexOf( k ) === -1 ? kinds.concat( [ k ] ) : kinds.filter( function ( v ) { return v !== k; } );
            $( this ).toggleClass( 'is-on' ).attr( 'aria-pressed', SCI.draft.kinds.indexOf( k ) !== -1 );
        } );
        $m.on( 'click', '[data-sci-reason]', function () {
            SCI.draft.reason = $( this ).attr( 'data-sci-reason' );
            SCI.render( true );
        } );
        $m.on( 'input', '[data-sci-text]', function () { SCI.draft[ $( this ).attr( 'data-sci-text' ) ] = $( this ).val(); } );
        $( '#sc_case_modal' ).on( 'hidden.bs.modal', function () { SCI.tvId = null; SCI.view = null; SCI.photos = {}; } );
    },

    /**
     * @description Opens the inspection of a TV (starts it on the server the first time)
     * @param {number} tvId - service_case_tvs.id
     * @returns {void}
     */
    open : function ( tvId ) {
        if ( SCI.tvId !== null && SCI.tvId !== tvId && SCI.gallery && SCI.gallery.busy() ) {
            showSimpleToast( 'warning', 'Upload (or remove) the waiting photos of the other TV first.' );
            return;
        }
        SC.post( { what_do_you_want: 'appstv_case_inspect', case_id: SC.current.case.id, case_tv_id: tvId, step: 'start' } ).then( function ( info ) {
            SC.current.inspections[ String( tvId ) ] = info.data.inspection;
            SCI.tvId = tvId;
            SCI.view = null;
            SC.renderCase();                                   // Redraws the cards; mount() fills the panel
            var el = document.getElementById( 'sci_panel_' + tvId );
            if ( el ) { el.scrollIntoView( { behavior: 'smooth', block: 'start' } ); }
        } ).catch( SC.toastError );
    },

    /**
     * @description Closes the panel (photos waiting to upload keep it open)
     * @returns {void}
     */
    close : function () {
        if ( SCI.gallery && SCI.gallery.busy() ) { showSimpleToast( 'warning', 'Upload (or remove) the waiting photos first.' ); return; }
        SCI.tvId = null;
        SCI.view = null;
        SCI.gallery = null;
        SC.reloadCase();                                       // Photos & videos tab, timeline, badges
    },

    /**
     * @description Fills the panel after the case window was drawn (called at the end of SC.renderCase)
     * @returns {void}
     */
    mount : function () {
        if ( SCI.tvId === null ) { return; }
        if ( !$( '#sci_panel_' + SCI.tvId ).length || !SCI.of( SCI.tvId ) ) { SCI.tvId = null; return; }
        SCI.render();
    },

    /**
     * @description Starts changing an answered step
     * @param {string} step - Step key
     * @returns {void}
     */
    change : function ( step ) {
        SCI.view = step;
        SCI.render();
    },

    /**
     * @description The step shown now
     * @param {object} x - Inspection
     * @returns {string} Step key, '' = finished
     */
    shownStep : function ( x ) { return SCI.view || x.next; },

    /**
     * @description Answer of a step as stored (to start the draft from)
     * @param {object} x - Inspection
     * @param {string} step - Step key
     * @returns {object}
     */
    stored : function ( x, step ) {
        if ( step === 'warranty' ) { return { value: x.warranty }; }
        if ( step === 'outside' ) { return { value: x.outside, kinds: x.outside_kinds.slice(), note: x.outside_note }; }
        if ( step === 'paid' ) { return { value: x.paid }; }
        if ( step === 'open_here' ) { return { value: x.open_here, reason: x.no_open_reason, note: x.no_open_note }; }
        if ( step === 'inside' ) { return { value: x.inside, note: x.inside_note }; }
        if ( step === 'repair' ) { return { value: x.repair, note: x.repair_note, part_name: x.part_name }; }
        return {};
    },

    /**
     * @description Why a paid repair is asked
     * @param {object} x - Inspection
     * @returns {string} warranty | outside | inside | ''
     */
    paidCause : function ( x ) {
        if ( x.warranty === 'out' ) { return 'warranty'; }
        if ( x.outside === 'damage' ) { return 'outside'; }
        if ( x.inside === 'liquid' ) { return 'inside'; }
        return '';
    },

    /**
     * @description The answered steps in the order they were asked: [ [ step, answer text ] ]
     * @param {object} x - Inspection
     * @returns {Array}
     */
    answered : function ( x ) {
        var rows = [], reasons = SC.current.inspect_no_open || {};
        var cause = SCI.paidCause( x );
        var paidText = x.paid === 'yes' ? 'Customer wants a paid repair' : 'Customer does not want a paid repair';
        if ( x.warranty ) { rows.push( [ 'warranty', x.warranty === 'under' ? 'Under warranty' : 'Out of warranty' ] ); }
        if ( x.warranty === 'under' && x.outside ) {
            rows.push( [ 'outside', x.outside === 'none' ? 'No damage visible from outside'
                         : $.map( x.outside_kinds, function ( k ) { return k === 'physical' ? 'Physical damage' : 'Liquid damage'; } ).join( ' + ' ) + ': ' + x.outside_note ] );
        }
        if ( ( cause === 'warranty' || cause === 'outside' ) && x.paid ) { rows.push( [ 'paid', paidText ] ); }
        if ( x.open_here ) {
            rows.push( [ 'open_here', x.open_here === 'yes' ? 'Can be opened here'
                         : 'Cannot be opened here: ' + ( reasons[ x.no_open_reason ] || x.no_open_reason ) + ( x.no_open_note ? ' – ' + x.no_open_note : '' ) ] );
        }
        if ( x.warranty === 'under' && x.outside === 'none' && x.inside ) {
            rows.push( [ 'inside', x.inside === 'none' ? 'No liquid damage inside' : 'Liquid damage inside: ' + x.inside_note ] );
            if ( x.inside === 'liquid' && x.paid ) { rows.push( [ 'paid', paidText ] ); }
        }
        if ( x.repair ) {
            var r = { repaired: 'Repaired: ' + x.repair_note, part: 'Spare part needed: ' + x.part_name + ( x.repair_note ? ' – ' + x.repair_note : '' ),
                      office: 'Take to the office: ' + x.repair_note, other: 'Not repaired here: ' + x.repair_note };
            rows.push( [ 'repair', r[ x.repair ] || x.repair ] );
        }
        return rows;
    },

    /**
     * @description List of the answered steps
     * @param {object} x - Inspection
     * @param {boolean} editable - Show the Change buttons
     * @returns {string} HTML
     */
    summaryHtml : function ( x, editable ) {
        var fixedWarranty = ( SCI.tvChain() || {} ).warranty_status;
        return $.map( SCI.answered( x ), function ( row ) {
            var canChange = editable && !x.locked && !( row[ 0 ] === 'warranty' && SCI.tvChain() !== null && fixedWarranty !== 'No Warranty Found' );
            return '<div class="sci-row' + ( SCI.view === row[ 0 ] ? ' is-editing' : '' ) + '"><div class="sci-row-k">' + SCI.STEP_TITLES[ row[ 0 ] ] + '</div>'
                 + '<div class="sci-row-v">' + SC.esc( row[ 1 ] ) + '</div>'
                 + ( canChange ? '<button type="button" class="btn btn-sm btn-link sci-row-change" data-sci="change" data-step="' + row[ 0 ] + '">Change</button>' : '' ) + '</div>';
        } ).join( '' );
    },

    /**
     * @description The TV being inspected
     * @returns {object|null} TV of SC.current.tvs
     */
    tv : function () {
        var found = null;
        $.each( SC.current.tvs, function ( i, t ) { if ( t.id === SCI.tvId ) { found = t; } } );
        return found;
    },

    /**
     * @description Warranty chain of the TV being inspected (null = not in the system)
     * @returns {object|null}
     */
    tvChain : function () { var t = SCI.tv(); return t ? t.chain : null; },

    /**
     * @description Option button (choose one)
     * @param {string} value - Value
     * @param {string} title - Main text
     * @param {string} sub - Small text ('' = none)
     * @param {string} icon - Font Awesome icon ('' = radio)
     * @returns {string} HTML
     */
    option : function ( value, title, sub, icon ) {
        var on = ( SCI.draft.value === value );
        return '<button type="button" class="scw-option' + ( on ? ' is-on' : '' ) + '" data-sci-pick data-value="' + value + '" aria-pressed="' + on + '">'
             + '<span class="scw-radio"></span><span><span class="fw-semibold d-block">' + ( icon ? '<i class="fa-solid ' + icon + ' me-2"></i>' : '' ) + title + '</span>'
             + ( sub ? '<span class="text-muted fs-8">' + sub + '</span>' : '' ) + '</span></button>';
    },

    /**
     * @description Text box bound to the draft
     * @param {string} key - Draft field
     * @param {string} label - Label
     * @param {boolean} required - Mark as required
     * @param {string} placeholder - Placeholder
     * @param {number} rows - Rows (0 = one-line input)
     * @returns {string} HTML
     */
    textBox : function ( key, label, required, placeholder, rows ) {
        var id = 'sci_' + key, value = SC.esc( SCI.draft[ key ] || '' );
        return '<div class="mt-3"><label class="form-label' + ( required ? ' required' : '' ) + '" for="' + id + '">' + label + '</label>'
             + ( rows ? '<textarea id="' + id + '" class="form-control form-control-solid" rows="' + rows + '" maxlength="2000" data-sci-text="' + key + '" placeholder="' + placeholder + '">' + value + '</textarea>'
                      : '<input type="text" id="' + id + '" class="form-control form-control-solid" maxlength="200" data-sci-text="' + key + '" placeholder="' + placeholder + '" value="' + value + '"/>' )
             + '</div>';
    },

    /**
     * @description The form of one step
     * @param {object} x - Inspection
     * @param {string} step - Step key
     * @returns {string} HTML
     */
    stepHtml : function ( x, step ) {
        var v = SCI.draft.value, html = '';
        if ( step === 'warranty' ) {
            html += '<div class="sci-q">This serial number is not in the system. Is the TV under warranty?</div><div class="scw-options">'
                  + SCI.option( 'under', 'Under warranty', '', 'fa-shield-halved' ) + SCI.option( 'out', 'Out of warranty', 'A service charge applies to the inspection', 'fa-hand-holding-dollar' ) + '</div>';
        }
        if ( step === 'outside' ) {
            html += '<div class="sci-q">Look at the TV from outside. Is there physical damage or liquid damage?</div><div class="scw-options">'
                  + SCI.option( 'none', 'No damage', '', 'fa-circle-check' ) + SCI.option( 'damage', 'Damage seen', 'Not covered by the warranty', 'fa-triangle-exclamation' ) + '</div>';
            if ( v === 'damage' ) {
                var kinds = SCI.draft.kinds || [];
                html += '<div class="mt-3"><div class="form-label required">What damage?</div><div class="scw-chips">'
                      + $.map( [ [ 'physical', 'Physical damage' ], [ 'liquid', 'Liquid damage' ] ], function ( k ) {
                            var on = kinds.indexOf( k[ 0 ] ) !== -1;
                            return '<button type="button" class="scw-chip' + ( on ? ' is-on' : '' ) + '" data-sci-kind="' + k[ 0 ] + '" aria-pressed="' + on + '">' + k[ 1 ] + '</button>';
                        } ).join( '' ) + '</div></div>'
                      + SCI.textBox( 'note', 'Where is the damage and what does it look like?', true, 'e.g. Cracked screen, bottom left corner', 3 )
                      + SCI.photoBox( 'Photos of the damage', true );
            }
        }
        if ( step === 'paid' ) {
            var cause = SCI.paidCause( x );
            html += '<div class="sci-q">' + ( cause === 'warranty' ? 'This TV is out of warranty: a service charge applies to the inspection. Does the customer want a paid repair?'
                                                                   : 'This damage is not covered by the warranty. Does the customer want a paid repair?' ) + '</div><div class="scw-options">'
                  + SCI.option( 'yes', 'Yes, paid repair', 'Go on with the inspection and repair', 'fa-screwdriver-wrench' )
                  + SCI.option( 'no', 'No, stop here', cause === 'warranty' ? 'Ends as "Out of warranty, no repair"' : 'Ends as "Not covered by warranty"', 'fa-ban' ) + '</div>';
        }
        if ( step === 'open_here' ) {
            var reasons = SC.current.inspect_no_open || {};
            html += '<div class="sci-q">Can the TV be opened and repaired here?</div><div class="scw-options">'
                  + SCI.option( 'yes', 'Yes', 'Open it and check inside', 'fa-toolbox' ) + SCI.option( 'no', 'No', 'It has to be taken to the office', 'fa-truck' ) + '</div>';
            if ( v === 'no' ) {
                html += '<div class="mt-3"><div class="form-label required">Why not here?</div><div class="scw-chips">'
                      + $.map( reasons, function ( text, key ) {
                            var on = ( SCI.draft.reason === key );
                            return '<button type="button" class="scw-chip' + ( on ? ' is-on' : '' ) + '" data-sci-reason="' + key + '" aria-pressed="' + on + '">' + SC.esc( text ) + '</button>';
                        } ).join( '' ) + '</div></div>'
                      + SCI.textBox( 'note', 'Details', SCI.draft.reason === 'other', 'e.g. TV is in a small cabinet, no room to lay it down', 2 );
            }
        }
        if ( step === 'inside' ) {
            html += '<div class="sci-q">Open the TV. Is there liquid damage inside?</div><div class="scw-options">'
                  + SCI.option( 'none', 'No liquid damage', '', 'fa-circle-check' ) + SCI.option( 'liquid', 'Liquid damage inside', 'Not covered by the warranty', 'fa-droplet' ) + '</div>';
            if ( v === 'liquid' ) {
                html += SCI.textBox( 'note', 'What does the liquid damage look like?', true, 'e.g. Corrosion on the power board', 3 )
                      + SCI.photoBox( 'Photos of the liquid damage', true );
            }
        }
        if ( step === 'repair' ) {
            html += '<div class="sci-q">Repair the TV here' + ( x.paid === 'yes' ? ' (paid repair)' : '' ) + '. What happened?</div><div class="scw-options">'
                  + SCI.option( 'repaired', 'Repaired, the TV works', 'Photo of the TV working', 'fa-circle-check' )
                  + SCI.option( 'part', 'Spare part needed', 'Come back with the part', 'fa-box' )
                  + SCI.option( 'office', 'Take it to the office', 'Cannot be repaired here', 'fa-truck' )
                  + SCI.option( 'other', 'Not repaired, other reason', 'Another visit is needed', 'fa-rotate' ) + '</div>';
            if ( v === 'repaired' ) {
                html += SCI.textBox( 'note', 'What was repaired?', true, 'e.g. Replaced the power board, TV starts and shows picture', 3 )
                      + SCI.photoBox( 'Photo of the TV working', true );
            }
            if ( v === 'part' ) {
                html += SCI.textBox( 'part_name', 'Which spare part?', true, 'e.g. T-Con board', 0 ) + SCI.textBox( 'note', 'Note', false, 'Anything the next visit should know', 2 );
            }
            if ( v === 'office' ) { html += SCI.textBox( 'note', 'Why does it have to go to the office?', true, 'e.g. Panel fault, needs the workshop', 2 ); }
            if ( v === 'other' ) { html += SCI.textBox( 'note', 'Why was it not repaired?', true, '', 2 ); }
        }
        return html;
    },

    /**
     * @description Place of the photo gallery of a step (filled by render)
     * @param {string} title - Title
     * @param {boolean} required - At least one photo is needed
     * @returns {string} HTML
     */
    photoBox : function ( title, required ) {
        return '<div class="mt-3"><div class="form-label' + ( required ? ' required' : '' ) + '">' + title + '</div><div id="sci_gallery"></div></div>';
    },

    /**
     * @description Photos of the TV for one purpose (uploaded now, else from the case)
     * @param {string} purpose - outside_damage | inside_liquid | working
     * @returns {Array}
     */
    photosOf : function ( purpose ) {
        var key = SCI.tvId + '|' + purpose;
        if ( SCI.photos[ key ] ) { return SCI.photos[ key ]; }
        return ( SC.current.media || [] ).filter( function ( m ) { return m.tv_id === SCI.tvId && m.purpose === purpose; } );
    },

    /**
     * @description Draws the panel of the TV being inspected
     * @param {boolean} [keepDraft] - Redraw after a pick: keep the answer being made
     * @returns {void}
     */
    render : function ( keepDraft ) {
        var $p = $( '#sci_panel_' + SCI.tvId );
        var x = SCI.of( SCI.tvId );
        if ( !$p.length || !x ) { return; }
        var step = SCI.shownStep( x );
        if ( !keepDraft ) { SCI.draft = SCI.stored( x, step ); }
        var html = '<div class="sci-head"><div class="fw-bold"><i class="fa-solid fa-magnifying-glass me-2"></i>Inspection</div>'
                 + '<button type="button" class="btn btn-sm btn-light" data-sci="close">Close</button></div>';
        html += '<div class="sci-rows">' + SCI.summaryHtml( x, true ) + '</div>';
        if ( step ) {
            html += '<div class="sci-step"><div class="sci-step-title">' + SCI.STEP_TITLES[ step ] + ( SCI.view ? ' <span class="text-muted fw-normal fs-7">(changing)</span>' : '' ) + '</div>'
                  + SCI.stepHtml( x, step )
                  + '<div class="sci-actions">' + ( SCI.view ? '<button type="button" class="btn btn-light" data-sci="back">Cancel</button>' : '' )
                  + '<button type="button" class="btn btn-primary" data-sci="save"' + ( SCI.draft.value ? '' : ' disabled' ) + '>Save</button></div></div>';
        } else {
            var hints = {
                not_covered : 'Not covered by the warranty. The case can be closed for this TV.',
                declined : 'Out of warranty and no repair wanted. Collect the service charge.',
                repaired : x.locked ? 'The repair is in the warranty records of this TV.' : 'Repaired. (This TV has no warranty record, so the repair is kept on the case only.)',
                waiting_part : 'Finish the visit with "Needs another visit: spare part needed" and plan the next visit.',
                to_office : 'Before the TV is taken, the customer signs the consent on this phone.',
                at_office : 'The TV is at the office. Finish the visit with "Needs another visit: TV taken to the office".',
                revisit : 'Finish the visit with "Needs another visit" and plan the next visit.'
            };
            html += '<div class="sci-result"><div>' + SCI.badge( x ) + '</div><div class="fs-7 mt-2">' + SC.esc( hints[ x.result ] || '' ) + '</div>'
                  + ( x.charge === 'to_collect' ? '<div class="fs-7 mt-1 sc-text-amber fw-semibold">Service charge to collect.</div>' : '' )
                  + ( x.result === 'to_office' && !x.consent ? '<button type="button" class="btn sc-btn-finish mt-3" data-sci="consent" data-tv-id="' + SCI.tvId + '">'
                                                               + '<i class="fa-solid fa-signature"></i> Customer signs the consent</button>' : '' ) + '</div>';
        }
        SCI.keepGallery();
        $p.html( '<div class="sci-box">' + html + '</div>' );
        SCI.mountGallery( step );
    },

    /**
     * @description Takes the photo gallery out of the page before a redraw, so its events and waiting files stay
     *              (called by render and by SC.renderCase)
     * @returns {void}
     */
    keepGallery : function () {
        if ( SCI.gallery ) { SCI.gallery.$box.detach(); }
    },

    /**
     * @description Puts the photo gallery of the step in its place (keeps the one being used)
     * @param {string} step - Step shown
     * @returns {void}
     */
    mountGallery : function ( step ) {
        var $g = $( '#sci_gallery' );
        if ( !$g.length ) { SCI.gallery = null; return; }
        var purpose = SCI.PURPOSE[ step ];
        if ( SCI.gallery && SCI.gallery.purpose === purpose && SCI.gallery.tvId === SCI.tvId ) {
            $g.replaceWith( SCI.gallery.$box );                // Same step redrawn: keep waiting files
            return;
        }
        var tvId = SCI.tvId;
        var g = new SCMedia( $g, {
            target : function () { return { case_id: SC.current.case.id, tv_id: tvId, purpose: purpose }; },
            editable : true,
            camera : true,
            onChange : function ( media ) { SCI.photos[ tvId + '|' + purpose ] = media.slice(); }
        } );
        g.purpose = purpose;
        g.tvId = tvId;
        g.setMedia( SCI.photosOf( purpose ) );
        SCI.gallery = g;
    },

    /**
     * @description Uploads the photos waiting in the step's gallery
     * @returns {Promise} Resolves when none is waiting; rejects when one failed
     */
    uploadWaiting : function () {
        var g = SCI.gallery;
        if ( !g || !g.busy() ) { return Promise.resolve(); }
        g.start();
        return new Promise( function ( resolve, reject ) {
            var timer = setInterval( function () {
                if ( g.queue.some( function ( q ) { return q.state === 'failed'; } ) ) { clearInterval( timer ); reject( 'A photo did not upload. Try it again or remove it.' ); return; }
                if ( !g.busy() ) { clearInterval( timer ); resolve(); }
            }, 300 );
        } );
    },

    /**
     * @description Sends the answer of the step shown
     * @returns {void}
     */
    save : function () {
        if ( SCI.saving ) { return; }
        var x = SCI.of( SCI.tvId ), step = SCI.shownStep( x );
        if ( !step ) { return; }
        var answer = $.extend( {}, SCI.draft );
        var $btn = $( '[data-sci="save"]' ).prop( 'disabled', true );
        SCI.saving = true;
        SCI.uploadWaiting().then( function () {
            return SC.post( { what_do_you_want: 'appstv_case_inspect', case_id: SC.current.case.id, case_tv_id: SCI.tvId, step: step, answer: JSON.stringify( answer ) } );
        } ).then( function ( info ) {
            var before = x.result, now = info.data.inspection;
            SC.current.inspections[ String( SCI.tvId ) ] = now;
            SCI.view = null;
            if ( now.result !== before ) {                     // Reached, changed or cleared: the card badge and timeline change too
                if ( now.result ) { showSimpleToast( now.result === 'repaired' ? 'success' : 'info', info.message ); }
                SC.reloadCase();                               // Badges, timeline, warranty events; mount() redraws the panel
                return;
            }
            SCI.render();
        } ).catch( function ( message ) {
            SC.toastError( message );
            $btn.prop( 'disabled', false );
        } ).then( function () { SCI.saving = false; } );
    },

    /**
     * @description Ticks the service charge as collected, or back
     * @param {number} tvId - service_case_tvs.id
     * @param {string} collected - '1' | '0'
     * @returns {void}
     */
    charge : function ( tvId, collected ) {
        SC.post( { what_do_you_want: 'appstv_case_inspect', case_id: SC.current.case.id, case_tv_id: tvId, step: 'charge', collected: collected } ).then( function ( info ) {
            showSimpleToast( 'success', info.message );
            SC.reloadCase();
        } ).catch( SC.toastError );
    }
};

$( function () { SCI.bind(); } );


/* =============================================
   SGN (v1.0.33): the customer's consent to take a TV to the office, signed with a finger on the staff member's
   phone. A full-screen page inside the case window: title (Mr. / Ms. / Mrs. / Dr. / none), name, the sentence,
   the signature box. The sentence is built here for the customer to read and again on the server (same words,
   same time), and kept with the signature. view() opens a printable copy of a signed consent.
   ============================================= */
var SGN = {
    tvId : null,                                               // service_case_tvs.id being signed for
    title : '',                                                // Chosen title ('' = none)
    shownAt : '',                                              // ISO time written in the sentence now
    timer : null,                                              // Refreshes the time in the sentence
    pad : null,                                                // { canvas, ctx, ratio, drawing, ink (length drawn, CSS px) }
    MIN_INK : 60,                                              // A signature is at least this long (CSS px)

    /**
     * @description Date and time in Singapore, for the sentence
     * @returns {object} { iso: '2026-11-13T15:45:00+08:00', text: 'Friday, 13 November 2026 at 3:45 PM' }
     */
    now : function () {
        var sg = new Date( Date.now() + 8 * 3600000 );         // Singapore is UTC+8 all year
        var pad = function ( n ) { return ( n < 10 ? '0' : '' ) + n; };
        var days = [ 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' ];
        var months = [ 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ];
        var h = sg.getUTCHours(), m = sg.getUTCMinutes();
        return {
            iso : sg.getUTCFullYear() + '-' + pad( sg.getUTCMonth() + 1 ) + '-' + pad( sg.getUTCDate() ) + 'T' + pad( h ) + ':' + pad( m ) + ':00+08:00',
            text : days[ sg.getUTCDay() ] + ', ' + sg.getUTCDate() + ' ' + months[ sg.getUTCMonth() ] + ' ' + sg.getUTCFullYear()
                 + ' at ' + ( h % 12 === 0 ? 12 : h % 12 ) + ':' + pad( m ) + ' ' + ( h < 12 ? 'AM' : 'PM' )
        };
    },

    /**
     * @description A saved time in Singapore time, whatever the device's time zone (a consent is a Singapore paper)
     * @param {string} iso - ISO 8601 time
     * @returns {string} e.g. "13 Nov 2026, 9:04 PM SGT"
     */
    sgt : function ( iso ) {
        var t = new Date( iso );
        if ( isNaN( t.getTime() ) ) { return iso || ''; }
        var sg = new Date( t.getTime() + 8 * 3600000 ), h = sg.getUTCHours(), m = sg.getUTCMinutes();
        var months = [ 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec' ];
        return sg.getUTCDate() + ' ' + months[ sg.getUTCMonth() ] + ' ' + sg.getUTCFullYear() + ', ' + ( h % 12 === 0 ? 12 : h % 12 ) + ':' + ( m < 10 ? '0' : '' ) + m
             + ' ' + ( h < 12 ? 'AM' : 'PM' ) + ' SGT';
    },

    /**
     * @description The sentence (same words as appstv_case_consent_statement on the server)
     * @param {string} title - Title ('' = none)
     * @param {string} name - Person signing
     * @param {string} when - Date and time text
     * @returns {string}
     */
    statement : function ( title, name, when ) {
        var tv = null;
        $.each( SC.current.tvs, function ( i, t ) { if ( t.id === SGN.tvId ) { tv = t; } } );
        var ch = tv ? tv.chain : null;
        var model = ch && ch.product_name ? $.trim( ch.product_name ) + ' ' : '';
        var staff = SC.current.me_name || SC.me;
        return 'I, ' + ( title ? title + ' ' : '' ) + ( name || '…' ) + ', authorize ' + staff + ' to take the TV ' + model
             + '(serial number ' + ( tv ? tv.serial_number : '' ) + ') on ' + when + ' for inspection, and repairs, thereby giving them my consent by signing below.';
    },

    /**
     * @description Opens the consent page for a TV
     * @param {number} tvId - service_case_tvs.id
     * @returns {void}
     */
    open : function ( tvId ) {
        SGN.tvId = tvId;
        SGN.title = '';
        var d = SC.current, tv = null;
        $.each( d.tvs, function ( i, t ) { if ( t.id === tvId ) { tv = t; } } );
        if ( !tv ) { return; }
        var titles = $.map( d.consent_titles || [ 'Mr.', 'Ms.', 'Mrs.', 'Dr.', '' ], function ( t ) {
            return '<button type="button" class="scw-chip' + ( t === '' ? ' is-on' : '' ) + '" data-sgn-title="' + SC.esc( t ) + '" aria-pressed="' + ( t === '' ) + '">' + ( t || 'None' ) + '</button>';
        } ).join( '' );
        var html = '<div class="sgn" id="sgn" role="dialog" aria-modal="true" aria-labelledby="sgn_title">'
            + '<div class="sgn-head"><div><div class="sgn-title" id="sgn_title">Consent to take the TV</div>'
            + '<div class="text-muted fs-7">' + SC.esc( d.case.label ) + ' · ' + SC.esc( d.case.customer_name ) + ' · TV <span class="font-monospace">' + SC.esc( tv.serial_number ) + '</span></div></div>'
            + '<button type="button" class="btn btn-icon btn-sm btn-light" data-sgn="cancel" aria-label="Close"><i class="fa-solid fa-xmark"></i></button></div>'
            + '<div class="sgn-body">'
            + '<div class="form-label">Title</div><div class="scw-chips mb-3">' + titles + '</div>'
            + '<label class="form-label required" for="sgn_name">Name of the person signing</label>'
            + '<input type="text" id="sgn_name" class="form-control form-control-solid mb-4" maxlength="200" autocomplete="off" value="' + SC.esc( d.case.contact_name || '' ) + '"/>'
            + '<div class="sgn-statement" id="sgn_statement"></div>'
            + '<div class="sgn-pad"><canvas id="sgn_canvas" aria-label="Signature box: sign with your finger"></canvas>'
            + '<div class="sgn-pad-line"></div><div class="sgn-pad-hint" id="sgn_hint">Sign here with your finger</div></div>'
            + '<div class="d-flex justify-content-between align-items-center mt-2"><span class="text-muted fs-8">Signed on this phone · kept with the case</span>'
            + '<button type="button" class="btn btn-sm btn-light" data-sgn="clear"><i class="fa-solid fa-eraser"></i> Clear</button></div>'
            + '</div>'
            + '<div class="sgn-foot"><button type="button" class="btn btn-light" data-sgn="cancel">Cancel</button>'
            + '<button type="button" class="btn btn-primary" data-sgn="sign" disabled><i class="fa-solid fa-signature"></i> Sign and take the TV</button></div></div>';
        $( '#sgn' ).remove();
        $( '#sc_case_modal' ).append( html );
        SGN.refresh();
        SGN.timer = setInterval( SGN.refresh, 15000 );
        SGN.initPad();
        // Escape closes this page, not the case window (listened to here, before Bootstrap sees it)
        document.getElementById( 'sgn' ).addEventListener( 'keydown', function ( e ) {
            if ( e.key === 'Escape' ) { e.stopPropagation(); e.preventDefault(); SGN.close(); }
        } );
        $( '#sgn_name' ).trigger( 'focus' );
    },

    /**
     * @description Redraws the sentence (name, title or the minute changed)
     * @returns {void}
     */
    refresh : function () {
        var now = SGN.now();
        SGN.shownAt = now.iso;
        $( '#sgn_statement' ).text( SGN.statement( SGN.title, $.trim( $( '#sgn_name' ).val() || '' ).replace( /\s+/g, ' ' ), now.text ) );
        SGN.ready();
    },

    /**
     * @description Turns the Sign button on when a name and a signature are there
     * @returns {void}
     */
    ready : function () {
        var ok = $.trim( $( '#sgn_name' ).val() || '' ) !== '' && SGN.pad && SGN.pad.ink > SGN.MIN_INK;
        $( '[data-sgn="sign"]' ).prop( 'disabled', !ok );
    },

    /**
     * @description Sets up the signature box (sharp on phones: drawn at the screen's pixel ratio)
     * @returns {void}
     */
    initPad : function () {
        var canvas = document.getElementById( 'sgn_canvas' );
        var ratio = Math.max( 1, Math.min( 3, window.devicePixelRatio || 1 ) );
        var rect = canvas.getBoundingClientRect();
        canvas.width = Math.round( rect.width * ratio );
        canvas.height = Math.round( rect.height * ratio );
        var ctx = canvas.getContext( '2d' );
        ctx.fillStyle = '#ffffff';
        ctx.fillRect( 0, 0, canvas.width, canvas.height );
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#0b1f44';
        ctx.lineWidth = 2.6 * ratio;
        SGN.pad = { canvas: canvas, ctx: ctx, ratio: ratio, drawing: false, ink: 0, last: null };
        $( '#sgn_hint' ).removeClass( 'd-none' );
        var at = function ( e ) {
            var r = canvas.getBoundingClientRect();
            return { x: ( e.clientX - r.left ) * ratio, y: ( e.clientY - r.top ) * ratio };
        };
        canvas.onpointerdown = function ( e ) {
            e.preventDefault();
            canvas.setPointerCapture( e.pointerId );
            SGN.pad.drawing = true;
            SGN.pad.last = at( e );
            ctx.beginPath();
            ctx.arc( SGN.pad.last.x, SGN.pad.last.y, ctx.lineWidth / 2, 0, Math.PI * 2 );
            ctx.fillStyle = '#0b1f44';
            ctx.fill();
            $( '#sgn_hint' ).addClass( 'd-none' );
        };
        canvas.onpointermove = function ( e ) {
            if ( !SGN.pad.drawing ) { return; }
            e.preventDefault();
            var p = at( e );
            ctx.beginPath();
            ctx.moveTo( SGN.pad.last.x, SGN.pad.last.y );
            ctx.lineTo( p.x, p.y );
            ctx.stroke();
            SGN.pad.ink += Math.sqrt( Math.pow( p.x - SGN.pad.last.x, 2 ) + Math.pow( p.y - SGN.pad.last.y, 2 ) ) / ratio;   // Length drawn (CSS px)
            SGN.pad.last = p;
        };
        canvas.onpointerup = canvas.onpointercancel = function () {
            if ( !SGN.pad.drawing ) { return; }
            SGN.pad.drawing = false;
            SGN.ready();
        };
    },

    /**
     * @description Closes the consent page
     * @returns {void}
     */
    close : function () {
        clearInterval( SGN.timer );
        SGN.timer = null;
        SGN.pad = null;
        $( '#sgn' ).remove();
    },

    /**
     * @description Sends the signed consent
     * @returns {void}
     */
    sign : function () {
        var name = $.trim( $( '#sgn_name' ).val() || '' ).replace( /\s+/g, ' ' );
        if ( !name ) { showSimpleToast( 'warning', 'Write the name of the person signing.' ); return; }
        if ( !SGN.pad || SGN.pad.ink <= SGN.MIN_INK ) { showSimpleToast( 'warning', 'The customer has to sign in the box.' ); return; }
        var $btn = $( '[data-sgn="sign"]' ).prop( 'disabled', true );
        var tvId = SGN.tvId;
        SC.post( { what_do_you_want: 'appstv_case_inspect', case_id: SC.current.case.id, case_tv_id: tvId, step: 'consent',
                   title: SGN.title, signer_name: name, shown_at: SGN.shownAt, signature: SGN.pad.canvas.toDataURL( 'image/png' ) } ).then( function ( info ) {
            SC.current.inspections[ String( tvId ) ] = info.data.inspection;
            SGN.close();
            showSimpleToast( 'success', info.message );
            SC.reloadCase();
        } ).catch( function ( message ) {
            SC.toastError( message );
            $btn.prop( 'disabled', false );
        } );
    },

    /**
     * @description Opens a printable copy of a signed consent in a new window
     * @param {number} tvId - service_case_tvs.id
     * @returns {void}
     */
    view : function ( tvId ) {
        var x = SCI.of( tvId ), d = SC.current;
        if ( !x || !x.consent ) { return; }
        var k = x.consent, e = SC.esc;
        var w = window.open( '', '_blank' );
        if ( !w ) { showSimpleToast( 'warning', 'The browser blocked the new window. Allow pop-ups for this site and try again.' ); return; }
        var img = new URL( SCMedia.url( k.media_id, 'file' ), window.location.href ).href;
        w.document.open();
        w.document.write( '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            + '<title>Consent ' + e( d.case.label ) + ' ' + e( k.serial_number ) + '</title><style>'
            + 'body{font-family:Arial,Helvetica,sans-serif;color:#181c32;max-width:760px;margin:32px auto;padding:0 20px;line-height:1.5}'
            + 'h1{font-size:22px;margin:0 0 4px}.muted{color:#5e6278;font-size:14px}.st{font-size:18px;margin:28px 0 20px}'
            + '.sig{border:1px solid #d6d6e0;border-radius:8px;padding:8px;max-width:100%}.sig img{display:block;width:100%;height:auto}'
            + 'table{margin-top:20px;border-collapse:collapse;font-size:14px}td{padding:4px 16px 4px 0;vertical-align:top}td:first-child{color:#5e6278}'
            + '@media print{body{margin:0}}</style></head><body>'
            + '<h1>Consent to take the TV</h1><div class="muted">' + e( d.case.label ) + ' · ' + e( d.case.customer_name ) + '</div>'
            + '<p class="st">' + e( k.statement ) + '</p>'
            + '<div class="sig"><img src="' + e( img ) + '" alt="Signature of ' + e( k.signer_name ) + '"></div>'
            + '<table><tr><td>Signed by</td><td>' + e( ( k.title ? k.title + ' ' : '' ) + k.signer_name ) + '</td></tr>'
            + '<tr><td>TV taken by</td><td>' + e( k.staff_name ) + '</td></tr>'
            + '<tr><td>Serial number</td><td>' + e( k.serial_number ) + '</td></tr>'
            + '<tr><td>Saved</td><td>' + e( SGN.sgt( k.signed_on ) ) + '</td></tr></table>'
            + '<script>var i=document.images[0];function p(){setTimeout(function(){window.print();},200);}if(i.complete){p();}else{i.onload=p;i.onerror=p;}<\/script>'
            + '</body></html>' );
        w.document.close();
    },

    /**
     * @description Binds the events (once)
     * @returns {void}
     */
    bind : function () {
        var $m = $( '#sc_case_modal' );
        $m.on( 'click', '[data-sci="consent"]', function () { SGN.open( +$( this ).attr( 'data-tv-id' ) ); } );
        $m.on( 'click', '[data-sci="view-consent"]', function () { SGN.view( +$( this ).attr( 'data-tv-id' ) ); } );
        $m.on( 'click', '[data-sgn="cancel"]', function () { SGN.close(); } );
        $m.on( 'click', '[data-sgn="clear"]', function () { SGN.initPad(); SGN.ready(); } );
        $m.on( 'click', '[data-sgn="sign"]', function () { SGN.sign(); } );
        $m.on( 'click', '[data-sgn-title]', function () {
            SGN.title = $( this ).attr( 'data-sgn-title' );
            $( '[data-sgn-title]' ).each( function () { var on = $( this ).attr( 'data-sgn-title' ) === SGN.title; $( this ).toggleClass( 'is-on', on ).attr( 'aria-pressed', on ); } );
            SGN.refresh();
        } );
        $m.on( 'input', '#sgn_name', function () { SGN.refresh(); } );
        $m.on( 'hidden.bs.modal', function () { SGN.close(); } );
    }
};

$( function () { SGN.bind(); } );
