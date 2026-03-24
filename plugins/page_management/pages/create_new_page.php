<?php 
	checkIfLoggedIn();	
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$createPGForm = new FormHelper( "form_create_page_group" );
	
	$labelPGName = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Page Group Name*" );
	$textPGName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"page_group_name",
			"name"=>"page_group_name",
			"ngValidated"=>"true",
			"ng-model"=>"page_group_name",
			"ng-pattern"=>"VLDTN_PAGE_GROUP_NAME",
			"required"=>"required",
			"placeholder"=>"Page Group Name",
			"form-name"=>$createPGForm->getFormName()
	), NULL );
	
	$labelPGIcon = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Icon" );
	$textPGIcon	= new FormField( "text", array(
			"class"=>"form-control input-medium",
			"id"=>"page_group_icon",
			"name"=>"page_group_icon",
			"ngValidated"=>"true",
			"ng-model"=>"page_group_icon",
			"ng-pattern"=>"VLDTN_ICON",
			"placeholder"=>"Icon",
			"form-name"=>$createPGForm->getFormName()
	), NULL );
	
	
	$labelPGSequence = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Page Group Sequence*" );
	$textPGSequence	= new FormField( "text", array(
			"class"=>"form-control input-medium",
			"id"=>"page_group_sequence",
			"name"=>"page_group_sequence",
			"ngValidated"=>"true",
			"ng-model"=>"page_group_sequence",
			"ng-pattern"=>"VLDTN_DIGITS",
			"required"=>"required",
			"placeholder"=>"Sequence",
			"form-name"=>$createPGForm->getFormName()
	), NULL );
	
	$hiddenPG = new FormField( "hidden", array(
			"name"=>"what_do_you_want",
			"id"=>"what_do_you_want",
			"value"=>"create_page_group"
	), NULL );
	
	$createPGButton = new FormField( "submit", array(
			"value"=>"Create",
			"class"=>"btn btn-primary",
			"name"=>"submit_".$createPGForm->getFormName(),
			"id"=>"submit_".$createPGForm->getFormName(),
			"ng-disabled"=>$createPGForm->getFormName().'.$invalid',
			"data-original-text"=>"Create",
			"data-loading-text"=>"Creating...",
	), "Create" );
	
	$createPGReset = new FormField( "reset", array(
			"value"=>"",
			"class"=>"btn btn-danger",
			"name"=>"reset_".$createPGForm->getFormName(),
			"id"=>"reset_".$createPGForm->getFormName(),
	), "Reset" );
	
	
	
	
	$createPageForm = new FormHelper( "form_create_page" );
	$labelPage = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Page Group*" );
	$labelIsVisible  = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Is Visible?" );
	
	$labelPageName = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Page Name*" );
	$textPageName = new FormField( "text", array(
			"name"=>"page_name",
			"id"=>"page_name",
			"ng-model"=>"page_name",
			"class"=>"form-control",
			"form-name"=>$createPageForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PAGE_NAME",
			"required"=>"required",
			"placeholder"=>"Page Name",
	), NULL );
	
	$labelPageTitle = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Page Title*" );
	$textPageTitle = new FormField( "text", array(
			"name"=>"page_title",
			"id"=>"page_title",
			"ng-model"=>"page_title",
			"class"=>"form-control",
			"form-name"=>$createPageForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PAGE_TITLE",
			"required"=>"required",
			"placeholder"=>"Page Title",
	), NULL );
	
	$labelPageSequence = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Page Sequence*" );
	$textPageSequence	= new FormField( "text", array(
			"class"=>"form-control input-medium",
			"id"=>"page_sequence",
			"name"=>"page_sequence",
			"ngValidated"=>"true",
			"ng-model"=>"page_sequence",
			"ng-pattern"=>"VLDTN_DIGITS",
			"required"=>"required",
			"placeholder"=>"Sequence",
			"form-name"=>$createPageForm->getFormName()
	), NULL );
	
	$labelPageIcon = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Icon" );
	$textPageIcon	= new FormField( "text", array(
			"class"=>"form-control input-medium",
			"id"=>"page_icon",
			"name"=>"page_icon",
			"ngValidated"=>"true",
			"ng-model"=>"page_icon",
			"ng-pattern"=>"VLDTN_ICON",
			"placeholder"=>"Icon",
			"form-name"=>$createPageForm->getFormName()
	), NULL );
	
	$labelFunctionality = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Functionality*" );
	$labelPlugin= new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Plugin*" );
	
	$labelDescription = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Description" );
	$textADescription = new FormField( "textarea", array(
			"name"=>"description",
			"id"=>"description",
			"ng-model"=>"description",
			"class"=>"form-control",
			"form-name"=>$createPageForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PAGE_DESCRIPTION",
			// "required"=>"required",
			"placeholder"=>"Description",
	), NULL );
	
	$labelTags = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Tags" );
	$textATags = new FormField( "textarea", array(
			"name"=>"tags",
			"id"=>"tags",
			"ng-model"=>"tags",
			"class"=>"form-control",
			"form-name"=>$createPageForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PAGE_TAGS",
			//"required"=>"required",
			"placeholder"=>"Tags",
	), NULL );
	
	$labelImage = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Image URL" );
	$textImage = new FormField( "text", array(
			"name"=>"image",
			"id"=>"image",
			"ng-model"=>"image",
			"class"=>"form-control",
			"form-name"=>$createPageForm->getFormName(),
			//"ngValidated"=>"true",
			//"ng-pattern"=>"VLDTN_URL",
			//"required"=>"required",
			"placeholder"=>"Image URL",
	), NULL );
	
	$labelContent = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Content" );
	$textAContent = new FormField( "textarea", array(
			"name"=>"content",
			"id"=>"content",
			"ng-model"=>"content",
			"class"=>"form-control",
			"form-name"=>$createPageForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PAGE_CONTENT",
			//"required"=>"required",
			"placeholder"=>"Content",
	), NULL );
	
	$hiddenPage = new FormField( "hidden", array(
			"name"=>"what_do_you_want",
			"id"=>"what_do_you_want",
			"value"=>"create_page",
	), NULL );
	
	$buttonPageSubmit = new FormField( "submit", array(
			"value"=>"",
			"class"=>"btn blue",
			"name"=>"submit_".$createPageForm->getFormName(),
			"id"=>"submit_".$createPageForm->getFormName(),
			"ng-disabled"=>$createPageForm->getFormName().'.$invalid',
			"data-original-text"=>"Create",
			"data-loading-text"=>"Creating...",
	), "Create" );
	
	$buttonPageReset = new FormField( "reset", array(
			"value"=>"",
			"class"=>"btn red",
			"name"=>"reset_".$createPageForm->getFormName(),
			"id"=>"reset_".$createPageForm->getFormName(),
	), "Reset" );
	
	
