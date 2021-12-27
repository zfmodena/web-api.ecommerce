<?

$sql = "select state_id, state from shipment_state where enabled = 1 " . ( @$_REQUEST["pid"] != "" ? " and state_id = '". main::formatting_query_string( $_REQUEST["pid"] ) ."' " : "" );
$rs = mysql_query( $sql );
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = array("propinsi"=>$data["state"], "propinsi_id"=>$data["state_id"]);

echo json_encode( $arr_data );

?>