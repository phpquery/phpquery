<?php
new jQueryPhpProxy($_POST['data']);
class jQueryPhpProxy{
	function __construct($data) {
		$_ = null;
		include_once(dirname(__FILE__).'/../phpQuery/phpQuery.php');
//		phpQueryClass::$debug = true;
		include_once(dirname(__FILE__).'/JSON.php');
		$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		$data = $json->decode($data);
		$count = count($data);
		foreach($data as $k => $r) {
			// load document (required for firs	t $data element)
			if (! $k || is_string($r) ) {
				if ( is_string($r) )
					$_ = phpQuery($r, false);
				if (! is_a($_, 'phpQueryClass')) {
					throw new Exception("URL needed to download content");
					break;
				}
			// check if method exists
			} else if (! method_exists(get_class($_), $r['method'])) {
				throw new Exception("Method '{$r['method']}' not implemented in phpQuery");
			// execute method
			} else {
				$_ = call_user_func_array(
					array($_, $r['method']),
					$r['arguments']
				);
			}
		}
		// output results
		if ( $_ instanceof self ) {
			$results = array();
			foreach($_ as $__) {
				$results[] = (string)$__;
			}
			print $json->encode($results);
		} else {
			print $_;
		}
	}
}
?>