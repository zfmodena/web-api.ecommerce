<?
error_reporting(0); 
$phpversion = phpversion();
list($phpversion_major, $phpversion_minor, $phpversion_release) = explode(".", $phpversion);

/* comment line berikut utk PHP 5.6 */
if( $phpversion_major >= 7 )
    include "mysql_converter.php";

if (strpos($_SERVER["REQUEST_URI"], "'") !== false || strpos($_SERVER["REQUEST_URI"], "*") !== false) {
    header("location:http://". $_SERVER["SERVER_NAME"] . str_replace("'", "", $_SERVER["REQUEST_URI"]));
}
ob_start();
session_start();

define("__ISDEBUG__", false);
define("__ECOMMERCE_ACTIVATION__", true);
define("__MOBILE_ACTIVATION__", true);
define("__FORCED_MOBILEVIEW__", false);

$string="ptindomomulia";

// koneksi database
$server="127.0.0.1";
$username="modenado_root3";
$password="KopiKapalApi05";
$database="modenado_modenaim";

// koneksi database recovery
$server_recovery="127.0.0.1";
$username_recovery="modenadotcom";
$password_recovery="KopiKapalApi05";
$database_recovery="modenado_modenaim";


/* comment line berikut utk PHP 5.6 */
if( $phpversion_major >= 7 )
    $mysqli_connection = mysql_connect($server_recovery, $username_recovery, $password_recovery, $database_recovery) or die(mysqli_connect_error());//("Could not connect :  ". mysql_error());
else{
/* aktifkan line berikut utk PHP 5.6 */
    mysql_connect($server_recovery, $username_recovery, $password_recovery) or die("");//("Could not connect :  ". mysql_error());
    mysql_select_db($database_recovery) or die("");// ("Error connect to database : ". mysql_error());
}
mysql_query("SET SESSION sql_mode = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';");
//mysql_query("SET SESSION sql_mode = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';");
//mysql_query("SET SESSION sql_mode = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';");
//@define("INCLUDE_PATH","/home/modenaim/public_html/lib/pear");
//@define("INCLUDE_PATH","/opt/lampp/htdocs/modena.2/lib/pear");
@define("INCLUDE_PATH",__DIR__ . "/pear");

//koneksi smtp email
//define("SMTP_HOST","smtp.cbn.net.id");
//define("SMTP_HOST","192.168.1.20");
define("SMTP_PORT",587);
define("SMTP_HOST","mail3.modena.co.id");
define("SMTP_AUTH",true);
define("SMTP_USERNAME","support@modena.co.id");
//define("SMTP_USERNAME","");
//define("SMTP_PASSWORD","sp*328");
define("SMTP_PASSWORD","5upp0RT@m0d3n4");
//define("SMTP_PASSWORD","");
define("CRLF","\n");

define("CUSTOMERCARE_EMAIL", "customercare@modena.co.id");
define("SALESADMIN_EMAIL", "Diah.Aryati@modena.com");
define("FINANCE_EMAIL", "collection.finance@modena.com");
define("WAREHOUSE_EMAIL", "warehouse@modena.co.id");
define("SHOWROOM_EMAIL", "showroom.center@modena.co.id");
define("SUPPORT_EMAIL", "support@modena.co.id");
define("MANAGEMENT_EMAIL", "support@modena.co.id");
define("MARCOMM_EMAIL", "vina.wijaya@modena.co.id");
define("CULINARIA_EMAIL", "marketing.culinaria@modena.co.id");
define("EMAIL_TEMPLATE","template/email.html");

//email HRD utk keperluan pengiriman aplikasi online
$email_hrd="hrd@modena.co.id,kevin.korompis@modena.co.id,esa.wibawa@modena.co.id";
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
$minimum_shipping_time=3;
$maximum_shipping_time=30;
$tambahan_shipping_cost=50000;
define("FREE_SHIPPINGCOST",0.13); // persentase dari sales grand total

/* diskon */
$diskon=0.15;
$diskon_sp=0.15;
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
$url_path="https://www.modena.co.id/";
$payment_va_expiration="125";// satuan menit

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
$shoppingcart_buffer_stock=3;
$notification_will_occur_stock=2;
$max_preorder_item=2;

/* interval waktu dalam satuan jam utk menghapus stok yang digantung */
$hour_interval="1 hour";

$auto_stock_url="https://api.web.modena.com/modena_accpac/trx/index.php";
$culinaria_auto_stock_url="https://api.web.modena.com/culinaria_modena_accpac/trx/index.php";

