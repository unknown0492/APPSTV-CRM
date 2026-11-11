/**
 * plugins/user_role_management/js/role_permissions_editor.js - Role permission editor (gen3 1.0.107)
 *
 * Shows EVERY functionality as a tree: Plugin > Feature group > functionality, with the role's grants ticked, so a
 * missing one is easy to see. Search, Plugin / Type / Granted filters, tri-state plugin and group checkboxes,
 * View only / Full / None buttons per group, "Copy from role", warnings (a page without its calls, calls without their
 * page, machine functionalities on a person's role, your own role losing Role Management) and a save summary.
 * Machine (system) and before-sign-in (public) functionalities are kept apart in their own section.
 *
 * Data: scodezy_get_role_permissions. One editor per modal: new RolePermEditor( '#rpe_c', '#select_copy_role_c' ).
 */

/**
 * @description Permission editor bound to one container
 * @param {string} container - Selector of the .rpe element
 * @param {string} copySelect - Selector of the "Copy functionalities from role" dropdown
 * @returns {void}
 */
function RolePermEditor( container, copySelect ){
    this.$root          = $( container );      // .rpe element
    this.$copy          = $( copySelect );     // Copy from role dropdown
    this.items          = {};                  // functionality_id => functionality row
    this.sections       = [];                  // [ { key, title, note, plugins: [ { plugin_id, alias, groups: [ { name, ids } ] } ] } ]
    this.selected       = {};                  // functionality_id => true
    this.original       = {};                  // Grants when loaded (for the change summary)
    this.roleId         = '';                  // Role being edited ('' for a new role)
    this.roleSlug       = '';                  // Its slug ('' for a new role)
    this.callerRoleId   = '';                  // Role of the signed-in person
    this.editorNames    = [];                  // Functionality names the signed-in role must keep
    this.roles          = [];                  // Roles for "Copy from role"
    this.ready          = false;               // true once the data is loaded
    this.bind();
}

/** Slug of the Administrator role: it holds machine functionalities on purpose, so no warning about them (1.0.108) */
RolePermEditor.ADMIN_SLUG = 'admin';

/** Types that only read: what "View only" ticks */
RolePermEditor.VIEW_TYPES = [ 'ACCESS', 'READ', 'SELECT' ];

/**
 * @description Escapes text for HTML
 * @param {*} text - Any value
 * @returns {string} HTML-safe text
 */
RolePermEditor.esc = function( text ){
    return $( '<div>' ).text( ( text === null || typeof text === 'undefined' ) ? '' : String( text ) ).html();
};

/**
 * @description Loads the tree and the grants of a role from the server, then draws the editor
 * @param {string} roleId - Role to edit, or '' for a new role
 * @param {function} done - Called with the role row (or null) once loaded; not called on error
 * @returns {void}
 */
RolePermEditor.prototype.load = function( roleId, done ){
    var self = this;    // Editor, for the callbacks
    self.ready  = false;
    self.roleId = roleId ? String( roleId ) : '';
    self.$root.find( '.rpe-tree' ).html( '<div class="text-center text-gray-500 py-10"><span class="spinner-border spinner-border-sm me-2"></span>Loading functionalities...</div>' );
    self.$root.find( '.rpe-warnings' ).addClass( 'd-none' ).empty();

    self.request( self.roleId, function( data ){
        self.build( data );
        self.original = {};
        self.selected = {};
        $.each( data.granted, function( i, id ){
            self.original[ id ] = true;
            self.selected[ id ] = true;
        });
        self.roleSlug = ( data.role && data.role.role_slug ) ? String( data.role.role_slug ) : '';
        self.fillCopyRoles();
        self.resetFilters();
        self.render();
        self.ready = true;
        if( typeof done === 'function' ){
            done( data.role );
        }
    });
};

/**
 * @description Calls scodezy_get_role_permissions
 * @param {string} roleId - Role whose grants are wanted ('' for none)
 * @param {function} success - Called with the data object
 * @returns {void}
 */
