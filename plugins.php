<?php 
	
	include( 'library/functions.php' );
	
	// echo WEBSITE_SUB_DOMAIN_DIRECTORY;
	
	checkIfLoggedIn();
	checkPrivilegeForPage( basename( __FILE__, ".php" ) . "_page" ); // Since the name of the Functionality is plugins_page
	
	$v = $_SERVER[ 'REQUEST_URI' ];
	if( $v === WEBSITE_SUB_DOMAIN_DIRECTORY )
		redirect( PAGE_LOGIN );
	
	$val = getSiteConfig();
	//$protocol = $val->protocol;
	
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js sidebar-large lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js sidebar-large lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js sidebar-large lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="no-js sidebar-large">
<!--<![endif]-->

<head>
<!-- BEGIN META SECTION -->
<meta charset="utf-8">

<title>Manage Plugins</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<meta content="" name="description" />
<meta content="themes-lab" name="author" />
<!-- END META SECTION -->

<?php include( 'includes/css.php' ); ?>

<!-- BEGIN PAGE LEVEL STYLE -->
<link rel="stylesheet" type="text/css" href="assets/plugins/datatables/dataTables.css" />
<!-- END PAGE LEVEL STYLE -->
<?php 
	$hooks_array = initHooks();
?>
</head>

<body>
	<?php echo getHookContent( "after_body_starts" ); ?>
	
	<!-- 
	<div id="preloader-before" style="z-index:10000; background-color:#000; position:fixed; top:0; left:0; width:100%; height:100%;"><img class="centered" src="images/loading.gif" align="middle"></div>
	 -->
	<?php 
		include 'includes/top-right-menu.php';
	?>
    
	<!-- Container begins here -->
	<div class="container">
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<br />
				<div class="panel">
					<div class="panel-body">
						<div class="row">
							<div class="col-lg-4 col-md-4 col-sm-5 col-xs-5">
								<form method="POST" name="form_add_new_plugin"
									id="form_add_new_plugin" action="plugins.php"
									enctype="multipart/form-data">
									<label><strong>Add New Plugin</strong></label> <input
										type="file" name="file_plugin_zip" id="file_plugin_zip" /><br />
									<button type="submit"
										class="btn btn-default btn-rounded btn-primary"
										id="submit_form_add_new_plugin"
										name="submit_form_add_new_plugin">Go</button>
								</form>
							</div>
							<!-- <div class="col-lg-4 col-lg-offset-4 col-md-4 col-md-offset-4 col-sm-5 col-xs-5">
									<div class="input-group">
	                                    <span class="input-group-addon bg-default">           
	                                      <i class="fa fa-search"></i>
	                                    </span>
	                                    <input class="form-control" placeholder="Search" type="text">
	                                </div>
								</div> -->
						</div>
					</div>
				</div>
			</div>
		</div>
			
			<?php 
			if( !isset( $_POST[ 'submit_form_add_new_plugin' ] ) || ($_FILES[ 'file_plugin_zip' ][ 'error' ] > 0 ) ){
			?>
			<!-- Data Tables -->
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<div class="panel">
					<div class="panel-body">
						<div class="row">
							<div
								class="col-lg-12 col-md-12 col-sm-120 col-xs-12 table-responsive">
								<table id="view-plugins-table"
									class="table table-tools table-hover table-striped table-bordered">
									<thead>
										<th>Sr No</th>
										<th>Plugin Name</th>
										<th>Description</th>
										<th>Creation Date</th>
										<th>Author</th>
										<th>Version</th>
										<th class="text-center">Options</th>
									</thead>
									<tbody>
											<?php 
											$count = 1;
											$plugin_names = scandir( PLUGIN_CONFIG_PATH );
											array_shift( $plugin_names );
											array_shift( $plugin_names );
											// print_r( $plugin_names );
											for( $i = 0 ; $i < count( $plugin_names ) ; $i++ ){
												$plugin_info = getPluginInfo( $plugin_names[ $i ] );
												// print_r( $plugin_info );
											?>
											<tr>
											<td><?=$count ?></td>
											<td><?=$plugin_info[ 'PLUGIN_NAME' ]; ?></td>
											<td><?=$plugin_info[ 'PLUGIN_DESCRIPTION' ]; ?></td>
											<td><?=$plugin_info[ 'PLUGIN_DATE' ]; ?></td>
											<td><?=$plugin_info[ 'PLUGIN_AUTHOR' ]; ?></td>
											<td><?=$plugin_info[ 'PLUGIN_VERSION' ]; ?></td>
											<td class="text-center">
												<button type="button"
													class="btn btn-sm btn-danger" id="<?=$plugin_info[ 'PLUGIN_NAME' ]; ?>" 
													onclick="deletePlugin( '<?=$plugin_info[ 'PLUGIN_NAME' ]; ?>', this );">
													<i class="fa fa-trash-o"></i>
												</button>
											</td>
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
		<!-- Data Tables Ends Here-->
			<?php 
			}
			else{
				
			?>
			<!-- Plugin Installation Process -->
		<div class="row">
			<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
				<div class="panel">
					<div class="panel-body">
						<div class="row">
							<div class="col-lg-12 col-md-12 col-sm-120 col-xs-12">
								<?php 
								// Variable Declaration
								$zip = new ZipArchive;
								$plugin_config_file;
								$prid = array();  // privilege_id
								$prgid = array(); // privilege_group_id
								$fid = array();	  // functionality_id
								$pid = array();	  // page_id
								$pgid = array();  // page_group_id
								$privilege_group_name;
								$privilege_group_id;
								$privileges;
								$privilege_name;
								$privilege_description;
								$functionality_name;
								$functionality_description;
								$functionality_id;
								$page_group_name;
								$pages;
								$icon;
								$page_group_id;
								$page_name;
								$visible;
								$page_title;
								$title;
								$tags;
								$description;
								$content;
								$image;
								$functionality_name;
								$functionality_id;
								$page_id;
								$files;
								$all_good = array();
								$is_update = false;
								
								// validate the config-file structure
								
								deleteTempPlugin();
								
								move_uploaded_file( $_FILES[ 'file_plugin_zip' ][ 'tmp_name' ], PLUGIN_PATH . "temp-plugin.zip" );
								
								openPluginZip(); 				// validate the zip file before extracting [done when opening]
								
								extractAndValidatePluginFiles();
								
								include( PLUGIN_TEMP_PATH . "plugin-config/$plugin_config_file" );// OR die( 'Plugin Config File Missing !' );
								checkIfPluginAlreadyExist();
								
								echo "<strong>Creating Tables </strong><br />";
									
								addPrivilegesAndFunctionalities();
								
								addPages();
								
								executeSqlQueries();
								
								echo "<h3 style='text-align: center;'>Don't forget to add Privileges to the Administrator Role in order to use the plugin functionalities !</h3>";
								
								extractPluginFiles();
								
								closePluginZip();
								
								deleteTempPlugin();
								
								echo "<h4 class='text-success text-center'>Plugin Installed Successfully !</h4>";
								
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Plugin Installation Process Ends Here -->
			
			<?php 
			}
			?>
		<!-- Container ends here -->
	</div>
		<?php 
		
		function openPluginZip(){
			global $zip;
			global $all_good;
			
			$res = $zip->open( PLUGIN_PATH . "temp-plugin.zip", ZipArchive::CHECKCONS );
			switch( $res ) {
				case ZipArchive::ER_NOZIP :
					//$all_good[ 0 ] = false; 
					//$all_good[ 1 ] = '<p class="alert alert-danger text-center">Corrupt ZIP File ! <strong>(err1)</strong></p>';
					//goto bottom;
					//die( '<p class="alert alert-danger text-center">Corrupt ZIP File ! <strong>(err1)</strong></p>' );
					break;
				case ZipArchive::ER_INCONS :
					/* $all_good[ 0 ] = false; 
					$all_good[ 1 ] = '<p class="alert alert-danger text-center">Corrupt ZIP File ! <strong>(err2)</strong></p>';
					goto bottom;
					 */
					die( '<p class="alert alert-danger text-center">Corrupt ZIP File ! <strong>(err2)</strong></p>' );
					break;
				case ZipArchive::ER_CRC :
					/* $all_good[ 0 ] = false; 
					$all_good[ 1 ] = '<p class="alert alert-danger text-center">Corrupt ZIP File ! <strong>(err3)</strong></p>';
					goto bottom; */
					die( '<p class="alert alert-danger text-center">Corrupt ZIP File ! <strong>(err3)</strong></p>' );
					break;
			}
		}
		
		function extractAndValidatePluginFiles(){
			global $zip;
			global $files;
			global $plugin_config_file;
			
			$zip->extractTo( PLUGIN_TEMP_PATH );
			
			// Get the name of the plugin config file
			$files = array_diff( scandir( PLUGIN_TEMP_PATH . "plugin-config/" ), array( '.', '..' ) );
			
			if( count( $files ) == 0 ){
				//$all_good[ 0 ] = false;
				//$all_good[ 1 ] = '<p class="alert alert-danger text-center">Plugin Config File Missing !</strong></p>';
				//goto bottom;
				die( '<p class="alert alert-danger text-center">Plugin Config File Missing !</strong></p>' );
			}
			$plugin_config_file = $files[ 2 ];
			
			/* // validate that the config file name is same as plugin name
			if( basename( $plugin_config_file, ".php" ) != $options( 'PLUGIN_NAME' ) )
				die( 'Plugin Name and the Plugin File Name does not match !' ); */
		}
		
		function checkIfPluginAlreadyExist(){
			global $plugin_config_file;
			global $is_update;
			$options_temp = getPluginInfoFromTempPath( $plugin_config_file );
			//echo "new plugin : " . $options_temp[ 'PLUGIN_NAME' ];
			
			// Check if this plugin already exist
			if( file_exists( PLUGIN_CONFIG_PATH . $plugin_config_file ) ){
				$options = getPluginInfo( $plugin_config_file );
				//echo "old plugin : " . $options[ 'PLUGIN_NAME' ];
			
					
				// Check if the version of already existing plugin, and new plugin is same or different
				if( $options[ 'PLUGIN_NAME' ] == $options_temp[ 'PLUGIN_NAME' ] ){
					// if the version is same
					if( $options[ 'PLUGIN_VERSION' ] == $options_temp[ 'PLUGIN_VERSION' ] ){
						die( "<p class='alert alert-danger text-center'>This plugin already exist <strong>OR</strong> a plugin with this name already exist !<br /> </p>" );
					}
					$is_update  = true;
					// version different but same name
					echo "<script>var c = confirm( 'The version number of this plugin is different than the existing one. Are you sure you want to upgrade this plugin ?' ); if( !c ){ window.location.href=window.location.href; }</script>";
					echo "<p class='alert alert-success text-center'>Upgrading the plugin !<br /> </p>";
					
				}
			}
		}
		
		function addPrivilegesAndFunctionalities(){
			global $prid;  		// privilege_id
			global $prgid; 		// privilege_group_id
			global $fid;	  	// functionality_id
			global $privilege_group_name;
			global $config_privileges;
			global $privilege_group_id;
			global $privileges;
			global $privilege_name;
			global $privilege_description;
			global $functionality_name;
			global $functionality_description;
			global $functionality_id;
			global $config_functionalities;
			
			for( $i = 0; $i < count( $config_privileges ) ; $i++ ){
				$privilege_group_name = $config_privileges[ $i ][ 'privilege_group_name' ];
				echo "<br /><strong>Creating Privilege Group Name</strong><br />";
				$sql = "SELECT privilege_group_name FROM privilege_group WHERE privilege_group_name = '$privilege_group_name'";
				$result_set = selectQuery( $sql );
				if( mysqli_num_rows( $result_set ) > 0 ){
					echo "<span class='text-danger'>Privilege Group Name -> <strong>$privilege_group_name</strong> already exist. Skipping Privilege Group creation..</span> <br />";
				}
				else{
					$sql = "INSERT INTO privilege_group( `privilege_group_name` ) VALUES( '$privilege_group_name' )";
					$rows = insertQuery( $sql );
					if( $rows > 0 ){
						echo "<span class='text-success'>Privilege Group -> <strong>$privilege_group_name</strong> created successfully <br />";
					}
				}
				// Get privilege_group_id
				$sql = "SELECT privilege_group_id FROM privilege_group WHERE privilege_group_name = '$privilege_group_name'";
				$result_set = selectQuery( $sql );
				$privilege_group_id = mysqli_fetch_object( $result_set );
				$privilege_group_id = $privilege_group_id->privilege_group_id;
				array_push( $prgid, $privilege_group_id );
			
			
				$privileges = $config_privileges[ $i ][ 'privilege_group_privileges' ];
				for( $j = 0; $j < count( $privileges ) ; $j++ ){
					$privilege_name				= $privileges[ $j ][ 'privilege_name' ];
					$privilege_description		= $privileges[ $j ][ 'privilege_description' ];
					$functionality_name			= $privileges[ $j ][ 'functionality_name' ];
					$functionality_description	= $privileges[ $j ][ 'functionality_description' ];
			
					echo "<br /><strong>Creating Privilege and Functionalities</strong><br />";
					$sql = "SELECT privilege_name FROM privileges WHERE privilege_name = '$privilege_name'";
					$result_set = selectQuery( $sql );
					if( mysqli_num_rows( $result_set ) > 0 ){
						echo "<span class='text-danger'>Privilege Name -> <strong>$privilege_name</strong> already exist. Skipping Privilege creation..</span><br />";
					}
					else{
						$sql = "INSERT INTO privileges( `privilege_name`, `privilege_description` ) VALUES( '$privilege_name', '$privilege_description' )";
						$rows = insertQuery( $sql );
						if( $rows > 0 ){
							echo "<span class='text-success'>Privilege Name -> <strong>$privilege_name</strong> created successfully</span><br />";
						}
					}
			
					$sql = "SELECT functionality_name FROM functionalities WHERE functionality_name = '$functionality_name'";
					$result_set = selectQuery( $sql );
					if( mysqli_num_rows( $result_set ) > 0 ){
						echo "<span class='text-danger'>Functionality Name -> <strong>$functionality_name</strong> already exist. Skipping Functionality creation..</span><br />";
					}
					else{
						$sql = "INSERT INTO functionalities( `functionality_name`, `functionality_description` ) VALUES( '$functionality_name', '$functionality_description' )";
						$rows = insertQuery( $sql );
						if( $rows > 0 ){
							echo "<span class='text-success'>Functionality Name -> <strong>$functionality_name</strong> created successfully</span><br />";
						}
					}
			
					// Get privilege_id, functionality_id
					$sql = "SELECT privilege_id FROM privileges WHERE privilege_name = '$privilege_name'";
					$result_set = selectQuery( $sql );
					$privilege_id = mysqli_fetch_object( $result_set );
					$privilege_id = $privilege_id->privilege_id;
			
					$sql = "SELECT functionality_id FROM functionalities WHERE functionality_name = '$functionality_name'";
					$result_set = selectQuery( $sql );
					$functionality_id = mysqli_fetch_object( $result_set );
					$functionality_id = $functionality_id->functionality_id;
			
					// Check if mapping for privileges_functionalities already exist
					$sql = "SELECT * FROM privileges_functionalities WHERE privilege_id = $privilege_id AND functionality_id = $functionality_id";
					$result_set = selectQuery( $sql );
					if( mysqli_num_rows( $result_set ) > 0 ){
						echo "<span class='text-danger'>Privilege - Functionality Mapping already exist. Skipping mapping..</span><br />";
					}
					else{
						$sql = "INSERT INTO privileges_functionalities( `functionality_id`, `privilege_id` ) VALUES( '$functionality_id', '$privilege_id' )";
						$rows = selectQuery( $sql );
						if( $rows > 0 ){
							echo "Privilege - Functionality mapped successfully</span><br />";
						}
					}
						
					// Check if the mapping for privileges_groups already exist
					$sql = "SELECT privilege_id FROM privileges_groups WHERE privilege_group_id = $privilege_group_id AND privilege_id = $privilege_id";
					$result_set = selectQuery( $sql );
					if( mysqli_num_rows( $result_set ) > 0 ){
						echo "<span class='text-danger'>Privilege already present in the Privilege Group. Skipping this operation..</span><br />";
					}
					else{
						// Insert the privilege into privileges_groups
						$sql = "INSERT INTO privileges_groups( `privilege_group_id`, `privilege_id` ) VALUES( '$privilege_group_id', '$privilege_id' )";
						$rows = insertQuery( $sql );
						if( $rows > 0 ){
							echo "<span class='text-success'>Privilege assigned to the Privilege Group successfully</span> <br />";
						}
					}
						
					array_push( $prid, $privilege_id );
					array_push( $fid, $functionality_id );
				}
				array_push( $prgid, $privilege_group_id );
			}
		}
		
		function rollbackAddPrivilegesPagesAndFunctionalities(){
			global $prid;  		// privilege_id
			global $prgid; 		// privilege_group_id
			global $fid;	  	// functionality_id
			
			echo "<br /><span class='text-danger'>Rolling Back Privileges & Functionalities</span><br />";
			// Delete From privileges
			for( $i = 0 ; $i < count( $prid ) ; $i++ ){
				$sql = "DELETE FROM privileges WHERE privilege_id = " . $prid[ $i ];
				deleteQuery( $sql );
			}
			
			// Delete From functionalities
			for( $i = 0 ; $i < count( $fid ) ; $i++ ){
				$sql = "DELETE FROM functionalities WHERE functionality_id = " . $fid[ $i ];
				deleteQuery( $sql );
			}
			
			// Delete From privilege_group
			for( $i = 0 ; $i < count( $prgid ) ; $i++ ){
				$sql = "DELETE FROM privilege_group WHERE privilege_id = " . $prgid[ $i ];
				deleteQuery( $sql );
			}
		}
		
		function addPages(){
			global $pid;
			global $pgid;
			global $page_group_name;
			global $pages;
			global $icon;
			global $page_group_id;
			global $page_name;
			global $visible;
			global $page_title;
			global $title;
			global $tags;
			global $description;
			global $content;
			global $image;
			global $functionality_name;
			global $functionality_id;
			global $page_id;
			global $config_pages;
			global $plugin_config_file;
					
			for( $i = 0 ; $i < count( $config_pages ) ; $i++ ){
				$page_group_name = $config_pages[ $i ][ 'page_group_name' ];
				$icon = $config_pages[ $i ][ 'icon' ];
				$pages = $config_pages[ $i ][ 'page_group_pages' ];
			
				$sql 		= "SELECT page_group_name FROM page_group WHERE page_group_name = '$page_group_name'";
				$result_set = selectQuery( $sql );
				if( mysqli_num_rows( $result_set ) > 0 ){
					echo "<span class='text-danger'>Page Group Name -> <strong>$page_group_name</strong> already exist. Skipping page Group creation..</span><br />";
				}
				else{
					$sql = "INSERT INTO page_group( `page_group_name`, `icon` ) VALUES( '$page_group_name', '$icon' )";
					$rows = insertQuery( $sql );
					if( $rows > 0 ){
						echo "<span class='text-success'>Page Group -> <strong>$page_group_name</strong> created successfully </span><br />";
					}
				}
			
				// Get the page_group_id
				$sql = "SELECT page_group_id FROM page_group WHERE page_group_name = '$page_group_name'";
				$result_set = selectQuery( $sql );
				$page_group_id = mysqli_fetch_object( $result_set );
				$page_group_id = $page_group_id->page_group_id;
			
				for( $j = 0 ; $j < count( $pages ) ; $j++ ){
					$page_name = 	$pages[ $j ][ 'page_name' ];
					$icon = 		$pages[ $j ][ 'icon' ];
					$visible = 		$pages[ $j ][ 'visible' ];
					$page_title = 	$pages[ $j ][ 'page_title' ];
					$title = 		$pages[ $j ][ 'title' ];
					$tags = 		$pages[ $j ][ 'tags' ];
					$description = 	$pages[ $j ][ 'description' ];
					$content = 		$pages[ $j ][ 'content' ];
					$image = 		$pages[ $j ][ 'image' ];
					$functionality_name = $pages[ $j ][ 'functionality_name' ]; 			// get functionality_id for this functionality_name
					$plugin_name = basename( $plugin_config_file, ".php" );
					
					$sql = "SELECT page_id FROM pages WHERE page_name = '$page_name'";
					$result_set = selectQuery( $sql );
					if( mysqli_num_rows( $result_set ) > 0 ){
						echo "<span class='text-danger'>Page Name -> <strong>$page_name</strong> already exist. Skipping Page creation..</span><br />";
					}
					else{
						$sql = "SELECT functionality_id FROM functionalities WHERE functionality_name = '$functionality_name'";
						$result_set = selectQuery( $sql );
						$functionality_id = mysqli_fetch_object( $result_set );
						$functionality_id = $functionality_id->functionality_id;
			
						$sql = "INSERT INTO pages( `page_name`, `icon`, `visible`, `page_title` ,`title`, `tags`, `description`, `content`, `image`, `functionality_id`, `plugin_name` )
						VALUES( '$page_name', '$icon', $visible, '$page_title', '$title', '$tags', '$description', '$content', '$image', $functionality_id, '$plugin_name' )";
						$rows = insertQuery( $sql );
						if( $rows > 0 ){
							echo "<span class='text-success'>Page Name -> <strong>$page_name</strong> created successfully </span><br />";
						}
					}
						
					// get page_id
					$sql = "SELECT page_id FROM pages WHERE page_name = '$page_name'";
					$result_set = selectQuery( $sql );
					$page_id = mysqli_fetch_object( $result_set );
					$page_id = $page_id->page_id;
						
					// Check if mapping for page_group_id and page_id exist
					$sql = "SELECT page_id FROM pages_groups WHERE group_id = $page_group_id AND page_id = $page_id";
					$result_set = selectQuery( $sql );
					if( mysqli_num_rows( $result_set ) > 0 ){
						echo "<span class='text-danger'>Mapping for Page Group and Page already exist. Skipping Page mapping operation..</span><br />";
					}
					else{
						$sql = "INSERT INTO pages_groups( `group_id`, `page_id` ) VALUES( $page_group_id, $page_id )";
						$rows = insertQuery( $sql );
						if( $rows > 0 ){
							echo "<span class='text-success'>Page Group mapping done successfully</span><br />";
						}
					}
					array_push( $pgid, $page_id );
				}
				array_push( $pgid, $page_group_id );
			}
		}
		
		function executeSqlQueries(){
			global $config_queries;
			// print_r( $config_queries );
			for( $i = 0 ; $i < count( $config_queries ) ; $i++ ){
				rawQuery( $config_queries[ $i ] );
			}
		}
		
		function extractPluginFiles(){
			global $plugin_config_file;
			global $files;
			global $is_update;
			
			$options = getPluginInfoFromTempPath( $plugin_config_file );
			
			// 1. Moving the config file
			rename( PLUGIN_TEMP_PATH . "plugin-config/$plugin_config_file", PLUGIN_CONFIG_PATH . $plugin_config_file );
				
			// 2. Moving the other directories from tmp/plugins to the plugins/
			if( $is_update ){ // is updating the plugin, den delete the plugin contents first
				deleteDirectoryRecursive( PLUGIN_PATH . $options[ 'PLUGIN_NAME' ] );
				
				//include( PLUGIN_WEBSERVICE_PATH . "plugin_functions.php" );
				
				$old_files = scandir( PLUGIN_WEBSERVICE_PATH . $options[ 'PLUGIN_NAME' ] );
				array_shift( $old_files );array_shift( $old_files );
				//print_r( $old_files );
				removeFilesFromWebservice( $old_files, "./" );
				
				deleteDirectoryRecursive( PLUGIN_WEBSERVICE_PATH . $options[ 'PLUGIN_NAME' ] );
				//sleep( 4000 );
			}
			mkdir( PLUGIN_PATH . $options[ 'PLUGIN_NAME' ] );
			rename( PLUGIN_TEMP_PATH . $options[ 'PLUGIN_NAME' ] . "/css", PLUGIN_PATH . $options[ 'PLUGIN_NAME' ] . "/css" );
			rename( PLUGIN_TEMP_PATH . $options[ 'PLUGIN_NAME' ] . "/js", PLUGIN_PATH . $options[ 'PLUGIN_NAME' ] . "/js" );
			rename( PLUGIN_TEMP_PATH . $options[ 'PLUGIN_NAME' ] . "/pages", PLUGIN_PATH . $options[ 'PLUGIN_NAME' ] . "/pages" );
			rename( PLUGIN_TEMP_PATH . $options[ 'PLUGIN_NAME' ] . "/images", PLUGIN_PATH . $options[ 'PLUGIN_NAME' ] . "/images" );
				
			// 3. Iterate the webservice files n move them to thier appropriate location
			$files = scandir( PLUGIN_TEMP_PATH . $options[ 'PLUGIN_NAME' ] . "/webservice/" );
			array_shift( $files );array_shift( $files );
			mkdir( PLUGIN_WEBSERVICE_PATH . $options[ 'PLUGIN_NAME' ] );
			//$file_contents = fg
			for( $i = 0 ; $i < count( $files ) ; $i++ ){
				rename( PLUGIN_TEMP_PATH . $options[ 'PLUGIN_NAME' ] . "/webservice/" . $files[ $i ], PLUGIN_WEBSERVICE_PATH . $options[ 'PLUGIN_NAME' ] . "/" . $files[ $i ] );
			}
				
			// 4. Modify the sub-functions.php file
			addFilesToWebservice( $files );
		}
		
		function deleteTempPlugin(){
			@deleteDirectoryRecursive( PLUGIN_TEMP_PATH );
			@unlink( PLUGIN_PATH . "temp-plugin.zip" );
		}
		
		function closePluginZip(){
			global $zip;
			$zip->close();
		}
		
		function getPluginInfo( $plugin_name ){
			// echo "$plugin_name <br />";
			include( PLUGIN_CONFIG_PATH . $plugin_name );
			return $options;
		}
		
		function getPluginInfoFromTempPath( $plugin_name ){
			// echo "$plugin_name <br />";
			include( PLUGIN_TEMP_PATH . "plugin-config/" . $plugin_name );
			return $options;
		}
		
		function addFilesToWebservice( $files ){
			global $plugin_config_file;
			global $is_update;
			
			//include( PLUGIN_WEBSERVICE_PATH . "plugin_functions.php" );
			$options = getPluginInfo( $plugin_config_file );
				
			/* if( $is_update ){ // remove previous webservice files from the sub-functions.php for this plugin
				$old_files = scandir( PLUGIN_WEBSERVICE_PATH . $options[ 'PLUGIN_NAME' ] );
				array_shift( $old_files );array_shift( $old_files );
				print_r( $old_files );
				removeFilesFromWebservice( $old_files, "./" );
				
				deleteDirectoryRecursive( PLUGIN_WEBSERVICE_PATH . $options[ 'PLUGIN_NAME' ] );
			} */
			
			
			$file = fopen( PLUGIN_WEBSERVICE_PATH . "sub-functions.php", "r+" );
			$arr = array();
			$i = 0;
			//$files = array( "abc.php", "cyz.php" );
			for( $i = 0 ; $i < count( $files ) ; $i++ ){
				$t = $options[ 'PLUGIN_NAME' ] . "/" . $files[ $i ];
				$f = "\tinclude( '$t' );\n";
				$arr[ $i ] = $f;
			}
			// print_r( $arr );
			
			$new_arr = array();
			$count = 0;
			$ss = "";
			while( ( $s = fgets( $file ) ) != NULL ){
				if( $count == 1 ){
					for( $i = 0 ; $i < count( $files ) ; $i++ ){
						$new_arr[ $count++ ] = $arr[ $i ];
					}
					//continue;
				}
				$new_arr[ $count++ ] = $s;
			}
			fclose( $file );
			//print_r( $new_arr );
			$fi = fopen( PLUGIN_WEBSERVICE_PATH . "sub-functions.php", "w+" );
			for( $i = 0 ; $i < count( $new_arr ) ; $i++ ){
				fprintf( $fi, $new_arr[ $i ] );
			}
		}
		
		function removeFilesFromWebservice( $files, $base_path ){
			$file = fopen( $base_path . "webservice/functions/sub-functions.php", "r" );
			// iterate each line of code into an array
			$arr = array();
			$i = 0;
			while( ( $s = fgets( $file ) ) != NULL ){
				$arr[ $i++ ] = $s;
			}
		
			fclose( $file );
			// print_r( $files );
			// print_r( $arr );
		
			$new_arr = array();
			$c = 0;
			for( $j = 0; $j < count( $files ) ; $j++ ){
				$new_arr = array();
				for( $k = 0; $k < count( $arr ) ; $k++ ){
					if( strpos( $arr[ $k ], $files[ $j ] ) != FALSE ){
						//echo "Skip this line ".$arr[ $k ]."\n";
						continue;
					}
					$new_arr[ $c++ ] = $arr[ $k ];
				}
				$c = 0;
				$arr = array();
				$arr = array_merge( $arr, $new_arr );
			}
		
			// print_r( $arr );
			// print_r( $new_arr );
		
			$fi = fopen( $base_path . "webservice/functions/sub-functions.php", "w" );
			for( $i = 0 ; $i < count( $arr ) ; $i++ ){
				fprintf( $fi, $arr[ $i ] );
			}
		
			fclose( $fi );
			//for( $j = 0; $j < count( $arr ) ; $j++ ){
			//$arr = array_diff( $arr, $files );
			//}
			//if( in_array( $needle, $arr ) )
		
			//print_r( $arr );
		}
	?>
	
	<?php
		/* bottom:
		if( !$all_good[ 0 ] ){
			
		} */
		
		include( 'includes/modals.php' );
	?>
		
	<!-- BEGIN MANDATORY SCRIPTS -->
	<script src="assets/plugins/jquery-1.11.js"></script>
	<script src="assets/plugins/jquery-migrate-1.2.1.js"></script>
	<script src="assets/plugins/jquery-ui/jquery-ui-1.10.4.min.js"></script>
	<script src="assets/plugins/jquery-mobile/jquery.mobile-1.4.2.js"></script>
	<script src="assets/plugins/bootstrap/bootstrap.min.js"></script>
	<script src="assets/plugins/jquery.cookie.min.js" type="text/javascript"></script>
	<!-- END MANDATORY SCRIPTS -->

	<!-- BEGIN PAGE LEVEL SCRIPTS -->
	<script src="assets/plugins/backstretch/backstretch.min.js"></script>
	<script src="assets/plugins/bootstrap-loading/lada.min.js"></script>
	<script src="assets/js/account.js"></script>
	<script src="js/angular.min.js"></script>
	<script src="js/plugins.js"></script>
	<script src="assets/plugins/datatables/dynamic/jquery.dataTables.min.js"></script>
	<script src="assets/plugins/datatables/dataTables.bootstrap.js"></script>
	<script src="assets/plugins/datatables/dataTables.tableTools.js"></script>
	<script src="assets/plugins/datatables/table.editable.js"></script>
	<script src="js/custom.js"></script>
	<!-- END PAGE LEVEL SCRIPTS -->
	
</body>
</html>