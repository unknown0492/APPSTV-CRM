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
                    var info    = jSon[ 'info' ];
                    var data    = info[ 'data' ];
                    var total   = info[ 'total_records' ];

                    var html = '';

                    $.each( data, function ( i, record ) {

                        var statusClass = record.warranty_status === 'Active'
                            ? 'badge badge-light-success'
                            : record.warranty_status === 'Expired'
                                ? 'badge badge-light-danger'
                                : 'badge badge-light-warning';

                        html += '<div class="warranty-record-card mb-6">';
                        html += '<table class="table table-bordered table-row-dashed fs-7 gy-2 gx-3">';
                        html += '<tbody>';
                        html += '<tr><td class="fw-bold text-gray-600 w-40">Company</td><td class="fw-bold fs-6">' + ( record.company_name || '—' ) + '</td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Customer Name</td><td>' + ( ( record.first_name || '' ) + ' ' + ( record.last_name || '' ) ).trim() + '</td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Warranty Status</td><td><span class="' + statusClass + ' fs-7 fw-bold">' + record.warranty_status + '</span></td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Serial Number</td><td><span style="font-family: monospace; font-size: 1.15rem; letter-spacing: 0.35rem; font-weight: 600;">' + ( record.serial_number || '—' ) + '</span></td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Product Title</td><td>' + ( record.product_title || '—' ) + '</td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Product Name</td><td>' + ( record.product_name  || '—' ) + '</td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Invoice No.</td><td>' + ( record.invoice_no   || '—' ) + '</td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Invoice Date</td><td>' + _formatDate( record.invoice_date ) + '</td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Warranty Start</td><td>' + _formatDate( record.warranty_start_date ) + '</td></tr>';
                        html += '<tr><td class="fw-bold text-gray-600">Warranty Expiry</td><td>' + _formatDate( record.warranty_expiry ) + '</td></tr>';html += '<tr><td class="fw-bold text-gray-600">Remarks</td><td>' + ( record.remarks || '—' ) + '</td></tr>';
                        if ( record.replacement ) {
                            var rep = record.replacement;
                            html += '<tr><td colspan="2" class="fw-bold text-warning fs-7 pt-4">⚠️ Warranty Replacement Record</td></tr>';
                            html += '<tr><td class="fw-bold text-gray-600">Date of Service</td><td>' + _formatDate( rep.date_of_service ) + '</td></tr>';
                            html += '<tr><td class="fw-bold text-gray-600">New Serial Number</td><td><span style="font-family: monospace; font-size: 1.15rem; letter-spacing: 0.35rem; font-weight: 600;">' + ( rep.new_serial_number || '—' ) + '</span></td></tr>';
                            html += '<tr><td class="fw-bold text-gray-600">New Invoice No.</td><td>' + ( rep.new_invoice_no || '—' ) + '</td></tr>';
                            html += '<tr><td class="fw-bold text-gray-600">Repairable</td><td>' + ( rep.is_repairable == 1 ? 'Yes' : 'No' ) + '</td></tr>';
                            html += '<tr><td class="fw-bold text-gray-600">Replacement Remarks</td><td>' + ( rep.remarks || '—' ) + '</td></tr>';
                        }
                        html += '</tbody></table>';
                        html += '</div>';

                        if ( i < data.length - 1 ) {
                            html += '<div class="warranty-or-separator my-4"><span class="warranty-or-line"></span><span class="warranty-or-text">RECORD ' + ( i + 2 ) + '</span><span class="warranty-or-line"></span></div>';
                        }
                    } );

                    // Show results in a SweetAlert with scrollable content
                    Swal.fire( {
                        title              : total + ' Record' + ( total > 1 ? 's' : '' ) + ' Found',
                        html               : html,
                        icon               : 'success',
                        confirmButtonText  : 'Close',
                        confirmButtonClass : 'btn btn-primary',
                        customClass        : { confirmButton: 'btn btn-primary' },
                        width              : '640px',
                        heightAuto         : false,
                        customClass        : {
                            confirmButton : 'btn btn-primary',
                            popup         : 'swal-warranty-popup'
                        }
                    } );

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