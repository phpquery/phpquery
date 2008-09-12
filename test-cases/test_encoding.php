<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
$result = phpQuery::newDocumentFile('test.html')
	->find('li:first')
		->find('p:first')
			->html('żźć')
		->end();
$result->dumpDie();
if ( $similarity > 95 )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
print "\n";
?>