<?php 
	checkIfLoggedIn();	
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$id 		= @$_REQUEST[ 'id' ];
	
	if( ( $id == "" ) ){
		echo "<script>alert( 'This page cannot be accessed directly. Click on edit button on the View Hooks page near the Hook row which you want to edit' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_hooks_page" );
	}
	$sql = "SELECT * FROM hooks WHERE id=$id";
	$result_set = selectQuery( $sql );
	if( mysqli_num_rows( $result_set ) <= 0 ){
		echo "<script>alert( 'Incorrect mapping in the database' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_hooks_page" );
	}
	$val= mysqli_fetch_assoc( $result_set );
?>
<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="editHooksPageValidation" ng-controller="editHooksPageController" >
	<div class="row">
    	<div class="col-md-12 col-lg-6 col-lg-offset-3">
    		<br>
        	<div class="panel panel-default">
				<div class="panel-body ">
	            	<div class="row-fluid">
	            		<form name="form_edit_hooks" id="form_edit_hooks" method="POST" 
	            			ng-submit="submitForm(form_edit_hooks.$valid,$event)" novalidate >
		                	<h3><strong>Edit</strong> Hook</h3>
		                    <br>
		                    <input type="hidden" id="id" name="id" value="<?php echo $val['id']; ?> ">
		                    <div>
		                    	<label><strong>Hook Name*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Hook Name" name="hook_name" 
		                				id="hook_name" ng-model="hook_name" ng-pattern="/^[a-z_]*$/" value="<?php echo $val['hook_name'] ?>" required  />
							</div>
							<p ng-show="form_edit_hooks.hook_name.required && !form_edit_hooks.hook_name.required" 
		                		class="help-block" style="color:red;">Hook Names is required</p>
		                	<p class="help-block" ng-show="form_edit_hooks.hook_name.$error.pattern" style="color:red;" >Only lowercase alphabets and underscores are allowed</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Hook Description*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<textarea class="form-control" cols="75" id="hook_description" name="hook_description" 
		                			placeholder="Hook Description" ng-model="hook_description" ng-pattern="/^[a-zA-Z0-9 /&$#\<\>\s]*$/"><?php echo $val['hook_description']; ?></textarea>
							</div>
							<p ng-show="form_edit_hooks.hook_description.required && !form_edit_hooks.hook_description.required" 
		                		class="help-block" style="color:red;">Hook Description is required</p>
		                	<p class="help-block" ng-show="form_edit_hooks.description.$error.pattern" style="color:red;">Only lowercase alphabets and underscores are allowed</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Hook Content</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                		<textarea class="form-control" cols="75" id="hook_content" name="hook_content" 
		                			placeholder="Hook Content" ng-model="hook_content" ng-pattern=""><?php echo $val['hook_content']; ?></textarea>
							</div>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Hook Content Meta</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Hook Content Meta" name="hook_content_meta" 
		                				id="hook_content_meta" ng-model="hook_content_meta" ng-pattern="/^[a-zA-Z0-9._\s]*$/" value="<?php echo $val['hook_content_meta']; ?> " required  />
							</div>
		                	<p class="help-block" ng-show="form_edit_hooks.hook_content_meta.$error.pattern" style="color:red;" >Only single and double quotes are not allowed</p>
		                    <br>
		                    
		                    <input type="hidden" value="update_hook" name="what_do_you_want" id="what_do_you_want" />
		                    <input type="submit" value="Update" class="btn btn-primary" name="submit_form_edit_hooks" id="submit_form_edit_hooks" ng-disabled="form_edit_hooks.$invalid" />
	                    </form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- END MAIN CONTENT -->

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="assets/plugins/bootstrap-dropdown/bootstrap-hover-dropdown.min.js"></script>
	<script src="assets/plugins/bootstrap-select/bootstrap-select.js"></script>
	<script src="js/angular.min.js"></script>
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
