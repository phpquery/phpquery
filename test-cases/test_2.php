<?php
require_once('../phpQuery/phpQuery.php');
phpQueryClass::$debug = true;
$testName = 'Filter with pseudoclass';
$testResult = array(
	'p.body',
	'p.body',
	'p.body',
);
$result = phpQueryCreateDomFromFile('test.html');
$result = $result->find('p')
	->filter('.body:gt(1)');
if ( $result->whois() == $testResult )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
print_r($result->whois());
print "\n";
?>