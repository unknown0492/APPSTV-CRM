/**
 * appstv_scan_serial.js
 * Reusable serial number scanner module (camera + static image)
 * Uses jsQR (QR codes) and QuaggaJS (barcodes)
 * 
 * Usage:
 *   AppStvScanSerial.openCamera(videoEl, canvasEl, onDetected, onError);
 *   AppStvScanSerial.stopCamera();
 *   AppStvScanSerial.scanImageFile(file, onDetected, onError);
 */

var AppStvScanSerial = (function () {

    var _stream       = null;
    var _animFrame    = null;
    var _detected     = false;

    /**
     * Open camera and start scanning for barcodes/QR codes.
     * @param {HTMLVideoElement}  videoEl     The <video> element to stream into
     * @param {HTMLCanvasElement} canvasEl    Off-screen canvas for jsQR
     * @param {Function}          onDetected  Callback(serialNumber)
     * @param {Function}          onError     Callback(errorMessage)
     */
    function openCamera(videoEl, canvasEl, onDetected, onError) {
        _detected = false;

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            onError('Camera access is not supported on this browser or connection.');
            return;
        }

        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function (stream) {
                _stream = stream;
                videoEl.srcObject = stream;
                videoEl.play();
                _scanLoop(videoEl, canvasEl, onDetected);
            })
            .catch(function (err) {
                onError('Camera access denied or unavailable: ' + err.message);
            });
    }

    /**
     * Internal rAF loop — tries jsQR first, then QuaggaJS static decode.
     */
    function _scanLoop(videoEl, canvasEl, onDetected) {
        if (_detected) return;

        if (videoEl.readyState === videoEl.HAVE_ENOUGH_DATA) {
            var ctx = canvasEl.getContext('2d');
            canvasEl.width  = videoEl.videoWidth;
            canvasEl.height = videoEl.videoHeight;
            ctx.drawImage(videoEl, 0, 0, canvasEl.width, canvasEl.height);

            var imageData = ctx.getImageData(0, 0, canvasEl.width, canvasEl.height);

            // 1. Try jsQR
            if (typeof jsQR !== 'undefined') {
                var qrCode = jsQR(imageData.data, imageData.width, imageData.height, {
                    inversionAttempts: 'dontInvert'
                });
                if (qrCode && qrCode.data) {
                    _detected = true;
                    stopCamera();
                    onDetected(qrCode.data.trim());
                    return;
                }
            }

            // 2. Try Quagga (barcode)
            if (typeof Quagga !== 'undefined') {
                Quagga.decodeSingle({
                    decoder: { readers: ['code_128_reader', 'ean_reader', 'ean_8_reader', 'upc_reader', 'code_39_reader'] },
                    locate: true,
                    src: canvasEl.toDataURL()
                }, function (result) {
                    if (result && result.codeResult && result.codeResult.code && !_detected) {
                        _detected = true;
                        stopCamera();
                        onDetected(result.codeResult.code.trim());
                    }
                });
            }
        }

        _animFrame = requestAnimationFrame(function () {
            _scanLoop(videoEl, canvasEl, onDetected);
        });
    }

    /**
     * Stop camera stream and cancel rAF loop.
     */
    function stopCamera() {
        if (_animFrame) {
            cancelAnimationFrame(_animFrame);
            _animFrame = null;
        }
        if (_stream) {
            _stream.getTracks().forEach(function (t) { t.stop(); });
            _stream = null;
        }
        _detected = false;
    }

    /**
     * Attempt to decode a serial number from a static image File object.
     * @param {File}     file        Image file (PNG/JPG)
     * @param {Function} onDetected  Callback(serialNumber)
     * @param {Function} onError     Callback(errorMessage)
     */
    function scanImageFile(file, onDetected, onError) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var img = new Image();
            img.onload = function () {
                var canvas = document.createElement('canvas');
                canvas.width  = img.width;
                canvas.height = img.height;
                var ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0);
                var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

                // Try jsQR
                if (typeof jsQR !== 'undefined') {
                    var qrCode = jsQR(imageData.data, imageData.width, imageData.height, {
                        inversionAttempts: 'attemptBoth'
                    });
                    if (qrCode && qrCode.data) {
                        onDetected(qrCode.data.trim());
                        return;
                    }
                }

                // Try Quagga
                if (typeof Quagga !== 'undefined') {
                    Quagga.decodeSingle({
                        decoder: { readers: ['code_128_reader', 'ean_reader', 'ean_8_reader', 'upc_reader', 'code_39_reader'] },
                        locate: true,
                        src: canvas.toDataURL()
                    }, function (result) {
                        if (result && result.codeResult && result.codeResult.code) {
                            onDetected(result.codeResult.code.trim());
                        } else {
                            onError('Could not decode barcode/QR from this image. Try scanning via camera instead.');
                        }
                    });
                } else {
                    onError('Barcode decoder not loaded.');
                }
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    return {
        openCamera:    openCamera,
        stopCamera:    stopCamera,
        scanImageFile: scanImageFile
    };

})();