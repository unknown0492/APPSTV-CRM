<?php 
	checkIfLoggedIn();
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$pgDeleteButton = new FormField( "button", array(
		"style"=>"margin: 10px 0px 10px 0px; margin-left: 0px;",
		"type"=>"button",
		"class"=>"btn red", 
		"id"=>"delete_privilege_group_button",
		"onclick"=>"deletePrivilegeGroup( this );",
	), "<i class=\"fa fa-trash-o\"></i> Delete Group" );
	
	
	$editPrForm = new FormHelper( "form_edit_privilege" );
	
	$labelPGID = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Privilege Group*" );
	$labelIsPage  = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Is a Page?" );
	
	$labelFnName = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Functionality Name*" );
	$textFnName = new FormField( "text", array(
			"name"=>"functionality_name",
			"id"=>"functionality_name",
			"ng-model"=>"functionality_name",
			"class"=>"form-control",
			"form-name"=>$editPrForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_FUNCTIONALITY_NAME",
			"required"=>"required",
			"placeholder"=>"Functionality Name",
	), NULL );
	
	$hiddenFnID = new FormField( "hidden", array(
		"id"=>"functionality_id",
		"name"=>"functionality_id",
		"ng-model"=>"functionality_id",
	), NULL );
	
	$labelPrName = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Privilege Name*" );
	$textPrName = new FormField( "text", array(
			"name"=>"privilege_name",
			"id"=>"privilege_name",
			"ng-model"=>"privilege_name",
			"class"=>"form-control",
			"form-name"=>$editPrForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PRIVILEGE_NAME",
			"required"=>"required",
			"placeholder"=>"Privilege Name",
	), NULL );
	
	$hiddenPrID = new FormField( "hidden", array(
			"id"=>"privilege_id",
			"name"=>"privilege_id",
			"ng-model"=>"privilege_id",
	), NULL );
	
	$labelPrDesc = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Privilege Description*" );
	$textareaPrDesc = new FormField( "textarea", array(
			"name"=>"privilege_description",
			"id"=>"privilege_description",
			"ng-model"=>"privilege_description",
			"class"=>"form-control",
			"form-name"=>$editPrForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PRIVILEGE_DESCRIPTION",
			"required"=>"required",
			"placeholder"=>"Privilege Description",
	), "" );
	
	$labelFnDesc = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Functionality Description*" );
	$textareaFnDesc = new FormField( "textarea", array(
			"name"=>"functionality_description",
			"id"=>"functionality_description",
			"ng-model"=>"functionality_description",
			"class"=>"form-control",
			"form-name"=>$editPrForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_FUNCTIONALITY_DESCRIPTION",
			"required"=>"required",
			"placeholder"=>"Functionality Description",
	), "" );
	
	$labelSelectPlugin = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Plugin*" );
	
	$hiddenPr = new FormField( "hidden", array(
			"name"=>"what_do_you_want",
			"id"=>"what_do_you_want",
			"value"=>"update_privilege",
	), NULL );
	
	$buttonPrSubmit = new FormField( "submit", array(
			"class"=>"btn blue",
			"name"=>"submit_".$editPrForm->getFormName(),
			"id"=>"submit_".$editPrForm->getFormName(),
			"ng-disabled"=>$editPrForm->getFormName().'.$invalid'
	), "Update" );
?>

<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="assets/global/plugins/bootstrap-select/css/bootstrap-select.min.css" />

<!-- BEGIN PAGE HEAD-->
<div class="page-head">
	<!-- BEGIN PAGE TITLE -->
	<div class="page-title">
		<h1>
			View/Edit Privileges
		</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD-->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">View/Edit Privileges</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered">
	<h3>Privilege Management</h3>
	<p>
		View or Edit/Delete the privileges that exist in the system.
	</p>
</div>


