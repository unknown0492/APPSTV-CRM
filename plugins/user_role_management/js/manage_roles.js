var _table_name = "table_roles";   // The name of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var _table = "#" + _table_name;   // The id with # of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var DataTable = null;
var DataTableParameters = null;
const loadingEl = document.createElement("div");    // Loading Div for DataTable entries loading

var roles_functionalities_c = [];       // Stores the functionality ids from the selected functionalities in the Create Role Modal
var roles_functionalities_e = [];       // Stores the functionality ids from the selected functionalities in the Edit Role Modal

$( document ).ready(function(){
    // All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
    initDataTable();
    
    loadRolesIntoDataTable();
    
    refresh_plugins_in_dropdown( 'c' );
    refresh_plugins_in_dropdown( 'e' );
    
    initCreateRoleModal();
    
    filter_checkbox_for_functionality_type( 'c' );
    filter_checkbox_for_functionality_type( 'e' );
    
    selectPluginToViewItsFunctionalities( 'c' );
    selectPluginToViewItsFunctionalities( 'e' );
    
    functionalitiesCheckedChangeListener( 'c' );
    functionalitiesCheckedChangeListener( 'e' );
    
    selectAllFunctionalitiesCheckbox( 'c' );
    selectAllFunctionalitiesCheckbox( 'e' );
    
    create_role();
    
    update_role();
    
    $( 'body' ).tooltip({
        selector: '.functionality_description_tooltip'
    });
});

function selectAllFunctionalitiesCheckbox( c_or_e ){
    var check_all = $( 'input[name="check_all_functionalities_'+c_or_e+'"]' );
    var temp_array = roles_functionalities_c;
    if( c_or_e == 'e' ){
        temp_array = roles_functionalities_e;        
    }

    $( '.div_check_functionalities_' + c_or_e ).on( 'change', 'input[name="check_all_functionalities_'+c_or_e+'"]', function( e ){
        
        if( c_or_e == 'c' )
            temp_array = roles_functionalities_c;
        else if( c_or_e == 'e' )
            temp_array = roles_functionalities_e;
        
        if( e.target.checked ){
            $.each( $( 'input[name="check_functionalities_'+c_or_e+'"]' ), function( i, v ){
                if( $( v ).parent().hasClass( 'hidden' ) || $( v ).parent().hasClass( 'd-none' ) ){
                    //console.log( 'is hidden' );
                }
                else{
                    $( v ).prop( 'checked', 'checked' );
                    ////console.log( 'temp_array.indexOf( $( v ).val() ): ' + $( v ).val() + " : " + temp_array.indexOf( $( v ).val() ) );
                    if( temp_array.indexOf( $( v ).val() ) == -1 ){  // If the functionality_id is not present in the array, then push it in
                        temp_array.push( $( v ).val() );
                    }
                }
            });
        }
        else{
            $.each( $( 'input[name="check_functionalities_'+c_or_e+'"]' ), function( i, v ){
                if( $( v ).parent().hasClass( 'hidden' ) || $( v ).parent().hasClass( 'd-none' ) ){
                    //console.log( 'is hidden' );
                }
                else{
                    $( v ).prop( 'checked', '' );
                    ////console.log( 'temp_array.indexOf( $( v ).val() ): ' + $( v ).val() + " : " + temp_array.indexOf( $( v ).val() ) );
                    if( (f_id = temp_array.indexOf( $( v ).val() ) ) > -1 ){  // If the functionality_id is present in the array, then remove it
                        temp_array.splice( f_id, 1 );
                        //console.log( 'Removing Item: ' + $( v ).val() );
                    }
                }
            });
        }
        
        if( c_or_e == 'c' )
            roles_functionalities_c = temp_array;
        else if( c_or_e == 'e' )
            roles_functionalities_e = temp_array;
        
        // 
        
        ////console.log( temp_array );
        //console.log( roles_functionalities_e );
        
    });
    
}

