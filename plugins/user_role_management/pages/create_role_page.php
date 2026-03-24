<?php 
	checkIfLoggedIn();
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
	
	$rolesForm = new FormHelper( "form_create_role" );
	
	$roleNameLabel = new FormField( "label", array( "class"=>"control-label" ), "Role Name*" );
	$roleNameText  = new FormField( "text", array(
			"placeholder"=>"Enter a name for the Role",
			"class"=>"form-control",
			"name"=>"role_name",
			"id"=>"role_name",
			"ngValidated"=>"true",
			"ng-model"=>"role_name",
			"ng-pattern"=>"VLDTN_ROLE_NAME",
			"required"=>"required",
			"form-name"=>$rolesForm->getFormName()
	), NULL );
	
	$rolesWhatDoYouwant = new FormField( "hidden", array(
			"id"=>"what_do_you_want",
			"name"=>"what_do_you_want",
			"value"=>"create_role"
	), NULL );
	
	$roleCreateButton = new FormField( "button", array(
			"value"=>"Create Role",
			"class"=>"btn green",
			"name"=>"submit_form_view_roles",
			"id"=>"submit_form_view_roles",
			"ng-disabled"=>$rolesForm->getFormName().'.$invalid',
			"ng-click"=>"createRole(". $rolesForm->getFormName() .")"
	), "Create Role" );
?>

<link href="assets/global/plugins/datatables/datatables.min.css" rel="stylesheet" type="text/css" />
<link href="assets/global/plugins/datatables/plugins/bootstrap/datatables.bootstrap.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="assets/global/plugins/bootstrap-select/css/bootstrap-select.min.css" />

<!-- BEGIN PAGE HEAD-->
<div class="page-head">
	<!-- BEGIN PAGE TITLE -->
	<div class="page-title">
		<h1>
			Create New Role
		</h1>
	</div>
	<!-- END PAGE TITLE -->
</div>
<!-- END PAGE HEAD-->

<!-- BEGIN PAGE BREADCRUMB -->
<ul class="page-breadcrumb breadcrumb">
	<li><a href="<?=WEBSITE_ADMINPANEL_URL ?>">Home</a> <i class="fa fa-circle"></i></li>
	<li><span class="active">Create New Role</span></li>
</ul>
<!-- END PAGE BREADCRUMB -->

<!-- BEGIN PAGE BASE CONTENT -->
<div class="m-heading-1 border-green m-bordered">
	<h3>Create a Role</h3>
	<p>
		Role is a group of privileges. Several privileges are combined and attached to a Role. Each Role should have a unique name. Each User of the system should have a Role.<br /> 
		Check/Uncheck beside the privileges in order to assign that privilege to the Role. Click Create to save the settings.
	</p>
</div>


<div class="row" ng-app="createRolePageValidation" ng-controller="createRolePageController">
	<div class="col-md-12">
		<div class="portlet box green">
			<div class="portlet-title">
				<div class="caption">
					<i class="fa fa-users"></i> Enter a Unique Role Name
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
									
									<h4 style="padding-left: 0px;" class="caption-subject font-grey-mint bold uppercase">Role Name*</h4>
									<?=$roleNameText->generateField() ?>
									
									
								</div>
									
							</div>
							
						</div>
					</div>
					<br />
				<div>
					<h4 style="padding-left: 0px;" class="caption-subject font-grey-mint bold uppercase">Choose Privileges</h4>
					<hr />
					<?php 
					//if( $role_id_selected != "" ){
						
						$sql = "SELECT * FROM privilege_group ORDER BY privilege_group_sequence";
						$result_set1 = selectQuery( $sql );
						
						$sql = "SELECT pg.privilege_group_id, p.privilege_id, p.privilege_description, p.privilege_name FROM privileges p, privileges_groups pg, privilege_group ppg WHERE ( p.privilege_id = pg.privilege_id ) AND ( pg.privilege_group_id = ppg.privilege_group_id ) ORDER BY ppg.privilege_group_sequence";
						$result_set3 = selectQuery( $sql );
						$privileges = array();
						while( ( $val3 = mysqli_fetch_assoc( $result_set3 ) ) != NULL ){
							$privileges[] = $val3;
						}
						//print_r( $privileges );
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
							// echo mysqli_num_rows( $result_set3 ) . "--$j";
							
							while( @$privileges[ $j ][ 'privilege_group_id' ] == $val1->privilege_group_id ){
								
							?>
								<tr>
									<td><label
											class="mt-checkbox mt-checkbox-single mt-checkbox-outline"> <input
												type="checkbox" class="privileges checkboxes" value="<?=$privileges[ $j ][ 'privilege_id' ] ?>"  /> <span></span>
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
					//}
					?>
				</div>
				<br />
				
				<?=$rolesWhatDoYouwant->generateField() ?>
				<?=$roleCreateButton->generateField() ?>
		       
		        
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
    <script src="js/angular.js" type="text/javascript"></script>
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->















