<?php
checkAuthorizationForPage( 'record_sale' );

// Products offered in the TV model lists: TV models (with serial numbers) first, then the other physical products
$rs_products = array();
$rs_result = selectQuery( "SELECT product_id, name, sku, has_sn FROM products WHERE physical_product = 1 ORDER BY has_sn DESC, name ASC" );
while( ( $rs_row = mysqli_fetch_assoc( $rs_result ) ) !== null ){
    $rs_products[] = array(
        'product_id' => $rs_row[ 'product_id' ],
        'name'       => $rs_row[ 'name' ],
        'sku'        => $rs_row[ 'sku' ],
        'has_sn'     => (int) $rs_row[ 'has_sn' ]
    );
}
$rs_css = dirname( __DIR__ ) . '/css/' . basename( __FILE__, ".php" ) . '.css';
$rs_js  = dirname( __DIR__ ) . '/js/' . basename( __FILE__, ".php" ) . '.js';
$rs_assets = dirname( __DIR__, 3 ) . '/assets/appstv';
?>
<link href="assets/appstv/css/appstv_scanner.css?v=<?= @filemtime( $rs_assets . '/css/appstv_scanner.css' ) ?>" rel="stylesheet" type="text/css"/>
<link href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css?v=<?= @filemtime( $rs_css ) ?>" rel="stylesheet" type="text/css"/>

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-fluid rs-page">

        <!-- 1. Customer -->
        <div class="card mb-5">
            <div class="card-header min-h-50px">
                <div class="card-title"><h3 class="mt-2"><span class="rs-step">1</span> Customer</h3></div>
            </div>
            <div class="card-body pt-5">
                <div class="btn-group w-100 w-md-auto mb-5" role="group" aria-label="Customer">
                    <input type="radio" class="btn-check" name="rs_customer_mode" id="rs_mode_existing" value="existing" checked />
                    <label class="btn btn-outline btn-outline-primary btn-active-primary" for="rs_mode_existing">Existing customer</label>
                    <input type="radio" class="btn-check" name="rs_customer_mode" id="rs_mode_new" value="new" />
                    <label class="btn btn-outline btn-outline-primary btn-active-primary" for="rs_mode_new">New customer</label>
                </div>

                <!-- Existing customer -->
                <div id="rs_existing_box">
                    <div id="rs_customer_search_box" class="position-relative">
                        <input type="search" id="rs_customer_search" class="form-control form-control-solid" placeholder="Search company, name, email, mobile or customer ID" autocomplete="off" />
                        <div id="rs_customer_results" class="rs-dropdown d-none"></div>
                    </div>
                    <div id="rs_customer_selected" class="rs-selected d-none">
                        <div>
                            <div class="fw-bold fs-5" id="rs_customer_selected_name"></div>
                            <div class="text-muted fs-7" id="rs_customer_selected_sub"></div>
                        </div>
                        <button type="button" class="btn btn-sm btn-light" id="rs_customer_change">Change</button>
                    </div>
                </div>

                <!-- New customer -->
                <div id="rs_new_box" class="d-none">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="rs_nc_company_name">Company name</label>
                            <input type="text" id="rs_nc_company_name" class="form-control form-control-solid" maxlength="200" data-rs-field="company_name" />
                            <div id="rs_nc_match" class="form-text text-warning d-none"></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="rs_nc_first_name">First name</label>
                            <input type="text" id="rs_nc_first_name" class="form-control form-control-solid" maxlength="100" data-rs-field="first_name" />
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label" for="rs_nc_last_name">Last name</label>
                            <input type="text" id="rs_nc_last_name" class="form-control form-control-solid" maxlength="100" data-rs-field="last_name" />
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="rs_nc_primary_email">Email</label>
                            <input type="email" id="rs_nc_primary_email" class="form-control form-control-solid" maxlength="200" data-rs-field="primary_email" />
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="rs_nc_primary_mobile">Mobile</label>
                            <input type="tel" id="rs_nc_primary_mobile" class="form-control form-control-solid" maxlength="20" data-rs-field="primary_mobile" />
                        </div>
                    </div>
                    <div class="form-text">Company name or first name is required.</div>
                </div>
            </div>
        </div>

        <!-- 2. Invoice and warranty -->
        <div class="card mb-5">
            <div class="card-header min-h-50px">
                <div class="card-title"><h3 class="mt-2"><span class="rs-step">2</span> Invoice and warranty</h3></div>
            </div>
            <div class="card-body pt-5">
                <div class="row g-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label required" for="rs_invoice_no">Invoice No.</label>
                        <input type="text" id="rs_invoice_no" class="form-control form-control-solid" maxlength="50" autocomplete="off" />
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label required" for="rs_invoice_date">Invoice date</label>
                        <input type="text" id="rs_invoice_date" class="form-control form-control-solid" placeholder="Choose date" />
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="form-label required" for="rs_warranty_start">Delivery date (warranty start)</label>
                        <input type="text" id="rs_warranty_start" class="form-control form-control-solid" placeholder="Choose date" />
                    </div>
                    <div class="col-12">
                        <div id="rs_invoice_info" class="d-none"></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label required" for="rs_warranty_months">Warranty</label>
                        <select id="rs_warranty_months" class="form-select form-select-solid">
                            <option value="12">1 year</option>
                            <option value="24">2 years</option>
                            <option value="36" selected>3 years</option>
                            <option value="48">4 years</option>
                            <option value="60">5 years</option>
                        </select>
                        <div class="form-text" id="rs_warranty_end_text"></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="rs_extended_years">Extended warranty</label>
                        <select id="rs_extended_years" class="form-select form-select-solid">
                            <option value="0" selected>None</option>
                            <option value="1">+1 year</option>
                            <option value="2">+2 years</option>
                            <option value="3">+3 years</option>
                            <option value="4">+4 years</option>
                            <option value="5">+5 years</option>
                        </select>
                        <div class="form-text" id="rs_extended_end_text"></div>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="rs_remarks">Remarks</label>
                        <input type="text" id="rs_remarks" class="form-control form-control-solid" maxlength="500" placeholder="Optional" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. TVs -->
        <div class="card mb-5">
            <div class="card-header min-h-50px">
                <div class="card-title"><h3 class="mt-2"><span class="rs-step">3</span> TVs sold</h3></div>
                <div class="card-toolbar">
                    <span class="badge badge-light-primary fs-6" id="rs_total_badge">0 TVs</span>
                </div>
            </div>
            <div class="card-body pt-5">
                <div id="rs_lines"></div>
                <button type="button" class="btn btn-light-primary w-100 w-md-auto" id="rs_add_line">
                    <i class="fa-solid fa-plus"></i> Add another TV model
                </button>
                <div class="form-text mt-3">
                    Type a serial number and press Enter, use a barcode scanner, scan with the camera, or choose a file (.xlsx, .csv, .txt).
                    Several serial numbers can be pasted at once.
                </div>
            </div>
        </div>

        <!-- Save bar -->
        <div class="rs-savebar card">
            <div class="card-body py-4">
                <div id="rs_warning_confirm_box" class="form-check form-check-custom form-check-solid form-check-warning mb-3 d-none">
                    <input class="form-check-input" type="checkbox" id="rs_confirm_warnings" />
                    <label class="form-check-label fw-semibold" for="rs_confirm_warnings" id="rs_confirm_warnings_label"></label>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <div class="me-auto">
                        <div class="fw-bold" id="rs_summary">No TVs added yet</div>
                        <div class="text-muted fs-7" id="rs_draft_status"></div>
                    </div>
                    <button type="button" class="btn btn-light" id="rs_start_over">Start over</button>
                    <button type="button" class="btn btn-primary" id="rs_save">
                        <span class="indicator-label"><i class="fa-solid fa-floppy-disk"></i> Save sale</span>
                        <span class="indicator-progress">Saving… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>
<!--end::Post-->

<input type="file" id="rs_file_input" class="d-none" accept=".xlsx,.csv,.txt" />

<script>
    var RS_PRODUCTS = <?= json_encode( $rs_products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) ?>;  // TV models for the product lines
</script>
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js"></script>
<script src="assets/appstv/js/appstv_scanner.js?v=<?= @filemtime( $rs_assets . '/js/appstv_scanner.js' ) ?>"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js?v=<?= @filemtime( $rs_js ) ?>"></script>
