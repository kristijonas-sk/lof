<?php
require("tro-list-class.php");
require("troll_set.php");
require("troll_fu.php");

$TableInfo = new class_tro_list();
$DBlink;
$Error = "";

if( !empty( $_GET['table'] ) )
{
	$TableFile = "tro-lists/".$_GET['table'].".csv";
	if( $TableInfo -> LoadFromFile( $TableFile ) === FALSE )
	{
		$Error = $TableInfo -> LastError;
	} else
	if( empty( $TableInfo -> TableInfo['AccessControll'] ) )
	{
		$Error = "Nenurodyta lentelės peržiūros leidimai.";
	}
}
else
	$Error = "Negautas lentelės failo pavadinimas.";
	
if( empty( $Error ) )
	tr_SetUp( $TableInfo -> TableInfo['AccessControll'] );
else
{
	// If unable to read template file, than all logged users can access, to view error message:
	tr_SetUp( "1,2,3,4,5" );
}

$TableInfo -> InitializeFromDB( $DBlink );

mysqli_close( $DBlink );

function FilterTipesList( $id, $FdefaultType )
{
	// Todo: visa šita sąrašą gal geriau į klasę perkelti:
	return '
	<select id="compar_'.$id.'" onchange="PasikeiteFiltras(\''.$id.'\',\''.$FdefaultType.'\');">
	<option value="">--- Operacija ---</option>
	<option title="Ieškoti įrašų turinčių teksto fragmentą">*abc*</option>
	<option title="Ieškoti įrašų turinčių teksto fragmentą žodžio pradžioje">*abc</option>
	<option title="Ieškoti įrašų turinčių teksto fragmentą pabaigoje">abc*</option>
	<option title="Ieškoti įrašų neturinčių teksto fragmento">!*abc*</option>
	<option title="Ieškoti įrašų neturinčių teksto fragmento žodžio pradžioje">!abc*</option>
	<option title="Ieškoti įrašų neturinčių teksto fragmento žodžio pabaigoje">!*abc</option>
	<option value="=" title="Ieškoti įrašų tiksliai atitinkančių paieškos tekstą">=</option>
	<option>&gt;</option>
	<option>&lt;</option>
	<option>&lt;&gt;</option>
	<option>nul</option>
	<option>!nul</option>
	<option title="Ieškoti skaičių/datų patenkančių į įvesta intervalą. skliaustai [] įskaito intervalo galus. Sklaustai () neįskaito intervalo galų. pvz.: [2025-01-01 2025-02-01).">[0 1]</option>
	</select>
	';
}

?>
<!DOCTYPE html> 
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="description" content="" /> 

	<meta name="keywords" content="" />
	<link rel="stylesheet" type="text/css" href="troll_sty.css" />
	<link rel="shortcut icon" type="image/png" href="tro-img/piktograma.png" />
	<title>Tro List</title>
	<style>
	table, tr, th, td
	{
		border-style:solid;
		border-width:1px;
	}
	tr
	{
		vertical-align:top;
	}
	button,input,select,checkbox {
	margin:0px;
	margin-top:3px;
	margin-bottom:3px;
	height:40px;
	border-radius:8px;
	text-align:center;
	}
	
	#all_filters
	{
		display: flex;
		width: 100%;
	}

	.filter_box
	{
		flex: 1; /* each visible box gets equal width */
		/*border: 1px solid #e5edd6;*/
		border: 3px solid #aeaeae;
		padding: 12px;
		margin: 4px;
		margin-left: 2px;
		margin-right: 2px;
		background-color:Khaki; /*Gainsboro;*/
		font-size:1.0em;
		text-align:center;
		border-radius:8px;
	}
	
	</style>
</head>
<body <?php if( empty( $Error ) ) echo "onload=\"Start()\"" ?>>

<?php
include("troll_menu.php");

