<? 

include __DIR__ . "/lib/cls_culinaria_program.php";
include __DIR__ . "/lib/cls_culinaria_cart.php";
include __DIR__ . "/../lang/id.php";
include __DIR__ . "/../lang/". $lang ."_culinaria.php";

$template_halaman = file_get_contents( __DIR__ . "/template/culinaria-program-detail.php.html" );

if( in_array(@$_REQUEST["l"], array("id", "en") ) ) $lang = $_REQUEST["l"];

$arr_parameter["program_id"] = array("=", "'" . main::formatting_query_string( $_REQUEST["id"] ) . "'" );
if( @$_REQUEST["preview"] == "" ) $arr_parameter["program_aktif"] = array("=", 1 );
$rs_program = culinaria_program::daftar_culinaria_program( $arr_parameter );
$program = mysqli_fetch_array( $rs_program );

$template_div_konten = "<div onclick=\"expand_(#detail_kode#)\" class=\"garis_bawah\"><span class=\"judul_program\">#detail_kode_lang#</span>
							<div class=\"panah\"><img id=\"img_#detail_kode#\" src=\"img/down.pngx\" /></div>
							<div class=\"program_konten font-grey-02-12\" id=\"konten_#detail_kode#\">#detail_lang#</div>
							</div>";

$string_program_detail = "";
unset( $arr_parameter );
$arr_parameter["a.program_id"] = array("=", "'" . main::formatting_query_string( $_REQUEST["id"] ) . "'" );
$arr_parameter["b.detail_kode"] = array("", " is not null " );
$rs_program_detail = culinaria_program::culinaria_program_detail( $arr_parameter );
while( $program_detail = mysqli_fetch_array( $rs_program_detail ) )
	$string_program_detail .= str_replace( 
								array("#detail_kode#", "#detail_kode_lang#", "#detail_lang#"), 
								array($program_detail["detail_kode"], ucwords($program_detail["detail_kode_" . $lang]), html_entity_decode($program_detail["detail_" . $lang])), 
								$template_div_konten 
								);	

$rs_sisa_kursi = culinaria_cart::sisa_kursi_program( array("c.program_id" => array("=", "'". main::formatting_query_string( $program["program_id"] ) ."'") ) );
$sisa_kursi = sql::fetch_array( $rs_sisa_kursi );
								
$arr_["#program_id#"] = $program["program_id"];
$arr_["#jumlah_detail#"] = mysqli_num_rows( $rs_program_detail );
$arr_["#lang_register#"] = $sisa_kursi["sisa_kursi"] <= 0 ? strtoupper( $lang_sold ) : strtoupper( $lang_register ) ;
$arr_["#disabled#"] = $sisa_kursi["sisa_kursi"] <= 0 ? "disabled=\"true\"" : "" ;
$arr_["#lang_program_lain_diminati#"] = ucwords( $lang_program_lain_diminati );
$arr_["#program_lain_diminati#"] = culinaria_program::box_program($template, $template_content, "ringkasan", array($program["program_id"]) );
$arr_["#program_judul#"] = $program["program_judul_" . $lang];
$arr_["#program_keterangan#"] = html_entity_decode($program["program_keterangan_" . $lang]);
$arr_["#program_detail#"] = $string_program_detail;
$arr_["#program_harga#"] = str_replace( 
								array("#detail_kode#", "#detail_kode_lang#", "#detail_lang#", "class=\"panah\"", "class=\"program_konten "), 
								array("'" . $lang_price . "'", $lang_price, "Rp". number_format( $program["program_harga"] ), "style=\"display:none\"", "style=\"margin-top:17px\" class=\"judul_program "), 
								$template_div_konten 
								);

echo json_encode(	
		array( 
			"culinaria_konten" => str_replace( array_keys($arr_), array_values($arr_), $template_halaman ),
			"jumlah_detail" => mysqli_num_rows( $rs_program_detail )
		) 
	);


?>