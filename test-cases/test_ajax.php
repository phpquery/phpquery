<?php
//error_reporting(E_ALL);
set_include_path(
	get_include_path()
	.':/home/bob/Sources/PHP/zend-framework/'
);

require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
phpQuery::$ajaxAllowedHosts[] = 'wikipedia.org';

//$pq = phpQuery::ajax(array(
//	'url' => 'http://wikipedia.org/',
//	'success' => 'v87shs79d8fhs9d'
//));
//function v87shs79d8fhs9d($html) {
//	$title = phpQuery::newDocument($html)->find('title');
//	$testName = 'Simple AJAX';
//	if ( strpos(strtolower($title->html()), 'wikipedia') !== false )
//		print "Test '$testName' PASSED :)";
//	else {
//		print "Test '$testName' <strong>FAILED</strong> !!! ";
//		print "<pre>";
//		print_r($title->whois());
//		print "</pre>\n";
//	}
//	print "\n";
//}


$testName = 'Load';
$test = phpQuery::newDocumentFile('test.html')
	->find('div:first')
	->load('http://wikipedia.org/ div[lang]');
if (pq('div[lang]')->size())
	print "Test '$testName' PASSED :)";
else {
	print "Test '$testName' <strong>FAILED</strong> !!! ";
	print "<pre>";
	print "</pre>\n";
}
print "\n";
?>