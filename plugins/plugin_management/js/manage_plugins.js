var _table_name = "table_plugins";   // The name of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var _table = "#" + _table_name;   // The id with # of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var DataTable = null;
var DataTableParameters = null;
const loadingEl = document.createElement("div");    // Loading Div for DataTable entries loading

$( document ).ready(function(){
    // All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
    initDataTable();
    
    loadPluginsIntoDataTable();
    
    initCreatePluginModal();
    
    initEditPluginModal();
    
    initImportPluginModal();
    
    create_plugin();
    
    update_plugin();
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

function initImportPluginModal(){
    var modal                       = $( '#modal_import_plugin' );
    var form_id                     = '#form_import_plugin';
    var form                        = $( form_id );
    var e_file_plugin               = $( '#file_plugin_import' );
    var e_scodezy_selected_files    = form.find( '.scodezy-selected-files' );
    var btn_upload                  = $( '#btn_import_plugin' );
    var btn_open_modal              = $( '#btn_open_import_plugin_modal' );
    var btn_cancel                  = $( '#btn_reset_import_plugin' );
    var btn_select_file             = $( '#btn_select_plugin_file_import' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    btn_upload.on( 'click', function(){
        // Check if the file is selected
        var files = e_file_plugin[ 0 ].files;
        if( files.length == 0 ){
            showSimpleToast( "error", "Please select a plugin zip file to import" );  
            return;
        }
        
        // Check if its a zip file format
        var selectedFile = files[ 0 ];
        if( selectedFile.type != "application/x-zip-compressed" ){
            showSimpleToast( "error", "Selected file is not a valid plugin zip file" );  
            return;
        }
        
        // Post to Ajax webservice
        showLoadingSweetAlert( "Exporting", "Please wait while the plugin contents are being zipped" );
        
        var formData = new FormData();
        formData.append( "what_do_you_want", "scodezy_import_plugin" );
        formData.append( "plugin_file", selectedFile );
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            processData: false,
            contentType: false,
            data: formData,
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

                    var info = jSon[ 'info' ];

                    //var data = info.data;
                    //window.location = data;
                    
                    btn_cancel.trigger( 'click' );
                    modal.modal( 'hide' );
                    
                    showActionSweetAlert( info, "success", false, "Okay", "btn btn-info", function(){
                        setTimeout( function(){
                            refreshPage();
                        }, 500 ); 
                    } );
                    
                    
                    
                    //showSimpleToast( "success", data.info );
                    
                    return;
                }



            }
        });
    });
    
    e_file_plugin.on( 'change', function( e ){
        ////console.log( e.target.files[ 0 ] );
        var file = e.target.files[ 0 ];
        var html = create_file_item( file );
        
        e_scodezy_selected_files.html( html );
    });
    
    btn_select_file.on( 'click', function(){
        e_file_plugin.trigger( 'click' );
    });
    
    
    btn_cancel.on( 'click', function(){
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );    
        
        e_file_plugin.val( null );
        
        e_scodezy_selected_files.html( '' );
    });
    
    btn_open_modal.on( 'click', function(){
        btn_cancel.trigger( 'click' );
        
        modal.modal( 'show' );
    });
}

function exportPlugin( thees, event ){
    event.preventDefault();
    
    var tr = $( thees ).parent().parent( 'tr' );
    var val_plugin_id = tr.data( 'plugin-id' );
    ////console.log( val_page_id );
    var val_plugin_alias = tr.find( 'td:nth-child(1)' ).text();
    
    // Show a confirmation SweetActionAlert to confirm the export operation
    showConfirmSweetAlert( "Confirm", "Are you sure you want to export the plugin '" + val_plugin_alias + "'" , "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
        ////console.log( 'yes clicked' );
        
        showLoadingSweetAlert( "Exporting", "Please wait while the plugin contents are being zipped" );
        
        var data = {
            what_do_you_want: "scodezy_export_plugin",
            plugin_id: val_plugin_id
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

                    var info = jSon[ 'info' ];

                    var data = info.data;
                    window.location = data;
                    
                    showActionSweetAlert( info.info, "success", false, "Okay", "btn btn-info", function(){} );
                    
                    //showSimpleToast( "success", data.info );
                    
                    return;
                }



            }
        });
    });
}

