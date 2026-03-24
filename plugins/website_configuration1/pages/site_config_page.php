<?php 
	checkIfLoggedIn();	
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$site_config = getSiteConfig();
	
	
	$formGeneralConfig = new FormHelper( "form_general_config" );
	$formSiteImages = new FormHelper( "form_site_images" );
	$formExtraOptions = new FormHelper( "form_extra_options" );
	
	// Form Labels
	$lblSiteName = new FormField( "label", array( "style" => "font-weight: bold;" ), "Site Name*" );
	$lblTagline = new FormField( "label", array( "style" => "font-weight: bold;" ), "Site Tagline*" );
	$lblRootPath = new FormField( "label", array( "style" => "font-weight: bold;" ), "Site Root Path*" );
	$lblProtocol = new FormField( "label", array( "style" => "font-weight: bold;" ), "Protocol*" );
	$lblLoginImage = new FormField( "label", array( "style" => "font-weight: bold;" ), "Login Page Logo*" );
	$lblAdminImage = new FormField( "label", array( "style" => "font-weight: bold;" ), "Admin Page Logo*" );
	$lblAllowRegistrations = new FormField( "label", array( "style" => "font-weight: bold;" ), "Allow Registrations : " );
	$lblAllowForgotPassword = new FormField( "label", array( "style" => "font-weight: bold;" ), "Allow Forgot Password : " );
	$lblAllowEmailCredentials = new FormField( "label", array( "style" => "font-weight: bold;" ), "Allow Email Credentials : " );
	
	// Form Text Fields
	$txtSiteName = new FormField( "text", 
			array( "ngValidated" => "true",
					"name"=>"site_name",
					"id"=>"site_name",
					"ng-model"=>"site_name",
					"form-name"=>$formGeneralConfig->getFormName(),
					"class"=>"form-control",
					"required"=>"required",
					"ng-pattern"=>"VLDTN_SITE_NAME",
					"placeholder"=>"Site Name (Required)",
					"value"=>$site_config->site_name
			)
			, NULL );
	
	$txtSiteTagline = new FormField( "text",
			array( "ngValidated" => "true",
					"name"=>"site_tagline",
					"id"=>"site_tagline",
					"ng-model"=>"site_tagline",
					"form-name"=>$formGeneralConfig->getFormName(),
					"class"=>"form-control",
					"required"=>"required",
					"ng-pattern"=>"VLDTN_SITE_TAGLINE",
					"placeholder"=>"Site Tagline (Required)",
					"value"=>$site_config->site_tagline
			)
			, NULL );
	
	$txtSiteRootPath = new FormField( "text",
			array( "ngValidated" => "false",
					"name"=>"site_root_path",
					"id"=>"site_root_path",
					"ng-model"=>"site_root_path",
					"form-name"=>$formGeneralConfig->getFormName(),
					"class"=>"form-control",
					"required"=>"required",
					"placeholder"=>"Site Root Path (Required)",
					"value"=>$site_config->domain_name
			)
			, NULL );
	
	// Buttons
	$submitSiteGeneralConfig = new FormField( "submit", array(
			"name"=>"submit_" . $formGeneralConfig->getFormName(),
			"id"=>"submit_" . $formGeneralConfig->getFormName(),
			"class"=>"btn btn-lg green",
			"data-original-text"=>"Update",
			"data-loading-text"=>"Updating...",
			"ng-disabled"=>$formGeneralConfig->getFormName().'.$invalid'
	), "Update" );
	
	// Form Hidden Fields
	$hiddenWhatDoYouWantExtraOptions = new FormField( "hidden", array(
			"name" => "what_do_you_want",
			"id" => "what_do_you_want",
			"value" => "update_extra_options"
	), NULL );
	
?>

<link rel="stylesheet" href="plugins/<?=$plugin_name ?>/css/<?=basename( __FILE__, ".php" ) ?>.css" />
<link rel="stylesheet" href="css/sc_ajax_file_upload.css" />

<!-- BEGIN PAGE HEAD-->
<div class="page-head">
	<!-- BEGIN PAGE TITLE -->
	<div class="page-title">
		<h1>
			Site Configuration
		</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD-->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">Site Configuration</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered">
	<h3>Site Configuration</h3>
	<p>
		Configure the basic paramters of the site here.
	</p>
</div>