function update_role(){
    var modal                       = getElementByID( 'modal_edit_role' );
    var form                        = getElementByID( 'form_edit_role' );
    var e_table                     = getElementByID( _table_name );
    var e_hidden_role_id            = getElementByID( 'hidden_role_id_e' );
    var e_role_name                 = getElementByID( 'input_role_name_e' );
    var e_role_slug                 = getElementByID( 'input_role_slug_e' );
    var e_select_plugin             = getElementByID( 'select_plugin_e' );
    var btn_submit                  = getElementByID( 'btn_update_role' );
    var btn_cancel                  = getElementByID( 'btn_cancel_edit_role_modal' );
    var e_div_functionality_type    = $( '.div_functionality_type_e' );
    var e_div                       = $( '.div_check_functionalities_e' );
    var e_select_functionality_type = getElementByID( 'select_functionality_type_e' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_edit_role' );
    
    btn_submit.on( 'click', function(){
        var parsley_role_name          = e_role_name.parsley();
        var parsley_role_slug          = e_role_slug.parsley();
        
        if( !parsley_role_name.isValid() ){
            showSimpleToast( "error", "Please check the Role Name and try again !" );
            return;
        }
        if( !parsley_role_slug.isValid() ){
            showSimpleToast( "error", "Please check the Role Slug and try again !" );
            return;
        }
        
        var val_role_id             = e_hidden_role_id.val();
        var val_role_name           = e_role_name.val();
        var val_role_slug           = e_role_slug.val();
        
        
        
        var formData = new FormData();
        formData.append( 'what_do_you_want', 'scodezy_update_role' );
        formData.append( 'role_id', val_role_id );
        formData.append( 'role_name', val_role_name );
        formData.append( 'role_slug', val_role_slug );
        formData.append( 'functionalities', roles_functionalities_e );
        
        ////console.log( roles_functionalities_e );
        //return;

        // Show Loading Animation on the Button
        btn_submit.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit );
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function( returned_data ){
                //console.log( returned_data );

                // Show Loading Animation on the Button
                btn_submit.attr( 'data-kt-indicator', 'off' );
                enableFormElement( btn_submit );

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    //showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );                    
                    showSimpleToast( "error", jSon[ 'info' ] );                    
                    //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];

                    // Remove old entry from the DataTable
                    var trToBeRemoved = e_table.find( 'tr[data-role-id="'+val_role_id+'"]' );
                    DataTable.row( trToBeRemoved ).remove().draw();
                    
                    // Add this entry into the DataTable 
                    var value = data.data;
                    var tr = createRowForDataTable( value );
                    DataTable.row.add( $( tr ) ).draw();
                    
                    showActionSweetAlert( data.info, "success", true, "Thank you", "btn btn-primary", function(){} );
                    
                    setTimeout( function(){
                        // Close the modal and reset the form
                        modal.modal( 'hide' );
                        btn_cancel.trigger( 'click' );
                    }, 1000 );
                    
                    return;
                }
            }
        });
        
    });
    
    btn_cancel.on( 'click', function(){
        
        modal.modal( 'hide' );
        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );  

        e_select_plugin.val( '' );
        e_select_plugin.trigger( 'change.select2' );

        e_select_functionality_type.val( '' );
        e_select_functionality_type.trigger( 'change.select2' );

        hide( e_div_functionality_type );

        e_div.html( '' );

        roles_functionalities_e = [];
        
    });
}

