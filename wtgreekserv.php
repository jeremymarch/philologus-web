<?php

error_reporting(0);
//error_reporting(E_ALL);

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);


require("wordtreeJSONLib.php");

function getSeq($pdo, $word, $lexicon, $req)
{
	$table = getTableForLexicon($lexicon);


	if ($req && $req->query->wordid)
	{
		$query = sprintf("SELECT seq FROM %s WHERE wordid = '%s' AND status = 0 ORDER BY unaccented_word LIMIT 1;", $table, $req->query->wordid);

        $pdores = $pdo->query($query, PDO::FETCH_ASSOC);
        if ($pdores === false) {
            return FALSE;
        }

		if ($pdores->rowCount() == 0) //select last seq to scroll all the way to the bottom
		{
			$query = sprintf("SELECT MIN(seq) FROM %s WHERE status = 0 LIMIT 1;", $table );

            $pdores = $pdo->query($query, PDO::FETCH_ASSOC);
            if ($pdores === false) {
                return FALSE;
            }
		}
	}
	else
	{
        $query = sprintf("SELECT seq FROM %s WHERE unaccented_word >= '%s' AND status = 0 ORDER BY unaccented_word LIMIT 1;", $table, $word);

        $pdores = $pdo->query($query, PDO::FETCH_ASSOC);
        if ($pdores === false) {
            return FALSE;
        }
        if ($pdores->rowCount() == 0) //select last seq to scroll all the way to the bottom
        {
    		$query = sprintf("SELECT MAX(seq) FROM %s WHERE status = 0 LIMIT 1;", $table );

            $pdores = $pdo->query($query, PDO::FETCH_ASSOC);
            if ($pdores === false) {
                return FALSE;
            }
        }
    }

	$row = $pdores->fetch(PDO::FETCH_NUM);
	//echo "def" . $row[0];
	return $row[0];
}

function getBefore($pdo, $req, &$result, $tagJoin, $tagSeq, $order, $tagwhere, $middleSeq)
{
    $table = getTableForLexicon($req->lexicon);

    $query = sprintf("SELECT DISTINCT a.id,a.word FROM %s a %s WHERE a.seq < %s AND status = 0 %s ORDER BY a.seq DESC LIMIT %s,%s;", $table, $tagJoin, $middleSeq, $tagwhere, $req->limit * $req->page * -1, $req->limit);

    $pdores = $pdo->query($query, PDO::FETCH_ASSOC);
    if ($pdores !== false) 
    {
        $numRows = $pdores->rowCount();
    	if ($numRows < $req->limit)
    		$result->lastPageUp = 1;
    	else
    		$result->lastPageUp = 0;

        //get rows in reverse order
        $pdores = $pdores->fetchAll();
        $pdores = array_reverse($pdores);
        foreach($pdores as $row)
        {
            $seq = ($req->tag_id) ? $row['seq'] : 0;

            printJSONRow($result->rows, $row['id'], $seq, array($row['word'], $row['id'], $seq), NULL);
        }
        return $numRows;
    }
    else {
        return 0;
    }
}

function getEqualAndAfter($pdo, $req, &$result, $tagJoin, $tagSeq, $order, $tagwhere, $middleSeq)
{
    $table = getTableForLexicon($req->lexicon);

	$query = sprintf("SELECT DISTINCT a.id,a.word FROM %s a %s WHERE a.seq >= %s AND status = 0 %s ORDER BY a.seq LIMIT %s,%s;", $table, $tagJoin, $middleSeq, $tagwhere, $req->limit * $req->page, $req->limit);

    $pdores = $pdo->query($query, PDO::FETCH_ASSOC);
    if ($pdores !== false) 
    {
    	$numRows = $pdores->rowCount();
    	if ($numRows < $req->limit)
    		$result->lastPage = 1;
    	else
    		$result->lastPage = 0;

        $first = TRUE;

    	//while ( $row = $res->fetch_assoc() )
        foreach($pdores as $row)
    	{
            //maybe switch this to do_first and move >,< + = to do_first too?
            if ($first)
            {
                $result->select = $row['id'];
                $result->scroll = $row['id'];
                $first = FALSE;
            }
    		$seq = ($req->tag_id) ? $row['seq'] : 0;

            printJSONRow($result->rows, $row['id'], $seq, array($row['word'], $row['id'], $seq), NULL);
    	}

        return $numRows;
    }
    else {
        return 0;
    }
}

function printRows($req, &$result)
{
    $conn = connect();

    if (($req->lexicon == "lsj" || $req->lexicon == "slater") && !$req->regex)
        $newword1 = beta2uni($req->word);
    else
        $newword1 = $req->word;

    $req->escapedWord = $newword1;//$conn->real_escape_string($newword1); //$conn->quote(

	if ($req->tag_id)
	{
        /*
		$tagjoin = sprintf("INNER JOIN %s_tag_x_words b ON a.id=b.word_id-1", $req->lexicon);

		$tagsAndChildren = array($req->tag_id);
		if ($req->tagIncludeChildren)
			$tagsAndChildren = array_merge($tagsAndChildren, getTagChildren($conn, "index", $req->tag_id));

		$tagwhere = "AND b.tag_id IN (" . implode(",", $tagsAndChildren) . ")";
		$order = ($req->escapedWord || $req->mode = "context") ? "a.word" : "b.seq";
        $tagSeq = ",b.seq,b.line";
        */
	}
	else
	{
        //this should probably be "a.word DESC, a.id DESC" for up and "a.word ASC, a.id ASC", but I
        //probably need to add an index "word,id" to optimize this.  also need to pass the different values to each function below
		$order = "a.word";//,a.id"; //order by id secondarily so the order is determinate even if words are spelled the same
        $tagSeq = "";
	}

	if ($req->page)
		$result->scroll = "none";

    if ($req->mode == "normal")
    {
        /*
        if ($req->regex)
            getRegex($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere);
        else
            getLike($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere);
        */
    }
    else if ($req->mode == "context" && $req->page == 0)
    {

    	$seq = getSeq($conn, $req->escapedWord, $req->lexicon, $req);

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
        //I think there is a bug here where lastpageup/lastpage gets resets when one gets a page in the other direction.
    	$seq = getSeq($conn, $req->escapedWord, $req->lexicon, $req);
        if ($req->page > 0)
            getEqualAndAfter($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere, $seq);
        else
            getBefore($conn, $req, $result, $tagjoin, $tagSeq, $order, $tagwhere, $seq);
    }

    if ($req->addWordId)
    {
    	$result->select = $req->addWordId;
    	 $result->scroll =  $req->addWordId;
    }
    if ($req->query->wordid)
    {
	$result->nocache = 1;
    }
    else
    {
	$result->nocache = 0; 
}
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

$request = new \stdClass();
if (isset($_GET['query']))
{
    $query = rawurldecode($_GET['query']);
    if (get_magic_quotes_gpc())
        $query = stripslashes($query);

$request->query = new \stdClass();
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

//$conn = connect($result);
/*
$neighbor = 0;
if ($request->delWordId && $request->tag_id && $request->delWordSeq)
{
    if (!deleteFromList($conn, $request, $neighbor))
        return mysql_error();

    $result->deletedNeighbor = $neighbor;
}
else if ($request->addWordId && $request->tag_id)
{
    if (!addToList($conn, $request))
        return mysql_error();
    else
    {
       $request->word = getWord($pdo, $request->addWordId, $request->lexicon);
    }
}
*/
process_request($request, $result, "printRows");
?>
