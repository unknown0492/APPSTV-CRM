var table_name = "table_products";   
var table = "#" + table_name;   // The id with # of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var DataTable = null;
var DataTableParameters = null;
const loadingEl = document.createElement("div");
var select_parent_page_original_data = "";

$( document ).ready(function(){
    // All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
    initDataTable();
    
    // Get all products from products table and display them in the DataTable
    loadAllProductsIntoTable();
    
    // This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
    multipleRowsSelection();
    
    // Initialize the components of Create product modal
    initCreateProductModal();
    
    // Initialize the components of Manage Inventory modal
    initManageProductInventoryModal();
    
    // Update Inventory button on the Manage Inventory Modal
    update_inventory();
});

// Update Inventory button on the Manage Inventory Modal
function update_inventory(){
    var form                            = getElementByID( 'form_manage_product_inventory' );
    var div_modal_footer                = form.find( '.modal-footer' );
    var div_inventory_section           = getElementByClass( 'div_inventory_section' );
    var p_inventory_history             = form.find( '.inventory_history' );
    var e_total_inventory_after_update  = getElementByID( 'total_inventory_after_update' );
    var e_hidden_product_id             = getElementByID( 'hidden_selected_product_id_for_inventory' );
    var e_store                         = getElementByID( 'store' );
    var e_current_inventory_count       = getElementByID( 'current_inventory_count' );
    var e_new_inventory_count           = getElementByID( 'new_inventory_count' );
    var btn_update                      = getElementByID( 'btn_update_inventory' );
    var btn_reset                       = getElementByID( 'btn_reset_manage_inventory' );
    
    btn_update = convertToAnimatedButton( btn_update );
    
    btn_update.on( 'click', function(){
        
        console.log( 'update clicked' );
        
        // Do the validation of the form
        var source_val                  = e_store.val();
        var new_inventory_count_val     = e_new_inventory_count.val();
        var product_id_val              = e_hidden_product_id.val();

        if( source_val == "" ){
            showSimpleToast( "error", "Please select a source" );
            return;
        }
        if( new_inventory_count_val == "" ){
            showSimpleToast( "error", "Please enter additional stock count" );
            return;
        }
        if( product_id_val == "" ){
            showSimpleToast( "error", "Product ID is missing" );
            return;
        }

        var new_inventory_count_parsley = e_new_inventory_count.parsley();
        if( !new_inventory_count_parsley.isValid() ){
            showSimpleToast( "error", "Please enter additional stock count" );
            return;
        }
        
        console.log( 'validated' );
        showLoadingOnButton( btn_update );
        
        var data = {
            what_do_you_want: 'update_crm_product_inventory',
            product_id: product_id_val,
            source: e_store.val(),
            current_inventory_count: e_current_inventory_count.val(),
            new_inventory_count: e_new_inventory_count.val()
        };
        
        console.log( data );
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            // dataType: "json",
            data: data,
            success: function( returned_data ){
                console.log( returned_data );
                hideLoadingOnButton( btn_update );

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                    showSimpleToast( "error", jSon[ 'info' ] );
                    //showSimpleSweetAlert( jSon[ 'info' ], "error", "Noted", "btn btn-primary" );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var info    = jSon[ 'info' ];
                    var message = info[ 'message' ];
                    var data    = info[ 'data' ];
                    console.log( info );
                    console.log( data );

                    // Empty the value of Additional Stock Count
                    e_new_inventory_count.val( '' );
                    e_new_inventory_count.parsley().reset();
                    
                    // Set the new inventory count received in response to the current_inventory_count readOnly field
                    var new_inventory_count = data.new_inventory_count;
                    e_current_inventory_count.val( new_inventory_count );
                    show( div_inventory_section );
                    
                    var inventory_history       = data.inventory_history;
                    e_total_inventory_after_update.text( new_inventory_count + "" );
                    
                    // Update the Inventory History
                    var inventory_history_text = '';
                    if( inventory_history != "NA" ){
                        var humanReadableDate = dbTimestampToSingaporeTimestamp( inventory_history.updated_at );
                        inventory_history_text = 'Inventory count <span class="fw-bold">'+ inventory_history.inventory +'</span> last updated on <span class="fw-bold">'+humanReadableDate+'</span>';
                    }
                    p_inventory_history.html( inventory_history_text );
                    
                    // Show the Update Button
                    show( div_modal_footer );
                    
                    // Show the success message
                    showSimpleSweetAlert( message, "success", "Thank you", "btn btn-primary" );
                    
                }
            }
        });
        
    });
}

