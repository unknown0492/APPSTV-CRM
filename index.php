<?php 
    include( "./includes/header.php" );
?>
<!DOCTYPE html>
<!--[if IE 8]> <html lang="en" class="ie8 no-js"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9 no-js"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
    <!--<![endif]-->
    <!-- BEGIN HEAD -->
    <head>
        
	<?php
            include( './includes/meta-tags.php' );
	?>
	
	<!-- Link tags starts here -->
	<?php
            include( './includes/css.php' );
	?>
	<!-- Link tags ends here  -->
	
	
	<?php include './includes/header-scripts.php'; ?>
        
    </head>

    <body class="page-container-bg-solid page-header-fixed page-md">
		
        

        <?php
            include( $current_page_name . '.php' );
        ?>

        
    </body>
</html>