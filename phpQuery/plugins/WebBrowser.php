<?php
/**
 * WebBrowser plugin.
 *
 * @
 */
class phpQueryObjectPlugin_WebBrowser {
	/**
	 * Limit binded methods to specified ones.
	 *
	 * @var array
	 */
	public static $phpQueryMethods = null;
	/**
	 * Enter description here...
	 *
	 * @param phpQueryObject $self
	 */
	public static function WebBrowser($self, $callback, $location = null) {
		$self = $self->_clone()->toRoot();
		$location = $location
			? $location
			: $self->document->xhr->getUri();
		if (! $location)
			throw new Exception('Location needed to activate WebBrowser plugin !');
		else {
			$self->bind('click', array($location, $callback), array('phpQueryPlugin_WebBrowser', 'hadleClick'));
			$self->bind('submit', array($location, $callback), array('phpQueryPlugin_WebBrowser', 'handleSubmit'));
		}
	}
}
class phpQueryPlugin_WebBrowser {
	public static $xhr = null;
	/**
	 * Limit binded methods to specified ones.
	 *
	 * @var array
	 */
//	public $phpQueryMethods = array('WebBrowserBind');
	/**
	 * Handler for default WebBrowser events.
	 * Same parameters as for AjaxSuccess event.
	 *
	 * @param unknown_type $callback
	 * @TODO bind with normal bind('webbrowser') :)))
	 */
	public static function browserGet($url, $callback) {
//		$success = $ajaxOptions['success'];
//		$error = $ajaxOptions['error'];
//		$complete = $ajaxOptions['complete'];
//		$ajaxOptions['success'] = null;
//		$ajaxOptions['error'] = null;
//		$ajaxOptions['complete'] = null;
		$xhr = phpQuery::ajax(array(
			'type' => 'GET',
			'url' => $url,
			'dataType' => 'html',
		));
		if ($xhr->getLastResponse()->isSuccessful()) {
			call_user_func_array($callback, array(
				self::browserReceive($xhr)//->WebBrowser($callback)
			));
			return true;
		} else
			return false;
	}
	public static function browserPost($url, $data, $callback) {
	}
	/**
	 * @param Zend_Http_Client $xhr
	 */
	public static function browserReceive($xhr) {
		// TODO handle meta redirects
		$body = $xhr->getLastResponse()->getBody();

		// XXX error ???
		if (strpos($body, '<!doctype html>') !== false) {
			$body = '<html>'
				.str_replace('<!doctype html>', '', $body)
				.'</html>';
		}
		$pq = phpQuery::newDocument($body);
		$pq->document->xhr = $xhr;
		$pq->document->location = $xhr->getUri();
		$refresh = $pq->find('meta[http-equiv=refresh]')->add('meta[http-equiv=Refresh]');
		if ($refresh->size()) {
//			print htmlspecialchars(var_export($xhr->getCookieJar()->getAllCookies(), true));
//			print htmlspecialchars(var_export($xhr->getLastResponse()->getHeader('Set-Cookie'), true));
			phpQuery::debug("Meta redirect... '{$refresh->attr('content')}'\n");
			// there is a refresh, so get the new url
			$content = $refresh->attr('content');
			$urlRefresh = substr($content, strpos($content, '=')+1);
			$urlRefresh = trim($urlRefresh, '\'"');
			// make ajax call, passing last $xhr object to preserve important stuff
			$xhr = phpQuery::ajax(array(
				'type' => 'GET',
				'url' => $urlRefresh,
				'dataType' => 'html',
			), $xhr);
			if ($xhr->getLastResponse()->isSuccessful()) {
				// if all is ok, repeat this method...
				return call_user_func_array(
					array('phpQueryPlugin_WebBrowser', 'browserReceive'), array($xhr)
				);
			}
		} else
			return $pq;
	}
	public static function hadleClick($e) {
		$node = phpQuery::pq($e->target);
		$type = null;
		if ($node->is('a[href]')) {
			// TODO document.location
			$xhr = isset($node->document->xhr)
				? $node->document->xhr
				: null;
			$xhr = phpQuery::ajax(array(
				'url' => resolve_url($e->data[0], $node->attr('href')),
			), $xhr);
			if ($xhr->getLastResponse()->isSuccessfull())
				call_user_func_array($e->data[1], array(
					self::browserReceive($xhr, $e->data[1])
				));
		} else if ($node->is(':submit') && $node->parents('form')->size())
			$node->parents('form')->trigger('submit', array($e));
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $e
	 * @TODO trigger submit for form after form's  submit button has a click event
	 */
	public static function handleSubmit($e) {
		$node = phpQuery::pq($e->target);
		if (!$node->is('form') || !$node->is('[action]'))
			return;
		// TODO document.location
		$xhr = isset($node->document->xhr)
			? $node->document->xhr
			: null;
		$submit = pq($e->target)->is(':submit')
			? $e->target
				// will this work ?
//			: $node->find(':submit:first')->get(0);
			: $node->find('*:submit:first')->get(0);
		$data = array();
		foreach($node->serializeArray($submit) as $r)
		// XXXt.c maybe $node->not(':submit')->add($sumit) would be better ?
//		foreach($node->serializeArray($submit) as $r)
			$data[ $r['name'] ] = $r['value'];
		$options = array(
			'type' => $node->attr('method')
				? $node->attr('method')
				: 'GET',
			'url' => resolve_url($e->data[0], $node->attr('action')),
			'data' => $data,
//			'success' => $e->data[1],
		);
		$xhr = phpQuery::ajax($options, $xhr);
		if ($xhr->getLastResponse()->isSuccessful())
			call_user_func_array($e->data[1], array(
				self::browserReceive($xhr, $e->data[1])
			));
	}
}
/**
 *
 * @param unknown_type $parsed
 * @return unknown
 * @link http://www.php.net/manual/en/function.parse-url.php
 * @author stevenlewis at hotmail dot com
 */
function glue_url($parsed)
    {
    if (! is_array($parsed)) return false;
    $uri = isset($parsed['scheme']) ? $parsed['scheme'].':'.((strtolower($parsed['scheme']) == 'mailto') ? '':'//'): '';
    $uri .= isset($parsed['user']) ? $parsed['user'].($parsed['pass']? ':'.$parsed['pass']:'').'@':'';
    $uri .= isset($parsed['host']) ? $parsed['host'] : '';
    $uri .= isset($parsed['port']) ? ':'.$parsed['port'] : '';
    if(isset($parsed['path']))
        {
        $uri .= (substr($parsed['path'],0,1) == '/')?$parsed['path']:'/'.$parsed['path'];
        }
    $uri .= isset($parsed['query']) ? '?'.$parsed['query'] : '';
    $uri .= isset($parsed['fragment']) ? '#'.$parsed['fragment'] : '';
    return $uri;
    }
/**
 * Enter description here...
 *
 * @param unknown_type $base
 * @param unknown_type $url
 * @return unknown
 * @author adrian-php at sixfingeredman dot net
 */
    function resolve_url($base, $url) {
        if (!strlen($base)) return $url;
        // Step 2
        if (!strlen($url)) return $base;
        // Step 3
        if (preg_match('!^[a-z]+:!i', $url)) return $url;
        $base = parse_url($base);
        if ($url{0} == "#") {
                // Step 2 (fragment)
                $base['fragment'] = substr($url, 1);
                return unparse_url($base);
        }
        unset($base['fragment']);
        unset($base['query']);
        if (substr($url, 0, 2) == "//") {
                // Step 4
                return unparse_url(array(
                        'scheme'=>$base['scheme'],
                        'path'=>substr($url,2),
                ));
        } else if ($url{0} == "/") {
                // Step 5
                $base['path'] = $url;
        } else {
                // Step 6
                $path = explode('/', $base['path']);
                $url_path = explode('/', $url);
                // Step 6a: drop file from base
                array_pop($path);
                // Step 6b, 6c, 6e: append url while removing "." and ".." from
                // the directory portion
                $end = array_pop($url_path);
                foreach ($url_path as $segment) {
                        if ($segment == '.') {
                                // skip
                        } else if ($segment == '..' && $path && $path[sizeof($path)-1] != '..') {
                                array_pop($path);
                        } else {
                                $path[] = $segment;
                        }
                }
                // Step 6d, 6f: remove "." and ".." from file portion
                if ($end == '.') {
                        $path[] = '';
                } else if ($end == '..' && $path && $path[sizeof($path)-1] != '..') {
                        $path[sizeof($path)-1] = '';
                } else {
                        $path[] = $end;
                }
                // Step 6h
                $base['path'] = join('/', $path);

        }
        // Step 7
        return glue_url($base);
}
?>