RolePermEditor.prototype.request = function( roleId, success ){
    var self = this;    // Editor, for the callbacks
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: { what_do_you_want: 'scodezy_get_role_permissions', role_id: roleId },
        success: function( returned_data ){
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon === false ){
                self.$root.find( '.rpe-tree' ).html( '<div class="text-danger py-10 text-center">The functionalities could not be loaded</div>' );
                return;
            }
            jSon = jSon[ 0 ];
            if( jSon[ 'type' ] !== 'success' ){
                showSimpleToast( 'error', jSon[ 'info' ] );
                self.$root.find( '.rpe-tree' ).html( '<div class="text-danger py-10 text-center">' + RolePermEditor.esc( jSon[ 'info' ] ) + '</div>' );
                return;
            }
            success( jSon[ 'info' ].data );
        },
        error: function(){
            showSimpleToast( 'error', 'The functionalities could not be loaded. Please check the connection and try again' );
        }
    });
};

/**
 * @description Builds the sections > plugins > groups structure from the server data
 * @param {object} data - scodezy_get_role_permissions data
 * @returns {void}
 */
RolePermEditor.prototype.build = function( data ){
    var self       = this;                                                 // Editor, for the loops
    var pluginName = {};                                                   // plugin_id => alias
    var plugins    = { user: {}, machine: {} };                            // section => plugin_id => { alias, groups: name => ids }
    self.items        = {};
    self.callerRoleId = String( data.caller_role_id || '' );
    self.editorNames  = data.editor_functionalities || [];
    self.roles        = data.roles || [];

    $.each( data.plugins, function( i, p ){
        pluginName[ p.plugin_id ] = p.plugin_alias || p.plugin_name;
    });
    $.each( data.functionalities, function( i, f ){
        var id      = String( f.functionality_id );                                     // Key
        var section = ( f.audience === 'system' || f.audience === 'public' ) ? 'machine' : 'user';
        var group   = f.feature_group ? f.feature_group : 'Other';                      // Ungrouped rows
        f.functionality_id = id;
        self.items[ id ] = f;
        if( !plugins[ section ][ f.plugin_id ] ){
            plugins[ section ][ f.plugin_id ] = { alias: pluginName[ f.plugin_id ] || ( 'Plugin ' + f.plugin_id ), groups: {} };
        }
        if( !plugins[ section ][ f.plugin_id ].groups[ group ] ){
            plugins[ section ][ f.plugin_id ].groups[ group ] = [];
        }
        plugins[ section ][ f.plugin_id ].groups[ group ].push( id );
    });

    /**
     * @description Sorted plugin list of one section
     * @param {object} map - plugin_id => { alias, groups }
     * @returns {array} [ { plugin_id, alias, groups: [ { name, ids } ] } ]
     */
    function sorted( map ){
        var list = [];  // Plugins
        $.each( map, function( pid, p ){
            var groups = [];    // Groups of the plugin, "Menu pages" first, "Other" last
            $.each( p.groups, function( name, ids ){
                ids.sort( function( a, b ){
                    var fa = self.items[ a ], fb = self.items[ b ];
                    if( fa.is_page !== fb.is_page ){ return ( fb.is_page === '1' ) ? 1 : -1; }    // Pages first
                    return String( fa.alias ).localeCompare( String( fb.alias ) );
                });
                groups.push( { name: name, ids: ids } );
            });
            groups.sort( function( a, b ){
                var rank = function( n ){ return ( n === 'Menu pages' ) ? 0 : ( ( n === 'Other' ) ? 2 : 1 ); };
                return ( rank( a.name ) - rank( b.name ) ) || a.name.localeCompare( b.name );
            });
            list.push( { plugin_id: String( pid ), alias: p.alias, groups: groups } );
        });
        list.sort( function( a, b ){ return String( a.alias ).localeCompare( String( b.alias ) ); } );
        return list;
    }

    self.sections = [
        { key: 'user', title: '', note: '', plugins: sorted( plugins.user ) },
        { key: 'machine', title: 'System & public',
          note: 'Called by TVs, the hotel PC, the cloud server, cron jobs and interfaces, or before signing in. Grant these only to machine roles (esTV CRON, Android Client Proxy ...), never to people.',
          plugins: sorted( plugins.machine ) }
    ];

    // Plugin filter
    var options = '<option value="">All plugins</option>';      // <option> list
    $.each( data.plugins, function( i, p ){
        if( plugins.user[ p.plugin_id ] || plugins.machine[ p.plugin_id ] ){
            options += '<option value="' + RolePermEditor.esc( p.plugin_id ) + '">' + RolePermEditor.esc( p.plugin_alias || p.plugin_name ) + '</option>';
        }
    });
    self.$root.find( '.rpe-filter-plugin' ).html( options );
};

/**
 * @description Fills the "Copy functionalities from role" dropdown (every role but the one being edited)
 * @returns {void}
 */
