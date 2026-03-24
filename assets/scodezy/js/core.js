// Global Variables
var localStorage = window.localStorage;
var ATN = 'xAwBo5Re9a';
var RTN = 'y8p9B3LqE8';

$( document ).ready(function(){
    // Convert all elements having class .animated-button automatically
    $.each( $( '.animated-button' ), function( i, v ){
        //console.log( v );
        //console.log( $( v ) );
        v = convertToAnimatedButton( $( v ) );
    });
});

/**
 * element: Provide the jQuery reference of the DOM element
 * This function will add the hidden class to the element
 * 
 */
function hide( element ){
   element.addClass( "hidden d-none" ); 
}

/**
 * element: Provide the jQuery reference of the DOM element
 * This function will remove the hidden class to the element
 * 
 */
function show( element ){
   element.removeClass( "hidden d-none" ); 
}

function getElementByID( id ){
    return $( '#' + id );
}

function getElementByClass( clas ){
    return $( '.' + clas );
}

function getElement( selector ){
    return $( selector );
}



function disableFormElement( element ){
    element.attr( 'disabled', 'disabled' );
}


function enableFormElement( element ){
    element.removeAttr( 'disabled' );
}

/**
 * Show Sweet Alert in the form of Modal. This method can only generate two types of alert viz,
 * if buttonText is present, it will generate an Alert with a descriptive message and a button to dismiss the alert
 * if buttonText is empty, it will generate an Alert with a descriptive message and a 2 second timer to auto close the Alert Modal
 * 
 * @param {type} text The descriptive text to show to the user
 * @param {type} icon Type error, warning, success, etc. Refer official site for more options
 * @param {type} buttonText The text to be displayed on the button. Keep it empty to not show any button
 * @param {type} buttonClass The css classes for the button
 * 
 */
function showSimpleSweetAlert( text, iconType, buttonText, buttonClass ){
    // Show error popup. For more info check the plugin's official documentation: https://sweetalert2.github.io/
    // console.log( 'type - 1' );
    if( buttonText == "" ){
        // Alert without a button when buttonText specified is empty
        Swal.fire({
            //position: "top-end",
            icon: iconType,
            title: text,
            showConfirmButton: false,
            timer: 2000
        });
    }
    else{
        Swal.fire({
            text: text,
            icon: iconType,
            buttonsStyling: false,
            confirmButtonText: buttonText,
            customClass: {
                confirmButton: buttonClass
            }
        });
    }
}

/**
 * Show Sweet Alert in the form of Modal. This method will generate an Alert with a button to dismiss the modal and a callback function that it will execute on pressing the configmButton 
 * 
 * @param {type} text The descriptive text to show to the user
 * @param {type} icon Type error, warning, success, etc. Refer official site for more options
 * @param {type} dismissable boolean True to make the dialog dismissable. False to make it stubborn
 * @param {type} buttonText The text to be displayed on the button. Keep it empty to not show any button
 * @param {type} buttonClass The css classes for the button
 * @param {type} func A function that would be executed on pressing the confirmButton
 * 
 */
function showActionSweetAlert( text, iconType, dismissable, buttonText, buttonClass, func ){
    // Show error popup. For more info check the plugin's official documentation: https://sweetalert2.github.io/
    //console.log( 'type - 2' );
    Swal.fire({
        html: text,
        icon: iconType,
        allowOutsideClick: dismissable,
        confirmButtonText: buttonText,
        focusConfirm: true,
        customClass: {
            confirmButton: buttonClass
        }
    }).then(( result ) => {
        if ( result.isConfirmed ) {
            func();
        }
    });
}

/**
 * Show Sweet Alert in the form of Modal. This method will generate an Alert with a button to dismiss the modal and a callback function that it will execute on pressing the configmButton 
 * 
 * @param {type} title The heading for the dialog
 * @param {type} text The descriptive text to show to the user
 * @param {type} iconType error, warning, success, etc. Refer official site for more options
 * @param {type} confirmButtonText The text to be displayed on the confirm button
 * @param {type} confirmButtonClass The css classes for the confirm button
 * @param {type} cancelButtonText The text to be displayed on the cancel button
 * @param {type} cancelButtonClass The css classes for the cancel button
 * @param {type} func A function that would be executed on pressing the confirmButton
 * 
 */
function showConfirmSweetAlert( title, text, iconType, confirmButtonText, confirmButtonClass, cancelButtonText, cancelButtonClass, func ){
    Swal.fire({
        title: title,
        html: text,
        icon: iconType,
        showCloseButton: false,
        showCancelButton: true,
        focusConfirm: true,
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText,
        customClass: {
            confirmButton: confirmButtonClass,
            cancelButton: cancelButtonClass
        }
      }).then(( result ) => {
        if ( result.isConfirmed ) {
            func();
        }
    });
}

