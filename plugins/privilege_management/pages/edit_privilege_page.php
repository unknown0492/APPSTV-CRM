<?php 
	checkIfLoggedIn();	
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$privilege_id 		= @$_REQUEST[ 'privilege_id' ];
	$privilege_group_id = @$_REQUEST[ 'privilege_group_id' ];
	
	if( ( $privilege_id == "" ) ){
		echo "<script>alert( 'This page cannot be accessed directly. Click on edit button on the View Privileges page near the privilege row which you want to edit' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_privileges_page" );
	}
	
	// Get the privilege_group_id
	$sql = "Select privilege_group_id FROM privileges_groups WHERE privilege_id = $privilege_id";
	$result_set = selectQuery( $sql );
	if( mysqli_num_rows( $result_set ) <= 0 ){
		echo "<script>alert( 'Invalid Privilege ID or Group ID' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_privileges_page" );
	}
	$val = mysqli_fetch_object( $result_set );
	$privilege_group_id = $val->privilege_group_id;
	
	$sql = "SELECT f.functionality_name,f.functionality_description,f.is_page,p.privilege_name,p.privilege_description,p.privilege_id,f.functionality_id,pg.privilege_group_id, pg1.privilege_group_name, pg1.privilege_group_sequence from functionalities f,privileges p,privileges_functionalities pf, privileges_groups pg, privilege_group pg1 where p.privilege_id = pf.privilege_id and f.functionality_id = pf.functionality_id and pg.privilege_id = p.privilege_id and pg.privilege_group_id = $privilege_group_id and pg.privilege_id = $privilege_id and pg.privilege_group_id = pg1.privilege_group_id";
	$result_set = selectQuery( $sql );
	if( mysqli_num_rows( $result_set ) <= 0 ){
		echo "<script>alert( 'Incorrect mapping in the database' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_privileges_page" );
	}
	$val_g = mysqli_fetch_object( $result_set );
	
