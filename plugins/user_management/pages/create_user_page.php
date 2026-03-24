<?php 
	checkIfLoggedIn();
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$formCreateUser = new FormHelper( "form_create_user" );
	
	$labelUserId = new FormField( "label", array(
			"style"=>"font-weight: bold;"
	), "User ID*" );
	
	$textUserID	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"user_id",
			"name"=>"user_id",
			"ngValidated"=>"true",
			"ng-model"=>"user_id",
			"ng-pattern"=>"VLDTN_USER_ID",
			"required"=>"required",
			"placeholder"=>"Type a User ID",
			"ng-blur"=>"isUserIdAvailable()",
			"ng-change"=>"userIdChanged()",
			"form-name"=>$formCreateUser->getFormName()
	), NULL );
	
	$labelPassword = new FormField( "label", array(
			"style"=>"font-weight: bold;"
	), "Password*" );
	
	$password	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"password",
			"name"=>"password",
			"ngValidated"=>"true",
			"ng-model"=>"password",
			"ng-pattern"=>"VLDTN_PASSWORD",
			"required"=>"required",
			"placeholder"=>"Password",
			"ng-init"=>"password='".getRandomString( 10 )."'",
			"value"=>getRandomString( 10 ),
			"form-name"=>$formCreateUser->getFormName()
	), NULL );
	
	
	$labelEmail = new FormField( "label", array(
			"style"=>"font-weight: bold;"
	), "Email*" );
	
	$textEmail	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"email",
			"name"=>"email",
			"ngValidated"=>"true",
			"ng-model"=>"email",
			"ng-pattern"=>"VLDTN_EMAIL",
			"required"=>"required",
			"placeholder"=>"Email ID here",
			"form-name"=>$formCreateUser->getFormName()
	), NULL );
	
	
	$labelFName = new FormField( "label", array(
			"style"=>"font-weight: bold;"
	), "First Name" );
	
	$textFName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"fname",
			"name"=>"fname",
			"ngValidated"=>"true",
			"ng-model"=>"fname",
			"ng-pattern"=>"VLDTN_FIRST_NAME",
			"placeholder"=>"First Name",
			"form-name"=>$formCreateUser->getFormName()
	), NULL );
	
	
	$labelLName = new FormField( "label", array(
			"style"=>"font-weight: bold;"
	), "Last Name" );
	
	$textLName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"lname",
			"name"=>"lname",
			"ngValidated"=>"true",
			"ng-model"=>"lname",
			"ng-pattern"=>"VLDTN_LAST_NAME",
			"placeholder"=>"Last Name",
			"form-name"=>$formCreateUser->getFormName()
	), NULL );
	
	$labelNickName = new FormField( "label", array(
			"style"=>"font-weight: bold;"
	), "Nick Name" );
	
	$textNickName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"nickname",
			"name"=>"nickname",
			"ngValidated"=>"true",
			"ng-model"=>"nickname",
			"ng-pattern"=>"VLDTN_NICK_NAME",
			"placeholder"=>"Nick Name",
			"form-name"=>$formCreateUser->getFormName()
	), NULL );
	
	
	$labelRoleName = new FormField( "label", array(
			"style"=>"font-weight: bold;"
	), "Role Name*" );
	
	
	$hiddenPr = new FormField( "hidden", array(
			"name"=>"what_do_you_want",
			"id"=>"what_do_you_want",
			"value"=>"create_user",
	), NULL );
	
	$submitCreateUser = new FormField( "submit", array(
			"value"=>"",
			"class"=>"btn blue",
			"name"=>"submit_".$formCreateUser->getFormName(),
			"id"=>"submit_".$formCreateUser->getFormName(),
			"ng-disabled"=>$formCreateUser->getFormName().'.$invalid',
			"data-original-text"=>"Create",
			"data-loading-text"=>"Please Wait..."
	), 'Create' );
	
	$buttonPrReset = new FormField( "reset", array(
			"value"=>"",
			"class"=>"btn red",
			"name"=>"reset_".$formCreateUser->getFormName(),
			"id"=>"reset_".$formCreateUser->getFormName(),
	), "Reset" );
	
?>

