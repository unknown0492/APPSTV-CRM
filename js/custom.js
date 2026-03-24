var console_active = true;
$( document ).ajaxStart(App.blockUI({ message: $( '#pageLoading' ).html() })).ajaxStop(App.unblockUI());

$( document ).ready( function(){
	preLoader();
	
	// Form Descriptive popovers
	$( 'input' ).popover( {trigger: 'focus'} );
	$( 'textarea' ).popover( {trigger: 'focus'} );
	// Form Descriptive popovers
	
	// getAllValidationConstants();
	//console.log( "var : "+VLDTN_USER_ID );
	//console.log( "var : "+VLDTN_PASSWORD.TYPE );
	// disableConsole();
});


function preLoader(){
	setTimeout( function(){
		preLoaderHide();
	}, 1000 );
}

function preLoaderShow(){
	$( "#preloader-before" ).fadeIn( 'slow' );
}

function preLoaderHide(){
	$( "#preloader-before" ).fadeOut( 'slow' );
}

function refreshPage(){
	window.location.href = window.location.href;
}

function getCurrentTimeMilliseconds(){
	var d = new Date();
	return d.getTime();
}

function showPopover( id ){
	$( '#' + id ).popover( 'show' );
}

function hidePopover( id ){
	$( '#' + id ).popover( 'hide' );
}

function showPopoverClass( clas ){
	$( '.' + clas ).popover( 'show' );
}

function hidePopoverClass( clas ){
	$( '.' + clas ).popover( 'hide' );
}

function hideMe( id, id_or_class ){
	if( id_or_class == "id" )
		$( '#'+id ).hide(); //addClass( 'hidden' );
	else if( id_or_class == "class" )
		$( '.'+id ).hide(); //addClass( 'hidden' );
}

function showMe( id, id_or_class ){
	if( id_or_class == "id" )
		$( '#'+id ).show(); //addClass( 'hidden' );
	else if( id_or_class == "class" )
		$( '.'+id ).show(); //addClass( 'hidden' );
}

function showModal( type, message ){
	if( type == 'success' ){
		$( '#modal-success-message' ).html( message );
		$( '#modal-success-button' ).click();
	}
	else if( type == 'error' ){
		$( '#modal-error-message' ).html( message );
		$( '#modal-error-button' ).click();
	}
}

function showConfirmModal( title, message, positive_button_text, negative_button_text ){
	$( '#modal-confirm-title' ).html( title );
	$( '#modal-confirm-message' ).html( message )
	$( '#modal-confirm-positive' ).html( positive_button_text );
	$( '#modal-confirm-negative' ).html( negative_button_text );
	$( '#modal-confirm-button' ).click();
}

function countChars( str, which_char ){
	var count = 0;
	for( var i = 0 ; i < str.length ; i++ ){
		if( str.charAt( i ) == which_char )
			count++;
	}
	return count;
}

// Disable a DOM element
function disableIt( id ){
	$( '#' + id ).attr( 'disabled', 'disabled' );
}

// Enable a DOM element
function enableIt( id ){
	$( '#' + id ).removeAttr( 'disabled' );
}

// Perform a POST operation using JavaScript
function post( path, params ) {
    method = "post"; // Set method to post by default if not specified.

    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("action", path);
   
    for( var key in params ) {
        if( params.hasOwnProperty( key ) ) {
            var hiddenField = document.createElement( "input" );
            hiddenField.setAttribute( "type", "hidden" );
            hiddenField.setAttribute( "name", key );
            hiddenField.setAttribute( "value", params[ key ] );

            form.appendChild( hiddenField );
         }
    }

    document.body.appendChild( form );
    form.submit();
    // Call this method in this way --> post('/contact/', {name: 'Johnny Bravo'});
}

function postNewTab( path, params ){
    method = "post"; // Set method to post by default if not specified.

    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    var form = document.createElement( "form" );
    form.setAttribute( "method", method );
    form.setAttribute( "action", path );
    form.setAttribute( "target", "_blank" );	// Open New Tab

    for( var key in params ) {
        if( params.hasOwnProperty( key ) ) {
            var hiddenField = document.createElement( "input" );
            hiddenField.setAttribute( "type", "hidden" );
            hiddenField.setAttribute( "name", key );
            hiddenField.setAttribute( "value", params[ key ] );

            form.appendChild( hiddenField );
         }
    }

    document.body.appendChild( form );
    form.submit();
    // Call this method in this way --> post('/contact/', {name: 'Johnny Bravo'});
}

function redirect( relative_url ){
	window.location.href = relative_url;
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

/**
 * 
 * Call like $( 'form' ).serializeObject();
 * 
 * this method returns the form parameters as name value pair
 * example : { "field1" : 'value1', 'fiel2' : 'value2' }
 * 
 */
$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

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
		jSon = $.parseJSON( string );
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
            jSon = $.parseJSON( string );
	}
	catch( e ){
            showNotification( "error", "bottomRight", error_msg, 10000, 1 );
            return false;
	}
	return jSon;
}

/**
 * 
 * Disable the console.log
 * 
 */
function disableConsole(){
	console.log = function() {}
}

