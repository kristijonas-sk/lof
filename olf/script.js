function OLF_SetLanguage()
{
	const lang = document.getElementById("lang_select").value;
	document.cookie = "lang=" + lang;
	location.reload();
}

