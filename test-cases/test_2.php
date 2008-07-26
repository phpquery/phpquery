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

$testName = 'Attributes in HTML element';
$testResult = array(
	'',
);
$result = phpQuery::newDocumentFile('test.html');
$result = $result
	->_empty()
	->attr('test', 'testValue');
$validResult = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" test="testValue"></html>
EOF;
$similarity = 0;
similar_text($result->htmlOuter(), $validResult, $similarity);
if ( $similarity > 80 )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
print "<pre>";
print $result;
print "</pre>\n";
?>