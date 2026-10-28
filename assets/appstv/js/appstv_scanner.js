"use strict";

/* =============================================
   appstv_scanner.js (v1.0.6)
   Shared serial number scanner for the APPSTV pages (Record Sale, Warranty).
   - Phone / PC camera: decodes barcodes and QR codes in the browser. Uses the browser's own
     BarcodeDetector when it has one (Chrome on Android), otherwise ZXing (the page loads
     @zxing/library 0.21.3).
   - Continuous mode: the camera stays open, every new code is handed over with a beep and a
     vibration, the same code is ignored for 2 seconds.
   - Take photo: sends the current camera frame to the server decoder (appstv_decode_qr_barcode).
   - Barcode guns: bindInput() turns an input box into a scan target (Enter or Tab after a
     fast burst of keys, and pasting several serial numbers at once).
   ============================================= */

var AppstvScanner = {

    REPEAT_IGNORE_MS : 2000,                                   // The same code is ignored for this long after a scan
    DECODE_INTERVAL_MS : 150,                                  // Pause between two decode attempts
    MAX_FRAME_WIDTH : 1280,                                    // Frames are scaled down to this width before ZXing decodes them
    GUN_KEY_GAP_MS : 50,                                       // Keys closer than this are treated as a barcode gun burst

    options : null,                                            // Options of the open scanner (see open())
    stream : null,                                             // The camera MediaStream while open
    timer : null,                                              // Decode loop timeout id
    detector : null,                                           // BarcodeDetector instance, when the browser has one
    zxingReader : null,                                        // ZXing MultiFormatReader, used when there is no BarcodeDetector
    canvas : null,                                             // Off-screen canvas used to grab frames
    lastSeen : {},                                             // code -> time it was last handed over (repeat filter)
    count : 0,                                                 // Codes accepted since the scanner was opened
    busy : false,                                              // True while a decode or a photo upload is running
    audioCtx : null,                                           // Web Audio context for the beep

    /**
     * @description Turns raw scanned / typed text into a serial number: letters and digits only, upper case
     * @param {string} raw - Text from a barcode, QR code, file or keyboard
     * @returns {string} The serial number, or '' when nothing valid is left (or it is longer than 50)
     */
    cleanSerial : function ( raw ) {
        var cleaned = String( raw === null || raw === undefined ? '' : raw ).replace( /[^A-Za-z0-9]/g, '' ).toUpperCase();
        if ( cleaned.length === 0 || cleaned.length > 50 ) { return ''; }
        return cleaned;
    },

    /**
     * @description Splits pasted or typed text holding several serial numbers (new lines, commas, semicolons, tabs, spaces)
     * @param {string} text - The text to split
     * @returns {string[]} Raw pieces, empty ones removed
     */
    splitMany : function ( text ) {
        return String( text || '' ).split( /[\r\n,;\t ]+/ ).filter( function ( piece ) { return piece !== ''; } );
    },

    /**
     * @description Makes an input box a scan target for keyboards and barcode guns. Enter adds the value; Tab adds it
     *              too when it arrived as a fast burst (guns set to send Tab); pasting several values adds them all.
     * @param {jQuery} $input - The text input
     * @param {function} onValues - Called with an array of raw values to add
     * @returns {void}
     */
    bindInput : function ( $input, onValues ) {
        var lastKeyAt = 0;                                     // Time of the previous key press
        var burst = 0;                                         // Number of fast consecutive keys

        $input.on( 'keydown', function ( e ) {
            var now = Date.now();
            burst = ( now - lastKeyAt < AppstvScanner.GUN_KEY_GAP_MS ) ? burst + 1 : 0;
            lastKeyAt = now;

            var isTabFromGun = ( e.key === 'Tab' && burst >= 4 && $input.val().trim() !== '' );
            if ( e.key === 'Enter' || isTabFromGun ) {
                e.preventDefault();
                var value = $input.val().trim();
                if ( value !== '' ) {
                    onValues( AppstvScanner.splitMany( value ) );
                    $input.val( '' );
                }
            }
        } );

        $input.on( 'paste', function ( e ) {
            var clip = ( e.originalEvent && e.originalEvent.clipboardData ) ? e.originalEvent.clipboardData.getData( 'text' ) : '';
            var pieces = AppstvScanner.splitMany( clip );
            if ( pieces.length > 1 ) {
                e.preventDefault();
                onValues( pieces );
            }
        } );
    },

    /**
     * @description Opens the full-screen camera scanner
     * @param {object} options - { title: string, continuous: bool, onScan: function( serial ) returning false to reject it,
     *                             onClose: function( count ), photo: bool (show the Take photo button, default true) }
     * @returns {void}
     */
    open : function ( options ) {
        if ( AppstvScanner.options !== null ) { AppstvScanner.close(); }

        AppstvScanner.options = $.extend( { title: 'Scan serial number', continuous: false, photo: true,
                                            onScan: function () {}, onClose: function () {} }, options );
        AppstvScanner.count = 0;
        AppstvScanner.lastSeen = {};
        AppstvScanner._buildOverlay();

        if ( !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia ) {
            AppstvScanner._status( AppstvScanner._cameraHelp( null ), 'error' );
            return;
        }

        navigator.mediaDevices.getUserMedia( { video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 }, height: { ideal: 1080 } }, audio: false } )
            .then( function ( stream ) {
                if ( AppstvScanner.options === null ) {        // Closed while waiting for permission
                    stream.getTracks().forEach( function ( track ) { track.stop(); } );
                    return;
                }
                AppstvScanner.stream = stream;
                var video = $( '#appstv_scanner_video' ).get( 0 );
                video.srcObject = stream;
                video.play().catch( function () {} );
                AppstvScanner._setupTorch( stream );
                AppstvScanner._prepareDecoder().then( function ( ready ) {
                    if ( !ready ) {
                        AppstvScanner._status( 'This browser cannot read barcodes from the camera. Use Take photo, or type the serial number.', 'error' );
                        return;
                    }
                    AppstvScanner._status( 'Point the camera at the barcode', '' );
                    AppstvScanner._loop();
                } );
            } )
            .catch( function ( err ) {
                AppstvScanner._status( AppstvScanner._cameraHelp( err ), 'error' );
            } );
    },

    /**
     * @description Closes the scanner, stops the camera and calls onClose with the number of accepted codes
     * @returns {void}
     */
    close : function () {
        var options = AppstvScanner.options;                   // Kept to call onClose after the cleanup
        clearTimeout( AppstvScanner.timer );
        AppstvScanner.timer = null;
        if ( AppstvScanner.stream ) {
            AppstvScanner.stream.getTracks().forEach( function ( track ) { track.stop(); } );
            AppstvScanner.stream = null;
        }
        $( '#appstv_scanner_overlay' ).remove();
        $( 'body' ).removeClass( 'appstv-scanner-open' );
        AppstvScanner.options = null;
        AppstvScanner.busy = false;
        if ( options ) { options.onClose( AppstvScanner.count ); }
    },

    /**
     * @description Sends an image to the server decoder (zbar) and returns the serial number found in it
     * @param {Blob} blob - The image (JPG or PNG)
     * @param {string} filename - File name sent with the upload
     * @param {function} onSerial - Called with the serial number
     * @param {function} onError - Called with an error message
     * @returns {void}
     */
    decodeOnServer : function ( blob, filename, onSerial, onError ) {
        var formData = new FormData();                         // Multipart body for the upload
        formData.append( 'what_do_you_want', 'appstv_decode_qr_barcode' );
        formData.append( 'scan_image', blob, filename );

        $.ajax( {
            url : getWebservice(),
            type : 'POST',
            data : formData,
            processData : false,
            contentType : false,
            success : function ( returned_data ) {
                var jSon = parseJSONWithError( returned_data, 'Server Side error occurred ! Contact Technical Support...' );
                if ( jSon === false ) { onError( 'Server error, please try again.' ); return; }
                jSon = jSon[ 0 ];
                if ( jSon[ 'type' ] === 'success' ) {
                    onSerial( jSon[ 'info' ][ 'serial_number' ] );
                } else {
                    onError( typeof jSon[ 'info' ] === 'string' ? jSon[ 'info' ] : 'No barcode was found in the photo.' );
                }
            },
            error : function () {
                onError( 'Server error, please try again.' );
            }
        } );
    },

    /**
     * @description Builds the full-screen overlay (video, frame, status, counter, buttons) and binds its buttons
     * @returns {void}
     */
    _buildOverlay : function () {
        var o = AppstvScanner.options;                         // Options of this scanner
        var html = ''
            + '<div id="appstv_scanner_overlay" class="appstv-scanner-overlay" role="dialog" aria-modal="true">'
            + '  <div class="appstv-scanner-top">'
            + '    <div class="appstv-scanner-title"></div>'
            + '    <button type="button" class="btn btn-icon btn-sm appstv-scanner-x" data-scanner-action="close" aria-label="Close"><i class="fa-solid fa-xmark fs-2"></i></button>'
            + '  </div>'
            + '  <div class="appstv-scanner-stage">'
            + '    <video id="appstv_scanner_video" autoplay muted playsinline></video>'
            + '    <div class="appstv-scanner-frame"><div class="appstv-scanner-line"></div></div>'
            + '  </div>'
            + '  <div class="appstv-scanner-bottom">'
            + '    <div class="appstv-scanner-status"></div>'
            + '    <div class="appstv-scanner-last"></div>'
            + '    <div class="appstv-scanner-buttons">'
            + '      <button type="button" class="btn btn-light d-none" data-scanner-action="torch"><i class="fa-solid fa-lightbulb"></i> Light</button>'
            + '      <button type="button" class="btn btn-light-primary" data-scanner-action="photo"><i class="fa-solid fa-camera"></i> Take photo</button>'
            + '      <button type="button" class="btn btn-primary" data-scanner-action="close">Done <span class="appstv-scanner-count"></span></button>'
            + '    </div>'
            + '  </div>'
            + '</div>';

        $( 'body' ).append( html ).addClass( 'appstv-scanner-open' );
        $( '#appstv_scanner_overlay .appstv-scanner-title' ).text( o.title );
        if ( !o.photo ) { $( '#appstv_scanner_overlay [data-scanner-action="photo"]' ).addClass( 'd-none' ); }

        $( '#appstv_scanner_overlay' ).on( 'click', '[data-scanner-action]', function () {
            var action = $( this ).attr( 'data-scanner-action' );
            if ( action === 'close' ) { AppstvScanner.close(); }
            if ( action === 'photo' ) { AppstvScanner._takePhoto(); }
            if ( action === 'torch' ) { AppstvScanner._toggleTorch( $( this ) ); }
        } );

        $( document ).off( 'keydown.appstvScanner' ).on( 'keydown.appstvScanner', function ( e ) {
            if ( e.key === 'Escape' && AppstvScanner.options !== null ) { AppstvScanner.close(); }
        } );
    },

    /**
     * @description Shows a status line under the camera picture
     * @param {string} text - Message
     * @param {string} kind - '' | 'ok' | 'error'
     * @returns {void}
     */
    _status : function ( text, kind ) {
        $( '#appstv_scanner_overlay .appstv-scanner-status' ).text( text )
            .toggleClass( 'is-ok', kind === 'ok' ).toggleClass( 'is-error', kind === 'error' );
    },

    /**
     * @description Explains why the camera could not start
     * @param {Error|null} err - The getUserMedia error, or null when the browser has no camera API
     * @returns {string} Message for the user
     */
    _cameraHelp : function ( err ) {
        if ( location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1' ) {
            return 'The camera only works on a secure (https) address.';
        }
        if ( err && err.name === 'NotAllowedError' ) { return 'Camera permission was denied. Allow the camera for this site in the browser settings.'; }
        if ( err && err.name === 'NotFoundError' ) { return 'No camera was found on this device.'; }
        if ( err && err.name === 'NotReadableError' ) { return 'The camera is being used by another app.'; }
        return 'The camera could not be started' + ( err ? ': ' + err.message : '.' );
    },

    /**
     * @description Prepares BarcodeDetector, or ZXing when the browser has no BarcodeDetector
     * @returns {Promise<boolean>} Resolves true when a decoder is ready
     */
    _prepareDecoder : function () {
        var wanted = [ 'code_128', 'code_39', 'code_93', 'codabar', 'ean_13', 'ean_8', 'upc_a', 'upc_e', 'itf', 'qr_code', 'data_matrix' ];

        if ( 'BarcodeDetector' in window && typeof window.BarcodeDetector.getSupportedFormats === 'function' ) {
            return window.BarcodeDetector.getSupportedFormats().then( function ( supported ) {
                var formats = wanted.filter( function ( f ) { return supported.indexOf( f ) !== -1; } );
                if ( formats.length > 0 ) {
                    AppstvScanner.detector = new window.BarcodeDetector( { formats: formats } );
                    return true;
                }
                return AppstvScanner._prepareZxing();
            } ).catch( function () {
                return AppstvScanner._prepareZxing();
            } );
        }
        return Promise.resolve( AppstvScanner._prepareZxing() );
    },

    /**
     * @description Creates the ZXing reader with the barcode formats used on TV labels
     * @returns {boolean} True when ZXing is loaded on the page
     */
    _prepareZxing : function () {
        if ( typeof ZXing === 'undefined' ) { return false; }
        if ( AppstvScanner.zxingReader === null ) {
            var hints = new Map();                             // ZXing decode hints
            hints.set( ZXing.DecodeHintType.POSSIBLE_FORMATS, [
                ZXing.BarcodeFormat.CODE_128, ZXing.BarcodeFormat.CODE_39, ZXing.BarcodeFormat.CODE_93,
                ZXing.BarcodeFormat.CODABAR, ZXing.BarcodeFormat.EAN_13, ZXing.BarcodeFormat.EAN_8,
                ZXing.BarcodeFormat.UPC_A, ZXing.BarcodeFormat.UPC_E, ZXing.BarcodeFormat.ITF,
                ZXing.BarcodeFormat.QR_CODE, ZXing.BarcodeFormat.DATA_MATRIX
            ] );
            hints.set( ZXing.DecodeHintType.TRY_HARDER, true );
            AppstvScanner.zxingReader = new ZXing.MultiFormatReader();
            AppstvScanner.zxingReader.setHints( hints );
        }
        AppstvScanner.detector = null;
        return true;
    },

    /**
     * @description Decode loop: one attempt every DECODE_INTERVAL_MS while the scanner is open
     * @returns {void}
     */
    _loop : function () {
        if ( AppstvScanner.options === null ) { return; }
        AppstvScanner.timer = setTimeout( function () {
            if ( AppstvScanner.options === null ) { return; }
            if ( AppstvScanner.busy ) { AppstvScanner._loop(); return; }
            AppstvScanner._decodeFrame().then( function ( text ) {
                if ( text !== null ) { AppstvScanner._found( text ); }
                AppstvScanner._loop();
            } );
        }, AppstvScanner.DECODE_INTERVAL_MS );
    },

    /**
     * @description Tries to read one code from the current camera frame
     * @returns {Promise<string|null>} The raw text of the code, or null when none was found
     */
    _decodeFrame : function () {
        var video = $( '#appstv_scanner_video' ).get( 0 );     // The live camera picture
        if ( !video || video.readyState < 2 || video.videoWidth === 0 ) { return Promise.resolve( null ); }

        if ( AppstvScanner.detector !== null ) {
            return AppstvScanner.detector.detect( video ).then( function ( codes ) {
                return ( codes && codes.length > 0 ) ? codes[ 0 ].rawValue : null;
            } ).catch( function () { return null; } );
        }

        var canvas = AppstvScanner._grabFrame( video );        // Scaled copy of the frame
        try {
            var source = new ZXing.HTMLCanvasElementLuminanceSource( canvas );
            var bitmap = new ZXing.BinaryBitmap( new ZXing.HybridBinarizer( source ) );
            var result = AppstvScanner.zxingReader.decode( bitmap );
            return Promise.resolve( result ? result.getText() : null );
        } catch ( e ) {
            return Promise.resolve( null );                    // NotFoundException: no code in this frame
        } finally {
            AppstvScanner.zxingReader.reset();
        }
    },

    /**
     * @description Copies the current video frame onto the off-screen canvas, scaled to MAX_FRAME_WIDTH
     * @param {HTMLVideoElement} video - The camera picture
     * @returns {HTMLCanvasElement} The canvas holding the frame
     */
    _grabFrame : function ( video ) {
        if ( AppstvScanner.canvas === null ) { AppstvScanner.canvas = document.createElement( 'canvas' ); }
        var scale = Math.min( 1, AppstvScanner.MAX_FRAME_WIDTH / video.videoWidth );
        var canvas = AppstvScanner.canvas;
        canvas.width = Math.round( video.videoWidth * scale );
        canvas.height = Math.round( video.videoHeight * scale );
        canvas.getContext( '2d', { willReadFrequently: true } ).drawImage( video, 0, 0, canvas.width, canvas.height );
        return canvas;
    },

    /**
     * @description Handles a decoded code: cleans it, filters repeats, hands it to onScan, gives feedback
     * @param {string} rawText - Text read from the code
     * @returns {void}
     */
    _found : function ( rawText ) {
        var o = AppstvScanner.options;                         // Options of this scanner
        if ( o === null ) { return; }
        var serial = AppstvScanner.cleanSerial( rawText );
        if ( serial === '' ) {
            AppstvScanner._status( 'That code is not a serial number', 'error' );
            return;
        }

        var now = Date.now();
        if ( AppstvScanner.lastSeen[ serial ] && now - AppstvScanner.lastSeen[ serial ] < AppstvScanner.REPEAT_IGNORE_MS ) {
            AppstvScanner.lastSeen[ serial ] = now;            // Still in front of the camera: keep ignoring it
            return;
        }
        AppstvScanner.lastSeen[ serial ] = now;

        var accepted = ( o.onScan( serial ) !== false );
        AppstvScanner._feedback( accepted );
        if ( !accepted ) {
            AppstvScanner._status( serial + ' was not added', 'error' );
            return;
        }

        AppstvScanner.count++;
        $( '#appstv_scanner_overlay .appstv-scanner-count' ).text( '(' + AppstvScanner.count + ')' );
        $( '#appstv_scanner_overlay .appstv-scanner-last' ).prepend( $( '<span>' ).text( serial ) )
            .children().slice( 3 ).remove();
        AppstvScanner._status( 'Added ' + serial, 'ok' );

        if ( !o.continuous ) { AppstvScanner.close(); }
    },

    /**
     * @description Beep, vibration and a green / red flash of the frame
     * @param {boolean} ok - True for an accepted code
     * @returns {void}
     */
    _feedback : function ( ok ) {
        try {
            if ( AppstvScanner.audioCtx === null ) {
                var Ctx = window.AudioContext || window.webkitAudioContext;
                AppstvScanner.audioCtx = Ctx ? new Ctx() : null;
            }
            if ( AppstvScanner.audioCtx !== null ) {
                var ctx = AppstvScanner.audioCtx;
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.frequency.value = ok ? 1800 : 400;
                gain.gain.value = 0.15;
                osc.connect( gain );
                gain.connect( ctx.destination );
                osc.start();
                osc.stop( ctx.currentTime + ( ok ? 0.08 : 0.25 ) );
            }
        } catch ( e ) { /* Sound is optional */ }

        if ( navigator.vibrate ) { navigator.vibrate( ok ? 60 : [ 80, 60, 80 ] ); }

        var $frame = $( '#appstv_scanner_overlay .appstv-scanner-frame' );
        $frame.removeClass( 'flash-ok flash-error' );
        void $frame.get( 0 ).offsetWidth;                      // Restarts the CSS animation
        $frame.addClass( ok ? 'flash-ok' : 'flash-error' );
    },

    /**
     * @description Sends the current camera frame to the server decoder (for labels the camera cannot read live)
     * @returns {void}
     */
    _takePhoto : function () {
        var video = $( '#appstv_scanner_video' ).get( 0 );     // The live camera picture
        if ( !video || video.videoWidth === 0 || AppstvScanner.busy ) { return; }

        var canvas = document.createElement( 'canvas' );       // Full size copy for the server
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext( '2d' ).drawImage( video, 0, 0 );

        AppstvScanner.busy = true;
        AppstvScanner._status( 'Reading the photo…', '' );
        canvas.toBlob( function ( blob ) {
            AppstvScanner.decodeOnServer( blob, 'scan_photo.jpg', function ( serial ) {
                AppstvScanner.busy = false;
                AppstvScanner.lastSeen = {};                   // A photo is a deliberate scan: no repeat filter
                AppstvScanner._found( serial );
            }, function ( message ) {
                AppstvScanner.busy = false;
                AppstvScanner._feedback( false );
                AppstvScanner._status( message, 'error' );
            } );
        }, 'image/jpeg', 0.92 );
    },

    /**
     * @description Shows the Light button when the camera has a torch
     * @param {MediaStream} stream - The camera stream
     * @returns {void}
     */
    _setupTorch : function ( stream ) {
        var track = stream.getVideoTracks()[ 0 ];
        if ( track && typeof track.getCapabilities === 'function' && track.getCapabilities().torch ) {
            $( '#appstv_scanner_overlay [data-scanner-action="torch"]' ).removeClass( 'd-none' );
        }
    },

    /**
     * @description Switches the camera torch on or off
     * @param {jQuery} $button - The Light button (gets the active class)
     * @returns {void}
     */
    _toggleTorch : function ( $button ) {
        if ( !AppstvScanner.stream ) { return; }
        var track = AppstvScanner.stream.getVideoTracks()[ 0 ];
        var on = !$button.hasClass( 'active' );
        track.applyConstraints( { advanced: [ { torch: on } ] } ).then( function () {
            $button.toggleClass( 'active', on );
        } ).catch( function () {} );
    }
};
