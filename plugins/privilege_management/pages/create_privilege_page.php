<?php 
	checkIfLoggedIn();	
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$createPGForm = new FormHelper( "form_create_privilege_group" );
	
	$labelPGName = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Privilege Group Name*" );
	$textPGName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"privilege_group_name",
			"name"=>"privilege_group_name",
			"ngValidated"=>"true",
			"ng-model"=>"privilege_group_name",
			"ng-pattern"=>"VLDTN_PRIVILEGE_GROUP_NAME",
			"required"=>"required",
			"placeholder"=>"Privilege Group Name",
			"form-name"=>$createPGForm->getFormName()
	), NULL );
	
	$labelPGSequence = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Privilege Group Sequence*" );
	$textPGSequence	= new FormField( "text", array(
			"class"=>"form-control input-medium",
			"id"=>"privilege_group_sequence",
			"name"=>"privilege_group_sequence",
			"ngValidated"=>"true",
			"ng-model"=>"privilege_group_sequence",
			"ng-pattern"=>"VLDTN_DIGITS",
			"required"=>"required",
			"placeholder"=>"Sequence",
			"form-name"=>$createPGForm->getFormName()
	), NULL );
	
	$hiddenPG = new FormField( "hidden", array(
			"name"=>"what_do_you_want",
			"id"=>"what_do_you_want",
			"value"=>"create_privilege_group"
	), NULL );
	
	$createPGButton = new FormField( "submit", array(
			"value"=>"Create",
			"class"=>"btn btn-primary",
			"name"=>"submit_".$createPGForm->getFormName(),
			"id"=>"submit_".$createPGForm->getFormName(),
			"ng-disabled"=>$createPGForm->getFormName().'.$invalid'
	), "Create" );
	
	$createPGReset = new FormField( "reset", array(
			"value"=>"",
			"class"=>"btn btn-danger",
			"name"=>"reset_".$createPGForm->getFormName(),
			"id"=>"reset_".$createPGForm->getFormName(),
	), "Reset" );
	
	
	
	
	$createPrForm = new FormHelper( "form_create_privilege" );
	$labelPrGroup = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Privilege Group*" );
	$labelIsPage  = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Is a Page?" );
	
	$labelFnName = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Functionality Name*" );
	$textFnName = new FormField( "text", array(
			"name"=>"functionality_name",
			"id"=>"functionality_name",
			"ng-model"=>"functionality_name",
			"class"=>"form-control",
			"form-name"=>$createPrForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_FUNCTIONALITY_NAME",
			"required"=>"required",
			"placeholder"=>"Functionality Name",
	), NULL );
	
	$labelPrName = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Privilege Name*" );
	$textPrName = new FormField( "text", array(
			"name"=>"privilege_name",
			"id"=>"privilege_name",
			"ng-model"=>"privilege_name",
			"class"=>"form-control",
			"form-name"=>$createPrForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PRIVILEGE_NAME",
			"required"=>"required",
			"placeholder"=>"Privilege Name",
	), NULL );
	
	$labelPrDesc = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Privilege Description*" );
	$textareaPrDesc = new FormField( "textarea", array(
			"name"=>"privilege_description",
			"id"=>"privilege_description",
			"ng-model"=>"privilege_description",
			"class"=>"form-control",
			"form-name"=>$createPrForm->getFormName(),
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
			"form-name"=>$createPrForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_FUNCTIONALITY_DESCRIPTION",
			"required"=>"required",
			"placeholder"=>"Functionality Description",
	), "" );
	
	$labelSelectPlugin = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Plugin*" );
	
	$hiddenPr = new FormField( "hidden", array(
			"name"=>"what_do_you_want",
			"id"=>"what_do_you_want",
			"value"=>"create_privilege",
	), NULL );
	
	$buttonPrSubmit = new FormField( "submit", array(
			"value"=>"",
			"class"=>"btn blue",
			"name"=>"submit_".$createPrForm->getFormName(),
			"id"=>"submit_".$createPrForm->getFormName(),
			"ng-disabled"=>$createPrForm->getFormName().'.$invalid'
	), "Create" );
	
	$buttonPrReset = new FormField( "reset", array(
			"value"=>"",
			"class"=>"btn red",
			"name"=>"reset_".$createPrForm->getFormName(),
			"id"=>"reset_".$createPrForm->getFormName(),
	), "Reset" );
	
	
