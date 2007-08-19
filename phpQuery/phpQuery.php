<?php
/**
 * jQuery port to PHP.
 * phpQuery is chainable DOM selector & manipulator.
 * 
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @link http://meta20.net/phpQuery
 * @link http://code.google.com/p/phpquery/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @version 0.7.2 beta
 */

class phpQueryClass implements Iterator {
	public static $debug = false;
	protected static $documents = array();
	public static $lastDocID = null;
	public static $defaultDoctype = 'html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"';
	public $docID = null;
	protected $DOM = null;
	protected $XPath = null;
	protected $stack = array();
	protected $history = array();
	protected $root = array();
	protected $interator_stack = array();
	/**
	 * Iterator helpers
	 */
	protected $valid = false;
	protected $current = null;
	/**
	 * Other helpers
	 */
	protected $regexpChars = array('^','*','$');
	protected $tmpNodes = array();

	/**
	 * Multi-purpose function.
	 * Use phpQuery() or _() as shortcut.
	 * 
	 * 1. Create new DOM:
	 * _('file.htm')
	 * _('<div/>', true)		// accepts text nodes at beginning of input string
	 * _('http://wikipedia.org', false)
	 * 2. Import HTML into existing DOM:
	 * _('<div/>')				// DOESNT accept text nodes at beginning of input string !
	 * _('<div/>', docID)
	 * 3. Run query:
	 * _('div.myClass')
	 * _('div.myClass', 'myFile.htm')
	 * _('div.myClass', _('div.anotherClass') )
	 * 
	 * @return	phpQueryClass|false			phpQueryClass object or false in case of error.
	 */
	public static function phpQuery() {
		$input = func_get_args();
		/**
		 * Create new DOM:
		 * _('file.htm')
		 * _('<div/>', true)
		 */
		if ( ($isHTMLfile = self::isHTMLfile($input[0])) || ( isset($input[1]) && gettype($input[1]) === 'boolean'/* && self::isHTML($input[0])*/ )) {
			// set document ID
			$ID = $input[1] !== true
				? $input[0]
				: md5(microtime());
			// check if already loaded
			if ( $isHTMLfile && isset( self::$documents[ $ID ] ) )
				return new phpQueryClass($ID);
			// create document
			self::$documents[ $ID ]['document'] = new DOMDocument();
			$DOM =& self::$documents[ $ID ];
			// load
			$isLoaded = $input[1] !== true
				? @$DOM['document']->loadHTMLFile($ID)
				: @$DOM['document']->loadHTML($input[0]);
			if (! $isLoaded ) {
				throw new Exception("Can't load '{$ID}'");
				return false;
			}
			$DOM['xpath'] = new DOMXPath(
				$DOM['document']
			);
			// remember last document
			self::$lastDocID = $ID;
			// we ready to create object
			return new phpQueryClass($ID);
		} else if ( is_object($input[0]) && get_class($input[0]) == 'DOMElement' ) {
			throw new Exception('DOM nodes not supported');
		/**
		 * Import HTML:
		 * _('<div/>')
		 */
		} else if ( self::isHTML($input[0]) ) {
			$docID = isset($input[1]) && $input[1]
				? $input[1]
				: self::$lastDocID;
			$phpQuery = new phpQueryClass($docID);
			$phpQuery->importHTML($input[0]);
			return $phpQuery;
		/**
		 * Run query:
		 * _('div.myClass')
		 * _('div.myClass', 'myFile.htm')
		 * _('div.myClass', _('div.anotherClass') )
		 */
		} else {
			$last = count($input)-1;
			$ID = isset( $input[$last] ) && self::isHTMLfile( $input[$last] )
				? $input[$last]
				: self::$lastDocID;
			$phpQuery = new phpQueryClass($ID);
			return $phpQuery->find(
				$input[0],
				isset( $input[1] )
				&& is_object( $input[1] )
				&& is_a( $input[1], 'phpQueryClass')
					? $input[1]
					: null
			);
		}
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function getDocID() {
		return $this->docID;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function unload() {
		unset( self::$documents[ $this->docID ] );
	}
	public static function unloadDocuments( $path = null ) {
		if ( $path )
			unset( self::$documents[ $path ] );
		else
			unset( self::$documents );
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function __construct($docPath) {
		if (! isset(self::$documents[ $docPath ] ) ) {
			throw new Exception("Doc path '{$docPath}' isn't loaded.");
			return;
		}
		$this->docID = $docPath;
		$this->DOM = self::$documents[ $docPath ]['document'];
		$this->XPath = self::$documents[ $docPath ]['xpath'];
		$this->root = $this->DOM->documentElement;
		$this->findRoot();
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
	 *
	 * @return phpQueryClass
	 */
	public function findRoot() {
		$this->stack = array( $this->DOM->documentElement );
		return $this;
	}
	protected function isRegexp($pattern) {
		return in_array(
			$pattern[ strlen($pattern)-1 ],
			$this->regexpChars
		);
	}
	protected static function isHTMLfile( $filename ) {
		return is_string($filename) && (
			substr( $filename, -5 ) == '.html'
				||
			substr( $filename, -4 ) == '.htm'
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
		$specialChars = array('>','+','~',' ');
		$specialCharsMapping = array('/' => '>');
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
				while( isset($query[$i]) && $this->isChar($query[$i])) {
					$tmp .= $query[$i];
					$i++;
				}
				$return[] = '#'.$tmp;
			// SPECIAL CHARS
			// todo znaki specjalne sie wykluczaja miedzy tagami (trimowac)
			// np 'tag + tag2' da w wyniku [tag, ,+, ,tag2] a to zle ;]
			} else if (in_array($c, $specialChars)) {
				$return[] = $c;
				$i++;
			// MAPPED SPECIAL MULTICHARS
			} else if ( $c.$query[$i+1] == '//' ) {
				$return[] = ' ';
				$i = $i+2;
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
			// ATTRS & NESTED XPATH
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
	protected function newInstance() {
		$new = new phpQueryClass($this->docID);
		$new->history = $this->history;
		$new->stack = $this->stack;
		$this->stack = array_pop($this->history);
		return $new;
	}
	
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
			// strip leading dot
			$class = substr($class, 1);
			$nodeClasses = explode(' ', $node->getAttribute('class') );
			if ( in_array($class, $nodeClasses) )
				return true;
		}
	}
	protected function runQuery( $XQuery, $selector = null, $compare = null ) {
		if ( $compare && ! method_exists($this, $compare) )
			return false;
		$stack = array();
		if (! $this->stack )
			$this->debug('Stack empty, skipping...');
		foreach( $this->stack as $k => $stackNode ) {
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
		$this->stack = $stack;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function find( $selectors, $context = null ) {
		// backup last stack /for end()/
		$this->history[] = $this->stack;
		// allow to define context
		if ( $context && is_a($context, get_class($this)) )
			$this->stack = $context->stack;
		$spaceBefore = false;
		$queries = $this->parseSelector( $selectors );
		$this->debug(array('FIND',$selectors,$queries));
		$XQuery = '';
		// remember stack state because of multi-queries
		$oldStack = $this->stack;
		// here we will be keeping found elements
		$stack = array();
		foreach( $queries as $selector ) {
			$this->stack = $oldStack;
			foreach( $selector as $s ) {
				// TAG
				if ( preg_match('@^\w+$@', $s) || $s == '*' ) {
					$XQuery .= $s;
				} else if ( $s[0] == '#' ) {
					// id
					if ( $spaceBefore )
						$XQuery .= '*';
					$XQuery .= "[@id='".substr($s, 1)."']";
				// ATTRIBUTES
				} else if ( isset($s[1]) && $s[0].$s[1] == '[@' ) {
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
							$XQuery .= "[{$attr}]";
						} else {
							$XQuery .= "[{$attr}='{$value}']";
						}
					// attr without specified value
					} else {
						$XQuery .= "[{$attr}]";
					}
					if ( $execute ) {
						$this->runQuery($XQuery, $s, 'is');
						$XQuery = '';
						if (! $this->length() )
							break;
					}
				// NESTED XPATH
				} else if ( $s[0] == '[' ) {
					if ( $XQuery && $XQuery != '//' ) {
						$this->runQuery($XQuery);
						$XQuery = '';
						if (! $this->length() )
							break;
					}
					// strip side brackets
					$x = substr($s, 1, -1);
					$this->stack = $this->find($x)->stack;
				// CLASSES
				} else if ( $s[0] == '.' ) {
					if ( $spaceBefore )
						$XQuery .= '*';
					$XQuery .= '[@class]';
					$this->runQuery($XQuery, $s, 'matchClasses');
					$XQuery = '';
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
				} else if ( $s == '>' ) {
					// direct descendant
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
			foreach( $this->stack as $node )
				if (! $this->stackContains($node, $stack) )
					$stack[] = $node;
		}
		$this->stack = $stack;
		return $this->newInstance();
	}
	
	/**
	 * @todo create API for classes with pseudoselectors
	 */
	protected function filterPseudoClasses( $class ) {
		// TODO clean args parsing ?
		$class = trim($class, ':');
		$haveArgs = strpos($class, '(');
		if ( $haveArgs !== false ) {
			$args = substr($class, $haveArgs+1, -1);
			$class = substr($class, 0, $haveArgs);
		}
		switch( $class ) {
			case 'even':
			case 'odd':
				$stack = array();
				foreach( $this->stack as $i => $node ) {
					if ( $class == 'even' && $i % 2 == 0 )
						$stack[] = $node;
					else if ( $class == 'odd' && $i % 2 )
						$stack[] = $node;
				}
				$this->stack = $stack;
				break;
			case 'eq':
				$k = intval($args);
				$this->stack = isset( $this->stack[$k] )
					? array( $this->stack[$k] )
					: array();
				break;
			case 'gt':
				$this->stack = array_slice($this->stack, $args+1);
				break;
			case 'lt':
				$this->stack = array_slice($this->stack, 0, $args+1);
				break;
			case 'first':
				if ( isset( $this->stack[0] ) )
					$this->stack = array( $this->stack[0] );
				break;
			case 'last':
				if ( $this->stack )
					$this->stack = array( $this->stack[ count($this->stack)-1 ] );
				break;
			case 'parent':
				$stack = array();
				foreach( $this->stack as $node ) {
					if ( $node->childNodes->length )
						$stack[] = $node;
				}
				$this->stack = $stack;
				break;
			case 'contains':
				$this->contains( trim($args, "\"'"), false );
				break;
			case 'not':
				$query = trim($args, "\"'");
				$stack = $this->stack;
				$newStack = array();
				foreach( $stack as $node ) {
					$this->stack = array($node);
					if (! $this->is($query) )
						$newStack[] = $node;
				}
				$this->stack = $newStack;
				break;
		}
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function is( $selector, $_node = null ) {
		$this->debug(array("Is:", $selector));
		if (! $selector)
			return false;
		$oldStack = $this->stack;
		if ( $_node )
			$this->stack = array($_node);
		$this->filter($selector, true);
		$match = (bool)$this->length();
		$this->stack = $oldStack;
		return $match;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function filter( $selectors, $_skipHistory = false ) {
		if (! $_skipHistory )
			$this->history[] = $this->stack;
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
			foreach( $this->stack as $node ) {
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
					} else if ( isset($s[1]) && $s[0].$s[1] == '[@' ) {
						// strip side brackets and @
						$attr = substr($s, 2, -1);
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
					// NESTED XPATH
					} else if ( $s[0] == '[' ) {
						// strip side brackets
						$x = substr($s, 1, -1);
						$oldStack = $this->stack;
						$this->stack = array($node);
						$pass = $this->find( $x )->size();
						$this->stack = $oldStack;
						if (! $pass )
							$break = true;
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
			$this->stack = $stack;
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
		return is_a($node, 'DOMDocument') || $node->tagName == 'html';
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function css() {
		// TODO
	}
	
	protected function importHTML($html) {
		$this->history[] = $this->stack;
		$this->stack = array();
		$DOM = new DOMDocument();
		@$DOM->loadHTML( $html );
		foreach($DOM->documentElement->firstChild->childNodes as $node)
			$this->stack[] = $this->DOM->importNode( $node, true );
	}
	/**
	 * Wraps elements with $before and $after.
	 * In case when there's no $after, $before should be a tag name (without <>).
	 *
	 * @return phpQueryClass
	 */
	public function wrap($before, $after = null) {
		if (! $after ) {
			$after = "</{$before}>";
			$before = "<{$before}>";
		}
		// safer...
		$each = clone $this;
		foreach( $each as $node ) {
			$wrap = self::phpQuery($before."<div id='__wrap'/>".$after)
				->insertAfter($node);
			$wrap->find('#__wrap')
					->replace($node);
		}
		return $this;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function contains( $text, $history = true ) {
		$this->history[] = $this->stack;
		$stack = array();
		foreach( $this->stack as $node ) {
			if ( strpos( $node->textContent, $text ) === false )
				continue;
			$stack[] = $node;
		}
		$this->stack = $stack;
		return $this;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function gt($num) {
		$this->history[] = $this->stack;
		$this->stack = array_slice( $this->stack, $num+1 );
		return $this->newInstance();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function lt($num) {
		$this->history[] = $this->stack;
		$this->stack = array_slice( $this->stack, 0, $num+1 );
		return $this->newInstance();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function eq($num) {
		$oldStack = $this->stack;
		$this->history[] = $this->stack;
		$this->stack = array();
		if ( isset($oldStack[$num]) )
			$this->stack[] = $oldStack[$num];
		return $this->newInstance();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function size() {
		return $this->length();
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function length() {
		return count( $this->stack );
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function end() {
		$this->stack = array_pop( $this->history );
		return $this;
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function select($selector) {
		return $this->is($selector)
			? $this->filter($selector)
			: $this->find($selector);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function each($callabck) {
		$this->history[] = $this->stack;
		foreach( $this->history[ count( $this->history )-1 ] as $node ) {
			$this->stack = array($node);
			if ( is_array( $callabck ) ) {
				if ( is_object( $callabck[0] ) )
					$callabck[0]->{$callabck[1]}( $this->newInstance() );
				else
					eval("{$callabck[0]}::{$callabck[1]}( \$this->newInstance() );");
			} else {
				$callabck( $this->newInstance() );
			}
		}
		return $this;
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function _clone() {
		$newStack = array();
		//pr(array('copy... ', $this->whois()));
		//$this->dumpHistory('copy');
		$this->history[] = $this->stack;
		foreach( $this->stack as $node ) {
			$newStack[] = $node->cloneNode(true);
		}
		$this->stack = $newStack;
		return $this;
	}

	/**
	 * Replaces current element with $content and changes stack to new element(s) (except text nodes).
	 * Can be reverted with end().
	 *
	 * @param string|phpQueryClass $with
	 * @return phpQueryClass
	 */
	public function replace($content) {
		$stack = array();
		// safer...
		$each = clone $this;
		foreach( $each as $node ) {
			$prev = $node->before($content)->_prev();
			if ( $this->isHTML($content) )
				$stack[] = $prev->stack;
		}
		$stack = $this->before($content);
		$this->remove();
		$this->history[] = $this->stack;
		$this->stack = $stack;
		return $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function replacePHP($code) {
		return $this->replace("<php>{$code}</php>");
	}

	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function remove() {
		foreach( $this->stack as $node ) {
			if (! $node->parentNode )
				continue;
			$this->debug("Removing '{$node->tagName}'");
			$node->parentNode->removeChild( $node );
		}
		return $this;
	}

	protected function isHTML($html) {
		return substr(trim($html), 0, 1) == '<';
//			|| is_object($html) && is_a($html, 'DOMElement');
	}

	public function html($html = null) {
		if (! is_null($html) ) {
			$this->debug("Inserting data with 'html'");
			if ( $this->isHTML( $html ) ) {
				$toInserts = array();
				$DOM = new DOMDocument();
				@$DOM->loadHTML( $html );
				foreach($DOM->documentElement->firstChild->childNodes as $node)
					$toInserts[] = $this->DOM->importNode( $node, true );
			} else {
				$toInserts = array($this->DOM->createTextNode( $html ));
			}
			$this->_empty();
			// i dont like brackets ! python rules ! ;)
			foreach( $toInserts as $toInsert )
				foreach( $this->stack as $alreadyAdded => $node )
					$node->appendChild( $alreadyAdded
						? $toInsert->cloneNode()
						: $toInsert
					);
			return $this;
		} else {
			if ( $this->length() == 1 && $this->isRoot( $this->stack[0] ) )
				return $this->save();
			$DOM = new DOMDocument();
			foreach( $this->stack as $node ) {
				foreach( $node->childNodes as $child ) {
					$DOM->appendChild(
						$DOM->importNode( $child, true )
					);
				}
			}
			return $this->save($DOM);
		}
	}
	/**
	 * Enter description here...
	 *
	 * @todo change to htmlWithElement
	 */
	public function htmlWithElement() {
		if ( $this->length() == 1 && $this->isRoot( $this->stack[0] ) )
			return $this->save();
		$DOM = new DOMDocument();
		foreach( $this->stack as $node ) {
			$DOM->appendChild(
				$DOM->importNode( $node, true )
			);
		}
		return $this->save($DOM);
	}
	protected function save($DOM = null){
		if (! $DOM)
			$DOM = $this->DOM;
		$DOM->formatOutput = false;
		$DOM->preserveWhiteSpace = true;
		$doctype = isset($DOM->doctype) && is_object($DOM->doctype)
			? $DOM->doctype->publicId
			: self::$defaultDoctype;
		return stripos($doctype, 'xhtml') !== false
			? $this->saveXHTML( $DOM->saveXML() )
			: $DOM->saveHTML();
	}
	protected function saveXHTML($content){
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
		return $this->htmlWithElement();
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function php($code) {
		return $this->html("<php>".trim($code)."</php>");
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function phpPrint($var) {
		return $this->php("print {$var};");
	}
	/**
	 * Fills elements selected by $selector with $content using html(), and roll back selection.
	 * 
	 * @param string	Selector
	 * @param string	Content
	 * 
	 * @return phpQueryClass
	 */
	public function fill($selector, $content) {
		$this->find($selector)
			->html($content);
		return $this;
	}
	/**
	 * Fills elements selected by $selector with $code using php(), and roll back selection.
	 * 
	 * @param string	Selector
	 * @param string	Valid PHP Code
	 * 
	 * @return phpQueryClass
	 */
	public function fillPhp($selector, $code) {
		$this->find($selector)
			->php($code);
		return $this;
	}
	protected function dumpHistory($when) {
		foreach( $this->history as $nodes ) {
			$history[] = array();
			foreach( $nodes as $node ) {
				$history[ count($history)-1 ][] = $this->whois( $node );
			}
		}
		//pr(array("{$when}/history", $history));
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function children( $selector = null ) {
		$tack = array();
		foreach( $this->stack as $node ) {
			foreach( $node->getElementsByTagName('*') as $newNode ) {
				if ( $selector && ! $this->is($selector, $newNode) )
					continue;
				$stack[] = $newNode;
			}
		}
		$this->history[] = $this->stack;
		$this->stack = $stack;
		return $this->newInstance();
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function unwrapContent() {
		foreach( $this->stack as $node ) {
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
	 * @return phpQueryClass
	 */
	public function ancestors( $selector = null ) {
		return $this->children( $selector );
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function append( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function appendPHP( $content ) {
		return $this->insert("<php>{$content}</php>", 'append');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function appendTo( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function prepend( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function prependPHP( $content ) {
		return $this->insert("<php>{$content}</php>", 'prepend');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function prependTo( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function before( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function beforePHP( $content ) {
		return $this->insert("<php>{$content}</php>", 'before');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function insertBefore( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function after( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function afterPHP( $content ) {
		return $this->insert("<php>{$content}</php>", 'after');
	}
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function insertAfter( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	protected function insert( $target, $type ) {
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
					$insertFrom = $this->stack;
					// insert into created element
					if ( $this->isHTML( $target ) ) {
						$DOM = new DOMDocument();
						@$DOM->loadHTML($target);
						$i = count($this->tmpNodes);
						$this->tmpNodes[] = array();
						foreach($DOM->documentElement->firstChild->childNodes as $node) {
							$this->tmpNodes[$i][] = $this->DOM->importNode( $node, true );
						}
						// XXX needed ?!
					//	$this->tmpNodes[$i] = array_reverse($this->tmpNodes[$i]);
						$insertTo =& $this->tmpNodes[$i];
					// insert into selected element
					} else {
						$thisStack = $this->stack;
						$this->findRoot();
						$insertTo = $this->find($target)->stack;
						$this->stack = $thisStack;
					}
				} else {
					$insertTo = $this->stack;
					// insert created element
					if ( $this->isHTML( $target ) ) {
						$DOM = new DOMDocument();
						@$DOM->loadHTML($target);
						$insertFrom = array();
						foreach($DOM->documentElement->firstChild->childNodes as $node) {
							$insertFrom[] = $this->DOM->importNode($node, true);
						}
						// XXX needed ?!
					//	$insertFrom = array_reverse($insertFrom);
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
				if ( is_a($target, get_class($this)) ){
					if ( $to ) {
						$insertTo = $target->stack;
						if ( $this->size() == 1 && $this->isRoot($this->stack[0]) )
							$loop = $this->find('body>*')->stack;
						else
							$loop = $this->stack;
						foreach( $loop as $node )
							$insertFrom[] = $target->DOM->importNode($node, true);
					} else {
						$insertTo = $this->stack;
						if ( $target->size() == 1 && $this->isRoot($target->stack[0]) )
							$loop = $target->find('body>*')->stack;
						else
							$loop = $target->stack;
						foreach( $loop as $node ) {
							$insertFrom[] = $this->DOM->importNode($node, true);
						}
					}
				}
				break;
		}
		foreach( $insertTo as $toNode ) {
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
				switch( $type ) {
					case 'appendTo':
					case 'append':
//						$toNode->insertBefore(
//							$fromNode,
//							$toNode->lastChild->nextSibling
//						);
						$toNode->appendChild($fromNode);
						break;
					case 'prependTo':
					case 'prepend':
						$toNode->insertBefore(
							$fromNode,
							$firstChild
						);
						break;
					case 'insertBefore':
					case 'before':
						$toNode->parentNode->insertBefore(
							$fromNode,
							$toNode
						);
						break;
					case 'insertAfter':
					case 'after':					
						$toNode->parentNode->insertBefore(
							$fromNode,
							$nextSibling
						);
						break;
				}
			}
		}
		return $this;
	}

	/**
	 * Returns JSON representation of stacked HTML elements and it's children.
	 * Compatible with @link http://programming.arantius.com/dollar-e 
	 *
	 * @return string
	 * 
	 * @todo
	 */
	public function toJSON() {
		$json = '';
		foreach( $this->stack as $node ) {
			
		}
		return $json;
	}
	protected function _toJSON($node) {
		$json = '';
		switch( $node->type ) {
			case 3:
				break;
		}
		return $json;
	}
	
	/**
	 * Python slices.
	 * 
	 * @return phpQueryClass
	 * @todo
	 */
	public function slice() {
	}	
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function reverse() {
		$this->history[] = $this->stack;
		$this->stack = array_reverse($this->stack);
	}

	public function text() {
		$return = '';
		foreach( $this->stack as $node ) {
			$return .= $node->textContent;
		}
		return $return;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function _next( $selector = null ) {
		$this->sibling( $selector, 'previousSibling' );
		return $this->newInstance();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function _prev( $selector = null ) {
		$this->sibling( $selector, 'previousSibling' );
		return $this->newInstance();
	}
	
	/**
	 * @return phpQueryClass
	 * @todo
	 */
	public function prevSiblings( $selector = null ) {
	}
	
	/**
	 * @return phpQueryClass
	 * @todo
	 */
	public function nextSiblings( $selector = null ) {
	}
	
	/**
	 * Number of prev siblings
	 * @return int
	 * @todo
	 */
	public function index() {
		return $this->prevSiblings()->size();
	}
	
	protected function sibling( $selector, $direction ) {
		$stack = array();
		foreach( $this->stack as $node ) {
			$test = $node;
			while( isset($test->{$direction}) && $test->{$direction} ) {
				$test = $test->nextSibling;
				if ( $selector ) {
					if ( $this->is( $selector, $test ) ) {
						$stack[] = $test;
						continue;
					}
				} else {
					$stack[] = $test;
					continue;
				}
			}
		}
		$this->history[] = $this->stack;
		$this->stack = $stack;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function siblings( $selector = null ) {
		$stack = array();
		foreach( $this->stack as $node ) {
			if ( $selector ) {
				if ( $this->is( $selector ) && ! $this->stackContains($node, $stack) )
					$stack[] = $node;
			} else if (! $this->stackContains($node, $stack) )
				$stack[] = $node;
		}
		$this->history[] = $this->stack;
		$this->stack = $stack;
		return $this->newInstance();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function not( $selector = null ) {
		$stack = array();
		foreach( $this->stack as $node ) {
			if (! $this->is( $selector, $node ) )
				$stack[] = $node;
		}
		$this->history[] = $this->stack;
		$this->stack = $stack;
		return $this->newInstance();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function add( $selector = null ) {
		$stack = array();
		$this->history[] = $this->stack;
		$found = $this->find($selector);
		$this->merge($found->stack);
		return $this->newInstance();
	}
	
	protected function merge() {
		foreach( get_func_args() as $nodes )
			foreach( $nodes as $newNode )
				if (! $this->stackContains($newNode) )
					$this->stack[] = $newNode;
	}
	
	protected function stackContains($nodeToCheck, $stackToCheck = null) {
		$loop = ! is_null($stackToCheck)
			? $stackToCheck
			: $this->stack;
		foreach( $loop as $node ) {
			if ( $node->isSameNode( $nodeToCheck ) )
				return true;
		}
		return false;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function parent( $selector = null ) {
		$stack = array();
		foreach( $this->stack as $node )
			if ( $node->parentNode && ! $this->stackContains($node->parentNode, $stack) )
				$stack[] = $node->parentNode;
		$this->history[] = $this->stack;
		$this->stack = $stack;
		if ( $selector )
			$this->filter($selector, true);
		return $this->newInstance();
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function parents( $selector = null ) {
		$stack = array();
		if (! $this->stack )
			$this->debug('parents() - stack empty');
		foreach( $this->stack as $node ) {
			$test = $node;
			while( $test->parentNode ) {
				$test = $test->parentNode;
				if ( is_a($test, 'DOMDocument') )
					break;
				if ( $selector ) {
					if ( $this->is( $selector, $test ) && ! $this->stackContains($test, $stack) ) {
						$stack[] = $test;
						continue;
					}
				} else if (! $this->stackContains($test, $stack) ) {
					$stack[] = $test;
					continue;
				}
			}
		}
		$this->history[] = $this->stack;
		$this->stack = $stack;
		return $this->newInstance();
	}
	
	/**
	 * Attribute method.
	 * Accepts * for all attributes (for setting and getting)
	 *
	 * @param unknown_type $attr
	 * @param unknown_type $value
	 * @return string|array
	 */
	public function attr( $attr = null, $value = null ) { 
		foreach( $this->stack as $node ) {
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
				$return = array();
				foreach( $node->attributes as $n => $v)
					$return[$n] = $v->value;
				return $return;
			} else
				return $node->getAttribute($attr);
		}
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
	 * @return phpQueryClass
	 */
	public function attrPHP( $attr, $value ) { 
		foreach( $this->stack as $node ) {
			$node->setAttribute($attr, "<?php {$value} ?>");
		}
		return $this;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function removeAttr( $attr ) {
		foreach( $this->stack as $node ) {
			$loop = $attr == '*'
				? $this->getNodeAttrs($node)
				: array($attr);
			foreach( $loop as $a )
				$node->removeAttribute($a);
		}
		return $this;
	}
	
	/**
	 * Enter description here...
	 * 
	 * @todo val()
	 */
	public function val() {
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function addClass( $className ) {
		foreach( $this->stack as $node ) {
			if (! $this->is( $node, '.'.$className))
				$node->setAttribute(
					'class',
					$node->getAttribute('class').' '.$className
				);
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
		foreach( $this->stack as $node ) {
			if ( $this->is( $node, '.'.$className))
				return true;
		}
		return false;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return phpQueryClass
	 */
	public function removeClass( $className ) {
		foreach( $this->stack as $node ) { 
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
	 * @return phpQueryClass
	 */
	public function toggleClass( $className ) {
		foreach( $this->stack as $node ) {
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
	 * _("p")._empty()
	 *  
	 * HTML:
	 * <p>Hello, <span>Person</span> <a href="#">and person</a></p>
	 *  
	 * Result:
	 * [ <p></p> ]
	 * 
	 * @return phpQueryClass
	 */
	public function _empty() {
		foreach( $this->stack as $node ) {
			// many thx to 'dave at dgx dot cz' :)
			$node->nodeValue = '';
		}
		return $this;
	}
	

	// ITERATOR INTERFACE
	public function rewind(){
		$this->debug('interating foreach');
		$this->history[] = $this->stack;
		$this->interator_stack = $this->stack;
		$this->valid = isset( $this->stack[0] )
			? 1
			: 0;
		$this->stack = $this->valid
			? array($this->stack[0])
			: array();
		$this->current = 0;
	}

	public function current(){
		return $this;
	}

	public function key(){
		return $this->current;
	}

	public function next(){
		$this->current++;
		$this->valid = isset( $this->interator_stack[ $this->current ] )
			? true
			: false;
		if ( $this->valid )
			$this->stack = array(
				$this->interator_stack[ $this->current ]
			);
	}
	public function valid(){
		return $this->valid;
	}

	protected function getNodeXpath( $oneNode = null ) {
		$return = array();
		$loop = $oneNode
			? array($oneNode)
			: $this->stack;
		foreach( $loop as $node ) {
			if ( is_a($node, 'DOMDocument') ) {
				$return[] = '';
				continue;
			}				
			$xpath = array();
			while(! is_a($node, 'DOMDocument') ) {
				$i = 1;
				$sibling = $node;
				while( $sibling->previousSibling ) {
					$sibling = $sibling->previousSibling;
					$isElement = get_class($sibling) == 'DOMElement';
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
			: $this->stack;
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

	public function dumpStack() { 
		$i = 1;
		foreach( $this->stack as $node ) {
			$this->debug("Node {$i} ".$this->whois($node));
			$i++;
		}
	}
	
	public function dumpSource( $node = null ) {
		$return = array();
		$loop = $node
			? array( $node )
			: $this->stack;
		foreach( $loop as $node ) {
			$DOM = new DOMDocument();
			$DOM->appendChild(
				$DOM->importNode( $node, true )
			);
			$return[] = $DOM->saveHTML();
		}
		return $return;
	}
}

/**
 * Shortcut to <code>new phpQueryClass($arg1, $arg2, ...)</code>
 *
 * @return phpQueryClass
 */
function phpQuery() {
	$args = func_get_args();
	return call_user_func_array(
		array('phpQueryClass', 'phpQuery'),
		$args
	);
}

if (! function_exists('_')) {
	/**
	 * Handy phpQuery shortcut.
	 * Optional, because conflicts with gettext extension.
	 * @link http://php.net/_
	 *
	 * @return phpQueryClass
	 */
	function _() {
		$args = func_get_args();
		return call_user_func_array('phpQuery', $args);
	}
}
?>
