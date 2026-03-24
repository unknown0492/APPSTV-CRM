<?php

checkAuthorizationForPage( 'manage_functionalities' );
/*
if( !hasAuthorization( 'manage_functionalities' ) ){
    echo "aa";
    return;
}
 * 
 */

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
                    <h3 class="mt-2">Functionalities</h3>
                </div>
                <!--end::Heading-->
                <!--begin::Card Toolbar-->
                <div class="card-toolbar ">
                    <!--begin::Toolbar-->
                    <div class="d-flex flex-wrap" data-selected-table-toolbar="table_functionalities">
                        <!--begin::Input group  Forms > Floating Labels -->
                        <div class="me-3 mt-3">
                            <select class="form-select form-select-sm form-select-solid w-250px" data-control="select2" data-placeholder="Select a plugin"
                                id="select_plugin">
                            </select>
                        </div>
                        <!--end::Input group-->

                        <button class="btn btn-sm btn-primary me-3 mt-3" id="btn_open_create_functionality_modal">Create</button>
                        <button class="btn btn-sm btn-info me-3 mt-3" id="btn_open_import_functionalities_modal">Import</button>
                    </div>
                    <!--begin::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none group_actions" data-selected-table-name="table_functionalities">
                        <div class="fw-bold mt-3 me-5">
                            <span class="me-2 selected_row_count"></span>Selected
                        </div>
                        <button type="button" class="btn btn-sm btn-danger me-3 mt-3 delete_selected_rows" >Delete Selected</button>
                        <button type="button" class="btn btn-sm btn-success me-3 mt-3 export_selected_rows" >Export Selected</button>
                    </div>
                    <!--end::Group actions-->
                </div>
                <!--end::Card Toolbar-->
                
            </div>
            <!--begin::Card body-->
            <div class="card-body pe-10 table-loading">
                <div class="table-responsive">
                    <table id="table_functionalities" class="table table-hover table-row-bordered border rounded gy-5 gs-7">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th class="min-w-50px">
                                    <div class="form-check form-check-custom form-check-solid form-check-sm ">
                                        <input class="form-check-input table-parent-checkbox" type="checkbox" id="flexRadioLg" value="1"/>
                                    </div>
                                </th>
                                <th class="min-w-175px">Alias</th>
                                <th class="min-w-175px">Functionality Name</th>
                                <th class="min-w-100px">Type</th>
                                <th class="min-w-50px">Is A Page</th>
                                <th class="min-w-50px">Is A Content</th>
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

