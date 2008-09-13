<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;

$testName = 'Text node append';
$result = phpQuery::newDocumentFile('test.html')
	->find('li:first')
		->find('p:first')
			->html('żźć');
if (trim($result->html()) == 'żźć')
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
print "\n";

$testName = 'HTML entite append';
$result = phpQuery::newDocumentFile('test.html')
	->find('li:first')
		->find('p:first')
			->_empty()
			->append('&eacute;');
if (trim($result->html()) == 'é')
	print "Test '{$testName}' passed :)";
else {
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
	print $result->html();
}
print "\n";

$testName = 'Append and move';
$result = phpQuery::newDocumentFile('test.html');
$li = $result->find('li:first');
$result->find('div')->_empty();
$li->html('test1-&eacute;-test1')
	->append('test2-é-test2')
	->appendTo(
		$result->find('div:first')
	);
$result = $result->find('div:first li:first');
$expected = 'test1-é-test1
test2-é-test2';
if (trim($result->html()) == $expected)
	print "Test '{$testName}' passed :)";
else {
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
	print "'".trim($result->html())."'";
}
print "\n";
?>