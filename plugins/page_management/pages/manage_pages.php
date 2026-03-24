<?php 
checkAuthorizationForPage( 'manage_pages' );
?>

<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css"/>
<link href="assets/plugins/custom/jkanban/jkanban.bundle.css" rel="stylesheet" type="text/css" />

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Heading-->
                <div class="card-title">
                    <h3 class="mt-2">Page Listing</h3>
                </div>
                <!--end::Heading-->
                <!--begin::Card Toolbar-->
                <div class="card-toolbar ">
                    <!--begin::Toolbar-->
                    <div class="d-flex flex-wrap" data-selected-table-toolbar="table_pages">
                        <!--begin::Input group  Forms > Floating Labels -->
                        <div class="me-3 mt-3">
                            <select class="form-select form-select-sm form-select-solid w-250px" data-control="select2" data-placeholder="Select a plugin"
                                id="select_plugin">
                            </select>
                        </div>
                        <!--end::Input group-->

                        <button class="btn btn-sm btn-primary me-3 mt-3" id="btn_open_create_new_page_modal">Create</button>
                        <button class="btn btn-sm btn-warning me-3 mt-3" id="btn_open_page_sequencing_modal">Sequence</button>
                        <button class="btn btn-sm btn-info mt-3" id="btn_open_import_pages_modal">Import</button>
                    </div>
                    <!--begin::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none group_actions" data-selected-table-name="table_pages">
                        <div class="fw-bold me-5">
                        <span class="me-2 selected_row_count"></span>Selected</div>
                        <button type="button" class="btn btn-danger me-2 delete_selected_rows" >Delete Selected</button>
                        <button type="button" class="btn btn-success export_selected_rows" >Export Selected</button>
                    </div>
                    <!--end::Group actions-->
                </div>
                <!--end::Card Toolbar-->
                
            </div>
            <!--begin::Card body-->
            <div class="card-body pe-10 pages-table-loading ">
                <div class="table-responsive">
                    <table id="table_pages" class="table table-hover table-row-bordered border rounded gy-5 gs-7">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th class="min-w-50px">
                                    <div class="form-check form-check-custom form-check-solid form-check-sm ">
                                        <input class="form-check-input table-parent-checkbox" type="checkbox" id="flexRadioLg" value="1"/>
                                    </div>
                                </th>
                                <th class="min-w-175px">Page Title</th>
                                <th class="min-w-175px">Page Name</th>
                                <th class="min-w-100px">Hierarchy</th>
                                <th class="min-w-50px">Icon</th>
                                <th class="min-w-50px">Visible</th>
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
                                        <input class="form-check-input" type="checkbox" value="" id="flexRadioLg"/>                                        
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
                                        <input class="form-check-input" type="checkbox" value="" id="flexRadioLg"/>                                        
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
                                        <input class="form-check-input" type="checkbox" value="" id="flexRadioLg"/>                                        
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
                                        <input class="form-check-input" type="checkbox" value="" id="flexRadioLg"/>                                        
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
                                        <input class="form-check-input" type="checkbox" value="" id="flexRadioLg"/>                                        
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

