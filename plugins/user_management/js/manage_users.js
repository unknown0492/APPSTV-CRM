// Card Toolbar
var e_select_role;
var btn_open_create_modal;
var btn_delete_selected_rows;

// Card Body
var _table_name;                // The name of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
var _table;
var DataTable;
var DataTableParameters;
var loadingEl;                  // Loading div for the DataTable
var e_table;

// Create Modal
var modal_create;
var form_create_name;
var form_create;
var e_user_id_c;
var e_user_id_availability_msg;
var e_password_c;
var e_email_c;
var e_fname_c;
var e_lname_c;
var e_nickname_c;
var e_select_role_c;
var e_check_email_credentials_c;
var btn_generate_password_c;
var btn_refresh_roles_c;
var btn_reset_create;
var btn_submit_create;

// Edit Modal
var modal_edit;
var form_edit_name;
var form_edit;
var e_div_user_id_e;
var e_hidden_user_id_e;
var e_password_e;
var e_password_reset_message;
var e_email_e;
var e_fname_e;
var e_lname_e;
var e_nickname_e;
var e_select_role_e;
var btn_reset_password_e;
var btn_refresh_roles_e;
var btn_cancel_edit;
var btn_submit_update;

// View Modal
var modal_view;
var form_view_name;
var form_view;
var e_user_id_v;
var e_email_v;
var e_role_v;
var e_fname_v;
var e_lname_v;
var e_nickname_v;
var e_registered_on_v;
var e_activation_status_v;
var e_activated_on_v;
var btn_close_view_modal;

$( document ).ready(function(){
    
    // Initiate all the DOM elements only once
    initDomElements();
    
    // All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
    initDataTable();
    
    // This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
    multipleRowsSelection();
    
    // Event listener for the Delete Selected button to delete multiple rows from the DataTable at once
    deleteSelectedRows();
    
    // Retrieve all the roles from API into the dropdown, so that we can select a role to view the users that belongs to it, in the DataTable
    loadRolesIntoDropdown();
    
    // Setting EventListener for change event on the Select Role dropdown, so that when its value is selected, it will deliver a call to API to get roles for that plugin
    selectRoleToViewItsUsers();
    
    // Initialiaze the Create User modal reset button, cancel button, open modal button, and refresh select role dropdown, and submit button
    initCreateUserModal();
    
    // Initialize the cancel button for the Edit User modal
    initEditUserModal();
    
    // Initialize the reset password button on the Edit User modal, to reset any user's password
    reset_password();
    
    // Send the data to the API for the user whose information is to be updated, from the Edit User modal
    update_user();
    
    // Initialize the View User information modal
    initViewUserModal();
    
});


// This function is present inside the onClick attribute of each row of the Users Listing datatable
function viewUser( thees, event ){
    event.preventDefault();
    
    // Reset all the td
    modal_view;
    form_view_name;
    form_view;
    e_user_id_v.text( '' );
    e_email_v.text( '' );
    e_role_v.text( '' );
    e_fname_v.text( '' );
    e_lname_v.text( '' );
    e_nickname_v.text( '' );    
    e_registered_on_v.attr( 'data-timestamp', '' );
    e_registered_on_v.text( '' );    
    e_activation_status_v.text( '' );    
    e_activated_on_v.attr( 'data-timestamp', '' );
    e_activated_on_v.text( '' );
    
    var val_user_id = $( thees ).parent().parent( 'tr' ).data( 'user-id' );
    
    // Show Loading Dialog while loading the page data from the API
    showLoadingSweetAlert( "Loading", "Please wait while the User Information is being retrieved" );
    
    var data = {
        what_do_you_want: 'scodezy_get_user',
        user_id: val_user_id,
        user_meta_required: "1"
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
                //console.log( data );
                
                e_user_id_v.text( data.user_id );
                e_email_v.text( data.email );
                e_role_v.text( data.role_name );
                e_fname_v.text( data.fname );
                e_lname_v.text( data.lname );
                e_nickname_v.text( data.nickname );
                e_registered_on_v.attr( 'data-timestamp', data.registered_on );
                e_registered_on_v.text( millisToHumanReadableDate( data.registered_on ) );
                
                // user_meta
                var user_meta = data.user_meta;
                e_activation_status_v.text( "Status Unavailable" );
                e_activated_on_v.text( 'Unavailable' );
                if( user_meta.length != 0 ){
                    //console.log( typeof user_meta._new_account_activation_status );
                    if( typeof user_meta._new_account_activation_status != "undefined" )
                        e_activation_status_v.text( (user_meta._new_account_activation_status).toUpperCase() );
                    //else
                    //    e_activation_status_v.text( "STATUS UNAVAILABLE" );
                    
                    //console.log( typeof user_meta._new_account_activation_timestamp );
                    if( typeof user_meta._new_account_activation_timestamp != "undefined" ){
                        e_activated_on_v.attr( 'data-timestamp', user_meta._new_account_activation_timestamp );
                        e_activated_on_v.text( millisToHumanReadableDate( user_meta._new_account_activation_timestamp ) );
                    }
                    //else{
                    //    e_activated_on_v.text( 'UNAVAILABLE' );
                    //}
                }
                
                modal_view.modal( 'show' );
                
                return;
            }
        }
    });
}

