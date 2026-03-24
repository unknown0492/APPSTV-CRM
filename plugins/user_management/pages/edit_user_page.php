<?php 
	checkIfLoggedIn();
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$user_id	= $_REQUEST[ "user_id" ];
	if( ( $user_id == "" ) || ( $user_id == NULL ) ){
		echo "<script>alert( 'This page cannot be accessed directly. Click on Edit User button on the View Users Page to edit user information.<br />' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_users_page" );
	}
	
	$sql 		= "SELECT user_id, email, fname, lname, nickname, role_id FROM users WHERE user_id = '$user_id'";
	$result_set	= selectQuery( $sql );
	if ( mysqli_num_rows( $result_set ) <= 0 ){
		echo "<script>alert( 'This page cannot be accessed directly. Click on Edit User button on the View Users Page to edit user information.<br />' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_users_page" );
	}
	$val		= mysqli_fetch_object( $result_set );
	
?>
<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="editUserPageValidation" ng-controller="editUserPageController" >
	<div class="row">
    	<div class="col-md-12 col-lg-6 col-lg-offset-3">
    		<br>
    		<br>
        	<div class="panel panel-default">
				<div class="panel-body ">
	            	<div class="row-fluid">
	            		<form name="form_edit_user" id="form_edit_user" method="POST"
	            			ng-submit="submitForm(form_edit_user.$valid,$event)" novalidate >
		                	<h3><strong>Edit</strong> User</h3>
		                    <br />		                    
		                    <div>
		                    	<label><strong>User ID : </strong></label>
		                    	<span class="label label-success" style="margin-left: 0px;"><?=$val->user_id ?></span>
		                    	<input type="hidden" name="user_id" id="user_id" value="<?=$val->user_id ?>" />
							</div>
							<br />
							
		                    <div>
		                    	<label><strong>Email*</strong></label>
		                    </div>
		                    <div class="input-group">
		                    	<span class="input-group-addon bg-blue">           
		                        	<i class="fa fa-envelope"></i>
								</span>
					            <input type="email" class="form-control" placeholder="Email" id="email" name="email"
					             ng-model="email" ng-pattern="/^[a-z]+[a-z0-9._]+@[a-z]+\.[a-z.]{2,10}$/" value="<?=$val->email ?>" required />
		            		</div>
		            		<p ng-show="form_edit_user.email.required && !form_edit_user.email.required" 
		                		class="help-block" style="color:red;" >Email ID is required</p>
		                	<p class="help-block" ng-show="form_edit_user.email.$error.pattern" style="color:red;">
		                		Invalid email ID</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>First Name</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="First Name" name="fname" 
		                			id="fname" ng-model="fname" ng-pattern="/^[a-zA-Z\s]*$/" value="<?=$val->fname ?>" />
							</div>
		                	<p class="help-block" ng-show="form_edit_user.fname.$error.pattern" style="color:red;">Only alphabets and spaces are allowed</p>
		                    <br>
									                    
		                    <div>
		                    	<label><strong>Last Name</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Last Name" name="lname" 
		                		id="lname" ng-model="lname" ng-pattern="/^[a-zA-Z\s]*$/" value="<?=$val->lname ?>" />
							</div>
							<p class="help-block" ng-show="form_edit_user.lname.$error.pattern" style="color:red;">Only alphabets and spaces are allowed</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Nick Name</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Nick Name" name="nickname" 
		                		id="nickname" ng-model="nickname" ng-pattern="/^[a-zA-Z\s]*$/" value="<?=$val->nickname ?>" />
							</div>
							<p class="help-block" ng-show="form_edit_user.nickname.$error.pattern" style="color:red;">Only alphabets and spaces are allowed</p>
		                    <br>

		                    <div>
		                    	<label><strong>Change Password</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="password" class="form-control" placeholder="Change Password" name="change_password" 
		                		id="change_password" ng-model="change_password" ng-pattern="" ng-minlength="8" ng-maxlength="20" value="" />
							</div>
							<p class="help-block" ng-show="form_edit_user.change_password.$error.pattern" style="color:red;">Only few special characters are allowed</p>
							<p ng-show="form_edit_user.change_password.$error.minlength" class="help-block">Password is too short.</p>
  							<p ng-show="form_edit_user.change_password.$error.maxlength" class="help-block">Password is too long.</p>
		                    <br>

		                    <div>
		                    	<label><strong>Confirm Password</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="password" class="form-control" placeholder="Confirm Password" name="confirm_password" 
		                		id="confirm_password" ng-model="confirm_password" ng-pattern="" ng-minlength="8" ng-maxlength="20" value="" />
							</div>
							<p class="help-block" ng-show="form_edit_user.confirm_password.$error.pattern" style="color:red;">Only few special characters are allowed</p>
							<p ng-show="form_edit_user.confirm_password.$error.minlength" class="help-block">Password is too short.</p>
  							<p ng-show="form_edit_user.confirm_password.$error.maxlength" class="help-block">Password is too long.</p>
		                    <br>
		                
		                    
		                    <div class="col-md-12 input-group">
			                    <label><strong>Select User Role*</strong></label>
		                    	<select class="form-control" id="role_id" name="role_id"  title="Choose one role" required>
		                        	<option value="none">Select a role</option>
		                        	<?php 
		                        	$sql = "Select * FROM roles";
		                        	$result_set = selectQuery( $sql );
		                        	
		                        	while( ( $val1 = mysqli_fetch_object( $result_set ) ) != NULL ){
		                        		if( ( $val1->role_id == roleToRoleId( SESSION_ADMIN ) ) && ( $_SESSION[ SESSION_AUTHORIZATION ] != roleToRoleId( SESSION_ADMIN ) ) )
		                        			continue;
		                        	?>
		                        		<option value="<?=$val1->role_id ?>" <?=($val->role_id==$val1->role_id)?"selected":"" ?>><?=$val1->role_name ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
		                        <p ng-show="form_edit_user.role_id.required && !form_edit_user.role_id.required" 
		                		class="help-block" style="color:red;" >Role is required</p>
							</div>
		                    <br>
		                    <input type="hidden"  value="update_user" name="what_do_you_want" id="what_do_you_want" value="update_user" />
		                    <input type="submit" value="Update" class="btn btn-primary" name="submit_form_edit_user" id="submit_form_edit_user" ng-disabled="form_edit_user.$invalid"  />
		            	</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- END MAIN CONTENT -->

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="js/angular.min.js"></script>
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
