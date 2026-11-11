<?php 
checkAuthorizationForPage( 'manage_roles' );
?>

<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css"/>
<link href="plugins/<?=$plugin_name ?>/css/manage_roles.css?v=<?= rawurlencode( trim( (string) @file_get_contents( BASE_PATH . '/VERSION' ) ) ) ?>" rel="stylesheet" type="text/css"/>

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

<?php
/**
 * Create Role and Edit Role modals (1.0.107): name, slug, "Copy from role", and the permission editor
 * (js/role_permissions_editor.js) - every functionality grouped by plugin and feature, with search and filters,
 * tri-state plugin / group checkboxes, View only / Full buttons per group, warnings and a save summary.
 * Both modals use the same markup; the suffix c (create) / e (edit) keeps the element IDs of the older code.
 */
$rpe_modals = array(
    'c' => array( 'modal' => 'modal_create_role', 'form' => 'form_create_role', 'title' => 'Create Role',
                  'submit' => 'btn_create_functionality', 'submit_text' => 'Create', 'cancel' => 'btn_cancel_create_role_modal' ),
    'e' => array( 'modal' => 'modal_edit_role', 'form' => 'form_edit_role', 'title' => 'Edit Role',
                  'submit' => 'btn_update_role', 'submit_text' => 'Update', 'cancel' => 'btn_cancel_edit_role_modal' )
);
foreach( $rpe_modals as $sfx => $m ):
?>
<!-- Modal - <?= $m[ 'title' ] ?> BEGINS -->
<div class="modal fade" tabindex="-1" id="<?= $m[ 'modal' ] ?>" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable rpe-dialog">
        <form name="<?= $m[ 'form' ] ?>" id="<?= $m[ 'form' ] ?>" method="POST" class="modal-content rounded" data-parsley-validate onsubmit="return false;">
            <div class="modal-header">
                <h3 class="modal-title"><?= $m[ 'title' ] ?><span class="rpe-title-role text-gray-500 fw-normal ms-2"></span></h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body">
                <?php if( $sfx === 'e' ): ?>
                <input type="hidden" name="hidden_role_id_e" id="hidden_role_id_e" value="" />
                <?php endif; ?>

                <!-- Role name, slug, copy from role -->
                <div class="row g-5 mb-6">
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control rounded form-control-solid" id="input_role_name_<?= $sfx ?>" name="input_role_name_<?= $sfx ?>"
                                   placeholder="Provide a unique name for the Role" value="" required autocomplete="off"
                                   data-parsley-required="true"
                                   data-parsley-errors-container="#input_role_name_<?= $sfx ?>_error"
                                   data-parsley-pattern="<?=getValidationRegex( 'VLDTN_ROLE_NAME' ) ?>"
                                   data-parsley-error-message="Only lowercase, uppercase alphabets, digits 0 to 9 and white spaces are allowed"
                                   data-parsley-trigger="keyup | blur" />
                            <label for="input_role_name_<?= $sfx ?>">Role Name*</label>
                            <div id="input_role_name_<?= $sfx ?>_error" class="scodezy-error-msg"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <input type="text" class="form-control rounded form-control-solid" id="input_role_slug_<?= $sfx ?>" name="input_role_slug_<?= $sfx ?>"
                                   placeholder="Enter a unique slug for the Role" value="" required autocomplete="off"
                                   data-parsley-required="true"
                                   data-parsley-errors-container="#input_role_slug_<?= $sfx ?>_error"
                                   data-parsley-pattern="<?=getValidationRegex( 'VLDTN_ROLE_SLUG' ) ?>"
                                   data-parsley-error-message="Only lowercase alphabets, digits 0 to 9 and (underscore) _ are allowed"
                                   data-parsley-trigger="keyup | blur" />
                            <label for="input_role_slug_<?= $sfx ?>">Role Slug*</label>
                            <div id="input_role_slug_<?= $sfx ?>_error" class="scodezy-error-msg"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-floating">
                            <select class="form-select rounded form-select-solid rpe-copy" id="select_copy_role_<?= $sfx ?>" aria-label="Copy functionalities from a role">
                                <option value="">-</option>
                            </select>
                            <label for="select_copy_role_<?= $sfx ?>">Copy functionalities from role</label>
                        </div>
                    </div>
                </div>

                <!-- Permission editor -->
                <div class="rpe" id="rpe_<?= $sfx ?>">
                    <div class="rpe-toolbar">
                        <div class="rpe-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="search" class="form-control form-control-sm form-control-solid rpe-filter-text" placeholder="Search name, alias, feature or description" aria-label="Search functionalities" />
                        </div>
                        <select class="form-select form-select-sm form-select-solid rpe-filter-plugin" aria-label="Plugin">
                            <option value="">All plugins</option>
                        </select>
                        <select class="form-select form-select-sm form-select-solid rpe-filter-type" aria-label="Type">
                            <option value="">All types</option>
                            <option value="ACCESS">ACCESS</option>
                            <option value="CREATE">CREATE</option>
                            <option value="READ">READ</option>
                            <option value="UPDATE">UPDATE</option>
                            <option value="DELETE">DELETE</option>
                            <option value="SELECT">SELECT</option>
                        </select>
                        <select class="form-select form-select-sm form-select-solid rpe-filter-show" aria-label="Show">
                            <option value="">All</option>
                            <option value="granted">Granted</option>
                            <option value="missing">Not granted</option>
                        </select>
                        <div class="rpe-toolbar-actions">
                            <button type="button" class="btn btn-sm btn-light rpe-expand-all" title="Open every plugin">Expand all</button>
                            <button type="button" class="btn btn-sm btn-light rpe-collapse-all" title="Close every plugin">Collapse all</button>
                        </div>
                    </div>

                    <div class="rpe-summary">
                        <span class="rpe-summary-count"></span>
                        <span class="rpe-summary-change"></span>
                        <button type="button" class="btn btn-link btn-sm p-0 rpe-summary-warnings d-none"></button>
                    </div>

                    <div class="rpe-warnings d-none" role="alert"></div>

                    <div class="rpe-tree">
                        <div class="rpe-loading text-center text-gray-500 py-10">
                            <span class="spinner-border spinner-border-sm me-2"></span>Loading functionalities...
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="<?= $m[ 'cancel' ] ?>" class="btn btn-light rounded">Cancel</button>
                <button type="button" id="<?= $m[ 'submit' ] ?>" class="btn btn-primary rounded">
                    <span class="indicator-label"><?= $m[ 'submit_text' ] ?></span>
                    <span class="indicator-progress">Please wait...
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal - <?= $m[ 'title' ] ?> ENDS -->
<?php endforeach; ?>



<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<?php $rpe_asset_version = rawurlencode( trim( (string) @file_get_contents( BASE_PATH . '/VERSION' ) ) );   // ?v= so browsers load new code after each deploy (1.0.107) ?>
<script src="plugins/<?=$plugin_name ?>/js/role_permissions_editor.js?v=<?= $rpe_asset_version ?>"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js?v=<?= $rpe_asset_version ?>"></script>
