<?php
$session_expiration = time() + 3600 * 24 * 60; // +60 days
session_set_cookie_params($session_expiration); //Need to call this before every session_start()
session_start();

error_reporting(0);
require("wordtreeJSONLib.php");

function getHistory($conn, $req, &$result)
{
	if ( !isset($_SESSION["userid"]) )
		return FALSE;
		
	$query = sprintf("SELECT a.id,b.word,c.word,a.lexicon,a.accessed FROM history a LEFT JOIN perseusLS2 c ON a.id=c.id LEFT JOIN perseusLSJ2 b ON a.id=b.id WHERE a.userid=%s ORDER BY accessed DESC LIMIT 100;", $_SESSION["userid"]);
	//echo $query;
	if (!($res = mysql_query ($query, $conn)))
		return FALSE;
        
	$numRows = @mysql_num_rows($res);
	if ($numRows < $req->limit)
		$result->lastPage = 1;
	else
		$result->lastPage = 0;
        
    $result->lastPageUp = 1;
    
	while ($row = @mysql_fetch_array ($res))
	{
		$seq = ($req->tag_id) ? $row['seq'] : 0;

        printJSONRow($result->rows, $row[0], 0, array(($row[3] == 0) ? $row[1] : $row[2], $row[0], $row[3]), NULL);
    }
    
    return TRUE;
}


function printRows($req, $result)
{
    $conn = connect($result);
    
    getHistory($conn, $req, $result);
    /*
    if (($req->lexicon == "greek" || $req->lexicon == "slater") && !$req->regex)
        $newword1 = beta2uni($req->word);
    else
        $newword1 = $req->word;
        
    $req->escapedWord = @mysql_real_escape_string ($newword1, $conn);

	if ($req->page)
		$result->scroll = "none";
    
    if ($req->mode == "normal")
    {
        if ($req->regex)
            getRegex($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere);
        else
            getLike($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere);
    }
    else if ($req->mode == "context" && $req->page == 0)
    {
    	$seq = getSeq($conn, $req->escapedWord, $req->lexicon);
		$rowsBefore = getBefore($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere, $seq); 
		$rowsAfter = getEqualAndAfter($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere, $seq); 
        
        if ($rowsBefore == 0)
        {
            $result->select = 0;
            $result->scroll = "top";
            $result->lastPageUp = 1;
        }
        else if ($rowsAfter == 0)
        {
            $result->select = 0;
            $result->scroll = "bottom";
            $result->lastPage = 1;        
        }
    }
    else if ($req->page != 0)
    {
        if ($req->page > 0)
            getEqualAndAfter($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere, $seq);  
    }
    */

}

function validate(&$req)
{
	if ($req->limit > 500)
		$req->limit = 500;
	if ($req->limit < 10)
		$req->limit = 10;
        
 	if (!$req->page)
        $req->page = 0;
        
    if (!$req->requestTime)
		$req->requestTime = 0;
    
    if (!$req->mode)
        $req->mode = "normal";
    
    //if word is a valid regex, then we treat it as a regex, else LIKE
 	if ($req->word != "" && @preg_match($req->word, "blah") !== FALSE)
    {
        $req->mode = "normal";
        $req->regex = TRUE;
    }
    else
    {
        $req->regex = FALSE;
    }
}

if (isset($_GET['query']))
{
    $query = rawurldecode($_GET['query']);
    if (get_magic_quotes_gpc())
        $query = stripslashes($query);
    $request->query = json_decode($query);
}

$request->limit = $_GET["n"];
$request->prefix = $_GET["idprefix"];
$request->page = $_GET['page'];
$request->requestTime = $_GET['requestTime'];
$request->mode = $_GET["mode"];

$request->word = $request->query->w;
$request->lexicon = $request->query->lexicon;
$request->tag_id = $request->query->tag_id;
if (isset($request->query->tagIncludeChildren))
	$request->tagIncludeChildren = $request->query->tagIncludeChildren;
else
	$request->tagIncludeChildren = TRUE;

$request->delWordId = $request->query->delwordid;
$request->delWordSeq = $request->query->seq;
$request->addWordId = $request->query->addwordid;
$request->line = $request->query->line;

validate($request);
   
process_request($request, $result, "printRows");

?>
