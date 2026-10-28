"use strict";

/* =============================================
   appstv_warranty_information.js
   Plugin: appstv_warranty
   ============================================= */

var WarrantyCheck = (function () {

    // ── Private state ──────────────────────────────────────────────
    var _codeReader     = null;   // ZXing reader instance
    var _activeStream   = null;   // MediaStream (camera)
    var _isScanning     = false;

    // ── DOM refs ───────────────────────────────────────────────────
    var _modal              = document.getElementById( 'modal_check_warranty_status' );
    var _btnOpenModal       = document.getElementById( 'btn_open_check_warranty_modal' );
    var _btnCloseModal      = document.getElementById( 'btn_close_warranty_modal' );
    var _btnStartScan       = document.getElementById( 'btn_start_scan' );
    var _btnCapture         = document.getElementById( 'btn_capture' );
    var _btnStopScan        = document.getElementById( 'btn_stop_scan' );
    var _chkAutoCapture     = document.getElementById( 'chk_auto_capture' );
    var _divCamera          = document.getElementById( 'div_camera_container' );
    var _videoPreview       = document.getElementById( 'video_camera_preview' );
    var _canvasCapture      = document.getElementById( 'canvas_capture' );
    var _inputBrowse        = document.getElementById( 'input_browse_image' );
    var _btnBrowse          = document.getElementById( 'btn_browse_image' );
    var _divBrowsedFilename = document.getElementById( 'div_browsed_filename' );
    var _inputManual        = document.getElementById( 'input_manual_serial_number' );
    var _btnManualGo        = document.getElementById( 'btn_manual_go' );

    // ── Helpers ────────────────────────────────────────────────────

    function _stopCamera() {
        _isScanning = false;

        if ( _codeReader ) {
            if ( _codeReader && _codeReader._stopLoop ) { _codeReader._stopLoop(); }
            _codeReader = null;
        }

        if ( _activeStream ) {
            _activeStream.getTracks().forEach( function ( track ) { track.stop(); } );
            _activeStream = null;
        }

        _videoPreview.srcObject = null;

        _divCamera.classList.add( 'd-none' );
        _btnCapture.classList.add( 'd-none' );
        _btnStopScan.classList.add( 'd-none' );
        _btnStartScan.classList.remove( 'd-none' );
    }

    // ── Replace _onCodeDetected() ──────────────────────────────────────────────
 
    function _onCodeDetected( blob ) {
        _stopCamera();
        _sendImageToServer( blob, 'scan_capture.png' );
    }
    
   

    function _startAutoScan() {
        var _detected   = false;
        var _loopActive = true;

        if ( _codeReader ) { _codeReader = null; }
        _codeReader = { _stopLoop: function () { _loopActive = false; } };

        function _scanFrame() {
            if ( !_loopActive || _detected ) { return; }

            if ( _videoPreview.readyState === _videoPreview.HAVE_ENOUGH_DATA ) {
                _canvasCapture.width  = _videoPreview.videoWidth;
                _canvasCapture.height = _videoPreview.videoHeight;
                var ctx = _canvasCapture.getContext( '2d', { willReadFrequently: true } );
                ctx.drawImage( _videoPreview, 0, 0 );

                var imageData = ctx.getImageData( 0, 0, _canvasCapture.width, _canvasCapture.height );

                // Try jsQR first
                var qrResult = jsQR( imageData.data, _canvasCapture.width, _canvasCapture.height, {
                    inversionAttempts: 'attemptBoth'
                } );

                if ( qrResult && !_detected ) {
                    _detected   = true;
                    _loopActive = false;
                    _canvasCapture.toBlob( function ( blob ) {
                        _stopCamera();
                        _sendImageToServer( blob, 'scan_auto.png' );
                    }, 'image/png' );
                    return;
                }

                // Try Quagga every 10 frames for barcodes
                _scanFrame._frameCount = ( _scanFrame._frameCount || 0 ) + 1;
                if ( _scanFrame._frameCount % 10 === 0 && !_detected ) {
                    var dataUrl = _canvasCapture.toDataURL( 'image/png' );
                    Quagga.decodeSingle( {
                        decoder : {
                            readers  : [ 'code_128_reader', 'ean_reader', 'ean_8_reader', 'code_39_reader', 'code_93_reader', 'upc_reader', 'upc_e_reader' ],
                            multiple : false
                        },
                        locate : true,
                        src    : dataUrl
                    }, function ( result ) {
                        if ( result && result.codeResult && !_detected ) {
                            _detected   = true;
                            _loopActive = false;
                            _canvasCapture.toBlob( function ( blob ) {
                                _stopCamera();
                                _sendImageToServer( blob, 'scan_auto.png' );
                            }, 'image/png' );
                        }
                    } );
                }
            }

            requestAnimationFrame( _scanFrame );
        }

        requestAnimationFrame( _scanFrame );
    }

    function _startManualScan() {
        // Camera is already running via getUserMedia; user clicks Capture manually
    }

    // ── Camera Start ───────────────────────────────────────────────

    function _startCamera() {
        if ( _isScanning ) { return; }
        _isScanning = true;

        var constraints = { video: { facingMode: 'environment' }, audio: false };

        navigator.mediaDevices.getUserMedia( constraints )
            .then( function ( stream ) {
                _activeStream           = stream;
                _videoPreview.srcObject = stream;

                _divCamera.classList.remove( 'd-none' );
                _btnStartScan.classList.add( 'd-none' );
                _btnStopScan.classList.remove( 'd-none' );

                // Wait for video to be ready before starting ZXing
                _videoPreview.onloadedmetadata = function () {
                    _videoPreview.play().then( function () {
                        if ( _chkAutoCapture.checked ) {
                            _startAutoScan();
                        } else {
                            _btnCapture.classList.remove( 'd-none' );
                        }
                    } ).catch( function ( err ) {
                        _isScanning = false;
                        alert( 'Could not start video playback: ' + err.message );
                    } );
                };
            } )
            .catch( function ( err ) {
                _isScanning = false;
                if ( err.name === 'NotAllowedError' ) {
                    alert( 'Camera permission was denied. Please allow camera access in your browser settings.' );
                } else if ( err.name === 'NotFoundError' ) {
                    alert( 'No camera found on this device.' );
                } else if ( location.protocol !== 'https:' && location.hostname !== 'localhost' ) {
                    alert( 'Camera requires a secure connection (HTTPS). Please access this page via HTTPS or localhost.' );
                } else {
                    alert( 'Camera error: ' + err.message );
                }
            } );
    }

    // ── Replace _captureSnapshot() ────────────────────────────────────────────
 
    function _captureSnapshot() {
        _canvasCapture.width  = _videoPreview.videoWidth;
        _canvasCapture.height = _videoPreview.videoHeight;
        var ctx = _canvasCapture.getContext( '2d', { willReadFrequently: true } );
        ctx.drawImage( _videoPreview, 0, 0 );

        _canvasCapture.toBlob( function ( blob ) {
            _stopCamera();
            _sendImageToServer( blob, 'scan_capture.png' );
        }, 'image/png' );
    }
    
    // ── Browse ─────────────────────────────────────────────────────

    // ── Replace _handleBrowsedFile() ──────────────────────────────────────────
 
    function _handleBrowsedFile( file ) {
        if ( !file ) { return; }

        _divBrowsedFilename.textContent = file.name;
        _divBrowsedFilename.classList.remove( 'd-none' );

        _sendImageToServer( file, file.name );
    }
    // ── Warranty results (v1.0.4) ──────────────────────────────────

    /**
     * @description Escapes text for safe use inside the results HTML
     * @param {*} value - Any value from the server
     * @returns {string} HTML-safe text, or an em dash when empty
     */
    function _esc( value ) {
        if ( value === null || value === undefined || value === '' ) { return '—'; }
        return $( '<div>' ).text( String( value ) ).html();
    }

    /**
     * @description Returns the badge class for a warranty status
     * @param {string} status - Active, Active (Extended), Expired or No Warranty Found
     * @returns {string} CSS classes
     */
    function _statusClass( status ) {
        if ( status === 'Active' || status === 'Active (Extended)' ) { return 'badge badge-light-success'; }
        if ( status === 'Expired' ) { return 'badge badge-light-danger'; }
        return 'badge badge-light-warning';
    }

    /**
     * @description Builds one table row
     * @param {string} label - Left column text
     * @param {string} valueHtml - Right column HTML (already escaped)
     * @returns {string} Row HTML
     */
    function _row( label, valueHtml ) {
        return '<tr><td class="fw-bold text-gray-600 w-40">' + label + '</td><td>' + valueHtml + '</td></tr>';
    }

    /**
     * @description Formats a serial number in the monospace style used on this page
     * @param {string} serial - Serial number
     * @returns {string} HTML
     */
    function _serialHtml( serial ) {
        return '<span class="warranty-serial">' + _esc( serial ) + '</span>';
    }

    /**
     * @description Describes a sale (customer, invoice no. and date) on one line
     * @param {object|null} sale - company_name, first_name, last_name, invoice_no, invoice_date
     * @returns {string} HTML
     */
    function _saleHtml( sale ) {
        if ( !sale ) { return '—'; }
        var name = sale.company_name || ( ( sale.first_name || '' ) + ' ' + ( sale.last_name || '' ) ).trim();   // Company, else person
        return '<span class="fw-bold">' + _esc( name ) + '</span><br />Invoice ' + _esc( sale.invoice_no ) + ' &middot; ' + _formatDate( sale.invoice_date );
    }

    /**
     * @description Builds the service history (all events, oldest first) of one warranty
     * @param {Array} events - Service events from the server
     * @returns {string} HTML
     */
    function _eventsHtml( events ) {
        if ( !events || events.length === 0 ) {
            return '<div class="text-gray-500 fs-7">No service events</div>';
        }
        var html = '<ol class="warranty-events">';   // Service events in date order
        $.each( events, function ( i, ev ) {
            var isReplacement = ev.type === 'replacement';   // A new TV was given
            html += '<li class="' + ( ev.is_scanned_tv ? 'warranty-event-scanned' : '' ) + '">';
            html += '<div class="fw-bold">' + _formatDate( ev.date_of_service ) + ' &middot; '
                  + ( isReplacement ? '<span class="text-warning">Replaced</span>' : '<span class="text-primary">Repaired</span>' ) + '</div>';
            if ( isReplacement ) {
                html += '<div>New TV: ' + _serialHtml( ev.new_serial_number )
                      + ( ev.new_product_title ? ' <span class="text-gray-600">(' + _esc( ev.new_product_title ) + ')</span>' : '' )
                      + ( ev.is_scanned_tv ? ' <span class="badge badge-light-primary">this TV</span>' : '' ) + '</div>';
            }
            if ( ev.new_invoice_no ) {
                html += '<div>Service invoice: ' + _esc( ev.new_invoice_no ) + '</div>';
            }
            html += '<div class="text-gray-700">' + _esc( ev.remarks ) + '</div>';
            html += '</li>';
        } );
        return html + '</ol>';
    }

    /**
     * @description Builds one warranty history: original TV, sale, warranty, extended warranty, status and events
     * @param {object} history - One entry of record.histories
     * @returns {string} HTML
     */
    function _historyHtml( history ) {
        var title = history.role === 'original'
            ? 'Warranty of this TV'
            : 'Given as a replacement under the warranty of TV ' + _esc( history.original_serial_number );   // Heading of this history
        var html  = '<div class="warranty-history mt-4">';
        html += '<div class="warranty-history-title">' + title + '</div>';
        html += '<table class="table table-bordered table-row-dashed fs-7 gy-2 gx-3 mb-2"><tbody>';
        html += _row( 'Warranty Status', '<span class="' + _statusClass( history.warranty_status ) + ' fs-7 fw-bold">' + _esc( history.warranty_status ) + '</span>' );
        if ( history.role !== 'original' ) {
            html += _row( 'Original TV', _serialHtml( history.original_serial_number ) + '<br />' + _esc( history.original_product_title ) );
        }
        html += _row( 'Sold To', _saleHtml( history.original_sale ) );
        html += _row( 'Warranty', _formatDate( history.warranty_start_date ) + ' to ' + _formatDate( history.warranty_expiry ) );
        if ( history.extended_warranty ) {
            html += _row( 'Extended Warranty', _formatDate( history.extended_warranty.start_date ) + ' to ' + _formatDate( history.extended_warranty.expiry_date ) );
        }
        if ( history.events && history.events.length > 0 ) {
            html += _row( 'TV In Service Now', _serialHtml( history.current_serial_number ) );
        }
        html += _row( 'Remarks', _esc( history.remarks ) );
        html += '</tbody></table>';
        html += '<div class="fw-bold text-gray-700 fs-7 mb-1">Service History</div>';
        html += _eventsHtml( history.events );
        return html + '</div>';
    }

    /**
     * @description Shows every TV found for the serial number, each with its full warranty history
     * @param {object} info - Server reply: total_records and data (one record per TV)
     * @returns {void}
     */
    function _showWarrantyResults( info ) {
        var data  = info[ 'data' ];            // One record per TV with this serial number
        var total = info[ 'total_records' ];   // Number of TVs
        var html  = '';

        if ( total > 1 ) {
            html += '<div class="alert alert-light-warning fs-7 py-2">' + total + ' TVs carry this serial number. Each is shown separately.</div>';
        }

        $.each( data, function ( i, record ) {
            html += '<div class="warranty-record-card mb-6">';
            html += '<table class="table table-bordered table-row-dashed fs-7 gy-2 gx-3 mb-0"><tbody>';
            html += _row( 'Serial Number', _serialHtml( record.serial_number ) );
            html += _row( 'Product', _esc( record.product_title ) );
            html += _row( 'Sold To', _saleHtml( record.sale ) );
            html += '</tbody></table>';

            if ( !record.histories || record.histories.length === 0 ) {
                html += '<div class="mt-3"><span class="badge badge-light-warning fs-7 fw-bold">No Warranty Found</span></div>';
            }
            $.each( record.histories || [], function ( j, history ) {
                html += _historyHtml( history );
            } );
            html += '</div>';

            if ( i < data.length - 1 ) {
                html += '<div class="warranty-or-separator my-4"><span class="warranty-or-line"></span><span class="warranty-or-text">TV ' + ( i + 2 ) + '</span><span class="warranty-or-line"></span></div>';
            }
        } );

        // Show results in a SweetAlert with scrollable content
        Swal.fire( {
            title             : total + ' Record' + ( total > 1 ? 's' : '' ) + ' Found',
            html              : html,
            icon              : 'success',
            confirmButtonText : 'Close',
            width             : '680px',
            heightAuto        : false,
            customClass       : {
                confirmButton : 'btn btn-primary',
                popup         : 'swal-warranty-popup'
            }
        } );
    }

    // ── Submit (AJAX placeholder) ──────────────────────────────────

    function _submitSerialNumber() {
        var serial_number = $( _inputManual ).val().trim();
 
        if ( serial_number === '' ) {
            showSimpleToast( 'error', 'Please enter a serial number' );
            return;
        }
 
        showLoadingSweetAlert( 'Validating', 'Please wait while the Serial Number is being validated' );
 
        var data = {
            what_do_you_want : 'appstv_read_sn_info',
            serial_number    : serial_number
        };
 
        $.ajax( {
            url     : getWebservice(),
            type    : 'POST',
            data    : data,
            success : function ( returned_data ) {
                console.log( returned_data );

                hideLoadingSweetAlert();

                var jSon = parseJSONWithError( returned_data, 'Server Side error occurred ! Contact Technical Support...' );
                if ( jSon == false ) { return; }

                jSon = jSon[ 0 ];

                if ( jSon[ 'type' ] == 'error' ) {
                    var message = jSon[ 'info' ][ 'message' ];
                    showSimpleSweetAlert( message, 'error', 'Close', 'btn btn-danger' );
                    return;
                }

                if ( jSon[ 'type' ] == 'success' ) {
                    _showWarrantyResults( jSon[ 'info' ] );
                    return;
                }
            }
        } );
    }


    // ── Reset modal state ──────────────────────────────────────────

    function _resetModal() {
        _stopCamera();
        _inputManual.value = '';
        _inputBrowse.value = '';
        _divBrowsedFilename.classList.add( 'd-none' );
        _divBrowsedFilename.textContent = '';
        _divCamera.classList.remove( 'scan-success' );
    }

    // ── Event Bindings ─────────────────────────────────────────────
    
    // ── Replace _processSerialNumber calls in _onCodeDetected, _captureSnapshot,
    //    _handleBrowsedFile with _sendImageToServer.
    //    Add this new function anywhere before _bindEvents() ──────────────────────

    function _sendImageToServer( blob, filename ) {
        var formData = new FormData();
        formData.append( 'what_do_you_want', 'appstv_decode_qr_barcode' );
        formData.append( 'scan_image', blob, filename );

        $.ajax( {
            url     : 'webservice.php',
            type    : 'POST',
            data    : formData,
            processData : false,
            contentType : false,
            success : function ( returned_data ) {
                var jSon = $.parseJSON( returned_data );
                $.each( jSon, function () {
                    if ( this[ 'type' ] === 'error' ) {
                        _inputManual.value = '';
                        alert( this[ 'info' ] );
                        return;
                    }
                    if ( this[ 'type' ] === 'success' ) {
                        _inputManual.value = this[ 'info' ][ 'serial_number' ];
                        return;
                    }
                } );
            },
            error : function () {
                _inputManual.value = '';
                alert( 'Server error occurred. Please try again.' );
            }
        } );
    }

    function _bindEvents() {

        // Modal open is handled via data-bs-toggle on the button

        // Close modal → stop camera
        _btnCloseModal.addEventListener( 'click', function () {
            _resetModal();
        } );

        // Also stop camera if modal is closed via backdrop/ESC
        _modal.addEventListener( 'hidden.bs.modal', function () {
            _resetModal();
        } );

        // Start Scan button
        _btnStartScan.addEventListener( 'click', function () {
            _startCamera();
        } );

        // Stop Scan button
        _btnStopScan.addEventListener( 'click', function () {
            _stopCamera();
        } );

        // Capture button (manual mode) - touchend for Android, click for desktop
        _btnCapture.addEventListener( 'click', function () {
            _captureSnapshot();
        } );
        _btnCapture.addEventListener( 'touchend', function ( e ) {
            e.preventDefault();
            _captureSnapshot();
        } );
        // Auto-capture checkbox toggle: if camera is running, switch modes
        _chkAutoCapture.addEventListener( 'change', function () {
            if ( _isScanning ) {
                _stopCamera();
                _startCamera();
            }
        } );

        // Browse button
        _btnBrowse.addEventListener( 'click', function () {
            _inputBrowse.click();
        } );

        // File selected from Browse
        _inputBrowse.addEventListener( 'change', function () {
            _handleBrowsedFile( this.files[0] );
        } );

        // GO button — validate and submit serial number
        var btn_go = convertToAnimatedButton( $( '#btn_manual_go' ) );
 
        btn_go.on( 'click', function () {
            _submitSerialNumber();
        } );
 
        $( _inputManual ).on( 'keydown', function ( e ) {
            if ( e.key === 'Enter' ) {
                _submitSerialNumber();
            }
        } );

    }

    // ── Init ───────────────────────────────────────────────────────

    function init() {
        _bindEvents();
    }

    return { init: init };

})();

// Initialise on DOM ready
document.addEventListener( 'DOMContentLoaded', function () {
    WarrantyCheck.init();
} );

function _formatDate( dateStr ) {
    if ( !dateStr ) { return '—'; }
    var d = new Date( dateStr );
    if ( isNaN( d.getTime() ) ) { return dateStr; }
    var dd   = String( d.getDate() ).padStart( 2, '0' );
    var mm   = String( d.getMonth() + 1 ).padStart( 2, '0' );
    var yyyy = d.getFullYear();
    return dd + '/' + mm + '/' + yyyy;
}