<?php
/**
 * jQuery port to PHP.
 * phpQuery is chainable DOM selector & manipulator.
 * Compatible with jQuery 1.2.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @link http://code.google.com/p/phpquery/
 * @link http://phpquery-library.blogspot.com/
 * @link http://jquery.com
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @version 0.9.4 beta2
 * @package phpQuery
 */

// class names for instanceof
define('DOMDOCUMENT', 'DOMDocument');
define('DOMELEMENT', 'DOMElement');
define('DOMNODELIST', 'DOMNodeList');
define('DOMNODE', 'DOMNode');
//define('PHPQUERYOBJECT', 'phpQueryObject');

/**
 * Static namespace for phpQuery functions.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
abstract class phpQuery {
	public static $debug = false;
	public static $documents = array();
	public static $lastDomId = null;
//	public static $defaultDoctype = 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';
	public static $defaultDoctype = '';
	public static $defaultEncoding = 'UTF-8';
	/**
	 * @todo
	 * @var unknown_type
	 */
	public static $pluginsAutoload = array(
//		'iframe',
	);
	/**
	 * Static namespace for plugins.
	 *
	 * @var object
	 */
	public static $plugins = array();
	/**
	 * List of loaded plugins.
	 *
	 * @var unknown_type
	 */
	public static $pluginsLoaded = array();
	public static $pluginsMethods = array();
	public static $pluginsStaticMethods = array();
	public static $ajaxAllowedHosts = array(
		'.'
	);
	/**
	 * AJAX settings.
	 *
	 * @var array
	 * XXX should it be static or not ?
	 */
	public static $ajaxSettings = array(
		'url' => '',//TODO
		'global' => true,
		'type' => "GET",
		'timeout' => null,
		'contentType' => "application/x-www-form-urlencoded",
		'processData' => true,
//		'async' => true,
		'data' => null,
		'username' => null,
		'password' => null,
		'accepts' => array(
			'xml' => "application/xml, text/xml",
			'html' => "text/html",
			'script' => "text/javascript, application/javascript",
			'json' => "application/json, text/javascript",
			'text' => "text/plain",
			'_default' => "*/*"
		)
	);
	public static $lastModified = null;
	public static $active = 0;
	/**
	 * Multi-purpose function.
	 * Use pq() as shortcut.
	 *
	 * *************
	 * 1. Import HTML into existing DOM (without any attaching):
	 * - Import into last used DOM:
	 *   pq('<div/>')				// DOESNT accept text nodes at beginning of input string !
	 * - Import into DOM with ID from $pq->getDocumentID():
	 *   pq('<div/>', 'domId')
	 * - Import into same DOM as DOMNode belongs to:
	 *   pq('<div/>', DOMNode)
	 * - Import into DOM from phpQuery object:
	 *   pq('<div/>', phpQuery)
	 * *************
	 * 2. Run query:
	 * - Run query on last used DOM:
	 *   pq('div.myClass')
	 * - Run query on DOM with ID from $pq->getDocumentID():
	 *   pq('div.myClass', 'domId')
	 * - Run query on same DOM as DOMNode belongs to and use node(s)as root for query:
	 *   pq('div.myClass', DOMNode)
	 * - Run query on DOM from $phpQueryObject and use object's stack as root nodes for query:
	 *   pq('div.myClass', phpQuery)
	 *
	 * @param string|DOMNode|DOMNodeList|array	$arg1	HTML markup, CSS Selector, DOMNode or array of DOMNodes
	 * @param string|phpQuery|DOMNode	$context	DOM ID from $pq->getDocumentID(), phpQuery object (determines also query root) or DOMNode (determines also query root)
	 *
	 * @return	phpQuery|false			phpQuery object or false in case of error.
	 */
	/**
	 * Enter description here...
	 *
	 * @return false|phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function pq($arg1, $context = null) {
		// TODO support DOMNodes as $context, find out ownerDocument, search loaded DOMs
		if (! $context) {
			$domId = self::$lastDomId;
			if (! $domId)
				throw new Exception("Can't use last used DOM, because there isn't any. Use phpQuery::newDocument() instead.");
//		} else if (is_object($context) && ($context instanceof PHPQUERY || is_subclass_of($context, 'phpQueryObject')))
		} else if (is_object($context) && $context instanceof phpQueryObject)
			$domId = $context->domId;
		else if ($context instanceof DOMDOCUMENT) {
			$domId = self::getDocumentID($context);
			if (! $domId) {
				//throw new Exception('Orphaned DOMDocument');
				$domId = self::newDocument($context)->getDocumentID();
			}
		} else if ($context instanceof DOMNODE) {
			$domId = self::getDocumentID($context);
			if (! $domId){
				throw new Exception('Orphaned DOMNode');
//				$domId = self::newDocument($context->ownerDocument);
			}
		} else
			$domId = $context;
		if ($arg1 instanceof phpQueryObject) {
//		if (is_object($arg1) && (get_class($arg1) == 'phpQueryObject' || $arg1 instanceof PHPQUERY || is_subclass_of($arg1, 'phpQueryObject'))) {
			/**
			 * Return $arg1 or import $arg1 stack if document differs:
			 * pq(pq('<div/>'))
			 */
			if ($arg1->domId == $domId)
				return $arg1;
			$class = get_class($arg1);
			// support inheritance by passing old object to overloaded constructor
			$phpQuery = $class != 'phpQuery'
				? new $class($arg1, $domId)
				: new phpQueryObject($domId);
			$phpQuery->elements = array();
			foreach($arg1->elements as $node)
				$phpQuery->elements[] = $phpQuery->DOM->importNode($node, true);
			return $phpQuery;
		} else if ($arg1 instanceof DOMNODE || (is_array($arg1) && isset($arg1[0]) && $arg[0] instanceof DOMNODE)) {
			/**
			 * Wrap DOM nodes with phpQuery object, import into document when needed:
			 * pq(array($domNode1, $domNode2))
			 */
			$phpQuery = new phpQueryObject($domId);
			if (!($arg1 instanceof DOMNODELIST) && ! is_array($arg1))
				$arg1 = array($arg1);
			$phpQuery->elements = array();
			foreach($arg1 as $node)
				$phpQuery->elements[] = ! $node->ownerDocument->isSameNode($phpQuery->DOM)
					? $phpQuery->DOM->importNode($node, true)
					: $node;
			return $phpQuery;
		} else if (self::isMarkup($arg1)) {
			/**
			 * Import HTML:
			 * pq('<div/>')
			 */
			$phpQuery = new phpQueryObject($domId);
			$phpQuery->importMarkup($arg1);
			return $phpQuery;
		} else {
			/**
			 * Run CSS query:
			 * pq('div.myClass')
			 */
			$phpQuery = new phpQueryObject($domId);
//			if ($context && ($context instanceof PHPQUERY || is_subclass_of($context, 'phpQueryObject')))
			if ($context && $context instanceof PHPQUERYOBJECT)
				$phpQuery->elements = $context->elements;
			else if ($context && $context instanceof DOMNODELIST) {
				$phpQuery->elements = array();
				foreach($context as $node)
					$phpQuery->elements[] = $node;
			} else if ($context && $context instanceof DOMNODE)
				$phpQuery->elements = array($context);
			return $phpQuery->find($arg1);
		}
	}
	/**
	 * Sets defaults document to $id. Document has to be loaded prior
	 * to using this method.
	 * $id can be retrived via getDocumentID() or getDocumentIDRef().
	 *
	 * @param unknown_type $id
	 */
	public static function selectDocument($id) {
		self::$lastDomId = $id;
	}
	/**
	 * Returns document with id $id or last selected.
	 * $id can be retrived via getDocumentID() or getDocumentIDRef().
	 * Chainable.
	 *
	 * @see phpQuery::selectDocument()
	 * @param unknown_type $id
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function getDocument($id = null) {
		if ($id)
			self::selectDocument($id);
		else
			$id = phpQuery::$lastDomId;
		return new phpQueryObject($id);
	}
	/**
	 * Creates new document from $html.
	 * Chainable.
	 *
	 * @param unknown_type $html
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @TODO support DOMDocument
	 */
	public static function newDocument($html) {
		$domId = self::createDom($html);
		return new phpQueryObject($domId);
	}
	/**
	 * Creates new document from file $file.
	 * Chainable.
	 *
	 * @param string $file URLs allowed. See File wrapper page at php.net for more supported sources.
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function newDocumentFile($file) {
		$domId = self::createDomFromFile($file);
		return new phpQueryObject($domId);
	}
	protected static function createDomFromFile($file, $domId = null) {
		return self::createDom(
			file_get_contents($file, $domId)
		);
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $html
	 * @param unknown_type $domId
	 * @return unknown New DOM ID
	 * @todo support PHP tags in input
	 */
	protected static function createDom($html, $domId = null) {
		$id = $domId
			? $domId
			: md5(microtime());
		$document = null;
		if ($html instanceof DOMDOCUMENT) {
			if (self::getDocumentID($html)) {
				// document already exists in phpQuery::$documents, make a copy
				$document = clone $html;
			} else {
				// new document, add it to phpQuery::$documents
				$document = $html;
			}
		} else {
			$document = new DOMDocument();
		}
		// create document
		phpQuery::$documents[ $id ] = array(
			'documentFragment' => true,
			'document' => $document,
			'eventNodes' => array(),
			'eventGlobals' => array(),
			'xpath' => null,
		);
		$DOM =& phpQuery::$documents[ $id ];
		if (!($html instanceof DOMDOCUMENT)) {
			// load markup
			if (! self::loadHtml($DOM, $html)) {
				throw new Exception("Can't load '{$html}'");
				return;
			}
		}
		$DOM['xpath'] = new DOMXPath(
			$DOM['document']
		);
		// remember last document
		return self::$lastDomId = $id;
	}
	protected static function loadHtmlFile(&$DOM, $file) {
		return self::loadHtml($DOM, file_get_contents($file));
	}
	protected static function isXML($markup) {
		return strpos($markup, '<?xml') !== false;
	}
	protected static function loadHtml(&$DOM, $html) {
		if (! self::isXML($html)) {
			self::checkDocumentFragment($DOM, $html);
			if (! self::containsEncoding($html))
				$html = self::appendEncoding($html);
			//			$html = mb_convert_encoding($html, 'HTML-ENTITIES', self::$defaultEncoding);
			//			$html = '<meta http-equiv="Content-Type" content="text/html;charset='.self::$defaultEncoding.'">'.$html;
			// TODO if ! self::containsEncoding() && self::containsHead() then attach encoding inside head
			// check comments on php.net about problems with charset when loading document without encoding as first line
			return @$DOM['document']->loadHTML($html);
		} else {
			return $DOM['document']->loadXML($html);
		}
	}
	protected static function checkDocumentFragment(&$DOM, $html) {
		if ( stripos($html, '<html') !== false ) {
			$DOM['documentFragment'] = false;
//			var_dump(array($DOM, stripos($html, '<html')));
		}
		return $DOM['documentFragment'];
//		else
//			var_dump(stripos($html, '<html'));
	}
	protected static function appendEncoding($html, $charset = null) {
		$charset = is_null($charset)
			? self::$defaultEncoding
			: $charset;
		$meta = '<meta http-equiv="Content-Type" content="text/html;charset='.$charset.'">';
		if (strpos($html, '<head') === false) {
			if (strpos($html, '<html') === false) {
				return $meta.$html;
			} else {
				return preg_replace(
					'@<html(.*?)(?(?<!\?)>)@s',
					"<html\\1><head>{$meta}</head>",
					$html
				);
			}
		} else {
			return preg_replace(
				'@<head(.*?)(?(?<!\?)>)@s',
				'<head\\1>'.$meta,
				$html
			);
		}
	}

	/**
	 * Extend phpQuery with $class from $file.
	 *
	 * @param string $class Extending class name. Real class name can be prepended phpQuery_.
	 * @param string $file Filename to include. Defaults to "{$class}.php".
	 */
	public static function extend($class, $file = null) {
		// TODO $class checked agains phpQuery_$class
//		if (strpos($class, 'phpQuery') === 0)
//			$class = substr($class, 8);
		if (in_array($class, self::$pluginsLoaded))
			return;
		if (! $file)
			$file = $class.'.php';
		require_once($file);
		self::$pluginsLoaded[] = $class;
		// static methods
		if (class_exists('phpQueryPlugin_'.$class)) {
			$realClass = 'phpQueryPlugin_'.$class;
			$vars = get_class_vars($realClass);
			$loop = isset($vars['phpQueryMethods'])
				&& ! is_null($vars['phpQueryMethods'])
				? $vars['phpQueryMethods']
				: get_class_methods($realClass);
			foreach($loop as $method) {
				if (! is_callable(array($realClass, $method)))
					continue;
				if (isset(self::$pluginsStaticMethods[$method])) {
					throw new Exception("Duplicate method '{$method}' from plugin '{$c}' conflicts with same method from plugin '".self::$pluginsStaticMethods[$method]."'");
					return;
				}
				self::$pluginsStaticMethods[$method] = $class;
			}
		}
		// object methods
		if (class_exists('phpQueryObjectPlugin_'.$class)) {
			$realClass = 'phpQueryObjectPlugin_'.$class;
			$vars = get_class_vars($realClass);
			$loop = isset($vars['phpQueryMethods'])
				&& ! is_null($vars['phpQueryMethods'])
				? $vars['phpQueryMethods']
				: get_class_methods($realClass);
			foreach($loop as $method) {
				if (! is_callable(array($realClass, $method)))
					continue;
				if (isset(self::$pluginsMethods[$method])) {
					throw new Exception("Duplicate method '{$method}' from plugin '{$c}' conflicts with same method from plugin '".self::$pluginsMethods[$method]."'");
					return;
				}
				self::$pluginsMethods[$method] = $class;
			}
		}
		return true;
	}
	/**
	 * Unloades all or specified document from memory.
	 *
	 * @param mixed $documentID @see phpQuery::getDocumentID() for supported types.
	 */
	public static function unloadDocuments($documentID = null) {
		if ($documentID) {
			if ($documentID = self::getDocumentID($documentID))
				unset(phpQuery::$documents[$documentID]);
		} else
			unset(phpQuery::$documents);
	}
	/**
	 * Parses phpQuery object or HTML result against PHP tags and makes them active.
	 *
	 * @param phpQuery|string $content
	 * @return string
	 */
	public static function unsafePHPTags($content) {
		if ($content instanceof phpQueryObject)
			$content = $content->htmlOuter();
		/* <php>...</php> to <?php...?> */
		$content = preg_replace_callback(
			'@<php>\s*<!--(.*?)-->\s*</php>@s',
			create_function('$m',
				'return "<?php ".htmlspecialchars_decode($m[1])." ?>";'
			),
			$content
		);
		/*$content = str_replace(
			array('<?php<!--', '<?php <!--', '-->?>', '--> ?>'),
			array('<?php', '<?php', '?>', '?>'),
			$content
		);*/
		$regexes = array(
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^\']*)\'@s',
			'@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^"]*)"@s',
		);
		foreach($regexes as $regex)
			while (preg_match($regex, $content))
			$content = preg_replace_callback(
				$regex,
				create_function('$m',
					'return $m[1].$m[2].$m[3]."<?php"
						.str_replace(
							array("%20", "%3E", "%09", "&#10;", "&#9;", "%7B", "%24", "%7D", "%22"),
							array(" ", ">", "	", "\n", "	", "{", "$", "}", \'"\'),
							htmlspecialchars_decode($m[4])
						)
						."?>".$m[5].$m[2];'
				),
				$content
			);
		return $content;
	}
	/**
	 * Checks if $input is HTML string, which has to start with '<'.
	 *
	 * @param String $input
	 * @return Bool
	 */
	public static function isMarkup($input) {
		return substr(trim($input), 0, 1) == '<';
	}
	/**
	 * Enter description here...
	 *
	 * @param string|DOMNode $html
	 */
	public static function containsEncoding($html) {
		if ( $html instanceof DOMNODE || is_array($html) ) {
			$loop = $html instanceof DOMNODELIST || is_array($html)
				? $html
				: array($html);
			foreach( $loop as $node ) {
				if (! $node instanceof DOMELEMENT )
					continue;
				$isEncoding = isset($node->tagName) && $node->tagName == 'meta'
					&& strtolower($node->getAttribute('http-equiv')) == 'content-type';
				if ($isEncoding)
					return true;
				foreach( $node->getElementsByTagName('meta') as $node )
					if ( strtolower($node->getAttribute('http-equiv')) == 'content-type' )
						return true;
			}
		} else
			return preg_match('@<meta\\s+http-equiv\\s*=\\s*(["|\'])Content-Type\\1@i', $html);
	}
	public static function isXhtml($dom) {
		$doctype = isset($dom->doctype) && is_object($dom->doctype)
			? $dom->doctype->publicId
			: self::$defaultDoctype;
		return stripos($doctype, 'xhtml') !== false;
	}
	public function debug($text) {
		if (self::$debug)
			print var_dump($text);
	}
	/**
	 * Make an AJAX request.
	 *
	 * @param array See $options http://docs.jquery.com/Ajax/jQuery.ajax#toptions
	 * Additional options are:
	 * 'document' - document for global events, @see phpQuery::getDocumentID()
	 * 'http_referer' - TODO; not implemented
	 * 'requested_with' - TODO; not implemented (X-Requested-With)
	 * @return Zend_Http_Client
	 * @link http://docs.jquery.com/Ajax/jQuery.ajax
	 *
	 * @TODO $options['cache']
	 * @TODO $options['processData']
	 * @TODO support callbackStructure like each() and map()
	 */
	public static function ajax($options = array(), $xhr = null) {
		$options = array_merge(
			self::$ajaxSettings, $options
		);
		$documentID = isset($options['document'])
			? self::getDocumentID($options['document'])
			: null;
		if ($xhr) {
			// reuse existing XHR object, but clean it up
			$client = $xhr;
			$client->setParameterPost(null);
			$client->setParameterGet(null);
			$client->setAuth(false);
			$client->setHeaders("If-Modified-Since", null);
		} else {
			// create new XHR object
			require_once('Zend/Http/Client.php');
			$client = new Zend_Http_Client();
			$client->setCookieJar();
		}
		if (isset($options['timeout']))
			$client->setConfig(array(
				'timeout'      => $options['timeout'],
			));
//			'maxredirects' => 0,
		foreach(self::$ajaxAllowedHosts as $k => $host)
			if ($host == '.')
				self::$ajaxAllowedHosts[$k] = $_SERVER['HTTP_HOST'];
		$host = parse_url($options['url'], PHP_URL_HOST);
		if (! in_array($host, self::$ajaxAllowedHosts)) {
			throw new Exception("Request not permitted, host '$host' not present in phpQuery::\$ajaxAllowedHosts");
			return false;
		}
		$client->setUri($options['url']);
		$client->setMethod($options['type']);
		$client->setHeaders(array(
//			'content-type' => $options['contentType'],
			'user-agent' => 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9a8) Gecko/2007100619 GranParadiso/3.0a8',
			'accept-charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
		));
		if ($options['username'])
			$client->setAuth($options['username'], $options['password']);
		if (isset($options['ifModified']) && $options['ifModified'])
			$client->setHeaders("If-Modified-Since",
				self::$lastModified
					? self::$lastModified
					: "Thu, 01 Jan 1970 00:00:00 GMT"
			);
		$client->setHeaders("Accept",
			isset($options['dataType'])
			&& isset(self::$ajaxSettings['accepts'][ $options['dataType'] ])
				? self::$ajaxSettings['accepts'][ $options['dataType'] ].", */*"
				: self::$ajaxSettings['accepts']['_default']
		);
		// TODO $options['processData']
		if ($options['data'] instanceof phpQueryObject) {
			$serialized = $options['data']->serializeArray($options['data']);
			$options['data'] = array();
			foreach($serialized as $r)
				$options['data'][ $r['name'] ] = $r['value'];
		}
		if (strtolower($options['type']) == 'get') {
			$client->setParameterGet($options['data']);
		} else if (strtolower($options['type']) == 'post') {
			$client->setEncType($options['contentType']);
			$client->setParameterPost($options['data']);
		}
		if (self::$active == 0 && $options['global'])
			phpQueryEvent::trigger($documentID, 'ajaxStart');
		self::$active++;
		// beforeSend callback
		if (isset($options['beforeSend']) && $options['beforeSend'])
			call_user_func_array($options['beforeSend'], array($client));
		// ajaxSend event
		if ($options['global'])
			phpQueryEvent::trigger($documentID, 'ajaxSend', array($client, $options));
		self::debug("{$options['type']}: {$options['url']}\n");
		self::debug("Options: <pre>".var_export($options, true)."</pre>\n");
		self::debug("Cookies: <pre>".var_export($client->getCookieJar()->getMatchingCookies($options['url']), true)."</pre>\n");
		// request
		$response = $client->request();
		if ($response->isSuccessful()) {
			// XXX tempolary
			self::$lastModified = $response->getHeader('Last-Modified');
			if (isset($options['success']) && $options['success'])
				call_user_func_array($options['success'], array($response->getBody(), $response->getStatus()));
			if ($options['global'])
				phpQueryEvent::trigger($documentID, 'ajaxSuccess', array($client, $options));
		} else {
			if (isset($options['error']) && $options['error'])
				call_user_func_array($options['error'], array($client, $response->getStatus(), $response->getMessage()));
			if ($options['global'])
				phpQueryEvent::trigger($documentID, 'ajaxError', array($client, /*$response->getStatus(),*/$response->getMessage(), $options));
		}
		if (isset($options['complete']) && $options['complete'])
			call_user_func_array($options['complete'], array($client, $response->getStatus()));
		if ($options['global'])
			phpQueryEvent::trigger($documentID, 'ajaxComplete', array($client, $options));
		if ($options['global'] && ! --self::$active)
			phpQueryEvent::trigger($documentID, 'ajaxStop');
		return $client;
//		if (is_null($domId))
//			$domId = self::$lastDomId ? self::$lastDomId : false;
//		return new phpQueryAjaxResponse($response, $domId);
	}
	/**
	 * Enter description here...
	 *
	 * @param array|phpQuery $data
	 *
	 */
	public static function param($data) {
		return http_build_query($data, null, '&');
	}

	public static function get($url, $data, $callback, $type) {
		if (!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		return self::ajax(array(
			'type' => 'GET',
			'url' => $url,
			'data' => $data,
			'success' => $callback,
			'dataType' => $type,
		));
	}

	public static function post($url, $data, $callback, $type) {
		if (!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		return self::ajax(array(
			'type' => 'POST',
			'url' => $url,
			'data' => $data,
			'success' => $callback,
			'dataType' => $type,
		));
	}
	public static function ajaxSetup($options) {
		self::$ajaxSettings = array_merge(
			self::$ajaxSettings,
			$options
		);
	}
	public static function ajaxAllowHost($host) {
		if ($host && !in_array($host, phpQuery::$ajaxAllowedHosts))
			phpQuery::$ajaxAllowedHosts[] = $host;
	}
	/**
	 * Returns JSON representation of $data.
	 *
	 * @static
	 * @param mixed $data
	 * @return string
	 */
	public static function toJSON($data) {
		if (function_exists('json_encode'))
			return json_encode($data);
		require_once('Zend/Json/Encoder');
		return Zend_Json_Encoder::encode($data);
	}
	/**
	 * Parses JSON into proper PHP type.
	 *
	 * @static
	 * @param string $json
	 * @return mixed
	 */
	public static function parseJSON($json) {
		if (function_exists('json_decode'))
			return json_decode($json, true);
		require_once('Zend/Json/Decoder');
		return Zend_Json_Decoder::decode($json);
	}
	/**
	 * Returns source's document ID.
	 *
	 * @param $source DOMNode|phpQueryObject
	 * @return string
	 */
	public static function getDocumentID($source) {
		if ($source instanceof DOMDOCUMENT) {
			foreach(phpQuery::$documents as $id => $document) {
				if ($source->isSameNode($document['document']))
					return $id;
			}
		} else if ($source instanceof DOMNODE) {
			foreach(phpQuery::$documents as $id => $document) {
				if ($source->ownerDocument->isSameNode($document['document']))
					return $id;
			}
		} else if ($source instanceof phpQueryObject)
			return $source->getDocumentID();
		else if (is_string($source) && isset(phpQuery::$documents[$source]))
			return $source;
	}
	/**
	 * Get DOMDocument object related to $source.
	 * Returns null if such document doesn't exist.
	 *
	 * @param $source DOMNode|phpQueryObject|string
	 * @return string
	 */
	public static function getDOMDocument($source) {
		if ($source instanceof DOMDOCUMENT)
			return $source;
		$source = self::getDocumentID($source);
		return $source
			? self::$documents[$id]['document']
			: null;
	}

	// UTILITIES
	// http://docs.jquery.com/Utilities

	/**
	 *
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.makeArray
	 */
	public static function makeArray($obj) {
		$array = array();
		if (is_object($object) && $object instanceof DOMNODELIST) {
			foreach($object as $value)
				$array[] = $value;
		} else if (is_object($object) && ! ($object instanceof Iterator)) {
			foreach(get_object_vars($object) as $name => $value)
				$array[0][$name] = $value;
		} else {
			foreach($object as $name => $value)
				$array[0][$name] = $value;
		}
		return $array;
	}
	public static function inArray($value, $array) {
		return in_array($value, $array);
	}
	/**
	 *
	 * @param $object
	 * @param $callback
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.each
	 */
	public static function each($object, $callback, $param1 = null, $param2 = null, $param3 = null) {
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		if (is_object($object) && ! ($object instanceof Iterator)) {
			foreach(get_object_vars($object) as $name => $value)
				phpQuery::callbackRun($callback, array($name, $value), $paramStructure);
		} else {
			foreach($object as $name => $value)
				phpQuery::callbackRun($callback, array($name, $value), $paramStructure);
		}
	}
	/**
	 *
	 * @link http://docs.jquery.com/Utilities/jQuery.map
	 */
	public static function map($array, $callback, $param1 = null, $param2 = null, $param3 = null) {
		$result = array();
		$paramStructure = null;
		if (func_num_args() > 2) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 2);
		}
		foreach($array as $v) {
			$vv = phpQuery::callbackRun($callback, array($v), $paramStructure);
//			$callbackArgs = $args;
//			foreach($args as $i => $arg) {
//				$callbackArgs[$i] = $arg instanceof CallbackParam
//					? $v
//					: $arg;
//			}
//			$vv = call_user_func_array($callback, $callbackArgs);
			if (is_array($vv))  {
				foreach($vv as $vvv)
					$result[] = $vvv;
			} else if ($vv !== null) {
				$result[] = $vv;
			}
		}
		return $result;
	}
	public static function callbackRun($callback, $params, $paramStructure = null) {
		if (! $paramStructure)
			return call_user_func_array($callback, $params);
		$p = 0;
		foreach($paramStructure as $i => $v) {
			$paramStructure[$i] = $v instanceof CallbackParam
				? $params[$p++]
				: $v;
		}
		return call_user_func_array($callback, $paramStructure);
	}
	/**
	 * Merge 2 phpQuery objects.
	 * @param array $one
	 * @param array $two
	 * @protected
	 * @todo node lists, phpQueryObject
	 */
	public static function merge($one, $two) {
		$elements = $one->elements;
		foreach($two->elements as $node) {
			$exists = false;
			foreach($elements as $node2) {
				if ($node2->isSameNode($node))
					$exists = true;
			}
			if (! $exists)
				$elements[] = $node;
		}
		return $elements;
//		$one = $one->newInstance();
//		$one->elements = $elements;
//		return $one;
	}
	/**
	 *
	 * @param $array
	 * @param $callback
	 * @param $invert
	 * @return unknown_type
	 * @link http://docs.jquery.com/Utilities/jQuery.grep
	 */
	public static function grep($array, $callback, $invert = false) {
		$result = array();
		foreach($array as $k => $v) {
			$r = call_user_func_array($callback, array($v, $k));
			if ($r === !(bool)$invert)
				$result[] = $v;
		}
		return $result;
	}
	public static function unique($array) {
		return array_unique($array);
	}
	/**
	 *
	 * @param $function
	 * @return unknown_type
	 * @TODO there are problems with non-static methods, second parameter pass it
	 * 	but doesnt verify is method is really callable
	 */
	public static function isFunction($function) {
		return is_callable($function);
	}
	public static function trim($str) {
		return trim($str);
	}
	/* PLUGINS NAMESPACE */
	public static function browserGet($url, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::extend('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browserGet'), $params);
		}
	}
	public static function browserPost($url, $data, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::extend('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browserPost'), $params);
		}
	}
	public static function browser($ajaxSettings, $callback, $param1 = null, $param2 = null, $param3 = null) {
		if (self::extend('WebBrowser')) {
			$params = func_get_args();
			return self::callbackRun(array(self::$plugins, 'browser'), $params);
		}
	}
}
/**
 * Plugins static namespace class.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
class phpQueryPlugins {
	public function __call($method, $args) {
		if (isset(phpQuery::$pluginsStaticMethods[$method])) {
			$class = phpQuery::$pluginsStaticMethods[$method];
			$realClass = "phpQueryPlugin_$class";
			$return = call_user_func_array(
				array($realClass, $method),
				$args
			);
			return $this;
		} else
			throw new Exception("Method '{$method}' doesnt exist");
	}
}
/**
 * Main class of phpQuery library.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
class phpQueryObject
	implements Iterator, Countable, ArrayAccess {
	public $domId = null;
	/**
	 * Alias for $document
	 *
	 * @var DOMDocument
	 * @see phpQueryObject::$document
	 * @depracated
	 */
	public $DOM = null;
	/**
	 * DOMDocument class.
	 *
	 * @var DOMDocument
	 */
	public $document = null;
	/**
	 * Document ID.
	 *
	 * @var string
	 */
	protected $docId = null;
	/**
	 * XPath interface.
	 *
	 * @var DOMXPath
	 */
	public $XPath = null;
	/**
	 * Stack of selected elements.
	 *
	 * @var array
	 */
	public $elements = array();
	/**
	 * Number of selected elements.
	 *
	 * @var int
	 */
