var table_name = "table_orders";   
var table = "#" + table_name;   // The id with # of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var DataTableOrders = null;
var DataTableOrdersParameters = null;
const loadingEl = document.createElement("div");
var select_parent_page_original_data = "";

$( document ).ready(function(){
    // All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
    initDataTable();
    
    // Get all orders from orders table and display them in the DataTable
    loadAllOrdersIntoTable();
    
    // Initialize the Edit Order Modal
    initEditOrderModal();
    
    // Set Listener on View Button to view order address
    setListenerOnViewItemsAnchorButton();
    setListenerOnViewBillingAddressAnchorButton();
    setListenerOnViewShippingAddressAnchorButton();
    
    // Clear the modal contents when the modal is hidden/closed
    setListenerToEmptyTheItemsAndAddressModal();
    
    // Create an entry for prepare order
    create_prepare_order();
    
    // Update/Unprepare the prepared order
    update_prepare_order();
    
    // Initialize the Prepare Order modal
    initPrepareOrderModal();
    
    // Initialze the Prepared Order Summary modal
    initPreparedOrderSummaryModal();
    
    // Initialize the Confirm Delivery modal
    initConfirmDeliveryModal();
    
    // This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
    multipleRowsSelection();    
});

function isValidPicture( picture ){
    console.log( picture );
    
    var file = picture[ 0 ];
    
}

function isValidSecondHalfProductSerialNumber( product_serial_number ){
    // Length should be strictly 8 characters
    var length = product_serial_number.length;
    if( length < 8 ){
        return false;
    }
    
    // Only numeric characters
    for( i = 0 ; i < length ; i++ ){
        var char = parseInt( product_serial_number.charAt( i ) );
        ////console.log( char );
        if( isNaN( char ) ){       // do not allow anything other than 0 to 9
            return false;
        }
    }
    
    return true;
}