if( !empty( $Error ) )
{
	echo "<div class=\"error\">$Error</div>";
}
else
{
	echo "<div style=\"float:right;margin-top:12px;background-color:white;padding:4px;\">".$TableInfo -> TableInfo['TableLabel']."</div>";
	echo "<h1>".$TableInfo -> TableInfo[ 'TableHeading' ]."</h1>";
}
?>
<div id="control_panell" style="display:none">
	

	<div class="field_box">
	<label for="page">Puslapiai</label><br><!--

	--><button type="button" style="width:38px;" onclick="FirstPage()" title="Eiti į pradžią.">|&lt;</button><!--
	--><button type="button" style="width:38px;" onclick="ChangePage(-1)" title="Ankstesnis puslapis.">-</button><!--
	--><input type="number" style="width:120px;height:40px;" value="1" name="page" id="page" min="1" onchange="Update(false);" ><!--
	--><button type="button" style="width:38px;" onclick="ChangePage(+1)" title="Kitas puslapis.">+</button><!--
	--><button type="button" style="width:38px;" onclick="LastPage()" title="Eiti į pabaigą.">&gt;|</button>
	</div>

	<div class="field_box" title="Kiek daugiausia įrašų rodyti kiekviename puslapyje.">
		<label for="limit">Kiekis </label><br>
		<select id="limit" name="limit" onchange="Update(true)" >
		<option>1</option>
		<option>2</option>
		<option>5</option>
		<option>10</option>
		<option>42</option>
		<option selected>100</option>
		<option>200</option>
		<option>500</option>
		</select>
	</div>

	<div class="field_box">
		<label for="sortby">Rikiavimas</label><br>
		<select id="sortby" onchange="Update(true);" title="Pagal kurį stulpelį rikiuoti.">
		<?php
		if( empty( $Error ) )
		{
			foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
					echo '<option value="'.$Value -> ColumnInfo[ 'DBcolumn' ].'">'.$Value -> ColumnInfo[ 'Heading' ].'</option>'."\n";
		}
		?>
		</select><!--
		--><select id="order" name="order" onchange="Update(true);" title="Rikiavimo tvarka">
		<option value="asc" title="Rikiuoti didėjimo tvarka">1,2,3</option>
		<option value="desc" title="Rikiuoti mažėjimo tvarka">3,2,1</option>
		</select>
	</div>


	<div class="field_box" style="display:none">
	Viso įrašų<br>
	<input type="text" id="kiek_isviso" style="width:180px" readonly>
	</div>
	
	<div class="field_box" style="display:none">
	Rodoma įrašų<br>
	<input type="text" id="kiek_rodoma" style="width:180px" readonly>
	</div>
	
	<div class="field_box">
	Rodoma<br>
	<div id="kas_ir_kiek" style="font-size:22px;padding:4px;padding-top:10px;"></div>
	</div>
	
	<div class="field_box">
		Filtrai<br><!--
		--><button type="button" onclick="UzdetStandartiniFiltra('<?= $TableInfo -> TableInfo[ "DefFilterCol" ] ?>')" title="ON. Įjungti standartinio stulpelio filtrą."> <img src="tro-img/find-on.svg"> </button><!--
		--><button type="button" onclick="ClearAllFilters()" title="OFF. Išjungti visus filtrus."> <img src="tro-img/find-off.svg"> </button>
	</div>
	<div class="field_box" title="Kokia logine operaciją naudoti sujungiant stulpelių filtrus.">
		<label for="boolop">Sujungti</label><br>
		<select id="boolop" onchange="Update(true);">
		<option value="AND">IR</option>
		<option value="OR">ARBA</option>
		<option value="XOR">XOR</option>
		</select>
	</div>
	
	<?php	if( $TableInfo -> TableInfo[ "ShowCsvButton" ] === "1" ) echo '
	<div class="field_box" title="Atsisiųsti rodoma lentelę *.csv formatu.">
		Atsisiųsti<br>
		<button type="button" onclick="GetCSV()">CSV</button>
	</div>'; ?>
	
	<?php 	if( $TableInfo -> TableInfo[ "ShowSqlButton" ] === "1" ) echo '
	<div class="field_box" title="Rodyti SQL komandą.">
		<label for="rodyt_sql">SQL</label><br>
		<input type="checkbox" id="rodyt_sql" onchange="Update(false)">
	</div>'	; ?>
	
	<?php 	
	if( !empty( $TableInfo -> TableInfo[ "TableUrlButton1" ] ) )
	{
		$ButtonName = strstr( $TableInfo -> TableInfo['TableUrlButton1'], ':', true );
		$ButtonUrl = strstr( $TableInfo -> TableInfo['TableUrlButton1'], ':', false );
		$ButtonUrl = substr( $ButtonUrl, 1 );
		echo '
		<div class="field_box">
			<label for="TableUrlButton1">Veiksmai</label><br>
			<button type="button" id="TableUrlButton1" onclick="window.location.href=\''.$ButtonUrl.'\'">'.$ButtonName.'</button>
		</div>'	;
	}
	?>
	
