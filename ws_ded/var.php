<?
error_reporting(0); 
//error_reporting(E_ALL); 

if (strpos($_SERVER["REQUEST_URI"], "'") !== false || strpos($_SERVER["REQUEST_URI"], "*") !== false) {
    header("location:http://". $_SERVER["SERVER_NAME"] . str_replace("'", "", $_SERVER["REQUEST_URI"]));
}

session_start();

define("__ISDEBUG__", false);
define("__ECOMMERCE_ACTIVATION__", true);
define("__MOBILE_ACTIVATION__", true);
define("__FORCED_MOBILEVIEW__", false);

$string="ptindomomulia";

// koneksi database
$server="localhost";
$username="root";
$password="";
$database="modena_web";
mysql_connect($server, $username, $password) or die("");//("Could not connect :  ". mysql_error());
mysql_select_db($database) or die("");// ("Error connect to database : ". mysql_error());

//@define("INCLUDE_PATH","/home/modenaim/public_html/lib/pear");
//@define("INCLUDE_PATH","/opt/lampp/htdocs/modena.2/lib/pear");
@define("INCLUDE_PATH",__DIR__ . "/pear");

//koneksi smtp email
//define("SMTP_HOST","smtp.cbn.net.id");
define("SMTP_HOST","192.168.1.20");
//define("SMTP_HOST","mail3.modena.co.id");
define("SMTP_AUTH",false);
define("SMTP_USERNAME","support@modena.co.id");
//define("SMTP_USERNAME","");
define("SMTP_PASSWORD","sp_328_indomo");
//define("SMTP_PASSWORD","");
define("CRLF","\n");
define("EMAIL_TEMPLATE", "template/email.html");

define("CUSTOMERCARE_EMAIL", "MODENA Customercare <zaenal.fanani@modena.co.id>");
define("SALESADMIN_EMAIL", "MODENA Online Sales <zaenal.fanani@modena.co.id>");
define("FINANCE_EMAIL", "MODENA Finance <zaenal.fanani@modena.co.id>");
define("WAREHOUSE_EMAIL", "MODENA Warehouse <zaenal.fanani@modena.co.id>");
define("SHOWROOM_EMAIL", "MODENA Showroom <zaenal.fanani@modena.co.id>");
define("SUPPORT_EMAIL", "support@modena.co.id");
define("MANAGEMENT_EMAIL", "zaenal.fanani@modena.co.id");
define("MARCOMM_EMAIL", "MODENA MarComm <zaenal.fanani@modena.co.id>");
//define("EMAIL_TEMPLATE","template/email.html");

//email HRD utk keperluan pengiriman aplikasi online
$email_hrd="hrd@modena.co.id";
$app_file_max_size=100000;//in byte

//koleksi variabel

/* durasi info (milidetik) */
$info_duration=5000;

/* language */
$arr_lang=array("en"=>"english", "id"=>"indonesia");
$arr_lang_mysql=array("en"=>"en_US", "id"=>"id_ID");
$lang="id";

/* default shipping time-satuan hari */
$default_shipping_time=7;
$minimum_shipping_time=2;
$maximum_shipping_time=30;
$tambahan_shipping_cost=50000;
define("FREE_SHIPPINGCOST",0.13); // persentase dari sales grand total

/* diskon */
$diskon=0.1;
$diskon_sp=0.25;
$max_kuantitas_tersedia_ditampilkan = 1;

/* payment gateway */
$remote_payment_gateway="pay.doku.com";
$target_payment_gateway="https://pay.doku.com/Suite/Receive";
$merchant_id="000100013002217";
$merchant_id_installment="000100013002704";
$merchant_password="ghTX534r";
$merchant_acquirerbin="410504";
$merchant_mallid="719";
$merchant_sharedkey="Ts9WmUnN4t62";
$payment_status_code_ok="SUCCESS";//"00";
$payment_status_code_doku_ok="0000";
$payment_status_text_ok="SUCCESS";
$url_path="http://air.modena.co.id/modena.dev/";
$payment_va_expiration="240";// satuan menit

