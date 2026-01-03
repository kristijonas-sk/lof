<?php
class class_tro_list
{
	public $TableInfo = Array(
	"DBmainTable" => "",
	"DBidCol" => "",
	
	"TableHeading" => "",
	"TableLabel" => "",
	
	"TableUrlButton1" => "",
	
	
	"AccessControll" => "",
	"DBselectConstrain" => "", // Aditional constraint to use with every select.
	
	"SortColumn" => "",
	"SortDirection" => "",
	"RowsPerPage" => "",
	
	"DefFilterCol" => "", // Default filter column. For witch column show search when user press search button.
	
	"ShowButtons" => "",
	"ShowDelete" => "",
	"ShowCsvButton" => "",
	"ShowSqlButton" => "",
	
	"UrlButton1" => "",
	"UrlButton2" => "",
	"UrlButton3" => ""
	);
	public $LastError = "";
	public $AllColumnsInfo = Array();
	
	
	public function LoadFromFile( $FileName )
	{
		// $CSVseparator = ";";
		$CSVseparator = "\t";
		if( !file_exists( $FileName ) )
		{
			$this -> LastError = "Table definition file not found.";
			return FALSE;
		}
		
		$LinesFromFile = file( $FileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		
		// var_dump( $LinesFromFile );
		
		// First line in file contains headers of table related information fields:
		$HeaderLineArray = explode( $CSVseparator, $LinesFromFile[0] ); // Headers line
		array_shift(  $LinesFromFile );
		$LineArray = explode( $CSVseparator, $LinesFromFile[0] ); // Values lines
		array_shift(  $LinesFromFile );
		
		// Read form related information to array:
		// Example: ListInfo["DBtable"] = "table1";
		foreach ( $HeaderLineArray as $index => $HeaderText )
		{
			if( isset( $LineArray [ $index ] ) )
				$this -> TableInfo[ $HeaderText ] = $LineArray [ $index ];
		}
		
		// Empty sepparator line is removed:
		array_shift(  $LinesFromFile );
		
		// Third line contain headers for columns info:
		$HeaderLine = $LinesFromFile[0];
		array_shift(  $LinesFromFile );
		
		// Next line is list of table rows that should be displayed:
		//$this -> Headers = explode( $CSVseparator, $LinesFromFile[0] ); // Headers line
		$i = 0;
		foreach ( $LinesFromFile as $line_num => $CurrentLine )
		{
			$this -> AllColumnsInfo[ $i ] = new class_tro_list_col();
			if( ! $this -> AllColumnsInfo[ $i ] -> SetValues( $HeaderLine, $CurrentLine, $CSVseparator ) )
			{
				// If it is impossible to set values, then line is skipped.
				array_pop( $this -> AllColumnsInfo  );
				continue;
			}
			$i ++;
		}
		return TRUE;
	}
	
	// Separate function than loading csv file.
	// Load values from DB.
	public function InitializeFromDB( $DBlink )
	{
		foreach( $this -> AllColumnsInfo as $Key => $Val )
		{
			// if( !empty( $Val -> ColumnInfo['FullNameCol'] ) )
			if( !empty( $Val -> ColumnInfo['FilterValuesList'] ) )
			{
				$Val -> LoadFilterValuesList( $DBlink );
			}
		}
	}
	
	public function Output()
	{
		foreach( $this -> AllColumnsInfo as $RowKey => $RowValue )
		{
			foreach( $RowValue -> ColumnInfo as $ColName => $ColValue )
				echo( "$ColValue\t" );
			echo("\n");
		}
	}
	
	public function GetIndexByArrayElementNameAndValue( $ElementName, $ElementValue )
	{
		foreach( $this -> AllColumnsInfo as $Key => $Val )
		{
			if( $Val -> ColumnInfo[ $ElementName ] === $ElementValue )
				return $Key;
		}
		return FALSE;
	}
}

class class_tro_list_col
{
	public $ColumnInfo = Array
	(
		"Name" => "",
		"Type" => "",
		"DBtable" => "", // Table where column is.
		"DBcolumn" => "", // One ore more comma separated columns names to display in one list cell. If columns are from child table, than first column name must be foreigen key to main table.
		
		"Label" => "",
		"Heading" => "",
		
		"Style" => "",
		"Class" => "",
		"DBChildTableReveal" => "", // Filters values that showe in table from child table.
	
		"FilterType" => "",
		"FilterValue" => "",
		"FilterValuesList" => "", // SQL or list or dates 
		
		"ValuesListUsage" => "" // 1 use in filter list, 2 use in table for substitution, 3 use both.
	);
	// Array to store full names readed from DB for sustitution.
	public $FilterValuesList = Array();
	
