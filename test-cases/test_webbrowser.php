<?php
//error_reporting(E_ALL);
set_include_path(
	get_include_path()
	.':/home/bob/Sources/PHP/zend-framework/'
);

require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
//die(file_get_contents('http://google.com/search?hl=pl&q=phpQuery&btnG=Szukaj+w+Google&lr='));
phpQuery::extend('WebBrowser');
//phpQuery::$plugins->WebBrowserBind('WebBrowser');
phpQuery::$plugins->browserGet('http://google.com/', 'success1');
function success1($browser) {
//	$html = mb_convert_encoding($html, 'HTML-ENTITIES', "UTF-8");
//	die($html);
//	phpQuery::newDocument($html)
//		->WebBrowser('BrowserHandler', 'http://google.com/')
//		->find('input')->dump()->end()
//		->dump();
		// google results
	$browser
			->WebBrowser('BrowserHandler')
			->find('input[name=q]')
			->val('phpQuery')
			->parents('form')
				->submit()
		// gmail login
//		->find('#Email')
//			->val('tobiasz.cudnik@gmail.com')->end()
//		->find('#Passwd')
//			->val('XXX')
//			->parents('form')
//				->submit()
	;
//		->find('a:contains(Polski)')
//			->click();
}
/**
 *
 * @param $html phpQueryObject
 * @return unknown_type
 */
function BrowserHandler($html) {
	print 'WebBrowser callback';
	die($html);
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