var _table = "#table_functionalities";   // The id with # of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var _table_name = "table_functionalities";   // The name of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var DataTable = null;
var DataTableParameters = null;
const loadingEl = document.createElement("div");    // Loading Div for DataTable entries loading

$( document ).ready(function(){
    // All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
    initDataTable();
    
    // This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
    multipleRowsSelection();
    
    // Setting EventListener for change event on the Select Plugin dropdown, so that when its value is selected, it will deliver a call to API to get functionalities  for that plugin
    selectPluginToViewItsFunctionalities();
    
    // Retrieve all the plugins from API into the dropdown, so that we can select a plugin to view the functionalities that it holds, in the DataTable
    loadPluginsIntoDropdown();
    
    // This will initialize the Create functionality Modal features
    initCreateFunctionalityModal();
    // This will fill the select plugin dropdown inside the create functoinality modal
    refresh_plugins_in_dropdown( 'c' );
    
    // This will initialize the Edit functionality Modal features
    initEditFunctionalityModal();
    // This will fill the select plugin dropdown inside the edit functoinality modal
    refresh_plugins_in_dropdown( 'e' );
    
    // EventListener on the Submit Button present inside Create functionality Modal to submit data to the API to create a new functionality
    create_functionality();
    
    // EventListener on the Submit Button present inside Edit functionality Modal to submit data to the API to update a functionality
    update_functionality();
    
    // Event listener for the Delete Selected button to delete multiple rows from the DataTable at once
    deleteSelectedRows();    
    
    // Event listener for the Export Selected button to export selected functionalities at once
    exportSelectedRows();
    
    initImportFunctionalitiesModal();
    
});


function create_file_item( file ){
    var name = file.name;
    var size = parseInt( Math.round(file.size/1000) );
    var sizeDisplay = size + " KB";
    //console.log( size );
    //console.log( (size > 1000) );
    if( size > 1000 ){
        size = Math.round(size/1000);
        sizeDisplay = size + " MB";
    }
    if( size > 1000 ){
        size = Math.round(size/1000);
        sizeDisplay = size + " GB";
    }
    //var sizeDisplay = (size > 1000)?size + " MB":size + " KB";
    
    var html = '<div class="file-item p-5">' +
        '<span class="file-name">' + name + '</span>' +
        '<span class="file-size">( ' + sizeDisplay + ' )</span>' +
    '</div>';
    
    return html;
}

function initImportFunctionalitiesModal(){
    var modal                       = $( '#modal_import_functionalities' );
    var form_id                     = '#form_import_functionalities';
    var form                        = $( form_id );
    var e_file_functionalities      = $( '#file_functionalities_import' );
    var e_scodezy_selected_files    = form.find( '.scodezy-selected-files' );
    var btn_upload                  = $( '#btn_import_functionalities' );
    var btn_open_modal              = $( '#btn_open_import_functionalities_modal' );
    var btn_cancel                  = $( '#btn_reset_import_functionalities' );
    var btn_select_file             = $( '#btn_select_functionalities_file_import' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    btn_upload.on( 'click', function(){
        // Check if the file is selected
        var files = e_file_functionalities[ 0 ].files;
        if( files.length == 0 ){
            showSimpleToast( "error", "Please select a json file to import" );  
            return;
        }
        
        // Check if its a json file format
        var selectedFile = files[ 0 ];
        //console.log( selectedFile );
        if( selectedFile.type != "application/json" ){
            showSimpleToast( "error", "Selected file is not a valid json file" );  
            return;
        }
        
        // Post to Ajax webservice
        showLoadingSweetAlert( "Importing", "Please wait while the functionalities are being imported" );
        
        var formData = new FormData();
        formData.append( "what_do_you_want", "scodezy_import_functionalities" );
        formData.append( "functionalities_file", selectedFile );
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            processData: false,
            contentType: false,
            data: formData,
            success: function( returned_data ){
                console.log( returned_data );

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

                    var info = jSon[ 'info' ];

                    //var data = info.data;
                    //window.location = data;
                    
                    showActionSweetAlert( info, "success", false, "Okay", "btn btn-info", function(){
                        setTimeout( function(){
                            refreshPage();
                        }, 500 );
                         
                    });
                    
                    btn_cancel.trigger( 'click' );
                    modal.modal( 'hide' );
                    
                    //showSimpleToast( "success", data.info );
                    
                    return;
                }



            }
        });
    });
    
    e_file_functionalities.on( 'change', function( e ){
        //console.log( e.target.files[ 0 ] );
        var file = e.target.files[ 0 ];
        var html = create_file_item( file );
        
        e_scodezy_selected_files.html( html );
    });
    
    btn_select_file.on( 'click', function(){
        e_file_functionalities.trigger( 'click' );
    });
    
    
    btn_cancel.on( 'click', function(){
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );    
        
        e_file_functionalities.val( null );
        
        e_scodezy_selected_files.html( '' );
    });
    
    btn_open_modal.on( 'click', function(){
        btn_cancel.trigger( 'click' );
        
        modal.modal( 'show' );
    });
}