function manageInventory( thees, event ){
    event.preventDefault();
    
    var btn_reset                        = getElementByID( 'btn_reset_manage_inventory' );
    
    // Reset the form
    btn_reset.trigger( 'click' );
    
    var product_id      = $( thees ).parents( 'tr' ).attr( "data-product-id" );
    var product_name    = $( thees ).parents( 'tr' ).find( ".product-name" ).text();
    //console.log( product_id );
    
    var modal                            = getElementByID( 'modal_manage_product_inventory' );
    var btn_update                       = getElementByID( 'btn_update_inventory' );
    var e_store                          = getElementByID( 'store' );
    var e_hidden_product_id              = getElementByID( 'hidden_selected_product_id_for_inventory' );
    var e_td_product_id                  = getElementByID( 'product_id_manage_inventory' );
    var e_td_product_name                = getElementByID( 'product_name_manage_inventory' );
    
    e_hidden_product_id.val( product_id );
    e_td_product_id.text( product_id );
    e_td_product_name.text( product_name );
    
    modal.modal( 'show' );
    
}

// Make an Ajax call to get all the plugins from API, and passing a callback function to be executed when the result of AJAX is received
function get_inventory( callback, data = '' ){
    
    if( data == '' ){
        data = {
            what_do_you_want: 'get_crm_product_inventory'
        };
    }
    console.log( data );
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        //dataType: "json",
        data: data,
        success: function( returned_data ){
            console.log( returned_data );
            
            callback( returned_data );
        }
    });
}