function showLoadingSweetAlert( title, text, dismissable = true ){
    Swal.fire({
        title: title,
        text: text,
        allowOutsideClick: dismissable
    });
    Swal.showLoading();
}

function hideLoadingSweetAlert(){
    Swal.close();
}

function showNotification( type, position, msg, duration, theme ){
	/**
	 * position takes 9 values : 
	 * topLeft -> topleft
	 * topRight -> top-right
	 * bottomLeft -> bottom-left
	 * bottomRight -> bottom-right
	 * topCenter
	 * centerLeft
	 * center
	 * centerRight
	 * bottomCenter
	 * 
	 * 
	 * http://ned.im/noty/#/about
	 * 
	 * duration -> false, for sticky notification
	 * 
	 * theme -> 0 : dark colored, 1, soft colored
	 * 
	 * type -> 
	 * 
	 */
	
	
	var th = (theme==0)?'defaultTheme':'relax';
	
	var options = {
		    layout: position,
		    theme: th, // 'defaultTheme' or 'relax'
		    type: type,
		    text: msg, // can be html or string
		    dismissQueue: true, // If you want to use queue feature set this true
		    template: '<div class="noty_message"><span class="noty_text"></span><div class="noty_close"></div></div>',
		    animation: {
		        open: {height: 'toggle'}, // or Animate.css class names like: 'animated bounceInLeft'
		        close: {height: 'toggle'}, // or Animate.css class names like: 'animated bounceOutLeft'
		        easing: 'swing',
		        speed: 500 // opening & closing animation speed
		    },
		    timeout: duration, // delay for closing event. Set false for sticky notifications
		    force: false, // adds notification to the beginning of queue when set to true
		    modal: false,
		    maxVisible: 5, // you can set max visible notification for dismissQueue true option,
		    killer: false, // for close all notifications before show
		    closeWith: ['click'], // ['click', 'button', 'hover', 'backdrop'] // backdrop click will close all notifications
		    callback: {
		        onShow: function() {},
		        afterShow: function() {},
		        onClose: function() {},
		        afterClose: function() {},
		        onCloseClick: function() {},
		    },
		    buttons: false // an array of buttons
		};
	
	var n = noty(options);
	
	
}

function showToast( type, position, msg, duration ){
    
    var pos = "bottomRight";
    switch( position ){
        case 'topRight':
            pos = "toastr-top-right";
            break;
        
        case 'bottomRight':
            pos = "toastr-bottom-right";
            break;
            
        case 'topLeft':
            pos = "toastr-top-left";
            break;
            
        case 'bottomLeft':
            pos = "toastr-bottom-left";
            break;
            
        case 'topCenter':
            pos = "toastr-top-center";
            break;
            
        case 'bottomCenter':
            pos = "toastr-bottom-center";
            break;
    }
    
    toastr.options = {
        "closeButton": false,
        "debug": false,
        "newestOnTop": false,
        "progressBar": false,
        "positionClass": pos,
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "2000",
        "timeOut": duration,
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };
    
    toastr[type]( msg );
}

function showSimpleToast( type, msg ){
    showToast( type, "bottomRight", msg, 5000 );
}

function getWebservice(){
    return 'webservice.php';
}

function redirect( relative_url ){
    window.location.href = relative_url;
}



/**
 * 
 * Validates the string and checks if it is JSON or not
 * 
 * @param string the string to be validated
 * @returns JSON object if the provided string is a valid JSON string, false otherwise
 * 
 */
function parseJSON( string ){
	var jSon;
	try{
            //console.log( typeof string !== 'object' );
            if( typeof string !== 'object' )
                jSon = $.parseJSON( string );
            else
                jSon = string;
	}
	catch( e ){
		return false;
	}
	return jSon;
}

/**
 * 
 * Validates the string and checks if it is JSON or not
 * 
 * @param string the string to be validated
 * @param string Error message to be shown as notification on false
 * 
 * @returns JSON object if the provided string is a valid JSON string, false otherwise
 * 
 */
function parseJSONWithError( string, error_msg ){
    var jSon;
    try{
        //console.log( typeof string );
        //console.log( typeof string !== 'object' );
        if( typeof string !== 'object' )
            jSon = $.parseJSON( string );
        else
            jSon = string;
        
        //console.log( jSon );
        
        var json = jSon[ 0 ];
        //console.log( json );
        if( json[ 'type' ] == 'token_expired' ){    // Means the session has been expired
            showActionSweetAlert( "Your session has been expired. Please sign in again to continue", "question", false, "Okay", "btn btn-danger", function(){
                scodezy_sign_out();
            });
            return false;
        }
    }
    catch( e ){
        //console.log( e );
        //showNotification( "error", "bottomRight", error_msg, 10000, 1 );
        showToast( "error", "bottomRight", error_msg, 6000 );
        return false;
    }
    return jSon;
}


