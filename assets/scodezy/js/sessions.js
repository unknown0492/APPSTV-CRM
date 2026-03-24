var session_check_interval = "";
$( document ).ready(function(){
    // console.log( 'inside sessions' );
    
    startSessionCheck();
    
    
    
});

function startSessionCheck(){
    session_check_interval = setInterval( function(){
        send_request_to_validate_session();
    }, 60000 );
} 


function send_request_to_validate_session(){
    var data = {
        what_do_you_want: 'scodezy_validate_user_session'
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
                //showNotification( "error", "bottomRight", jSon[ 'info' ], 5000, 1 )
                //showSimpleToast( "error", jSon[ 'info' ] );
                //showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                //console.log( jSon[ 'info' ] );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var data = jSon[ 'info' ];
                
                // Force Logout
                if( data.session_status == "0" ){
                    clearInterval( session_check_interval );
                    showActionSweetAlert( data.info, "error", false, "OK", "btn btn-primary", function(){
                        redirect( data.login_url );
                    });
                }
                else if( data.session_status == "1" ){      // Seek confirmation for extension of the session
                    clearInterval( session_check_interval );
                    
                    Swal.fire({
                        icon: "question",
                        allowOutsideClick: false,
                        confirmButtonText: "Continue",
                        showconfirmButton: true,
                        showDenyButton: true,
                        denyButtonText: "Sign Out",
                        focusConfirm: true,
                        html: data.info, //+ "<br /><b class='fs-1' style='line-height: 3rem; '></b>", // padding: 18px; border: 1px solid gray; border-radius: 50px;
                        timer: parseInt( data.remaining_time ),
                        timerProgressBar: true,
                        customClass: {
                            confirmButton: "btn btn-primary",
                            denyButton: "btn btn-danger"
                        },
                        didOpen: () => {
                            const timer = Swal.getPopup().querySelector("b");
                            timerInterval = setInterval(() => {
                              timer.textContent = `${Math.floor(Swal.getTimerLeft()/1000)}` + 's';
                            }, 1000);
                          },
                        willClose: () => {
                            //redirect( data.login_url );
                            //startSessionCheck();
                        }
                    }).then(( result ) => {
                        if (result.dismiss === Swal.DismissReason.timer) { // When the timer ends
                            sign_out();
                        }
                        else if ( result.isConfirmed ) {
                            //console.log( 'confirmed' );
                            startSessionCheck();
                            
                            // Send request to API to extend session
                            extend_session();
                        }
                        else if( result.isDenied ){
                            //console.log( 'denied' );
                            startSessionCheck();
                            
                            // Call the Logout API and redirect to the Login Page
                            sign_out();
                        }
                    });
                }
                
                return;
            }
        }
    });
}

function sign_out(){
    var data = {
        what_do_you_want: 'scodezy_sign_out'
    };
    showPageLoading( "Please wait..." );
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        success: function( returned_data ){
           //console.log( returned_data );
           
           hidePageLoading( "Please wait..." );
            
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                //console.log( jSon[ 'info' ] );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var data = jSon[ 'info' ];
                
                setTimeout( function(){
                    redirect( data.login_url );
                }, 1000 );
                
                return;
            }
        }
    });
}

function scodezy_sign_out(){
    var data = {
        what_do_you_want: "scodezy_sign_out",
        refresh_token: getDataFromLocalStorage( RTN )
    };
    
    // Show Loading Animation on the Screen
    showLoadingSweetAlert( "Signing Out", "Please wait while you are being signed out of the system", false );

    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
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
                showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var data = jSon[ 'info' ];
                
                // Clear the RTN
                removeDataFromLocalStorage( RTN );
                
                showSimpleSweetAlert( data.info, "success", "", "" );
                setTimeout(function(){
                    redirect( data.login_url );
                }, 1500 );

                return;
            }

        }
    });
}

function extend_session(){
    var data = {
        what_do_you_want: "scodezy_extend_session",
        refresh_token: getDataFromLocalStorage( RTN )
    };
    
    // Show Loading Animation on the Screen
    showLoadingSweetAlert( "Extending Session", "Please wait while your session is being extended", false );

    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
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
                showSimpleSweetAlert( jSon[ 'info' ], "error", "Try Again", "btn btn-primary" );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var data = jSon[ 'info' ];
                
                showSimpleToast( "success", data.info );
                
                // Update the RTN
                removeDataFromLocalStorage( RTN );                  // Delete old refresh token
                storeDataIntoLocalStorage( RTN, data[ RTN ] );      // Store new refresh token
                /*
                showSimpleSweetAlert( data.info, "success", "", "" );
                setTimeout(function(){
                    redirect( data.login_url );
                }, 1500 );
*/
                return;
            }

        }
    });
}

function extend_session1(){
    var data = {
        what_do_you_want: 'scodezy_extend_session'
    };
    
    showPageLoading( "Please wait..." );
    
    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        success: function( returned_data ){
           //console.log( returned_data );
            
            hidePageLoading( "Please wait..." );
            
            var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
            if( jSon == false ){
                return;
            }

            jSon = jSon[ 0 ];

            if( jSon[ 'type' ] == 'error' ){
                //console.log( jSon[ 'info' ] );
                return;
            }
            if( jSon[ 'type' ] == 'success' ){

                var data = jSon[ 'info' ];
                
                setTimeout( function(){
                    //redirect( data.login_url );
                }, 1000 );
                
                return;
            }
        }
    });
}

function showPageLoading( loading_message, overlay = true ){
    const loadingEl = document.createElement("div");
    document.body.prepend(loadingEl);
    loadingEl.classList.add("page-loader");
    loadingEl.classList.add("flex-column");
    loadingEl.classList.add("bg-dark");
    loadingEl.classList.add("bg-opacity-25");
    loadingEl.innerHTML = `
        <span class="spinner-border text-primary" role="status"></span>
        <span class="text-gray-800 fs-6 fw-semibold mt-5">`+loading_message+`</span>
    `;

    // Show page loading
    KTApp.showPageLoading();
}

function hidePageLoading(){
    KTApp.hidePageLoading();
}