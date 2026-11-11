var _table_name = "table_roles";   // The name of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var _table = "#" + _table_name;   // The id with # of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var DataTable = null;
var DataTableParameters = null;
const loadingEl = document.createElement("div");    // Loading Div for DataTable entries loading

var rolePermEditorC = null;     // Permission editor of the Create Role modal (role_permissions_editor.js, 1.0.107)
var rolePermEditorE = null;     // Permission editor of the Edit Role modal (1.0.107)

$( document ).ready(function(){
    // All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
    initDataTable();

    loadRolesIntoDataTable();

    rolePermEditorC = new RolePermEditor( '#rpe_c', '#select_copy_role_c' );
    rolePermEditorE = new RolePermEditor( '#rpe_e', '#select_copy_role_e' );

    initCreateRoleModal();

    create_role();

    update_role();

    $( 'body' ).tooltip({
        selector: '.functionality_description_tooltip'
    });
});

/**
 * @description Sends Create / Update of a role, with the functionalities ticked in its editor
 * @param {object} opts - { what (webservice function), roleId ('' to create), nameInput, slugInput, editor, button, done(data) }
 * @returns {void}
 */
function saveRoleRequest( opts ){
    var formData = new FormData();      // Request body
    formData.append( 'what_do_you_want', opts.what );
    if( opts.roleId !== '' ){
        formData.append( 'role_id', opts.roleId );
    }
    formData.append( 'role_name', opts.nameInput.val() );
    formData.append( 'role_slug', opts.slugInput.val() );
    formData.append( 'functionalities', opts.editor.selectedIds().join( ',' ) );

    // Show Loading Animation on the Button
    opts.button.attr( 'data-kt-indicator', 'on' );
    disableFormElement( opts.button );

    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function( returned_data ){
            opts.button.attr( 'data-kt-indicator', 'off' );
            enableFormElement( opts.button );

            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){
                return;
            }
            jSon = jSon[ 0 ];
            if( jSon[ 'type' ] == 'error' ){
                showSimpleToast( "error", jSon[ 'info' ] );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){
                opts.done( jSon[ 'info' ] );
            }
        },
        error: function(){
            opts.button.attr( 'data-kt-indicator', 'off' );
            enableFormElement( opts.button );
            showSimpleToast( "error", "The role could not be saved. Please check the connection and try again" );
        }
    });
}

/**
 * @description Checks the Role Name and Role Slug fields
 * @param {jQuery} nameInput - Role Name input
 * @param {jQuery} slugInput - Role Slug input
 * @returns {boolean} true when both are valid
 */
function roleFieldsValid( nameInput, slugInput ){
    if( !nameInput.parsley().isValid() ){
        nameInput.parsley().validate();
        showSimpleToast( "error", "Please check the Role Name and try again !" );
        return false;
    }
    if( !slugInput.parsley().isValid() ){
        slugInput.parsley().validate();
        showSimpleToast( "error", "Please check the Role Slug and try again !" );
        return false;
    }
    return true;
}

/**
 * @description Edit Role modal: Update and Cancel buttons
 * @returns {void}
 */
function update_role(){
    var modal                       = getElementByID( 'modal_edit_role' );
    var e_table                     = getElementByID( _table_name );
    var e_hidden_role_id            = getElementByID( 'hidden_role_id_e' );
    var e_role_name                 = getElementByID( 'input_role_name_e' );
    var e_role_slug                 = getElementByID( 'input_role_slug_e' );
    var btn_submit                  = getElementByID( 'btn_update_role' );
    var btn_cancel                  = getElementByID( 'btn_cancel_edit_role_modal' );

    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_edit_role' );

    btn_submit.on( 'click', function(){
        if( !roleFieldsValid( e_role_name, e_role_slug ) ){
            return;
        }
        var val_role_id = e_hidden_role_id.val();     // Role being edited

        rolePermEditorE.confirmSave( function(){
            saveRoleRequest({
                what: 'scodezy_update_role', roleId: val_role_id, nameInput: e_role_name, slugInput: e_role_slug,
                editor: rolePermEditorE, button: btn_submit,
                done: function( data ){
                    // Replace the row in the DataTable
                    var trToBeRemoved = e_table.find( 'tr[data-role-id="'+val_role_id+'"]' );
                    DataTable.row( trToBeRemoved ).remove().draw();
                    DataTable.row.add( $( createRowForDataTable( data.data ) ) ).draw();

                    modal.data( 'rpe-closing', true );     // Saved: closing does not ask
                    modal.modal( 'hide' );
                    showSimpleToast( "success", data.info );
                }
            });
        });
    });

    btn_cancel.on( 'click', function(){
        modal.modal( 'hide' );
    });

    // Leaving with unsaved changes asks first (1.0.107)
    modal.on( 'hide.bs.modal', function( e ){
        var change = rolePermEditorE.changes();     // Unsaved differences
        if( modal.data( 'rpe-closing' ) || !rolePermEditorE.ready || ( change.added === 0 && change.removed === 0 ) ){
            modal.data( 'rpe-closing', false );
            return;
        }
        if( btn_submit.attr( 'data-kt-indicator' ) === 'on' ){
            return;
        }
        e.preventDefault();
        showConfirmSweetAlert( "Unsaved changes", "Close without saving <b>+" + change.added + " / &minus;" + change.removed + "</b> functionality changes?",
            "warning", "Close without saving", "btn btn-danger", "Keep editing", "btn btn-light", function(){
                modal.data( 'rpe-closing', true );
                modal.modal( 'hide' );
            });
    });

    modal.on( 'hidden.bs.modal', function(){
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
    });
}