?>
<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="editPrivilegePageValidation" ng-controller="editPrivilegePageController" >
		<div class="row">
			<div class="col-md-12 col-lg-6 col-lg-offset-3">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Edit <strong>Privilege/Group</strong></h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                	<button type="button" class="btn btn-lg btn-block btn-primary" name="editPrivilegeGroup" id="editPrivilegeGroup" ng-click="editPrivilegeGroup()">
                                		<i class="fa fa-facebook pull-left"></i> Edit Privilege Group
                                	</button>
                            	</div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <button type="button" class="btn btn-lg btn-block btn-primary" name="editPrivilege" id="editPrivilege" ng-click="editPrivilege()">
                                    	<i class="fa fa-facebook pull-left"></i> Edit Privilege
                                   	</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
         	</div>      
                
	
	<div class="row hidden" id="editPrivilegeForm">
    	<div class="col-md-12 col-lg-6 col-lg-offset-3">
    		<br>
    		<br>
        	<div class="panel panel-default">
				<div class="panel-body ">
	            	<div class="row-fluid">
	            		<form name="form_edit_privilege" id="form_edit_privilege" method="POST" 
	            			ng-submit="submitForm(form_edit_privilege.$valid,$event)" novalidate >
		                	<h3><strong>Edit</strong> Privilege</h3>
		                    <br>
		                    
		                    <div class="col-md-12 input-group">
			                    <label><strong>Select Privilege Group*</strong></label>
		                    	<select class="form-control" id="privilege_group_id" name="privilege_group_id"  title="Select Privilege Group" required>
		                        	<option value="">Choose a role</option>
		                        	<?php 
		                        	$sql = "Select * FROM privilege_group";
		                        	$result_set = selectQuery( $sql );
		                        	while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
		                        	?>
		                        		<option value="<?=$val->privilege_group_id ?>" <?=($val->privilege_group_id == $val_g->privilege_group_id)?"selected":"" ?>><?=$val->privilege_group_name ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
		                        <p ng-show="form_edit_privilege.privilege_group_id.required && !form_edit_privilege.privilege_group_id.required" 
		                			class="help-block" style="color:red;" >Please select a Privilege Group</p>
							</div>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Privilege Name*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Privilege Name" name="privilege_name" 
		                				id="privilege_name" ng-model="privilege_name" ng-pattern="/^[a-z_]*$/" value="<?=$val_g->privilege_name ?>" required  />
							</div>
							<p ng-show="form_edit_privilege.privilege_name.required && !form_edit_privilege.privilege_name.required" 
		                		class="help-block" style="color:red;">Privilege Names is required</p>
		                	<p class="help-block" ng-show="form_edit_privilege.privilege_name.$error.pattern" style="color:red;" >Only lowercase alphabets and underscores are allowed</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Functionality Name*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-wrench"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Functionality Name" name="functionality_name" 
		                			id="functionality_name" ng-model="functionality_name" ng-pattern="/^[a-z_]*$/" value="<?=$val_g->functionality_name ?>" required />
							</div>
							<p ng-show="form_edit_privilege.functionality_name.required && !form_edit_privilege.functionality_name.required" 
		                		class="help-block" style="color:red;">Functionality Name is required</p>
		                	<p class="help-block" ng-show="form_edit_privilege.functionality_name.$error.pattern" style="color:red;">Only lowercase alphabets and underscores are allowed</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Privilege Description*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                		<textarea class="form-control" cols="75" id="privilege_description" name="privilege_description" 
		                			placeholder="Privilege Description" ng-model="privilege_description" ng-pattern="/^[a-zA-Z0-9\s]*$/"  required><?=$val_g->privilege_description ?></textarea>
							</div>
							<p ng-show="form_edit_privilege.privilege_description.required && !form_edit_privilege.privilege_description.required" 
		                				class="help-block" style="color:red;" >Privilege Description is required.</p>
		                	<p class="help-block" ng-show="form_edit_privilege.privilege_description.$error.pattern" style="color:red;">Only alphabets, numbers and spaces are allowed</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Functionality Description*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                		<textarea class="form-control" cols="75" id="functionality_description" name="functionality_description" 
		                		placeholder="Functionality Description (No special characters)" ng-model="functionality_description" ng-pattern="/^[a-zA-Z0-9\s]*$/" required><?=$val_g->functionality_description ?></textarea>
							</div>
							<p ng-show="form_edit_privilege.functionality_description.required && !form_edit_privilege.functionality_description.required" 
		                				class="help-block" style="color:red;" >Functionality Description is required.</p>
		                	<p class="help-block" ng-show="form_edit_privilege.functionality_description.$error.pattern" style="color:red;" >Only alphabets, numbers and spaces are allowed</p>
		                    <br>

		                    <div class="col-md-12 input-group">
			                    <label><strong>Is a Page?</strong></label>
		                    	<select class="form-control" id="is_page" name="is_page"  title="Is a Page" required>
		                        	<option value="0" <?= ( $val_g->is_page == 0 )?"selected":""  ?> >No</option>
		                        	<option value="1" <?= ( $val_g->is_page == 1 )?"selected":""  ?> >Yes</option>
		                        </select>
		                        <p ng-show="form_edit_privilege.is_page.required && !form_edit_privilege.is_page.required" 
		                			class="help-block" style="color:red;" >Please select Whether it is a page or not</p>
							</div>
		                    <br>
		                    
		                    <input type="hidden" value="update_privilege" name="what_do_you_want" id="what_do_you_want" />
		                    <input type="hidden" value="<?=$val_g->functionality_id ?>" name="functionality_id" id="functionality_id" />
		                    <input type="hidden" value="<?=$val_g->privilege_id ?>" name="privilege_id" id="privilege_id" />
		                    <input type="submit" value="Update" class="btn btn-primary" name="submit_form_edit_privilege" id="submit_form_edit_privilege" ng-disabled="form_edit_privilege.$invalid" />
		                    <input type="reset" value="Reset" class="btn btn-danger" name="reset_form_edit_privilege" id="reset_form_edit_privilege" />
	                    </form>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	<!--form to edit privilege group and enter group name  -->
	<div class="row hidden" id="editPrivilegeGroupForm" >
    	<div class="col-md-12 col-lg-6 col-lg-offset-3">
    		<br>
    		<br>
        	<div class="panel panel-default">
				<div class="panel-body ">
	            	<div class="row-fluid">
	            		<form name="form_edit_privilege_group" id="form_edit_privilege_group" method="POST" 
	            			ng-submit="submitPrivilegeGroup(form_edit_privilege_group.$valid,$event)" novalidate >
		                	<h3><strong>Edit</strong> Privilege Group</h3>
		                    <br>
		                   
		                    <div>
		                    	<label><strong>Privilege Group Name*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Privilege Group Name" name="privilege_group_name" 
		                				id="privilege_group_name" ng-model="privilege_group_name" ng-pattern="/^[a-zA-Z ]*$/" value="<?=$val_g->privilege_group_name ?>" required  />
							</div>
							<p ng-show="form_edit_privilege_group.privilege_group_name.required && !form_edit_privilege_group.privilege_group_name.required" 
		                		class="help-block" style="color:red;">Privilege Group Name is required</p>
		                	<p class="help-block" ng-show="form_edit_privilege_group.privilege_group_name.$error.pattern" style="color:red;" >Only alphabets and spaces are allowed</p>
		                    <br>
		                    
							<div class="col-md-12 input-group">
			                    <label><strong>Privilege Group Sequence*</strong></label>
		                    	<select class="form-control" id="privilege_group_sequence" name="privilege_group_sequence"  title="Choose Sequence" required>
		                        	<?php
		                        	for( $i=1 ; $i < 101 ; $i++ ){
		                        	?>
		                        		<option value="<?= $i ?>" <?php if( $i == $val_g->privilege_group_sequence ){ echo 'selected'; } ?> > <?= $i ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
		                        <p ng-show="form_edit_privilege_group.privilege_group_sequence.required && !form_edit_privilege_group.privilege_group_sequence.required" 
		                		class="help-block" style="color:red;" >Privilege Sequence is required</p>
							</div>
		                    <br>
		                    
		                    <input type="hidden" value="<?=$val_g->privilege_group_id ?>" name="privilege_group_main_id" id="privilege_group_main_id" />
		                    <input type="hidden" value="update_privilege_group" name="what_do_you_want" id="what_do_you_want" />
		                    <input type="submit" value="Update" class="btn btn-primary" name="submit_form_edit_privilege_group" id="submit_form_edit_privilege_group" ng-disabled="form_edit_privilege_group.$invalid" />
		                    <input type="reset" value="Reset" class="btn btn-danger" name="reset_form_edit_privilege" id="reset_form_edit_privilege" />
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
    <script src="assets/plugins/bootstrap-dropdown/bootstrap-hover-dropdown.min.js"></script>
	<script src="assets/plugins/bootstrap-select/bootstrap-select.js"></script>
	<script src="js/angular.min.js"></script>
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
