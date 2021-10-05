<?php
/* αβγ */

require("login.php");
/*
function restyleDef($def)
{
	if (isset($_COOKIE["filter"]))
	{
		//order of $search should be same order as values in cookie
		$search = array('class="au', 'class="tr', 'class="qu', 'class="fo', 'class="bi', 'class="ti');
		$replace = array();

		$filters = explode(":", $_COOKIE["filter"]);
		$numFilters = count($filters);

		for ($i = 0; $i < $numFilters; $i++)
		{
			if ($filters[$i] != "") {
				$replace[$i] = $search[$i] . ' ' . $filters[$i] . 'text';
			}
			else {
				$replace[$i] = $search[$i];
			}
		}

		return str_replace($search, $replace, $def);
	}
	else
	{
		return $def;
	}
}
*/
function getWord($id, $wordid, $lexicon, $skipCache, $addWordLinks, $requestTime)
{
	$def = "";
	$merror = "";
	$defError = FALSE;

	$conn = connect();
	if ($conn === false)
	{
		return '{"error":"Connection error."}';
	}
	
	if ($lexicon == "latin")
	{
	    $lexicon = "ls";
	}
	else if ($lexicon == "greek")
	{
	    $lexicon = "lsj";
	}

	if ($lexicon == "lsj") {
		$l = 0;
		$isLSJ = FALSE;//TRUE;
	} else if ($lexicon == "slater") {
		$l = 1;
		$isLSJ = FALSE;
	} else {
		$l = 2;
		$isLSJ = FALSE;
	}

	$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
	
	$lexiconTable = getTableForLexicon($lexicon);
	
	$ip = $_SERVER['REMOTE_ADDR'];

	$query = "";
	/*
	if ($wordid)
	{
		$wordid = $conn->real_escape_string($wordid);

		//$query .= sprintf("SELECT id INTO @WORDID FROM %s WHERE wordid='%s' LIMIT 1;", $lexiconTable, $wordid);
		//$id = "@WORDID";
	}
	*/
	$query .= sprintf("SELECT a.%s,a.%s,a.%s,a.%s,a.%s %s FROM %s a %s WHERE a.%s=%s LIMIT 1;", ID_COL,WORDID_COL,WORD_COL,UNACCENTED_COL,DEF_COL,($isLSJ) ? ",b.present,b.future,b.aorist,b.perfect,b.perfmid,b.aoristpass,b.usePP " : "", $lexiconTable, ($isLSJ) ? " LEFT JOIN greek_verbs b ON a.id=b.id-1 " : "", ID_COL,$id);
	//echo $query;
	//$query .= sprintf("INSERT INTO log VALUES (NULL,NULL,%s,%s,'%s','%s');", $l, $id, $ip, $agent);

    $pdores = $conn->query($query, PDO::FETCH_ASSOC);
    if ($pdores === false) {
        $defError = 3;
    }
    else {
    	$word = $pdores->fetch(PDO::FETCH_ASSOC);
    }

	/*
	if ($defError !== FALSE)
	{
		if ($errorConn = connect($merror)) //NEW CONNECT TO AVOID MYSQL OUT-OF-SEQUENCE ERRORS
		{
			$myError = "";
			$myErrorNum = $errorConn->real_escape_string($conn->errno);
			$myError = $errorConn->real_escape_string($conn->error);
			$myQuery = $errorConn->real_escape_string($query);

			if(is_object($res))
			{
        $res->free_result();
			}
			while( $conn->next_result() )
			{
				$res = $conn->store_result();
				if ($res)
				{
					$myError .= " AND " . $errorConn->real_escape_string($conn->error);
					$res->free_result();
				}
			}
			//$errorQuery = sprintf("INSERT INTO deferrors VALUES (NULL,NULL,%s,%s,'%s','%s','%s','%s');", $defError, $myErrorNum, $myError, $myQuery, $ip, $agent);
			//$errorConn->query($errorQuery);
		}
		
		if ($wordid)
		{
			return '{"errorMesg":"Could not find word \"' . $wordid . '\" in ' . $lexicon . '.","method":"setWord"}';
		}

		return '{"error":"Could not find word."}';
	}
	*/
	$def = $word[DEF_COL];

	if ($isLSJ && $word['usePP'] == 1)
	{
		$spacer = "—";

		$present = ($word['present']) ? str_replace ( "," , " or " , $word['present'] ) : $spacer;
		$future = ($word['future']) ? str_replace ( "," , " or " , $word['future'] ) : $spacer;
		$aorist = ($word['aorist']) ? str_replace ( "," , " or " , $word['aorist'] ) : $spacer;
		$perfect = ($word['perfect']) ? str_replace ( "," , " or " , $word['perfect'] ) : $spacer;
		$perfmid = ($word['perfmid']) ? str_replace ( "," , " or " , $word['perfmid'] ) : $spacer;
		$aoristpass = ($word['aoristpass']) ? str_replace ( "," , " or " , $word['aoristpass'] ) : $spacer;

		$pps = $present  . ", " . $future . ", " . $aorist . ", " . $perfect . ", " . $perfmid . ", " . $aoristpass;
	}

	$def2 = $def;//restyleDef($def);

	if (!$requestTime)
	{
		$requestTime = 0;
  }

  $defname = (isset($_GET['defname'])) ? $_GET['defname'] : "";

	if (isset($pps)) {
		$pps = trim($pps);
		if ($pps == "—, —, —, —, —, —")
		{
			$pps = "";
		}
	}
	$json = new stdClass;

	$json->principalParts = (isset($pps)) ? $pps : "";
	$json->def = trim($def2);
	$json->defName = $defname;
	$json->word = trim($word[WORD_COL]);
	$json->unaccentedWord = trim($word[UNACCENTED_COL]);
	$json->lemma = trim($word[WORD_COL]);
	$json->requestTime = $requestTime;
	$json->status = "0";//trim($word[STATUS_COL]);
	$json->lexicon = $lexicon;
	$json->word_id = $word[ID_COL];
	$json->wordid = $word[UNACCENTED_COL];
	$json->method = "setWord";

	return json_encode($json, JSON_UNESCAPED_UNICODE);
}

ob_start("ob_gzhandler");

header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: no-cache");
header("Expires: -1");

$id = NULL;
$wordid = NULL;
$lexicon  = NULL;
$skipcache  = NULL;
$addwordlinks = NULL;
$requestTime = NULL;

if (isset($_GET["id"])) {
	$id = $_GET["id"];
}
if (isset($_GET['wordid'])) {
	$wordid = $_GET['wordid'];
}

if (isset($_GET["lexicon"])) {
	$lexicon = $_GET["lexicon"];
}

if (isset($_GET["skipcache"])) {
	$skipcache = $_GET["skipcache"];
}

if (isset($_GET["addwordlinks"])) {
	$addwordlinks = $_GET["addwordlinks"];
}

if (isset($_GET["requestTime"])) {
	$requestTime = $_GET["requestTime"];
}

echo getWord($id, $wordid, $lexicon, $skipcache, $addwordlinks, $requestTime);
?>
