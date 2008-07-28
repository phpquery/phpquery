<?php
/**
 * jQuery port to PHP.
 * phpQuery is chainable DOM selector & manipulator.
 * Compatible with jQuery 1.2.
 * 
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @link http://code.google.com/p/phpquery/
 * @link http://meta20.net/phpQuery
 * @link http://jquery.com
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @version 0.9.1 beta
 */

/**
 * @todo:
 * support DOMDocument in phpQuery::newDocument()
 * implements Countable
 * use when possible $n->getNodePath()
metadata plugin
forward funtions to jquery:
	events
	FIXME charset in inserts
 */

// class names for instanceof
define('DOMDOCUMENT', 'DOMDocument');
define('DOMELEMENT', 'DOMElement');
define('DOMNODELIST', 'DOMNodeList');
define('DOMNODE', 'DOMNode');

class phpQuery implements Iterator {
	public static $debug = false;
	protected static $documents = array();
	public static $lastDomId = null;
//	public static $defaultDoctype = 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';
	public static $defaultDoctype = '';
	public static $defaultEncoding = 'UTF-8';
	public $domId = null;
	/**
	 * Enter description here...
	 *
	 * @var DOMDocument
	 */
	public $DOM = null;
	protected $docId = null;
	protected $XPath = null;
	protected $elementsBackup = array();
	protected $elements = array();
	protected $previous = null;
	protected $root = array();
	public $documentFragment = true;
	/**
	 * Iterator helpers
	 */
	protected $elementsInterator = array();
	protected $valid = false;
	protected $current = null;
	/**
	 * Other helpers
	 */
	protected $regexpChars = array('^','*','$');
	// TODO check this within insert(), it probably not needed
	protected $tmpNodes = array();
	