// Event listener for the Export Selected button to export selected functionalities at once
function exportSelectedRows(){
    var e_table                     = $( _table );
    
    $( '.group_actions' ).on( 'click', '.export_selected_rows', function(){
        
        // Show a confirmation SweetActionAlert to confirm the export operation
        showConfirmSweetAlert( "Confirm", "Are you sure you want to export the selected functionalities ?", "question", "Yes", "btn btn-primary", "Cancel", "btn btn-default", function(){
            //console.log( 'yes clicked' );

            showLoadingSweetAlert( "Exporting", "Please wait while the functionalities are being exported" );
            
            var e_table_children_checkbox   = e_table.find( '.table-children-checkbox' );
            var val_functionalities = [];
            $.each( e_table_children_checkbox, function( i, v ){
                var checked = $( v ).prop( 'checked' );
                if( checked )
                    val_functionalities.push( $( v ).parent().parent().parent().attr( 'data-functionality-id' ) );
            });
            //console.log( val_pages );
            
            var formData = new FormData();
            formData.append( 'what_do_you_want', 'scodezy_export_functionalities' );
            formData.append( 'functionality_ids', val_functionalities );

            $.ajax({
                url: getWebservice(),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function( returned_data ){
                    console.log( returned_data );

                    // Hide Loading Animation 
                    hideLoadingSweetAlert();

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
                        var message = data.info;
                        var data = data.data;
                        window.location = data;

                        showActionSweetAlert( message, "success", true, "Okay", "btn btn-primary", function(){} );

                        return;
                    }



                }
            });
        });
        
        
        
    });
}

// Event listener for the Delete Selected button to delete multiple rows from the DataTable at once
function deleteSelectedRows(){
    var e_table                     = $( _table );
    
    $( '.group_actions' ).on( 'click', '.delete_selected_rows', function(){
        
        // Show a confirmation SweetActionAlert to confirm the delete operation
        showConfirmSweetAlert( "Confirm", "Are you sure you want to delete the selected functionalities", "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
            //console.log( 'yes clicked' );

            showLoadingSweetAlert( "Deleting", "Please wait while the functionalities are being deleted" );
            
            var e_table_children_checkbox   = e_table.find( '.table-children-checkbox' );
            var val_functionalities = [];
            $.each( e_table_children_checkbox, function( i, v ){
                var checked = $( v ).prop( 'checked' );
                if( checked )
                    val_functionalities.push( $( v ).parent().parent().parent().attr( 'data-functionality-id' ) );
            });
            //console.log( val_pages );
            
            var formData = new FormData();
            formData.append( 'what_do_you_want', 'scodezy_delete_functionalities' );
            formData.append( 'functionality_ids', val_functionalities );

            $.ajax({
                url: getWebservice(),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function( returned_data ){
                    console.log( returned_data );

                    // Hide Loading Animation 
                    hideLoadingSweetAlert();

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

                        var deleted_functionalities = data.data;
                        
                        $.each( deleted_functionalities, function( i, v ){
                            DataTable.row( e_table.find( 'tr[data-functionality-id="'+v.functionality_id+'"]' ) ).remove().draw();
                        });
                        
                        //showActionSweetAlert( data.info, "success", "Thank you", "btn btn-primary", function(){} );
                        showSimpleToast( "success", data.info );

                        return;
                    }



                }
            });
        });
        
        
        
    });
}