</div>

<div style="clear:both" id="div_sql"></div>

<div id="all_filters">

<?php

if( empty( $Error ) )
{
	foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
	{
		$ColName = $Value -> ColumnInfo[ 'Name' ];
		$ColHeading = $Value -> ColumnInfo[ 'Heading' ];
		$ColLabel = $Value -> ColumnInfo[ 'Label' ];
		$Ftype = $Value -> ColumnInfo[ 'FilterType' ];
		
		echo "<div class=\"filter_box\" id=\"fbox_$ColName\" style=\"display:none;\">";
		echo "<img src=\"img/close.svg\" title=\"Išjungti šį filtrą\" onclick=\"FilterTOOGLE('$ColName','$Ftype',true)\" style=\"float:right;width:18px;cursor:pointer;\">";
		echo "<span style=\"font-size:1.6em;padding-right:12px;\">$ColHeading</span> ";

		echo FilterTipesList( $ColName, $Ftype );
		if( ! empty( $Value -> FilterValuesList ) )
		{
			echo( "<select id=\"proposals_$ColName\" onchange=\"SelectProposal('$ColName','$Ftype');\">" );
			echo( "<option value=\"\">--- Pasiūlymai ---</option>" );
			foreach( $Value -> FilterValuesList as $Key => $Val )
				echo( "<option value=\"$Key\">$Val</option>");
			echo( "</select>");
		}
		
		echo '<input 
		type="text" 
		placeholder="Paieškos žodis" 
		class="filter_input" 
		style="width:99%;font-size:1.4em;text-align:center;padding:30px;border-radius:32px;"
		id="filter_'.$ColName.'" 
		oninput="PasikeiteFiltras( \''.$ColName.'\', \''.$Ftype.'\' )" 
		onkeydown="if(event.key === \'Enter\') EnterFiltroLaukeli( \''.$ColName.'\', \''.$Ftype.'\' )" 
		title="Įveskite paieškos žodį pagal kurį norite filtruoti šį stulpelį.">
		'."\n";
			
		echo "</div>";
	}
}

?>
</div>

<div id="table_error" style="background-color:red;padding:2px;display:none"></div>

<table id="list_table">
<?php
echo "<tr>";
if( empty( $Error ) )
{
	foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
	{
		$CurName = $Value -> ColumnInfo['Name'];
		$Ftype = $Value -> ColumnInfo[ 'FilterType' ];
		echo "<th id=\"th_$CurName\" title=\"".$Value -> ColumnInfo['Label']."\">";
		if( $Value -> ColumnInfo['Type'] != "ImageJPG" )
			echo "<img src=\"img/find.svg\" title=\"Įjungt/Išjungt filtrą\" onclick=\"FilterTOOGLE('$CurName','$Ftype',true)\" style=\"float:right;width:18px;cursor:pointer\">";
		echo "<br>";
		echo $Value -> ColumnInfo['Heading']."<br>";

		echo "</th>";
		
	}
}
if( $TableInfo -> TableInfo['ShowButtons'] === "1" && empty( $Error ) )
	echo "<th>- ⚜️ -<br>Veiksmai</th>";

echo "</tr>";
?>
</table>