var Defaults = {
    // ### General
    // Default data-namespace for DOM API
    namespace: 'data-parsley-',
    // Supported inputs by default
    inputs: 'input, textarea, select',
    // Excluded inputs by default
    excluded: 'input[type=button], input[type=submit], input[type=reset], input[type=hidden]',
    // Stop validating field on highest priority failing constraint
    priorityEnabled: true,
    // ### Field only
    // identifier used to group together inputs (e.g. radio buttons...)
    multiple: null,
    // identifier (or array of identifiers) used to validate only a select group of inputs
    group: null,
    // ### UI
    // Enable\Disable error messages
    uiEnabled: true,
    // Key events threshold before validation
    validationThreshold: 3,
    // Focused field on form validation error. 'first'|'last'|'none'
    focus: 'first',
    // event(s) that will trigger validation before first failure. eg: `input`...
    trigger: false,
    // event(s) that will trigger validation after first failure.
    triggerAfterFailure: 'input',
    // Class that would be added on every failing validation Parsley field
    errorClass: 'parsley-error is-invalid',
    // Same for success validation
    successClass: 'parsley-success is-valid',
    // Return the `$element` that will receive these above success or error classes
    // Could also be (and given directly from DOM) a valid selector like `'#div'`
    classHandler: function classHandler(Field) {},
    // Return the `$element` where errors will be appended
    // Could also be (and given directly from DOM) a valid selector like `'#div'`
    errorsContainer: function errorsContainer(Field) {
    },
    // ul elem that would receive errors' list
    //errorsWrapper: '<div class="parsley-errors-list scodezy-error"></div>',
    // li elem that would receive error message
    //errorTemplate: '<span></span>',
    
};

/**
 * Scodezy JS Form Class.
 * The forms in Scodezy are initialized using this class.
 * 
 * 
 * @type object
 */
class ScodezyForm{
    
    /** Form object after it has been initialized */
    form = undefined;
    formData = undefined;
    formDataExtras = undefined;
    parsleyForm = undefined;
    defaults = undefined;
    
    
    /**
    * Since all the forms are validated using Parsley
    * Each time a form is being used in jQuery,
    * it has to be initialized.
    * This function will initialize the form with Parseley 
    * Defaults : have been initialized in sc-assets/scodezy/parsley/parsley-scodezy-init.js
    * 
    * @var string form id or class name prefixed accordingly. For eg: '#form1' or '.form2'
    * @var object Defaults object of Parsley
    * 
    * @return object jQuery form element
    * 
    */
    initForm = function( formIdOrClass, defaults = Defaults ){
        /** Defaults has been initialized in the parsley/parsley-scodezy-init.js file */
        this.form = formIdOrClass;
        this.defaults = defaults;
        this.initParsleyForm();
        this.formDataExtras = new FormData();
        return $( formIdOrClass );
    }
    
    /***
     * Initializes the form jQuery object and returns it
     * 
     * @returns object jQuery object for the form
     */
    getForm = function(){
       return $( this.form );
    }
   
   
    /***
     * Initialize the form with parsley
     * 
     * @returns {ScodezyForm@call;getForm@call;parsley}
     */
    initParsleyForm = function(){
       this.parsleyForm = this.getForm().parsley( this.defaults );
       return this.parsleyForm;
    }
   
   
    /***
     * Fetches the parsley form object
     * 
     * @return object Parsley form object which was initialzed using initParsleyForm()
     */
    getParsleyForm = function(){
       return this.parsleyForm;
    }
   
   
    /**
     * Initialize the Form's data into FormData object
     * 
     * @return FormData object
     */
    initFormData = function(){
       this.formData = this.getForm().getFormData();
       return this.formData;
    }
   
