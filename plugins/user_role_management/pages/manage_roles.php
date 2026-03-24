<?php 
checkAuthorizationForPage( 'manage_roles' );
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
                    <h3 class="mt-2">Roles</h3>
                </div>
                <!--end::Heading-->
                <!--begin::Card Toolbar-->
                <div class="card-toolbar ">
                    <!--begin::Toolbar-->
                    <div class="d-flex flex-wrap" data-selected-table-toolbar="table_roles">
                        <button class="btn btn-sm btn-primary me-3 mt-3" id="btn_open_create_role_modal">Create Role</button>
                    </div>
                    <!--begin::Toolbar-->
                </div>
                <!--end::Card Toolbar-->
                
            </div>
            <!--begin::Card body-->
            <div class="card-body pe-10 table-loading">
                <div class="table-responsive">
                    <table id="table_roles" class="table table-hover table-row-bordered border rounded gy-5 gs-7">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th class="min-w-175px ">Role Name</th>
                                <th class="min-w-175px">Role Slug</th>
                                <th class="min-w-100px">Functionality Count</th>
                                <th class="min-w-30px">Options</th>
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

<!-- Modal - Create Role Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_create_role">
    <div class="modal-dialog modal-lg">
        <form name="form_create_role" id="form_create_role" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Create Role</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            <!-- Role Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_role_name_c" name="input_role_name_c" 
                                       placeholder="Provide a unique name for the Role" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_role_name_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_ROLE_NAME' ) ?>"
                                        data-parsley-error-message="Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_role_name_c">Role Name*</label>
                                <div id="input_role_name_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Role Slug -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_role_slug_c" name="input_role_slug_c" 
                                       placeholder="Enter a unique slug for the Role" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_role_slug_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_ROLE_SLUG' ) ?>"
                                        data-parsley-error-message="Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_role_slug_c">Role Slug*</label>
                                <div id="input_role_slug_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Plugin Selection -->
                            <div class="input-group input-group-solid flex-nowrap mb-7">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_plugin_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a plugin*" data-search="true"
                                            data-dropdown-parent="#modal_create_role" data-dropdown-parent="body" >
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_refresh_plugins_c">
                                        <span class="indicator-label">
                                            <i class="fa-solid fa-arrows-rotate "> </i>
                                        </span>
                                        <span class="indicator-progress">
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </span>
                            </div>
                            
                            <!-- Filter Functionality Type -->
                            <div class="div_functionality_type_c">
                                <select id="select_functionality_type_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Filter Functionality Type" data-search="true"
                                        data-dropdown-parent="#modal_create_role" data-dropdown-parent="body">
                                    <option></option>
                                    <option value="ALL">ALL</option>
                                    <option value="CREATE">CREATE</option>
                                    <option value="READ">READ</option>
                                    <option value="UPDATE">UPDATE</option>
                                    <option value="DELETE">DELETE</option>
                                    <option value="ACCESS">ACCESS</option>
                                </select>
                            </div>
                                
                            
                            
                        </div>
                        
                        <div class="col">
                            <!-- Select functionalities -->
                            <label class="label text-gray-500 fs-6 me-5 me-10 mb-10 mt-0" for="">
                                Choose functionalities for the Role
                            </label>
                            
                            <div class="div_check_functionalities_c ms-5 scroll h-200px">
                            </div>
                            
                            
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_cancel_create_role_modal" class="btn btn-light rounded">Reset</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_create_functionality" class="btn btn-primary rounded">
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
<!-- Modal - Create Role Modal ENDS-->



<!-- Modal - Edit Role Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_edit_role">
    <div class="modal-dialog modal-lg">
        <form name="form_edit_role" id="form_edit_role" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Role</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            
                            <input type="hidden" name="hidden_role_id_e" id="hidden_role_id_e" value="" />
                            
                            <!-- Role Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_role_name_e" name="input_role_name_e" 
                                       placeholder="Provide a unique name for the Role" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_role_name_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_ROLE_NAME' ) ?>"
                                        data-parsley-error-message="Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_role_name_e">Role Name*</label>
                                <div id="input_role_name_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Role Slug -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_role_slug_e" name="input_role_slug_e" 
                                       placeholder="Enter a unique slug for the Role" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_role_slug_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_ROLE_SLUG' ) ?>"
                                        data-parsley-error-message="Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_role_slug_e">Role Slug*</label>
                                <div id="input_role_slug_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Plugin Selection -->
                            <div class="input-group input-group-solid flex-nowrap mb-7">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_plugin_e" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a plugin*" data-search="true"
                                            data-dropdown-parent="#modal_edit_role" data-dropdown-parent="body" >
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_refresh_plugins_e">
                                        <span class="indicator-label">
                                            <i class="fa-solid fa-arrows-rotate "> </i>
                                        </span>
                                        <span class="indicator-progress">
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </span>
                            </div>
                            
                            <!-- Filter Functionality Type -->
                            <div class="div_functionality_type_e">
                                <select id="select_functionality_type_e" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Filter Functionality Type" data-search="true"
                                        data-dropdown-parent="#modal_edit_role" data-dropdown-parent="body">
                                    <option></option>
                                    <option value="ALL">ALL</option>
                                    <option value="CREATE">CREATE</option>
                                    <option value="READ">READ</option>
                                    <option value="UPDATE">UPDATE</option>
                                    <option value="DELETE">DELETE</option>
                                    <option value="ACCESS">ACCESS</option>
                                </select>
                            </div>
                            
                        </div>
                        
                        <div class="col">
                            <!-- Select functionalities -->
                            <label class="label text-gray-500 fs-6 me-5 me-10 mb-10 mt-0" for="">
                                Choose functionalities for the Role
                            </label>
                            
                            <div class="div_check_functionalities_e ms-5 scroll h-200px">
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_cancel_edit_role_modal" class="btn btn-light rounded">Cancel</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_update_role" class="btn btn-primary rounded">
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
<!-- Modal - Edit Role Modal ENDS-->


<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