function initConfirmDeliveryModal(){
    var modal                       = getElementByID( 'modal_confirm_delivery' );
    var table_cd_items              = getElementByClass( 'table-confirm-delivery-items' );
    var form                        = getElementByID( 'form_confirm_delivery' );
    
    var e_order_id                  = getElementByID( 'cd_hidden_order_id' );
    
    var e_td_order_id               = getElementByID( 'cd_order_id' );
    var e_contact                   = getElementByID( 'cd_contact' );
    var e_address                   = getElementByID( 'cd_address' );
    var e_pictures                  = getElementByClass( 'cd_picture' );
    
    var btn_add_more_pictures       = getElementByID( 'btn_add_more_pictures' );
    var btn_submit                  = getElementByID( 'btn_submit_confirm_delivery' );
    
    // Accept only numeric characters
    table_cd_items.on( 'keypress', 'input.second-half-serial-number', function( e ){
        //console.log( e );
        
        if( !((e.which >= 48) && (e.which <= 57)) ){       // do not allow anything other than 0 to 9
            e.preventDefault();
        }
        
        var length = ($( this ).val()).length;
        //console.log( length );
        if( length >= 8 ){
            e.preventDefault();
        }
        
    });
    
    // Allow to paste only numeric characters of max length 8 characters
    table_cd_items.on( 'paste', 'input.second-half-serial-number', function( e ){
        var pasteData = e.originalEvent.clipboardData.getData('text');
        
        var thees = $( this );
        //console.log( pasteData );
        
        var existing_val = thees.val();
        existing_val = existing_val.trim();
        var existing_val_length = existing_val.length;
        
        var allowed_length = 8 - existing_val_length;
        if( (allowed_length < 0) ){
            allowed_length = 0;
        }
        
        var input_value = pasteData;
        input_value = pasteData.substring( 0, allowed_length );
        
        //console.log( input_value );
        
        //thees.val( input_value );
        
        setTimeout(function(){
            
            //console.log( thees );
            thees.val( existing_val + input_value );  
            
        }, 100 );
        
        // Check the value character by character, if any non-numeric character is found, discard the entire set of characters
        for( i = 0 ; i < input_value.length ; i++ ){
            var char = parseInt( input_value.charAt( i ) );
            ////console.log( char );
            if( isNaN( char ) ){       // do not allow anything other than 0 to 9
                e.preventDefault();
                $( this ).val( '' );
                break;
            }
        }
    });
    
    // Upload Pictures -> Add More pictures
    var add_more_block = '<div class="cd_picture">' +
                              '<i class="fa fa-close text-light bg-danger rounded cd_remove_picture_file p-2 me-2"></i>' +
                              '<input type="file" name="cd_picture_file" accept="image/*" class="cd_picture_file" capture>' +
                         '</div>';
    
    btn_add_more_pictures.on( 'click', function(){
        var thees = $( this );
        thees.before( add_more_block );
    });
    
    // Remove 'Add More' pictures browse button
    form.on( 'click', 'i.cd_remove_picture_file', function(){
        var thees = $( this );
        thees.parent().remove();
    });
    
    // Submit the confirm delivery form
    btn_submit.on( 'click', function(){
        
        // Validate order_id, serial_nos, ticked, at least one picture
        var order_id_val = e_order_id.val();
        if( order_id_val == "" ){
            showSimpleSweetAlert( "Failed to post the Order ID. Please try again !", "error", "Okay", "btn btn-danger" );
            return;
        }
        
        // Validate serial_nos and checkboxes
        var sn_validated = true;
        var items = [];
        $.each( table_cd_items.find( 'tbody tr' ), function( i, v ){
            var thees = $( v );
            
            var tempProduct = {};
            
            // Check if the item has_sn=1 but sn has not been filled
            var has_sn = thees.find( 'td.product-serial-number' ).attr( 'data-has-sn' );
            var sn_val = '';
            var sn_first_half_val = '';
            if( has_sn == "1" ){
                // Check if the SN input value has been filled
                sn_first_half_val   = thees.find( 'span.first-half-serial-number' ).text();
                sn_val              = thees.find( 'input.second-half-serial-number' ).val();
                if( !isValidSecondHalfProductSerialNumber( sn_val ) ){
                    thees.addClass( 'bg-danger' );
                    showSimpleSweetAlert( "You are required to fill in the 8 Digit Product Serial Number and tick/check the item before confirming the delivery", "error", "Okay", "btn btn-danger" );
                    sn_validated = false;
                    return false;
                }
                else{
                    // Remove the danger color from the table row
                    thees.removeClass( 'bg-danger' );
                    
                    
                }
            }
            
            // Check if the item has been checked
            var is_checked = thees.find( 'input.confirm-delivery-items-check' ).prop( 'checked' );
            console.log( is_checked );
            
            if( !is_checked ){
                // Change the color of the Table Row to Danger
                thees.addClass( 'bg-danger' );
                showSimpleSweetAlert( "You are required to tick/check all the items after you have delivered and installed them successfully, and before confirming the delivery", "error", "Okay", "btn btn-danger" );
                sn_validated = false;
                return false;
            }
            else{
                // Remove the danger color from the table row
                thees.removeClass( 'bg-danger' );
            }
            
            tempProduct.product_id = thees.attr( 'data-value' );
            tempProduct.has_sn = has_sn;
            tempProduct.sn = sn_first_half_val + sn_val;

            items.push( tempProduct );
            
            //console.log( v );
        });
        
        if( !sn_validated ){
            return;
        }
        
        
        // Validate the pictures. At least one picture should be valid
        var is_pictures_validated = true;
        var cd_picture_file = form.find( '.cd_picture_file' );
        var total_pictures = cd_picture_file.length;
        var selectedPicturesCount = 0;
        
        $.each( cd_picture_file, function( i, v ){
            var thees = $( v );
            
            // If the pictur is not selected in the first input
            if( thees[ 0 ].files.length == 0 ){
                selectedPicturesCount++;
            }
            else {
                var file = thees[ 0 ].files[ 0 ];
                var file_type = (file.type).toLowerCase();
                console.log( file_type );
                //console.log( file_type != "image/png" );
                //console.log( file_type != "image/jpeg" );
                if( (file_type != "image/png") && (file_type != "image/jpeg") ){
                    console.log( 'here' );
                    //selectedPicturesCount++;
                    showSimpleSweetAlert( "You have selected an invalid picture in 'Position "+ (i+1) +"'. Only JPEG and PNG files are allowed !", "error", "Okay", "btn btn-danger" );
                    is_pictures_validated = false;
                    return false;
                }
                               
                //showSimpleSweetAlert( "Failed to post the Order ID. Please try again !", "error", "Okay", "btn btn-danger" );                
            }
            
        });
        
        if( selectedPicturesCount == total_pictures ){
            showSimpleSweetAlert( "Please select at least one valid picture to upload", "error", "Okay", "btn btn-danger" );                    
            is_pictures_validated = false;            
        }
        
        if( !is_pictures_validated ){
            return;
        }
        
        // Validation Ends Here -----
        
        var cd_picture_file = form.find( '.cd_picture_file' );
        
        // Prepare the data to send to webservice
        var formData = new FormData();
        formData.append( "what_do_you_want", "update_confirm_delivery" );
        formData.append( "products", JSON.stringify( items ) );
        formData.append( "order_id", order_id_val );
        
        $.each( cd_picture_file, function( i, obj ) {
                //console.log( obj );
                //console.log( obj.files );
                //console.log( obj.files[ 0 ] );
                //formData.append('pis['+i+']', obj.files[ 0 ]);
                //formData.append( 'pictures', obj.files[ 0 ] );
                formData.append( 'pictures[]', obj.files[ 0 ] );
                /*
            $.each( obj.files, function( j, file ){
                //console.log( file );
                //formData.append('pictures['+j+']', file);
                formData.append('pis['+i+']', file);
                formData.append( 'pictures', file );
                formData.append( 'pics[]', file );
                //formData.append( 'pictures[]', file );
            });
            */
        });
        //console.log( formData );
        //formData.append( "pictures[]", cd_picture_file[ 0 ].files[ 0 ] );
        
        // Show Loading on Button
        convertToAnimatedButton( btn_submit );
        showLoadingOnButton( btn_submit );
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            processData: false,
            contentType: false,
            data: formData,
            success: function( returned_data ){
                console.log( returned_data );

                // Hide Loading Animation on the Button
                hideLoadingOnButton( btn_submit );
                
                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];
                
                var info            = jSon[ 'info' ];
                var message         = info.message;
                var modal_direction = info.modal_direction

                if( jSon[ 'type' ] == 'error' ){
                    
                    showSimpleSweetAlert( message, "error", "Okay", "btn btn-primary" );
                        
                    if( modal_direction == "close_modal" ){
                        modal.modal( 'hide' );
                        return;
                    }
                    
                }
                if( jSon[ 'type' ] == 'success' ){
                    
                    var data = info.data;
                    var response_messages = data.response_messages;
                    var order_status      = data.order_status;
                    
                    showSimpleSweetAlert( message, "success", "Okay", "btn btn-primary" );
                    
                    if( modal_direction == "close_modal" ){
                        modal.modal( 'hide' );
                        //return;
                    }
                    
                    // Update order status on the DataTables row
                    $( '#table_orders tbody' ).find( 'tr[data-order-id="'+ order_id_val +'"' ).find( 'td[data-key="order_status"]' ).html( order_status.toUpperCase() );
                    
                }



            }
        });
        
    });
    
    modal.on( 'hide.bs.modal', function(){
        //console.log( 'hehehe' );
        
        e_td_order_id.text( '' );
        e_contact.text( '' );
        e_address.text( '' );
        
        table_cd_items.find( 'tbody' ).html( '' );
        
        var e_pictures = getElementByClass( 'cd_picture' );
        
        $.each( e_pictures, function( i, v ){
            var thees = $( v );
            //console.log( thees );
            //console.log( !thees.hasClass( 'necessary' ) );
            if( !thees.hasClass( 'necessary' ) ){
                thees.remove();
            }
        });
        
        var necessary_picture_input = $( '.cd_picture.necessary' );
        //console.log( necessary_picture_input );
        necessary_picture_input.find( 'input[type="file"]' ).val( null );
        
    });
}

function createConfirmDeliveryItemsTableRow( srNo, data ){
    var tr = '<tr data-type="product-id" data-value="'+ data.product_id +'">';
    tr += '<td>' + srNo + '.</td>\n'; 
    tr += '<td>' + data.name + '</td>\n'; 
    tr += '<td>' + data.sku + '</td>\n'; 
    
    var has_sn = data.has_sn;
    var product_serial_number = "Not Applicable \n";
    if( has_sn == "1" ){
        product_serial_number = '<span class="first-half-serial-number">' + data.sku + '</span>' + 
                                    '<input placeholder="Last 8 Digits" type="text" class="form-control second-half-serial-number" />';
    }
    tr += '<td class="product-serial-number min-w-250px" data-has-sn="'+has_sn+'">' +
                product_serial_number +
          '</td>\n'; 
    tr += '<td><div class="form-check form-check-custom form-check-sm ">' +
                   '<input class="form-check-input confirm-delivery-items-check" type="checkbox" value="" maxlength="8" id="flexRadioLg" />' +
               '</div>' +
          '</td>\n';
    tr += '</tr>';
    
    return tr;
}