/* untuk real-time stok */
$ftp_address="202.158.114.230";
$ftp_username="modenaim";
$random=rand(0, 9999);

$curl_connection_timeout = 10;
$curl_timeout = 15;

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
$arr_main_product_category = array(1=>"Cooking", 2=>"Cleaning", 3=>"Cooling",187=>"Masterpiece",174=>"PARTS & ACCESSORIES", 135=>"Cooking", 136=>"Cooling",175=>"PARTS & ACCESSORIES",197=>"Small Equipment");

// array berisi data halaman mana saja logo dan product category menu dimunculkan.
$arr_logo_and_product_category_menu_enable = array(
	"appliances.php", 
	"professional.php", 
	"culinaria.php", 
	"category.php", 
	"product.php", 
	"promo.php", 	
	"culinaria-intro.php",
	"culinaria-program.php",
	"culinaria-program-detail.php",
	"culinaria-menu.php",
	"culinaria-space.php",
	"culinaria-gallery.php",
	"culinaria-gallery-detail.php",
	"culinaria-register.php",
	"culinaria-register-cooking-class.php",
	"culinaria-register-space.php",
	"culinaria-cart.php",
	"culinaria_shoppingcart_proceed.php",
	"culinaria_confirmation.php",
	"m.culinaria.php",
	"culinaria-promotions.php",
	"franchise.php",
	"survey_page.php"
);

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
								1=>	array("label"=>"modena professional", "url"=>"professional.php", "path" =>"21"), 
										array("label"=>"Culinaria Modena", "url"=>"culinaria.php", "path" =>"22"), 
										array("label"=>"promotions", "url"=>"promo.php?categoryid=1", "path" =>"23") 
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
$arr_imageheadline_homepage["professional.php"][0]["#string_headline#"] = "Introducing MODENA Professional";
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
								1=>	array("label"=>"cooking class", "url"=>"culinaria-register.php", "path" =>"11"), 
										array("label"=>"kitchen for rent", "url"=>"javascript:void(0)", "path" =>"12"), 
										array("label"=>"create your own event", "url"=>"javascript:void(0)", "path" =>"13"), 
								),
					/*2=> array(
								1=>	array("label"=>"inspire me", "url"=>"javascript:void(0)", "path" =>"21"), 
										array("label"=>"macaron class is now up", "url"=>"javascript:void(0)", "path" =>"22"), 
										array("label"=>"get to know modena chef", "url"=>"javascript:void(0)", "path" =>"23") 
								),*/
				);
$arr_imagepromo_homepage["culinaria.php"][0]["#home_banner#"] = "images/home/temp/2/culinaria.php/default_promo.jpg";
$arr_imagepromo_homepage["culinaria.php"][0]["#banner_url#"] = "javascript:void(0)";
$arr_imagepromo_homepage["culinaria.php"][0]["#font_color#"] = "#FFF";
$arr_imagepromo_homepage["culinaria.php"][0]["#string_headline#"] = "";
$arr_imagepromo_homepage["culinaria.php"][0]["#string_subheadline#"] = "";
	
//negara
include_once "var_country.php";
$arr_kategori_sp_acs = array(174,175); // --> UTK DATA CBN
$arr_kategori_fg = array(1,2,3,187,135,136);
//$arr_kategori_sp_acs = array(176,186);
$sql = "select categoryid from category where parentcategoryid in (". (implode(",", $arr_kategori_sp_acs)) .")";
$rs_kategori_sp_acs = mysql_query( $sql );
while( $data_kategori_sp_acs = mysql_fetch_array( $rs_kategori_sp_acs ) )
	if( !in_array( $data_kategori_sp_acs["categoryid"], $arr_kategori_sp_acs ) )
		$arr_kategori_sp_acs[] = $data_kategori_sp_acs["categoryid"];

	