// Initialize the View User information modal
function initViewUserModal(){
    btn_close_view_modal.on( 'click', function(){
        
        e_user_id_v.text( '' );
        e_email_v.text( '' );
        e_role_v.text( '' );
        e_fname_v.text( '' );
        e_lname_v.text( '' );
        e_nickname_v.text( '' );

        e_registered_on_v.attr( 'data-timestamp', '' );
        e_registered_on_v.text( '' );

        e_activation_status_v.text( '' );

        e_activated_on_v.attr( 'data-timestamp', '' );
        e_activated_on_v.text( '' );
        
        modal_view.modal( 'hide' );
    });
}

// Send the data to the API for the user whose information is to be updated, from the Edit User modal
function update_user(){
    btn_submit_update.on( 'click', function(){
        
        var parsley_user_id     = e_hidden_user_id_e.parsley();
        var parsley_email       = e_email_e.parsley();
        var parsley_fname       = e_fname_e.parsley();
        var parsley_lname       = e_lname_e.parsley();
        var parsley_nickname    = e_nickname_e.parsley();
        
        var val_role_id             = e_select_role_e.val();
        /*
        //console.log( "Visible : " + val_page_is_visible );
        //console.log( "Plugin : " + val_plugin );
        //console.log( "Functionality : " + val_functionality );
        //console.log( "Hierarchy : " + val_hierarchy );
        //console.log( "Parent : " + val_parent_page );
        */
       
        if( !parsley_user_id.isValid() ){
            showSimpleToast( "error", "Please check the User ID and try again !" );
            return;
        }
        if( !parsley_email.isValid() ){
            showSimpleToast( "error", "Please check the email and try again !" );
            return;
        }
        if( !parsley_fname.isValid() ){
            showSimpleToast( "error", "Please check the first name and try again !" );
            return;
        }
        if( !parsley_lname.isValid() ){
            showSimpleToast( "error", "Please check the last name and try again !" );
            return;
        }
        if( !parsley_nickname.isValid() ){
            showSimpleToast( "error", "Please check the nickname and try again !" );
            return;
        }
        if( (val_role_id == "") || (typeof val_role_id == "undefined") ){
            showSimpleToast( "error", "Please select a role from the dropdown !" );
            return;
        }
        
        var val_user_id     = e_hidden_user_id_e.val();        
        var val_email       = e_email_e.val();
        var val_fname       = e_fname_e.val();
        var val_lname       = e_lname_e.val();
        var val_nickname    = e_nickname_e.val();
        
        // Show Loading Animation on the Button
        btn_submit_update.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit_update );

        var data = {
            what_do_you_want: "scodezy_update_user",
            user_id: val_user_id,
            email: val_email,
            role_id: val_role_id,
            fname: val_fname,
            lname: val_lname,
            nickname: val_nickname
        };

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );

                // Show Loading Animation on the Button
                btn_submit_update.attr( 'data-kt-indicator', 'off' );
                enableFormElement( btn_submit_update );

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
                    // Update this entry onto the DataTable
                    var value = data.data;

                    var tr = createRowForDataTable( value ); 
                    var trToBeRemoved = e_table.find( 'tr[data-user-id="'+val_user_id+'"]' );
                    DataTable.row( trToBeRemoved ).remove().draw();
                    if( (e_select_role.val() == value.role_id) || (e_select_role.val() == "-1") ){               
                        DataTable.row.add( $( tr ) ).draw();
                    }
                    
                    showSimpleToast( "success", data.info );
                    //showActionSweetAlert( data.info, "success", "Thank you", "btn btn-primary", function(){} );
                    
                    modal_edit.modal( 'hide' );
                    
                    setTimeout( function(){
                        // Close the modal and reset the form
                        btn_cancel_edit.trigger( 'click' );
                    }, 1000 );

                    return;
                }

            }
        });
        
    });
}