<!-- Modal - Create functionality BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_create_functionality">
    <div class="modal-dialog modal-lg">
        <form name="form_create_functionality" id="form_create_functionality" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Create functionality</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            <!-- Functionality Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_functionality_name_c" name="input_functionality_name_c" placeholder="Provide a unique name for the functionality" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_functionality_name_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FUNCTIONALITY_NAME' ) ?>"
                                        data-parsley-error-message="Functionality name can only contain lowercase letters, numbers and an underscore"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_functionality_name_c">Functionality Name*</label>
                                <div id="input_functionality_name_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Alias -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_alias_c" name="input_alias_c" placeholder="Provide an alias for the functionality" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_alias_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FUNCTIONALITY_DESCRIPTION' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the functionality alias"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_alias_c">Alias*</label>
                                <div id="input_alias_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Functionality Description -->
                            <div class="form-floating mb-7">
                                <textarea class="form-control rounded h-150px form-control-solid" id="ta_functionality_description_c" name="ta_functionality_description_c" 
                                        placeholder="A brief description identifying the purpose of the functionality" rows="4"
                                        value=""
                                        data-parsley-errors-container="#ta_functionality_description_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FUNCTIONALITY_DESCRIPTION' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the functionality description"
                                        data-parsley-trigger="keyup | blur"></textarea>
                                <label for="ta_functionality_description_c">Description</label>
                                <div id="ta_functionality_description_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Plugin Selection -->
                            <div class="input-group input-group-solid flex-nowrap">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_plugin_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a plugin*" data-search="true"
                                            data-dropdown-parent="#modal_create_functionality" data-dropdown-parent="body" >
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
                            
                            
                        </div>
                        
                        <div class="col">
                            <!-- Is a page ? -->
                            <div class="form-check form-switch form-check-custom form-check-solid me-10 mb-10 mt-3">
                                <label class="form-check-label text-black fs-6 me-5" for="check_is_page_c">
                                    Is a page ? *
                                </label>
                                <input class="form-check-input h-30px w-50px" type="checkbox" value="" id="check_is_page_c" name="check_is_page_c" />                                
                            </div>
                            
                            <!-- Is content ? -->
                            <div class="form-check form-switch form-check-custom form-check-solid me-10 mb-10 mt-3">
                                <label class="form-check-label text-black fs-6 me-5" for="check_is_a_content_c">
                                    Is a content ? *
                                </label>
                                <input class="form-check-input h-30px w-50px" type="checkbox" value="" id="check_is_a_content_c" name="check_is_a_content_c" />                                
                            </div>
                            
                            <!-- Functionality Type -->
                            <div class="mt-13">
                                <div class="mb-3">
                                    <!--begin::Label-->
                                    <label class="d-flex align-items-center fs-5 fw-semibold">
                                        <span class="">Type *</span>

                                        <span class="m2-1" data-bs-toggle="tooltip" title="The type of content's accessibility for the functionality">
                                            <i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        </span>
                                    </label>
                                    <!--end::Label-->
                                </div>
                                <!--end::Heading-->

                                <!--begin::Radio group-->
                                <div class="btn-group w-100 w-lg-50 flex-wrap" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button]">
                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-primary" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_create_c" class="btn-check radio_functionality_type_c" type="radio" name="radio_functionality_type_c" value="CREATE"/>
                                        <!--end::Input-->
                                        CREATE
                                    </label>
                                    <!--end::Radio-->

                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-success" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_read_c" class="btn-check radio_functionality_type_c" type="radio" name="radio_functionality_type_c" value="READ"/>
                                        <!--end::Input-->
                                        READ
                                    </label>
                                    <!--end::Radio-->

                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-warning" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_update_c" class="btn-check radio_functionality_type_c" type="radio" name="radio_functionality_type_c" value="UPDATE" />
                                        <!--end::Input-->
                                        UPDATE
                                    </label>
                                    <!--end::Radio-->
                                    
                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-info" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_delete_c" class="btn-check radio_functionality_type_c" type="radio" name="radio_functionality_type_c" value="DELETE" />
                                        <!--end::Input-->
                                        DELETE
                                    </label>
                                    <!--end::Radio-->
                                    
                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-success" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_access_c" class="btn-check radio_functionality_type_c" type="radio" name="radio_functionality_type_c" value="ACCESS" />
                                        <!--end::Input-->
                                        ACCESS
                                    </label>
                                    <!--end::Radio-->
                                </div>
                                <!--end::Radio group-->
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_cancel_create_functionality_modal" class="btn btn-light rounded">Cancel</button>
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
<!-- Modal - Create functionality ENDS-->


