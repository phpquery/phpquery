<?php
require('phpQuery/phpQuery.php');

// INITIALIZE IT
// $doc = phpQuery::newDocumentHTML($markup);
// $doc = phpQuery::newDocumentXML();
// $doc = phpQuery::newDocumentFileXHTML('test.html');
// $doc = phpQuery::newDocumentFilePHP('test.php');
// $doc = phpQuery::newDocument('test.xml', 'application/rss+xml');
// this one defaults to text/html in utf8
$doc = phpQuery::newDocument('<div/>');

// FILL IT
// array syntax works like ->find() here
$doc['div']->append('<ul></ul>');
// array set changes inner html
$doc['div ul'] = '<li>1</li><li>2</li><li>3</li>';

// MANIPULATE IT
// almost everything can be a chain
$li;
$doc['ul > li']
	->addClass('my-new-class')
	->filter(':last')
		->addClass('last-li')
// save it anywhere in the chain
		->toReference($li);

// SELECT IT
// pq(); is using selected document as default
phpQuery::selectDocument($doc);
// documents are selected when created, iterated or by above method
// query all unordered lists in last selected document
pq('ul')->insertAfter('div');

// INTERATE IT
// all LIs from last selected DOM
foreach(pq('li') as $li) {
	// iteration returns PLAIN dom nodes, NOT phpQuery objects
	$tagName = $li->tagName;
	$childNodes = $li->childNodes;
	// so you NEED to wrap it within phpQuery, using pq();
	pq($li)->addClass('my-second-new-class');
}

// PRINT OUTPUT
// 1st way
print phpQuery::getDocument($doc->getDocumentID());
// 2nd way
print phpQuery::getDocument(pq('div')->getDocumentID());
// 3rd way
print pq('div')->getDocument();
// 4th way
print $doc->htmlOuter();
// 5th way
print $doc;
// another...
print $doc['ul'];