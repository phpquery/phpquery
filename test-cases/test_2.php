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
	print "Test '{$testName}' PASSED :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
print_r($result->whois());
print "\n";




$testName = 'Loading document without meta charset';
$result = phpQuery::newDocumentFile('test.html')
	->_empty();
//var_dump((string)$result->htmlOuter());
$result = phpQuery::newDocument($result->htmlOuter());
$validResult = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/xhtml"><head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8" /></head></html>
EOF;
$similarity = 0;
similar_text($result->htmlOuter(), $validResult, $similarity);
if ( $similarity > 90 )
	print "Test '{$testName}' PASSED :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
print "<pre>";
print $result;
print "</pre>\n";




$testName = 'Attributes in HTML element';
$validResult = 'testValue';
$result = phpQuery::newDocumentFile('test.html')
	->_empty()
	->attr('test', $validResult);
$result = phpQuery::newDocument($result->htmlOuter())
	->attr('test');
//similar_text($result->htmlOuter(), $validResult, $similarity);
if ( $result == $validResult )
	print "Test '{$testName}' PASSED :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
print "<pre>";
print $result;
print "</pre>\n";
?>