<?php 
    $rand = mt_rand();
?>

<link href="assets/plugins/custom/datatables/datatables.bundle.css?x=<?=$rand ?>" rel="stylesheet" type="text/css"/>
<link href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css?x=<?=$rand ?>" rel="stylesheet" type="text/css"/>

<!--begin::Post-->
<div class="post d-flex flex-column-fluid" id="kt_post">
    <!--begin::Container-->
    <div id="kt_content_container" class="container-fluid">
        <div class="card mb-5 mb-xl-10">
            <!--begin::Card header-->
            <div class="card-header">
                <!--begin::Heading-->
                <div class="card-title">
                    <h3 class="mt-2">Orders Listing</h3>
                </div>
                <!--end::Heading-->
                <!--begin::Card Toolbar-->
                <div class="card-toolbar">
                    <!--begin::Toolbar-->
                    <div class="d-flex flex-wrap" data-selected-table-toolbar="table_orders">
                        <button class="btn btn-sm btn-primary me-3 mt-3 hidden" id="btn_open_create_order_modal">Create</button>
                        <button class="btn btn-sm btn-primary me-3 mt-3" id="btn_open_prepare_order_summary_modal">Prepared Order Summary</button>
                    </div>
                    <!--begin::Toolbar-->
                    <!--begin::Group actions-->
                    <div class="d-flex justify-content-end align-items-center d-none group_actions" data-selected-table-name="table_orders">
                        <div class="fw-bold me-5">
                        <span class="me-2 selected_row_count"></span>Selected</div>
                        <button type="button" class="btn btn-danger me-2 delete_selected_rows" >Delete Selected</button>
                        <button type="button" class="btn btn-success export_selected_rows hidden" >Export Selected</button>
                    </div>
                    <!--end::Group actions-->
                </div>
                <!--end::Card Toolbar-->
                
            </div>
            <!--end::Card header-->
            
            <!--begin::Card body-->
            <div class="card-body pe-10 orders-table-loading ">
                <div class="table-responsive">
                    <table id="table_orders" class="table table-hover table-row-bordered border rounded gy-5 gs-7">
                        <thead>
                            <tr class="fw-semibold fs-6 text-gray-800">
                                <th class="min-w-50px">
                                    <div class="form-check form-check-custom form-check-solid form-check-sm ">
                                        <input class="form-check-input table-parent-checkbox" type="checkbox" id="flexRadioLg" value="1"/>
                                    </div>
                                </th>
                                <th class="min-w-100px">Order ID</th>
                                <th class="min-w-100px">Source</th>
                                <th class="min-w-100px">Created</th>
                                <th class="min-w-50px">Cancelled</th>
                                <!-- <th class="min-w-50px">Test</th> -->
                                <th class="min-w-30px">Order Status</th>
                                <th class="min-w-30px">Items</th>
                                <th class="min-w-30px">Order Total</th>
                                <th class="min-w-30px"></th>
                            </tr>
                        </thead>
                        <tbody> 
                            <!--
                            <tr>
                                <td>
                                    <div class="form-check form-check-custom form-check-sm">
                                        <input class="form-check-input table-children-checkbox" type="checkbox" value="" id="flexRadioLg"/>                                        
                                    </div>
                                </td>
                                <td>5572548146421</td>
                                <td>Shopify</td>
                                <td>21-10-2024 18:36</td>
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


<!-- Modal - View Address BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_view_address" style="z-index: 10000;">
    <div class="modal-dialog modal-md modal-dialog-centered">
        
        <div class="modal-content rounded">
            <div class="modal-header">
                <h3 class="modal-title" id="view_address_type">Order Address</h3>

                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <!--end::Close-->
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col">
                        <table class="table table-bordered table-view-address" id="table_view_address">
                            <tr>
                                <th class="w-175px">First Name</th>
                                <td id="address_first_name"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Last Name</th>
                                <td id="address_last_name"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Name on order</th>
                                <td id="address_name_on_order"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Contact Number</th>
                                <td id="address_contact"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Address Line 1</th>
                                <td id="address_address1"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Address Line 2</th>
                                <td id="address_address2"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">City</th>
                                <td id="address_city"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">State</th>
                                <td id="address_state"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Province</th>
                                <td id="address_province"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Zip/Postal Code</th>
                                <td id="address_zip"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Country</th>
                                <td id="address_country"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Latitude</th>
                                <td id="address_latitude"></td>
                            </tr>
                            <tr>
                                <th class="w-175px">Longitude</th>
                                <td id="address_longitude"></td>
                            </tr>
                            
                        </table>

                    </div>

                </div>
            </div>

        </div>
        
    </div>