// Make an Ajax call to get all the data from API, and passing a callback function to be executed when the result of AJAX is received
function get_confirm_delivery_information( callback, data = "" ){
    
    if( data == "" ){
        data = {
            what_do_you_want: 'get_confirm_delivery_information'        
        };
    }
    
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

function confirmDelivery( thees, event ){
    event.preventDefault();
    
    /**
     * - Check if this order is prepared, without prepared, you cannot deliver an order
     * - 
     * 
     */
    var modal                       = getElementByID( 'modal_confirm_delivery' );
    
    var e_hidden_order_id           = getElementByID( 'cd_hidden_order_id' );
    var e_order_id                  = getElementByID( 'cd_order_id' );
    var e_contact                   = getElementByID( 'cd_contact' );
    var e_address                   = getElementByID( 'cd_address' );
    var e_table_items               = getElementByClass( 'table-confirm-delivery-items' );
    
    var order_id_val                = $( thees ).parents( 'tr[data-type="order-id"]' ).attr( 'data-order-id' );
    
    var sections_to_hide_together   = modal.find( '.sections_to_hide_together' );
    var div_cd_loading              = modal.find( '.cd_loading' );
    var div_cd_content              = modal.find( '.cd_content' );
    
    e_hidden_order_id.val( order_id_val );
    
    
    var data = {
        what_do_you_want: 'get_confirm_delivery_information',
        order_id: e_hidden_order_id.val()
    };
    
    // Hide everything
    hide( sections_to_hide_together );
    
    // Show Loading on the Modal
    show( div_cd_loading );
    showLoadingOnElement( div_cd_loading );
    
    get_confirm_delivery_information( function( returned_data ){
        
        var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
        if( jSon == false ){                    
            return;
        }

        jSon = jSon[ 0 ];
        
        var info            = jSon[ 'info' ];
        var message         = info.message;
        var modal_direction = info.modal_direction;

        if( jSon[ 'type' ] == 'error' ){
            //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
            //showSimpleToast( "error", jSon[ 'info' ][ 'message' ] );
            //return;
            if( modal_direction == "close_modal" ){
                showSimpleSweetAlert( message, "error", "Okay", "btn btn-primary" );
                modal.modal( 'hide' );
                return;
            }
        }
        if( jSon[ 'type' ] == 'success' ){
            
            // Show the content
            show( sections_to_hide_together );
            
            // Hide Loading
            hide( div_cd_loading );

            var info    = jSon[ 'info' ];

            var message = info.message;

            var data    = info.data;
            
            var shipping_address = data.shipping_address;

            e_order_id.text( order_id_val );
            
            var contact = (shipping_address.contact == '')?"NA":'<a href="tel:'+ shipping_address.contact +'">' + shipping_address.contact + '</a>';
            e_contact.html( contact );
            
            e_address.html( convertToSimpleAddress( shipping_address ) );
            
            var products = data.products;
            // console.log( products );
            var tbody = "";
            var srNo = 1;
            if( products.hasOwnProperty( 'with_sku' ) ){
                $.each( products.with_sku, function( i, v ){
                    tbody += createConfirmDeliveryItemsTableRow( srNo++, v );
                });
            }
            if( products.hasOwnProperty( 'without_sku' ) ){
                $.each( products.without_sku, function( i, v ){
                    tbody += createConfirmDeliveryItemsTableRow( srNo++, v );
                });
            }
            
            e_table_items.find( 'tbody' ).html( tbody );
            
            modal.modal( 'show' );
        }
    }, data );
}

function createPOSummaryTableRow( srNo, data ){
    var tr = '<tr>';
    tr += '<td>' + srNo + '.</td>\n'; 
    tr += '<td>' + data.product_name + '</td>\n'; 
    tr += '<td>' + data.sku + '</td>\n'; 
    tr += '<td>' + data.quantity + '</td>\n'; 
    tr += '<td><div class="form-check form-check-custom form-check-sm ">' +
                   '<input class="form-check-input" type="checkbox" value="" id="flexRadioLg" />' +
               '</div>' +
          '</td>\n';
    tr += '</tr>';
    
    return tr;
}

// Make an Ajax call to get all the plugins from API, and passing a callback function to be executed when the result of AJAX is received
function get_prepared_order_summary( callback, data = "" ){
    
    if( data == "" ){
        data = {
            what_do_you_want: 'get_prepared_order_summary'        
        };
    }
    
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

function initPreparedOrderSummaryModal(){
    var modal                       = getElementByID( 'modal_prepare_order_summary' );
    var btn_open_modal              = getElementByID( 'btn_open_prepare_order_summary_modal' );
    var btn_close_modal             = getElementByID( 'btn_close_po_summary_modal' );
    var table_po_summary            = getElementByClass( 'table-prepared-order-summary' );
    var table_po_summary_items      = getElementByClass( 'table-prepared-order-summary-items' );
    
    var e_order_ids                 = getElementByID( 'po_summary_order_id' );
    var e_total_orders              = getElementByID( 'po_summary_total_orders' );
    var e_total_products            = getElementByID( 'po_summary_total_items' );
    
    var div_po_summary_loading      = getElementByClass( 'div_po_summary_loading' );
    var div_po_summary_content      = getElementByClass( 'div_po_summary_content' );
    var div_to_hide_together        = modal.find( '.sections_to_hide_together' );
    
    // Hide all sections
    hide( div_to_hide_together );
    
    btn_open_modal.on( 'click', function(){
        
        // Hide all sections
        hide( div_to_hide_together );
        
        // Show Loading
        show( div_po_summary_loading );
        showLoadingOnElement( div_po_summary_loading );
        
        modal.modal( 'show' );
        
        get_prepared_order_summary( function( returned_data ){
            
            // Hide Loading
            hideLoadingOnElement( div_po_summary_loading );
            
            // Show all sections
            show( div_po_summary_content );
                
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){                    
                return;
            }

            jSon = jSon[ 0 ];
            
            var info            = jSon[ 'info' ];
            var message         = info.message;
            var modal_direction = info.modal_direction;
            
            console.log( modal_direction );

            if( jSon[ 'type' ] == 'error' ){
                //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                if( modal_direction == "close_modal" ){
                    modal.modal( 'hide' );
                }
                showSimpleSweetAlert( message, "error", "Okay", "btn btn-primary" );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var info    = jSon[ 'info' ];

                var message = info.message;
                
                var total_orders_count = info.total_orders_count;
                if( total_orders_count == "0" ){
                    showSimpleSweetAlert( "success", "There are no orders prepared for delivery", "Okay", "btn btn-primary" );
                    modal.modal( 'hide' );
                    return;
                }
                
                var order_ids               = info.order_ids;
                var total_products_count    = info.total_products_count;
                var products                = info.products;
                var modal_direction         = info.modal_direction;
                
                var orderIDs = order_ids.split( ',' );
                var orderIDsSpan = '';
                $.each( orderIDs, function( i, v ){
                    orderIDsSpan+= '<span data-value="order-id" data-order-id="'+ v +'" class="order-id badge badge-primary rounded">'+v+'</span>\n';
                });
                
                e_order_ids.html( orderIDsSpan );
                e_total_orders.text( total_orders_count );
                e_total_products.text( total_products_count );
                
                var tbody = "";
                $.each( products, function( i, v ){
                    var tr = createPOSummaryTableRow( i+1, v );
                    tbody += tr;
                });
                table_po_summary_items.find( 'tbody' ).html( tbody );
                
            }
        });
        
    });
    
    btn_close_modal.on( 'click', function(){
        modal.modal( 'hide' );
    });
    
    // Modal Hidden functionalities
    modal.on( 'hide.bs.modal', function(){
        
        console.log( 'modal hide' );
        
        // Clear the table cell values
        $.each( table_po_summary.find( 'td.dynamic' ), function( i, v ){
            $( v ).text( '' );
        });
        
        // Clear the items table 
        table_po_summary_items.find( 'tbody' ).html( '' );
        
        // Hide all sections to hide together
        hide( div_to_hide_together );
        
        
    });
}

