<?php
// here you can directly run xpath queries to debug your tests
$Query = "/html[1]//*";

$DOM = new DOMDocument();
$DOM->loadHTMLFile('test.html');
$X = new DOMXPath($DOM);
print $Query;
whois($X->query($Query));
function whois($nodeList) {
	$return = array();
	foreach( $nodeList as $node ) {
		$return[] = (
			$node->tagName
			.($node->getAttribute('id')
				? '#'.$node->getAttribute('id'):'')
			.($node->getAttribute('class')
				? '.'.join('.', split(' ', $node->getAttribute('class'))):'')
		);
	}
	print "<pre>";
	print_r($return);
	print "</pre>";
}
?>