// EventListener on the Submit Button present inside Create functionality Modal to submit data to the API to create a new functionality
function create_functionality(){    
    var modal           = $( '#modal_create_functionality' );
    var btn_modal       = $( '#btn_open_create_functionality_modal' );
    var form_id         = '#form_create_functionality';
    var form            = $( form_id );
    var btn_cancel      = $( '#btn_cancel_create_functionality_modal' );
    var btn_submit      = $( '#btn_create_functionality' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    // Submit the data to the API
    btn_submit.on( 'click', function(){
        var e_functionality_name            = getElementByID( 'input_functionality_name_c' );
        var e_alias                         = getElementByID( 'input_alias_c' );
        var e_functionality_description     = getElementByID( 'ta_functionality_description_c' );
        var e_is_page                       = getElementByID( 'check_is_page_c' );
        var e_is_a_content                  = getElementByID( 'check_is_a_content_c' );
        var e_select_plugin                 = getElementByID( 'select_plugin_c' );
        var e_functionality_type            = getElement( 'input[name="radio_functionality_type_c"]' );
        
        var parsley_functionality_name          = e_functionality_name.parsley();
        var parsley_alias                       = e_alias.parsley();
        var parsley_functionality_description   = e_functionality_description.parsley();
        
        var val_is_page             = e_is_page.is( ':checked' )?"1":"0";
        var val_is_a_content        = e_is_a_content.is( ':checked' )?"1":"0";
        var val_plugin              = e_select_plugin.val();
        var val_functionality_type  = $( 'input[name="radio_functionality_type_c"]:checked' ).val();
        
        /*
        console.log( "Visible : " + val_page_is_visible );
        console.log( "Plugin : " + val_plugin );
        console.log( "Functionality : " + val_functionality );
        console.log( "Hierarchy : " + val_hierarchy );
        console.log( "Parent : " + val_parent_page );
        */
        
        if( !parsley_functionality_name.isValid() ){
            showSimpleToast( "error", "Please check the Functionality Name and try again !" );
            return;
        }
        if( !parsley_alias.isValid() ){
            showSimpleToast( "error", "Please check the Alias and try again !" );
            return;
        }
        if( !parsley_functionality_description.isValid() ){
            showSimpleToast( "error", "Please check the Functionality Description and try again !" );
            return;
        }
        if( (val_plugin == "") || (typeof val_plugin == "undefined") ){
            showSimpleToast( "error", "Please select a plugin from the dropdown !" );
            return;
        }
        if( (val_functionality_type == "") || (typeof val_functionality_type == "undefined") ){
            showSimpleToast( "error", "Please select a functionality type !" );
            return;
        }   
        
        var val_functionality_name           = e_functionality_name.val();
        var val_alias                        = e_alias.val();
        var val_functionality_description    = e_functionality_description.val();
        
        // Show Loading Animation on the Button
        btn_submit.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit );

        var data = {
            what_do_you_want: "scodezy_create_functionality",
            functionality_name: val_functionality_name,
            alias: val_alias,
            functionality_description: val_functionality_description,
            is_page: val_is_page,
            is_a_content: val_is_a_content,
            plugin_id: val_plugin,
            functionality_type: val_functionality_type
        };

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                console.log( returned_data );

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

                    // Add this entry into the pages DataTable if it belongs to the currently selected plugin
                    var value = data.data;
                    if( ($( '#select_plugin' ).val() == value.plugin_id) || ($( '#select_plugin' ).val()=="-1") ){
                        var tr = createRowForDataTable( value );
                        DataTable.row.add( $( tr ) ).draw();
                    }
                    
                    showActionSweetAlert( data.info, "success", "Thank you", "btn btn-primary", function(){} );
                    
                    // Close the modal and reset the form
                    btn_cancel.trigger( 'click' );
                    
                    return;
                }
            }
        });
    });
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
                console.log( data.info );
                console.log( data.data );
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

