<?php 
	
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js sidebar-large lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js sidebar-large lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js sidebar-large lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!-->
<html class="sidebar-large">
<!--<![endif]-->

<head>
<!-- BEGIN META SECTION -->
<meta charset="utf-8">

<title>New User Created</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<meta content="" name="description" />
<meta content="themes-lab" name="author" />
<!-- END META SECTION -->


</head>

<body>
	
	
	<div style="width: 400px; padding: 20px; padding-left: 0px;
		border-top-left-radius: 10px; border-top-right-radius: 10px;
		-moz-border-top-left-radius: 10px; -moz-border-top-right-radius: 10px;
		-webkit-border-top-left-radius: 10px; -webkit-border-top-right-radius: 10px;">
		
                    <img src="https://static.myappstv.com/img/appstv-email-logo-dark.png" />
		
	</div>
	
	<div style="font-family: Calibri;">
		<span style="font-size: 22px;">Congratulations</span> <br /><br />
		<span style="font-size: 17px;">Your account has been created !
		<br/><br/>
		
		You can proceed to Login using the credentials mentioned below. Please remember your User ID and Password and delete this email, so as to
		prevent your password from getting into the wrong hands.</span>
		
		<br /><br />
		
		<table style="border-collapse: collapse; ">
			<tr>
				<th style="border: 1px solid grey;padding: 10px; text-align: left;">URL for the Portal </th>
				<td style="border: 1px solid grey;padding: 10px; text-align: left;">{{url_portal}}</td>
			</tr>
			<tr>
				<th style="border: 1px solid grey;padding: 10px; text-align: left;">User ID</th>
				<td style="border: 1px solid grey;padding: 10px; text-align: left;">{{user_id}}</td>
			</tr>
			<tr>
				<th style="border: 1px solid grey;padding: 10px; text-align: left;">Password</th>
				<td style="border: 1px solid grey;padding: 10px; text-align: left;">{{password}}</td>
			</tr>
			
		</table>
		
		<p style="font-family: Calibri; text-align: center; font-size: 15px; padding: 25px; color: grey;">Warning : This email contains confidential information, if you are not the authorized receipient of this email, please ignore and delete this email.</td>
		</p>
	</div>

</body>
</html>