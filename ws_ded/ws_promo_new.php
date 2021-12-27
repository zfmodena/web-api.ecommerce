<?

include __DIR__ . "/../lib/cls_image_homepage.php";
$arr_data_return = array();
$page = "promo.php";

foreach( array_keys($arr_imageheadline_homepage) as $_SESSION["homepage"]){

	unset($arr_data);
	
	$promo_image_homepage = new image_homepage(2);

	for( $x = 0; $x < count( $arr_imagepromo_homepage[ $_SESSION["homepage"] ] ); $x++ ){

		$arr_data_header["#home_banner#"] = $arr_imagepromo_homepage[ $_SESSION["homepage"] ][$x]["#home_banner#"];
		
		if( strpos($arr_data_header["#home_banner#"], "default_promo.jpg") !== false || strpos($arr_data_header["#home_banner#"], "draft-home.jpg") !== false ) continue;
		$arr_data_return[]['image'] = $arr_data_header["#home_banner#"];
		
		//$arr_data_return["string_homepage"][] =  strpos($string_homepage, "default_promo.jpg") === false && strpos($string_homepage, "draft-home.jpg") === false ? $string_homepage : "";
		
	}	
}

if( count($arr_data_return) <= 0 ) $arr_data_return[]["image"] = "images/home/temp/2/appliances.php/default_promo.jpg";

echo json_encode($arr_data_return);

?>