<?php 
checkAuthorizationForPage( 'manage_users' );
?>
<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css"/>
<link href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css" rel="stylesheet" type="text/css"/>

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    
    <!--begin::Container-->
    <div id="kt_content_container" class="container-xxl">
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Heading-->
                <div class="card-title">
                    <h3 class="mt-2">Users</h3>
                </div>
                <!--end::Heading-->
                <!--begin::Card Toolbar-->
                <div class="card-toolbar ">
                    <!--begin::Toolbar-->
                    <div class="d-flex flex-wrap" data-selected-table-toolbar="table_users">
                        <!--begin::Input group  Forms > Floating Labels -->
                        <div class="me-3 mt-3">
                            <select class="form-select form-select-sm form-select-solid w-250px" data-control="select2" data-placeholder="Select a role"
                                id="select_role">
                            </select>
                        </div>
                        <!--end::Input group-->

                        <button class="btn btn-sm btn-primary me-3 mt-3" id="btn_open_create_user_modal">Create User</button>
                    </div>
                    <!--begin::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none group_actions" data-selected-table-name="table_users">
                        <div class="fw-bold me-5">
                        <span class="me-2 selected_row_count"></span>Selected</div>
                        <button type="button" class="btn btn-danger delete_selected_rows" >Delete Selected</button>
                    </div>
                    <!--end::Group actions-->
                </div>
                <!--end::Card Toolbar-->
                
            </div>
            <!--begin::Card body-->
            <div class="card-body pe-10 users-table-loading ">
                <div class="table-responsive">
                    <table id="table_users" class="table table-hover table-row-bordered border rounded gy-5 gs-7">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th class="min-w-50px">
                                    <div class="form-check form-check-custom form-check-solid form-check-sm ">
                                        <input class="form-check-input table-parent-checkbox" type="checkbox" value="1"/>
                                    </div>
                                </th>
                                <th class="min-w-175px">Username</th>
                                <th class="min-w-175px">First Name</th>
                                <th class="min-w-100px">Last Name</th>
                                <th class="min-w-50px">Email</th>
                                <th class="min-w-50px">Role</th>
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


<!-- Modal - Create User BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_create_user">
    <div class="modal-dialog modal-lg">
        <form name="form_create_user" id="form_create_user" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Create User</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            <!-- User ID -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_user_id_c" name="input_user_id_c" placeholder="Provide a unique user_id for the user" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#input_user_id_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_USER_ID' ) ?>"
                                        data-parsley-error-message="User ID can contain lowercase alphabets a to z<br />Digits 0 to 9 <br />An underscore _ <br />Minimum 3 & maximum 20 characters"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_user_id_c">User ID*</label>
                                <div id="user_id_availability_msg" class="mt-1"></div>
                                <div id="input_user_id_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Password -->
                            <div class="input-group input-group-solid flex-nowrap">
                                <div class="overflow-hidden flex-grow-1">
                                    <div class="form-floating mb-7">
                                        <input type="text" class="form-control rounded form-control-solid" id="input_password_c" name="input_password_c" placeholder="Provide a password for the user" 
                                                value=""
                                                required
                                                data-parsley-required="true"
                                                data-parsley-errors-container="#input_password_c_error" 
                                                autocomplete="off" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PASSWORD' ) ?>"
                                                data-parsley-error-message="Password should be of minimum 3 characters & maximum 20 characters<br />Can contain lowercase and uppercase letters<br />Digits 0 to 9<br />Special characters like underscore _ @ and #"
                                                data-parsley-trigger="keyup | blur" />
                                        <label for="input_password_c">Password*</label>
                                        <div id="input_password_c_error" class="scodezy-error-msg"></div>
                                    </div>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_generate_password_c">
                                        <span class="indicator-label">
                                            <i class="fa-solid fa-arrows-rotate "> </i>
                                        </span>
                                        <span class="indicator-progress">
                                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                        </span>
                                    </button>
                                </span>
                            </div>
                            
                            <!-- Email -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_email_c" name="input_email_c" placeholder="Enter the email id of the user" 
                                        value=""
                                        data-parsley-errors-container="#input_email_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_EMAIL' ) ?>"
                                        data-parsley-error-message="Email ID is invalid"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_email_c">Email*</label>
                                <div id="input_email_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Role Selection -->
                            <div class="input-group input-group-solid flex-nowrap">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_role_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a role*" data-search="true"
                                            data-dropdown-parent="#modal_create_user" data-dropdown-parent="body" >
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_refresh_roles_c">
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
                            
                            <!-- First Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_fname_c" name="input_fname_c" placeholder="Enter first name of the user" 
                                        value=""
                                        data-parsley-errors-container="#input_fname_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FIRST_NAME' ) ?>"
                                        data-parsley-error-message="Only lowercase & uppercase letters, spaces, apostrophe and a hyphen are allowed for the First Name"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_fname_c">First Name</label>
                                <div id="input_fname_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Last Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_lname_c" name="input_lname_c" placeholder="Enter last name of the user" 
                                        value=""
                                        data-parsley-errors-container="#input_lname_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_LAST_NAME' ) ?>"
                                        data-parsley-error-message="Only lowercase & uppercase letters, spaces, apostrophe and a hyphen are allowed for the First Name"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_lname_c">Last Name</label>
                                <div id="input_lname_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Nick Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_nickname_c" name="input_nickname_c" placeholder="Enter nickname of the user" 
                                        value=""
                                        data-parsley-errors-container="#input_nick_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_NICK_NAME' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the nickname"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_nick_c">Nick Name</label>
                                <div id="input_nick_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Email Credentials to the user's email ? -->
                            <div class="form-check form-check-custom form-check-solid form-check-sm me-10 mb-10 mt-10 ms-2">
                                <input class="form-check-input" type="checkbox" value="" checked='checked' id="check_email_credentials_c" name="check_email_credentials_c" />    
                                <label class="form-check-label text-gray-500 fs-6 me-5" for="check_email_credentials_c">
                                    Send sign-in information to user's email ?
                                </label><br />                                                            
                            </div>
                            
                            
                            

                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_reset_create_user" class="btn btn-light rounded">Reset</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_create_user" class="btn btn-primary rounded">
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
<!-- Modal - Create User ENDS-->