/* parameter utk update tabel ordercustomer : cls_shoppingcart, cls_order */
$arr_par_ordercustomer=array(
	"order_date"=>"current_timestamp",
	"order_status"=>"",
	"additional_discount_code"=>"",
	"additional_discount"=>"",
	"coupon_code"=>"",
	"coupon_discount"=>"",
	"custname"=>"b.name",
	"custemail"=>"b.email",
	"address"=>"b",
	"city"=>"h.region",
	"state"=>"c.state",
	"postcode"=>"b.homepostcode",
//	"country"=>"b",
	"phone_no"=>"b.phone",
	"handphone_no"=>"b.handphone",
	"billing_first_name"=>"d",
	"billing_last_name"=>"d",
	"billing_address"=>"d",
	"billing_address_city"=>"d.billing_city",
	"billing_address_state"=>"d.billing_state",
	"billing_address_postcode"=>"d.billing_postcode",
	"billing_address_country"=>"d.billing_country",
	"billing_phone_no"=>"d.billing_phone",
	"billing_handphone_no"=>"d.billing_handphone",
	"receiver_name_for_shipping"=>"f.shipping_name",
	"shipping_address"=>"f",
	"shipping_address_city"=>"i.region",
	"shipping_address_state"=>"g.state",
	"shipping_address_postcode"=>"f.shipping_postcode",
	"shipping_address_country"=>"f.shipping_country",
	"shippingcost"=>"",
	"shipping_phone_no"=>"f.shipping_phone",
	"shipping_handphone_no"=>"f.shipping_handphone",
	"shipping_date"=>"",
	"shipping_installation_option"=>"",
	"shipping_note"=>"");

/* parameter utk select, update tabel doku : cls_shoppingcart */
$arr_par_doku=array(
	"id"=>"select",
	"starttime"=>"update",
	"finishtime"=>"update",
	"trxstatus"=>"update",
	"totalamount"=>"update",
	"transidmerchant"=>"select",
	"session_id"=>"update",
	"response_code"=>"update",
	"creditcard"=>"update",
	"bank"=>"update",
	"approvalcode"=>"update");

/* minimum stok utk pembelian online */
$shoppingcart_buffer_stock=0;
$notification_will_occur_stock=2;
$max_preorder_item=2;

/* interval waktu dalam satuan jam utk menghapus stok yang digantung */
$hour_interval="1 hour";

//$auto_stock_url="http://mzf.modena.co.id/modena_accpac/trx/index.php";
$auto_stock_url="http://localhost/modena_accpac_dev/trx/index.php";

/* untuk real-time stok */
$ftp_address="202.158.114.230";
$ftp_username="modenaim";
$random=rand(0, 9999);

$curl_connection_timeout = 5;
$curl_timeout = 10;

//koleksi cookies
/*
-. lang

*/

//koleksi session
/*
-. shopping_cart[product_id]=qty
-. order_no
-. product_compare[product_id]=product_id : isinya data product id
-. email 
-. sec_code : int random 0-1000000
-. shipping_state, shipping_region : isinya propinsi dan kota pengiriman produk di shopping cart
-. tradein : isinya product id utk trade in
-. homepage : isinya homepage yg sedang dibuka : appliances, professional, culinaria
*/

$maximum_products_compared=5;

if(!isset($_SESSION["sec_code"]))$_SESSION["sec_code"]=rand(0,1000000);
//unset($_SESSION["sec_code"]);

//propinsi yg semua kota-kabupatennya digratiskan ongkos kirimnya
$arr_propinsi_free_shipping=array(13, 26);

// store locator
$arr_store=array(4=>"Modern Chain Stores", 5=>"Conventional Stores", 7=>"Kitchen Specialists", 8=>"Premium Stores", 9=>"Online Stores",10=>"MODENA Home Center",11=>"MODENA Experience Center");
$arr_store_link_orderby=array(11,10,4, 8, 5, 7, 9);
$arr_store_orderby=array(4=>"sort", 5=>"area", 7=>"area", 8=>"area");

//search 
$arr_search_opt=array("product","aboutus",/*"faqs",*/ "", "promos","store");
$arr_search_aboutus=array("profile", "vision", "mission", "our_history", "recognition");
$search_result_per_batch=3;

