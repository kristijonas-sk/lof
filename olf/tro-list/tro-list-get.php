<?php

require( "tro-list-class.php" );
require("troll_set.php");
require("troll_fu.php");
$DBlink;
//tr_SetUpA( "1,2,3,4", TRUE );


if( empty( $_GET['tablefile'] ) )
	ExitWithError( "Can't get file with table definition name.\n" );

$TableInfo = new class_tro_list();

if( $TableInfo -> LoadFromFile( $_GET['tablefile'] ) === FALSE )
	ExitWithError( $TableInfo -> LastError );

if( empty( $TableInfo -> TableInfo['AccessControll'] ) )
	ExitWithError( "Nenurodyta lentelės peržiūros leidimai." );

if( tr_SetUpA( $TableInfo -> TableInfo['AccessControll'], FALSE ) === FALSE )
	ExitWithError( "Vartotojo prisijungimo klaida." );

$TableInfo -> InitializeFromDB( $DBlink );
CreateSQL( $DBlink );
mysqli_close( $DBlink );


function ExitWithError( $ErrorDescription )
{
	echo( "0\n" );
	echo( "$ErrorDescription\n" );
	exit;
}

function CreateSQL( $DBlink )
{
	global $TableInfo;
	
	$Offset = 0;
	if( isset( $_GET['offset'] ) ) if( ctype_digit( $_GET['offset'] ) ) $Offset = $_GET['offset'];
	
	$RowAmount = 100;
	if( isset( $_GET['limit'] ) ) if( ctype_digit( $_GET['limit'] ) ) $RowAmount = $_GET['limit'];
	
	$SortBy = '';
	if( isset( $_GET['sortby'] ) )
	$SortBy = mysqli_real_escape_string( $DBlink, $_GET['sortby'] ); // Sort column name
	
	$Order = 'ASC';
	if( isset( $_GET['order'] ) ) if( $_GET['order'] == 'desc' ) $Order = 'DESC';
	
	$FullOrdering = "";
	if( !empty( $SortBy ) )
		$FullOrdering = " ORDER BY $SortBy $Order ";
	
	$BoolOp = ' AND ';
	if( isset( $_GET['boolop'] ) ) if( $_GET['boolop'] == 'OR' ) $BoolOp = ' OR ';
	if( isset( $_GET['boolop'] ) ) if( $_GET['boolop'] == 'XOR' ) $BoolOp = ' XOR ';
	
	
	/// With preagregation:
	$SQL = "";

	$ColsArr = Array(); // 
	$ChildTableQueryesArr = Array();
	$JoinQueryesArr = Array();
	$MainTableFiltersArr = Array();
	
	$MainTable = $TableInfo -> TableInfo[ 'DBmainTable' ];
	$MainIDcol = $TableInfo -> TableInfo[ 'DBidCol' ];
	
	foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
	{
		$CurrTable = $Value -> ColumnInfo[ 'DBtable' ];
		$CurrColl = $Value -> ColumnInfo[ 'DBcolumn' ];
		$CurrName = $Value -> ColumnInfo[ 'Name' ];
		$CurrType = $Value -> ColumnInfo[ 'Type' ];
		
		// Generating filters if needed:
		$CurrFilterString = "";
		if( isset( $_GET[ "filter_$CurrName" ]  ) )
		{
			$FilterValue = $_GET[ "filter_$CurrName" ];
			$FilterCompare = "";
			if( !empty( $_GET[ "compar_$CurrName" ] ) )
			{
				$FilterCompare = $_GET[ "compar_$CurrName" ];
				// $FiltersArr[] = Preaper_FILTER( $MainTable, $CurrTable, $CurrColl, $DBlink, $FilterCompare, $FilterValue, $Value -> ColumnInfo[ 'DBChildTableReveal' ] );
				// if( !empty( $FiltersByTableArr[ $CurrTable ] ) ) 
				// 	$FiltersByTableArr[ $CurrTable ] .= " $BoolOp "; // Since there is already filter then adding OR/AND betwen it and new filter.
				
				// $CurrFilterString = Preaper_FILTER( $MainTable, $CurrTable, $CurrColl, $DBlink, $FilterCompare, $FilterValue, $Value -> ColumnInfo[ 'DBChildTableReveal' ] );
				$CurrFilterString = Preaper_FILTER( $MainTable, $CurrTable, $CurrColl, $DBlink, $FilterCompare, $FilterValue );
			}
		}
		
		if( $CurrTable == $MainTable )
		{
			// $ColsArr[] = Preaper_CONCAT_WS( $MainTable, $CurrColl, $CurrName, $CurrType );
			$ColsArr[] = PreaperByType( $MainTable, $CurrColl, $CurrName, $CurrType );
			if( !empty( $CurrFilterString ) ) $MainTableFiltersArr[] = $CurrFilterString;
		}
		else
		{
			$TempTableName = $CurrTable."_tmp";
			$ForeigenKey = strstr( $CurrColl, ',', true ); // First column must be foreigen key.
			
			$JointType = " LEFT ";
			if( !empty( $CurrFilterString ) )
			{
				$JointType = " INNER ";
				// If there is filtering by child table, than joint must be INNER.
				// Since rows that not mach must be exculded from main table.
			}

			if( !empty( $Value -> ColumnInfo[ 'DBChildTableReveal' ] ) )
			{
				// When appending only $Value -> ColumnInfo[ 'DBChildTableReveal' ]
				// than join must be as it is. 
				// If there is no filtering by this column it must stay LEFT, 
				// so that rows will be included in finall table, 
				// but columns that not much this condition from child table, will not be revealed.
				if( !empty( $CurrFilterString ) )
				{
					$CurrFilterString = " (".$Value -> ColumnInfo[ 'DBChildTableReveal' ].") AND ($CurrFilterString) ";

				}
				else
					$CurrFilterString = $Value -> ColumnInfo[ 'DBChildTableReveal' ];
			}
			if( !empty( $CurrFilterString ) )
			{
				$CurrFilterString = " WHERE $CurrFilterString";
			}
			
			$CurrCollGrouped = "$ForeigenKey, ".Preaper_GROUP_CONCAT( $CurrTable, $CurrColl, $CurrName, $Value -> ColumnInfo[ 'DBChildTableReveal' ] );
			$ChildTableQueryesArr[] = " $TempTableName AS (SELECT $CurrCollGrouped FROM $CurrTable $CurrFilterString GROUP BY $ForeigenKey)";
			$ColsArr[] = "$TempTableName.$CurrName";
			
			// $JoinQueryesArr[] = " LEFT JOIN $TempTableName ON $TempTableName.$ForeigenKey = $MainTable.$MainIDcol ";
			$JoinQueryesArr[] = " $JointType JOIN $TempTableName ON $TempTableName.$ForeigenKey = $MainTable.$MainIDcol ";
		}
		

	}
	
	$MainTableCols = implode( ', ', $ColsArr );
	$With = implode( ', ', $ChildTableQueryesArr );
	if( !empty( $With ) ) $With = " WITH $With ";
	$JoinQueryes = implode( ' ', $JoinQueryesArr );
	$QueryFilter = implode( " $BoolOp ", $MainTableFiltersArr );
	// if( !empty( $QueryFilter ) )
	// 	$QueryFilter = " WHERE ".$QueryFilter;
	$QueryFilter = AppendWhereIfNeedeed( $QueryFilter, $TableInfo -> TableInfo[ 'DBselectConstrain' ], " AND " );
	
	
	$SQL = "
	$With
	SELECT $MainTableCols 
	FROM $MainTable
	$JoinQueryes
	$QueryFilter
	$FullOrdering
	";
	
	$SQLtoCount = "SELECT COUNT(*) AS kiek_isviso FROM ( $SQL ) AS visa_rez_lentele";
	$SQL .= " LIMIT $RowAmount OFFSET $Offset "; // Appending limits to actuall query.
	
	// ExitWithError( "sql: ".str_replace( "\n", " ", $SQLtoCount ) );
	OutputTable( $DBlink, $SQLtoCount, $SQL );
}

