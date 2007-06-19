<?php
require_once('../phpQuery.php');
$testName = 'Selectors';
$tests = array(
	array(
		'div:first',
		array(
			'div.artciles',
		)
	),
	array(
		"p:contains('title 2')",
		array(
			'p.title',
		)
	),
	array(
		'li:eq(1) p:eq(1)',
		array(
			'p.body',
		)
	),
	array(
		'head[@name]',
		array(
			'meta'
		)
	),
	array(
		'',
		array(
		)
	),
	array(
		'',
		array(
		)
	),
);

$similarity = 0.0;
similar_text($testResult, $result, $similarity);
if ( $similarity > 95 )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
print "\n";
?>