function update_prepare_order(){
    var modal                       = getElementByID( 'modal_prepare_order' );
    var e_hidden_order_id           = getElementByID( 'po_hidden_order_id' );
    var e_order_status              = getElementByID( 'po_status' );
    
    var form                        = getElementByID( 'form_prepare_order' );
    var btn_unprepare_order         = getElementByID( 'btn_unprepare_order' );
    
    var table_orders                = getElementByID( 'table_orders' );
    
    btn_unprepare_order.on( 'click', function(){
        
        // All checkboxes must be checked
        var checkCount = 0;
        var totalItems = $( '.prepare-order-items-check' ).length;
        $.each( $( '.prepare-order-items-check' ), function( i, v ){
            if( $( v ).prop( 'checked' ) == true ){
                checkCount++;
            }
        });
        if( checkCount != totalItems ){
            showSimpleSweetAlert( "Please tick/check all the items and ensure that they have been taken out of the Van before you press the 'Unprepare Order' button", "error", "Oh! I forgot", "btn btn-danger" );
            return;
        }
        
        // Show Loading on the Button
        btn_unprepare_order = convertToAnimatedButton( btn_unprepare_order );
        showLoadingOnButton( btn_unprepare_order );
        
        
        var data = {
            what_do_you_want: 'update_prepare_order',
            order_id: e_hidden_order_id.val()
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                console.log( returned_data );
                
                hideLoadingOnButton( btn_unprepare_order );

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    //showSimpleToast( "error", jSon[ 'info' ][ 'message' ] );
                    var info                = jSon[ 'info' ];
                    var message             = info[ 'message' ];
                    var modal_direction     = info[ 'modal_direction' ];
                    
                    if( modal_direction == "close_modal" ){
                        modal.modal( 'hide' );
                    }
                    
                    showSimpleSweetAlert( message, "error", "Close !", "btn btn-danger" );
                    
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var info    = jSon[ 'info' ];

                    var info                = jSon[ 'info' ];
                    var message             = info[ 'message' ];
                    var modal_direction     = info[ 'modal_direction' ];
                    var order_prepared      = info[ 'order_prepared' ];
                    
                    if( modal_direction == "close_modal" ){
                        modal.modal( 'hide' );
                    }
                    
                    showSimpleSweetAlert( message, "success", "Close !", "btn btn-primary" );
                    
                    if( order_prepared == "0" ){
                        // Set order prepared status
                        e_order_status.addClass( 'text-danger' );
                        e_order_status.html( 'Order Not Prepared for Delivery' );                        
                    }
                    
                    // Update this status on the orders DataTable rows
                    var data            = info[ 'data' ];
                    var order_status    = data.order_status;
                    
                    //console.log( data );
                    //console.log( order_status );
                    
                    table_orders.find( 'tr[data-order-id="'+e_hidden_order_id.val()+'"] td[data-key="order_status"]' ).text( order_status );

                }
            }
        });
        
    });
}

function create_prepare_order(){
    var modal                       = getElementByID( 'modal_prepare_order' );
    var e_remarks                   = getElementByID( 'po_remarks' );
    var e_hidden_order_id           = getElementByID( 'po_hidden_order_id' );
    
    var form                        = getElementByID( 'form_prepare_order' );
    var btn_reset_po                = getElementByID( 'btn_reset_po' );
    var btn_prepare_order           = getElementByID( 'btn_prepare_order' );
    var btn_unprepare_order         = getElementByID( 'btn_unprepare_order' );
    
    var table_orders                = getElementByID( 'table_orders' );
    
    btn_prepare_order.on( 'click', function(){
        // Validate the fields
        var remarks_parsley             = e_remarks.parsley();
        if( !remarks_parsley.isValid() ){
            showSimpleToast( "error", "Please remove special characters from the remarks" );
            return;
        }

        // All checkboxes must be checked
        var checkCount = 0;
        var totalItems = $( '.prepare-order-items-check' ).length;
        $.each( $( '.prepare-order-items-check' ), function( i, v ){
            if( $( v ).prop( 'checked' ) == true ){
                checkCount++;
            }
        });
        if( checkCount != totalItems ){
            showSimpleSweetAlert( "Please tick/check all the items and ensure that they have been loaded into the van before you press the 'Prepare Order' button", "error", "Oh! I forgot", "btn btn-danger" );
            return;
        }
        
        // Show Loading on the Button
        btn_prepare_order = convertToAnimatedButton( btn_prepare_order );
        showLoadingOnButton( btn_prepare_order );
        
        
        var data = {
            what_do_you_want: 'create_prepare_order',
            order_id: e_hidden_order_id.val(),
            remarks: e_remarks.val()
        };
    
    
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                console.log( returned_data );
                
                hideLoadingOnButton( btn_prepare_order );

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    //showSimpleToast( "error", jSon[ 'info' ][ 'message' ] );
                    var info                = jSon[ 'info' ];
                    var message             = info[ 'message' ];
                    var modal_direction     = info[ 'modal_direction' ];
                    
                    if( modal_direction == "close_modal" ){
                        modal.modal( 'hide' );
                    }
                    
                    showSimpleSweetAlert( message, "error", "Close !", "btn btn-danger" );
                    
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var info    = jSon[ 'info' ];

                    var info                = jSon[ 'info' ];
                    var message             = info[ 'message' ];
                    var modal_direction     = info[ 'modal_direction' ];
                    
                    if( modal_direction == "close_modal" ){
                        modal.modal( 'hide' );
                    }
                    
                    showSimpleSweetAlert( message, "success", "Close !", "btn btn-primary" );
                    
                    // Update this status on the orders DataTable rows
                    if( info.hasOwnProperty( 'data' ) ){
                        var data            = info[ 'data' ];
                        var order_status    = data.order_status;

                        //console.log( data );
                        //console.log( order_status );

                        table_orders.find( 'tr[data-order-id="'+e_hidden_order_id.val()+'"] td[data-key="order_status"]' ).text( order_status );
                    }
                    
                    
                }
            }
        });
        
        
    });
}

function createPrepareOrderTableItemsRow( srNo, data ){
    var tr = '<tr>';
    tr += '<td>' + srNo + '.</td>\n'; 
    tr += '<td>' + data.name + '</td>\n'; 
    tr += '<td>' + data.quantity + '</td>\n'; 
    tr += '<td><div class="form-check form-check-custom form-check-sm ">' +
                   '<input class="form-check-input prepare-order-items-check" type="checkbox" value="" id="flexRadioLg" />' +
               '</div>' +
          '</td>\n';
    tr += '</tr>';
    
    return tr;
}