//	public $length = 0;
	protected $elementsBackup = array();
	protected $previous = null;
	protected $root = array();
	/**
	 * Indicated if doument is just a fragment (no <html> tag).
	 *
	 * Every document is realy a full document, so even documentFragments can
	 * be queried against <html>, but getDocument(id)->htmlOuter() will return
	 * only contents of <body>.
	 *
	 * @var bool
	 */
	public $documentFragment = true;
	/**
	 * Iterator interface helper
	 */
	protected $elementsInterator = array();
	/**
	 * Iterator interface helper
	 */
	protected $valid = false;
	/**
	 * Iterator interface helper
	 */
	protected $current = null;
	/**
	 * Chars indicating regex comparsion.
	 */
	protected $regexpChars = array('^','*','$');
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function __construct($domId) {
		if ( $domId instanceof self )
			$domId = $domId->domId;
		if (! isset(phpQuery::$documents[$domId] ) ) {
			throw new Exception("DOM with ID '{$domId}' isn't loaded. Use phpQuery::newDocument(\$html) or phpQuery::newDocumentFile(\$file) first.");
			return;
		}
		$this->domId = $domId;
		phpQuery::$lastDomId = $domId;
		$this->document = phpQuery::$documents[$domId]['document'];
		$this->DOM = $this->document;
		$this->XPath = phpQuery::$documents[$domId]['xpath'];
		$this->documentFragment = phpQuery::$documents[$domId]['documentFragment'];
		$this->root = $this->DOM->documentElement;
//		$this->toRoot();
		$this->elements = array($this->root);
	}
	public function __get($attr) {
		switch($attr) {
			// FIXME doesnt work at all ?
			case 'length':
				return $this->size();
			break;
			default:
				return $this->$attr;
		}
	}
	/**
	 * Saves actual object to $var by reference.
	 * Useful when need to break chain.
	 * @param phpQueryObject $var
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function toReference(&$var) {
		return $var = $this;
	}
	public function documentFragment($state = null) {
		if ($state) {
			phpQuery::$documents[$this->docId]['documentFragment'] = $state;
			return $this;
		}
		return $this->documentFragment;
	}

	protected function isRoot( $node ) {
//		return $node instanceof DOMDOCUMENT || $node->tagName == 'html';
		return $node instanceof DOMDOCUMENT
			|| ($node instanceof DOMNODE && $node->tagName == 'html')
			|| $this->root->isSameNode($node);
	}

	/**
	 *
	 * @param $html
	 * @return unknown_type
	 * @protected
	 */
	public function importMarkup($html) {
		$this->debug('Importing markup...');
		$this->elementsBackup = $this->elements;
		$this->elements = array();
		$DOM = new DOMDocument('1.0', 'utf-8');
		// TODO encoding issues, run self::loadHtml() but some pointer to first
		// loaded element is neccessary
//		if ($html instanceof DOMNODE) {
//			$this->elements[] = $this->DOM->importNode($html, true);
//		} else {
			@$DOM->loadHTML($html);
			// equivalent of selector 'html > body > *'
			foreach($DOM->documentElement->firstChild->childNodes as $node)
				$this->elements[] = $this->DOM->importNode($node, true);
//		}
//		self::checkDocumentFragment(
//			phpQuery::$documents[$this->domId],
//			$html
//		);
//		foreach($DOM->documentElement->firstChild->childNodes as $node)
	}
	/**
	 * Merges two HTML DOMs.
	 *
	 * @param DOMDocument $domSource
	 * @param DOMDocument $domTarget
	 */
