<?php
//error_reporting(E_ALL);
set_include_path(
	get_include_path()
	.':/home/bob/Sources/PHP/zend-framework/'
);

require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
$testHtml = phpQuery::newDocumentFile('test.html');
$testHtml['li:first']->append('<span class="just-added">test</span>');
$testName = 'Array Access';
if (trim($testHtml['.just-added']->html()) == 'test')
	print "Test '$testName' PASSED :)";
else {
	print "Test '$testName' <strong>FAILED</strong> !!! ";
	print "<pre>";
	print_r($testHtml['.just-added']->whois());
	print "</pre>\n";
}
print "\n";
?>