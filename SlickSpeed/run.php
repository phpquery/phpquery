<?php
include('../phpQuery/phpQuery.php');
$selectors = array(
	'*','div div','div > div','div + div','div ~ div','div[class^=dia][class$=log]','body','body div','div','div div div','div, div, div','div, a, span','.dialog','div.dialog','div .dialog','div.character, div.dialog','#speech5','div#speech5','div #speech5','div.scene div.dialog','div#scene1 div.dialog div','#scene1 #speech1','div[%class]','div[%class=dialog]','div[%class^=dia]','div[%class$=log]','div[%class*=sce]','div[%class|=dialog]','div[%class!=madeup]','div[%class~=dialog]','div:only-child','div:contains(CELIA)','div:nth-child(even)','div:nth-child(2n)','div:nth-child(odd)','div:nth-child(2n+1)','div:nth-child(n)','div:last-child','div:first-child'
);
phpQuery('source_test.html');
foreach($selectors as $query) {
	$query = str_replace('[%', '[', $query);
	$start = explode(' ', microtime());
	$result = phpQuery($query);
	$end = explode(' ', microtime());
	$time = ($end[1]-$start[1]) + ($end[0]-$start[0]);
	print "<p><strong>{$query}</strong>: {$time} | ".$result->length()." found</p>\n";
	flush();
}
?>