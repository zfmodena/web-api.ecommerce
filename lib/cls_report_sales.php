<?
class report{
public static function get_report_sales($from,$to){
		$sql = "select order_date tanggal_order, order_no no_order, custname nama_konsumen, city kota, state propinsi, f.name produk, e.quantity kuantitas, product_price harga_net, round(e.product_promo,0) promo, a.coupon_code coupon_code,round(product_pricepromo,0) harga_promo, e.quantity*round(product_pricepromo,0) total_sales from ordercustomer a, doku b, transaction c, (select max(edu_time) edu_time, transidmerchant from transaction group by transidmerchant) d, orderproduct e, product f
		where a.order_no=b.transidmerchant and b.trxstatus='Success' and b.transidmerchant=c.transidmerchant and c.edu_time=d.edu_time and a.order_id=e.order_id and e.product_id=f.productid and
		order_date between '$from' and '$to' and order_status>=1 order by order_date";
		$result = mysql_query($sql) or die();
		return $result;
	}
	
public static function get_report_sales_discontinue($from,$to){
		$sql = "select g.name, email, h.state, i.region, order_date tanggal_order, order_no no_order, f.name produk, e.quantity kuantitas, product_price harga_net
, round(e.product_promo,0) promo, a.coupon_code, round(product_pricepromo,0) harga_promo, e.quantity*round(product_pricepromo,0) total_sales
, case  e.quantity*round(product_pricepromo,0) when 0 then 'Kuantitas tidak tersedia' 
else case IFNULL(j.id,'') when '' then 'Tidak dilanjutkan ke pembayaran' else CONCAT('Lanjut ke pembayaran tapi gagal (KODE',': ',j.response_code,')') end end keterangan
from ordercustomer a left outer join orderproduct e on a.order_id=e.order_id 
left outer join product f on e.product_id=f.productid 
left outer join membersdata g on a.memberid=g.memberid 
left outer join shipment_state h on g.homestate=h.state_id 
left outer join shipment_exception i on g.homeregion=i.region_id 
left outer join doku j on a.order_no=j.transidmerchant
where 
order_date between '$from' and '$to' and email not in ('doelzac@gmail.com', 'online.sales@modena.co.id', 'zaenal.fanani@modena.co.id','', 'rahdian@nsiapay.com','ferdian.wijaya@modena.co.id')
and order_status=0 order by order_date";
		$result = mysql_query($sql) or die();
		return $result;
	}
	
}

?>