// This function is inside the onclick attribute of the Edit Button in the column of each row of the DataTable
function editRole( thees, e ){
    e.preventDefault();
    ////console.log( thees );
    var val_role_id = $( thees ).parent().parent( 'tr' ).data( 'role-id' );
    ////console.log( val_functionality_id );
    
    var modal                       = $( '#modal_edit_role' );
    var form_id                     = '#form_edit_role';
    var form                        = $( form_id );
    var btn_cancel                  = $( '#btn_cancel_edit_role_modal' );
    var btn_submit                  = $( '#btn_edit_role' );
    var e_div_functionality_type    = $( '.div_functionality_type_e' );
    var e_div                       = $( '.div_check_functionalities_e' );
    var e_select_functionality_type = getElementByID( 'select_functionality_type_e' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    var e_hidden_role_id_e     = getElementByID( 'hidden_role_id_e' );
    var e_role_name            = getElementByID( 'input_role_name_e' );
    var e_role_slug            = getElementByID( 'input_role_slug_e' );
    
    
    // Clear the Modal Form and reset all values
    $( '#btn_refresh_plugins_e' ).trigger( 'click' );
    roles_functionalities_e = [];        
    hide( e_div_functionality_type );        
    e_div.html( '' );
    
    scodezyForm.getParsleyForm().reset();
    scodezyForm.getForm().trigger( "reset" );
    
    // Show Loading Dialog while loading the role data from the API
    showLoadingSweetAlert( "Loading", "Please wait while the Role Information is being retrieved" );
    
    var data = {
        what_do_you_want: 'scodezy_get_role',
        role_id: val_role_id
    };
    
    // get single role information from Webservice and display it in Edit Modal
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        success: function( returned_data ){
            //console.log( returned_data );
            
            hideLoadingSweetAlert();

            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                //showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 )
                showSimpleToast( "error", jSon[ 'info' ] );
                //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var jsonInfo = jSon[ 'info' ];
                var data = jsonInfo.data;
                
                modal.modal( 'show' );
                
                e_hidden_role_id_e.val( data.role_id );
                e_role_name.val( data.role_name );
                e_role_slug.val( data.role_slug );
                roles_functionalities_e = [];
                
                var roles_functionalities = data.roles_functionalities;
                //console.log( roles_functionalities );
                var html = '<div class="form-check form-check-custom form-check-solid form-check-sm mb-5">\n' +
                                        '<input class="form-check-input check_all_functionalities_e" type="checkbox" name="check_all_functionalities_e" value="" />\n' +
                                            '<label class="form-check-label bold">\n' +
                                                'SELECT ALL' +
                                            '</label>\n' +
                                   '</div>\n';
                $.each( roles_functionalities, function( i, v ){
                    html += createCheckboxForFunctionalitySelection( v, 'e' );
                    roles_functionalities_e.push( v.functionality_id );
                });
                //console.log( roles_functionalities_e );
                e_div.html( html );
                
                // Set checked from the roles_functionalities_e array
                $.each( e_div.find( 'input[name="check_functionalities_e"]' ), function( i, v ){
                    var val_functionality_id = $( v ).val();
                    //console.log( val_functionality_id );
                    var index = roles_functionalities_e.indexOf( val_functionality_id );
                    //console.log( index );
                    if( index > -1 ){
                        $( v ).prop( "checked", "checked" );
                    }
                });
                
                
                show( e_div );
                
                return;
            }
        }
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

function create_role(){
    var modal       = getElementByID( 'modal_create_role' );
    //var form        = getElementByID( 'form_create_role' );
    var e_role_name = getElementByID( 'input_role_name_c' );
    var e_role_slug = getElementByID( 'input_role_slug_c' );
    //var e_select_plugin = getElementByID( 'select_plugin_c' );
    var btn_submit      = getElementByID( 'btn_create_functionality' );
    var btn_cancel      = getElementByID( 'btn_cancel_create_role_modal' );
    
    btn_submit.on( 'click', function(){
        
        //var e_role_name            = getElementByID( 'input_role_name_c' );
        //var e_role_slug            = getElementByID( 'input_role_slug_c' );
        //var e_select_plugin        = getElementByID( 'select_plugin_c' );
        
        var parsley_role_name          = e_role_name.parsley();
        var parsley_role_slug          = e_role_slug.parsley();
        
        /*
        //console.log( "Visible : " + val_page_is_visible );
        //console.log( "Plugin : " + val_plugin );
        //console.log( "Functionality : " + val_functionality );
        //console.log( "Hierarchy : " + val_hierarchy );
        //console.log( "Parent : " + val_parent_page );
        */
        
        if( !parsley_role_name.isValid() ){
            showSimpleToast( "error", "Please check the Role Name and try again !" );
            return;
        }
        if( !parsley_role_slug.isValid() ){
            showSimpleToast( "error", "Please check the Role Slug and try again !" );
            return;
        }
        
        var val_role_name           = e_role_name.val();
        var val_role_slug           = e_role_slug.val();
        
        // Show Loading Animation on the Button
        btn_submit.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit );

        var data = {
            what_do_you_want: "scodezy_create_role",
            role_name: val_role_name,
            role_slug: val_role_slug,
            functionalities: roles_functionalities_c
        };
        
        var formData = new FormData();
        formData.append( 'what_do_you_want', 'scodezy_create_role' );
        formData.append( 'role_name', val_role_name );
        formData.append( 'role_slug', val_role_slug );
        formData.append( 'functionalities', roles_functionalities_c );

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function( returned_data ){
                //console.log( returned_data );

                // Show Loading Animation on the Button
                btn_submit.attr( 'data-kt-indicator', 'off' );
                enableFormElement( btn_submit );

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    //showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );                    
                    showSimpleToast( "error", jSon[ 'info' ] );                    
                    //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];

                    // Add this entry into the DataTable 
                    var value = data.data;
                    var tr = createRowForDataTable( value );
                    DataTable.row.add( $( tr ) ).draw();
                    
                    showActionSweetAlert( data.info, "success", true, "Thank you", "btn btn-primary", function(){} );
                    
                    // Close the modal and reset the form
                    modal.modal( 'hide' );
                    btn_cancel.trigger( 'click' );
                    
                    return;
                }
            }
        });
        
    });
    
    
}

