<?php 
	checkIfLoggedIn();
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$pgDeleteButton = new FormField( "button", array(
			"style"=>"margin: 10px 0px 10px 0px; margin-left: 0px;",
			"type"=>"button",
			"class"=>"btn red",
			"id"=>"delete_page_group_button",
			"onclick"=>"deletePageGroup( this );",
			"data-original-text"=>"Delete Group",
			"data-loading-text"=>"Deleting...",
	), "<i class=\"fa fa-trash-o\"></i> Delete Group" );
	
	
	
	// Edit Page Modal Form Fields
	
	$editPageForm = new FormHelper( "edit_create_page_group" );
	
	$labelPGName = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Page Group Name*" );
	$labelIsVisible  = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Is Visible?" );
	
	$labelPageName  = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Page Name*" );
	$textPageName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"page_name",
			"name"=>"page_name",
			"ngValidated"=>"true",
			"ng-model"=>"page_name",
			"ng-pattern"=>"VLDTN_PAGE_NAME",
			"required"=>"required",
			"placeholder"=>"Page Name",
			"form-name"=>$editPageForm->getFormName()
	), NULL );
	
	$hiddenPageID = new FormField( "hidden", array( 
			"name" => "page_id",
			"id" => "page_id",
			"ng-model" => "page_id"
	), NULL );
	
	$labelPageTitle = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Page Title*" );
	$textPageTitle = new FormField( "text", array(
			"name"=>"page_title",
			"id"=>"page_title",
			"ng-model"=>"page_title",
			"class"=>"form-control",
			"form-name"=>$editPageForm->getFormName(),
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
			"form-name"=>$editPageForm->getFormName()
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
			"form-name"=>$editPageForm->getFormName()
	), NULL );
	
	$labelFunctionality = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Functionality*" );
	$labelPlugin= new FormField( "label", array( "style"=>"font-weight: bold;" ), "Select Plugin*" );
	
	$labelDescription = new FormField( "label", array( "style"=>"font-weight: bold;" ), "Description" );
	$textADescription = new FormField( "textarea", array(
			"name"=>"description",
			"id"=>"description",
			"ng-model"=>"description",
			"class"=>"form-control",
			"form-name"=>$editPageForm->getFormName(),
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
			"form-name"=>$editPageForm->getFormName(),
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
			"form-name"=>$editPageForm->getFormName(),
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
			"form-name"=>$editPageForm->getFormName(),
			"ngValidated"=>"true",
			"ng-pattern"=>"VLDTN_PAGE_CONTENT",
			//"required"=>"required",
			"placeholder"=>"Content",
	), NULL );
	
	$hiddenPage = new FormField( "hidden", array(
			"name"=>"what_do_you_want",
			"id"=>"what_do_you_want",
			"value"=>"update_page",
	), NULL );
	
	$buttonPageSubmit = new FormField( "submit", array(
			"value"=>"",
			"class"=>"btn blue",
			"name"=>"submit_".$editPageForm->getFormName(),
			"id"=>"submit_".$editPageForm->getFormName(),
			"ng-disabled"=>$editPageForm->getFormName().'.$invalid',
			"data-original-text"=>"Update",
			"data-loading-text"=>"Updating...",
	), "Update" );
	
	$buttonPageReset = new FormField( "reset", array(
			"value"=>"",
			"class"=>"btn red",
			"name"=>"reset_".$editPageForm->getFormName(),
			"id"=>"reset_".$editPageForm->getFormName(),
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
			View/Edit Pages
		</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD -->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">View/Edit Pages</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered">
	<h3>Page Management</h3>
	<p>
		View or Edit/Delete the pages that exist in the system.
	</p>
</div>
<div ng-app="edit_page_app" ng-controller="edit_page_ctrl">
	<div class="row" >
		<div class="col-md-12">
			<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-crosshairs"></i> View Pages for Selected Group
					</div>
				</div>
				<div class="portlet-body">
					<div class="row" style="padding: 10px;">
					<?php 
		            	$sql = "Select * FROM page_group ORDER BY page_group_sequence";
		                $result_set0 = selectQuery( $sql );
		                $result_set_temp = $result_set0;
	                ?>
		                <div class="col-lg-4 col-md-4 col-sm-8 col-xs-12">
		                	<label><strong>Select Page Group : </strong></label><br/>
		                    <select class="form-control input-medium" 
		                        data-live-search="true" 
		                        name="selected_page_group_id" 
		                        id="selected_page_group_id" 
		                        ng-model="selected_page_group_id" ng-change="changePageGroup(this.id);" >
		                        <!-- <option value="0">All Other Privileges</option> -->
		                        <?php 
		                        	$i = 1;
		                            while( ( $val0 = mysqli_fetch_object( $result_set0 ) ) != NULL ){
		                            ?>
		                            	<option value="<?=$val0->page_group_id ?>" <?=(@$_REQUEST[ 'selected_page_group_id' ]==$val0->page_group_id)?"selected":"" ?> <?=(!isset($_REQUEST[ 'selected_page_group_id' ]) && $i==1)?"selected":"" ?>><?=$val0->page_group_name ?></option>
		                            <?php
		                            	$i++;
		                            }
		                            ?>
		                    </select>
		                    
		                    <?=$pgDeleteButton->generateField() ?>
		                    
		                    <br />
		                </div>
		                <div class="col-md-12 col-sm-12 col-xs-12 table-responsive">
		                	<table id="view-system-page-table" class="table table-striped table-bordered table-hover order-column">
		                    	<thead>
		                        	<tr>
										<th width="80px;">Sr. No.</th>
										<th><strong>Page Name</strong></th>
										<th><strong>Sequence</strong></th>
										<th width="80px;"><strong>Icon</strong></th>
										<th><strong>Is Visible</strong></th>
										<th><strong>Functionality Name</strong></th>
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
	</div>
	
	<div class="modal fade" id="edit-page-modal" tabindex="-1" role="basic" aria-hidden="true" >
        <div class="modal-dialog">
        	<div class="modal-content">
            	<div class="modal-header bg-blue bg-font-blue">
                	<button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                    <h4 class="modal-title"><strong>Edit</strong> Page</h4>
                </div>
                <div class="modal-body">
                	<?=getLoadingHTML( "loading_page_info", "Loading Content...", false, "center", "50" ) ?>
                    <div class="row content form" style="margin: 10px;">
				    	<!-- BEGIN FORM-->
						<form class="" name="<?=$editPageForm->getFormName() ?>" id="<?=$editPageForm->getFormName() ?>" method="POST" 
	            			ng-submit="updatePage( <?=$editPageForm->getFormName() ?>, $event )" novalidate >
		                	
		                	<?=$hiddenPageID->generateField() ?>
		                	
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
		                    
		                    <!-- Is Visible -->
		                    <div class="col-lg-6">
		                    	<div class="form-group">
		                    		<?=$labelIsVisible->generateField() ?>
		                    		<br />
		                    		<input id="is_visible" name="is_visible" ng-model="is_visible" 
		                        	   type="checkbox" class="make-switch" checked 
		                        	   data-on-text="Yes" data-off-text="No"
		                        	   data-on-color="success" data-off-color="danger">
		                    	</div>
		                    </div>
		                    <!--/ Is Visible -->
		                    
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
		                    
		                    <!-- Page Icon -->
		                    <div class="col-lg-6">
		                    	<div class="form-group">
		                    		<?=$labelPageIcon->generateField() ?>
		                    		<?=$textPageIcon->generateField() ?>
		                    	</div>
		                    </div>
		                    <!--/ Page Icon -->
		                    
		                    <!-- Functionality -->
		                    <div class="col-lg-6">
		                    	<div class="form-group">
		                    		<?=$labelFunctionality->generateField() ?>
		                    		<select class="bs-select form-control" id="functionality_id" name="functionality_id" data-live-search="true" title="Choose functionality">
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
		                    <!--/ Functionality -->
		                    
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
				                </center>
	                    	</div>
		                    
	                    </form>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
	<div class="md-overlay"></div>
	</div>
	
	
</div>
        

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="assets/global/plugins/bootstrap-select/js/bootstrap-select.min.js"></script>
    <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
    <script src="js/angular.js"></script>
    <!-- END PAGE LEVEL SCRIPTS -->
    
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
