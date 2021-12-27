<?

$versi = "3.0.11";
$versi_user = $_REQUEST["lv"];
$play_url = "https://play.google.com/store/apps/details?id=id.co.modena.ciaomodena";
$update = 0;
$show_menu = true;

if( @$_REQUEST["platform"] == "ios" ){
	$versi = "3.1.2";
	$play_url = "itms-apps://itunes.apple.com/app/id.co.modena.ciaomodena";
	$show_menu = true;
}

if( $versi != $versi_user )
	$update = 1;
	
echo json_encode( array("update" => $update, "url" => $play_url, "show_menu" => $show_menu) );

?>