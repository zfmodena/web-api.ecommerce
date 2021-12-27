<?
$arr_data = array();

if( @$_REQUEST["c"] == "reset_password" ){
	include_once "lib/cls_member.php";	
	$member=new member;
	$member->enabled="=true";
	$member->email="='".main::formatting_query_string($_REQUEST["e"])."'";
	$rs_member_data=$member->member_data("%");
	if(mysql_num_rows($rs_member_data)>0){
		include_once "lib/cls_message.php";include_once "lang/".$lang.".php";				
		$rs_member_data_=mysql_fetch_array($rs_member_data);
		
		$contactus_ticket=message::new_contactus_ticket();
		
		$message=new message;
		$message->contactus_no=$contactus_ticket;
		$message->contactus_type="0";
		$message->name=$rs_member_data_["name"];
		$message->member_email=$rs_member_data_["email"];
		$message->email=$rs_member_data_["email"];
		$message->alamat=$rs_member_data_["address"];
		$message->kota=$rs_member_data_["homecity"];
		$message->state=$rs_member_data_["homestate"];
		$message->country=$rs_member_data_["homecountry"];
		$message->postcode=$rs_member_data_["homepostcode"];
		$message->phone=$rs_member_data_["phone"];
		$message->handphone=$rs_member_data_["handphone"];		
		$message->contactus_subject="[modena.co.id] Password Request :: ".$contactus_ticket;		
		$s_message=str_replace("#email#", $rs_member_data_["email"], $lang_forgot_password_information);
		$s_message=str_replace("#password#", $rs_member_data_["password"], $s_message);
		$s_message=str_replace("#path#", $url_path, $s_message);
		$message->contactus=@$s_message;
		$message->__insert_contactus();
		
		//get message lagi
		unset($message);
		$message=new message;
		$message->contactus_no=$contactus_ticket;
		$message->contactus_email="='".main::formatting_query_string($rs_member_data_["email"])."'";
		$rs_message=$message->get_contactus();
		if(mysql_num_rows($rs_message)>0){
			$rs_message_=mysql_fetch_array($rs_message);
			//kirim email ke member
			$message->__set("lang", "lang/".$lang.".php");
			$message->__set("email_template", "template/email.html");
			$message->__set("content_template", "template/contactus.html");
			$message->__set("email_subject", $rs_message_["subject"]);
			$message->__set("id", $rs_message_["id"]);
			$message->__set("contactus", $rs_message_["isi"]);
			$message->__set("nama", $rs_message_["nama"]);
			$message->__set("email", $rs_message_["email"]);
			$message->__set("alamat", $rs_message_["alamat"].", ".$rs_message_["kota"].", ".$rs_message_["state"].", ".$rs_message_["country"]." ".$rs_message_["postcode"]);
			$message->__set("phone", $rs_message_["telepon"]."/".$rs_message_["handphone"]);

			$message->contactus_email("customer");
		}
		$return_forgot_password=$lang_forgot_password_email_success;
		$arr_data[] = array("status"=> "ok", "pesan"=> $lang_forgot_password_email_success);
	}else{
		$return_forgot_password=$lang_forgot_password_email_not_exists;
		$arr_data[] = array("status"=> "error", "pesan"=> "Email tidak terdaftar !<br> Silahkan melakukan registrasi.");
	}
	echo json_encode( $arr_data );
	exit;
}elseif( @$_REQUEST["c"] == "reset_password_pin" ){
	include_once "lib/cls_member.php";	
	$member=new member;
	$member->enabled="=true";
	$member->email="='".main::formatting_query_string($_REQUEST["e"])."'";
	$rs_member_data=$member->member_data("%");
	if(mysql_num_rows($rs_member_data)>0){
		include_once "lib/cls_message.php";
		include_once "lang/".$lang.".php";
		$rs_member_data_=mysql_fetch_array($rs_member_data);
		
		$pin = mt_rand(100000, 999999);
		$sql = "insert into forgot_pass (email,pin) values ( '". $rs_member_data_["email"] ."' ,'". $pin ."') ";
    	$rs = mysql_query( $sql );
        
        $msgc=file_get_contents(__DIR__ . "/../lang/".$lang."_pin_forgot.html");
		$ar=array(
			"#name#"=>$rs_member_data_["name"],
			"#pin#"=>$pin
			);
		$content = str_replace( array_keys($ar), array_values($ar), $msgc );
		kirim_email($_REQUEST["e"],"PIN untuk lupa password CIAO",$content);
		
		$arr_data[] = array("status"=> "ok", 
		            "pesan"=> "PIN sudah berhasil dikirimkan ke alamat email Anda.<br />Silahkan akses inbox email Anda.", 
		            "pin" => $pin);
	}else{
		$return_forgot_password=$lang_forgot_password_email_not_exists;
		$arr_data[] = array("status"=> "error", "pesan"=> "Email tidak terdaftar !<br> Silahkan melakukan registrasi.");
	}
	echo json_encode( $arr_data );
	exit;
}elseif(@$_REQUEST["c"] == "cek_pin"){
    $sql = "select * from forgot_pass where email = '". main::formatting_query_string( @$_REQUEST["e"] ) ."' and pin = '". main::formatting_query_string( @$_REQUEST["pin"] ) ."' and flag=0 ";
	$rs = mysql_query( $sql );
    if( mysql_num_rows($rs)){
        $arr_data[] = array("status"=> "ok", "pesan"=>"");
        $sql = "update forgot_pass set flag=1 where email = '". main::formatting_query_string( @$_REQUEST["e"] ) ."' and pin = '". main::formatting_query_string( @$_REQUEST["pin"] ) ."' and flag=0 ";
	    mysql_query( $sql );
    }else{
        $arr_data[] = array("status"=> "error", "pesan"=> "PIN tidak valid");
    }
    echo json_encode( $arr_data );
    exit;
}elseif(@$_REQUEST["c"] == "upp"){
	$dataku=array();
	
	$sql = "select * from membersdata where email = '". main::formatting_query_string( @$_REQUEST["e"] ) ."' " ;
    $rs = mysql_query( $sql ) or die( mysql_error() );
    if( mysql_num_rows( $rs ) > 0 )
    while( $data = mysql_fetch_array( $rs ) ){
        $sql = "update membersdata set Password= '". main::formatting_query_string( @$_REQUEST["nps"] ) ."' where email = '". main::formatting_query_string( @$_REQUEST["e"] ) ."' ";
	    mysql_query( $sql );

    	$dataku[] = array("konsumen_id"=>$data["MemberID"], "email"=>$data["Email"], "nama" => $data["Name"], "alamat" => $data["Address"] , "hp" => $data["HandPhone"]);
    }
    $arr_data[] = array("status"=> "ok", "data"=> $dataku);
    
    echo json_encode( $arr_data );
    exit;

}elseif(@$_REQUEST["c"] == "cek_phone_information"){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "lang/lang.php");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec ($ch);
    $server_output = json_decode($server_output, true);

	$sql = "select * from membersdata where Memberid <> '". main::formatting_query_string( @$_REQUEST["memberid"] ) ."' and HandPhone = '". main::formatting_query_string( @$_REQUEST["hp"] ) ."' ";
	$rs = mysql_query( $sql );
    if( mysql_num_rows($rs)){
        $arr_data[] = array("status"=> "duplikasi", "pesan"=>$server_output[$lang]["login"]["notif_hp_sudah_terdaftar"]);
    }else{
        $arr_data[] = array("status"=> "ok", "pesan"=> "");
    }
    echo json_encode( $arr_data );
    exit;
}