    /**
     * Returns a FormData object from the form parameters
     * This function calls the getFormData() Core Function
     * and then appends the formDataExtras object values to it
     * 
     * @returns FormData object
     */
    getFormData = function(){
       var formData = this.getForm().getFormData();
       var formDataWithExtras = new FormData();
       
       for ( var value of formData.entries() ) {
          if( value.length == 3 )
              formDataWithExtras.append( value[ 0 ], value[ 1 ], value[ 2 ] );
          else
              formDataWithExtras.append( value[ 0 ], value[ 1 ] );
       }
       
       for ( var value of this.formDataExtras.entries() ) {
          if( value.length == 3 )
              formDataWithExtras.append( value[ 0 ], value[ 1 ], value[ 2 ] );
          else
              formDataWithExtras.append( value[ 0 ], value[ 1 ] );
       }
       
       return formDataWithExtras;
    }
   
   
    /***
     * Set the Purpose for the FormData object
     * 
     * @param string purpose The purpose is the identifier for the webservice function.
     * 
     *  @returns object FormData object with purpose set
     */
    setFormPurpose = function( purpose ){
       return setFormPurpose( this.formDataExtras, purpose );
    }
   
   
    /**
     * Set the extra parameters for the from through programming.
     * Note that the 2nd parameter must strictly be a Blob of File Object, 
     * when using the 3rd parameter in .append()
     * 
     * @param string The key using which the form value can be accessed
     * @param string The value for the key
     * @param string name (optional) name for the object
     * 
     * @returns object FormData object with the key-value set
     */
    setFormParameter = function( key, value, name = undefined ){
       return setFormParameter( this.formDataExtras, key, value, name );
    }
}


function generateRandomString( length ) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    const charactersLength = characters.length;
    let counter = 0;
    while (counter < length) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
      counter += 1;
    }
    return result;
}



/**
 * Converts timestamp/milliseconds to human readble string. Requires inclusion of Moment.js
 * 
 * @param millis milliseconds
 * @return Human Readable Timestamp in the format Eg : 26th March, 2016 11:30am
 */
function millisToHumanReadableDate( millis ){
	millis = parseInt( millis );
	var mom = moment( millis );
	mom = mom.format( "Do MMM, YYYY hh:mma" );
	// console.log( mom.toString() );
	
	return mom.toString();
}

function refreshPage(){
    window.location.href = window.location.href;
}

function storeDataIntoLocalStorage( key, value ){
    localStorage.setItem( key, value );
}

function getDataFromLocalStorage( key ){
    return localStorage.getItem( key );
}

function removeDataFromLocalStorage( key ){
    localStorage.removeItem( key );
}

function clearLocalStorage(){
    localStorage.clear();
}

function scodezy_get_new_access_token(){
    var data = {
        what_do_you_want: "scodezy_get_new_access_token",
        refresh_token: getDataFromLocalStorage( RTN )
    };
    
    // Show Loading Animation on the Screen
    //showLoadingSweetAlert( "Signing Out", "Please wait while you are being signed out of the system", false );

    $.ajax({
        url: getWebservice(),
        type: 'POST',
        data: data,
        success: function( returned_data ){
            console.log( returned_data );

            // Hide Loading Animation
            //hideLoadingSweetAlert();
            
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
                //removeDataFromLocalStorage( RTN );
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

function convertToAnimatedButton( element ){
    var buttonText = element.html();
    var animationHTML = '<span class="indicator-label">' + buttonText + '</span>' + 
            '<span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>';
    
    element.html( animationHTML );

    return element;
}

function showLoadingOnButton( element, disable = true ){    
    element.attr( 'data-kt-indicator', 'on' );
    if( disable )
        disableFormElement( element );
}

function hideLoadingOnButton( element, disable = true ){
    element.attr( 'data-kt-indicator', 'off' );
    if( disable )
        enableFormElement( element );
}


function showLoadingOnElement( element ){
    //var e_table_loading = $( '.products-table-loading' );
    var loadingEl = document.createElement( "div" );
    loadingEl.classList.add( "section-loading" );
    loadingEl.classList.add( "page-loader" );
    loadingEl.classList.add( "section-loader" );
    loadingEl.classList.add( "no-z-index" );
    loadingEl.classList.add( "flex-column" );
    loadingEl.innerHTML = `
        <span class="spinner-border text-primary" role="status"></span>
        <span class="text-gray-800 fs-6 fw-semibold mt-5">Loading...</span>
    `;
    if( element.find( '.section-loading' ).length > 0 )
        element.find( '.section-loading' ).remove();
    element.append( loadingEl );

    // Show page loading
    setTimeout( function(){
        KTApp.showPageLoading();        
    }, 1000 );

}

function hideLoadingOnElement( element ){
    var el = element.find( "div.section-loader" );
    
    KTApp.hidePageLoading();
    el.remove();
}

// Convert the timestamp stored in the database created_at and updated_at columns into a human readable singapore timezone format
function dbTimestampToSingaporeTimestamp( dbTimestamp ){
    var m = moment(dbTimestamp);
    var localTimestamp = m.local();        // Convert the Date from DB into local format where it is being viewed
    var displayFormat = localTimestamp.format( 'ddd MMM DD YYYY HH:mm ZZ' );
    //console.log(displayFormat);
    //console.log(m._d.getTimezoneOffset());
    return displayFormat;
}