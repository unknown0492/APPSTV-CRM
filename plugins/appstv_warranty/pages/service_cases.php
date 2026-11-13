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
<link href="assets/plugins/custom/cropper/cropper.bundle.css" rel="stylesheet" type="text/css"/>
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
                    <button type="button" class="sc-chip sc-chip-office" data-sc-chip="at_office"><span class="sc-chip-n" id="sc_count_at_office">0</span> TVs at the office</button>
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
                            <option value="revisit">Needs another visit</option>
                            <option value="at_office">TVs at the office</option>
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

        <!-- Drafts of cases not opened yet (shared by everyone) -->
        <div class="card mb-5 d-none" id="sc_drafts">
            <div class="card-header min-h-50px">
                <div class="card-title"><h4 class="mt-2 mb-0">Drafts <span class="badge badge-light-warning ms-2 align-middle" id="sc_drafts_count">0</span></h4></div>
                <div class="card-toolbar text-muted fs-7">Cases being filled in, not opened yet</div>
            </div>
            <div class="card-body py-3" id="sc_drafts_list"></div>
        </div>

        <div id="sc_list" class="row g-4"></div>
        <div id="sc_empty" class="text-center text-muted py-10 d-none">No cases match these filters.</div>

    </div>
</div>
<!--end::Post-->

<?php include dirname( __DIR__ ) . "/snippets/service_case_wizard.php"; ?>

<!-- Case details (1.0.22): header with actions, key tiles, tabs; drawn by SC.renderCase. The Photos & videos pane stays
     in place between redraws so uploads in progress are kept. -->
<div class="modal fade" id="sc_case_modal" tabindex="-1" data-bs-focus="false" aria-labelledby="sc_case_label">
    <div class="modal-dialog modal-dialog-scrollable sc-case-dialog modal-fullscreen-lg-down">
        <div class="modal-content">
            <div class="modal-header sc-case-header" id="sc_case_head"></div>
            <div class="modal-body sc-case-body">
                <div id="sc_case_tiles" class="sc-tiles"></div>
                <div id="sc_case_tabs" class="sc-tabs" role="tablist"></div>
                <div class="sc-pane" data-pane="overview" id="sc_pane_overview" role="tabpanel"></div>
                <div class="sc-pane d-none" data-pane="visits" id="sc_pane_visits" role="tabpanel"></div>
                <div class="sc-pane d-none" data-pane="tvs" id="sc_pane_tvs" role="tabpanel"></div>
                <div class="sc-pane d-none" data-pane="media" role="tabpanel"><div id="sc_case_media"></div></div>
                <div class="sc-pane d-none" data-pane="notes" id="sc_pane_notes" role="tabpanel"></div>
            </div>
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
<script src="assets/plugins/custom/cropper/cropper.bundle.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/service_case_media.js?v=<?= @filemtime( dirname( __DIR__ ) . '/js/service_case_media.js' ) ?>"></script>
<script src="plugins/<?=$plugin_name ?>/js/service_case_inspect.js?v=<?= @filemtime( dirname( __DIR__ ) . '/js/service_case_inspect.js' ) ?>"></script>
<script src="plugins/<?=$plugin_name ?>/js/service_case_wizard.js?v=<?= @filemtime( dirname( __DIR__ ) . '/js/service_case_wizard.js' ) ?>"></script>
