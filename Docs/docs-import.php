<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::browserGet('http://jquery-api-browser.googlecode.com/svn/trunk/api-docs.xml', 'success');
/**
 * @param phpQueryObject $pq
 */
function success($pq) {
	$docLinks = 'http://docs.jquery.com/';
	$content = array();
	$toc = array();
	$page = $_GET['page'];
	switch($page) {
		case 'Selectors':
		case 1:
			$categories = $pq->find('cat[value=Selectors] subcat');
			foreach($categories as $subcat) {
				$catName = pq($subcat)->parent()->attr('value');
				$subCatName = pq($subcat)->attr('value');
				$content[] = "====$subCatName====";
				$toc[] = "&nbsp;* [#".str_replace(' ', '_', $subCatName)." $subCatName]";
				foreach(pq($subcat)->find('selector') as $selector)
					$content[] = '&nbsp;*&nbsp;*['
						.$docLinks.$catName.'/'.pq($selector)->attr('name').' '
						.pq('sample', $selector)->text().']* '
						.pq('> desc', $selector)->text();
			}
			$content[] = "Read more at [http://docs.jquery.com/$catName $catName] section on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
		case 'Attribues':
		case 2:
		case 'Traversing':
		case 3:
		case 'Manipulation':
		case 4:
		case 'Events':
		case 6:
		case 'Ajax':
		case 8:
		case 'Utilities':
		case 9:
			$categories = is_numeric($page)
				? $pq->find("cat:eq($page) subcat")
				: $pq->find('cat[value=Attributes] subcat');
			foreach($categories as $subcat) {
				$catName = pq($subcat)->parent()->attr('value');
				$subCatName = pq($subcat)->attr('value');
				$content[] = "==$subCatName==";
				$toc[] = "&nbsp;* [#".str_replace(' ', '_', $subCatName)." $subCatName]";
				foreach(pq($subcat)->find('function') as $function) {
					$url = $docLinks.$catName.'/'.pq($function)->attr('name');
					$name = $catName == 'Ajax' || $catName == 'Utilities'
						? str_replace('jQuery.', 'phpQuery::', pq($function)->attr('name'))
						: pq($function)->attr('name');
					$tmp = "&nbsp;*&nbsp;*[$url $name]*[$url (";
					foreach(pq($function)->find('params') as $params) {
						$tmp .= '$'.pq($params)->attr('name');
						if (pq($params)->nextAll('params')->length)
							$tmp .= ', ';
					}
					$tmp .= ')] '.strip_tags(pq('> desc', $function)->text());
					$content[] = $tmp;
				}
			}
			if ($catName == 'Ajax') {
				$content[] = '==Options==';
				$content[] = 'Detailed options description in available at [http://docs.jquery.com/Ajax/jQuery.ajax#toptions jQuery Documentation Site].';
				foreach($pq->find('cat[value=Ajax] subcat:first function:first option') as $option) {
						$content[] = '&nbsp;*&nbsp;*`'.pq($option)->attr('name').'`* `'.pq($option)->attr('type').'`';
				}
			}
			$content[] = '';
			$content[] = "Read more at [http://docs.jquery.com/$catName $catName] section on [http://docs.jquery.com/ jQuery Documentation Site].";
		break;
//		case 'Traversing':
//		case 3:
//			$categories = $pq->find('cat[value=Traversing] subcat');
//			foreach($categories as $subcat) {
//				$content[] = '===='.pq($subcat)->attr('value')."====";
//				foreach(pq($subcat)->find('function') as $function) {
//					$tmp = '&nbsp;*&nbsp;*`'.pq($function)->attr('name').'(';
//					foreach(pq($function)->find('params') as $params) {
//						$tmp .= '$'.pq($params)->attr('name');
//						if (pq($params)->nextAll('params')->length)
//							$tmp .= ', ';
//					}
//					$tmp .= ')`* '.pq('> desc', $function)->text();
//					$content[] = $tmp;
//				}
//			}
//		break;
//		case 'Manipulation':
//		case 4:
//			$categories = $pq->find('cat[value=Manipulation] subcat');
//			foreach($categories as $subcat) {
//				$content[] = '===='.pq($subcat)->attr('value')."====";
//				foreach(pq($subcat)->find('function') as $function) {
//					$tmp = '&nbsp;*&nbsp;*`'.pq($function)->attr('name').'(';
//					foreach(pq($function)->find('params') as $params) {
//						$tmp .= '$'.pq($params)->attr('name');
//						if (pq($params)->nextAll('params')->length)
//							$tmp .= ', ';
//					}
//					$tmp .= ')`* '.pq('> desc', $function)->text();
//					$content[] = $tmp;
//				}
//			}
//		break;
//		case 'Events':
//		case 5:
//			$categories = $pq->find('cat[value=Events] subcat');
//			foreach($categories as $subcat) {
//				$content[] = '===='.pq($subcat)->attr('value')."====";
//				foreach(pq($subcat)->find('function') as $function) {
//					$tmp = '&nbsp;*&nbsp;*`'.pq($function)->attr('name').'(';
//					foreach(pq($function)->find('params') as $params) {
//						$tmp .= '$'.pq($params)->attr('name');
//						if (pq($params)->nextAll('params')->length)
//							$tmp .= ', ';
//					}
//					$tmp .= ')`* '.pq('> desc', $function)->text();
//					$content[] = $tmp;
//				}
//			}
//		break;
//		case 'Ajax':
//		case 6:
//			$categories = $pq->find('cat[value=Ajax] subcat');
//			foreach($categories as $subcat) {
//				$content[] = '===='.pq($subcat)->attr('value')."====";
//				foreach(pq($subcat)->find('function') as $function) {
//					$tmp = '&nbsp;*&nbsp;*`'.str_replace('jQuery.', 'phpQuery::', pq($function)->attr('name')).'(';
//					foreach(pq($function)->find('params') as $params) {
//						$tmp .= '$'.pq($params)->attr('name');
//						if (pq($params)->nextAll('params')->length)
//							$tmp .= ', ';
//					}
//					$tmp .= ')`* '.pq('> desc', $function)->text();
//					$content[] = $tmp;
//				}
//			}
//			$content[] = '====Options====';
//			foreach($pq->find('cat[value=Ajax] subcat:first function:first option') as $option) {
//					$content[] = '&nbsp;*&nbsp;*`'.pq($option)->attr('name').'`* `'.pq($option)->attr('type').'`';
//			}
//		break;
//		case 'Utilities':
//		case 7:
//			$categories = $pq->find('cat[value=Utilities] subcat');
//			foreach($categories as $subcat) {
//				$content[] = '===='.pq($subcat)->attr('value')."====";
//				foreach(pq($subcat)->find('function') as $function) {
//					$tmp = '&nbsp;*&nbsp;*`'.str_replace('jQuery.', 'phpQuery::', pq($function)->attr('name')).'(';
//					foreach(pq($function)->find('params') as $params) {
//						$tmp .= '$'.pq($params)->attr('name');
//						if (pq($params)->nextAll('params')->length)
//							$tmp .= ', ';
//					}
//					$tmp .= ')`* '.pq('> desc', $function)->text();
//					$content[] = $tmp;
//				}
//			}
//		break;
	}
	if ($toc) {
		array_unshift($toc, '=Table of Contents=');
		array_unshift($content, implode("<br />\n", $toc));
	}
	print implode("<br />\n", $content);
}
?>