<script>
function Start()
{
	// Setting initial sorting column and direction from file:
	<?php
	if( !empty( $TableInfo -> TableInfo['SortColumn'] ) ) echo "document.getElementById('sortby').value = '". $TableInfo -> TableInfo['SortColumn'] ."';\n";
	if( !empty( $TableInfo -> TableInfo['SortDirection'] ) ) echo "document.getElementById('order').value = '". $TableInfo -> TableInfo['SortDirection'] ."';\n";
	if( !empty( $TableInfo -> TableInfo['RowsPerPage'] ) ) echo "document.getElementById('limit').value = '". $TableInfo -> TableInfo['RowsPerPage'] ."';\n";
	?>
	document.getElementById("control_panell").style.display = "";
	ClearAllFilters( true );
	// Update( true );
	// NuimtVisusFiltrus();
}
// Page navigation functions:
function FirstPage()
{
	document.getElementById( 'page' ).value = 1;
	Update(false);
}
function LastPage()
{
	document.getElementById( 'page' ).value = GetLastPage();
	Update(false);
}
function GetLastPage()
{
	let kiek_viso_rezultatu = document.getElementById( 'kiek_isviso' ).value;
	let KiekPasirinkta = document.getElementById( 'limit' ).value;
	return Math.ceil( kiek_viso_rezultatu / KiekPasirinkta );
	
}
function ChangePage( a )
{
	const page = document.getElementById( 'page' );
	page.value = parseInt( page.value ) + a;
	
	// Čia nereikia tikrinti, nes on Update() patikrina:
	// let LastPage = GetLastPage();
	// if( page.value > LastPage ) page.value = LastPage;
	// if( page.value < 1 ) page.value = 1;
	
	Update(false);
}
function SutvarkytPsl()
{
	const page_nr = document.getElementById( 'page' );

	if( page_nr.value < 1 ) page_nr.value = 1;
	let max_page = GetLastPage();
	if( page_nr.value > max_page ) page_nr.value = max_page;
}

////////
function SukurtURL( kaip_csv )
{
	const tablefile = '<?= $TableFile ?>';
	const limit = document.getElementById( 'limit' ).value;
	let offset = document.getElementById( 'page' ).value;
	offset = (offset-1) * limit;
	const sortby = document.getElementById( 'sortby' ).value;
	const order = document.getElementById( 'order' ).value;
	const boolop = document.getElementById( 'boolop' ).value;
	
	let rodyt_sql = "";
	if( document.getElementById("rodyt_sql") )
	if( document.getElementById("rodyt_sql").checked == true )
		rodyt_sql = "&sql=1";
	
	// Collecting filters columns:
	let filter_colmns = "";
	// let show_columns_name = "";
	// let show_columns_headings = "";
	<?php 
	if( empty( $Error ) )
	{
		foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
		{
			$HTMLname = $Value -> ColumnInfo[ 'Name' ];
			
			echo "
			const compar_$HTMLname = document.getElementById( 'compar_$HTMLname' ).value;
			const filter_$HTMLname = document.getElementById( 'filter_$HTMLname' ).value;
			if( compar_$HTMLname !== '' )
			{
				filter_colmns += '&compar_$HTMLname=' + encodeURIComponent( compar_$HTMLname );
				filter_colmns += '&filter_$HTMLname=' + encodeURIComponent( filter_$HTMLname );
			}
			";
		}
	}
	?>
	let url = 'tro-list-get.php?';
	url += 'tablefile=' + encodeURIComponent( tablefile );
	url += '&limit=' + encodeURIComponent( limit );
	url += '&offset=' + encodeURIComponent( offset );
	url += '&sortby=' + encodeURIComponent( sortby );
	url += '&order=' + encodeURIComponent( order );
	url += '&boolop=' + encodeURIComponent( boolop );
	if( kaip_csv == false ) url += '&output_keys=1' ;
	else url += "&csv=1";
	url += filter_colmns 
	url += rodyt_sql;
	console.log( "URL: " + url );
	return url;

}

