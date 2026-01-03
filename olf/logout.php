<?php
require("functions.php");
require("settings.php");


// $Errors = "";

main();

function main()
{
	$DBlink = mysqli_connect( OLF_SQL_SERVER, OLF_SQL_USER, OLF_SQL_PASSWORD, OLF_SQL_DB );

	// If isn't possbile to connect to DB, cookie still can be unset.
	TryLogOut( $DBlink );
	
	if( $DBlink != FALSE ) mysqli_close( $DBlink );
	
	OLF_GoToFile( "login.php", FALSE );
}
function TryLogOut( $DBlink )
{
	// No cookie no session id so can't log out anything:
	if( ! isset( $_COOKIE['troll_key'] )  )
		return;
	// Copying what is in cookie:
	$WhatsInCookie = $_COOKIE['troll_key'];
	// Unsetting cookie:
	setcookie( 'troll_key', "", [
	'expires' => 0,
	'path' => '/',
	'secure' => true, // 
	'httponly' => true,
	'samesite' => 'Strict',
	]);
	
	if( ! $DBlink )
		return;
	
	// Extracting session id from cookie:
	$session_id = substr( $WhatsInCookie, 0, 10 );
	if( ! ctype_digit( $session_id ) ) return;
	// Extracting random string from cookie:
	$RandomString = substr( $WhatsInCookie, 10 );
	// Updating session entry in database: 
	$e_RandomString = mysqli_real_escape_string( $DBlink, $RandomString );

	// $SQL = "SELECT session_id, loginame, login_cookie, UNIX_TIMESTAMP(time_expire) AS unix_time_expire, INET6_NTOA(initial_ip) AS initial_ip FROM sessions WHERE session_id = $session_id AND login_cookie = '$e_RandomString'";
	$SQL = "UPDATE sessions SET login_cookie = '' WHERE session_id = $session_id AND login_cookie = '$e_RandomString' ";
	$Rez = mysqli_query( $DBlink, $SQL );
	
	if( ! $Rez )
		return;
	
	// If there is cookie with aditional settings, it also unseted:
	if( isset( $_COOKIE['troll_cookie'] )  )
	{
		// Unsetting cookie:
		setcookie( 'troll_cookie', "", [
		'expires' => 0,
		'path' => '/',
		'secure' => true, // 
		'httponly' => true,
		'samesite' => 'Strict',
		]);
	}
}

?>
