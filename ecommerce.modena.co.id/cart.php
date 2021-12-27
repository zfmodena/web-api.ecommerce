<?

include_once "../lib/var.php";
include_once "lib/cls_main.php"; // lib/cls_coupon_code_karyawan.php, 
include_once "lib/cls_discount.php";
include_once "lib/cls_shoppingcart.php";
include_once "lib/cls_product.php";
include_once "lib/cls_culinaria_program.php"; // lib/sql.php
include_once "lib/cls_culinaria_cart.php";

// cek shopping cart
if( trim(@$_REQUEST["shopping_cart"] . @$_REQUEST["shopping_cart_culinaria"]) == "" ) die("E0");

// otorisasi key
$auth = sha1(__KEY__ . @$_REQUEST["rand"] . str_replace(" ", "", trim(@$_REQUEST["shopping_cart"] . @$_REQUEST["shopping_cart_culinaria"]) ) ) ;
//die(__KEY__ . @$_REQUEST["rand"] . str_replace(" ", "", trim(@$_REQUEST["shopping_cart"] . @$_REQUEST["shopping_cart_culinaria"]) ));
if( $auth != @$_REQUEST["auth"] ) die( $auth . " E1");

include_once "tradein-cek.php";		
function cari_nilai_diskon_per_item( $kode_promo, $data_item, $arr_diskon_rdm = array(), $arr_diskon_per_item_tanpa_kode = array(), $arr_diskon_per_item_dengan_kode = array() ){
	$diskon = 0;
	$status = $paketid = "";
	// cek diskon per item RDM, dengan kode.. belum dibuatkan mekanisme call API RDM di air.modena.co.id/dm/ws
	if( count($arr_diskon_rdm) > 0 ){
		foreach( $arr_diskon_rdm as $index => $item ){
			if( $item["item_id"] == $data_item["sku"] ){	
				$diskon = $item["diskon"];
				$paketid = $item["paketid"];
				break;
			}
		}
	}

	// cek diskon per item dari lokal, tanpa kode
	if( count($arr_diskon_per_item_tanpa_kode) > 0 && array_key_exists($data_item["productid"], $arr_diskon_per_item_tanpa_kode) ){
		$diskon = $arr_diskon_per_item_tanpa_kode[ $data_item["productid"] ]["diskon"];
		$diskon = $diskon <= 1 ? $diskon * ( $data_item["harga"] * $data_item["qty"] ) : $diskon * $data_item["qty"];
		$status = $arr_diskon_per_item_tanpa_kode[ $data_item["productid"] ]["status"];
	}
	// cek diskon per item dari lokal, dengan kode
	/* promo per item dari sumber lokal tidak digunakan, digantikan dengan RDM
	if( count($arr_diskon_per_item_dengan_kode) > 0 && array_key_exists($data_item["productid"], $arr_diskon_per_item_dengan_kode) ){
		$diskon = $arr_diskon_per_item_dengan_kode[ $data_item["productid"] ]["diskon"];
		$diskon = $diskon <= 1 ? $diskon * ( $data_item["harga"] * $data_item["qty"] ) : $diskon * $data_item["qty"];
		$status = $arr_diskon_per_item_dengan_kode[ $data_item["productid"] ]["status"];
	}*/
	// cek diskon tradein
	// apabila tradein umum, item tsb tidak mendapatkan diskon tambahan (hanya diskon tradein saja). kecuali disetting dari kode promo publik (lokal)
	if(order_online_promo::isvalid_tradein_location(@$_SESSION["shipping_state"], @$_SESSION["shipping_region"])){
		
		$data_item["price"] = $data_item["harga"];
		if( count(@$_SESSION["tradein"]) > 0 && in_array($data_item["productid"], $_SESSION["tradein"]) ){
			//$diskon = __DISC_TRADEIN__ * ( $data_item["harga"] * $data_item["qty"] );
			$diskon=order_online_promo::get_trade_in_mekanisme_baru($kode_promo, $data_item, $GLOBALS["arr_tradein_category"], $GLOBALS["arr_tradein_kuota"], $GLOBALS["arr_tradein_pemakaian_kuota"]);
		}
	}
	return array("diskon" => $diskon, "status" => $status, "paketid" => $paketid );
}