	/**
	 * Multi-purpose function.
	 * Use pq() as shortcut.
	 * 
	 * *************
	 * 1. Import HTML into existing DOM (without any attaching):
	 * - Import into last used DOM:
	 *   pq('<div/>')				// DOESNT accept text nodes at beginning of input string !
	 * - Import into DOM with ID 'domId':
	 *   pq('<div/>', 'domId')
	 * - Import into same DOM as DOMNode belongs to:
	 *   pq('<div/>', DOMNode)
	 * - Import into DOM from phpQuery object:
	 *   pq('<div/>', phpQuery)
	 * *************
	 * 2. Run query:
	 * - Run query on last used DOM:
	 *   pq('div.myClass')
	 * - Run query on DOM with ID 'domId':
	 *   pq('div.myClass', 'domId')
	 * - Run query on same DOM as DOMNode belongs to and use node(s)as root for query:
	 *   pq('div.myClass', DOMNode)
	 * - Run query on DOM from $phpQueryObject and use object's stack as root nodes for query:
	 *   pq('div.myClass', $phpQueryObject )
	 * 
	 * @param string|DOMNode|DOMNodeList|array	$arg1	HTML markup, CSS Selector, DOMNode or array of DOMNodes
	 * @param string|phpQuery|DOMNode	$context	DOM ID from $pq->getDocumentId(), phpQuery object (determines also query root) or DOMNode (determines also query root)
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
		if (! $context)
			$domId = self::$lastDomId;
		else if ($context instanceof self)
			$domId = $context->domId;
		else if ($context instanceof DOMDOCUMENT) {
			foreach(phpQuery::$documents as $id => $document) {
				if ($context->isSameNode($document['document']))
					$domId = $id;
			}
			if (! $domId) {
				throw new Exception('Orphaned DOMNode');
//				$domId = self::newDocument($context);
			}
		} else if ($context instanceof DOMNODE) {
			foreach(phpQuery::$documents as $id => $document) {
				if ($context->ownerDocument->isSameNode($document['document']))
					$domId = $id;
			}
			if (! $domId){
				throw new Exception('Orphaned DOMNode');
//				$domId = self::newDocument($context->ownerDocument);
			}
		} else
			$domId = $context;
		if ($arg1 instanceof self) {
			/**
			 * Return $arg1 or import $arg1 stack if document differs:
			 * pq(pq('<div/>'))
			 */
			if ($arg1->domId == $domId)
				return $arg1;
			$phpQuery = new phpQuery($domId);
			$phpQuery->elements = array();
			foreach($arg1->elements as $node)
				$phpQuery->elements[] = $phpQuery->DOM->importNode($node, true);
			return $phpQuery;
		} else if ($arg1 instanceof DOMNODE || (is_array($arg1) && isset($arg1[0]) && $arg[0] instanceof DOMNODE)) {
			/**
			 * Wrap DOM nodes with phpQuery object, import into document when needed:
			 * pq(array($domNode1, $domNode2))
			 */
			$phpQuery = new phpQuery($domId);
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
			$phpQuery = new phpQuery($domId);
			$phpQuery->importMarkup($arg1);
			return $phpQuery;
		} else {
			/**
			 * Run CSS query:
			 * pq('div.myClass')
			 */
			$phpQuery = new phpQuery($domId);
			if ($context && $context instanceof self)
				$phpQuery->elements = $context->elements;
			else if ($context && $context instanceof DOMNODELIST) {
				$phpQuery->elements = array();
				foreach($context as $node)
					$phpQuery->elements[] = $node;
			} else if ($context && $context instanceof DOMNODE)
				$phpQuery->elements = array(DOMNODE);
			return $phpQuery->find($arg1);
		}
	}
	/**
	 * Sets defaults document to $id. Document has to be loaded prior
	 * to using this method.
	 * $id can be retrived via getDocumentId() or getDocumentIdRef().
	 *
	 * @param unknown_type $id
	 */
	public static function selectDocument($id) {
		self::$lastDomId = $id;
	}
	/**
	 * Returns document with id $id or last selected.
	 * $id can be retrived via getDocumentId() or getDocumentIdRef().
	 * Chainable.
	 *
	 * @see phpQuery::selectDocument()
	 * @param unknown_type $id
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function getDocument($id = null) {
		if ($id)
			self::selectDocument($id);
		else
			$id = phpQuery::$lastDomId;
		return new phpQuery($id);
	}
	/**
	 * Creates new document from $html.
	 * Chainable.
	 *
	 * @param unknown_type $html
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function newDocument($html) {
		$domId = self::createDom($html);
		return new phpQuery($domId);
	}
	/**
	 * Creates new document from file $file.
	 * Chainable.
	 *
	 * @param string $file URLs allowed. See File wrapper page at php.net for more supported sources.
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public static function newDocumentFile($file) {
		$domId = self::createDomFromFile($file);
		return new phpQuery($domId);
	}
	public static function documentFragment($state = null) {
		if ( $state ) {
			self::$documents[$this->docId]['documentFragment'] = $state;
			return $this;
		}
		return $this->documentFragment;
	}
	public static function createDomFromFile($file, $domId = null) {
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
	 */
	public static function createDom($html, $domId = null) {
		$id = $domId
			? $domId
			: md5(microtime());
		// create document
		self::$documents[ $id ] = array(
			'documentFragment' => true,
			'document' =>  new DOMDocument(),
		);
		$DOM =& self::$documents[ $id ];
		// load data
		if (! self::loadHtml($DOM, $html)) {
			throw new Exception("Can't load '{$html}'");
			return;
		}
//		if (! $DOM['document']->encoding ) {
//			if ( self::$debug )
//				print(
//					"No encoding selected, reloading with default: ".self::$defaultEncoding
//				);
////			$DOM['document'] = new DOMDocument('1.0', 'utf-8');
//			$html = '<meta http-equiv="Content-Type" content="text/html;charset='
//				.self::$defaultEncoding.'">'
//				.$html;
//			if ( self::$debug )
//				print($html);
//			self::loadHtml($DOM, $html);
////			if ( $DOM['documentFragment'] ) {
////				$head = $DOM['document']
////					->getElementsByTagName('head')
////					->item(0);
////				if ( $head->childNodes->length == 1 ) {
////					$head->removeChild($head->firstChild);
////					$head->parentNode->removeChild($head);
////				}
////			}
//				
//		}
		$DOM['xpath'] = new DOMXPath(
			$DOM['document']
		);
		// remember last document
		return self::$lastDomId = $id;
	}
	protected static function loadHtmlFile(&$DOM, $file) {
		return self::loadHtml($DOM, file_get_contents($file));
	}
	protected static function loadHtml(&$DOM, $html) {
		self::checkDocumentFragment($DOM, $html);
		if (! self::containsEncoding($html) )
			$html = self::appendEncoding($html);
//			$html = mb_convert_encoding($html, 'HTML-ENTITIES', self::$defaultEncoding);
//			$html = '<meta http-equiv="Content-Type" content="text/html;charset='.self::$defaultEncoding.'">'.$html;
		// TODO if ! self::containsEncoding() && self::containsHead() then attach encoding inside head
		// check comments on php.net about problems with charset when loading document without encoding as first line
		return @$DOM['document']->loadHTML($html);
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
	public static function unloadDocuments( $path = null ) {
		if ( $path )
			unset( self::$documents[ $path ] );
		else
			unset( self::$documents );
	}
	/**
	 * Get objetc's Document ID for later use.
	 * Value is returned via reference.
	 * <code>
	 * $myDocumentId;
	 * phpQuery::newDocument('<div/>')
	 *     ->getDocumentIdRef($myDocumentId)
	 *     ->find('div')->...
	 * </code>
	 *
	 * @param unknown_type $domId
	 * @see phpQuery::newDocument
	 * @see phpQuery::newDocumentFile
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function getDocumentIdRef(&$domId) {
		$domId = $this->domId;
		return $this;
	}
	/**
	 * Get objetc's Document ID for later use.
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function getDocumentId() {
		return $this->domId;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function unload() {
		unset( self::$documents[ $this->domId ] );
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function __construct($domId) {
		if ( $domId instanceof self )
			$domId = $domId->domId;
		if (! isset(self::$documents[$domId] ) ) {
			throw new Exception("DOM with ID '{$domId}' isn't loaded. Use phpQuery::newDocument(\$html) or phpQuery::newDocumentFile(\$file) first.");
			return;
		}
		$this->domId = $domId;
		self::$lastDomId = $domId;
		$this->DOM = self::$documents[$domId]['document'];
		$this->XPath = self::$documents[$domId]['xpath'];
		$this->documentFragment = self::$documents[$domId]['documentFragment'];
		$this->root = $this->DOM->documentElement;
//		$this->toRoot();
		$this->elements = array($this->root);
	}
	protected function debug($in) {
		if (! self::$debug )
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
		return $index
			? $this->elements[$index]
			: $this->elements;
	}
	/**
	 * Returns new instance of actual class.
	 *
	 * @param array $newStack Optional. Will replace old stack with new and move old one to history.
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	protected function newInstance($newStack = null) {
		$class = get_class($this);
		// support inheritance by passing old object to overloaded constructor
		$new = $class != 'phpQuery'
			? new $class($this, $this->domId)
			: new phpQuery($this->domId);
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
			$remove = false;
			// to work on detached nodes we need temporary place them somewhere
			// thats because context xpath queries sucks ;]
			if (! $stackNode->parentNode && ! $this->isRoot($stackNode) ) {
				$this->root->appendChild($stackNode);
				$remove = true;
			}
			$xpath = $this->getNodeXpath($stackNode);
			$query = $xpath.$XQuery;
			$this->debug("XPATH: {$query}");
			// run query, get elements
			$nodes = $this->XPath->query($query);
			$this->debug("QUERY FETCHED");
			if (! $nodes->length )
				$this->debug('Nothing found');
			foreach( $nodes as $node ) {
				$matched = false;
				if ( $compare ) {
					self::$debug ?
						$this->debug("Found: ".$this->whois( $node ).", comparing with {$compare}()")
						: null;
					if ( call_user_method($compare, $this, $selector, $node) )
						$matched = true;
				} else {
					$matched = true;
				}
				if ( $matched ) {
					self::$debug
						? $this->debug("Matched: ".$this->whois( $node ))
						: null;
					$stack[] = $node;
				}
			}
			if ( $remove )
				$stackNode = $this->root->removeChild( $this->root->lastChild );
		}
		$this->elements = $stack;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function find( $selectors, $context = null ) {
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
					$this->filterPseudoClasses( $s );
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
	protected function filterPseudoClasses( $class ) {
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
					if ( $class == 'even' && $i % 2 == 0 )
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
			case 'parent':
				$stack = array();
				foreach( $this->elements as $node ) {
					if ( $node->childNodes->length )
						$stack[] = $node;
				}
				$this->elements = $stack;
				break;
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
					if ( $this->find($selector, $el)->length() )
						$stack[] = $el;
				}
				$this->elements = $stack;
				break;
			default:
				$this->debug("Unknown pseudoclass '{$class}', skipping...");
		}
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function is( $selector, $_node = null ) {
		$this->debug(array("Is:", $selector));
		if (! $selector)
			return false;
		$oldStack = $this->elements;
		if ( $_node )
			$this->elements = array($_node);
		$this->filter($selector, true);
		$match = (bool)$this->length();
		$this->elements = $oldStack;
		return $match;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
			$this->elements = $stack;
			// PER ALL NODES selector chunks
			foreach($selector as $s)
				// PSEUDO CLASSES
				if ( $s[0] == ':' )
					$this->filterPseudoClasses($s);
		}
		return $_skipHistory
			? $this
			: $this->newInstance();
	}
	
	protected function isRoot( $node ) {
//		return $node instanceof DOMDOCUMENT || $node->tagName == 'html';
		return $node instanceof DOMDOCUMENT
			|| ($node instanceof DOMNODE && $node->tagName == 'html')
			|| $this->root->isSameNode($node);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function css() {
		// TODO
	}
	
	protected function importMarkup($html) {
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
//			self::$documents[$this->domId],
//			$html
//		);
//		foreach($DOM->documentElement->firstChild->childNodes as $node)
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapAllTest($wrapper) {
		if (! $this->length() )
			return $this;
		return pq($wrapper)
			->_clone()
			->insertBefore($this->elements[0])
			->map(array(self, 'wrapAllCallback'))
			->append($this);
	}
	
	protected function wrapAllCallback($node) {
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrap($wrapper) {
		foreach($this as $node)
			self::pq($node, $this->domId)->wrapAll($wrapper);
		return $this;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapInner($wrapper) {
		foreach($this as $node)
			self::pq($node, $this->domId)->contents()->wrapAll($wrapper);
		return $this;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function size() {
		return $this->length();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function length() {
		return count( $this->elements );
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function end() {
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
	 * XXX what is this ?
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function select($selector) {
		return $this->is($selector)
			? $this->filter($selector)
			: $this->find($selector);
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
		return $this;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function replacePHP($code) {
		return $this->replaceWith("<php><!-- {$code} --></php>");
	}
	
	/**
	 * Enter description here...
	 * 
	 * @param String|phpQuery $content
	 * @link http://docs.jquery.com/Manipulation/replaceWith#content
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function replaceWith($content) {
		return $this->after($content)->remove();
	}
	
	/**
	 * Enter description here...
	 *
	 * @param String $selector
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo this works ?
	 */
	public function replaceAll($selector) {
		foreach(self::pq($selector, $this->domId) as $node)
			self::pq($node, $this->domId)
				->after($this->_clone())
				->remove();
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * Checks if $input is HTML string, which has to start with '<'.
	 *
	 * @param String $input
	 * @return Bool
	 */
	protected static function isMarkup($input) {
		return substr(trim($input), 0, 1) == '<';
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
			if ( self::isMarkup( $html ) ) {
				$toInserts = array();
				$DOM = new DOMDocument();
				@$DOM->loadHtml( $html );
				foreach($DOM->documentElement->firstChild->childNodes as $node)
					$toInserts[] = $this->DOM->importNode($node, true);
			} else {
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
					: self::$defaultEncoding
			);
			$nodes = array();
			foreach( $this->elements as $node )
				foreach( $node->childNodes as $child ) {
					$nodes[] = $child;
					$DOM->appendChild(
						$DOM->importNode( $child, true )
					);
				}
			if (! self::containsEncoding($nodes) ) {
				$html = $this->fixXhtml(
					$DOM->saveXML()
				);
				if (! self::isXhtml($this->DOM))
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
				: self::$defaultEncoding
		);
		foreach( $this->elements as $node ) {
			$DOM->appendChild(
				$DOM->importNode( $node, true )
			);
		}
		if (! self::containsEncoding($this->elements) ) {
			$html = $this->fixXhtml(
				$DOM->saveXML()
			);
			if (! self::isXhtml($this->DOM))
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
			: self::$defaultDoctype;
		if ($DOM->isSameNode($this->DOM) && $this->documentFragment && $this->stackIsRoot())
			// double php tags removement ?
			$return = $this->find('body')->html();
		else
			$return = self::isXhtml($DOM)
				? $this->fixXhtml( $DOM->saveXML() )
				: $DOM->saveHTML();
//		debug($return);
		return $return;
	}
	/**
	 * Enter description here...
	 *
	 * @param string|DOMNode $html
	 */
	protected static function containsEncoding($html) {
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
	protected function stackIsRoot() {
		return $this->size() == 1 && $this->isRoot($this->elements[0]);
	}
	/**
	 * Parses phpQuery object or HTML result against PHP tags and makes them active.
	 *
	 * @param phpQuery|string $content
	 * @return string
	 */
	public static function unsafePhpTags($content) {
		if ($content instanceof phpQuery)
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function php($code) {
//		TODO
//		$args = func_get_args();
		return $this->html("<php><!-- ".trim($code)." --></php>");
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function ancestors( $selector = null ) {
		return $this->children( $selector );
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function append( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function appendPHP( $content ) {
		return $this->insert("<php><!-- {$content} --></php>", 'append');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function appendTo( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function prepend( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function prependPHP( $content ) {
		return $this->insert("<php><!-- {$content} --></php>", 'prepend');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function prependTo( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function before( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function beforePHP( $content ) {
		return $this->insert("<php><!-- {$content} --></php>", 'before');
	}
	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function insertBefore( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function after( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function afterPHP( $content ) {
		return $this->insert("<php><!-- {$content} --></php>", 'after');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function insertAfter( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	/**
	 * Various insert scenarios.
	 *
	 * @param unknown_type $target
	 * @param unknown_type $type
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
				if ( $to ) {
					$insertFrom = $this->elements;
					// insert into created element
					if ( self::isMarkup( $target ) ) {
						// TODO use phpQuery::loadHtml
						$DOM = new DOMDocument('1.0', 'utf-8');
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
					if ( self::isMarkup( $target ) ) {
						// TODO use phpQuery::loadHtml
						$DOM = new DOMDocument('1.0', 'utf-8');
						@$DOM->loadHtml($target);
						$insertFrom = array();
						foreach($DOM->documentElement->firstChild->childNodes as $node) {
							$insertFrom[] = $this->DOM->importNode($node, true);
						}
					// insert selected element
					} else {
						$insertFrom = array(
							$this->DOM->createTextNode( $target )
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
								$insertFrom[] = $target->DOM->importNode($node, true);
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
		foreach($this->newInstance() as $k => $node) {
			if ($node->isSameNode($subject->elements[0]))
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function reverse() {
		$this->elementsBackup = $this->elements;
		$this->elements = array_reverse($this->elements);
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function _next( $selector = null ) {
		return $this->newInstance(
			$this->getElementSiblings('nextSibling', $selector, true)
		);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function _prev( $selector = null ) {
		return $this->prev($selector);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function prev( $selector = null ) {
		return $this->newInstance(
			$this->getElementSiblings('previousSibling', $selector, true)
		);
	}
	
	/**
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo
	 */
	public function prevAll( $selector = null ) {
		return $this->newInstance(
			$this->getElementSiblings('previousSibling', $selector)
		);
	}
	
	/**
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function siblings($selector = null) {
		$stack = array();
		$siblings = array_merge(
			$this->getElementSiblings('prevSibling', $selector),
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function not( $selector = null ) {
		$stack = array();
		foreach( $this->elements as $node ) {
			if (! $this->is( $selector, $node ) )
				$stack[] = $node;
		}
		return $this->newInstance($stack);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function add( $selector = null ) {
		$stack = array();
		$this->elementsBackup = $this->elements;
		$found = $this->find($selector);
		$this->merge($found->elements);
		return $this->newInstance();
	}
	
	protected function merge() {
		foreach( get_func_args() as $nodes )
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return string|array|phpQuery
	 */
	public function attr($attr = null, $value = null) { 
		foreach( $this->elements as $node ) {
			if (! is_null( $value )) {
				$loop = $attr == '*'
					? $this->getNodeAttrs($node)
					: array($attr);
				foreach( $loop as $a ) {
					if ( $value )
						$node->setAttribute($a, $value);
					else
						$node->removeAttribute($a);
				}
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
	 * @param string $attr
	 * @param mixed $value
	 * @return phpQuery
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
	 *
	 * @param string $attr
	 * @param mixed $value
	 * @return phpQuery
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function attrPHP( $attr, $value ) { 
		foreach( $this->elements as $node ) {
			if (! is_null( $value )) {
//				$attrNode = $this->DOM->createAttribute($attr);
				$value = '<?php '.$value.' ?>';
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 */
	public function val() {
		$el = $this->eq(0);
		if ($el->is('select'))
			return $el->find('option[selected=selected]')
				->attr('value');
		else
			return $el->attr('value');
	}
	
	/**
	 * Enter description here...
	 * 
	 * @return phpQuery
	 */
	public function andSelf() {
		if ( $this->previous )
			$this->elements = array_merge($this->elements, $this->previous->elements);
	}	
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
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
	 * @param unknown_type $callback
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function each($callback) {
		foreach($this->newInstance() as $node)
			call_user_func($callback, $node);
		return $this;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function map($callback) {
		$stack = array();
		foreach($this->newInstance() as $node) {
			$result = call_user_func($callback, $node);
			if ($result)
				$stack[] = $result;
		}
		return $this->newInstance($stack);
	}

	// ITERATOR INTERFACE
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

	public function next($cssSelector = null){
		if ($cssSelector)
			return $this->_next($cssSelector);
		$this->current++;
		$this->valid = isset( $this->elementsInterator[ $this->current ] )
			? true
			: false;
		if ( $this->valid )
			$this->elements = array(
				$this->elementsInterator[ $this->current ]
			);
	}
	public function valid(){
		return $this->valid;
	}
	// ITERATOR INTERFACE END

	protected function getNodeXpath( $oneNode = null ) {
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
	// HELPERS
	/**
	 * Dump htmlOuter and preserve chain. Usefull for debugging.
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function dump() {
		print __FILE__.':'.__LINE__;
		var_dump($this->htmlOuter());
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
	public function dumpStack() { 
		print __FILE__.':'.__LINE__;
		$i = 1;
		foreach( $this->elements as $node )
			$this->debug("Node ".$i++.": ".$this->whois($node));
		return $this;
	}
	protected function dumpHistory($when = null) {
		print __FILE__.':'.__LINE__;
		foreach( $this->history as $nodes ) {
			$history[] = array();
			foreach( $nodes as $node ) {
				$history[ count($history)-1 ][] = $this->whois( $node );
			}
		}
		var_dump(array("{$when}/history", $history));
	}
}
if (! function_exists('pq')) {
	/**
	 * Equivalent of:
	 * <code>
	 * phpQuery::pq($arg1, $arg2, ...)
	 * </code>
	 * Chainable.
	 *
	 * @return phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	function pq() {
		$args = func_get_args();
		return call_user_func_array(
			array('phpQuery', 'pq'),
			$args
		);
	}
}
?>