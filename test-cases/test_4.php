<?php
require_once('../phpQuery/phpQuery.php');
phpQueryClass::$debug = true;

// SLICE1
$testResult = array(
	'li#testID',
);
$result = phpQuery('test.html')
	->find('li')
	->slice(1, 1);
if ( $result->whois() == $testResult )
	print "Test 'Slice1' passed :)";
else {
	print "Test 'Slice1' <strong>FAILED</strong> !!! ";
	print "<pre>";
	print_r($result->whois());
	print "</pre>\n";
}
print "\n";

// SLICE2
$testResult = array(
	'li#testID',
	'li',
	'li#i_have_nested_list',
	'li.nested',
);
$result = phpQuery('test.html')
	->find('li')
	->slice(1, -1);
if ( $result->whois() == $testResult )
	print "Test 'Slice2' passed :)";
else {
	print "Test 'Slice2' <strong>FAILED</strong> !!! ";
	print "<pre>";
	print_r($result->whois());
	print "</pre>\n";
}
print "\n";
?>