// This will initialize the Create functionality Modal features
function initCreateFunctionalityModal(){
    var modal           = $( '#modal_create_functionality' );
    var btn_modal       = $( '#btn_open_create_functionality_modal' );
    var form_id         = '#form_create_functionality';
    var form            = $( form_id );
    var btn_cancel      = $( '#btn_cancel_create_functionality_modal' );
    var btn_submit      = $( '#btn_create_functionality' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    // Open the Modal
    btn_modal.on( 'click', function(){
        $( '#btn_refresh_plugins_c' ).trigger( 'click' );
        $( '.radio_functionality_type_c' ).parent().removeClass( 'active' );
        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        modal.modal( 'show' );        
    });
    
    // Reset the form and close the modal
    btn_cancel.on( 'click', function(){        
        modal.modal( 'hide' );        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );        
    });
}

// This function is inside the onclick attribute of the Delete Button in the column of each row of the DataTable
function deleteFunctionality( thees, e ){
    //console.log( e );
    //console.log( thees );
    //thees.preventDefault();
    e.preventDefault();
    
    var tr = $( thees ).parent().parent( 'tr' );
    var val_functionality_id = tr.data( 'functionality-id' );
    //console.log( val_page_id );
    var val_functionality_alias = tr.find( 'td:nth-child(2)' ).text();
    
    // Show a confirmation SweetActionAlert to confirm the delete operation
    showConfirmSweetAlert( "Confirm", "Are you sure you want to delete the functionality '" + val_functionality_alias + "'" , "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
        //console.log( 'yes clicked' );
        
        showLoadingSweetAlert( "Deleting", "Please wait while the functionality is being deleted" );
        
        var data = {
            what_do_you_want: "scodezy_delete_functionality",
            functionality_id: val_functionality_id
        };

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );

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
                    
                    // Delete this entry from the DataTable if it belongs to the currently selected plugin
                    if( ($( '#select_plugin' ).val() == value.plugin_id) || ($( '#select_plugin' ).val() == "-1") ){
                        DataTable.row( tr ).remove().draw();
                    }
                    
                    showSimpleToast( "success", data.info );
                    
                    
                    return;
                }



            }
        });
    });
}