?>

<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />




<!-- BEGIN PAGE HEAD-->
<div class="page-head">
	<!-- BEGIN PAGE TITLE -->
	<div class="page-title">
		<h1>
			Create Page Group and Pages
		</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD-->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">Create Page</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered">
	<h3>Create Page</h3>
	<p>
		Page is a visible Entity for Administrator Panel on which certain functionalities can be coded.<br />
		Pages can be grouped together under a Page Group.<br />
	</p>
</div>

<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="pageApp" ng-controller="pageCtrl" > 
	<div class="row">
    	
    	<div class="col-md-6 col-lg-6">
    		<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-crosshairs"></i> <strong>Create</strong> Page Group
					</div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
					
					<form name="<?=$createPGForm->getFormName() ?>" id="<?=$createPGForm->getFormName() ?>" method="POST"
					ng-submit="submitPageGroup(<?=$createPGForm->getFormName() ?>.$valid, $event )">
						
						<!-- Page Group Name -->
				        <div class="form-group">
				        	<?=$labelPGName->generateField() ?>
				            <?=$textPGName->generateField() ?>
				        </div> 
						<!--/ Page Group Name -->
				        
				        <!-- Icon -->
				        <div class="form-group">
				        	<?=$labelPGIcon->generateField() ?>
				            <?=$textPGIcon->generateField() ?>
				        </div> 
						<!--/ Icon -->
				                    
				        <!-- Page Group Sequence -->
						<div class="form-group">
							<?=$labelPGSequence->generateField() ?>
				            <?=$textPGSequence->generateField() ?>
						</div>
						<!--/ Page Group Sequence -->
				                    
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
						<i class="fa fa-crosshairs"></i> <strong>Create</strong> Page
					</div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
					<div class="row">
            		
            			<form name="<?=$createPageForm->getFormName() ?>" id="<?=$createPageForm->getFormName() ?>" method="POST" 
            			ng-submit="submitForm( <?=$createPageForm->getFormName() ?>.$valid, $event )">
	                	
	                	<!-- Page Group Name -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
	                    		<?=$labelPGName->generateField() ?>
	            				<select class="form-control" id="page_group_id" name="page_group_id"  title="Select Page Group*">
		                        	<option value="none" disabled>Select Page Group</option>
		                        	<?php 
		                        	$sql = "Select * FROM page_group";
		                        	$result_set = selectQuery( $sql );
		                        	while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
		                        	?>
		                        		<option value="<?=$val->page_group_id ?>"><?=$val->page_group_name ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
	                    	</div>
	                    </div>
	                    <!--/ Page Group Name -->
	                    
	                    <!-- IS Visible ? -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
	                    		<?=$labelIsVisible->generateField() ?><br />
	                    		<!-- <select class="form-control" >
		                        	<option value="0">No</option>
		                        	<option value="1">Yes</option>
		                        </select> -->
		                        <input id="is_visible" name="is_visible" ng-model="is_visible" 
		                        	   type="checkbox" class="make-switch" checked 
		                        	   data-on-text="Yes" data-off-text="No"
		                        	   data-on-color="success" data-off-color="danger">
	                    	</div>
	                    </div>
	                    <!--/ Is Visible ? -->
	                    
	                    
						<!-- Page Name -->
	                    <div class="col-lg-6">
							<div class="form-group">
								<?=$labelPageName->generateField() ?>
		                    	<?=$textPageName->generateField() ?>		
							</div>
						</div>
						<!--/ Page Name -->
	                    
	                    
	                    <!-- Page Title -->
	                    <div class="col-lg-6">
							<div class="form-group">
								<?=$labelPageTitle->generateField() ?>
		                    	<?=$textPageTitle->generateField() ?>
							</div>
						</div>
						<!--/ Page Title -->
	                    
						
						<!-- Page Sequence -->
	                    <div class="col-lg-6">
							<div class="form-group">
		                    	<?=$labelPageSequence->generateField() ?>
		                		<?=$textPageSequence->generateField() ?>
							</div>
							
						</div>
	                    <!--/ Page Sequence -->
	                    
	                    
	                    <!-- Icon -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
		                    	<?=$labelPageIcon->generateField() ?>
		                		<?=$textPageIcon->generateField() ?>
							</div>
	                    </div>
						<!--/ Icon -->
	                    
	                    <div class="col-lg-6">
		                    <div class="form-group">
		                    	<?=$labelFunctionality->generateField() ?>
		                    	<select class="form-control" id="functionality_id" name="functionality_id" data-live-search="true" title="Choose functionality">
		                    		<option value="none">Select Functionality</option>
		                        	<?php
		                        	$sql 		= "Select * FROM functionalities WHERE is_page = 1";
		                        	$result_set = selectQuery( $sql );
		                        	while( ( $val = mysqli_fetch_assoc( $result_set ) ) != NULL ){
		                        	?>
		                        		<option value="<?=$val[ 'functionality_id' ]; ?>" > <?= $val[ 'functionality_name' ] . " : " .$val[ 'functionality_description' ] ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
		                     </div>
						</div>
	                    
	                    
	                    
	                    
	                    <!-- Description -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
		                    	<?=$labelDescription->generateField() ?>
		                		<?=$textADescription->generateField() ?>
							</div>
	                    </div>
						<!--/ Description -->
	                    
	                    <!-- Tags -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
		                    	<?=$labelTags->generateField() ?>
		                		<?=$textATags->generateField() ?>
							</div>
	                    </div>
						<!--/ Tags -->
						
						
						<!-- Select Plugin -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
	                    		<?=$labelPlugin->generateField() ?>
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
						
						
						<!-- Image -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
		                    	<?=$labelImage->generateField() ?>
		                		<?=$textImage->generateField() ?>
							</div>
	                    </div>
						<!--/ Image -->
						
						<!-- Content -->
	                    <div class="col-lg-6">
	                    	<div class="form-group">
		                    	<?=$labelContent->generateField() ?>
		                		<?=$textAContent->generateField() ?>
							</div>
	                    </div>
						<!--/ Content -->
	                    
						<div class="col-lg-12">
							<center>
			                    <?=$hiddenPage->generateField() ?>
			                    <?=$buttonPageSubmit->generateField() ?>
			                    <?=$buttonPageReset->generateField() ?>
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
    <script src="assets/global/plugins/bootstrap-hover-dropdown/bootstrap-hover-dropdown.min.js"></script>
	<script src="assets/global/plugins/bootstrap-select/js/bootstrap-select.min.js"></script>
	<script src="js/angular.js"></script>
	<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