<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="siteConfigApp" ng-controller="siteConfigCtrl" > 
	<div class="row" id="">
    	
    	
    	<div class="col-md-6 col-lg-6">
    	
    		<!-- General Configuration -->
    		<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-crosshairs"></i> <strong>General</strong> Configuration 
					</div>
					<div class="tools">
                    	<a href="" class="collapse"> </a>
                    </div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
					
					<form name="<?=$formGeneralConfig->getFormName() ?>" id="<?=$formGeneralConfig->getFormName() ?>" method="POST"
					ng-submit="saveSiteConfig( <?=$formGeneralConfig->getFormName() ?>.$valid, $event )">
						
						<!-- Site Name -->
				        <div class="form-group">
				        	<?=$lblSiteName->generateField() ?>
				        	<?=$txtSiteName->generateField() ?>
				        </div> 
						<!--/ Site Name -->
				                  
				        <!-- Site Tagline -->
				        <div class="form-group">
				        	<?=$lblTagline->generateField() ?>
				        	<?=$txtSiteTagline->generateField() ?>
				        </div> 
						<!--/ Site Tagline -->  
				                    
				        <!-- Site Root Path -->
				        <div class="form-group">
				        	<?=$lblRootPath->generateField() ?>
				        	<?=$txtSiteRootPath->generateField() ?>
				        </div> 
						<!--/ Site Root Path -->
						
						<!-- Protocol -->
				        <div class="form-group">
				        	<?=$lblProtocol->generateField() ?><br />
				        	<div class="btn-group btn-group-circle" data-toggle="buttons">
								<label class="btn blue <?=($site_config->protocol=="http"?"active":"") ?>">
                                	<input type="radio" class="toggle protocol" name="protocol" value="http" <?=($site_config->protocol=="http"?"checked":"") ?>> HTTP
                               	</label>
                                <label class="btn blue <?=($site_config->protocol=="https"?"active":"") ?>">
                                	<input type="radio" class="toggle protocol" name="protocol" value="https" <?=($site_config->protocol=="https"?"checked":"") ?>> HTTPS (SSL)
                                </label>
                            </div>
				        </div> 
						<!--/ Protocol -->
				        
				        <div class="form-group pull-right">     
				        	<?=$submitSiteGeneralConfig->generateField() ?>
				        </div>
				        <br /><br />
			        </form>      	
				</div>
			</div>
			<!--/ General Configuration -->
			
			
			<!-- Extra Options -->
			<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-crosshairs"></i> <strong>Extra</strong> Options 
					</div>
					<div class="tools">
                    	<a href="" class="collapse"> </a>
                    </div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
					
					<form name="<?=$formExtraOptions->getFormName() ?>" id="<?=$formExtraOptions->getFormName() ?>" method="POST">
						
						<div class="form-group">
							<div class="row">
								<div class="col-md-6">
									<?=$lblAllowRegistrations->generateField() ?>
								</div>
								<div class="col-md-6">
									<input type="checkbox" id="abc" class="extra-option-switch make-switch" value="is_registration_open" <?=($site_config->is_registration_open=="1")?"checked":"" ?> data-on-color="primary" data-off-color="danger" data-size="small">
								</div>
							</div>
						</div>
						
						<div class="form-group">
							<div class="row">
								<div class="col-md-6">
									<?=$lblAllowForgotPassword->generateField() ?>
								</div>
								<div class="col-md-6">
									<input type="checkbox" class="extra-option-switch make-switch" value="allow_forgot_password" <?=($site_config->allow_forgot_password=="1")?"checked":"" ?>  data-on-color="primary" data-off-color="danger" data-size="small">
								</div>
							</div>
						</div>
						
						<div class="form-group">
							<div class="row">
								<div class="col-md-6">
									<?=$lblAllowEmailCredentials->generateField() ?>
								</div>
								<div class="col-md-6">
									<input type="checkbox" class="extra-option-switch make-switch" value="allow_email_credentials" <?=($site_config->allow_email_credentials=="1")?"checked":"" ?>  data-on-color="primary" data-off-color="danger" data-size="small">
								</div>
							</div>
						</div>

						<?=$hiddenWhatDoYouWantExtraOptions->generateField() ?>
				        
			        </form>
			            	
				</div>
			</div>
			<!--/ Extra Options -->
		
		
		</div>
		
    	
    	<!-- Site Image Configurations -->
    	<div class="col-md-6 col-lg-6">
    		<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-crosshairs"></i> <strong>Site</strong> Image Configuration 
					</div>
					<div class="tools">
                    	<a href="" class="collapse"> </a>
                    </div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
					<div class="row">
						<div class="col-md-6 col-lg-6">
							<div class="form-group">
								<?=$lblLoginImage->generateField() ?><br />
								<img class="img-login-page-logo" src="<?=$site_config->login_page_logo ?>" />
								
								<div id="login_page_logo"></div>
		
					        </div>
						</div>
						<div class="col-md-6 col-lg-6">
							<div class="form-group">
					        	<?=$lblAdminImage->generateField() ?><br />
								<img class="img-admin-page-logo" src="<?=$site_config->admin_page_logo ?>" />
								
								<div id="admin_page_logo"></div>
								
							</div>
					        
						</div>
			        </div>
			        
			            	
				</div>
			</div>
    		
		</div>
    	<!--/ Site Image Configurations -->
    	
    	
    	
    	
    	<div class="col-md-6 col-lg-6">
    		
    		
		</div>
		<!--/ Extra Options -->
	
		
	</div>
	<!--form ends here for privilege group -->
</div>
<!-- END MAIN CONTENT -->

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
	<script src="js/sc_ajax_file_upload.js"></script>
	<script src="js/angular.js"></script>
	<script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
