<?php
require("blahpw.php");

define("SQLITEJWM", FALSE);


//a.id,a.wordid,a.word,a.unaccented_word,a.htmlDef2,a.status
if (SQLITEJWM) {
    define("LSJ_TABLE", "ZGREEK");
    define("LS_TABLE", "ZLATIN");
    //define("LSJ_TABLE_DEF", "ZGREEKDEFS");
    //define("LS_TABLE_DEF", "ZLATINDEFS");
    define("SLATER_TABLE", "ZSLATER");
    //define("SLATER_TABLE_DEF", "slater");

    define("ID_COL", "seq");//"Z_PK");
    define("WORDID_COL", "sortword");
    define("WORD_COL", "word");
    define("SEQ_COL", "seq");
    define("STATUS_COL", "0"); //fake it for now
    define("UNACCENTED_COL", "sortword");
    define("DEF_COL", "def");
    /*
    define("ID_COL_DEF", "");
    define("ID_COL_DEF", "");
    define("ID_COL_DEF", "");
    define("ID_COL_DEF", "");
    */
}
/*if (SQLITEJWM) {
    define("LSJ_TABLE", "ZGREEKWORDS");
    define("LS_TABLE", "ZLATINWORDS");
    define("LSJ_TABLE_DEF", "ZGREEKDEFS");
    define("LS_TABLE_DEF", "ZLATINDEFS");
    //define("SLATER_TABLE", "slater");
    //define("SLATER_TABLE_DEF", "slater");

    define("ID_COL", "Z_PK");
    //define("WORDID_COL", "wordid");
    define("WORD_COL", "ZWORD");
    define("SEQ_COL", "ZSEQ");
    define("STATUS_COL", "0"); //fake it for now
    define("UNACCENTED_COL", "zunaccentedword");

}*/
else {
    define("LSJ_TABLE", "lsj");
    define("LS_TABLE", "ls");
    define("SLATER_TABLE", "slater");

    define("ID_COL", "id");
    define("WORDID_COL", "wordid");
    define("WORD_COL", "word");
    define("SEQ_COL", "seq");
    define("STATUS_COL", "status");
    define("UNACCENTED_COL", "unaccented_word");
    define("DEF_COL", "htmlDef2");
}

function connectold(&$result)
{
 	//$conn = new PDO('mysql:host=127.0.0.1;dbname=DATABASE', USER, PASS);

	$conn = new mysqli('p:127.0.0.1', USER, PASS, DATABASE);

	if ($conn == false)
	{
		die("ERROR: Could not connect. " . mysqli_connect_error() );
	}

	$conn->set_charset("utf8");

	return $conn;
}

function connect() {
	if (SQLITEJWM) {
		$conn = new PDO('sqlite:testphilolog_us.sqlite', null, null); 
	}
	else {
    	$conn = new PDO('mysql:host=localhost;dbname=philolog_us;charset=UTF8MB4', USER, PASS);
	}
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);  
    $conn->setAttribute(PDO::ATTR_PERSISTENT, true);
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    return $conn;
}

function getTableForLexicon($lexicon)
{
    if ($lexicon == "lsj")
	{
	    return LSJ_TABLE;
	}
	else if ($lexicon == "ls")
	{
	    return LS_TABLE;
	}
	else if ($lexicon == "slater")
	{
	    return SLATER_TABLE;
	}
}

?>