<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="assets/global/plugins/bootstrap-select/css/bootstrap-select.min.css" />
<!-- <link href="assets/global/plugins/ladda/ladda-themeless.min.css" rel="stylesheet" type="text/css" /> -->

<!-- BEGIN PAGE HEAD-->
<div class="page-head">
	<!-- BEGIN PAGE TITLE -->
	<div class="page-title">
		<h1>Create New User</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD-->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">Create New User</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered">
	<h3>Create a User</h3>
	<p>
		A User is an Integral part of the system. A user can access the functionalities of the system. The system features are inaccessible without the user.
	</p>
</div>

<!-- BEGIN MAIN CONTENT -->

	<div class="row" ng-app="createUserPageValidation" ng-controller="createUserPageController" >
    	<div class="col-md-6">
			<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-user"></i> Choose a Unique User ID
					</div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
				
	            	<form name="<?=$formCreateUser->getFormName() ?>" id="<?=$formCreateUser->getFormName() ?>" method="POST"
	            		ng-submit="submitForm( <?=$formCreateUser->getFormName() ?>.$valid, $event )" >
		                
		                <!-- User ID -->
		                <div class="form-group">
		                	<?=$labelUserId->generateField() ?>
			                <?=$textUserID->generateField() ?>
			                <span id="user_id_message" class="help-block hidden font-green"></span>
		                </div>
		                <!--/ User ID  -->
		                
		                <!-- Password -->
		                <div class="form-group">
		                	<?=$labelPassword->generateField() ?>
			                <?=$password->generateField() ?>
		                </div>
		                <!--/ Password  -->
		                
		                <!-- Email -->
		                <div class="form-group">
		                	<?=$labelEmail->generateField() ?>
			                <?=$textEmail->generateField() ?>
		                </div>
		                <!--/ Email -->
		                
		                <!-- Role Name -->
		                <div class="form-group">
		                	<?=$labelRoleName->generateField() ?>
			                <select class="form-control input-medium" id="role_id" name="role_id"  title="Choose one role" required>
	                        	<option value="none">Select a role</option>
	                        	<?php 
	                        	$sql = "Select * FROM roles";
	                        	$result_set = selectQuery( $sql );
	                        	
	                        	while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
	                        		//if( ( $val->role_id == roleToRoleId( SESSION_ADMIN ) ) && ( $_SESSION[ SESSION_AUTHORIZATION ] != roleToRoleId( SESSION_ADMIN ) ) )
	                        		if( onlyAdminCan( $_SESSION[ SESSION_USER_ID ], false ) )
	                        			continue;
	                        	?>
	                        		<option value="<?=$val->role_id ?>"><?=$val->role_name ?></option>	
	                        	<?php

	                        	}
	                        	?>
	                        </select>
	                        
		                </div>
		                <!--/ Role Name -->
		                    
		            	
			            <?=$hiddenPr->generateField() ?>
			            <?=$submitCreateUser->generateField() ?>
			            <?=$buttonPrReset->generateField() ?>
			            
		            	
				</div>
				
			</div>
		</div>
		
		<div class="col-md-6">
			<div class="portlet box green">
				<div class="portlet-title">
					<div class="caption">
						<i class="fa fa-user"></i> Personal Information (Optional)
					</div>
				</div>
				<div class="portlet-body" style="padding: 30px;">
				
	            	    <!-- First Name -->
		                <div class="form-group">
		                	<?=$labelFName->generateField() ?>
			                <?=$textFName->generateField() ?>
		                </div>
		                <!--/ First Name -->
		                
		                <!-- Last Name -->
		                <div class="form-group">
		                	<?=$labelLName->generateField() ?>
			                <?=$textLName->generateField() ?>
		                </div>
		                <!--/ Last Name -->
		                
		                <!-- NickName -->
		                <div class="form-group">
		                	<?=$labelNickName->generateField() ?>
			                <?=$textNickName->generateField() ?>
		                </div>
		                <!--/ NickName -->

		            </form>
				</div>
				
			</div>
		</div>
		
	</div>

<!-- END MAIN CONTENT -->

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <!-- <script src="assets/global/plugins/ladda/spin.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/ladda/ladda.min.js" type="text/javascript"></script>
    <script src="assets/pages/scripts/ui-buttons.min.js" type="text/javascript"></script> -->
    <script src="js/angular.js"></script>
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
