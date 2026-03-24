var view_users_table;
var confirmDeleteUser;// = new ConfirmModal( "Caution", "Are you sure you want to delete this user ? All information associated with this user account will be deleted permanently and cannot be reverted !", "Yes", "No" );


$( document ).ready(function(){
    confirmDeleteUser = new ConfirmModal( "Caution", "Are you sure you want to delete this user ? All information associated with this user account will be deleted permanently and cannot be reverted !", "Yes", "No" );
    view_users_table = $( '#view-users-table' ).DataTable({});
});


var editUserPageApp = angular.module( 'editUserPageApp', [] );
var editUserPageCtrl = editUserPageApp.controller( 'editUserPageCtrl', function( $scope, $compile ){
	
    /*$scope.edit_fname = "";
    $scope.edit_lname = "";
    $scope.edit_nickname = "";
    $scope.edit_email = "";*/
    $scope.user_id = "";

    $scope.editUser = function( user_id, thees ){
        // post( 'adminpanel.html?what_do_you_want=edit_user_page', { 'user_id' : user_id } );
        showLoading( "loading_user_info" );
        hideMe( "content", "class" );

        $scope.user_id = user_id;
        $( '.password_reset_message' ).text( '' );

        $.ajax({
            url : 'webservice.php',
            type : 'POST',
            data : { 'what_do_you_want' : 'user_info', 'user_id' : user_id.trim() },
            success : function( returned_data ){
                // console.log( returned_data );
                hideLoading( "loading_user_info" );
                showMe( "content", "class" );

                var jSon = parseJSON( returned_data, "JSON Error occurred !" )
                if( jSon == false ){ 
                    $( '.content' ).html( "<p style='color:red; text-align:center; font-weight: bold;'>JSON Error occurred !</p>" );
                    return;
                }

                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == "error" ){
                    //showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
                    $( '.content' ).html( "<p style='color:red; text-align:center'; font-weight: bold;>"+jSon[ 'info' ]+"</p>" );
                    return;
                }
                if( jSon[ 'type' ] == "success" ){
                    var json = $.parseJSON( jSon[ 'info' ] );
                    json = json[ 0 ];

                    $scope.edit_fname = json[ 'fname' ];
                    $scope.edit_lname = json[ 'lname' ];
                    $scope.edit_nickname = json[ 'nickname' ];
                    $scope.edit_email = json[ 'email' ];

                    $( '#edit_user_id' ).html( "<b>"+user_id+"</b>" );
                    

                    $( '#edit_fname' ).val( json[ 'fname' ] );
                    $( '#edit_lname' ).val( json[ 'lname' ] );
                    $( '#edit_nickname' ).val( json[ 'nickname' ] );
                    $( '#edit_email' ).val( json[ 'email' ] );
                    $( '#edit_role_id' ).val( json[ 'role_id' ] );
                    $( '#view_registered_on' ).text( millisToHumanReadableDate( json[ 'registered_on' ] ) );
                    $( '#edit_reset_password' ).attr( 'data-user-id', $scope.user_id );

                    return;
                }

            }
        });
    }

    $scope.updateUser = function( form ){
            // console.log( form );

        if( form.$invalid ){
            showNotification( "error", "bottomRight", "Please fix the errors and then try to Update !", 5000, 1 );
            return;
        }

        var fsa = new FormSubmitAnimator( 'submit_form_edit_user' );
        fsa.showLoading();

        $.ajax({
            url:'webservice.php',
            type:'POST',
            data:  $( '#form_edit_user' ).serialize() + "&what_do_you_want=update_user&user_id="+$scope.user_id,
            success: function( returned_data ){
                // console.log( returned_data );
                fsa.hideLoading();

                var jSon = parseJSONWithError( returned_data, "Server Side Error. Please contact the Administrator !" );
                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == "error" ){
                    showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
                    return;
                }
                if( jSon[ 'type' ] == "success" ){
                    showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );

                    var fname = $( '#view-users-table > tbody > tr td[data-user_id="'+$scope.user_id+'"]' ).next();

                    // Updating the DataTable Cell in order for the results to be available in search
                    var cell = view_users_table.cell( fname );
                    cell.data( $scope.edit_fname ).draw();

                    view_users_table.cell( fname.next() ).data( $scope.edit_lname );
                    view_users_table.cell( fname.next().next() ).data( $scope.edit_email );
                    view_users_table.cell( fname.next().next().next() ).data( $( '#edit_role_id option[value="'+$( '#edit_role_id' ).val()+'"]' ).text() );

                    return;
                }

            }
        });

    }

    $scope.deleteUser = function( user_id, id ){
        confirmDeleteUser.showConfirm();

        confirmDeleteUser.positiveFunction = function(){
            deleteUSER( user_id, id );
        };
    }

    $scope.resetOthersPassword = function(){
        var val_user_id = $( '#edit_reset_password' ).attr( 'data-user-id' );

        var fsa = new FormSubmitAnimator( 'edit_reset_password' );
        fsa.showLoading();

        $.ajax({
            url:'webservice.php',
            type:'POST',
            data:  { "what_do_you_want" : "reset_others_password", "user_id" : val_user_id, "email" : $scope.edit_email },
            success: function( returned_data ){
                console.log( returned_data );
                fsa.hideLoading();

                var jSon = parseJSONWithError( returned_data, "Server Side Error. Please contact the Administrator !" );
                jSon = jSon[ 0 ];

                if( jSon[ 'type' ] == "error" ){
                    //showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
                    $( '.password_reset_message' ).text( jSon[ 'info' ] );

                    return;
                }
                if( jSon[ 'type' ] == "success" ){
                    //showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
                    $( '.password_reset_message' ).text( jSon[ 'info' ] );

                    return;
                }

            }
        });
    }
	
	
});




