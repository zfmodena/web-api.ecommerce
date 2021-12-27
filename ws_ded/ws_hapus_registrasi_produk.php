<?

include_once("../lib/var.php");
include_once("../lib/cls_main.php");
include_once("../ws/sql.php");

$key = sha1("modena.ptim*328." . @$_REQUEST["mpid"]);
if( $key != @$_REQUEST["key"] ) die("invalid key");

$sql = "SELECT a.membersproductid, upper(trim(a.serialnumber)) serialnumber, a.product, b.email, a.purchaseat, a.purchasedate, a.tanggal_registrasi, b.name, b.address, b.phone, b.handphone, c.region, d.state, g.region cabang , concat(case when h.klaim = 0 then '' when h.klaim = 1 then 'Bank Transfer: ' else 'GOPAY/OVO: ' end, h.klaim_info) klaim_info, case when trim(replace(h.klaim_info, '|','')) = '' then 0 else h.klaim end klaim
FROM `membersproduct` a inner join membersdata b on a.memberid = b.MemberID inner join shipment_exception c on b.HomeRegion = c.region_id inner join shipment_state d on c.state_id = d.state_id
left join branch_service e on c.region_id = e.service_region_id and c.state_id = e.service_state_id left join branch f on f.state_id = e.branch_state_id and f.region_id = e.branch_region_id
left join shipment_exception g on f.state_id = g.state_id and f.region_id = g.region_id
left join sellout_klaim h on a.membersproductid = h.membersproductid
where a.membersproductid = '". main::formatting_query_string($_REQUEST["mpid"]) ."' ";
$data = mysql_fetch_array(mysql_query($sql));
$arr_file_faktur_pembelian =glob("../upload/garansi/*". trim($data["membersproductid"]) ."*.jpg");
$file_kuitansi = str_replace("../upload/garansi/", "/upload/garansi/", $arr_file_faktur_pembelian[0]);

if( @$_REQUEST["confirm"] != 1 ) die( 
	str_replace("\n","", "<div style=\"border:solid 1px black; padding:7px\">
		<h3>Detail Data Garansi Konsumen</h3>
		<div>
			<table style=\"width:100%; border:none\">
				<tr><td>Konsumen</td><td>". $data["name"] ."</td></tr>
				<tr><td>Email</td><td>". $data["email"] ."</td></tr>
				<tr><td>Alamat</td><td>". $data["address"] ." ". $data["region"] ." ". $data["state"] ."</td></tr>
				<tr><td>Telepon</td><td>". $data["phone"] ." - ". $data["handphone"] ."</td></tr>
				<tr><td>Produk</td><td>". $data["product"] ."</td></tr>
				<tr><td>Nomor Seri</td><td>". $data["serialnumber"] ."</td></tr>
				<tr><td>Lokasi Pembelian</td><td>". $data["purchaseat"] ."</td></tr>
				<tr><td>Tanggal Pembelian</td><td>". $data["purchasedate"] ."</td></tr>
				<tr><td>Info Klaim</td><td>". ($data["klaim_info"] == "" || $data["klaim"] == 0 ? "<span style=\"font-weight:900; color:red\">Isian Kosong</span>" : $data["klaim_info"]) ."</td></tr>
				<tr><td>Foto Kuitansi</td><td><img src=\"http://www.modena.co.id/$file_kuitansi\" style=\"max-width:100%\" /></td></tr>
				<tr><td colspan=2 style=\"text-align:center; padding-top:17px\"><input type=\"button\" class=\"b_min\" id=\"batal\" value=\"Tutup\" onclick=\"__command(this.id, '', '')\"> | <input type=\"button\" class=\"b_plus\" id=\"ok\" value=\"Hapus\" onclick=\"__command(this.id, '". $_REQUEST["mpid"] ."', '".  $key ."')\"></td></tr>
			</table>
		</div>
	</div>")
);

$sql = "update membersproduct set valid_registrasi = 0 where membersproductid = '". $_REQUEST["mpid"] ."' ";
mysql_query( $sql );

?>