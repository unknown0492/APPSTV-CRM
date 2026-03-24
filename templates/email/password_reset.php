<?php 
	
?>
<!DOCTYPE html>
<html class="sidebar-large">

<head>
<!-- BEGIN META SECTION -->
<meta charset="utf-8">

<title>Password Reset</title>

<meta name="viewport" content="width=device-width, initial-scale=1">
<meta content="" name="description" />
<meta content="themes-lab" name="author" />
<!-- END META SECTION -->


</head>

<?php

	// $password_reset_url = @$_GET[ 'password_reset_url' ];
	// $password_reset_code = @$_GET[ 'password_reset_code' ];
	
?>



<body>
	<center>
	<div style="width: 400px; padding: 20px; padding-left: 0px;
		border-top-left-radius: 10px; border-top-right-radius: 10px;
		-moz-border-top-left-radius: 10px; -moz-border-top-right-radius: 10px;
		-webkit-border-top-left-radius: 10px; -webkit-border-top-right-radius: 10px;">
		
                    <img src="https://static.myappstv.com/img/appstv-email-logo-dark.png" />
		
	</div>
	<div style="font-family: Calibri;">
		<table border="0" style="border-collapse: collapse; width: 400px; ">
			<tr>
				<td style="font-family: Calibri; background-color: #80d2f7; text-align: center; font-size: 25px; padding: 25px;
					border-radius: 40px 40px 0px 0px; -moz-border-radius: 40px 40px 0px 0px; -webkit-border-radius: 40px 40px 0px 0px;">Password Reset</td>
			</tr>
			<tr>
				<td style="font-family: Calibri; text-align: center; font-size: 17px; padding: 25px;
				border-right: 1px solid #80d2f7; border-left: 1px solid #80d2f7;">Click on the following button to reset the password. If the following
				link is not clickable, copy and paste the below URL in your browser</td>
			</tr>
			<tr>
				<td style="text-align: center; font-size: 20px; padding: 25px;
					border-right: 1px solid #80d2f7; border-left: 1px solid #80d2f7;">
					<a href="{{password_reset_url}}" style="background-color: #7a204e; 
					color: white; text-decoration: none; padding: 10px 20px 10px 20px; 
					box-shadow: 3px 3px 3px grey; -webkit-box-shadow: 3px 3px 3px grey; -moz-box-shadow: 3px 3px 3px grey; 
					border-radius: 20px; -moz-border-radius: 20px; -webkit-border-radius: 20px; ">
					Reset Password</a>
				</td>
			</tr>
			<tr>
				<td style="border-bottom: 1px solid #80d2f7; text-align: center; font-size: 11px; padding: 25px; font-family: 'Courier New';
				border-right: 1px solid #80d2f7; border-left: 1px solid #80d2f7;">{{password_reset_url_html}}</td>
			</tr>
			<tr>
				<td style="font-family: Calibri; text-align: center; font-size: 15px; padding: 25px; color: grey;">Warning : This email contains confidential information, if you are not the authorized receipient of this email, please ignore and delete this email.</td>
			</tr>
		</table>
		
	</div>
	</center>
</body>
</html>