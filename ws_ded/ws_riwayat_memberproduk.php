<?

$arr_data = array();
$lang = "id";
if( in_array( @$_REQUEST["l"], array("id", "en")) ) $lang = $_REQUEST["l"];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "lang/lang.php");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$server_output = curl_exec ($ch);
$server_output = json_decode($server_output, true);

$sql = "
	select * from (
		select '". $server_output[$lang]["index"]["label_registrasi"] ."' `mode`, concat('". $server_output[$lang]["index"]["label_pembelian_dari"] ." ', purchaseat) detail_mode, cast(PurchaseDate as date) tanggal_transaksi from membersproduct where membersproductid = ". main::formatting_query_string( $_GET["membersproductid"] ) ." UNION
		select '". $server_output[$lang]["index"]["label_servis"] ."', concat(isi, '<div style=\"padding:7px 0px 7px 0px\">". $server_output[$lang]["index"]["label_info_kontak"] .": ', nama, '<br />', alamat, ' ', case when ifnull(c.region, '') = '' then kota else c.region end, ' ', ifnull(d.state, ''), '<br />". $server_output[$lang]["index"]["label_telepon"] .": ', telepon, ' - ', handphone, '</div>') ,tanggal 
			from `contact us` a inner join membersproduct b 
			left outer join shipment_exception c on a.kota = c.region_id
			left outer join shipment_state d on a.state = d.state_id
			where a.memberid = b.memberid and b.membersproductid = ". main::formatting_query_string( $_GET["membersproductid"] ) ." and isi like concat('%', b.serialnumber, '%')
	) a order by tanggal_transaksi " . ( in_array( strtoupper(trim(@$_GET["order"])), array("ASC", "DESC") ) ? strtoupper(trim(@$_GET["order"])) : "DESC" ) ;

$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[] = array("mode"=>$data["mode"], "detail_mode"=>$data["detail_mode"], "tanggal_transaksi" => $data["tanggal_transaksi"] );

echo json_encode( $arr_data );

	
?>