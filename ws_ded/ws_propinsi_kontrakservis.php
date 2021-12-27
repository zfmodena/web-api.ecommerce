<?

$sql = "select distinct service_state_id state_id, b.state from branch_service a, shipment_state b where a.service_state_id = b.state_id and b.enabled = 1 and a.kontrak_servis = 1";
$rs = mysql_query( $sql );
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = array("propinsi"=>$data["state"], "propinsi_id"=>$data["state_id"]);

echo json_encode( $arr_data );

?>