RolePermEditor.prototype.fillCopyRoles = function(){
    var self = this;                                         // Editor, for the loop
    var html = '<option value="">-</option>';                // <option> list
    $.each( self.roles, function( i, r ){
        if( String( r.role_id ) === self.roleId ){
            return;
        }
        html += '<option value="' + RolePermEditor.esc( r.role_id ) + '">' + RolePermEditor.esc( r.role_name ) + ' (' + RolePermEditor.esc( r.count ) + ')</option>';
    });
    self.$copy.html( html ).val( '' );
};

/**
 * @description Clears search and filters
 * @returns {void}
 */
RolePermEditor.prototype.resetFilters = function(){
    this.$root.find( '.rpe-filter-text' ).val( '' );
    this.$root.find( '.rpe-filter-plugin, .rpe-filter-type, .rpe-filter-show' ).val( '' );
};

/**
 * @description Draws the whole tree (plugins closed), then the counts, filters and warnings
 * @returns {void}
 */
RolePermEditor.prototype.render = function(){
    var self = this;    // Editor, for the loops
    var esc  = RolePermEditor.esc;
    var html = '';      // Tree HTML

    $.each( self.sections, function( si, section ){
        if( section.plugins.length === 0 ){
            return;
        }
        html += '<div class="rpe-section rpe-section-' + section.key + '" data-section="' + section.key + '">';
        if( section.title ){
            html += '<div class="rpe-section-head"><i class="fa-solid fa-microchip me-2"></i>' + esc( section.title ) +
                    '<span class="badge badge-light-warning ms-2">Machines only</span><div class="rpe-section-note">' + esc( section.note ) + '</div></div>';
        }
        $.each( section.plugins, function( pi, plugin ){
            var pkey = section.key + '-' + plugin.plugin_id;    // Plugin key
            html += '<div class="rpe-plugin" data-key="' + esc( pkey ) + '" data-plugin-id="' + esc( plugin.plugin_id ) + '">' +
                        '<div class="rpe-plugin-head">' +
                            '<button type="button" class="rpe-toggle" aria-expanded="false" title="Open / close"><i class="fa-solid fa-chevron-right"></i></button>' +
                            '<input type="checkbox" class="form-check-input rpe-check rpe-check-plugin" aria-label="Every functionality of ' + esc( plugin.alias ) + '" />' +
                            '<span class="rpe-plugin-name">' + esc( plugin.alias ) + '</span>' +
                            '<span class="rpe-count"></span>' +
                        '</div>' +
                        '<div class="rpe-plugin-body">';
            $.each( plugin.groups, function( gi, group ){
                html += '<div class="rpe-group" data-group="' + esc( group.name ) + '">' +
                            '<div class="rpe-group-head">' +
                                '<input type="checkbox" class="form-check-input rpe-check rpe-check-group" aria-label="Every functionality of ' + esc( group.name ) + '" />' +
                                '<span class="rpe-group-name">' + esc( group.name ) + '</span>' +
                                '<span class="rpe-count"></span>' +
                                '<span class="rpe-group-actions">' +
                                    '<button type="button" class="btn btn-sm btn-light rpe-preset" data-preset="view" title="Only ACCESS, READ and SELECT of this feature">View only</button>' +
                                    '<button type="button" class="btn btn-sm btn-light rpe-preset" data-preset="full" title="Every functionality of this feature">Full</button>' +
                                    '<button type="button" class="btn btn-sm btn-light rpe-preset" data-preset="none" title="Nothing of this feature">None</button>' +
                                '</span>' +
                            '</div>' +
                            '<div class="rpe-items">';
                $.each( group.ids, function( ii, id ){
                    var f    = self.items[ id ];                                                // Functionality
                    var tip  = f.functionality_name + ( f.functionality_description ? ' - ' + f.functionality_description : '' );
                    html += '<label class="rpe-item" data-id="' + esc( id ) + '">' +
                                '<input type="checkbox" class="form-check-input rpe-check rpe-check-item" value="' + esc( id ) + '" />' +
                                '<span class="rpe-item-alias">' + esc( f.alias ) + '</span>' +
                                ( f.is_page === '1' ? '<span class="rpe-page" title="Page"><i class="fa-regular fa-file-lines"></i></span>' : '' ) +
                                '<span class="rpe-type rpe-type-' + esc( f.functionality_type ) + '">' + esc( f.functionality_type ) + '</span>' +
                                '<span class="rpe-info functionality_description_tooltip" data-bs-toggle="tooltip" title="' + esc( tip ) + '">' +
                                    '<i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i></span>' +
                            '</label>';
                });
                html += '</div></div>';
            });
            html += '</div></div>';
        });
        html += '</div>';
    });

    self.$root.find( '.rpe-tree' ).html( html || '<div class="text-gray-500 py-10 text-center">There are no functionalities yet</div>' );
    self.$root.find( '.rpe-check-item' ).each( function(){
        $( this ).prop( 'checked', !!self.selected[ this.value ] );
    });
    self.applyFilters();
};