// EventListener on the Submit Button present inside Edit functionality Modal to submit data to the API to update a functionality
function update_functionality(){
    var modal           = $( '#modal_edit_functionality' );
    var form_id         = '#form_edit_functionality';
    var form            = $( form_id );
    var btn_cancel      = $( '#btn_cancel_edit_functionality_modal' );
    var btn_submit      = $( '#btn_edit_functionality' );
    var e_table         = $( _table );
    
    btn_submit.on( 'click', function(){
        
        var e_hidden_functionality_id       = getElementByID( 'hidden_functionality_id_e' );
        var e_functionality_name            = getElementByID( 'input_functionality_name_e' );
        var e_alias                         = getElementByID( 'input_alias_e' );
        var e_functionality_description     = getElementByID( 'ta_functionality_description_e' );
        var e_is_page                       = getElementByID( 'check_is_page_e' );
        var e_is_a_content                  = getElementByID( 'check_is_a_content_e' );
        var e_select_plugin                 = getElementByID( 'select_plugin_e' );
        var e_functionality_type            = getElement( 'input[name="radio_functionality_type_e"]' );
        
        var parsley_functionality_name          = e_functionality_name.parsley();
        var parsley_alias                       = e_alias.parsley();
        var parsley_functionality_description   = e_functionality_description.parsley();
        
        
        var val_is_page             = e_is_page.is( ':checked' )?"1":"0";
        var val_is_a_content        = e_is_a_content.is( ':checked' )?"1":"0";
        var val_plugin              = e_select_plugin.val();
        var val_functionality_type  = $( 'input[name="radio_functionality_type_e"]:checked' ).val();
        
        /*
        console.log( "Visible : " + val_page_is_visible );
        console.log( "Plugin : " + val_plugin );
        console.log( "Functionality : " + val_functionality );
        console.log( "Hierarchy : " + val_hierarchy );
        console.log( "Parent : " + val_parent_page );
        */
        
        if( !parsley_functionality_name.isValid() ){
            showSimpleToast( "error", "Please check the Functionality Name and try again !" );
            return;
        }
        if( !parsley_alias.isValid() ){
            showSimpleToast( "error", "Please check the Alias and try again !" );
            return;
        }
        if( !parsley_functionality_description.isValid() ){
            showSimpleToast( "error", "Please check the Functionality Description and try again !" );
            return;
        }
        if( (val_plugin == "") || (typeof val_plugin == "undefined") ){
            showSimpleToast( "error", "Please select a plugin from the dropdown !" );
            return;
        }
        if( (val_functionality_type == "") || (typeof val_functionality_type == "undefined") ){
            showSimpleToast( "error", "Please select a functionality type !" );
            return;
        }   
        
        var val_functionality_id             = e_hidden_functionality_id.val();
        var val_functionality_name           = e_functionality_name.val();
        var val_alias                        = e_alias.val();
        var val_functionality_description    = e_functionality_description.val();
        
        // Show Loading Animation on the Button
        btn_submit.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit );

        var data = {
            what_do_you_want: "scodezy_update_functionality",
            functionality_id: val_functionality_id,
            functionality_name: val_functionality_name,
            alias: val_alias,
            functionality_description: val_functionality_description,
            is_page: val_is_page,
            is_a_content: val_is_a_content,
            plugin_id: val_plugin,
            functionality_type: val_functionality_type
        };

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                console.log( returned_data );

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

                    // Add this entry into the pages DataTable if it belongs to the currently selected plugin
                    var value = data.data;
                    var tr = createRowForDataTable( value ); 
                    var trToBeRemoved = e_table.find( 'tr[data-functionality-id="'+val_functionality_id+'"]' );
                    DataTable.row( trToBeRemoved ).remove().draw();
                    if( ($( '#select_plugin' ).val() == value.plugin_id) || ($( '#select_plugin' ).val() == "-1") ){               
                        DataTable.row.add( $( tr ) ).draw();
                    }
                    
                    showSimpleToast( "success", data.info );
                    //showActionSweetAlert( data.info, "success", "Thank you", "btn btn-primary", function(){} );
                    
                    modal.modal( 'hide' );
                    
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
}

// This will initialize the Edit functionality Modal features
function initEditFunctionalityModal(){
    var modal           = $( '#modal_edit_functionality' );
    var form_id         = '#form_edit_functionality';
    //var form            = $( form_id );
    var btn_cancel      = $( '#btn_cancel_edit_functionality_modal' );
    //var btn_submit      = $( '#btn_edit_functionality' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    // Open the Modal
    /*
    btn_modal.on( 'click', function(){
        $( '#btn_refresh_plugins_c' ).trigger( 'click' );
        $( '.radio_functionality_type_c' ).parent().removeClass( 'active' );
        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        modal.modal( 'show' );        
    });
    */
    
    // Reset the form and close the modal
    btn_cancel.on( 'click', function(){        
        modal.modal( 'hide' );
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        $( '.radio_hierarchy_e' ).parent().removeClass( 'active' );
    });
}

// This function is inside the onclick attribute of the Edit Button in the column of each row of the DataTable
function editFunctionality( thees, e ){
    e.preventDefault();
    //console.log( thees );
    var val_functionality_id = $( thees ).parent().parent( 'tr' ).data( 'functionality-id' );
    console.log( val_functionality_id );
    
    var modal           = $( '#modal_edit_functionality' );
    var form_id         = '#form_edit_functionality';
    var form            = $( form_id );
    var btn_cancel      = $( '#btn_cancel_edit_functionality_modal' );
    var btn_submit      = $( '#btn_edit_functionality' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    var e_hidden_functionality_id       = getElementByID( 'hidden_functionality_id_e' );
    var e_functionality_name            = getElementByID( 'input_functionality_name_e' );
    var e_alias                         = getElementByID( 'input_alias_e' );
    var e_functionality_description     = getElementByID( 'ta_functionality_description_e' );
    var e_is_page                       = getElementByID( 'check_is_page_e' );
    var e_is_a_content                  = getElementByID( 'check_is_a_content_e' );
    var e_select_plugin                 = getElementByID( 'select_plugin_e' );
    var e_functionality_type            = getElement( 'input[name="radio_functionality_type_e"]' );
    
    // Clear the Modal Form and reset all values
    $( '#btn_refresh_plugins_e' ).trigger( 'click' );
    $( '.radio_functionality_type_e' ).parent().removeClass( 'active' );
        
    scodezyForm.getParsleyForm().reset();
    scodezyForm.getForm().trigger( "reset" );
    
    // Show Loading Dialog while loading the functionality data from the API
    showLoadingSweetAlert( "Loading", "Please wait while the Functionality Information is being retrieved" );
    
    var data = {
        what_do_you_want: 'scodezy_get_functionality',
        functionality_id: val_functionality_id
    };
    
    // get single functionality information from Webservice and display it in Edit Modal
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        success: function( returned_data ){
            console.log( returned_data );
            
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
                
                e_hidden_functionality_id.val( data.functionality_id );
                e_functionality_name.val( data.functionality_name );
                e_alias.val( data.alias );
                e_functionality_description.val( data.functionality_description );
                e_is_page.prop( 'checked', (data.is_page=="1")?'checked':'' ).trigger( 'change' );
                e_is_a_content.prop( 'checked', (data.is_a_content=="1")?'checked':'' ).trigger( 'change' );
                e_select_plugin.val( data.plugin_id );
                e_select_plugin.trigger( 'change.select2' );
                
                var e_radio = "";
                switch( data.functionality_type ){
                    case "CREATE":
                        e_radio = $( '#radio_type_create_e' );
                        break;

                    case "READ":
                        e_radio = $( '#radio_type_read_e' );
                        break;

                    case "UPDATE":
                        e_radio = $( '#radio_type_update_e' );
                        break;

                    case "DELETE":
                        e_radio = $( '#radio_type_delete_e' );
                        break;

                    case "ACCESS":
                        e_radio = $( '#radio_type_access_e' );
                        break;

                    default:
                        e_radio = "";
                }
                if( e_radio != "" ){
                    e_radio.prop( 'checked', 'checked' );                        
                    e_radio.trigger( 'click' );
                    e_radio.parent().addClass( 'active' );
                    e_radio.trigger( 'change' );
                }
                
                return;
            }
        }
    });
    
}

