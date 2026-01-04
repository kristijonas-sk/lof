<?php
require("functions.php");
require("settings.php");

$Errors = "";
$trn = OLF_LoadLanguage( "login" );

main();

// Jei per POST perduota trijų laukelių reikšmės, gražina TRUE, jei bent viena neperduota, gražina FALSE.
function AllPostIsSet()
{
	if( isset( $_POST['username'] ) == FALSE || isset( $_POST['slaptazodis'] ) == FALSE || isset( $_POST['login_time'] ) == FALSE )
		return FALSE;
	return TRUE;
}

function LogInError( $Error )
{
	global $Errors;
	$Errors .= $Error;
	return FALSE;
}

// Check everything. If all ok, than and set session cookie and add session entry to DB and return TRUE.
function TryLogIn( $DBlink, $User, $Password, $Time )
{
	global $trn;
	global $Errors;
	// Taking information about user that trying to log in:	
	// WHERE BINARY loginame = for case sensitive comparison.
	$SQL = "SELECT loginame, pas_hash, account_active, INET6_NTOA(user_ip) AS user_ip, only_user_ip, lock_to_ip, failed_attempts, UNIX_TIMESTAMP(last_attempt) AS last_attempt_unix FROM users WHERE loginame = '".mysqli_real_escape_string( $DBlink, $User)."'";
	$Rez = mysqli_query( $DBlink, $SQL );
	
	// Check if user exist at all:
	if( ! $Rez )
	{
		return LogInError( ['Login failed']." (1).<br>" );
	}
	if( mysqli_num_rows( $Rez ) < 1 )
	{
		// User don't exist.
		mysqli_free_result( $Rez );
		return LogInError( Translate('Incorrect user name or password',$trn).".<br>" );
	}
	if( mysqli_num_rows( $Rez ) > 1 )
	{
		// User don't exist.
		mysqli_free_result( $Rez );
		return LogInError( Translate('Login failed',$trn)." (2).<br>" );
	}
	$RowFromDB = mysqli_fetch_assoc( $Rez );
	mysqli_free_result( $Rez );
	// Checking if account is active:
	if( ! $RowFromDB['account_active'] )
	{
		// Account is deactivated currently.
		return LogInError( Translate('Login failed',$trn)." (3).<br>" );
	}
	// Checking how much failed atempt was before:
	if( $RowFromDB['failed_attempts'] > 5 )
	{
		// To much incorrect attempts. Blocking for 15 min.
		if( (time() - $RowFromDB['last_attempt_unix']) < 60*15 )
		{
			
			return LogInError( Translate('Too much incorrect attempts',$trn).".<br>" );
		}
		else
		{
			// Clearing count since required time already passed:
			// Setting to 2, so 3 left instead 5:
			$SQL = "UPDATE users SET failed_attempts = 2, last_attempt = FROM_UNIXTIME(".time().") WHERE loginame = '".mysqli_real_escape_string( $DBlink, $User)."'";
			mysqli_query( $DBlink, $SQL );
			$RowFromDB['failed_attempts'] = 2;
		}
	}
	// Trying to verify password:	
	if( ! password_verify( $Password, $RowFromDB['pas_hash'] ) )
	{
		// Password is incorrect. Logging failed attempt:
		$SQL = "UPDATE users SET failed_attempts = ".($RowFromDB['failed_attempts'] + 1).", last_attempt = FROM_UNIXTIME(".time().") WHERE loginame = '".mysqli_real_escape_string( $DBlink, $User)."'";
		mysqli_query( $DBlink, $SQL );
		return LogInError( Translate('Incorrect user name or password',$trn).".<br>" );
	}
	
	// Password is correct.
	// Confirming ip if needed:
	$UserIP = OLF_GetUserIP();
	if( $RowFromDB['only_user_ip'] )
	{
		if( empty( $UserIP ) )
			return LogInError( Translate('Login failed',$trn)." (4).<br>" ); // Can't confirm client IP
		if( empty( $RowFromDB['user_ip'] ) )
			return LogInError( Translate('Login failed',$trn)." (5).<br>" ); // Allowed client IP not set.
		if( $UserIP != $RowFromDB['user_ip'] )
			return LogInError( Translate('Login failed',$trn)." (6).<br>" ); // IP is not allowed.
	}
	
	// Password and IP correct. Reseting failed_attempts count:
	$SQL = "UPDATE users SET failed_attempts = 0, last_attempt = FROM_UNIXTIME(".time().") WHERE loginame = '".mysqli_real_escape_string( $DBlink, $User)."'";
	mysqli_query( $DBlink, $SQL );
	
	// Calculating expiration date:
	if( ! ctype_digit($Time) ) $Time = 0; // Just in case.
	$ExpTimeDB = $Time + time();
	$ExpTimeCookie = $ExpTimeDB;
	
	// If user select „until windows close“
	if( $Time == 0 )
	{
		$ExpTimeDB = time() + 12 * 60 * 60; // DB hold that token is valid for 12 hours.
		$ExpTimeCookie = 0;
	}
	
	// Generating random session number:
	$RandomString = bin2hex( random_bytes(42) );
	
	// Preapering field for DB insertion:
	if( isset( $_SERVER['HTTP_USER_AGENT'] ) ) $e_UserAgent = mysqli_real_escape_string( $DBlink, $_SERVER['HTTP_USER_AGENT'] );
	else $e_UserAgent = "";
	$e_User = mysqli_real_escape_string( $DBlink, $User);
	$e_RandomString = mysqli_real_escape_string( $DBlink, $RandomString );
	$e_UserIP = mysqli_real_escape_string( $DBlink, $UserIP );

	// Inserting new session entry into DB:
	$SQL = "INSERT INTO sessions( loginame, time_expire, login_cookie, initial_ip, user_agent ) VALUES( '$e_User', FROM_UNIXTIME($ExpTimeDB), '$e_RandomString', (INET6_ATON('$e_UserIP')), '$e_UserAgent' );";
	// echo( $SQL );
	if( mysqli_query( $DBlink, $SQL ) === FALSE )
		return LogInError( Translate('Login failed',$trn)." (".Translate('Failed to add session to database',$trn).").<br>" ); // Unable to add session to db.
	
	// Retrieving last session id to store it to cookie:
	$SQL = "SELECT LAST_INSERT_ID() AS last" ;
	$Rez = mysqli_query( $DBlink, $SQL );
	if( $Rez === FALSE )
		return LogInError( Translate('Login failed',$trn)." (7).<br>" ); // Unable to get last session id from database.
	$temp = mysqli_fetch_array( $Rez );
	mysqli_free_result( $Rez );
	$PaddedSessionID = sprintf( '%010d', $temp['last'] );
	$WhatsInCookie = $PaddedSessionID.$RandomString;
	
	// Setting cookie:
	setcookie( 'troll_key', $WhatsInCookie, [
	'expires' => $ExpTimeCookie,
	'path' => '/',
	'secure' => true,
	'httponly' => true,
	'samesite' => 'Strict',
	]);
	
	return TRUE;
}

