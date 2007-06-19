<?php
require_once('../phpQuery.php');
$testName = '1';
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
_('test.html');
$articles = _('.articles ul');
$row = clone $articles->find('li');
$row->remove()->eq(0);
foreach( $rows as $r ) {
	$row->_copy();
	foreach( $r as $field => $value ) {
		$row->find(".{$field}")
				->html( $value )
			->end();
	}
	$row->appendTo('.articles ul')
	// DOESNT WORK
//	$row->appendTo($articles)
		->end();
}
$result = _('.articles')->html();
// DOESNT WORK
// print $articles->html();
$similarity = 0.0;
similar_text($testResult, $result, $similarity);
if ( $similarity > 95 )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
print "\n";
?>