function set_cart($nama_session, $request){
	if( @$request != "" ) {
		$shopping_cart = explode(";", @$request);
		if( is_array($shopping_cart) && count($shopping_cart) > 0 ){
			$_SESSION[ $nama_session ] = $_SESSION[ $nama_session . "_price" ] = $_SESSION[ $nama_session . "_sku" ] = $_SESSION[ $nama_session . "_nama" ] = $_SESSION[ $nama_session . "_diskon" ] = array();
			foreach( $shopping_cart as $cart_item ){
				list($stok_item_id, $cart_item_id, $sku, $nama, $price, $qty, $diskon, $keterangan_diskon, $tradein,$berat) = explode(",", $cart_item);
				if($cart_item_id == "") continue;
				$_SESSION[ $nama_session ][$cart_item_id] = $qty;
				$_SESSION[ $nama_session . "_stok" ][$cart_item_id] = $stok_item_id;
				$_SESSION[ $nama_session . "_price" ][$cart_item_id] = $price;
				$_SESSION[ $nama_session . "_sku" ][$cart_item_id] = $sku;
				$_SESSION[ $nama_session . "_nama" ][$cart_item_id] = $nama;
				$_SESSION[ $nama_session . "_berat" ][$cart_item_id] = $berat;
				if( $diskon > 0 ) 
					$_SESSION[ $nama_session . "_diskon" ][ $cart_item_id ] = array(
						"productid" => $cart_item_id, 
						"diskon" => ($diskon <= 1 ? $price * $diskon : $diskon), 
						"status" => $keterangan_diskon
					);
				if( $nama_session == "shopping_cart" && $tradein == 1 ) $_SESSION["tradein"][] = $cart_item_id;
			}
		}
	}
}

function get_shipping_cost_per_weight($regionid){
    $parameter="upper(trim(region_id))=upper(trim('".$regionid."')) and enabled='1';";
	$sql="select cost, percent from shipment_exception where ".$parameter; 
	$rs_shipping_cost=mysql_query($sql) or die();//("get_shipping_cost_per_weight query error.<br />".mysql_error());
	$rs_shipping_cost_=mysql_fetch_array($rs_shipping_cost);
	$return["cost"]=$rs_shipping_cost_["cost"];
	$return["percent"]=$rs_shipping_cost_["percent"];
	return $return;
}

function get_shipping_cost($total_sales, $total_weight, $regionid){
	$cost=get_shipping_cost_per_weight($regionid)	;	
	$cost_by_weight	=	($total_weight	*$cost["cost"])>0	?	(($total_weight*$cost["cost"])+$GLOBALS["tambahan_shipping_cost"])		:0	;
	$cost_by_percent=	$cost["percent"]*$total_sales;
	if($cost_by_weight>0	&& $cost_by_percent>0)
		$return=	($cost_by_weight<$cost_by_percent	?	$cost_by_weight	:	$cost_by_percent);
	elseif($cost_by_weight>0	&& $cost_by_percent<=0)
		$return=	$cost_by_weight;
	elseif($cost_by_weight<=0	&& $cost_by_percent>0)
		$return=	$cost_by_percent;
	else
		$return=0;
	return $return;
}

function panggil_curl($url, $arr_par){
	$ch = curl_init();
	//echo $url . "?" . http_build_query($arr_par);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, __CURL_TIMEOUT); 
	curl_setopt($ch, CURLOPT_TIMEOUT, __CURL_TIMEOUT);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$server_output = curl_exec ($ch); 
	$server_output = json_decode($server_output, true);		
    return $server_output;    
}