<!-- Modal - Edit functionality BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_edit_functionality">
    <div class="modal-dialog modal-lg">
        <form name="form_edit_functionality" id="form_edit_functionality" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Edit functionality</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                    
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            
                            <input type="hidden" id="hidden_functionality_id_e" name="hidden_functionality_id_e" value="" />
                            
                            <!-- Functionality Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_functionality_name_e" name="input_functionality_name_e" placeholder="Provide a unique name for the functionality" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_functionality_name_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FUNCTIONALITY_NAME' ) ?>"
                                        data-parsley-error-message="Functionality name can only contain lowercase letters, numbers and an underscore"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_functionality_name_e">Functionality Name*</label>
                                <div id="input_functionality_name_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Alias -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_alias_e" name="input_alias_e" placeholder="Provide an alias for the functionality" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_alias_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FUNCTIONALITY_DESCRIPTION' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the functionality alias"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_alias_e">Alias*</label>
                                <div id="input_alias_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Functionality Description -->
                            <div class="form-floating mb-7">
                                <textarea class="form-control rounded h-150px form-control-solid" id="ta_functionality_description_e" name="ta_functionality_description_e" 
                                        placeholder="A brief description identifying the purpose of the functionality" rows="4"
                                        value=""
                                        data-parsley-errors-container="#ta_functionality_description_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FUNCTIONALITY_DESCRIPTION' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the functionality description"
                                        data-parsley-trigger="keyup | blur"></textarea>
                                <label for="ta_functionality_description_e">Description</label>
                                <div id="ta_functionality_description_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Plugin Selection -->
                            <div class="input-group input-group-solid flex-nowrap">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_plugin_e" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a plugin*" data-search="true"
                                            data-dropdown-parent="#modal_edit_functionality" data-dropdown-parent="body" >
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
                            
                            
                        </div>
                        
                        <div class="col">
                            <!-- Is a page ? -->
                            <div class="form-check form-switch form-check-custom form-check-solid me-10 mb-10 mt-3">
                                <label class="form-check-label text-black fs-6 me-5" for="check_is_page_e">
                                    Is a page ? *
                                </label>
                                <input class="form-check-input h-30px w-50px" type="checkbox" value="" id="check_is_page_e" name="check_is_page_e" />                                
                            </div>
                            
                            <!-- Is content ? -->
                            <div class="form-check form-switch form-check-custom form-check-solid me-10 mb-10 mt-3">
                                <label class="form-check-label text-black fs-6 me-5" for="check_is_a_content_e">
                                    Is a content ? *
                                </label>
                                <input class="form-check-input h-30px w-50px" type="checkbox" value="" id="check_is_a_content_e" name="check_is_a_content_e" />                                
                            </div>
                            
                            <!-- Functionality Type -->
                            <div class="mt-13">
                                <div class="mb-3">
                                    <!--begin::Label-->
                                    <label class="d-flex align-items-center fs-5 fw-semibold">
                                        <span class="">Type *</span>

                                        <span class="m2-1" data-bs-toggle="tooltip" title="The type of content's accessibility for the functionality">
                                            <i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        </span>
                                    </label>
                                    <!--end::Label-->
                                </div>
                                <!--end::Heading-->

                                <!--begin::Radio group-->
                                <div class="btn-group w-100 w-lg-50 flex-wrap" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button]">
                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-primary" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_create_e" class="btn-check radio_functionality_type_e" type="radio" name="radio_functionality_type_e" value="CREATE"/>
                                        <!--end::Input-->
                                        CREATE
                                    </label>
                                    <!--end::Radio-->

                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-success" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_read_e" class="btn-check radio_functionality_type_e" type="radio" name="radio_functionality_type_e" value="READ"/>
                                        <!--end::Input-->
                                        READ
                                    </label>
                                    <!--end::Radio-->

                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-warning" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_update_e" class="btn-check radio_functionality_type_e" type="radio" name="radio_functionality_type_e" value="UPDATE" />
                                        <!--end::Input-->
                                        UPDATE
                                    </label>
                                    <!--end::Radio-->
                                    
                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-info" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_delete_e" class="btn-check radio_functionality_type_e" type="radio" name="radio_functionality_type_e" value="DELETE" />
                                        <!--end::Input-->
                                        DELETE
                                    </label>
                                    <!--end::Radio-->
                                    
                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-success" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_type_access_e" class="btn-check radio_functionality_type_e" type="radio" name="radio_functionality_type_e" value="ACCESS" />
                                        <!--end::Input-->
                                        ACCESS
                                    </label>
                                    <!--end::Radio-->
                                </div>
                                <!--end::Radio group-->
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_cancel_edit_functionality_modal" class="btn btn-light rounded">Cancel</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_edit_functionality" class="btn btn-primary rounded">
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
<!-- Modal - Edit functionality ENDS-->



<!-- Modal - Import Functionalities Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_import_functionalities">
    <div class="modal-dialog modal-md">
        <form name="form_import_functionalities" id="form_import_functionalities" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Import Functionalities</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            
                            <!-- Pages File Upload -->
                            <div class="form-group scodezy-file-upload">
                                <input type="file" class="hidden" name="file_functionalities_import" id="file_functionalities_import" />
                                <button type="button" id="btn_select_functionalities_file_import" name="btn_select_functionalities_file_import" class="btn btn-sm btn-primary">Select File</button>
                                <div class="scodezy-selected-files ">
                                    <div class="file-item p-5">
                                        <span class="file-name">this is a test filename.pdf</span>
                                        <span class="file-size">(1.1 MB)</span>
                                    </div>
                                </div>
                                <p class="text-gray-600 bold mt-2">Please select only compatible JSON files exported from scodezy functionalities</p>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_reset_import_functionalities" class="btn btn-light rounded">Reset</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_import_functionalities" class="btn btn-primary rounded">
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
<!-- Modal - Import Functionalities Modal ENDS-->


<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
