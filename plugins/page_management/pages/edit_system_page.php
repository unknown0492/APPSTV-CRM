<?php 
	checkIfLoggedIn();	
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$page_id 		= @$_REQUEST[ 'page_id' ];
	$page_group_id 	= @$_REQUEST[ 'page_group_id' ];
	
	if( ( $page_id == "" ) ){
		echo "<script>alert( 'This page cannot be accessed directly. Click on edit button on the View System Page near the page entry which you want to edit' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_system_page" );
	}
	
	$sql = "SELECT * FROM page_group WHERE page_group_id = $page_group_id";
	$result_set = selectQuery( $sql );
	if( mysqli_num_rows( $result_set ) <= 0 ){
		echo "<script>alert( 'Invalid Page Group' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_system_page" );
	}
	
	$val0 = mysqli_fetch_object( $result_set );
	
	$sql = "SELECT * FROM pages,plugins 
			WHERE page_id = $page_id
			AND pages.`plugin_name` = plugins.`plugin_name`";

	$result_set = selectQuery( $sql );
	if( mysqli_num_rows( $result_set ) <= 0 ){
		echo "<script>alert( 'Invalid Page' );</script>";
		redirect( PAGE_NAME_ADMIN . WEBSITE_LINK_ENDS_WITH . "?what_do_you_want=view_system_page" );
	}
	
	$val2 = mysqli_fetch_object( $result_set );
