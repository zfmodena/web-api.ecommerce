<?php

$doc = new DOMDocument;
$doc->load('http://themoneyconverter.com/rss-feed/USD/rss.xml');
$xpath = new DOMXPath($doc);
$query = '//rss/channel/item[title="IDR/USD"]/description';
$entries = $xpath->query($query);

foreach ($entries as $entry) {
	$scur=preg_replace("/[a-z=,]/i", "", $entry->nodeValue);
	$arr_curr_result[]=trim(substr($scur, 1, strlen($scur)-1));
}	

?>