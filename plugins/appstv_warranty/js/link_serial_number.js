/**
 * link_serial_number.js
 * JS logic for the "Link Serial Number to Customer" modal
 */

var LSN = (function ($) {

    // ── serial number row counter ──────────────────────────────────────────
    var _snCounter       = 0;
    // ── which row's scan overlay is active ────────────────────────────────
    var _activeScanRowId = null;
    // ── is replacement scan active ────────────────────────────────────────
    var _replacementScanActive = false;

    // ─────────────────────────────────────────────────────────────────────
    // INIT
    // ─────────────────────────────────────────────────────────────────────
    function init() {
        _bindCustomerToggle();
        _bindCustomerSearch();
        _bindProductDropdown();
        _bindAddSerialNumber();
        _bindFileImport();
        _bindScanOverlayClose();
        _bindReplacementToggle();
        _bindReplacementScan();
        _bindSubmit();
        _bindReset();
        _initDatepickers();
    }

    // ─────────────────────────────────────────────────────────────────────
    // CUSTOMER TOGGLE
    // ─────────────────────────────────────────────────────────────────────
    function _updateSerialNumberCount() {
        var count = $('#lsn_serial_numbers_container .lsn-sn-row').length;
        $('#lsn_sn_count_badge').text('(' + count + ')');
    }
    
    function _bindCustomerToggle() {
        $('input[name="radio_customer_mode"]').on('change', function () {
            var mode = $(this).val();
            if (mode === 'new') {
                $('#section_new_customer').removeClass('d-none');
                $('#section_existing_customer').addClass('d-none');
            } else {
                $('#section_new_customer').addClass('d-none');
                $('#section_existing_customer').removeClass('d-none');
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // CUSTOMER AUTOCOMPLETE
    // ─────────────────────────────────────────────────────────────────────
    function _bindCustomerSearch() {
        var timeout = null;

        $('#lsn_customer_search').on('input', function () {
            clearTimeout(timeout);
            var q = $(this).val().trim();
            if (q.length < 2) {
                $('#lsn_customer_autocomplete').addClass('d-none').empty();
                return;
            }
            timeout = setTimeout(function () {
                $.ajax({
                    url: getWebservice(),
                    type: 'POST',
                    dataType: 'json',
                    data: { what_do_you_want: 'appstv_search_customers', q: q },
                    success: function (res) {
                        var json = _parseResponse(res);
                        if (!json || json.type !== 'success') return;
                        _renderCustomerAutocomplete(json.info.data);
                    }
                });
            }, 300);
        });

        // Close dropdown on outside click
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#lsn_customer_search, #lsn_customer_autocomplete').length) {
                $('#lsn_customer_autocomplete').addClass('d-none');
            }
        });

        // Clear selected customer
        $(document).on('click', '#btn_clear_selected_customer', function (e) {
            e.preventDefault();
            $('#lsn_selected_customer_badge').addClass('d-none');
            $('#lsn_selected_customer_id').val('');
            $('#lsn_customer_search').val('');
        });
    }

    function _renderCustomerAutocomplete(customers) {
        var $dd = $('#lsn_customer_autocomplete');
        $dd.empty();

        if (!customers || customers.length === 0) {
            $dd.append('<div class="lsn-ac-item text-muted">No customers found</div>');
            $dd.removeClass('d-none');
            return;
        }

        $.each(customers, function (i, c) {
            var name = _buildCustomerLabel(c);
            var $item = $('<div class="lsn-ac-item"></div>');
            $item.html(
                '<span class="lsn-ac-name">' + _esc(name) + '</span>' +
                '<span class="lsn-ac-sub">' + _esc(c.customer_id) + '</span>'
            );
            $item.on('click', function () {
                $('#lsn_selected_customer_label').text(name + ' — ' + c.customer_id);
                $('#lsn_selected_customer_id').val(c.customer_id);
                $('#lsn_selected_customer_badge').removeClass('d-none');
                $('#lsn_customer_search').val('');
                $dd.addClass('d-none').empty();
            });
            $dd.append($item);
        });

        $dd.removeClass('d-none');
    }

    function _buildCustomerLabel(c) {
        if (c.company_name && c.company_name.trim()) return c.company_name.trim();
        var parts = [c.first_name, c.middle_name, c.last_name].filter(Boolean);
        return parts.join(' ') || c.customer_id;
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRODUCT DROPDOWN
    // ─────────────────────────────────────────────────────────────────────
    function _bindProductDropdown() {
        _loadProducts();

        $('#btn_refresh_lsn_products').on('click', function () {
            var $btn = $(this);
            $btn.find('.indicator-label').addClass('d-none');
            $btn.find('.indicator-progress').removeClass('d-none');
            _loadProducts(function () {
                $btn.find('.indicator-label').removeClass('d-none');
                $btn.find('.indicator-progress').addClass('d-none');
            });
        });
    }

    function _loadProducts(callback) {
        $.ajax({
            url: getWebservice(),
            type: 'POST',
            dataType: 'json',
            data: { what_do_you_want: 'get_crm_products' },
            success: function (res) {
                var json = _parseResponse(res);
                if (!json || json.type !== 'success') {
                    if (callback) callback();
                    return;
                }
                var products = json.info.data;
                var $sel = $('#lsn_product_id');

                $sel.empty().append('<option></option>');
                $.each(products, function (i, p) {
                    var label = p.name + ' — ' + p.sku + ' / ' + p.product_id;
                    $sel.append('<option value="' + _esc(p.product_id) + '">' + _esc(label) + '</option>');
                });

                // Refresh Select2
                if ($sel.data('select2')) $sel.select2('destroy');
                $sel.select2({
                    placeholder: 'Select a product...',
                    dropdownParent: $('#modal_link_serial_number'),
                    templateResult: function (d) {
                        if (!d.id) return d.text;
                        var parts = d.text.split(' — ');
                        var $el = $(
                            '<span>' + _esc(parts[0] || d.text) + '</span>' +
                            '<br><small class="text-muted">' + _esc(parts[1] || '') + '</small>'
                        );
                        return $el;
                    }
                });

                if (callback) callback();
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // SERIAL NUMBER ROWS
    // ─────────────────────────────────────────────────────────────────────
    

    function _bindAddSerialNumber() {
        $('#btn_add_serial_number').on('click', function () {
            _addSerialNumberRow('');
            _updateSerialNumberCount();
        });
    }

    function _addSerialNumberRow(prefillValue) {
        _snCounter++;
        var id = 'sn_row_' + _snCounter;

        var $row = $('<div class="lsn-sn-row mb-3" id="' + id + '" data-sn-index="' + _snCounter + '"></div>');

        $row.html(
            '<div class="input-group input-group-solid">' +
                '<input type="text" class="form-control form-control-solid lsn-sn-input" ' +
                       'placeholder="Enter serial number" maxlength="50" ' +
                       'value="' + _esc(prefillValue) + '" ' +
                       'data-row-id="' + id + '" />' +
                '<button type="button" class="btn btn-secondary btn-verify-sn" data-row-id="' + id + '" title="Verify serial number">' +
                    '<span class="indicator-label"><i class="fa-solid fa-circle-check me-1"></i>Verify</span>' +
                    '<span class="indicator-progress"><span class="spinner-border spinner-border-sm"></span></span>' +
                '</button>' +
                '<button type="button" class="btn btn-info btn-scan-sn dropdown-toggle" data-row-id="' + id + '" ' +
                        'data-bs-toggle="dropdown" title="Scan serial number">' +
                    '<i class="fa-solid fa-camera"></i>' +
                '</button>' +
                '<ul class="dropdown-menu dropdown-menu-end">' +
                    '<li><a class="dropdown-item btn-scan-camera" href="#" data-row-id="' + id + '">' +
                        '<i class="fa-solid fa-video me-2"></i>Open Camera</a></li>' +
                    '<li><a class="dropdown-item btn-scan-gallery" href="#" data-row-id="' + id + '">' +
                        '<i class="fa-solid fa-image me-2"></i>Browse Image</a></li>' +
                '</ul>' +
                '<button type="button" class="btn btn-danger btn-delete-sn" data-row-id="' + id + '" title="Remove">' +
                    '<i class="fa-solid fa-trash"></i>' +
                '</button>' +
            '</div>' +
            // Hidden file input for gallery browse
            '<input type="file" class="d-none lsn-gallery-input" accept="image/png,image/jpeg" data-row-id="' + id + '" />' +
            // Feedback text
            '<div class="lsn-sn-feedback mt-1" id="feedback_' + id + '"></div>'
        );

        $('#lsn_serial_numbers_container').prepend($row);
        $('#lsn_no_sn_message').addClass('d-none');

        _bindRowEvents($row, id);
        _evaluateReplacementEligibility();

        if (prefillValue) {
            _verifySingleRow($row.find('.lsn-sn-input'), id);
        }
    }

    function _bindRowEvents($row, id) {
        // Delete
        $row.find('.btn-delete-sn').on('click', function () {
            $row.remove();
            _updateSerialNumberCount();
            if ($('#lsn_serial_numbers_container .lsn-sn-row').length === 0) {
                $('#lsn_no_sn_message').removeClass('d-none');
            }
            _evaluateReplacementEligibility();
        });

        // Verify
        $row.find('.btn-verify-sn').on('click', function () {
            _verifySingleRow($row.find('.lsn-sn-input'), id);
        });

        // Camera scan
        $row.find('.btn-scan-camera').on('click', function (e) {
            e.preventDefault();
            _activeScanRowId = id;
            _replacementScanActive = false;
            _openScanOverlay();
        });

        // Gallery browse
        $row.find('.btn-scan-gallery').on('click', function (e) {
            e.preventDefault();
            $row.find('.lsn-gallery-input').trigger('click');
        });

        $row.find('.lsn-gallery-input').on('change', function () {
            var file = this.files[0];
            if (!file) return;
            AppStvScanSerial.scanImageFile(file,
                function (val) {
                    $row.find('.lsn-sn-input').val(val);
                    _verifySingleRow($row.find('.lsn-sn-input'), id);
                },
                function (err) { _setFeedback(id, 'error', err); }
            );
        });

        // Strip non-numeric on input
        $row.find('.lsn-sn-input').on('input', function () {
            var cleaned = $(this).val().replace(/\D/g, '').substring(0, 50);
            $(this).val(cleaned);
            _clearFeedback(id);
            _evaluateReplacementEligibility();
        });
    }

    function _verifySingleRow($input, rowId) {
        var sn = $input.val().trim();
        if (!sn) {
            _setFeedback(rowId, 'error', 'Please enter a serial number first.');
            return;
        }

        var $btn = $('#' + rowId + ' .btn-verify-sn');
        $btn.find('.indicator-label').addClass('d-none');
        $btn.find('.indicator-progress').removeClass('d-none');
        $btn.prop('disabled', true);

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            dataType: 'json',
            data: { what_do_you_want: 'appstv_verify_serial_number', serial_number: sn },
            success: function (res) {
                $btn.find('.indicator-label').removeClass('d-none');
                $btn.find('.indicator-progress').addClass('d-none');
                $btn.prop('disabled', false);

                var json = _parseResponse(res);
                if (!json) return;

                var info = json.info;
                if (json.type === 'success') {
                    var d = info.data;
                    if (d.status === 'not_found') {
                        _setFeedback(rowId, 'error', 'Serial Number does not exist in the system.');
                        $input.attr('data-verified', 'not_found');
                    } else if (d.status === 'unallotted') {
                        _setFeedback(rowId, 'success',
                            'Serial Number exists &mdash; Product SN ID: <strong>' + _esc(d.product_sn_id) + '</strong>');
                        $input.attr('data-verified', 'unallotted').attr('data-product-sn-id', d.product_sn_id);
                    } else if (d.status === 'allotted') {
                        var customerLabel = d.company_name || d.customer_name || d.customer_id;
                        _setFeedback(rowId, 'error',
                            'Serial Number has already been allotted to customer: <strong>' + _esc(customerLabel) + '</strong>');
                        $input.attr('data-verified', 'allotted').attr('data-customer-label', customerLabel);
                    }
                } else {
                    _setFeedback(rowId, 'error', info.message || 'Verification failed.');
                }
                _evaluateReplacementEligibility();
            },
            error: function () {
                $btn.find('.indicator-label').removeClass('d-none');
                $btn.find('.indicator-progress').addClass('d-none');
                $btn.prop('disabled', false);
                _setFeedback(rowId, 'error', 'Server error during verification.');
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // FILE IMPORT (Excel / CSV)
    // ─────────────────────────────────────────────────────────────────────
    function _bindFileImport() {
        $('#lsn_sn_file_input').on('change', function () {
            var file = this.files[0];
            if (!file) return;

            var formData = new FormData();
            formData.append('what_do_you_want', 'appstv_read_serial_number_from_file');
            formData.append('sn_file', file);

            $.ajax({
                url: getWebservice(),
                type: 'POST',
                dataType: 'json',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    var json = _parseResponse(res);
                    if (!json || json.type !== 'success') {
                        showSimpleToast('error', json ? json.info.message : 'Failed to read file.');
                        return;
                    }

                    var serials = json.info.data.serial_numbers || [];
                    if (serials.length === 0) {
                        showSimpleToast('warning', 'No serial numbers found in the file.');
                        return;
                    }

                    $.each(serials, function (i, item) {
                        // Add the row with the serial number pre-filled
                        _addSerialNumberRow(item.serial_number);

                        // Find the row that was just added (last row in container)
                        var $rows    = $('#lsn_serial_numbers_container .lsn-sn-row');
                        var $lastRow = $rows.last();
                        var rowId    = $lastRow.attr('id');
                        var $input   = $lastRow.find('.lsn-sn-input');

                        // Set verified attributes and feedback based on status
                        if (item.status === 'unallotted') {
                            $input.attr('data-verified', 'unallotted')
                                  .attr('data-product-sn-id', item.product_sn_id);
                            _setFeedback(rowId, 'success',
                                'Serial Number exists &mdash; Product SN ID: <strong>' + _esc(item.product_sn_id) + '</strong>');

                        } else if (item.status === 'allotted') {
                            var customerLabel = item.company_name || item.customer_name || item.customer_id;
                            $input.attr('data-verified', 'allotted')
                                  .attr('data-customer-label', customerLabel);
                            _setFeedback(rowId, 'error',
                                'Serial Number has already been allotted to: <strong>' + _esc(customerLabel) + '</strong>');

                        } else if (item.status === 'not_found') {
                            $input.attr('data-verified', 'not_found');
                            _setFeedback(rowId, 'error', 'Serial Number does not exist in the system.');
                        }
                    });

                    _evaluateReplacementEligibility();
                    _updateSerialNumberCount();
                    showSimpleToast('success', serials.length + ' serial number(s) imported and verified.');
                },
                error: function () {
                    showSimpleToast('error', 'Failed to upload file.');
                }
            });

            // Reset input so same file can be re-selected
            $(this).val('');
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // SCAN OVERLAY
    // ─────────────────────────────────────────────────────────────────────
    function _openScanOverlay() {
        var $overlay = $('#lsn_scan_modal_overlay');
        var videoEl  = document.getElementById('lsn_scan_video');
        var canvasEl = document.getElementById('lsn_scan_canvas');

        $overlay.removeClass('d-none');

        AppStvScanSerial.openCamera(videoEl, canvasEl,
            function (val) {
                $overlay.addClass('d-none');
                if (_replacementScanActive) {
                    $('#lsn_replacement_sn').val(val);
                    // Auto-trigger replacement verify
                    _verifyReplacementSN();
                } else if (_activeScanRowId) {
                    var $row = $('#' + _activeScanRowId);
                    $row.find('.lsn-sn-input').val(val);
                    _verifySingleRow($row.find('.lsn-sn-input'), _activeScanRowId);
                }
                _activeScanRowId = null;
                _replacementScanActive = false;
            },
            function (err) {
                $overlay.addClass('d-none');
                showSimpleToast('error', err);
            }
        );
    }

    function _bindScanOverlayClose() {
        $('#btn_close_scan_overlay').on('click', function () {
            AppStvScanSerial.stopCamera();
            $('#lsn_scan_modal_overlay').addClass('d-none');
            _activeScanRowId = null;
            _replacementScanActive = false;
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // REPLACEMENT SECTION
    // ─────────────────────────────────────────────────────────────────────
    function _evaluateReplacementEligibility() {
        var $rows    = $('#lsn_serial_numbers_container .lsn-sn-row');
        var $chk     = $('#chk_enable_replacement');
        var $lbl     = $('#lbl_replacement_toggle');

        if ($rows.length !== 1) {
            $chk.prop('disabled', true).prop('checked', false);
            $('#section_replacement_fields').addClass('d-none');
            $lbl.text('Only available with exactly 1 serial number');
            return;
        }

        var $input   = $rows.first().find('.lsn-sn-input');
        var verified = $input.attr('data-verified');

        if (verified === 'allotted') {
            $chk.prop('disabled', false);
            $lbl.text('Enable replacement for this serial number');
        } else {
            $chk.prop('disabled', true).prop('checked', false);
            $('#section_replacement_fields').addClass('d-none');
            $lbl.text(verified === 'unallotted'
                ? 'Serial number is not yet allotted — replacement not applicable'
                : 'Verify the serial number first');
        }
    }

    function _bindReplacementToggle() {
        $('#chk_enable_replacement').on('change', function () {
            if ($(this).is(':checked')) {
                $('#section_replacement_fields').removeClass('d-none');
            } else {
                $('#section_replacement_fields').addClass('d-none');
            }
        });
    }

    function _bindReplacementScan() {
        $('#btn_scan_replacement_sn').on('click', function () {
            _replacementScanActive = true;
            _activeScanRowId = null;
            _openScanOverlay();
        });

        $('#btn_verify_replacement_sn').on('click', function () {
            _verifyReplacementSN();
        });

        $('#lsn_replacement_sn').on('input', function () {
            var cleaned = $(this).val().replace(/\D/g, '').substring(0, 50);
            $(this).val(cleaned);
            $('#lsn_replacement_sn_feedback').html('');
        });
    }

    function _verifyReplacementSN() {
        var sn = $('#lsn_replacement_sn').val().trim();
        if (!sn) {
            $('#lsn_replacement_sn_feedback').html(
                '<span class="text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Please enter a serial number.</span>');
            return;
        }

        var $btn = $('#btn_verify_replacement_sn');
        $btn.find('.indicator-label').addClass('d-none');
        $btn.find('.indicator-progress').removeClass('d-none');
        $btn.prop('disabled', true);

        $.ajax({
            url: getWebservice(),
            type: 'POST',
            dataType: 'json',
            data: { what_do_you_want: 'appstv_verify_serial_number', serial_number: sn },
            success: function (res) {
                $btn.find('.indicator-label').removeClass('d-none');
                $btn.find('.indicator-progress').addClass('d-none');
                $btn.prop('disabled', false);

                var json = _parseResponse(res);
                if (!json) return;
                var d = json.info.data;

                var $fb = $('#lsn_replacement_sn_feedback');
                if (d.status === 'not_found') {
                    $fb.html('<span class="text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Serial Number does not exist in the system.</span>');
                    $('#lsn_replacement_sn').attr('data-verified', 'not_found');
                } else if (d.status === 'unallotted') {
                    $fb.html('<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i>Serial Number exists &mdash; Product SN ID: <strong>' + _esc(d.product_sn_id) + '</strong></span>');
                    $('#lsn_replacement_sn').attr('data-verified', 'unallotted').attr('data-product-sn-id', d.product_sn_id);
                } else if (d.status === 'allotted') {
                    var c = d.company_name || d.customer_name || d.customer_id;
                    $fb.html('<span class="text-danger"><i class="fa-solid fa-circle-xmark me-1"></i>Serial Number already allotted to: <strong>' + _esc(c) + '</strong></span>');
                    $('#lsn_replacement_sn').attr('data-verified', 'allotted');
                }
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // DATEPICKERS
    // ─────────────────────────────────────────────────────────────────────
    function _initDatepickers() {
        $('#modal_link_serial_number').on('shown.bs.modal', function () {
            $('.lsn-datepicker').each(function () {
                if (!$(this).data('datepicker-init')) {
                    flatpickr(this, {
                        dateFormat   : 'Y-m-d',
                        altInput     : true,
                        altFormat    : 'd-m-Y',
                        allowInput   : false,
                        disableMobile: true,
                        appendTo     : document.getElementById('modal_link_serial_number')
                    });
                    $(this).data('datepicker-init', true);
                }
            });
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // SUBMIT
    // ─────────────────────────────────────────────────────────────────────
    function _bindSubmit() {
        $('#btn_submit_link_sn').on('click', function () {
            var errors   = [];
            var warnings = [];
            var payload  = {};

            // --- Customer ---
            var mode = $('input[name="radio_customer_mode"]:checked').val();
            payload.customer_mode = mode;

            if (mode === 'new') {
                payload.company_name   = $('#lsn_company_name').val().trim();
                payload.first_name     = $('#lsn_first_name').val().trim();
                payload.middle_name    = $('#lsn_middle_name').val().trim();
                payload.last_name      = $('#lsn_last_name').val().trim();
                payload.primary_email  = $('#lsn_primary_email').val().trim();
                payload.primary_mobile = $('#lsn_primary_mobile').val().trim();

                if (!payload.company_name && !payload.first_name) {
                    errors.push({ field: 'Company Name / First Name', error: 'Either Company Name or First Name is required.' });
                }
            } else {
                payload.customer_id = $('#lsn_selected_customer_id').val().trim();
                if (!payload.customer_id) {
                    errors.push({ field: 'Customer', error: 'Please select a customer from the search.' });
                }
            }

            // --- Invoice ---
            payload.invoice_no   = $('#lsn_invoice_no').val().trim();
            payload.invoice_date = $('#lsn_invoice_date').val().trim();
            if (!payload.invoice_no)   errors.push({ field: 'Invoice No.', error: 'Invoice number is required.' });
            if (!payload.invoice_date) errors.push({ field: 'Invoice Date', error: 'Invoice date is required.' });

            // --- Product ---
            payload.product_id = $('#lsn_product_id').val();
            if (!payload.product_id) errors.push({ field: 'Product', error: 'Please select a product.' });

            // --- Serial Numbers ---
            var serial_numbers = [];
            var snError = false;
            $('#lsn_serial_numbers_container .lsn-sn-row').each(function () {
                var $inp     = $(this).find('.lsn-sn-input');
                var sn       = $inp.val().trim();
                var verified = $inp.attr('data-verified');
                var rowId    = $(this).attr('id');

                if (!sn) {
                    errors.push({ field: 'Serial Number Row #' + $(this).attr('data-sn-index'), error: 'Serial number is empty.' });
                    snError = true;
                    return;
                }
                if (!verified) {
                    errors.push({ field: 'Serial Number: ' + sn, error: 'Not verified. Please click Verify.' });
                    snError = true;
                    return;
                }

                serial_numbers.push({
                    serial_number:  sn,
                    verified:       verified,
                    product_sn_id:  $inp.attr('data-product-sn-id') || null
                });
            });

            if (serial_numbers.length === 0 && !snError) {
                errors.push({ field: 'Serial Numbers', error: 'At least one serial number is required.' });
            }

            payload.serial_numbers = JSON.stringify(serial_numbers);

            // --- Warranty ---
            payload.warranty_start_date = $('#lsn_warranty_start_date').val().trim();
            payload.warranty_period_months = $('#lsn_warranty_period').val();
            if (!payload.warranty_start_date)    errors.push({ field: 'Warranty Start Date', error: 'Date of purchase is required.' });
            if (!payload.warranty_period_months) errors.push({ field: 'Warranty Period', error: 'Warranty period is required.' });

            // --- Replacement ---
            var replacementEnabled = $('#chk_enable_replacement').is(':checked');
            payload.replacement_enabled = replacementEnabled ? '1' : '0';
            if (replacementEnabled) {
                payload.date_of_service    = $('#lsn_date_of_service').val().trim();
                payload.new_invoice_no     = $('#lsn_new_invoice_no').val().trim();
                payload.replacement_sn     = $('#lsn_replacement_sn').val().trim();
                payload.replacement_remark = $('#lsn_replacement_remark').val().trim();

                if (!payload.date_of_service) {
                    errors.push({ field: 'Date of Service', error: 'Date of service is required for replacement.' });
                }
            }

            // --- Show error alert if any ---
            if (errors.length > 0) {
                _showValidationAlert(errors, payload);
                return;
            }

            // --- Submit ---
            payload.what_do_you_want = 'appstv_link_serial_number_to_customer';

            var $btn = $('#btn_submit_link_sn');
            $btn.find('.indicator-label').addClass('d-none');
            $btn.find('.indicator-progress').removeClass('d-none');
            $btn.prop('disabled', true);

            $.ajax({
                url: getWebservice(),
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (res) {
                    $btn.find('.indicator-label').removeClass('d-none');
                    $btn.find('.indicator-progress').addClass('d-none');
                    $btn.prop('disabled', false);

                    var json = _parseResponse(res);
                    if (!json) return;

                    if (json.type === 'success') {
                        _showSuccessSummary(json.info);
                    } else {
                        showSimpleSweetAlert(json.info.message || 'Submission failed.', 'error', 'Okay', 'btn btn-primary');
                    }
                },
                error: function () {
                    $btn.find('.indicator-label').removeClass('d-none');
                    $btn.find('.indicator-progress').addClass('d-none');
                    $btn.prop('disabled', false);
                    showSimpleSweetAlert('A server error occurred. Please try again.', 'error', 'Okay', 'btn btn-primary');
                }
            });
        });
    }

    function _showValidationAlert(errors, payload) {
        var rows = '';
        $.each(errors, function (i, e) {
            rows += '<tr><td class="text-danger fw-bold"><i class="fa-solid fa-circle-xmark me-1"></i>' +
                    _esc(e.field) + '</td><td class="text-danger">' + _esc(e.error) + '</td></tr>';
        });

        var html =
            '<p class="mb-3">Please fix the following issues before submitting:</p>' +
            '<table class="table table-sm table-bordered">' +
                '<thead><tr><th>Field</th><th>Issue</th></tr></thead>' +
                '<tbody>' + rows + '</tbody>' +
            '</table>';

        Swal.fire({
            title: 'Validation Errors',
            html: html,
            icon: 'error',
            confirmButtonText: 'Fix Issues',
            confirmButtonClass: 'btn btn-primary'
        });
    }

    function _showSuccessSummary(info) {
        // Hide form sections, show summary
        $('.card.card-bordered').addClass('d-none');
        $('#lsn_modal_footer').find('#btn_submit_link_sn, #btn_reset_link_sn_form').addClass('d-none');

        var detail = '<ul class="mb-0">';
        if (info.customer_id)    detail += '<li>Customer ID: <strong>' + _esc(info.customer_id) + '</strong></li>';
        if (info.invoice_id)     detail += '<li>Invoice ID: <strong>' + _esc(info.invoice_id) + '</strong></li>';
        if (info.serial_numbers) {
            $.each(info.serial_numbers, function (i, s) {
                detail += '<li>Serial: <strong>' + _esc(s.serial_number) + '</strong> → Product SN ID: <strong>' + _esc(s.product_sn_id) + '</strong>, Warranty ID: <strong>' + _esc(s.psnw_id) + '</strong></li>';
            });
        }
        detail += '</ul>';

        $('#lsn_success_detail').html(detail);
        $('#lsn_success_summary').removeClass('d-none');

        // Show close button only
        var $closeBtn = $('<button type="button" class="btn btn-success rounded" id="btn_lsn_success_close"><i class="fa-solid fa-check me-2"></i>Close</button>');
        $closeBtn.on('click', function () {
            $('#modal_link_serial_number').modal('hide');
        });
        $('#lsn_modal_footer').append($closeBtn);
    }

    // ─────────────────────────────────────────────────────────────────────
    // RESET
    // ─────────────────────────────────────────────────────────────────────
    function _bindReset() {
        $('#btn_reset_link_sn, #modal_link_serial_number').on('hidden.bs.modal', function () {
            // Reset form fields
            $('#form_link_serial_number')[0].reset();

            // Clear flatpickr instances explicitly
            $('.lsn-datepicker').each(function () {
                var fp = this._flatpickr;
                if (fp) {
                    fp.clear();
                }
            });

            // Clear serial number rows
            $('#lsn_serial_numbers_container').empty();
            $('#lsn_no_sn_message').removeClass('d-none');
            _updateSerialNumberCount();

            // Reset customer section
            $('#lsn_selected_customer_badge').addClass('d-none');
            $('#lsn_selected_customer_id').val('');
            $('#lsn_customer_search').val('');
            $('#lsn_customer_autocomplete').addClass('d-none').empty();

            // Reset replacement section
            $('#chk_enable_replacement').prop('checked', false);
            $('#section_replacement_fields').addClass('d-none');
            $('#lsn_replacement_sn_feedback').html('');

            // Reset radio to default
            $('input[name="radio_customer_mode"][value="existing"]').prop('checked', true).trigger('change');

            _evaluateReplacementEligibility();
        });
    }

    function _resetForm() {
        // Customer
        $('#radio_customer_new').prop('checked', true).trigger('change');
        $('#lsn_company_name, #lsn_first_name, #lsn_middle_name, #lsn_last_name, #lsn_primary_email, #lsn_primary_mobile').val('');
        $('#lsn_customer_search').val('');
        $('#lsn_selected_customer_badge').addClass('d-none');
        $('#lsn_selected_customer_id').val('');

        // Invoice
        $('#lsn_invoice_no').val('');
        $('#lsn_invoice_date').val('');

        // Product
        if ($('#lsn_product_id').data('select2')) $('#lsn_product_id').val(null).trigger('change');

        // Serial numbers
        $('#lsn_serial_numbers_container').empty();
        $('#lsn_no_sn_message').removeClass('d-none');
        _snCounter = 0;

        // Warranty
        $('#lsn_warranty_start_date').val('');
        if ($('#lsn_warranty_period').data('select2')) $('#lsn_warranty_period').val(null).trigger('change');

        // Replacement
        $('#chk_enable_replacement').prop('checked', false).prop('disabled', true);
        $('#section_replacement_fields').addClass('d-none');
        $('#lsn_date_of_service, #lsn_new_invoice_no, #lsn_replacement_sn, #lsn_replacement_remark').val('');
        $('#lsn_replacement_sn_feedback').html('');
        $('#lbl_replacement_toggle').text('Only available with 1 already-allotted serial number');

        // Success summary — restore
        $('#lsn_success_summary').addClass('d-none');
        $('.card.card-bordered').removeClass('d-none');
        $('#btn_submit_link_sn, #btn_reset_link_sn_form').removeClass('d-none');
        $('#btn_lsn_success_close').remove();
    }

    // ─────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────
    function _setFeedback(rowId, type, msg) {
        var icon  = type === 'success' ? 'fa-circle-check' : 'fa-circle-xmark';
        var color = type === 'success' ? 'text-success' : 'text-danger';
        $('#feedback_' + rowId).html(
            '<span class="' + color + ' fs-7"><i class="fa-solid ' + icon + ' me-1"></i>' + msg + '</span>'
        );
    }

    function _clearFeedback(rowId) {
        $('#feedback_' + rowId).html('');
    }

    function _parseResponse(res) {
        try {
            var arr = (typeof res === 'string') ? JSON.parse(res) : res;
            return Array.isArray(arr) ? arr[0] : arr;
        } catch (e) { return null; }
    }

    function _esc(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    return { init: init };

})(jQuery);


// ── Boot on DOM ready ─────────────────────────────────────────────────────
$(document).ready(function () {
    LSN.init();
});