?>
<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="editPageValidation" ng-controller="editPageController" >
		<div class="row">
			<div class="col-md-12 col-lg-6 col-lg-offset-3">
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Edit <strong>Page/Group</strong></h3>
                        </div>
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                	<button type="button" class="btn btn-lg btn-block btn-primary" name="editPageGroup" id="editPageGroup" ng-click="editPageGroup()"><i class="fa fa-facebook pull-left"></i>Edit Page Group</button>
                            	</div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <button type="button" class="btn btn-lg btn-block btn-primary" name="editPage" id="editPage" ng-click="editPage()"><i class="fa fa-facebook pull-left"></i>Edit Page</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
         	</div>      
                
	
	<div class="row hidden" id="editPageForm">
    	<div class="col-md-12 col-lg-6 col-lg-offset-3">
    		<br>
    		<br>
        	<div class="panel panel-default">
				<div class="panel-body ">
	            	<div class="row-fluid">
	            		<form name="form_edit_page" id="form_edit_page" method="POST" 
	            			ng-submit="submitForm(form_edit_page.$valid,$event)" novalidate >
	            			<input type="hidden" value="<?=$page_group_id ?>" id="selected_page_group_id" name="selected_page_group_id" />
		                	<h3><strong>Edit</strong> Page</h3>
		                    <br>
		                    
		                    <div class="col-md-12 input-group">
			                    <label><strong>Select Page Group*</strong></label>
		                    	<select class="form-control" id="page_group_id" name="page_group_id"  title="Select Page Group" required>
		                        	<option value="">Choose one Page Group</option>
		                        	<?php 
		                        	$sql = "Select * FROM page_group";
		                        	$result_set = selectQuery( $sql );
		                        	while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
		                        	?>
		                        		<option value="<?=$val->page_group_id ?>" <?=($val->page_group_id==$page_group_id)?"selected":"" ?>><?=$val->page_group_name ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
							</div>
							<span class="label label-success">Select one Page Group from the above dropdown list</span>
		                        <p ng-show="form_edit_page.page_group_id.required && !form_edit_page.page_group_id.required" 
		                			class="help-block" style="color:red;" >Please select a Page Group</p>
		                    <br><br>
		                    
		                    <div>
		                    	<label><strong>Page Name*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Page Name" name="page_name" 
		                				id="page_name" ng-model="page_name" ng-pattern="/^[a-z_]*$/" value="<?=$val2->page_name ?>" required 
		                				data-container="body" data-toggle="popover" data-placement="top" data-content="Only use lowercase and underscores. Use the sample format `some_name_page`" />
							</div>
							<!-- <span class="label label-success">Only use lowercase and underscores. Use the sample format `some_name_page`</span> -->
							<p ng-show="form_edit_page.page_name.required && !form_edit_page.page_name.required" 
		                		class="help-block" style="color:red;">Page Name is required</p>
		                	<p class="help-block" ng-show="form_edit_page.page_name.$error.pattern" style="color:red;" >Please use above rules for `Page Name`</p>
		                    <br>
							
							<div class="col-md-12 input-group">
			                    <label><strong>Page Sequence*</strong></label>
		                    	<select class="form-control" id="page_sequence" name="page_sequence"  title="Choose Page Sequence" required>
		                        	<?php
		                        	for( $i=1 ; $i < 101 ; $i++ ){
		                        	?>
		                        		<option value="<?= $i ?>" <?=($val2->page_sequence==$i)?"selected":"" ?> > <?= $i ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
		                        
							</div>
							<span class="label label-success">Select a page sequence number for the page</span>
		                        <p ng-show="form_edit_page.page_sequence.required && !form_edit_page.page_sequence.required" 
		                		class="help-block" style="color:red;" >Page Sequence is required</p>
		                    <br><br>
		                    	                    
		                    <div>
		                    	<label><strong>Icon</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-wrench"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Icon" name="icon" 
		                			id="icon" ng-model="icon" ng-pattern="/^[a-zA-Z_ -]*$/" value="<?=$val2->icon ?>" 
		                			data-container="body" data-toggle="popover" data-placement="top" data-content="Use icons from fontawesome.io. And should be in the format `fa fa-iconname`" />
							</div>		
							<!-- <span class="label label-success">Use icons from fontawesome.io. And should be in the format `fa fa-iconname`</span> -->
							<p class="help-block" ng-show="form_edit_page.icon.$error.pattern" style="color:red;">Only Alphabets, whitespaces, underscore and hyphen are allowed</p>
		                    <br>
		                    
		                    <div class="col-md-12 input-group">
			                    <label><strong>Visible</strong></label>
		                    	<select class="form-control" id="visible" name="visible"  title="Select Visiblility">
		                        		<option value= "" >Select Visibility</option>
		                        		<option value= "0" <?=($val2->visible==0)?"selected":"" ?>> Not Visible</option>
		                        		<option value= "1" <?=($val2->visible==1)?"selected":"" ?>> Visible </option>
		                        </select>
							</div>
							<span class="label label-success">Choose 0 if you don't want this page to be displayed, and 1 if you want it to be displayed</span>
							<br><br>
		                    
		                    <div>
		                    	<label><strong>Page Title*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-wrench"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Page Title" name="page_title" 
		                			id="page_title" ng-model="page_title" ng-pattern="/^[a-zA-Z_ !@#$%*()\]\[?&-,\/]*$/" value="<?=$val2->page_title ?>" required 
		                			data-container="body" data-toggle="popover" data-placement="top" data-content="Some special characters are not allowed for `Page Title`" />
							</div>
							<!-- <span class="label label-success">Some special characters are not allowed for `Page Title`</span> -->
							<p ng-show="form_edit_page.page_title.required && !form_edit_page.page_title.required" 
		                		class="help-block" style="color:red;">Page Title is required</p>			
		                	<p class="help-block" ng-show="form_edit_page.page_title.$error.pattern" style="color:red;">Please remove some special characters from the `Page Title`</p>
		                    <br />
		                    	
		                    <div class="col-md-12 input-group">
			                    <label><strong>Functionality*</strong></label>
		                    	<select class="form-control" id="functionality_id" name="functionality_id"  title="Choose funtionality">
		                    		<option value="none">Select Functionality </option>
		                        	<?php
		                        	$sql = "Select * FROM functionalities WHERE is_page = 1";
		                        	$result_set = selectQuery( $sql );
		                        	while( ( $val = mysqli_fetch_assoc( $result_set ) ) != NULL ){
		                        	?>
		                        		<option value="<?=$val[ 'functionality_id' ]; ?>" <?=($val[ 'functionality_id' ]==$val2->functionality_id)?"selected":"" ?>> <?= $val[ 'functionality_name' ] . " : " .$val[ 'functionality_description' ] ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
							</div>
		                    <span class="label label-success">Select the corresponding Functionality Name for this page</span>
							<br /><br>
		                    
		                    <div>
		                    	<label><strong>Description</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                		<textarea class="form-control" cols="75" id="description" name="description" 
		                			placeholder="Description" ng-model="description" ng-pattern="/^[a-zA-Z0-9 ,.#$&*\[\]]*$/" 
		                			data-container="body" data-toggle="popover" data-placement="top" data-content="Use only from lowercase, uppercase alphabets, digits, white spaces and the special characters ,.#$&*[]" ><?=$val2->description ?></textarea>
							</div>
		                	<!-- <span class="label label-success">Use only from lowercase, uppercase alphabets, digits, white spaces and the special characters ,.#$&*[]</span> -->
							<p class="help-block" ng-show="form_edit_page.description.$error.pattern" style="color:red;">Special Characters not allowed except comma</p>
		                    <br>

		                    <div class="col-md-12 input-group">
			                    <label><strong>Plugin*</strong></label>
		                    	<select class="form-control" id="plugin_name" name="plugin_name"  title="Select Plugin Name" required>
		                        	<option value="">Choose one Plugin Name</option>
		                        	<?php 
		                        	$sql = "Select * FROM plugins";
		                        	$result_set = selectQuery( $sql );
		                        	while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
		                        	?>
		                        		<option value="<?=$val->plugin_name ?>" <?= ( $val->plugin_name == $val2->plugin_name )?"selected":"" ?> ><?=$val->plugin_display_name ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
							</div>
							<span class="label label-success">Select one Plugin from the above dropdown list</span>
		                    <p ng-show="form_edit_page.plugin_name.required && !form_edit_page.plugin_name.required" 
		                			class="help-block" style="color:red;" >Please select a Plugin</p>
		                    <br>
		                    <br>
		      				
		      				<div>
		                    	<label><strong>Tags</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                		<textarea class="form-control" cols="75" id="tags" name="tags" 
		                			placeholder="Tags" ng-model="tags" ng-pattern="/^[a-zA-Z0-9 ,]*$/" 
		                			data-container="body" data-toggle="popover" data-placement="top" data-content="Use comma-separated values of keywords"><?=$val2->tags ?></textarea>
							</div>
		                	<!-- <span class="label label-success">Use comma-separated values of keywords</span> -->
							<p class="help-block" ng-show="form_edit_page.tags.$error.pattern" style="color:red;">Special Characters not allowed except comma</p>
		                    <br>    
		      				
		      				<div>
		                    	<label><strong>Image</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-wrench"></i>
						        </span>
		                		<input type="url" class="form-control" placeholder="Image" name="image" id="image" ng-model="image" value="<?=$val2->image ?>" 
		                		data-container="body" data-toggle="popover" data-placement="top" data-content="Input a URL for the image. This URL will be used for the meta tags " />
							</div>			
		                	<!-- <span class="label label-success">Input a URL for the image. This URL will be used for the meta tags </span> -->
							<p class="help-block" ng-show="form_edit_page.image.$error.url" style="color:red;">Invalid URL. Please enter Valid URL.</p>
		                    <br>
		      				
		      				<div>
		                    	<label><strong>Content</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                		<textarea class="form-control" cols="75" id="content" name="content" 
		                			placeholder="Content" ng-model="content" ng-pattern="/^[a-zA-Z0-9 ,.#$&*\[\]]*$/"
		                			data-container="body" data-toggle="popover" data-placement="top" data-content="Use only from lowercase, uppercase alphabets, digits, white spaces and the special characters ,.#$&*[]"><?=$val2->content ?></textarea>
							</div>
		                	<!-- <span class="label label-success">Use only from lowercase, uppercase alphabets, digits, white spaces and the special characters ,.#$&*[]</span> -->
							<p class="help-block" ng-show="form_edit_page.content.$error.pattern" style="color:red;">Only alphabets, numbers and spaces are allowed</p>
		                    <br>
		      				
		                    <input type="hidden" value="<?=$page_id ?>" name="page_id" id="page_id" />
		                    <input type="hidden" value="update_page" name="what_do_you_want" id="what_do_you_want" />
		                    <input type="submit" value="Update" class="btn btn-primary" name="submit_form_edit_page" id="submit_form_edit_page" ng-disabled="form_edit_page.$invalid" />
	                    </form>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	
	
	
	
	<!--form to edit page group and enter group name  -->
	<div class="row hidden" id="editPageGroupForm" >
    	<div class="col-md-12 col-lg-6 col-lg-offset-3">
    		<br>
    		<br>
        	<div class="panel panel-default">
				<div class="panel-body ">
	            	<div class="row-fluid">
	            		<form name="form_edit_page_group" id="form_edit_page_group" method="POST" 
	            			ng-submit="submitPageGroup(form_edit_page_group.$valid,$event)" novalidate >
		                	<h3><strong>Edit</strong> Page Group</h3>
		                    <br>
		                   
		                    <div>
		                    	<label><strong>Page Group Name*</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-font"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Page Group Name" name="page_group_name" 
		                				id="page_group_name" ng-model="page_group_name" ng-pattern="/^[a-zA-Z0-9 ]*$/" value="<?=$val0->page_group_name ?>" required  
		                				data-container="body" data-toggle="popover" data-placement="top" data-content="Only use lowercase, uppercase alphabets, digits and white spaces" />
							</div>
							<!-- <span class="label label-success">Only use lowercase, uppercase alphabets, digits and white spaces</span> -->
							<p ng-show="form_edit_page_group.page_group_name.required && !form_edit_page_group.page_group_name.required" 
		                		class="help-block" style="color:red;">Page Group Name is required</p>
		                	<p class="help-block" ng-show="form_edit_page_group.page_group_name.$error.pattern" style="color:red;" >You have used invalid characters. Please refer to the above rules for `Page Group Name`</p>
		                    <br>
		                    
		                    <div>
		                    	<label><strong>Icon</strong></label>
		                    </div>
		                    <div class="input-group transparent">
		                    	<span class="input-group-addon bg-blue">
		                        	<i class="fa fa-wrench"></i>
						        </span>
		                		<input type="text" class="form-control" placeholder="Icon" name="page_group_icon" 
		                			id="page_group_icon" ng-model="page_group_icon" ng-pattern="/^[a-zA-Z_ -]*$/" value="<?=$val0->icon ?>" 
		                			data-container="body" data-toggle="popover" data-placement="top" data-content="Use icons from fontawesome.io. And should be in the format `fa fa-iconname`" />
							</div>			
		                	<!-- <span class="label label-success">Use icons from fontawesome.io. And should be in the format `fa fa-iconname`</span> -->
							<p class="help-block" ng-show="form_edit_page_group.page_group_icon.$error.pattern" style="color:red;">You have used invalid characters. Please refer to the above rules for `icon`</p>
		                    <br />
		                    
							<div class="col-md-12 input-group">
			                    <label><strong>Page Group Sequence*</strong></label>
		                    	<select class="form-control" id="page_group_sequence" name="page_group_sequence"  title="Choose Group Sequence" required>
		                        	<?php
		                        	$sql = "Select MAX(page_group_sequence) as a FROM page_group";
		                        	$result_set = selectQuery( $sql );
		                        	while( ( $val = mysqli_fetch_assoc( $result_set ) ) != NULL ){
		                        		$maxSequence = $val[ 'a' ] + 1;
		                        	}
		                        	for( $i=1 ; $i < 101 ; $i++ ){
		                        	?>
		                        		<option value="<?= $i ?>" <?=($val0->page_group_sequence == $i)?"selected":"" ?> > <?= $i ?></option>	
		                        	<?php
		                        	}
		                        	?>
		                        </select>
							</div>
							<span class="label label-success">Select a page sequence number for the page</span>
		                    <p ng-show="form_edit_page_group.page_group_sequence.required && !form_edit_page_group.page_group_sequence.required" 
		                		class="help-block" style="color:red;" >Page Sequence is required</p>
		                    <br /><br />
		                    
		                    <input type="hidden" value="<?=$page_group_id ?>" name="page_group_id" id="page_group_id" />
		                    <input type="hidden" value="update_page_group" name="what_do_you_want" id="what_do_you_want" />
		                    <input type="submit" value="Update" class="btn btn-primary" name="submit_form_edit_page_group" id="submit_form_edit_page_group" ng-disabled="form_edit_page_group.$invalid" />
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
