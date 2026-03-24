<?php 
	checkIfLoggedIn();	
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$limit = 100;
	
	$role_id 	 = @$_REQUEST[ 'selected_role_id' ];
	$user_search = @$_REQUEST[ 'user_search' ];
	if( ( trim( $role_id ) == "" ) || ( $role_id == NULL ) ){
		$role_id = 2;
	}
	
	$sql = "SELECT count(user_id) as count from users";
	$result_set = selectQuery( $sql );
	$val = mysqli_fetch_object( $result_set );
	
	//$total_rows = $val->count;
	$start = ( !isset( $_REQUEST[ 'start' ] ) )?1:$_REQUEST[ 'start' ];
	$end   = ( !isset( $_REQUEST[ 'end' ] ) )?/* min( array( $limit, $total_rows ) ) */$start+$limit-1:$_REQUEST[ 'end' ];
	
	$new_start = $start - 1;
	
	if( isset( $_REQUEST[ 'user_search' ] ) && ( trim( @$_REQUEST[ 'user_search' ] ) != "" ) ){
		$sql 	  = "Select * FROM users u, roles r WHERE (u.role_id = r.role_id) AND ( ( u.user_id LIKE '%$user_search%' ) || ( u.fname LIKE '%$user_search%' ) || ( u.lname LIKE '%$user_search%' ) || ( u.nickname LIKE '%$user_search%' ) || ( r.role_name LIKE '%$user_search%' ) ) LIMIT $new_start, $limit";
		$sql_rows = "Select count(*) as count FROM users u, roles r WHERE (u.role_id = r.role_id) AND ( ( u.user_id LIKE '%$user_search%' ) || ( u.fname LIKE '%$user_search%' ) || ( u.lname LIKE '%$user_search%' ) || ( u.nickname LIKE '%$user_search%' ) || ( r.role_name LIKE '%$user_search%' ) )";
		$rs = selectQuery( $sql_rows );
		$val2 = mysqli_fetch_object( $rs );
		$total_rows = $val2->count;
		//$current_rows = mysqli_num_rows( $result_set );
	}
	else if( $role_id != 0 ){
		$sql 	  = "Select * FROM users, roles WHERE (users.role_id = roles.role_id) AND (roles.role_id = $role_id) LIMIT $new_start, $limit";
		$sql_rows = "Select count(*) as count FROM users, roles WHERE (users.role_id = roles.role_id) AND (roles.role_id = $role_id)";
		$rs = selectQuery( $sql_rows );
		$val2 = mysqli_fetch_object( $rs );
		$total_rows = $val2->count;
	}
	else if( $role_id == 0 ){
		$sql = "Select * FROM users, roles WHERE (users.role_id = roles.role_id) LIMIT $new_start, $limit";
		$sql_rows = "Select count(*) as count FROM users, roles WHERE (users.role_id = roles.role_id)";
		$rs = selectQuery( $sql_rows );
		$val2 = mysqli_fetch_object( $rs );
		$total_rows = $val2->count;
	}
	// echo $sql;
	
	
	// CASE - 1
	if( $total_rows <= $limit ){
		$button_prev = "";
		$button_next = "";
	}
	
	if( ( $total_rows > $limit ) && ( $start < $limit ) ){
		$button_prev = "";
		//$start += $limit;
		$s = $start + $limit;
		 //$e = $total_rows - $end;
		$e = min( array( $end+$limit, $total_rows ) );
		$button_next = "<button id='button_next' data-start='$s' data-end='$e' type='button' class='btn-sm btn btn-warning'>Load Next &gt;</button>";
	}
	
	if( ( $total_rows > $limit ) && ( $start > $limit ) ){
		//$start = $start - $limit;
		//$end = $total - $end;
		$s = $start - $limit;
		//$e = $total_rows - $end;
		if( $end == $total_rows )
			$e = $start - 1;
		else
			$e = min( array( $end-$limit, $total_rows ) );//$start+$limit-1;
		$button_prev = "<button id='button_prev' data-start='$s' data-end='$e' type='button' class='btn-sm btn btn-warning'> &lt; Load Previous</button>";
		if( ($start + $limit) <= $total_rows ){
			$s = $start + $limit;
			//$e = $total_rows - $end;
			$e = min( array( $end+$limit, $total_rows ) ); //$start+$limit-1;
			$button_next = "<button id='button_next' data-start='$s' data-end='$e' type='button' class='btn-sm btn btn-warning'>Load Next &gt;</button>";
		}
		else{$button_next = "";}
	}
	
	
	/**
	 * 
	 * Edit Form Modal Form Elements Definition
	 * 
	 */
	$formEditUser = new FormHelper( "form_edit_user" );
	
	$textEmail	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"edit_email",
			"name"=>"edit_email",
			"ngValidated"=>"true",
			"ng-model"=>"edit_email",
			"ng-pattern"=>"VLDTN_EMAIL",
			"required"=>"required",
			"placeholder"=>"Email ID here",
			"form-name"=>$formEditUser->getFormName()
	), NULL );
	
	$textFName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"edit_fname",
			"name"=>"edit_fname",
			"ngValidated"=>"true",
			"ng-model"=>"edit_fname",
			"ng-pattern"=>"VLDTN_FIRST_NAME",
			"placeholder"=>"First Name",
			"form-name"=>$formEditUser->getFormName()
	), NULL );
	
	
	$textLName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"edit_lname",
			"name"=>"edit_lname",
			"ngValidated"=>"true",
			"ng-model"=>"edit_lname",
			"ng-pattern"=>"VLDTN_LAST_NAME",
			"placeholder"=>"Last Name",
			"form-name"=>$formEditUser->getFormName()
	), NULL );
	
	
	$textNickName	= new FormField( "text", array(
			"class"=>"form-control",
			"id"=>"edit_nickname",
			"name"=>"edit_nickname",
			"ngValidated"=>"true",
			"ng-model"=>"edit_nickname",
			"ng-pattern"=>"VLDTN_NICK_NAME",
			"placeholder"=>"Nick Name",
			"form-name"=>$formEditUser->getFormName()
	), NULL );
	