/**
 * @description Binds the toolbar, tree and copy dropdown events (once)
 * @returns {void}
 */
RolePermEditor.prototype.bind = function(){
    var self  = this;           // Editor, for the handlers
    var $root = self.$root;
    var timer = null;           // Search debounce

    $root.on( 'input', '.rpe-filter-text', function(){
        clearTimeout( timer );
        timer = setTimeout( function(){ self.applyFilters(); }, 150 );
    });
    $root.on( 'change', '.rpe-filter-plugin, .rpe-filter-type, .rpe-filter-show', function(){
        self.applyFilters();
    });
    $root.on( 'click', '.rpe-expand-all', function(){
        $root.find( '.rpe-plugin:not(.rpe-hidden)' ).each( function(){ self.setOpen( $( this ), true ); } );
    });
    $root.on( 'click', '.rpe-collapse-all', function(){
        $root.find( '.rpe-plugin' ).each( function(){ self.setOpen( $( this ), false ); } );
    });
    $root.on( 'click', '.rpe-toggle, .rpe-plugin-name', function(){
        var $plugin = $( this ).closest( '.rpe-plugin' );   // Plugin clicked
        self.setOpen( $plugin, !$plugin.hasClass( 'rpe-open' ) );
    });

    // One functionality
    $root.on( 'change', '.rpe-check-item', function(){
        self.setIds( [ this.value ], this.checked );
    });
    // Group / plugin checkbox: the functionalities shown (filters apply)
    $root.on( 'change', '.rpe-check-group, .rpe-check-plugin', function(){
        var $scope = $( this ).closest( $( this ).hasClass( 'rpe-check-group' ) ? '.rpe-group' : '.rpe-plugin' );   // Group or plugin
        var ids    = [];                                                                                          // Shown IDs
        $scope.find( '.rpe-item:not(.rpe-hidden) .rpe-check-item' ).each( function(){ ids.push( this.value ); } );
        self.setIds( ids, this.checked );
    });
    // View only / Full / None: the whole feature, whatever the filters
    $root.on( 'click', '.rpe-preset', function(){
        var preset = $( this ).data( 'preset' );                                // view / full / none
        var on = [], off = [];                                                  // IDs to tick / untick
        $( this ).closest( '.rpe-group' ).find( '.rpe-check-item' ).each( function(){
            var type = self.items[ this.value ].functionality_type;            // ACCESS, READ ...
            var tick = ( preset === 'full' ) || ( preset === 'view' && RolePermEditor.VIEW_TYPES.indexOf( type ) > -1 );
            ( tick ? on : off ).push( this.value );
        });
        self.setIds( on, true, true );
        self.setIds( off, false );
    });
    // Warning: open its feature
    $root.on( 'click', '.rpe-warning-go', function(){
        self.reveal( String( $( this ).data( 'key' ) ), String( $( this ).data( 'group' ) ) );
    });
    $root.on( 'click', '.rpe-summary-warnings', function(){
        var $panel = $root.find( '.rpe-warnings' );     // Warnings list
        $panel.toggleClass( 'd-none' );
    });

    // Copy from role
    self.$copy.on( 'change', function(){
        var fromId = self.$copy.val();                                          // Role to copy
        if( !fromId || !self.ready ){
            return;
        }
        var fromName = self.$copy.find( 'option:selected' ).text();            // "Property Manager (148)"
        var count    = self.selectedIds().length;                               // Ticked now
        self.$copy.val( '' );                                                   // The dropdown is an action, not a setting
        var go = function(){
            self.request( fromId, function( data ){
                self.selected = {};
                $.each( data.granted, function( i, id ){ self.selected[ String( id ) ] = true; } );
                self.syncChecks();
                showSimpleToast( 'success', data.granted.length + ' functionalities copied. Nothing is saved until you press ' + ( self.roleId ? 'Update' : 'Create' ) );
            });
        };
        if( count === 0 ){
            go();
            return;
        }
        showConfirmSweetAlert( 'Copy functionalities',
            'Replace the <b>' + count + '</b> functionalities ticked now with the ones of <b>' + RolePermEditor.esc( fromName ) + '</b>?',
            'question', 'Replace', 'btn btn-primary', 'Cancel', 'btn btn-light', go );
    });
};

