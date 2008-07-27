<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
$testName = 'Selectors';
$tests = array(
	array(
		'div:first',
		array(
			'div.articles',
		)
	),
	array(
		"p:contains('title')",
		array(
			'p.title',
			'p.title',
			'p.noTitle',
		)
	),
	array(
		"p:contains('title 2')",
		array(
			'p.title',
		)
	),
	array(
		'li:eq(1)',
		array(
			'li#testID',
		)
	),
	array(
		'li:eq(1) p:eq(1)',
		array(
			'p.title',
		)
	),
	array(
		'*[rel="test"]',
		array(
			'p',
			'p'
		)
	),
	array(
		'#testID p:first',
		array(
			'p'
		)
	),
	array(
		"p:not('.title'):not('.body')",
		array(
			'p',
			'p',
			'p',
			'p.noTitle',
			'p.after',
		)
	),
	array(
		"[content*=html]",
		array(
			'meta'
		)
	),
	array(
		"li#testID, div.articles",
		array(
			'li#testID',
			'div.articles'
		)
	),
	array(
		"script[src]:not([src^=<?php])",
		array(
			'script'
		)
	),
//	array(
//		'li:not([ul/li])',
//		array(
//			'li',
//			'li#testID',
//			'li',
//			'li.nested',
//			'li.second',
//		)
//	),
	array(
		'li:has(ul)',
		array(
			'li#i_have_nested_list',
		)
	),
	array(
		'p[rel] + p',
		array(
			'p.title',
			'p.noTitle',
		)
	),
	array(
		'ul:first > li:first ~ *',
		array(
			'li#testID',
			'li',
		)
	),
);

phpQuery::newDocumentFile('test.html');
foreach( $tests as $k => $test ) {
	$tests[ $k ][2] = pq( $test[0] )->whois();
}
foreach( $tests as $test ) {
	if ( $test[1] == $test[2] )
		print "Test '{$test[0]}' PASSED :)";
	else {
		print "Test '{$test[0]}' <strong>FAILED</strong> !!!";
		print_r($test[2]);
	}
	print "<br /><br />";
}
?>