<?php
if( $TableInfo -> TableInfo['ShowCsvButton'] === "1" ) echo
'
function GetCSV()
{
	SutvarkytPsl();
	window.location.href = SukurtURL( true ) ;
}
'
?>
	
	
async function Update( PirmasPsl )
{
	if( PirmasPsl == true )
	 	document.getElementById( 'page' ).value = 1;
	else
		SutvarkytPsl();
	
	try
	{
		// fetch( 'remontu_paieska.php?kas=' + encodeURIComponent(ko_cia_ieskot) + "&kur=" + encodeURIComponent(kur_cia_ieskot) + "&mygtukas=1&funkcija=RodytSleptIrenginioInfo" )
		const response = await fetch( SukurtURL( false ) );
		if( !response.ok ) throw new Error(`HTTP error! status: ${response.status}`);
		else
		{
			document.getElementById("table_error").style.display = "none";
			document.getElementById( 'table_error' ).innerHTML = "";
		}
		const text = await response.text();
		
		// document.getElementById( 'laik' ).value = text;
		
		const lines = text.split(/\r?\n/);
		
		// Displaying error if there is one:
		if( lines[0].trim() === "0" )
			throw new Error( `Error: ` + lines[1] );
		
		// Displaying SQL commands if they are:
		document.getElementById( 'div_sql' ).innerHTML = "<p>" + lines[1] + "</p><p>" + lines[2] + "</p>";
		
		// Displaying total amount and displayed amount of entries:
		const TotalAndDisplayed = lines[3].split('\t');
		document.getElementById( 'kiek_isviso' ).value = TotalAndDisplayed[0];
		document.getElementById( 'kiek_rodoma' ).value = TotalAndDisplayed[1];
		
		// document.getElementById( 'kas_ir_kiek' ).innerHTML = TotalAndDisplayed[1];
		
		// remontu_lentele.innerHTML = text.substring( firstNewlineIndex + 1 );
		// remontu_lentele.innerHTML = text;
		
		let laik_txt = "";
		let Puslapis = document.getElementById( 'page' ).value-1;
		let KiekPasirinkta = document.getElementById( 'limit' ).value;
		let KiekRodoma = document.getElementById( 'kiek_rodoma' ).value;
		let KiekVisoIrasu = document.getElementById( 'kiek_isviso' ).value;
		laik_txt = Number(Number(Puslapis) * Number(KiekPasirinkta) + 1 ) + "–" + Number( (Number(Puslapis) * Number(KiekPasirinkta) ) + Number(KiekRodoma)) + " iš " + Number(KiekVisoIrasu);
		if( KiekVisoIrasu == 0 ) laik_txt = "0 iš 0";
		// document.getElementById( 'kiek_rodoma' ).value = laik_txt;
		document.getElementById( 'kas_ir_kiek' ).innerHTML = laik_txt;
		
		PopulateTableFromLines( lines, 4 );

	}
	catch( error )
	{
		document.getElementById("table_error").style.display = "";
		document.getElementById( 'table_error' ).innerHTML = error.message;
	}
}

async function TrintiIrasa( delete_from_table, delete_id_col, delete_id_val )
{
	
	if( !confirm("⚠️ Ar tikrai norite visam laikui ištrinti įrašą:\n\n[" + delete_id_col + " = " + delete_id_val + "] iš lentelės „" + delete_from_table + "“?\n\nPastaba: taip pat bus ištrinti ir atitinkami įrašai iš šalutinių lentelių turinčių „ON DELETE CASCADE“.") ) return;
	try
	{
		// const del_response = await fetch( 'tr-delete.php?delete=yess&delete_from_table=' + encodeURIComponent( delete_from_table )+"&delete_id_val=" + encodeURIComponent(delete_id_val) );
		const del_response = await fetch( 'tro-list-delete.php?delete=yess&tablefile=<?= urlencode( $TableFile ) ?>&delete_from_table=' + encodeURIComponent( delete_from_table )+"&delete_id_val=" + encodeURIComponent(delete_id_val) );
		
		if( !del_response.ok ) throw new Error(`HTTP error! status: ${del_response.status}`);

		const answer_text = await del_response.text();
		if( answer_text != 'deleted' )
			alert( answer_text );
		Update( false );
	}
	catch( error)
	{
		alert( "Error: " + error.message );
	}

}

