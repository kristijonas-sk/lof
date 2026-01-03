<?php
function OLF_LoadLanguage( $file )
{
	// Allowed languages lists:
	$IncludedLang = Array( "LT", "EN" );
	$Language = "LT"; // Default language.
	if( !empty( $_COOKIE['lang'] ) )
	{
		if( in_array( $Language, $IncludedLang ) )
			$Language = $_COOKIE['lang'];
	}
	
	$LangFile = "lang/".$file."-".strtolower( $Language ).".php";
	if( file_exists( $LangFile ) )
	{
		$trn = include $LangFile;
		return $trn;
	}
	// echo( "Can't open language file." );
	return FALSE;
}

function Translate( $Text, $Trn )
{
	if( $Trn == FALSE )
		return $Text;
	if( array_key_exists( $Text, $Trn ) )
		return $Trn[ $Text ];
	return $Text;
}

// Return array with logged user rigts, name, color or FALSE, if not logged.
function OLF_LoggedUserInfo( $DBlink )
{

	if( isset( $_COOKIE['troll_key'] ) == FALSE )
		return FALSE;
	if( $DBlink == FALSE )
		return FALSE;
	
	// Extracting session id from cookie:
	$session_id = substr( $_COOKIE['troll_key'], 0, 10 );
	if( ! ctype_digit( $session_id ) ) return FALSE;
	// Extracting random string from cookie:
	$RandomString = substr( $_COOKIE['troll_key'], 10 );
	// Checking if there is session entry in database: 
	$e_RandomString = mysqli_real_escape_string( $DBlink, $RandomString );

	$SQL = "SELECT session_id, loginame, login_cookie, UNIX_TIMESTAMP(time_expire) AS unix_time_expire, INET6_NTOA(initial_ip) AS initial_ip FROM sessions WHERE session_id = $session_id AND login_cookie = '$e_RandomString'";
	$Rez = mysqli_query( $DBlink, $SQL );
	
	
	if( ! $Rez )
		return FALSE;
	if( mysqli_num_rows( $Rez ) != 1 )
	{
		mysqli_free_result( $Rez );
		return FALSE;
	}
	$FromSessions = mysqli_fetch_assoc( $Rez );
	mysqli_free_result( $Rez );
	
	// Checking time:
	if( $FromSessions['unix_time_expire'] < time()  )
		return FALSE;
	
	// Checking what are in user table:
	$AdditionalUserFields = "";
	if( !empty( OLF_LOAD_FROM_USERS ) ) $AdditionalUserFields = ",".OLF_LOAD_FROM_USERS;
		
	$e_User = mysqli_real_escape_string( $DBlink, $FromSessions['loginame'] );
	$SQL = "SELECT loginame, greeting, favorit_color, user_rigts, account_active, INET6_NTOA(user_ip) AS user_ip, only_user_ip, lock_to_ip $AdditionalUserFields FROM users WHERE loginame = '$e_User' AND account_active=1";
	$Rez = mysqli_query( $DBlink, $SQL );
	
	if( ! $Rez )
		return FALSE;
	if( mysqli_num_rows( $Rez ) != 1 )
	{
		mysqli_free_result( $Rez );
		return FALSE;
	}
	
	$FromUsers = mysqli_fetch_assoc( $Rez );
	mysqli_free_result( $Rez );
	
	$CurrentUserIP =  OLF_GetUserIP();
	
	// Session locked to ip, so it must be same as was when loged in:
	if( $FromUsers['lock_to_ip'] )
	{
		// echo("1: ".$FromSessions['initial_ip'] ."<br>" );
		// echo("2: ".$CurrentUserIP ."<br>");
		// exit();
		
		if( empty( $FromSessions['initial_ip'] ) )
			return FALSE;
		if( $FromSessions['initial_ip'] != $CurrentUserIP )
			return FALSE;
	}
	// If only allowed from predefined ip:
	if( $FromUsers['only_user_ip'] )
	{
		if( empty( $FromUsers['user_ip'] ) )
			return FALSE;
		if( $FromUsers['user_ip'] != $CurrentUserIP )
			return FALSE;
	}
	//$UserInfo = Array();
	//$UserInfo['loginame'] = $FromUsers['loginame'];
	//$UserInfo['greeting'] = $FromUsers['greeting'];
	//$UserInfo['user_rigts'] = $FromUsers['user_rigts'];
	//$UserInfo['favorit_color'] = $FromUsers['favorit_color'];
	//$UserInfo['unix_time_expire'] = $FromSessions['unix_time_expire']; // It needed for additional cookies setting.
	
	// All columns selected from „users“ table  + unix_time_expire from table „sessions“ returned:
	$FromUsers[ 'unix_time_expire' ] = $FromSessions['unix_time_expire']; // It needed for additional cookies setting.
	return $FromUsers;
	// return TRUE;
}