// Initialize the reset password button on the Edit User modal, to reset any user's password
function reset_password(){
    btn_reset_password_e.on( 'click', function(){
        
        
        var val_user_id = e_hidden_user_id_e.val();
        if( val_user_id.trim() == "" ){
            showSimpleToast( "error", "User ID is required to reset the user's password " );
            return;
        }
        
        // Show Loading Animation on the Button
        btn_reset_password_e.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_reset_password_e );
        
        var data = {
            what_do_you_want: "scodezy_reset_user_password",
            user_id: val_user_id
        };

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );

                // Show Loading Animation on the Button
                btn_reset_password_e.attr( 'data-kt-indicator', 'off' );
                enableFormElement( btn_reset_password_e );

                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    e_password_reset_message.text( jSon[ 'info' ] );
                    e_password_reset_message.addClass( 'text-error' );
                    //showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );                    
                    //showSimpleToast( "error", jSon[ 'info' ] );                    
                    //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];
                    e_password_reset_message.text( data );
                    e_password_reset_message.addClass( 'text-success' );
                    
                    return;
                }



            }
        });
    });
}

// This function is inside the onClick attribute of each row of the Users Listing datatable
function editUser( thees, event ){
    event.preventDefault();
    
    var val_user_id = $( thees ).parent().parent( 'tr' ).data( 'user-id' );
    //console.log( val_user_id );
    
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#' + form_edit_name );
    
    // Clear the Modal Form and reset all values
    btn_refresh_roles_e.trigger( 'click' );
    e_password_reset_message.text( '' );
        
    scodezyForm.getParsleyForm().reset();
    scodezyForm.getForm().trigger( "reset" );
    
    // Show Loading Dialog while loading the page data from the API
    showLoadingSweetAlert( "Loading", "Please wait while the User Information is being retrieved" );
    
    var data = {
        what_do_you_want: 'scodezy_get_user',
        user_id: val_user_id
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
                
                modal_edit.modal( 'show' );
                
                e_hidden_user_id_e.val( data.user_id );
                e_div_user_id_e.text( data.user_id );
                e_email_e.val( data.email );
                e_select_role_e.val( data.role_id );
                e_select_role_e.trigger( 'change.select2' );
                e_fname_e.val( data.fname );
                e_lname_e.val( data.lname );
                e_nickname_e.val( data.nickname );
                
                return;
            }
        }
    });
}

// Initialize the cancel button for the Edit User modal
function initEditUserModal(){
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#' + form_edit_name );
    
    
    // Reset the form and close the modal
    btn_cancel_edit.on( 'click', function( e ){        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        // Hide the password reset message string
        e_password_reset_message.text( '' );
        
        e_select_role_e.val( '' );
        e_select_role_e.trigger( 'change.select2' );
        
        modal_edit.modal( 'hide' );
    });
    
    refresh_plugins_in_dropdown( 'e' );
}

// This function is inside the onClick attribute of each row of the Users Listing datatable
function deleteUser( thees, event ){
    event.preventDefault();
    
    var tr = $( thees ).parent().parent( 'tr' );
    var val_user_id = tr.data( 'user-id' );
    ////console.log( val_page_id );
    
    // Show a confirmation SweetActionAlert to confirm the delete operation
    showConfirmSweetAlert( "Confirm", "Are you sure you want to delete the User '" + val_user_id + "'" , "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
        ////console.log( 'yes clicked' );
        
        showLoadingSweetAlert( "Deleting", "Please wait while the user is being deleted" );
        
        var data = {
            what_do_you_want: "scodezy_delete_user",
            user_id: val_user_id
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
                    
                    // Delete this entry from the users DataTable if it belongs to the currently selected role
                    if( (e_select_role.val() == value.role_id) || (e_select_role.val() == "-1") ){
                        DataTable.row( tr ).remove().draw();
                    }
                    
                    showSimpleToast( "success", data.info );
                    
                    return;
                }
            }
        });
    });
}

