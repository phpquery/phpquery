<?php
require_once('../phpQuery.php');
phpQueryClass::$debug = true;
$testName = 'Filter with pseudoclass';
$testResult = array('p.body');
$result = phpQuery('test.html')
	->find('p')
	->filter('.body:gt(1)');
if ( $result->whois() == $testResult )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
print "\n";
?>