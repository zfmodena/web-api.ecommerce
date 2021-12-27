<?

header('Access-Control-Allow-Origin: *');
//header('Content-Type: application/json');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://air.modena.co.id/csapps/");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $_REQUEST ));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
echo curl_exec ($ch); 

?>