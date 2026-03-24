<link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css"/>
<link href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css" rel="stylesheet" type="text/css"/>

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-fluid">
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Heading-->
                <div class="card-title">
                    <h3 class="mt-2">Products Listing</h3>
                </div>
                <!--end::Heading-->
                <!--begin::Card Toolbar-->
                <div class="card-toolbar ">
                    <!--begin::Toolbar-->
                    <div class="d-flex flex-wrap " data-selected-table-toolbar="table_products">
                        <button class="btn btn-sm btn-primary me-3 mt-2" id="btn_open_create_product_modal">Create</button>
                    </div>
                    <!--begin::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none group_actions" data-selected-table-name="table_products">
                        <div class="fw-bold me-5">
                        <span class="me-2 selected_row_count"></span>Selected</div>
                        <button type="button" class="btn btn-danger btn-sm me-2 delete_selected_rows" >Delete Selected</button>
                    </div>
                    <!--end::Group actions-->
                </div>
                <!--end::Card Toolbar-->
                
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="card-body pe-10 products-table-loading ">
                <div class="table-responsive">
                    <table id="table_products" class="table table-hover table-row-bordered border rounded gy-5 gs-7">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th class="min-w-50px">
                                    <div class="form-check form-check-custom form-check-solid form-check-sm ">
                                        <input class="form-check-input table-parent-checkbox" type="checkbox" id="flexRadioLg" value="1"/>
                                    </div>
                                </th>
                                <th class="w-150px">Product ID</th>
                                <th class="w-100px">SKU</th>
                                <th class="min-w-100px">Name</th>
                                <th class="min-w-50px">Physical Product</th>
                                <th class="min-w-50px">Price</th>
                                <th class="min-w-30px">Actions</th>
                            </tr>
                        </thead>
                        <tbody> 
                            
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
    <!--end::Container-->
</div>
<!--end::Post-->


