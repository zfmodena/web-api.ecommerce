<?

$arr_data = array();

$sql = "select b.region_id, b.region, c.state from branch_service a, shipment_exception b, shipment_state c where 
	a.service_state_id = b.state_id and a.service_region_id = b.region_id and b.state_id  = c.state_id and
	b.enabled = 1 and a.kontrak_servis = 1 and b.state_id = '". main::formatting_query_string( $_REQUEST["propinsi_id"] ) ."' " 
	. ( @$_REQUEST["rid"] != "" ? " and b.region_id = '". main::formatting_query_string( $_REQUEST["rid"] ) ."' " : "" );
	
$rs = mysql_query( $sql );
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = array("kota"=>$data["region"], "kota_id"=>$data["region_id"], "propinsi" => $data["state"] );

echo json_encode( $arr_data );

?>