function OLF_GetUserIP()
{
	if( !empty( $_SERVER['HTTP_CLIENT_IP'] ) ) return $_SERVER['HTTP_CLIENT_IP'];
	return $_SERVER['REMOTE_ADDR'];
}

function OLF_GoToFile( $FileName, $Absolute )
{
	$host = $_SERVER['HTTP_HOST'];
	$uri = rtrim( dirname( $_SERVER['PHP_SELF'] ),'/\\');
	$extra = $FileName;
	
	if( $Absolute == TRUE )
		header( 'Location: https://'.$host.OLF_PATH.$extra );
	else
		header('Location: https://'.$host.$uri.'/'.$extra);
	exit;
}

function OLF_ConnectToDB( $AutoRedirect )
{
	$DBlink = mysqli_connect( OLF_SQL_SERVER, OLF_SQL_USER, OLF_SQL_PASSWORD, OLF_SQL_DB );
	// If impossible to connect to db, than redirecting to error page:
	if( $DBlink == FALSE )
	{
		if( $AutoRedirect === TRUE) OLF_GoToFile( OLF_NO_DB, TRUE );
		return FALSE;
	}
	return $DBlink;
}

// Verify that user is logged in.
// Verify that user have rights to access.
// If error and $AutoRedirect==TRUE – redirect to apropriate page.
// If success – return $UserInfo, if failed (and no autoredirect) - FALSE.
function OLF_VerifyUser( $DBlink, $AllowedUsers, $AutoRedirect )
{

	//global $DBlink, $UserInfo;
	// $DBlink = mysqli_connect( OLF_SQL_SERVER, OLF_SQL_USER, OLF_SQL_PASSWORD, OLF_SQL_DB );
	// If impossible to connect to db, than redirecting to error page:
	// if( $DBlink == FALSE )
	// {
	//	if( $AutoRedirect === TRUE) OLF_GoToFile( OLF_NO_DB, TRUE );
	//	return FALSE;
	// }
	
	// Checking if user logged in, and store his info:
	$UserInfo = OLF_LoggedUserInfo( $DBlink );
	if( $UserInfo === FALSE )
	{
		mysqli_close( $DBlink );
		if( $AutoRedirect === TRUE ) OLF_GoToFile( 'login.php', TRUE );
		return FALSE;
	}
	
	// Checking if currently logged user have rights to open this form:
	if( empty( $AllowedUsers ) )
	{
		mysqli_close( $DBlink );
		if( $AutoRedirect === TRUE )  OLF_GoToFile( OLF_WRONG_RIGHTS, TRUE );
		return FALSE;
	}
	$UserRightsArray = explode( ',', $AllowedUsers );
	// print_r( $UserRightsArray );
	// echo("\n\na\n\n");
	// print_r( $UserInfo['user_rigts'] );
	// exit();
	if( ! in_array( $UserInfo['user_rigts'], $UserRightsArray ) )
	{
		mysqli_close( $DBlink );
		if( $AutoRedirect === TRUE ) OLF_GoToFile( OLF_WRONG_RIGHTS );
		return FALSE;
	}
	return $UserInfo;
}
?>