</div>
<!-- Modal - View Address ENDS-->

<!-- Modal - View Products BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_view_products" style="z-index: 10000;">
    <div class="modal-dialog modal-md modal-dialog-centered">
        
        <div class="modal-content rounded">
            <div class="modal-header">
                <h3 class="modal-title">Order Items</h3>

                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <!--end::Close-->
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col">

                        <table class="table table-bordered table-list-products" id="view_products">
                            <tr>
                              
                            </tr>
                        </table>

                    </div>

                </div>
            </div>

        </div>
        
    </div>
</div>
<!-- Modal - View Products ENDS-->

<!-- Modal - Edit Order BEGINS -->
<div class="modal fade " tabindex="-1" id="modal_edit_order">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        
        <div class="modal-content rounded">
            <div class="modal-header">
                <h3 class="modal-title">Edit Order</h3>

                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <!--end::Close-->
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12 col-xs-12">
                        <table class="table table-bordered table-edit-order">
                            <tbody>
                                <tr>
                                    <th class="w-175px">Order ID</th>
                                    <td id="order_id_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Customer ID</th>
                                    <td id="customer_id_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Order Created At</th>
                                    <td id="created_at_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Order Updated At</th>
                                    <td id="updated_at_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Cancelled Status</th>
                                    <td id="is_cancelled_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Test Order ?</th>
                                    <td id="test_e" class=""></td>
                                </tr>
                                
                                <tr>
                                    <th class="">Contact Email</th>
                                    <td id="contact_email_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Order Email</th>
                                    <td id="email_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Financial Status</th>
                                    <td id="financial_status_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Fulfillment Status</th>
                                    <td id="fulfillment_status_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Order Status</th>
                                    <td id="order_status_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Billing Address</th>
                                    <td id="billing_address_e"><a href="#">View</a></td>
                                </tr>
                                <tr>
                                    <th class="">Shipping Address</th>
                                    <td id="shipping_address_e"><a href="#">View</a></td>
                                </tr>
                                
                            </tbody>
                        </table>

                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-12 col-sm-12 col-xs-12 col-xs-12">
                        <table class="table table-bordered table-edit-order">
                            <tbody>
                                <tr>
                                    <th class="w-175px">Items</th>
                                    <td id="items_e"><a href="#">View</a></td>
                                </tr>
                                <tr>
                                    <th class="">Currency</th>
                                    <td id="currency_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Products Total Price</th>
                                    <td id="total_products_price_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Total Discounts</th>
                                    <td id="total_discounts_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Order Subtotal</th>
                                    <td id="subtotal_price_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Shipping Method</th>
                                    <td id="shipping_method_name_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Shipping Amount</th>
                                    <td id="shipping_price_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Shipping Tax</th>
                                    <td id="shipping_tax_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Total Tax</th>
                                    <td id="total_tax_e"></td>
                                </tr>
                                <tr>
                                    <th class="">Order Total</th>
                                    <td id="total_price_e"></td>
                                </tr>
                                
                            </tbody>
                        </table>
                        
                        <table class="table table-bordered table-order-meta">
                            <thead>
                                <tr>
                                    <th class="w-175px">Order Source</th>
                                    <td id="source_e"></td>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                        
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <!--begin::Submit button-->
                
                <button type="button" id="btn_cancel_order" class="btn btn-danger rounded hidden">Cancel Order</button>
                <button type="button" id="btn_close_edit_modal" class="btn btn-primary rounded">Close</button>
                
                <!--end::Submit button-->
            </div>

        </div>
        
    </div>
</div>
<!-- Modal - Edit Order ENDS-->

