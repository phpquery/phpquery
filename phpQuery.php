<?php
/**
 * jQuery port to PHP.
 * phpQuery is chainable DOM selector & manipulator.
 * 
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @link http://wiadomosc.info/plainTemplate
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @version 0.6 beta
 * 
 * @todo rewrite selector explode (support attr values with spaces, dots and xpath queries)
 * @todo comma separated queries
 * @todo missing jquery functions (css, wrap, val)
 * @todo docs (copied from jquery)
 * @todo more test cases
 * @todo cache (mainly class and regex attrs)
 * @todo charset
 */

class phpQuery implements Iterator {
	public static $debug = false;
	private static $documents = array();
	private static $lastDocument = null;
	private $DOM = null;
	private $XPath = null;
	private $stack = array();
	private $history = array();
	private $root = array();
	private $_stack = array();
	/**
	 * Interator helpers
	 */
	private $valid = false;
	private $current = null;
	/**
	 * Other helpers
	 */
	private $regexpChars = array('^','*','$');

	public static function load( $path ) {
		self::$documents[ $path ]['document'] = new DOMDocument();
		$DOM =& self::$documents[ $path ];
		if (! $DOM['document']->loadHTMLFile( $path ) ) { 
			unset( self::$documents[ $path ] );
			return false;
		}
		$DOM['document']->preserveWhiteSpace = true;
		$DOM['document']->formatOutput = true;
		$DOM['nodes'] = array();
		$DOM['xpath'] = new DOMXPath(
			$DOM['document']
		);
		self::$lastDocument = $path;
		return true;
	}
	public static function unload( $path = null ) {
		if ( $path )
			unset( self::$documents[ $path ] );
		else
			unset( self::$documents );
	}
	public function __construct( $path = null ) {
		if ( $path )
			self::load($path);
		else
			$path = self::$lastDocument;
		$this->DOM = self::$documents[ $path ]['document'];
		$this->XPath = self::$documents[ $path ]['xpath'];
		$this->nodes = self::$documents[ $path ]['nodes'];
		$this->root = $this->DOM->documentElement;
		$this->stackToRoot();
	}
	private function debug($in) {
		if (! self::$debug )
			return;
		print('<pre>');
		print_r($in);
	//	if ( is_array($in))
	//		print_r(array_slice(debug_backtrace(), 3));
		print('</pre>');
	}
	private function stackToRoot() {
		$this->stack = array( $this->DOM->documentElement );
	}
	private function isRegexp($pattern) {
		return in_array(
			$pattern[ strlen($pattern)-1 ],
			$this->regexpChars
		);
	}
	public static function isHTMLfile( $filename ) {
		return is_string($filename) && (
			substr( $filename, -5 ) == '.html'
				||
			substr( $filename, -4 ) == '.htm'
		);
	}
	private function parseSelectors( $selectors ) {
		$return = array();
		foreach( split(',', $selectors) as $parse ) {
			// clean spaces
			$parse = trim(
				preg_replace('@\s+@', ' ',
					str_replace('>', ' > ', $parse)
				)
			);
			$elements = array();
			// TODO: realy parsing of selector
			foreach( split(' ', $parse) as $s ) {
				if ( $elements && $elements[ count($elements)-1 ] != '>' && $s != '>' )
					$elements[] = ' ';
				$elements = array_merge(
					$elements,
					$this->parseSimpleSelector( $s )
				);
			}
			if ( isset($elements[0]) && $elements[0] != '>' )
				array_unshift($elements, ' ');
			$return[] = $elements;
			$this->debug(array('SELECTOR',$parse,$elements));
		}
		return $return;
	}
	// tag.class1.class2[@attr]:checkbox
	private function parseSimpleSelector( $s ) {
		$selector = array();
		$match = preg_split(
			'@(\\.|#|\\[|:)@',
			$s, null,
			PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY
		);
		// tag present
		if ( count( $match ) % 2 == 1 )
			array_unshift($match, '');
		for( $i = 0; $i < count( $match )-1; $i = $i+2 ) {
			// join classes, args and pseudo-selectors
			$append = (
				$selector
				&& (
					$match[ $i ][0] == '.'
					||
					$match[ $i ][0] == '['
					||
					$match[ $i ][0] == ':'
				) &&
				$selector[ count($selector)-1 ][0] == $match[ $i ][0]
			);
			if ( $append )
				$selector[ count($selector)-1 ] .= $match[ $i ].$match[ $i+1 ];
			else
				$selector[] = $match[ $i ].$match[ $i+1 ];
		}
		return $selector;
	}
	private function matchClasses( $class, $node ) {
		$class = strpos($class, '.', 1)
			// multi-class
			? explode('.', substr($class, 1))
			// single-class
			: substr($class, 1);
		$classCount = is_array( $class )
			? count( $class )
			: null;
		if ( is_array( $class )) {
			$nodeClasses = explode(' ', $node->getAttribute('class') );
			$nodeClassesCount = count( $nodeClasses );
			if ( $classCount > $nodeClassesCount )
				return false;
			$diff = count(
				array_diff(
					$class,
					$nodeClasses
				)
			);
			if ( $diff == count($nodeClasses) - count($class) )
				return true;
		} else if ( in_array($class, explode(' ', $node->getAttribute('class') )) )
			return true;
	}
	public function find( $selectors, $stack = null ) {
		// backup last stack /for end()/
		$this->history[] = $this->stack;
		if ( $stack && get_class($stack) == get_class($this) )
			$this->stack = $stack;
		$spaceBefore = false;
		$XQuery = '';
		foreach( $this->parseSelectors( $selectors ) as $selector ) {
			foreach( $selector as $s ) {
				if ( preg_match('@^\w+$@', $s) || $s == '*' ) {
					// tag
					$XQuery .= $s;
				} else if ( $s[0] == '#' ) {
					// id
					if ( $spaceBefore )
						$XQuery .= '*';
					$XQuery .= "[@id='".substr($s, 1)."']";
				} else if ( $s[0] == '[' ) {
					// attributes and nests
					if ( $spaceBefore )
						$XQuery .= '*';
					// strip side brackets
					$attrs = explode('][', trim($s, '[]'));
					$execute = false;
					foreach( $attrs as $attr ) {
						if ( $attr[0] == '@' ) {
							// attr with specifed value
							if ( strpos( $attr, '=' ) ) {
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
						// TODO nested xpath
						} else {
						}
					}
					if ( $execute ) {
						$this->runQuery($XQuery, $s, 'is');
						$XQuery = '';
						if (! $this->length() )
							return $this;
					}
				} else if ( $s[0] == '.' ) {
					// class(es)
					if ( $spaceBefore )
						$XQuery .= '*';
					$XQuery .= '[@class]';
					$this->runQuery($XQuery, $s, 'matchClasses');
					$XQuery = '';
					if (! $this->length() )
						return $this;
				} else if ( $s[0] == ':' ) {
					// pseudo classes
					// TODO optimization for :first :last
					$this->runQuery($XQuery);
					$XQuery = '';
					if (! $this->length() )
						return $this;
					$this->filterPseudoClasses( $s );
					if (! $this->length() )
						return $this;
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
			if ( $XQuery ) {
				$this->runQuery($XQuery);
				$XQuery = '';
				if (! $this->length() )
					return $this;
			}
		}
		// preserve chain
		return $this;
	}
	private function runQuery( $XQuery, $selector = null, $compare = null ) {
		if ( $compare && ! method_exists($this, $compare) )
			return false;
		$stack = array();
		if (! $this->stack )
			$this->debug('Stack empty, skipping...');
		foreach( $this->stack as $k => $stackNode ) {
			$remove = false;
			if (! $stackNode->parentNode && ! $this->isRoot($stackNode) ) {
				$this->root->appendChild($stackNode);
				$remove = true;
			}
			$xpath = $this->getNodeXpath($stackNode);
			$query = $xpath.$XQuery;
			$this->debug("XPATH: {$query}\n");
			// run query, get elements
			$nodes = $this->XPath->query($query);
//			// TEST: keep document nodes in one place
//			foreach( $nodes as $k => $node ) {
//				foreach( $this->nodes as $fetchedNode ) {
//					if ( $node->isSameNode( $fetchedNode ) )
//						$nodes[$k] = $fetchedNode;
//					else {
//						$this->nodes[] = $node;
//					}
//				}
//			}
			foreach( $nodes as $node ) {
				$matched = false;
				if ( $compare ) {
					self::$debug ? $this->debug("Found: ".$this->whois( $node )) : null;
					$this->debug("Comparing with {$compare}()");
					if ( call_user_method($compare, $this, $selector, $node) )
						$matched = true;
				} else {
					$matched = true;
				}
				if ( $matched ) {
					self::$debug ? $this->debug("Matched: ".$this->whois( $node )) : null;
					$stack[] = $node;
				}
			}
			if ( $remove )
				$stackNode = $this->root->removeChild( $this->root->lastChild );
		}
		$this->stack = $stack;
	}
	
	public function filterPseudoClasses( $classes ) {
		foreach( explode(':', substr($classes, 1)) as $class ) {
			// TODO clean args parsing
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
					$this->stack = isset( $this->stack[ intval($args) ] )
						? array( $this->stack[ $args ] )
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
							$stack = $node;
					}
					$this->stack = $stack;
					break;
				case 'contains':
					$this->contains( trim($args, "\"'"), false );
					break;
			}
		}
	}
	public function is( $selector, $_node = null ) {
		$oldStack = $this->stack;
		if ( $_node )
			$this->stack = array($_node);
		$this->filter($selector, true);
		$match = (bool)$this->length();
		$this->stack = $oldStack;
		return $match;
	}
	
	public function filter( $selector, $_skipHistory = false ) {
		if (! $_skipHistory )
			$this->history[] = $this->stack;
		$selector = $this->parseSimpleSelector( $selector );
		$stack = array();
		foreach( $this->stack as $k => $node ) { 
			foreach( $selector as $s ) {
				switch( $s[0] ) {
					case '#':
						if ( $node->getAttribute('id') != $val )
							$stack[] = $node;
						break;
					case '.':
						if ( $this->matchClasses( $s, $node ) )
							$stack[] = $node;
						break;
					case '[':
						foreach( explode( '][', trim($s, '[]') ) as $attr ) {
							// attrs
							if ( $attr[0] == '@' ) {
								// cut-da-monkey ;)
								$attr = substr($attr, 1);
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
										if ( preg_match("@{$pattern}@", $node->getAttribute($attr)))
											$stack[] = $node;
									} else if ( $node->getAttribute($attr) == $val )
										$stack[] = $node;
								} else if ( $node->hasAttribute($attr) )
									$stack[] = $node;
							// nested xpath
							} else {
								// TODO
							}
						}
						break;
					case ':':
						// at the end of function
						break;
					default:
						// tag
						if ( isset($node->tagName) ) {
							if ( $node->tagName == $s )
								$stack[] = $node;
						} else if ( $s == 'html' && $this->isRoot($node) )
							$stack[] = $node;
				}
			}
		}
		$this->stack = $stack;
		// pseudoclasses
		if ( $selector[ count($selector)-1 ][0] == ':' )
			$this->filterPseudoClasses( $selector[ count($selector)-1 ] );
		return $this;
	}
	
	private function isRoot( $node ) {
		return get_class($node) == 'DOMDocument';
	}

	public function css() {
		// TODO
	}
	
	private function importHTML($html) {
		$this->history[] = $this->stack;
		$this->stack = array();
		$DOM = new DOMDocument();
		@$DOM->loadHTML( $html );
		foreach($DOM->documentElement->firstChild->childNodes as $node)
			$this->stack[] = $this->DOM->importNode( $node, true );
	}

	public function wrap($before, $after) {
		foreach( $this->stack as $node ) {
			_($before.$after)
				->insertAfter($node);
			
		}
	}
	
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
	
	public function gt($num) {
		$this->history[] = $this->stack;
		$this->stack = array_slice( $this->stack, $num+1 );
		return $this;
	}

	public function lt($num) {
		$this->history[] = $this->stack;
		$this->stack = array_slice( $this->stack, 0, $num+1 );
		return $this;
	}

	public function eq($num) {
		$oldStack = $this->stack;
		$this->history[] = $this->stack;
		$this->stack = array();
		if ( isset($oldStack[$num]) )
			$this->stack[] = $oldStack[$num];
		return $this;
	}

	public function size() {
		return $this->length();
	}

	public function length() {
		return count( $this->stack );
	}

	public function end() {
		$this->stack = array_pop( $this->history );
		return $this;
	}

	public function each($callabck) {
		$this->history[] = $this->stack;
		foreach( $this->history[ count( $this->history-1 ) ] as $node ) {
			$this->stack = array($node);
			if ( is_array( $func ) ) {
				${$callabck[0]}->{$callabck[1]}( $this );
			} else {
				$callabck[1]( $this );
			}
		}
		return $this->end();
	}

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

	public function remove() {
		foreach( $this->stack as $node ) {
			$this->debug("Removing '{$node->tagName}'");
			$node->parentNode->removeChild( $node );
		}
		return $this;
	}

	private function isHTML( $html ) {
		return substr(trim($html), 0, 1) == '<';
	}

	public function html($html = null) {
		if ( $html ) {
			if ( $this->isHTML( $html ) ) {
				$toInserts = array();
				$DOM = new DOMDocument();
				@$DOM->loadHTML( $html );
				foreach($DOM->documentElement->firstChild->childNodes as $node)
					$toInserts[] = $this->DOM->importNode( $node, true );
			//	$toInserts = array_reverse( $toInserts );
			} else {
				$toInserts = array($this->DOM->createTextNode( $html ));
			}
			$this->_empty();
			// i dont like brackets ! python rules ! ;)
			foreach( $toInserts as $toInsert )
				foreach( $this->stack as $k => $node )
					$node->appendChild( $k
						? $toInsert->cloneNode()
						: $toInsert
					);
			return $this;
		} else {
			if ( $this->length() == 1 && $this->isRoot( $this->stack[0] ) )
				return $this->DOM->saveHTML();
			$DOM = new DOMDocument();
			foreach( $this->stack as $node ) {
				$DOM->appendChild(
					$DOM->importNode( $node, true )
				);
			}
			$DOM->formatOutput = true;
			return $DOM->saveHTML();
		}
	}
	public function __toString() {
		return $this->html();
	}
	public function php($code) {
		return $this->html("<php>".trim($code)."</php>");
	}
	public function phpPrint($var) {
		return $this->php("print {$var};");
	}
	/**
	 * Meta PHP insert - finds element(s), inserts code and rolls back stack.
	 * 
	 * @param string	Selector
	 * @param string	Valid PHP Code
	 */
	public function phpMeta($selector, $code) {
		return $this->find($selector)
			->php($code)
			->end();
	}
	private function dumpHistory($when) {
		foreach( $this->history as $nodes ) {
			$history[] = array();
			foreach( $nodes as $node ) {
				$history[ count($history)-1 ][] = $this->whois( $node );
			}
		}
		//pr(array("{$when}/history", $history));
	}
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
		$this->stack = $tack;
		return $this;
	}
	public function ancestors( $selector ) {
		return $this->children( $selector );
	}
	
	public function append( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	public function appendPHP( $content ) {
		return $this->insert("<php>{$content}</php>", 'append');
	}
	public function appendTo( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	public function prepend( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	public function prependPHP( $content ) {
		return $this->insert("<php>{$content}</php>", 'prepend');
	}
	public function prependTo( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	public function before( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	public function beforePHP( $content ) {
		return $this->insert("<php>{$content}</php>", 'before');
	}
	public function insertBefore( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	
	public function after( $content ) {
		return $this->insert($content, __FUNCTION__);
	}
	public function afterPHP( $content ) {
		return $this->insert("<php>{$content}</php>", 'after');
	}
	public function insertAfter( $seletor ) {
		return $this->insert($seletor, __FUNCTION__);
	}
	private function insert( $target, $type ) {
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
					//$this->dumpHistory('appendTo');
					$oldStack = $this->stack;
					$historyCount = count( $this->history );
					$this->stack = array( $this->root );
					$this->find($target);
					$insertTo = $this->stack;
					$this->stack = $oldStack;
					$insertFrom = $this->stack;
					if ( count( $this->history ) > $historyCount )
						$this->history = array_slice( $this->history, 0, $historyCount );
					//$this->dumpHistory('appendTo-END');
				} else {
					$insertTo = $this->stack;
					if ( $this->isHTML( $target ) ) {
						$DOM = new DOMDocument();
						@$DOM->loadHTML($target);
						foreach($DOM->documentElement->firstChild->childNodes as $node) {
							$insertFrom[] = $this->DOM->importNode( $node, true );
						}
						$insertFrom = array_reverse($insertFrom);
					} else {
						$insertFrom = array(
							$this->DOM->createTextNode( $target )
						);
					}
				}
				break;
			case 'object':
				if ( get_class( $target ) == get_class( $this )) {
					if ( $to ) {
						$insertTo = $target->stack;
						foreach( $this->stack as $node )
							$insertFrom[] = $target->DOM->importNode($node);
					} else {
						$insertTo = $this->stack;
						foreach( $target->stack as $node )
							$insertFrom[] = $this->DOM->importNode($node);
					}
				}
				break;
		}
		foreach( $insertFrom as $fromNode ) {
			foreach( $insertTo as $toNode ) {
				switch( $type ) {
					case 'appendTo':
					case 'append':
						$toNode->insertBefore(
							$fromNode,
							$toNode->lastChild->nextSibling
						);
						break;
					case 'prependTo':
					case 'prepend':
						$toNode->insertBefore(
							$fromNode,
							$toNode->firstChild
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
							$toNode->nextSibling
						);
						break;
				}
			}
		}
		return $this;
	}

	public function text() {
		$return = '';
		foreach( $this->stack as $node ) {
			$return .= $node->textContent;
		}
		return $return;
	}
	
	public function _next( $selector = null ) {
		$this->sibling( $selector, 'previousSibling' );
		return $this;
	}
	
	public function _prev( $selector = null ) {
		$this->sibling( $selector, 'previousSibling' );
		return $this;
	}
	
	private function sibling( $selector, $direction ) {
		$stack = array();
		foreach( $this->stack as $node ) {
			$test = $node;
			while( $test->{$direction} ) {
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
	
	public function siblings( $selector = null ) {
		$stack = array();
		foreach( $this->stack as $node ) {
			if ( $selector ) {
				if ( $this->is( $selector, $test ) )
					$stack[] = $node;
			} else
				$stack[] = $node;
		}
		$this->history[] = $this->stack;
		$this->stack = $stack;
		return $this;
	}
	
	public function not( $selector = null ) {
		$stack = array();
		foreach( $this->stack as $node ) {
			if (! $this->is( $selector, node ) )
				$stack[] = $node;
		}
		$this->history[] = $this->stack;
		$this->stack = $stack;
		return $this;
	}
	
	public function add( $selector = null ) {
		$stack = array();
		$this->history[] = $this->stack;
		$this->find($selector);
		$this->merge(
			$this->history[ count($this->history)-2 ]
		);
		return $this;
		
	}
	
	private function merge() {
		foreach( get_func_args() as $nodes ) {
			foreach( $nodes as $newNode ) { 
				foreach( $this->stack as $node ) {
					if (! $node->isSameNode( $newNode ))
						$this->stack[] = $newNode;
				}
			}
		}
	}
	
	public function parent( $selector = null ) {
		$stack = array();
		foreach( $this->stack as $node ) {
			if ( $this->is( $selector, $node->parentNode ) )
				$stack[] = $node->parentNode;
		}
		$this->history[] = $this->stack;
		$this->stack = $stack;
		return $this;
		
	}
	
	public function parents( $selector = null ) {
		$stack = array();
		foreach( $this->stack as $node ) {
			$test = $node;
			while( $test->parentNode ) {
				$test = $test->parentNode;
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
		return $this;
	}
	
	public function attr( $attr, $value = null ) { 
		foreach( $this->stack as $node ) {
			if ( $value )
				return $node->setAttribute($attr, $value);
			else
				return $node->getAttribute($attr);
		}
	}
	
	public function val( $selector = null ) {
		
	}
	
	public function removeAttr( $attr ) {
		foreach( $this->stack as $node )
			$node->removeAttribute($attr);
	}
	
	public function addClass( $className ) {
		foreach( $this->stack as $node ) {
			if (! $this->is( $node, '.'.$className))
				$node->setAttribute(
					'class',
					$node->getAttribute('class').' '.$className
				);
		}
	}
	
	public function removeClass( $className ) {
		foreach( $this->stack as $node ) { 
			$classes = explode( ' ', $node->getAttribute('class'));
			if ( in_array($className, $classes) ) {
				$classes = array_diff($classes, array($className));
				if ( $classes )
					$node->setAttribute('class', $classes);
				else
					$node->removeAttribute('class');
			}
		}
	}
	
	public function toggleClass( $className ) {
		foreach( $this->stack as $node ) {
			if ( $this->is( $node, '.'.$className ))
				$this->removeClass($className);
			else 
				$this->addClass($className);
		}
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
	 * @return phpQuery
	 */
	public function _empty() {
		foreach( $this->stack as $node ) {
			// many thx to 'dave at dgx dot cz' :)
			$node->nodeValue = '';
		}
		return $this;
	}
	

	// INTERATOR INTERFACE
	public function rewind(){
		$this->_stack = $this->stack;
		$this->valid = isset( $this->stack[0] )
			? 1
			: 0;
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
		$this->valid = isset( $this->_stack[ $this->current ] )
			? true
			: false;
		if ( $this->valid )
			$this->stack = array(
				$this->_stack[ $this->current++ ]
			);
	}
	public function valid(){
		return $this->valid;
	}

	// ADDONS

	private function getNodeXpath( $oneNode = null ) {
		$return = array();
		$loop = $oneNode
			? array($oneNode)
			: $this->stack;
		foreach( $loop as $node ) {
			if ( $this->isRoot($node) ) {
				$return[] = '';
				continue;
			}				
			$xpath = array();
			while( get_class($node) != 'DOMDocument' ) {
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
			$return[] = (
				$node->tagName
				.($node->getAttribute('id')
					? '#'.$node->getAttribute('id'):'')
				.($node->getAttribute('class')
					? '.'.join('.', split(' ', $node->getAttribute('class'))):'')
			);
		}
		return $oneNode
		? $return[0]
		: $return;
	}
	
	// HELPERS

	public function dumpStack() {
		foreach( $this->stack as $node ) {
			$this->debug($node->tagName);
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

function _() {
	if (! func_num_args() )
		return new phpQuery();
	$input = func_get_args();
	// load template file
	if ( phpQuery::isHTMLfile( $input[0] ) ) {
		$loaded = phpQuery::load( $input[0] );
		return new phpQuery();
	} else if ( is_object($input[0]) && get_class($input[0]) == 'DOMElement' ) {
		// TODO suppot dom nodes
	} else {
		$last = count($input)-1;
		$PQ = new phpQuery(
			// document path
			isset( $input[$last] ) && phpQuery::isHTMLfile( $input[$last] )
				? $input[$last]
				: null
		);
		if ( $input[0][0] == '<' )
			// load HTML
			return $PQ->importHTML( $input[0] );
		else // do query
			return $PQ->find(
				$input[0],
				isset( $input[1] )
					&& is_object( $input[1] )
					&& get_class( $input[1] ) == 'phpQuery'
					? $input[1]
					: null
			);
	}
}
?>