//member account
$arr_member_account_menu = array("account_information", "current_order", "order_tracking", "product_registration", "registered_product", "communication_archive");

// link login
$TINY_BOX_login = "javascript:TINY.box.show({iframe:'". @$FOLDER_PREFIX ."member_login.php?path=#path#',boxid:'frameless',width:731,height:539,fixed:true,maskid:'greymask',maskopacity:40,close:true}); void(0);";

// link question
$TINY_BOX_question = "javascript:TINY.box.show({iframe:'questions.php?#parameter#',boxid:'frameless',width:676,height:475,fixed:true,maskid:'greymask',maskopacity:40,close:true}); void(0);";

// array berisi data kategori utama di tabel category
$arr_main_product_category = array(1=>"Cooking", 2=>"Cleaning", 3=>"Cooling", 174=>"Masterpiece",176=>"PARTS & ACCESSORIES", 135=>"Cooking", 136=>"Cooling",186=>"PARTS & ACCESSORIES");

// array berisi data halaman mana saja logo dan product category menu dimunculkan.
$arr_logo_and_product_category_menu_enable = array("appliances.php", "professional.php", "culinaria.php", "category.php", "product.php", "promo.php","franchise.php", "survey_page.php");

// konfigurasi default image untuk homepage
define("HOMEPAGE_IMAGE_PATH", "images/home/temp/2/#halaman#/#image#");
// appliances.php
$arr_imageheadline_homepage["appliances.php"][0]["#home_banner#"] = "images/home/temp/2/appliances.php/draft-home.jpg";
$arr_imageheadline_homepage["appliances.php"][0]["#banner_url#"] = "javascript:void(0)";
$arr_imageheadline_homepage["appliances.php"][0]["#font_color#"] = "#FFF";
$arr_imageheadline_homepage["appliances.php"][0]["#string_headline#"] = "New Inspirations for Your Home";
$arr_imageheadline_homepage["appliances.php"][0]["#string_subheadline#"] = "";
$arr_imagecell_homepage["appliances.php"] = array
				(
					1=> array(
								1=>	array("label"=>"cooking", "url"=>"category-1--clearcompare.php#cooking", "path" =>"11"), 
										array("label"=>"cooling", "url"=>"category-3--clearcompare.php#cooling", "path" =>"12"), 
										array("label"=>"cleaning", "url"=>"category-2--clearcompare.php#cleaning", "path" =>"13"), 
								),
					2=> array(
								1=>	array("label"=>"MASTERPIECE", "url"=>"category-174--clearcompare.php#masterpiece", "path" =>"22"), 
										array("label"=>"promotions", "url"=>"promo.php?categoryid=1", "path" =>"27"),
										array("label"=>"PARTS & ACCESSORIES", "url"=>"category-176--clearcompare.php#accesories", "path" =>"25"), 
										
								),
					3=> array(
								1=>	array("label"=>"Franchise", "url"=>"franchise.php", "path" =>"24"),
									array("label"=>"modena professional", "url"=>"professional.php", "path" =>"21"), 
									array("label"=>"Culinaria Modena", "url"=>"culinaria.php", "path" =>"23") 
										 
								),
				);	
$arr_imagepromo_homepage["appliances.php"][0]["#home_banner#"] = "images/home/temp/2/appliances.php/default_promo.jpg";
$arr_imagepromo_homepage["appliances.php"][0]["#banner_url#"] = "javascript:void(0)";
$arr_imagepromo_homepage["appliances.php"][0]["#font_color#"] = "#FFF";
$arr_imagepromo_homepage["appliances.php"][0]["#string_headline#"] = "";
$arr_imagepromo_homepage["appliances.php"][0]["#string_subheadline#"] = "";