<!-- Modal - Create Product Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_create_product">
    <div class="modal-dialog modal-lg">
        <form name="form_create_product" id="form_create_product" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Create Product</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row row-cols-2">
                        <div class="col">
                            <!-- SKU -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="sku_c" name="sku_c" 
                                       placeholder="Stock Keeping Unit" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#sku_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_SKU' ) ?>"
                                        data-parsley-error-message="SKU can contain only uppercase alphabets and numerical digits"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="sku_c">SKU*</label>
                                <div id="sku_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- GTIN -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="gtin_c" name="gtin_c" 
                                       placeholder="Universal Barcode Serial" 
                                        value=""
                                        data-parsley-errors-container="#gtin_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_DIGITS' ) ?>"
                                        data-parsley-error-message="GTIN can only contain numerical digits"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="gtin_c">GTIN</label>
                                <div id="gtin_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Product Name -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="name_c" name="name_c" 
                                       placeholder="Name for the product" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#name_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_NAME' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for product name"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="name_c">Product Name*</label>
                                <div id="name_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Product Title -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="title_c" name="title_c" 
                                       placeholder="Listing title on store for the product" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#title_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_TITLE' ) ?>"
                                        data-parsley-error-message="Some special characters are not allowed for product title"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="title_c">Title*</label>
                                <div id="title_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <div class="form-floating mb-7">
                                <textarea class="form-control rounded h-150px form-control-solid" id="description_c" name="description_c" 
                                       placeholder="Description for the product" 
                                        value=""
                                        data-parsley-errors-container="#description_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_DESCRIPTION' ) ?>"
                                        data-parsley-error-message="Certain special characters are not allowed for product description"
                                        data-parsley-trigger="keyup | blur"></textarea>
                                <label for="description_c">Product Description</label>
                                <div id="description_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Product Picture Upload -->
                            <div class="form-group scodezy-file-upload">
                                <p class="text-gray-600 fw-bold">Product Picture</p>
                                <input type="file" class="hidden" name="file_product_image_c" id="file_product_image_c" />
                                <button type="button" id="btn_select_product_image_c" name="btn_select_product_image_c" class="btn btn-sm btn-primary">Select Picture</button>
                                <p class="text-gray-600 bold mt-2">Please select only compatible JPEG/PNG files for product picture</p>
                                <div class="scodezy-selected-files">
                                   
                                </div>
                            </div>
                            
                            <!-- -->
                            <!--
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="_c" name="_c" 
                                       placeholder="" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern=""
                                        data-parsley-error-message=""
                                        data-parsley-trigger="keyup | blur" />
                                <label for="_c">Role Name*</label>
                                <div id="_c_error" class="scodezy-error-msg"></div>
                            </div>
                            -->
                            
                        </div>
                        <div class="col">
                            
                            
                            
                            <!-- Dimensions-->
                            <div class="row">
                                <p class="text-gray-600 fw-bold">Dimensions (cm)</p>
                                <div class="col">
                                    <div class="form-floating mb-7">
                                        <input type="text" class="form-control rounded form-control-solid" id="height_c" name="height_c" 
                                               placeholder="Height" 
                                                value=""
                                                data-parsley-errors-container="#height_c_error" 
                                                autocomplete="off" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_DIMENSION' ) ?>"
                                                data-parsley-error-message="Height is invalid"
                                                data-parsley-trigger="keyup | blur" />
                                        <label for="height_c">Height</label>
                                        <div id="height_c_error" class="scodezy-error-msg"></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating mb-7">
                                        <input type="text" class="form-control rounded form-control-solid" id="width_c" name="width_c" 
                                               placeholder="Width" 
                                                value=""
                                                data-parsley-errors-container="#width_c_error" 
                                                autocomplete="off" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_DIMENSION' ) ?>"
                                                data-parsley-error-message="Width is invalid"
                                                data-parsley-trigger="keyup | blur" />
                                        <label for="width_c">Width</label>
                                        <div id="width_c_error" class="scodezy-error-msg"></div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="form-floating mb-7">
                                        <input type="text" class="form-control rounded form-control-solid" id="depth_c" name="depth_c" 
                                               placeholder="Depth" 
                                                value=""
                                                data-parsley-errors-container="#depth_c_error" 
                                                autocomplete="off" 
                                                data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_DIMENSION' ) ?>"
                                                data-parsley-error-message="Depth is invalid"
                                                data-parsley-trigger="keyup | blur" />
                                        <label for="width_c">Depth</label>
                                        <div id="depth_c_error" class="scodezy-error-msg"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Weight -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="weight_c" name="weight_c" 
                                       placeholder="Product Weight*" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#weight_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_WEIGHT' ) ?>"
                                        data-parsley-error-message="Weight is invalid"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="weight_c">Weight (grams)*</label>
                                <div id="weight_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            <!-- Product Price -->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control rounded form-control-solid" id="price_c" name="price_c" 
                                       placeholder="" 
                                        value=""
                                        required
                                        data-parsley-required="true"
                                        data-parsley-errors-container="#price_c_error" 
                                        autocomplete="off" 
                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PRODUCT_PRICE' ) ?>"
                                        data-parsley-error-message="Price is invalid"
                                        data-parsley-trigger="keyup | blur" />
                                <label for="price_c">Listing Price*</label>
                                <div id="price_c_error" class="scodezy-error-msg"></div>
                            </div>
                            
                            
                            <!-- Price Inclusive of Tax -->
                            <div class="input-group input-group-solid flex-nowrap mb-7">
                                <div class="overflow-hidden form-floating flex-grow-1">
                                    <select id="tax_inclusive_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Please select" data-search="true"
                                            data-dropdown-parent="#modal_create_product" data-dropdown-parent="body" >
                                        <option></option>
                                        <option value="1">YES</option>
                                        <option value="0">NO</option>
                                    </select>
                                    <label for="tax_inclusive_c">Price Inclusive of Tax ?*</label>
                                </div>                                
                            </div>
                            
                            <!-- Physical Product -->
                            <div class="input-group input-group-solid flex-nowrap mb-7">
                                <div class="overflow-hidden form-floating flex-grow-1">
                                    <select id="physical_product_c" class="form-select form-select-solid rounded h-50px" data-control="select2" data-placeholder="Please select" data-search="true"
                                            data-dropdown-parent="#modal_create_product" data-dropdown-parent="body" >
                                        <option></option>
                                        <option value="1">YES</option>
                                        <option value="0">NO</option>
                                    </select>
                                    <label for="physical_product_c">Physial Product ?*</label>
                                </div>                                
                            </div>
                        </div>
                        
                        
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="reset" id="btn_reset_create_product_form" class="btn btn-light rounded">Reset</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_create_product" class="btn btn-primary rounded">
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
<!-- Modal - Create Products Modal ENDS-->


