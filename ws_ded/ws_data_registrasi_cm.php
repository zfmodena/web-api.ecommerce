<?

$sql = "SELECT upper(trim(a.serialnumber)) serialnumber, a.product, b.email, a.purchasedate, a.tanggal_registrasi, b.name, b.address, b.phone, b.handphone, c.region, d.state  FROM `membersproduct` a inner join membersdata b on a.memberid = b.MemberID inner join shipment_exception c on b.HomeRegion = c.region_id inner join shipment_state d on c.state_id = d.state_id
where date_add( CURRENT_DATE, interval -". (@$_REQUEST["d"] != "" && is_numeric(@$_REQUEST["d"]) ? @$_REQUEST["d"] : 30 ) ." day) <= a.tanggal_registrasi and b.membercode like 'CM%' and trim(a.serialnumber) != ''";

$sql = "SELECT a.membersproductid, upper(trim(a.serialnumber)) serialnumber, a.product, b.email, a.purchasedate, a.tanggal_registrasi, b.name, b.address, b.phone, b.handphone, c.region, d.state, g.region cabang , concat(case when h.klaim = 0 then '' when h.klaim = 1 then 'Bank Transfer: ' else 'GOPAY/OVO: ' end, h.klaim_info) klaim_info, case when trim(replace(h.klaim_info, '|','')) = '' then 0 else h.klaim end klaim
FROM `membersproduct` a inner join membersdata b on a.memberid = b.MemberID inner join shipment_exception c on b.HomeRegion = c.region_id inner join shipment_state d on c.state_id = d.state_id
left join branch_service e on c.region_id = e.service_region_id and c.state_id = e.service_state_id left join branch f on f.state_id = e.branch_state_id and f.region_id = e.branch_region_id
left join shipment_exception g on f.state_id = g.state_id and f.region_id = g.region_id
left join sellout_klaim h on a.membersproductid = h.membersproductid
where date_add( CURRENT_DATE, interval -". (@$_REQUEST["d"] != "" && is_numeric(@$_REQUEST["d"]) ? @$_REQUEST["d"] : 30 ) ." day) <= a.tanggal_registrasi and (b.membercode like 'CM%' or a.valid_registrasi = 2) and trim(a.serialnumber) != ''  
order by a.tanggal_registrasi asc";

