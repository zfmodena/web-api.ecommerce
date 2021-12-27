<?

if( @$_REQUEST["c"] != "" ){
	if( $_REQUEST["c"] == "maksimal_usia_produk" ){
		// dalam satuan tahun
		echo json_encode( array( "maksimal_usia_produk" => 3) );
	}
	exit;
}

function new_contactus_ticket(){
	$sql="select concat('MKS',day(now()),month(now()),right(year(now()),2),'.',right(rand(),3)) as contactus_no;";
	$rs_contactus_number=mysql_query($sql) or die();//("new_contactus_ticket query error.<br />".mysql_error());
	$rs_contactus_number_=mysql_fetch_array($rs_contactus_number);
	$check_contactus_number=mysql_query("select 1 from `contact us` where id='".$rs_contactus_number_["contactus_no"]."';");
	if(mysql_num_rows($check_contactus_number)>0)
		new_contactus_ticket();
	else
		return $rs_contactus_number_["contactus_no"];	
}

function nomor_kontrak_servis(){
	$date = getdate();
	$nomor = "CM/KS/" . ($date["mon"] < 10 ? "0" . $date["mon"] : $date["mon"]) . $date["year"] . "-" . rand(0,1000);
	if(sql::num_rows( sql::execute( "select 1 from kontrak_servis_registrasi where kontrak_servis_registrasi_nomor = '". $nomor ."';" ) ) > 0)
		nomor_kontrak_servis();
	else
		return $nomor;
}

$_POST = $_REQUEST;

