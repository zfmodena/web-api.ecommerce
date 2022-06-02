<?
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, "http://download.finance.yahoo.com/d/quotes?s=USDIDR=X&f=l1");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$arr_curr_result[] = curl_exec($ch);
	curl_close($ch);

?>