<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;


//$doc = phpQuery::newDocumentXML('<article><someMarkupStuff/><p>p</p></article>');
//print $doc['article']->children(':empty')->get(0)->tagName;

$doc = phpQuery::newDocumentFile('test.html');
setlocale(LC_ALL, 'pl_PL.UTF-8');
$string =  strftime('%B %Y', time());
$doc['p:first']->append($string)->dump();