$identitas_konsumen = sql::fetch_array( sql::execute("select membercode, name, email, address, region, state, phone, handphone, a.homeregion, b.state_id , homepostcode 
			from membersdata a inner join shipment_exception b on a.homeregion = b.region_id 
			inner join shipment_state c on b.state_id = c.state_id where 
			MemberID = '". main::formatting_query_string( $_POST["konsumen_id"] ) ."' ") );
$nomor_registrasi_kontrak_servis = 	nomor_kontrak_servis();
		
// insert ke data registrasi kontrak servis (kontrak_servis_registrasi)
unset( $sql_insert );
$sql_insert["kontrak_servis_registrasi_memberid"] = $_POST["konsumen_id"];
$sql_insert["kontrak_servis_registrasi_nomor"] = $nomor_registrasi_kontrak_servis;
$kontrak_servis_registrasi_id = sql::__insert( "kontrak_servis_registrasi", $sql_insert, "kontrak_servis_registrasi_id" );

// entri data pembelian produk penunjang, insert ke tabel kontrak_servis_produk
$arr_produk_penunjang = explode("|", $_REQUEST["minat_produk_penunjang"]);
$counter = 1;
$template_produk_penunjang = file_get_contents("ws/template/kontrak_servis_produk_penunjang.html");
$string_data_produk_penunjang = "";
foreach( $arr_produk_penunjang as $produk_penunjang ){
	if( trim( $produk_penunjang ) == "" ) continue;
	$produk_penunjang_underscore = str_replace(" ", "_", $produk_penunjang);
	unset( $sql_insert, $arr_rpl );
	$arr_rpl["#counter#"] = $counter;
	$arr_rpl["#nama_produk#"] = $produk_penunjang;
	$arr_rpl["#harga_produk#"] = number_format($_REQUEST["harga_" . $produk_penunjang_underscore ]);
	$arr_rpl["#qty_produk#"] = $_REQUEST["qty_" . $produk_penunjang_underscore ];
	$arr_rpl["#subtotal_produk#"] = number_format ( $_REQUEST["qty_" . $produk_penunjang_underscore ] * $_REQUEST["harga_" . $produk_penunjang_underscore ] );
	$string_data_produk_penunjang .= str_replace( array_keys( $arr_rpl ), array_values( $arr_rpl ), $template_produk_penunjang );
	
	$sql_insert["kontrak_servis_registrasi_id"] = $kontrak_servis_registrasi_id;
	$sql_insert["kontrak_servis_produk"] = $produk_penunjang;
	$sql_insert["kontrak_servis_produk_harga"] = $_REQUEST["harga_" . $produk_penunjang_underscore ];
	$sql_insert["kontrak_servis_produk_qty"] = $_REQUEST["qty_" . $produk_penunjang_underscore ];
	sql::__insert( "kontrak_servis_produk", $sql_insert );
	$counter++;
}


// insert ke data contact us (tabel `contact us`) dan data kontrak servis (tabel kontrak_servis) per item produk				
$counter = 1;
$template_produk = file_get_contents("ws/template/kontrak_servis_produk.html");
$string_data_produk = "";
for( $x = 0; $x < $_POST["jumlah_produk"]; $x++ ){

	$identitas_produk = sql::fetch_array( sql::execute( "select MembersProductID, Product, SerialNumber, PurchaseAt,  cast(PurchaseDate as date) PurchaseDate 
			from membersproduct where membersproductid = '". main::formatting_query_string( $_POST["hd_membersproductid_" . $x] ) ."' " ) );
	
	$isi = "<strong>Pendaftaran kontrak servis untuk produk: ". trim($identitas_produk["Product"]) ."	[serial no. ". $identitas_produk["SerialNumber"] ."]</strong>" .
		"<br /><strong>Nomor registrasi : " . $nomor_registrasi_kontrak_servis . "</strong>" .
		"<br />Catatan: " . $_POST["t_keterangan"] . 
		"<!-- {\"serial_no\":\"". $identitas_produk["SerialNumber"] ."\",  \"catatan\":\"". $_POST["t_keterangan"] ."\"} -->";
	
	unset( $sql_insert, $arr_rpl );

	$sql_insert["id"] = new_contactus_ticket();
	$sql_insert["type"] = 0;
	$sql_insert["memberid"] = $_POST["konsumen_id"];
	$sql_insert["nama"] = $identitas_konsumen["name"];
	$sql_insert["email"] = $identitas_konsumen["email"];
	$sql_insert["alamat"] = $identitas_konsumen["address"];
	$sql_insert["kota"] = $identitas_konsumen["region"];
	$sql_insert["state"] = $identitas_konsumen["state_id"];
	$sql_insert["country"] = "Indonesia";
	$sql_insert["postcode"] = $identitas_konsumen["homepostcode"];
	$sql_insert["telepon"] = $identitas_konsumen["phone"];
	$sql_insert["handphone"] = $identitas_konsumen["handphone"];
	$sql_insert["subject"] = "[modena.co.id] Service Contract Registration :: " . $sql_insert["id"];
	$sql_insert["isi"] = $isi;
	
	if( $_POST["hd_ubah_lokasi_" . $x] == "1" ){
		
		$sql_insert["alamat"] = $_POST["t_alamat_" . $x];
		$sql_insert["kota"] = $_POST["s_kota_" . $x];
		$sql_insert["state"] = $_POST["s_propinsi_" . $x];
		$sql_insert["postcode"] = $_POST["t_kodepos_" . $x];
		$sql_insert["telepon"] = $_POST["t_telepon_" . $x];
		$sql_insert["handphone"] = $_POST["t_telepon_selular_" . $x];
		
	}
	sql::__insert( "`contact us`", $sql_insert );
	
	unset( $sql_insert );
	$sql_insert["kontrak_servis_registrasi_id"] = $kontrak_servis_registrasi_id;
	$sql_insert["kontrak_servis_nomor"] = "";
	$sql_insert["kontrak_servis_produk_nomor_seri"] = $identitas_produk["SerialNumber"];
	$sql_insert["kontrak_servis_produk_atas_nama"] = $identitas_konsumen["name"];
	$sql_insert["kontrak_servis_produk_alamat"] = $identitas_konsumen["address"];
	$sql_insert["kontrak_servis_produk_kota"] = $identitas_konsumen["homeregion"];
	$sql_insert["kontrak_servis_produk_propinsi"] = $identitas_konsumen["state_id"];
	$sql_insert["kontrak_servis_produk_kodepos"] = $identitas_konsumen["homepostcode"];
	$sql_insert["kontrak_servis_produk_telepon"] = $identitas_konsumen["phone"];
	$sql_insert["kontrak_servis_produk_handphone"] = $identitas_konsumen["handphone"];
	$sql_insert["kontrak_servis_registrasi_keterangan"] = $_POST["t_keterangan"];
	$sql_insert["kontrak_servis_cabang"] = "";
	
	if( $_POST["hd_ubah_lokasi_" . $x] == "1" ){
		$sql_insert["kontrak_servis_produk_alamat"] = $_POST["t_alamat_" . $x];
		$sql_insert["kontrak_servis_produk_kota"] = $_POST["s_kota_" . $x];
		$sql_insert["kontrak_servis_produk_propinsi"] = $_POST["s_propinsi_" . $x];
		$sql_insert["kontrak_servis_produk_kodepos"] = $_POST["t_kodepos_" . $x];
		$sql_insert["kontrak_servis_produk_telepon"] = $_POST["t_telepon_" . $x];
		$sql_insert["kontrak_servis_produk_handphone"] = $_POST["t_telepon_selular_" . $x];
	}
	
	$kontrak_servis_id = sql::__insert( "kontrak_servis", $sql_insert, "kontrak_servis_id" );
	
	$sql = "select a.*, b.region, c.state from kontrak_servis a inner join shipment_exception b on a.kontrak_servis_produk_kota = b.region_id 
			inner join shipment_state c on b.state_id = c.state_id where a.kontrak_servis_id = '". $kontrak_servis_id ."';";
	$data_kontrak_servis = sql::fetch_array( sql::execute( $sql ) );
	
	list($tahun_pembelian, $bulan_pembelian, $tanggal_pembelian) = explode("-", $identitas_produk["PurchaseDate"]);
	$arr_rpl["#counter#"] = $counter;
	$arr_rpl["#nama_produk#"] = trim($identitas_produk["Product"]);
	$arr_rpl["#nomor_seri#"] = $identitas_produk["SerialNumber"];
	$arr_rpl["#tanggal_pembelian#"] = implode("/", array($tanggal_pembelian, $bulan_pembelian , $tahun_pembelian)) ;
	$arr_rpl["#lokasi_pembelian#"] = $identitas_produk["PurchaseAt"];
	$arr_rpl["#lokasi_produk#"] = implode(" ", array( $data_kontrak_servis["kontrak_servis_produk_alamat"],$data_kontrak_servis["region"], $data_kontrak_servis["state"], $data_kontrak_servis["kontrak_servis_produk_kodepos"]) ) . "<br />Telp : " . 
						implode("|" , array( $data_kontrak_servis["kontrak_servis_produk_telepon"],$data_kontrak_servis["kontrak_servis_produk_handphone"] ) ) ;
	$string_data_produk .= str_replace( array_keys( $arr_rpl ), array_values( $arr_rpl ), $template_produk );

	
	$counter++;
}

$date = new DateTime();

// kirim email service request ke CS
$template = file_get_contents("ws/template/email_kontrak_servis.html");
unset($arr_rpl);
$arr_rpl["#nomor_registrasi#"] = $nomor_registrasi_kontrak_servis;
$arr_rpl["#nama_konsumen#"] = $identitas_konsumen["name"];
$arr_rpl["#email_konsumen#"] = $identitas_konsumen["email"];
$arr_rpl["#alamat_konsumen#"] = implode(" ", array($identitas_konsumen["address"], $identitas_konsumen["region"], $identitas_konsumen["state"], $identitas_konsumen["homepostcode"]));
$arr_rpl["#telepon/hp_konsumen#"] = implode(" | ", array($identitas_konsumen["phone"], $identitas_konsumen["handphone"] ));
$arr_rpl["#tanggal_registrasi#"] = $date->format("d/m/Y");
$arr_rpl["#data_produk#"] = $string_data_produk;
$arr_rpl["#data_produk_penunjang#"] = $string_data_produk_penunjang;
$konten = str_replace( array_keys($arr_rpl), array_values( $arr_rpl ), $template );
main::send_email(CUSTOMERCARE_EMAIL, "[ciao MODENA] Registrasi Kontrak Servis No. " . $nomor_registrasi_kontrak_servis, $konten);

$json = array("tanggal" => $date->getTimestamp(), "nomor_registrasi" => $nomor_registrasi_kontrak_servis);
echo json_encode($json);

?>