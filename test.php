<?php
require('phpQuery/phpQuery.php');

phpQuery::newDocument('<div>mydiv<ul><li>1</li><li>2</li><li>3</li></ul></div>')
	->find('ul >li')
		->addClass('my-new-class')
		->filter(':last')
			->addClass('last-li');
			
pq('ul')->insertAfter('div');

foreach(pq('li') as $li) {
	$li->addClass('my-second-new-class');
}

print phpQuery::getDocument();
?>