function update_plugin(){
    var modal               = getElementByID( 'modal_edit_plugin' );
    //var form        = getElementByID( 'form_create_role' );
    var e_plugin_id         = getElementByID( 'hidden_plugin_id_e' );
    var e_plugin_name       = getElementByID( 'input_plugin_name_e' );
    var e_plugin_alias      = getElementByID( 'input_plugin_alias_e' );
    var e_plugin_version    = getElementByID( 'input_plugin_version_e' );
    //var e_select_plugin = getElementByID( 'select_plugin_c' );
    var btn_submit          = getElementByID( 'btn_update_plugin' );
    var btn_cancel          = getElementByID( 'btn_cancel_edit_plugin_modal' );
    var e_table             = getElementByID( _table_name );
    
    btn_submit.on( 'click', function(){
        
        var parsley_plugin_name          = e_plugin_name.parsley();
        var parsley_plugin_alias         = e_plugin_alias.parsley();
        var parsley_plugin_version       = e_plugin_version.parsley();
        
        /*
        //console.log( "Visible : " + val_page_is_visible );
        //console.log( "Plugin : " + val_plugin );
        //console.log( "Functionality : " + val_functionality );
        //console.log( "Hierarchy : " + val_hierarchy );
        //console.log( "Parent : " + val_parent_page );
        */
        
        if( !parsley_plugin_name.isValid() ){
            showSimpleToast( "error", "Please check the Plugin Name and try again !" );
            return;
        }
        if( !parsley_plugin_alias.isValid() ){
            showSimpleToast( "error", "Please check the Plugin Alias and try again !" );
            return;
        }
        if( !parsley_plugin_version.isValid() ){
            showSimpleToast( "error", "Please check the Plugin Version and try again !" );
            return;
        }
        
        var val_plugin_id             = e_plugin_id.val();
        var val_plugin_name           = e_plugin_name.val();
        var val_plugin_alias          = e_plugin_alias.val();
        var val_plugin_version        = e_plugin_version.val();
        
        // Show Loading Animation on the Button
        btn_submit.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit );

        var data = {
            what_do_you_want: "scodezy_update_plugin",
            plugin_id: val_plugin_id,
            plugin_name: val_plugin_name,
            plugin_alias: val_plugin_alias,
            plugin_version: val_plugin_version
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
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
                    var trToBeRemoved = e_table.find( 'tr[data-plugin-id="'+val_plugin_id+'"]' );
                    DataTable.row( trToBeRemoved ).remove().draw();
                    
                    // Add this entry into the DataTable 
                    var value = data.data;
                    var tr = createRowForDataTable( value );
                    DataTable.row.add( $( tr ) ).draw();
                    
                    showActionSweetAlert( data.info, "success", true, "Thank you", "btn btn-primary", function(){} );
                    
                    // Close the modal 
                    modal.modal( 'hide' );
                    setTimeout( function(){
                        // Reset the form
                        btn_cancel.trigger( 'click' );
                    }, 1000 );
                    
                    return;
                }
            }
        });
        
    });
    
}

function initEditPluginModal(){
    var modal                       = $( '#modal_edit_plugin' );
    var form_id                     = '#form_edit_plugin';
    var form                        = $( form_id );
    var btn_cancel                  = $( '#btn_cancel_edit_plugin_modal' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    btn_cancel.on( 'click', function(){
        modal.modal( 'hide' );
        
        setTimeout(function(){
            scodezyForm.getParsleyForm().reset();
            scodezyForm.getForm().trigger( "reset" );
        }, 1500 );
        
    });
}

// This function is inside the onclick attribute of the Edit Button in the column of each row of the DataTable
function editPlugin( thees, e ){
    e.preventDefault();
    ////console.log( thees );
    var val_plugin_id = $( thees ).parent().parent( 'tr' ).data( 'plugin-id' );
    ////console.log( val_functionality_id );
    
    var modal                       = $( '#modal_edit_plugin' );
    var form_id                     = '#form_edit_plugin';
    var form                        = $( form_id );
    var btn_cancel                  = $( '#btn_cancel_edit_plugin_modal' );
    var btn_submit                  = $( '#btn_update_plugin' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    var e_hidden_plugin_id_e     = getElementByID( 'hidden_plugin_id_e' );
    var e_plugin_name            = getElementByID( 'input_plugin_name_e' );
    var e_plugin_alias           = getElementByID( 'input_plugin_alias_e' );
    var e_plugin_version         = getElementByID( 'input_plugin_version_e' );
    
    
    // Clear the Modal Form and reset all values
    scodezyForm.getParsleyForm().reset();
    scodezyForm.getForm().trigger( "reset" );
    
    // Show Loading Dialog while loading the plugin data from the API
    showLoadingSweetAlert( "Loading", "Please wait while the Plugin Information is being retrieved" );
    
    var data = {
        what_do_you_want: 'scodezy_get_plugin',
        plugin_id: val_plugin_id
    };
    
    // get single plugin information from Webservice and display it in Edit Modal
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
                
                e_hidden_plugin_id_e.val( data.plugin_id );
                e_plugin_name.val( data.plugin_name );
                e_plugin_alias.val( data.plugin_alias );
                e_plugin_version.val( data.version );
                
                
                return;
            }
        }
    });
    
}

