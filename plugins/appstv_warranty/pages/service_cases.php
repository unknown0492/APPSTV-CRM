<?php
checkAuthorizationForPage( 'service_cases' );

// TV models for the replacement model list of the Record service form
$sc_products = array();
$sc_result = selectQuery( "SELECT product_id, name, sku, has_sn FROM products WHERE physical_product = 1 ORDER BY has_sn DESC, name ASC" );
while( ( $sc_row = mysqli_fetch_assoc( $sc_result ) ) !== null ){
    $sc_products[] = array( 'product_id' => $sc_row[ 'product_id' ], 'name' => $sc_row[ 'name' ], 'sku' => $sc_row[ 'sku' ], 'has_sn' => (int) $sc_row[ 'has_sn' ] );
}
$sc_css = dirname( __DIR__ ) . '/css/' . basename( __FILE__, ".php" ) . '.css';
$sc_js  = dirname( __DIR__ ) . '/js/' . basename( __FILE__, ".php" ) . '.js';
$sc_assets = dirname( __DIR__, 3 ) . '/assets/appstv';
?>
<link href="assets/appstv/css/appstv_scanner.css?v=<?= @filemtime( $sc_assets . '/css/appstv_scanner.css' ) ?>" rel="stylesheet" type="text/css"/>
<link href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css?v=<?= @filemtime( $sc_css ) ?>" rel="stylesheet" type="text/css"/>

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    <div id="kt_content_container" class="container-fluid sc-page">

        <div class="card mb-5">
            <div class="card-header min-h-50px flex-wrap">
                <div class="card-title"><h3 class="mt-2">Service Cases</h3></div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-primary btn-sm" id="sc_new_case"><i class="fa-solid fa-plus"></i> New case</button>
                </div>
            </div>
            <div class="card-body pt-4 pb-4">
                <!-- Summary chips (click = filter) -->
                <div class="sc-chips mb-4">
                    <button type="button" class="sc-chip sc-chip-danger" data-sc-chip="overdue"><span class="sc-chip-n" id="sc_count_overdue">0</span> Overdue visits</button>
                    <button type="button" class="sc-chip sc-chip-warning" data-sc-chip="today"><span class="sc-chip-n" id="sc_count_today">0</span> Visits today</button>
                    <button type="button" class="sc-chip sc-chip-primary" data-sc-chip="active"><span class="sc-chip-n" id="sc_count_active">0</span> Active cases</button>
                    <button type="button" class="sc-chip sc-chip-info" data-sc-chip="mine"><span class="sc-chip-n" id="sc_count_mine">0</span> Assigned to me</button>
                </div>
                <!-- Filters -->
                <div class="row g-3">
                    <div class="col-12 col-md-5">
                        <input type="search" id="sc_q" class="form-control form-control-solid" placeholder="Search case no., customer, contact, phone, serial, problem" />
                    </div>
                    <div class="col-6 col-md-3">
                        <select id="sc_status" class="form-select form-select-solid">
                            <option value="active" selected>Active (not closed)</option>
                            <option value="overdue">Overdue visits</option>
                            <option value="today">Visits today</option>
                            <option value="open">Open</option>
                            <option value="scheduled">Visit scheduled</option>
                            <option value="visited">Visited</option>
                            <option value="closed">Closed</option>
                            <option value="all">All</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4">
                        <select id="sc_assigned" class="form-select form-select-solid">
                            <option value="" selected>Anyone</option>
                            <option value="me">Assigned to me</option>
                            <option value="none">Not assigned</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div id="sc_list" class="row g-4"></div>
        <div id="sc_empty" class="text-center text-muted py-10 d-none">No cases match these filters.</div>

    </div>
</div>
<!--end::Post-->

<!-- New case -->
<div class="modal fade" id="sc_new_modal" tabindex="-1" data-bs-focus="false" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header py-4">
                <h3 class="modal-title">New service case</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fs-2"></i></button>
            </div>
            <div class="modal-body">
                <!-- TV first: finds the customer -->
                <label class="form-label">TV serial number <span class="text-muted fw-normal">(finds the customer)</span></label>
                <div class="input-group mb-2">
                    <input type="text" id="sc_new_serial" class="form-control form-control-solid font-monospace" placeholder="Type or scan, press Enter" autocomplete="off" autocapitalize="characters" />
                    <button type="button" class="btn btn-light-primary" id="sc_new_find">Find</button>
                    <button type="button" class="btn btn-primary" id="sc_new_scan"><i class="fa-solid fa-camera"></i></button>
                </div>
                <div id="sc_new_found" class="mb-3"></div>
                <div id="sc_new_tvs" class="mb-5"></div>

                <label class="form-label required">Customer</label>
                <div id="sc_new_customer_box" class="position-relative mb-5">
                    <input type="search" id="sc_new_customer_search" class="form-control form-control-solid" placeholder="Search company, name, email, mobile or customer ID" autocomplete="off" />
                    <div id="sc_new_customer_results" class="sc-dropdown d-none"></div>
                </div>
                <div id="sc_new_customer_selected" class="sc-selected d-none mb-5">
                    <div><div class="fw-bold" id="sc_new_customer_name"></div><div class="text-muted fs-7" id="sc_new_customer_sub"></div></div>
                    <button type="button" class="btn btn-sm btn-light" id="sc_new_customer_change">Change</button>
                </div>

                <div class="row g-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="sc_new_contact_name">Contact person</label>
                        <input type="text" id="sc_new_contact_name" class="form-control form-control-solid" maxlength="200" />
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="sc_new_contact_phone">Contact phone</label>
                        <input type="tel" id="sc_new_contact_phone" class="form-control form-control-solid" maxlength="50" />
                    </div>
                    <div class="col-12">
                        <label class="form-label required" for="sc_new_problem">Problem reported</label>
                        <textarea id="sc_new_problem" class="form-control form-control-solid" rows="3" maxlength="5000" placeholder="What the customer reported"></textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="sc_new_assigned">Assign to</label>
                        <select id="sc_new_assigned" class="form-select form-select-solid sc-staff-select"></select>
                    </div>
                    <div class="col-7 col-md-3">
                        <label class="form-label" for="sc_new_visit_date">Visit date</label>
                        <input type="date" id="sc_new_visit_date" class="form-control form-control-solid" />
                    </div>
                    <div class="col-5 col-md-3">
                        <label class="form-label" for="sc_new_visit_time">Time</label>
                        <input type="time" id="sc_new_visit_time" class="form-control form-control-solid" />
                    </div>
                </div>
            </div>
            <div class="modal-footer py-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="sc_new_save">
                    <span class="indicator-label">Open case</span>
                    <span class="indicator-progress">Saving… <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Case details -->
<div class="modal fade" id="sc_case_modal" tabindex="-1" data-bs-focus="false">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header py-4">
                <h3 class="modal-title" id="sc_case_title">Case</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark fs-2"></i></button>
            </div>
            <div class="modal-body" id="sc_case_body"></div>
        </div>
    </div>
</div>

<script>
    var APPSTV_PRODUCTS = <?= json_encode( $sc_products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) ?>;  // Models for the replacement list
</script>
<script src="https://cdn.jsdelivr.net/npm/@zxing/library@0.21.3/umd/index.min.js"></script>
<script src="assets/appstv/js/appstv_scanner.js?v=<?= @filemtime( $sc_assets . '/js/appstv_scanner.js' ) ?>"></script>
<script src="assets/appstv/js/appstv_service_forms.js?v=<?= @filemtime( $sc_assets . '/js/appstv_service_forms.js' ) ?>"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js?v=<?= @filemtime( $sc_js ) ?>"></script>
