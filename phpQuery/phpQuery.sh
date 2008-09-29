#!/usr/bin/php
<?php
if ($argv[1] == 'help' || $argv[1] == '--help' || $argv[1] == '-h')
	die("Usage: phpQuery URL --method1 arg1 arg2 --method2 --method3 arg1 ...\n");
require_once('phpQuery.php');
//phpQuery::$debug = true;
//var_dump($argv);
phpQuery::ajaxAllowURL($argv[1]);
phpQuery::get($argv[1], 'czx023gcs9sg83');
function czx023gcs9sg83($html) {
	global $argv;
	$pq = phpQuery::newDocument($html);
	$method = null;
	$params = array();
	foreach(array_slice($argv, 2) as $param) {
		if (strpos($param, '--') === 0) {
			if ($method) {
				$pq = call_user_func_array(array($pq, $method), $params);
			}
			$method = substr($param, 2);
			$params = array();
		} else {
			$params[] = $param;
		}
	}
	if ($method)
		$pq = call_user_func_array(array($pq, $method), $params);
	print trim($pq)."\n";
}
?>