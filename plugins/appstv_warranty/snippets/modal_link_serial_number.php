<?php
/**
 * Modal - Link Serial Number to Customer
 * Included by manage_appstv_warranty.php
 */
?>

<!-- Modal - Link Serial Number to Customer BEGINS -->
<div class="modal fade" tabindex="-1" id="modal_link_serial_number" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded">

            <!-- HEADER -->
            <div class="modal-header">
                <h3 class="modal-title">
                    <i class="fa-solid fa-link me-2 text-primary"></i>Link Serial Number to Customer
                </h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" id="btn_close_link_sn_modal" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <!-- =====================================================
                     SECTION 1: CUSTOMER
                ===================================================== -->
                <div class="card card-bordered mb-6">
                    <div class="card-header min-h-50px">
                        <h4 class="card-title">
                            <i class="fa-solid fa-user me-2 text-primary"></i>Customer
                        </h4>
                    </div>
                    <div class="card-body pt-5">

                        <!-- Radio: New or Existing -->
                        <div class="mb-5">
                            <div class="btn-group w-100" data-kt-buttons="true">
                                <label class="btn btn-outline btn-color-muted btn-active-primary active" id="lbl_customer_new" data-kt-button="true">
                                    <input class="btn-check" type="radio" name="radio_customer_mode" id="radio_customer_new" value="new" checked />
                                    <i class="fa-solid fa-user-plus me-2"></i>Create New Customer
                                </label>
                                <label class="btn btn-outline btn-color-muted btn-active-primary" id="lbl_customer_existing" data-kt-button="true">
                                    <input class="btn-check" type="radio" name="radio_customer_mode" id="radio_customer_existing" value="existing" />
                                    <i class="fa-solid fa-search me-2"></i>Choose Existing Customer
                                </label>
                            </div>
                        </div>

                        <!-- NEW CUSTOMER FIELDS -->
                        <div id="section_new_customer">
                            <div class="row g-4">
                                <div class="col-lg-6 col-md-12">
                                    <div class="overflow-hidden form-floating">
                                        <input type="text" id="lsn_company_name" class="form-control form-control-solid" placeholder="Company Name" maxlength="255" />
                                        <label for="lsn_company_name">Company Name <span class="text-muted fs-8">(required if no First Name)</span></label>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="overflow-hidden form-floating">
                                        <input type="text" id="lsn_first_name" class="form-control form-control-solid" placeholder="First Name" maxlength="100" />
                                        <label for="lsn_first_name">First Name <span class="text-muted fs-8">(required if no Company)</span></label>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="overflow-hidden form-floating">
                                        <input type="text" id="lsn_middle_name" class="form-control form-control-solid" placeholder="Middle Name" maxlength="100" />
                                        <label for="lsn_middle_name">Middle Name</label>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="overflow-hidden form-floating">
                                        <input type="text" id="lsn_last_name" class="form-control form-control-solid" placeholder="Last Name" maxlength="100" />
                                        <label for="lsn_last_name">Last Name</label>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="overflow-hidden form-floating">
                                        <input type="email" id="lsn_primary_email" class="form-control form-control-solid" placeholder="Email" maxlength="255" />
                                        <label for="lsn_primary_email">Primary Email</label>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6">
                                    <div class="overflow-hidden form-floating">
                                        <input type="text" id="lsn_primary_mobile" class="form-control form-control-solid" placeholder="Mobile" maxlength="20" />
                                        <label for="lsn_primary_mobile">Primary Mobile</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- EXISTING CUSTOMER SEARCH (hidden by default) -->
                        <div id="section_existing_customer" class="d-none">
                            <div class="row g-4">
                                <div class="col-12">
                                    <div class="overflow-hidden form-floating">
                                        <input type="text" id="lsn_customer_search" class="form-control form-control-solid" placeholder="Search by name, email, mobile, ID..." autocomplete="off" />
                                        <label for="lsn_customer_search">Search Customer</label>
                                    </div>
                                    <!-- Autocomplete dropdown -->
                                    <div id="lsn_customer_autocomplete" class="lsn-autocomplete-dropdown d-none"></div>
                                    <!-- Selected customer badge -->
                                    <div id="lsn_selected_customer_badge" class="mt-3 d-none">
                                        <span class="badge badge-light-success fs-7 p-3">
                                            <i class="fa-solid fa-circle-check text-success me-2"></i>
                                            <span id="lsn_selected_customer_label"></span>
                                            <a href="#" id="btn_clear_selected_customer" class="ms-3 text-danger" title="Clear selection">
                                                <i class="fa-solid fa-times"></i>
                                            </a>
                                        </span>
                                        <input type="hidden" id="lsn_selected_customer_id" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- =====================================================
                     SECTION 2: INVOICE
                ===================================================== -->
                <div class="card card-bordered mb-6">
                    <div class="card-header min-h-50px">
                        <h4 class="card-title">
                            <i class="fa-solid fa-file-invoice me-2 text-primary"></i>Invoice
                        </h4>
                    </div>
                    <div class="card-body pt-5">
                        <div class="row g-4">
                            <div class="col-lg-6 col-md-12">
                                <div class="overflow-hidden form-floating">
                                    <input type="text" id="lsn_invoice_no" class="form-control form-control-solid" placeholder="Invoice No." maxlength="100" />
                                    <label for="lsn_invoice_no">Invoice No. *</label>
                                </div>
                                <div class="form-text text-muted ms-1">If invoice already exists in the system, it will be reused.</div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="overflow-hidden form-floating">
                                    <input type="text" id="lsn_invoice_date" class="form-control form-control-solid lsn-datepicker" placeholder="Invoice Date" readonly />
                                    <label for="lsn_invoice_date">Invoice Date *</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                     SECTION 3: PRODUCT
                ===================================================== -->
                <div class="card card-bordered mb-6">
                    <div class="card-header min-h-50px">
                        <h4 class="card-title">
                            <i class="fa-solid fa-tv me-2 text-primary"></i>Product (TV Model)
                        </h4>
                    </div>
                    <div class="card-body pt-5">
                        <div class="input-group input-group-solid flex-nowrap">
                            <div class="overflow-hidden form-floating flex-grow-1">
                                <select id="lsn_product_id" class="form-select form-select-solid"
                                        data-control="select2"
                                        data-placeholder="Select a product..."
                                        data-search="true"
                                        data-dropdown-parent="#modal_link_serial_number">
                                    <option></option>
                                </select>
                                <label for="lsn_product_id">Product *</label>
                            </div>
                            <span class="input-group-text border-start px-3" title="Refresh product list">
                                <button type="button" class="btn btn-sm btn-icon btn-active-light-primary" id="btn_refresh_lsn_products">
                                    <span class="indicator-label"><i class="fa-solid fa-arrows-rotate"></i></span>
                                    <span class="indicator-progress"><span class="spinner-border spinner-border-sm"></span></span>
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                     SECTION 4: SERIAL NUMBERS
                ===================================================== -->
                <div class="card card-bordered mb-6">
                    <div class="card-header min-h-50px">
                        <h4 class="card-title">
                            <i class="fa-solid fa-barcode me-2 text-primary"></i>Serial Numbers
                            <span id="lsn_sn_count_badge" class="text-muted fs-7 ms-2">(0)</span>
                        </h4>
                        <div class="card-toolbar gap-2">
                            <!-- Browse Excel/CSV -->
                            <label class="btn btn-sm btn-light-info" title="Import serial numbers from Excel/CSV file">
                                <i class="fa-solid fa-file-excel me-1"></i>Import from File
                                <input type="file" id="lsn_sn_file_input" accept=".xlsx,.csv" class="d-none" />
                            </label>
                            <!-- Add Serial Number -->
                            <button type="button" class="btn btn-sm btn-light-primary" id="btn_add_serial_number">
                                <i class="fa-solid fa-plus me-1"></i>Add Serial Number
                            </button>
                        </div>
                    </div>
                    <div class="card-body pt-4">
                        <!-- Scrollable serial numbers area -->
                        <div style="max-height: 600px; overflow-y: auto; padding-right: 4px;">
                            <!-- Serial number rows container -->
                            <div id="lsn_serial_numbers_container">
                                <!-- Rows injected by JS -->
                            </div>
                            <div id="lsn_no_sn_message" class="text-center text-muted py-4">
                                <i class="fa-solid fa-circle-info me-2"></i>No serial numbers added yet. Click <strong>Add Serial Number</strong> or <strong>Import from File</strong>.
                            </div>
                        </div>
                        <!-- Camera scan modal (inner) -->
                        <div id="lsn_scan_modal_overlay" class="lsn-scan-overlay d-none">
                            <div class="lsn-scan-box">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0 text-white"><i class="fa-solid fa-camera me-2"></i>Scan Barcode / QR Code</h5>
                                    <button type="button" class="btn btn-sm btn-icon btn-light" id="btn_close_scan_overlay">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                                <video id="lsn_scan_video" class="w-100 rounded" autoplay muted playsinline></video>
                                <canvas id="lsn_scan_canvas" class="d-none"></canvas>
                                <p class="text-white text-center mt-3 fs-7">
                                    <i class="fa-solid fa-circle-info me-1"></i>Point camera at a barcode or QR code
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                     SECTION 5: WARRANTY
                ===================================================== -->
                <div class="card card-bordered mb-6">
                    <div class="card-header min-h-50px">
                        <h4 class="card-title">
                            <i class="fa-solid fa-shield-halved me-2 text-primary"></i>Warranty
                        </h4>
                    </div>
                    <div class="card-body pt-5">
                        <div class="row g-4">
                            <div class="col-lg-6 col-md-12">
                                <div class="overflow-hidden form-floating">
                                    <input type="text" id="lsn_warranty_start_date" class="form-control form-control-solid lsn-datepicker" placeholder="Date of Purchase" readonly />
                                    <label for="lsn_warranty_start_date">Date of Purchase (Warranty Start) *</label>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="overflow-hidden form-floating">
                                    <select id="lsn_warranty_period" class="form-select form-select-solid"
                                            data-control="select2"
                                            data-placeholder="Select warranty period..."
                                            data-dropdown-parent="#modal_link_serial_number">
                                        <option></option>
                                        <?php for($m = 1; $m <= 60; $m++): ?>
                                            <option value="<?= $m ?>"><?= $m ?> Month<?= $m > 1 ? 's' : '' ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <label for="lsn_warranty_period">Warranty Period *</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                     SECTION 6: REPLACEMENT (conditional)
                ===================================================== -->
                <div class="card card-bordered mb-6" id="section_replacement_card">
                    <div class="card-header min-h-50px">
                        <h4 class="card-title">
                            <i class="fa-solid fa-rotate me-2 text-primary"></i>Replacement / Service Event
                        </h4>
                        <div class="card-toolbar">
                            <div class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" id="chk_enable_replacement" disabled />
                                <label class="form-check-label text-muted fs-7" for="chk_enable_replacement" id="lbl_replacement_toggle">
                                    Only available with 1 already-allotted serial number
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-5 d-none" id="section_replacement_fields">
                        <div class="row g-4">
                            <div class="col-lg-6 col-md-12">
                                <div class="overflow-hidden form-floating">
                                    <input type="text" id="lsn_date_of_service" class="form-control form-control-solid lsn-datepicker" placeholder="Date of Service" readonly />
                                    <label for="lsn_date_of_service">Date of Service *</label>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="overflow-hidden form-floating">
                                    <input type="text" id="lsn_new_invoice_no" class="form-control form-control-solid" placeholder="New/Ref Invoice No." maxlength="100" />
                                    <label for="lsn_new_invoice_no">New / Ref Invoice No.</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">New Serial Number (Replacement Unit)</label>
                                <div class="input-group input-group-solid" id="lsn_replacement_sn_group">
                                    <input type="text" id="lsn_replacement_sn" class="form-control form-control-solid"
                                           placeholder="Enter or scan replacement serial number"
                                           maxlength="50" />
                                    <button type="button" class="btn btn-secondary" id="btn_verify_replacement_sn" title="Verify serial number">
                                        <span class="indicator-label"><i class="fa-solid fa-circle-check me-1"></i>Verify</span>
                                        <span class="indicator-progress"><span class="spinner-border spinner-border-sm"></span></span>
                                    </button>
                                    <button type="button" class="btn btn-info" id="btn_scan_replacement_sn" title="Scan replacement serial number">
                                        <i class="fa-solid fa-camera"></i>
                                    </button>
                                </div>
                                <div id="lsn_replacement_sn_feedback" class="form-text mt-1"></div>
                            </div>
                            <div class="col-12">
                                <div class="overflow-hidden form-floating">
                                    <textarea id="lsn_replacement_remark" class="form-control form-control-solid" placeholder="Remarks" style="height:90px;" maxlength="500"></textarea>
                                    <label for="lsn_replacement_remark">Remarks</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- =====================================================
                     SUCCESS SUMMARY (hidden until submit succeeds)
                ===================================================== -->
                <div id="lsn_success_summary" class="d-none">
                    <div class="alert alert-success d-flex align-items-start mb-5">
                        <i class="fa-solid fa-circle-check fs-2 me-3 mt-1 text-success"></i>
                        <div>
                            <h5 class="mb-1">Submission Successful</h5>
                            <div id="lsn_success_detail"></div>
                        </div>
                    </div>
                </div>

            </div><!-- end modal-body -->

            <!-- FOOTER -->
            <div class="modal-footer" id="lsn_modal_footer">
                <button type="button" class="btn btn-light rounded" id="btn_reset_link_sn_form">Reset</button>
                <button type="button" class="btn btn-primary rounded" id="btn_submit_link_sn">
                    <span class="indicator-label"><i class="fa-solid fa-floppy-disk me-2"></i>Submit</span>
                    <span class="indicator-progress">Please wait... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>

        </div><!-- end modal-content -->
    </div>
</div>
<!-- Modal - Link Serial Number to Customer ENDS -->