/*
Request parameter :
1. email
2. shopping_cart, format : stok1(int, untuk barang Modena diisikan 0),productid1(int),sku1(varchar),nama1(varchar),price1(float),qty1(int),diskon1(float,0=belum diskon|<1=diskon desimal misal 0.1|>1=diskon nominal misal 100000),keterangan_diskon1(varchar,apabila sudah_diskon1=1),tradein1(tinyint,0=bukan tradein|1=tradein),berat1(float,berat produk dlm satuan kilogram); dst
3. shopping_cart_culinaria, format : stok1(int, untuk item culinaria diisikan max kursi kelas yang tersedia),programid1(int),sku1(varchar),nama1(varchar),price1(float),qty1(int),diskon1(float,0=belum diskon|<1=diskon desimal misal 0.1|>1=diskon nominal misal 100000),keterangan_diskon1(varchar,apabila sudah_diskon1=1),tradein1(tinyint,utk culinaria diisikan 0),berat1(float,utk culinaria diisikan 0); dst
4. lang : id|en
5. kode_promo
6. kode_promo_culinaria
7. kota
8. rand
9. auth, format : sha1(__KEY__ . @$_REQUEST["rand"] . str_replace(" ", "", trim(@$_REQUEST["shopping_cart"] . @$_REQUEST["shopping_cart_culinaria"]) ) ) 
10. c=cart

Contoh :
shopping_cart=0,1306,BH293311017S50,PRODUK PERTAMA,1200000,1,0,,0,6;0,1308,BH4934L1017S50,PRODUK KEDUA,2100000,3,0.1,sampel diskon,0,3;0,1265,MG2516L0418S19,PRODUK KETIGA,3300000,2,0,,1,5;
email=zaenal.fanani@modena.co.id
lang=id
kode_promo=ADER10
kota=275
shopping_cart_culinaria=20,253,CGC04A,UJICOBA KELAS CULINARIA,900000,1,0,,0,0;
kode_promo_culinaria=
rand=12345
auth=0b989916ab114ced2a0296f838f1d8f5375bff87
c=cart

Respon :
{
    "gudang": "GDGPST",
    "order_no": "MAPI4820643",
    "arr_cart": [
        {
            "kode_produk": "BH293311017S50",
            "produk": "PRODUK PERTAMA",
            "stok": 404,
            "stok_pusat": 404,
            "shipment_delay": 3,
            "qty": "1",
            "harga": "1200000",
            "diskon": 0,
            "subtotal": 1200000,
            "productid": 1306,
            "berat" : 6
        },
        {
            "kode_produk": "BH4934L1017S50",
            "produk": "PRODUK KEDUA",
            "stok": 24,
            "stok_pusat": 24,
            "shipment_delay": 3,
            "qty": "3",
            "harga": "2100000",
            "diskon": 630000,
            "subtotal": 5670000,
            "productid": 1308,
            "berat" : 3
        },
        {
            "kode_produk": "MG2516L0418S19",
            "produk": "PRODUK KETIGA",
            "stok": -2,
            "stok_pusat": -2,
            "shipment_delay": 3,
            "qty": 0,
            "harga": "3300000",
            "diskon": 0,
            "subtotal": 0,
            "productid": 1265,
            "berat" : 5
        }
    ],
    "total": 6870000,
    "diskon": 120000,
    "subtotal": 6750000,
    "kode_promo": "ADER10",
    "status_kode_promo": "DISKON INVOICE TIDAK DOBEL DISKON",
    "order_no_culinaria": "11820101",
    "arr_cart_culinaria": [
        {
            "program": "Traditional Snack Course",
            "harga": "990000",
            "qty": "1"
        }
    ],
    "total_culinaria": 990000,
    "diskon_culinaria": 0,
    "subtotal_culinaria": 990000,
    "kode_promo_culinaria": "CULMZFXX",
    "status_kode_promo_culinaria": "Kode salah/tidak terdaftar|Kode habis masa berlaku|Kode pernah digunakan sebelumnya",
    "ongkos_kirim":0
    "grand_total": 7740000 
}
*/