/*VALIDATIONS = {};
*//**
 * 
 *  
 * 
 * 
 * 
 *//*
function getAllValidationConstants(){
	$.ajax({
		url : 'webservice.php',
		type: 'GET',
		data: { what_do_you_want : 'get_all_validation_constants' },
		success: function( returned_data ){
			console.log( returned_data );
			var jSon = $.parseJSON( returned_data );
			$.each( jSon, function(){
				// console.log( "var "+ this[ 0 ] + "='"+this[ 2 ]+"'" );
				//eval( "VALIDATIONS={ A : 'aa' }" );
				eval( "VALIDATIONS."+ this[ 0 ] + "={}" );
				//eval( "VALIDATIONS."+ this[ 0 ] + "='"+this[ 2 ]+"'" );
				eval( "VALIDATIONS."+ this[ 0 ] + ".REGEX='"+this[ 2 ]+"'" );
				eval( "VALIDATIONS."+ this[ 0 ] + ".TYPE='"+this[ 1 ]+"'" );
				
				
				//eval( ""+ this[ 0 ] + ".REGEX" + "='"+this[ 2 ]+"'" );
				//eval( ""+ this[ 0 ] + ".TYPE" + "='"+this[ 1 ]+"'" );
				console.log( eval( "VALIDATIONS."+this[ 0 ]+".REGEX" ) );	
				console.log( eval( "VALIDATIONS."+this[ 0 ]+".TYPE" ) );	
				//console.log( eval( this[ 0 ] + ".REGEX" ) );
				// console.log( eval( this[ 0 ] + ".TYPE" ) );
			});
		}
	});
}*/

function ConfirmModal( title, message, positive, negative ){
	var elem = this;
	this.title = title;
	this.message = message;
	this.positive = positive;
	this.negative = negative;
	this.positiveFunction = function(){
		console.log( "FU" );
	};
	this.negativeFunction = function(){
		console.log( "SU" );
	};
	this.showConfirm = function(){
		$( '#confirm-modal-title' ).html( elem.title );
		$( '#confirm-modal-message' ).html( elem.message );
		$( '#confirm-modal-positive-button' ).html( elem.positive );
		$( '#confirm-modal-negative-button' ).html( elem.negative );
		$( '#show_confirm_modal' ).click();
	}
	
	$( '#confirm-modal-positive-button' ).on( 'click', function(){
		elem.positiveFunction();
	});
	
	$( '#confirm-modal-negative-button' ).on( 'click', function(){
		elem.negativeFunction();
	});
	// elem.showConfirm();
}

function iProgessModal( loading_text ){
	var elem = this;
	this.loading_text = loading_text;
	this.showProgress = function(){
		$( '#iprogress-message' ).html( elem.loading_text );
		$( '#show_iprogress_modal' ).click();
	}
	this.hideProgress = function(){
		$( '#dismiss-ipmodal' ).click();
	}
}

function FormSubmitAnimator( id ){
	var elem = this;
	this.id = id; 
	
	this.showLoading = function(){
		console.log( "showLoading() : "+elem.id );
		var thees = $( '#'+elem.id );
		thees.attr( "disabled", "disabled" );
		thees.html( thees.data( "loading-text" ) );	
	}
	
	this.hideLoading = function(){
		console.log( "hideLoading() : "+elem.id );
		var thees = $( '#'+elem.id );
		thees.removeAttr( "disabled" );
		thees.html( thees.data( "original-text" ) );
	}
}

function changeValue( id, new_val ){
	$( '#' + id ).val( new_val );
}

function showLoading( clas ){
	$( '.' + clas ).removeClass( "hidden" );
}

function hideLoading( clas ){
	$( '.' + clas ).addClass( "hidden" );
}

function silentConsole( string ){
    if( console_active )
        console.log( string );
}



/**
 * 
 * Call it like $( '#formName' ).serializeObject();
 * 
 * This method returns the form parameters as name value pair
 * example : { "field1" : 'value1', 'fiel2' : 'value2' }
 * 
 * @return object Object of all the form input elements key-value pairs
 */
$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};



/**
 * 
 * Returns a FormData object from the form parameters
 * Note that the 2nd parameter must strictly be a Blob of File Object, 
 * when using the 3rd parameter in .append()
 * 
 * Example : Use it like : $( '#formName' ).getFormData()
 * 
 * @return FormData object containing all the form input elements key-value pairs
 */
$.fn.getFormData = function()
{
    var formData = new FormData();
    var a = this.serializeArray();
    $.each(a, function(){
        formData.append( this.name, this.value );
    });
    
    return formData;
};



/**
 * Set the Purpose for the FormData object
 * The purpose is the identifier in the webservice call
 * 
 * @var object FormData object on which the purpose has to be set
 * 
 * @return object FormData object with purpose set
 */
function setFormPurpose( formData, purpose ){
    return formData.set( "purpose", purpose );
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
function setFormParameter( formData, key, value, name = undefined ){
   if( name == undefined ){
       formData.append( key, value );
   }
   else{
       formData.append( key, value, name );
   }
   return formData;
}


function getRandomNumber( digits = 4 ){
    if( digits > 8 ){
        digits = 8;
    }
    var str = "1";
    for( var i = 0 ; i < digits ; i++ ){
        str += "0";
    }
    var num = parseInt( str );
    return Math.floor((Math.random() * str) + 10);
}


function getWebservice(){
    return 'webservice.php';
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

