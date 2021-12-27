<?

$sql = "select region_id, region, state from shipment_exception a, shipment_state b where a.state_id = b.state_id and b.enabled = 1 and a.enabled = 1 and a.state_id = '". main::formatting_query_string( $_REQUEST["propinsi_id"] ) ."' " 
	. ( @$_REQUEST["rid"] != "" ? " and region_id = '". main::formatting_query_string( $_REQUEST["rid"] ) ."' " : "" );
	
$rs = mysql_query( $sql );
while( $data = mysql_fetch_array( $rs ) ){
    // print("<pre>".print_r($data,true)."</pre>");
	$arr_data[] = array("kota"=>$data["region"], "kota_id"=>$data["region_id"], "propinsi" => $data["state"] );
}
echo json_encode( $arr_data );

?>