function addImageToCell( cell, column, id, content_type )
{
  //const table = document.getElementById("myTable");
  //const row = table.insertRow();

 // const cell1 = row.insertCell(0);
 // const cell2 = row.insertCell(1);

  // cell1.textContent = "John Doe";

  // Create image element dynamically
  cell.textContent = "";
  const img = document.createElement("img");
  img.src = "tro-list-img.php?" + 
  "tablefile=" +  encodeURIComponent( "<?= $TableFile ?>") +
  "&content_type=" + encodeURIComponent( content_type ) + 
  "&column=" + encodeURIComponent( column ) + 
  "&id=" + encodeURIComponent( id ); // image URL
  
  img.alt = "";
  img.width = 80;
// console.log( "img.src: " + img.src );
  cell.appendChild(img);
}

function ShowImages()
{

}

function PopulateTableFromLines( lines, StartingLine )
{
	<?php
	// Type: color
	if( empty( $Error ) )
	{	
		echo( "const colored_cell = [ ");
		foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
		{
			// echo "/* ".$Value -> ColumnInfo[ 'Type' ]."*/ ";
			if( $Value -> ColumnInfo[ 'Type' ] == "Color" )
				echo( "'1'," );
			else
				echo( "'0'," );
		}
		echo( " ];\n");
		
		echo( "const columns_types = [ ");
		foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
		{
			echo " '".$Value -> ColumnInfo[ 'Type' ]."', ";
		}
		echo( " ];\n");
	}
	
	?>

	const list_table = document.getElementById( 'list_table' );
	// Clearing curren entries:
	while( list_table.rows.length > 1 )
		list_table.deleteRow( 1 );

	for( let i = StartingLine; i < lines.length; i++ )
	{
		if( lines[i].trim() === "" ) continue;
		
		let columns = lines[i].split('\t');
		//console.log("Line " + i + ":", lines[i]);

		// Insert a new row at the end of the table
		const row = list_table.insertRow();

		// Starting from 1, since there is key values at position 0
		for( let j = 1; j < columns.length; j++ )
		{
			const LastCell = row.insertCell(  );
			LastCell.textContent = columns[ j ];
			
			// if( colored_cell[ j-1 ] == 1 )
			if( columns_types[ j-1 ] == 'Color' )
			{
				// Removing # if it at the begining of color code.
				if ( columns[ j-1 ].startsWith('#') )
				{
					LastCell.style.backgroundColor = columns[ j ].slice(1);
					
				}
				else
					LastCell.style.backgroundColor = columns[ j ];
			}
			else if( columns_types[ j-1 ] == 'ImageJPG' )
			{
				addImageToCell( LastCell, columns[ j ], columns[0], 'image/jpeg' );
				// addImageToCell( cell, column, id )
			}

			// Insert cells
			//const nameCell = row.insertCell(0);
			//const ageCell = row.insertCell(1);

			// Add text to cells
			//nameCell.textContent = name;
			//ageCell.textContent = age;
		}
		
		<?php
		// Adding column with buttons:
		if( $TableInfo -> TableInfo['ShowButtons'] === "1" )
		{
			echo "const ButtonCell = row.insertCell();";
			
			if( ! empty( $TableInfo -> TableInfo['UrlButton1'] ) )
			{
				$NameAndUrl = explode( ":", $TableInfo -> TableInfo['UrlButton1'] );
				echo( "AddUrlButonToCell( ButtonCell, columns[0] , '". $NameAndUrl[0] ."', '". $NameAndUrl[1] ."' )\n" );
			}
			if( ! empty( $TableInfo -> TableInfo['UrlButton2'] ) )
			{
				$NameAndUrl = explode( ":", $TableInfo -> TableInfo['UrlButton2'] );
				echo( "AddUrlButonToCell( ButtonCell, columns[0] , '". $NameAndUrl[0] ."', '". $NameAndUrl[1] ."' )\n" );
			}
			if( ! empty( $TableInfo -> TableInfo['UrlButton3'] ) )
			{
				$NameAndUrl = explode( ":", $TableInfo -> TableInfo['UrlButton3'] );
				echo( "AddUrlButonToCell( ButtonCell, columns[0] , '". $NameAndUrl[0] ."', '". $NameAndUrl[1] ."' )\n" );
			}
			
			if( ! empty( $TableInfo -> TableInfo['ShowDelete'] ) )
			{
				echo( "AddDelButonToCell( ButtonCell, columns[0] );\n" );
			}			
			
		}
		?>
	}
}

