var table_pages = "#table_pages";   // The id with # of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var DataTablePages = null;
var DataTablePagesParameters = null;
const loadingEl = document.createElement("div");
var select_parent_page_original_data = "";

$( document ).ready( function(){
    
    // All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
    initPagesDataTable();
    
    // Retrieve all the plugins from API into the dropdown, so that we can select a plugin to view the pages that it holds, in the DataTable
    loadPluginsIntoDropdown();
    
    // Setting EventListener for change event on the Select Plugin dropdown, so that when its value is selected, it will deliver a call to API to get pages for that plugin
    selectPluginToViewItsPages();
    
    // This is the Event Listener attached to the Toggle button for visibility changing of the pages on each row of the DataTable
    update_page_visibile_status();
    
    // This is the Event Listener attached to the Open create page Modal button, Submit button on create page modal and cancel button on create page modal
    create_new_page();
    // Load the list of plugins from API into the Select Plugin dropdown on Create page modal and Edit page modal based on the function input parameter c or e
    refresh_plugins_in_dropdown( 'c' );     // c is For create Modal
    refresh_plugins_in_dropdown( 'e' );     // e is For edit Modal
    // Load the list of plugins from API into the Select Plugin dropdown on Create page modal and Edit page modal based on the function input parameter c or e
    refresh_functionalities_in_dropdown( 'c' );
    refresh_functionalities_in_dropdown( 'e' );
    // Load all parent page ids into the Select Parent page dropdown for Create Page modal and Edit Page modal
    refresh_parent_pages_in_dropdown( 'c' );
    refresh_parent_pages_in_dropdown( 'e' );
    // Setting EventListeners for Radio Button for hierarchy selection, to appropriately display only those pages that are of higher hierarchy than the current hierarchy selected
    initPageHierarchySelection( 'c' );
    initPageHierarchySelection( 'e' );
    
    // This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
    multipleRowsSelection();    
    
    // Event listener for the Delete Selected button to delete multiple rows from the DataTable at once
    deleteSelectedRows();
    
    // This is the Event Listener attached to the Submit button and Cancel button of Edit Page modal, to update the page information
    update_page();
    
    // EventListener for the Page Sequencing button to open the Page Sequencing Modal
    initPageSequencingModal();
    
    // Load all parent page ids into the Select Parent page dropdown for Page Sequencing modal
    refresh_parent_pages_in_page_sequencing_dropdown( 's', { what_do_you_want: 'scodezy_get_parent_pages' } );
    
    // EventListener on the Select Dropdown on the Page Sequencing modal, to retrieve the child pages for the selected parent page.
    // This dropdown is also capable of retrieving only the Top Hierarchy parent pages using the first option from the dropdown
    selectParentPageToRetrieveItsChildren();
    
    // Event listener for the Export Selected button to export selected pages at once
    exportSelectedRows();
    
    initImportPagesModal();
    
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

function initImportPagesModal(){
    var modal                       = $( '#modal_import_pages' );
    var form_id                     = '#form_import_pages';
    var form                        = $( form_id );
    var e_file_pages                = $( '#file_pages_import' );
    var e_scodezy_selected_files    = form.find( '.scodezy-selected-files' );
    var btn_upload                  = $( '#btn_import_pages' );
    var btn_open_modal              = $( '#btn_open_import_pages_modal' );
    var btn_cancel                  = $( '#btn_reset_import_pages' );
    var btn_select_file             = $( '#btn_select_pages_file_import' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( form_id );
    
    btn_upload.on( 'click', function(){
        // Check if the file is selected
        var files = e_file_pages[ 0 ].files;
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
        showLoadingSweetAlert( "Importing", "Please wait while the pages are being imported" );
        
        var formData = new FormData();
        formData.append( "what_do_you_want", "scodezy_import_pages" );
        formData.append( "pages_file", selectedFile );
        
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
    
    e_file_pages.on( 'change', function( e ){
        ////console.log( e.target.files[ 0 ] );
        var file = e.target.files[ 0 ];
        var html = create_file_item( file );
        
        e_scodezy_selected_files.html( html );
    });
    
    btn_select_file.on( 'click', function(){
        e_file_pages.trigger( 'click' );
    });
    
    
    btn_cancel.on( 'click', function(){
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );    
        
        e_file_pages.val( null );
        
        e_scodezy_selected_files.html( '' );
    });
    
    btn_open_modal.on( 'click', function(){
        btn_cancel.trigger( 'click' );
        
        modal.modal( 'show' );
    });
}

// Event listener for the Export Selected button to export selected pages at once
function exportSelectedRows(){
    var e_table                     = $( '#table_pages' );
    
    $( '.group_actions' ).on( 'click', '.export_selected_rows', function(){
        
        // Show a confirmation SweetActionAlert to confirm the delete operation
        showConfirmSweetAlert( "Confirm", "Are you sure you want to export the selected pages", "question", "Yes", "btn btn-primary", "Cancel", "btn btn-default", function(){
            ////console.log( 'yes clicked' );

            showLoadingSweetAlert( "Exporting", "Please wait while the pages data is being export" );
            
            var e_table_children_checkbox   = e_table.find( '.table-children-checkbox' );
            var val_pages = [];
            $.each( e_table_children_checkbox, function( i, v ){
                var checked = $( v ).prop( 'checked' );
                if( checked )
                    val_pages.push( $( v ).parent().parent().parent().attr( 'data-page-id' ) );
            });
            ////console.log( val_pages );
            
            var formData = new FormData();
            formData.append( 'what_do_you_want', 'scodezy_export_pages' );
            formData.append( 'page_ids', val_pages );

            $.ajax({
                url: getWebservice(),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function( returned_data ){
                    //console.log( returned_data );

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

// Send a request to the API to get child pages 
function get_child_pages( callback, url_parameters = "" ){
    var data = "";
    if( url_parameters == "" ){
        data = {
            what_do_you_want: 'scodezy_get_child_pages'
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
            callback( returned_data );
        }
    });
}

// Update the sequence of the pages after the drag and drop is performed to re arrange the pages inside the KanBan box
function update_page_sequence( kanban_element_class_or_id, parent_page_id ){
    var e_kanban_parent = $( kanban_element_class_or_id );
    var e_kanban_items  = e_kanban_parent.find( '.kanban-item' );
    
    var page_ids = [];
    $.each( e_kanban_items, function( i, v ){
        page_ids.push( $( v ).data( 'page-id' ) );
    });
    
    var formData = new FormData();
    formData.append( 'what_do_you_want', 'scodezy_update_page_sequence' );
    formData.append( 'page_ids', page_ids );
    formData.append( 'parent_page_id', parent_page_id );
    
    
    ////console.log( data );
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function( returned_data ){
            //console.log( returned_data );
            
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){                    
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                //showSimpleToast( "error", jSon[ 'info' ] );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var data  = jSon[ 'info' ];
                //var info  = data.info;
                //var pages = data.data;
                
                showSimpleToast( "success", data );
                
                return;
            }
            
        }
    });
}

function create_child_kanban_1( id_or_class, board_id, board_title, items, callback ){
    var kanban = new jKanban({
        element: id_or_class,
        gutter: '0px',
        dragItems: true,
        dragBoards: false,
        boards: [
            {
                "id"    : board_id,               // id of the board
                "title" : board_title,              // title of the board
                "class" : "primary,rounded-top",        // css classes to add at the title
                //"dragTo": ['another-board-id'],   // array of ids of boards where items can be dropped (default: [])
                "item"  : items
            }
        ],
        dropEl: function ( el, target, source, sibling ) {
            //console.log( el );
            
            callback( id_or_class );
            
        }
    });
    $( id_or_class ).find( '.kanban-board' ).addClass( 'border' );
}

// EventListener on the Select Dropdown on the Page Sequencing modal, to retrieve the child pages for the selected parent page.
// This dropdown is also capable of retrieving only the Top Hierarchy parent pages using the first option from the dropdown
function selectParentPageToRetrieveItsChildren(){
    var e_select_parent_page    = $( '#select_parent_page_s' );
    var e_child_kanban_1        = $( '.child-kanban-1' );
    
    e_select_parent_page.on( 'change', function(){
        val_parent_page_id      = e_select_parent_page.val();
        val_parent_page_title   = $( '#select_parent_page_s' ).find( 'option[value="'+val_parent_page_id+'"]' ).text();
        ////console.log( val_parent_page_id );
        ////console.log( val_parent_page_title );
        
        var url_parameters = {
            what_do_you_want: 'scodezy_get_child_pages',
            parent_id: val_parent_page_id
        };
        
        showLoadingSweetAlert( 'Loading', 'Please wait while the child pages are being retrieved' );
        
        get_child_pages(function( returned_data ){
            //console.log( returned_data );
            
            hideLoadingSweetAlert();
            
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){                    
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                showSimpleToast( "error", jSon[ 'info' ] );
                // Hide the KanBan Div
                hide( e_child_kanban_1 );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var data  = jSon[ 'info' ];
                var info  = data.info;
                var pages = data.data;
                
                var items = [];
                $.each( pages, function( i, v ){
                    var item = {
                        "id"    : v.page_id,        // id of the item
                        "title" : v.page_title,            // title of the item
                        "page-id": v.page_id,
                        "page-name": v.page_name,
                        "page-sequence": v.page_sequence,
                    };
                    items.push( item );
                });
                
                e_child_kanban_1.text( '' );
                create_child_kanban_1( '.child-kanban-1', 'child-kanban-1', val_parent_page_title, items, function(){
                    update_page_sequence( '.child-kanban-1', val_parent_page_id );
                });
                
                show( e_child_kanban_1 );
                
                return;
            }
           
        }, url_parameters );
        
    });
}

// Only a test function to test the KanBan box
function test_kanban(){
    var kanban = new jKanban({
        element: '.child-kanban-1',
        gutter: '0px',
        dragItems: true,
        class: 'border',
        boards:[
                {
                    "id"    : "board-id-1",               // id of the board
                    "title" : "Board Title",              // title of the board
                    "class" : "primary,rounded-top",        // css classes to add at the title
                    "dragTo": ['another-board-id'],   // array of ids of boards where items can be dropped (default: [])
                    "item"  : [                           // item of this board
                        {
                            "id"    : "{page_id}",        // id of the item
                            "title" : "{page_title}",            // title of the item
                            "page-id": "1",
                            "page-name": "page-name"
                        }
                    ]
                }
            ]
    });
    $( '.child-kanban-1' ).find( '.kanban-board' ).addClass( 'border' );
}

// Load all parent page ids into the Select Parent page dropdown for Page Sequencing modal and Edit Page modal
function refresh_parent_pages_in_page_sequencing_dropdown( c_or_e, url_parameters = "" ){
    var btn_refresh = $( '#btn_refresh_parent_pages_' + c_or_e );
    var e_child_kanban_1        = $( '.child-kanban-1' );
    
    btn_refresh.on( 'click', function(){
        
        btn_refresh.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_refresh );
        
        var e_select = $( '#select_parent_page_' + c_or_e );
    
        get_all_pages(function( returned_data ){

            btn_refresh.attr( 'data-kt-indicator', 'off' );
            enableFormElement( btn_refresh );

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
                
                hide( e_child_kanban_1 );
                
                var html = '<option></option>\n';
                html += '<option value="-1">Top Hierarchy Pages</option>\n';
                
                $.each( data.data, function( i, v ){
                    html += createParentPageOptionElementForSelectTag( v );
                });

                e_select.html( html );

                //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
                //    redirect( data.login_url ); 
                //});

                return;
            }
        }, url_parameters );
    });
    
   // btn_refresh.trigger( 'click' );

    
}

// EventListener for the Page Sequencing button to open the Page Sequencing Modal
function initPageSequencingModal(){
    var modal               = $( '#modal_page_sequencing' );
    var btn_open_modal      = $( '#btn_open_page_sequencing_modal' );
    
    btn_open_modal.on( 'click', function(){
        modal.modal( 'show' );
        $( '#btn_refresh_parent_pages_s' ).trigger( 'click' );
    });
}

// Event listener for the Delete Selected button to delete multiple rows from the DataTable at once
function deleteSelectedRows(){
    var e_table                     = $( '#table_pages' );
    
    $( '.group_actions' ).on( 'click', '.delete_selected_rows', function(){
        
        // Show a confirmation SweetActionAlert to confirm the delete operation
        showConfirmSweetAlert( "Confirm", "Are you sure you want to delete the selected pages", "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
            ////console.log( 'yes clicked' );

            showLoadingSweetAlert( "Deleting", "Please wait while the pages are being deleted" );
            
            var e_table_children_checkbox   = e_table.find( '.table-children-checkbox' );
            var val_pages = [];
            $.each( e_table_children_checkbox, function( i, v ){
                var checked = $( v ).prop( 'checked' );
                if( checked )
                    val_pages.push( $( v ).parent().parent().parent().attr( 'data-page-id' ) );
            });
            ////console.log( val_pages );
            
            var formData = new FormData();
            formData.append( 'what_do_you_want', 'scodezy_delete_pages' );
            formData.append( 'page_ids', val_pages );

            $.ajax({
                url: getWebservice(),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function( returned_data ){
                    //console.log( returned_data );

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

                        var deleted_pages = data.data;
                        
                        $.each( deleted_pages, function( i, v ){
                            DataTablePages.row( e_table.find( 'tr[data-page-id="'+v.page_id+'"]' ) ).remove().draw();
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

// This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
function multipleRowsSelection(){
    var e_table                     = $( '#table_pages' );
    var e_table_parent_checkbox     = e_table.find( '.table-parent-checkbox' );
    var e_group_actions             = $( '.group_actions[data-selected-table-name="table_pages"]' );
    var e_table_toolbar             = $( 'div[data-selected-table-toolbar="table_pages"]' );
    var e_selected_row_count        = e_group_actions.find( '.selected_row_count' );
    
    
    e_table_parent_checkbox.on( 'change', function( e ){
        // //console.log( 'parent-checked' );
        // //console.log( e.target.checked );
        var checked_status = e.target.checked?"checked":'';
        var e_table_children_checkboxes = e_table.find( '.table-children-checkbox' );
        if( e_table_children_checkboxes.length == 0 ){
            $( e.target ).prop( 'checked', null );
            showSimpleToast( "error", "There are no pages to select" );
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
    
    $( '#table_pages' ).on( 'change', '.table-children-checkbox', function( e ){
        ////console.log( e ); 
        var checked_status = e.target.checked?"checked":'';
        var count = (e_selected_row_count.text()=="")?0:parseInt(e_selected_row_count.text());
        var totalRowsOnPage = e_table.find( '.table-children-checkbox' ).length;
        
        ////console.log( count );
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
    var e_table                     = $( '#table_pages' );
    var e_table_parent_checkbox     = e_table.find( '.table-parent-checkbox' );
    var e_group_actions             = $( '.group_actions[data-selected-table-name="table_pages"]' );
    var e_table_toolbar             = $( 'div[data-selected-table-toolbar="table_pages"]' );
    var e_selected_row_count        = e_group_actions.find( '.selected_row_count' );
    var e_table_children_checkbox   = e_table.find( '.table-children-checkbox' );
    
    var count = 0;
    var totalRowsOnPage = e_table_children_checkbox.length;
    //console.log( totalRowsOnPage );
    
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

// Setting EventListeners for Radio Button for hierarchy selection, to appropriately display only those pages that are of higher hierarchy than the current hierarchy selected
function initPageHierarchySelection( c_or_e ){
    var e_hierarchy     = $( '.radio_hierarchy_' + c_or_e );
    var e_select        = $( '#select_parent_page_' + c_or_e );
    
    e_hierarchy.on( 'change',function( e ){
        // //console.log( e );
        var val_selected_hierarchy = $( e.target ).val();
        //console.log( val_selected_hierarchy );
        
        // If selected hierarchy is Top, then do not allow to select parent page
        var e_select = $( '.div_parent_page_selection_' + c_or_e );
        if( val_selected_hierarchy == 1 ){            
            hide( e_select );
        }
        else{
            show( e_select );
            
            var html = '<option></option>\n';
            $.each( select_parent_page_original_data, function( i, v ){
                html += createParentPageOptionElementForSelectTag( v, val_selected_hierarchy );
            });
            
            $( '#select_parent_page_' + c_or_e ).html( html );
            $( '#select_parent_page_' + c_or_e ).trigger( 'change.select2' );
        }
        
    });
}

// Create an HTML Element for Select Tag's option for Page ID selection
function createParentPageOptionElementForSelectTag( value, hierarchy = -1 ){
    var html = '';
    if( hierarchy >= 0 ){
        if( (hierarchy == 2) && (value.hierarchy == "1") )
            return '<option value="'+ value.page_id +'">'+ value.page_title +' - '+ value.page_name +'</option>\n';
        else if( (hierarchy == 3) && (value.hierarchy == "2") )
            return '<option value="'+ value.page_id +'">'+ value.page_title +' - '+ value.page_name +'</option>\n';
        else
            return '';
    }
    else{
        return '<option value="'+ value.page_id +'">'+ value.page_title +' - '+ value.page_name +'</option>\n';
    }
    
}

// Load all parent page ids into the Select Parent page dropdown for Create Page modal and Edit Page modal
function refresh_parent_pages_in_dropdown( c_or_e, url_parameters = "" ){
    var btn_refresh = $( '#btn_refresh_parent_pages_' + c_or_e );
    
    btn_refresh.on( 'click', function(){
        
        btn_refresh.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_refresh );
        
        var e_select = $( '#select_parent_page_' + c_or_e );
    
        get_all_pages(function( returned_data ){

            btn_refresh.attr( 'data-kt-indicator', 'off' );
            enableFormElement( btn_refresh );

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
                select_parent_page_original_data = data.data;
                var html = '<option></option>\n';
                //var html = '';
                //var vv = $( '.radio_hierarchy_c' ).val();
                var vv = $( 'input[name="radio_hierarchy_'+ c_or_e+'"]:checked' ).val();
                ////console.log( vv );
                if( vv == 'undefined'){
                    vv = -1;
                }
                $.each( data.data, function( i, v ){
                    html += createParentPageOptionElementForSelectTag( v, vv );
                });

                e_select.html( html );

                //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
                //    redirect( data.login_url ); 
                //});

                return;
            }
        }, url_parameters );
    });
    
   // btn_refresh.trigger( 'click' );

    
}

// API Call to get all the functionalities from the webservice
function get_all_functionalities( callback ){
    
    var data = {
        what_do_you_want: 'scodezy_get_page_functionalities'
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

// Create an HTML Element for the Select Tag's dropdown option element for Functionality Selection
function createFunctionalityOptionElementForSelectTag( value ){
    return '<option value="'+ value.functionality_id +'">'+ value.alias +' - '+ value.functionality_name +'</option>\n';    
}

// Load the list of functionalities from API into the Select Plugin dropdown on Create page modal and Edit page modal based on the function input parameter c or e
function refresh_functionalities_in_dropdown( c_or_e ){
    var btn_refresh = $( '#btn_refresh_functionalities_' + c_or_e );
    
    btn_refresh.on( 'click', function(){
        
        btn_refresh.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_refresh );
        
        var e_select = $( '#select_functionality_' + c_or_e );
    
        get_all_functionalities(function( returned_data ){

            btn_refresh.attr( 'data-kt-indicator', 'off' );
            enableFormElement( btn_refresh );

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
                //var html = '';
                $.each( data.data, function( i, v ){
                    html += createFunctionalityOptionElementForSelectTag( v );
                });

                e_select.html( html );

                //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
                //    redirect( data.login_url ); 
                //});

                return;
            }
        });
    });
    
    //btn_refresh.trigger( 'click' );

    
}

// Load the list of plugins from API into the Select Plugin dropdown on Create page modal and Edit page modal based on the function input parameter c or e
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

                //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
                //    redirect( data.login_url ); 
                //});

                return;
            }
        });
    });
    
    //btn_refresh_plugins_c.trigger( 'click' );

    
}

// This is the Event Listener attached to the Open create page Modal button, Submit button on create page modal and cancel button on create page modal
function create_new_page(){
    
    var modal           = $( '#modal_create_new_page' );
    var btn_modal       = $( '#btn_open_create_new_page_modal' );
    var form            = $( '#form_create_new_page' );
    var btn_cancel      = $( '#btn_cancel_create_new_page_modal' );
    var btn_submit      = $( '#btn_create_new_page' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_create_new_page' );
    
    
    // Show Loading Animation on the Button
    //btn_submit.attr( 'data-kt-indicator', 'on' );
    //disableFormElement( btn_submit );
    
    // Open the Modal
    btn_modal.on( 'click', function(){
        $( '#btn_refresh_plugins_c' ).trigger( 'click' );
        $( '#btn_refresh_functionalities_c' ).trigger( 'click' );
        $( '#btn_refresh_parent_pages_c' ).trigger( 'click' );
        
        $( '.div_parent_page_selection_c' ).addClass( 'hidden' );
        $( '.radio_hierarchy_c' ).parent().removeClass( 'active' );
        
        modal.modal( 'show' );        
        btn_cancel.trigger( 'click' );        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
    });
    
    // Reset the form and close the modal
    btn_cancel.on( 'click', function(){        
        modal.modal( 'hide' );        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );        
    });
    
    // Submit the data to the API
    btn_submit.on( 'click', function(){
        var form                   = getElementByID( 'form_create_new_page' );
        var e_page_name            = getElementByID( 'input_page_name_c' );
        var e_page_title           = getElementByID( 'input_page_title_c' );
        var e_page_icon            = getElementByID( 'input_page_icon_c' );
        var e_page_description     = getElementByID( 'ta_page_description_c' );
        var e_page_is_visible      = getElementByID( 'check_page_is_visible_c' );
        var e_select_plugin        = getElementByID( 'select_plugin_c' );
        var e_select_functionality = getElementByID( 'select_functionality_c' );
        var e_page_hierarchy       = getElement( 'input[name="radio_hierarchy_c"]' );
        var e_parent_page          = getElementByID( 'select_parent_page_c' );

        var parsley_page_name           = e_page_name.parsley();
        var parsley_page_title          = e_page_title.parsley();
        var parsley_page_icon           = e_page_icon.parsley();
        var parsley_page_description    = e_page_description.parsley();
        
        var val_page_is_visible     = e_page_is_visible.is( ':checked' )?"1":"0";
        var val_plugin              = e_select_plugin.val();
        var val_functionality       = e_select_functionality.val();
        var val_hierarchy           = $( 'input[name="radio_hierarchy_c"]:checked' ).val();
        var val_parent_page         = e_parent_page.val();
        
        //console.log( "Visible : " + val_page_is_visible );
        //console.log( "Plugin : " + val_plugin );
        //console.log( "Functionality : " + val_functionality );
        //console.log( "Hierarchy : " + val_hierarchy );
        //console.log( "Parent : " + val_parent_page );
        
        if( !parsley_page_name.isValid() ){
            showSimpleToast( "error", "Please check the Page Name and try again !" );
            return;
        }
        if( !parsley_page_title.isValid() ){
            showSimpleToast( "error", "Please check the Page Title and try again !" );
            return;
        }
        if( !parsley_page_icon.isValid() ){
            showSimpleToast( "error", "Please check the Page Icon and try again !" );
            return;
        }
        if( !parsley_page_description.isValid() ){
            showSimpleToast( "error", "Please check the Page Description and try again !" );
            return;
        }
        if( (val_plugin == "") || (typeof val_plugin == "undefined") ){
            showSimpleToast( "error", "Please select a plugin from the dropdown !" );
            return;
        }
        if( (val_functionality == "") || (typeof val_functionality == "undefined") ){
            showSimpleToast( "error", "Please select a functionality from the dropdown !" );
            return;
        }
        if( (val_hierarchy == "") || (typeof val_hierarchy == "undefined") ){
            showSimpleToast( "error", "Please select a page hierarchy !" );
            return;
        }        
        if( val_hierarchy == "1" ){
            val_parent_page = "-1";
            //val_parent_page = "0";
        }
        if( (val_parent_page == "") || (val_parent_page == "undefined") ){
            showSimpleToast( "error", "Please select a parent page !" );
            return;
        }
        
        var val_page_name           = e_page_name.val();
        var val_page_title          = e_page_title.val();
        var val_page_icon           = e_page_icon.val();
        var val_page_description    = e_page_description.val();
        
        // Show Loading Animation on the Button
        btn_submit.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit );

        var data = {
            what_do_you_want: "scodezy_create_page",
            page_name: val_page_name,
            page_title: val_page_title,
            page_icon: val_page_icon,
            page_description: val_page_description,
            is_visible: val_page_is_visible,
            plugin_id: val_plugin,
            functionality_id: val_functionality,
            hierarchy: val_hierarchy,
            parent_page_id: val_parent_page
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
                    //showSimpleToast( "error", jSon[ 'info' ] );                    
                    //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];

                    // Add this entry into the pages DataTable if it belongs to the currently selected plugin
                    var value = data.data;
                    if( $( '#select_plugin' ).val() == value.plugin_id ){
                        var tr = createPageRowForPagesDataTable( value );
                        DataTablePages.row.add( $( tr ) ).draw();
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

// This is the Event Listener attached to the Toggle button for visibility changing of the pages on each row of the DataTable
function update_page_visibile_status(){
    var e_table_pages                   = $( '#table_pages' );
    //var e_check_page_visible_status     = $( '.check_page_visible_status' );
    
    e_table_pages.on( 'click', '.check_page_visible_status', function( e ){
        
        var is_checked = (e.target.checked)?"1":"0";
        var val_page_id = $( e.target ).parent().parent().parent().data( 'page-id' );
                
        var data = {
            what_do_you_want: 'scodezy_update_page_visible_status',
            status: is_checked,
            page_id: val_page_id
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    showSimpleToast( "error", jSon[ 'info' ] );
                    
                    // Revert the switch back to its original state
                    setTimeout( function(){
                        $( e.target ).prop( 'checked', !e.target.checked ).trigger('change');                        
                    }, 1000 );
                    
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    // Do Nothing

                    return;
                }
            }
        });
        
    });
}

// This is the Event Listener attached to the Submit button and Cancel button of Edit Page modal, to update the page information
function update_page(){
    var modal                   = $( '#modal_edit_page' );
    var form                    = $( '#form_edit_page' );
    var e_table                 = $( '#table_pages' );
    var btn_cancel              = $( '#btn_cancel_edit_page_modal' );
    var btn_submit              = $( '#btn_update_page' );
    var e_hidden_page_id        = $( '#hidden_page_id_e' );
    var e_page_id               = getElementByID( 'hidden_page_id_e' );
    var e_page_name             = getElementByID( 'input_page_name_e' );
    var e_page_title            = getElementByID( 'input_page_title_e' );
    var e_page_icon             = getElementByID( 'input_page_icon_e' );
    var e_page_description      = getElementByID( 'ta_page_description_e' );
    var e_page_is_visible       = getElementByID( 'check_page_is_visible_e' );
    var e_select_plugin         = getElementByID( 'select_plugin_e' );
    var e_select_functionality  = getElementByID( 'select_functionality_e' );
    var e_page_hierarchy        = getElement( 'input[name="radio_hierarchy_e"]' );
    var e_parent_page           = getElementByID( 'select_parent_page_e' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_edit_page' );
    
    // Reset the form and close the modal
    btn_cancel.on( 'click', function(){        
        modal.modal( 'hide' );        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );        
    });
    
    // Submit the data to the API
    btn_submit.on( 'click', function(){
        var parsley_page_name           = e_page_name.parsley();
        var parsley_page_title          = e_page_title.parsley();
        var parsley_page_icon           = e_page_icon.parsley();
        var parsley_page_description    = e_page_description.parsley();
        
        var val_page_id             = e_page_id.val();
        var val_page_is_visible     = e_page_is_visible.is( ':checked' )?"1":"0";
        var val_plugin              = e_select_plugin.val();
        var val_functionality       = e_select_functionality.val();
        var val_hierarchy           = $( 'input[name="radio_hierarchy_e"]:checked' ).val();
        var val_parent_page         = e_parent_page.val();
        
        //console.log( "Visible : " + val_page_is_visible );
        //console.log( "Plugin : " + val_plugin );
        //console.log( "Functionality : " + val_functionality );
        //console.log( "Hierarchy : " + val_hierarchy );
        //console.log( "Parent : " + val_parent_page );
        
        if( !parsley_page_name.isValid() ){
            showSimpleToast( "error", "Please check the Page Name and try again !" );
            return;
        }
        if( !parsley_page_title.isValid() ){
            showSimpleToast( "error", "Please check the Page Title and try again !" );
            return;
        }
        if( !parsley_page_icon.isValid() ){
            showSimpleToast( "error", "Please check the Page Icon and try again !" );
            return;
        }
        if( !parsley_page_description.isValid() ){
            showSimpleToast( "error", "Please check the Page Description and try again !" );
            return;
        }
        if( (val_plugin == "") || (typeof val_plugin == "undefined") ){
            showSimpleToast( "error", "Please select a plugin from the dropdown !" );
            return;
        }
        if( (val_functionality == "") || (typeof val_functionality == "undefined") ){
            showSimpleToast( "error", "Please select a functionality from the dropdown !" );
            return;
        }
        if( (val_hierarchy == "") || (typeof val_hierarchy == "undefined") ){
            showSimpleToast( "error", "Please select a page hierarchy !" );
            return;
        }        
        if( val_hierarchy == "1" ){
            //val_parent_page = "-1";
            val_parent_page = "0";
        }
        if( (val_parent_page == "") || (val_parent_page == "undefined") ){
            showSimpleToast( "error", "Please select a parent page !" );
            return;
        }
        
        var val_page_name           = e_page_name.val();
        var val_page_title          = e_page_title.val();
        var val_page_icon           = e_page_icon.val();
        var val_page_description    = e_page_description.val();
        
        // Show Loading Animation on the Button
        btn_submit.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit );

        var data = {
            what_do_you_want: "scodezy_update_page",
            page_id: val_page_id,
            page_name: val_page_name,
            page_title: val_page_title,
            page_icon: val_page_icon,
            page_description: val_page_description,
            is_visible: val_page_is_visible,
            plugin_id: val_plugin,
            functionality_id: val_functionality,
            hierarchy: val_hierarchy,
            parent_page_id: val_parent_page
        };

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                ////console.log( returned_data );

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

                    // Update this entry onto the pages DataTable
                    var value = data.data;
                    
                    //var trToBeUpdated = e_table.find( 'tr[data-page-id="'+val_page_id+'"]' );
                    var tr = createPageRowForPagesDataTable( value ); 
                    var trToBeRemoved = e_table.find( 'tr[data-page-id="'+val_page_id+'"]' );
                    DataTablePages.row( trToBeRemoved ).remove().draw();
                    if( ($( '#select_plugin' ).val() == value.plugin_id) || ($( '#select_plugin' ).val() == "-1") ){               
                        DataTablePages.row.add( $( tr ) ).draw();
                    }
                    
                    showSimpleToast( "success", data.info );
                    //showActionSweetAlert( data.info, "success", "Thank you", "btn btn-primary", function(){} );
                    
                    modal.modal( 'hide' );
                    
                    setTimeout( function(){
                        // Close the modal and reset the form
                        btn_cancel.trigger( 'click' );
                    }, 1000 );
                    
                    return;
                }
            }
        });

    });
}

// This function is inside the onclick attribute of the Edit Button in the column of each row of the pages DataTable
function editPage( thees, e ){
    e.preventDefault();
    ////console.log( thees );
    var val_page_id = $( thees ).parent().parent( 'tr' ).data( 'page-id' );
    //console.log( val_page_id );
    
    var modal                   = $( '#modal_edit_page' );
    var form                    = $( '#form_edit_page' );
    var btn_cancel              = $( '#btn_cancel_edit_page_modal' );
    var btn_submit              = $( '#btn_update_page' );
    var e_hidden_page_id        = $( '#hidden_page_id_e' );
    var e_page_name             = getElementByID( 'input_page_name_e' );
    var e_page_title            = getElementByID( 'input_page_title_e' );
    var e_page_icon             = getElementByID( 'input_page_icon_e' );
    var e_page_description      = getElementByID( 'ta_page_description_e' );
    var e_page_is_visible       = getElementByID( 'check_page_is_visible_e' );
    var e_select_plugin         = getElementByID( 'select_plugin_e' );
    var e_select_functionality  = getElementByID( 'select_functionality_e' );
    var e_page_hierarchy        = getElement( 'input[name="radio_hierarchy_e"]' );
    var e_parent_page           = getElementByID( 'select_parent_page_e' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_edit_page' );
    
    // Clear the Modal Form and reset all values
    $( '#btn_refresh_plugins_e' ).trigger( 'click' );
    $( '#btn_refresh_functionalities_e' ).trigger( 'click' );
    $( '#btn_refresh_parent_pages_e' ).trigger( 'click' );

    $( '.div_parent_page_selection_e' ).addClass( 'hidden' );
    $( '.radio_hierarchy_e' ).parent().removeClass( 'active' );
        
    btn_cancel.trigger( 'click' );        
    scodezyForm.getParsleyForm().reset();
    scodezyForm.getForm().trigger( "reset" );
    
    // Show Loading Dialog while loading the page data from the API
    showLoadingSweetAlert( "Loading", "Please wait while the Page Information is being retrieved" );
    
    var data = {
        what_do_you_want: 'scodezy_get_page',
        page_id: val_page_id
    };
    
    // get single page information from Webservice and display it in Edit Modal
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
                
                e_hidden_page_id.val( data.page_id );
                e_page_name.val( data.page_name );
                e_page_title.val( data.page_title );
                e_page_icon.val( data.icon );
                e_page_description.val( data.description );
                e_page_is_visible.prop( 'checked', (data.visible=="1")?'checked':'' ).trigger( 'change' );
                e_select_plugin.val( data.plugin_id );
                e_select_plugin.trigger( 'change.select2' );
                e_select_functionality.val( data.functionality_id );
                e_select_functionality.trigger( 'change.select2' );
                var e_radio = "";
                switch( data.hierarchy ){
                    case "1":
                        e_radio = $( '#radio_hierarchy_top_e' );
                        break;
                    
                    case "2":
                        e_radio = $( '#radio_hierarchy_middle_e' );
                        break;
                    
                    case "3":
                        e_radio = $( '#radio_hierarchy_bottom_e' );
                        break;
                }
                e_radio.prop( 'checked', 'checked' );                        
                e_radio.trigger( 'click' );
                e_radio.parent().addClass( 'active' );
                e_radio.trigger( 'change' );
                
                e_parent_page.val( data.parent_id );
                e_parent_page.trigger( 'change.select2' );
                
                return;
            }
        }
    });
    
    
    
    
    
    
}

// This function is inside the onclick attribute of the Delete Button in the column of each row of the pages DataTable
function deletePage( thees, e ){
    ////console.log( e );
    ////console.log( thees );
    //thees.preventDefault();
    e.preventDefault();
    
    var tr = $( thees ).parent().parent( 'tr' );
    var val_page_id = tr.data( 'page-id' );
    ////console.log( val_page_id );
    var val_page_title = tr.find( 'td:nth-child(2)' ).text();
    
    // Show a confirmation SweetActionAlert to confirm the delete operation
    showConfirmSweetAlert( "Confirm", "Are you sure you want to delete the page '" + val_page_title + "'" , "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
        ////console.log( 'yes clicked' );
        
        showLoadingSweetAlert( "Deleting", "Please wait while the page is being deleted" );
        
        var data = {
            what_do_you_want: "scodezy_delete_page",
            page_id: val_page_id
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
                    
                    // Delete this entry from the pages DataTable if it belongs to the currently selected plugin
                    if( ($( '#select_plugin' ).val() == value.plugin_id) || ($( '#select_plugin' ).val() == "-1") ){
                        DataTablePages.row( tr ).remove().draw();
                    }
                    
                    showSimpleToast( "success", data.info );
                    
                    
                    return;
                }



            }
        });
    });
}

// Creates an HTML Element for the Table Row (tr) for tha pages that have been receveived from the API
function createPageRowForPagesDataTable( value ){
    var html = '<tr data-page-id="'+value.page_id+'">\n';
    html += '<td><div class="form-check form-check-solid  form-check-sm">\n' +
                    '<input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg"/>\n' +                                         
                '</div>\n';
            '</td>\n';
    html += '<td>' + value.page_title + '</td>\n';
    html += '<td>' + value.page_name + '</td>\n';
    
    var hierarchy = "";
    var hierarchy_color = "";
    switch( value.hierarchy ){
        case "1":
            hierarchy = '&nbsp;&nbsp;&nbsp;Top&nbsp;&nbsp;&nbsp;';
            //hierarchy_color = 'bg-success text-inverse-success';
            hierarchy_color = 'badge-success';
            break;
            
        case "2":
            hierarchy = 'Middle';
            //hierarchy_color = 'bg-warning text-inverse-warning';
            hierarchy_color = 'badge-warning';
            break;
            
        case "3":
            hierarchy = 'Bottom';
            //hierarchy_color = 'bg-info text-inverse-info';
            hierarchy_color = 'badge-info';
            break;
            
        default:
            hierarchy = 'Not Set';
            //hierarchy_color = 'bg-danger text-inverse-danger';
            hierarchy_color = 'badge-danger';
    }
    /*
    hierarchy = '<div class="symbol symbol-50px">' +
                    '<div class="symbol-label fs-2 fw-semibold '+ hierarchy_color +'">'+ hierarchy +'</div>' +
                '</div>';
    */
    hierarchy = '<span class="badge rounded '+hierarchy_color+'">'+hierarchy+'</span>';
    
    var visible = (value.visible=="1")?'value="1" checked="checked"':'value="1"';
    
    html += '<td>' + hierarchy + '</td>\n';
    html += '<td><i class="' + value.icon + '"> </i></td>\n';
    html += '<td>' + 
                '<label class="form-check form-switch form-check-custom form-check-solid">\n' +
                    '<input class="form-check-input check_page_visible_status" type="checkbox" '+visible+' />\n' +
                    //'<span class="form-check-label">\n' +
                    //    'Without id linking' +
                    //'</span>\n' + 
                '</label>\n' +
            '</td>\n';
    html += '<td><a href="#" class="" title="Edit Page" onclick="editPage(this,event);"><i class="fa-regular fs-2x text-primary fa-pen-to-square"></i></a> \n\
                 <a href="#" class="ms-3" title="Delete Page" onclick="deletePage(this,event);"><i class="bi bi-trash fs-2x text-danger"></i></a></td>\n';
    html += '</tr>\n';
    
    return html;
}

// Make an Ajax call to get all the pages from API, and passing a callback function to be executed when the result of AJAX is received
function get_all_pages( callback, url_parameters = "" ){
    
    var data = "";
    if( url_parameters == "" ){
        data = {
            what_do_you_want: 'scodezy_get_pages'
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

// Setting EventListener for change event on the Select Plugin dropdown, so that when its value is selected, it will deliver a call to API to get pages for that plugin
function selectPluginToViewItsPages(){
    var e_select_plugin     = $( '#select_plugin' );
    var e_table_pages       = $( '#table_pages' );
    //var e_table_loading     = $( '.pages-table-loading' );
    
    e_select_plugin.on( 'change', function( e ){
        
        var val_plugin_id = e_select_plugin.val();
        
        var data = {
            what_do_you_want: 'scodezy_get_pages',
            plugin_id: val_plugin_id
        };
        
        get_all_pages(function(){
            //e_table_pages.empty();
            showDataTableLoading();

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
                        showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                        e_select_plugin.val( '-1' );
                        e_select_plugin.trigger( 'change' );
                        return;
                    }
                    if( jSon[ 'type' ] == 'success' ){

                        var data = jSon[ 'info' ];

                        html = "";
                        $.each( data.data, function( i, v ){
                            html += createPageRowForPagesDataTable( v );
                        });

                        DataTablePages.destroy();
                        e_table_pages.find( 'tbody' ).html( html );
                        initPagesDataTable();

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
            //console.log( returned_data );
            
            callback( returned_data );
        }
    });
}

// Retrieve all the plugins from API into the dropdown, so that we can select a plugin to view the pages that it holds, in the DataTable
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
            // //console.log( data.info );
            // //console.log( data.data );
            var html = '<option></option>\n';
            html += '<option value="-1">All Plugins</option>\n';
            $.each( data.data, function( i, v ){
                html += createPluginInformationOptionElementForSelectTag( v );
            });

            e_select_plugin.html( html );

            //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
            //    redirect( data.login_url ); 
            //});

            return;
        }
    });
}

// All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
function initPagesDataTable(){
    
    DataTablePagesParameters = {
        searching: true,
        language: {
            emptyTable: 'Select a plugin from the above dropdown to view its pages'
        },        
        columnDefs: [
            {// set default column settings
                orderable: false,
                targets: [0, 6]
            },
            {
                searchable: false,
                targets: [0]
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
        "dom": // This dom is to fix the datatables search input from not appearing 
            "<'row mb-2'" +
            "<'col-sm-6 d-flex align-items-center justify-conten-start dt-toolbar'l>" +
            "<'col-sm-6 d-flex align-items-center justify-content-end dt-toolbar'f>" +
            ">" +

            "<'table-responsive'tr>" +

            "<'row'" +
            "<'col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start'i>" +
            "<'col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end'p>" +
            ">"
        /*
        select: {
            style: 'multi',
            selector: 'td:first-child input[type="checkbox"]',
            className: 'row-selected'
        },
         * 
         */
    };
    
    
    DataTablePages = $( table_pages ).DataTable( DataTablePagesParameters );
    
    // This is used for the purpose of Multi-selecting table rows to show the count on top right corner of the card-toolbar
    DataTablePages.on( 'draw', function(){
        updateMultipleRowsSelection();
    });
    
    
}











function showDataTableLoading(){
    var e_table_loading = $( '.pages-table-loading' );
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