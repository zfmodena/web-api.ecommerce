<?

$arr_data = array();

$sql = "select * from push_langganan_kiriman where 
				apps = '". main::formatting_query_string( $_REQUEST["apps"] ) ."' 
				/*and langganan_id = '". main::formatting_query_string( $_REQUEST["par_konsumen_registrationid"] ) ."' */
				and email = '". main::formatting_query_string( $_REQUEST["email"] ) ."'
				". ( @$_REQUEST["kiriman_id"] != "" ? " and kiriman_id = '". main::formatting_query_string( $_REQUEST["kiriman_id"] ) ."' " : "" ) ."
				". ( @$_REQUEST["status_langganan_kiriman"] != "" ? " and status_langganan_kiriman = '". main::formatting_query_string( $_REQUEST["status_langganan_kiriman"] ) ."' " : "" ) ."
			order by tanggal desc ";

if( @$_REQUEST["kiriman_id"] != "" ){
	$sql_update = "update push_langganan_kiriman set status_langganan_kiriman = 1 
				where 
					apps = '". main::formatting_query_string( $_REQUEST["apps"] ) ."' and 
					/*langganan_id = '". main::formatting_query_string( $_REQUEST["par_konsumen_registrationid"] ) ."'  and */
					email = '". main::formatting_query_string( $_REQUEST["email"] ) ."' and 
					kiriman_id = '". main::formatting_query_string( $_REQUEST["kiriman_id"] ) ."' " ;
	mysql_query( $sql_update );
}			
			
$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = $data;

echo json_encode( $arr_data );

?>