$sql = "
SELECT a.membersproductid, upper(trim(a.serialnumber)) serialnumber, a.product, 
case when ifnull(a.email, '') = '' then case when (ifnull(a.nama, '') != '' or ifnull(a.alamat, '') != '' or ifnull(a.telp, '') != '' or ifnull(a.hp, '') != '' or ifnull(a.kota, '') or ifnull(a.propinsi, '') != '' or ifnull(a.kota_string, '') != '' or ifnull(a.propinsi_string, '') != '' ) then a.email else b.email end else a.email end email, 
a.purchasedate, a.purchaseat, a.tanggal_registrasi, 
case when ifnull(a.nama, '') = '' then case when (ifnull(a.email, '') != '' or ifnull(a.alamat, '') != '' or ifnull(a.telp, '') != '' or ifnull(a.hp, '') != '' or ifnull(a.kota, '') or ifnull(a.propinsi, '') != '' or ifnull(a.kota_string, '') != '' or ifnull(a.propinsi_string, '') != '' ) then a.nama else b.name end else a.nama end name, 
case when ifnull(a.alamat, '') = '' then case when (ifnull(a.email, '') != '' or ifnull(a.nama, '') != '' or ifnull(a.telp, '') != '' or ifnull(a.hp, '') != '' or ifnull(a.kota, '') or ifnull(a.propinsi, '') != '' or ifnull(a.kota_string, '') != '' or ifnull(a.propinsi_string, '') != '' ) then a.alamat else b.address end else a.alamat end address, 
case when ifnull(a.telp, '') = '' then case when (ifnull(a.email, '') != '' or ifnull(a.nama, '') != '' or ifnull(a.alamat, '') != '' or ifnull(a.hp, '') != '' or ifnull(a.kota, '') or ifnull(a.propinsi, '') != '' or ifnull(a.kota_string, '') != '' or ifnull(a.propinsi_string, '') != '' ) then a.telp else b.phone end else a.telp end phone, 
case when ifnull(a.hp, '') = '' then case when (ifnull(a.email, '') != '' or ifnull(a.nama, '') != '' or ifnull(a.alamat, '') != '' or ifnull(a.telp, '') != '' or ifnull(a.kota, '') or ifnull(a.propinsi, '') != '' or ifnull(a.kota_string, '') != '' or ifnull(a.propinsi_string, '') != '' ) then a.hp else b.handphone end else a.hp end handphone, 
case when ifnull(a.kota_string, '') = '' then case when (ifnull(a.email, '') != '' or ifnull(a.nama, '') != '' or ifnull(a.alamat, '') != '' or ifnull(a.telp, '') != '' or ifnull(a.kota, '') or ifnull(a.propinsi, '') != '' or ifnull(a.hp, '') != '' or ifnull(a.propinsi_string, '') != '' ) then a.kota_string else c.region end else a.kota_string end region, 
case when ifnull(a.propinsi_string, '') = '' then case when (ifnull(a.email, '') != '' or ifnull(a.nama, '') != '' or ifnull(a.alamat, '') != '' or ifnull(a.telp, '') != '' or ifnull(a.kota, '') or ifnull(a.propinsi, '') != '' or ifnull(a.hp, '') != '' or ifnull(a.kota_string, '') != '' ) then a.propinsi_string else d.state end else a.propinsi_string end state, 
g.region cabang , concat(case when h.klaim = 0 then '' when h.klaim = 1 then 'Bank Transfer: ' else 'GOPAY/OVO: ' end, h.klaim_info) klaim_info, case when trim(replace(h.klaim_info, '|','')) = '' then 0 else h.klaim end klaim
FROM `membersproduct` a inner join membersdata b on a.memberid = b.MemberID 
inner join shipment_exception c on 
	case when ifnull(a.kota, '') = '' then case when (ifnull(a.email, '') != '' or ifnull(a.nama, '') != '' or ifnull(a.alamat, '') != '' or ifnull(a.telp, '') != '' or ifnull(a.kota_string, '') or ifnull(a.propinsi, '') != '' or ifnull(a.hp, '') != '' or ifnull(a.propinsi_string, '') != '' ) then a.kota else b.homeregion end else a.kota end
	= c.region_id 
inner join shipment_state d on c.state_id = d.state_id
left join branch_service e on c.region_id = e.service_region_id and c.state_id = e.service_state_id 
left join branch f on f.state_id = e.branch_state_id and f.region_id = e.branch_region_id
left join shipment_exception g on f.state_id = g.state_id and f.region_id = g.region_id
left join sellout_klaim h on a.membersproductid = h.membersproductid
where date_add( CURRENT_DATE, interval -". (@$_REQUEST["d"] != "" && is_numeric(@$_REQUEST["d"]) ? @$_REQUEST["d"] : 30 ) ." day) <= a.tanggal_registrasi and (b.membercode like 'CM%' or a.valid_registrasi = 2) and trim(a.serialnumber) != ''  
order by a.tanggal_registrasi asc
";

$rs = mysql_query( $sql );
while( $data = mysql_fetch_array( $rs ) )
	$arr_data[ strtoupper(trim($data["serialnumber"])) ] = array(
		"membersproductid" =>$data["membersproductid"],
		"serialnumber"=>strtoupper(trim($data["serialnumber"])), "email"=>$data["email"], "purchasedate" => $data["purchasedate"] ,
		"tanggal_registrasi"=>$data["tanggal_registrasi"], "name"=>$data["name"], "address" => $data["address"], 
		"phone"=>$data["phone"], "handphone"=>$data["handphone"], "region" => $data["region"], "state" => $data["state"], "product" => $data["product"], "cabang" => $data["cabang"],  
		"klaim" => $data["klaim"], "klaim_info" => $data["klaim_info"],  
		);

echo json_encode( $arr_data );

?>