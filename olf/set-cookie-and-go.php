<?php
// Set cookie with settings used for forms initialisation, and redirect to beginning.
require("functions.php");

$UserInfo = Array();
$DBlink;
tr_SetUp( "1,2,3,4,5,6,7" );
main( $DBlink );
mysqli_close( $DBlink );
tr_GoToFile( "welcome.php" );

function main( $DBlink )
{
	global $UserInfo;
	$SQL = "SELECT darbuotojas, miestas FROM darbuotojai WHERE slapyvardis = '".mysqli_real_escape_string( $DBlink, $UserInfo['loginame'] )."'";
	$Rez = mysqli_query( $DBlink, $SQL );
	if( $Rez === FALSE )
		return FALSE;
	
	$Columns = mysqli_fetch_assoc( $Rez );
	mysqli_free_result( $Rez );
	
	if( empty( $Columns['darbuotojas'] ) )
	{
		$Columns['darbuotojas'] = $UserInfo['loginame']; // Jei userio nėra darbuotojų lentelėje, tuomet įrašoma userio vardas vietoj darbutojo vardo.
		// Kažka reikia įrašyti į miestą, nes ir jis imamas iš tos pat lentelės?
	}
	
	// Setting cookie:
	$WhatsInCookie = "miestas|".$Columns['miestas']."|darbuotojas|".$Columns['darbuotojas'];
	setcookie( 'troll_cookie', $WhatsInCookie, [
	'expires' => $UserInfo['unix_time_expire'],
	'path' => '/',
	'secure' => true, // 
	'httponly' => true,
	'samesite' => 'Strict',
	]);
	
}
?>