function initManageProductInventoryModal(){
    var modal                            = getElementByID( 'modal_manage_product_inventory' );
    var btn_reset                        = getElementByID( 'btn_reset_manage_inventory' );
    var btn_update                       = getElementByID( 'btn_update_inventory' );
    var e_store                          = getElementByID( 'store' );
    var e_hidden_product_id              = getElementByID( 'hidden_selected_product_id_for_inventory' );
    var e_td_product_id                  = getElementByID( 'product_id_manage_inventory' );
    var e_td_product_name                = getElementByID( 'product_name_manage_inventory' );
    var e_current_inventory_count        = getElementByID( 'current_inventory_count' );
    var e_new_inventory_count            = getElementByID( 'new_inventory_count' );
    var e_hidden_product_id              = getElementByID( 'hidden_selected_product_id_for_inventory' );
    var div_sections_to_hide             = getElementByClass( 'sections_to_hide_together' );
    var div_loading_section              = getElementByClass( 'div_inventory_loading_section' );
    var div_inventory_section            = getElementByClass( 'div_inventory_section' );
    var e_total_inventory_after_update   = getElementByID( 'total_inventory_after_update' );
    var form                             = getElementByID( 'form_manage_product_inventory' );
    var div_modal_footer                 = form.find( '.modal-footer' );
    var p_inventory_history              = form.find( '.inventory_history' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_manage_product_inventory' );
    
    btn_reset.on( 'click', function(){
        // Reset the form
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );  
        
        // Reset the select tag
        e_store.val( '' );
        e_store.trigger( 'change.select2' );
        
        // Hide the inventory input section
        hide( div_sections_to_hide );
        
        // Empty the Product ID and Product Name
        e_td_product_id.text( '' );
        e_td_product_name.text( '' );
        
        // Empty the History tag
        p_inventory_history.text( '' );
        
    });
    
    e_store.on( 'change', function(){
        
        var source_id = e_store.val();
        console.log( source_id );
        
        // Hide the inventory input section
        hide( div_sections_to_hide );
        
        // Empty the history
        p_inventory_history.text( '' );
        
        // Show Loading Section
        show( div_loading_section );
        showLoadingOnElement( div_loading_section );
        
        var data = {
            what_do_you_want: 'get_crm_product_inventory',
            source: e_store.val(),
            product_id: e_hidden_product_id.val()
        };
        
        
        //console.log( data );
        get_inventory(function( returned_data ){
            
            hide( div_loading_section );
            hideLoadingOnElement( div_loading_section );
        
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){                    
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                //showSimpleToast( "error", jSon[ 'info' ] );
                showSimpleSweetAlert( jSon[ 'info' ], "error", "Noted", "btn btn-primary" );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){
                
                var info    = jSon[ 'info' ];
                var message = info[ 'message' ];
                var data    = info[ 'data' ];
                console.log( info );
                console.log( data );
                
                e_new_inventory_count.val( '' );
                e_new_inventory_count.parsley().reset();
                
                var current_inventory_count = parseInt( data.current_inventory_count );
                var inventory_history       = data.inventory_history;
                
                e_current_inventory_count.val( current_inventory_count );
                show( div_inventory_section );
                
                
                e_total_inventory_after_update.text( current_inventory_count + "" );
                
                // Show the Update Button
                show( div_modal_footer );
                
                // Update the Inventory History
                var inventory_history_text = '';
                if( inventory_history != "NA" ){
                    var humanReadableDate = dbTimestampToSingaporeTimestamp( inventory_history.updated_at );
                    inventory_history_text = 'Inventory count <span class="text-decoration-underline">'+ inventory_history.inventory +'</span> last updated on <span class="text-decoration-underline">'+humanReadableDate+'</span>';
                }
                p_inventory_history.html( inventory_history_text );
                /*
                var html = '';

                $.each( data, function( i, v ){
                    html += createTableRow( v );
                });

                DataTable.destroy();
                e_table.find( 'tbody' ).html( html );
                initDataTable();
                */
                //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
                //    redirect( data.login_url ); 
                //});

                //return;
            }
        }, data );
        
    });
    
    // Calculate the Total Stock count on entry of values in the stock count box
    e_new_inventory_count.on( 'keyup', function(){
        var current_inventory_count     = parseInt( e_current_inventory_count.val() );
        var new_inventory_count         = parseInt( e_new_inventory_count.val() );
        if( isNaN( new_inventory_count )){
            new_inventory_count = 0;
        }
        
        var total = current_inventory_count + new_inventory_count;
        e_total_inventory_after_update.text( total );
        
        
    });
}

