<?

$_POST = $_REQUEST;
$arr_data = array();

$sql = "select c.*, 
	case when isnull(b.nama) or trim(b.nama) = '' then a.name else b.nama end nama,  
	case when isnull(b.email) or trim(b.email) = '' then trim(replace(replace(a.email, ' ',''), '\n','')) else trim(replace(replace(b.email, ' ',''), '\n','')) end email, 
	case when isnull(b.alamat) or trim(b.alamat) = '' then a.address else b.alamat end alamat,   
	case when isnull(b.kota_string) or trim(b.kota_string) = '' then d.region else b.kota_string end kota, 
	case when isnull(b.propinsi_string) or trim(b.propinsi_string) = '' then  e.state else b.propinsi_string end propinsi, 
	case when isnull(b.telp) or trim(b.telp) = '' then  a.Phone else b.telp end telp, 
	case when isnull(b.hp) or trim(b.hp) = '' then a.HandPhone else b.hp end hp, 
	b.purchaseat, b.purchasedate, b.membersproductid, b.tanggal_registrasi, b.product
				from membersdata a 
				inner join membersproduct b on a.memberid = b.memberid 
				inner join sellout_klaim c on b.membersproductid = c.membersproductid
                left outer join shipment_exception d on a.HomeRegion = d.region_id and a.HomeState = d.state_id
                left outer join shipment_state e on a.HomeState = e.state_id
				where  (trim(replace(replace(a.email, ' ',''), '\n','')) = '". main::formatting_query_string( trim($_REQUEST["email"]) ) ."'  or trim(replace(replace(b.email, ' ',''), '\n','')) = '". main::formatting_query_string( trim($_REQUEST["email"]) ) ."' )
				and trim(b.serialnumber) =  '". main::formatting_query_string( trim($_REQUEST["serialnumber"]) ) ."' " ;

$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = $data;

Skip_all:
echo json_encode( $arr_data );

?>
