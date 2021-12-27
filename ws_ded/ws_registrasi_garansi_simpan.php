<?

$_POST = $_REQUEST;
include_once __DIR__ . "/../lang/id.php";

// cek utk campaign sellout
$arr_info_metode_sellout_klaim = array();
if( @$_POST["sel_metode_klaim"] > 0 ){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=sellout_metode_klaim&sc=validasi_sellout_klaim&metode_klaim=" . $_POST["sel_metode_klaim"]);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_POST, true);
	//curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	$server_output = json_decode($server_output, true);
	$arr_info_metode_sellout_klaim = $server_output["wajib_diisi"];
	$arr_info_metode_sellout_klaim_label = $server_output["wajib_diisi_label"];
	
	foreach( $arr_info_metode_sellout_klaim as $info_metode_sellout_klaim )
		$str_info_metode_sellout_klaim[] = $_POST[ $info_metode_sellout_klaim ];
	
	$_POST["t_klaim"] = implode("|", $str_info_metode_sellout_klaim);
	
	$str_info_metode_sellout_klaim_terpilih = array();
	foreach( explode("|", $_POST["t_klaim"]) as $index => $info_metode_sellout_klaim)
		$str_info_metode_sellout_klaim_terpilih[] = $arr_info_metode_sellout_klaim_label[ $index ] . " : " . $info_metode_sellout_klaim;
	$str_info_metode_sellout_klaim_terpilih = implode("<br />", $str_info_metode_sellout_klaim_terpilih);
		
}


