<?php
define("HOST", "localhost");
define("USER", "root");
define("PASS", "clam1234");
define("DATABASE", "philolog_us");

define("LSJ_TABLE", "lsj");
define("LS_TABLE", "ls");
define("SLATER_TABLE", "slater");

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
    $conn = new PDO('mysql:host=localhost;dbname=philolog_us', "root", "clam1234");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);  
    $conn->setAttribute(PDO::ATTR_PERSISTENT, true);

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