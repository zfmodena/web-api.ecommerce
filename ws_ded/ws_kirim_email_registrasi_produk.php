<?

include_once("../lib/var.php");
include_once("../lib/cls_main.php");
include_once("sql.php");
define("__AIR_URL__", "http://air.modena.co.id/csapps/");
//error_reporting(E_ALL);

if( @$_POST["tm"] == ""  )
	die(
	"<form method=\"post\">Isikan data membersproductid : <textarea name=\"tm\"></textarea><input type=\"submit\" value=\"Kirim\" /></form>"
	);

$_POST = $_REQUEST;
include_once __DIR__ . "/../lang/id.php";
	
// load data product + info klaim
foreach( explode(",", $_POST["tm"]) as $membersproductid ){
	
	$membersproductid = trim($membersproductid);
	if( $membersproductid == "" ) continue;
	
	$arr_file_faktur_pembelian =glob("../upload/garansi/*". trim($membersproductid) ."*.jpg");
	if(@$arr_file_faktur_pembelian[0] != ""){
		$arr_membersproductid = explode("_", str_replace(array(".jpg", "../upload/garansi/"), "", $arr_file_faktur_pembelian[0]));
		$str_membersproductid = implode(",", $arr_membersproductid);
	}else $str_membersproductid = $membersproductid;
	
	
	$sql = "select a.*, date_format(a.PurchaseDate, '%d %M %Y') tanggal_format, c.klaim, c.klaim_info from membersproduct a inner join membersdata b on a.memberid = b.memberid left outer join sellout_klaim c on a.membersproductid = c.membersproductid where a.membersproductid in (". main::formatting_query_string( $str_membersproductid ) .")";
	$rs_produk = mysql_query($sql);

	$memberid = "";
	unset($arr_list_registrasi);
	while( $data_produk = mysql_fetch_array($rs_produk) ){
		$memberid = $data_produk["MemberID"];
		$arr_list_registrasi[] = array("produk" => $data_produk["Product"], "barcode" => $data_produk["SerialNumber"], "tempat_pembelian" => $data_produk["PurchaseAt"], "tanggal_pembelian" => $data_produk["tanggal_format"]);
		$arr_klaim_sellout = array("sel_metode_klaim" => $data_produk["klaim"], "klaim_info" => $data_produk["klaim_info"]);
	}
		
			// cek utk campaign sellout
	$arr_info_metode_sellout_klaim = array();
	if( $arr_klaim_sellout["sel_metode_klaim"] > 0 ){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=sellout_metode_klaim&sc=validasi_sellout_klaim&metode_klaim=" . $arr_klaim_sellout["sel_metode_klaim"]);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, true);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		$server_output = json_decode($server_output, true);
		$arr_info_metode_sellout_klaim = $server_output["wajib_diisi"];
		$arr_info_metode_sellout_klaim_label = $server_output["wajib_diisi_label"];
				
		$str_info_metode_sellout_klaim_terpilih = array();
		foreach( explode("|", $arr_klaim_sellout["klaim_info"]) as $index => $info_metode_sellout_klaim)
			$str_info_metode_sellout_klaim_terpilih[] = $arr_info_metode_sellout_klaim_label[ $index ] . " : " . $info_metode_sellout_klaim;
		$str_info_metode_sellout_klaim_terpilih = implode("<br />", $str_info_metode_sellout_klaim_terpilih);
			
	}
		
		$date = new DateTime();

		// kirim email garansi ke CS
		$identitas_konsumen = sql::fetch_array( sql::execute("select membercode, name, email, address, region, state, phone, handphone, a.homeregion, b.state_id , homepostcode 
					from membersdata a left outer join shipment_exception b on a.homeregion = b.region_id 
					left outer join shipment_state c on b.state_id = c.state_id 
					where 
					a.MemberID = '". main::formatting_query_string( $memberid ) ."' ") );
					
		$template = file_get_contents("template/email_registrasi_produk.html");
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
		
		// append utk surat pernyataan
		$string_url_suratpernyataan = "";
		$arr_lampiran["garansi"] = __DIR__ . "/../upload/garansi/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]);
		
		$file_ktp = __DIR__ . "/../upload/ktp/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]);
		$file_selfie = __DIR__ . "/../upload/selfie/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]);
		if( file_exists($file_ktp) )    $arr_lampiran["ktp"] = $file_ktp;
		if( file_exists($file_selfie) ) $arr_lampiran["selfie"] = $file_selfie;
		    
		if( file_exists(__DIR__ . "/../upload/suratpernyataan/". str_replace("../upload/suratpernyataan/", "", str_replace("garansi","suratpernyataan",@$arr_file_faktur_pembelian[0]))) ){
			$arr_lampiran["suratpernyataan"] = __DIR__ . "/../upload/suratpernyataan/". str_replace("../upload/suratpernyataan/", "", str_replace("garansi","suratpernyataan",@$arr_file_faktur_pembelian[0]));
			$string_url_suratpernyataan = "<div style=\"margin-top:13px\"><h4>Surat pernyataan ketentuan program cashback MODENA</h4><img src='". $url_path ."/upload/suratpernyataan/". str_replace("../upload/suratpernyataan/", "", str_replace("garansi","suratpernyataan",@$arr_file_faktur_pembelian[0])) ."' style='max-width:100%; padding:7px 0px 7px 0px' /></div>";
		}
		
		$arr_rpl["#faktur_pembelian#"] = "<img src='". $url_path ."/upload/garansi/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]) ."' style='max-width:100%; padding:7px 0px 7px 0px' />
		                <h3>FOTO KTP</h3>
			            <img src='". $url_path ."/upload/ktp/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]) ."' style='max-width:100%; padding:7px 0px 7px 0px' />
			            <h3>FOTO SELFIE</h3>
			            <img src='". $url_path ."/upload/selfie/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]) ."' style='max-width:100%; padding:7px 0px 7px 0px' />
		" . $string_url_suratpernyataan;

		$konten = str_replace( array_keys($arr_rpl), array_values( $arr_rpl ), $template );
	//	echo CUSTOMERCARE_EMAIL;
		//echo $konten;
		//kirim_email(CUSTOMERCARE_EMAIL, "[ciao MODENA] Copy - New Product Warranty", $konten, __DIR__ . "/../upload/garansi/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]) );
		kirim_email(CUSTOMERCARE_EMAIL, "[ciao MODENA] Copy - New Product Warranty", $konten, $arr_lampiran, $identitas_konsumen["email"] );
		kirim_email("modena@mail.qiscus.com", "[ciao MODENA] Copy - New Product Warranty", $konten, $arr_lampiran, $identitas_konsumen["email"] );
		
		
		if( @$_POST["kirim_customer"] != "" ){
			// kirim email terimakasih ke konsumen, garansi sedang diverifikasi
			$template = file_get_contents("template/konsumen_email_registrasi_produk.html");
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
			
			$arr_rpl["#faktur_pembelian#"] = "<img src='". $url_path ."/upload/garansi/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]) ."' style='max-width:100%; padding:7px 0px 7px 0px' />
			            <h3>FOTO KTP</h3>
			            <img src='". $url_path ."/upload/ktp/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]) ."' style='max-width:100%; padding:7px 0px 7px 0px' />
			            <h3>FOTO SELFIE</h3>
			            <img src='". $url_path ."/upload/selfie/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]) ."' style='max-width:100%; padding:7px 0px 7px 0px' />
			";

			$konten = str_replace( array_keys($arr_rpl), array_values( $arr_rpl ), $template );
			
			if( filter_var( $identitas_konsumen["email"], FILTER_VALIDATE_EMAIL) )
				//main::send_email($identitas_konsumen["email"], "[ciao MODENA] New Product Warranty", $konten);
				//kirim_email($identitas_konsumen["email"], "[ciao MODENA] Terimakasih atas pendaftaran produk Anda", $konten, __DIR__ . "/../upload/garansi/". str_replace("../upload/garansi/", "", @$arr_file_faktur_pembelian[0]) );
				kirim_email($identitas_konsumen["email"], "[ciao MODENA] Terimakasih atas pendaftaran produk Anda", $konten, $arr_lampiran, CUSTOMERCARE_EMAIL );
			
		}
	
}

function kirim_email($target=SUPPORT_EMAIL,$subject="",$content="", $lampiran=array(), $par_from = ""){
	$message=file_get_contents("../template/email.html");	

	$message=str_replace("#greetingname#", $target, $message);
	$message=str_replace("#content#", $content, $message);
	$message=str_replace("#lang_email_info_en#", "", $message);
	$message=str_replace("#lang_email_footer#", "", $message);
	$message=str_replace("#horizontal_line#", "<hr width=100% size=1 />", $message);

	if($target=="")$target=SUPPORT_EMAIL;
	$subject = $subject;
	$to["To"]=$target;
// $to["Bcc"]=SUPPORT_EMAIL;
	$to["Bcc"]="dedi.supatman@modena.com,support.it@modena.com,".CUSTOMERCARE_EMAIL;
	$recipient_header_to=$target;
	$from=CUSTOMERCARE_EMAIL;
	if( $par_from != "" && filter_var( $par_from, FILTER_VALIDATE_EMAIL) )
	    $from = $par_from;

	ini_set("include_path",__DIR__ . "/../lib/pear");
	require_once "../lib/pear/Mail.php";
	require_once "../lib/pear/mime.php";		
	
	$mime=new Mail_mime(array('eol' => CRLF));
	$mime->setTXTBody(strip_tags($message));
	$mime->setHTMLBody($message);	
	//if( is_array($lampiran) ){
		foreach( $lampiran as $string_lampiran )
			$mime->addAttachment($string_lampiran);	
	//}else{
	//	if ($lampiran != "") $mime->addAttachment($lampiran);	
	//}
	
	$headers = array ('From' => $from,
		'To' => $recipient_header_to, 
		'Subject' => $subject);		
	$smtp=Mail::factory('smtp',
		array ('host' => SMTP_HOST,
		'auth' => SMTP_AUTH,
		'username' => SMTP_USERNAME,
		'password' => SMTP_PASSWORD));		
					
	$body=$mime->get();
	$headers_=$mime->headers($headers);		

	$mail = $smtp->send($to, $headers_, $body);
	$pear=new PEAR;
	if ($pear->isError($mail))echo "Email Error. " . $mail->getMessage();
	restore_include_path ();
	//mail($to, $subject, $message, $headers);		
	//return $message;
}
header("location:http://www.modena.co.id/ws/ws_kirim_email_registrasi_produk.php");

?>