function deletePlugin( thees, event ){
    event.preventDefault();
    
    var tr = $( thees ).parent().parent( 'tr' );
    var val_plugin_id = tr.data( 'plugin-id' );
    ////console.log( val_page_id );
    var val_plugin_alias = tr.find( 'td:nth-child(1)' ).text();
    
    // Show a confirmation SweetActionAlert to confirm the delete operation
    showConfirmSweetAlert( "Confirm", "Are you sure you want to delete the plugin '" + val_plugin_alias + "'" , "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
        ////console.log( 'yes clicked' );
        
        showLoadingSweetAlert( "Deleting", "Please wait while the plugin is being deleted" );
        
        var data = {
            what_do_you_want: "scodezy_delete_plugin",
            plugin_id: val_plugin_id
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
                    
                    // Delete this entry from the DataTable
                    DataTable.row( tr ).remove().draw();
                    
                    showSimpleToast( "success", data.info );
                    
                    return;
                }



            }
        });
    });
}

function create_plugin(){
    var modal               = getElementByID( 'modal_create_plugin' );
    //var form        = getElementByID( 'form_create_role' );
    var e_plugin_name       = getElementByID( 'input_plugin_name_c' );
    var e_plugin_alias      = getElementByID( 'input_plugin_alias_c' );
    var e_plugin_version    = getElementByID( 'input_plugin_version_c' );
    //var e_select_plugin = getElementByID( 'select_plugin_c' );
    var btn_submit          = getElementByID( 'btn_create_plugin' );
    var btn_reset           = getElementByID( 'btn_reset_create_plugin_modal' );
    
    btn_submit.on( 'click', function(){
        
        var parsley_plugin_name          = e_plugin_name.parsley();
        var parsley_plugin_alias          = e_plugin_alias.parsley();
        var parsley_plugin_version          = e_plugin_version.parsley();
        
        /*
        //console.log( "Visible : " + val_page_is_visible );
        //console.log( "Plugin : " + val_plugin );
        //console.log( "Functionality : " + val_functionality );
        //console.log( "Hierarchy : " + val_hierarchy );
        //console.log( "Parent : " + val_parent_page );
        */
        
        if( !parsley_plugin_name.isValid() ){
            showSimpleToast( "error", "Please check the Plugin Name and try again !" );
            return;
        }
        if( !parsley_plugin_alias.isValid() ){
            showSimpleToast( "error", "Please check the Plugin Alias and try again !" );
            return;
        }
        if( !parsley_plugin_version.isValid() ){
            showSimpleToast( "error", "Please check the Plugin Version and try again !" );
            return;
        }
        
        var val_plugin_name           = e_plugin_name.val();
        var val_plugin_alias           = e_plugin_alias.val();
        var val_plugin_version           = e_plugin_version.val();
        
        // Show Loading Animation on the Button
        btn_submit.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit );

        var data = {
            what_do_you_want: "scodezy_create_plugin",
            plugin_name: val_plugin_name,
            plugin_alias: val_plugin_alias,
            plugin_version: val_plugin_version
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
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
                    btn_reset.trigger( 'click' );
                    
                    return;
                }
            }
        });
        
    });
    
    
}

function initCreatePluginModal(){
    var modal                       = getElementByID( 'modal_create_plugin' );
    var btn_open_modal              = getElementByID( 'btn_open_create_plugin_modal' );
    var btn_reset                   = getElementByID( 'btn_reset_create_plugin_modal' );
    //var form                        = getElementByID( 'form_create_plugin' );
    
    //var e_form              = getElementByID( 'form_create_role' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_create_plugin' );
    
    btn_open_modal.on( 'click', function(){
        btn_reset.trigger( 'click' );
        modal.modal( 'show' );
    });
        
    btn_reset.on( 'click', function(){
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
    });
}

function loadPluginsIntoDataTable (){
    var e_table = getElementByID( _table_name );
    
    var data = {
        what_do_you_want: 'scodezy_get_all_plugin_information'
    };
    
    showDataTableLoading();
    
    get_plugins(function( returned_data ){
        
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

// Creates an HTML Element for the Table Row (tr) for the data that have been receveived from the API
function createRowForDataTable( value ){
    var html = '<tr data-plugin-id="'+value.plugin_id+'">\n';
    html += '<td>' + value.plugin_alias + '</td>\n';
    html += '<td>' + value.plugin_name + '</td>\n';
    html += '<td>' + ((value.version=="")?'NA':value.version) + '</td>\n';
    
    html += '<td><a href="#" class="" title="Edit Plugin" onclick="editPlugin(this,event);"><i class="fa-regular fs-2x text-primary fa-pen-to-square"></i></a> \n' +
                 '<a href="#" class="ms-3 me-3" title="Delete Plugin" onclick="deletePlugin(this,event);"><i class="bi bi-trash fs-2x text-danger"></i></a> \n' +
                 '<a href="#" class="" title="Export Plugin" onclick="exportPlugin(this,event);"><i class="fa-solid fs-2x text-info fa-file-export"></i></a></td> \n';
    html += '</tr>\n';
    
    return html;
}

// Make an Ajax call to get all the roles from API, and passing a callback function to be executed when the result of AJAX is received
function get_plugins( callback, url_parameters = "" ){
    
    var data = "";
    if( url_parameters == "" ){
        data = {
            what_do_you_want: 'scodezy_get_all_plugin_information'
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
            emptyTable: 'Plugins have not been created yet'
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
                "targets": [0,1,2,3]
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