<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;
phpQuery::extend('WebBrowser');

phpQuery::$ajaxAllowedHosts[] = 'gmail.com';
phpQuery::$ajaxAllowedHosts[] = 'google.com';
phpQuery::$ajaxAllowedHosts[] = 'www.google.com';
phpQuery::$ajaxAllowedHosts[] = 'www.google.pl';
phpQuery::$ajaxAllowedHosts[] = 'mail.google.com';

// Google search results
phpQuery::$plugins->browserGet('http://google.com/', 'success1');
/**
 *
 * @param $pq phpQueryObject
 * @return unknown_type
 */
function success1($pq) {
	print 'success1 callback';
	$pq
		->WebBrowser('success2')
			->find('input[name=q]')
			->val('phpQuery')
			->parents('form')
				->submit()
	;
}
/**
 *
 * @param $html phpQueryObject
 * @return unknown_type
 */
function success2($pq) {
	print 'success2 callback';
	print $pq
		->find('script')->remove()->end();
}

// Gmail login (not working...)
phpQuery::plugin("Scripts");
phpQuery::newDocument('<div/>')
	->script('google_login')
	->location('http://mail.google.com/')
	->toReference($pq);
if ($pq) {
	print $pq->script('safe_print');
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