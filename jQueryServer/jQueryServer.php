<?php
set_include_path(
	get_include_path()
		.':/home/bob/Sources/PHP/zend-framework/'
);
/**
 * jQuery Server Plugin
 *
 * Backend class using phpQuery.
 *
 * @version 0.5
 * @author Tobiasz Cudnik tobiasz.cudnik/gmail.com
 * @link http://code.google.com/p/phpquery/
 * @todo local files support (safe...)
 * @todo respond with proper HTTP code
 * @todo use Zend_Json_Encoder
 * @todo use Zend_Json_Encoder
 * @todo 2.0: JSON RPC - Zend_Json_Server
 * @todo 2.0: XML RPC ?
 */
class jQueryServer {
	/**
	 *
	 * @var Services_JSON
	 */
	protected $json = null;
	public $calls = null;
	public $options = null;
	function __construct($data) {
		$pq = null;
		include_once(dirname(__FILE__).'/../phpQuery/phpQuery.php');
//		phpQueryClass::$debug = true;
		if (! function_exists('json_decode')) {
			include_once(dirname(__FILE__).'/JSON.php');
			$this->json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		}
		$data = $this->jsonDecode($data);
		// load document (required for first $data element)
		if (is_array($data[0]) && isset($data[0]['url'])) {
			$this->options = $data[0];
			$ajax = $this->options;
			$this->calls = array_slice($data, 1);
			$ajax['success'] = array($this, 'success');
			phpQuery::ajax($ajax);
		} else {
			throw new Exception("URL needed to download content");
			break;
		}
	}
	public function success($response) {
		$pq = phpQuery::newDocument($response);
		foreach($this->calls as $k => $r) {
			// check if method exists
			if (! method_exists(get_class($pq), $r['method'])) {
				throw new Exception("Method '{$r['method']}' not implemented in phpQuery, sorry...");
			// execute method
			} else {
				$pq = call_user_func_array(
					array($pq, $r['method']),
					$r['arguments']
				);
			}
		}
		if (! isset($this->options['dataType']))
			$this->options['dataType'] = '';
		switch(strtolower($this->options['dataType'])) {
			case 'json':
				if ( $pq instanceof PHPQUERYOBJECT ) {
					$results = array();
					foreach($pq as $node)
						$results[] = pq($node)->htmlOuter();
					print $this->jsonEncode($results);
				} else {
					print $this->jsonEncode($pq);
				}
			break;
			default:
				print $pq;
		}
		// output results
	}
	public function jsonEncode($data) {
		return function_exists('json_encode')
			? json_encode($data)
			: $this->json->encode($data);
	}
	public function jsonDecode($data) {
		return function_exists('json_decode')
			? json_decode($data, true)
			: $this->json->decode($data);
	}
}
new jQueryServer($_POST['data']);
?>