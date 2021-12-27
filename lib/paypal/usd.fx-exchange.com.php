<?php

$doc = new DOMDocument;
$doc->load('http://usd.fx-exchange.com/idr.xml');
$xpath = new DOMXPath($doc);
$query = '//rss/channel/item[title="US Dollar(USD)/Indonesian Rupiah(IDR)"]/description';
$entries = $xpath->query($query);

foreach ($entries as $entry) {
	$scur=preg_replace("/[a-z,]/i", "", $entry->nodeValue);
	$scur=str_replace(
		array("<",">","!","-",":","/","\"","\n"),
		array("","","","","","","",""),
		$scur
	);
	$arcur1=explode("=",trim($scur));
	$arcur2=explode(" ",trim($arcur1[1]));
	$arr_curr_result[]=trim($arcur2[0]);
}	

?>