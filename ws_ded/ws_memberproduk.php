<?
if( @$_REQUEST["c"] == "reject_verifikasi" ){
	$sql = "update membersproduct set valid_registrasi = 3 where SerialNumber = '". main::formatting_query_string( $_REQUEST["sn"] ) ."';";
	sql::execute( $sql );
	echo json_encode( $sql );
	exit;
}

$arr_data = $arr_par = array();
if( @$_REQUEST["memberid"] == "" ) goto Skip_All;

if( @$_REQUEST["c"] == "update_data_kontrak_servis" ){
	$sql = "update kontrak_servis set kontrak_servis_nomor = '". main::formatting_query_string( $_REQUEST["ksn"] ) ."' where kontrak_servis_produk_nomor_seri = '". main::formatting_query_string( $_REQUEST["sn"] ) ."';";
	sql::execute( $sql );
	exit;
}

//if( @$_REQUEST["c"] == "cek_kontrak_servis" ){
	$sql_kolom_tambahan = ",case when ifnull(b.kontrak_servis_produk_nomor_seri, '') = '' then 0 else 1 end kontrak_servis_registrasi, cast(b.kontrak_servis_registrasi_tanggal as date) kontrak_servis_registrasi_tanggal  ";
	$sql_tabel_tambahan = " left outer join  kontrak_servis b on a.serialnumber = b.kontrak_servis_produk_nomor_seri and  b.kontrak_servis_nomor = ''
														left outer join (select max(kontrak_servis_registrasi_id) kontrak_servis_registrasi_id, kontrak_servis_produk_nomor_seri from kontrak_servis where kontrak_servis_nomor = '' group by kontrak_servis_produk_nomor_seri) c on 
															c.kontrak_servis_registrasi_id = b.kontrak_servis_registrasi_id and b.kontrak_servis_produk_nomor_seri = c.kontrak_servis_produk_nomor_seri ";
//}

$sql = "select a1.email,a.MembersProductID, a.Product, a.SerialNumber, a.PurchaseAt,  cast(a.PurchaseDate as date) PurchaseDate, datediff(CURRENT_DATE, cast(a.PurchaseDate as date)) usia_produk,a.valid_registrasi ". @$sql_kolom_tambahan ." 
	from membersproduct a INNER JOIN membersdata a1 ON a1.memberid=a.memberid ". @$sql_tabel_tambahan ." 
	where a.MemberID = '". main::formatting_query_string( $_REQUEST["memberid"] ) ."' " ;

if( isset( $_REQUEST["membersproductid"] ) ){
	if( is_array($_REQUEST["membersproductid"]) && count( $_REQUEST["membersproductid"] ) > 0 )
		$sql .= " and a.membersproductid in (". implode(" , ", $_REQUEST["membersproductid"]) .") ";
	else
		$sql .= " and a.membersproductid = '". $_REQUEST["membersproductid"] ."' ";
	
}

$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) ){
	$arr_par["email"] = $data["email"];
	$arr_data[] = array("membersproductid"=>$data["MembersProductID"], 
					"product"=>$data["Product"], "serialnumber" => $data["SerialNumber"], 
					"purchaseat" => $data["PurchaseAt"], "purchasedate" => $data["PurchaseDate"],
					"usia_produk" => $data["usia_produk"],
					"kontrak_servis_registrasi" => @$data["kontrak_servis_registrasi"],
					"kontrak_servis_registrasi_tanggal" => @$data["kontrak_servis_registrasi_tanggal"],
					"status_verifikasi" => $data["valid_registrasi"]
				);
	$arr_par["barcode"][] = $data["SerialNumber"];

}

foreach( $arr_data as $index => $arr_memberproduk ){

// 	$arr_filename = glob( __DIR__ . "/../upload/suratpernyataan/*". $arr_memberproduk["membersproductid"] ."*.jpg");
// 	foreach ( $arr_filename as $filename ) {
// 		$arr_data[ $index ]["image_pernyataan"] = "https://www.modena.co.id/ws_ded/ws_upload_garansi.php?sc=lihat_foto_pernyataan&k=" . $arr_memberproduk["membersproductid"];
// 	}
	
	$arr_filename_ktp = glob( __DIR__ . "/../upload/ktp/*". $arr_memberproduk["membersproductid"] ."*.jpg");
	foreach ( $arr_filename_ktp as $filename ) {
		$arr_data[ $index ]["image_ktp"] = "https://www.modena.co.id/ws_ded/ws_upload_garansi.php?sc=lihat_foto_ktp&k=" . $arr_memberproduk["membersproductid"];
	}
	
	$arr_filename_selfie = glob( __DIR__ . "/../upload/selfie/*". $arr_memberproduk["membersproductid"] ."*.jpg");
	foreach ( $arr_filename_selfie as $filename ) {
		$arr_data[ $index ]["image_selfie"] = "https://www.modena.co.id/ws_ded/ws_upload_garansi.php?sc=lihat_foto_selfie&k=" . $arr_memberproduk["membersproductid"];
	}
	
	$arr_filename = glob( __DIR__ . "/../upload/suratpernyataan/*". $arr_memberproduk["membersproductid"] ."*.jpg");
	foreach ( $arr_filename as $filename ) {
	    $arr = explode("/",$filename);
        for($x=0;$x < count($arr); $x++){
        	if(strpos($arr[$x], ".jpg") !== false){
        // 	    $arr_data[ $index ]["pdf_pernyataan"] = "https://www.modena.co.id/upload/suratpernyataan/" . $arr[$x];
		      //  $arr_data[ $index ]["pdf_name"] = $arr[$x] ;
		      $arr_data[ $index ]["image_pernyataan"] = "https://www.modena.co.id/upload/suratpernyataan/" . $arr[$x];
        	}
        }
	
	}
}