function create_product(){
    var modal                   = getElementByID( 'modal_create_product' );
    var btn_reset_form          = getElementByID( 'btn_reset_create_product_form' );
    var btn_submit_form         = getElementByID( 'btn_create_product' );
    var form                    = getElementByID( 'form_create_product' );
    
    var e_sku                   = getElementByID( 'sku_c' );
    var e_gtin                  = getElementByID( 'gtin_c' );
    var e_product_name          = getElementByID( 'name_c' );
    var e_product_title         = getElementByID( 'title_c' );
    var e_product_description   = getElementByID( 'description_c' );
    var e_height                = getElementByID( 'height_c' );
    var e_width                 = getElementByID( 'width_c' );
    var e_depth                 = getElementByID( 'depth_c' );
    var e_weight                = getElementByID( 'weight_c' );
    var e_price                 = getElementByID( 'price_c' );
    //var e_inventory             = getElementByID( 'inventory_c' );
    var e_tax_inclusive         = getElementByID( 'tax_inclusive_c' );
    var e_physical_product      = getElementByID( 'physical_product_c' );
    var e_file_product_image    = getElementByID( 'file_product_image_c' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#form_create_product' );
    
    var parsley_sku                     = e_sku.parsley();
    var parsley_gtin                    = e_gtin.parsley();
    var parsley_product_name            = e_product_name.parsley();
    var parsley_product_title           = e_product_title.parsley();
    var parsley_product_description     = e_product_description.parsley();
    var parsley_height                  = e_height.parsley();
    var parsley_width                   = e_width.parsley();
    var parsley_depth                   = e_depth.parsley();
    var parsley_weight                  = e_weight.parsley();
    var parsley_price                   = e_price.parsley();
    //var parsley_inventory               = e_inventory.parsley();
    if( !parsley_sku.isValid() ){
        showSimpleToast( "error", "Please correct the SKU and try again" );
        return;
    }    
    
    if( !parsley_gtin.isValid() ){
        showSimpleToast( "error", "Please correct the GTIN and try again" );
        return;
    }
    
    if( !parsley_product_name.isValid() ){
        showSimpleToast( "error", "Please correct the product name and try again" );
        return;
    }
    if( !parsley_product_title.isValid() ){
        showSimpleToast( "error", "Please correct the product title and try again" );
        return;
    }
    if( !parsley_product_description.isValid() ){
        showSimpleToast( "error", "Please correct the product description and try again" );
        return;
    }
    if( !parsley_height.isValid() ){
        showSimpleToast( "error", "Please correct the product height and try again" );
        return;
    }
    if( !parsley_width.isValid() ){
        showSimpleToast( "error", "Please correct the product width and try again" );
        return;
    }
    if( !parsley_depth.isValid() ){
        showSimpleToast( "error", "Please correct the product depth and try again" );
        return;
    }
    if( !parsley_weight.isValid() ){
        showSimpleToast( "error", "Please correct the product weight and try again" );
        return;
    }
    if( !parsley_price.isValid() ){
        showSimpleToast( "error", "Please correct the product price and try again" );
        return;
    }
    /*
    if( !parsley_inventory.isValid() ){
        showSimpleToast( "error", "Please correct the product inventory and try again" );
        return;
    }
    */
    if( e_tax_inclusive.val() == "" ){
        showSimpleToast( "error", "Please select a value for `Price inclusive of Tax`" );
        return;
    }
    if( e_physical_product.val() == "" ){
        showSimpleToast( "error", "Please select a value for `Physical Product`" );
        return;
    }
    
    // Post to Ajax webservice
    

    var formData = new FormData();
    formData.append( "what_do_you_want", "create_crm_product" );
    formData.append( "sku", e_sku.val() );
    formData.append( "gtin", e_gtin.val() );
    formData.append( "title", e_product_title.val() );
    formData.append( "name", e_product_name.val() );
    formData.append( "description", e_product_description.val() );
    formData.append( "picture", e_file_product_image[ 0 ].files[ 0 ] );
    formData.append( "height", e_height.val() );
    formData.append( "width", e_width.val() );
    formData.append( "depth", e_depth.val() );
    formData.append( "weight", e_weight.val() );
    formData.append( "price", e_price.val() );
    formData.append( "tax_included", e_tax_inclusive.val() );
    formData.append( "physical_product", e_physical_product.val() );
    //formData.append( "inventory", e_inventory.val() );

    btn_submit_form = convertToAnimatedButton( btn_submit_form );
    showLoadingOnButton( btn_submit_form );
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        processData: false,
        contentType: false,
        //dataType: "json",
        data: formData,
        success: function( returned_data ){
            console.log( returned_data );

            // Hide Loading Animation on the Button
            hideLoadingOnButton( btn_submit_form );

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

                var message = info.message;
                var data    = info.data;
                
                showSimpleToast( "success", message );
                //window.location = data;
                
                var tr = createTableRow( data );
                
                DataTable.row.add( $( tr ) ).draw();
                
                showActionSweetAlert( message, "success", true, "Thank you", "btn btn-primary", function(){} );
                    
                // Close the modal and reset the form
                modal.modal( 'hide' );
                setTimeout( function(){
                    btn_reset_form.trigger( 'click' );
                }, 1500 );
                
                // showSimpleToast( "success", data.info );

                return;
            }

        }
    });
    
}