<!-- Modal - Prepare Order BEGINS-->
<div class="modal fade " tabindex="-1" id="modal_prepare_order" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        
        <div class="modal-content rounded">
            <div class="modal-header">
                <h3 class="modal-title">Prepare Delivery Order</h3>

                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <!--end::Close-->
            </div>
            
            <div class="modal-body ">
                
                <div class="div_prepare_order_loading"></div>
                <div class="div_prepare_order_content sections_to_hide_together">
                    <form name="form_prepare_order" id="form_prepare_order" method="POST" data-parsley-validate>
                        <input type="hidden" name="po_hidden_order_id" id="po_hidden_order_id" value="" /> 
                        <div class="row">
                            <div class="col">
                                <table class="table table-borderless table-prepare-order">
                                    <tr>
                                        <th class="min-w-150px w-50 m-0 p-0"><h2 class="m-0 p-0">Order Information</h2></th>
                                        <th class="min-w-150px w-50 m-0 p-0"><h2 class="m-0 p-0" id="po_status">Order Not Prepared for Delivery</h2></th>                                
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">                        
                                <table class="table table-bordered table-prepare-order mt-1">
                                    <tr>
                                        <th class="min-w-150px">Order ID:</th>
                                        <td id="po_order_id"></td>
                                    </tr>
                                    <tr>
                                        <th class="min-w-150px">Time of Order:</th>
                                        <td id="po_time_of_order"></td>
                                    </tr>
                                    <tr>
                                        <th class="min-w-150px text-danger">Delivery Deadline:</th>
                                        <td id="po_delivery_deadline" class="text-danger"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <div class="form-floating">
                                                <textarea class="form-control rounded h-75px form-control-solid mb-0 pb-0" id="po_remarks" name="po_remarks" 
                                                        placeholder="Remarks" 
                                                        value=""
                                                        data-parsley-errors-container="#po_remarks_error" 
                                                        autocomplete="off" 
                                                        data-parsley-pattern="<?=getValidationRegex( 'VLDTN_PREPARE_ORDER_REMARKS' ) ?>"
                                                        data-parsley-error-message="Certain special characters are not allowed for remarks"
                                                        data-parsley-trigger="keyup | blur"></textarea>
                                                <label for="po_remarks">Remarks</label>
                                                <div id="po_remarks_error" class="scodezy-error-msg"></div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <table class="table table-bordered table-prepare-order mt-1">
                                    <tr>
                                        <th class="min-w-100px">Order Source:</th>
                                        <td id="po_order_source"></td>
                                    </tr>
                                    <tr>
                                        <th class="min-w-100px">Contact No:</th>
                                        <td id="po_contact_number"><a href="tel:"></a></td>
                                    </tr>
                                    <tr>
                                        <th class="min-w-100px">Address:</th>
                                        <td id="po_shipping_address"></td>
                                    </tr>
                                </table>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col">
                                <h3 class="mt-8 mb-1 mt-1">Items</h3>
                                <table class="table table-bordered table-prepare-order-products">
                                    <thead>
                                        <tr>
                                            <th class="min-w-10px">Sr. No.</th>
                                            <th class="min-w-250px">Product Name</th>
                                            <th class="min-w-50px">Quantity</th>
                                            <th class="min-w-10px"></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>                            
                        </div>
                        <div class="row">
                            <div class="col">
                                
                            </div>
                        </div>
                    </form>
                </div>

            </div>
            <div class="modal-footer sections_to_hide_together">
                <!--begin::Submit button-->
                <button type="reset" id="btn_reset_po" class="btn btn-danger rounded hidden">Reset</button>
                <button type="button" id="btn_unprepare_order" class="btn btn-danger rounded">Unprepare Order</button>
                <button type="button" id="btn_prepare_order" class="btn btn-primary rounded">Prepare Order</button>
                <!--end::Submit button-->
            </div>
            
        </div>
        
    </div>
</div>
<!-- Modal - Prepare Order ENDS-->


<!-- Modal - View Prepare Order Summarn BEGINS-->
<div class="modal fade " tabindex="-1" id="modal_prepare_order_summary" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        
        <div class="modal-content rounded">
            <div class="modal-header">
                <h3 class="modal-title">Prepared Order Summary</h3>

                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
                <!--end::Close-->
            </div>
            
            <div class="modal-body ">
                
                <div class="div_po_summary_loading sections_to_hide_together"></div>
                <div class="div_po_summary_content sections_to_hide_together">
                    <form name="form_po_summary" id="form_po_summary" method="POST" data-parsley-validate>
                        <input type="hidden" name="po_summary_hidden_order_id" id="po_summary_hidden_order_id" value="" /> 
                        <div class="row">
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">                        
                                <table class="table table-bordered table-prepared-order-summary mt-1">
                                    <tr>
                                        <th class="min-w-150px">Order IDs:</th>
                                        <td id="po_summary_order_id" class="dynamic"></td>
                                    </tr>
                                    <tr>
                                        <th class="min-w-150px">Total Orders:</th>
                                        <td id="po_summary_total_orders" class="dynamic"></td>
                                    </tr>
                                    <tr>
                                        <th class="min-w-150px">Total Products/Items:</th>
                                        <td id="po_summary_total_items" class="dynamic"></td>
                                    </tr>                                    
                                </table>
                            </div>
                            <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                <table class="table table-bordered table-prepared-order-summary mt-1">
                                    <tr>
                                        <td class="fs-2">
                                            Please tally the number of products loaded into the Delivery Van against the summary in order to prevent problems during the delivery
                                        </td>
                                    </tr>
                                </table>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col">
                                <h3 class="mt-8 mb-1 mt-1">Items</h3>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover table-prepared-order-summary-items">
                                        <thead>
                                            <tr>
                                                <th class="min-w-10px">Sr. No.</th>
                                                <th class="min-w-250px">Product Name</th>
                                                <th class="min-w-100px">SKU</th>
                                                <th class="min-w-50px">Quantity</th>
                                                <th class="min-w-10px"></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>                            
                        </div>
                        <div class="row">
                            <div class="col">
                                
                            </div>
                        </div>
                    </form>
                </div>

            </div>
            <div class="modal-footer sections_to_hide_together">
                <!--begin::Submit button-->
                <button type="button" id="btn_close_po_summary_modal" class="btn btn-primary rounded">Close</button>
                <!--end::Submit button-->
            </div>
            
        </div>
        
    </div>