// Check if the user id is available as soon as the user types it
function checkIfUserIDIsAvailable(){    
    e_user_id_c.on( 'keyup', function(){
        e_user_id_availability_msg.text( '' ); 
        e_user_id_availability_msg.removeClass( 'text-danger text-success' ); 
    });
    
    e_user_id_c.on( 'blur', function(){
        
        var val_user_id     = e_user_id_c.val();
        if( val_user_id.length < 3 ){
            return;
        }
        
        var parsley_user_id = e_user_id_c.parsley();
        
        if( !parsley_user_id.isValid() ){
            return;
        }
        
        var data = {
            what_do_you_want: 'scodezy_is_user_id_available',
            user_id: val_user_id
        };
        
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                ////console.log( returned_data );
                
                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){                    
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == 'error' ){
                    showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 0 );                    
                    return;
                }
                if( jSon[ 'type' ] == 'success' ){

                    var data = jSon[ 'info' ];
                    e_user_id_availability_msg.text( data.info );
                    
                    if( data.is_available == "0" ){                        
                        e_user_id_availability_msg.addClass( 'text-danger' );
                        e_user_id_c.removeClass( 'is-valid' );
                        e_user_id_c.addClass( 'is-invalid' );
                    }
                    else if( data.is_available == "1" ){
                        e_user_id_availability_msg.addClass( 'text-success' );
                        e_user_id_c.addClass( 'is-valid' );
                        e_user_id_c.removeClass( 'is-invalid' );
                    }

                    return;
                }
            }
        });
    });
}

// Send the form data to the API to create the new user
function create_user(){
    // Submit the data to the API
    btn_submit_create.on( 'click', function(){
        var parsley_user_id     = e_user_id_c.parsley();
        var parsley_password    = e_password_c.parsley();
        var parsley_email       = e_email_c.parsley();
        var parsley_fname       = e_fname_c.parsley();
        var parsley_lname       = e_lname_c.parsley();
        var parsley_nickname    = e_nickname_c.parsley();
        
        var val_send_credentials    = e_check_email_credentials_c.is( ':checked' )?"1":"0";
        var val_role_id             = e_select_role_c.val();
        /*
        //console.log( "Visible : " + val_page_is_visible );
        //console.log( "Plugin : " + val_plugin );
        //console.log( "Functionality : " + val_functionality );
        //console.log( "Hierarchy : " + val_hierarchy );
        //console.log( "Parent : " + val_parent_page );
        */
       
        if( !parsley_user_id.isValid() ){
            showSimpleToast( "error", "Please check the User ID and try again !" );
            return;
        }
        if( !parsley_password.isValid() ){
            showSimpleToast( "error", "Please check the password and try again !" );
            return;
        }
        if( !parsley_email.isValid() ){
            showSimpleToast( "error", "Please check the email and try again !" );
            return;
        }
        if( !parsley_fname.isValid() ){
            showSimpleToast( "error", "Please check the first name and try again !" );
            return;
        }
        if( !parsley_lname.isValid() ){
            showSimpleToast( "error", "Please check the last name and try again !" );
            return;
        }
        if( !parsley_nickname.isValid() ){
            showSimpleToast( "error", "Please check the nickname and try again !" );
            return;
        }
        if( (val_role_id == "") || (typeof val_role_id == "undefined") ){
            showSimpleToast( "error", "Please select a role from the dropdown !" );
            return;
        }
        
        var val_user_id     = e_user_id_c.val();
        var val_password    = e_password_c.val();
        var val_email       = e_email_c.val();
        var val_fname       = e_fname_c.val();
        var val_lname       = e_lname_c.val();
        var val_nickname    = e_nickname_c.val();
        
        // Show Loading Animation on the Button
        btn_submit_create.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_submit_create );

        var data = {
            what_do_you_want: "scodezy_create_user",
            user_id: val_user_id,
            password: val_password,
            email: val_email,
            role_id: val_role_id,
            fname: val_fname,
            lname: val_lname,
            nickname: val_nickname,
            send_credentials: val_send_credentials
        };

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: data,
            success: function( returned_data ){
                //console.log( returned_data );

                // Show Loading Animation on the Button
                btn_submit_create.attr( 'data-kt-indicator', 'off' );
                enableFormElement( btn_submit_create );

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
                    if( (e_select_role.val() == value.role_id) || (e_select_role.val() == "-1") ){
                        var tr = createRowForDataTable( value );
                        DataTable.row.add( $( tr ) ).draw();
                    }
                    
                    showActionSweetAlert( data.info, "success", "Thank you", "btn btn-primary", function(){} );
                    
                    // Close the modal and reset the form
                    modal_create.modal( 'hide' );
                    btn_reset_create.trigger( 'click' );
                    
                    return;
                }



            }
        });



        
    });
}