/**
 * @description Ticks or unticks functionalities, then refreshes checkboxes, counts and warnings
 * @param {array} ids - Functionality IDs
 * @param {boolean} on - true to grant
 * @param {boolean} quiet - true to skip the refresh (another setIds follows)
 * @returns {void}
 */
RolePermEditor.prototype.setIds = function( ids, on, quiet ){
    var self = this;    // Editor, for the loop
    $.each( ids, function( i, id ){
        if( on ){ self.selected[ id ] = true; }
        else{ delete self.selected[ id ]; }
    });
    if( !quiet ){
        self.syncChecks();
    }
};

/**
 * @description Puts the item checkboxes in line with the selection, then the counts and warnings
 * @returns {void}
 */
RolePermEditor.prototype.syncChecks = function(){
    var self = this;    // Editor, for the loop
    self.$root.find( '.rpe-check-item' ).each( function(){
        $( this ).prop( 'checked', !!self.selected[ this.value ] );
    });
    self.applyFilters();
};

/**
 * @description Applies search and filters (hides what does not match, opens plugins that match while filtering),
 *              then the counts and warnings
 * @returns {void}
 */
RolePermEditor.prototype.applyFilters = function(){
    var self     = this;                                                                       // Editor, for the loops
    var $root    = self.$root;
    var text     = $.trim( String( $root.find( '.rpe-filter-text' ).val() || '' ) ).toLowerCase();
    var plugin   = String( $root.find( '.rpe-filter-plugin' ).val() || '' );
    var type     = String( $root.find( '.rpe-filter-type' ).val() || '' );
    var show     = String( $root.find( '.rpe-filter-show' ).val() || '' );
    var active   = ( text !== '' ) || ( plugin !== '' ) || ( type !== '' ) || ( show !== '' );   // Any filter

    $root.find( '.rpe-plugin' ).each( function(){
        var $plugin  = $( this );
        var pluginOk = ( plugin === '' ) || ( String( $plugin.data( 'plugin-id' ) ) === plugin );
        var pluginShown = 0;                                                                   // Items shown in the plugin
        $plugin.find( '.rpe-group' ).each( function(){
            var $group    = $( this );
            var groupName = String( $group.data( 'group' ) ).toLowerCase();
            var shown     = 0;                                                                 // Items shown in the group
            $group.find( '.rpe-item' ).each( function(){
                var f  = self.items[ String( $( this ).data( 'id' ) ) ];
                var ok = pluginOk
                      && ( type === '' || f.functionality_type === type )
                      && ( show === '' || ( show === 'granted' ) === !!self.selected[ f.functionality_id ] )
                      && ( text === '' || ( f.alias + ' ' + f.functionality_name + ' ' + groupName + ' ' + ( f.functionality_description || '' ) ).toLowerCase().indexOf( text ) > -1 );
                $( this ).toggleClass( 'rpe-hidden', !ok );
                if( ok ){ shown++; }
            });
            $group.toggleClass( 'rpe-hidden', shown === 0 );
            pluginShown += shown;
        });
        $plugin.toggleClass( 'rpe-hidden', pluginShown === 0 );
        if( active && pluginShown > 0 ){
            self.setOpen( $plugin, true );
        }
    });
    $root.find( '.rpe-section' ).each( function(){
        $( this ).toggleClass( 'rpe-hidden', $( this ).find( '.rpe-plugin:not(.rpe-hidden)' ).length === 0 );
    });
    var $tree = $root.find( '.rpe-tree' );
    $tree.find( '.rpe-no-match' ).remove();
    if( $tree.find( '.rpe-plugin:not(.rpe-hidden)' ).length === 0 && $tree.find( '.rpe-plugin' ).length > 0 ){
        $tree.append( '<div class="rpe-no-match text-gray-500 py-10 text-center">No functionality matches the search and filters</div>' );
    }
    self.updateCounts();
    self.updateWarnings();
};

/**
 * @description Opens or closes a plugin
 * @param {jQuery} $plugin - .rpe-plugin element
 * @param {boolean} open - true to open
 * @returns {void}
 */