<div class="row" ng-app="edit_privilege_app" ng-controller="edit_privilege_ctrl">
	<div class="col-md-12">
		<div class="portlet box green">
			<div class="portlet-title">
				<div class="caption">
					<i class="fa fa-crosshairs"></i> View Privileges for Selected Group
				</div>
			</div>
			<div class="portlet-body">
				<div class="row" style="padding: 10px;">
				<?php 
	            	$sql = "Select * FROM privilege_group ORDER BY privilege_group_sequence";
	                $result_set0 = selectQuery( $sql );
	                $result_set_temp = $result_set0;
                ?>
	                <div class="col-lg-4 col-md-4 col-sm-8 col-xs-12">
	                	<label><strong>Select Privilege Group : </strong></label><br/>
	                    <select class="form-control input-medium" 
	                        data-live-search="true" 
	                        name="selected_privilege_group_id" 
	                        id="selected_privilege_group_id" 
	                        ng-model="selected_privilege_group_id" ng-change="changePrivilegeGroup(this.id);" >
	                        <!-- <option value="0">All Other Privileges</option> -->
	                        <?php 
	                        	$i = 1;
	                            while( ( $val0 = mysqli_fetch_object( $result_set0 ) ) != NULL ){
	                            ?>
	                            	<option value="<?=$val0->privilege_group_id ?>" <?=(@$_REQUEST[ 'selected_privilege_group_id' ]==$val0->privilege_group_id)?"selected":"" ?> <?=(!isset($_REQUEST[ 'selected_privilege_group_id' ]) && $i==1)?"selected":"" ?>><?=$val0->privilege_group_name ?></option>
	                            <?php
	                            	$i++;
	                            }
	                            ?>
	                    </select>
	                    
	                    <?=$pgDeleteButton->generateField() ?>
	                    
	                    <br />
	                </div>
	                <div class="col-md-12 col-sm-12 col-xs-12 table-responsive">
	                	<table id="view-privilege-table" class="table table-striped table-bordered table-hover order-column">
	                    	<thead>
	                        	<tr>
									<th width="80px;">Sr. No.</th>
									<th width="80px;"><strong>PID</strong></th>
									<th><strong>Privilege Name</strong></th>
									<th><strong>Description</strong></th>
									<th width="80px;"><strong>FID</strong></th>
									<th><strong>Functionality Name</strong></th>
									<th><strong>Description</strong></th>
									<th><strong>Is a Page</strong></th>
									<th><strong>Options</strong></th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
						<div class="table-loading hidden">
							<center>
								<img src="images/small-loading.gif" style="height: 100px;" />
					    		<p>Loading Content...</p>
					    	</center>
					    </div>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	<div class="modal fade" id="edit-privilege-modal" aria-hidden="true" >
        <div class="modal-dialog modal-md">
        	<div class="modal-content">
            	<div class="modal-header">
                	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                    <h4 class="modal-title"><strong>Edit</strong> Privilege</h4>
                </div>
                <div class="modal-body">
                	<div class="row">
                    	<div class="col-md-12 privileges-loading">
	                		<center>
								<img src="images/small-loading.gif" style="height: 100px;" />
								<p>Loading Content...</p>
							</center>
                		</div>
                    	<div class="col-md-12 privileges-loaded hidden">
                        	<form name="<?=$editPrForm->getFormName() ?>" id="<?=$editPrForm->getFormName() ?>" method="POST" 
		            			ng-submit="updatePrivilege( <?=$editPrForm->getFormName() ?>, $event )" novalidate >
			                	
			                	<!-- Privilege Group Name -->
			                    <div class="col-lg-6">
			                    	<div class="form-group">
			                    		<?=$labelPGID->generateField() ?>
			            				<select class="form-control" 
			            					id="privilege_group_id" 
			            					name="privilege_group_id" title="Select Privilege Group">
				                        	
				                        	<option value="none" disabled>Select Privilege Group</option>
				                        	<?php 
				                        	$sql = "Select * FROM privilege_group";
				                        	$result_set = selectQuery( $sql );
				                        	while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
				                        	?>
				                        		<option value="<?=$val->privilege_group_id ?>"><?=$val->privilege_group_name ?></option>	
				                        	<?php
				                        	}
				                        	?>
				                        </select>
			                    	</div>
			                    </div>
			                    <!--/ Privilege Group Name -->
			                    
			                    <!-- IS a Page ? -->
			                    <div class="col-lg-6">
			                    	<div class="form-group">
			                    		<?=$labelIsPage->generateField() ?>
				                    	<select class="form-control" id="is_page" name="is_page" ng-model="is_page">
				                        	<option value="0">No</option>
				                        	<option value="1">Yes</option>
				                        </select>
			                    	</div>
			                    </div>
			                    <!--/ Is a Page ? -->
			                    
			                    
								<!-- Functionality Name -->
			                    <div class="col-lg-6">
									<div class="form-group">
										<?=$labelFnName->generateField() ?>
				                    	<?=$hiddenFnID->generateField() ?>
				                    	<?=$textFnName->generateField() ?>
									</div>
								</div>
								<!--/ Functionality Name -->
			                    
			                    <!-- Privilege Name -->
			                    <div class="col-lg-6">
									<div class="form-group">
										<?=$labelPrName->generateField() ?>
				                    	<?=$hiddenPrID->generateField() ?>
				                    	<?=$textPrName->generateField() ?>
									</div>
								</div>
								<!--/ Privilege Name -->
			                    
								
								
								<!-- Privilege Desciption -->
			                    <div class="col-lg-6">
									<div class="form-group">
				                    	<?=$labelPrDesc->generateField() ?>	
				                    	<?=$textareaPrDesc->generateField() ?>
				                    </div>
									
								</div>
			                    <!--/ Privilege Desciption -->
			                    
			                    <!-- Functionality Desciption -->
			                    <div class="col-lg-6">
			                    	<div class="form-group">
				                    	<?=$labelFnDesc->generateField() ?>	
				                    	<?=$textareaFnDesc->generateField() ?>
									</div>
			                    </div>
								<!--/ Functionality Desciption -->
			                    
			                    <!-- Select Plugin -->
			                    <div class="col-lg-6">
			                    	<div class="form-group">
			                    		<?=$labelSelectPlugin->generateField() ?>
			                    		<select id="plugin_id" name="plugin_id" ng-model="plugin_id" class="form-control">
			                    			<option value="none" disabled selected>Please choose a Plugin</option>
			                    			<?php 
			                    			$sql = "SELECT * FROM plugins ORDER by plugin_name";
			                    			$result_set = selectQuery( $sql );
			                    			while( ( $val = mysqli_fetch_object( $result_set ) ) != null ){
			                    			?>
			                    			<option value="<?=$val->plugin_id ?>"><?=$val->plugin_display_name ?></option>
			                    			<?php 
											}
			                    			?>
			                    		</select>
			                    	</div>
			                    </div>
			                    <!--/ Select Plugin -->
			                    
			                    
			                    
								<div class="col-lg-12">
									<center>
					                    <?=$hiddenPr->generateField() ?>
					                    <?=$buttonPrSubmit->generateField() ?>
					                    <div style="display: inline-block;" class="loading-update-privilege hidden">
					                    	<img src="images/small-loading.gif" style="height: 30px;" />
								    		<span> Updating...</span>
								    	</div>
		                    		</center>
		                    	</div>
		                    </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
	<div class="md-overlay"></div>
	</div>
	
	
	
	
	
</div>
<!-- END MAIN CONTENT -->
	
	
	

	


	<!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="assets/global/plugins/bootstrap-select/js/bootstrap-select.min.js"></script>
    <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
    <script src="js/angular.js"></script>
    <!-- END PAGE LEVEL SCRIPTS -->
    
    
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
	
	