	function __construct()
	{
	
	}
	
	
	// HeaderLine - string with header line.
	// ValuesLine - string with values line.
	// $Separator - what symbol separates values. Like for example \t or ;
	public function SetValues( $HeaderLine, $ValuesLine, $Separator )
	{
		$Headers = explode( $Separator, $HeaderLine );
		$Values = explode( $Separator, $ValuesLine );
		
		// Name value is mandatory, so if it don't exist, cant add.
		$WhereIsName = array_search( 'Name', $Headers );
		if( $WhereIsName === FALSE ) return FALSE;
		if( strlen( $Values[ $WhereIsName ] ) < 1 ) return FALSE;
		
		foreach( $Headers as $index => $HeaderValue )
		{
			if( array_key_exists( $HeaderValue, $this -> ColumnInfo ) )
			{
				$this -> ColumnInfo[ $HeaderValue ] = $Values[ $index ];
				
			}
		}
		return TRUE;
	}
	public function LoadFilterValuesList( $DBlink )
	{
		// $TypeAndSql Type and SQL
		$TypeAndSql = explode( ":", $this -> ColumnInfo['FilterValuesList'] );
		
		$ListType = strstr( $this -> ColumnInfo['FilterValuesList'], ':', true );
		$ListText = strstr( $this -> ColumnInfo['FilterValuesList'], ':', false );
		$ListText = substr( $ListText, 1 );
		// echo( "aa: ".$ListText );
		
		// if( $ListType === FALSE )
		//	return FALSE;
			
		if( $ListType == "SQL" )
			$this -> FilterValuesList = $this -> LoadArrayFromDB( $DBlink, $ListText );
		if( $ListType == "list" )
			$this -> FilterValuesList = $this -> LoadArrayFromList( $ListText );
		if( $ListType == "list2" )
			$this -> FilterValuesList = $this -> LoadArrayFromList2( $ListText );
		if( $ListType == "dates" )
			$this -> FilterValuesList = $this -> LoadArrayFromDates( $ListText );
		
	}
	// Helper function.
	// Execute $SQL querry, and return results as array.
	// Array keys is the first column value in results set.
	// If result set have more than one column, array value will be second column value.
	private function LoadArrayFromDB( $DBlink, $SQL )
	{
		$Rez = mysqli_query( $DBlink, $SQL );
		if( !$Rez ) return FALSE;
		$Ats = Array();
		$ColForVal = 0;
		if( mysqli_num_fields( $Rez ) > 1 )
			$ColForVal = 1;
		while( $RastiStulpeliai = mysqli_fetch_row( $Rez ) )
			$Ats[ $RastiStulpeliai[ 0 ] ] = $RastiStulpeliai[ $ColForVal ];

		return $Ats;
	}
	private function LoadArrayFromList( $List )
	{
		$Ats = Array();
		$ArrayFromList = explode( ",", $List );
		foreach( $ArrayFromList as $Key => $Val )
		{
			$Ats[ $Val ] = $Val;
		}
		return $Ats;
	}	
	private function LoadArrayFromList2( $List )
	{
		$Ats = Array();
		$ArrayFromList = explode( ",", $List );
		$n = count( $ArrayFromList );
		if( $n % 2 !== 0 ) return $Ats;
		for( $i = 0; $i < $n; $i += 2 )
		{
			$Key = $ArrayFromList[ $i ]; // Keys are in even memebers.
			$Val = $ArrayFromList[ $i + 1 ]; // Values are in odd memebers.
			$Ats[ $Key ] = $Val;
		}
		return $Ats;
	}
	private function LoadArrayFromDates( $DatesList )
	{
		$Ats = Array();
		$ArrayFromList = explode( ",", $DatesList );
		foreach( $ArrayFromList as $Key => $Val )
		{
			$FilterTxt = "";
			$ThisYear = Date('Y');
			$NextYear = $ThisYear + 1;
			$PrevYear = $ThisYear - 1;
			
			$ThisMonth = Date('Y-m');
			$NextMonth = Date( 'Y-m', strtotime('+1 month') );
			$PrevMonth = Date( 'Y-m', strtotime('-1 month') );
			
			$ThisDay = Date('Y-m-d');
			$NextDay = Date( 'Y-m-d', strtotime('+1 day') );
			$PrevDay = Date( 'Y-m-d', strtotime('-1 day') );
			
			switch( $Val )
			{
				// case "Sausis":    $Ats[ $Val ] = " [$ThisYear-01-01 $ThisYear-02-01)"; break;
				case "Sausis":    $Ats["[$ThisYear-01-01 $ThisYear-02-01)"] = $Val; break;
				case "Vasaris":   $Ats["[$ThisYear-02-01 $ThisYear-03-01)"] = $Val; break;
				case "Kovas":     $Ats["[$ThisYear-03-01 $ThisYear-04-01)"] = $Val; break;
				case "Balandis":  $Ats["[$ThisYear-04-01 $ThisYear-05-01)"] = $Val; break;
				case "Gegužė":    $Ats["[$ThisYear-05-01 $ThisYear-06-01)"] = $Val; break;
				case "Birželis":  $Ats["[$ThisYear-06-01 $ThisYear-07-01)"] = $Val; break;
				case "Liepa":     $Ats["[$ThisYear-07-01 $ThisYear-08-01)"] = $Val; break;
				case "Rugpjūtis": $Ats["[$ThisYear-08-01 $ThisYear-09-01)"] = $Val; break;
				case "Rugsėjis":  $Ats["[$ThisYear-09-01 $ThisYear-10-01)"] = $Val; break;
				case "Spalis":    $Ats["[$ThisYear-10-01 $ThisYear-11-01)"] = $Val; break;
				case "Lapkritis": $Ats["[$ThisYear-11-01 $ThisYear-12-01)"] = $Val; break;
				case "Gruodis":   $Ats["[$ThisYear-12-01 $NextYear-01-01)"] = $Val; break;
				
				case "Šiandiena": $Ats["$ThisDay"] = $Val; break;
				case "Šis mėnuo": $Ats["[$ThisMonth-01 $NextMonth-01)"] = $Val; break;
				case "Šie metai": $Ats["[$ThisYear-01-01 $NextYear-01-01)"] = $Val; break;
				
				case "Vakardiena":     $Ats["$PrevDay"] = $Val;  break;
				case "Praeitas mėnuo": $Ats["[$PrevMonth-01 $ThisMonth-01)"] = $Val; break;
				case "Praeiti metai":  $Ats["[$PrevYear-01-01 $ThisYear-01-01)"] = $Val; break;
			}
		
		}
		return $Ats;

	}
}
?>
