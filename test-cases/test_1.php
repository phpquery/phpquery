<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
$testName = 'Simple data insertion';
$testResult = <<<EOF
<div class="articles">
			div.articles text node
            <ul>
                
            <li>
                	<p>This is paragraph of first LI</p>
                    <p class="title">News 1 title</p>
                    <p class="body">News 1 body</p>
                </li>

<li>
                	<p>This is paragraph of first LI</p>
                    <p class="title">News 2 title</p>
                    <p class="body">News 2 body</p>
                </li>
<li>
                	<p>This is paragraph of first LI</p>
                    <p class="title">News 3</p>
                    <p class="body">News 3 body</p>
                </li>
</ul>
<p>paragraph after UL</p>
        </div>	
EOF;
$rows = array(
	array(
		'title' => 'News 1 title',
		'body'	=> 'News 1 body',
	),
	array(
		'title' => 'News 2 title',
		'body'	=> 'News 2 body',
	),
	array(
		'title' => 'News 3',
		'body'	=> 'News 3 body',
	),
);
phpQuery::newDocumentFile('test.html');
$articles = pq('.articles ul');
$rowSrc = $articles->find('li')
	->remove()
	->eq(0);
foreach( $rows as $r ) {
	$row = $rowSrc->_clone();
	foreach( $r as $field => $value ) {
		$row->find(".{$field}")
			->html($value);
//		die($row->htmlOuter());
	}
	$row->appendTo($articles);
}
$result = pq('.articles')->htmlOuter();
//print htmlspecialchars("<pre>{$result}</pre>").'<br />';
$similarity = 0.0;
similar_text($testResult, $result, $similarity);
if ( $similarity > 95 )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
print "\n";
?>