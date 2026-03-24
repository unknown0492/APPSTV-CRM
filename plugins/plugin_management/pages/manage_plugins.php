<?php 
checkAuthorizationForPage( 'manage_plugins' );
?>

<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css"/>

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Heading-->
                <div class="card-title">
                    <h3 class="mt-2">Plugins</h3>
                </div>
                <!--end::Heading-->
                <!--begin::Card Toolbar-->
                <div class="card-toolbar ">
                    <!--begin::Toolbar-->
                    <div class="d-flex flex-wrap" data-selected-table-toolbar="table_plugins">
                        <button class="btn btn-sm btn-primary me-3 mt-3" id="btn_open_create_plugin_modal">Create</button>
                        <button class="btn btn-sm btn-warning me-3 mt-3" id="btn_open_import_plugin_modal">Import</button>
                    </div>
                    <!--begin::Toolbar-->
                </div>
                <!--end::Card Toolbar-->
                
            </div>
            <!--begin::Card body-->
            <div class="card-body pe-10 table-loading">
                <div class="table-responsive">
                    <table id="table_plugins" class="table table-hover table-row-bordered border rounded gy-5 gs-7">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th class="min-w-175px ">Plugin Alias</th>
                                <th class="min-w-175px">Plugin Name</th>
                                <th class="min-w-100px">Version</th>
                                <th class="min-w-75px">Options</th>
                            </tr>
                        </thead>
                        <tbody> 
                            <!--
                            <tr>
                                <td>
                                    <div class="form-check form-check-custom  form-check-sm">
                                        <input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg"/>                                        
                                    </div>
                                </td>
                                <td>Tiger</td>
                                <td>Nixon</td>
                                <td>System Architect</td>
                                <td>Edinburgh</td>
                                <td>61</td>
                                <td>2011/04/25</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-custom  form-check-sm">
                                        <input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg"/>                                        
                                    </div>
                                </td>
                                <td>Garrett</td>
                                <td>Winters</td>
                                <td>Accountant</td>
                                <td>Tokyo</td>
                                <td>63</td>
                                <td>2011/07/25</td>                                
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-custom form-check-solid form-check-sm">
                                        <input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg"/>                                        
                                    </div>
                                </td>
                                <td>Tiger</td>
                                <td>Nixon</td>
                                <td>System Architect</td>
                                <td>Edinburgh</td>
                                <td>61</td>
                                <td>2011/04/25</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-custom  form-check-sm">
                                        <input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg"/>                                        
                                    </div>
                                </td>
                                <td>Garrett</td>
                                <td>Winters</td>
                                <td>Accountant</td>
                                <td>Tokyo</td>
                                <td>63</td>
                                <td>2011/07/25</td>                                
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-custom form-check-solid form-check-sm">
                                        <input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg"/>                                        
                                    </div>
                                </td>
                                <td>Tiger</td>
                                <td>Nixon</td>
                                <td>System Architect</td>
                                <td>Edinburgh</td>
                                <td>61</td>
                                <td>2011/04/25</td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="form-check form-check-custom form-check-solid form-check-sm">
                                        <input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg"/>                                        
                                    </div>
                                </td>
                                <td>Garrett</td>
                                <td>Winters</td>
                                <td>Accountant</td>
                                <td>Tokyo</td>
                                <td>63</td>
                                <td>2011/07/25</td>                                
                            </tr>
                            -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!--end::Container-->
</div>
<!--end::Post-->

<!-- Modal - Create Plugin Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_create_plugin">
    <div class="modal-dialog modal-md">
        <form name="form_create_plugin" id="form_create_plugin" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Create Plugin</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            <!-- Plugin Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_plugin_name_c" name="input_plugin_name_c" 
                                       placeholder="Provide a unique name for the plugin" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_plugin_name_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PLUGIN_NAME' ) ?>"
                                        data-parsley-error-message="Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_plugin_name_c">Plugin Name*</label>
                                <div id="input_plugin_name_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Plugin Alias -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_plugin_alias_c" name="input_plugin_alias_c" 
                                       placeholder="A descriptive name for the plugin" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_plugin_alias_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PLUGIN_ALIAS' ) ?>"
                                        data-parsley-error-message="Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_plugin_alias_c">Plugin Alias*</label>
                                <div id="input_plugin_alias_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Plugin Version -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_plugin_version_c" name="input_plugin_version_c" 
                                       placeholder="A version for the plugin" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_plugin_version_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PLUGIN_VERSION' ) ?>"
                                        data-parsley-error-message="Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and dots (.) are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_plugin_version_c">Version*</label>
                                <div id="input_plugin_version_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_reset_create_plugin_modal" class="btn btn-light rounded">Reset</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_create_plugin" class="btn btn-primary rounded">
                            <!--begin::Indicator label-->
                            <span class="indicator-label">Submit</span>
                            <!--end::Indicator label-->

                            <!--begin::Indicator progress-->
                            <span class="indicator-progress">Please wait... 
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            <!--end::Indicator progress-->
                        </button>
                    </div>
                    <!--end::Submit button-->
                </div>

            </div>
        </form>
    </div>
