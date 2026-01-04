<?php
require("functions.php");
require("settings.php");


$Errors = "";
$trn = OLF_LoadLanguage( "welcome" );

/*
$UserInfo = Array();
$DBlink;
tr_SetUp( "1" );
mysqli_close( $DBlink );
*/

$DBlink = OLF_ConnectToDB( TRUE );
$UserInfo = OLF_VerifyUser( $DBlink, "1,2,3,4,5,6,7", TRUE );
mysqli_close( $DBlink );


?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="" /> 
	<meta name="keywords" content="" />
	<link rel="stylesheet" type="text/css" href="styles.css" />
	<link rel="shortcut icon" type="image/png" href="img/ico.png" />
	<style>
	</style>
	<title><?= Translate('Welcome', $trn ) ?></title>
</head>
<body onload="Start()">

<?php include("meniu.php"); ?>

<div class="content_box">
	<h1><?php echo OLF_PROJECT_NAME;?></h1>
	<hr>
	<p style="font-size:1.6rem;text-align:center"><?= $UserInfo['greeting'] ?></p>
	<hr>
	🔑 <a href="password.php"><?= Translate('Change password',$trn) ?></a>
	<div class="content_box_footer">
	<div><?= Translate('Version', $trn ) ?> 2026-01-03</div>
	<div>
	<select name="lang_select" id="lang_select" style="font-size:0.8rem;border-radius:4px;padding:0rem;padding-left:0.2rem;" onchange="OLF_SetLanguage()">
	<option value="LT" <?php if( Translate('lang',$trn) == 'LT' ) echo " selected " ?>>LT</option>
	<option value="EN" <?php if( Translate('lang',$trn) == 'EN' ) echo " selected " ?>>EN</option>
	</select>
	</div>
	<div><a href="logout.php" style="text-decoration:none"><?= Translate('Log out', $trn ) ?> <img src="img/ico/logout.svg" style="width:1.2rem;vertical-align:top"></a></div>
	</div>
</div>



<script src="script.js"></script>

<script>
function Start()
{
}
</script>

</body>
</html>
