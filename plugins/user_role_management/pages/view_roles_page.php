<?php 
	checkIfLoggedIn();
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$role_id_selected = @$_REQUEST[ 'role_id_selected' ];
	
	$rolesForm = new FormHelper( "form_view_roles" );
	$rolesLabel = new FormField( "label", array( "class"=>"control-label" ), "Roles" );
	
?>
<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="assets/global/plugins/bootstrap-select/css/bootstrap-select.min.css" />

<!-- BEGIN PAGE HEAD-->
<div class="page-head">
	<!-- BEGIN PAGE TITLE -->
	<div class="page-title">
		<h1>
			View/Edit Roles
		</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD-->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">View/Edit Roles</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered">
	<h3>Assign privileges to roles</h3>
	<p>
		Select a Role below in order to View/Assign/Remove privileges assigned to it.<br> 
		Check/Uncheck beside the privileges in order to assign/remove the privilege from the Role. Click Update to save the settings.
	</p>
</div>

<div class="row">
	<div class="col-md-12">
		<div class="portlet box green">
			<div class="portlet-title">
				<div class="caption">
					<i class="fa fa-users"></i>Select a Role
				</div>
			</div>
			<div class="portlet-body">
				
				<!-- BEGIN FORM-->
				<form name="<?=$rolesForm->getFormName() ?>" id="<?=$rolesForm->getFormName() ?>" 
					method="POST">
					
					<div class="form-body">
						<div class="form-group">
							<div class="row">
								<div class="col-md-4">
									
									<?=$rolesLabel->generateField() ?>
									<select
										class="form-control" style="display: inline-block;" id="role_id" name="role_id"
										title="Select a Role" onchange="getPrivileges();">
										<option value="none" selected disabled>Select a Role</option>
				                    	<?php
											$sql = "Select * FROM roles";
											$result_set = selectQuery ( $sql );
											while ( ($val = mysqli_fetch_object ( $result_set )) != NULL ) {
												if (($val->role_id == roleToRoleId ( SESSION_ADMIN )) && ($_SESSION [SESSION_AUTHORIZATION] != roleToRoleId ( SESSION_ADMIN )))
													continue;
												?>
					                    		<option value="<?=$val->role_id ?>"
											<?=($val->role_id==$role_id_selected)?"selected":"" ?>>
					                    			<?=$val->role_name?>
					                    		</option>
				                    	<?php
											}
										?>															
									</select>
									
								</div>
							</div>
							<?php 
							if( $role_id_selected != "" ){
							?>
							<div class="row">
								<div class="form-group">
									<div class="col-md-2">
										<input style="margin: 5px 0px;" type="button" value="Delete Role" class="btn red" name="delete_form_view_roles" id="delete_form_view_roles" onclick="deleteRole();" />
									</div>
								</div>
							</div>
							<?php } ?>
						</div>
					</div>

				
				
				<br />
				<div>
					<?php 
					if( $role_id_selected != "" ){
						
						$sql = "Select * from roles_privileges WHERE role_id = '$role_id_selected'";
						$result_set3 = selectQuery ( $sql );
						$data = array ();
						while ( ($val3 = mysqli_fetch_object ( $result_set3 )) != NULL ) {
							$data[] = $val3->privilege_id;
						}
						
						$sql = "SELECT * FROM privilege_group ORDER BY privilege_group_sequence";
						$result_set1 = selectQuery( $sql );
						
						$sql = "SELECT pg.privilege_group_id, p.privilege_id, p.privilege_description, p.privilege_name FROM privileges p, privileges_groups pg, privilege_group ppg WHERE ( p.privilege_id = pg.privilege_id ) AND ( pg.privilege_group_id = ppg.privilege_group_id ) ORDER BY ppg.privilege_group_sequence";
						$result_set3 = selectQuery( $sql );
						$privileges = array();
						while( ( $val3 = mysqli_fetch_assoc( $result_set3 ) ) != NULL ){
							$privileges[] = $val3;
						}
						// print_r( $privileges );
						$j = 0;
						while( ( $val1 = mysqli_fetch_object( $result_set1 ) ) != NULL ){					
						?>
						<h4 style="padding-left: 0px;" class="caption-subject font-green-sharp bold uppercase"><?=$val1->privilege_group_name ?></h4>
						<table class="table table-striped table-bordered table-hover table-checkable order-column roles_tables" id="sample_<?=$val1->privilege_group_id ?>">
							<thead>
								<tr>
									<th class="sorting_disabled"><label
										class="mt-checkbox mt-checkbox-single mt-checkbox-outline"> <input
											type="checkbox" class="group-checkable"
											data-set="#sample_<?=$val1->privilege_group_id ?> .checkboxes" /> <span></span>
										</label>
									</th>
									<th>Privilege Name</th>
									<th>Description</th>
								</tr>
							</thead>
							<tbody>
							<?php 
							//echo mysqli_num_rows( $result_set3 ) . "--";
							//echo "j=$j" . "--" ;
							//echo $privileges[ $j ][ 'privilege_group_id' ] . "," .$val1->privilege_group_id;
							while( @$privileges[ $j ][ 'privilege_group_id' ] == $val1->privilege_group_id ){
								
							?>
								<tr>
									<td><label
											class="mt-checkbox mt-checkbox-single mt-checkbox-outline"> <input
												type="checkbox" class="privileges checkboxes" value="<?=$privileges[ $j ][ 'privilege_id' ] ?>" <?=(in_array( $privileges[ $j ][ 'privilege_id' ], $data ))?'checked="checked"':'' ?> /> <span></span>
										</label>
									</td>
									<td><?=$privileges[ $j ][ 'privilege_name' ] ?></td>
									<td><?=$privileges[ $j ][ 'privilege_description' ] ?></td>
								</tr>
							<?php
								$j++;
								if( $j == mysqli_num_rows( $result_set3 ) )
									break;
							}
							?>
							</tbody>
						</table>
						<br /><br />
						<?php
						}
					}
					?>
				</div>
				<br />
				
				<?php 
				if( $role_id_selected != "" ){
				?>
				<input type="hidden" id="what_do_you_want" name="what_do_you_want" value="update_role" />
		        <input type="submit" value="Update" class="btn green" name="submit_form_view_roles" id="submit_form_view_roles" />
		        <?php } ?>
		        
		    </form>
			<!-- END FORM-->
		        
		</div>
	</div>

</div>

<!-- END PAGE BASE CONTENT -->



    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="assets/global/plugins/bootstrap-select/js/bootstrap-select.min.js"></script>
    <script src="assets/global/scripts/datatable.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/datatables.min.js" type="text/javascript"></script>
    <script src="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.js" type="text/javascript"></script>
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