$arr_kategori_stiker = array(207);
$product_custom=array(
	"1373" => array(
		"margin-top" => 67,
		"width"		=> 178,
		"height"	=> 563,
		"list-border" => array(
			"1373-list-1.png" => array(
				"top" => '64%',
    			"left" => '44.2%',
    			"css" => "width: 179px;"
			),
			"1373-list-2.png"  => array(
				"top" => '18.9%',
    			"left"=> '52%',
    			"css" => 'width: 19px;padding-left: 3px;height: 74px;'
			)
		),
		"template-img" => array(
			"left" =>33.46,
			"top"	=> '50px'
		),
		"stickerid" => 1528,
		"spec" => "SPEK KULKAS RF 2336 L.png"
	),
	"1370" => array(
		"margin-top" => 110,
		"width"		=> 247,
		"height"	=> 545,
		"list-border" => array(
			"1370-list-1.png" => array(
				"top" => '119px',
    			"left" => '52.9%',
    			"css" => 'height: 535px;'
			),
			"1370-list-2.png"  => array(
				"top" => '386.8px',
    			"left"=> '40.785%',
    			"css" => 'width: 247px;'
			),
			"logo modena.png"  => array(
				"top" => '122px',
    			"left"=> '60.7%',
    			"css" => 'width: 30px;'
			)
		),
		"template-img" => array(
			"left" =>32.8,
			"top"	=> '80px'
		),
		"stickerid" => 1529,
		"spec" => "SPEK KULKAS RF 4540 L.png"
	),
	"1371" => array(
		"margin-top" => 67,
		"width"		=> 280,
		"height"	=> 601,
		"list-border" => array(
			"1371-list-1.png" => array(
				"top" => '66px',
    			"left" => '51%',
    			"css" => 'height: 606px;'
			),
			"1371-list-2.png"  => array(
				"top" => '398px',
    			"left"=> '52.8%',
    			"css" => 'width: 143px;'
			),
			"logo modena.png"  => array(
				"top" => '81.5px',
    			"left"=> '62.4%',
    			"css" => 'width: 31px;'
			)
		),
		"template-img" => array(
			"left" =>27.4,
			"top"	=> '0px'
		),
		"stickerid" => 1530,
		"spec" => "SPEK KULKAS RF 2555 L.png"
	),
	"1527" => array(
		"margin-top" => 59,
		"width"		=> 152,
		"height"	=> 585,
		"list-border" => array(
			"1276-list-1.png" => array(
			 	"top" => '44px',
     			"left" => '45%',
     			"css" => 'width: 164px;'
			 ),
			"logo modena 2.png"  => array(
				"top" => '72px',
    			"left"=> '50.7%',
    			"css" => 'width: 45px;'
			)
		),
		"template-img" => array(
			"left" =>39.4,
			"top"	=> '19px'
		),
		"stickerid" => 1535,
		"spec" => "SPEK DISPENSER PD F30A S.png"
	),
	"1107" => array(
		"margin-top" => 226,
		"width"		=> 296,
		"height"	=> 298,
		"list-border" => array(
			 "1107-list-1.png" => array(
			 	"top" => '249px',
     			"left" => '46.75%',
     			"css" => 'height: 261px;width: 126px;'
			 ),
			 "1107-list-2.png" => array(
			 	"top" => '222px',
     			"left" => '37.99%',
     			"css" => 'width: 302px;'
			 ),
			 "1107-list-3.png" => array(
			 	"top" => '481px',
     			"left" => '37.99%',
     			"css" => 'width: 302px;'
			 ),
			 "1107-list-4.png" => array(
			 	"top" => '225px',
     			"left" => '38.1%',
			 ),
			 "1107-list-5.png" => array(
			 	"top" => '225px',
     			"left" => '65.7%',
			 )
		),
		"template-img" => array(
			"left" =>38.1,
			"top"	=> '225px'
		),
		"stickerid" => 1532,
		"spec" => "SPEK WATER HEATER ES 15 D.png"
	),
	"1108" => array(
		"margin-top" => 226,
		"width"		=> 296,
		"height"	=> 298,
		"list-border" => array(
			 "1107-list-1.png" => array(
			 	"top" => '249px',
     			"left" => '46.75%',
     			"css" => 'height: 261px;width: 126px;'
			 ),
			 "1107-list-2.png" => array(
			 	"top" => '222px',
     			"left" => '37.99%',
     			"css" => 'width: 302px;'
			 ),
			 "1107-list-3.png" => array(
			 	"top" => '481px',
     			"left" => '37.99%',
     			"css" => 'width: 302px;'
			 ),
			 "1107-list-4.png" => array(
			 	"top" => '225px',
     			"left" => '38.1%',
			 ),
			 "1107-list-5.png" => array(
			 	"top" => '225px',
     			"left" => '65.7%',
			 )
		),
		"template-img" => array(
			"left" =>38.1,
			"top"	=> '225px'
		),
		"stickerid" => 1533,
		"spec" => "SPEK WATER HEATER ES 10 D.png"
	),
	"1106" => array(
		"margin-top" => 226,
		"width"		=> 296,
		"height"	=> 298,
		"list-border" => array(
			 "1107-list-1.png" => array(
			 	"top" => '249px',
     			"left" => '46.75%',
     			"css" => 'height: 261px;width: 126px;'
			 ),
			 "1107-list-2.png" => array(
			 	"top" => '222px',
     			"left" => '37.99%',
     			"css" => 'width: 302px;'
			 ),
			 "1107-list-3.png" => array(
			 	"top" => '481px',
     			"left" => '37.99%',
     			"css" => 'width: 302px;'
			 ),
			 "1107-list-4.png" => array(
			 	"top" => '225px',
     			"left" => '38.1%',
			 ),
			 "1107-list-5.png" => array(
			 	"top" => '225px',
     			"left" => '65.7%',
			 )
		),
		"template-img" => array(
			"left" =>38.1,
			"top"	=> '225px'
		),
		"stickerid" => 1534,
		"spec" => "SPEK WATER HEATER ES 30 D.png"
	),
	"1275" => array(
		"margin-top" => 113,
		"width"		=> 192,
		"height"	=> 562,
		"list-border" => array(
			 "1275-list-1.png" => array(
			 	"top" => '228px',
     			"left" => '62.58%',
     			"css" => 'height: 302px;'
			 ),
			 "1275-list-2.png" => array(
			 	"top" => '350px',
     			"left" => '43.3%',
     			"css" => 'width: 192px;height: 34px;'
			 )
		),
		"template-img" => array(
			"left" =>43.3,
			"top"	=> '32px'
		),
		"stickerid" => 1531,
		"spec" => "SPEK DISPENSER DD 1380.png"
	),
	"1132" => array(
		"margin-top" => 231,
		"width"		=> 525,
		"height"	=> 295,
		"list-border" => array(
			"1132-list-1.png" => array(
			 	"top" => '458px',
     			"left" => '49.9%',
     			"css" => 'width: 71px;'
			 ),
			"1132-list-2.png" => array(
			 	"top" => '231px',
     			"left" => '76.1%',
     			"css" => 'height: 303px;'
			 ),
			"1132-list-3.png" => array(
			 	"top" => '226px',
     			"left" => '22.99%',
     			"css" => 'height: 306px;'
			 ),
			 "logo modena.png"  => array(
				"top" => '248px',
    			"left"=> '50.5%',
    			"css" => 'width: 59px;'
			)
		),
		"template-img" => array(
			"left" =>23.1,
			"top"	=> '32px'
		),
		"stickerid" => 1536,
		"spec" => "SPEK HOOD CX 7712 L.png"
	),
	"976" => array(
		"margin-top" => 232,
		"width"		=> 553,
		"height"	=> 303,
		"list-border" => array(
			"976-list-1.png" => array(
			 	"top" => '241px',
     			"left" => '25.47%',
     			"css" => 'height: 306px;'
			 ),
			"976-list-2 - Copy.png" => array(
			 	"top" => '231px',
     			"left" => '75.5%',
     			"css" => 'height: 304px;'
			 ),
			"976-list-3.png" => array(
			 	"top" => '255px',
     			"left" => '41.1%',
     			"css" => 'height: 247px;'
			 ),
			"logo modena 3.png"  => array(
				"top" => '257px',
    			"left"=> '49.39%',
    			"css" => 'width: 80px;'
			)
		),
		"template-img" => array(
			"left" =>25.5,
			"top"	=> '102px'
		),
		"stickerid" => 1537,
		"spec" => "SPEK HOOD CX 9701 L.png"
	)
);

$arr_sticker_lokasi_valid = array("244","246","250","251","267","268","271", 274,275,276,277,278);

// utk API website versi baru
define("__KEY__", "KopiKapalApi17");
define("__KEY_AIR__", "ptim328");
define("__API__", "https://dm.modena.com/ws/");
define("__EMAIL__", "zaenal.fanani@modena.co.id");
define("__LANG__", "id");
define("__GUDANG_PUSAT__", "WH-KWFGA");
define("__GUDANG_PUSAT_TGN__", "WH-TGFGA");
define("__IDCUST_FG__", "C018-000389");
define("__IDCUST_FG_TRA__", "C018-000389");
define("__IDCUST_CUL__", "C018-000389");
define("__DISC_TRADEIN__", 0.15);
define("__CURL_TIMEOUT", 20);
define("__DURASI_HARI_KIRIM_REGULER__", 3);
define("__SHOWROOM_INISIAL__", "MAPI");
define("__STOK_AMAN__", 10);
$arr_showroom_inisial_diperbolehkan = array("A"=>"Aplikasi C2C"); // kode statis diparse sewaktu panggil API cart di aplikasi klien
//define("__IDCUST_FG__", "DS15A11012B0");

?>
