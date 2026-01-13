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
	
	$txt = '<table>';
	
	$txt .= '
	<tr>
	<th>'.Translate('Table name', $trn ).'</th>
	<th>'.Translate('Numer of records', $trn ).'</th>
	<th>'.Translate('Actions', $trn ).'</th>
	<th>'.Translate('Comment', $trn ).'</th>
	</tr>
	';

	// Reading all db tables names:
	$Rez = mysqli_query( $DBlink, 'SHOW TABLES' );
	
	// $txt .= Translate('Database tables', $trn ).':<br>';

	$n = 0;
	while( $CurrentRow = mysqli_fetch_array( $Rez, MYSQLI_NUM ) )
	{
		$CurrentTableName = $CurrentRow[0];
		
		// How much entries table have:
		$Rez2 = mysqli_query( $DBlink, 'SELECT COUNT(*) AS HowMuchEntries FROM '.$CurrentTableName );
		$CurrentN = mysqli_fetch_array( $Rez2 )['HowMuchEntries'];
		mysqli_free_result( $Rez2 );
		
		// What is table comment:
		$SQL = "
		SELECT TABLE_COMMENT
		FROM information_schema.TABLES
		WHERE TABLE_SCHEMA = '".OLF_SQL_DB."'
		AND TABLE_NAME = '".$CurrentTableName."';
		";
		$Rez2 = mysqli_query( $DBlink, $SQL );
		$CurrentComment = mysqli_fetch_array( $Rez2 )[0];
		mysqli_free_result( $Rez2 );
		
		$txt .= "<tr>";
		$txt .= "<td><a href=\"db_table_info.php?table=".urlencode($CurrentTableName)."\">$CurrentTableName</a></b></td>";
		$txt .= "<td>$CurrentN</td>";
		$txt .= '<td><a href="db_table_info.php?table='.urlencode($CurrentTableName).'">'.Translate('Structure', $trn ).'</a> <a href="">'.Translate('Records', $trn ).'</a></td>';
		$txt .= "<td>". htmlspecialchars( $CurrentComment, ENT_QUOTES ) ."</td>";
		$txt .= "</tr>";
		$n++;
	}
	$txt .= "</table>";
	mysqli_free_result( $Rez );
	
	/// $txt2 = '<p>'. Translate('Database', $trn ) .': <b>'.OLF_SQL_DB.'</b></p>';
	/// $txt2 .= '<p>'.Translate('Database tables', $trn )." ($n):<br></p>";
	
	$txt2 = '<p>[ <b>'.OLF_SQL_DB.'</b> ] tables ('.$n.'):</p>';
	$txt2 .= $txt;
	return $txt2;
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
