<?php
require("functions.php");
require("settings.php");

$Errors = "";
$Success = "";

$trn = OLF_LoadLanguage( "password" );

$DBlink = OLF_ConnectToDB( TRUE );
$UserInfo = OLF_VerifyUser( $DBlink, "1,2,3,4,5,6,7", TRUE );

if( ! empty( $_POST['password_current'] ) ) main( $DBlink );
mysqli_close( $DBlink );


function ReportError( $Error )
{
	global $trn;
	global $Errors;
	$Errors .= Translate( $Error, $trn )."<br>";
	return FALSE;
	
}

function main( $DBlink )
{
	global $UserInfo, $Success, $Errors;
	
	
	if( empty( $_POST['password_current'] ) ) return ReportError( "Current password field is empty" );
	if( empty( $_POST['password_new1'] ) ) return ReportError( "New password field is empty" );
	if( empty( $_POST['password_new2'] ) ) return ReportError( "New password repetition field is empty" );

	if(  $_POST['password_new1'] != $_POST['password_new2']  ) return ReportError( "New password repetition is different" );
	if(  $_POST['password_new1'] == $_POST['password_current']  ) return ReportError( "New password can't be same as old" );

	if(  strlen( $_POST['password_new1'] ) < 8  ) return ReportError( "New password is to short (must be 8 symbols at least)" );
	if(  ctype_alnum( $_POST['password_new1'] ) == TRUE  ) return ReportError( "New password must contain at least one special symbol" );
	
	// Trying to verify current password:
	if( empty( $UserInfo['loginame'] ) ) return ReportError( "Unable to get currently loged user" );
	$SQL = "SELECT loginame, pas_hash, account_active FROM users WHERE loginame = '".mysqli_real_escape_string( $DBlink, $UserInfo['loginame'])."'";
	$Rez = mysqli_query( $DBlink, $SQL );
	
	// Check if user exist at all:
	if( ! $Rez ) return ReportError( "Unable to change password (1)" );
	
	if( mysqli_num_rows( $Rez ) != 1 )
	{
		// User don't exist.
		mysqli_free_result( $Rez );
		return ReportError( "Unable to change password (2)" );
	}
	$RowFromDB = mysqli_fetch_assoc( $Rez );
	mysqli_free_result( $Rez );
	// Checking if account is active (just in case):
	if( ! $RowFromDB['account_active'] )
	{
		// Account is deactivated currently.
		return ReportError( "Unable to change password (account is inactive)" );
	}
	
	// Trying to verify old password:
	if( ! password_verify( $_POST['password_current'], $RowFromDB['pas_hash'] ) )
		return ReportError( "Wrong old password" );
	
	$PasswordHash = password_hash( $_POST['password_new1'], PASSWORD_DEFAULT );
	$PasswordHash = mysqli_real_escape_string( $DBlink, $PasswordHash );
	$SQL = "UPDATE users SET pas_hash = '$PasswordHash' WHERE loginame = '".mysqli_real_escape_string( $DBlink, $UserInfo['loginame'])."'";

	$Rez = mysqli_query( $DBlink, $SQL );
	
	if( $Rez === FALSE ) return ReportError( "Password change failed (database write error)" );
	
	
	$Success = "Slaptažodis pakeistas sėkmingai";
	return TRUE;
}
	


?>

<!DOCTYPE html> 
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
	<title><?= Translate( "Password change", $trn ) ?></title>
</head>
<body>

<?php include("meniu.php"); ?>

<div class="content_box">

	<h1><?= Translate( "Password change", $trn ) ?></h1>
	<?php
	if( strlen( $Errors ) > 0 )
	{
		echo('<p class="error">'.$Errors.'</p>');
	}
	if( strlen( $Success ) > 0 )
	{
		echo('<p class="success">'.$Success.'</p>');
	}

	?>
	<p><?= Translate( "New password must have 8 symbols at least", $trn ) ?>.<br>
	<?= Translate( "New password must have at least one special symbol", $trn ) ?> „+-/!?.[]{}@#$%&*=,“.</p>

	<form method="post" action="password.php" class="type1">

		<label for="username" class="type1"><?= Translate( "User name", $trn ) ?></label><br>
		<input type="text" class="type1" name="username" id="username" autocomplete="username" readonly value="<?php echo htmlspecialchars( $UserInfo['loginame'], ENT_COMPAT|ENT_HTML5|ENT_SUBSTITUTE,'UTF-8' );  ?>"><br><br>
		<label for="password_current" class="type1"><?= Translate( "Current password", $trn ) ?></label><br>
		<input type="password" class="type1" name="password_current" id="password_current" required autocomplete="current-password">
		<br><br>
		
		<label for="password_new1" class="type1"><?= Translate( "New password", $trn ) ?></label><br>
		<input type="password" class="type1" name="password_new1" id="password_new1" required autocomplete="new-password">
		<br><br>
		
		<label for="password_new2" class="type1"><?= Translate( "New password again", $trn ) ?></label><br>
		<input type="password" class="type1" name="password_new2" id="password_new2" required autocomplete="new-password">
		<br><br>
		
		<div style="text-align:center">
		<button type="submit"><?= Translate( "Change", $trn ) ?></button>
		</div>
		<br><br>
	</form>

</div>

</body>
</html>