//	protected static function domMerge($domSource, $domTarget) {
//		$sourceNode = $domTarget->importNode($domSource->documentElement, true);
//		// HEAD
//		$sourceHeads = $sourceNode->getElementsByTagName('head');
//		if ( $sourceHeads->length ) {
//			$tagetHeads = $domTarget->documentElement->getElementsByTagName('head');
//			if (! $tagetHeads->length ) {
//				if (! $domTarget->documentElement->firstChild)
//					$domTarget->documentElement->appendChild($sourceHeads->item(0));
//				else
//					$domTarget->documentElement->insertBefore(
//						$sourceHeads->item(0),
//						$domTarget->documentElement->firstChild
//					);
//			} else
//				foreach($sourceHeads->item(0)->getElementsByTagName('*') as $node)
//					$tagetHeads->item(0)->appendChild($node);
//		}
//		// BODY
//		$sourceBodies = $sourceNode->getElementsByTagName('body');
//		var_dump($sourceBodies->item(0));
//		if ( $sourceBodies->length ) {
//			$tagetBodies = $domTarget->documentElement->getElementsByTagName('body');
//			if (! $tagetBodies->length )
//				$domTarget->documentElement->appendChild(
//					$sourceBodies->item(0)
//				);
//			else
//				foreach($sourceBodies->item(0)->getElementsByTagName('*') as $node)
//					$tagetBodies->item(0)->appendChild($node);
//		}
//	}
	/**
	 * Get objetc's Document ID for later use.
	 * Value is returned via reference.
	 * <code>
	 * $myDocumentId;
	 * phpQuery::newDocument('<div/>')
	 *     ->getDocumentIDRef($myDocumentId)
	 *     ->find('div')->...
	 * </code>
	 *
	 * @param unknown_type $domId
	 * @see phpQuery::newDocument
	 * @see phpQuery::newDocumentFile
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function getDocumentIDRef(&$documentID) {
		$domId = $this->domId;
		return $this;
	}
	/**
	 * Get objetc's Document ID for later use.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function getDocumentID() {
		return $this->domId;
	}
	/**
	 * Enter description here...
	 * Unload whole document from memory.
	 * CAUTION! None further operations will be possible on this document.
	 * All objects refering to it will be useless.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function unloadDocument() {
		phpQuery::unloadDocuments($this->getDocumentID());
	}
	/**
	 * Enter description here...
	 *
	 * @link http://docs.jquery.com/Ajax/serialize
	 * @return string
	 */
	public function serialize() {
		return phpQuery::param($this->serializeArray());
	}

	/**
	 * Enter description here...
	 *
	 * @link http://docs.jquery.com/Ajax/serializeArray
	 * @return array
	 */
	public function serializeArray($submit = null) {
		$source = $this->filter('form, input, select, textarea')
			->find('input, select, textarea')
			->andSelf()
			->not('form');
		$return = array();
//		$source->dumpDie();
		foreach($source as $input) {
			$input = phpQuery::pq($input);
			if ($input->is('[disabled]'))
				continue;
			if (!$input->is('[name]'))
				continue;
			if ($input->is('[type=checkbox]') && !$input->is('[checked]'))
				continue;
			// jquery diff
			if ($submit && $input->is('[type=submit]')) {
				if ($submit instanceof DOMELEMENT && ! $input->elements[0]->isSameNode($submit))
					continue;
				else if (is_string($submit) && $input->attr('name') != $submit)
					continue;
			}
			$return[] = array(
				'name' => $input->attr('name'),
				'value' => $input->val(),
			);
		}
		return $return;
	}

	protected function debug($in) {
		if (! phpQuery::$debug )
			return;
		print('<pre>');
		print_r($in);
		// file debug
//		file_put_contents(dirname(__FILE__).'/phpQuery.log', print_r($in, true)."\n", FILE_APPEND);
		// quite handy debug trace
//		if ( is_array($in))
//			print_r(array_slice(debug_backtrace(), 3));
		print("</pre>\n");
	}
	/**
	 * Enter description here...
	 * NON JQUERY METHOD
	 *
	 * TODO SUPPORT FOR end() !!! Causing problems in queryTemplates...
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function toRoot() {
		$this->elements = array($this->root);
		return $this;
//		return $this->newInstance(array($this->root));
	}
	protected function isRegexp($pattern) {
		return in_array(
			$pattern[ strlen($pattern)-1 ],
			$this->regexpChars
		);
	}
	/**
	 * Determines if $char is really a char.
	 *
	 * @param string $char
	 * @return bool
	 * @todo rewrite me to charcode range ! ;)
	 */
	protected function isChar($char) {
		return preg_match('/\w/', $char);
	}
	protected function parseSelector( $query ) {
		// clean spaces
		// TODO include this inside parsing
		$query = trim(
			preg_replace('@\s+@', ' ',
				preg_replace('@\s*(>|\\+|~)\s*@', '\\1', $query)
			)
		);
		$queries = array(array());
		$return =& $queries[0];
		$specialChars = array('>',' ');
//		$specialCharsMapping = array('/' => '>');
		$specialCharsMapping = array();
		$strlen = strlen($query);
		$classChars = array('.', '-');
		$pseudoChars = array('-');
		// it works, but i dont like it...
		$i = 0;
		while( $i < $strlen) {
			$c = $query[$i];
			$tmp = '';
			// TAG
			if ( $this->isChar($c) || $c == '*' ) {
				while( isset($query[$i]) && ($this->isChar($query[$i]) || $query[$i] == '*')) {
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = $tmp;
			// IDs
			} else if ( $c == '#' ) {
				$i++;
				while( isset($query[$i]) && ($this->isChar($query[$i]) || $query[$i] == '-')) {
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = '#'.$tmp;
			// SPECIAL CHARS
			} else if (in_array($c, $specialChars)) {
				$return[] = $c;
				$i++;
			// MAPPED SPECIAL MULTICHARS
//			} else if ( $c.$query[$i+1] == '//' ) {
//				$return[] = ' ';
//				$i = $i+2;
			// MAPPED SPECIAL CHARS
			} else if ( isset($specialCharsMapping[$c]) ) {
				$return[] = $specialCharsMapping[$c];
				$i++;
			// COMMA
			} else if ( $c == ',' ) {
				$queries[] = array();
				$return =& $queries[ count($queries)-1 ];
				$i++;
				while( isset($query[$i]) && $query[$i] == ' ')
					$i++;
			// CLASSES
			} else if ($c == '.') {
				while( isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $classChars))) {
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = $tmp;
			// ~ General Sibling Selector
			} else if ($c == '~') {
				$spaceAllowed = true;
				$tmp .= $query[$i++];
				while( isset($query[$i])
					&& ($this->isChar($query[$i])
						|| in_array($query[$i], $classChars)
						|| $query[$i] == '*'
						|| ($query[$i] == ' ' && $spaceAllowed)
					)) {
					if ($query[$i] != ' ')
						$spaceAllowed = false;
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = $tmp;
			// + Adjacent sibling selectors
			} else if ($c == '+') {
				$spaceAllowed = true;
				$tmp .= $query[$i++];
				while( isset($query[$i])
					&& ($this->isChar($query[$i])
						|| in_array($query[$i], $classChars)
						|| $query[$i] == '*'
						|| ($spaceAllowed && $query[$i] == ' ')
					)) {
					if ($query[$i] != ' ')
						$spaceAllowed = false;
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = $tmp;
			// ATTRS
			} else if ($c == '[') {
				$stack = 1;
				$tmp .= $c;
				while( isset($query[++$i]) ) {
					$tmp .= $query[$i];
					if ( $query[$i] == '[' ) {
						$stack++;
					} else if ( $query[$i] == ']' ) {
						$stack--;
						if (! $stack )
							break;
					}
				}
				$return[] = $tmp;
				$i++;
			// PSEUDO CLASSES
			} else if ($c == ':') {
				$stack = 1;
				$tmp .= $query[$i++];
				while( isset($query[$i]) && ($this->isChar($query[$i]) || in_array($query[$i], $pseudoChars))) {
					$tmp .= $query[$i];
					$i++;
				}
				// with arguments ?
				if ( isset($query[$i]) && $query[$i] == '(' ) {
					$tmp .= $query[$i];
					$stack = 1;
					while( isset($query[++$i]) ) {
						$tmp .= $query[$i];
						if ( $query[$i] == '(' ) {
							$stack++;
						} else if ( $query[$i] == ')' ) {
							$stack--;
							if (! $stack )
								break;
						}
					}
					$return[] = $tmp;
					$i++;
				} else {
					$return[] = $tmp;
				}
			} else {
				$i++;
			}
		}
		foreach($queries as $k =>$q ) {
			if ( isset($q[0]) && $q[0] != '>' )
				array_unshift($queries[$k], ' ');
		}
		return $queries;
	}

	/**
	 * Return matched DOM nodes.
	 *
	 * @todo return DOMNodeList insted of array
	 * @param int $index
	 * @return array|DOMElement Single DOMElement or array of DOMElement.
	 */
	public function get($index = null) {
		return ! is_null($index)
			? $this->elements[$index]
			: $this->elements;
	}
	/**
	 * Return matched DOM nodes.
	 * jQuery difference.
	 *
	 * @param int $index
	 * @return array|string Returns string if $index != null
	 */
	public function getText($index = null) {
		if ($index)
			return trim($this->eq($index)->text());
		$return = array();
		for($i = 0; $i < $this->size(); $i++) {
			$return[] = trim($this->eq($i)->text());
		}
		return $return;
	}
	/**
	 * Returns new instance of actual class.
	 *
	 * @param array $newStack Optional. Will replace old stack with new and move old one to history.c
	 */
	protected function newInstance($newStack = null) {
		$class = get_class($this);
		// support inheritance by passing old object to overloaded constructor
		$new = $class != 'phpQuery'
			? new $class($this, $this->domId)
			: new phpQueryObject($this->domId);
		$new->previous = $this;
		if (is_null($newStack)) {
			$new->elements = $this->elements;
			if ($this->elementsBackup)
				$this->elements = $this->elementsBackup;
		} else {
			$new->elements = $newStack;
		}
		return $new;
	}

	/**
	 * Enter description here...
	 *
	 * In the future, when PHP will support XLS 2.0, then we would do that this way:
	 * contains(tokenize(@class, '\s'), "something")
	 * @param unknown_type $class
	 * @param unknown_type $node
	 * @return boolean
	 */
	protected function matchClasses( $class, $node ) {
		// multi-class
		if ( strpos($class, '.', 1) ) {
			$classes = explode('.', substr($class, 1));
			$classesCount = count( $classes );
			$nodeClasses = explode(' ', $node->getAttribute('class') );
			$nodeClassesCount = count( $nodeClasses );
			if ( $classesCount > $nodeClassesCount )
				return false;
			$diff = count(
				array_diff(
					$classes,
					$nodeClasses
				)
			);
			if (! $diff )
				return true;
		// single-class
		} else {
			return in_array(
				// strip leading dot from class name
				substr($class, 1),
				// get classes for element as array
				explode(' ', $node->getAttribute('class') )
			);
		}
	}
	protected function runQuery( $XQuery, $selector = null, $compare = null ) {
		if ( $compare && ! method_exists($this, $compare) )
			return false;
		$stack = array();
		if (! $this->elements )
			$this->debug('Stack empty, skipping...');
		foreach( $this->elements as $k => $stackNode ) {
			$detachAfter = false;
			// to work on detached nodes we need temporary place them somewhere
			// thats because context xpath queries sucks ;]
			$testNode = $stackNode;
			while ($testNode) {
				if (! $testNode->parentNode && ! $this->isRoot($testNode) ) {
					$this->root->appendChild($testNode);
					$detachAfter = $testNode;
					break;
				}
				$testNode = isset($testNode->parentNode)
					? $testNode->parentNode
					: null;
			}
			$xpath = $this->getNodeXpath($stackNode);
			// FIXME deam...
			$query = $XQuery == '//' && $xpath == '/html[1]'
				? '//*'
				: $xpath.$XQuery;
			$this->debug("XPATH: {$query}");
			// run query, get elements
			$nodes = $this->XPath->query($query);
			$this->debug("QUERY FETCHED");
			if (! $nodes->length )
				$this->debug('Nothing found');
			$debug = array();
			foreach( $nodes as $node ) {
				$matched = false;
				if ( $compare ) {
					phpQuery::$debug ?
						$this->debug("Found: ".$this->whois( $node ).", comparing with {$compare}()")
						: null;
					if (call_user_func_array(array($this, $compare), array($selector, $node)))
						$matched = true;
				} else {
					$matched = true;
				}
				if ( $matched ) {
					if (phpQuery::$debug)
						$debug[] = $this->whois( $node );
					$stack[] = $node;
				}
			}
			if (phpQuery::$debug) {
				$this->debug("Matched ".count($debug).": ".implode(', ', $debug));
			}
			if ($detachAfter)
				$this->root->removeChild($detachAfter);
		}
		$this->elements = $stack;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function find( $selectors, $context = null, $noHistory = false ) {
		if (!$noHistory)
			// backup last stack /for end()/
			$this->elementsBackup = $this->elements;
		// allow to define context
		if ( $context ) {
			if (! is_array($context) && $context instanceof DOMELEMENT )
				$this->elements = array($context);
			else if ( is_array($context) ) {
				$this->elements = array();
				foreach ($context as $e)
					if ( $c instanceof DOMELEMENT )
						$this->elements[] = $c;

			} else if ( $context instanceof self )
				$this->elements = $context->elements;
		}
		$spaceBefore = false;
		$queries = $this->parseSelector( $selectors );
		$this->debug(array('FIND',$selectors,$queries));
		$XQuery = '';
		// remember stack state because of multi-queries
		$oldStack = $this->elements;
		// here we will be keeping found elements
		$stack = array();
		foreach( $queries as $selector ) {
			$this->elements = $oldStack;
			foreach( $selector as $s ) {
				// TAG
				if ( preg_match('@^\w+$@', $s) || $s == '*' ) {
					$XQuery .= $s;
				// ID
				} else if ( $s[0] == '#' ) {
					if ( $spaceBefore )
						$XQuery .= '*';
					$XQuery .= "[@id='".substr($s, 1)."']";
				// ATTRIBUTES
				} else if ( $s[0] == '[' ) {
					if ( $spaceBefore )
						$XQuery .= '*';
					// strip side brackets
					$attr = trim($s, '][');
					$execute = false;
					// attr with specifed value
					if ( strpos( $s, '=' ) ) {
						list( $attr, $value ) = explode('=', $attr);
						$value = trim($value, "'\"'");
						if ( $this->isRegexp($attr) ) {
							// cut regexp character
							$attr = substr($attr, 0, -1);
							$execute = true;
							$XQuery .= "[@{$attr}]";
						} else {
							$XQuery .= "[@{$attr}='{$value}']";
						}
					// attr without specified value
					} else {
						$XQuery .= "[@{$attr}]";
					}
					if ( $execute ) {
						$this->runQuery($XQuery, $s, 'is');
						$XQuery = '';
						if (! $this->length() )
							break;
					}
				// CLASSES
				} else if ( $s[0] == '.' ) {
					// TODO use return $this->find("./self::*[contains(concat(\" \",@class,\" \"), \" $class \")]");
					// thx wizDom ;)
					if ( $spaceBefore )
						$XQuery .= '*';
					$XQuery .= '[@class]';
					$this->runQuery($XQuery, $s, 'matchClasses');
					$XQuery = '';
					if (! $this->length() )
						break;
				// ~ General Sibling Selector
				} else if ( $s[0] == '~' ) {
					$this->runQuery($XQuery);
					$XQuery = '';
					$this->elements = $this
						->siblings(
							substr($s, 1)
						)->elements;
					if (! $this->length() )
						break;
				// + Adjacent sibling selectors
				} else if ( $s[0] == '+' ) {
					// TODO /following-sibling::
					$this->runQuery($XQuery);
					$XQuery = '';
					$subSelector = substr($s, 1);
					$subElements = $this->elements;
					$this->elements = array();
					foreach($subElements as $node) {
						// search first DOMElement sibling
						$test = $node->nextSibling;
						while($test && ! ($test instanceof DOMELEMENT))
							$test = $test->nextSibling;
						if ($test && $this->is($subSelector, $test))
							$this->elements[] = $test;
					}
					if (! $this->length() )
						break;
				// PSEUDO CLASSES
				} else if ( $s[0] == ':' ) {
					// TODO optimization for :first :last
					if ( $XQuery ) {
						$this->runQuery($XQuery);
						$XQuery = '';
					}
					if (! $this->length() )
						break;
					$this->pseudoClasses($s);
					if (! $this->length() )
						break;
				// DIRECT DESCENDANDS
				} else if ( $s == '>' ) {
					$XQuery .= '/';
				} else {
					$XQuery .= '//';
				}
				if ( $s == ' ' )
					$spaceBefore = true;
				else
					$spaceBefore = false;
			}
			// run query if any
			if ( $XQuery && $XQuery != '//' ) {
				$this->runQuery($XQuery);
				$XQuery = '';
//				if (! $this->length() )
//					break;
			}
			foreach( $this->elements as $node )
				if (! $this->elementsContainsNode($node, $stack) )
					$stack[] = $node;
		}
		$this->elements = $stack;
		return $this->newInstance();
	}

	/**
	 * @todo create API for classes with pseudoselectors
	 */
	protected function pseudoClasses( $class ) {
		// TODO clean args parsing ?
		$class = ltrim($class, ':');
		$haveArgs = strpos($class, '(');
		if ( $haveArgs !== false ) {
			$args = substr($class, $haveArgs+1, -1);
			$class = substr($class, 0, $haveArgs);
		}
		switch( $class ) {
			case 'even':
			case 'odd':
				$stack = array();
				foreach( $this->elements as $i => $node ) {
					if ($class == 'even' && ($i%2) == 0)
						$stack[] = $node;
					else if ( $class == 'odd' && $i % 2 )
						$stack[] = $node;
				}
				$this->elements = $stack;
				break;
			case 'eq':
				$k = intval($args);
				$this->elements = isset( $this->elements[$k] )
					? array( $this->elements[$k] )
					: array();
				break;
			case 'gt':
				$this->elements = array_slice($this->elements, $args+1);
				break;
			case 'lt':
				$this->elements = array_slice($this->elements, 0, $args+1);
				break;
			case 'first':
				if ( isset( $this->elements[0] ) )
					$this->elements = array( $this->elements[0] );
				break;
			case 'last':
				if ( $this->elements )
					$this->elements = array( $this->elements[ count($this->elements)-1 ] );
				break;
			/*case 'parent':
				$stack = array();
				foreach( $this->elements as $node ) {
					if ( $node->childNodes->length )
						$stack[] = $node;
				}
				$this->elements = $stack;
				break;*/
			case 'contains':
				$text = trim($args, "\"'");
				$stack = array();
				foreach( $this->elements as $node ) {
					if ( strpos( $node->textContent, $text ) === false )
						continue;
					$stack[] = $node;
				}
				$this->elements = $stack;
				break;
			case 'not':
				$query = trim($args, "\"'");
				$stack = $this->elements;
				$newStack = array();
				foreach( $stack as $node ) {
					$this->elements = array($node);
					if (! $this->is($query) )
						$newStack[] = $node;
				}
				$this->elements = $newStack;
				break;
			case 'slice':
				// jQuery difference ?
				$args = exlode(',',
					str_replace(', ', ',', trim($args, "\"'"))
				);
				$start = $args[0];
				$end = isset($args[1])
					? $args[1]
					: null;
				if ($end > 0)
					$end = $end-$start;
				$this->elements = array_slice($this->elements, $start, $end);
				break;
			case 'has':
				$selector = trim($args, "\"'");
				$stack = array();
				foreach( $this->elements as $el ) {
					if ( $this->find($selector, $el, true)->length() )
						$stack[] = $el;
				}
				$this->elements = $stack;
				break;
			case 'submit':
			case 'reset':
				$this->elements = phpQuery::merge(
					$this->map(array($this, 'is'),
						"input[type=$class]", new CallbackParam()
					),
					$this->map(array($this, 'is'),
						"button[type=$class]", new CallbackParam()
					)
				);
			break;
//				$stack = array();
//				foreach($this->elements as $node)
//					if ($node->is('input[type=submit]') || $node->is('button[type=submit]'))
//						$stack[] = $el;
//				$this->elements = $stack;
			case 'input':
				$this->elements = $this->map(
					array($this, 'is'),
					'input', new CallbackParam()
				)->elements;
			break;
			case 'password':
			case 'checkbox':
			case 'hidden':
			case 'image':
			case 'file':
				$this->elements = $this->map(
					array($this, 'is'),
					"input[type=$class]", new CallbackParam()
				)->elements;
			break;
			case 'parent':
				$this->elements = $this->map(
					create_function('$node', '
						return $node->childNodes->length ? $node : null;')
				)->elements;
			break;
			case 'empty':
				$this->elements = $this->map(
					create_function('$node', '
						return $node->childNodes->length ? null : $node;')
				)->elements;
			break;
			case 'disabled':
			case 'selected':
			case 'checked':
				$this->elements = $this->map(
					array($this, 'is'),
					"[$class]", new CallbackParam()
				)->elements;
			break;
			case 'enabled':
				$this->elements = $this->map(
					create_function('$node', '
						return pq($node)->not(":disabled") ? $node : null;')
				)->elements;
			break;
			case 'header':
				$this->elements = $this->map(
					create_function('$node',
						'$isHeader = $node->tagName && in_array($node->tagName, array(
							"h1", "h2", "h3", "h4", "h5", "h6", "h7"
						));
						return $isHeader
							? $node
							: null;')
				)->elements;
//				$this->elements = $this->map(
//					create_function('$node', '$node = pq($node);
//						return $node->is("h1")
//							|| $node->is("h2")
//							|| $node->is("h3")
//							|| $node->is("h4")
//							|| $node->is("h5")
//							|| $node->is("h6")
//							|| $node->is("h7")
//							? $node
//							: null;')
//				)->elements;
			break;
			case 'only-child':
				$this->elements = $this->map(
					create_function('$node',
						'return pq($node)->siblings()->size() == 0 ? $node : null;')
				)->elements;
			break;
			case 'first-child':
				$this->elements = $this->map(
					create_function('$node', 'return pq($node)->prevAll()->size() == 0 ? $node : null;')
				)->elements;
			break;
			case 'last-child':
				$this->elements = $this->map(
					create_function('$node', 'return pq($node)->nextAll()->size() == 0 ? $node : null;')
				)->elements;
			break;
			case 'nth-child':
				$param = trim($args, "\"'");
				if (! $param)
					break;
					// nth-child(n+b) to nth-child(1n+b)
				if ($param{0} == 'n')
					$param = '1'.$param;
				// :nth-child(index/even/odd/equation)
				if ($param == 'even' || $param == 'odd')
					$mapped = $this->map(
						create_function('$node, $param',
							'$index = pq($node)->prevAll()->size()+1;
							if ($param == "even" && ($index%2) == 0)
								return $node;
							else if ($param == "odd" && $index%2 == 1)
								return $node;
							else
								return null;'),
						new CallbackParam(), $param
					);
				else if (strlen($param) > 1 && $param{1} == 'n')
					// an+b
					$mapped = $this->map(
						create_function('$node, $param',
							'$prevs = pq($node)->prevAll()->size();
							$index = 1+$prevs;
							$b = strlen($param) > 3
								? $param{3}
								: 0;
							$a = $param{0};
							if ($b && $param{2} == "-")
								$b = -$b;
							if ($a > 0) {
								return ($index-$b)%$a == 0
									? $node
									: null;
								phpQuery::debug($a."*".floor($index/$a)."+$b-1 == ".($a*floor($index/$a)+$b-1)." ?= $prevs");
								return $a*floor($index/$a)+$b-1 == $prevs
										? $node
										: null;
							} else if ($a == 0)
								return $index == $b
										? $node
										: null;
							else
								// negative value
								return $index <= $b
										? $node
										: null;
//							if (! $b)
//								return $index%$a == 0
//									? $node
//									: null;
//							else
//								return ($index-$b)%$a == 0
//									? $node
//									: null;
							'),
						new CallbackParam(), $param
					);
				else
					// index
					$mapped = $this->map(
						create_function('$node, $index',
							'$prevs = pq($node)->prevAll()->size();
							if ($prevs && $prevs == $index-1)
								return $node;
							else if (! $prevs && $index == 1)
								return $node;
							else
								return null;'),
						new CallbackParam(), $param
					);
				$this->elements = $mapped->elements;
			break;
			default:
				$this->debug("Unknown pseudoclass '{$class}', skipping...");
		}
	}
	protected function __pseudoClassParam($paramsString) {
		// TODO;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function is($selector, $nodes = null) {
		phpQuery::debug(array("Is:", $selector));
		if (! $selector)
			return false;
		$oldStack = $this->elements;
		$returnArray = false;
		if ($nodes && is_array($nodes)) {
			$this->elements = $nodes;
		} else if ($nodes)
			$this->elements = array($nodes);
		$this->filter($selector, true);
		$stack = $this->elements;
		$this->elements = $oldStack;
		if ($nodes)
			return $stack ? $stack : null;
		return (bool)count($stack);
	}

	/**
	 * Enter description here...
	 * jQuery difference.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @link http://docs.jquery.com/Traversing/filter
	 */
	public function filterCallback($callback, $_skipHistory = false) {
		if (! $_skipHistory ) {
			$this->elementsBackup = $this->elements;
			$this->debug(array("Filtering:", $selectors));
		}
		$newStack = array();
		foreach($this->elements as $index => $node) {
			if (false !== call_user_func_array($callback, array($index, $node)))
				$newStack[] = $node;
		}
		$this->elements = $newStack;
		return $_skipHistory
			? $this
			: $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @link http://docs.jquery.com/Traversing/filter
	 */
	public function filter( $selectors, $_skipHistory = false ) {
		if (! $_skipHistory )
			$this->elementsBackup = $this->elements;
		$notSimpleSelector = array(' ', '>', '~', '+', '/');
		$selectors = $this->parseSelector( $selectors );
		if (! $_skipHistory )
			$this->debug(array("Filtering:", $selectors));
		$stack = array();
		foreach ( $selectors as $selector ) {
			if (! $selector )
				break;
			// avoid first space or /
			if (in_array( $selector[0], $notSimpleSelector ) )
				$selector = array_slice($selector, 1);
			// PER NODE selector chunks
			foreach( $this->elements as $node ) {
				$break = false;
				foreach( $selector as $s ) {
					// ID
					if ( $s[0] == '#' ) {
						if ( $node->getAttribute('id') != substr($s, 1) )
							$break = true;
					// CLASSES
					} else if ( $s[0] == '.' ) {
						if (! $this->matchClasses( $s, $node ) )
							$break = true;
					// ATTRS
					} else if ( $s[0] == '[' ) {
						// strip side brackets
						$attr = trim($s, '[]');
						if ( strpos($attr, '=') ) {
							list( $attr, $val ) = explode('=', $attr);
							if ( $this->isRegexp($attr)) {
								// switch last character
								switch( substr($attr, -1) ) {
									case '^':
										$pattern = '^'.preg_quote($val, '@');
										break;
									case '*':
										$pattern = '.*'.preg_quote($val, '@').'.*';
										break;
									case '$':
										$pattern = preg_quote($val, '@').'$';
										break;
								}
								// cut last character
								$attr = substr($attr, 0, -1);
								if (! preg_match("@{$pattern}@", $node->getAttribute($attr)))
									$break = true;
							} else if ( $node->getAttribute($attr) != $val )
								$break = true;
						} else if (! $node->hasAttribute($attr) )
							$break = true;
					// PSEUDO CLASSES
					} else if ( $s[0] == ':' ) {
						// skip
					// TAG
					} else if ( trim($s) ) {
						if ( $s != '*' ) {
							if ( isset($node->tagName) ) {
								if ( $node->tagName != $s )
									$break = true;
							} else if ( $s == 'html' && ! $this->isRoot($node) )
								$break = true;
						}
					// AVOID NON-SIMPLE SELECTORS
					} else if ( in_array($s, $notSimpleSelector)) {
						$break = true;
						$this->debug(array('Skipping non simple selector', $selector));
					}
					if ( $break )
						break;
				}
				// if element passed all chunks of selector - add it to new stack
				if (! $break )
					$stack[] = $node;
			}
		}
		$this->elements = $stack;
		// PER ALL NODES selector chunks
		foreach($selector as $s)
			// PSEUDO CLASSES
			if ( $s[0] == ':' )
				$this->pseudoClasses($s);
		return $_skipHistory
			? $this
			: $this->newInstance();
	}

	/**
	 * Enter description here...
	 *
	 * @link http://docs.jquery.com/Ajax/load
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo Support $selector
	 */
	public function load($url, $data = null, $callback = null) {
		if ($data && ! is_array($data)) {
			$callback = $data;
			$data = null;
		}
		if (strpos($url, ' ') !== false) {
			$matches = null;
			preg_match('@^(.+?) (.*)$@', $url, $matches);
			$url = $matches[1];
			$selector = $matches[2];
			// this sucks, but what to do ?
			$this->_loadSelector = $selector;
		}
		$ajax = array(
			'url' => $url,
			'type' => $data ? 'POST' : 'GET',
			'data' => $data,
			'complete' => $callback,
			'success' => array($this, '__loadSuccess')
		);
		phpQuery::ajax($ajax);
		return $this;
	}
	/**
	 * @protected
	 * @param $html
	 * @return unknown_type
	 */
	public function __loadSuccess($html) {
		if ($this->_loadSelector) {
			$html = phpQuery::newDocument($html)->find($this->_loadSelector);
			unset($this->_loadSelector);
		}
		foreach($this as $node) {
			phpQuery::pq($node, $this->getDocumentID())
				->html($html);
		}
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function css() {
		// TODO
		return $this;
	}
	/**
	 * @todo
	 *
	 */
	public function show(){
		// TODO
		return $this;
	}
	/**
	 * @todo
	 *
	 */
	public function hide(){
		// TODO
		return $this;
	}
	/**
	 * Trigger a type of event on every matched element.
	 *
	 * @param unknown_type $type
	 * @param unknown_type $data
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @TODO support more than event in $type (space-separated)
	 */
	public function trigger($type, $data = array()) {
		foreach($this->elements as $node)
			phpQueryEvent::trigger($this->getDocumentID(), $type, $data, $node);
		return $this;
	}
	/**
	 * This particular method triggers all bound event handlers on an element (for a specific event type) WITHOUT executing the browsers default actions.
	 *
	 * @param unknown_type $type
	 * @param unknown_type $data
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @TODO
	 */
	public function triggerHandler($type, $data = array()) {
		// TODO;
	}
	/**
	 * Binds a handler to one or more events (like click) for each matched element.
	 * Can also bind custom events.
	 *
	 * @param unknown_type $type
	 * @param unknown_type $data Optional
	 * @param unknown_type $callback
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @TODO support '!' (exclusive) events
	 * @TODO support more than event in $type (space-separated)
	 */
	public function bind($type, $data, $callback = null) {
		if (is_null($callback) && is_callable($data)) {
			$callback = $data;
			$data = null;
		}
		foreach($this->elements as $node)
			phpQueryEvent::add($this->getDocumentID(), $node, $type, $data, $callback);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $type
	 * @param unknown_type $callback
	 * @return unknown
	 * @TODO namespace events
	 * @TODO support more than event in $type (space-separated)
	 */
	public function unbind($type = null, $callback = null) {
		foreach($this->elements as $node)
			phpQueryEvent::remove($this->getDocumentID(), $node, $type, $callback);
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function change($callback = null) {
		if ($callback)
			return $this->bind('change', $callback);
		return $this->trigger('change');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function select($callback = null) {
		if ($callback)
			return $this->bind('select', $callback);
		return $this->trigger('select');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function submit() {
		if ($callback)
			return $this->bind('change', $callback);
		return $this->trigger('submit');
	}
	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapAll($wrapper) {
		$wrapper = pq($wrapper)->_clone();
		if (! $wrapper->length() || ! $this->length() )
			return $this;
		$wrapper->insertBefore($this->elements[0]);
		$deepest = $wrapper->elements[0];
		while($deepest->firstChild && $deepest->firstChild instanceof DOMELEMENT)
			$deepest = $deepest->firstChild;
		pq($deepest)->append($this);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * TODO testme...
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapAllTest($wrapper) {
		if (! $this->length() )
			return $this;
		return pq($wrapper)
			->_clone()
			->insertBefore($this->elements[0])
			->map(array(self, '_wrapAllCallback'))
			->append($this);
	}

	protected function _wrapAllCallback($node) {
		$deepest = $node;
		while($deepest->firstChild && $deepest->firstChild instanceof DOMELEMENT)
			$deepest = $deepest->firstChild;
		return $deepest;
	}

	/**
	 * Enter description here...
	 * NON JQUERY METHOD
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapAllPHP($codeBefore, $codeAfter) {
		return $this
			->slice(0, 1)
				->beforePHP($codeBefore)
			->end()
			->slice(-1)
				->afterPHP($codeAfter)
			->end();
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrap($wrapper) {
		foreach($this as $node)
			phpQuery::pq($node, $this->domId)->wrapAll($wrapper);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapPHP($codeBefore, $codeAfter) {
		foreach($this as $node)
			phpQuery::pq($node, $this->domId)->wrapAllPHP($codeBefore, $codeAfter);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapInner($wrapper) {
		foreach($this as $node)
			phpQuery::pq($node, $this->domId)->contents()->wrapAll($wrapper);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapInnerPHP($wrapper) {
	//	return $this->wrapInner("<php><!-- {$wrapper} --></php>");
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @testme Support for text nodes
	 */
	public function contents() {
		$stack = array();
		foreach( $this->elements as $el ) {
			foreach( $el->childNodes as $node ) {
				$stack[] = $node;
			}
		}
		return $this->newInstance($stack);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function eq($num) {
		$oldStack = $this->elements;
		$this->elementsBackup = $this->elements;
		$this->elements = array();
		if ( isset($oldStack[$num]) )
			$this->elements[] = $oldStack[$num];
		return $this->newInstance();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function size() {
		return count( $this->elements );
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @depracated Use length as attribute
	 */
	public function length() {
		return $this->size();
	}
	public function count() {
		return $this->length();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo $level
	 */
	public function end($level = 1) {
//		$this->elements = array_pop( $this->history );
//		return $this;
		$this->previous->DOM = $this->DOM;
		$this->previous->XPath = $this->XPath;
		return $this->previous
			? $this->previous
			: $this;
	}
	/**
	 * Enter description here...
	 * Normal use ->clone() .
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function _clone() {
		$newStack = array();
		//pr(array('copy... ', $this->whois()));
		//$this->dumpHistory('copy');
		$this->elementsBackup = $this->elements;
		foreach( $this->elements as $node ) {
			$newStack[] = $node->cloneNode(true);
		}
		$this->elements = $newStack;
		return $this->newInstance();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function replaceWithPHP($code) {
		return $this->replaceWith("<php><!-- {$code} --></php>");
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery $content
	 * @link http://docs.jquery.com/Manipulation/replaceWith#content
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function replaceWith($content) {
		return $this->after($content)->remove();
	}

	/**
	 * Enter description here...
	 *
	 * @param String $selector
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo this works ?
	 */
	public function replaceAll($selector) {
		foreach(phpQuery::pq($selector, $this->domId) as $node)
			phpQuery::pq($node, $this->domId)
				->after($this->_clone())
				->remove();
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function remove() {
		foreach( $this->elements as $node ) {
			if (! $node->parentNode )
				continue;
			$this->debug("Removing '{$node->tagName}'");
			$node->parentNode->removeChild( $node );
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $html
	 * @return string|phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function html($html = null) {
		if (! is_null($html) ) {
			$this->debug("Inserting data with 'html'");
			if (phpQuery::isMarkup($html)) {
				$toInserts = array();
				$DOM = new DOMDocument();
				// FIXME tempolary
				$html = utf8_decode($html);
				@$DOM->loadHtml($html);
				foreach($DOM->documentElement->firstChild->childNodes as $node)
					$toInserts[] = $this->DOM->importNode($node, true);
			} else {
				// FIXME tempolary, utf8 only
				// http://code.google.com/p/phpquery/issues/detail?id=17#c12
				if (mb_detect_encoding($html) == 'ASCII')
					$html	= mb_convert_encoding($html,'UTF-8','HTML-ENTITIES');
				$toInserts = array($this->DOM->createTextNode($html));
			}
			$this->_empty();
			// i dont like brackets ! python rules ! ;)
			foreach( $toInserts as $toInsert )
				foreach( $this->elements as $alreadyAdded => $node )
					$node->appendChild( $alreadyAdded
						? $toInsert->cloneNode(true)
						: $toInsert
					);
//			$this->dumpStack();
			return $this;
		} else {
			if ( $this->stackIsRoot() )
				return $this->getMarkup();
			$DOM = new DOMDocument('1.0',
				$this->DOM->encoding
					? $this->DOM->encoding
					: phpQuery::$defaultEncoding
			);
			$nodes = array();
			foreach( $this->elements as $node )
				foreach( $node->childNodes as $child ) {
					$nodes[] = $child;
					$DOM->appendChild(
						$DOM->importNode( $child, true )
					);
				}
			if (! phpQuery::containsEncoding($nodes) ) {
				$html = $this->fixXhtml(
					$DOM->saveXML()
				);
				if (! phpQuery::isXhtml($this->DOM))
					$html = str_replace('/>', '>', $html);
				return $html;
			}
			return $this->getMarkup($DOM);
		}
	}
	/**
	 * Enter description here...
	 *
	 * @return String
	 */
	public function htmlOuter() {
		if ($this->stackIsRoot())
			return $this->getMarkup();
		$DOM = new DOMDocument('1.0',
			$this->DOM->encoding
				? $this->DOM->encoding
				: phpQuery::$defaultEncoding
		);
		foreach( $this->elements as $node ) {
			$DOM->appendChild(
				$DOM->importNode( $node, true )
			);
		}
		if (! phpQuery::containsEncoding($this->elements) ) {
			$html = $this->fixXhtml(
				$DOM->saveXML()
			);
			if (! phpQuery::isXhtml($this->DOM))
				$html = str_replace('/>', '>', $html);
			return $html;
		}
		return $this->getMarkup($DOM);
	}
	protected function getMarkup($DOM = null){
		if (! $DOM)
			$DOM = $this->DOM;
		$this->debug("Getting markup with encoding: {$DOM->encoding}");
		$DOM->formatOutput = false;
		$DOM->preserveWhiteSpace = true;
		$doctype = isset($DOM->doctype) && is_object($DOM->doctype)
			? $DOM->doctype->publicId
			: phpQuery::$defaultDoctype;
		if ($DOM->isSameNode($this->DOM) && $this->documentFragment && $this->stackIsRoot())
			// double php tags removement ?
			$return = $this->find('body')->html();
		else
			$return = phpQuery::isXhtml($DOM)
				? $this->fixXhtml( $DOM->saveXML() )
				: $DOM->saveHTML();
//		debug($return);
		return $return;
	}
	protected function stackIsRoot() {
		return $this->size() == 1 && $this->isRoot($this->elements[0]);
	}
	protected function fixXhtml($content){
		return
			// TODO find out what and why it is. maybe it has some relations with extra new lines ?
			str_replace(array('&#13;','&#xD;'), '',
			// strip non-commented cdata
			str_replace(']]]]><![CDATA[>', ']]>',
			preg_replace('@(<script[^>]*>\s*)<!\[CDATA\[@', '\1',
			preg_replace('@\]\]>(\s*</script>)@', '\1',
			// textarea can't be short tagged
			preg_replace('!<textarea([^>]*)/>!', '<textarea\1></textarea>',
				// cut first line xml declaration
				implode("\n",
					array_slice(
						explode("\n", $content),
						1
		)))))));
	}
	public function __toString() {
		return $this->htmlOuter();
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function php($code) {
//		TODO
//		$args = func_get_args();
		return $this->html("<php><!-- ".trim($code)." --></php>");
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function children($selector = null) {
		$stack = array();
		foreach( $this->elements as $node ) {
			foreach( $node->getElementsByTagName('*') as $newNode ) {
				if ( $selector && ! $this->is($selector, $newNode) )
					continue;
				if ($this->elementsContainsNode($newNode, $stack))
					continue;
				$stack[] = $newNode;
			}
		}
		$this->elementsBackup = $this->elements;
		$this->elements = $stack;
		return $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * NON JQUERY-COMPATIBLE METHOD!
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function unwrapContent() {
		foreach( $this->elements as $node ) {
			if (! $node->parentNode )
				continue;
			$childNodes = array();
			// any modification in DOM tree breaks childNodes iteration, so cache them first
			foreach( $node->childNodes as $chNode )
				$childNodes[] = $chNode;
			foreach( $childNodes as $chNode )
//				$node->parentNode->appendChild($chNode);
				$node->parentNode->insertBefore($chNode, $node);
			$node->parentNode->removeChild($node);
		}
		return $this->newInstance();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function ancestors( $selector = null ) {
		return $this->children( $selector );
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function append( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function appendPHP( $content ) {
		return $this->insert("<php><!-- {$content} --></php>", 'append');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function appendTo( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function prepend( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @todo accept many arguments, which are joined, arrays maybe also
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function prependPHP( $content ) {
		return $this->insert("<php><!-- {$content} --></php>", 'prepend');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function prependTo( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function before( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function beforePHP( $content ) {
		return $this->insert("<php><!-- {$content} --></php>", 'before');
	}
	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function insertBefore( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function after( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function afterPHP( $content ) {
		return $this->insert("<php><!-- {$content} --></php>", 'after');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function insertAfter( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}

	/**
	 * Various insert scenarios.
	 *
	 * @param unknown_type $target
	 * @param unknown_type $type
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	protected function insert($target, $type) {
		$this->debug("Inserting data with '{$type}'");
		$to = false;
		switch( $type ) {
			case 'appendTo':
			case 'prependTo':
			case 'insertBefore':
			case 'insertAfter':
				$to = true;
		}
		switch(gettype( $target )) {
			case 'string':
				$insertFrom = $insertTo = array();
				if ( $to ) {
					$insertFrom = $this->elements;
					// insert into created element
					if ( phpQuery::isMarkup( $target ) ) {
						// TODO use phpQuery::loadHtml
						$DOM = new DOMDocument('1.0', 'utf-8');
						// FIXME tempolary
						$target = utf8_decode($target);
						@$DOM->loadHtml($target);
						foreach($DOM->documentElement->firstChild->childNodes as $node) {
							$insertTo[] = $this->DOM->importNode( $node, true );
						}
					// insert into selected element
					} else {
						$thisStack = $this->elements;
						$this->toRoot();
						$insertTo = $this->find($target)->elements;
						$this->elements = $thisStack;
					}
				} else {
					$insertTo = $this->elements;
					// insert created element
					if ( phpQuery::isMarkup( $target ) ) {
						// TODO use phpQuery::loadHtml
						$DOM = new DOMDocument('1.0', 'utf-8');
						// FIXME tempolary
						$target = utf8_decode($target);
						@$DOM->loadHtml($target);
						$insertFrom = array();
						foreach($DOM->documentElement->firstChild->childNodes as $node) {
							$insertFrom[] = $this->DOM->importNode($node, true);
						}
					// insert selected element
					} else {
						// FIXME tempolary, utf8 only
						// http://code.google.com/p/phpquery/issues/detail?id=17#c12
						if (mb_detect_encoding($target) == 'ASCII')
							$target	= mb_convert_encoding($target,'UTF-8','HTML-ENTITIES');
						$insertFrom = array(
							$this->DOM->createTextNode($target)
						);
					}
				}
				break;
			case 'object':
				$insertFrom = $insertTo = array();
				// phpQuery
				if ( $target instanceof self ) {
					if ( $to ) {
						$insertTo = $target->elements;
						if ( $this->documentFragment && $this->stackIsRoot() )
							// get all body children
							$loop = $this->find('body > *')->elements;
						else
							$loop = $this->elements;
						// import nodes if needed
						if ($this->domId != $target->domId)
							foreach($loop as $node)
								$insertFrom[] = c;
						else
							$insertFrom = $loop;
					} else {
						$insertTo = $this->elements;
						if ( $target->documentFragment && $target->stackIsRoot() )
							// get all body children
							$loop = $target->find('body > *')->elements;
						else
							$loop = $target->elements;
						// import nodes if needed
						if ($target->domId != $this->domId)
							foreach($loop as $node)
								$insertFrom[] = $this->DOM->importNode($node, true);
						else
							$insertFrom = $loop;
					}
				// DOMNODE
				} elseif ( $target instanceof DOMNODE) {
					// import node if needed
//					if ( $target->ownerDocument != $this->DOM )
//						$target = $this->DOM->importNode($target, true);
					if ( $to ) {
						$insertTo = array($target);
						if ( $this->documentFragment && $this->stackIsRoot() )
							// get all body children
							$loop = $this->find('body > *')->elements;
						else
							$loop = $this->elements;
						foreach($loop as $fromNode)
							// import nodes if needed
							$insertFrom[] = ! $fromNode->ownerDocument->isSameNode($target->ownerDocument)
								? $target->ownerDocument->importNode($fromNode, true)
								: $fromNode;
					} else {
						// import node if needed
						if (! $target->ownerDocument->isSameNode($this->DOM))
							$target = $this->DOM->importNode($target, true);
						$insertTo = $this->elements;
						$insertFrom[] = $target;
					}
				}
				break;
		}
		foreach( $insertTo as $insertNumber => $toNode ) {
			// we need static relative elements in some cases
			switch( $type ) {
				case 'prependTo':
				case 'prepend':
					$firstChild = $toNode->firstChild;
					break;
				case 'insertAfter':
				case 'after':
					$nextSibling = $toNode->nextSibling;
					break;
			}
			foreach( $insertFrom as $fromNode ) {
				// clone if inserted already before
				$insert = $insertNumber
					? $fromNode->cloneNode(true)
					: $fromNode;
				switch( $type ) {
					case 'appendTo':
					case 'append':
//						$toNode->insertBefore(
//							$fromNode,
//							$toNode->lastChild->nextSibling
//						);
						$toNode->appendChild($insert);
						break;
					case 'prependTo':
					case 'prepend':
						$toNode->insertBefore(
							$insert,
							$firstChild
						);
						break;
					case 'insertBefore':
					case 'before':
						if (! $toNode->parentNode)
							throw new Exception("No parentNode, can't do {$type}()");
						else
							$toNode->parentNode->insertBefore(
								$insert,
								$toNode
							);
						break;
					case 'insertAfter':
					case 'after':
						if (! $toNode->parentNode)
							throw new Exception("No parentNode, can't do {$type}()");
						else
							$toNode->parentNode->insertBefore(
								$insert,
								$nextSibling
							);
						break;
				}
			}
		}
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return Int
	 */
	public function index($subject) {
		$index = -1;
		$subject = $subject instanceof phpQueryObject
			? $subject->elements[0]
			: $subject;
		foreach($this->newInstance() as $k => $node) {
			if ($node->isSameNode($subject))
				$index = $k;
		}
		return $index;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $start
	 * @param unknown_type $end
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @testme
	 */
	public function slice($start, $end = null) {
//		$last = count($this->elements)-1;
//		$end = $end
//			? min($end, $last)
//			: $last;
//		if ($start < 0)
//			$start = $last+$start;
//		if ($start > $last)
//			return array();
		if ($end > 0)
			$end = $end-$start;
		return $this->newInstance(
			array_slice($this->elements, $start, $end)
		);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function reverse() {
		$this->elementsBackup = $this->elements;
		$this->elements = array_reverse($this->elements);
		return $this->newInstance();
	}

	public function text() {
		$return = '';
		foreach( $this->elements as $node ) {
			$return .= $node->textContent;
		}
		return $return;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function extend($class, $file) {
		phpQuery::extend($class, $file);
		return $this;
	}
	public function __call($method, $args) {
		$aliasMethods = array('clone', 'empty');
		if (method_exists($this, $method))
			return call_user_method_array(array($this, $method), $args);
		else if (in_array($method, $aliasMethods)) {
			return call_user_func_array(array($this, '_'.$method), $args);
		} else if (isset(phpQuery::$pluginsMethods[$method])) {
			array_unshift($args, $this);
			$class = phpQuery::$pluginsMethods[$method];
			$realClass = "phpQueryObjectPlugin_$class";
			$return = call_user_func_array(
				array($realClass, $method),
				$args
			);
			return is_null($return)
				? $this
				: $return;
		} else
			throw new Exception("Method '{$method}' doesnt exist");
	}

	/**
	 * Safe rename of next().
	 *
	 * Use it ONLY when need to call next() on an iterated object (in same time).
	 * Normaly there is no need to do such thing ;)
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function _next( $selector = null ) {
		return $this->newInstance(
			$this->getElementSiblings('nextSibling', $selector, true)
		);
	}

	/**
	 * Use prev() and next().
	 *
	 * @deprecated
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function _prev( $selector = null ) {
		return $this->prev($selector);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function prev( $selector = null ) {
		return $this->newInstance(
			$this->getElementSiblings('previousSibling', $selector, true)
		);
	}

	/**
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo
	 */
	public function prevAll( $selector = null ) {
		return $this->newInstance(
			$this->getElementSiblings('previousSibling', $selector)
		);
	}

	/**
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo FIXME: returns source elements insted of next siblings
	 */
	public function nextAll( $selector = null ) {
		return $this->newInstance(
			$this->getElementSiblings('nextSibling', $selector)
		);
	}

	protected function getElementSiblings($direction, $selector = null, $limitToOne = false) {
		$stack = array();
		$count = 0;
		foreach( $this->elements as $node ) {
			$test = $node;
			while( isset($test->{$direction}) && $test->{$direction} ) {
				$test = $test->{$direction};
				if (! $test instanceof DOMELEMENT)
					continue;
				if ( $selector ) {
					if ( $this->is( $selector, $test ) )
						$stack[] = $test;
				} else
					$stack[] = $test;
				if ($limitToOne && $stack)
					return $stack;
			}
		}
		return $stack;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function siblings($selector = null) {
		$stack = array();
		$siblings = array_merge(
			$this->getElementSiblings('previousSibling', $selector),
			$this->getElementSiblings('nextSibling', $selector)
		);
		foreach($siblings as $node) {
			if (! $this->elementsContainsNode($node, $stack))
				$stack[] = $node;
		}
		return $this->newInstance($stack);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function not($selector = null) {
		$stack = array();
		foreach($this->elements as $node) {
			if ($selector instanceof self) {
				// XXX chack all nodes ?
				if (count($selector->elements) && ! $selector->elements[0]->isSameNode($node))
					$stack[] = $node;
			} else if ($selector instanceof DOMNODE) {
				if (! $selector->isSameNode($node))
					$stack[] = $node;
			} else if (! $this->is($selector, $node))
				$stack[] = $node;
		}
		return $this->newInstance($stack);
	}

	/**
	 * Enter description here...
	 *
	 * @param string|phpQueryObject
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function add($selector = null) {
		if (! $selector)
			return $this;
		$stack = array();
		$this->elementsBackup = $this->elements;
		$found = phpQuery::pq($selector, $this->getDocumentID());
		$this->merge($found->elements);
		return $this->newInstance();
	}

	protected function importNodes($nodes) {
		foreach($nodes as $node)
			$this->document->importNode($node, true);
	}

	protected function merge() {
		foreach(func_get_args() as $nodes)
			foreach( $nodes as $newNode )
				if (! $this->elementsContainsNode($newNode) )
					$this->elements[] = $newNode;
	}

	protected function elementsContainsNode($nodeToCheck, $elementsStack = null) {
		$loop = ! is_null($elementsStack)
			? $elementsStack
			: $this->elements;
		foreach( $loop as $node ) {
			if ( $node->isSameNode( $nodeToCheck ) )
				return true;
		}
		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function parent( $selector = null ) {
		$stack = array();
		foreach( $this->elements as $node )
			if ( $node->parentNode && ! $this->elementsContainsNode($node->parentNode, $stack) )
				$stack[] = $node->parentNode;
		$this->elementsBackup = $this->elements;
		$this->elements = $stack;
		if ( $selector )
			$this->filter($selector, true);
		return $this->newInstance();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function parents( $selector = null ) {
		$stack = array();
		if (! $this->elements )
			$this->debug('parents() - stack empty');
		foreach( $this->elements as $node ) {
			$test = $node;
			while( $test->parentNode ) {
				$test = $test->parentNode;
				if ( is_a($test, 'DOMDocument') )
					break;
				if ( $selector ) {
					if ( $this->is( $selector, $test ) && ! $this->elementsContainsNode($test, $stack) ) {
						$stack[] = $test;
						continue;
					}
				} else if (! $this->elementsContainsNode($test, $stack) ) {
					$stack[] = $test;
					continue;
				}
			}
		}
		return $this->newInstance($stack);
	}

	/**
	 * Attribute method.
	 * Accepts * for all attributes (for setting and getting)
	 *
	 * @param unknown_type $attr
	 * @param unknown_type $value
	 * @return string|array|phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo uncheck other radios in group
	 * @todo select event
	 * @todo unselect other selecte's options when !multiply
	 * @todo  * *`val($val)`* Checks, or selects, all the radio buttons, checkboxes, and select options that match the set of values.
	 */
	public function attr($attr = null, $value = null) {
		if (! is_null( $value )) {
			// TODO tempolary solution
			// http://code.google.com/p/phpquery/issues/detail?id=17
			if (mb_detect_encoding($value) == 'ASCII')
				$value	= mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
		}
		foreach( $this->elements as $node ) {
			if (! is_null($value)) {
				$loop = $attr == '*'
					? $this->getNodeAttrs($node)
					: array($attr);
				foreach($loop as $a) {
					$event = null;
					if ($value) {
						// identifi
						$isInputValue = $node->tagName == 'input'
							&& (
								in_array($node->getAttribute('type'),
									array('text', 'password', 'hidden'))
								|| !$node->getAttribute('type')
							);
						$isRadio = $node->tagName == 'input'
							&& $node->getAttribute('type') == 'radio';
						$isCheckbox = $node->tagName == 'input'
							&& $node->getAttribute('type') == 'checkbox';
						$isOption = $node->tagName == 'option'
							&& $node->getAttribute('type') == 'radio';
						// detect 'change' event
						if ($isInputValue && $a == 'value'
							&& $value != $node->getAttribute($a)) {
							$event = new DOMEvent(array(
								'target' => $node,
								'type' => 'change'
							));
						} else if (($isRadio || $isCheckbox)
							&& $a == 'checked' && !$node->getAttribute($a)) {
							$event = new DOMEvent(array(
								'target' => $node,
								'type' => 'change'
							));
						} else if ($isOption && $node->parentNode
							&& $a == 'selected' && !$node->getAttribute($a)) {
							$event = new DOMEvent(array(
								'target' => $node->parentNode,
								'type' => 'change'
							));
						}
					}
					if ($value)
						$node->setAttribute($a, $value);
					else
						$node->removeAttribute($a);
					if ($event) {
						phpQueryEvent::trigger($this->getDocumentID(),
							$event->type, array($event)
						);
					}
				}
			} else if ($attr == '*') {
				// jQuery difference
				$return = array();
				foreach( $node->attributes as $n => $v)
					$return[$n] = $v->value;
				return $return;
			} else
				return $node->getAttribute($attr);
		}
		return $this;
	}

	/**
	 * Enter description here...
	 * jQuery difference.
	 *
	 * @param string $attr
	 * @param mixed $value
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo use attr() function (encoding issues etc).
	 */
	public function attrPrepend($attr, $value) {
		foreach( $this->elements as $node )
			$node->setAttribute($attr,
				$value.$node->getAttribute($attr)
			);
		return $this;
	}

	/**
	 * Enter description here...
	 * jQuery difference.
	 *
	 * @param string $attr
	 * @param mixed $value
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo use attr() function (encoding issues etc).
	 */
	public function attrAppend($attr, $value) {
		foreach( $this->elements as $node )
			$node->setAttribute($attr,
				$node->getAttribute($attr).$value
			);
		return $this;
	}

	protected function getNodeAttrs($node) {
		$return = array();
		foreach( $node->attributes as $n => $o)
			$return[] = $n;
		return $return;
	}


	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo check CDATA ???
	 */
	public function attrPHP( $attr, $value ) {
		if (! is_null( $value )) {
			$value = '<?php '.$value.' ?>';
			// TODO tempolary solution
			// http://code.google.com/p/phpquery/issues/detail?id=17
			if (mb_detect_encoding($value) == 'ASCII')
				$value	= mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
		}
		foreach( $this->elements as $node ) {
			if (! is_null( $value )) {
//				$attrNode = $this->DOM->createAttribute($attr);
				$node->setAttribute($attr, $value);
//				$attrNode->value = $value;
//				$node->appendChild($attrNode);
			} else if ( $attr == '*' ) {
				// jQuery diff
				$return = array();
				foreach( $node->attributes as $n => $v)
					$return[$n] = $v->value;
				return $return;
			} else
				return $node->getAttribute($attr);
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function removeAttr( $attr ) {
		foreach( $this->elements as $node ) {
			$loop = $attr == '*'
				? $this->getNodeAttrs($node)
				: array($attr);
			foreach( $loop as $a )
				$node->removeAttribute($a);
		}
		return $this;
	}

	/**
	 * Return form element value.
	 *
	 * @return String Fields value.
	 * @TODO $val
	 */
	public function val($val = null) {
		if ($this->eq(0)->is('select')) {
			// TODO
			return $this->eq(0)->find('option[selected=selected]')
				->attr('value');
		} else if ($this->eq(0)->is('textarea'))
			return $val
				? $this->eq(0)->html($val)->end()
				: $this->eq(0)->html($val);
		else
			return $val
				? $this->eq(0)->attr('value', $val)->end()
				: $this->eq(0)->attr('value', $val);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function andSelf() {
		if ( $this->previous )
			$this->elements = array_merge($this->elements, $this->previous->elements);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function addClass( $className ) {
		if (! $className)
			return $this;
		foreach( $this->elements as $node ) {
			if (! $this->is(".$className", $node))
				$node->setAttribute(
					'class',
					trim($node->getAttribute('class').' '.$className)
				);
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function addClassPHP( $className ) {
		foreach( $this->elements as $node ) {
//			if (! $this->is(".$className", $node)) {
//				$attr = $this->DOM->createAttribute('class');
				$classes = $node->getAttribute('class');
				$newValue = $classes
					? $classes.' <?php '.$className.' ?>'
					: '<?php '.$className.' ?>';
				$node->setAttribute('class', $newValue);
//				$attr->value = $newValue;
//				$node->removeAttribute('class');
//				$node->appendChild($attr);
				/*$attr = $node->setAttribute(
					'class',
					$classes = $node->getAttribute('class')
						? $classes.' <?php<!-- '.$className.'-->?>'
						: '<?php<!-- '.$className.'-->?>'
				);*/
//			}
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param	string	$className
	 * @return	bool
	 */
	public function hasClass( $className ) {
		foreach( $this->elements as $node ) {
			if ( $this->is(".$className", $node))
				return true;
		}
		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function removeClass( $className ) {
		foreach( $this->elements as $node ) {
			$classes = explode( ' ', $node->getAttribute('class'));
			if ( in_array($className, $classes) ) {
				$classes = array_diff($classes, array($className));
				if ( $classes )
					$node->setAttribute('class', implode(' ', $classes));
				else
					$node->removeAttribute('class');
			}
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function toggleClass( $className ) {
		foreach( $this->elements as $node ) {
			if ( $this->is( $node, '.'.$className ))
				$this->removeClass($className);
			else
				$this->addClass($className);
		}
		return $this;
	}

	/**
	 * Proper name without underscore (just ->empty()) also works.
	 *
	 * Removes all child nodes from the set of matched elements.
	 *
	 * Example:
	 * pq("p")._empty()
	 *
	 * HTML:
	 * <p>Hello, <span>Person</span> <a href="#">and person</a></p>
	 *
	 * Result:
	 * [ <p></p> ]
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function _empty() {
		foreach( $this->elements as $node ) {
			// many thx to 'dave at dgx dot cz' :)
			$node->nodeValue = '';
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param array|string $callback Expects $node as first param, $index as second
	 * @param array $scope External variables passed to callback. Use compact('varName1', 'varName2'...) and extract($scope)
	 * @param array $arg1 Will ba passed as third and futher args to callback.
	 * @param array $arg2 Will ba passed as fourth and futher args to callback, and so on...
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function each($callback, $param1 = null, $param2 = null, $param3 = null) {
		$paramStructure = null;
		if (func_num_args() > 1) {
			$paramStructure = func_get_args();
			$paramStructure = array_slice($paramStructure, 1);
		}
		foreach($this->elements as $v)
			phpQuery::callbackRun($callback, array($v), $paramStructure);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo add $scope and $args as in each() ???
	 */
	public function map($callback, $param1 = null, $param2 = null, $param3 = null) {
//		$stack = array();
////		foreach($this->newInstance() as $node) {
//		foreach($this->newInstance() as $node) {
//			$result = call_user_func($callback, $node);
//			if ($result)
//				$stack[] = $result;
//		}
		$params = func_get_args();
		array_unshift($params, $this->elements);
		return $this->newInstance(
			call_user_func_array(array('phpQuery', 'map'), $params)
//			phpQuery::map($this->elements, $callback)
		);
	}

	// INTERFACE IMPLEMENTATIONS

	// ITERATOR INTERFACE
	// TODO IteratorAggregate
	public function rewind(){
		$this->debug('iterating foreach');
		$this->elementsBackup = $this->elements;
		$this->elementsInterator = $this->elements;
		$this->valid = isset( $this->elements[0] )
			? 1
			: 0;
		$this->elements = $this->valid
			? array($this->elements[0])
			: array();
		$this->current = 0;
	}

	public function current(){
		return $this->elements[0];
	}

	public function key(){
		return $this->current;
	}
	/**
	 * Double-function method.
	 *
	 * First: main iterator interface method.
	 * Second: Returning next sibling, alias for _next().
	 *
	 * Proper functionality is choosed automagicaly.
	 *
	 * @see phpQueryObject::_next()
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function next($cssSelector = null){
//		if ($cssSelector || $this->valid)
//			return $this->_next($cssSelector);
		$this->current++;
		$this->valid = isset( $this->elementsInterator[ $this->current ] )
			? true
			: false;
		if ( $this->valid )
			$this->elements = array(
				$this->elementsInterator[ $this->current ]
			);
		else {
			$this->current--;
			return $this->_next($cssSelector);
		}
	}
	public function valid(){
		return $this->valid;
	}
	// ITERATOR INTERFACE END

	// ARRAYACCESS INTERFACE
	public function offsetExists($offset) {
		return $this->find($offset)->size() > 0;
	}
	public function offsetGet($offset) {
		return $this->find($offset);
	}
	public function offsetSet($offset, $value) {
		$this->find($offset)->replaceWith($value);
	}
	public function offsetUnset($offset) {
		// empty
		throw new Exception("Can't do unset, use array interface only for calling queries and replacing HTML.");
	}
	// ARRAYACCESS INTERFACE END

	/**
	 * Returns node's XPath.
	 *
	 * @param unknown_type $oneNode
	 * @return string
	 * @TODO use native getNodePath is avaible
	 */
	protected function getNodeXpath($oneNode = null) {
		$return = array();
		$loop = $oneNode
			? array($oneNode)
			: $this->elements;
		foreach( $loop as $node ) {
			if ($node instanceof DOMDOCUMENT) {
				$return[] = '';
				continue;
			}
			$xpath = array();
			while(! ($node instanceof DOMDOCUMENT) ) {
				$i = 1;
				$sibling = $node;
				while( $sibling->previousSibling ) {
					$sibling = $sibling->previousSibling;
					$isElement = $sibling instanceof DOMELEMENT;
					if ( $isElement && $sibling->tagName == $node->tagName )
						$i++;
				}
				$xpath[] = "{$node->tagName}[{$i}]";
				$node = $node->parentNode;
			}
			$xpath = join('/', array_reverse($xpath));
			$return[] = '/'.$xpath;
		}
		return $oneNode
			? $return[0]
			: $return;
	}
	// HELPERS
	public function whois($oneNode = null) {
		$return = array();
		$loop = $oneNode
			? array( $oneNode )
			: $this->elements;
		foreach( $loop as $node ) {
			$return[] = isset($node->tagName)
				? $node->tagName
					.($node->getAttribute('id')
						? '#'.$node->getAttribute('id'):'')
					.($node->getAttribute('class')
						? '.'.join('.', split(' ', $node->getAttribute('class'))):'')
				: get_class($node);
		}
		return $oneNode
			? $return[0]
			: $return;
	}
	/**
	 * Dump htmlOuter and preserve chain. Usefull for debugging.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function dump() {
		print __FILE__.':'.__LINE__."\n";
		var_dump($this->htmlOuter());
		return $this;
	}
	public function dumpWhois() {
		print __FILE__.':'.__LINE__."\n";
		var_dump($this->whois());
		return $this;
	}
	/**
	 * Dump htmlOuter and stop script execution. Usefull for debugging.
	 *
	 */
	public function dumpDie() {
		print __FILE__.':'.__LINE__;
		var_dump($this->htmlOuter());
		die();
	}
}
/**
 * Event handling class.
 *
 * @author Tobiasz Cudnik
 * @package phpQuery
 * @static
 */
class phpQueryEvent {
	/**
	 * Trigger a type of event on every matched element.
	 *
	 * @param DOMNode|phpQueryObject|string $document
	 * @param unknown_type $type
	 * @param unknown_type $data
	 *
	 * @TODO exclusive events (with !)
	 * @TODO global events
	 * @TODO support more than event in $type (space-separated)
	 */
	public static function trigger($document, $type, $data = array(), $node = null) {
		// trigger: function(type, data, elem, donative, extra) {
		$documentID = phpQuery::getDocumentID($document);
		$namespace = null;
		if (strpos($type, '.') !== false)
			list($name, $namespace) = explode('.', $type);
		else
			$name = $type;
		if (! $node) {
			if (self::issetGlobal($documentID, $type)) {
				$pq = phpQuery::getDocument($documentID);
				// TODO check add($pq->document)
				$pq->find('*')->add($pq->document)
					->trigger($type, $data);
			}
		} else {
			if (isset($data[0]) && $data[0] instanceof DOMEvent) {
				$event = $data[0];
				$data = array_slice($data, 1);
			} else {
				$event = new DOMEvent(array(
					'type' => $type,
					'target' => $node,
					'timeStamp' => time(),
				));
			}
			while($node) {
				phpQuery::debug("Triggering event '{$type}' on node ".phpQueryObject::whois($node)."\n");
				$event->currentTarget = $node;
				$eventNode = self::getNode($documentID, $node);
				if (isset($eventNode->eventHandlers)) {
					foreach($eventNode->eventHandlers as $eventType => $handlers) {
						$eventNamespace = null;
						if (strpos($type, '.') !== false)
							list($eventName, $eventNamespace) = explode('.', $eventType);
						else
							$eventName = $eventType;
						if ($name != $eventName)
							continue;
						if ($namespace && $eventNamespace && $namespace != $eventNamespace)
							continue;
						foreach($handlers as $handler) {
							$event->data = $handler['data']
								? $handler['data']
								: null;
							$return = call_user_func_array($handler['callback'], array_merge(array($event), $data));
						}
						if ($return === false) {
							$event->bubbles = false;
						}
					}
				}
				// to bubble or not to bubble...
				if (! $event->bubbles)
					break;
				$node = $node->parentNode;
			}
		}
	}
	/**
	 * Binds a handler to one or more events (like click) for each matched element.
	 * Can also bind custom events.
	 *
	 * @param DOMNode|phpQueryObject|string $document
	 * @param unknown_type $type
	 * @param unknown_type $data Optional
	 * @param unknown_type $callback
	 *
	 * @TODO support '!' (exclusive) events
	 * @TODO support more than event in $type (space-separated)
	 */
	public static function add($document, $node, $type, $data, $callback = null) {
		$documentID = phpQuery::getDocumentID($document);
//		if (is_null($callback) && is_callable($data)) {
//			$callback = $data;
//			$data = null;
//		}
		$eventNode = self::getNode($documentID, $node);
		if (! $eventNode)
			$eventNode = self::setNode($documentID, $node);
		if (!isset($eventNode->eventHandlers[$type]))
			$eventNode->eventHandlers[$type] = array();
		$eventNode->eventHandlers[$type][] = array(
			'callback' => $callback,
			'data' => $data,
		);
	}
	/**
	 * Enter description here...
	 *
	 * @param DOMNode|phpQueryObject|string $document
	 * @param unknown_type $type
	 * @param unknown_type $callback
	 *
	 * @TODO namespace events
	 * @TODO support more than event in $type (space-separated)
	 */
	public static function remove($document, $node, $type = null, $callback = null) {
		$documentID = phpQuery::getDocumentID($document);
		$eventNode = self::getNode($documentID, $node);
		if (is_object($eventNode) && isset($eventNode->eventHandlers[$type])) {
			if ($callback) {
				foreach($eventNode->eventHandlers[$type] as $k => $handler)
					if ($handler['callback'] == $callback)
						unset($eventNode->eventHandlers[$type][$k]);
			} else {
				unset($eventNode->eventHandlers[$type]);
			}
		}
	}
	protected static function getNode($documentID, $node) {
		foreach(phpQuery::$documents[$documentID]['eventNodes'] as $eventNode) {
			if ($node->isSameNode($eventNode))
				return $eventNode;
		}
	}
	protected static function setNode($documentID, $node) {
		phpQuery::$documents[$documentID]['eventNodes'][] = $node;
		return phpQuery::$documents[$documentID]['eventNodes'][
			count(phpQuery::$documents[$documentID]['eventNodes'])-1
		];
	}
	protected static function issetGlobal($documentID, $type) {
		return isset(phpQuery::$documents[$documentID])
			? in_array($type, phpQuery::$documents[$documentID]['eventGlobals'])
			: false;
	}
}
class CallbackParam {
	public $index = null;
	public function __construct($index = null) {
		$this->index = $index;
	}
}
/**
 * DOMEvent class.
 *
 * Based on
 * @link http://developer.mozilla.org/En/DOM:event
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 * @todo implement ArrayAccess ?
 */
class DOMEvent {
	/**
	 * Returns a boolean indicating whether the event bubbles up through the DOM or not.
	 *
	 * @var unknown_type
	 */
	public $bubbles = true;
	/**
	 * Returns a boolean indicating whether the event is cancelable.
	 *
	 * @var unknown_type
	 */
	public $cancelable = true;
	/**
	 * Returns a reference to the currently registered target for the event.
	 *
	 * @var unknown_type
	 */
	public $currentTarget;
	/**
	 * Returns detail about the event, depending on the type of event.
	 *
	 * @var unknown_type
	 * @link http://developer.mozilla.org/en/DOM/event.detail
	 */
	public $detail;	// ???
	/**
	 * Used to indicate which phase of the event flow is currently being evaluated.
	 *
	 * @var unknown_type
	 * @link http://developer.mozilla.org/en/DOM/event.eventPhase
	 */
	public $eventPhase;	// ???
	/**
	 * The explicit original target of the event (Mozilla-specific).
	 *
	 * @var unknown_type
	 */
	public $explicitOriginalTarget; // moz only
	/**
	 * The original target of the event, before any retargetings (Mozilla-specific).
	 *
	 * @var unknown_type
	 */
	public $originalTarget;	// moz only
	/**
	 * Identifies a secondary target for the event.
	 *
	 * @var unknown_type
	 */
	public $relatedTarget;
	/**
	 * Returns a reference to the target to which the event was originally dispatched.
	 *
	 * @var unknown_type
	 */
	public $target;
	/**
	 * Returns the time that the event was created.
	 *
	 * @var unknown_type
	 */
	public $timeStamp;
	/**
	 * Returns the name of the event (case-insensitive).
	 */
	public $type;
	public $runDefault = true;
	public $data = null;
	public function __construct($data) {
		foreach($data as $k => $v) {
			$this->$k = $v;
		}
		if (! $this->timeStamp)
			$this->timeStamp = time();
	}
	/**
	 * Cancels the event (if it is cancelable).
	 *
	 */
	public function preventDefault() {
		$this->runDefault = false;
	}
	/**
	 * Stops the propagation of events further along in the DOM.
	 *
	 */
	public function stopPropagation() {
		$this->bubbles = false;
	}
}
/**
 * Shortcut to phpQuery::pq($arg1, $context)
 * Chainable.
 *
 * @see phpQuery::pq()
 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
function pq($arg1, $context = null) {
	$args = func_get_args();
	return call_user_func_array(
		array('phpQuery', 'pq'),
		$args
	);
}
// add plugins dir and Zend framework to include path
set_include_path(
	get_include_path()
		.':'.dirname(__FILE__).'/'
		.':'.dirname(__FILE__).'/plugins/'
);
// why ? no __call nor __get for statics in php...
phpQuery::$plugins = new phpQueryPlugins();
?>

