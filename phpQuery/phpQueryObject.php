<?php
/**
 * Class representing phpQuery objects.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 */
class phpQueryObject
	implements Iterator, Countable, ArrayAccess {
	public $documentID = null;
	/**
	 * DOMDocument class.
	 *
	 * @var DOMDocument
	 */
	public $document = null;
	public $charset = null;
	/**
	 *
	 * @var DOMDocumentWrapper
	 */
	public $documentWrapper = null;
	/**
	 * XPath interface.
	 *
	 * @var DOMXPath
	 */
	public $xpath = null;
	/**
	 * Stack of selected elements.
	 * @TODO refactor to ->nodes
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
	public function __construct($documentID) {
		$id = $documentID instanceof self
			? $documentID->getDocumentID()
			: $documentID;
		if (! isset(phpQuery::$documents[$id] ) ) {
			throw new Exception("Document with ID '{$id}' isn't loaded. Use phpQuery::newDocument(\$html) or phpQuery::newDocumentFile(\$file) first.");
			return;
		}
		$this->documentID = $id;
		$this->documentWrapper =& phpQuery::$documents[$id];
		$this->document =& $this->documentWrapper->document;
		$this->xpath =& $this->documentWrapper->xpath;
		$this->charset =& $this->documentWrapper->charset;
		$this->documentFragment =& $this->documentWrapper->isDocumentFragment;
		// TODO check $this->DOM->documentElement;
//		$this->root = $this->document->documentElement;
		$this->root =& $this->documentWrapper->root;
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
			phpQuery::$documents[$this->getDocumentID()]['documentFragment'] = $state;
			return $this;
		}
		return $this->documentFragment;
	}
	protected function isRoot( $node ) {
//		return $node instanceof DOMDOCUMENT || $node->tagName == 'html';
		return $node instanceof DOMDOCUMENT
			|| ($node instanceof DOMELEMENT && $node->tagName == 'html')
			|| $this->root->isSameNode($node);
	}
	protected function stackIsRoot() {
		return $this->size() == 1 && $this->isRoot($this->elements[0]);
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
	/**
	 * Saves object's DocumentID to $var by reference.
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
		$documentID = $this->getDocumentID();
		return $this;
	}
	/**
	 * Returns object with stack set to document root.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function getDocument() {
		return phpQuery::getDocument($this->getDocumentID());
	}
	/**
	 *
	 * @return DOMDocument
	 */
	public function getDOMDocument() {
		return $this->document;
	}
	/**
	 * Get object's Document ID.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function getDocumentID() {
		return $this->documentID;
	}
	/**
	 * Unloads whole document from memory.
	 * CAUTION! None further operations will be possible on this document.
	 * All objects refering to it will be useless.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function unloadDocument() {
		phpQuery::unloadDocuments($this->getDocumentID());
	}
	public function isHTML() {
		return $this->documentWrapper->isXML;
	}
	public function isXHTML() {
		return $this->documentWrapper->isXHTML;
	}
	public function isXML() {
		return $this->documentWrapper->isXML;
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
		if (! $query)
			return $queries;
		if ($query{0} == ':')
			$query = '*'.$query;
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
			if ( $this->isChar($c) || $c == '*' || $c == '|' ) {
				while( isset($query[$i]) && ($this->isChar($query[$i]) || $query[$i] == '*' || $query[$i] == '|')) {
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
	public function get($index = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		$return = ! is_null($index)
			? $this->elements[$index]
			: $this->elements;
		// pass thou callbacks
		$args = func_get_args();
		$args = array_slice($args, 1);
		foreach($args as $callback) {
			$return = phpQuery::callbackRun($callback, array($return));
		}
		return $return;
	}
	/**
	 * Return matched DOM nodes.
	 * jQuery difference.
	 *
	 * @param int $index
	 * @return array|string Returns string if $index != null
	 * @todo implement callbacks
	 * @todo return only arrays ?
	 * @todo maybe other name...
	 */
	public function getString($index = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		if ($index)
			return trim($this->eq($index)->text());
		$return = array();
		for($i = 0; $i < $this->size(); $i++) {
			$return[] = trim($this->eq($i)->text());
		}
		// pass thou callbacks
		$args = func_get_args();
		$args = array_slice($args, 1);
		foreach($args as $callback) {
			$return = phpQuery::callbackRun($callback, array($return));
		}
		return $return;
	}
	/**
	 * Returns new instance of actual class.
	 *
	 * @param array $newStack Optional. Will replace old stack with new and move old one to history.c
	 */
	public function newInstance($newStack = null) {
		$class = get_class($this);
		// support inheritance by passing old object to overloaded constructor
		$new = $class != 'phpQuery'
			? new $class($this, $this->getDocumentID())
			: new phpQueryObject($this->getDocumentID());
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
			// TODO tmp
			$xpath = $this->documentWrapper->isXHTML
				? $this->getNodeXpath($stackNode, 'html')
				: $this->getNodeXpath($stackNode);
			// FIXME deam...
			$query = $XQuery == '//' && $xpath == '/html[1]'
				? '//*'
				: $xpath.$XQuery;
			$this->debug("XPATH: {$query}");
			// run query, get elements
			$nodes = $this->xpath->query($query);
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
					// TODO use phpQuery::callbackRun()
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
				if ( preg_match('@^[\w|\|]+$@', $s) || $s == '*' ) {
					// TODO support namespaces
					if ($this->documentWrapper->isXHTML) {
						$XQuery .= "html:{$s}";
					} else
						$XQuery .= $s;
				// ID
				} else if ( $s[0] == '#' ) {
					if ( $spaceBefore )
						$XQuery .= '*';
					$XQuery .= "[@id='".substr($s, 1)."']";
				// ATTRIBUTES
				} else if ($s[0] == '[') {
					if ( $spaceBefore )
						$XQuery .= '*';
					// strip side brackets
					$attr = trim($s, '][');
					$execute = false;
					// attr with specifed value
					if (strpos( $s, '=' )) {
						list($attr, $value) = explode('=', $attr);
						$value = trim($value, "'\"");
						if ($this->isRegexp($attr)) {
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
					if ($execute) {
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
						return $node instanceof DOMELEMENT && $node->childNodes->length
							? $node : null;')
				)->elements;
			break;
			case 'empty':
				$this->elements = $this->map(
					create_function('$node', '
						return $node instanceof DOMELEMENT && $node->childNodes->length
							? null : $node;')
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
						'$isHeader = isset($node->tagName) && in_array($node->tagName, array(
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
			if (false !== phpQuery::callbackRun($callback, array($index, $node)))
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
				// TODO support other nodeTypes
//				if (! ($node instanceof DOMELEMENT))
//					continue;
				$break = false;
				foreach( $selector as $s ) {
					if (!($node instanceof DOMELEMENT)) {
						if ( $s[0] == '[' ) {
							$attr = trim($s, '[]');
							if ( strpos($attr, '=') ) {
								list( $attr, $val ) = explode('=', $attr);
								if ($attr == 'nodeType' && $node->nodeType != $val)
									$break = true;
							}
						} else
							$break = true;
					} else {
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
								if ($attr == 'nodeType') {
									if ($val != $node->nodeType)
										$break = true;
								} else if ( $this->isRegexp($attr)) {
									$val = trim($val, '"\'');
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
			phpQueryEvents::trigger($this->getDocumentID(), $type, $data, $node);
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
			phpQueryEvents::add($this->getDocumentID(), $node, $type, $data, $callback);
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
			phpQueryEvents::remove($this->getDocumentID(), $node, $type, $callback);
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
	public function submit($callback = null) {
		if ($callback)
			return $this->bind('submit', $callback);
		return $this->trigger('submit');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function click($callback = null) {
		if ($callback)
			return $this->bind('click', $callback);
		return $this->trigger('click');
	}
	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapAllOld($wrapper) {
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
	public function wrapAll($wrapper) {
		if (! $this->length())
			return $this;
		return phpQuery::pq($wrapper, $this->getDocumentID())
			->_clone()
			->insertBefore($this->get(0))
			->map(array($this, '___wrapAllCallback'))
			->append($this);
	}

	public function ___wrapAllCallback($node) {
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
		foreach($this->stack() as $node)
			phpQuery::pq($node, $this->getDocumentID())->wrapAll($wrapper);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapPHP($codeBefore, $codeAfter) {
		foreach($this->stack() as $node)
			phpQuery::pq($node, $this->getDocumentID())->wrapAllPHP($codeBefore, $codeAfter);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapInner($wrapper) {
		foreach($this->stack() as $node)
			phpQuery::pq($node, $this->getDocumentID())->contents()->wrapAll($wrapper);
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @param String|phpQuery
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function wrapInnerPHP($wrapper) {
		// TODO test
//		return $this->wrapInner("<php><!-- {$wrapper} --></php>");
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @testme Support for text nodes
	 */
	public function contents() {
		$stack = array();
		foreach($this->stack(1) as $el) {
			// FIXME (fixed) http://code.google.com/p/phpquery/issues/detail?id=56
//			if (! isset($el->childNodes))
//				continue;
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
//		$this->previous->DOM = $this->DOM;
//		$this->previous->XPath = $this->XPath;
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
		foreach(phpQuery::pq($selector, $this->getDocumentID()) as $node)
			phpQuery::pq($node, $this->getDocumentID())
				->after($this->_clone())
				->remove();
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function remove($selector = null) {
		$loop = $selector
			? $this->filter($selector)->elements
			: $this->elements;
		foreach($loop as $node) {
			if (! $node->parentNode )
				continue;
			if (isset($node->tagName))
				$this->debug("Removing '{$node->tagName}'");
			$node->parentNode->removeChild( $node );
		}
		return $this;
	}

	/**
	 * jQuey difference
	 *
	 * @param $markup
	 * @return unknown_type
	 */
	public function markup($markup = null) {
		if ($this->documentWrapper->isXML)
			return $this->xml($markup);
		else
			return $this->html($markup);
	}
	/**
	 * jQuey difference
	 *
	 * @param $markup
	 * @return unknown_type
	 */
	public function markupOuter() {
		if ($this->documentWrapper->isXML)
			return $this->xmlOuter();
		else
			return $this->htmlOuter();
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $html
	 * @return string|phpQuery|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function html($html = null) {
		if (isset($html)) {
			// INSERT
			$nodes = $this->documentWrapper->import($html);
			$this->empty();
			// i dont like brackets ! python rules ! ;)
			foreach($nodes as $newNode)
				foreach($this->elements as $alreadyAdded => $node)
					$node->appendChild($alreadyAdded
						? $newNode->cloneNode(true)
						: $newNode
					);
			return $this;
		} else {
			// FETCH
			return $this->documentWrapper->markup($this->elements, true);
		}
	}
	public function xml($xml = null) {
		return $this->html($xml);
	}
	/**
	 * Enter description here...
	 *
	 * @return String
	 */
	public function htmlOuter($callback1 = null, $callback2 = null, $callback3 = null) {
		$markup = $this->documentWrapper->markup($this->elements);
		// pass thou callbacks
		$args = func_get_args();
		foreach($args as $callback) {
			$markup = phpQuery::callbackRun($callback, array($markup));
		}
		return $markup;
	}
	public function xmlOuter($callback1 = null, $callback2 = null, $callback3 = null) {
		$args = func_get_args();
		return call_user_func_array(array($this, 'htmlOuter'), $args);
	}
	public function __toString() {
		return $this->htmlOuter();
	}
	/**
	 * Just like html(), but returns markup with VALID (dangerous) PHP tags.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 * @todo support returning markup with PHP tags when called without param
	 */
	public function php($code = null) {
//		TODO
//		$args = func_get_args();
		return $code
			? $this->html("<php><!-- ".trim($code)." --></php>")
			: phpQuery::unsafePHPTags($this->htmlOuter());
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function children($selector = null) {
		$stack = array();
		foreach($this->stack(1) as $node) {
//			foreach($node->getElementsByTagName('*') as $newNode) {
			foreach($node->childNodes as $newNode) {
				if ($newNode->nodeType != 1)
					continue;
				if ($selector && ! $this->is($selector, $newNode))
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
	 * jQuery difference.
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function unwrapContent() {
		foreach($this->stack(1) as $node) {
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
				if ($to) {
					// INSERT TO
					$insertFrom = $this->elements;
					if (phpQuery::isMarkup( $target )) {
						// $target is new markup, import it
						$insertTo = $this->documentWrapper->import($target);
					// insert into selected element
					} else {
						// $tagret is a selector
						$thisStack = $this->elements;
						$this->toRoot();
						$insertTo = $this->find($target)->elements;
						$this->elements = $thisStack;
					}
				} else {
					// INSERT FROM
					$insertTo = $this->elements;
					$insertFrom = $this->documentWrapper->import($target);
				}
				break;
			case 'object':
				$insertFrom = $insertTo = array();
				// phpQuery
				if ($target instanceof self) {
					if ($to) {
						$insertTo = $target->elements;
						if ($this->documentFragment && $this->stackIsRoot())
							// get all body children
//							$loop = $this->find('body > *')->elements;
							// TODO test it, test it hard...
//							$loop = $this->newInstance($this->root)->find('> *')->elements;
							$loop = $this->root->childNodes;
						else
							$loop = $this->elements;
						// import nodes if needed
						$insertFrom = $this->getDocumentID() == $target->getDocumentID()
							? $loop
							: $target->documentWrapper->import($loop);
					} else {
						$insertTo = $this->elements;
						if ( $target->documentFragment && $target->stackIsRoot() )
							// get all body children
//							$loop = $target->find('body > *')->elements;
							$loop = $target->root->childNodes;
						else
							$loop = $target->elements;
						// import nodes if needed
						$insertFrom = $this->getDocumentID() == $target->getDocumentID()
							? $loop
							: $this->documentWrapper->import($loop);
					}
				// DOMNODE
				} elseif ($target instanceof DOMNODE) {
					// import node if needed
//					if ( $target->ownerDocument != $this->DOM )
//						$target = $this->DOM->importNode($target, true);
					if ( $to ) {
						$insertTo = array($target);
						if ($this->documentFragment && $this->stackIsRoot())
							// get all body children
							$loop = $this->root->childNodes;
//							$loop = $this->find('body > *')->elements;
						else
							$loop = $this->elements;
						foreach($loop as $fromNode)
							// import nodes if needed
							$insertFrom[] = ! $fromNode->ownerDocument->isSameNode($target->ownerDocument)
								? $target->ownerDocument->importNode($fromNode, true)
								: $fromNode;
					} else {
						// import node if needed
						if (! $target->ownerDocument->isSameNode($this->document))
							$target = $this->document->importNode($target, true);
						$insertTo = $this->elements;
						$insertFrom[] = $target;
					}
				}
				break;
		}
		phpQuery::debug("From ".count($insertFrom)."; To ".count($insertTo)." nodes");
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

	/**
	 * Return joined text content.
	 * @return string
	 */
	public function text($text = null, $callback1 = null, $callback2 = null, $callback3 = null) {
		if ($text)
			return $this->html(htmlspecialchars($text));
		$args = func_get_args();
		$args = array_slice($args, 1);
		$return = '';
		foreach($this->elements as $node ) {
			$text = $node->textContent;
			if (count($this->elements) > 1 && $node->textContent)
				$text .= "\n";
			foreach($args as $callback) {
				$text = phpQuery::callbackRun($callback, array($text));
			}
			$return .= $text;
		}
		return $return;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryObject|queryTemplatesFetch|queryTemplatesParse|queryTemplatesPickup
	 */
	public function plugin($class, $file = null) {
		phpQuery::plugin($class, $file);
		return $this;
	}
	/**
	 * Deprecated, use $pq->plugin() instead.
	 *
	 * @deprecated
	 * @param $class
	 * @param $file
	 * @return unknown_type
	 */
	public static function extend($class, $file = null) {
		return $this->plugin($class, $file);
	}
	public function __call($method, $args) {
		$aliasMethods = array('clone', 'empty');
		if (method_exists($this, $method))
			return call_user_func_array(array($this, $method), $args);
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
	protected function stack($nodeTypes = null) {
		if (!isset($nodeTypes))
			return $this->elements;
		if (!is_array($nodeTypes))
			$nodeTypes = array($nodeTypes);
		$return = array();
		foreach($this->elements as $node)
			if (in_array($node->nodeType, $nodeTypes))
				$return[] = $node;
		return $return;
	}
	public function attr($attr = null, $value = null) {
		if (! is_null( $value )) {
//			die(var_dump(mb_detect_encoding($value)));
			// TODO tempolary solution
			// http://code.google.com/p/phpquery/issues/detail?id=17
//			if (function_exists('mb_detect_encoding')
//				 && mb_detect_encoding($value) == 'ASCII'
//				 && $this->charset = 'utf-8')
//				$value	= mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
		}
		foreach($this->stack(1) as $node) {
			if (! is_null($value)) {
				$loop = $attr == '*'
					? $this->getNodeAttrs($node)
					: array($attr);
				foreach($loop as $a) {
					$event = null;
					if ($value) {
						// identify
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
						phpQueryEvents::trigger($this->getDocumentID(),
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
			$value = '<'.'?php '.$value.' ?'.'>';
			// TODO tempolary solution
			// http://code.google.com/p/phpquery/issues/detail?id=17
//			if (function_exists('mb_detect_encoding') && mb_detect_encoding($value) == 'ASCII')
//				$value	= mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
		}
		foreach($this->stack(1) as $node) {
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
					? $classes.' <'.'?php '.$className.' ?'.'>'
					: '<'.'?php '.$className.' ?'.'>';
				$node->setAttribute('class', $newValue);
//				$attr->value = $newValue;
//				$node->removeAttribute('class');
//				$node->appendChild($attr);
				/*$attr = $node->setAttribute(
					'class',
					$classes = $node->getAttribute('class')
						? $classes.' <'.'?php<!-- '.$className.'-->?'.'>'
						: '<'.'?php<!-- '.$className.'-->?'.'>'
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
		phpQuery::selectDocument($this->getDocumentID());
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
//		$this->find($offset)->replaceWith($value);
		$this->find($offset)->html($value);
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
	protected function getNodeXpath($oneNode = null, $namespace = null) {
		$return = array();
		$loop = $oneNode
			? array($oneNode)
			: $this->elements;
		if ($namespace)
			$namespace .= ':';
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
				$xpath[] = "{$namespace}{$node->tagName}[{$i}]";
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