function deleteUSER( user_id, id ){
	disableIt( id ); 
	
	// Show Loading Modal
	var ipmodal = new iProgessModal( "Deleting User..." );
	ipmodal.showProgress();
	
	$.ajax({
    	url:'webservice.php',
    	type:'POST',
    	data:  { what_do_you_want : "delete_user", "user_id" : user_id },
    	success: function( returned_data ){
    		console.log( returned_data );
    		
    		// Hide Loading Modal
    		ipmodal.hideProgress();
    		enableIt( id ); 
    		
    		var jSon = parseJSONWithError( returned_data, "Server Side Error. Please contact the Administrator !" );
    		jSon = jSon[ 0 ];
    		
			if( jSon[ 'type' ] == "error" ){
				showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
				
				return;
			}
			if( jSon[ 'type' ] == "success" ){
				showNotification( "success", "bottomRight", jSon[ 'info' ], 5000, 1 );
				
				view_users_table.row( $( '#'+id ).parent().parents( 'tr' ) ).remove().draw();
				
				return;
			}
    		
    	}
    });
}



function viewUserInfo( user_id_selected, thees ){
	
	showLoading( "loading_user_info" );
	hideMe( "content", "class" );
	
	$.ajax({
		url : 'webservice.php',
		type : 'POST',
		data : { 'what_do_you_want' : 'user_info', 'user_id' : user_id_selected.trim() },
		success : function( returned_data ){
			// console.log( returned_data );
			hideLoading( "loading_user_info" );
			showMe( "content", "class" );
			
			var jSon = parseJSON( returned_data, "JSON Error occurred !" )
			if( jSon == false ){ 
				$( '.content' ).html( "<p style='color:red; text-align:center; font-weight: bold;'>JSON Error occurred !</p>" );
				return;
			}
			
			jSon = jSon[ 0 ];
			
			if( jSon[ 'type' ] == "error" ){
				//showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 );
				$( '.content' ).html( "<p style='color:red; text-align:center'; font-weight: bold;>"+jSon[ 'info' ]+"</p>" );
				return;
			}
			if( jSon[ 'type' ] == "success" ){
				var json = $.parseJSON( jSon[ 'info' ] );
				json = json[ 0 ];
				
				$( '#view_user_id' ).text( user_id_selected );
				$( '#view_fname' ).text( (json[ 'fname' ]=="")?"--":json[ 'fname' ] );
				$( '#view_lname' ).text( (json[ 'lname' ]=="")?"--":json[ 'lname' ] );
				$( '#view_nickname' ).text( (json[ 'nickname' ]=="")?"--":json[ 'nickname' ] );
				$( '#view_email' ).text( (json[ 'email' ]=="")?"--":json[ 'email' ] );
				$( '#view_role_name' ).text( json[ 'role_name' ] );
				$( '#view_registered_on' ).text( millisToHumanReadableDate( json[ 'registered_on' ] ) );
			
				return;
			}
		
		}
	});
}

function getUsers(){
	post( 'adminpanel'+$( '#website_link_ends_with' ).val()+'?what_do_you_want=view_users_page', { 'selected_role_id' : $( '#selected_role_id' ).val() } );
}

$( '#user_search' ).on( 'keypress', function( e ){
	if( e.keyCode == 13 ){ // Enter press
		post( 'adminpanel'+$( '#website_link_ends_with' ).val()+'?what_do_you_want=view_users_page', { 'user_search' : $( '#user_search' ).val() } );
	}
});

$( '#button_next' ).on( 'click', function(){
	post( 'adminpanel'+$( '#website_link_ends_with' ).val()+'?what_do_you_want=view_users_page', { 'start' : $( this ).data( 'start' ), 'end' : $( this ).data( 'end' ), 'selected_role_id' : $( '#selected_role_id' ).val(), 'user_search' : $( '#user_search' ).val() }  );
});

$( '#button_prev' ).on( 'click', function(){
	post( 'adminpanel'+$( '#website_link_ends_with' ).val()+'?what_do_you_want=view_users_page', { 'start' : $( this ).data( 'start' ), 'end' : $( this ).data( 'end' ), 'selected_role_id' : $( '#selected_role_id' ).val(), 'user_search' : $( '#user_search' ).val() }  );
});