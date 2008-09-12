<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
phpQuery::newDocumentFile('test.html');
pq('li#testID')
	->bind('testEvent', 'handler1')
	->parent()
		->bind('testEvent', 'handler2')
	->end();
pq('li#testID')
	->trigger('testEvent');
	
function handler1($e) {
	var_dump($e);
}
function handler2($e) {
	var_dump($e);
}
function handler3() {
	var_dump($e);
}
		
//	if ( $result->whois() == $testResult )
//		print "Test '$testName' PASSED :)";
//	else {
//		print "Test '$testName' <strong>FAILED</strong> !!! ";
//		print "<pre>";
//		print_r($result->whois());
//		print "</pre>\n";
//	}
//	print "\n";
?>