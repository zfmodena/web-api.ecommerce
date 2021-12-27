<?

$arr_data = array();

$sql = "select * from membersproduct where serialnumber = '". main::formatting_query_string( @$_REQUEST["sn"] ) ."' and valid_registrasi > 0;" ;
$rs = mysql_query( $sql ) or die( mysql_error() );

if( mysql_num_rows( $rs ) <= 0 || @$_REQUEST["sc"] == "cek_data_registrasiproduk_revisi"  ){
	// cek data serial number di air.modena.co.id (dari CSMS)
	$_REQUEST["s"] = $_REQUEST["sn"];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=info_registrasi_garansi&cs=info_registrasi_produk_csms");
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_REQUEST));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec ($ch);
	
	//diinaktifkan dulu cek ke CSMS
	//if( count(json_decode($server_output)) > 0 ) die( json_encode( array( "item" => "duplikasi" ) ) );
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=load_barcode");
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_REQUEST));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec ($ch);
	echo $server_output;
	
}else{

	echo json_encode( array( "item" => "duplikasi" ) );
}


?>