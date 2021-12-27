<?

include __DIR__ . "/../lib/cls_image_homepage.php";
$arr_data_return = array();
$page = "promo.php";

foreach( array_keys($arr_imageheadline_homepage) as $_SESSION["homepage"]){

	unset($arr_data);
	
	$promo_image_homepage = new image_homepage(2);

	for( $x = 0; $x < count( $arr_imagepromo_homepage[ $_SESSION["homepage"] ] ); $x++ ){

		$string_homepage = file_get_contents( __DIR__ . "/../template/homepage.html");
		$template_homepage_header = file_get_contents( __DIR__ . "/../template/homepage-header.html");
		
		$arr_data_header["#home_banner#"] = $arr_imagepromo_homepage[ $_SESSION["homepage"] ][$x]["#home_banner#"];
		$arr_data_header["#banner_url#"] = @$arr_imagepromo_homepage[ $_SESSION["homepage"] ][$x]["#banner_url#"];
		$arr_data_header["#banner_cursor#"] = @$arr_imagepromo_homepage[ $_SESSION["homepage"] ][$x]["#banner_cursor#"];
		$arr_data_header["#font_color#"] = $arr_imagepromo_homepage[ $_SESSION["homepage"] ][$x]["#font_color#"];
		$arr_data_header["#string_headline#"] = $arr_imagepromo_homepage[ $_SESSION["homepage"] ][$x]["#string_headline#"];
		$arr_data_header["#string_subheadline#"] = $arr_imagepromo_homepage[ $_SESSION["homepage"] ][$x]["#string_subheadline#"];
		
		$arr_src_header = array_keys($arr_data_header);
		$arr_rpl_header = array_values($arr_data_header);

		$arr_data["#homepage-header#"] = str_replace($arr_src_header, $arr_rpl_header, $template_homepage_header);
		$arr_data["#string_homepage_image_cell#"] = "";

		$arr_src = array_keys($arr_data);
		$arr_rpl = array_values($arr_data);
		
		$arr_data_return["homepage_script_tambahan"][] = $promo_image_homepage->homepage_script_tambahan;
		$string_homepage = str_replace($arr_src, $arr_rpl, $string_homepage);
		$arr_data_return["string_homepage"][] =  strpos($string_homepage, "default_promo.jpg") === false && strpos($string_homepage, "draft-home.jpg") === false ? $string_homepage : "";
		
	}	
}

echo json_encode($arr_data_return);

?>