// inisialisasi variabel
$cart = file_get_contents("cart.html");
$cart_fg_template = file_get_contents("cart_fg.html");
$cart_culinaria_template = file_get_contents("culinaria-cart-item.html");
$default_status_kodepromo_fg["id"] = "Kode salah/tidak terdaftar|Kode habis masa berlaku|Kode pernah digunakan sebelumnya|Khusus untuk voucher trade-in, silakan pilih produk yang akan dilakukan trade-in";
$default_status_kodepromo_fg["en"] = "Invalid code|Expired code|Code has been used before|For trade-in, please choose one/more items to be traded-in";
$default_status_kodepromo_culinaria["id"] = "Kode salah/tidak terdaftar|Kode habis masa berlaku|Kode pernah digunakan sebelumnya";
$default_status_kodepromo_culinaria["en"] = "Invalid code|Expired code|Code has been used before";

unset($_SESSION["fg_order_no"], $_SESSION["culinaria_order_no"]);
unset($_SESSION["shopping_cart"], $_SESSION["culinaria_shopping_cart"]);

$_SESSION["tradein"] = array();
$_SESSION["showroom_inisial"] = __SHOWROOM_INISIAL__ . (isset($_REQUEST["showroom_inisial"]) && array_key_exists($_REQUEST["showroom_inisial"], $arr_showroom_inisial_diperbolehkan) ? $_REQUEST["showroom_inisial"] : "");

// cek data member berbasis email
$sql = "INSERT INTO membersdata (email)
    SELECT * FROM (SELECT '". main::formatting_query_string($_REQUEST["email"]) ."') AS tmp
        WHERE NOT EXISTS (SELECT email FROM membersdata WHERE email = '". main::formatting_query_string($_REQUEST["email"]) ."') ";
mysql_query($sql);
$_SESSION["email"] = @$_REQUEST["email"] != "" ? $_REQUEST["email"] : __EMAIL__;

$_SESSION["shopping_cart"] = count(@$_SESSION["shopping_cart"]) > 0 ? $_SESSION["shopping_cart"] : array(); set_cart( "shopping_cart", @$_REQUEST["shopping_cart"]);
$_SESSION["culinaria_shopping_cart"] = count(@$_SESSION["culinaria_shopping_cart"]) > 0 ? $_SESSION["culinaria_shopping_cart"] : array(); set_cart( "culinaria_shopping_cart", @$_REQUEST["shopping_cart_culinaria"]);
$_SESSION["arr_order_no"] = array();
$lang = in_array(strtolower(trim(@$_REQUEST["lang"])), array("id", "en")) ? $_REQUEST["lang"] : __LANG__;
// konversi kota menjadi gudang
$_REQUEST["gudang"] = __GUDANG_PUSAT__;
if( @$_REQUEST["kota"] != "" ){
    $arr_par = array("c"=>"kebetot", "kota"=>$_REQUEST["kota"]);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, __API__ . "gudang");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, __CURL_TIMEOUT); 
    curl_setopt($ch, CURLOPT_TIMEOUT, __CURL_TIMEOUT);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $server_output = curl_exec ($ch);
    $server_output = json_decode($server_output, true);		
    
    if( @$server_output["gudang"] != "" )   
        $_REQUEST["gudang"] = $server_output["gudang"];
}
$_SESSION["gudang"] = $_REQUEST["gudang"];


// setting nomor order FG
if( count($_SESSION["shopping_cart"]) > 0 ){
	if( @$_SESSION["fg_order_no"] != "" )	mysql_query("update ordercustomer set order_date = current_timestamp where order_no = '". main::formatting_query_string($_SESSION["fg_order_no"]) ."'");
	else								$_SESSION["fg_order_no"] =  shoppingcart::__insert_order($_SESSION["email"], __SHOWROOM_INISIAL__);
	$_SESSION["arr_order_no"]["fg_order_no"] = $_SESSION["fg_order_no"];
}else unset($_SESSION["arr_order_no"]["fg_order_no"]);

