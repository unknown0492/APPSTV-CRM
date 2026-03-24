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

<title>Account Activation</title>

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
            <span style="font-size: 22px;">Email Verification</span> <br /><br />
            <span style="font-size: 17px;">Please verify your email to link it with your account
            <br/><br/>

            Click on the link given below to confirm that you are the owner of this email and that you are the actual person who has initiated update of your email</span>

            <br /><br />

            <table style="border-collapse: collapse; ">
                <tr>
                    <th style="border: 1px solid grey;padding: 10px; text-align: left;">User ID</th>
                    <td style="border: 1px solid grey;padding: 10px; text-align: left;">{{user_id}}</td>
                </tr>
                <tr>
                    <th style="border: 1px solid grey;padding: 10px; text-align: left;">Verification Link</th>
                    <td style="border: 1px solid grey;padding: 10px; text-align: left;"><a href="{{verification_link}}" target="_blank">Click here to verify</a></td>
                </tr>			
            </table>

            <p style="font-family: Calibri; text-align: center; font-size: 15px; padding: 25px; color: grey;">Warning : This email contains confidential information, if you are not the authorized recipient of this email, please ignore and delete this email.</td>
            </p>
	</div>

</body>
</html>