function OutputTable( $DBlink, $SQLtoCount, $SQL )
{
	global $TableInfo;
	
	$Csv = "";
	if( !empty( $_GET['csv'] ) )
	{
		if( $TableInfo -> TableInfo['ShowCsvButton'] === "1" && $_GET['csv'] === "1" )
			$Csv = "1";
	}
	
	$ShowSql = "";
	if( !empty( $_GET['sql'] ) )
	{
		if( $TableInfo -> TableInfo['ShowSqlButton'] === "1" && $_GET['sql'] === "1" )
			$ShowSql = "1";
	}
	
	
	if( $Csv !== "1" )
	{
		echo( "1\n" ); // No error indication.
		if( $ShowSql === "1" )
		{
			echo( str_replace( "\n", " ", $SQLtoCount )."\n" );
			echo( str_replace( "\n", " ", $SQL )."\n" );
		}
		else
			echo( "\n\n" );
	}
	else
	{
		// Generating CSV file name:
		date_default_timezone_set('Europe/Vilnius');
		$CsvFileName = "lentele ".$TableInfo -> TableInfo[ 'DBmainTable' ].' '. date( 'Y-m-d Hi' ).".csv";
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$CsvFileName.'"');
		echo "\xEF\xBB\xBF"; //UTF8 bom;
	}
	
	
	$Rez = mysqli_query( $DBlink, $SQLtoCount );
	$RastiStulpeliai = mysqli_fetch_assoc( $Rez );
	$NumberOfTotalResults = $RastiStulpeliai['kiek_isviso'];
	mysqli_free_result( $Rez );
	
	$Rez = mysqli_query( $DBlink, $SQL );
	$NumberOfPageResults = mysqli_num_rows( $Rez );
	
	if( $Csv !== "1" )
		// Showing how much results found:
		echo("$NumberOfTotalResults\t$NumberOfPageResults\n");
	else
	{
		$HeadersOutput = "";
		// Outputing table headers for csv:
		foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
		{
			$HeadersOutput .= $Value -> ColumnInfo[ "Heading" ] ."\t" ;
		}
		// Removing last tab:
		$HeadersOutput = substr( $HeadersOutput, 0, -1 );
		echo "$HeadersOutput\n";
	}
	
	
	
	$TableOutput = "";
	
	while( $RastiStulpeliai = mysqli_fetch_assoc( $Rez ) )
	{
		// Outputing value of key column at the row begining, if needed:
		if( !empty( $_GET['output_keys'] ) )
			$TableOutput .= $RastiStulpeliai[ $TableInfo -> TableInfo['DBidCol'] ]."\t";
			
		foreach( $RastiStulpeliai as $Key => $Value )
		{
			// tab and new lines in values  will break table. So removing them:
			$Value = str_replace( ["\t","\n"], " ", $Value );
			
			$index = $TableInfo -> GetIndexByArrayElementNameAndValue( "Name", $Key );
			if( $index === FALSE )
			{
				// There is no such name in definition file:
				$TableOutput .=  "$Value\t";
				continue;
			}
			// If needed replacing value readed from DB by value from FilterValuesList:
			if( $TableInfo -> AllColumnsInfo[ $index ] -> ColumnInfo[ 'ValuesListUsage' ] == 2 || $TableInfo -> AllColumnsInfo[$index] -> ColumnInfo[ 'ValuesListUsage' ] == 3 )
			{
				// if( empty( $Value ) )
				if( !isset( $Value ) )
				{
					$TableOutput .= "\t";
					continue;
				}
				if( !empty( $TableInfo -> AllColumnsInfo[ $index ] -> FilterValuesList[ $Value ] ) )
				{
		
					$TableOutput .=  $TableInfo -> AllColumnsInfo[ $index ] -> FilterValuesList[ $Value ] ."\t" ;
					continue;
				}
			}
			$TableOutput .=  "$Value\t";
		}
		// Removing last tab:
		$TableOutput = substr( $TableOutput, 0, -1 );
		$TableOutput .= "\n";
	}
	echo( $TableOutput );
	mysqli_free_result( $Rez );
}

