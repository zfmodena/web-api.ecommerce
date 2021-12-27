<?
$arr_data = array();

$sql = "select * from membersdata where (email = '". main::formatting_query_string( @$_REQUEST["e"] ) ."' or HandPhone = '". main::formatting_query_string( @$_REQUEST["e"] ) ."') and password = '". main::formatting_query_string( @$_REQUEST["p"] ) ."' " ;

$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = array("konsumen_id"=>$data["MemberID"], "email"=>$data["Email"], "nama" => $data["Name"], "alamat" => $data["Address"] , "hp" => $data["HandPhone"]);

if( mysql_num_rows($rs)){
    
}else{
    $arr_data= array("error"=> "Email/Handphone dan password salah");
}
echo json_encode( $arr_data );

?>