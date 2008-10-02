<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = true;

//$testName = 'ReplaceWith';
phpQuery::newDocumentFile('document-types/document.html');
phpQuery::newDocumentFile('document-types/document.xhtml');
phpQuery::newDocumentFile('document-types/document.xml');
//	->find('p:eq(1)')
//		->replaceWith("<p class='newTitle'>
//                        this is example title
//                    </p>");
//$result = pq('p:eq(1)');
//if ( $result->hasClass('newTitle') )
//	print "Test '{$testName}' PASSED :)";
//else
//	print "Test '{$testName}' <strong>FAILED</strong> !!! ";
//$result->dump();
//print "\n";
?>