// professional
$arr_imageheadline_homepage["professional.php"][0]["#home_banner#"] = "images/home/temp/2/professional.php/draft-home.jpg";
$arr_imageheadline_homepage["professional.php"][0]["#banner_url#"] = "javascript:void(0)";
$arr_imageheadline_homepage["professional.php"][0]["#font_color#"] = "#FFF";
$arr_imageheadline_homepage["professional.php"][0]["#string_headline#"] = "Introducing Modena Professional";
$arr_imageheadline_homepage["professional.php"][0]["#string_subheadline#"] = "MODENA Professional Equipment";
$arr_imagecell_homepage["professional.php"] = array
				(
					1=> array(
								1=>	array("label"=>"cooking", "url"=>"category-135--clearcompare.php#professional-cooking", "path" =>"11"), 
										array("label"=>"cooling", "url"=>"category-136--clearcompare.php#professional-cooling", "path" =>"12"), 
										array("label"=>"promotions", "url"=>"promo.php?categoryid=135", "path" =>"13"), 
								)
				);					
$arr_imagepromo_homepage["professional.php"][0]["#home_banner#"] = "images/home/temp/2/professional.php/default_promo.jpg";
$arr_imagepromo_homepage["professional.php"][0]["#banner_url#"] = "javascript:void(0)";
$arr_imagepromo_homepage["professional.php"][0]["#font_color#"] = "#FFF";
$arr_imagepromo_homepage["professional.php"][0]["#string_headline#"] = "";
$arr_imagepromo_homepage["professional.php"][0]["#string_subheadline#"] = "";
				
// culinaria
$arr_imageheadline_homepage["culinaria.php"][0]["#home_banner#"] = "images/home/temp/2/culinaria.php/draft-home.jpg";
$arr_imageheadline_homepage["culinaria.php"][0]["#banner_url#"] = "javascript:void(0)";
$arr_imageheadline_homepage["culinaria.php"][0]["#font_color#"] = "#FFF";
$arr_imageheadline_homepage["culinaria.php"][0]["#string_headline#"] = "";
$arr_imageheadline_homepage["culinaria.php"][0]["#string_subheadline#"] = "";
$arr_imagecell_homepage["culinaria.php"] = array
				(
					1=> array(
								1=>	array("label"=>"cooking class", "url"=>"javascript:void(0)", "path" =>"11"), 
										array("label"=>"kitchen for rent", "url"=>"javascript:void(0)", "path" =>"12"), 
										array("label"=>"create your own event", "url"=>"javascript:void(0)", "path" =>"13"), 
								),
					2=> array(
								1=>	array("label"=>"inspire me", "url"=>"javascript:void(0)", "path" =>"21"), 
										array("label"=>"macaron class is now up", "url"=>"javascript:void(0)", "path" =>"22"), 
										array("label"=>"get to know modena chef", "url"=>"javascript:void(0)", "path" =>"23") 
								),
				);
	
//negara
include_once "var_country.php";

//masterpiece text
$text_1121="Menghidupkan karakter yang dilukis dan penggunaan warna yang sebanyak mungkin adalah gaya dari pelukis asal Yogyakarta kelahiran tahun 1983 ini. Ciri khas visualnya terkesan kekanakan, menampilkan figur anomali dan juga dengan harmonisasi warna yang menjadi elemen penting di setiap karya. Dimana hal ini baginya adalah celah dengan penuh kemungkinan untuk bermain dengan imajinasi dan kenyataan.";

$text_1122="Menghidupkan karakter yang dilukis dan penggunaan warna yang sebanyak mungkin adalah gaya dari pelukis asal Yogyakarta kelahiran tahun 1983 ini. Ciri khas visualnya terkesan kekanakan, menampilkan figur anomali dan juga dengan harmonisasi warna yang menjadi elemen penting di setiap karya. ";

//$arr_kategori_sp_acs = array(174,175); // --> UTK DATA CBN
$arr_kategori_sp_acs = array(176,186);
$sql = "select categoryid from category where parentcategoryid in (". (implode(",", $arr_kategori_sp_acs)) .")";
$rs_kategori_sp_acs = mysql_query( $sql );
while( $data_kategori_sp_acs = mysql_fetch_array( $rs_kategori_sp_acs ) )
	if( !in_array( $data_kategori_sp_acs["categoryid"], $arr_kategori_sp_acs ) )
		$arr_kategori_sp_acs[] = $data_kategori_sp_acs["categoryid"];

?>