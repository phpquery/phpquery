<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
<?php
require_once('../phpQuery/phpQuery.php');
phpQuery::$debug = 2;


//$doc = phpQuery::newDocumentXML('<article><someMarkupStuff/><p>p</p></article>');
//print $doc['article']->children(':empty')->get(0)->tagName;

//$doc = phpQuery::newDocumentFile('test.html');
//setlocale(LC_ALL, 'pl_PL.UTF-8');
//$string =  strftime('%B %Y', time());
//$doc['p:first']->append($string)->dump();

/*
 *
$doc1 = phpQuery::newDocumentFileXHTML('doc1.html');
$doc2 = phpQuery::newDocumentFileXHTML('doc2.html');
$doc3 = phpQuery::newDocumentFileXHTML('doc3.html');
$doc4 = phpQuery::newDocumentFileXHTML('doc4.html');
$doc2['body']
	->append($doc3['body >*'])
	->append($doc4['body >*']);
$doc1['body']
	->append($doc2['body >*']);
print $doc1->plugin('Scripts')->script('safe_print');
*/
//$doc = phpQuery::newDocument('<p> p1 <b> b1 </b> <b> b2 </b> </p><p> p2 </p>');
//print $doc['p']->contents()->not('[nodeType=1]');

//print phpQuery::newDocumentFileXML('tmp.xml');


//$doc = phpQuery::newDocumentXML('text<node>node</node>test');
//pq('<p/>', $doc)->insertBefore(pq('node'))->append(pq('node'));
//$doc->contents()->wrap('<p/>');
//$doc['node']->wrapAll('<p/>');
//	->contents()
//	->wrap('<p></p>');
//print $doc;

// http://code.google.com/p/phpquery/issues/detail?id=66
//$doc = phpQuery::newDocumentXML('<p>123<span/>123</p>');
//$doc->dump();
//$doc->children()->wrapAll('<div/>')->dump();

// http://code.google.com/p/phpquery/issues/detail?id=69
//$doc = phpQuery::newDocumentXML('<p class="test">123<span/>123</p>');
//$doc['[class^="test"]']->dump();

// http://code.google.com/p/phpquery/issues/detail?id=71
// $doc = phpQuery::newDocument('<input value=""/>');
// print $doc['input']->val('new')->val();

// http://code.google.com/p/phpquery/issues/detail?id=71
// $doc = phpQuery::newDocument('<select><option value="10">10</option><option value="10">20</option></select>');
// $doc['select']->val('20')->dump();

// http://code.google.com/p/phpquery/issues/detail?id=73
// $doc = phpQuery::newDocument('<input value=""/>');
// var_dump($doc['input']->val(0)->val());

// $a = null;
// new CallbackReference($a);
// phpQuery::callbackRun(new CallbackReference($a), array('new $a value'));
// var_dump($a);

// check next() inside (also, but separatly)
// $inputs->dump();
// foreach($inputs as $node) {
// }
// $inputs->dump();

// http://code.google.com/p/phpquery/issues/detail?id=74
// http://code.google.com/p/phpquery/issues/detail?id=31
//$doc = phpQuery::newDocument('<div class="class1 class2"/><div class="class1"/><div class="class2"/>');
//$doc['div']->filter('.class1, .class2')->dump()->dumpWhois();

// http://code.google.com/p/phpquery/issues/detail?id=76
// mb_internal_encoding("UTF-8");
// mb_regex_encoding("UTF-8");
// $xml = phpQuery::newDocumentXML('<документа/>');
//
// $xml['документа']->append('<список></список>');
// $xml['документа список'] = '<эл>1</эл><эл>2</эл><эл>3</эл>';
// print "<xmp>$xml</xmp>";

// zeromski 0.9.5 vs 0.9.1
// phpQuery::newDocumentXML('<xml><b></xml>')->dump();

// http://code.google.com/p/phpquery/issues/detail?id=77
// phpQuery::newDocumentFile('http://google.com/')
// 	->find('body > *')->dumpWhois();
$XHTML = <<<EOF
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
       <head>
               <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
               <meta http-equiv="Content-Language" content="fr" />
       </head>
       <body>
               <div id="contenu_volets">
                       <div id="contenu_voletGauche">
                               <div id="contenu_navigation" class="bloc_arrondi blanc_10_10 administrable">
                                       <div class="bloc_arrondi_haut">
                                               <div class="bloc_arrondi_haut_gauche"></div>
                                               <div class="bloc_arrondi_haut_droit"></div>
                                       </div>
                                       <div class="bloc_arrondi_centre">
                                               <div class="bloc_arrondi_centre1">
                                                       <div class="bloc_arrondi_centre2 bloc_contenu administrable_contenu"></div>
                                               </div>
                                       </div>
                                       <div class="bloc_arrondi_bas">
                                               <div class="bloc_arrondi_bas_gauche"></div>
                                               <div class="bloc_arrondi_bas_droit"></div>
                                       </div>
                               </div>
                       </div>
                       <div id="contenu_voletDroit">
                               <div id="contenu_article" class="bloc_arrondi grisDegrade_10_10_341 administrable
redimensionnable">
                                       <div class="bloc_arrondi_haut">
                                               <div class="bloc_arrondi_haut_gauche"></div>
                                               <div class="bloc_arrondi_haut_droit"></div>
                                       </div>
                                       <div class="bloc_arrondi_centre">
                                               <div class="bloc_arrondi_centre1">
                                                       <div class="bloc_arrondi_centre2 bloc_contenu administrable_contenu
WAI_element-40-WAI_principal-30 WAI_contenu">
                                                               <p class="contenu_filAriane justifier_non">
                                                                       Vous êtes ici : <span class="filAriane_contenu"></span>
                                                               </p>
                                                               <h1 id="IDcmsRef-page-titre">Editorial</h1>
                                                               <div id="IDcmsTag_article"></div>
                                                               <div class="nettoyeur"></div>
                                                       </div>
                                               </div>
                                       </div>
                                       <div class="bloc_arrondi_bas">
                                               <div class="bloc_arrondi_bas_gauche"></div>
                                               <div class="bloc_arrondi_bas_droit"></div>
                                       </div>
                               </div>
                       </div>
                       <div id="contenu_voletPied"></div>
               </div>
       </body>
</html>
EOF;
phpQuery::newDocumentXHTML($XHTML)
	->find('body:first > *')->dumpWhois();