</div>
<!-- Modal - Create Plugin Modal ENDS-->



<!-- Modal - Edit Plugin Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_edit_plugin">
    <div class="modal-dialog modal-md">
        <form name="form_edit_plugin" id="form_edit_plugin" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Plugin</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            
                            <input type="hidden" name="hidden_plugin_id_e" id="hidden_plugin_id_e" value="" />
                            
                            <!-- Plugin Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_plugin_name_e" name="input_plugin_name_e" 
                                       placeholder="Provide a unique name for the plugin" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_plugin_name_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PLUGIN_NAME' ) ?>"
                                        data-parsley-error-message="Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_plugin_name_e">Plugin Name*</label>
                                <div id="input_plugin_name_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Plugin Alias -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_plugin_alias_e" name="input_plugin_alias_e" 
                                       placeholder="A descriptive name for the plugin" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_plugin_alias_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PLUGIN_ALIAS' ) ?>"
                                        data-parsley-error-message="Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_plugin_alias_e">Plugin Alias*</label>
                                <div id="input_plugin_alias_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Plugin Version -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_plugin_version_e" name="input_plugin_version_e" 
                                       placeholder="A version for the plugin" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_plugin_version_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PLUGIN_VERSION' ) ?>"
                                        data-parsley-error-message="Only lowercase, uppercase alphabets, digits 0 to 9, white spaces and dots (.) are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_plugin_version_e">Version*</label>
                                <div id="input_plugin_version_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_cancel_edit_plugin_modal" class="btn btn-light rounded">Cancel</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_update_plugin" class="btn btn-primary rounded">
                            <!--begin::Indicator label-->
                            <span class="indicator-label">Update</span>
                            <!--end::Indicator label-->

                            <!--begin::Indicator progress-->
                            <span class="indicator-progress">Please wait... 
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            <!--end::Indicator progress-->
                        </button>
                    </div>
                    <!--end::Submit button-->
                </div>

            </div>
        </form>
    </div>
</div>
<!-- Modal - Edit Plugin Modal ENDS-->



<!-- Modal - Import Plugin Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_import_plugin">
    <div class="modal-dialog modal-md">
        <form name="form_import_plugin" id="form_import_plugin" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Import Plugin</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            
                            <!-- Plugin File Upload -->
                            <div class="form-group scodezy-file-upload">
                                <input type="file" class="hidden" name="file_plugin_import" id="file_plugin_import" />
                                <button type="button" id="btn_select_plugin_file_import" name="btn_select_plugin_file_import" class="btn btn-sm btn-primary">Select File</button>
                                <div class="scodezy-selected-files ">
                                    <div class="file-item p-5">
                                        <span class="file-name">this is a test filename.pdf</span>
                                        <span class="file-size">(1.1 MB)</span>
                                    </div>
                                </div>
                                <p class="text-gray-600 bold mt-2">Please select only compatible ZIP files exported from scodezy</p>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_reset_import_plugin" class="btn btn-light rounded">Reset</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_import_plugin" class="btn btn-primary rounded">
                            <!--begin::Indicator label-->
                            <span class="indicator-label">Upload</span>
                            <!--end::Indicator label-->

                            <!--begin::Indicator progress-->
                            <span class="indicator-progress">Please wait... 
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                            <!--end::Indicator progress-->
                        </button>
                    </div>
                    <!--end::Submit button-->
                </div>

            </div>
        </form>
    </div>
</div>
<!-- Modal - Import Plugin Modal ENDS-->


<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
