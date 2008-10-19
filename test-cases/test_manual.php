<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;


//$doc = phpQuery::newDocumentXML('<article><someMarkupStuff/><p>p</p></article>');
//print $doc['article']->children(':empty')->get(0)->tagName;

//$doc = phpQuery::newDocumentFile('test.html');
//setlocale(LC_ALL, 'pl_PL.UTF-8');
//$string =  strftime('%B %Y', time());
//$doc['p:first']->append($string)->dump();

/*
$doc1 = phpQuery::newDocumentFileXHTML('doc1.html');
$doc2 = phpQuery::newDocumentFileXHTML('doc2.html');
$doc3 = phpQuery::newDocumentFileXHTML('doc3.html');
$doc4 = phpQuery::newDocumentFileXHTML('doc4.html');
$doc2['body']
	->append($doc3['body >*'])
	->append($doc4['body >*']);
$doc1['body']
	->append($doc2['body >*']);
print $doc1->plugin('Scripts')->script('safe_print');
*/
//$doc = phpQuery::newDocument('<p> p1 <b> b1 </b> <b> b2 </b> </p><p> p2 </p>');
//print $doc['p']->contents()->not('[nodeType=1]');
