<?php
class MenuItem
{
	public $Name, $Url, $Title, $Rights = Array();
	public function __construct( $Name, $Url, $RightsAsString, $Title )
	{
		$this -> Name = $Name;
		$this -> Url = $Url;
		$this -> Title = $Title;
		$this -> Rights = explode( ',', $RightsAsString );
	
	}
	public function GetNameHtml()
	{
		// return  htmlspecialchars( $this -> Name , ENT_COMPAT|ENT_HTML5|ENT_SUBSTITUTE, 'UTF-8' );
		return $this -> Name;
	}
}
$Menu = Array();


$Menu[] = new MenuItem( "<img src=\"img/ico/home.svg\" class=\"meniu_ico\">", "welcome.php", "1,2,3,4,5", "Pradžia."  );
$Menu[] = new MenuItem( "<img src=\"img/ico/database.svg\" class=\"meniu_ico\">", "meniu_admin.php", "1", "Duombazės valdymas."  );
$Menu[] = new MenuItem( "<img src=\"img/ico/book.svg\" class=\"meniu_ico\">", "gaminiai", "1,2,3,4,5", "Operos lab gaminami gaminiai" );


?>

<div id="meniu" style="background:linear-gradient(to bottom, <?php echo $UserInfo['favorit_color']; ?> 0%, RGBA(255,255,255,1) 30%, RGBA(255,255,255,1) 100% );border-color:<?= $UserInfo['favorit_color']; ?>;">

<div>
	<img src="img/logo.png" style="height:3rem;">
</div>

<div>
<?php
$txt_buffer = "";
if( isset($UserInfo['user_rigts']) )
{
	foreach( $Menu as $index => $Item )
	{
		if( in_array( $UserInfo['user_rigts'], $Item -> Rights ) )
		{
			$txt_buffer .= " <a href=\"".$Item -> Url."\" title=\"".$Item -> Title."\">". $Item -> GetNameHtml() ."</a>&nbsp;&nbsp;&nbsp;";
		}
	}
	// Last bullet is removed:
	$txt_buffer = mb_substr( $txt_buffer, 0, -1 );
}
else
{
	$txt_buffer = "Error: can't load meniu.";
}
echo $txt_buffer;
?>
</div>

<div style="font-size:2.1rem;">
	<?= htmlspecialchars( $UserInfo['loginame'] , ENT_COMPAT|ENT_HTML5|ENT_SUBSTITUTE, 'UTF-8' );  ?>
	<a href="logout.php">
	<img src="img/ico/logout.svg" title="Atsijungti" style="height:1.4rem;vertical-align:middle">
	</a>
</div>



</div>

