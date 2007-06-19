<?php
require_once('../phpQuery.php');
$testName = 'Filter with pseudoclass';
$testResult = <<<EOF
<div class="articles">
			div.articles text node
            <ul>
                
            <li>
                	<p>This is paragraph of first LI</p>
                    <p class="title">News 1 title</p>
                    <p class="body">News 1 body</p>
                </li>

<li>
                	<p>This is paragraph of first LI</p>
                    <p class="title">News 2 title</p>
                    <p class="body">News 2 body</p>
                </li>
<li>
                	<p>This is paragraph of first LI</p>
                    <p class="title">News 3</p>
                    <p class="body">News 3 body</p>
                </li>
</ul>
<p>paragraph after UL</p>
        </div>	
EOF;
$result = _('test.html')
	->find('p')
	->filter('.body:gt(1)');
if ( $result->whois() == array('p.body') )
	print "Test '{$testName}' passed :)";
else
	print "Test '{$testName}' <strong>FAILED</strong> !!!";
print "\n";
?>