<!-- Modal - Product Inventory Modal BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_manage_product_inventory">
    <div class="modal-dialog modal-md">
        <form name="form_manage_product_inventory" id="form_manage_product_inventory" method="POST" data-parsley-validate>
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Manage Inventory</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col">
                            
                            <input type="hidden" id="hidden_selected_product_id_for_inventory" name="hidden_selected_product_id_for_inventory" value="" />
                            <table class="table table-bordered">
                                <tr>
                                    <th class="w-150px">Product ID:</th>
                                    <td id="product_id_manage_inventory"></td>
                                </tr>
                                <tr>
                                    <th class="w-150px">Product Name:</th>
                                    <td id="product_name_manage_inventory"></td>
                                </tr>
                            </table>
                            
                            <!-- Store -->
                            <div class="input-group input-group-solid flex-nowrap mb-7">
                                <div class="overflow-hidden form-floating flex-grow-1">
                                    <select id="store" class="form-select form-select-solid rounded h-50px" 
                                            data-control="select2" data-placeholder="Please select" data-search="true"
                                            data-dropdown-parent="#modal_manage_product_inventory" data-dropdown-parent="body" >
                                        <option></option>
                                        <!-- <option value="19701458">CRM</option> -->
                                        <option value="22418569">QuickBooks</option>
                                        <option value="s7YtgHu79D_so">Shopify</option>
                                    </select>
                                    <label for="store">Source*</label>
                                </div>                                
                            </div>
                            
                            <div class="div_inventory_loading_section no-z-index sections_to_hide_together"></div>
                            <div class="div_inventory_section sections_to_hide_together">
                                <!-- Current Stock Count -->
                                <div class="form-floating mb-7">
                                    <input type="text" class="form-control rounded form-control-solid" id="current_inventory_count" name="current_inventory_count" 
                                            value=""
                                            autocomplete="off"
                                            readonly="readonly" />
                                    <label for="current_inventory_count">Current Stock Count</label>
                                </div>
                                
                                <!-- New Stock Count -->
                                <div class="form-floating mb-15">
                                    <input type="text" class="form-control rounded form-control-solid" id="new_inventory_count" name="new_inventory_count" 
                                           placeholder="" 
                                            value=""
                                            required
                                            data-parsley-required="true"
                                            data-parsley-errors-container="#new_inventory_count_error" 
                                            autocomplete="off" 
                                            data-parsley-pattern="<?=getValidationRegex( 'VLDTN_DIGITS_INC_NEGATIVE' ) ?>"
                                            data-parsley-error-message="New stock count value is invalid"
                                            data-parsley-trigger="keyup | blur" />
                                    <label for="new_inventory_count">Additional Stock Count*</label>
                                    <div id="new_inventory_count_error" class="scodezy-error-msg"></div>
                                </div>
                                
                                <p class="text-gray-600 inventory_history"></p>
                                
                                <table class="table table-bordered">
                                    <tr>
                                        <th class="w-250px">Total Inventory after Update:</th>
                                        <td id="total_inventory_after_update"></td>
                                    </tr>
                                </table>
                            </div>
                            
                            
                        </div>                        
                    </div>
                </div>

                <div class="modal-footer sections_to_hide_together">
                    <button type="reset" id="btn_reset_manage_inventory" class="btn btn-light rounded hidden">Reset</button>
                    <!--begin::Submit button-->
                    <div class="d-grid ">
                        <button type="button" id="btn_update_inventory" class="btn btn-primary rounded">Update</button>
                    </div>
                    <!--end::Submit button-->
                </div>

            </div>
        </form>
    </div>
</div>
<!-- Modal - Product Inventory Modal ENDS -->


<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<script src="assets/scodezy/js/moment/moment.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
