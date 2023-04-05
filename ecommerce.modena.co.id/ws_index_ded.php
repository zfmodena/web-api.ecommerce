<?

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

include_once("../lib/var.php");
include_once("../lib/cls_main.php");
include_once("../ws_ded/sql.php");

define("__AIR_URL__", "http://air.modena.co.id/csapps/");

$filews = "../ws_ded/ws_" . @$_REQUEST["ws"] . ".php";

if( file_exists( $filews ) )
	include_once $filews;

?>