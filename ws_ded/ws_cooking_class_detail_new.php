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
while( $program = mysqli_fetch_array( $rs_program ) ){
	$program["program_keterangan_" . $lang]= html_entity_decode($program["program_keterangan_" . $lang]);
	$arr_data["program"] = $program;

}


$string_program_detail = "";
unset( $arr_parameter );
$arr_parameter["a.program_id"] = array("=", "'" . main::formatting_query_string( $_REQUEST["id"] ) . "'" );
$arr_parameter["b.detail_kode"] = array("", " is not null " );
$rs_program_detail = culinaria_program::culinaria_program_detail( $arr_parameter );
while( $program_detail = mysqli_fetch_array( $rs_program_detail ) ){
	$program_detail["detail_" . $lang]= html_entity_decode($program_detail["detail_" . $lang]);
	$arr_data["program_detail"][] = $program_detail;

}



$rs_sisa_kursi = culinaria_cart::sisa_kursi_program( array("c.program_id" => array("=", "'". main::formatting_query_string( $program["program_id"] ) ."'") ) );
$sisa_kursi = sql::fetch_array( $rs_sisa_kursi );

$arr_data["jumlah_detail"] = $sisa_kursi;
								

echo json_encode($arr_data);


?>