function member_data_information($memberemail, $information = "", $hp = ""){
	$sql = "select ". ($information != "" ? $information : "*") ." from membersdata where email = '". main::formatting_query_string( $memberemail ) ."' ";
	//$sql = "select ". ($information != "" ? $information : "*") ." from membersdata where email = '". main::formatting_query_string( $memberemail ) ."' OR HandPhone = '". main::formatting_query_string( $hp ) ."' ";
	$rs = mysql_query( $sql );
	return $rs;
}

function __generate_membercode($email, $showroom_inisial){
	$date=getdate();
	//format membercode : $_SESSION["showroom_inisial"]MMYY-XXXX
	$rs_membercode=member_data_information($email, "membercode");
	$membercode = mysql_fetch_array( $rs_membercode );
	if(@$membercode["membercode"]!="") return $membercode["membercode"];
	$showroom=$showroom_inisial;
	$sql="select max(substring(membercode,locate('-',membercode)+1))+1 code_sequence from membersdata where (membercode<>'' or membercode is not null) 
		and membercode like '".$showroom."".$date["mon"].$date["year"]."-%'";
	$rs=mysql_query($sql) or die();
	$data=mysql_fetch_array($rs);
	$code_sequence=$data["code_sequence"]!=""?$data["code_sequence"]:"1";
	return $showroom.$date["mon"].$date["year"]."-".str_repeat("0",4-strlen($code_sequence)).$code_sequence;
}