function functionalitiesCheckedChangeListener( c_or_e ){
    var e_check_functionalities     = $( '.check_functionalities_' + c_or_e );
    
    $( '.div_check_functionalities_' + c_or_e ).on( 'change', 'input[name="check_functionalities_'+c_or_e+'"]', function( e ){
        ////console.log( e.target.checked );
        var val_functionality_id = $( e.target ).val();
        if( e.target.checked ){
            if( c_or_e == "c" )
                roles_functionalities_c.push( val_functionality_id );
            else
                roles_functionalities_e.push( val_functionality_id );
        }
        else{
            if( c_or_e == "c" ){
                var index = roles_functionalities_c.indexOf( val_functionality_id );
                if (index > -1) { // only splice array when item is found
                    roles_functionalities_c.splice( index, 1 ); // 2nd parameter means remove one item only
                }
            }
            else{
                var index = roles_functionalities_e.indexOf( val_functionality_id );
                if (index > -1) { // only splice array when item is found
                    roles_functionalities_e.splice( index, 1 ); // 2nd parameter means remove one item only
                }
            }
        }
        ////console.log( roles_functionalities_c );
        
    });
}

function createCheckboxForFunctionalitySelection( value, c_or_e ){
    var functionality_description = '<span class="m2-1 functionality_description_tooltip" data-bs-html="true" data-bs-toggle="tooltip" title="'+ value.functionality_name + '<br />' + value.functionality_description +'">\n' + 
                                        '<i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>\n' +
                                    '</span>\n';
    
    var html = '<div data-functionality-id="'+ value.functionality_id +'" data-functionality-type="'+ value.functionality_type +'" class="form-check form-check-custom form-check-solid form-check-sm mb-5">\n' +
                    '<input class="form-check-input" type="checkbox" name="check_functionalities_'+c_or_e+'" value="'+ value.functionality_id +'" />\n' +
                        '<label class="form-check-label">\n' +
                            value.alias + '\n' +
                            functionality_description + '\n' +
                        '</label>\n' +                        
               '</div>\n';
    return html;
}

