<?php
require('phpQuery/phpQuery.php');

// intialize new DOM from markup
$doc = phpQuery::newDocument('</div>');
// fill it
// array syntax works like ->find()
$doc['div']->append('<ul></ul>');
$doc['div ul']->html('<li>1</li><li>2</li><li>3</li>');
// manipulate it
// almost everything can be a chain
$doc['ul > li']
	->addClass('my-new-class')
	->filter(':last')
		->addClass('last-li');

// query all unordered lists in last selected Document
// Documents are selected when created, iterated or by phpQuery::selectDocument()
pq('ul')->insertAfter('div');

// iterate all LIs from last selected DOM
foreach(pq('li') as $li) {
	// iteration returns plain DOM nodes, not phpQuery objects
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
print $doc->outerHTML();
// 5th way
print $doc;