<!-- Modal - Edit User BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_edit_user">
    <div class="modal-dialog modal-lg">
        <form name="form_edit_user" id="form_edit_user" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Edit User</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            <!-- User ID -->
                            <!--begin::Label-->
                            <label class="d-flex fs-5 fw-semibold">
                                <div class="fw-bold">User ID</div>

                                <span class="m2-1" data-bs-toggle="tooltip" title="The user id of a user cannot be changed once it has been created">
                                    <i class="ki-duotone ki-information fs-7"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                </span>
                            </label>
                            <!--end::Label-->
                            <div class="text-gray-600 mb-7" id="div_user_id_e"></div>
                            <input type="hidden" name="hidden_user_id_e" id="hidden_user_id_e" value="" />
                            
                            <!-- Reset Password Button -->
                            <div class="d-grid fs-5 mb-7">
                                <div class="fw-bold mb-2">Reset password the for User</div>
                                <button type="button" id="btn_reset_password_e" class="btn btn-danger rounded btn-sm w-150px">
                                    <!--begin::Indicator label-->
                                    <span class="indicator-label">Reset</span>
                                    <!--end::Indicator label-->

                                    <!--begin::Indicator progress-->
                                    <span class="indicator-progress">Please wait... 
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                    <!--end::Indicator progress-->
                                </button>
                                <div id="password_reset_message" class="fs-6 mt-1">aaaaa</div>
                            </div>
                            
                            
                            <!-- Email -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_email_e" name="input_email_e" placeholder="Enter the email id of the user" 
                                        value=""
                                        data-parsley-errors-container="#input_email_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_EMAIL' ) ?>"
                                        data-parsley-error-message="Email ID is invalid"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_email_e">Email*</label>
                                <div id="input_email_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Role Selection -->
                            <div class="input-group input-group-solid flex-nowrap">
                                <div class="overflow-hidden flex-grow-1">
                                    <select id="select_role_e" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Select a role*" data-search="true"
                                            data-dropdown-parent="#modal_edit_user" data-dropdown-parent="body" >
                                        <option></option>
                                    </select>
                                </div>
                                <span class="input-group-text h-50px border-start">
                                    <button type="button" class="btn btn-default" id="btn_refresh_roles_e">
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
                            
                            <!-- First Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_fname_e" name="input_fname_e" placeholder="Enter first name of the user" 
                                        value=""
                                        data-parsley-errors-container="#input_fname_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_FIRST_NAME' ) ?>"
                                        data-parsley-error-message="Only lowercase & uppercase letters, spaces, apostrophe and a hyphen are allowed for the First Name"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_fname_e">First Name</label>
                                <div id="input_fname_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Last Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_lname_e" name="input_lname_e" placeholder="Enter last name of the user" 
                                        value=""
                                        data-parsley-errors-container="#input_lname_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_LAST_NAME' ) ?>"
                                        data-parsley-error-message="Only lowercase & uppercase letters, spaces, apostrophe and a hyphen are allowed for the First Name"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_lname_e">Last Name</label>
                                <div id="input_lname_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Nick Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="input_nickname_e" name="input_nickname_e" placeholder="Enter nickname of the user" 
                                        value=""
                                        data-parsley-errors-container="#input_nick_e_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_NICK_NAME' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for the nickname"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="input_nick_e">Nick Name</label>
                                <div id="input_nick_e_error" class="scodezy-error-msg"></div>
                            </div>
                            
                        </div>
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_cancel_edit_user" class="btn btn-light rounded">Cancel</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_update_user" class="btn btn-primary rounded">
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
<!-- Modal - Edit User ENDS-->


<!-- Modal - View User BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_view_user">
    <div class="modal-dialog modal-md">
        <form name="form_view_user" id="form_view_user" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">User Information</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-striped table_view_user">
                            <tbody>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800 w-175px text-right">User ID</th>
                                    <td id="td_user_id_v">aaaa</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800">Email</th>
                                    <td id="td_email_v">aaaa</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800">Role</th>
                                    <td id="td_role_v">aaaa</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800">First Name</th>
                                    <td id="td_fname_v">aaaa</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800">Last Name</th>
                                    <td id="td_lname_v">aaaa</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800">Nick Name</th>
                                    <td id="td_nickname_v">aaaa</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800">Registered On</th>
                                    <td id="td_registered_on_v" data-timestamp="asas">aaaa</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800">Activation Status</th>
                                    <td id="td_activation_status_v">aaaa</td>
                                </tr>
                                <tr>
                                    <th class="fw-bold fs-6 text-gray-800">Activated On</th>
                                    <td id="td_activated_on_v" data-timestamp="asa">aaaa</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="btn_close_view_user" class="btn btn-light rounded">Close</button>                    
                </div>

            </div>
        </form>
    </div>
</div>
<!-- Modal - View User ENDS-->

<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<script src="assets/scodezy/js/moment/moment.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