<!-- Modal - Create new page BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_create_new_page">
    <div class="modal-dialog modal-lg">
        <form name="form_create_new_page" id="form_create_new_page" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Create new page</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            <!-- Page Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_page_name_c" name="input_page_name_c" placeholder="Provide a unique name for the page" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_page_name_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PAGE_NAME' ) ?>"
                                        data-parsley-error-message="Page name can only contain lowercase letters, numbers and an underscore"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_page_name_c">Page Name*</label>
                                <div id="input_page_name_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Page Title -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_page_title_c" name="input_page_title_c" placeholder="Provide a title for the page" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_input_page_title_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PAGE_TITLE' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the page title"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_page_title_c">Page Title*</label>
                                <div id="input_input_page_title_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Page Icon -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_page_icon_c" name="input_page_icon_c" placeholder="Provide an icon string from any line icon sites" 
                                        value=""
                                        data-parsley-errors-container="#input_page_icon_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_ICON' ) ?>"
                                        data-parsley-error-message="Compliant with only fontawesome, lineicons, bootstrap icon names"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_page_icon_c">Icon</label>
                                <div id="input_page_icon_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Page Description -->
                            <div class="form-floating mb-7">
                                <textarea class="form-control rounded h-150px form-control-solid" id="ta_page_description_c" name="ta_page_description_c" placeholder="A brief description identifying the purpose of the page" rows="4"
                                        value=""
                                        data-parsley-errors-container="#ta_page_description_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PAGE_DESCRIPTION' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the page description"
                                        data-parsley-trigger="keyup | blur"></textarea>
                                <label for="ta_page_description_c">Description</label>
                                <div id="ta_page_description_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            
                        </div>
                        
                        <div class="col">
                            <!-- Page Is Visible ? -->
                            <div class="form-check form-switch form-check-custom form-check-solid me-10 mb-10 mt-3">
                                <label class="form-check-label text-black fs-6 me-5" for="check_page_is_visible_c">
                                    Is this page visible ?
                                </label>
                                <input class="form-check-input h-30px w-50px" type="checkbox" value="" id="check_page_is_visible_c" name="check_page_is_visible_c" />                                
                            </div>
                            
                            <!-- Plugin Selection -->
                            <div class="input-group input-group-solid flex-nowrap">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_plugin_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a plugin" data-search="true"
                                            data-dropdown-parent="#modal_create_new_page" data-dropdown-parent="body" >
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
                            
                            <!-- Functionality Selection -->
                            <div class="input-group input-group-solid flex-nowrap mt-8">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_functionality_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a functionality"
                                            data-dropdown-parent="#modal_create_new_page" data-dropdown-parent="body">
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_refresh_functionalities_c">
                                        <span class="indicator-label">
                                            <i class="fa-solid fa-arrows-rotate "> </i>
                                        </span>
                                        <span class="indicator-progress">
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </span>
                            </div>
                            
                            <!-- Hierarchy -->
                            <div class="mt-7">
                                <div class="mb-3">
                                    <!--begin::Label-->
                                    <label class="d-flex align-items-center fs-5 fw-semibold">
                                        <span class="">Page Hierarchy *</span>

                                        <span class="m2-1" data-bs-toggle="tooltip" title="The hierarchy of the page for its placement on the Left Navigation Menu">
                                            <i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        </span>
                                    </label>
                                    <!--end::Label-->
                                </div>
                                <!--end::Heading-->

                                <!--begin::Radio group-->
                                <div class="btn-group w-100 w-lg-50" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button]">
                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-success" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_hierarchy_top_c" class="btn-check radio_hierarchy_c" type="radio" name="radio_hierarchy_c" value="1"/>
                                        <!--end::Input-->
                                        Top
                                    </label>
                                    <!--end::Radio-->

                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-warning" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_hierarchy_middle_c" class="btn-check radio_hierarchy_c" type="radio" name="radio_hierarchy_c" value="2"/>
                                        <!--end::Input-->
                                        Middle
                                    </label>
                                    <!--end::Radio-->

                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-info" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_hierarchy_bottom_c" class="btn-check radio_hierarchy_c" type="radio" name="radio_hierarchy_c" value="3" />
                                        <!--end::Input-->
                                        Bottom
                                    </label>
                                    <!--end::Radio-->
                                </div>
                                <!--end::Radio group-->
                            </div>
                            
                            <!-- Parent page Selection -->
                            <div class="input-group input-group-solid flex-nowrap mt-7 div_parent_page_selection_c hidden">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_parent_page_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a parent page"
                                            data-dropdown-parent="#modal_create_new_page" data-dropdown-parent="body">
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_refresh_parent_pages_c">
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
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_cancel_create_new_page_modal" class="btn btn-light rounded">Cancel</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_create_new_page" class="btn btn-primary rounded">
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
<!-- Modal - Create new page ENDS-->



