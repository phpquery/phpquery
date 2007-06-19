<?php
require_once('../phpQuery.php');
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
		'*[@rel=test]',
		array(
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
			'p.after',
			'p.noTitle',
		)
	),
	array(
		"[@content*=html]",
		array(
			'meta'
		)
	),
);

_('test.html');
foreach( $tests as $k => $test ) {
	$tests[ $k ][2] = _( $test[0] )->whois();
}
foreach( $tests as $test ) {
	if ( $test[1] == $test[2] )
		print "Test '{$test[0]}' passed :)";
	else {
		print "Test '{$test[0]}' <strong>FAILED</strong> !!!";
		print_r($test[2]);
	}
	print "<br /><br />";
}
?>