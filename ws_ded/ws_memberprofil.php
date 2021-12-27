<?

$_POST = $_REQUEST;
$arr_data = array();

if( @$_REQUEST["c"] == "simpan" ){
	$sql = "SELECT * FROM `membersdata` where Email='". main::formatting_query_string( $_POST["email"] ) ."' and MemberID<>'". main::formatting_query_string( $_POST["memberid"] ) ."'" ;
	
	$rs = mysql_query( $sql ) or die( mysql_error() );
	if( mysql_num_rows( $rs ) > 0 ){
		$arr_data["error"] = "Email sudah digunakan, silahkan pilih email lain!";
		goto Skip_all;
	}
	$arr_update["name"] = main::formatting_query_string( $_POST["txt_nama"] );
	$arr_update["email"] = main::formatting_query_string( $_POST["email"] );
	$arr_update["address"] = main::formatting_query_string( $_POST["txt_alamat"] );
	$arr_update["homepostcode"] = main::formatting_query_string( $_POST["txt_kodepos"] );
	$arr_update["homeregion"] = main::formatting_query_string( $_POST["s_kota"] );
	$arr_update["homestate"] = main::formatting_query_string( $_POST["s_propinsi"] );
	$arr_update["phone"] = main::formatting_query_string( $_POST["txt_telepon"] );
	$arr_update["handphone"] = main::formatting_query_string( $_POST["txt_telepon_selular"] );
	if( $_POST["txt_password_2"] != "" )	$arr_update["password"] = main::formatting_query_string( $_POST["txt_password_2"] );
	$arr_parameter["memberid"] = array("=", "'". main::formatting_query_string( $_REQUEST["memberid"] ) ."'");
	sql::__update("membersdata", $arr_update, $arr_parameter);
	
	$lang = "id";
	if( in_array( @$_REQUEST["lg"], array("id", "en")) ) $lang = $_REQUEST["lg"];
	$arr_status["id"]= "Data berhasil diperbarui";
	$arr_status["en"]= "Successfully save changes";
	
	$arr_data["status"] = $arr_status[ $lang ];
	goto Skip_all;
}

$sql = "select membercode, ifnull(name, '') name, email, ifnull(address, '') address, ifnull(region, '') region, ifnull(state, '') state, ifnull(phone, '') phone, ifnull(handphone, '') handphone , ifnull(trim(b.state_id), '') state_id, ifnull(trim(b.region_id), '') region_id, homepostcode 
				from membersdata a left outer join shipment_exception b on a.homeregion = b.region_id 
				left outer join shipment_state c on b.state_id = c.state_id where 
				MemberID = '". main::formatting_query_string( $_REQUEST["memberid"] ) ."' " ;
$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = $data;

Skip_all:
echo json_encode( $arr_data );

?>