// Setting EventListener for change event on the Select Plugin dropdown, so that when its value is selected, it will deliver a call to API to get functionalities for that plugin
function selectPluginToViewItsFunctionalities( c_or_e ){
    var e_select_plugin             = $( '#select_plugin_' + c_or_e );
    var e_div                       = $( '.div_check_functionalities_' + c_or_e );
    var e_div_functionality_type    = $( '.div_functionality_type_' + c_or_e );
    var e_select_functionality_type = getElementByID( 'select_functionality_type_' + c_or_e );
    //var e_table_loading     = $( '.pages-table-loading' );
    
    e_select_plugin.on( 'change', function( e ){
        
        var val_plugin_id = e_select_plugin.val();
        
        var data = {
            what_do_you_want: 'scodezy_get_functionalities',
            plugin_id: val_plugin_id
        };
        
        get_all_functionalities(function(){
            //e_table_pages.empty();
            showDataTableLoading();
            hide( e_div_functionality_type );

            $.ajax({
                url: getWebservice(),
                type: 'POST',
                data: data,
                success: function( returned_data ){
                    //console.log( returned_data );

                    hideDataTableLoading();

                    var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                    if( jSon == false ){                    
                        return;
                    }

                    jSon = jSon[ 0 ];

                    if( jSon[ 'type' ] == 'error' ){
                        //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                        //e_select_plugin.val( '-1' );
                        //e_select_plugin.trigger( 'change' );
                        e_div.html( jSon[ 'info' ] );
                        //hide( e_div );
                        return;
                    }
                    if( jSon[ 'type' ] == 'success' ){

                        var json = jSon[ 'info' ];
                        var data = json.data;

                        //var html = "";
                        var html = '<div class="form-check form-check-custom form-check-solid form-check-sm mb-5">\n' +
                                        '<input class="form-check-input check_all_functionalities_'+c_or_e+'" type="checkbox" name="check_all_functionalities_'+c_or_e+'" value="" />\n' +
                                            '<label class="form-check-label bold">\n' +
                                                'SELECT ALL' +
                                            '</label>\n' +
                                   '</div>\n';
                        $.each( data, function( i, v ){
                            html += createCheckboxForFunctionalitySelection( v, c_or_e );
                        });
                        ////console.log( html );
                        show( e_div_functionality_type );
                        e_div.html( html );
                        
                        // Set checked from the roles_functionalities_c array
                        var temp_array = roles_functionalities_c;
                        if( c_or_e == "e" )
                            temp_array = roles_functionalities_e;
                        $.each( e_div.find( 'input[name="check_functionalities_'+c_or_e+'"]' ), function( i, v ){
                            var val_functionality_id = $( v ).val();
                            var index = temp_array.indexOf( val_functionality_id );
                            if( index > -1 ){
                                $( v ).prop( "checked", "checked" );
                            }
                        });
                        
                        //e_select_functionality_type = getElementByID( 'select_functionality_type_c' );
                        e_select_functionality_type.trigger( 'change' );
                        return;
                    }
                }
            });
        }, data );
        
    });
}

// Make an Ajax call to get all the pages from API, and passing a callback function to be executed when the result of AJAX is received
function get_all_functionalities( callback, url_parameters = "" ){
    
    var data = "";
    if( url_parameters == "" ){
        data = {
            what_do_you_want: 'scodezy_get_functionalities'
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

// Creates an HTML Element for the Select Tag's options for each value that is supplied to it
function createPluginInformationOptionElementForSelectTag( value ){
    return '<option value="'+ value.plugin_id +'">'+ value.plugin_alias +'</option>\n';    
}

// Load the list of plugins from API into the Select Plugin dropdown on Create functionality modal and Edit functionality modal based on the function input parameter c or e
function refresh_plugins_in_dropdown( c_or_e ){
    var btn_refresh_plugins_c = $( '#btn_refresh_plugins_' + c_or_e );
    
    btn_refresh_plugins_c.on( 'click', function(){
        btn_refresh_plugins_c.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_refresh_plugins_c );
        
        var e_select_plugin = $( '#select_plugin_' + c_or_e );
    
        get_all_plugin_information(function( returned_data ){

            btn_refresh_plugins_c.attr( 'data-kt-indicator', 'off' );
            enableFormElement( btn_refresh_plugins_c );

            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){                    
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var data = jSon[ 'info' ];
                //console.log( data.info );
                //console.log( data.data );
                var html = '<option></option>\n';
                $.each( data.data, function( i, v ){
                    html += createPluginInformationOptionElementForSelectTag( v );
                });

                e_select_plugin.html( html );

                return;
            }
        });
    });
    
    //btn_refresh_plugins_c.trigger( 'click' );

}

// Make an Ajax call to get all the plugins from API, and passing a callback function to be executed when the result of AJAX is received
function get_all_plugin_information( callback ){
    
    
    var data = {
        what_do_you_want: 'scodezy_get_all_plugin_information'
    };
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        success: function( returned_data ){
            //console.log( returned_data );
            
            callback( returned_data );
        }
    });
}