// This function is inside the onclick attribute of the Edit Button in the column of each row of the DataTable
function editRole( thees, e ){
    e.preventDefault();
    var val_role_id = $( thees ).parent().parent( 'tr' ).data( 'role-id' );    // Role of the row

    var modal               = $( '#modal_edit_role' );
    var e_hidden_role_id_e  = getElementByID( 'hidden_role_id_e' );
    var e_role_name         = getElementByID( 'input_role_name_e' );
    var e_role_slug         = getElementByID( 'input_role_slug_e' );

    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_edit_role' );
    scodezyForm.getParsleyForm().reset();
    scodezyForm.getForm().trigger( "reset" );

    e_hidden_role_id_e.val( val_role_id );
    modal.find( '.rpe-title-role' ).text( '' );
    modal.data( 'rpe-closing', false );
    modal.modal( 'show' );

    // Name, slug and every functionality with this role's grants, in one call (1.0.107)
    rolePermEditorE.load( String( val_role_id ), function( role ){
        if( !role ){
            return;
        }
        e_role_name.val( role.role_name );
        e_role_slug.val( role.role_slug );
        modal.find( '.rpe-title-role' ).text( role.role_name );
    });
}

function deleteRole( thees, event ){
    event.preventDefault();
    
    var tr = $( thees ).parent().parent( 'tr' );
    var val_role_id = tr.data( 'role-id' );
    ////console.log( val_page_id );
    var val_role_name = tr.find( 'td:nth-child(1)' ).text();
    
    // Show a confirmation SweetActionAlert to confirm the delete operation
    showConfirmSweetAlert( "Confirm", "Are you sure you want to delete the Role '" + val_role_name + "'" , "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
        ////console.log( 'yes clicked' );
        
        showLoadingSweetAlert( "Deleting", "Please wait while the Role is being deleted" );
        
        var data = {
            what_do_you_want: "scodezy_delete_role",
            role_id: val_role_id
        };

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                ////console.log( returned_data );

                // Hide Loading Animation on the Button
                hideLoadingSweetAlert();
                
                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    showSimpleToast( "error", jSon[ 'info' ] );                    
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];

                    var value = data.data;
                    
                    // Delete this entry from the DataTable
                    DataTable.row( tr ).remove().draw();
                    
                    showSimpleToast( "success", data.info );
                    
                    return;
                }



            }
        });
    });
}

/**
 * @description Create Role modal: Create button
 * @returns {void}
 */
function create_role(){
    var modal       = getElementByID( 'modal_create_role' );
    var e_role_name = getElementByID( 'input_role_name_c' );
    var e_role_slug = getElementByID( 'input_role_slug_c' );
    var btn_submit  = getElementByID( 'btn_create_functionality' );

    btn_submit.on( 'click', function(){
        if( !roleFieldsValid( e_role_name, e_role_slug ) ){
            return;
        }
        rolePermEditorC.confirmSave( function(){
            saveRoleRequest({
                what: 'scodezy_create_role', roleId: '', nameInput: e_role_name, slugInput: e_role_slug,
                editor: rolePermEditorC, button: btn_submit,
                done: function( data ){
                    // Add this entry into the DataTable
                    DataTable.row.add( $( createRowForDataTable( data.data ) ) ).draw();

                    modal.data( 'rpe-closing', true );     // Saved: closing does not ask
                    modal.modal( 'hide' );
                    showSimpleToast( "success", data.info );
                }
            });
        });
    });
}

/**
 * @description Create Role modal: opening (fresh form and editor), Cancel, and the question on leaving with ticks
 * @returns {void}
 */
