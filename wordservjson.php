<?php
/* αβγ */

require("login.php");

function restyleDef($def)
{
	if (isset($_COOKIE["filter"]))
	{
		//order of $search should be same order as values in cookie
		$search = array('class="au"', 'class="tr"', 'class="qu"', 'class="fo"', 'class="bi"', 'class="ti"');
		$replace = array();

		$filters = explode(":", $_COOKIE["filter"]);
		$numFilters = count($filters);

		for ($i = 0; $i < $numFilters; $i++)
		{
			if ($filters[$i] != "")
			{
				if ($filters[$i] == "bold")
					$replace[$i] = $search[$i] . ' style="font-weight:' . $filters[$i] . ';"';
				else
					$replace[$i] = $search[$i] . ' style="color:' . $filters[$i] . ';"';
			}
			else
			{
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

function getWord($id, $wordid, $lexicon, $skipCache, $addWordLinks, $requestTime)
{
	$def = "";
	$merror = "";
	$defError = FALSE;

  $wordid = rawurldecode($wordid);
  if (get_magic_quotes_gpc())
	{
      $wordid = stripslashes($wordid);
	}

	if (!($conn = connect($merror)))
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
		$isLSJ = TRUE;
	} else if ($lexicon == "slater") {
		$l = 1;
		$isLSJ = FALSE;
	} else {
		$l = 2;
		$isLSJ = FALSE;
	}

	$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : "";
	
	$lexiconTable = getTableForLexicon($lexicon);
	
	$agent = $conn->real_escape_string($agent);
	$lexiconTable = $conn->real_escape_string($lexiconTable);
	$ip = $conn->real_escape_string($_SERVER['REMOTE_ADDR']);

	$query = "";
	if ($wordid)
	{
		$wordid = $conn->real_escape_string($wordid);

		$query .= sprintf("SELECT id INTO @WORDID FROM %s WHERE wordid='%s' LIMIT 1;", $lexiconTable, $wordid);
		$id = "@WORDID";
	}
	$query .= sprintf("SELECT a.id,a.wordid,a.word,a.unaccented_word,a.htmlDef2 %s FROM %s a %s WHERE a.id=%s LIMIT 1;", ($isLSJ) ? ",b.present,b.future,b.aorist,b.perfect,b.perfmid,b.aoristpass,b.usePP " : "", $lexiconTable, ($isLSJ) ? " LEFT JOIN greek_verbs b ON a.id=b.id-1 " : "", $id);
	$query .= sprintf("INSERT INTO log VALUES (NULL,NULL,%s,%s,'%s','%s');", $l, $id, $ip, $agent);

	if ( !$conn->multi_query($query) )
	{
		$defError = 1;
	}
	if ( !$res = $conn->store_result() )
	{
		if (!$wordid)
			$defError = 2;
		else if ( !$conn->next_result() || !$res = $conn->store_result() ){
			$defError = 4;
		}
	}

	if (!$defError && $res->num_rows > 0)
	{
		$word = $res->fetch_assoc();
	}
	else if (!$defError)
	{
		$defError = 3;
	}

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
			$errorQuery = sprintf("INSERT INTO deferrors VALUES (NULL,NULL,%s,%s,'%s','%s','%s','%s');", $defError, $myErrorNum, $myError, $myQuery, $ip, $agent);
			$errorConn->query($errorQuery);
		}

		if ($wordid)
		{
			return '{"errorMesg":"Could not find word \"' . $wordid . '\" in ' . $lexicon . '.","method":"setWord"}';
		}

		return '{"error":"Could not find word."}';
	}

	$def = $word['htmlDef2'];

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

	$def2 = restyleDef($def);

	if (!$requestTime)
	{
		$requestTime = 0;
  }

  $defname = ($_GET['defname']) ? $_GET['defname'] : "";

	$pps = trim($pps);
	if ($pps == "—, —, —, —, —, —")
	{
		$pps = "";
	}
	$json = new stdClass;

	$json->principalParts = $pps;
	$json->def = trim($def2);
	$json->defName = $defname;
	$json->word = trim($word['word']);
	$json->unaccentedWord = trim($word['unaccented_word']);
	$json->lemma = trim($word['word']);
	$json->requestTime = $requestTime;
	$json->status = trim($word['status']);
	$json->lexicon = $lexicon;
	$json->word_id = $word['id'];
	$json->wordid = $word['wordid'];
	$json->method = "setWord";

	return json_encode($json, JSON_UNESCAPED_UNICODE);
}

ob_start("ob_gzhandler");

header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: no-cache");
header("Expires: -1");

echo getWord($_GET["id"], $_GET['wordid'], $_GET["lexicon"], $_GET["skipcache"], $_GET["addwordlinks"], $_GET["requestTime"]);
?>