?>

<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="assets/global/plugins/bootstrap-select/css/bootstrap-select.min.css" />

<!-- BEGIN PAGE HEAD-->
<div class="page-head">
	<!-- BEGIN PAGE TITLE -->
	<div class="page-title">
		<h1>
			Create Privilege Group and Privileges
		</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD-->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">Create Privilege</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered">
	<h3>Create Privilege</h3>
	<p>
		Privilege refers to the permission or accessibility to specific functionality of the system. For every function on the system, a privilege should be created and linked to that function.
		Privileges can be grouped together under a Privilege Group.<br />
		Roles can be created and a combination of different privileges are assigned to different Roles.
	</p>
</div>

<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="createPrivilegePageValidation" ng-controller="createPrivilegePageController" > 
	<div class="row" id="createPrivilegeForm">
    	
    	<div class="col-md-6 col-lg-6">
    		<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-crosshairs"></i> <strong>Create</strong> Privilege Group
					</div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
					
					<form name="<?=$createPGForm->getFormName() ?>" id="<?=$createPGForm->getFormName() ?>" method="POST"
					ng-submit="submitPrivilegeGroup(<?=$createPGForm->getFormName() ?>.$valid, $event )">
						
						<!-- Privilege Group Name -->
				        <div class="form-group">
				        	<?php echo $labelPGName->generateField() ?>
				            <?php echo $textPGName->generateField() ?>
				        </div> 
						<!--/ Privilege Group Name -->
				                    
				        <!-- Privilege Group Sequence -->
						<div class="form-group">
							<?=$labelPGSequence->generateField() ?>
				            <?=$textPGSequence->generateField() ?>
						</div>
						<!--/ Privilege Group Sequence -->
				                    
				        <?=$hiddenPG->generateField() ?>
				        <?=$createPGButton->generateField() ?>
				        <?=$createPGReset->generateField() ?>
				        
			        </form>
			            	
				</div>
			</div>
    		
		</div>
    	
    	<div class="col-md-6 col-lg-6">
    		<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-crosshairs"></i> <strong>Create</strong> Privilege
					</div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
					<div class="row">
            		
            			<form name="<?=$createPrForm->getFormName() ?>" id="<?=$createPrForm->getFormName() ?>" method="POST" 
            			ng-submit="submitForm( <?=$createPrForm->getFormName() ?>.$valid, $event )">
	                	
	                	<!-- Privilege Group Name -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
	                    		<?=$labelPrGroup->generateField() ?>
	            				<select class="form-control" id="privilege_group_id" name="privilege_group_id"  title="Select Privilege Group">
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
		                    	<?=$textFnName->generateField() ?>		
		                		
							</div>
						</div>
						<!--/ Functionality Name -->
	                    
	                    
	                    <!-- Privilege Name -->
	                    <div class="col-lg-6">
							<div class="form-group">
								<?=$labelPrName->generateField() ?>
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
	                    		<select class="form-control" id="plugin_id" name="plugin_id" ng-model="plugin_id">
	                    			<option  value="none" disabled selected>Please choose a Plugin</option>
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
			                    <?=$buttonPrReset->generateField() ?>
			                </center>
                    	</div>
                    </form>
					</div>
				</div>
			</div>
		</div>
	
		
	</div>
	<!--form ends here for privilege group -->
</div>
<!-- END MAIN CONTENT -->

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="assets/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js"></script>
	<script src="assets/plugins/bootstrap-select/js/bootstrap-select.js"></script>
	<script src="js/angular.min.js"></script>
	<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
