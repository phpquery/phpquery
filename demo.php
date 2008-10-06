<?php
// just one file to include
require('phpQuery/phpQuery.php');

// intialize new DOM from markup
$doc = phpQuery::newDocument('</div>');
$doc['div']->append('<ul><li>1</li><li>2</li><li>3</li></ul>');
$doc['ul > li']
	->addClass('my-new-class')
	->filter(':last')
		->addClass('last-li');

// query all unordered lists in last used DOM
pq('ul')->insertAfter('div');

// iterate all LIs from last used DOM
foreach(pq('li') as $li) {
	// iteration returns plain DOM nodes, not phpQuery objects
	pq($li)->addClass('my-second-new-class');
}

// same as pq('anything')->htmlOuter() but on document root (returns doctype etc)
print phpQuery::getDocument();
?>