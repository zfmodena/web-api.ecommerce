<?


$arr_data = array("langganan_id" => "");

if( isset( $_REQUEST["mode"] ) ){
	
	$rs_data_langganan = sql::execute( "select * from push_langganan where apps = '". main::formatting_query_string( $_REQUEST["apps"] ) ."' and 
		langganan_id = '". main::formatting_query_string( $_REQUEST["registrasionid"] ) ."' and email = '". main::formatting_query_string( $_REQUEST["email"] ) ."' " );
	if( sql::num_rows( $rs_data_langganan ) > 0 ){
		
		$arr_mode = array( 0 => "logout", "login" );
		
		if( in_array($_REQUEST["mode"], $arr_mode ) )
			sql::execute( "update push_langganan set status_langganan = ". array_search( $_REQUEST["mode"], $arr_mode ) ." where apps = '". main::formatting_query_string( $_REQUEST["apps"] ) ."' and 
				langganan_id = '". main::formatting_query_string( $_REQUEST["registrasionid"] ) ."' and email = '". main::formatting_query_string( $_REQUEST["email"] ) ."' " );
		
		$arr_data["langganan_id"] = $_REQUEST["registrasionid"];
	}
		
	
}else{
	
	unset($sql_insert);
	$sql_insert["langganan_id"] = $_REQUEST["registrasionid"];
	$sql_insert["apps"] = $_REQUEST["apps"];
	$sql_insert["email"] = $_REQUEST["email"];

	print("<pre>".print_r($sql_insert,true)."</pre>");
	sql::__insert( "push_langganan", $sql_insert );
	
}

echo json_encode( $arr_data );
?>