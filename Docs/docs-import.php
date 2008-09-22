<?php
require_once('./phpQuery/phpQuery.php');
//phpQuery::$debug = true;
//die(file_get_contents('http://google.com/search?hl=pl&q=phpQuery&btnG=Szukaj+w+Google&lr='));
//phpQuery::extend('WebBrowser');
//phpQuery::$ajaxAllowedHosts[] = 'http://jquery-api-browser.googlecode.com';
//
//phpQuery::$plugins->browserGet('http://jquery-api-browser.googlecode.com/svn/trunk/api-docs.xml', 'success');
phpQuery::browserGet('http://jquery-api-browser.googlecode.com/svn/trunk/api-docs.xml', 'success');
/**
 * @param phpQueryObject $pq
 */
function success($pq) {
	switch($_GET['page']) {
		case 'selectors':
		case 1:
			$categories = $pq->find('cat[value=Selectors] subcat');
			$content = array();
//			$content[] = "==Selectors==";
			$content[] = "In *phpQuery*, as in *jQuery*, supported are following *CSS3* selectors.";
			foreach($categories as $subcat) {
				$content[] = '===='.pq($subcat)->attr('value')."====";
				foreach(pq($subcat)->find('selector') as $selector)
					$content[] = '&nbsp;*&nbsp;*`'.pq('sample', $selector)->text().'`* '.pq('> desc', $selector)->text();
			}
			$content[] = '';
			$content[] = "Read more at [http://docs.jquery.com/Selectors Selectors section] on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
		case 'Attribues':
		case 2:
			$categories = $pq->find('cat[value=Attributes] subcat');
			$content = array();
//			$content[] = "==Attributes==";
			$content[] = "Attributes related methods.";
			foreach($categories as $subcat) {
				$content[] = '===='.pq($subcat)->attr('value')."====";
				foreach(pq($subcat)->find('function') as $function) {
					$tmp = '&nbsp;*&nbsp;*`'.pq($function)->attr('name').'(';
					foreach(pq($function)->find('params') as $params) {
						$tmp .= '$'.pq($params)->attr('name');
						if (pq($params)->nextAll('params')->length)
							$tmp .= ', ';
					}
					$tmp .= ')`* '.pq('> desc', $function)->text();
					$content[] = $tmp;
				}
			}
			$content[] = '';
			$content[] = "Read more at [http://docs.jquery.com/Attributes Attributes section] on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
		case 'Traversing':
		case 3:
			$categories = $pq->find('cat[value=Traversing] subcat');
			$content = array();
//			$content[] = "==Attributes==";
			$content[] = "Traversing related methods.";
			foreach($categories as $subcat) {
				$content[] = '===='.pq($subcat)->attr('value')."====";
				foreach(pq($subcat)->find('function') as $function) {
					$tmp = '&nbsp;*&nbsp;*`'.pq($function)->attr('name').'(';
					foreach(pq($function)->find('params') as $params) {
						$tmp .= '$'.pq($params)->attr('name');
						if (pq($params)->nextAll('params')->length)
							$tmp .= ', ';
					}
					$tmp .= ')`* '.pq('> desc', $function)->text();
					$content[] = $tmp;
				}
			}
			$content[] = '';
			$content[] = "Read more at [http://docs.jquery.com/Traversing Traversing section] on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
		case 'Manipulation':
		case 4:
			$categories = $pq->find('cat[value=Manipulation] subcat');
			$content = array();
//			$content[] = "==Attributes==";
			$content[] = "Manipulatin related methods.";
			foreach($categories as $subcat) {
				$content[] = '===='.pq($subcat)->attr('value')."====";
				foreach(pq($subcat)->find('function') as $function) {
					$tmp = '&nbsp;*&nbsp;*`'.pq($function)->attr('name').'(';
					foreach(pq($function)->find('params') as $params) {
						$tmp .= '$'.pq($params)->attr('name');
						if (pq($params)->nextAll('params')->length)
							$tmp .= ', ';
					}
					$tmp .= ')`* '.pq('> desc', $function)->text();
					$content[] = $tmp;
				}
			}
			$content[] = '';
			$content[] = "Read more at [http://docs.jquery.com/Manipulation Manipulation section] on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
		case 'Events':
		case 5:
			$categories = $pq->find('cat[value=Events] subcat');
			$content = array();
//			$content[] = "==Attributes==";
			$content[] = "Events related methods.";
			foreach($categories as $subcat) {
				$content[] = '===='.pq($subcat)->attr('value')."====";
				foreach(pq($subcat)->find('function') as $function) {
					$tmp = '&nbsp;*&nbsp;*`'.pq($function)->attr('name').'(';
					foreach(pq($function)->find('params') as $params) {
						$tmp .= '$'.pq($params)->attr('name');
						if (pq($params)->nextAll('params')->length)
							$tmp .= ', ';
					}
					$tmp .= ')`* '.pq('> desc', $function)->text();
					$content[] = $tmp;
				}
			}
			$content[] = '';
			$content[] = "Read more at [http://docs.jquery.com/Events Events section] on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
		case 'Ajax':
		case 6:
			$categories = $pq->find('cat[value=Ajax] subcat');
			$content = array();
//			$content[] = "==Attributes==";
			$content[] = "Ajax related methods.";
			foreach($categories as $subcat) {
				$content[] = '===='.pq($subcat)->attr('value')."====";
				foreach(pq($subcat)->find('function') as $function) {
					$tmp = '&nbsp;*&nbsp;*`'.str_replace('jQuery.', 'phpQuery::', pq($function)->attr('name')).'(';
					foreach(pq($function)->find('params') as $params) {
						$tmp .= '$'.pq($params)->attr('name');
						if (pq($params)->nextAll('params')->length)
							$tmp .= ', ';
					}
					$tmp .= ')`* '.pq('> desc', $function)->text();
					$content[] = $tmp;
				}
			}
			$content[] = '====Options====';
			foreach($pq->find('cat[value=Ajax] subcat:first function:first option') as $option) {
					$content[] = '&nbsp;*&nbsp;*`'.pq($option)->attr('name').'`* `'.pq($option)->attr('type').'`';
			}
			$content[] = '';
			$content[] = "Read more at [http://docs.jquery.com/Ajax Ajax section] on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
		case 'Utilities':
		case 7:
			$categories = $pq->find('cat[value=Utilities] subcat');
			$content = array();
//			$content[] = "==Attributes==";
			$content[] = "Some handy functions.";
			foreach($categories as $subcat) {
				$content[] = '===='.pq($subcat)->attr('value')."====";
				foreach(pq($subcat)->find('function') as $function) {
					$tmp = '&nbsp;*&nbsp;*`'.str_replace('jQuery.', 'phpQuery::', pq($function)->attr('name')).'(';
					foreach(pq($function)->find('params') as $params) {
						$tmp .= '$'.pq($params)->attr('name');
						if (pq($params)->nextAll('params')->length)
							$tmp .= ', ';
					}
					$tmp .= ')`* '.pq('> desc', $function)->text();
					$content[] = $tmp;
				}
			}
			$content[] = '';
			$content[] = "Read more at [http://docs.jquery.com/Utilities Utilities section] on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
	}
	print implode("<br />\n", $content);
}
?>