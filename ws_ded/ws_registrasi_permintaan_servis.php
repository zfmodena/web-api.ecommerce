<?

function new_contactus_ticket(){
	$sql="select concat('MCU',day(now()),month(now()),right(year(now()),2),'.',right(rand(),3)) as contactus_no;";
	$rs_contactus_number=mysql_query($sql) or die();//("new_contactus_ticket query error.<br />".mysql_error());
	$rs_contactus_number_=mysql_fetch_array($rs_contactus_number);
	$check_contactus_number=mysql_query("select 1 from `contact us` where id='".$rs_contactus_number_["contactus_no"]."';");
	if(mysql_num_rows($check_contactus_number)>0)
		new_contactus_ticket();
	else
		return $rs_contactus_number_["contactus_no"];	
}

$_POST = $_REQUEST;

$identitas_konsumen = sql::fetch_array( sql::execute("select membercode, name, email, address, region, state, phone, handphone, a.homeregion, b.state_id , homepostcode 
			from membersdata a inner join shipment_exception b on a.homeregion = b.region_id 
			inner join shipment_state c on b.state_id = c.state_id where 
			MemberID = '". main::formatting_query_string( $_POST["konsumen_id"] ) ."' ") );

$konten_email = array();				
for( $x = 0; $x < $_POST["jumlah_produk"]; $x++ ){

	$identitas_produk = sql::fetch_array( sql::execute( "select MembersProductID, Product, SerialNumber, PurchaseAt,  cast(PurchaseDate as date) PurchaseDate 
			from membersproduct where membersproductid = '". main::formatting_query_string( $_POST["hd_membersproductid_" . $x] ) ."' " ) );
	
	$isi = "<strong>Pendaftaran servis untuk produk: ". trim($identitas_produk["Product"]) ."	[serial no. ". $identitas_produk["SerialNumber"] ."]</strong>" .
		"<br />Keluhan: " . $_POST["t_keluhan_" . $x] .
		"<br />Tanggal servis diinginkan: " . $_POST["t_tanggal_servis_" . $x] . 
		"<br />Catatan: " . $_POST["t_keterangan"] . 
		"<!-- {\"serial_no\":\"". $identitas_produk["SerialNumber"] ."\", \"keluhan\":\"". $_POST["t_keluhan_" . $x] ."\", \"tgl_servis\":\"". $_POST["hd_tanggal_servis_" . $x] ."\", \"catatan\":\"". $_POST["t_keterangan"] ."\"} -->";
	
	$nomor_registrasi = new_contactus_ticket();
	$konten_email[ $nomor_registrasi ] = str_replace("<br />Catatan: " . $_POST["t_keterangan"], "", $isi);
	
	unset( $sql_insert );
	$sql_insert["id"] = $nomor_registrasi;
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
	$sql_insert["subject"] = "[modena.co.id] Service Request :: " . $sql_insert["id"];
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
	
}

$date = new DateTime();

// kirim email service request ke CS
$template = file_get_contents("ws/template/email_servis_request.html");
unset($arr_rpl);
$arr_rpl["#nama_konsumen#"] = $identitas_konsumen["name"];
$arr_rpl["#email_konsumen#"] = $identitas_konsumen["email"];
$arr_rpl["#alamat_konsumen#"] = implode(" ", array($identitas_konsumen["address"], $identitas_konsumen["region"], $identitas_konsumen["state"], $identitas_konsumen["homepostcode"]));
$arr_rpl["#telepon/hp_konsumen#"] = implode(" | ", array($identitas_konsumen["phone"], $identitas_konsumen["handphone"] ));
$arr_rpl["#tanggal_registrasi#"] = $date->format("d/m/Y");
$arr_rpl["#catatan#"] = $_POST["t_keterangan"];

$string_data_produk = "";
foreach( $konten_email as $nomor_registrasi=>$string_konten_email )
	$string_data_produk .= "<h3>Nomor Registrasi $nomor_registrasi</h3><div>$string_konten_email</div>";
$arr_rpl["#daftar_produk#"] = $string_data_produk;

$konten = str_replace( array_keys($arr_rpl), array_values( $arr_rpl ), $template );
main::send_email(CUSTOMERCARE_EMAIL, "[ciao MODENA] Registrasi Service Request", $konten);
main::send_email($identitas_konsumen["email"], "[ciao MODENA] Registrasi Service Request", $konten);


$json = array("tanggal" => $date->getTimestamp());
echo json_encode($json);

?>