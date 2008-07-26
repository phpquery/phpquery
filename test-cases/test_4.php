<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;

// SLICE1
$testResult = array(
	'li#testID',
);
$result = phpQuery::newDocumentFile('test.html')
	->find('li')
	->slice(1, 2);
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
$result = phpQuery::newDocumentFile('test.html')
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



// Multi-insert
$result = phpQuery::newDocument('<li><span class="field1"></span><span class="field1"></span></li>')
	->find('.field1')
	->php('longlongtest');
$validResult = '<li><span class="field1"><php>longlongtest</php></span><span class="field1"><php>longlongtest</php></span></li>';
similar_text($result->htmlOuter(), $validResult, $similarity);
if ( $similarity > 80 )
	print "Test 'Multi-insert' passed :)";
else {
	print "Test 'Multi-insert' <strong>FAILED</strong> !!! ";
	print "<pre>";
	var_dump($result->htmlOuter());
	print "</pre>\n";
}
print "\n";
?>