// cek info status verifikasi
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=info_registrasi_garansi&cs=info_konsumen_produk_in");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output_verify = curl_exec ($ch);
$server_output_verify = json_decode($server_output_verify, true);

if( count($server_output_verify) > 0 ){
	foreach( $arr_data as $index => $arr_memberproduk ){

		foreach( $server_output_verify as $indx => $data_server_output ){
			if( is_array( $server_output_verify) && (strtoupper($arr_memberproduk["serialnumber"]) == strtoupper($data_server_output['produk_serial']) )){
				$arr_data[ $index ]["status_verifikasi"] = 1;
			}
		}
	}
}

// cek info kontrak servis dari air.modena.co.id/csapps
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=kontrak_servis");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec ($ch);
$server_output = json_decode($server_output, true);

if( count($server_output) > 0 ){
	foreach( $arr_data as $index => $arr_memberproduk ){
		if( is_array( $server_output["kontrak_servis"] ) && array_key_exists( $arr_memberproduk["serialnumber"], $server_output["kontrak_servis"] ) )
			$arr_data[ $index ]["data_kontrak_servis"] = $server_output["kontrak_servis"][ $arr_memberproduk["serialnumber"] ];
		if( is_array($server_output["servis_request"]) && array_key_exists( $arr_memberproduk["serialnumber"], $server_output["servis_request"] ) )
			$arr_data[ $index ]["data_servis_request"] = $server_output["servis_request"][ $arr_memberproduk["serialnumber"] ];
	}
}

if( count( $arr_data ) == 1 ){
		
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=load_multiple_barcode");
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$server_output = curl_exec ($ch);
	$server_output = json_decode($server_output, true);
	$arr_nama_produk = array();

	if( is_array($server_output) && count( $server_output ) > 0 ) {
		
		foreach( $server_output as $mesdb_barcode ){
			
			foreach( $arr_par["barcode"] as $membersproductserialnumber ){
				if( strtolower(trim($mesdb_barcode["BARCODESN"])) ==  strtolower(trim( substr($membersproductserialnumber, 0, strlen( trim($mesdb_barcode["BARCODESN"]) ) ) )) ){
					$arr_nama_produk[ $membersproductserialnumber ] = $mesdb_barcode["SPLMODEL"];
					continue;
				}
			}
		}
	}
	
	$arr_breakdown_nama_produk = array();
	foreach( $arr_nama_produk as $nama_produk ){
		$arr_ = preg_split( "/(\s|\/)/", $nama_produk );
		$arr_breakdown_nama_produk[] = "cast(a.name as binary ) like '%" . $arr_[0] . "%'";
		if( isset( $arr_[1] ) )  $arr_breakdown_nama_produk[] = "cast(a.name as binary ) like '%". $arr_[1] . "%'";
	}
	
	$arr_breakdown_nama_produk = $arr_breakdown_nama_produk_ordering = array();
	foreach( $arr_nama_produk as $nama_produk ){
		$nama_produk_tanpa_kode_warna = explode(" ", $nama_produk);
		$arr_breakdown_nama_produk[] = " a.name like '%". main::formatting_query_string( $nama_produk ) ."%' or a.name like '%". main::formatting_query_string( $nama_produk_tanpa_kode_warna[0] . " " . $nama_produk_tanpa_kode_warna[1] ) ."%' ";
		$arr_breakdown_nama_produk_ordering[] = " 
														when a.name like '%". main::formatting_query_string( $nama_produk ) ."%' then  ". count( $arr_breakdown_nama_produk_ordering ) ."
														when a.name like '%". main::formatting_query_string( $nama_produk_tanpa_kode_warna[0] . " " . $nama_produk_tanpa_kode_warna[1] ) ."%' then " . (count( $arr_breakdown_nama_produk_ordering ) + 1);
	}
	
	if(count($arr_breakdown_nama_produk) > 0){
    	$arr_nama_produk_penunjang = array();
    	$sql = "select a.name, upper(trim(a.name)) nama_produk from product a where (". implode(" or ", $arr_breakdown_nama_produk) .") order by case ". implode("", $arr_breakdown_nama_produk_ordering) ." else ". count($arr_breakdown_nama_produk_ordering) ." end";
    	$rs = mysql_query( $sql ) or die( mysql_error() );
    	if( mysql_num_rows( $rs ) > 0 ){
    		// ambil satu produk yang paling atas saja
    		while( $data = mysql_fetch_array( $rs ) ){
    			if( file_exists("images/product/tn" . $data["name"] . ".png") ){
    				$arr_data[0]["nama_produk"] = $data["name"];
    				break;
    			}
    		}
    	}
	}//else{
	//    $arr_data[0]["nama_produk"] = "default.png";
	//}	
}				
				
Skip_All:				
echo json_encode( $arr_data );

?>