?>
<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="assets/global/plugins/bootstrap-select/css/bootstrap-select.min.css" />

<!-- BEGIN PAGE HEAD-->
<div class="page-head">
	<!-- BEGIN PAGE TITLE -->
	<div class="page-title">
		<h1>
			View/Edit Users
		</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD-->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">View/Edit Users</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered" 
	 >
	<h3>View all user accounts</h3>
	<p>
		A User is an Integral part of the system. A user can access the functionalities of the system. The system features are inaccessible without the user.
		Here, all the user accounts in the system are listed, and some of their attributes can be modified.
	</p>
</div>

<div ng-app="editUserPageApp" ng-controller="editUserPageCtrl">
            <div class="row">
            	<div class="col-md-12">
					<div class="portlet box green">
						<div class="portlet-title">
							<div class="caption">
								<i class="fa fa-user"></i> Choose a Unique User ID
							</div>
						</div>
						<div class="portlet-body" style="padding: 30px;">
							<div class="row">
                            	<div class="col-lg-6 col-md-8 col-sm-12 col-xs-12">
                            		<h3><strong>View</strong> Users</h3>
                                
                                    <label><strong>Select User Group/Role : </strong></label>
                                    <select name="selected_role_id" id="selected_role_id" onchange="getUsers();" class="form-control input-medium">
                                    	<?php 
                                    		$sql1 = "SELECT * FROM roles";
                                    		$result_set1 = selectQuery( $sql1 );
                                    		while( ( $val1 = mysqli_fetch_object( $result_set1 ) ) != NULL ){
                                    		?>
                                    			<option value="<?=$val1->role_id ?>" <?=($role_id==$val1->role_id)?"selected":"" ?>><?=$val1->role_name ?></option>
                                    		<?php 
                                    		}
                                    		?>
                                    	<option value="0" <?=($role_id==0)?"selected":"" ?>>View all users</option>
                                    </select>
								</div>
								<div class="col-lg-6 col-md-8 col-sm-12 col-xs-12">
									<div class="alert alert-success fade in">
                                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                                        Showing <strong><?=$start ?></strong> to <strong><?=$end ?></strong> of <strong><?=$total_rows ?></strong> records.
                                    </div>
                                    <br />
                                    <div class="text-center" >
	                                    <?= $button_prev ?>
	                                    <?= $button_next ?>
									</div>
								</div>
							</div>
                            <br />
                            <div class="row">
                            	<div class="col-lg-4 col-md-6 col-sm-8 col-xs-12">
                            		<label><strong>Deep Search : </strong></label>
                                    <input name="user_search" id="user_search" class="form-control" placeholder="Search User" value="<?=$user_search ?>" />
                                    <label class="label label-sm label-success">Note : Use Deep Search when you are not able to find the appropriate record</label>
								</div>
							</div>
							<br />
							<div class="row">
                                <div class="col-md-12 col-sm-12 col-xs-12 table-responsive">
                                    <table id="view-users-table" class="table table-tools table-hover table-striped table-bordered">
                                        <thead>
                                        	<tr>
								                <th>Sr. No.</th>
								                <th><strong>Username</strong></th>
								                <th><strong>First Name</strong></th>
								                <th><strong>Last Name</strong></th>
								                <th><strong>Email</strong></th>
								                <th><strong>Role</strong></th>
								                <th><center><strong>Options</strong></center></th>
								            </tr>
								        </thead>
								        <tbody>
								        	<?php 
								        	$count = 1;
								        	$result_set = selectQuery( $sql );
								        	while( ( $val = mysqli_fetch_object( $result_set ) ) != NULL ){
								        		$idd = "delete" . mt_rand( 10, 1000 );
								        		$user_id = $val->user_id;
								        		$view_button		= "<a data-toggle=\"modal\" href=\"#view_user_info_modal\" class=\"btn btn-sm btn-success\" type=\"button\" onclick=\"viewUserInfo( '$user_id', this );\" title=\"View User Info\" alt=\"View User Info\"><i class=\"fa fa-eye fa-lg\" > </i></a>";
								        		$edit_button		= "<a data-toggle=\"modal\" href=\"#edit_user_info_modal\" class=\"btn btn-sm btn-primary\" type=\"button\" ng-click=\"editUser( '$user_id', this );\" title=\"Edit User\" alt=\"Edit User\"><i class=\"fa fa-pencil fa-lg\" > </i></a>";
								        		//$reset_pass_button	= "<button class=\"btn btn-sm btn-default\" type=\"button\" onclick=\"resetPassword( '$user_id', this );\" title=\"Reset Password\" alt=\"Reset Password\"><i class=\"fa fa-refresh fa-lg\" > </i></button>";
								        		$delete_button		= "<button id=\"$idd\" class=\"btn btn-sm btn-danger\" type=\"button\" ng-click=\"deleteUser( '$user_id', '$idd' );\" title=\"Delete User\" alt=\"Delete User\"><i class=\"fa fa-trash-o fa-lg\" > </i></button>";
								        		
								        		if( ( $val->role_id == roleToRoleId( SESSION_ADMIN ) ) && ( $_SESSION[ SESSION_AUTHORIZATION ] != roleToRoleId( SESSION_ADMIN ) ) ){
								        			$view_button  = "";
								        			$edit_button = "";
								        			//$reset_pass_button = "";
								        			$delete_button = "";
								        		}
								        	?>
								        	<tr>
								                <td><?=$count ?></td>
								                <td data-user_id="<?=$val->user_id ?>"><?=$val->user_id ?></td>
								                <td><?=$val->fname ?></td>
								                <td><?=$val->lname ?></td>
								                <td><?=$val->email ?></td>
								                <th><?=$val->role_name ?></th>
								                <td style="text-align: center;"><?=$view_button ?> <?=$edit_button ?>  <?=$delete_button ?></td>
								            </tr>
								            <?php 
								            	$count++;
								        	}
								            ?>
								        </tbody>
								    </table>
								</div>
							</div>
						
            			</div>
            		</div>
            	</div>
            </div>
            
            
			
        <!-- View User Info Modal -->
        
        <div class="modal fade" id="view_user_info_modal" tabindex="-1" role="basic" aria-hidden="true">
			<div class="modal-dialog">
            	<div class="modal-content">
                	<div class="modal-header bg-green bg-font-green">
                    	<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                    	<h4 class="modal-title">User Information</h4>
                    </div>
                    <div class="modal-body">
                    	<?=getLoadingHTML( "loading_user_info", "Loading Content...", false, "center", "50" ) ?>
                    	<div class="content form">
					    	<!-- BEGIN FORM-->
							<div class="form-horizontal form-bordered form-label-stripped">
								<div class="form-body">
									<div class="form-group">
										<label class="control-label col-md-3">Username</label>
										<div class="col-md-9">
											<span class="help-block" id="view_user_id"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">First Name</label>
										<div class="col-md-9">
											<span class="help-block" id="view_fname"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Last Name</label>
										<div class="col-md-9">
											<span class="help-block" id="view_lname"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">NickName</label>
										<div class="col-md-9">
											<span class="help-block" id="view_nickname"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Email</label>
										<div class="col-md-9">
											<span class="help-block" id="view_email"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Role</label>
										<div class="col-md-9">
											<span class="help-block" id="view_role_name"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Created On</label>
										<div class="col-md-9">
											<span class="help-block" id="view_registered_on"></span>
										</div>
									</div>
								</div>
							</div>
							<!-- END FORM-->
							<br />
							<center>
					    		<button type="button" class="btn green" data-dismiss="modal">Close</button>
					    	</center>
					    </div>
                    </div>
                    
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        
        <!--/ View User Info Modal -->
        
        
        <!-- Edit User Info Modal -->
        
        <div class="modal fade" id="edit_user_info_modal" tabindex="-1" role="basic" aria-hidden="true">
			<div class="modal-dialog">
            	<div class="modal-content">
                	<div class="modal-header bg-blue bg-font-blue">
                    	<button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                    	<h4 class="modal-title">Edit User Information</h4>
                    </div>
                    <div class="modal-body">
                    	<?=getLoadingHTML( "loading_user_info", "Loading Content...", false, "center", "50" ) ?>
                    	<div class="content form">
					    	<!-- BEGIN FORM-->
							<form class="form-horizontal form-bordered form-label-stripped" method="POST" ng-submit="updateUser( <?=$formEditUser->getFormName() ?> )"
								  id="<?=$formEditUser->getFormName() ?>" name="<?=$formEditUser->getFormName() ?>" >
								<div class="form-body">
									<div class="form-group">
										<label class="control-label col-md-3">Username</label>
										<div class="col-md-9">
											<span class="help-block" id="edit_user_id"></span>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">First Name</label>
										<div class="col-md-9">
											<?=$textFName->generateField() ?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Last Name</label>
										<div class="col-md-9">
											<?=$textLName->generateField() ?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">NickName</label>
										<div class="col-md-9">
											<?=$textNickName->generateField() ?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Email</label>
										<div class="col-md-9">
											<?=$textEmail->generateField() ?>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Password</label>
										<div class="col-md-9">
											<button type="button" data-original-text="Reset Password" data-loading-text="Please wait..." class="btn btn-circle btn-danger" ng-click="resetOthersPassword();" data-user-id="" id="edit_reset_password">Reset Password</button>
											<p class="font-green-jungle bold password_reset_message" ></p>
										</div>
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Role</label>
										<div class="col-md-9">
											<select class="form-control input-medium" id="edit_role_id" name="edit_role_id"  title="Choose one role" required>
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
									</div>
									<div class="form-group">
										<label class="control-label col-md-3">Created On</label>
										<div class="col-md-9">
											<span class="help-block" id="edit_registered_on"></span>
										</div>
									</div>
									<div>
										<br />
										<center>
											<button type="submit" class="btn green" data-loading-text="Updating..." data-original-text="Update" id="submit_<?=$formEditUser->getFormName() ?>" name="submit_<?=$formEditUser->getFormName() ?>">Update</button>
								    		<button type="button" class="btn blue" data-dismiss="modal">Close</button>
								    	</center>
									</div>
								</div>
							</form>
							<!-- END FORM-->
							
					    </div>
                    </div>
                    
                </div>
                <!-- /.modal-content -->
            </div>
            <!-- /.modal-dialog -->
        </div>
        
        <!--/ Edit User Info Modal -->
</div>    
	
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="assets/global/plugins/bootstrap-select/js/bootstrap-select.min.js"></script>
    <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
    <script src="js/moment.js"></script>
    <script src="js/angular.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
    
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