</div>
<!-- Modal - View Prepare Order Summarn ENDS-->


<!-- Modal - View Prepare Order Summarn BEGINS-->
<div class="modal fade " tabindex="-1" id="modal_confirm_delivery" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">        
        
            <div class="modal-content rounded">
                <div class="modal-header">
                    <h3 class="modal-title">Confirm Delivery</h3>

                    <!--begin::Close-->
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <!--end::Close-->
                </div>

                <div class="modal-body">
                    <div class="cd_loading sections_to_hide_together"></div>
                    <div class="cd_content sections_to_hide_together">
                        <form name="form_confirm_delivery" id="form_confirm_delivery" data-parsley-validate>
                            <input type="hidden" name="cd_hidden_order_id" id="cd_hidden_order_id" />
                            <div class="row">
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <table class="table table-bordered table-confirm-delivery-summary">
                                        <tr>
                                            <th class="min-w-150px">Order ID:</th>
                                            <td id="cd_order_id"></td>
                                        </tr>
                                        <tr>
                                            <th class="min-w-150px">Customer Contact:</th>
                                            <td id="cd_contact"></td>
                                        </tr>
                                        <tr>
                                            <th class="min-w-150px">Customer Address:</th>
                                            <td id="cd_address"></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">
                                    <table class="table table-bordered table-confirm-delivery-summary">
                                        <tr>
                                            <td>Please ensure that you are at the correct address</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 ">
                                    <div class="cd_content_loading sections_to_hide_together"></div>
                                    <div class="cd_content sections_to_hide_together ">

                                        <h3 class="mt-8 mb-1 mt-1">Items</h3>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-confirm-delivery-items">
                                                <thead>
                                                    <tr>

                                                        <th class="min-w-10px">Sr. No.</th>
                                                        <th class="min-w-250px">Product Name</th>
                                                        <th class="min-w-100px">SKU</th>
                                                        <th class="min-w-50px">Product Serial Number</th>
                                                        <th class="min-w-10px"></th>
                                                        <!-- 
                                                        <th class="">Sr. No.</th>
                                                        <th class="">Product Name</th>
                                                        <th class="">SKU</th>
                                                        <th class="">Product Serial Number</th>
                                                        <th class=""></th>
                                                        -->
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr data-type="product-id" data-value="">
                                                        <td>1.</td>
                                                        <td>50" TV</td>
                                                        <td>101043</td>
                                                        <td class="product-serial-number"><span class="first-half-serial-number">101043</span><input placeholder="Last 8 Digits" type="text" class="form-control second-half-serial-number" /></td>
                                                        <td>
                                                            <div class="form-check form-check-custom form-check-lg">
                                                                <input class="form-check-input" type="checkbox" value="" id="flexRadioLg" />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                    <h3 class="mt-8 mb-2 mt-1">Upload Pictures *</h3>
                                    <div class="cd_picture necessary">
                                        <!-- <i class="fa fa-close text-light bg-danger rounded p-2 me-5 cd_remove_picture_file"></i> -->
                                        <input type="file" name="cd_picture_file" accept="image/*" class="cd_picture_file" capture>
                                    </div>
                                    <button type="button" id="btn_add_more_pictures" name="btn_add_more_pictures" class="btn btn-sm btn-primary rounded mt-2 d-block">
                                        <i class="fa fa-plus"> </i> Add More
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="modal-footer sections_to_hide_together">
                    <button type="button" id="btn_submit_confirm_delivery" class="btn btn-primary rounded">Confirm</button>
                </div>

            </div>
        
    </div>
</div>

<script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
<script src="assets/scodezy/js/moment/moment.js"></script>
<script src="plugins/<?=$plugin_name ?>/js/order-meta-key-mappings.js?x=<?=$rand ?>"></script>
<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js?x=<?=$rand ?>"></script>