// Load the list of plugins from API into the Select Role dropdown on Create modal and Edit modal based on the function input parameter c or e
function refresh_plugins_in_dropdown( c_or_e ){
    var btn_refresh = btn_refresh_roles_c;
    var e_select_role = e_select_role_c;
    if( c_or_e == 'e' ){
        btn_refresh = btn_refresh_roles_e;
        e_select_role = e_select_role_e;
    }
    
    btn_refresh.on( 'click', function(){
        
        btn_refresh.attr( 'data-kt-indicator', 'on' );
        disableFormElement( btn_refresh );
        
        get_roles(function( returned_data ){

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
                $.each( data.data, function( i, v ){
                    html += createRoleInformationOptionElementForSelectTag( v );
                });

                e_select_role.html( html );

                //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
                //    redirect( data.login_url ); 
                //});

                return;
            }
        });
    });
    
    //btn_refresh_plugins_c.trigger( 'click' );
}

// Initialiaze the Create User modal reset button, cancel button, open modal button, and refresh select role dropdown, and submit button
function initCreateUserModal(){
    var scodezyForm     = new ScodezyForm();
    scodezyForm.initForm( '#' + form_create_name );
    
    checkIfUserIDIsAvailable();
    
    // Open the Modal
    btn_open_create_modal.on( 'click', function(){
        btn_refresh_roles_c.trigger( 'click' );
        
        btn_reset_create.trigger( 'click' );        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        e_password_c.val( generateRandomString( 10 ) );
        
        modal_create.modal( 'show' );
    });
    
    // Reset the form and close the modal
    btn_reset_create.on( 'click', function( e ){        
        scodezyForm.getParsleyForm().reset();
        scodezyForm.getForm().trigger( "reset" );
        
        e_user_id_availability_msg.text( '' );
        var password = generateRandomString( 10 );
        
        e.preventDefault();
        
        e_password_c.val( password );
        e_select_role_c.val( '' );
        e_select_role_c.trigger( 'change.select2' );
    });
    
    btn_generate_password_c.on( 'click', function(){
        e_password_c.val( generateRandomString( 10 ) );
    });
    
    refresh_plugins_in_dropdown( 'c' );
    create_user();
    
}

// Creates an HTML Element for the Table Row (tr) for the user that have been receveived from the API
function createRowForDataTable( value ){
    var html = '<tr data-user-id="'+value.user_id+'">\n';
    html += '<td><div class="form-check form-check-solid form-check-sm">\n' +
                    '<input class="form-check-input table-children-checkbox" type="checkbox" value="" />\n' +                                         
                '</div>\n';
            '</td>\n';
    html += '<td>' + value.user_id + '</td>\n';
    html += '<td>' + value.fname + '</td>\n';
    html += '<td>' + value.lname + '</td>\n';
    html += '<td>' + value.email + '</td>\n';
    html += '<td>' + value.role_name + '</td>\n';
    
    html += '<td>'+
                '<a href="#" class="" title="View User" onclick="viewUser(this,event);"><i class="fa-solid fa-eye fs-2x text-info"></i></a>\n'+ 
                '<a href="#" class="ms-4" title="Edit User" onclick="editUser(this,event);"><i class="fa-regular fs-2x text-primary fa-pen-to-square"></i></a> \n'+
                '<a href="#" class="ms-3" title="Delete User" onclick="deleteUser(this,event);"><i class="bi bi-trash fs-2x text-danger"></i></a>\n'+
            '</td>\n';
    html += '</tr>\n';
    
    return html;
}

