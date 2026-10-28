var session_check_interval = "";
$( document ).ready(function(){
    // console.log( 'inside sessions' );

    startSessionCheck();

    SessionSync.init();

});

/**
 * Keeps the session popup in step across all open tabs of the same browser.
 * The token cookie is shared by every tab, but each tab runs its own popup countdown, so a tab
 * that extends or ends the session tells the others through a localStorage "storage" event
 * (fired in every other tab of the same origin, never in the sender).
 */
var SessionSync = {

    KEY: 'scodezy_session_sync',    // localStorage key the tabs signal each other through
    popupOpen: false,               // True while this tab shows the extend / sign out popup

    /**
     * @description Listens for signals from the other tabs
     * @returns {void}
     */
    init: function(){
        $( window ).on( 'storage', function( e ){
            SessionSync.onSignal( e.originalEvent );
        });
    },

    /**
     * @description Tells the other tabs that the session was extended or ended
     * @param {string} event - 'extended' or 'signed_out'
     * @param {string} login_url - Login page the other tabs go to on 'signed_out'
     * @returns {void}
     */
    publish: function( event, login_url ){
        var signal = {
            event: event,
            login_url: login_url || 'login.php',
            at: new Date().getTime()        // Makes every signal a new value, so the event always fires
        };
        try {
            localStorage.setItem( SessionSync.KEY, JSON.stringify( signal ) );
        } catch( err ){
            // Storage blocked: the other tabs fall back to the server check before signing out
        }
    },

    /**
     * @description Acts on a signal from another tab
     * @param {StorageEvent} oe - The native storage event
     * @returns {void}
     */
    onSignal: function( oe ){
        if( !oe || oe.key !== SessionSync.KEY || !oe.newValue ){
            return;
        }

        var signal = null;  // Parsed signal
        try {
            signal = JSON.parse( oe.newValue );
        } catch( err ){
            return;
        }

        if( signal.event === 'extended' ){
            // Close this tab's popup and start counting again from the new token
            if( SessionSync.popupOpen ){
                SessionSync.closePopup();
                showSimpleToast( "success", "Session has been extended in another tab" );
            }
            startSessionCheck();
        }
        else if( signal.event === 'signed_out' ){
            clearInterval( session_check_interval );
            SessionSync.closePopup();
            redirect( signal.login_url );
        }
    },

    /**
     * @description Closes the session popup of this tab, if it is open
     * @returns {void}
     */
    closePopup: function(){
        if( SessionSync.popupOpen ){
            SessionSync.popupOpen = false;
            Swal.close();
        }
    },

    /**
     * @description Called when this tab's countdown runs out. Asks the server first, because another
     *              tab may have extended the session without this tab hearing of it (e.g. a sleeping tab).
     *              Signs out only if the session really is about to end.
     * @returns {void}
     */
    confirmExpiry: function(){
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            data: { what_do_you_want: 'scodezy_validate_user_session' },
            success: function( returned_data ){
                var jSon = parseJSONWithError( returned_data, "Server Side error occurred ! Contact Technical Support..." );
                if( jSon == false ){
                    sign_out();
                    return;
                }

                jSon = jSon[ 0 ];
                var data = jSon[ 'info' ];  // session_status: 0 = ended, 1 = ending soon, 2 = active

                if( jSon[ 'type' ] == 'success' && data.session_status == "2" ){
                    startSessionCheck();        // Extended elsewhere, carry on
                    return;
                }
                if( jSon[ 'type' ] == 'success' && data.session_status == "0" ){
                    SessionSync.publish( 'signed_out', data.login_url );
                    redirect( data.login_url );
                    return;
                }

                sign_out();
            },
            error: function(){
                sign_out();
            }
        });
    }
};

function startSessionCheck(){
    clearInterval( session_check_interval );    // Never run two checks side by side
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

                    SessionSync.popupOpen = true;
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
                            clearInterval( timerInterval );     // Stop the seconds display
                            //redirect( data.login_url );
                            //startSessionCheck();
                        }
                    }).then(( result ) => {
                        SessionSync.popupOpen = false;

                        if (result.dismiss === Swal.DismissReason.timer) { // When the timer ends
                            // Another tab may have extended the session, so ask the server before signing out
                            SessionSync.confirmExpiry();
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

                SessionSync.publish( 'signed_out', data.login_url );   // Sign out the other tabs too

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

                SessionSync.publish( 'signed_out', data.login_url );   // Sign out the other tabs too

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

                SessionSync.publish( 'extended' );                  // Close the popup on the other tabs
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