function kirim_email($target=SUPPORT_EMAIL,$subject="",$content="", $lampiran=array()){
	$message=file_get_contents(__DIR__ . "/../template/email.html");	

	$message=str_replace("#greetingname#", $target, $message);
	$message=str_replace("#content#", $content, $message);
	$message=str_replace("#lang_email_info_en#", "", $message);
	$message=str_replace("#lang_email_footer#", "", $message);
	$message=str_replace("#horizontal_line#", "<hr width=100% size=1 />", $message);

	if($target=="")$target=SUPPORT_EMAIL;
	$subject = $subject;
	$to["To"]=$target;
	//$to["Bcc"]=SUPPORT_EMAIL;
	$recipient_header_to=$target;
	$from=SUPPORT_EMAIL;

	ini_set("include_path",__DIR__ . "/../lib/pear");
	require_once __DIR__ . "/../lib/pear/Mail.php";
	require_once __DIR__ . "/../lib/pear/mime.php";		
	
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

$_POST = $_REQUEST;
$lang = "id";
if( in_array( @$_REQUEST["l"], array("id", "en")) ) $lang = $_REQUEST["l"];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "lang/lang.php");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
$server_output = json_decode($server_output, true);

if(@$_POST["hp"] == "") $_POST["hp"] = "";
if( mysql_num_rows( member_data_information( $_POST["e"],"", $_POST["hp"] ) ) > 0 ) 
	$arr_data[] = array("status"=> "duplikasi", "pesan"=>$server_output[$lang]["login"]["notif_email_sudah_terdaftar"]);
else{
	$arr_insert["membercode"] = __generate_membercode( $_POST["e"], $_POST["src"] );
	$arr_insert["email"] = $_POST["e"];
	$arr_insert["password"] = $_POST["p"];
	$arr_insert["fbid"] = $_POST["fbid"];
	if(@$_POST["fbid"] != "") $arr_insert["verifikasi"] = 1;
	
	if(@$_POST["nama"] != "") $arr_insert["name"] = $_POST["nama"];
	if(@$_POST["hp"] != "") $arr_insert["handphone"] = $_POST["hp"];
	
	sql::__insert("membersdata", $arr_insert);

	$rs = member_data_information( $_POST["e"] ) ;
	if( mysql_num_rows( $rs ) > 0 )
	while( $data = mysql_fetch_array( $rs ) ){
		$arr_data[] = array("status"=>"OK", "pesan"=>"OK","konsumen_id"=>$data["MemberID"], "email"=>$data["Email"], "nama" => $data["Name"] );
		
		$msgc=file_get_contents(__DIR__ . "/../lang/".$lang."_greet_member.html");
		$ar=array(
			"#email#"=>$data["Email"],
			"#password#"=>$data["Password"]/*,
			"#nama#"=>$data["name"],
			"#alamat#"=>$data["address"]." ".$data["homecity"]." ".$data["state"]." ".$data["homepostcode"],
			"#telepon#"=>$data["phone"],
			"#handphone#"=>$data["handphone"],
			"#membercard#"=>$data["membercode"]*/
			);
		$content = str_replace( array_keys($ar), array_values($ar), $msgc );
		kirim_email($data["Email"],"Thank you for registering to MODENA",$content);
		break;
	}
}

echo json_encode( $arr_data );
?>