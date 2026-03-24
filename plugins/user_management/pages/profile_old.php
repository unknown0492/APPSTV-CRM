<?php 
	checkIfLoggedIn();
	checkPrivilegeForPage( "update_profile" );
    //print_r($_SESSION);
    $sql=" SELECT * FROM users WHERE user_id='".$_SESSION['user_id']."' ";
    $result_set = selectQuery( $sql );
    if( mysqli_num_rows( $result_set )>0 ){
        $val = mysqli_fetch_assoc( $result_set );
    }
?>
<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="createProfileValidation" ng-controller="createProfileController" >
	<div class="row">
    	<div class="col-md-12">
    		<?php 
    		if( havePrivilege( "edit_user_profile", false) ){
    		?>
        	<div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><strong>Edit Profile</strong> Details Here</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <form method="POST" id="form_edit_user_profile" name="form_edit_user_profile" ng-submit="submitProfileForm(form_edit_user_profile.$valid,$event)" novalidate> 
	                                
                                    <div class="form-group">
	                                    <label class="form-label"><strong>First Name</strong></label>
	                                    <div class="controls">
	                                        <input type="text" id="first_name" name="first_name" ng-model="first_name" value="<?= $val['fname']; ?>" ng-pattern="/^[a-zA-Z_]*$/" placeholder="First Name" class="form-control">
	                                    </div>
                                        <p class="help-block" ng-show="form_edit_user_profile.first_name.$error.pattern" style="color:red;" >Only alphabets and underscores are allowed</p>
                                        <br>
	                                </div>
	                                
	                                <div class="form-group">
	                                    <label class="form-label"><strong>Last Name</strong></label>
	                                    <div class="controls">
	                                        <input type="text" id="last_name" name="last_name" ng-model="last_name" value="<?= $val['lname']; ?>" ng-pattern="/^[a-zA-Z_]*$/" placeholder="Last Name" class="form-control">
	                                    </div>
                                        <p class="help-block" ng-show="form_edit_user_profile.last_name.$error.pattern" style="color:red;" >Only alphabets and underscores are allowed</p>
                                        <br>
	                                </div>

	                                <div class="form-group">
	                                    <label class="form-label"><strong>Nick Name</strong></label>
	                                    <div class="controls">
	                                        <input type="text" id="nick_name" name="nick_name" ng-model="nick_name" value="<?= $val['nickname']; ?>" ng-pattern="/^[a-zA-Z_]*$/" placeholder="Nick Name" class="form-control">
	                                    </div>
                                         <p class="help-block" ng-show="form_edit_user_profile.nick_name.$error.pattern" style="color:red;" >Only alphabets and underscores are allowed</p>
                                        <br>
	                                </div>

	                                <div class="form-group">
	                                    <label class="form-label"><strong>Email</strong></label>
	                                    <div class="controls">
	                                        <input type="email" id="user_email" name="user_email" ng-model="user_email" value="<?= $val['email']; ?>" ng-pattern="/^[a-z]+[a-z0-9._]+@[a-z]+\.[a-z.]{2,10}$/" placeholder="Email" class="form-control" required>
	                                    </div>
                                        <p ng-show="form_edit_user_profile.user_email.required && !form_edit_user_profile.user_email.required" 
                                            class="help-block" style="color:red;">Email is required</p>
                                        <p class="help-block" ng-show="form_edit_user_profile.user_email.$error.pattern" style="color:red;" >Invalid Email Address</p>
                                        <br>
	                                </div>

	                                <div class="form-group">
	                                    <label class="form-label"><strong>Security Question</strong></label>
	                                    <div class="controls">
	                                        <input type="text" id="security_question" name="security_question" ng-model="security_question" value="<?= $val['security_question']; ?>" ng-pattern="/^[A-Za-z0-9_ ]*$/" placeholder="Security Question" class="form-control">
	                                    </div>
                                        <p class="help-block" ng-show="form_edit_user_profile.security_question.$error.pattern" style="color:red;" >Only alphabets,numbers and underscores are allowed</p>
                                        <br>
	                                </div>

	                                <div class="form-group">
	                                    <label class="form-label"><strong>Security Answer</strong></label>
	                                    <div class="controls">
	                                        <input type="text" id="security_answer" name="security_answer" ng-model="security_answer" value="<?= $val['security_answer']; ?>" ng-pattern="/^[a-zA-Z0-9_ ]*$/" placeholder="Security Answer" class="form-control">
	                                    </div>
                                        <p class="help-block" ng-show="form_edit_user_profile.security_answer.$error.pattern" style="color:red;" >Only alphabets,numbers and underscores are allowed</p>
                                        <br>
	                                </div>

	                                <div align="center">
	                                     <input type="hidden" id="user_id" name="user_id" value="<?= $_SESSION['user_id'] ?>" placeholder="User Id" class="form-control" required readonly>
	                                     <input type="hidden" id="what_do_you_want" name="what_do_you_want" value="edit_user_profile" placeholder="Edit User Profile" class="form-control" required readonly>
	                                     <input type="submit" id="submit_user_profile" name="submit_user_profile" ng-disabled="form_edit_user_profile.$invalid" class="btn btn-primary" value="Update">
	                                </div>
                            	</form>
                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			<?php 
    		}
			if( havePrivilege( "edit_user_password", false) ){
    		?>
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h3 class="panel-title"><strong>Change Profile</strong> Password Here</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <form method="POST" id="form_edit_user_password" name="form_edit_user_password" ng-submit="submitPasswordForm(form_edit_user_password.$valid,$event)" novalidate>
                                
                                <div class="form-group">
                                    <label class="form-label"><strong>Old Password</strong></label>
                                    <div class="controls">
                                        <input type="password" id="user_password_old" name="user_password_old" ng-model="user_password_old" ng-pattern="" placeholder="Old Password" class="form-control" autocomplete="off" required>
                                    </div>
                                    <p ng-show="form_edit_user_password.user_password_old.required && !form_edit_user_password.user_password_old.required" 
                                            class="help-block" style="color:red;">Old Password is required</p>
                                    <p class="help-block" ng-show="form_edit_user_password.user_password_old.$error.pattern" style="color:red;" >Invalid Password</p>
                                    <br>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label"><strong>New Password</strong></label>
                                    <div class="controls">
                                        <input type="password" id="user_password_new" name="user_password_new" ng-model="user_password_new" ng-pattern="" placeholder="New Password" class="form-control" autocomplete="off" required>
                                    </div>
                                    <p ng-show="form_edit_user_password.user_password_new.required && !form_edit_user_password.user_password_new.required" 
                                            class="help-block" style="color:red;">New Password is required</p>
                                    <p class="help-block" ng-show="form_edit_user_password.user_password_new.$error.pattern" style="color:red;" >Invalid Password</p>
                                    <br>
                                </div>

                                <div class="form-group">
                                    <label class="form-label"><strong>Repeat New Password</strong></label>
                                    <div class="controls">
                                        <input type="password" id="user_password_repeat" name="user_password_repeat" ng-model="user_password_repeat" ng-pattern="" placeholder="Repeat New Password" class="form-control" autocomplete="off" required>
                                    </div>
                                    <p ng-show="form_edit_user_password.user_password_repeat.required && !form_edit_user_password.user_password_repeat.required" 
                                            class="help-block" style="color:red;">Repeat New Password is required</p>
                                    <p class="help-block" ng-show="form_edit_user_password.user_password_repeat.$error.pattern" style="color:red;" >Invalid Password</p>
                                    <br>
                                </div>

                                <div align="center">
                                     <input type="hidden" id="user_id" name="user_id" value="<?= $_SESSION['user_id'] ?>" placeholder="User Id" class="form-control" required readonly>
                                     <input type="hidden" id="what_do_you_want" name="what_do_you_want" value="edit_user_password" placeholder="Edit User Password" class="form-control" required>
                                    <input type="submit" id="submit_edit_user_password" name="submit_edit_user_password" ng-disabled="form_edit_user_password.$invalid" value="Submit" class="btn btn-primary" >
                                </div>
                            </form>
                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>
			<?php 
			}
			?>
		</div>
	</div>
</div>
<!-- END MAIN CONTENT -->

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="js/angular.min.js"></script>
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
