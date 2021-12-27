<?

$_POST = $_REQUEST;
$arr_data = array();

$sql = "select * from sellout_klaim where membersproductid = '".  main::formatting_query_string( $_REQUEST["membersproductid"] )  ."'";
$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = $data;

echo json_encode( $arr_data );
?>