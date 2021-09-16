<?php
/*
$sample = '{"wtprefix":"test1","container":"test1Container","requestTime":"99999","selectId":"0","page":"0","lastPage":"0","lastPageUp":"1","scroll":"top","query":"a","cols":"2","arrOptions":[{"i":1,"r":["Α","abc"]},{"i":5,"r":["ἃ","abc"]},{"i":2,"r":["ἀ1","abc"]},{"i":20395,"r":["α1","abc"]},{"i":3,"r":["ἀ2","abc"]},{"i":102761,"r":["α2","abc"]},{"i":4,"r":["ἆ3","abc"]},{"i":6,"r":["ἄα","abc"]},{"i":8,"r":["ἀάβακτοι","abc"]},{"i":9,"r":["ἀαγής","abc"]}]}'; 
*/

require("login.php");

function printJSONObj(&$str, $treeObj)
{
    $str .= json_encode($treeObj);
    $str = trim($str, "[]");  //remove brackets in case array.  they'll be added on again later.  see below.
}

//children is an array of child objects which contain members for intId, arrColumns and arrChildren
function printJSONRow(&$str, $id, $seq, $columns, $children)
{
    $str .= sprintf('{"i":%s,"r":[', is_numeric($id) ? $id : '"' . addcslashes($id, '\"') . '"');
    
    foreach ($columns as $col)
    {
        $str .= sprintf('%s,', is_numeric($col) ? $col : '"' . addcslashes($col, '\"') . '"');
    }
    $str = trim($str, ",");
    $str .= ']';
    
    if ($seq)
        $str .= sprintf(',"s":%s', $seq);

    if ($children === TRUE)
    {   
        $str .= ',"h":1';
    }
    else if (is_array($children))
    {
        $str .= ',"h":1,"c":[';
        foreach ($children as $child)
        {
            printJSONRow($str, $child->id, $child->seq, $child->columns, $child->children);
        }
        $str = trim($str, ",");
        $str .= ']';
    }
    
    $str .= '},';
}

function process_request($req, $result, $printRowsCallback)
{
	//Don't set or initialize these here, they might have no value at all.
    //$result->lastPageUp = 0;
    //$result->lastPage = 0;

    //Initialize
    $result = new \stdClass();
    $result->select = -1; //selected word id
    $result->tree = 0;   //boolean
	$result->error = "";
	$result->str = "";

    //Build the Rows in the callback function
    $result->rows = "";
    $printRowsCallback($req, $result);
	$result->rows = trim($result->rows, ",");
	$result->rows = "[". $result->rows . "]";

    //Build the Header
    if ($req->con)
        $container = $req->con;
    else
        $container = $req->prefix . "Container";

	$result->head = sprintf('{%s"wtprefix":"%s","nocache":"%s","container":"%s","requestTime":"%s","selectId":"%s","page":"%s","lastPage":"%s","lastPageUp":"%s","scroll":"%s","query":"%s",%s%s"arrOptions":', (isset($result->error)) ? '"error":"' . $result->error . '",' : "" , $req->prefix, $result->nocache, $container, $req->requestTime, (isset($result->deletedNeighbor) ? $result->deletedNeighbor : $result->select), $req->page, $result->lastPage, $result->lastPageUp, (isset($result->scroll)) ? $result->scroll : "", (isset($req->word)) ? $req->word : "", ($result->roots ? '"roots":1,' : ""), ($req->rootid) ? '"parentid":' . $req->rootid . ',' : "");
    
    //Put it all together
    $result->str = $result->head . $result->rows . "}";

    //Send it out
	sendJSONResponse($result->str);
}

function sendJSONResponse($json)
{
    ob_start("ob_gzhandler");

    header('Content-Type: text/html; charset=utf-8');
    header("Cache-Control: no-cache"); //http://support.microsoft.com/kb/234067
    header("Expires: -1");

    echo $json;
}

function beta2uni($word)
{
	$newword = "";
	$len = mb_strlen($word, "UTF-8");

	for ($i = 0; $i < $len; $i++)
	{
		$letter = mb_substr($word, $i, 1, "UTF-8");

		switch ($letter)
		{
		case "a":
			$newword .= "α";
			break;
		case "A":
			$newword .= "Α";
			break;
		case "b":
			$newword .= "β";
			break;
		case "B":
			$newword .= "Β";
			break;
		case "c":
			$newword .= "ψ";
			break;
		case "C":
			$newword .= "Ψ";
			break;
		case "d":
			$newword .= "δ";
			break;
		case "D":
			$newword .= "Δ";
			break;
		case "e":
			$newword .= "ε";
			break;
		case "E":
			$newword .= "Ε";
			break;
		case "f":
			$newword .= "φ";
			break;
		case "F":
			$newword .= "Φ";
			break;
		case "g":
			$newword .= "γ";
			break;
		case "G":
			$newword .= "Γ";
			break;
		case "h":
			$newword .= "η";
			break;
		case "H":
			$newword .= "Η";
			break;
		case "i":
			$newword .= "ι";
			break;
		case "I":
			$newword .= "Ι";
			break;
		case "j":
			$newword .= "ξ";
			break;
		case "J":
			$newword .= "Ξ";
			break;
		case "k":
			$newword .= "κ";
			break;
		case "K":
			$newword .= "Κ";
			break;
		case "l":
			$newword .= "λ";
			break;
		case "L":
			$newword .= "Λ";
			break;
		case "m":
			$newword .= "μ";
			break;
		case "M":
			$newword .= "Μ";
			break;
		case "n":
			$newword .= "ν";
			break;
		case "N":
			$newword .= "Ν";
			break;
		case "o":
			$newword .= "ο";
			break;
		case "O":
			$newword .= "Ο";
			break;
		case "p":
			$newword .= "π";
			break;
		case "P":
			$newword .= "Π";
			break;
		case "q":
			$newword .= "";
			break;
		case "Q":
			$newword .= "";
			break;
		case "r":
			$newword .= "ρ";
			break;
		case "R":
			$newword .= "Ρ";
			break;
		case "s":
			$newword .= "σ";
			break;
		case "S":
			$newword .= "Σ";
			break;
		case "t":
			$newword .= "τ";
			break;
		case "T":
			$newword .= "Τ";
			break;
		case "u":
			$newword .= "θ";
			break;
		case "U":
			$newword .= "Θ";
			break;
		case "v":
			$newword .= "ω";
			break;
		case "V":
			$newword .= "Ω";
			break;
		case "w":
			$newword .= "ω";
			break;
		case "W":
			$newword .= "Ω";
			break;
		case "x":
			$newword .= "χ";
			break;
		case "X":
			$newword .= "Χ";
			break;
		case "y":
			$newword .= "υ";
			break;
		case "Y":
			$newword .= "Υ";
			break;
		case "z":
			$newword .= "ζ";
			break;
		case "Z":
			$newword .= "Ζ";
			break;

		default:
			$newword .= $letter;
			break;
		}
	}

	return $newword;
}

?>