function main()
{
	global $trn;
	$DBlink = mysqli_connect( OLF_SQL_SERVER, OLF_SQL_USER, OLF_SQL_PASSWORD, OLF_SQL_DB );
	if( $DBlink == FALSE )
		return LogInError( Translate('Failed to connect to the database',$trn).'.<br>' );

	if( AllPostIsSet() )
	{
		if( TryLogIn( $DBlink, $_POST['username'], $_POST['slaptazodis'], $_POST['login_time'] ) )
		{
			// If login succesful, than redirecting:
			mysqli_close( $DBlink );
			OLF_GoToFile( "welcome.php", FALSE );
		}
	}
	else
	{
		// If already signed in, and no new POST values, than redirecting:
		// if( OLF_IsLogged( $DBlink ) )
		if( OLF_LoggedUserInfo( $DBlink ) !== FALSE )
		{
			mysqli_close( $DBlink );
			OLF_GoToFile( "welcome.php", FALSE );
		}
	}
	
	mysqli_close( $DBlink );
}

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
	input, select
	{
		width: 100%;
		box-sizing: border-box;
		margin:0.4rem;
	}
	label
	{
	padding-left:0.6rem;
	}
	</style>
	<title><?= Translate('User login',$trn) ?></title>
</head>
<body onload="Start()" style="margin-top:4rem;">

<div class="content_box">

	<h1><?php echo OLF_PROJECT_NAME;?></h1>
	<h2><?= Translate('User login',$trn) ?></h2>
	<?php
	if( strlen( $Errors ) > 0 )
	{
		echo('<p class="error">'.$Errors.'</p>');
	}
	?>
	<form method="post" action="login.php" style="text-align:left">

		<label for="username"><?= Translate('User',$trn) ?></label><br>
		<input type="text" name="username" id="username" value="" required autocomplete="username"><br><br>
		
		<label for="slaptazodis"><?= Translate('Password',$trn) ?></label><br>
		<input type="password" name="slaptazodis" id="slaptazodis" required autocomplete="current-password"><br><br>
		
		<label for="login_time"><?= Translate('Login duration',$trn) ?></label><br>
		<select name="login_time" id="login_time">
			<option value="0" selected><?= Translate('Until window closed',$trn) ?></option>
			<option value="60" >1 <?= Translate('minute',$trn) ?></option>
			<option value="900" >15 <?= Translate('minutes',$trn) ?></option>
			<option value="3600" >1 <?= Translate('hour',$trn) ?></option>
			<option value="86400" >24 <?= Translate('hours',$trn) ?></option>
			<option value="604800" >1 <?= Translate('week',$trn) ?></option>
			<option value="2562000" >30 <?= Translate('days',$trn) ?></option>
			<option value="31536000" >1 <?= Translate('year',$trn) ?></option>
			<option value="378432000" >12 <?= Translate('years',$trn) ?></option>
			<option value="7568640000" >1 <?= Translate('million years',$trn) ?></option>
		</select>
		
		<br>
		<br>
		
		<div style="text-align:center">
		<button type="submit"><?= Translate('login',$trn) ?></button>
		</div>
		
	</form>
	
	<div class="content_box_footer"></div>
</div> 

		<select name="lang_select" id="lang_select" onchange="OLF_SetLanguage()" style="position:absolute;right:8px;top:12px;font-size:1.8rem;border-radius:16px;padding:0.4rem;width:auto;">
			<option value="LT" <?php if( Translate('lang',$trn) == 'LT' ) echo " selected " ?>>LT</option>
			<option value="EN" <?php if( Translate('lang',$trn) == 'EN' ) echo " selected " ?>>EN</option>
		</select>

	<script src="script.js"></script>
	<script>
	function Start()
	{
		<?php
		$Nickname = "";
		$Time = "0";
		if( !empty( $_POST['username'] ) )	$Nickname = str_replace('`', '', $_POST['username'] );
		if( !empty( $_POST['login_time'] ) )	$Time = str_replace('`', '', $_POST['login_time'] );
		?>
		
		document.getElementById('username').value = `<?= $Nickname ?>`;
		document.getElementById('login_time').value = `<?= $Time ?>`;
	}
	</script>

</body>
</html>
