<?
include_once "../lib/var.php";
include_once "../lib/cls_main.php";
$requestData = json_decode(file_get_contents('php://input'));

$auth = sha1( __KEY__ . trim($requestData->kode_voucher) . trim($requestData->item_id));
if( $requestData->auth != $auth ) die("$auth E1");

$requestData->auth = sha1( "ptim328" . trim($requestData->kode_voucher) . trim($requestData->item_id));
$result = main::__curl_connect("code-validation", json_encode($requestData));

$response = $result["response"]; 
$responseDecoded = $result["responseDecoded"];

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
http_response_code($responseDecoded->status);
echo $response;