function AddUrlButonToCell( cell, id, text, url )
{
	// Create the button
	let button = document.createElement( "button" );
	button.type = "button";
	button.textContent = text;
	// button.id = "button_" + id;  // Assign ID
	// btn.onclick = () => alert("Button clicked!");
	// button.addEventListener( "click", function(){ OpenLinkOnClick( url, which_mouse_button ); } );
	button.addEventListener( "mousedown", function(){ OpenLinkOnClick( url + encodeURIComponent( id ), event.button ); } );
	// Add it to the cell
	cell.appendChild( button );
}

function AddDelButonToCell( cell, id )
{
	// Create the button
	let button = document.createElement( "button" );
	button.type = "button";
	button.textContent = "🗙";
	button.title = "Ištrinti įrašą.";
	//button.id = "button_" + id;  // Assign ID
	button.addEventListener( "click", function(){ TrintiIrasa( '<?= $TableInfo -> TableInfo['DBmainTable'] ?>', '<?= $TableInfo -> TableInfo['DBidCol'] ?>', id ); } );
	// Add it to the cell
	cell.appendChild( button );
}

function OpenLinkOnClick( url, which_mouse_button )
{
	if ( which_mouse_button === 1) window.open( url, '_blank' );
	else if ( which_mouse_button === 0) window.location.href = url;
}

function FilterTOOGLE( name, default_filter_type, update_list )
{
	const filter_box = document.getElementById( "fbox_" + name );
	if( filter_box.style.display == "none" )
		FilterON( name, default_filter_type, update_list );
	else
		FilterOFF( name, update_list );
}

function FilterON( name, default_filter_type, update_list )
{
	const filter_box = document.getElementById( "fbox_" + name );
	filter_box.style.display = "";
	// document.getElementById( "compar_" + name ).value = default_filter_type; // Filtering start only when user enter text, not when it turn on field
	document.getElementById( "th_" + name ).style.backgroundColor = "Khaki";
	document.getElementById( "filter_" + name ).focus();
	if( update_list == true ) Update( true );
}

function FilterOFF( name, update_list )
{
	const filter_box = document.getElementById( "fbox_" + name );
	filter_box.style.display = "none";
	document.getElementById( "filter_" + name ).value = "";
	document.getElementById( "compar_" + name ).value = "";
	document.getElementById( "th_" + name ).style.backgroundColor = "";
	if( update_list == true ) Update( true );
}


function SelectProposal( name, default_filter_type )
{
	document.getElementById( "filter_" + name ).value = document.getElementById( "proposals_" + name ).value;
	document.getElementById( "compar_" + name ).value = default_filter_type;
	document.getElementById( "proposals_" + name ).value = "";
	
	Update( true );
}
function PasikeiteFiltras( name, default_filter_type )
{
	if( document.getElementById( "compar_" + name ).value == "" )
	{
		document.getElementById( "compar_" + name ).value = default_filter_type;
	}
	Update( true );
}
function EnterFiltroLaukeli( id, default_filter_type )
{
	document.getElementById( 'compar_' + id ).value = "*abc*"; // Geriau, kad šita parinktų vietoj default.
	//document.getElementById( 'compar_' + id ).value = default_filter_type;

	PasikeiteFiltras( id, default_filter_type );
	// Update();
}

function UzdetStandartiniFiltra( koks_standartinis )
{
	FilterON( koks_standartinis, "*abc*", true )
}


function ClearAllFilters()
{
	<?php
	if( empty( $Error ) )
	{
		foreach( $TableInfo -> AllColumnsInfo as $Key => $Value )
		{
			$HTMLname = $Value -> ColumnInfo[ 'Name' ];
			echo "FilterOFF( '$HTMLname', false );\n";
		}
	}
	?>
	Update( true );
}
</script>

</body>
</html>