RolePermEditor.prototype.setOpen = function( $plugin, open ){
    $plugin.toggleClass( 'rpe-open', open );
    $plugin.find( '.rpe-toggle' ).attr( 'aria-expanded', open ? 'true' : 'false' );
};

/**
 * @description Refreshes the "granted / total" counts and the tri-state plugin and group checkboxes
 *              (counts: every functionality; checkboxes: the ones shown)
 * @returns {void}
 */
RolePermEditor.prototype.updateCounts = function(){
    var self = this;    // Editor, for the loops

    /**
     * @description Count badge and checkbox of one plugin or group
     * @param {jQuery} $scope - .rpe-plugin or .rpe-group
     * @param {jQuery} $check - Its checkbox
     * @param {jQuery} $count - Its count badge
     * @returns {void}
     */
    function paint( $scope, $check, $count ){
        var all = 0, on = 0, shownAll = 0, shownOn = 0;     // Totals, then shown only
        $scope.find( '.rpe-item' ).each( function(){
            var id = String( $( this ).data( 'id' ) ), hidden = $( this ).hasClass( 'rpe-hidden' );
            all++;
            if( self.selected[ id ] ){ on++; }
            if( !hidden ){
                shownAll++;
                if( self.selected[ id ] ){ shownOn++; }
            }
        });
        $count.text( on + ' / ' + all )
              .toggleClass( 'rpe-count-full', on === all && all > 0 )
              .toggleClass( 'rpe-count-part', on > 0 && on < all );
        $check.prop( 'checked', shownAll > 0 && shownOn === shownAll )
              .prop( 'indeterminate', shownOn > 0 && shownOn < shownAll );
    }

    self.$root.find( '.rpe-group' ).each( function(){
        var $g = $( this );
        paint( $g, $g.find( '> .rpe-group-head .rpe-check-group' ), $g.find( '> .rpe-group-head .rpe-count' ) );
    });
    self.$root.find( '.rpe-plugin' ).each( function(){
        var $p = $( this );
        paint( $p, $p.find( '> .rpe-plugin-head .rpe-check-plugin' ), $p.find( '> .rpe-plugin-head .rpe-count' ) );
    });

    var total   = Object.keys( self.items ).length;        // Every functionality
    var granted = self.selectedIds().length;               // Ticked
    var change  = self.changes();                          // { added, removed }
    self.$root.find( '.rpe-summary-count' ).html( '<b>' + granted + '</b> of ' + total + ' functionalities granted' );
    self.$root.find( '.rpe-summary-change' ).html(
        ( change.added || change.removed )
            ? '<span class="text-success">+' + change.added + ' added</span> <span class="text-danger ms-2">&minus;' + change.removed + ' removed</span> <span class="text-gray-500 ms-1">(not saved yet)</span>'
            : '<span class="text-gray-500">No changes</span>' );
};

/**
 * @description Functionality IDs ticked now
 * @returns {array} IDs (strings)
 */
RolePermEditor.prototype.selectedIds = function(){
    var self = this;    // Editor, for the filter
    return Object.keys( self.selected ).filter( function( id ){ return !!self.items[ id ]; } );
};

/**
 * @description Changes against the grants loaded with the role
 * @returns {object} { added, removed, addedIds, removedIds }
 */
RolePermEditor.prototype.changes = function(){
    var self = this;                        // Editor, for the loops
    var addedIds = [], removedIds = [];     // Differences
    $.each( self.selected, function( id ){ if( !self.original[ id ] && self.items[ id ] ){ addedIds.push( id ); } } );
    $.each( self.original, function( id ){ if( !self.selected[ id ] && self.items[ id ] ){ removedIds.push( id ); } } );
    return { added: addedIds.length, removed: removedIds.length, addedIds: addedIds, removedIds: removedIds };
};

/**
 * @description Problems with the current selection
 * @returns {array} [ { level: 'danger'|'warning'|'info', text (HTML), key, group } ] - danger blocks the save
 */
