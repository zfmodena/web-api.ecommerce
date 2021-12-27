<?
$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, "https://masterkey.masterweb.com/scriptlets/get-paypal-usd-idr-rate.php");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$arr_curr_result[] = curl_exec($ch);
	curl_close($ch);
?>