// Make an Ajax call to get all the users from API, and passing a callback function to be executed when the result of AJAX is received
function get_users( callback, url_parameters = "" ){    
    var data = "";
    if( url_parameters == "" ){
        data = {
            what_do_you_want: 'scodezy_get_users'
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

// Setting EventListener for change event on the Select Role dropdown, so that when its value is selected, it will deliver a call to API to get users for that plugin
function selectRoleToViewItsUsers(){
    e_select_role.on( 'change', function( e ){
        
        var val_role_id = e_select_role.val();
        
        var data = {
            what_do_you_want: 'scodezy_get_users',
            role_id: val_role_id
        };
        
        get_users(function(){
            
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
                        e_select_role.val( '-1' );
                        e_select_role.trigger( 'change' );
                        return;
                    }
                    if( jSon[ 'type' ] == 'success' ){

                        var data = jSon[ 'info' ];

                        var html = "";
                        $.each( data.data, function( i, v ){
                            html += createRowForDataTable( v );
                        });

                        DataTable.destroy();
                        e_table.find( 'tbody' ).html( html );
                        initDataTable();

                        return;
                    }
                }
            });
        }, data );
        
    });
}

// Create HTML option element for select tag to display roles
function createRoleInformationOptionElementForSelectTag( value ){
    return '<option value="'+ value.role_id +'">'+ value.role_name +'</option>\n';    
}