// cari data serial number dari mesdb
for( $x = 1; $x < ( $x+1 ); $x++ ){
	if( !isset( $_POST["t_produk_" . $x] ) ) break;
	$arr_par["barcode"][] = $_POST["t_produk_" . $x];
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=load_multiple_barcode");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$server_output = curl_exec ($ch);
$server_output = json_decode($server_output, true);
$json = array();

if( is_array($server_output) && count( $server_output ) > 0 ) {
	
	foreach( $server_output as $mesdb_barcode ){
		for( $x = 1; $x < ( $x+1 ); $x++ ){
			
			if( !isset( $_POST["t_produk_" . $x] ) ) break;
			
			if( strtolower(trim($mesdb_barcode["BARCODESN"])) ==  strtolower(trim( substr($_POST["t_produk_" . $x], 0, strlen( trim($mesdb_barcode["BARCODESN"]) ) ) )) )
				//$arr_list_item[] = array("barcode" => trim(str_replace(" ", "", $_POST["t_produk_" . $x])), "produk" => $mesdb_barcode["ITEMDESC"] );
				$arr_list_item[] = array("barcode" => trim(preg_replace("/(\s+|\.)/", "", $_POST["t_produk_" . $x])), "produk" => $mesdb_barcode["ITEMDESC"] );
			
		}
	}
	
	$identitas_konsumen = sql::fetch_array( sql::execute("select membercode, name, email, address, region, state, phone, handphone, a.homeregion, b.state_id , homepostcode 
				from membersdata a left outer join shipment_exception b on a.homeregion = b.region_id 
				left outer join shipment_state c on b.state_id = c.state_id 
				where 
				a.MemberID = '". main::formatting_query_string( $_POST["konsumen_id"] ) ."' ") );
	
	
	// insert ke modena.membersproduct
	$arr_list_registrasi = array();
	foreach( $arr_list_item as $list_item ){
		list($tahun, $bulan, $tanggal)= explode("/", $_POST["hd_tanggal_pembelian"]);
		$arr_list_registrasi[] = $list_item + array("tempat_pembelian" => $_POST["t_tempat_pembelian"], "tanggal_pembelian" => implode(" ", array($tanggal , $arr_month[ (int)$bulan - 1 ], $tahun) ) );
		
		unset($arr_insert);
		$arr_insert["product"] = $list_item["produk"];
		$arr_insert["serialnumber"] = $list_item["barcode"];
		$arr_insert["memberid"] = $_POST["konsumen_id"];
		$arr_insert["purchaseat"] = $_POST["t_tempat_pembelian"];
		$arr_insert["purchasedate"] = $_POST["hd_tanggal_pembelian"];
		$arr_insert["nama"] = $identitas_konsumen["name"];
		$arr_insert["email"] = $identitas_konsumen["email"];
		$arr_insert["alamat"] = $identitas_konsumen["address"];
		$arr_insert["kota_string"] = $identitas_konsumen["region"];
		$arr_insert["propinsi_string"] = $identitas_konsumen["state"];
		$arr_insert["kota"] = $identitas_konsumen["homeregion"];
		$arr_insert["propinsi"] = $identitas_konsumen["state_id"];
		$arr_insert["telp"] = $identitas_konsumen["phone"];
		$arr_insert["hp"] = $identitas_konsumen["handphone"];
		$arr_insert["valid_registrasi"] = 2;
		
		// cek API utk konten metode klaim
		
		unset($arr_insert_sellout_klaim);
		$arr_insert_sellout_klaim["klaim"] = in_array( @$_POST["sel_metode_klaim"], array(2,3) ) ? 4 : @$_POST["sel_metode_klaim"]; // terkait inaktifasi gopay dan ovo
		$arr_insert_sellout_klaim["klaim_info"] = @$_POST["t_klaim"];

		// entri data baru 
		if( @$_REQUEST["sc"] == "" ){
			$membersproductid = sql::__insert("membersproduct", $arr_insert, "membersproductid");
			$arr_garansi_id[] = $membersproductid;
			
			// entri data klaim
			//if( $arr_insert_sellout_klaim["klaim"] != "" && $arr_insert_sellout_klaim["klaim_info"] != "" ){
				$arr_insert_sellout_klaim["membersproductid"] = $membersproductid;
				sql::__insert("sellout_klaim", $arr_insert_sellout_klaim);
			//}

		}else{
			// update data garansi yg sudah ada
			if( $_REQUEST["sc"] == "revisi" ){
				sql::__update( "membersproduct", $arr_insert, array("membersproductid" => array("=", "'". main::formatting_query_string( $_POST["par_membersproductid"] ) ."'") ) );
				
				// cek runtutan garansi_id yg didaftarin barengan
				$arr_filename = glob( __DIR__ . "/../upload/garansi/*". $_POST["par_membersproductid"] ."*.jpg");
				$filename = pathinfo( $arr_filename[0] );
				$arr_garansi_id[] = $filename["filename"];
	           

				// update data klaim
				if( trim($arr_insert_sellout_klaim["klaim"]) != "" && trim($arr_insert_sellout_klaim["klaim_info"]) != "" )
				sql::__update( "sellout_klaim", $arr_insert_sellout_klaim, array("membersproductid" => array(" in ", "(" . implode(",", str_replace("_", ",", $arr_garansi_id)) . ")") ) );

			}
			
		}
	}

	$json = array("konsumen_id" => $_POST["konsumen_id"], "garansi_id" => implode("_", $arr_garansi_id));
	/*
	$date = new DateTime();

	// kirim email garansi ke CS				
	$template = file_get_contents("ws_ded/template/email_registrasi_produk.html");
	unset($arr_rpl);
	$arr_rpl["#nama_konsumen#"] = $identitas_konsumen["name"];
	$arr_rpl["#email_konsumen#"] = $identitas_konsumen["email"];
	$arr_rpl["#alamat_konsumen#"] = implode(" ", array($identitas_konsumen["address"], $identitas_konsumen["region"], $identitas_konsumen["state"], $identitas_konsumen["homepostcode"]));
	$arr_rpl["#telepon/hp_konsumen#"] = implode(" | ", array($identitas_konsumen["phone"], $identitas_konsumen["handphone"] ));
	$arr_rpl["#tanggal_registrasi#"] = $date->format("d/m/Y");
	$arr_rpl["#data_klaim_cashback#"] = @$str_info_metode_sellout_klaim_terpilih;

	$template_data_produk = "
		<hr width=\"100%\" />
		<ul style=\"padding:7px 0px 7px 0px;\">
			<li>Produk: #product# [serial no. #serialnumber#]</li>
			<li>Tempat pembelian: #store#</li>
			<li>Tanggal pembelian: #purchasedate#</li>
		</ul>
		";
		
	$string_data_produk = "";
	foreach( $arr_list_registrasi as $index=>$str )
		$string_data_produk .= str_replace( array("#product#", "#serialnumber#", "#store#", "#purchasedate#"), array( $str["produk"], $str["barcode"], $str["tempat_pembelian"], $str["tanggal_pembelian"] ), $template_data_produk );
	$arr_rpl["#daftar_produk#"] = $string_data_produk;
	
	$arr_rpl["#faktur_pembelian#"] = "<img src='". $url_path ."/upload/garansi/". $json["garansi_id"] .".jpg' style='max-width:100%; padding:7px 0px 7px 0px' />";

	$konten = str_replace( array_keys($arr_rpl), array_values( $arr_rpl ), $template );
	main::send_email(CUSTOMERCARE_EMAIL, "[ciao MODENA] New Product Warranty", $konten);
	
	// kirim email terimakasih ke konsumen, garansi sedang diverifikasi
	$template = file_get_contents("ws_ded/template/konsumen_email_registrasi_produk.html");
	unset($arr_rpl);
	$arr_rpl["#nama_konsumen#"] = $identitas_konsumen["name"];
	$arr_rpl["#email_konsumen#"] = $identitas_konsumen["email"];
	$arr_rpl["#alamat_konsumen#"] = implode(" ", array($identitas_konsumen["address"], $identitas_konsumen["region"], $identitas_konsumen["state"], $identitas_konsumen["homepostcode"]));
	$arr_rpl["#telepon/hp_konsumen#"] = implode(" | ", array($identitas_konsumen["phone"], $identitas_konsumen["handphone"] ));
	$arr_rpl["#tanggal_registrasi#"] = $date->format("d/m/Y");
	$arr_rpl["#data_klaim_cashback#"] = @$str_info_metode_sellout_klaim_terpilih;

	$template_data_produk = "
		<hr width=\"100%\" />
		<ul style=\"padding:7px 0px 7px 0px;\">
			<li>Produk: #product# [serial no. #serialnumber#]</li>
			<li>Tempat pembelian: #store#</li>
			<li>Tanggal pembelian: #purchasedate#</li>
		</ul>
		";
		
	$string_data_produk = "";
	foreach( $arr_list_registrasi as $index=>$str )
		$string_data_produk .= str_replace( array("#product#", "#serialnumber#", "#store#", "#purchasedate#"), array( $str["produk"], $str["barcode"], $str["tempat_pembelian"], $str["tanggal_pembelian"] ), $template_data_produk );
	$arr_rpl["#daftar_produk#"] = $string_data_produk;
	
	$arr_rpl["#faktur_pembelian#"] = "<img src='". $url_path ."/upload/garansi/". $json["garansi_id"] .".jpg' style='max-width:100%; padding:7px 0px 7px 0px' />";

	$konten = str_replace( array_keys($arr_rpl), array_values( $arr_rpl ), $template );
	
	if( filter_var( $identitas_konsumen["email"], FILTER_VALIDATE_EMAIL) )
		main::send_email($identitas_konsumen["email"], "[ciao MODENA] New Product Warranty", $konten);
	*/
}

echo json_encode($json);
?>