<!-- Modal - Edit page BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_edit_page">
    <div class="modal-dialog modal-lg">
        <form name="form_edit_page" id="form_edit_page" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Edit page</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            <!-- Page ID -->
                            <input type="hidden" id="hidden_page_id_e" name="hidden_page_id_e" value="" />
                            
                            <!-- Page Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_page_name_e" name="input_page_name_e" placeholder="Provide a unique name for the page" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_page_name_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PAGE_NAME' ) ?>"
                                        data-parsley-error-message="Page name can only contain lowercase letters, numbers and an underscore"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_page_name_e">Page Name*</label>
                                <div id="input_page_name_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Page Title -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_page_title_e" name="input_page_title_e" placeholder="Provide a title for the page" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_input_page_title_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PAGE_TITLE' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the page title"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_page_title_e">Page Title*</label>
                                <div id="input_input_page_title_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Page Icon -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_page_icon_e" name="input_page_icon_e" placeholder="Provide an icon string from any line icon sites" 
                                        value=""
                                        data-parsley-errors-container="#input_page_icon_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_ICON' ) ?>"
                                        data-parsley-error-message="Compliant with only fontawesome, lineicons, bootstrap icon names"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_page_icon_e">Icon</label>
                                <div id="input_page_icon_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Page Description -->
                            <div class="form-floating mb-7">
                                <textarea class="form-control rounded h-150px form-control-solid" id="ta_page_description_e" name="ta_page_description_e" placeholder="A brief description identifying the purpose of the page" rows="4"
                                        value=""
                                        data-parsley-errors-container="#ta_page_description_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PAGE_DESCRIPTION' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the page description"
                                        data-parsley-trigger="keyup | blur"></textarea>
                                <label for="ta_page_description_e">Description</label>
                                <div id="ta_page_description_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            
                        </div>
                        
                        <div class="col">
                            <!-- Page Is Visible ? -->
                            <div class="form-check form-switch form-check-custom form-check-solid me-10 mb-10 mt-3">
                                <label class="form-check-label text-black fs-6 me-5" for="check_page_is_visible_e">
                                    Is this page visible ?
                                </label>
                                <input class="form-check-input h-30px w-50px" type="checkbox" value="" id="check_page_is_visible_e" name="check_page_is_visible_e" />                                
                            </div>
                            
                            <!-- Plugin Selection -->
                            <div class="input-group input-group-solid flex-nowrap">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_plugin_e" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a plugin" data-search="true"
                                            data-dropdown-parent="#modal_edit_page" data-dropdown-parent="body" >
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
                            
                            <!-- Functionality Selection -->
                            <div class="input-group input-group-solid flex-nowrap mt-8">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_functionality_e" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a functionality"
                                            data-dropdown-parent="#modal_edit_page" data-dropdown-parent="body">
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_refresh_functionalities_e">
                                        <span class="indicator-label">
                                            <i class="fa-solid fa-arrows-rotate "> </i>
                                        </span>
                                        <span class="indicator-progress">
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </span>
                            </div>
                            
                            <!-- Hierarchy -->
                            <div class="mt-7">
                                <div class="mb-3">
                                    <!--begin::Label-->
                                    <label class="d-flex align-items-center fs-5 fw-semibold">
                                        <span class="">Page Hierarchy *</span>

                                        <span class="m2-1" data-bs-toggle="tooltip" title="The hierarchy of the page for its placement on the Left Navigation Menu">
                                            <i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        </span>
                                    </label>
                                    <!--end::Label-->
                                </div>
                                <!--end::Heading-->

                                <!--begin::Radio group-->
                                <div class="btn-group w-100 w-lg-50" data-kt-buttons="true" data-kt-buttons-target="[data-kt-button]">
                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-success" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_hierarchy_top_e" class="btn-check radio_hierarchy_e" type="radio" name="radio_hierarchy_e" value="1"/>
                                        <!--end::Input-->
                                        Top
                                    </label>
                                    <!--end::Radio-->

                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-warning" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_hierarchy_middle_e" class="btn-check radio_hierarchy_e" type="radio" name="radio_hierarchy_e" value="2"/>
                                        <!--end::Input-->
                                        Middle
                                    </label>
                                    <!--end::Radio-->

                                    <!--begin::Radio-->
                                    <label class="btn btn-outline btn-color-muted btn-active-info" data-kt-button="true">
                                        <!--begin::Input-->
                                        <input id="radio_hierarchy_bottom_e" class="btn-check radio_hierarchy_e" type="radio" name="radio_hierarchy_e" value="3" />
                                        <!--end::Input-->
                                        Bottom
                                    </label>
                                    <!--end::Radio-->
                                </div>
                                <!--end::Radio group-->
                            </div>
                            
                            <!-- Parent page Selection -->
                            <div class="input-group input-group-solid flex-nowrap mt-7 div_parent_page_selection_e hidden">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_parent_page_e" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a parent page"
                                            data-dropdown-parent="#modal_edit_page" data-dropdown-parent="body">
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_refresh_parent_pages_e">
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
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_cancel_edit_page_modal" class="btn btn-light rounded">Cancel</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_update_page" class="btn btn-primary rounded">
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
<!-- Modal - Edit page ENDS-->