// Make an Ajax call to get all the plugins from API, and passing a callback function to be executed when the result of AJAX is received
function get_prepare_order_information( callback, data = "" ){
    
    if( data == "" ){
        data = {
            what_do_you_want: 'get_prepare_order_information'        
        };
    }
    
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

function convertToSimpleAddress( address ){
    var addr = '';
    addr += address.name_on_order + "<br />";
    
    if( address.address1 != '' ){
        addr += address.address1 + ', ';
    }
    if( address.address2 != '' ){
        addr += address.address2 + ', ';
    }
    if( address.city != '' ){
        addr += address.city + ', ';
    }
    if( address.state != '' ){
        addr += address.state + ', ';
    }
    if( address.province != '' ){
        addr += address.province + ', ';
    }
    if( address.country != '' ){
        addr += address.country + ', <br />';
    }
    if( address.zip != '' ){
        addr += address.zip;
    }
    
    return addr;
}

function calculateDeliveryDeadlineDate( date_of_order, working_days ){
    var m = moment( date_of_order );
    var m_today = moment();
    
    //console.log( m.toLocaleString() );
    //console.log( working_days );
    m = m.add( working_days, 'days' );
    //console.log( m.toString() );
    if( m.day() == 6 ){     // 0-> Sunday, 6-> Saturday
        m.add( 2, 'days' );
    }
    else if( m.day() == 0 ){     // 0-> Sunday, 6-> Saturday
        m.add( 1, 'days' );
    }
    
    //var deadline_duration   = m.duration();
    //var today_duration      = m_today.duration();
    //var remaining_days      = deadline_duration.subtract( today_duration ).days();
    var remaining_days      = m.diff( m_today, 'days' );
    var remaining_days_str  = 'Delivery due in ' + remaining_days + ' days';
    
    console.log( remaining_days + " days" );
    
    if( remaining_days == "1" ){
        remaining_days_str = 'Delivery due TOMORROW';
    }
    else if( remaining_days == "0" ){
        remaining_days_str = 'Delivery due TODAY';
    }
    else if( remaining_days < 0 ){
        remaining_days_str = 'Attention !!! Delivery date has passed';
    }
    
    //m.diff( m_today, 'days' );
    //console.log( remaining_days + " days" );
    
    var localTimestamp  = m.local();        // Convert the Date from DB into local format where it is being viewed
    var displayFormat   = localTimestamp.format( 'ddd MMM DD YYYY' ) + "<br />" + remaining_days_str;
    
    return displayFormat;
}

function prepareOrder( thees, event ){
    event.preventDefault();
    
    var modal                       = getElementByID( 'modal_prepare_order' );
    var sections_to_hide            = modal.find( '.sections_to_hide_together' );
    var div_loading                 = getElementByClass( 'div_prepare_order_loading' );
    var div_content                 = getElementByClass( 'div_prepare_order_content' );
    
    var e_order_id                  = getElementByID( 'po_order_id' );
    var e_order_status              = getElementByID( 'po_status' );
    var e_hidden_order_id           = getElementByID( 'po_hidden_order_id' );
    var e_time_of_order             = getElementByID( 'po_time_of_order' );
    var e_delivery_deadline         = getElementByID( 'po_delivery_deadline' );
    var e_order_source              = getElementByID( 'po_order_source' );
    var e_contact                   = getElementByID( 'po_contact_number' );
    var e_shipping_address          = getElementByID( 'po_shipping_address' );
    var e_remarks                   = getElementByID( 'po_remarks' );
    
    var e_table_po_items            = getElementByClass( 'table-prepare-order-products' );
    
    var order_id_val                = $( thees ).parents( 'tr[data-type="order-id"]' ).attr( 'data-order-id' );
    
    var data = {
        what_do_you_want: 'get_prepare_order_information',
        order_id: order_id_val
    };
    
    //showLoadingSweetAlert( 'Loading', 'Please wait while the order information is being retrieved', false );
    
    get_prepare_order_information(function( returned_data ){
        //console.log( returned_data );
        
        //hideLoadingSweetAlert();
        show( sections_to_hide );
    
        // Show Loading on the modal
        hide( div_loading );
        hideLoadingOnElement( div_loading );
        
        var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
        if( jSon == false ){                    
            return;
        }

        jSon = jSon[ 0 ];
        
        var info            = jSon[ 'info' ];
        var message         = info.message;
        var modal_direction = info.modal_direction

        if( jSon[ 'type' ] == 'error' ){

            if( modal_direction == "close_modal" ){
                showSimpleSweetAlert( message, "error", "Okay", "btn btn-primary" );
                modal.modal( 'hide' );
                return;
            }

        }
        if( jSon[ 'type' ] == 'success' ){

            var info    = jSon[ 'info' ];
            
            var message = info[ 'message' ];
            var data    = info[ 'data' ];
            
            console.log( info );
            console.log( data );
            
            var delivery_deadline = data.delivery_deadline;
            var shipping_address  = data.shipping_address;
            var products          = data.products;//$.parseJSON( data.products );
            console.log( products );
            
            // Set order prepared status
            var order_prepared          = data.order_prepared;
            var order_prepared_status   = '';
            if( order_prepared == "1" ){
                e_order_status.addClass( 'text-success' );
                e_order_status.html( 'Order is prepared for Delivery' );
            }
            else{
                e_order_status.addClass( 'text-danger' );
                e_order_status.html( 'Order Not Prepared for Delivery' );                
            }
            
            e_order_id.text( data.order_id );
            e_hidden_order_id.val( data.order_id );
            e_time_of_order.text( dbTimestampToSingaporeTimestamp( data.created_at ) );
            e_delivery_deadline.html( '<span class="blink">' + calculateDeliveryDeadlineDate( data.created_at, data.delivery_deadline ) + '</span>' );
            e_order_source.text( data.source_name );
            e_contact.text( shipping_address.contact );
            e_shipping_address.html( convertToSimpleAddress( shipping_address ) );
            e_remarks.text( data.order_prepared_remarks );
            //e_remarks.val( data.order_prepared_remarks );
            
            var tbody = '';
            $.each( products, function( i, v ){
                tbody += createPrepareOrderTableItemsRow( i+1, v );
            });
            
            e_table_po_items.find( 'tbody' ).html( tbody );
            
            modal.modal( 'show' );
        }
    }, data );
    
    
    
    
    
}

function initPrepareOrderModal(){
    var modal                   = getElementByID( 'modal_prepare_order' );
    var form                    = getElementByID( 'form_prepare_order' );
    var table_prepare_order     = getElementByClass( 'table-prepare-order' );
    var sections_to_hide        = modal.find( '.sections_to_hide_together' );
    var div_loading             = getElementByClass( 'div_prepare_order_loading' );
    var div_content             = getElementByClass( 'div_prepare_order_content' );
    var btn_reset_po            = getElementByID( 'btn_reset_po' );
    var e_remarks               = getElementByID( 'po_remarks' );
    var e_order_status          = getElementByID( 'po_status' );
    
    // Hide Content Section
    hide( sections_to_hide );
    
    // Show Loading on the modal
    show( div_loading );
    showLoadingOnElement( div_loading );
    
    modal.on( 'hide.bs.modal', function(){
        console.log( 'modal hidden' );
        
        // Reset modal data
        $.each( table_prepare_order.find( 'td' ), function( i, v ){
            if( $( v ).find( 'textarea' ).length == 0 ){                
                $( v ).text( '' );
            }
        });
        
        // Hide Content Section
        hide( sections_to_hide );

        // Show Loading on the modal
        show( div_loading );
        showLoadingOnElement( div_loading );
        
        // Reset Prepare Order status
        e_order_status.removeClass( 'text-danger' );
        e_order_status.removeClass( 'text-success' );
        e_order_status.text( '' );
        
        form.trigger( 'reset' );
    });
    
    form.on( 'reset', function(){
        
        // Reset TextArea parsley 
        var remarks_parsley = e_remarks.parsley();
        remarks_parsley.reset();
        
    });
}

function setListenerToEmptyTheItemsAndAddressModal(){
    var modal_products           = getElementByID( 'modal_view_products' );
    var modal_address            = getElementByID( 'modal_view_address' );
    var modal_edit_order         = getElementByID( 'modal_edit_order' );
    
    var table_edit_order         = getElementByClass( 'table-edit-order' );
    var table_order_meta         = getElementByClass( 'table-order-meta' );
    var table_products           = getElementByID( 'view_products' );
    var table_address            = getElementByID( 'table_view_address' );
    
    modal_edit_order.on( 'hide.bs.modal', function(){
        console.log( 'Edit Order modal hidden' );
        
        $.each( table_edit_order.find( 'td' ), function( i, v ){
            $( v ).text( '' );
        });
        
        $.each( table_order_meta.find( 'td' ), function( i, v ){
            $( v ).text( '' );
        });
    });
    
    modal_products.on( 'hide.bs.modal', function(){
        console.log( 'Products modal hidden' );
        
        $.each( table_products.find( 'td' ), function( i, v ){
            $( v ).text( '' );
        });
    });
    
    modal_address.on( 'hide.bs.modal', function(){
        console.log( 'Address modal hidden' );
        
        $.each( table_address.find( 'td' ), function( i, v ){
            $( v ).text( '' );
        });
    });
}

function setListenerOnViewItemsAnchorButton(){
    var e_items         = getElementByID( 'items_e' );
    var e_source_name   = getElementByID( 'source_e' );
    
    
    e_items.on( 'click', function( e ){
        e.preventDefault();
        
        viewOrderItems( e, e_items.find( 'a' ), e_source_name.text() );
    });
    
    
}

function setListenerOnViewBillingAddressAnchorButton(){
    var e_billing_address = getElementByID( 'billing_address_e' );
    
    e_billing_address.on( 'click', function( e ){
        e.preventDefault();
        
        viewOrderAddress( e, e_billing_address.find( 'a' ), 'Billing Address' );
    });
    
    
}

function setListenerOnViewShippingAddressAnchorButton(){
    var e_shipping_address = getElementByID( 'shipping_address_e' );
    
    
    e_shipping_address.on( 'click', function( e ){
        e.preventDefault();
        
        viewOrderAddress( e, e_shipping_address.find( 'a' ), 'Shipping Address' );
    });
    
    
}

function viewOrderAddress( e, thees, address_type ){
    e.preventDefault();
    var modal                   = getElementByID( 'modal_view_address' );
    var e_address_type          = getElementByID( 'view_address_type' );
    var e_table                 = getElementByID( 'table_view_address' );
    
    var e_first_name       = getElementByID( 'address_first_name' );
    var e_last_name        = getElementByID( 'address_last_name' );
    var e_name_on_order    = getElementByID( 'address_name_on_order' );
    var e_contact          = getElementByID( 'address_contact' );
    var e_address1         = getElementByID( 'address_address1' );
    var e_address2         = getElementByID( 'address_address2' );
    var e_city             = getElementByID( 'address_city' );
    var e_state            = getElementByID( 'address_state' );
    var e_province         = getElementByID( 'address_province' );
    var e_zip              = getElementByID( 'address_zip' );
    var e_country          = getElementByID( 'address_country' );
    var e_latitude         = getElementByID( 'address_latitude' );
    var e_longitude        = getElementByID( 'address_longitude' );
    //var e_        = getElementByID( '' );
    
    // Empty all the fields
    e_address_type.text( '' );
    e_first_name.text( '' );
    e_last_name.text( '' );
    e_name_on_order.text( '' );
    e_contact.text( '' );
    e_address1.text( '' );
    e_address2.text( '' );
    e_city.text( '' );
    e_state.text( '' );
    e_province.text( '' );
    e_zip.text( '' );
    e_country.text( '' );
    e_latitude.text( '' );
    e_longitude.text( '' );
    
    
    //console.log( thees );
    var html = atob( $( thees ).parent().attr( 'data-address' ) );     // atob() converts base64 to string  | btoa() converts string to base64  | Source: https://www.digitalocean.com/community/tutorials/how-to-encode-and-decode-strings-with-base64-in-javascript
    
    var address = $.parseJSON( html );
    console.log( address );
    var trHTML = "";
    
    e_address_type.text( address_type );
    e_first_name.text( address.first_name );
    e_last_name.text( address.last_name );
    e_name_on_order.text( address.name_on_order );
    e_contact.text( address.contact );
    e_address1.text( address.address1 );
    e_address2.text( address.address2 );
    e_city.text( address.city );
    e_state.text( address.state );
    e_province.text( address.province );
    e_zip.text( address.zip );
    e_country.text( address.country );
    e_latitude.text( address.latitude );
    e_longitude.text( address.longitude );
    
    modal.modal( 'show' );
}

// Make an Ajax call to get all the plugins from API, and passing a callback function to be executed when the result of AJAX is received
function get_order_information( callback, data = "" ){
    
    if( data == "" ){
        data = {
            what_do_you_want: 'get_crm_order_information'        
        };
    }
    
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

// This function is called on the edit order button beside each order in the datatables
function viewOrder( thees, event ){
    event.preventDefault();
    
    var modal = getElementByID( 'modal_edit_order' );
    
    var e_order_id                  = getElementByID( 'order_id_e' );
    var e_customer_id               = getElementByID( 'customer_id_e' );
    var e_created_at                = getElementByID( 'created_at_e' );
    var e_updated_at                = getElementByID( 'updated_at_e' );
    var e_is_cancelled              = getElementByID( 'is_cancelled_e' );
    var e_test                      = getElementByID( 'test_e' );
    var e_currency                  = getElementByID( 'currency_e' );
    var e_contact_email             = getElementByID( 'contact_email_e' );
    var e_email                     = getElementByID( 'email_e' );
    var e_financial_status          = getElementByID( 'financial_status_e' );
    var e_fulfillment_status        = getElementByID( 'fulfillment_status_e' );
    var e_order_status              = getElementByID( 'order_status_e' );
    var e_billing_address           = getElementByID( 'billing_address_e' );
    var e_shipping_address          = getElementByID( 'shipping_address_e' );
    var e_items                     = getElementByID( 'items_e' );
    var e_total_products_price      = getElementByID( 'total_products_price_e' );
    var e_shipping_method_name      = getElementByID( 'shipping_method_name_e' );
    var e_shipping_price            = getElementByID( 'shipping_price_e' );
    var e_shipping_tax              = getElementByID( 'shipping_tax_e' );
    var e_subtotal_price            = getElementByID( 'subtotal_price_e' );
    var e_total_tax                 = getElementByID( 'total_tax_e' );
    var e_total_discounts           = getElementByID( 'total_discounts_e' );
    var e_total_price               = getElementByID( 'total_price_e' );
    var e_source                    = getElementByID( 'source_e' );
    
    var e_table_order_meta          = getElementByClass( 'table-order-meta' );
    //var e_ = getElementByID( '' );
    
    var order_id_val = $( thees ).parents( 'tr[data-type="order-id"]' ).attr( 'data-order-id' );
    
    // Empty all the fields
    e_order_id.text( '' );
    e_customer_id.text( '' );
    e_created_at.text( '' );
    e_updated_at.text( '' );
    e_is_cancelled.text( '' );
    e_test.text( '' );
    e_currency.text( '' );
    e_contact_email.text( '' );
    e_email.text( '' );
    e_financial_status.text( '' );
    e_fulfillment_status.text( '' );
    e_order_status.text( '' );
    e_billing_address.attr( 'data-address', '' );
    e_shipping_address.attr( 'data-address', '' );
    e_items.attr( 'data-items', '' );
    e_total_products_price.text( '' );
    e_shipping_method_name.text( '' );
    e_shipping_price.text( '' );
    e_shipping_tax.text( '' );
    e_subtotal_price.text( '' );
    e_total_tax.text( '' );
    e_total_discounts.text( '' );
    e_total_price.text( '' );
    e_source.text( '' );

    //console.log( order_id_val );
    
    var data = {
        what_do_you_want: 'get_crm_order_information',
        order_id: order_id_val
    };
    
    showLoadingSweetAlert( 'Loading', 'Please wait while the order information is being retrieved', false );
    
    get_order_information(function( returned_data ){
        //console.log( returned_data );
        
        hideLoadingSweetAlert();
        
        var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
        if( jSon == false ){                    
            return;
        }

        jSon = jSon[ 0 ];

        if( jSon[ 'type' ] == 'error' ){
            //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
            showSimpleToast( "error", jSon[ 'info' ][ 'message' ] );
            return;
        }
        if( jSon[ 'type' ] == 'success' ){

            var info    = jSon[ 'info' ];
            
            var message = info[ 'message' ];
            var data    = info[ 'data' ];
            
            console.log( info );
            console.log( data );
            
            // Set data on the fields
            e_order_id.html( data.order_id );
            e_customer_id.html( data.customer_id );
            e_created_at.html( dbTimestampToSingaporeTimestamp( data.created_at ) );
            e_updated_at.html( dbTimestampToSingaporeTimestamp( data.updated_at ) );
            
            // Is Cancelled
            if( data.is_cancelled=="1" ){
                e_is_cancelled.html( "ORDER CANCELLED" );
                e_is_cancelled.addClass( 'bg-danger text-white' );
            }
            else{
                e_is_cancelled.html( "ORDER ACTIVE" );
                e_is_cancelled.addClass( 'bg-success text-white' );
            }
            
            // Test Order ?
            if( data.test=="1" ){
                e_test.html( "YES" );
                e_test.addClass( 'bg-danger text-white' );
            }
            else{
                e_test.html( "NO" );
                e_test.addClass( 'bg-success text-white' );
            }
            
            e_currency.html( data.currency );
            e_contact_email.html( data.contact_email );
            e_email.html( data.email );
            e_financial_status.html( data.financial_status );
            e_fulfillment_status.html( data.fulfillment_status );
            e_order_status.html( data.order_status );
                    
            var base64_billing_address = btoa( JSON.stringify( data.billing_address ) );
            e_billing_address.attr( 'data-address', base64_billing_address );
            e_billing_address.html( '<a href="#">View</a>' );
            
            var base64_shipping_address = btoa( JSON.stringify( data.shipping_address ) );
            e_shipping_address.attr( 'data-address', base64_shipping_address );
            e_shipping_address.html( '<a href="#">View</a>' );
            
            var base64_products = btoa( ( data.products ) );
            e_items.attr( 'data-products', base64_products );
            e_items.html( '<a href="#">View</a>' );
            
            e_total_products_price.html( data.total_products_price );
            e_total_discounts.html( data.total_discounts );
            e_subtotal_price.html( data.subtotal_price );
            e_shipping_method_name.text( data.shipping_method_name );
            e_shipping_price.html( data.total_shipping_price );
            e_shipping_tax.html( data.total_shipping_tax );
            e_total_tax.html( data.total_tax );
            e_total_price.html( data.total_price );
            
            e_source.html( data.source_name );
            
            // Check if order_meta has values
            // console.log( data.order_meta.length );
            if( data.order_meta.length > 0 ){
                var html = "";
                $.each( data.order_meta, function( i, v ){
                    var tr = "<tr> \n";
                    
                    if( v.order_meta_key == "shopify_order_confirmed" ){
                        v.order_meta_value = (v.order_meta_value=="1")?"Confirmed":"Not Confirmed";
                    }
                    if( v.order_meta_key == "shopify_order_status_url" ){
                        v.order_meta_value = '<a href="'+ v.order_meta_value +'" target="_blank">View</a>'
                    }
                    
                    tr += "<th class=\"w-175px\">" + order_meta_key_mappings[ v.order_meta_key ] + "</th> \n" + "<td>" + v.order_meta_value + "</td> \n";
                    html += tr;
                });
                e_table_order_meta.find( 'tbody' ).html( html );
            }
            
        }
    }, data );
    
    
    
    modal.modal( 'show' );
}

// Load the products JSON into a modal
function viewOrderItems( e, thees, source_name ){
    e.preventDefault();
    var modal           = $( '#modal_view_products' );
    var e_view_products = $( '#view_products' );
    
    //console.log( thees );
    var html = atob( $( thees ).parent().attr( 'data-products' ) );     // atob() converts base64 to string  | btoa() converts string to base64  | Source: https://www.digitalocean.com/community/tutorials/how-to-encode-and-decode-strings-with-base64-in-javascript
    
    source_name = source_name.toLowerCase();
    //console.log( source_name );
    console.log( html );
    //html = html.replace(/"/g, '\\"');
    //html = JSON.stringify( html );
    //console.log( html );
    var items = $.parseJSON( html );
    console.log( items );
    var srNo = 1;
    var productHTML = "";
    if( source_name == "shopify" ){
        $.each( items, function( i, v ){
            var tr = '<tr>';
            tr += '<td class="srNo w-25px">'+ srNo +'</td>';
            var currency = v.price_set.shop_money.currency_code;
            //console.log( v.price_set );
            tr += '<td class=""><span class="product_title">'+ v.name + '</span><br />SKU: ' + v.sku + '<br />' + currency + ' ' + v.price + '</td>';
            tr += '<td class="w-75px product_qty"><span class="product_qty">Qty</span><br />' + v.quantity + '</td>';
            var totalPrice = ( parseFloat( v.price ) * parseInt( v.quantity ) ).toFixed( 2 );
            tr += '<td class="w-75px product_total"><span class="product_total">Total</span><br />' + totalPrice + '</td>';
            tr += "</tr>\n";
            productHTML += tr; 
            srNo++;
        });
    }
    
    e_view_products.html( productHTML );
    
    modal.modal( 'show' );
}

// Initialize the Edit Order Modal
function initEditOrderModal(){
    var modal = getElementByID( 'modal_edit_order' );
    var close = getElementByID( 'btn_close_edit_modal' );
    
    close.on( 'click', function(){
        modal.modal( 'hide' );
    });
    
}



// Creates an HTML Element for the Select Tag's options for each value that is supplied to it
function createTableRow( value ){
    var test_tr_class = (value.test=="1")?'bg-danger text-light':'';
    var html = '<tr class="order-item '+ test_tr_class +'" data-type="order-id" data-id="'+ value.id +'" data-order-id="'+ value.order_id +'">\n';
    html += '<td><div class="form-check form-check-custom form-check-sm">' +
                    '<input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg" />' +
                 '</div>' +
            '</td>\n';
    html += '<td>'+ value.order_id +'</td>\n';
    html += '<td>'+ value.source_name +'</td>\n';
    html += '<td data-created-at="'+value.created_at+'">'+ dbTimestampToSingaporeTimestampWithAgo( value.created_at ) +'</td>\n';
    var cancelled = (value.is_cancelled=="1")?'<span class="badge rounded badge-danger">YES</span>':'<span class="badge rounded badge-info">NO</span>';
    html += '<td>'+ cancelled +'</td>\n';
    var test = (value.test=="1")?'<span class="badge rounded badge-warning">YES</span>':'<span class="badge rounded badge-success">NO</span>';
    //html += '<td>'+ test +'</td>\n';
    var order_status = value.order_status;
    order_status = order_status.toUpperCase();
    html += '<td data-key="order_status">'+ order_status +'</td>\n';
    html += '<td data-products="'+ value.products +'"><a href="#" onclick="viewOrderItems( event, this,\''+ value.source_name +'\' );">View</a></td>\n';
    html += '<td>'+ value.total_price +'</td>\n';
    //html += '<td><a href="#" class="" title="Edit Order" onclick="editOrder(this,event);"><i class="fa-regular fs-2x text-primary fa-pen-to-square"></i></a>\n';
    
    var action_view_order = '<div class="menu-item px-3">' + 
                                '<a href="#" class="menu-link px-3 text-left" onclick="viewOrder(this,event);" >View Order</a>' + 
                            '</div>';
                    
    var action_prepare_order = '<div class="menu-item px-3">' + 
                                   '<a href="#" class="menu-link px-3 text-left" onclick="prepareOrder(this,event);">Prepare Order</a>' + 
                               '</div>';
    var action_confirm_delivery = '<div class="menu-item px-3">' + 
                                    '<a href="#" class="menu-link px-3 text-left" onclick="confirmDelivery(this,event);">Confirm Delivery</a>' + 
                                  '</div>';
    var actions = '';
    if( (value.order_status == "delivered") || 
            (value.order_status == "partially_delivered") || 
            (value.order_status == "cancelled") ){
        
        actions = action_view_order;
    }
    else{
        actions = action_view_order + 
                    action_prepare_order +  
                    action_confirm_delivery;
    }
    
    html += '<td class="">' + 
                '<a href="#" class="btn btn-light btn-active-light-primary btn-flex btn-center btn-sm float-end" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Actions' + 
                    '<i class="ki-outline ki-down fs-5 ms-1"></i>' + 
                '</a>' +
                '<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">' + 
                    actions
                '</div>' + 
            '</td>';
    
    html += '</tr>\n';

    return html;
}

// Make an Ajax call to get all the plugins from API, and passing a callback function to be executed when the result of AJAX is received
function get_orders( callback ){
    
    var data = {
        what_do_you_want: 'get_crm_orders'
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
function loadAllOrdersIntoTable(){
    var e_table     = getElementByID( table_name );
    
    showDataTableLoading();
    
    get_orders(function( returned_data ){
        //console.log( returned_data );
        
        hideDataTableLoading();
        
        var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
        if( jSon == false ){                    
            return;
        }

        jSon = jSon[ 0 ];

        if( jSon[ 'type' ] == 'error' ){
            //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
            showSimpleToast( "error", jSon[ 'info' ][ 'message' ] );
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

            DataTableOrders.destroy();
            e_table.find( 'tbody' ).html( html );
            initDataTable();
            KTMenu.createInstances();


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
    
    DataTableOrdersParameters = {
        searching: true,
        language: {
            emptyTable: 'No orders have been placed yet'
        },        
        columnDefs: [
            {// set default column settings
                orderable: false,
                targets: [0, 8]
            },
            {
                searchable: false,
                targets: [0, 8]
            },
            {
                className: "text-left",
                "targets": [1,7]
            }
        ],
        order: [
            //[3, "asc"]
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
    
    
    DataTableOrders = $( table ).DataTable( DataTableOrdersParameters );
    
    // This is used for the purpose of Multi-selecting table rows to show the count on top right corner of the card-toolbar
    DataTableOrders.on( 'draw', function(){
        updateMultipleRowsSelection();
        KTMenu.createInstances();
    });
    
    
}









// Convert the timestamp stored in the database created_at and updated_at columns into a human readable singapore timezone format
function dbTimestampToSingaporeTimestampWithAgo( dbTimestamp ){
    var m = moment(dbTimestamp);
    var localTimestamp = m.local();        // Convert the Date from DB into local format where it is being viewed
    //var displayFormat = localTimestamp.format( 'ddd MMM DD YYYY HH:mm ZZ' );
    var displayFormat = localTimestamp.format( 'ddd MMM DD YYYY HH:mm ' );
    var ago = '<span class="badge rounded badge-primary float-end">' + localTimestamp.fromNow() + '</span>';
    
    var dateString = displayFormat + " " + ago;
    //console.log(displayFormat);
    //console.log(m._d.getTimezoneOffset());
    return dateString;
}


function showDataTableLoading(){
    var e_table_loading = $( '.orders-table-loading' );
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