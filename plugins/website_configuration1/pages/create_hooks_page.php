<?php 
	checkIfLoggedIn();	
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
?>
<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="createHooksPageValidation" ng-controller="createHooksPageController" >    
	<div class="row">
    	<div class="col-md-12 col-lg-6 col-lg-offset-3">
    		<br>
    		<br>
        	<div class="panel panel-default">
				<div class="panel-body ">
	            	<div class="row-fluid">
	            		<form name="form_create_hook" id="form_create_hook" method="POST" 
	            			ng-submit="submitForm(form_create_hook.$valid,$event)" novalidate >
		                	<h3><strong>Create</strong> New Hook</h3>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Hook Name*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Hook Name" name="hook_name" 
		                				id="hook_name" ng-model="hook_name" ng-pattern="/^[a-z_]*$/" required  />
							</div>
							<p ng-show="form_create_hook.hook_name.required && !form_create_hook.hook_name.required" 
		                		class="help-block" style="color:red;">Hook Names is required</p>
		                	<p class="help-block" ng-show="form_create_hook.hook_name.$error.pattern" style="color:red;" >Only lowercase alphabets and underscores are allowed</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Hook Description*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<textarea class="form-control" cols="75" id="hook_description" name="hook_description" 
		                			placeholder="Hook Description" ng-model="hook_description" ng-pattern="/^[a-zA-Z0-9\s]*$/"  required>
		                		</textarea>
							</div>
							<p ng-show="form_create_hook.hook_description.required && !form_create_hook.hook_description.required" 
		                		class="help-block" style="color:red;">Hook Description is required</p>
		                	<p class="help-block" ng-show="form_create_hook.description.$error.pattern" style="color:red;">Only lowercase alphabets and underscores are allowed</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Hook Content</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                		<textarea class="form-control" cols="75" id="hook_content" name="hook_content" 
		                			placeholder="Hook Content" ng-model="hook_content" ng-pattern="" >
		                		</textarea>
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
		                				id="hook_content_meta" ng-model="hook_content_meta" ng-pattern="/^[a-z_]*$/" required  />
							</div>
		                	<p class="help-block" ng-show="form_create_hook.hook_content_meta.$error.pattern" style="color:red;" >Only single and double quotes are not allowed</p>
		                    <br>
		                    
		                    <input type="hidden" value="create_hooks_page" name="what_do_you_want" id="what_do_you_want" />
		                    <input type="submit" value="Create" class="btn btn-primary" name="submit_form_create_hook" id="submit_form_create_hook" ng-disabled="form_create_hook.$invalid" />
		                    <input type="reset" value="Reset" class="btn btn-danger" name="reset_form_create_hook" id="reset_form_create_hook" />
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