function filter_checkbox_for_functionality_type( c_or_e ){
    var e_select_functionality_type = getElementByID( 'select_functionality_type_' + c_or_e );
    
    // Filter CRUD/ACCESS for functionality type checkboxes display
    e_select_functionality_type.on( 'change', function(){
        var val_type_filter = $( this ).val();
        //console.log( val_type_filter );
        if( val_type_filter == "" )
            val_type_filter = "ALL";
        
        // Hide all checkboxes that does not have this filter
        $.each( $( 'input[name="check_functionalities_'+c_or_e+'"]' ), function( i, v ){
            if( val_type_filter == "ALL" ){
                show( $( v ).parent() );
            }
            else if( $( v ).parent().attr( 'data-functionality-type' ) != val_type_filter ){
                hide( $( v ).parent() );
            }
            else{
                show( $( v ).parent() );
            }
        });
        
        // Check/Uncheck Select All checkbox
        var selectedCount = 0;
        var visibleItemsCount = 0;
        $.each( $( 'input[name="check_functionalities_'+c_or_e+'"]' ), function( i, v ){
            //show( $( v ).parent() ); 
            if( !($( v ).parent().hasClass( 'hidden' ) || $( v ).parent().hasClass( 'd-none' )) ){
                visibleItemsCount++;
            }
            if( !($( v ).parent().hasClass( 'hidden' ) || $( v ).parent().hasClass( 'd-none' )) && $( v ).is( ':checked' ) ){
                //console.log( v );
                selectedCount++;
            }
        });
        //console.log( 'visibleItemsCount: ' + visibleItemsCount );
        //console.log( 'selectedCount: ' + selectedCount );
        if( selectedCount == visibleItemsCount )
            $( '.check_all_functionalities_' + c_or_e ).prop( 'checked', 'checked' );
        else
            $( '.check_all_functionalities_' + c_or_e ).prop( 'checked', '' );
        
    });
}

function initCreateRoleModal(){
    var modal                       = getElementByID( 'modal_create_role' );
    var btn_open_modal              = getElementByID( 'btn_open_create_role_modal' );
    var btn_reset                   = getElementByID( 'btn_cancel_create_role_modal' );
    var btn_refresh_plugins         = $( '#btn_refresh_plugins_c' );
    var e_select_plugin             = $( '#select_plugin_c' );
    var e_div_functionality_type    = $( '.div_functionality_type_c' );
    var e_div                       = $( '.div_check_functionalities_c' );
    var e_select_functionality_type = getElementByID( 'select_functionality_type_c' );
    var form                        = getElementByID( 'form_create_role' );
    
    //var e_form              = getElementByID( 'form_create_role' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_create_role' );
    
    btn_open_modal.on( 'click', function(){
        btn_reset.trigger( 'click' );
        
        // Refresh the select plugin dropdown
        btn_refresh_plugins.trigger( 'click' );
        
        // Refresh the functinalities storage array
        roles_functionalities_c = [];
        
        hide( e_div_functionality_type );
        
        e_div.html( '' );
        
        modal.modal( 'show' );
        
    });
    
    
    btn_reset.on( 'click', function(){
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );  
        
        e_select_plugin.val( '' );
        e_select_plugin.trigger( 'change.select2' );
        
        e_select_functionality_type.val( '' );
        e_select_functionality_type.trigger( 'change.select2' );
        
        hide( e_div_functionality_type );
        
        e_div.html( '' );
        
        roles_functionalities_c = [];
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