RolePermEditor.prototype.warnings = function(){
    var self   = this;                          // Editor, for the loops
    var esc    = RolePermEditor.esc;
    var list   = [];                            // Warnings
    var byName = {};                            // functionality_name => id
    $.each( self.items, function( id, f ){ byName[ f.functionality_name ] = id; } );

    // Your own role must keep the Role Management functionalities it has now (1.0.109: not the ones it never had)
    if( self.roleId !== '' && self.roleId === self.callerRoleId ){
        var lost = [];  // Aliases that would go
        $.each( self.editorNames, function( i, n ){
            var id = byName[ n ];   // Its functionality ID
            if( id && self.original[ id ] && !self.selected[ id ] ){ lost.push( esc( self.items[ id ].alias ) ); }
        });
        if( lost.length ){
            list.push( { level: 'danger', text: 'You are signed in with this role. It must keep <b>' + lost.join( '</b>, <b>' ) + '</b>, or you would lose access to Role Management.' } );
        }
    }

    // Pages and their calls (people's functionalities only)
    $.each( self.sections, function( si, section ){
        if( section.key !== 'user' ){
            return;
        }
        $.each( section.plugins, function( pi, plugin ){
            $.each( plugin.groups, function( gi, group ){
                if( group.name === 'Menu pages' || group.name === 'Other' ){
                    return;
                }
                var pages = [], pagesOn = 0, reads = 0, readsOn = 0, callsOn = 0;   // Tallies of the group
                $.each( group.ids, function( ii, id ){
                    var f = self.items[ id ], on = !!self.selected[ id ];
                    if( f.is_page === '1' ){
                        pages.push( f );
                        if( on ){ pagesOn++; }
                        return;
                    }
                    if( on ){ callsOn++; }
                    if( f.functionality_type === 'READ' || f.functionality_type === 'SELECT' ){
                        reads++;
                        if( on ){ readsOn++; }
                    }
                });
                var where = '<b>' + esc( plugin.alias ) + ' &rsaquo; ' + esc( group.name ) + '</b>';     // Place label
                var go    = { key: 'user-' + plugin.plugin_id, group: group.name };
                if( pagesOn > 0 && reads > 0 && readsOn === 0 ){
                    list.push( $.extend( { level: 'warning', text: where + ': the page is granted but none of its read functionalities, so it will open empty or with errors.' }, go ) );
                }
                if( pages.length > 0 && pagesOn === 0 && callsOn > 0 ){
                    list.push( $.extend( { level: 'info', text: where + ': ' + callsOn + ' functionalit' + ( callsOn === 1 ? 'y is' : 'ies are' ) + ' granted but not the page <b>' + esc( pages[ 0 ].alias ) + '</b>, so it will not be in the menu.' }, go ) );
                }
            });
        });
    });

    // Machine functionalities on a role that signs in to the admin panel (not Administrator: it holds them on purpose, 1.0.108)
    if( self.roleSlug !== RolePermEditor.ADMIN_SLUG && byName[ 'adminpanel' ] && self.selected[ byName[ 'adminpanel' ] ] ){
        var machine = 0;    // System / public ticked
        $.each( self.selected, function( id ){
            var f = self.items[ id ];
            if( f && f.audience === 'system' ){ machine++; }
        });
        if( machine > 0 ){
            list.push( { level: 'warning', key: 'machine', group: '',
                         text: '<b>' + machine + '</b> machine (system) functionalit' + ( machine === 1 ? 'y is' : 'ies are' ) + ' granted to a role that signs in to the admin panel. They are meant for TVs, the hotel PC, cron jobs and interfaces.' } );
        }
    }
    return list;
};

/**
 * @description Refreshes the warnings panel and its link in the summary line
 * @returns {void}
 */
RolePermEditor.prototype.updateWarnings = function(){
    var list   = this.warnings();                                     // Current warnings
    var $panel = this.$root.find( '.rpe-warnings' );
    var $link  = this.$root.find( '.rpe-summary-warnings' );
    if( list.length === 0 ){
        $panel.addClass( 'd-none' ).empty();
        $link.addClass( 'd-none' );
        return;
    }
    var html = '';  // Panel HTML
    $.each( list, function( i, w ){
        var icon = ( w.level === 'danger' ) ? 'fa-circle-xmark text-danger' : ( ( w.level === 'warning' ) ? 'fa-triangle-exclamation text-warning' : 'fa-circle-info text-primary' );
        html += '<div class="rpe-warning rpe-warning-' + w.level + '"><i class="fa-solid ' + icon + '"></i><span>' + w.text + '</span>' +
                ( w.key ? '<button type="button" class="btn btn-link btn-sm p-0 rpe-warning-go" data-key="' + RolePermEditor.esc( w.key ) + '" data-group="' + RolePermEditor.esc( w.group ) + '">Show</button>' : '' ) +
                '</div>';
    });
    $panel.html( html );
    var blocking = list.filter( function( w ){ return w.level === 'danger'; } ).length;   // Errors
    if( blocking ){
        $panel.removeClass( 'd-none' );
    }
    $link.removeClass( 'd-none' ).html( '<i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>' + list.length + ' warning' + ( list.length === 1 ? '' : 's' ) );
};

