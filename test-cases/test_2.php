<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
$testName = 'Filter with pseudoclass';
$testResult = array(
	'p.body',
);
$result = phpQuery::newDocumentFile('test.html');
$result = $result->find('p')
	->filter('.body:gt(1)');
if ( $result->whois() == $testResult )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
print_r($result->whois());
print "\n";
?>