// Creates an HTML Element for the Table Row (tr) for the data that have been receveived from the API
function createRowForDataTable( value ){
    var html = '<tr data-functionality-id="'+value.functionality_id+'">\n';
    html += '<td><div class="form-check form-check-solid form-check-sm">\n' +
                    '<input class="form-check-input table-children-checkbox" type="checkbox" value="" />\n' +                                         
                '</div>\n';
            '</td>\n';
    html += '<td>' + value.alias + '</td>\n';
    html += '<td>' + value.functionality_name + '</td>\n';
    
    var type = value.functionality_type;
    if( type == "" ){
        type = "UNDEFINED";
    }
    else{
        type = type.toUpperCase();        
    }
    type = type.trim();
    var type_class = "";
    
    switch( type ){
        case "CREATE":
            type_class = 'badge-primary';
            break;
            
        case "READ":
            type = '&nbsp;&nbsp;&nbsp;' + type + '&nbsp;&nbsp;&nbsp;';
            type_class = 'badge-success';
            break;
            
        case "UPDATE":
            type_class = 'badge-warning';
            break;
            
        case "DELETE":
            type_class = 'badge-info';
            break;
            
        case "ACCESS":
            type_class = 'badge-success';
            break;
            
        default:
            type_class = 'badge-danger';
    }
    
    type = '<span class="badge rounded '+type_class+'">'+type+'</span>';
    
    var is_a_page       = (value.is_page=="1")?'<span class="text-success">YES</span>':'<span class="text-danger">NO</span>';
    var is_a_content    = (value.is_a_content=="1")?'<span class="text-success">YES</span>':'<span class="text-danger">NO</span>';
    
    
    html += '<td>' + type + '</td>\n';
    html += '<td>' + is_a_page + '</td>\n';
    html += '<td>' + is_a_content + '</td>\n';
    html += '<td><a href="#" class="" title="Edit Functionality" onclick="editFunctionality(this,event);"><i class="fa-regular fs-2x text-primary fa-pen-to-square"></i></a> \n\
                 <a href="#" class="ms-3" title="Delete Functionality" onclick="deleteFunctionality(this,event);"><i class="bi bi-trash fs-2x text-danger"></i></a></td>\n';
    html += '</tr>\n';
    
    return html;
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
            //console.log( returned_data );
            
            callback( returned_data );
        }
    });
}