// Make an Ajax call to get all the roles from API, and passing a callback function to be executed when the result of AJAX is received
function get_roles( callback ){    
    var data = {
        what_do_you_want: 'scodezy_get_roles'
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

// Retrieve all the roles from API into the dropdown, so that we can select a role to view the users that belongs to it, in the DataTable
function loadRolesIntoDropdown(){
    get_roles(function( returned_data ){
        
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
            ////console.log( data.info );
            ////console.log( data.data );
            var html = '<option></option>\n';
            html += '<option value="-1">All Roles</option>\n';
            $.each( data.data, function( i, v ){
                html += createRoleInformationOptionElementForSelectTag( v );
            });

            e_select_role.html( html );

            //showActionSweetAlert( data.info, "success", "Thank You !", "btn btn-primary", function(){
            //    redirect( data.login_url ); 
            //});

            return;
        }
    });
}

// Event listener for the Delete Selected button to delete multiple rows from the DataTable at once
function deleteSelectedRows(){
    var e_table                     = $( _table );
    
    $( '.group_actions' ).on( 'click', '.delete_selected_rows', function(){
        
        // Show a confirmation SweetActionAlert to confirm the delete operation
        showConfirmSweetAlert( "Confirm", "Are you sure you want to delete the selected users", "question", "Yes", "btn btn-danger", "Cancel", "btn btn-default", function(){
            ////console.log( 'yes clicked' );

            showLoadingSweetAlert( "Deleting", "Please wait while the users are being deleted" );
            
            var e_table_children_checkbox   = e_table.find( '.table-children-checkbox' );
            var val_users = [];
            $.each( e_table_children_checkbox, function( i, v ){
                var checked = $( v ).prop( 'checked' );
                if( checked )
                    val_users.push( $( v ).parent().parent().parent().attr( 'data-user-id' ) );
            });
            ////console.log( val_pages );
            
            var formData = new FormData();
            formData.append( 'what_do_you_want', 'scodezy_delete_users' );
            formData.append( 'user_ids', val_users );

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

                        var deleted_users = data.data;
                        
                        $.each( deleted_users, function( i, v ){
                            DataTable.row( e_table.find( 'tr[data-user-id="'+v.user_id+'"]' ) ).remove().draw();
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

// This is to maintain the multi-select state when the DataTables are navigated while some of the rows are checked and multi-selected
function updateMultipleRowsSelection(){
    var e_table_parent_checkbox     = e_table.find( '.table-parent-checkbox' );
    var e_group_actions             = $( '.group_actions[data-selected-table-name="'+_table_name+'"]' );
    var e_table_toolbar             = $( 'div[data-selected-table-toolbar="'+_table_name+'"]' );
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

// This is a custom feature to multi-select DataTable rows and then make a Delete Button appear on the Card Toolbar section along with selection count
function multipleRowsSelection(){
    var e_table                     = $( _table );
    var e_table_parent_checkbox     = e_table.find( '.table-parent-checkbox' );
    var e_group_actions             = $( '.group_actions[data-selected-table-name="'+_table_name+'"]' );
    var e_table_toolbar             = $( 'div[data-selected-table-toolbar="'+_table_name+'"]' );
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
    
    $( _table ).on( 'change', '.table-children-checkbox', function( e ){
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

// All the initialization of DataTable, along with initialization of Default parameters of DataTable are done here
function initDataTable(){
    
    DataTableParameters = {
        searching: true,
        language: {
            emptyTable: 'Select a role from the above dropdown to view its users'
        },        
        columnDefs: [
            {   // set default column settings
                orderable: false,
                targets: [0, 6]
            },
            {
                searchable: false,
                targets: [0, 6]
            },
            {
                className: "text-left",
                "targets": [2]
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
    
    
    DataTable = e_table.DataTable( DataTableParameters );
    
    // This is used for the purpose of Multi-selecting table rows to show the count on top right corner of the card-toolbar
    DataTable.on( 'draw', function(){
        updateMultipleRowsSelection();
    });
}

// Initiate all the DOM elements only once
function initDomElements(){
    // Card Toolbar
    e_select_role               = getElementByID( 'select_role' );
    btn_open_create_modal       = getElementByID( 'btn_open_create_user_modal' );
    btn_delete_selected_rows    = getElementByClass( 'delete_selected_rows' );

    // Card Body
    _table_name         = "table_users";                // The name of the table id that will have the datatables. Used multiple times at multiple places, so declared globally inside the file
    _table              = "#" + _table_name;
    DataTable           = null;
    DataTableParameters = null;
    loadingEl           = document.createElement("div");  // Loading div for the DataTable
    e_table             = getElementByID( 'table_users' );
    
    // Create Modal
    modal_create                    = getElementByID( 'modal_create_user' );
    form_create_name                = 'form_create_user';
    form_create                     = getElementByID( 'form_create_user' );
    e_user_id_c                     = getElementByID( 'input_user_id_c' );
    e_user_id_availability_msg      = getElementByID( 'user_id_availability_msg' );
    e_password_c                    = getElementByID( 'input_password_c' );
    e_email_c                       = getElementByID( 'input_email_c' );
    e_fname_c                       = getElementByID( 'input_fname_c' );
    e_lname_c                       = getElementByID( 'input_lname_c' );
    e_nickname_c                    = getElementByID( 'input_nickname_c' );
    e_select_role_c                 = getElementByID( 'select_role_c' );
    e_check_email_credentials_c     = getElementByID( 'check_email_credentials_c' );
    btn_generate_password_c         = getElementByID( 'btn_generate_password_c' );
    btn_refresh_roles_c             = getElementByID( 'btn_refresh_roles_c' );
    btn_reset_create                = getElementByID( 'btn_reset_create_user' );
    btn_submit_create               = getElementByID( 'btn_create_user' );
    
    // Edit Modal
    modal_edit                  = getElementByID( 'modal_edit_user' );
    form_edit_name              = 'form_edit_user';
    form_edit                   = getElementByID( 'form_edit_user' );
    e_div_user_id_e             = getElementByID( 'div_user_id_e' );
    e_hidden_user_id_e          = getElementByID( 'hidden_user_id_e' );
    btn_reset_password_e        = getElementByID( 'btn_reset_password_e' );
    e_password_reset_message    = getElementByID( 'password_reset_message' );
    e_email_e                   = getElementByID( 'input_email_e' );
    e_select_role_e             = getElementByID( 'select_role_e' );
    btn_refresh_roles_e         = getElementByID( 'btn_refresh_roles_e' );
    e_fname_e                   = getElementByID( 'input_fname_e' );
    e_lname_e                   = getElementByID( 'input_lname_e' );
    e_nickname_e                = getElementByID( 'input_nickname_e' );
    btn_cancel_edit             = getElementByID( 'btn_cancel_edit_user' );
    btn_submit_update           = getElementByID( 'btn_update_user' );
    
    // View Modal
    modal_view              = getElementByID( 'modal_view_user' );
    form_view_name          = 'form_view_user';
    form_view               = getElementByID( 'form_view_user' );
    e_user_id_v             = getElementByID( 'td_user_id_v' );
    e_email_v               = getElementByID( 'td_email_v' );
    e_role_v                = getElementByID( 'td_role_v' );
    e_fname_v               = getElementByID( 'td_fname_v' );
    e_lname_v               = getElementByID( 'td_lname_v' );
    e_nickname_v            = getElementByID( 'td_nickname_v' );
    e_registered_on_v       = getElementByID( 'td_registered_on_v' );
    e_activation_status_v   = getElementByID( 'td_activation_status_v' );
    e_activated_on_v        = getElementByID( 'td_activated_on_v' );
    btn_close_view_modal    = getElementByID( 'btn_close_view_user' );
}









function showDataTableLoading(){
    var e_table_loading = $( '.users-table-loading' );
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