function initCreateRoleModal(){
    var modal           = getElementByID( 'modal_create_role' );
    var btn_open_modal  = getElementByID( 'btn_open_create_role_modal' );
    var btn_cancel      = getElementByID( 'btn_cancel_create_role_modal' );
    var btn_submit      = getElementByID( 'btn_create_functionality' );

    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_create_role' );

    btn_open_modal.on( 'click', function(){
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        modal.data( 'rpe-closing', false );
        modal.modal( 'show' );
        rolePermEditorC.load( '', null );
    });

    btn_cancel.on( 'click', function(){
        modal.modal( 'hide' );
    });

    // Leaving with ticked functionalities asks first (1.0.107)
    modal.on( 'hide.bs.modal', function( e ){
        var change = rolePermEditorC.changes();     // Ticked so far
        if( modal.data( 'rpe-closing' ) || !rolePermEditorC.ready || change.added === 0 ){
            modal.data( 'rpe-closing', false );
            return;
        }
        if( btn_submit.attr( 'data-kt-indicator' ) === 'on' ){
            return;
        }
        e.preventDefault();
        showConfirmSweetAlert( "Unsaved role", "Close without creating the role? The <b>" + change.added + "</b> ticked functionalities will be lost.",
            "warning", "Close without saving", "btn btn-danger", "Keep editing", "btn btn-light", function(){
                modal.data( 'rpe-closing', true );
                modal.modal( 'hide' );
            });
    });
}


// Creates an HTML Element for the Table Row (tr) for the data that have been receveived from the API
function createRowForDataTable( value ){
    var html = '<tr data-role-id="'+value.role_id+'">\n';
    html += '<td>' + value.role_name + '</td>\n';
    html += '<td>' + value.role_slug + '</td>\n';
    
    html += '<td>' + value.count + '</td>\n';
    html += '<td><a href="#" class="" title="Edit Role" onclick="editRole(this,event);"><i class="fa-regular fs-2x text-primary fa-pen-to-square"></i></a> \n\
                 <a href="#" class="ms-3" title="Delete Role" onclick="deleteRole(this,event);"><i class="bi bi-trash fs-2x text-danger"></i></a></td>\n';
    html += '</tr>\n';
    
    return html;
}

function loadRolesIntoDataTable(){
    var e_table = getElementByID( _table_name );
    
    var data = {
        what_do_you_want: 'scodezy_get_roles',
        need_functionality_count: "1"
    };
    
    showDataTableLoading();
    
    get_roles(function( returned_data ){
        
        hideDataTableLoading();
       
        //console.log( returned_data );
       
        var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
        if( jSon == false ){                    
            return;
        }

        jSon = jSon[ 0 ];

        if( jSon[ 'type' ] == 'error' ){
            showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
            return;
        }
        if( jSon[ 'type' ] == 'success' ){
            var json = jSon[ 'info' ];
            var data = json.data;

            var html = "";
            $.each( data, function( i, v ){
                html += createRowForDataTable( v );
            });

            DataTable.destroy();
            e_table.find( 'tbody' ).html( html );
            initDataTable();

            //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
            //    redirect( data.login_url ); 
            //});

            return;
        }
        
    }, data );
}

// Make an Ajax call to get all the roles from API, and passing a callback function to be executed when the result of AJAX is received
function get_roles( callback, url_parameters = "" ){
    
    var data = "";
    if( url_parameters == "" ){
        data = {
            what_do_you_want: 'scodezy_get_roles'
        };
    }
    else{
        data = url_parameters;
    }
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        success: function( returned_data ){
            ////console.log( returned_data );
            
            callback( returned_data );
        }
    });
}

// All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
function initDataTable(){
    
    DataTableParameters = {
        searching: true,
        language: {
            emptyTable: 'Roles does not exist in the system'
        },        
        columnDefs: [
            {// set default column settings
                orderable: false,
                targets: [3] // column indexes separated by a comma to which the above attribute should be applied
            },
            {
                searchable: false,
                targets: [3]
            },
            {
                className: "text-left",
                "targets": [2]
            }
        ],
        order: [
            [0, "asc"]
        ], // set first column as a default sort by asc,
        
        filter: false,
        dom: // This dom is to fix the datatables search input from not appearing 
            "<'row mb-2'" +
            "<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
            "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>" +
            ">" +

            "<'table-responsive'tr>" +

            "<'row'" +
            "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
            "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
            ">"
    };
    
    
    DataTable = $( _table ).DataTable( DataTableParameters );
    
    
}











function showDataTableLoading(){
    var e_table_loading = $( '.table-loading' );
    e_table_loading.append( loadingEl );
    loadingEl.classList.add( "page-loader" );
    loadingEl.classList.add( "section-loader" );
    loadingEl.classList.add( "flex-column" );
    loadingEl.innerHTML = `
        <span class="spinner-border text-primary" role="status"></span>
        <span class="text-gray-800 fs-6 fw-semibold mt-5">Loading...</span>
    `;

    // Show page loading
    KTApp.showPageLoading();

}

function hideDataTableLoading(){
    KTApp.hidePageLoading();
    loadingEl.remove();
}