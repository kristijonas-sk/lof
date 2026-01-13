<?php
require("functions.php");
require("settings.php");


// $Errors = "";
$trn = OLF_LoadLanguage( "db_tables" );

$DBlink = OLF_ConnectToDB( TRUE );
$UserInfo = OLF_VerifyUser( $DBlink, "1", TRUE );


$html_buffer = DatabaseTables( $DBlink );

mysqli_close( $DBlink );


function DatabaseTables( $DBlink )
{
	global $trn;
	$txt = '';
	// $txt .= '<p>'. Translate('Database name', $trn ) .':<br><b>'.OLF_SQL_DB.'</b></p>';

	$Rez = mysqli_query( $DBlink, 'SHOW TABLES' ); //Reading all db tables names.
	
	// $txt .= Translate('Database tables', $trn ).':<br>';

	$n = 0;
	while( $CurrentRow = mysqli_fetch_array( $Rez, MYSQLI_NUM ) )
	{
		$CurrentTableName = $CurrentRow[0];

		$Rez2 = mysqli_query( $DBlink, 'SELECT COUNT(*) AS HowMuchEntries FROM '.$CurrentRow[0] );
		$CurrentN = mysqli_fetch_array( $Rez2 )['HowMuchEntries'];
		mysqli_free_result( $Rez2 );

		$txt .= "<b><a href=\"db_table_info.php?table=".urlencode($CurrentTableName)."\">$CurrentTableName</a></b> ($CurrentN); ";
		$n++;
	}
	
	mysqli_free_result( $Rez );
	
	return '<p>'.Translate('Database tables', $trn )." ($n):<br>$txt</p>";
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
	<title><?= Translate('Database tables', $trn ) ?></title>
</head>
<body>

<?php include("meniu.php"); ?>

<div class="content_box">

	<h1><?= Translate('Database tables', $trn ) ?></h1>
	<?= $html_buffer; ?>
</div>

</body>
</html>
