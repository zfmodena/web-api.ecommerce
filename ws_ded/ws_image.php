<?

define("__PATH_BG__", "/images/ciaomodena/");

$url_image = __PATH_BG__ . "index.jpg";
if( $_REQUEST["pg"] != "" )
	$url_image = __PATH_BG__ . $_REQUEST["pg"] . ".jpg";

$json = array("status" => 0, "path" => "");

if( file_exists( __DIR__. "/../". $url_image) )
	$json = array("status" => 1, "path" => $url_image);

echo json_encode( $json );

?>