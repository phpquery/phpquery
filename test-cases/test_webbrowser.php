<?php
//error_reporting(E_ALL);
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
//die(file_get_contents('http://google.com/search?hl=pl&q=phpQuery&btnG=Szukaj+w+Google&lr='));
phpQuery::extend('WebBrowser');
phpQuery::$ajaxAllowedHosts[] = 'gmail.com';
phpQuery::$ajaxAllowedHosts[] = 'google.com';
phpQuery::$ajaxAllowedHosts[] = 'www.google.com';
phpQuery::$ajaxAllowedHosts[] = 'www.google.pl';
phpQuery::$ajaxAllowedHosts[] = 'mail.google.com';
//phpQuery::$ajaxAllowedHosts[] = '';
//phpQuery::$plugins->WebBrowserBind('WebBrowser');
phpQuery::$plugins->browserGet('http://google.com/', 'success1');
/**
 *
 * @param $pq phpQueryObject
 * @return unknown_type
 */
function success1($pq) {
	print 'success1 callback';
//	print 'SETCOOKIE'.$pq->document->xhr->getLastResponse()->getHeader('Set-Cookie');
	$pq
		->WebBrowser('success2')
		/* google results */
			->find('input[name=q]')
			->val('phpQuery')
			->parents('form')
				->submit()
		/* gmail login */
		// it doesnt work and i dont know why... :(
//		->find('#Email')
//			->val('XXX@gmail.com')->end()
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
function success2($pq) {
//	die(var_dump($pq->document->xhr->getCookieJar()->getMatchingCookies('http://mail.google.com/mail/?ui=html&zy=l')));
	print 'success2 callback';
	print $pq
		->find('script')->remove()->end();
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