function create_file_item( file ){
    var name = file.name;
    /*
    var size = parseInt( Math.round(file.size/1000) );
    var sizeDisplay = size + " KB";
    console.log( size );
    console.log( (size > 1000) );
    if( size > 1000 ){
        size = Math.round(size/1000);
        sizeDisplay = size + " MB";
    }
    if( size > 1000 ){
        size = Math.round(size/1000);
        sizeDisplay = size + " GB";
    }
    //var sizeDisplay = (size > 1000)?size + " MB":size + " KB";
    */
   console.log( file );
    var html = '<div class="file-item p-5">' +
        '<i class="fs-2x fa-solid fa-circle-xmark remove-file-image"></i>' +
        '<img class="file-image" src="'+ URL.createObjectURL( file )+'" />' +
        '<span class="file-name">' + name + '</span>' +        
    '</div>';
    
    return html;
}

function initCreateProductModal(){
    var modal                   = getElementByID( 'modal_create_product' );
    var btn_open_modal          = getElementByID( 'btn_open_create_product_modal' );
    var btn_reset_form          = getElementByID( 'btn_reset_create_product_form' );
    var e_tax_inclusive         = getElementByID( 'tax_inclusive_c' );
    var e_physical_product      = getElementByID( 'physical_product_c' );
    var e_file_product_image    = getElementByID( 'file_product_image_c' );
    var btn_select_picture      = getElementByID( 'btn_select_product_image_c' );
    var btn_submit_form         = getElementByID( 'btn_create_product' );
    
    var form                            = getElementByID( 'form_create_product' );
    var e_scodezy_selected_picture      = form.find( '.scodezy-selected-files' );
    var e_remove_selected_picture       = form.find( '.remove-file-image' );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#modal_create_product' );
    
    
    btn_submit_form.on( 'click', function(){
        //showLoadingOnButton( btn_submit_form );
        
        create_product();
    });
    
    btn_open_modal.on( 'click', function(){
        
        // Trigger the Form's Reset event
        // Reset the form using Parsley
        btn_reset_form.trigger( 'click' );
        
        // Reset the picture field
        e_scodezy_selected_picture.html( '' );        
        e_file_product_image.val( null );
        
        // Show the modal
        modal.modal( 'show' );
        
    });
    
    btn_reset_form.on( 'click', function(){
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );  
        
        // Reset the select tag
        e_tax_inclusive.val( '' );
        e_tax_inclusive.trigger( 'change.select2' );
        
        e_physical_product.val( '' );
        e_physical_product.trigger( 'change.select2' );
        
        // Reset the selected image
        e_scodezy_selected_picture.html( '' );        
        e_file_product_image.val( null );
    });
    
    btn_select_picture.on( 'click', function(){
        e_file_product_image.trigger( 'click' );
    });
    
    e_file_product_image.on( 'change', function( e ){
        var file = e.target.files[ 0 ];
        
        // Validate the Picture
        //console.log( file );
        if( (file.type != "image/png") && (file.type != "image/jpeg") ){
            showSimpleToast( "error", "Only JPEG and PNG file types are allowed" );
            return;
        }
        var html = create_file_item( file );
        
        e_scodezy_selected_picture.html( html );
    });
    
    form.on( 'click', e_remove_selected_picture, function( e ){
        
        var element = $( e.target )[ 0 ];
        if( !$( element ).hasClass( 'remove-file-image' ) )
            return;
        
        e_scodezy_selected_picture.html( '' );
        
        e_file_product_image.val( null );
    });
}