/**
 * @description Clears the filters, opens a plugin and scrolls to a feature group
 * @param {string} key - Plugin key ("user-12") or "machine" for the machine section
 * @param {string} group - Feature group name ('' for the plugin / section)
 * @returns {void}
 */
RolePermEditor.prototype.reveal = function( key, group ){
    var self = this;    // Editor
    self.resetFilters();
    self.applyFilters();
    var $target;        // Element to scroll to
    if( key === 'machine' ){
        $target = self.$root.find( '.rpe-section-machine' );
        $target.find( '.rpe-plugin' ).each( function(){ self.setOpen( $( this ), true ); } );
    }
    else{
        var $plugin = self.$root.find( '.rpe-plugin[data-key="' + key + '"]' );
        self.setOpen( $plugin, true );
        $target = $plugin.find( '.rpe-group' ).filter( function(){ return String( $( this ).data( 'group' ) ) === group; } );
        if( !$target.length ){ $target = $plugin; }
    }
    if( $target.length && $target[ 0 ].scrollIntoView ){
        $target[ 0 ].scrollIntoView( { behavior: 'smooth', block: 'start' } );
        $target.addClass( 'rpe-flash' );
        setTimeout( function(){ $target.removeClass( 'rpe-flash' ); }, 1600 );
    }
};

/**
 * @description Before saving: refuses on a blocking problem; otherwise shows what changes and the warnings, and calls
 *              save when confirmed (straight away when nothing needs saying)
 * @param {function} save - Does the save
 * @returns {void}
 */
RolePermEditor.prototype.confirmSave = function( save ){
    if( !this.ready ){
        showSimpleToast( 'error', 'Please wait until the functionalities are loaded' );
        return;
    }
    var list   = this.warnings();                                                  // Problems
    var change = this.changes();                                                   // Differences
    var esc    = RolePermEditor.esc;
    var self   = this;                                                             // Editor, for the lists
    var errors = list.filter( function( w ){ return w.level === 'danger'; } );
    if( errors.length ){
        this.$root.find( '.rpe-warnings' ).removeClass( 'd-none' );
        showSimpleSweetAlert( $( '<div>' ).html( errors[ 0 ].text ).text(), 'error', 'OK', 'btn btn-primary' );
        return;
    }
    if( change.added === 0 && change.removed === 0 && list.length === 0 ){
        save();
        return;
    }

    /**
     * @description Up to 8 aliases as a list, then "and N more"
     * @param {array} ids - Functionality IDs
     * @returns {string} HTML
     */
    function names( ids ){
        var shown = ids.slice( 0, 8 ).map( function( id ){ return esc( self.items[ id ].alias ); } );
        return shown.join( ', ' ) + ( ids.length > 8 ? ' and ' + ( ids.length - 8 ) + ' more' : '' );
    }
    var html = '<div class="text-start fs-6">';     // Dialog body
    if( change.added ){
        html += '<div class="mb-3"><span class="text-success fw-bold">+' + change.added + ' added</span>' +
                '<div class="text-gray-600 fs-7">' + names( change.addedIds ) + '</div></div>';
    }
    if( change.removed ){
        html += '<div class="mb-3"><span class="text-danger fw-bold">&minus;' + change.removed + ' removed</span>' +
                '<div class="text-gray-600 fs-7">' + names( change.removedIds ) + '</div></div>';
    }
    if( !change.added && !change.removed ){
        html += '<div class="mb-3 text-gray-600">No functionality changes.</div>';
    }
    if( list.length ){
        html += '<div class="fw-bold mt-4 mb-2">' + list.length + ' warning' + ( list.length === 1 ? '' : 's' ) + '</div><ul class="ps-5 fs-7 text-gray-700">';
        $.each( list.slice( 0, 6 ), function( i, w ){ html += '<li class="mb-1">' + w.text + '</li>'; } );
        html += ( list.length > 6 ? '<li>and ' + ( list.length - 6 ) + ' more</li>' : '' ) + '</ul>';
    }
    html += '</div>';
    showConfirmSweetAlert( 'Save the role?', html, list.length ? 'warning' : 'question', 'Save', 'btn btn-primary', 'Go back', 'btn btn-light', save );
};