// Default select all plugins to list all plugin functionalities
function defaultSelectAllPluginsFromDropdown(){
    var e_select_plugin     = $( '#select_plugin' );
    e_select_plugin.val( '-1' );
    e_select_plugin.trigger( 'change' );
    e_select_plugin.trigger( 'change.select2' );
}

// Setting EventListener for change event on the Select Plugin dropdown, so that when its value is selected, it will deliver a call to API to get pages for that plugin
function selectPluginToViewItsFunctionalities(){
    var e_select_plugin     = $( '#select_plugin' );
    var e_table             = $( _table );
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

            $.ajax({
                url: getWebservice(),
                type: 'POST',
                data: data,
                success: function( returned_data ){
                    console.log( returned_data );

                    hideDataTableLoading();

                    var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                    if( jSon == false ){                    
                        return;
                    }

                    jSon = jSon[ 0 ];

                    if( jSon[ 'type' ] == 'error' ){
                        showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                        e_select_plugin.val( '-1' );
                        e_select_plugin.trigger( 'change' );
                        return;
                    }
                    if( jSon[ 'type' ] == 'success' ){

                        var data = jSon[ 'info' ];

                        html = "";
                        $.each( data.data, function( i, v ){
                            html += createRowForDataTable( v );
                        });

                        DataTable.destroy();
                        e_table.find( 'tbody' ).html( html );
                        initDataTable();
                        
                        //defaultSelectAllPluginsFromDropdown();

                        //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
                        //    redirect( data.login_url ); 
                        //});

                        return;
                    }
                }
            });
        }, data );
        
        
        
    });
}

// Creates an HTML Element for the Select Tag's options for each value that is supplied to it
function createPluginInformationOptionElementForSelectTag( value ){
    return '<option value="'+ value.plugin_id +'">'+ value.plugin_alias +'</option>\n';    
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
            console.log( returned_data );
            
            callback( returned_data );
        }
    });
}

// Retrieve all the plugins from API into the dropdown, so that we can select a plugin to view the functionalities that it holds, in the DataTable
function loadPluginsIntoDropdown(){
    var e_select_plugin = $( '#select_plugin' );
    
    get_all_plugin_information(function( returned_data ){
        
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
            html += '<option value="-1">All Plugins</option>\n';
            $.each( data.data, function( i, v ){
                html += createPluginInformationOptionElementForSelectTag( v );
            });

            e_select_plugin.html( html );
            
            // Default select all plugins to list all plugin functionalities
            defaultSelectAllPluginsFromDropdown();

            //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
            //    redirect( data.login_url ); 
            //});

            return;
        }
    });
}