// setting nomor order culinaria
if( count($_SESSION["culinaria_shopping_cart"]) > 0 ){
	if( @$_SESSION["culinaria_order_no"] != "" )	mysql_query("update culinaria_ordercustomer set order_date = current_timestamp where order_no = '". main::formatting_query_string($_SESSION["culinaria_order_no"]) ."'");
	else								$_SESSION["culinaria_order_no"] =  culinaria_cart::culinaria_nomor_order_baru($_SESSION["email"]);
	$_SESSION["arr_order_no"]["culinaria_order_no"] = $_SESSION["culinaria_order_no"];
}else unset($_SESSION["arr_order_no"]["culinaria_order_no"]);

// reset grup order customer
if( @$_SESSION["arr_order_no"]["fg_order_no"] != "" )
    mysql_query("delete from group_ordercustomer where parent_order_no = '". main::formatting_query_string($_SESSION["arr_order_no"]["fg_order_no"]) ."'");
if( @$_SESSION["arr_order_no"]["culinaria_order_no"] != "" ) 
    mysql_query("delete from group_ordercustomer where parent_order_no = '". main::formatting_query_string($_SESSION["arr_order_no"]["culinaria_order_no"]) ."'");

// entri ke grup order customer
if( count($_SESSION["arr_order_no"]) > 0 ){
	$template_sql = "
		select ifnull(
				(
					select parent_order_no from group_ordercustomer a 
					where a.order_no in ('". main::formatting_query_string( implode( "',", array_values( $_SESSION["arr_order_no"] ) ) ) ."')
				) , '#order_no[0]#')
			, a.order_no 
		from (select '#order_no#' order_no) a
		where not exists(select 1 from group_ordercustomer where order_no = a.order_no)";
	$arr_order_no_values = array_values($_SESSION["arr_order_no"]);
	foreach( $arr_order_no_values as $index => $order_no )
		$arr_sql_order_no[] = str_replace( array("#order_no[0]#", "#order_no#"), array($arr_order_no_values[0], $order_no), $template_sql );

	mysql_query( " insert into group_ordercustomer " . implode(" union ", $arr_sql_order_no)  );
}

// dapatkan order_id dari ordercustomer dan culinaria_ordercustomer
$sql = "select a.order_id, b.culinaria_order_id from (select order_id from ordercustomer where order_no = '". main::formatting_query_string($_SESSION["fg_order_no"]) ."') a 
            left join (select order_id culinaria_order_id from culinaria_ordercustomer where order_no = '". main::formatting_query_string($_SESSION["culinaria_order_no"]) ."') b on 1=1";
$rs_order_id = mysql_query($sql);
if(mysql_num_rows($rs_order_id)<=0){
    $sql = "select a.order_id, b.culinaria_order_id from (select order_id culinaria_order_id from culinaria_ordercustomer where order_no = '". main::formatting_query_string($_SESSION["culinaria_order_no"]) ."') b 
                left join (select order_id from ordercustomer where order_no = '". main::formatting_query_string($_SESSION["fg_order_no"]) ."') a on 1=1";
    $rs_order_id = mysql_query($sql);
}
$data_order_id = mysql_fetch_array($rs_order_id);

if( @$_REQUEST["c"] != "" )	include_once "cart-exec.php";

// load propinsi
$arr_lokasi["propinsi"] = "";
$rs_state=main::shipment_state("");$counter_state=1;$default_state="";$string_opsi_propinsi="<option value=\"\">$lang_select_state</option>";
while($rs_state_=mysql_fetch_array($rs_state, MYSQL_ASSOC)){
	$arr_t_state=preg_split("/\|/", (@$_REQUEST["t_state"]!=""?$_REQUEST["t_state"]:@$_SESSION["shipping_state"])	);
	$arr_lokasi["propinsi"] = $arr_t_state[0];
	$rs_state_["state_id"]==@$arr_t_state[0]?$selected="selected=\"selected\"":$selected="";
	$string_opsi_propinsi .= "<option value=\"".$rs_state_["state_id"]."|".$rs_state_["cost"]."\" ".$selected." id=\"opt_t_state_".$rs_state_["state_id"]."\">".$rs_state_["state"]."</option>";
	if($counter_state==1)$default_state=$rs_state_["state_id"];
	$counter_state++;
}								

// load kota
$arr_t_state=preg_split("/\|/", (@$_REQUEST["t_state"]!=""?$_REQUEST["t_state"]: (@$_SESSION["shipping_state"] != "" ? $_SESSION["shipping_state"] : "" ))	);
$string_opsi_kota = "";
$rs_region=main::shipment_exception(@$arr_t_state[0], "");$show_image=false;$s_city_disabled = true;
if(mysql_num_rows($rs_region)<=0){
	$show_image=true;
}
else{
	if(@$_SESSION["shipping_state"] != "") $s_city_disabled = false;
	$string_opsi_kota = "<option value=\"\">".$lang_select_region."</option>";
	while($region=mysql_fetch_array($rs_region)){
		$region["region_id"]==(@$_REQUEST["s_city"]!=""?$_REQUEST["s_city"]:@$_SESSION["shipping_region"])	
			?$selected="selected=\"selected\"":$selected="";
		$string_opsi_kota .= "<option value=\"".$region["region_id"]."\" ".$selected.">".ucwords(strtolower($region["region"]))."</option>";
	}
	$arr_lokasi["kota"] = (@$_REQUEST["s_city"]!=""?$_REQUEST["s_city"]:@$_SESSION["shipping_region"]);	
}


// load item FG
include "cart_fg.php";

// load item Culinaria
include "cart_culinaria.php";

// itung ongkos kirim
$ongkos_kirim = get_shipping_cost($total_fg_sblm_diskon, $total_berat_produk, $_REQUEST["kota"]);

// opsional utk isi alamat kirim
//$arr_opsional_alamat_kirim = array("nama_kirim", "alamat_kirim", "kota_kirim", "propinsi_kirim", "kodepos_kirim", "handphone_kirim","phone_kirim");
//foreach( $arr_opsional_alamat_kirim as $value ){
//    if( !isset( $_REQUEST[$value] ) || trim($_REQUEST[$value]) == "" )
//        $_REQUEST[$value] = $value;
//}

// dummy utk isi informasi pengiriman produk / culinaria
$sql = "update ordercustomer set 
            order_date = CURRENT_TIMESTAMP,
            coupon_code = '". main::formatting_query_string(@$_REQUEST["kode_promo"]) ."',
            coupon_discount = '". main::formatting_query_string(is_null($discount_price) ? 0 : $discount_price) ."',
			receiver_name_for_shipping = '". main::formatting_query_string($_REQUEST["nama_kirim"]) ."',
			shipping_address = '". main::formatting_query_string($_REQUEST["alamat_kirim"]) ."',
			shipping_address_city = '". main::formatting_query_string($_REQUEST["kota_kirim"]) ."',
			shipping_address_state = '". main::formatting_query_string($_REQUEST["propinsi_kirim"]) ."',
			shipping_address_postcode = '". main::formatting_query_string($_REQUEST["kodepos_kirim"]) ."',
			shipping_phone_no = '". main::formatting_query_string($_REQUEST["phone_kirim"]) ."',
			shipping_handphone_no = '". main::formatting_query_string($_REQUEST["handphone_kirim"]) ."',
			shipping_date = '". main::formatting_query_string($_REQUEST["tanggal_kirim"]) ."',
			shipping_note = '". main::formatting_query_string($_REQUEST["note_kirim"]) ."',
			billing_first_name = 'DUMMY FIRST NAME BILLING',
			billing_last_name = 'DUMMY LAST NAME BILLING',
			billing_address = 'DUMMY ADDRESS BILLING',
			billing_address_city = 'DUMMY ADDRESS CITY BILLING',
			billing_address_state = 'DUMMY ADDRESS STATE BILLING',
			billing_phone_no = 'DUMMY PHONE NO BILLING',
			billing_handphone_no = 'DUMMY HANDPHONE NO BILLING',
			custemail = '". main::formatting_query_string($_REQUEST["email"]) ."',
			shippingcost = '". main::formatting_query_string($ongkos_kirim) ."'
			where order_no = '". main::formatting_query_string($_SESSION["fg_order_no"]) ."'";
mysql_query($sql);			
$sql = "update culinaria_ordercustomer set 
            order_date = CURRENT_TIMESTAMP,
            coupon_code = '". main::formatting_query_string(@$_REQUEST["kode_promo_culinaria"]) ."',
            coupon_discount = '". main::formatting_query_string(is_null($discount_price_culinaria) ? 0 : $discount_price_culinaria) ."',
			billing_first_name = '". main::formatting_query_string($_REQUEST["nama_kirim"]) ."',
			billing_last_name = '',
			billing_address = '". main::formatting_query_string($_REQUEST["alamat_kirim"]) ."',
			billing_address_city = '". main::formatting_query_string($_REQUEST["kota_kirim"]) ."',
			billing_address_state = '". main::formatting_query_string($_REQUEST["propinsi_kirim"]) ."',
			billing_phone_no = '". main::formatting_query_string($_REQUEST["phone_kirim"]) ."',
			billing_handphone_no = '". main::formatting_query_string($_REQUEST["handphone_kirim"]) ."',
			custemail = '". main::formatting_query_string($_REQUEST["email"]) ."'
			where order_no = '". main::formatting_query_string($_SESSION["culinaria_order_no"]) ."'";			
mysql_query($sql);

// API return
unset($json);
$json["modena"] = array(
        "order_no" => $_SESSION["fg_order_no"],	
        "detail" => $arr_cart_fg,
        "gudang" =>  (@$_REQUEST["gudang"] != "" ? $_REQUEST["gudang"] : $_SESSION["gudang"]) ,
        "subtotal" => $total_fg,
        "diskon" => is_null($discount_price) ? 0 : $discount_price,
        "total" => $fg_grandtotal,
        "kode_promo" => @$_REQUEST["kode_promo"],
        "status_kode_promo" => @$server_output_diskon["status"] != "" && $discount_price <= 0 && !$ada_diskon_per_item_fg ? @$server_output_diskon["status"] : $status_diskon_invoice ,	
        "ongkos_kirim" => $ongkos_kirim,
    );
$json["culinaria"] = array(
        "order_no" => $_SESSION["culinaria_order_no"],	
        "detail" => $arr_cart_culinaria,
        "subtotal" => $total_culinaria,
        "diskon" => is_null($discount_price_culinaria) ? 0 : $discount_price_culinaria,
        "total" => $culinaria_grandtotal,
        "kode_promo" => @$_REQUEST["kode_promo_culinaria"],
        "status_kode_promo" => @$server_output_diskon["status"] != "" && $discount_price_culinaria <= 0 && count($arr_list_promo_per_item_dengan_voucher) <= 0 ? @$server_output_diskon["status"] : $status_diskon_invoice_culinaria ,	
    );
$json["grand_total"] = $fg_grandtotal+$culinaria_grandtotal+$ongkos_kirim;

if( $json["modena"]["kode_promo"] != "" && $json["modena"]["status_kode_promo"] != "" )
	$json["modena"]["status_kode_promo"] = $default_status_kodepromo_fg[$lang];
if( $json["culinaria"]["kode_promo"] != "" && $json["culinaria"]["status_kode_promo"] != "" )
	$json["culinaria"]["status_kode_promo"] = $default_status_kodepromo_culinaria[$lang];

$json = json_encode($json);
header("Content-Type: application/json");
echo $json;
?>
