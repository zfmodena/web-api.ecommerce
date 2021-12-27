<?

include_once "../lib/var.php";
include_once "../lib/cls_coupon_code.php";

$auth = sha1(__KEY__ . @$_REQUEST["rand"] . sha1( trim( @$_REQUEST["paket_kode"] . @$_REQUEST["remark_id"] . @$_REQUEST["remark_en"] . @$_REQUEST["paket_diskon"] . @$_REQUEST["paket_expired"] . @$_REQUEST["mode"] . @$_REQUEST["jumlah_paket"] . @$_REQUEST["paket_min_sales"] . @$_REQUEST["mekanisme_kupon"] . @$_REQUEST["kuota_kupon"] ) ) ) ;
//die($auth);
if( $auth != @$_REQUEST["auth"] ) die("E1 " . $auth);


try{
	$kode=new coupon_code;
	$kode->paket_kode=		array( $_REQUEST["paket_kode"] );
	$kode->remark_id = array( $_REQUEST["remark_id"] );
	$kode->remark_en = array( $_REQUEST["remark_en"] );
	$kode->paket_diskon=	array( $_REQUEST["paket_diskon"] );
	$kode->paket_expired=	array( $_REQUEST["paket_expired"] );
	$kode->paket_limitasi=	array( $_REQUEST["paket_limitasi"] );
	
	$kode->mode=		$_REQUEST["mode"];
	$kode->jumlah_paket=	$_REQUEST["jumlah_paket"];
	$kode->paket_min_sales=	array( $_REQUEST["paket_min_sales"] );
	$kode->mekanisme_kupon=	$_REQUEST["mekanisme_kupon"];
	$kode->kuota_kupon=	$_REQUEST["kuota_kupon"];
	$kode->status_double_promo=	$_REQUEST["status_double_promo"];
	
	$kode->database_insert=	$_REQUEST["database_insert"];
	
	header("Content-Type: application/json");
	echo json_encode( $kode->generate_promotion_code() );	
	
}catch(Exception $e){echo $e->getMessage();}

?>