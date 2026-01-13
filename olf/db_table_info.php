<?php
require("functions.php");
require("settings.php");


// $Errors = "";
$trn = OLF_LoadLanguage( "db_table_info" );

$DBlink = OLF_ConnectToDB( TRUE );
$UserInfo = OLF_VerifyUser( $DBlink, "1", TRUE );


$html_buffer = TableInfo( $DBlink );

mysqli_close( $DBlink );


function TableInfo( $DBlink )
{
	global $trn;
	$txt = '';
	$txt .= '<p>'. Translate('Database name', $trn ) .':<br><b><a href="db_tables.php">'.OLF_SQL_DB.'</b></a></p>';
	$txt .= '<p>'. Translate('Table name', $trn ) .':<br><b>'.$_GET['table'].'</b></p>';

	$Rez = mysqli_query( $DBlink, 'SHOW TABLES' ); //Reading all db tables names.
	
	
	$txt .= '<p>'.Translate('Table structure', $trn ).':</p>';
/*
	while( $CurrentRow = mysqli_fetch_array( $Rez, MYSQLI_NUM ) )
	{
		$CurrentTableName = $CurrentRow[0];

		$Rez2 = mysqli_query( $DBlink, 'SELECT COUNT(*) AS HowMuchEntries FROM '.$CurrentRow[0] );
		$CurrentN = mysqli_fetch_array( $Rez2 )['HowMuchEntries'];
		mysqli_free_result( $Rez2 );

		$txt .= '<p>';
		$txt .= "<b>$CurrentTableName</b> ($CurrentN)";
		//TODO: show list of table columns
		$txt .= '</p>';
	}
	
	mysqli_free_result( $Rez );
	*/
	return $txt;
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
	<title><?= Translate('Table info', $trn ) ?></title>
</head>
<body>

<?php include("meniu.php"); ?>

<div class="content_box">

	<h1><?= Translate('Table info', $trn ) ?></h1>
	<?= $html_buffer; ?>
</div>

</body>
</html>
