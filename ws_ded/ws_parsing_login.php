<?

$arr_data = array();

if(@$_REQUEST["fbid"] === ""){
	$sql = "select * from membersdata where email = '". trim(main::formatting_query_string( @$_REQUEST["e"] ) ) ."' " ;
}else{
	$sql = "select * from membersdata where fbid = '". trim(main::formatting_query_string( @$_REQUEST["fbid"] ) ) ."' or email = '". trim(main::formatting_query_string( @$_REQUEST["e"] ) ) ."' " ;
}

$rs = mysql_query( $sql ) or die( mysql_error() );

if( mysql_num_rows( $rs ) > 0 ){
	
	while( $data = mysql_fetch_array( $rs ) )
		$arr_data[] = array("konsumen_id"=>$data["MemberID"], "email"=>$data["Email"], "nama" => $data["Name"] );
	echo json_encode( $arr_data );
	exit;
	
}else{
	$_REQUEST["p"] = "";
	if( @$_REQUEST["src"] == "" ) $_REQUEST["src"] = "CM";
	include_once "ws_register.php";
}
	
?>