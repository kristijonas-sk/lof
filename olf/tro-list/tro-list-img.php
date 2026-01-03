<?php
require( "tro-list-class.php" );
require("troll_set.php");
require("troll_fu.php");
$DBlink;
//tr_SetUpA( "1,2,3,4", TRUE );
//var_dump( $_COOKIE['troll_key'] );
if( empty( $_GET['column'] ) )
	ExitWithError( "Column with imaga data not provided.\n" );
	
if( empty( $_GET['id'] ) )
	ExitWithError( "Row id is not provided.\n" );
	
if( empty( $_GET['content_type'] ) )
	ExitWithError( "Conent type is not provided.\n" );

if( 
$_GET['content_type'] != "image/jpeg" && 
$_GET['content_type'] != "image/png" && 
$_GET['content_type'] != "image/gif" && 
$_GET['content_type'] != "image/webp" && 
$_GET['content_type'] != "image/svg+xml" && 
$_GET['content_type'] != "image/avif" && 
$_GET['content_type'] != "image/tiff" && 
$_GET['content_type'] != "image/bmp"
)
	ExitWithError( "Conent type is invalid.\n" );

if( empty( $_GET['tablefile'] ) )
	ExitWithError( "Can't get file with table definition name.\n" );
	

$TableInfo = new class_tro_list();

if( $TableInfo -> LoadFromFile( $_GET['tablefile'] ) === FALSE )
	ExitWithError( $TableInfo -> LastError );

if( empty( $TableInfo -> TableInfo['AccessControll'] ) )
	ExitWithError( "Nenurodyta lentelės peržiūros leidimai." );
// echo $TableInfo -> TableInfo['AccessControll'];
if( tr_SetUpA( $TableInfo -> TableInfo['AccessControll'], FALSE ) === FALSE )
	ExitWithError( "Vartotojo prisijungimo klaida." );
	

//var_dump($_GET);
//exit;

function ExitWithError( $Error )
{
	// header('Content-type: image/png');
	//$img = imagecreatetruecolor(160,200);
	//imagepng($img);
	//imagedestroy($img);
	echo( $Error );
	exit;
}


$MySQL_Uzklausa='
SELECT '. mysqli_real_escape_string( $DBlink, $_GET['column'] ) .' AS image 
FROM '. $TableInfo -> TableInfo['DBmainTable'] .' 
WHERE '. $TableInfo -> TableInfo['DBidCol'] .'=\''.mysqli_real_escape_string($DBlink,$_GET['id']).'\'';
//echo($MySQL_Uzklausa.'<br />');
//exit;

$sqlrez = mysqli_query( $DBlink, $MySQL_Uzklausa );
if( $sqlrez == FALSE )
	ExitWithError();

$kas_rasta = mysqli_fetch_assoc($sqlrez);
mysqli_close( $DBlink );

if( empty( $kas_rasta[ 'image' ] ) )
	ExitWithError("Error loading image data from DB.");
	
Header("Content-type: ".$_GET['content_type'] );
echo( $kas_rasta[ 'image' ] );
exit;
?>
