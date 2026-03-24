<?php 
	checkIfLoggedIn();
	//checkPrivilegeForPage( basename( __FILE__, ".php" ) );
?>
<link rel="stylesheet" type="text/css" href="assets/plugins/datatables/dataTables.css" />
<!-- BEGIN MAIN CONTENT -->
<div id="main-content" ng-app="PluginValidation" ng-controller="PluginController" >
	<div class="row">
    	<div class="col-md-12 col-lg-6 col-lg-offset-3">
    		<br>
    		<br>
        	<div class="panel panel-default">
				<div class="panel-body ">
	            	<div class="row-fluid">
	            		<form name="form_plugin" id="form_plugin" method="POST"
	            			ng-submit="submitForm(form_plugin.$valid,$event)" novalidate >
		                	<h3><strong>Plugin</strong></h3>
		                    <br />		                    
		                    
		                   <div>
		                    	<label><strong>Plugin Name*</strong></label>
		                    </div>
		                    <div class="input-group">
		                    	<span class="input-group-addon bg-blue">           
		                        	<i class="fa fa-envelope"></i>
								</span>
					            <input type="text" class="form-control" placeholder="Plugin Name" id="plugin_name" name="plugin_name"
					             ng-model="plugin_name" ng-pattern="/^[a-z0-9\s]*$/" required />
		            		</div>
		            		<p ng-show="form_plugin.plugin_name.required && !form_plugin.plugin_name.required" 
		                		class="help-block" style="color:red;" >Plugin  Name is required</p>
		                	<p class="help-block" ng-show="form_plugin.plugin_name.$error.pattern" style="color:red;">
		                		Invalid Plugin Name</p>
		                    <br>

		                    <div>
		                    	<label><strong>Plugin Display Name*</strong></label>
		                    </div>
		                    <div class="input-group">
		                    	<span class="input-group-addon bg-blue">           
		                        	<i class="fa fa-envelope"></i>
								</span>
					            <input type="text" class="form-control" placeholder="Plugin Display Name" id="plugin_display_name" name="plugin_display_name"
					             ng-model="plugin_display_name" ng-pattern="/^[a-zA-Z0-9\s]*$/" required />
		            		</div>
		            		<p ng-show="form_plugin.plugin_display_name.required && !form_plugin.plugin_display_name.required" 
		                		class="help-block" style="color:red;" >Plugin Display Name is required</p>
		                	<p class="help-block" ng-show="form_plugin.plugin_display_name.$error.pattern" style="color:red;">
		                		Invalid Plugin Display Name</p>
		                    <br>

		                    <input type="hidden"  value="create_plugin" name="what_do_you_want" id="what_do_you_want"/>
		                    <input type="submit" value="Create" class="btn btn-primary" name="submit_form_plugin" id="submit_form_plugin" ng-disabled="form_plugin.$invalid"  />
		                    <input type="reset" value="Reset" class="btn btn-danger" name="reset_form_plugin" id="reset_form_plugin" />
		            	</form>
					</div>
				</div>
			</div>
		</div>

		<div class="col-md-12 col-lg-12 ">
    		<br>
    		<br>
        		<div class="panel panel-default">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-md-12 col-sm-12 col-xs-12 table-responsive">
                            <table id="view-privilege-table" class="table table-tools table-hover table-striped table-bordered">
                                <thead>
                                	<tr>
						                <th class="text-center">Sr. No.</th>
						                <th class="text-center">Plugin Name</th>
						                <th class="text-center">Plugin Display Name</th>
						                <th><strong>Options</strong></th>
						            </tr>
						        </thead>
						        <tbody>
						        	<?php 
					        			$count = 1;
						        		$sql = "select * FROM plugins ORDER BY plugin_name";								
						        		$result_set = selectQuery( $sql );
						        		
						        		while( ( $val = mysqli_fetch_assoc( $result_set ) ) != NULL ){
						        		?>
									<tr>
										<td  class="text-center"><?=$count++; ?></td>
										<td class="text-center"><?=$val[ 'plugin_name' ] ?></td>
										<td class="text-center"><?=$val[ 'plugin_display_name' ] ?></td>
										<td class="text-center">
											<button type="button" onclick="javascript:editPlugin( '<?=$val[ 'plugin_name' ] ?>', this );" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modal-responsive" title="Edit Plugin" alt="Edit Plugin"><i class="fa fa-pencil"> </i></button><button type="button" class="btn btn-danger btn-sm" onclick="javascript:deletePlugin( '<?=$val[ 'plugin_name' ] ?>', this );" title="Delete Plugin" alt="Delete Plugin"><i class="fa fa-trash-o"> </i></button>
										</td>
									</tr>
										<?php 
										}
						        	//}
										?>
						        </tbody>
						    </table>
						</div>
					</div>
				</div>
			</div>
		</div>

        <div class="modal fade" id="modal-responsive" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                        <h4 class="modal-title"><strong>Edit</strong> Plugin </h4>
                    </div>
                    <form id="form_edit_plugin" name="form_edit_plugin" method="POST">
	                    <div class="modal-body">
	                        <div class="row">
	                            <div class="col-md-12">
	                                <div class="form-group">
	                                    <label for="Plugin Name" class="control-label">Plugin Name</label>
	                                    <input type="text" class="form-control" id="edit_plugin_name"  name="edit_plugin_name" placeholder="Plugin Name" readonly>
	                                </div>
	                            </div>
	                            <div class="col-md-12">
	                                <div class="form-group">
	                                    <label for="Plugin Display Name" class="control-label">Plugin Display Name</label>
	                                    <input type="text" class="form-control" id="edit_plugin_display_name" name="edit_plugin_display_name" placeholder="Plugin Display Name" >
	                                </div>
	                            </div>
	                        </div>
	                    </div>
	                    <div class="modal-footer text-center">
	                        <input type="submit" id="submit_edit_form" name="submit_edit_form"  onclick="updatePluginDetails()" class="btn btn-primary" value="update">
	                        <input type="hidden" id="what_do_you_want" name="what_do_you_want" value="edit_plugin_details" >
		                    <input type="reset" value="Reset" class="btn btn-danger" name="reset_form_edit_product" id="reset_form_edit_product" />
	                    </div>
                	</form>
                </div>
            </div>
        </div>
        <div class="md-overlay"></div> <!-- Overlay Element. Important: place just after last modal -->

	</div>
</div>
<!-- END MAIN CONTENT -->

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="js/angular.min.js"></script>
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
    <!-- END  PAGE LEVEL SCRIPTS -->
