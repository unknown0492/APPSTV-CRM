<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css"/>
<link href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css" rel="stylesheet" type="text/css"/>

<!-- ZXing for QR/Barcode scanning -->
<script src="https://unpkg.com/@zxing/library@latest/umd/index.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quagga@0.12.1/dist/quagga.min.js"></script>

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-fluid">
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Heading-->
                <div class="card-title">
                    <h3 class="mt-2">Warranty Information</h3>
                </div>
                <!--end::Heading-->
                <!--begin::Card Toolbar-->
                <div class="card-toolbar">
                    <div class="d-flex flex-wrap">
                        <button class="btn btn-sm btn-primary me-3 mt-2" 
                                id="btn_open_check_warranty_modal"
                                data-bs-toggle="modal" 
                                data-bs-target="#modal_check_warranty_status">
                            Check Warranty Status
                        </button>
                        <button type="button" class="btn btn-success rounded ms-3" data-bs-toggle="modal" data-bs-target="#modal_link_serial_number">
                            <i class="fa-solid fa-link me-2"></i>Link Serial Number to Customer
                        </button>
                    </div>
                    
                </div>
                <!--end::Card Toolbar-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body">
                <!-- Content will be added later -->
            </div>
            <!--end::Card body-->

        </div>
    </div>
    <!--end::Container-->
</div>
<!--end::Post-->


<!-- Modal - Check Warranty Status BEGINS -->
<div class="modal fade" tabindex="-1" id="modal_check_warranty_status" data-bs-focus="false">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content rounded">

            <div class="modal-header">
                <h3 class="modal-title">Check Warranty Status</h3>
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close" id="btn_close_warranty_modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <!--end::Close-->
            </div>

            <div class="modal-body py-8 px-10">

                <!-- OPTION 1: SCAN -->
                <div class="warranty-option">
                    <div class="warranty-option-label">Scan QR / Barcode</div>

                    <!-- Auto Capture Checkbox -->
                    <div class="form-check form-check-custom form-check-solid form-check-sm mb-4">
                        <input class="form-check-input" type="checkbox" id="chk_auto_capture" checked />
                        <label class="form-check-label text-gray-600 fs-6" for="chk_auto_capture">
                            Auto Capture (detects QR/Barcode automatically)
                        </label>
                    </div>

                    <!-- Camera Preview -->
                    <div id="div_camera_container" class="camera-container d-none">
                        <video id="video_camera_preview" class="camera-preview" autoplay muted playsinline></video>
                        <canvas id="canvas_capture" class="d-none"></canvas>
                        <div id="div_scan_overlay" class="scan-overlay">
                            <div class="scan-line"></div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 mt-3">
                        <button type="button" class="btn btn-sm btn-light-primary w-100" id="btn_start_scan">
                            <i class="ki-duotone ki-camera fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Scan
                        </button>
                        <button type="button" class="btn btn-sm btn-primary w-100 d-none" id="btn_capture">
                            Capture
                        </button>
                        <button type="button" class="btn btn-sm btn-light-danger w-100 d-none" id="btn_stop_scan">
                            Stop
                        </button>
                    </div>
                </div>

                <!-- OR Separator -->
                <div class="warranty-or-separator">
                    <span class="warranty-or-line"></span>
                    <span class="warranty-or-text">OR</span>
                    <span class="warranty-or-line"></span>
                </div>

                <!-- OPTION 2: BROWSE -->
                <div class="warranty-option">
                    <div class="warranty-option-label">Browse Image</div>
                    <input type="file" id="input_browse_image" accept="image/png, image/jpeg" class="d-none" />
                    <button type="button" class="btn btn-sm btn-light-primary w-100" id="btn_browse_image">
                        <i class="ki-duotone ki-folder fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Browse
                    </button>
                    <div id="div_browsed_filename" class="text-gray-500 fs-7 mt-2 text-center d-none"></div>
                </div>

                <!-- OR Separator -->
                <div class="warranty-or-separator">
                    <span class="warranty-or-line"></span>
                    <span class="warranty-or-text">OR</span>
                    <span class="warranty-or-line"></span>
                </div>

                <!-- OPTION 3: MANUAL ENTRY -->
                <div class="warranty-option">
                    <div class="warranty-option-label">Enter Serial Number Manually</div>
                    <div class="input-group input-group-solid">
                        <input type="text" class="form-control form-control-solid rounded-start" 
                               id="input_manual_serial_number" 
                               placeholder="e.g. SN1234567890"
                               autocomplete="off" />
                        <button class="btn btn-primary rounded-end px-6" type="button" id="btn_manual_go">
                            GO
                        </button>
                    </div>
                </div>

            </div>
            <!-- end::modal-body -->

        </div>
    </div>
</div>
<!-- Modal - Check Warranty Status ENDS -->

<?php include PLU_PATH . '/appstv_warranty/snippets/modal_link_serial_number.php'; ?>


<!-- BEGIN PAGE LEVEL SCRIPTS -->
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
<script src="plugins/appstv_warranty/js/appstv_scan_serial.js"></script>
<script src="plugins/appstv_warranty/js/link_serial_number.js"></script>
<!-- END PAGE LEVEL SCRIPTS -->