// Creates an HTML Element for the Select Tag's options for each value that is supplied to it
function createTableRow( value ){
    var html = '<tr class="product-item" data-id="'+ value.id +'" data-product-id="'+ value.product_id +'">\n';
    html += '<td><div class="form-check form-check-custom form-check-sm">' +
                    '<input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg" />' +
                 '</div>' +
            '</td>\n';
    html += '<td>'+ value.product_id +'</td>\n';
    html += '<td>'+ value.sku +'</td>\n';
    html += '<td class="product-name">'+ value.name +'</td>\n';

    var physical_product = (value.physical_product=="1")?'<span class="badge rounded badge-success">YES</span>':'<span class="badge rounded badge-info">NO</span>';
    html += '<td>'+ physical_product +'</td>\n';       
    html += '<td>'+ value.price +'</td>\n';
    html += '<td>' +
                '<a href="#" class="me-4" title="Edit Product" onclick="editProduct(this,event);"><i class="fa-regular fs-2x text-primary fa-pen-to-square"></i></a> ' + 
                '<a href="#" class="me-4" title="Manage Inventory" onclick="manageInventory(this,event);"><i class="fa-solid fs-2x text-warning fa-boxes-stacked"></i></a>' + 
                '<a href="#" class="" title="Sync Products" onclick="syncProducts(this,event);"><i class="fa-solid fs-2x text-info fa-rotate"></i></a>' + 
            '</td>';
    html += '</tr>\n';

    return html;
}

// Make an Ajax call to get all the plugins from API, and passing a callback function to be executed when the result of AJAX is received
function get_products( callback ){
    
    var data = {
        what_do_you_want: 'get_crm_products'
    };
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        dataType: "json",
        data: data,
        success: function( returned_data ){
            console.log( returned_data );
            
            callback( returned_data );
        }
    });
}

// Get all orders from orders table and display them in the DataTable
function loadAllProductsIntoTable(){
    var e_table     = getElementByID( table_name );
    
    showDataTableLoading();
    
    get_products(function( returned_data ){
        //console.log( returned_data );
        
        hideDataTableLoading();
        
        var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
        if( jSon == false ){                    
            return;
        }

        jSon = jSon[ 0 ];

        if( jSon[ 'type' ] == 'error' ){
            //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
            //showSimpleToast( "error", jSon[ 'info' ][ 'message' ] );
            return;
        }
        if( jSon[ 'type' ] == 'success' ){

            var info    = jSon[ 'info' ];
            var message = info[ 'message' ];
            var data    = info[ 'data' ];
            console.log( info );
            console.log( data );
            
            var html = '';
            
            $.each( data, function( i, v ){
                html += createTableRow( v );
            });

            DataTable.destroy();
            e_table.find( 'tbody' ).html( html );
            initDataTable();

            //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
            //    redirect( data.login_url ); 
            //});

            //return;
        }
    });
}


// This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
function multipleRowsSelection(){
    var e_table                     = $( table );
    var e_table_parent_checkbox     = e_table.find( '.table-parent-checkbox' );
    var e_group_actions             = $( '.group_actions[data-selected-table-name="'+table_name+'"]' );
    var e_table_toolbar             = $( 'div[data-selected-table-toolbar="'+table_name+'"]' );
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
    
    $( table ).on( 'change', '.table-children-checkbox', function( e ){
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
    var e_table                     = $( table );
    var e_table_parent_checkbox     = e_table.find( '.table-parent-checkbox' );
    var e_group_actions             = $( '.group_actions[data-selected-table-name="'+table_name+'"]' );
    var e_table_toolbar             = $( 'div[data-selected-table-toolbar="'+table_name+'"]' );
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
            emptyTable: 'No products have been created yet'
        },        
        columnDefs: [
            {// set default column settings
                orderable: false,
                targets: [0, 6]
            },
            {
                searchable: false,
                targets: [0, 6]
            },
            {
                className: "text-left",
                "targets": [1,2,3, 5]
            }
        ],
        order: [
            [3, "asc"]
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
    
    
    DataTable = $( table ).DataTable( DataTableParameters );
    
    // This is used for the purpose of Multi-selecting table rows to show the count on top right corner of the card-toolbar
    DataTable.on( 'draw', function(){
        updateMultipleRowsSelection();
    });
    
    
}











function showDataTableLoading(){
    var e_table_loading = $( '.products-table-loading' );
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