// Function generate apropriate sql command for joining multiple columns from child table:
function Preaper_GROUP_CONCAT( $Table, $ColList, $Name )
{
	
	if( str_contains( $ColList, ',' ) )
	{
		// Multiple columns from child table:
		$ColArray = explode( ",", $ColList );
		array_shift( $ColArray ); // Preventing display foreigen key,
		// Appending table name before each column name,
		// and COALESCE function to remove NULL values:
		foreach( $ColArray as $Key => &$Val )
			$Val = " COALESCE(".$Table.".".trim( $Val ).",'-')";
			//$Val = " ".$Table.".".trim( $Val )." ";
		unset( $Val );
		$ColList = implode( ",'; ',", $ColArray );
	
		// return "GROUP_CONCAT( /*DISTINCT*/ CONCAT( $ColList ) SEPARATOR ' • ' ) AS $Name";
		return "GROUP_CONCAT( CONCAT( $ColList ) SEPARATOR ' • ' ) AS $Name";
	}
	else
	{
		// Only single column from child table:
		// Actually its not allowed single column there, since it must be foreigen key at first column.
		return "GROUP_CONCAT($Table.$ColList SEPARATOR ' • ') AS $Name";
	}
}

function PreaperByType( $Table, $ColList, $Name, $Type )
{
	// Single DBcolumn in one HTML table column: 
	if( !str_contains( $ColList, ',' ) )
	{
		$TableAndColumn = "$Table.$ColList";
		switch(  $Type )
		{
			case "IPaddress": return " INET6_NTOA( $TableAndColumn ) AS $Name ";
			case "ImageJPG": return " '$ColList' AS $Name ";
		}
		// Type is
		if( $ColList != $Name )
			return " $TableAndColumn AS $Name ";
		return " $TableAndColumn ";
	}
	// Multiple DBcolumns in one HTML table column: 
	return Preaper_CONCAT_WS( $Table, $ColList, $Name );
}

