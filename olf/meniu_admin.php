<?php
require("functions.php");
require("settings.php");


$Errors = "";
$trn = OLF_LoadLanguage( "meniu_admin-en.php" );

/*
$UserInfo = Array();
$DBlink;
tr_SetUp( "1" );
mysqli_close( $DBlink );
*/

$DBlink = OLF_ConnectToDB( TRUE );
$UserInfo = OLF_VerifyUser( $DBlink, "1", TRUE );
mysqli_close( $DBlink );


?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="" /> 
	<meta name="keywords" content="" />
	<link rel="stylesheet" type="text/css" href="styles.css" />
	<link rel="shortcut icon" type="image/png" href="img/piktograma.png" />
	<style>
	</style>
	<title><?= Translate('Administration Meniu', $trn ) ?></title>
</head>
<body onload="Start()">

<?php include("meniu.php"); ?>

<div class="content_box">
	<h1><?= Translate('Administration Meniu', $trn ) ?></h1>
	<hr>
	<p class="submeniu_item"><a href=""><?= Translate('Users List', $trn ) ?></a></p>
	<p class="submeniu_item"><a href="db_tables.php"><?= Translate('Database tables', $trn ) ?></a></p>
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