// This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
function multipleRowsSelection(){
    var e_table                     = $( _table );
    var e_table_parent_checkbox     = e_table.find( '.table-parent-checkbox' );
    var e_group_actions             = $( '.group_actions[data-selected-table-name="'+_table_name+'"]' );
    var e_table_toolbar             = $( 'div[data-selected-table-toolbar="'+_table_name+'"]' );
    var e_selected_row_count        = e_group_actions.find( '.selected_row_count' );
    
    
    e_table_parent_checkbox.on( 'change', function( e ){
        // console.log( 'parent-checked' );
        // console.log( e.target.checked );
        var checked_status = e.target.checked?"checked":'';
        var e_table_children_checkboxes = e_table.find( '.table-children-checkbox' );
        if( e_table_children_checkboxes.length == 0 ){
            $( e.target ).prop( 'checked', null );
            showSimpleToast( "error", "There are no functionalities to select" );
            return;
        }
        var count = 0;
        if( e.target.checked ){
            show( e_group_actions );
            hide( e_table_toolbar );
        }
        else{
            count = e_table_children_checkboxes.length;
            show( e_table_toolbar );
            hide( e_group_actions );
        }
        
        
        $.each( e_table_children_checkboxes, function( i, v ){
            $( v ).prop( 'checked', checked_status );
            
            if( e.target.checked )
                count++;
            else
                count--;
            
        });
        e_selected_row_count.text( count );
    });    
    
    $( _table ).on( 'change', '.table-children-checkbox', function( e ){
        //console.log( e ); 
        var checked_status = e.target.checked?"checked":'';
        var count = (e_selected_row_count.text()=="")?0:parseInt(e_selected_row_count.text());
        var totalRowsOnPage = e_table.find( '.table-children-checkbox' ).length;
        
        //console.log( count );
        if( e.target.checked ){
            count++;
            if( count > 0 ){
                show( e_group_actions );
                hide( e_table_toolbar );
            }
            
        }
        else{
            count--;
            if( count == 0 ){
                show( e_table_toolbar );
                hide( e_group_actions );
                e_table_parent_checkbox.prop( 'checked', '' );
            }
        }
        if( count == totalRowsOnPage ){
            e_table_parent_checkbox.prop( 'checked', 'checked' );
        }
        else{
            e_table_parent_checkbox.prop( 'checked', '' );
        }
        e_selected_row_count.text( count );
    });
    
}

// This is to maintain the multi-select state when the DataTables are navigated while some of the rows are checked and multi-selected
function updateMultipleRowsSelection(){
    var e_table                     = $( _table );
    var e_table_parent_checkbox     = e_table.find( '.table-parent-checkbox' );
    var e_group_actions             = $( '.group_actions[data-selected-table-name="'+_table_name+'"]' );
    var e_table_toolbar             = $( 'div[data-selected-table-toolbar="'+_table_name+'"]' );
    var e_selected_row_count        = e_group_actions.find( '.selected_row_count' );
    var e_table_children_checkbox   = e_table.find( '.table-children-checkbox' );
    
    var count = 0;
    var totalRowsOnPage = e_table_children_checkbox.length;
    console.log( totalRowsOnPage );
    
    $.each( e_table_children_checkbox, function( i, v ){
        var checked = $( v ).prop( 'checked' );
        //totalRowsOnPage++;
        
        if( checked ){
            count++;    
        }/*
        else{
            count--;
        }
        */
    });
    
    if( count > 0 ){
        show( e_group_actions );
        hide( e_table_toolbar );
    }
    if( count == totalRowsOnPage ){
        e_table_parent_checkbox.prop( 'checked', 'checked' );
    }
    if( count == 0 ){
        show( e_table_toolbar );
        hide( e_group_actions );
        e_table_parent_checkbox.prop( 'checked', '' );
    }
    e_selected_row_count.text( count );
    
}

// All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
function initDataTable(){
    
    DataTableParameters = {
        searching: true,
        language: {
            emptyTable: 'Select a plugin from the above dropdown to view the functionalities'
        },        
        columnDefs: [
            {// set default column settings
                orderable: false,
                targets: [0, 6] // column indexes separated by a comma to which the above attribute should be applied
            },
            {
                searchable: false,
                targets: [0, 6]
            },
            {
                //className: "dt-right",
                //"targets": [2]
            }
        ],
        order: [
            [1, "asc"]
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
    
    // This is used for the purpose of Multi-selecting table rows to show the count on top right corner of the card-toolbar
    DataTable.on( 'draw', function(){
        updateMultipleRowsSelection();
    });
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