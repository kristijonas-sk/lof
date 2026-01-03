<?php
require("tro-list-class.php");
require("troll_set.php");
require("troll_fu.php");

$UserInfo = Array();
$DBlink;
tr_SetUp( "1,2,3,4" );

// needed to verify, the user rights:

if( empty( $_GET['tablefile'] ) )
{
	ExitWithError( "Niekas neištrinta. Negautas lentelės aprašo failo pavadinimas." );
}

$TableInfo = new class_tro_list();

if( $TableInfo -> LoadFromFile( $_GET['tablefile'] ) === FALSE )
{
	ExitWithError( "Niekas neištrinta. Nepavyko perskaityti lentelės aprašo failo." );
}

if( empty( $TableInfo -> TableInfo['AccessControll'] ) )
{
	ExitWithError( "Niekas neištrinta. Nenurodyta lentelės peržiūros leidimai." );
}

if( tr_SetUpA( $TableInfo -> TableInfo['AccessControll'], FALSE ) === FALSE )
{
	ExitWithError( "Niekas neištrinta. Prisijungimo  klaida." );
}


//// Vartojo prisijungimas patvirtintas:
if( empty( $_GET['delete_from_table'] ) )
{
	ExitWithError( "Niekas neištrinta. Negauta lentelė iš kurios reikia trinti." );
}

// Just in case:
if( $_GET['delete_from_table'] !==  $TableInfo -> TableInfo[ 'DBmainTable' ] )
{
	ExitWithError( "Niekas neištrinta. Gautas lentelės pavadinimas nesutampa su nurodytu faile." );
}


// Just in case:
if( $TableInfo -> TableInfo[ 'ShowDelete' ] !== "1" )
{
	ExitWithError( "Niekas neištrinta. Šio sąrašo failas trynimo neleidžia." );
}

if( $TableInfo -> TableInfo[ 'ShowButtons' ] !== "1" )
{
	ExitWithError( "Niekas neištrinta. Šio sąrašo failas trynimo neleidžia." );
}


if( empty( $_GET['delete_id_val'] ) )
{
	ExitWithError( "Niekas neištrinta. Negauta trinamo įrašo ID." );
}

if( empty( $_GET['delete'] ) )
{
	ExitWithError( "Niekas neištrinta. Nepatvirtina operacija." );
}

if( $_GET['delete'] !== 'yess' )
{
	ExitWithError( "Niekas neištrinta. Nepatvirtina operacija." );
}

function ExitWithError( $Error )
{
	echo $Error;
	exit();
}

$Table = $TableInfo -> TableInfo[ 'DBmainTable' ];
$Column = $TableInfo -> TableInfo[ 'DBidCol' ];


// Entries are deleted only from main table, so child tables must have caskade on delete.
if( TrintiIrasa( $DBlink, $Table, $Column,  $_GET['delete_id_val']  ) )
	echo "deleted";
else
	echo "klaida";

mysqli_close( $DBlink );

function TrintiIrasa( $DBlink, $Table, $IDcol, $IDval )
{
	$Apskliaustas_IDcol = mysqli_real_escape_string( $DBlink, $IDcol );
	$Apskliaustas_IDval = mysqli_real_escape_string( $DBlink, $IDval );
	$Apskliaustas_table = mysqli_real_escape_string( $DBlink, $Table );
	
	$SQL = "DELETE FROM $Apskliaustas_table WHERE $Apskliaustas_IDcol = '$Apskliaustas_IDval'";
	// echo( $SQL );
	$Rez = mysqli_query( $DBlink, $SQL );
	if( !$Rez )
		return FALSE;

	return TRUE;
}

?>