<!-- Modal - Page Sequencing BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_page_sequencing">
    <div class="modal-dialog modal-sm">
        <form name="form_page_sequencing" id="form_page_sequencing" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Page Sequencing</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-1">
                        <div class="col">
                            
                            <!-- Parent page Selection -->
                            <div class="input-group input-group-solid flex-nowrap mt-1">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_parent_page_s" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a parent page"
                                            data-dropdown-parent="#modal_page_sequencing" data-dropdown-parent="body">
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px w-50px border-start ps-0">
                                    <button type="button" class="btn btn-default " id="btn_refresh_parent_pages_s">
                                        <span class="indicator-label">
                                            <i class="fa-solid fa-arrows-rotate "> </i>
                                        </span>
                                        <span class="indicator-progress">
                                            <span class="spinner-border spinner-border-sm align-middle ms-0"></span>
                                        </span>
                                    </button>
                                </span>
                            </div>
                            
                            <!-- Page Sequencing area -->
                            <div class="mt-5">
                                <div class="child-kanban-1 rounded"></div>
                            </div>
                        </div>                        
                    </div>
                </div>

            </div>
        </form>
    </div>
</div>
<!-- Modal - Page Sequencing ENDS-->




<!-- Modal - Import Page Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_import_pages">
    <div class="modal-dialog modal-md">
        <form name="form_import_pages" id="form_import_pages" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Import Pages</h3>

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
                                <input type="file" class="hidden" name="file_pages_import" id="file_pages_import" />
                                <button type="button" id="btn_select_pages_file_import" name="btn_select_pages_file_import" class="btn btn-sm btn-primary">Select File</button>
                                <div class="scodezy-selected-files ">
                                    <div class="file-item p-5">
                                        <span class="file-name">this is a test filename.pdf</span>
                                        <span class="file-size">(1.1 MB)</span>
                                    </div>
                                </div>
                                <p class="text-gray-600 bold mt-2">Please select only compatible JSON files exported from scodezy pages</p>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_reset_import_pages" class="btn btn-light rounded">Reset</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_import_pages" class="btn btn-primary rounded">
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
<!-- Modal - Import Page Modal ENDS-->

<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<!-- <script src="assets/scodezy/js/list.js"></script> -->
<script src="assets/plugins/custom/jkanban/jkanban.bundle.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