// Function generate apropriate sql command for joining multiple columns from main table:
function Preaper_CONCAT_WS( $Table, $ColList, $Name )
{
	// Multiple columns:
	$ColArray = explode( ",", $ColList );
	// Appending table name before each column name:
	foreach( $ColArray as $Key => &$Val )
		$Val = $Table.".".trim( $Val );
	unset( $Val );
	$ColList = implode( ",", $ColArray );
	return "CONCAT_WS( ',', $ColList ) AS $Name";
}

function AppendWhereIfNeedeed( $SelectSQL, $ConstrainSQL, $Operation )
{
	global $UserInfo; // Array that hold information about current user.
	// Appending additional SELECT constraint per table if it exist:
	if( empty( $SelectSQL ) && empty( $ConstrainSQL ) )
		return "";
		
	// Replacing variables names that is between dollar signs (like $my_var$) with they values in array $UserInfo.
	if( str_contains( $ConstrainSQL, '$' ) )
	{
		if( !empty( $UserInfo ) )
		{
			$Split = explode( '$', $ConstrainSQL );
			$n = count( $Split );
			for( $i=0; $i<$n; $i++ )
			{
				// Variable names will be in odd values:
				if( $i % 2 != 0 )
				{
					$LastVarName = $Split[ $i ];
					if( array_key_exists( $LastVarName, $UserInfo ) )
					{
						$Split[ $i ] = $UserInfo[ $LastVarName ];
					}
				}
			}
			$ConstrainSQL = implode( $Split );
		}
	}
	if( !empty( $SelectSQL ) && empty( $ConstrainSQL ) )
		return " WHERE( $SelectSQL )";
	if( empty( $SelectSQL ) && !empty( $ConstrainSQL ) )
		return " WHERE( $ConstrainSQL )";
	if( !empty( $SelectSQL ) && !empty( $ConstrainSQL ) )
		return " WHERE( ($SelectSQL) $Operation ($ConstrainSQL) )";

}
/*
function AppendWhereIfNeedeed( $SelectSQL, $ConstrainSQL, $Operation )
{
	// Appending additional SELECT constraint per table if it exist:
	if( empty( $SelectSQL ) && empty( $ConstrainSQL ) )
		return "";
	if( !empty( $SelectSQL ) && empty( $ConstrainSQL ) )
		return " WHERE( $SelectSQL )";
	if( empty( $SelectSQL ) && !empty( $ConstrainSQL ) )
		return " WHERE( $ConstrainSQL )";
	if( !empty( $SelectSQL ) && !empty( $ConstrainSQL ) )
		return " WHERE( ($SelectSQL) $Operation ($ConstrainSQL) )";
}
*/

