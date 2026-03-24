<?php 
	checkIfLoggedIn();
	checkPrivilegeForPage( basename( __FILE__, ".php" ) );
?>
<link rel="stylesheet" type="text/css" href="assets/plugins/datatables/dataTables.css" />
        <div id="main-content">
            <div class="row">
                <div class="col-md-12">
            		<h3><strong>Hooks</strong> Management</h3>
            		<div class="panel panel-default">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12 col-sm-12 col-xs-12 table-responsive">
                      				<table id="view-hooks-table" class="table table-tools table-hover table-striped table-bordered">
									 	<thead>
									 		<tr>
									 			<th class="text-center"><strong>Sr.no</strong></th>
									 			<th class="text-center"><strong>Hook Name</strong></th>
								                <th class="text-center"><strong>Description</strong></th>
								                <th class="text-center"><strong>Hook Content</strong></th>
								                <th class="text-center"><strong>Hook Content Meta</strong></th>
								                <th class="text-center"><strong>Actions</strong></th>
									 		</tr>
									 	</thead>       
									 	<tbody>
									 		<?php 
									        	$count = 1;
								        		$sql = "select * FROM hooks ";								        		
								        		$result_set = selectQuery( $sql );
									        	while( ( $val = mysqli_fetch_assoc( $result_set ) ) != NULL ){
									        ?>
									 		<tr>
									 			<td class="text-center"><?php echo $count++; ?></td>
									 			<td class="text-center"><?php echo $val['hook_name']; ?></td>
									 			<td class="text-center" style="max-width: 200px;"><?php echo htmlentities( $val['hook_description'] ); ?></td>
									 			<td class="text-center" style="max-width: 250px;"><pre><?php echo htmlentities( $val['hook_content'] ); ?></pre></td>
									 			<td class="text-center"><?php echo $val['hook_content_meta']; ?></td>
									 			<td class="text-center">
													<button type="button" class="btn btn-success btn-sm" onclick="javascript:editHook( '<?=$val[ 'id' ] ?>', this );" title="Edit Hook" alt="Edit Hook"><i class="fa fa-pencil"> </i></button><button type="button" class="btn btn-danger btn-sm" onclick="javascript:deleteHook( '<?=$val[ 'id' ] ?>', this );" title="Delete Hook" alt="Delete Hook"><i class="fa fa-trash-o"> </i></button>
												</td>
									 		</tr>
									 		<?php } ?>
									 	</tbody>
								    </table>          
                                </div>
                            </div>
                        </div>
                    </div>            
                </div>
            </div>
        </div>

    
    <!-- BEGIN PAGE LEVEL SCRIPTS -->
    <script src="assets/plugins/bootstrap-switch/bootstrap-switch.js"></script>
    <script src="assets/plugins/bootstrap-progressbar/bootstrap-progressbar.js"></script>
    <script src="assets/plugins/datatables/dynamic/jquery.dataTables.min.js"></script>
    <script src="assets/plugins/datatables/dataTables.bootstrap.js"></script>
    <script src="assets/plugins/datatables/dataTables.tableTools.js"></script>
    <script src="assets/plugins/datatables/table.editable.js"></script>
    <script src="assets/js/ecommerce.js"></script>
    <!-- END PAGE LEVEL SCRIPTS -->
    
    <script src="plugins/<?=$plugin_name ?>/js/<?=basename( __FILE__, ".php" ) ?>.js"></script>
