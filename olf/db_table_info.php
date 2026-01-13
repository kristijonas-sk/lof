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
	
	$TableNameForSQL =  mysqli_real_escape_string( $DBlink, $_GET['table'] );
	$TableNameForHTML = htmlspecialchars( $_GET['table'], ENT_QUOTES );
	
	$txt = '';
	
	$txt .= "<p>";
	$txt .= '[ <b><a href="db_tables.php">'.OLF_SQL_DB."</b></a> → $TableNameForHTML ] ".Translate('table structure', $trn ).":";
	$txt .= "</p>";
	
	// Checking if table exist at all:
	$SQL =
	"SELECT 1
	FROM information_schema.tables
	WHERE table_schema = DATABASE()
  	AND table_name = '$TableNameForSQL'";

	$Rez = mysqli_query( $DBlink, $SQL );
	if( mysqli_num_rows( $Rez ) === 0 )
	{
		$txt .= '<div class="error">';
		$txt .= Translate('Table does not exist.', $trn );
		$txt .= "</div>";
		$txt .= '<p><a href="db_tables.php">'.Translate('View existing tables.', $trn ).'</a></p>';
		return $txt;
	}
	
	$txt .= '<table>';
	$txt .= '
	<tr>
	<th>'.Translate('Colum name', $trn ).'</th>

	<th>'.Translate('Data type', $trn ).'</th>
	<th>'.Translate('Can be empty?', $trn ).'</th>
	<th>'.Translate('Default value', $trn ).'</th>

	<th>'.Translate('Key', $trn ).'</th>
	<th>'.Translate('Extra', $trn ).'</th>
	<th>'.Translate('Comment', $trn ).'</th>

	</tr>';

	// Reading information about columns in table:
	$Rez = mysqli_query( $DBlink, "SHOW FULL COLUMNS FROM $TableNameForSQL" );

	while( $CurrentRow = mysqli_fetch_array( $Rez, MYSQLI_ASSOC ) )
	{
		$txt.='
		<tr>
		<td>'. htmlspecialchars( $CurrentRow["Field"], ENT_QUOTES) .'</td>
		<td>'. htmlspecialchars( $CurrentRow["Type"], ENT_QUOTES) .'</td>
		<td>'. htmlspecialchars( $CurrentRow["Null"], ENT_QUOTES) .'</td>
		<td>'. htmlspecialchars( $CurrentRow["Default"], ENT_QUOTES) .'</td>
		<td>'. htmlspecialchars( $CurrentRow["Key"], ENT_QUOTES) .'</td>
		<td>'. htmlspecialchars( $CurrentRow["Extra"], ENT_QUOTES) .'</td>
		<td>'. htmlspecialchars( $CurrentRow["Comment"], ENT_QUOTES) .'</td>
		</tr>';
	}
	$txt.='</table>';
	
	mysqli_free_result( $Rez );
	
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