// Preaper filter for DBcolumns under the one Name: 
// function Preaper_FILTER( $MainTable, $Table, $ColList, $DBlink, $Fcompare, $Fvalue, $FadditionalConstrain )
function Preaper_FILTER( $MainTable, $Table, $ColList, $DBlink, $Fcompare, $Fvalue )
{
	$FilterArray = Array();

	$ColArray = explode( ",", $ColList );
	
	// If columns from main table, then first item must be removed, since it is foreigen key.
	if( $MainTable != $Table )
		array_shift( $ColArray );
	
	foreach( $ColArray as $Key => $Val )
	{
		$Val = trim( $Val );
		$FilterArray[] = CreateSqlComparison( $DBlink, $Fcompare, "$Table.$Val" , $Fvalue );
	}
	
	if( empty( $FilterArray ) ) return "";
	// Columns under one Name always use OR comparison among them self:
	$RezSql = " (". implode( " OR ", $FilterArray ) .")";
	// if( !empty( $FadditionalConstrain ) )
	// 	$RezSql = "( $RezSql AND ( $FadditionalConstrain ) )";
	return $RezSql;
}

function CreateSqlComparison( $DBlink, $Compare, $Col, $Val )
{
	$EscapedValue = mysqli_real_escape_string( $DBlink, $Val );
	switch(	$Compare )
	{
		case "*abc*":  return "$Col LIKE '%$EscapedValue%' ";
		case "!*abc*": return "$Col NOT LIKE '%$EscapedValue%' ";
		case "abc*":   return "$Col LIKE '$EscapedValue%' ";
		case "!abc*":  return "$Col NOT LIKE '$EscapedValue%' ";
		case "*abc":   return "$Col LIKE '%$EscapedValue' ";
		case "!*abc":  return "$Col NOT LIKE '%$EscapedValue' ";
		case "=":
		case "<>":
		case ">":
		case "<":     return "$Col $Compare '$EscapedValue' ";
		case "nul":   return "$Col IS NULL ";
		case "!nul":  return "$Col IS NOT NULL ";
		case "[0 1]":
		// pvz.: 2025-01-05 2025-07-04
		$SQL = "";
		$Val = trim( $Val );
		$NR1andNR2 = explode( " ", $Val );
		if( count( $NR1andNR2 ) !== 2 )
		{
			// if( ctype_digit( $EscapedValue ) ) // If only one number entered, it interpreted as years. 
			// {
			// 	return " ($Col >= '$EscapedValue-01-01' AND $Col <= '$EscapedValue-12-31' )";
			// }
			return "$Col = '$EscapedValue' ";
		}
		
		$Op1 = ">=";
		if( $NR1andNR2[0][0] == '('  )
		{
			$NR1andNR2[0] = substr( $NR1andNR2[0], 1 ); // Removing first bracket.
			$Op1 = '>';
		}
		if( $NR1andNR2[0][0] == '['  )
			$NR1andNR2[0] = substr( $NR1andNR2[0], 1 ); // Removing first bracket.
		
		$Op2 = "<=";
		if( $NR1andNR2[1][-1] == ')'  )
		{
			$NR1andNR2[1] = substr( $NR1andNR2[1], 0, -1 ); // Removing last bracket.
			$Op2 = '<';
		}
		if( $NR1andNR2[1][-1] == ']'  )
			$NR1andNR2[1] = substr( $NR1andNR2[1], 0, -1 ); // Removing last bracket.
		
		
		$SQL = "$Col $Op1 '".$NR1andNR2[0]."' AND $Col $Op2 '".$NR1andNR2[1]."'";
		return $SQL;
		
		default:      return "$Col = '$EscapedValue' ";
	}
}

?>
