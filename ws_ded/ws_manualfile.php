<?

$arr_data = array();

if( @$_REQUEST["c"] != "" ){
	if( @$_REQUEST["c"] == "kirim_email" ){
		include_once "lib/var.php";
		include_once "lib/cls_main.php";
		$template = ""; //file_get_contents("http://www.modena.co.id/template/email.html");
		$konten = file_get_contents("ws/template/email_manualfile.html");
		for($x = 0; $x < $_REQUEST["jumlah_manual"]; $x++){
			if( $_REQUEST["cb_" . $x] != "" )	$manualfileid[] = $_REQUEST["cb_" . $x];
		}
		$sql = "select manualfileid, title, manualfilename, tag from manualfile where typeid in (3,4,5,6,7) and enabled = 1 and manualfileid in (". implode(",", $manualfileid) .")";
		$rs = mysql_query( $sql ) or die( mysql_error() );
		$string_kontenmanualfile = "";
		while( $data = mysql_fetch_array( $rs ) )
			$string_kontenmanualfile .= "<li><a href=\"http://www.modena.co.id/manual/". $data["manualfilename"] ."\" style=\"color:blue\">". $data["title"] ."</a></li>"; 
		
		$konten = str_replace(
										array("#produk#", "#daftar_manual#"),
										array( $_REQUEST["produk"], $string_kontenmanualfile ),
										$konten
									);
		echo str_replace(	array("#greetingname#", "#content#", "#lang_email_info_en#", "#lang_email_footer#"),
											array("Pelanggan", $konten, "", ""),
											$template
										);
		main::send_email($_REQUEST["email"], "Dokumen manual pengguna produk MODENA", $konten);
		echo json_encode( array("notifikasi" => "Email manual pengguna sudah dikirimkan ke " . $_REQUEST["email"]) );
	}
	exit;
}

$arr_tag_produk = explode(" ", $_REQUEST["p"]);
foreach( $arr_tag_produk as $index => $tag_produk ){
	if( $index >= count( $arr_tag_produk ) - 1 && strlen($tag_produk) <= 1 ){}
	else
		$arr_par_tag_produk[] = " tag like '%". main::formatting_query_string( $tag_produk ) ."%' ";
}

$sql = "select manualfileid, title, manualfilename, tag from manualfile where typeid in (3,4,5,6,7) and enabled = 1 and (". implode("or", $arr_par_tag_produk) .")";

$rs = mysql_query( $sql ) or die( mysql_error() );
while( $data = mysql_fetch_array( $rs ) ){
	$arr_data[] = $data;
}

echo json_encode( $arr_data );

?>