<?
include_once("var54.php");
include_once("mobile_detect.php");
include_once("cls_main.php");

// check session validitas 
if( @$_SESSION["login_session"] != "" && @$_SESSION["email"] != "" ){
	include_once("cls_member.php");
	$check_session = new member;
	if( !$check_session->check_login_session(false, $_SESSION["login_session"]) ) 
		@$maic .= "
			function load_box_login_session_expired(){
				TINY.box.show({iframe:'session_expired.php',boxid:'frameless',width:431,height:219,fixed:true,maskid:'greymask',maskopacity:40,close:true});
			}
			try{
				$(document).ready(function(){
					load_box_login_session_expired();
				})
			}catch(e){
				document.body.onload = load_box_login_session_expired();
			}";
}

// page
$arr_page=preg_split("/\//", $_SERVER['SCRIPT_FILENAME']);
$page=$arr_page[count($arr_page)-1];

// auto updating price
@include_once "auto-price-update.php";

// language
if(isset($_COOKIE["lang"])&&$_COOKIE["lang"]!="")$lang=$_COOKIE["lang"];
if(isset($_REQUEST["lg"])&&$_REQUEST["lg"]!="")$lang=$_REQUEST["lg"];
$offset_avail_lang=main::get_array_index($arr_lang, $lang, "key");
$tmp_arr_lang = $arr_lang;
array_splice($tmp_arr_lang, $offset_avail_lang, 1);
$arr_avail_lang=array_keys($tmp_arr_lang);
$arr_avail_language=array_values($tmp_arr_lang);

mysql_query("SET lc_time_names = '". $arr_lang_mysql[$lang] ."'");
	
//delete all unconfirmed stock purchased
main::__delete_all_pending_product_inventory_purchased($hour_interval);
//delete all unconfirmed doku transaction -  ga jadi, jangan soalnya transaksi yg sukses ikut kehapus, transaksi gagal biarin aja ga usah dihapus, dipake sebagai history
//main::__delete_all_pending_doku($hour_interval);

//set tanggal
$date=getdate();

// redirect utk desain member login baru
if( $page == "member_login.php" ){
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') main::reload_page( $_REQUEST, "member_login_simple.php" );
	else header("location:member_login_simple.php?" . http_build_query($_REQUEST) ) ;
	exit;
}

//################################ commands ##############################

if(isset($_REQUEST["c"]))
	
	if($_REQUEST["c"]=="lang"){
	setcookie("lang", $_REQUEST["lg"]);
	
// PRODUCT CATALOG	- SHOPPING CART
	}elseif($_REQUEST["c"]=="product_list"){

	}elseif($_REQUEST["c"]=="addcompare"){
		$_SESSION["product_compare"][$_REQUEST["productid"]]=$_REQUEST["productid"];
	
	}elseif($_REQUEST["c"]=="deletecompare"){
		unset($_SESSION["product_compare"][$_REQUEST["productid"]]);	

	}elseif($_REQUEST["c"]=="clearcompare"){
		unset($_SESSION["product_compare"]);	
	
	}elseif($_REQUEST["c"]=="addcart" || $_REQUEST["c"]=="addcart_bulk"){
		include_once "lib/cls_discount.php";
		if($_REQUEST["c"]=="addcart_bulk"){
			//save new shipping destination
			if($_REQUEST["s_shipping_address"]!="add" && $_REQUEST["s_shipping_address"]!=""){
				include_once "lib/cls_member.php";
				$member_shipping=new member;
				$member_shipping->email=$_SESSION["email"];
				$member_shipping->enabled="=true ";
				$rs_member_shipping=$member_shipping->member_data_shipping(@$_REQUEST["s_shipping_address"]);
				$_member_shipping=mysql_fetch_array($rs_member_shipping);
				$_SESSION["shipping_state"]=$_member_shipping["shipping_state"];		$_SESSION["shipping_region"]=$_member_shipping["shipping_region"];
				
			}else{	$_SESSION["shipping_state"]=@$_REQUEST["t_state"];		$_SESSION["shipping_region"]=@$_REQUEST["s_city"];	}

			for($x=0; $x<count(@$_REQUEST["productid"]); $x++){
				//if(isset($_SESSION["shopping_cart"]))
					//if(isset($_SESSION["shopping_cart"][$_REQUEST["productid"][$x]]))
						$_SESSION["shopping_cart"][$_REQUEST["productid"][$x]]=
							(isset($_REQUEST["qty_".$_REQUEST["productid"][$x]]) && $_REQUEST["qty_".$_REQUEST["productid"][$x]]>0?$_REQUEST["qty_".$_REQUEST["productid"][$x]]:1);
					//else $_SESSION["shopping_cart"][$_REQUEST["productid"][$x]]=1;
				//else $_SESSION["shopping_cart"][$_REQUEST["productid"][$x]]=1;
			}

		}else{
			//if(isset($_SESSION["shopping_cart"]))
				//if(isset($_SESSION["shopping_cart"][$_REQUEST["productid"]]))
					$_SESSION["shopping_cart"][$_REQUEST["productid"]]=
						(isset($_REQUEST["qty_".$_REQUEST["productid"]]) && $_REQUEST["qty_".$_REQUEST["productid"]]>0?$_REQUEST["qty_".$_REQUEST["productid"]]:1);
				//else $_SESSION["shopping_cart"][$_REQUEST["productid"]]=1;
			//else $_SESSION["shopping_cart"][$_REQUEST["productid"]]=1;
			
		}

		//save shoppingcart data before continue
		include_once "lib/cls_shoppingcart.php";				
						
		if(isset($_SESSION["email"])&&$_SESSION["email"]!=""){			
			if(!isset($_SESSION["order_no"]))$_SESSION["order_no"]=shoppingcart::__insert_order($_SESSION["email"]);
			if(isset($_SESSION["shopping_cart"])&&count($_SESSION["shopping_cart"])>0){
				shoppingcart::__delete_all_product($_SESSION["email"], 0);				
				
				$arr_product_available_quantity = shoppingcart::arr_product_available_quantity( $_SESSION["shopping_cart"], 
						(@$_REQUEST["state_id"]?$_REQUEST["state_id"]:@$_SESSION["shipping_state"]), 
						(@$_REQUEST["region_id"]?$_REQUEST["region_id"]:@$_SESSION["shipping_region"]) 
						);
				
				foreach($_SESSION["shopping_cart"] as $productid=>$quantity){// check productid quantity availability in stock					
					if(shoppingcart::is_valid_product_id($productid)){
						if(/*shoppingcart::product_available_quantity($productid, $quantity, 
							(@$_REQUEST["state_id"]?$_REQUEST["state_id"]:@$_SESSION["shipping_state"]), 
							(@$_REQUEST["region_id"]?$_REQUEST["region_id"]:@$_SESSION["shipping_region"])	)*/ $arr_product_available_quantity[ $productid ] <$shoppingcart_buffer_stock)
							$_SESSION["shopping_cart"][$productid]=0;
													
					}else unset($_SESSION["shopping_cart"][$productid]);
				}		
				
				foreach($_SESSION["shopping_cart"] as $productid=>$quantity){// mekanisme automatic tambah 1 item free di shopping cart
					$gift=order_online_promo::remark_disc_peritem($productid);			
					if(@$gift[4]!=""&& @$gift[5]==1  && order_online_promo::check_ipc_per_item($productid, @$_REQUEST["t_discount_coupon"])!==false){								
						$rs_productsrc=order_online_promo::discount_product_source($gift[4]);
						if(mysql_num_rows($rs_productsrc)>0){
							$_SESSION["shopping_cart"][@$gift[4]]=0;
							while($productsrc=mysql_fetch_array($rs_productsrc))
								if(array_key_exists($productsrc["productid"], $_SESSION["shopping_cart"]) )
									$_SESSION["shopping_cart"][@$gift[4]]	+=	$_SESSION["shopping_cart"][$productsrc["productid"]]; 
						}
					}														
					$maximumqty=order_online_promo::maximum_productpromo_qty($productid);
					if($_SESSION["shopping_cart"][$productid]>$maximumqty && $maximumqty>0)
						$_SESSION["shopping_cart"][$productid]=$maximumqty;
				}
				
				shoppingcart::__insert_product($_SESSION["email"], $_SESSION["shopping_cart"], $diskon);
			}
		}else{
			
			$arr_product_available_quantity = shoppingcart::arr_product_available_quantity( $_SESSION["shopping_cart"], 
						(@$_REQUEST["state_id"]?$_REQUEST["state_id"]:@$_SESSION["shipping_state"]), 
						(@$_REQUEST["region_id"]?$_REQUEST["region_id"]:@$_SESSION["shipping_region"]) 
						);
						
			foreach($_SESSION["shopping_cart"] as $productid=>$quantity){// check productid quantity availability in stock					
				if(shoppingcart::is_valid_product_id($productid)){
					if( /*shoppingcart::product_available_quantity($productid, $quantity, 
						(@$_REQUEST["state_id"]?$_REQUEST["state_id"]:@$_SESSION["shipping_state"]), 
						(@$_REQUEST["region_id"]?$_REQUEST["region_id"]:@$_SESSION["shipping_region"])	)*/ $arr_product_available_quantity[ $productid ] < $shoppingcart_buffer_stock)
						$_SESSION["shopping_cart"][$productid]=0;
												
				}else unset($_SESSION["shopping_cart"][$productid]);
			}		
			
			foreach($_SESSION["shopping_cart"] as $productid=>$quantity){// mekanisme automatic tambah 1 item free di shopping cart
				$gift=order_online_promo::remark_disc_peritem($productid);			
				if(@$gift[4]!=""&& @$gift[5]==1  && order_online_promo::check_ipc_per_item($productid, @$_REQUEST["t_discount_coupon"])!==false){								
					$rs_productsrc=order_online_promo::discount_product_source($gift[4]);
					if(mysql_num_rows($rs_productsrc)>0){
						$_SESSION["shopping_cart"][@$gift[4]]=0;
						while($productsrc=mysql_fetch_array($rs_productsrc))
							if(array_key_exists($productsrc["productid"], $_SESSION["shopping_cart"]) )
								$_SESSION["shopping_cart"][@$gift[4]]	+=	$_SESSION["shopping_cart"][$productsrc["productid"]]; 
					}
				}														
				$maximumqty=order_online_promo::maximum_productpromo_qty($productid);
				if($_SESSION["shopping_cart"][$productid]>$maximumqty && $maximumqty>0)
					$_SESSION["shopping_cart"][$productid]=$maximumqty;
			}
		}

	}elseif($_REQUEST["c"]=="deletecart"){
		unset($_SESSION["shopping_cart"][$_REQUEST["productid"]]);
		//save shoppingcart data before continue
		include_once "lib/cls_shoppingcart.php";	include_once "lib/cls_discount.php";	
		if(isset($_SESSION["email"])&&$_SESSION["email"]!="")		shoppingcart::__delete_all_product($_SESSION["email"], 0);

		$arr_product_available_quantity = shoppingcart::arr_product_available_quantity( $_SESSION["shopping_cart"], @$_SESSION["shipping_state"], @$_SESSION["shipping_region"] );

		foreach($_SESSION["shopping_cart"] as $productid=>$quantity){// check productid quantity availability in stock
			if(shoppingcart::is_valid_product_id($productid)){
				$maximumqty=order_online_promo::maximum_productpromo_qty($productid);
				//if(shoppingcart::product_available_quantity($productid, $quantity, @$_SESSION["shipping_state"], @$_SESSION["shipping_region"])<$shoppingcart_buffer_stock)$_SESSION["shopping_cart"][$productid]=0;
				if($arr_product_available_quantity[ $productid ]<$shoppingcart_buffer_stock){$_SESSION["shopping_cart"][$productid]=0;}
				if($quantity>$maximumqty && $maximumqty>0){$_SESSION["shopping_cart"][$productid]=$maximumqty;}
			}else unset($_SESSION["shopping_cart"][$productid]);
		}
		if(isset($_SESSION["email"])&&$_SESSION["email"]!="")		shoppingcart::__insert_product($_SESSION["email"], $_SESSION["shopping_cart"], $diskon);

	}elseif($_REQUEST["c"]=="cancel_order"){
		if(isset($_SESSION["shopping_cart"]))unset($_SESSION["shopping_cart"]);
		if(isset($_SESSION["email"])&&$_SESSION["email"]!=""){
			include_once "lib/cls_shoppingcart.php";
			shoppingcart::__delete_all_product(@$_SESSION["email"], 0);
			shoppingcart::__delete_order(@$_SESSION["order_no"]);
			//shoppingcart::__delete_doku_data(@$_SESSION["order_no"]);
			if(isset($_SESSION["order_no"]))unset($_SESSION["order_no"]);// sekalian harus dimatikan session order_no utk menghindari duplikasi nomor order oleh customer lain
		}
			
	}elseif($_REQUEST["c"]=="shipment_estimation"){
		include_once("cls_shoppingcart.php");include_once("cls_product.php");include_once "cls_discount.php";
		include_once("../lang/".$lang.".php");

		//itung total sales
		$product_list=new product;
		$product_list->distinct=true;
		$product_list->penable="='Y'";
		foreach($_SESSION["shopping_cart"] as $productid=>$qty){
			if(isset($s_productid))$s_productid.=",".$productid;
			else $s_productid=$productid;
		}
		
		// cek tipe produk barang jadi / SP, apabila mixed atau barang jadi saja, biaya kirim seperti sekarang... apabila hanya spare part, biaya kirim menggunakan API 21express
		
		$product_list->productid=" in (".main::formatting_query_string($s_productid).")";
		$product_list->orderby="name";
		$rs_product_list=$product_list->product_list("'%'");
		$subtotal=0;
		while($rs_product_list_=mysql_fetch_array($rs_product_list)){	//$subtotal+=$rs_product_list_["price"]*$_SESSION["shopping_cart"][$rs_product_list_["productid"]];
/*			$diskon_peritem=new order_online_promo;
			$diskon_peritem->price_off_peritem($rs_product_list_["productid"], $rs_product_list_["price"], $_SESSION["shopping_cart"][$rs_product_list_["productid"]]);
			$subtotal+=($rs_product_list_["price"]-($rs_product_list_["price"]*$diskon_peritem->discount_peritem()))*$_SESSION["shopping_cart"][$rs_product_list_["productid"]];*/
			$diskon_peritem=new order_online_promo;
			$diskon_peritem->price_off_peritem($rs_product_list_["productid"], $rs_product_list_["price"], $_SESSION["shopping_cart"][$rs_product_list_["productid"]], "", @$_REQUEST["t_discount_coupon"]);
			
			$product_subtotal=$diskon_peritem->disc_gt_peritem();
			$product_price_discount=0;
			if($diskon_peritem->discount_peritem()<=0){	
				$tradein_discount=order_online_promo::get_trade_in($rs_product_list_["productid"]);
				if(@in_array($rs_product_list_["productid"],@$_SESSION["tradein"])) {			
					$product_subtotal=(
						$tradein_discount<1? (	$rs_product_list_["price"]-($rs_product_list_["price"]*$tradein_discount)	):
							($rs_product_list_["price"]-$tradein_discount)
						)*$_SESSION["shopping_cart"][$rs_product_list_["productid"]];
				}
			}
			if($diskon_peritem->disc_peritem()=="FREE")$product_subtotal=0;
			$subtotal+=$product_subtotal;			
		}
		
		if(order_online_promo::thediscount(false, @$_REQUEST["t_discount_coupon"])!="")
			$discount_subtotal=order_online_promo::price_off($_SESSION["shopping_cart"],$subtotal,"disc_gt", @$_REQUEST["t_discount_coupon"]);
		else 
			$discount_subtotal=$subtotal;

		if(@$_REQUEST["t_discount_coupon"]!="" && !$is_discount_per_item && order_online_promo::is_ipc_all_item(@$_REQUEST["t_discount_coupon"])){
			$discount_coupon_=main::counting_discount($_REQUEST["t_discount_coupon"], $discount_subtotal);
			$discount_coupon=$discount_coupon_[0];
			$discount_price=$discount_coupon_[1];
			$discount_grandtotal=$discount_coupon_[2];	
			if($discount_coupon==0){
				$s_discount="<br />".$lang_error_discount_coupon;
				$add_error=shoppingcart::get_remark_coupon_code($_REQUEST["t_discount_coupon"]);
				if($add_error!="") 	$s_discount=str_replace("#add_error#", "<li>".$add_error."</li>", $s_discount);
				else 				$s_discount=str_replace("#add_error#", "", $s_discount);
//				$s_column_discount="0 <sup>[".(order_online_promo::thediscount(false, @$_REQUEST["t_discount_coupon"])!=""?"2":"1")."]</sup>";
//				$s_note_coupon_code="<br /><sup>[".(order_online_promo::thediscount(false, @$_REQUEST["t_discount_coupon"])!=""?"2":"1")."]</sup> ".$lang_promo_code_discount.", ".(0*100)."";
			}elseif($discount_coupon<0){
				unset($discount_grandtotal);// PC all item
				$discount=order_online_promo::check_ipc(@$_REQUEST["t_discount_coupon"]);
				if($discount===false){$s_discount=str_replace("#add_error#", "", "<br />".$lang_error_discount_coupon);goto SkipPc;}
				foreach($_SESSION["shopping_cart"] as $productid=>$qty)						
					if( /*!order_online_promo::check_ipc_per_item($productid, @$_REQUEST["t_discount_coupon"]) &&
						!order_online_promo::check_ipc_per_item($productid) &&
						!order_online_promo::is_ipc_all_item(@$_REQUEST["t_discount_coupon"]) && */
						$discount>0) 
						$s_discount=str_replace("#add_error#", "<li>Kode :: Err. EMP</li>", "<br />".$lang_error_discount_coupon);
				SkipPc:
			}else {
				$s_column_discount=number_format($discount_price)." <sup>[".(order_online_promo::thediscount(false, @$_REQUEST["t_discount_coupon"])!=""?"2":"1")."]</sup>";
				$s_note_coupon_code="<br /><sup>[".(order_online_promo::thediscount(false, @$_REQUEST["t_discount_coupon"])!=""?"2":"1")."]</sup> ".$lang_promo_code_discount.", ".
					($discount_coupon>1?
						"Rp".number_format($discount_coupon)."</span>":
						($discount_coupon*100)."%</span>"
					);
			}
			$add_script="<script>parent.document.getElementById('column_discount').innerHTML='".(@$s_column_discount!=""?"Rp".$s_column_discount:"")."';</script>";
			$add_script.="<script>parent.document.getElementById('note_coupon_code').innerHTML='".@$s_note_coupon_code."';</script>";
			$add_script.="
				<script>
				var ed = parent.document.getElementById('error_discount');
				var disc = parent.document.getElementById('t_discount_coupon');
				if(disc.value != '". $lang_discount_coupon_info ."' && '".@$s_discount."' != ''){
					ed.setAttribute('title', '".@$s_discount."');
					ed.style.display='block';
				}
				</script>";
		}
		
		$sub_grandtotal=(isset($discount_grandtotal)?$discount_grandtotal:$discount_subtotal);
				
		$product_weight=shoppingcart::get_total_product_weight($_SESSION["shopping_cart"], $lang);
		$state_id=preg_split("/\|/", $_REQUEST["t_state"]);
		$shipping_cost=shoppingcart::get_shipping_cost($sub_grandtotal, $product_weight, $state_id[0],$_REQUEST["s_city"]);//
		echo @$add_script."<script>";
		echo "
			parent.document.getElementById('span_ship_cost').innerHTML='".	($shipping_cost>0?"Rp".number_format($shipping_cost):"FREE")		."';
			parent.document.getElementById('column_grandtotal').innerHTML='Rp".number_format(	$sub_grandtotal+($shipping_cost>0?($shipping_cost):$shipping_cost)	)."';
			";
		echo "try{parent.TINY.box.hide();}catch(e){TINY.box.hide();}</script>";
		exit;
			
	}elseif($_REQUEST["c"]=="discount_coupon"){
		goto discount_coupon_Skip;
		include_once("../lang/".$lang.".php");
		include_once("../lib/cls_discount.php");include_once("../lib/cls_product.php");include_once("../lib/cls_shoppingcart.php");
		
		//itung total sales
		$product_list=new product;
		$product_list->distinct=true;
		$product_list->penable="='Y'";
		foreach($_SESSION["shopping_cart"] as $productid=>$qty){
			if(isset($s_productid))$s_productid.=",".$productid;
			else $s_productid=$productid;
		}
		$product_list->productid=" in (".main::formatting_query_string($s_productid).")";
		$product_list->orderby="name";
		$rs_product_list=$product_list->product_list("'%'");
		$subtotal=0;
		while($rs_product_list_=mysql_fetch_array($rs_product_list)){	//$subtotal+=$rs_product_list_["price"]*$_SESSION["shopping_cart"][$rs_product_list_["productid"]];
			$diskon_peritem=new order_online_promo;
			$diskon_peritem->price_off_peritem($rs_product_list_["productid"], $rs_product_list_["price"], $_SESSION["shopping_cart"][$rs_product_list_["productid"]],"",@$_REQUEST["t_discount_coupon"]);
			$subtotal+=($rs_product_list_["price"]-($rs_product_list_["price"]*$diskon_peritem->discount_peritem()))*$_SESSION["shopping_cart"][$rs_product_list_["productid"]];
		}
		
		if(order_online_promo::thediscount(false, @$_REQUEST["t_discount_coupon"])!="")
			$discount_subtotal=order_online_promo::price_off($_SESSION["shopping_cart"],$subtotal,true, @$_REQUEST["t_discount_coupon"]);
		else 
			$discount_subtotal=$subtotal;
				
		$discount_coupon_=main::counting_discount(main::formatting_query_string($_REQUEST["code"]), $discount_subtotal);
		$discount_coupon=$discount_coupon_[0];
		$discount_price=$discount_coupon_[1];
		$discount_grandtotal=$discount_coupon_[2];
		if($discount_coupon==0){
			$s_discount="<br />".$lang_error_discount_coupon;
			$add_error=shoppingcart::get_remark_coupon_code($_REQUEST["code"]);
			if($add_error!="") 	$s_discount=str_replace("#add_error#", "<li>".$add_error."</li>", $s_discount);
			else 				$s_discount=str_replace("#add_error#", "", $s_discount);
			$s_column_discount="0 <sup>[".(order_online_promo::thediscount()!=""?"2":"1")."]</sup>";
			$s_note_coupon_code="<br /><sup>[".(order_online_promo::thediscount()!=""?"2":"1")."]</sup> ".$lang_promo_code_discount.", ".(0*100)."";
		}else {
			$s_column_discount=number_format($discount_price)." <sup>[".(order_online_promo::thediscount()!=""?"2":"1")."]</sup>";
			$s_note_coupon_code="<br /><sup>[".(order_online_promo::thediscount()!=""?"2":"1")."]</sup> ".$lang_promo_code_discount.", ".($discount_coupon*100)."";
		}
		
		$sub_grandtotal=$discount_subtotal-$discount_price;
		$product_weight=shoppingcart::get_total_product_weight($_SESSION["shopping_cart"], $lang);
		$shipping_cost=shoppingcart::get_shipping_cost($sub_grandtotal, $product_weight, $_SESSION["shipping_state"],$_SESSION["shipping_region"]);//
		
		echo "<script>parent.document.getElementById('column_discount').innerHTML='Rp".$s_column_discount."'</script>";
		echo "<script>parent.document.getElementById('note_coupon_code').innerHTML='".$s_note_coupon_code."%';</script>";
		echo "<script>parent.document.getElementById('error_discount').innerHTML='".@$s_discount."'</script>";
		echo "<script>parent.document.getElementById('column_grandtotal').innerHTML='<strong>Rp".number_format($sub_grandtotal+$shipping_cost)."</strong>'</script>";
		exit;	
		discount_coupon_Skip:
	
	}elseif($_REQUEST["c"]=="stock_change"){
		include_once("lang/".$lang.".php");
		$s_additional_orderreview_confirmation=$lang_stockchange_confirmation_info;
	
	}elseif($_REQUEST["c"]=="set_tradein"){
		$_SESSION["tradein"][count(@$_SESSION["tradein"])]=@$_REQUEST["productid"];
		$_SESSION["shopping_cart"][$_REQUEST["productid"]]=
			$_SESSION["shopping_cart"][$_REQUEST["productid"]]>1?	1:$_SESSION["shopping_cart"][$_REQUEST["productid"]];
		include_once "lib/cls_shoppingcart.php";
		if(isset($_SESSION["email"]))shoppingcart::__insert_product($_SESSION["email"], $_SESSION["shopping_cart"], $diskon);
		echo "<script>location.href='shoppingcart.php';</script>";exit;
		
	}elseif($_REQUEST["c"]=="unset_tradein"){
		unset($_SESSION["tradein"][	array_search($_REQUEST["productid"], $_SESSION["tradein"])	]);
		include_once "lib/cls_shoppingcart.php";
		if(isset($_SESSION["email"]))shoppingcart::__insert_product($_SESSION["email"], $_SESSION["shopping_cart"], $diskon);
		echo "<script>location.href='shoppingcart.php';</script>";exit;	
	
// END PRODUCT CATALOG	- SHOPPING CART

// MEMBER REGISTRATION
	}elseif($_REQUEST["c"]=="member_register"){
		//error_reporting(E_ALL);
		$set_login_span_class = $set_register_span_class =  $set_register_span_onclick = $set_login_span_onclick = "";
		$set_div_login_display = $set_div_register_display = "none";
		
		require_once __DIR__ . "/../securimage.php";
      	$securimage = new Securimage();      
		if( !$securimage->check($_REQUEST["t_captcha_register"]) ) {
			
			$captcha_error=true;
			$set_login_span_class = "span-link";
			$set_login_span_onclick = "set_tab(this)";
			$set_div_register_display = "block";
			$return_member_register = "
					<script>
					window.onload=function(){
					try{
						document.getElementById( 't_captcha_register' ).style.backgroundImage=\"url('". $url_path ."/images/warning.png')\";
						document.getElementById( 't_captcha_register' ).style.backgroundPosition=\"right center\";
						document.getElementById( 't_captcha_register' ).style.backgroundRepeat=\"no-repeat\";
						document.getElementById( 't_captcha_register' ).title='". str_replace("\r\n", "", $lang_captcha_error ) ."';
						
					}catch(e){}
					}
					</script>
				";

		}else{
			include_once __DIR__ . "/../lang/".$lang.".php";
			include_once __DIR__ . "/../lib/cls_member.php";
			include_once __DIR__ . "/../membercard/lib/cls_shoppingcart.php";
			$rs_member_exists=mysql_query("select 1 from membersdata where email='".main::formatting_query_string($_REQUEST["t_username_register"])."';");
			if(mysql_num_rows($rs_member_exists)>0){
				$member_exists=true; 
				$set_login_span_class = "span-link";
				$set_login_span_onclick = "set_tab(this)";
				$set_div_register_display = "block";
				$return_member_register = "
					<script>
					window.onload=function(){
					try{
						document.getElementById( 't_username_register' ).style.backgroundImage=\"url('". $url_path ."/images/warning.png')\";
						document.getElementById( 't_username_register' ).style.backgroundPosition=\"right center\";
						document.getElementById( 't_username_register' ).style.backgroundRepeat=\"no-repeat\";
						document.getElementById( 't_username_register' ).title='". str_replace("\r\n", "", $lang_member_register_emailexists ) ."';
						
					}catch(e){}
					}
					</script>
				";
			}else{
				$member_state=preg_split("/\|/", @$_REQUEST["t_state"]);			
				$sql="insert into membersdata(name, email, password, address, homecity, homeregion, homestate, homepostcode, homecountry, phone, handphone, milis, dateofbirth, membercode) values 
					('".substr(main::formatting_query_string(@$_REQUEST["t_username_register"]),0,200)."', '".substr(main::formatting_query_string(@$_REQUEST["t_username_register"]),0,150)."', 
					'".main::formatting_query_string(@$_REQUEST["t_password_1"])."',
					'".main::formatting_query_string(@$_REQUEST["t_address"])."', '".substr(main::formatting_query_string(@$_REQUEST["t_city"]),0,110)."', 
					'".main::formatting_query_string(@$_REQUEST["s_city"])."',
					'".main::formatting_query_string($member_state[0])."',
					'".substr(main::formatting_query_string(@$_REQUEST["t_postalcode"]),0,10)."', '".substr(main::formatting_query_string(@$_REQUEST["t_country"]),0,200)."', 
					'".substr(main::formatting_query_string(@$_REQUEST["t_telephone"]),0,50)."', 
					'".substr(main::formatting_query_string(@$_REQUEST["t_handphone"]),0,100)."', ".(isset($_REQUEST["c_enewsletter"])?"true":"false").", 
					'".main::date_database_formatting($arr_month, main::formatting_query_string(@$_REQUEST["t_dob"]))."',
					'".mc_shoppingcart::__generate_membercode($_REQUEST["t_username_register"], "OL")."');";
				mysql_query($sql) or die();//("error member registration<br />".mysql_error());
				$_SESSION["email"]=@$_REQUEST["t_username_register"];
				if ($_REQUEST["c_enewsletter"]=true ){
				include_once __DIR__ . "/../api2.php"; //api mailhcimp Modena subscriber Modena Customer 1
				}
				include_once __DIR__ . "/../lib/cls_member.php";
				include_once __DIR__ . "/../membercard/lib/cls_main.php";
				$gm=new member;
				$gm->email="='".main::formatting_query_string($_SESSION["email"])."'";
				$rs=$gm->member_data("%");
				if(mysql_num_rows($rs)>0){
					$data=mysql_fetch_array($rs);
					$msgc=file_get_contents(__DIR__ . "/../lang/".$lang."_greet_member.html");
					$ar=array(
						"#email#"=>$data["email"],
						"#password#"=>$data["password"],
						"#nama#"=>$data["name"],
						"#alamat#"=>$data["address"]." ".$data["homecity"]." ".$data["state"]." ".$data["homepostcode"],
						"#telepon#"=>$data["phone"],
						"#handphone#"=>$data["handphone"],
						"#membercard#"=>$data["membercode"]);
					$msgc=member_main::remove_character($msgc, $ar);
					$gm->lang="lang/".$lang.".php";
					$gm->custemail=$_SESSION["email"];
					$gm->email_template="template/email.html";
					$gm->t_message_en=$msgc;
					$gm->email_subject="Thank you for registering to MODENA";
					$gm->member_email("customer");
				}
				$_SESSION["login_session"] = $gm->check_login_session(true);

				//save shoppingcart data before continue
				if(isset($_SESSION["shopping_cart"])&&count($_SESSION["shopping_cart"])>0){
					include_once __DIR__ . "/../lib/cls_shoppingcart.php";
					$_SESSION["order_no"]=shoppingcart::__insert_order($_SESSION["email"]);
					
					$arr_product_available_quantity = shoppingcart::arr_product_available_quantity( $_SESSION["shopping_cart"], 
						(@$_REQUEST["state_id"]?$_REQUEST["state_id"]:@$_SESSION["shipping_state"]), 
						(@$_REQUEST["region_id"]?$_REQUEST["region_id"]:@$_SESSION["shipping_region"]) 
						);
						
					foreach($_SESSION["shopping_cart"] as $productid=>$quantity){// check productid quantity availability in stock
						if(shoppingcart::is_valid_product_id($productid)){
							if( /*shoppingcart::product_available_quantity($productid, $quantity, @$_SESSION["shipping_state"], @$_SESSION["shipping_region"])*/ $arr_product_available_quantity[ $productid ] < $shoppingcart_buffer_stock)$_SESSION["shopping_cart"][$productid]=0;
						}else unset($_SESSION["shopping_cart"][$productid]);
					}
					shoppingcart::__insert_product($_SESSION["email"], $_SESSION["shopping_cart"], $diskon);
				}
				$return_member_register="
						<script>
							try{__submit('member_login.php', 'cs=success+path=".@$_REQUEST["path"]."+t_discount_coupon=".@$_REQUEST["t_discount_coupon"]."+cb_toc=".@$_REQUEST["cb_toc"]."');}
							catch(e){__submit('member_register.php', 'cs=success+path=".@$_REQUEST["path"]."+t_discount_coupon=".@$_REQUEST["t_discount_coupon"]."+cb_toc=".@$_REQUEST["cb_toc"]."');}
						</script>";
//				exit;
			}
		}
// MEMBER UPDATE
	}elseif($_REQUEST["c"]=="member_update"){
		include_once "lang/".$lang.".php";
		//check email exists
		$member_state=preg_split("/\|/", @$_REQUEST["t_state"]);			
/*		if($_SESSION["email"]!=main::formatting_query_string($_REQUEST["t_email"])){
			$rs_check_email=mysql_query("select 1 from membersdata 
				where email='".main::formatting_query_string($_REQUEST["t_email"])."';") or die("error checking email.<br />".mysql_error());
			if(mysql_num_rows($rs_check_email)<=0){
				$sql="update membersdata set 
					name='".substr(main::formatting_query_string($_REQUEST["t_name"]),0,200)."', 
					email='".substr(main::formatting_query_string($_REQUEST["t_email"]),0,150)."', 
					password=".($_REQUEST["t_password_1"]!=""?"'".main::formatting_query_string($_REQUEST["t_password_1"])."'":"password").",
					address='".main::formatting_query_string($_REQUEST["t_address"])."', 
					homecity='".substr(main::formatting_query_string(@$_REQUEST["t_city"]),0,110)."', 
					homeregion='".main::formatting_query_string(@$_REQUEST["s_city"])."', 
					homestate='".main::formatting_query_string($member_state[0])."', 
					homepostcode='".substr(main::formatting_query_string($_REQUEST["t_postalcode"]),0,10)."',
					homecountry='".substr(main::formatting_query_string($_REQUEST["t_country"]),0,200)."',
					phone='".substr(main::formatting_query_string($_REQUEST["t_telephone"]),0,50)."', 
					handphone='".substr(main::formatting_query_string($_REQUEST["t_handphone"]),0,100)."',
					dateofbirth='".main::date_database_formatting($arr_month, main::formatting_query_string($_REQUEST["t_dob"]))."', 
					milis=".(isset($_REQUEST["c_enewsletter"])?"true":"false")." where email='".main::formatting_query_string($_SESSION["email"])."';";			
			}else{
				$return_update_member=$lang_member_register_emailexists;
			}
		}else{*/
			$sql="update membersdata set 
				name='".substr(main::formatting_query_string($_REQUEST["t_name"]),0,200)."', 
				address='".main::formatting_query_string($_REQUEST["t_address"])."', 
				password=".($_REQUEST["t_password_1"]!=""?"'".main::formatting_query_string($_REQUEST["t_password_1"])."'":"password").",
				homecity='".substr(main::formatting_query_string(@$_REQUEST["t_city"]),0,110)."', 
				homeregion='".main::formatting_query_string(@$_REQUEST["s_city"])."', 
				homestate='".main::formatting_query_string($member_state[0])."', 
				homepostcode='".substr(main::formatting_query_string($_REQUEST["t_postalcode"]),0,10)."',
				homecountry='".substr(main::formatting_query_string($_REQUEST["t_country"]),0,200)."',
				phone='".substr(main::formatting_query_string($_REQUEST["t_telephone"]),0,50)."', 
				handphone='".substr(main::formatting_query_string($_REQUEST["t_handphone"]),0,100)."',
				dateofbirth='".main::date_database_formatting($arr_month, main::formatting_query_string($_REQUEST["t_dob"]))."', 
				milis=".(isset($_REQUEST["c_enewsletter"])?"true":"false")." where email='".main::formatting_query_string($_SESSION["email"])."';";
//		}		
		if(@$sql!=""){
			mysql_query($sql) or die();//"error member update.<br />".mysql_error());
//			$_SESSION["email"]=$_REQUEST["t_email"];
			$return_update_member=$lang_member_update_success;
		}
			
// MEMBER LOGIN
	}elseif($_REQUEST["c"]=="member_login"){

		$set_login_span_class = $set_register_span_class =  $set_register_span_onclick = $set_login_span_onclick = "";
		$set_div_login_display = $set_div_register_display = "none";
		
		require_once "securimage.php";
      	$securimage = new Securimage();      
		if(!$securimage->check($_REQUEST["t_captcha"])) {
			
			$captcha_error=true;
			$set_register_span_class = "span-link";
			$set_register_span_onclick = "set_tab(this)";
			$set_div_login_display = "block";
			$return_member_register = "
					<script>
					window.onload=function(){
					try{
						document.getElementById( 't_captcha_register' ).style.backgroundImage=\"url('". $url_path ."/images/warning.png')\";
						document.getElementById( 't_captcha_register' ).style.backgroundPosition=\"right center\";
						document.getElementById( 't_captcha_register' ).style.backgroundRepeat=\"no-repeat\";
						document.getElementById( 't_captcha_register' ).title='". str_replace("\r\n", "", $lang_captcha_error ) ."';
						
					}catch(e){}
					}
					</script>
				";
				
		}else{
			include_once "cls_member.php";
			$member_login=new member;
			$member_login->email="='".main::formatting_query_string($_REQUEST["t_username"])."'";
			$member_login->password="='".str_replace("'","\'",$_REQUEST["t_password"])."'";
			$member_login->enabled="=true";
			$rs_member_login=$member_login->member_data("%");
			if(mysql_num_rows($rs_member_login)>0){
				$rs_member_login_=mysql_fetch_array($rs_member_login);
				$_SESSION["email"]=$rs_member_login_["email"];
				$_SESSION["login_session"] = $member_login->check_login_session(true);
				//save shoppingcart data before continue
				include_once "cls_shoppingcart.php";
				if(isset($_SESSION["shopping_cart"])&&count($_SESSION["shopping_cart"])>0){				
					$_SESSION["order_no"]=shoppingcart::__insert_order($_SESSION["email"]);
					
					$arr_product_available_quantity = shoppingcart::arr_product_available_quantity( $_SESSION["shopping_cart"], 
						(@$_REQUEST["state_id"]?$_REQUEST["state_id"]:@$_SESSION["shipping_state"]), 
						(@$_REQUEST["region_id"]?$_REQUEST["region_id"]:@$_SESSION["shipping_region"]) 
						);
						
					foreach($_SESSION["shopping_cart"] as $productid=>$quantity){// check productid quantity availability in stock
						if(shoppingcart::is_valid_product_id($productid)){
							if(/*shoppingcart::product_available_quantity($productid, $quantity, @$_SESSION["shipping_state"], @$_SESSION["shipping_region"])*/ $arr_product_available_quantity[ $productid ] < $shoppingcart_buffer_stock)$_SESSION["shopping_cart"][$productid]=0;
						}else unset($_SESSION["shopping_cart"][$productid]); 
					}
					shoppingcart::__insert_product($_SESSION["email"], $_SESSION["shopping_cart"], $diskon);				
				}
				$rs_shoppingcart=shoppingcart::get_product($_SESSION["email"], "");
				if(mysql_num_rows($rs_shoppingcart)>0)$_SESSION["order_no"]=shoppingcart::get_order_number($_SESSION["email"], 0);
				
				$arr_product_available_quantity = shoppingcart::arr_product_available_quantity( $_SESSION["shopping_cart"], 
						(@$_REQUEST["state_id"]?$_REQUEST["state_id"]:@$_SESSION["shipping_state"]), 
						(@$_REQUEST["region_id"]?$_REQUEST["region_id"]:@$_SESSION["shipping_region"]) 
						);
						
				while($rs_shoppingcart_=mysql_fetch_array($rs_shoppingcart)){
					if(/*shoppingcart::product_available_quantity($rs_shoppingcart_["product_id"], $rs_shoppingcart_["quantity"], @$_SESSION["shipping_state"], @$_SESSION["shipping_region"])*/ $arr_product_available_quantity[ $rs_shoppingcart_["product_id"] ] < $shoppingcart_buffer_stock)
						$_SESSION["shopping_cart"][$rs_shoppingcart_["product_id"]]=0;
					else
						$_SESSION["shopping_cart"][$rs_shoppingcart_["product_id"]]=$rs_shoppingcart_["quantity"];
				}
				
				// simpan data culinaria order
				include("cls_culinaria_program.php"); 
				include("cls_culinaria_cart.php"); 
				$arr_parameter["b.memberid"] = array("=", "(select memberid from membersdata where email = '". main::formatting_query_string( $_SESSION["email"] ) ."')");
				$arr_parameter["b.order_status"] = array("=", 0);
				$arr_parameter["c.program_awal"] = array(">", "current_timestamp");
				$rs_data_order = culinaria_cart::browse_culinaria_cart_item( $arr_parameter );
				
				if( sql::sql_num_rows($rs_data_order) > 0 ){
					while( $data_order = sql::sql_fetch_array( $rs_data_order ) )
						$_SESSION["culinaria_order"][ $data_order["program_id"] ] = $data_order["quantity"];
				}
				
				// reset data order program
				unset($arr_parameter_reset_cart_item);
				$arr_parameter_reset_cart_item["order_id"] = array(" in ", "(select order_id from culinaria_ordercustomer where order_status = 0 and custemail = '". main::formatting_query_string( $_SESSION["email"] ) ."')");
				if( count( $_SESSION["culinaria_order"] )  > 0 )
					$arr_parameter_reset_cart_item["product_id"] = array("", " not in (". implode(",", array_keys($_SESSION["culinaria_order"])) .") ");
				culinaria_cart::delete_culinaria_cart_item($arr_parameter_reset_cart_item);		

				$member_login_cs ="success";
				$return_member_login="<script>__submit('member_login.php', 'cs=". $member_login_cs ."+path=".@$_REQUEST["path"]."+t_discount_coupon=".@$_REQUEST["t_discount_coupon"]."+cb_toc=".@$_REQUEST["cb_toc"]."');</script>";
				
			}else{
				
				$member_login_cs ="error";
				$set_register_span_class = "span-link";
				$set_register_span_onclick = "set_tab(this)";
				$set_div_login_display = "block";
				$return_member_register = "
					<script>
					window.onload=function(){
					try{
						document.getElementById( 't_password' ).style.backgroundImage=\"url('". $url_path ."/images/warning.png')\";
						document.getElementById( 't_password' ).style.backgroundPosition=\"right center\";
						document.getElementById( 't_password' ).style.backgroundRepeat=\"no-repeat\";
						document.getElementById( 't_password' ).title='". str_replace("\r\n", "", $lang_login_error ) ."';
						
					}catch(e){}
					}
					</script>
				";
			}
			
		}	

	}elseif(@$_REQUEST["c"]=="b2b_login"){
		$return_member_login="<script>__submit('', 'cs=error')</script>";
	
	
//MEMBER BILLING	
	}elseif($_REQUEST["c"]=="change_billing"){
		include_once "cls_member.php";
		include_once "../lang/".$lang.".php";
		$member_billing=new member;
		$rs_member=$member_billing->member_data_billing($_REQUEST["s_billing_address"]);
		$rs_member_=mysql_fetch_array($rs_member, MYSQL_ASSOC);
		$s_rs_member_="<br /><br /><strong>".main::formatting_javascript_string($rs_member_["billing_first_name"])." ".main::formatting_javascript_string($rs_member_["billing_last_name"])."<br />:: ".main::formatting_javascript_string($rs_member_["billing_address"]).", ".main::formatting_javascript_string($rs_member_["billing_city"]).", ".main::formatting_javascript_string($rs_member_["state"]).", ".main::formatting_javascript_string($rs_member_["billing_country"])." ".main::formatting_javascript_string($rs_member_["billing_postcode"])."<br />			:: ".main::formatting_javascript_string($rs_member_["billing_phone"])." | ".main::formatting_javascript_string($rs_member_["billing_handphone"])."			</strong>";
		echo "<script>
			try{
				parent.document.getElementById('billing_detail').innerHTML='".str_replace("#back#", $lang_back, str_replace("#continue#", $lang_continue, $s_rs_member_))."';
				parent.document.getElementById('h_billingid').value='".$rs_member_["billingid"]."';
				parent.document.getElementById('a_billing_next').setAttribute('href', 'javascript:__submit(\'shoppingcart_proceed.php\', \'shoppingcart_step=4\')');
			}catch(e){};
			</script>";
		
		$delete_button = "<div class=\"cell-product-cmd-compare\" style=\"float:left !important; vertical-align:bottom !important; padding:0px; margin-right:-11px; height:53px;\">
													<a href=\"javascript:__submit(\\'\\', \\'c=confirm_delete_billing+billingid=".$rs_member_["billingid"]."\\')\" id=\"b_delete\">
														<div style=\"width:136px !important;\">". $lang_delete ."</div>
													</a>
												</div>";
		$delete_button = str_replace("\n", "", $delete_button);
		
		echo "<script>
			try{
				parent.document.getElementById('t_first_name').value='".main::formatting_javascript_string($rs_member_["billing_first_name"])."';
				parent.document.getElementById('t_last_name').value='".main::formatting_javascript_string($rs_member_["billing_last_name"])."';
				parent.document.getElementById('t_address').value='".main::formatting_javascript_string($rs_member_["billing_address"])."';
				parent.document.getElementById('t_city').value='".main::formatting_javascript_string($rs_member_["billing_city"])."';
				parent.document.getElementById('t_state').value='".main::formatting_javascript_string($rs_member_["billing_state"])."';
				var sel_country=parent.document.getElementById('t_country');
				for(var x=0; x<sel_country.length; x++){
					if(sel_country.options[x].value.toUpperCase()=='".strtoupper(main::formatting_javascript_string($rs_member_["billing_country"]))."')sel_country.selectedIndex=x;
				}
				parent.document.getElementById('t_postalcode').value='".main::formatting_javascript_string($rs_member_["billing_postcode"])."';
				parent.document.getElementById('t_telephone').value='".main::formatting_javascript_string($rs_member_["billing_phone"])."';
				parent.document.getElementById('t_handphone').value='".main::formatting_javascript_string($rs_member_["billing_handphone"])."';
				parent.document.getElementById('container_del_button').innerHTML='". $delete_button ."';
			}catch(e){}
			</script>";
		exit;
	
	}elseif($_REQUEST["c"]=="copy_billing" || $_REQUEST["c"]=="copy_shipping"){
		include_once "cls_member.php";	include_once "cls_shoppingcart.php"; include_once "../lang/".$lang.".php";		
		$member=new member;
		$member->email="='".$_SESSION["email"]."'";
		$member->enabled="=true";
		$rs_member=$member->member_data("%");
		if(mysql_num_rows($rs_member)>0){
			$rs_member_=mysql_fetch_array($rs_member);
			echo "<script>try{
					try{parent.document.getElementById('t_first_name').value='".$rs_member_["name"]."';}
					catch(e){parent.document.getElementById('t_name').value='".$rs_member_["name"]."';}
					parent.document.getElementById('t_address').value='".$rs_member_["address"]."';
					try{parent.document.getElementById('t_city').value='".ucwords(strtolower($rs_member_["homecity"]))."';}catch(e){}
					parent.document.getElementById('t_postalcode').value='".$rs_member_["homepostcode"]."';
					parent.document.getElementById('t_telephone').value='".$rs_member_["phone"]."';
					parent.document.getElementById('t_handphone').value='".$rs_member_["handphone"]."';
					var s_state=parent.document.getElementById('t_state');
					try{						
						for(var x=0; x<s_state.options.length; x++){
							var opt_state_id=String(s_state.options[x].value).split('|');
							if(opt_state_id[0]=='".$rs_member_["homestate"]."')s_state.selectedIndex=x;
						}
					}catch(e){s_state.value='".$rs_member_["state"]."';}
					
					var s_country=parent.document.getElementById('t_country');
					for(var x=0; x<s_country.options.length; x++){
						if(s_country.options[x].value.toUpperCase()=='".strtoupper($rs_member_["homecountry"])."')s_country.selectedIndex=x;
					}										
					";
			if($_REQUEST["c"]=="copy_shipping")	{
				$rs_region=main::shipment_exception($rs_member_["homestate"], "");
				if(mysql_num_rows($rs_region)<=0){
					echo "var text_city=parent.document.getElementById('t_city'); text_city.style.visibility='visible'; text_city.style.position='static';";
					echo "var sel_city=parent.document.getElementById('s_city'); sel_city.style.visibility='hidden'; sel_city.style.position='absolute';";						
					echo "sel_city.options.length=0;";
					echo "try{parent.document.getElementById('t_city').value='".$rs_member_["homecity"]."';}catch(e){}";
				}else{
					echo "var text_city=parent.document.getElementById('t_city'); text_city.style.visibility='hidden'; text_city.style.position='absolute';";
					echo "var sel_city=parent.document.getElementById('s_city'); sel_city.style.visibility='visible'; sel_city.style.position='static';";		
					echo "sel_city.options.length=0;";
					while($region=mysql_fetch_array($rs_region))
						echo "var option=parent.document.createElement('option');
									option.text='".ucwords(strtolower($region["region"]))."';
									option.value='".$region["region_id"]."';
									try{sel_city.add(option,sel_city.options[null]);}
									catch (e){sel_city.add(option,null);}";
					if(!in_array($rs_member_["homestate"], $arr_propinsi_free_shipping)){
						/*echo "var option=parent.document.createElement('option');
							option.text='-. ".$lang_other_city."';
							option.value='';
							try{sel_city.add(option,sel_city.options[null]);}
							catch (e){sel_city.add(option,null);}";		*/
					}						
					echo "	for(var x=0; x<sel_city.length; x++){
									if(sel_city.options[x].value.toUpperCase()=='".strtoupper($rs_member_["homeregion"])."')sel_city.selectedIndex=x;
								}";
					echo "parent.__change_shipping(s_state,sel_city);";
				}
			}
			echo "
				}catch(e){}
				</script>";
		}exit;

	}elseif($_REQUEST["c"]=="register_billing"){
		include_once "cls_member.php";	include_once "lang/".$lang.".php";		
		if($_REQUEST["s_billing_address"]=="add" || $_REQUEST["s_billing_address"]=="")$billingid=member::__entri_member_data_billing($_SESSION["email"]);
		else $billingid=$_REQUEST["s_billing_address"];
		//$member_state=preg_split("/\|/", @$_REQUEST["t_state"]);			
		$sql="update membersbilling set 
			billing_first_name='".substr(main::formatting_query_string(@$_REQUEST["t_first_name"]),0,200)."', 
			billing_last_name='".substr(main::formatting_query_string(@$_REQUEST["t_last_name"]),0,200)."', 
			billing_address='".main::formatting_query_string(@$_REQUEST["t_address"])."', 
			billing_city='".substr(main::formatting_query_string(@$_REQUEST["t_city"]),0,110)."', 
			billing_state='".substr(main::formatting_query_string(@$_REQUEST["t_state"]),0,110)."', 
			billing_postcode='".substr(main::formatting_query_string(@$_REQUEST["t_postalcode"]),0,10)."', 
			billing_country='".substr(main::formatting_query_string(@$_REQUEST["t_country"]),0,200)."', 
			billing_phone='".substr(main::formatting_query_string(@$_REQUEST["t_telephone"]),0,50)."', 
			billing_handphone='".substr(main::formatting_query_string(@$_REQUEST["t_handphone"]),0,100)."' 
			where billingid='".main::formatting_query_string($billingid)."';"; 
		mysql_query($sql) or die();//("error member billing<br />".mysql_error());
		if(@$_REQUEST["shoppingcart_step"]!="")
			$return_shoppingcart_proceed="<script>__submit('shoppingcart_proceed.php', 'shoppingcart_step=".($_REQUEST["shoppingcart_step"]+1)."+h_billingid=".$billingid."+s_billing_address=".$billingid."')</script>";
		else{
			$return_redirect_action="try{document.getElementById('s_billing_address').selectedIndex=document.getElementById('s_billing_address').length-2}catch(e){}__submit('member_account.php', '');";
			$return_member_account_billing=$lang_confirm_insertupdate_billing."<br />".str_replace("#path#", "javascript:".$return_redirect_action, $lang_click_here_for_redirect);
		}
		
//MEMBER BILLING DELETE
	}elseif($_REQUEST["c"]=="confirm_delete_billing"){
		include_once "lib/cls_member.php";
		include_once "lang/".$lang.".php";
		$member_billing=new member;
		$rs_member=$member_billing->member_data_billing($_REQUEST["s_billing_address"]);
		$rs_member_=mysql_fetch_array($rs_member, MYSQL_ASSOC);
		
		$back_button = "<div class=\"cell-product-cmd-compare\" style=\"float:left !important; vertical-align:bottom !important; padding:0px; margin-right:-11px; height:53px;\">
													<a href=\"javascript:__submit('member_account.php', 's_billing_address=".$_REQUEST["s_billing_address"]."')\" id=\"b_billing_back\">
														<div style=\"width:136px !important;\">#back#</div>
													</a>
												</div>";
		$next_button = "<div class=\"cell-product-cmd-compare\" style=\"float:left !important; vertical-align:bottom !important; padding:0px; margin-right:-11px; height:53px;\">
													<a href=\"javascript:__submit('', 'c=delete_billing+s_billing_address=".$_REQUEST["s_billing_address"]."')\" id=\"b_delete\">
														<div style=\"width:136px !important;\">#continue#</div>
													</a>
												</div>";
		$back_button = str_replace("\n", "", $back_button);	
		$next_button = str_replace("\n", "", $next_button);
		
		$return_member_account_billing=$lang_confirm_delete_billing."<br /><br /><strong>".$rs_member_["billing_first_name"]." ".$rs_member_["billing_last_name"]."<br />:: ".$rs_member_["billing_address"].", ".$rs_member_["billing_city"].", ".$rs_member_["state"].", ".$rs_member_["billing_country"]." ".$rs_member_["billing_postcode"]."<br />			:: ".$rs_member_["billing_phone"]." | ".$rs_member_["billing_handphone"]."			</strong><br /><br />" . $back_button . $next_button;
		
	}elseif($_REQUEST["c"]=="delete_billing"){
		include_once "lang/".$lang.".php";
		$sql="delete from membersbilling where memberid=(select memberid from membersdata 
			where email='".main::formatting_query_string($_SESSION["email"])."') and billingid='".main::formatting_query_string(@$_REQUEST["s_billing_address"])."';";
		mysql_query($sql) or die();//("error deleting member billing.");
		$return_redirect_action="try{document.getElementById('s_billing_address').selectedIndex=document.getElementById('s_billing_address').length-2}catch(e){}__submit('member_account.php', '');";
		$return_member_account_billing=$lang_confirm_insertupdate_billing."<br />".str_replace("#path#", "javascript:".$return_redirect_action ,$lang_click_here_for_redirect);
		
//MEMBER SHIPPING
	}elseif($_REQUEST["c"]=="change_shipping"){
		//if(file_exists("shoppingcart_proceed.php")) $url = "shoppingcart_proceed.php";
		//else $url = "../shoppingcart_proceed.php";	
		//unset($_REQUEST["c"]);
		//main::reload_page($_REQUEST, "$url?shoppingcart_step=2", "_parent");
		//exit;
		
		include_once "cls_member.php";
		include_once "cls_shoppingcart.php";include_once "cls_product.php";include_once "cls_discount.php";
		if			(file_exists("../lang/".$lang.".php"))			include_once "../lang/".$lang.".php";
		elseif	(file_exists("../../lang/".$lang.".php"))		include_once "../../lang/".$lang.".php"; 

		//itung total sales
		$product_list=new product;
		$product_list->distinct=true;
		$product_list->penable="='Y'";
		foreach($_SESSION["shopping_cart"] as $productid=>$qty){
			if(isset($s_productid))$s_productid.=",".$productid;
			else $s_productid=$productid;
		}
		if(@$s_productid=="")goto Skip;
		$product_list->productid=" in (".main::formatting_query_string($s_productid).")";
		$product_list->orderby="name";
		$rs_product_list=$product_list->product_list("'%'");
		$subtotal=0;
		while($rs_product_list_=mysql_fetch_array($rs_product_list)){	//$subtotal+=$rs_product_list_["price"]*$_SESSION["shopping_cart"][$rs_product_list_["productid"]];
			$diskon_peritem=new order_online_promo;
			$diskon_peritem->price_off_peritem($rs_product_list_["productid"], $rs_product_list_["price"], $_SESSION["shopping_cart"][$rs_product_list_["productid"]]);
			$subtotal+=($rs_product_list_["price"]-($rs_product_list_["price"]*$diskon_peritem->discount_peritem()))*$_SESSION["shopping_cart"][$rs_product_list_["productid"]];
		}
		
		if(order_online_promo::thediscount()!="")
			$discount_subtotal=order_online_promo::price_off($_SESSION["shopping_cart"],$subtotal,"disc_gt", @$_REQUEST["t_discount_coupon"]);
		else 
			$discount_subtotal=$subtotal;
		
		if(@$_REQUEST["t_discount_coupon"]!=""){
			$discount_coupon_=main::counting_discount($_REQUEST["t_discount_coupon"], $discount_subtotal);
			$discount_coupon=$discount_coupon_[0];
			$discount_price=$discount_coupon_[1];
			$discount_grandtotal=$discount_coupon_[2];	
		}
		$sub_grandtotal=(isset($discount_grandtotal)?$discount_grandtotal:$discount_subtotal);
				
		$product_weight=shoppingcart::get_total_product_weight($_SESSION["shopping_cart"], $lang);
		$shipping_cost=shoppingcart::get_shipping_cost($sub_grandtotal, $product_weight, $_REQUEST["s_shipping_address"]);
Skip:				
		$member_shipping=new member;
		$rs_member=$member_shipping->member_data_shipping($_REQUEST["s_shipping_address"]);
		$rs_member_=mysql_fetch_array($rs_member, MYSQL_ASSOC);
		$s_rs_member_="<br /><br /><strong>".main::formatting_javascript_string($rs_member_["shipping_name"])." <br />:: ".main::formatting_javascript_string($rs_member_["shipping_address"]).", ".main::formatting_javascript_string($rs_member_["shipping_city"]).", ".main::formatting_javascript_string($rs_member_["state"]).", ".main::formatting_javascript_string($rs_member_["shipping_country"])." ".main::formatting_javascript_string($rs_member_["shipping_postcode"])."<br />			:: ".main::formatting_javascript_string($rs_member_["shipping_phone"])." | ".main::formatting_javascript_string($rs_member_["shipping_handphone"])."			</strong><br /><br /><strong style=\"text-decoration:underline\" id=\"shipping_cost_note\">".$lang_shipping_cost." ".(@$shipping_cost>0?"Rp".number_format(@$shipping_cost):"FREE")."</strong><br /><br /><span style=\"line-height:25px; font-size:10px\">".$lang_shipping_date.":<br /><div style=float:left><input type=\"text\" onblur=\"javascript:if(!set_valid_date(".$minimum_shipping_time.", ".$maximum_shipping_time.", this.value)){alert(\\'".$lang_not_valid_date."\\');document.getElementById(\\'t_shipping_date\\').value=\\'\\'}\" name=\"t_shipping_date\" id=\"t_shipping_date\" size=\"30px\" maxlength=\"50\" value=\"".@$_REQUEST["t_shipping_date"]."\" readonly /></div><div style=\"float:left; vertical-align:middle; padding-left:10px\"><img src=\"images/calendar.png\" border=0 onclick=\"javascript:cal1.select(document.getElementById(\'t_shipping_date\'),\'anchor1\',\'dd MMM yyyy\'); return false;\"/></div><br /><span style=\"line-height:13px; font-size:10px\">".str_replace("#maximum_shipping_time#",$maximum_shipping_time,str_replace("#minimum_shipping_time#", $minimum_shipping_time, str_replace("#day_no#", $default_shipping_time,$lang_shipping_date_note)))."</span><br /><table width=100% cellpadding=0 cellspacing=0 border=0 style=padding-top:10px><tr><td valign=top><input type=\"checkbox\" name=\"cb_installation\" id=\"cb_installation\" value=\"1\" ".(@$_REQUEST["cb_installation"]!=""?"checked":"")." /></td><td><span style=\"line-height:13px; font-size:10px\"><label for=\"cb_installation\">".$lang_installation_option."</label></span></td></tr></table><br />".$lang_note.":<br /><textarea name=\"t_note\" id=\"t_note\" cols=\"47\">".@$_REQUEST["t_note"]."</textarea></span><br /><br /><input type=\"button\" name=\"b_shipping_back\" id=\"b_shipping_back\" value=\"#back#\" onclick=\"javascript:__submit(\'shoppingcart.php\',\'\')\" /> | <input type=\"button\" name=\"b_shipping_next\" id=\"b_shipping_next\" value=\"#continue#\" onclick=\"javascript:__submit(\'shoppingcart_proceed.php\', \'shoppingcart_step=3\')\" />";
		echo "<script>try{parent.document.getElementById('shipping_detail').innerHTML=
			'".str_replace("\r\n","",str_replace("#back#", $lang_back, str_replace("#continue#", $lang_continue, $s_rs_member_)))."';
			/*parent.document.getElementById('h_shippingid').value='".$rs_member_["shippingid"]."';*/}catch(e){}
			</script>";
		echo "<script>
			try{parent.setShippingLayout()}catch(e){}
			try{
				parent.document.getElementById('t_name').value='".main::formatting_javascript_string($rs_member_["shipping_name"])."';
				parent.document.getElementById('t_address').value='".main::formatting_javascript_string($rs_member_["shipping_address"])."';";
		echo "	var sel_state=parent.document.getElementById('t_state');
					for(var x=0; x<sel_state.length; x++){
						var opt_state=sel_state.options[x].value.split('|');
						if(opt_state[0]=='".main::formatting_javascript_string($rs_member_["shipping_state"])."')sel_state.selectedIndex=x;
					}";
		$rs_region=main::shipment_exception($rs_member_["shipping_state"], "");
		if(mysql_num_rows($rs_region)<=0){
			echo "parent.document.getElementById('t_city').value='".$rs_member_["shipping_city"]."';";
		}else{
			echo "var text_city=parent.document.getElementById('t_city'); text_city.style.visibility='hidden'; text_city.style.position='absolute';";
			echo "var sel_city=parent.document.getElementById('s_city'); sel_city.style.visibility='visible'; sel_city.style.position='static';";		
			echo "sel_city.options.length=0;";
			while($region=mysql_fetch_array($rs_region))
				echo "var option=parent.document.createElement('option');
							option.text='".ucwords(strtolower($region["region"]))."';
							option.value='".$region["region_id"]."';
							try{sel_city.add(option,sel_city.options[null]);}
							catch (e){sel_city.add(option,null);}";
			if(!in_array($rs_member_["shipping_state"], $arr_propinsi_free_shipping)){
				/*echo "var option=parent.document.createElement('option');
					option.text='-. ".$lang_other_city."';
					option.value='';
					try{sel_city.add(option,sel_city.options[null]);}
					catch (e){sel_city.add(option,null);}";		*/
			}							
			echo "	var found_region=false;
						for(var x=0; x<sel_city.length; x++){
							if(sel_city.options[x].value.toUpperCase()=='".strtoupper($rs_member_["shipping_region"])."')
								{sel_city.selectedIndex=x;found_region=true;}
						}
						if(!found_region){
							sel_city.style.visibility='hidden';sel_city.style.position='absolute';sel_city.options.length=0;
							text_city.style.visibility='visible'; text_city.style.position='static'; text_city.value='".$rs_member_["shipping_city"]."';
						}";
		}					
		//echo "var select_region=parent.document.getElementById('select_region');";
		//echo "select_region.style.visibility='hidden';";	
		
		
		$delete_button = "<div class=\"cell-product-cmd-compare\" style=\"float:left !important; vertical-align:bottom !important; padding:0px; margin-right:-11px; height:53px;\">
													<a href=\"javascript:__submit(\\'\\', \\'c=confirm_delete_shipping+shippingid=".$rs_member_["shippingid"]."\\')\" id=\"b_delete\">
														<div style=\"width:136px !important;\">". $lang_delete ."</div>
													</a>
												</div>";
		$delete_button = str_replace("\n", "", $delete_button);	
	
		echo "parent.document.getElementById('t_postalcode').value='".main::formatting_javascript_string($rs_member_["shipping_postcode"])."';
				parent.document.getElementById('t_telephone').value='".main::formatting_javascript_string($rs_member_["shipping_phone"])."';
				parent.document.getElementById('t_handphone').value='".main::formatting_javascript_string($rs_member_["shipping_handphone"])."';
				parent.document.getElementById('container_del_button').innerHTML='". $delete_button ."';
			}catch(e){}
			</script>";
		exit;

	}elseif($_REQUEST["c"]=="change_state"){
		include_once("../lang/".$lang.".php");	
		
		if(@$_REQUEST["mode"]!="view")	$_SESSION["shipping_state"]=$_REQUEST["state_id"];
		if($_SESSION["shipping_state"] == "") $_SESSION["shipping_region"] = "";
		$s_city="s_city";
		if(isset($_REQUEST["m"])){
			if($_REQUEST["m"]!="t_state")$s_city="s_city1";
		}else $s_city = $_REQUEST["m_mai"]; // untuk perubahan state & region di member account information
		
		echo "<script>";		
		echo "var selection=parent.document.getElementById('".$s_city."');";
		echo "try{var textbox=parent.document.getElementById('t_city');}catch(e){}";
		echo "try{var sel_qty=parent.document.getElementById('qty_'+ parent.document.getElementById('productid').value);
			sel_qty.options.length=0;sel_qty.disabled=true;}catch(e){}";
		$rs_region=main::shipment_exception($_REQUEST["state_id"], "");
		if(mysql_num_rows($rs_region)>0 && $_REQUEST["state_id"] != ""){
			echo "selection.style.visibility='visible';selection.style.position='static';";
			echo "selection.style.disabled=false;";
			echo "try{textbox.style.visibility='hidden';textbox.style.position='absolute'; textbox.value='';}catch(e){}";
			echo "selection.options.length=0;";
			echo "var option=document.createElement('option');
					option.text='".$lang_select_region."';
					option.value='';
					try{selection.add(option,selection.options[null]);}
					catch (e){selection.add(option,null);}";
			while($region=mysql_fetch_array($rs_region)){
				echo "var option=document.createElement('option');
					option.text='".ucwords(strtolower($region["region"]))."';
					option.value='".$region["region_id"]."';
					". (@$_REQUEST["region_id"] == $region["region_id"] ? "option.selected = true;" : "") ."
					try{selection.add(option,selection.options[null]);}
					catch (e){selection.add(option,null);}";
			}
			if(!in_array($_REQUEST["state_id"], $arr_propinsi_free_shipping)){
				/*echo "var option=document.createElement('option');
					option.text='-. ".$lang_other_city."';
					option.value='';
					try{selection.add(option,selection.options[null]);}
					catch (e){selection.add(option,null);}";		*/
			}
		}else{				
			echo "selection.style.disabled=true;selection.style.position='absolute'; selection.options.length=0;";
			echo "selection.setAttribute('class', 'font-grey-01-11');";
			echo "selection.setAttribute('style', 'font-size:11px !important; width:270px');";				
			//echo "try{textbox.style.visibility='visible';textbox.style.position='static'; textbox.value='';}catch(e){}";
		}
		//echo "var select_region=parent.document.getElementById('select_region');";
		//echo "select_region.style.visibility='hidden';";					
		echo "</script>";
		exit;	

	}elseif($_REQUEST["c"]=="change_state2"){
		include_once("../lang/".$lang.".php");	
		
		if(@$_REQUEST["mode"]!="view")	$_SESSION["shipping_state"]=$_REQUEST["state_id"];
		if($_SESSION["shipping_state"] == "") $_SESSION["shipping_region"] = "";
		$s_city="s_city";
		if(isset($_REQUEST["m"])){
			if($_REQUEST["m"]!="t_state")$s_city="s_city1";
		}else $s_city = $_REQUEST["m_mai"]; // untuk perubahan state & region di member account information
		
		echo "<script>";		
		echo "var selection=parent.document.getElementById('".$s_city."');";
		echo "try{var textbox=parent.document.getElementById('t_city');}catch(e){}";
		echo "try{var sel_qty=parent.document.getElementById('qty_'+ parent.document.getElementById('productid').value);
			sel_qty.options.length=0;sel_qty.disabled=true;}catch(e){}";
		$rs_region=main::shipment_exception($_REQUEST["state_id"], "");
		if(mysql_num_rows($rs_region)>0 && $_REQUEST["state_id"] != ""){
			echo "selection.style.visibility='visible';selection.style.position='static';";
			echo "selection.style.disabled=false;";
			echo "try{textbox.style.visibility='hidden';textbox.style.position='absolute'; textbox.value='';}catch(e){}";
			echo "selection.options.length=0;";
			echo "var option=document.createElement('option');
					option.text='".$lang_select_region."';
					option.value='';
					try{selection.add(option,selection.options[null]);}
					catch (e){selection.add(option,null);}";
			while($region=mysql_fetch_array($rs_region)){
				if($_REQUEST["state_id"]==11){
					if($region["region_id"]==244 or $region["region_id"]==246 or $region["region_id"]==572 or $region["region_id"]==247){
						echo "var option=document.createElement('option');
						option.text='".ucwords(strtolower($region["region"]))."';
						option.value='".$region["region_id"]."';
						". (@$_REQUEST["region_id"] == $region["region_id"] ? "option.selected = true;" : "") ."
						try{selection.add(option,selection.options[null]);}
						catch (e){selection.add(option,null);}";
					}
				}elseif($_REQUEST["state_id"]==12){
					if($region["region_id"]==267 or $region["region_id"]==268 or $region["region_id"]==271 or $region["region_id"]==250 or $region["region_id"]==251){
						echo "var option=document.createElement('option');
						option.text='".ucwords(strtolower($region["region"]))."';
						option.value='".$region["region_id"]."';
						". (@$_REQUEST["region_id"] == $region["region_id"] ? "option.selected = true;" : "") ."
						try{selection.add(option,selection.options[null]);}
						catch (e){selection.add(option,null);}";
					}
				}else{
					echo "var option=document.createElement('option');
					option.text='".ucwords(strtolower($region["region"]))."';
					option.value='".$region["region_id"]."';
					". (@$_REQUEST["region_id"] == $region["region_id"] ? "option.selected = true;" : "") ."
					try{selection.add(option,selection.options[null]);}
					catch (e){selection.add(option,null);}";
				}
				
				// echo "var option=document.createElement('option');
					// option.text='".ucwords(strtolower($region["region"]))."';
					// option.value='".$region["region_id"]."';
					// ". (@$_REQUEST["region_id"] == $region["region_id"] ? "option.selected = true;" : "") ."
					// try{selection.add(option,selection.options[null]);}
					// catch (e){selection.add(option,null);}";
			}
			if(!in_array($_REQUEST["state_id"], $arr_propinsi_free_shipping)){
				/*echo "var option=document.createElement('option');
					option.text='-. ".$lang_other_city."';
					option.value='';
					try{selection.add(option,selection.options[null]);}
					catch (e){selection.add(option,null);}";		*/
			}
		}else{				
			echo "selection.style.disabled=true;selection.style.position='absolute'; selection.options.length=0;";
			echo "selection.setAttribute('class', 'font-grey-01-11');";
			echo "selection.setAttribute('style', 'font-size:11px !important; width:270px');";				
			//echo "try{textbox.style.visibility='visible';textbox.style.position='static'; textbox.value='';}catch(e){}";
		}
		//echo "var select_region=parent.document.getElementById('select_region');";
		//echo "select_region.style.visibility='hidden';";					
		echo "</script>";
		exit;	

	}elseif($_REQUEST["c"]=="change_region"){
		include_once("lang/".$lang.".php");	
		
		$_SESSION["shipping_region"]=$_REQUEST["region_id"];
		//header("location:mainclass.php?c=shipment_estimation&t_state=".$_SESSION["shipping_state"]."&s_city=".$_SESSION["shipping_region"]."&t_discount_coupon=".$_REQUEST["t_discount_coupon"]);
		if(@$_REQUEST["categoryid"]!="" || @$_REQUEST["sub_categoryid"]!="")	header("location:shoppingcart-".@$_REQUEST["categoryid"]."-".@$_REQUEST["sub_categoryid"].".php");
		else	header("location:shoppingcart.php");
		exit;
		
		echo "<script>";
		echo "var selection=parent.document.getElementById('s_city');";
		echo "var textbox=parent.document.getElementById('t_city');";		
		echo "selection.style.visibility='hidden';selection.style.position='absolute'; selection.options.length=0;";		
		echo "textbox.style.visibility='visible';textbox.style.position='static'; textbox.value='';";
		echo "var select_region=parent.document.getElementById('select_region');";
		echo "select_region.style.visibility='visible';";
		echo "</script>";
		exit;				
		
	}elseif($_REQUEST["c"]=="register_shipping"){
		include_once "cls_member.php";	include_once "lang/".$lang.".php";
		if($_REQUEST["s_shipping_address"]=="add" || $_REQUEST["s_shipping_address"] == "")$shippingid=member::__entri_member_data_shipping($_SESSION["email"]);
		else $shippingid=$_REQUEST["s_shipping_address"];
		$member_state=preg_split("/\|/", @$_REQUEST["t_state"]);			
		$city=(@$_REQUEST["s_city"]!=""?$_REQUEST["s_city"]:$_REQUEST["t_city"]);
		$sql="update membersshipping set 
			shipping_name='".substr(main::formatting_query_string(@$_REQUEST["t_name"]),0,200)."', 			
			shipping_address='".main::formatting_query_string(@$_REQUEST["t_address"])."', 
			shipping_city='".substr(main::formatting_query_string($city),0,110)."', 
			shipping_region='".main::formatting_query_string($city)."', 
			shipping_state='".main::formatting_query_string($member_state[0])."', 
			shipping_postcode='".substr(main::formatting_query_string(@$_REQUEST["t_postalcode"]),0,10)."', 
			shipping_country='".substr(main::formatting_query_string(@$_REQUEST["t_country"]),0,200)."', 
			shipping_phone='".substr(main::formatting_query_string(@$_REQUEST["t_telephone"]),0,50)."', 
			shipping_handphone='".substr(main::formatting_query_string(@$_REQUEST["t_handphone"]),0,100)."'
			where shippingid='".main::formatting_query_string($shippingid)."';"; 
		mysql_query($sql) or die();//("error member shipping<br />".mysql_error());
		if(@$_REQUEST["shoppingcart_step"]!="")
			$return_shoppingcart_proceed="<script>
				try{__clear_data('t_address|t_postalcode|t_telephone|t_handphone');}catch(e){}
				try{document.getElementById('t_state').options.length=0;}catch(e){}
				try{__submit('shoppingcart_proceed.php', 'shoppingcart_step=".($_REQUEST["shoppingcart_step"]+1)."+h_shippingid=".$shippingid."+s_shipping_address=".$shippingid."');}catch(e){}</script>";
		else{
			$return_redirect_action="var s_shipping_address;try{document.getElementById('s_shipping_address').selectedIndex=document.getElementById('s_shipping_address').length-2}catch(e){s_shipping_address='".$shippingid."'}__submit('member_account.php', 's_shipping_address='+s_shipping_address);";
			$return_member_account_shipping=$lang_confirm_insertupdate_shipping."<br />".str_replace("#path#", "javascript:".$return_redirect_action, $lang_click_here_for_redirect);		
		}
	
//MEMBER SHIPPING DELETE
	}elseif($_REQUEST["c"]=="confirm_delete_shipping"){
		include_once "lib/cls_member.php";
		include_once "lang/".$lang.".php";
		$member_shipping=new member;
		$rs_member=$member_shipping->member_data_shipping($_REQUEST["s_shipping_address"]);
		$rs_member_=mysql_fetch_array($rs_member, MYSQL_ASSOC);
		
		$back_button = "<div class=\"cell-product-cmd-compare\" style=\"float:left !important; vertical-align:bottom !important; padding:0px; margin-right:-11px; height:53px;\">
													<a href=\"javascript:__submit('member_account.php', 's_shipping_address=".$_REQUEST["s_shipping_address"]."')\" id=\"b_shipping_back\">
														<div style=\"width:136px !important;\">#back#</div>
													</a>
												</div>";
		$next_button = "<div class=\"cell-product-cmd-compare\" style=\"float:left !important; vertical-align:bottom !important; padding:0px; margin-right:-11px; height:53px;\">
													<a href=\"javascript:__submit('', 'c=delete_shipping+s_shipping_address=".$_REQUEST["s_shipping_address"]."')\" id=\"b_delete\">
														<div style=\"width:136px !important;\">#continue#</div>
													</a>
												</div>";
		$back_button = str_replace("\n", "", $back_button);	
		$next_button = str_replace("\n", "", $next_button);
			
		$return_member_account_shipping=$lang_confirm_delete_shipping."<br /><br /><strong>".$rs_member_["shipping_name"]."<br />:: ".$rs_member_["shipping_address"].", ".$rs_member_["shipping_city"].", ".$rs_member_["state"].", ".$rs_member_["shipping_country"]." ".$rs_member_["shipping_postcode"]."<br />			:: ".$rs_member_["shipping_phone"]." | ".$rs_member_["shipping_handphone"]."			</strong><br /><br />" . $back_button . $next_button;

	}elseif($_REQUEST["c"]=="delete_shipping"){
		include_once "lang/".$lang.".php";
		$sql="delete from membersshipping where memberid=(select memberid from membersdata where 
			email='".main::formatting_query_string($_SESSION["email"])."') and shippingid='".main::formatting_query_string(@$_REQUEST["s_shipping_address"])."';";
		mysql_query($sql) or die();//("error deleting member shipping.");
		$return_redirect_action="try{document.getElementById('s_shipping_address').selectedIndex=document.getElementById('s_shipping_address').length-2}catch(e){}__submit('member_account.php', '');";
		$return_member_account_shipping=$lang_confirm_insertupdate_shipping."<br />".str_replace("#path#", "javascript:".$return_redirect_action, $lang_click_here_for_redirect);

//MEMBER ORDER	
	}elseif($_REQUEST["c"]=="customer_order_detail_notify"){
		include_once "lang/".$lang.".php";
		include_once "lib/cls_order.php";
		include_once "lib/cls_message.php";
		
		$message=new message;
		$message->email=$_SESSION["email"];
		$message->order_no=$_REQUEST["order_no"];
		$message->message_type=0;
		$message->message_subject=$lang_email_order_message_subject." [No. ".$_REQUEST["order_no"]."]";
		$message->message=$_REQUEST["t_message"];
		$message->message_no=message::new_message_ticket();
		$message->__insert_message();
		
		try{
			$order=new order;
			$order->lang="lang/".$lang.".php";
			$order->arr_par_ordercustomer=$arr_par_ordercustomer;
			$order->custemail=$_SESSION["email"];
			$order->order_no=$_REQUEST["order_no"];
//			$order->order_status=$_REQUEST["order_status"];
			$order->order_print_template="template/order_print.html";
			$order->email_template="template/email.html";
			//$order->discount=$diskon;
			$order->email_subject=$lang_email_order_message_subject;
			$order->t_message_en=$_REQUEST["t_message"]."<br /><br />
				<a href=\"".$url_path."secure/order.php?opw=true&
				order_no=".$_REQUEST["order_no"]."&message_no=".$message->message_no."\">
				<strong>Klik disini untuk mengirimkan balasan</strong></a>";
			$order->print_order("email", SHOWROOM_EMAIL);
		}catch(Exception $e){echo $e->getMessage();}
		try{
			$order=new order;
			$order->lang="lang/".$lang.".php";
			$order->arr_par_ordercustomer=$arr_par_ordercustomer;
			$order->custemail=$_SESSION["email"];
			$order->order_no=$_REQUEST["order_no"];
//			$order->order_status=$_REQUEST["order_status"];
			$order->message_no=$message->message_no;
			$order->t_message_en=$lang_email_auto_reply_order.$_REQUEST["t_message"];
			$order->order_print_template="template/order_print.html";
			$order->email_template="template/email.html";
			//$order->discount=$diskon;
			$order->email_subject=$lang_email_order_message_subject;
			$order->print_order("email", "customer");
		}catch(Exception $e){echo $e->getMessage();}	
		echo "<script>
			location.href='order_detail.php?order_no=".$_REQUEST["order_no"]."&c=sendmail_00';
			</script>";
		exit;
		
//MEMBER PRODUCT REGISTER		
	}elseif(@$_REQUEST["c"]=="product_register"){
		include_once "lib/cls_member.php";
		include_once "lang/".$lang.".php";
		
		if($_REQUEST["h_membersproductid"]!="")		
			if(mysql_num_rows(mysql_query("select 1 from membersproduct where 
				serialnumber in (select serialnumber from membersproduct where membersproductid<>'".main::formatting_query_string($_REQUEST["h_membersproductid"])."' and 
				memberid=(select memberid from membersdata where email='".main::formatting_query_string($_SESSION["email"])."')) and 
				serialnumber='".main::formatting_query_string($_REQUEST["t_serialnumber"])."';"))>0)
				$return=$lang_product_registration_notification_notok;
			else
				$sql="update membersproduct a left outer join serialnumber b on substring(trim(a.serialnumber),1,6)=trim(b.serialnumber)
					left outer join product c on b.productid=c.productid 
					set a.product=ifnull(c.name, '".substr(main::formatting_query_string($_REQUEST["t_product"]),0,200)."'), a.serialnumber='".substr(main::formatting_query_string($_REQUEST["t_serialnumber"]),0,50)."', 
					a.purchaseat='".main::formatting_query_string($_REQUEST["t_store"])."', a.purchasedate='".main::date_database_formatting($arr_month, main::formatting_query_string($_REQUEST["t_date"]))."' 
					where a.membersproductid='".main::formatting_query_string($_REQUEST["h_membersproductid"])."';";
		else{
			if(mysql_num_rows(mysql_query("select 1 from membersproduct where serialnumber='".main::formatting_query_string($_REQUEST["t_serialnumber"])."' 
				and memberid=(select memberid from membersdata where email='".main::formatting_query_string($_SESSION["email"])."');"))>0)
				$return=$lang_product_registration_notification_notok;
			else{
				$sql="insert into membersproduct(product, serialnumber, purchaseat, purchasedate, memberid) 
					select ifnull(
						(select name from product where productid=
							(select productid from serialnumber where trim(serialnumber)='".main::formatting_query_string(substr($_REQUEST["t_serialnumber"],0,6))."' limit 1)), 
					'".substr(main::formatting_query_string($_REQUEST["t_product"]),0,200)."'), '".substr(main::formatting_query_string($_REQUEST["t_serialnumber"]),0,50)."', 
					'".main::formatting_query_string($_REQUEST["t_store"])."', '".main::date_database_formatting($arr_month, main::formatting_query_string($_REQUEST["t_date"]))."', 
					(select memberid from membersdata where email='".main::formatting_query_string($_SESSION["email"])."')";
				//kirim email
				$message_to_customer=$lang_email_product_warranty_to_customer;
				$message_to_modena=$lang_email_product_warranty_to_modena;
			}
		}
		if(@$sql!=""){
			mysql_query($sql) or die();//("product_register error.<br />".mysql_error());
			if(@$message_to_customer!=""&&@$message_to_modena!=""){
				$rs_registered_product=member::get_last_product_registration($_SESSION["email"]);
				$registered_product=mysql_fetch_array($rs_registered_product);
				$message_to_customer=str_replace("#product#", $registered_product["product"], $message_to_customer);
				$message_to_modena=str_replace("#product#", $registered_product["product"], $message_to_modena);
				$message_to_customer=str_replace("#serialnumber#", $registered_product["serialnumber"], $message_to_customer);
				$message_to_modena=str_replace("#serialnumber#", $registered_product["serialnumber"], $message_to_modena);
				$message_to_customer=str_replace("#store#", $registered_product["purchaseat"], $message_to_customer);
				$message_to_modena=str_replace("#store#", $registered_product["purchaseat"], $message_to_modena);
				$message_to_customer=str_replace("#purchasedate#", $registered_product["purchasedate_formatted"], $message_to_customer);
				$message_to_modena=str_replace("#purchasedate#", $registered_product["purchasedate_formatted"], $message_to_modena);
				
				$message_to_customer=str_replace("#nama#", $registered_product["name"], $message_to_customer);
				$message_to_modena=str_replace("#nama#", $registered_product["name"], $message_to_modena);
				$message_to_customer=str_replace("#email#", $registered_product["email"], $message_to_customer);
				$message_to_modena=str_replace("#email#", $registered_product["email"], $message_to_modena);
				$message_to_customer=str_replace("#alamat#", $registered_product["address"]." ".$registered_product["homecity"].", ".$registered_product["state"]." ".$registered_product["homepostcode"], $message_to_customer);
				$message_to_modena=str_replace("#alamat#", $registered_product["address"]." ".$registered_product["homecity"].", ".$registered_product["state"]." ".$registered_product["homepostcode"], $message_to_modena);
				$message_to_customer=str_replace("#telepon#", $registered_product["phone"]." / ".$registered_product["handphone"], $message_to_customer);
				$message_to_modena=str_replace("#telepon#", $registered_product["phone"]." / ".$registered_product["handphone"], $message_to_modena);
				
				
				$email=new member;				
				$email->__set("lang", "lang/".$lang.".php");
				$email->__set("custemail", $_SESSION["email"]);
				$email->__set("email_template", "template/email.html");
				$email->__set("t_message_".$lang, $message_to_modena);
				$email->__set("email_subject", "[modena.co.id] New Product Warranty");
				$email->member_email("");
				$email->__set("t_message_".$lang, $message_to_customer);
				$email->member_email("customer");
			}
			echo "<script>location.href='member_account.php?mode=registered_product&c=true';</script>"; 
			exit;
		}
		
//MEMBER MESSAGE		
	}elseif(@$_REQUEST["c"]=="message_order_detail"){
		include_once "cls_message.php";include_once "../lang/".$lang.".php";		
		
		$message_obj=new message;
		$message_obj->memberemail=$_SESSION["email"];
		$message_obj->message_no=$_REQUEST["message_no"];
		$message_obj->message_starter_only=false;
		$rs_message=$message_obj->get_message();		
		$message=mysql_fetch_array($rs_message);
		$s_message=main::formatting_query_string($message["message"])."<br /><br /><strong>".$lang_message_reply." ::</strong> #reply#";
		
		$reply_obj=new message;
		$reply_obj->memberemail=$_SESSION["email"];
		$reply_obj->message_ref_no=$message["message_no"];
		$reply_obj->message_starter_only=false;		
		$rs_message_reply=$reply_obj->get_message();
		if(mysql_num_rows($rs_message_reply)>0){
			$s_reply="";
			while($message_reply=mysql_fetch_array($rs_message_reply)){
				$s_reply.="<br />customercare@modena.co.id [".$message_reply["message_date"]."]<br />".main::formatting_query_string($message_reply["message"])."<br /><br /><div align=center>---------------- End of message ----------------</div>";
			}
			$s_message=str_replace("#reply#", $s_reply, $s_message);
		}else $s_message=str_replace("#reply#", $lang_message_not_replied_yet, $s_message);
		
		echo "<script>";
		echo "for(var x=1; x<=(x+2); x++){";
		echo "	try{var table_detail=parent.document.getElementById('table_'+x);";
		echo "	for(var y=0; y<table_detail.rows.length; y++)table_detail.deleteRow(y);";
		echo "	}catch(e){break;}";
		echo "}";
		echo "var tablex=parent.document.getElementById('table_".$_REQUEST["row"]."');";
		echo "var rowx=tablex.insertRow(0);";
		echo "var cellx=rowx.insertCell(0);";
		echo "cellx.style.padding='10px';";
		echo "cellx.innerHTML='".$s_message."';";
		echo "</script>";
		exit;
		
	}elseif(@$_REQUEST["c"]=="message_general_detail"){
		include_once "cls_message.php";include_once "../lang/".$lang.".php";		
		
		$message_obj=new message;
		$message_obj->memberemail=$_SESSION["email"];
		$message_obj->contactus_no=$_REQUEST["id"];
		$message_obj->message_starter_only=false;
		$rs_message=$message_obj->get_contactus();		
		$message=mysql_fetch_array($rs_message);
		$s_message=main::formatting_query_string($message["isi"])."<br /><br /><strong>".$lang_message_reply." ::</strong> #reply#";
		
		$reply_obj=new message;
		$reply_obj->memberemail=$_SESSION["email"];
		$reply_obj->contactus_ref_no=$message["id"];
		$reply_obj->message_starter_only=false;		
		$rs_message_reply=$reply_obj->get_contactus();
		if(mysql_num_rows($rs_message_reply)>0){
			$s_reply="";
			while($message_reply=mysql_fetch_array($rs_message_reply)){
				$s_reply.="<br />customercare@modena.co.id [".$message_reply["tanggal"]."]<br />".main::formatting_query_string($message_reply["isi"])."<br /><br /><div align=center>---------------- End of message ----------------</div>";
			}
			$s_message=str_replace("#reply#", $s_reply, $s_message);
		}else $s_message=str_replace("#reply#", $lang_message_not_replied_yet, $s_message);
		
		echo "<script>";
		echo "for(var x=1; x<=(x+2); x++){";
		echo "	try{var table_detail=parent.document.getElementById('table_'+x);";
		echo "	for(var y=0; y<table_detail.rows.length; y++)table_detail.deleteRow(y);";
		echo "	}catch(e){break;}";
		echo "}";
		echo "var tablex=parent.document.getElementById('table_".$_REQUEST["row"]."');";
		echo "var rowx=tablex.insertRow(0);";
		echo "var cellx=rowx.insertCell(0);";
		echo "cellx.style.padding='10px';";
		echo "cellx.innerHTML='".$s_message."';";
		echo "</script>";
		exit;

//SERVICE REQUEST	 || SERVICE STATUS || CONTACT US
	}elseif(@$_REQUEST["c"]=="service_request" || @$_REQUEST["c"]=="service_status" || @$_REQUEST["c"]=="contact_us"){
		require_once "securimage.php";
		$securimage = new Securimage();      
		
		if(isset($_REQUEST["t_captcha"]) && !$securimage->check($_REQUEST["t_captcha"])) {
			$captcha_error=true;
		}else{
			include_once "lib/cls_message.php";include_once "lang/".$lang.".php";			
					
			$contactus_ticket=message::new_contactus_ticket();
			
			$message=new message;
			$message->contactus_no=$contactus_ticket;
			$message->contactus_type="0";
			$message->name=@$_REQUEST["t_name"];
			if(isset($_SESSION["email"]) && $_SESSION["email"]!="")$message->member_email=$_SESSION["email"];
			$message->email=@$_REQUEST["t_email"];
			$message->alamat=@$_REQUEST["t_address"];
			$message->kota=@$_REQUEST["t_city"];
			$message->state=@$_REQUEST["t_state"];
			$message->country=@$_REQUEST["t_country"];
			$message->postcode=@$_REQUEST["t_postalcode"];
			$message->phone=@$_REQUEST["t_telephone"];
			$message->handphone=@$_REQUEST["t_handphone"];		
			if(@$_REQUEST["c"]=="service_request"){
				$message->contactus_subject="[modena.co.id] Service Request :: ".$contactus_ticket;		
				$s_message=str_replace("#produk#", $_REQUEST["t_product"], $lang_servicerequest_email);
				$s_message=str_replace("#serialnumber#", $_REQUEST["t_serialnumber"], $s_message);
				$s_message=str_replace("#complain#", $_REQUEST["t_complain"], $s_message);
			}elseif(@$_REQUEST["c"]=="service_status"){
				$message->contactus_subject="[modena.co.id] Service Status :: ".$contactus_ticket;		
				$s_message=str_replace("#serviceno#", $_REQUEST["t_serviceno"], $lang_servicestatus_email);
				$s_message=str_replace("#message#", $_REQUEST["t_complain"], $s_message);
			}else{
				$message->contactus_subject="[modena.co.id] ".$lang_arr_kategori_contact_us[$_REQUEST["s_category"]]." :: ".$contactus_ticket;		
				$s_message=str_replace("#information#", $lang_arr_kategori_contact_us_short[$_REQUEST["s_category"]], $lang_contactus_email);
				$s_message=str_replace("#message#", $_REQUEST["t_complain"], $s_message);	
			}
			$message->contactus=@$s_message;
			$message->__insert_contactus();

			//get message lagi
			unset($message);
			$message=new message;
			$message->contactus_no=$contactus_ticket;
			$message->contactus_email="='".main::formatting_query_string($_REQUEST["t_email"])."'";
			$rs_message=$message->get_contactus();
			if(mysql_num_rows($rs_message)>0){
				$rs_message_=mysql_fetch_array($rs_message);
		
				//kirim email ke member-customercare@modena.co.id
				$message->__set("lang", "lang/".$lang.".php");
				$message->__set("email_template", "template/email.html");
				$message->__set("content_template", "lang/".$lang."_contactus.html");
				$message->__set("email_subject", $rs_message_["subject"]);
				$message->__set("id", $rs_message_["id"]);
				$message->__set("contactus", $rs_message_["isi"]);
				$message->__set("nama", $rs_message_["nama"]);
				$message->__set("email", $rs_message_["email"]);
				$message->__set("alamat", $rs_message_["alamat"].", ".$rs_message_["kota"].", ".$rs_message_["state"].", ".$rs_message_["country"]." ".$rs_message_["postcode"]);
				$message->__set("phone", $rs_message_["telepon"]."/".$rs_message_["handphone"]);
	
				$message->contactus_email("customer");
				$message->__set("contactus", $rs_message_["isi"]."<br /><br /><a href=\"".$url_path."secure/customer_message.php?id=".$contactus_ticket."&opw=true\">Klik disini untuk mengirimkan balasan</a>");
				if(@$_REQUEST["c"]=="service_request"){
					$message->contactus_email("service_request");}
				else{
					$message->contactus_email("");}
				
				
			}			
			echo "<script>location.href='contactus_confirm.php?cs=".@$_REQUEST["c"]."';</script>";
			exit;
		}
		
// CHAT		
	}elseif(@$_REQUEST["c"]=="start_chat"){
		require_once "securimage.php";
      	$securimage = new Securimage();      
		if(!$securimage->check($_REQUEST["t_captcha"])) {
			$captcha_error=true;
		}else{
			$_SESSION["sessionid_os"]=trim(rand(1,1000));
			echo 
			"<script>
				window.open('chat.php?auth_code=".sha1($_SESSION["sessionid_os"])."', 'chat', 'width=500px, height=500px, status=0, toolbar=0');
				location.href='onlinesupport.php';
			</script>";
			exit;
		}	

	}elseif(@$_REQUEST["c"]=="terminate_session"){
		include_once "lib/cls_chat.php";
		chat::__terminate_session($_SESSION["sessionid_os"], "false");

// SUPPORT / DOWNLOAD 
	}elseif(@$_REQUEST["c"]=="request_auth_media_download"){
		require_once "securimage.php";
      	$securimage = new Securimage();      
		if(!$securimage->check($_REQUEST["t_captcha"])) {
			$captcha_error=true;
		}else{
			include_once "lib/cls_member.php";
			$member_data=new member;
			$member_data->email="='".main::formatting_query_string($_SESSION["email"])."'";
			$member_data->enabled="=true";
			$rs_member_data=$member_data->member_data("%");
			$rs_member_data_=mysql_fetch_array($rs_member_data);
		
			include_once "lib/cls_message.php";include_once "lang/".$lang.".php";								
			$contactus_ticket=message::new_contactus_ticket();

			$message=new message;
			$message->contactus_no=$contactus_ticket;
			$message->contactus_type="0";
			$message->name=$rs_member_data_["name"];
			$message->member_email=$rs_member_data_["email"];
			$message->email=$rs_member_data_["email"];
			$message->alamat=$rs_member_data_["address"];
			$message->kota=$rs_member_data_["homecity"];
			$message->state=$rs_member_data_["homestate"];
			$message->country=$rs_member_data_["homecountry"];
			$message->postcode=$rs_member_data_["homepostcode"];
			$message->phone=$rs_member_data_["phone"];
			$message->handphone=$rs_member_data_["handphone"];		
			$message->contactus_subject="[modena.co.id] Download Authorization Request :: ".$contactus_ticket;		
			$s_message=str_replace("#companyname#", $_REQUEST["t_company"], $lang_media_requesting_authorization_email);
			$s_message=str_replace("#companyaddress#", $_REQUEST["t_address"], $s_message);
			$s_message=str_replace("#companytelephone#", $_REQUEST["t_telephone"], $s_message);
			$s_message=str_replace("#companyfax#", $_REQUEST["t_fax"], $s_message);
			$message->contactus=@$s_message;
			$message->__insert_contactus();
			
			//get message lagi
			unset($message);
			$message=new message;
			$message->contactus_no=$contactus_ticket;
			$message->contactus_email="='".main::formatting_query_string($rs_member_data_["email"])."'";
			$rs_message=$message->get_contactus();
			if(mysql_num_rows($rs_message)>0){
				$rs_message_=mysql_fetch_array($rs_message);
				$link_approval="<div style=\"text-align:center\"><a href=\"".$url_path."lib/mainclass.php?c=authorization_download_approval&s=w&e=".sha1($rs_member_data_["email"])."&q=1\">Approve</a> | 
					<a href=\"".$url_path."lib/mainclass.php?c=authorization_download_approval&s=w&e=".sha1($rs_member_data_["email"])."&q=0\">Not Approve</a></div>";
				//kirim email ke member-customercare@modena.co..id
				$message->__set("lang", "lang/".$lang.".php");
				$message->__set("email_template", "template/email.html");
				$message->__set("content_template", "template/contactus.html");
				$message->__set("email_subject", $rs_message_["subject"]);
				$message->__set("id", $rs_message_["id"]);
				$message->__set("contactus", $rs_message_["isi"]);
				$message->__set("nama", $rs_message_["nama"]);
				$message->__set("email", $rs_message_["email"]);
				$message->__set("alamat", $rs_message_["alamat"].", ".$rs_message_["kota"].", ".$rs_message_["state"].", ".$rs_message_["country"]." ".$rs_message_["postcode"]);
				$message->__set("phone", $rs_message_["telepon"]."/".$rs_message_["handphone"]);
				
				$message->contactus_email("customer");
				$message->__set("contactus", $rs_message_["isi"]."<br /><br />".$link_approval);
				$message->__set("internal_rcpt_to", MARCOMM_EMAIL);
				$message->contactus_email("");
			}
			echo "<script>location.href='supportdownload_rq_auth.php?cs=success';</script>";
			exit;
		}	
	
	}elseif(@$_REQUEST["c"]=="authorization_download_approval"){
		include_once "cls_member.php";
		$sql="update membersdata set download_auth=".(@$_REQUEST["q"]=="1"?"true":"false")." 
			where email='".member::member_data_information_orig(@$_REQUEST["e"], "email", "true", 1)."';";
		mysql_query($sql) or die();//("authorization_download_approval error.<br />".mysql_error());
		if(@$_REQUEST["s"]=="w")echo "<script>window.close()</script>";
	

//ENEWS SUBSCRIPTION	
	}elseif(@$_REQUEST["c"]=="enewsletter_subscribe"){
		include_once "lib/cls_member.php";	include_once "lib/cls_message.php";	include_once "lang/".$lang.".php";
		$continue=true;
		$member=new member;
		$member->email="='".main::formatting_query_string($_REQUEST["t_enews_subscription"])."'";
		if(mysql_num_rows($member->member_data("%"))>0)$continue=false;
		if(mysql_num_rows(member::mailing_list_member_data($_REQUEST["t_enews_subscription"], ""))>0)$continue=false;
		if(!$continue)$return_enewsletter_subscription=$lang_t_enews_subscription_exists;
		else{
			mysql_query("insert into enewsmember(email) values('".main::formatting_query_string($_REQUEST["t_enews_subscription"])."');") 
				or die();//("enewsletter_subscribe error.<br />".mysql_error());
				include_once "api.php"; //insert subscribe to mailchimp list
				//kirim email ke member
				$message=new message;
				$message->__set("lang", "lang/".$lang.".php");
				$message->__set("email_template", "template/email_noname.html");
				$message->__set("content_template", "lang/".$lang."_enews_subscription.html");
				$message->__set("email_subject", "[modena.co.id] Mailing list subscription");
				$message->__set("email", $_REQUEST["t_enews_subscription"]);
				$message->__set("nama", "");
				$message->__set("contactus_no", "");
				$message->__set("contactus", "");
				$message->contactus_email("customer");
				
			$return_enewsletter_subscription=$lang_t_enews_subscription_success;
		}
	
//FORGOT PASSWORD	
	}elseif(@$_REQUEST["c"]=="forgot_password"){
		require_once "securimage.php";
      	$securimage = new Securimage();      
		if(!$securimage->check($_REQUEST["t_captcha"])) {
			$captcha_error=true;
		}else{
			include_once "lib/cls_member.php";	
			$member=new member;
			$member->enabled="=true";
			$member->email="='".main::formatting_query_string($_REQUEST["t_email"])."'";
			$rs_member_data=$member->member_data("%");
			if(mysql_num_rows($rs_member_data)>0){
				include_once "lib/cls_message.php";include_once "lang/".$lang.".php";				
				$rs_member_data_=mysql_fetch_array($rs_member_data);
				
				$contactus_ticket=message::new_contactus_ticket();
				
				$message=new message;
				$message->contactus_no=$contactus_ticket;
				$message->contactus_type="0";
				$message->name=$rs_member_data_["name"];
				$message->member_email=$rs_member_data_["email"];
				$message->email=$rs_member_data_["email"];
				$message->alamat=$rs_member_data_["address"];
				$message->kota=$rs_member_data_["homecity"];
				$message->state=$rs_member_data_["homestate"];
				$message->country=$rs_member_data_["homecountry"];
				$message->postcode=$rs_member_data_["homepostcode"];
				$message->phone=$rs_member_data_["phone"];
				$message->handphone=$rs_member_data_["handphone"];		
				$message->contactus_subject="[modena.co.id] Password Request :: ".$contactus_ticket;		
				$s_message=str_replace("#email#", $rs_member_data_["email"], $lang_forgot_password_information);
				$s_message=str_replace("#password#", $rs_member_data_["password"], $s_message);
				$s_message=str_replace("#path#", $url_path, $s_message);
				$message->contactus=@$s_message;
				$message->__insert_contactus();
				
				//get message lagi
				unset($message);
				$message=new message;
				$message->contactus_no=$contactus_ticket;
				$message->contactus_email="='".main::formatting_query_string($rs_member_data_["email"])."'";
				$rs_message=$message->get_contactus();
				if(mysql_num_rows($rs_message)>0){
					$rs_message_=mysql_fetch_array($rs_message);
					//kirim email ke member
					$message->__set("lang", "lang/".$lang.".php");
					$message->__set("email_template", "template/email.html");
					$message->__set("content_template", "template/contactus.html");
					$message->__set("email_subject", $rs_message_["subject"]);
					$message->__set("id", $rs_message_["id"]);
					$message->__set("contactus", $rs_message_["isi"]);
					$message->__set("nama", $rs_message_["nama"]);
					$message->__set("email", $rs_message_["email"]);
					$message->__set("alamat", $rs_message_["alamat"].", ".$rs_message_["kota"].", ".$rs_message_["state"].", ".$rs_message_["country"]." ".$rs_message_["postcode"]);
					$message->__set("phone", $rs_message_["telepon"]."/".$rs_message_["handphone"]);
					
					$message->contactus_email("customer");
				}
				$return_forgot_password=$lang_forgot_password_email_success;
				
			}else{
				$return_forgot_password=$lang_forgot_password_email_not_exists;
			}
		}
	
// FAQ	
	}elseif($_REQUEST["c"]=="faq_sub_category"){
		include_once "cls_faq.php";include_once "../lang/".$lang.".php";
		
		$s_faq_sub_category=faq::get_faq_sub_category($_REQUEST["id"], "","", false, $lang);	
		echo "<script>";
		echo "parent.document.getElementById('sp_".$_REQUEST["id"]."').innerHTML='".str_replace("'", "\'", $s_faq_sub_category[0])."';";
		echo "</script>";
		exit;
	
// KARIR	
	}elseif($_REQUEST["c"]=="career_apply"){
		require_once "securimage.php"; include_once "lib/cls_career.php";
      	$securimage = new Securimage();      
		if(!$securimage->check($_REQUEST["t_captcha"])) {
			$captcha_error=true;
		}else{
			include_once "lang/".$lang.".php";
			//insert karir_data_pribadi
			$sql="insert into karir_data_pribadi(
				pelamarid,tanggallamaran,posisiid,nama,
				tanggallahir,email,
				alamatdomisili,kotadomisili,propinsidomisili,
				telepondomisili,alamattetap,
				kotatetap,propinsitetap,telepontetap,
				hp,gaji,keahlianteknis) values(
				last_insert_id(), now(), '".main::formatting_query_string(($_REQUEST["id"]!=""?$_REQUEST["id"]:$_REQUEST["posisi"]))."', '".main::formatting_query_string($_REQUEST["full_name"])."', 
				'".main::date_database_formatting($arr_month, main::formatting_query_string($_REQUEST["dob"]))."', '".main::formatting_query_string($_REQUEST["email"])."', 
				'".main::formatting_query_string($_REQUEST["domicile_address"])."', '".main::formatting_query_string($_REQUEST["domicile_city"])."', ".($_REQUEST["domicile_state"]!=""?main::formatting_query_string($_REQUEST["domicile_state"]):"null").", 
				'".main::formatting_query_string($_REQUEST["domicile_telephone"])."', '".main::formatting_query_string($_REQUEST["home_address"])."',
				'".main::formatting_query_string($_REQUEST["home_city"])."', '".main::formatting_query_string($_REQUEST["home_state"])."', '".main::formatting_query_string($_REQUEST["home_telephone"])."', 
				'".main::formatting_query_string($_REQUEST["handphone"])."', '".main::formatting_query_string(str_replace(",", "", $_REQUEST["expected_salary"]))."', '".main::formatting_query_string($_REQUEST["technical_expertise"])."');";			
			mysql_query($sql) or die();//(mysql_error());
			$idpelamar=mysql_insert_id();
			
			//insert karir_data_pengalaman
			for($x=1; $x<=4; $x++){
				if($_REQUEST["year_je_start_".$x]!=""){
					$sql="insert into karir_data_pengalaman(pelamarid,
						namaperusahaan,alamatperusahaan,teleponperusahaan,
						bekerjadari,bekerjasampai,
						jabatanawal,jabatanakhir,
						gajiterakhir,uraianpekerjaan) values(".$idpelamar.",
						'".main::formatting_query_string($_REQUEST["company_name_".$x])."', '".main::formatting_query_string($_REQUEST["company_address_".$x])."', '".main::formatting_query_string($_REQUEST["company_telephone_".$x])."', 
						'".main::formatting_query_string($_REQUEST["year_je_start_".$x])."-".main::formatting_query_string($_REQUEST["month_je_start_".$x])."-1', '".main::formatting_query_string($_REQUEST["year_je_end_".$x])."-".main::formatting_query_string($_REQUEST["month_je_end_".$x])."-1',
						'".main::formatting_query_string($_REQUEST["former_position_".$x])."', '".main::formatting_query_string($_REQUEST["latest_position_".$x])."',
						'".main::formatting_query_string(str_replace(",", "", $_REQUEST["latest_salary_".$x]))."', '".main::formatting_query_string($_REQUEST["job_desc_".$x])."');";
					mysql_query($sql) or die();//(mysql_error());
				}
			}
			
			//insert karir_data_pendidikan
			for($x=1; $x<=4; $x++){
				if($_REQUEST["year_eb_start_".$x]!=""){
					$sql="insert into karir_data_pendidikan (pelamarid,
						belajardari,belajarsampai,
						tingkatpendidikan,namainstitusi,
						jurusan,kelulusan) values(".$idpelamar.",
						'".main::formatting_query_string($_REQUEST["year_eb_start_".$x])."-".main::formatting_query_string($_REQUEST["month_eb_start_".$x])."-1', '".main::formatting_query_string($_REQUEST["year_eb_end_".$x])."-".main::formatting_query_string($_REQUEST["month_eb_end_".$x])."-1',
						'".main::formatting_query_string($_REQUEST["education_level_".$x])."', '".main::formatting_query_string($_REQUEST["institution_name_".$x])."',
						'".main::formatting_query_string($_REQUEST["major_".$x])."', case '".main::formatting_query_string($_REQUEST["graduated_".$x])."' when '0' then false when '1' then true end);";
					mysql_query($sql) or die();//(mysql_error());
				}
			}
			
			//insert karir_data_pelatihan
			for($x=1; $x<=4; $x++){
				if($_REQUEST["year_tc_start_".$x]!=""){
					$sql="insert into karir_data_pelatihan (pelamarid,
						pelatihan,pelatihandari,
						pelatihansampai,sertifikat) values(".$idpelamar.",
						'".main::formatting_query_string($_REQUEST["tc_subject_".$x])."', '".main::formatting_query_string($_REQUEST["year_tc_start_".$x])."-".main::formatting_query_string($_REQUEST["month_tc_start_".$x])."-1', 
						'".main::formatting_query_string($_REQUEST["year_tc_end_".$x])."-".main::formatting_query_string($_REQUEST["month_tc_end_".$x])."-1',
						case '".main::formatting_query_string($_REQUEST["certification_".$x])."' when '0' then false when '1' then true end);";
					mysql_query($sql) or die();//(mysql_error());
				}
			}

			//insert karir_data_bahasa
			for($x=1; $x<=4; $x++){
				if($_REQUEST["language_".$x]!=""){
					$sql="insert into karir_data_bahasa (pelamarid,
						bahasa,mendengar,
						berbicara,membaca,
						menulis) values(".$idpelamar.",
						'".main::formatting_query_string($_REQUEST["language_".$x])."', '".main::formatting_query_string($_REQUEST["listening_".$x])."', 
						'".main::formatting_query_string($_REQUEST["speaking_".$x])."', '".main::formatting_query_string($_REQUEST["reading_".$x])."',
						'".main::formatting_query_string($_REQUEST["writing_".$x])."');";
					mysql_query($sql) or die();//(mysql_error());
				}
			}

			//insert karir_data_referensi
			$sql="insert into karir_data_referensi (pelamarid,
				nama,alamat,
				teleponhp,perusahaanpekerjaan,
				hubungan) values(".$idpelamar.",
				'".main::formatting_query_string($_REQUEST["ref_name"])."', '".main::formatting_query_string($_REQUEST["ref_address"])."', 
				'".main::formatting_query_string($_REQUEST["ref_phone"])."', '".main::formatting_query_string($_REQUEST["ref_company"])."',
				'".main::formatting_query_string($_REQUEST["ref_relationship"])."');";
			mysql_query($sql) or die();//(mysql_error());
			
			//kirim email ke hrd
			$career=new career;
			$career->lang="lang/id.php";
			$career->email_content="template/email.html";
			$career->email_recipient=$email_hrd;
			$career->email_subject="[modena.co.id] Aplikasi kerja baru";
			$career->applicant_id=$idpelamar;
			$career->app_main_content="template/career_application_form.html";
			$career->app_job_experience="template/career_application_job_experience.html";
			$career->app_background_education="template/career_application_background_education.html";
			$career->app_training_certification="template/career_application_training_certification.html";
			$career->app_bahasa="template/career_application_bahasa.html";
			$career->send_email_application();
			
			echo "<script>location.href='career_form.php?cs=ok&id=".$_REQUEST["id"]."';</script>";
			exit;
		}

	}elseif($_REQUEST["c"]=="career_apply_upload"){
//		require_once "securimage.php"; include_once "lib/cls_career.php";
 //     	$securimage = new Securimage();      
//		if(!$securimage->check($_REQUEST["t_captcha"])) {
//			$captcha_error=true;
//		}else{	
/*			$path=__DIR__."/../temp/upload/".basename($_FILES['filex']['name']);
			move_uploaded_file($_FILES['filex']['tmp_name'],$path);
			if(mime_content_type($path)!="application/pdf"){
				unlink($path);
				echo "<script>alert('File bukan pdf');history.back();</script>";exit;
			}
			if(filesize($path)>$app_file_max_size){
				unlink($path);
				echo "<script>alert('File lebih besar');history.back();</script>";exit;			
			}

			$sql="insert into karir_data_pribadi(pelamarid,tanggallamaran,
				posisiid,
				nama,
				email, 
				keterangan) values(last_insert_id(), now(),
				'".main::formatting_query_string(($_REQUEST["id"]!=""?$_REQUEST["id"]:$_REQUEST["posisi"]))."',
				'".main::formatting_query_string($_REQUEST["full_name"])."',
				'".main::formatting_query_string($_REQUEST["email"])."',
				'Upload curriculum vitae');";
			mysql_query($sql) or die();//(mysql_error());
			echo "<script>location.href='career_upload.php?cs=ok&id=".$_REQUEST["id"]."';</script>";
			exit;*/
//		}
		
//SEARCH	
	}elseif($_REQUEST["c"]=="load_search_result"){
		include_once "cls_search.php"; include_once "../lang/".$lang.".php";

		if(@$_REQUEST["cs"]=="product"){
			$rs=search::__search_product($lang, @$_REQUEST["src"]);		   
			$s=search::search_product_subsection($rs, @$_REQUEST["num"], $search_result_per_batch);
		}elseif(@$_REQUEST["cs"]=="aboutus"){
			$p=new search;
			$p->lang_code=$lang;
			$p->arr_search_label=$arr_search_label[1];
			$p->arr_search=$arr_search_opt[1];
			$p->arr_aboutus=$arr_search_aboutus;
			$p->folder_aboutus="../lang/";
			$p->search=@$_REQUEST["src"];
			$s=$p->search_aboutus_section(@$_REQUEST["num"], $search_result_per_batch);
		}elseif(@$_REQUEST["cs"]=="faqs"){
			$p=new search;
			$p->lang_code=$lang;
			$p->arr_search_label=$arr_search_label[2];
			$p->arr_search=$arr_search_opt[2];
			$p->faq_class="cls_faq.php";
			$p->search=@$_REQUEST["src"];
			$s=$p->search_faq_section(@$_REQUEST["num"], $search_result_per_batch);
		}elseif(@$_REQUEST["cs"]=="promos"){
			$p=new search;
			$p->lang_code=$lang;
			$p->arr_search_label=$arr_search_label[3];
			$p->arr_search=$arr_search_opt[3];
			$p->search=@$_REQUEST["src"];
			$s=$p->search_news_section(@$_REQUEST["num"], $search_result_per_batch);
		}elseif(@$_REQUEST["cs"]=="store"){
			$p=new search;
			$p->lang_code=$lang;
			$p->arr_search_label=$arr_search_label[4];
			$p->arr_search=$arr_search_opt[4];
			$p->arr_store=$arr_store;
			$p->search=@$_REQUEST["src"];
			$s=$p->search_store_section(@$_REQUEST["num"], $search_result_per_batch);
		}
		
		echo "<script>
			var tbl=parent.document.getElementById('table_".@$_REQUEST["cs"]."');
			tbl.deleteRow(tbl.rows.length-1);";		
		for($x=0; $x<count($s[2]); $x++){
			echo "var tr=tbl.insertRow(tbl.rows.length);
				var td=tr.insertCell(0); ";
			echo "td.setAttribute('style','padding-bottom:10px');";				
			if(@$_REQUEST["cs"]=="product"){
				echo "td.innerHTML='<a href=\"javascript:location.href=\'".$s[2][$x][0]."-".$s[2][$x][1]."\'\" class=link_white_nochange_hover_orange>".str_replace("\r\n","<br />",str_replace("'","\'",$s[2][$x][2]))."</a>';";
			}elseif(@$_REQUEST["cs"]=="aboutus"){

				if($s[2][$x][0]!="")
					echo "td.innerHTML='".$s[2][$x][2]." <a href=\"".str_replace("\r\n","<br />",str_replace("'","\'",$s[2][$x][0]))."\" class=link_white_nochange_hover_orange>MORE</a>';";
				else
					echo "td.innerHTML='".str_replace("\r\n","<br />",str_replace("'","\'",$s[2][$x][2]))."';";
			}elseif(@$_REQUEST["cs"]=="faqs" || @$_REQUEST["cs"]=="promos" || @$_REQUEST["cs"]=="store"){
				echo "td.innerHTML='".str_replace("\r\n","<br />",str_replace("'","\'",$s[2][$x][2]))."';";
			}
		}
		if($s[1]<$s[3]){
			$more_link=search::more_link(@$_REQUEST["cs"], @$_REQUEST["src"], ($s[1]+1));
			echo "var tr=tbl.insertRow(tbl.rows.length);
					var td=tr.insertCell(0); td.setAttribute('style','padding-bottom:10px'); td.setAttribute('id','a_".@$_REQUEST["cs"]."');
					td.innerHTML='".str_replace("\r\n","<br />",str_replace("'","\'",$more_link[1]))."';";
		}
		echo "var h=parent.document.getElementById('h_".@$_REQUEST["cs"]."');
			h.value=(h.value!=''?(parseInt(h.value)+1):1);";
		echo "</script>";
	
	
	}elseif($_REQUEST["c"]=="relogin"){
		@$maic .= "
			function load_login_box(){
				". str_replace("javascript:", "", $TINY_BOX_login) ."
			}
			try{
				$(document).ready(function(){
					load_login_box();
				})
			}catch(e){
				document.body.onload = load_login_box();
			}";
	
	}elseif($_REQUEST["c"]=="debugon"){
		$k1 = "ducksterx_1981";
		$k2 = @$_REQUEST["k"];
		$k3 = "mzf6931";
		$auth = sha1($k1 . $k2 . $k3);
		if($auth == @$_REQUEST["l"]) $_SESSION["debugon"] = true;

	}elseif($_REQUEST["c"]=="debugoff"){
		unset($_SESSION["debugon"]);
		
	}elseif($_REQUEST["c"]=="tradein_register"){
	    //ini_set('upload_max_filesize', '10M');
        //ini_set('post_max_size', '10M');
	    
		// print_r($_REQUEST);exit;
		include_once "cls_message.php";include_once "../lang/".$lang.".php";
		
		$dakon = mysql_fetch_array( mysql_query("select max(tradein_regid) + 1 id from tradein_konsumen") );
		if($dakon['id']=="") $dakon['id']=1;
		// $sql="BEGIN;";
		$state = explode("|",$_REQUEST['t_state']);
		$sql ="INSERT INTO tradein_konsumen(tradein_regid,tradein_tanggal,tradein_nama,tradein_alamat,tradein_region_id,tradein_state_id,tradein_kodepos,tradein_hp,
			tradein_email,tradein_brand,tradein_coupon_code,tradein_verifikasi,tradein_verifikasi_tanggal) 
			VALUES ('".$dakon['id']."', '".date("Y-m-d H:i:s")."','".$_REQUEST['t_name']."','".$_REQUEST['t_address']."','".$_REQUEST['s_city']."',
			'".$state[0]."','".$_REQUEST['t_postalcode']."','".$_REQUEST['t_telephone']."',
			'".$_REQUEST['t_email']."','".$_REQUEST['t_brand']."',NULL,0,NULL);";
		mysql_query($sql);
		
		$_REQUEST['t_state'] = $state[0];
		
		for($i=1;$i<$_REQUEST['produk'];$i++){
			if(@$_FILES['t_foto_'.$i]["error"] == 0) {
				//$tmp = explode(".",basename($_FILES["fileOver"]["name"]));
				//$ext = $tmp[1];
				if(@$_FILES['t_foto_'.$i]["size"] > 2097152){
					//echo "<script>alert('File Size Limit');</script>";
					mysql_query("delete from tradein_konsumen where tradein_regid='".$dakon['id']."'");
					$_REQUEST["gagal"] = 1;
					goto modal;
				}
				
				$nama=basename($_FILES['t_foto_'.$i]["name"]);
				$nama_file_lampiran = $dakon['id']."_".$i."_".preg_replace('/\s+/', '_' ,$nama);
				$alamat = "images/tradein/". $nama_file_lampiran;
				@unlink( $alamat );
				move_uploaded_file( $_FILES['t_foto_'.$i]["tmp_name"],  $alamat );
				//echo $nama_file_lampiran." - ".$alamat;
			}else{
				//echo "<script>alert('File Size Limit');</script>";
				mysql_query("delete from tradein_konsumen where tradein_regid='".$dakon['id']."'");
				$_REQUEST["gagal"] = 1;
				goto modal;
			}
			
			$bongkar = 0;
			$instal = 0;
			if($_REQUEST['c_bongkar_'.$i]==true) $bongkar = 1;
			if($_REQUEST['c_instal_'.$i]==true) $instal = 1;
			$sql ="INSERT INTO tradein_produk(tradein_regid,tradein_categoryid,tradein_kuantitas,tradein_bongkar,tradein_pasang,tradein_foto,tradein_usiaproduk) 
			VALUES ('".$dakon['id']."','".$_REQUEST['t_cat_'.$i]."','".$_REQUEST['t_jumlah_'.$i]."','".$bongkar."','".$instal."',
			'".$nama_file_lampiran."','".$_REQUEST['t_umur_'.$i]."');";
			
			mysql_query($sql);
			$_REQUEST = array_merge($_REQUEST, array("nama_foto_".$i=>$nama_file_lampiran));
		}
		
		$_REQUEST['id'] = $dakon['id'];
		$_REQUEST['tipe']=1;
		$_REQUEST['lang']=$lang;
		
		$message=new message;
		$message->__set("lang", "lang/".$lang.".php");
		$message->__set("email_template", "template/email_noname_tradein.html");
		$message->__set("content_template", "lang/".$lang."_tradein_user.html");
		$message->__set("email_subject", "MODENA Trade In Registration");
		$message->__set("email", $_REQUEST['t_email']);
		$message->__set("req", $_REQUEST);
		$message->__set("nama", "");
		$message->__set("contactus_no", "");
		$message->__set("contactus", "");
		// $message->__set("mode", "preview");
		echo $message->tradein_email("user");
		
		$_REQUEST['tipe']=2;
		$message=new message;
		$message->__set("lang", "lang/".$lang.".php");
		$message->__set("email_template", "template/email_noname_tradein.html");
		$message->__set("content_template", "lang/".$lang."_tradein_cs.html");
		$message->__set("email_subject", "MODENA Trade In Verification");
		$message->__set("email", "fauzi.atmaja@modena.co.id");
		$message->__set("req", $_REQUEST);
		$message->__set("nama", "");
		$message->__set("contactus_no", "");
		$message->__set("contactus", "");
		// $message->__set("mode", "preview");
		echo $message->tradein_email();
		
		echo "<script>alert(\"Registrasi trade in berhasil\");window.location.replace(\"https://www.modena.co.id/tradein_register.php\");</script>";
		// echo $sql;
		exit;
		// echo "<script>alert('".$_FILES["t_foto_2"]["name"]."');</script>";exit;
	}elseif($_REQUEST["c"]=="change_cat"){
		$poto="";
		$string_produk = "Berlaku untuk tukar ke produk baru di dalam kategori: <ol>";
		$sql ="select a.tradein_to_categoryid, b.parentcategoryid, b.Name from tradein_matrix a
				inner join category b on a.tradein_to_categoryid = b.CategoryID
				where a.tradein_from_categoryid='".$_REQUEST['catid']."' order by b.parentcategoryid ";
		$rs_produk = mysql_query($sql);
		while($dapro = mysql_fetch_array($rs_produk)){
			$rs_kat = main::tradein_category($dapro['tradein_to_categoryid']);
			$kat = mysql_fetch_array($rs_kat);
			$string_produk .= "<li>";
			$string_produk .= "<a href=\"https://www.modena.co.id/category-".$dapro['parentcategoryid']."-".$dapro['tradein_to_categoryid']."-clearcompare.php?sc=".$dapro['Name']."\" target=\"_blank\">".ucwords(strtolower($kat['Name']))."</a>, ";
			$string_produk .= "</li>";
			
			$sql="select `name` as nama from product a where a.categoryid='".$dapro['tradein_to_categoryid']."' ORDER BY productid";
			$rsp = mysql_query($sql);
			$katp = mysql_fetch_array($rsp);

			while($katp = mysql_fetch_array($rsp)){
				if(file_exists("../images/product/".$katp['nama'].".png")){
					$poto .= "<a href=\"https://www.modena.co.id/category-".$dapro['parentcategoryid']."-".$dapro['tradein_to_categoryid']."-clearcompare.php?sc=".$dapro['Name']."\" target=\"_blank\"><img alt=\"".$katp['nama']."\" src=\"images/product/".$katp['nama'].".png\" width=\"90\" height=\"90\" border=\"1\"></a> ";
					break;
				}
			}
		}
		
		$os = array(12,15,26,7,126);
		$string_produk .= "</ol>".$poto;
		echo "<script>";		
		echo "parent.document.getElementById('".$_REQUEST['tampilid']."').innerHTML = '".$string_produk."';";
		if(in_array($_REQUEST['catid'],$os)){
			echo "parent.document.getElementById('cekin_".$_REQUEST['x']."').style.visibility = 'visible';";
			echo "parent.document.getElementById('cekbon_".$_REQUEST['x']."').style.visibility = 'visible';";
		}else{
			echo "parent.document.getElementById('cekin_".$_REQUEST['x']."').style.visibility = 'hidden';";
			echo "parent.document.getElementById('cekbon_".$_REQUEST['x']."').style.visibility = 'hidden';";
		}
		echo "</script>";
		exit;	

	}elseif($_REQUEST["c"]=="tradein_resend"){
		// print_r($_REQUEST);exit;
		include_once "cls_message.php";include_once "../lang/".$lang.".php";
		
		$sql="SELECT tradein_regid,tradein_tanggal,tradein_nama,tradein_alamat,tradein_region_id,tradein_state_id,tradein_kodepos,tradein_hp,
			tradein_email,tradein_brand,tradein_coupon_code,tradein_verifikasi,tradein_verifikasi_tanggal,tradein_alasan from tradein_konsumen
			where tradein_regid='".$_REQUEST['id']."'";
		$rs_kon = mysql_query($sql) or die();
		$kon = mysql_fetch_array($rs_kon);
		$_REQUEST['t_name'] = $kon['tradein_nama'];
		$_REQUEST['t_address'] = $kon['tradein_alamat'];
		$_REQUEST['s_city'] = $kon['tradein_region_id'];
		$_REQUEST['t_state'] = $kon['tradein_state_id'];
		$_REQUEST['t_postalcode'] = $kon['tradein_kodepos'];
		$_REQUEST['t_telephone'] = $kon['tradein_hp'];
		$_REQUEST['t_email'] = $kon['tradein_email'];
		$_REQUEST['t_brand'] = $kon['tradein_brand'];
		$_REQUEST['t_pocer'] = $kon['tradein_coupon_code'];
		$_REQUEST['alasan'] = $kon['tradein_alasan'];
		
		$sql="select coupon_id,coupon_code,enabled,expired_date,discount,unlimited,remark_id,remark_en,
					min_qty,min_sales,status_double_promo,image,coupon_mekanisme,qty_quota from discount_coupon where coupon_code = '".$kon['tradein_coupon_code']."'";
		$rs_kon = mysql_query($sql) or die();
		$kon = mysql_fetch_array($rs_kon);
		
		$phpdate = strtotime( $kon['expired_date'] );
		$tgl = date( 'Y-m-d', $phpdate );
		
		$sql = "SELECT tradein_regid,tradein_categoryid,tradein_kuantitas,tradein_bongkar,tradein_pasang,tradein_foto,tradein_usiaproduk,tradein_alasanproduk
			from tradein_produk where tradein_regid = '".$_REQUEST['id']."'";
		
		$rs_kon = mysql_query($sql) or die();
		$i=1;
		while($kon = mysql_fetch_array($rs_kon)){
			$_REQUEST['t_cat_'.$i] = $kon['tradein_categoryid'];
			$_REQUEST['t_jumlah_'.$i] = $kon['tradein_kuantitas'];
			$_REQUEST['c_bongkar_'.$i] = $kon['tradein_bongkar'];
			$_REQUEST['c_instal_'.$i] = $kon['tradein_pasang'];
			$_REQUEST['nama_foto_'.$i] = $kon['tradein_foto'];
			$_REQUEST['t_umur_'.$i] = $kon['tradein_usiaproduk'];
			$_REQUEST['t_alasan_'.$i] = $kon['tradein_alasanproduk'];
			
			$i++;
		}
		
		$_REQUEST['tgl_pakai'] = $tgl;
		$_REQUEST['produk'] = $i;
		$_REQUEST['lang'] = $lang;
		// print_r($_REQUEST);exit;
		
		
		$message=new message;
		$message->__set("lang", "lang/".$lang.".php");
		$message->__set("email_template", "template/email_noname_tradein.html");
		
		if($_REQUEST['tipe']==1){
			$message->__set("content_template", "lang/".$lang."_tradein_user.html");
			$message->__set("email_subject", "MODENA Trade In Registration");
		}else if($_REQUEST['tipe']==2){
			$message->__set("email_template", "template/email_noname_cs.html");
			$message->__set("content_template", "lang/".$lang."_tradein_cs.html");
			$message->__set("email_subject", "MODENA Trade In Verification");
		}else if($_REQUEST['tipe']==3){
			$message->__set("content_template", "lang/".$lang."_tradein_user_setuju.html");
			$message->__set("email_subject", "MODENA Trade In Registration (Approved)");
		}else{
			$message->__set("content_template", "lang/".$lang."_tradein_user_tolak.html");
			$message->__set("email_subject", "MODENA Trade In Registration (Not Approved)");
		}
		
		$mode = $_REQUEST['mode']==""?"preview":$_REQUEST['mode'];
		$message->__set("email", $_REQUEST['t_email']);
		$message->__set("req", $_REQUEST);
		$message->__set("nama", "");
		$message->__set("contactus_no", "");
		$message->__set("contactus", "");
		$message->__set("mode", $mode);
		echo $message->tradein_email();
		// echo $sql;
		exit;
		// echo "<script>alert('".$_FILES["t_foto_2"]["name"]."');</script>";exit;
	}elseif($_REQUEST["c"]=="tradein_konfirm"){
		// print_r($_REQUEST);exit;
		include_once "cls_message.php";include_once "../lang/".$lang.".php";
		
		$sql="SELECT tradein_regid,tradein_tanggal,tradein_nama,tradein_alamat,tradein_region_id,tradein_state_id,tradein_kodepos,tradein_hp,
				tradein_email,tradein_brand,tradein_coupon_code,tradein_verifikasi,tradein_verifikasi_tanggal,tradein_alasan from tradein_konsumen
				where tradein_regid='".$_REQUEST['id']."'";
		$rs_kon = mysql_query($sql) or die();
		$kon = mysql_fetch_array($rs_kon);
		$_REQUEST['t_name'] = $kon['tradein_nama'];
		$_REQUEST['t_address'] = $kon['tradein_alamat'];
		$_REQUEST['s_city'] = $kon['tradein_region_id'];
		$_REQUEST['t_state'] = $kon['tradein_state_id'];
		$_REQUEST['t_postalcode'] = $kon['tradein_kodepos'];
		$_REQUEST['t_telephone'] = $kon['tradein_hp'];
		$_REQUEST['t_email'] = $kon['tradein_email'];
		$_REQUEST['t_brand'] = $kon['tradein_brand'];
		$_REQUEST['t_pocer'] = $kon['tradein_coupon_code'];
		$diskon=$kon['tradein_brand']=='1'?'0.3':'0.25';
		$_REQUEST['lang'] = $lang;
		
		if($kon['tradein_verifikasi']==1){ 
			echo "<script>alert(\"Persetujuan sudah dilakukan sebelumnya\");</script>";exit;
		}
		
		$sql = "SELECT tradein_regid,tradein_categoryid,tradein_kuantitas,tradein_bongkar,tradein_pasang,tradein_foto,tradein_usiaproduk,tradein_alasanproduk
			from tradein_produk where tradein_regid = '".$_REQUEST['id']."'";
		
		$rs_kon = mysql_query($sql) or die();
		$i=1;$lum=0;
		while($kon2 = mysql_fetch_array($rs_kon)){
			if($kon2['tradein_categoryid']==$_REQUEST['cat'] and $kon2['tradein_alasanproduk']!=""){
				echo "<script>alert(\"Persetujuan untuk produk ini sudah dilakukan sebelumnya\");</script>";exit;
			}
			
			$_REQUEST['t_cat_'.$i] = $kon2['tradein_categoryid'];
			$_REQUEST['t_jumlah_'.$i] = $kon2['tradein_kuantitas'];
			$_REQUEST['c_bongkar_'.$i] = $kon2['tradein_bongkar'];
			$_REQUEST['c_instal_'.$i] = $kon2['tradein_pasang'];
			$_REQUEST['nama_foto_'.$i] = $kon2['tradein_foto'];
			$_REQUEST['t_umur_'.$i] = $kon2['tradein_usiaproduk'];
			if($kon2['tradein_alasanproduk']=="") $lum++;
			
			$alas = 0;
			if($_REQUEST['alasan'] != '') $alas = $_REQUEST['alasan'];
			if($kon2['tradein_categoryid']==$_REQUEST['cat']){
				$_REQUEST['t_alasan_'.$i] = $alas;
			}else{
				$_REQUEST['t_alasan_'.$i] = $kon2['tradein_alasanproduk'];
			}
			
			$i++;
		}
		$_REQUEST['produk'] = $i;
		//echo $lum;exit;
		if($lum>0){
			$alas = 0;
			if($_REQUEST['alasan'] != '') $alas = $_REQUEST['alasan'];
			
			$sql ="UPDATE tradein_produk SET tradein_alasanproduk = '".$alas."'
					where tradein_regid = '".$_REQUEST['id']."'
					and tradein_categoryid = '".$_REQUEST['cat']."' ;";
			mysql_query($sql);
			if($lum==1) goto per;
			echo "<script>alert(\"Persetujuan telah disimpan, silahkan lanjutkan untuk persetujuan produk lain\");</script>";
			goto lom;
		}
		
		per:
		$sql = "SELECT tradein_regid,tradein_categoryid,tradein_kuantitas,tradein_bongkar,tradein_pasang,tradein_foto,tradein_usiaproduk,tradein_alasanproduk
			from tradein_produk where tradein_regid = '".$_REQUEST['id']."' and tradein_alasanproduk = 0";
		$rs_kon = mysql_query($sql);
		$jum = mysql_num_rows($rs_kon);
		
		if($jum>=1){
			$cek = "TR".date('y').date('m');
			$kup = date('y').date('m');
		
			$sql = "select max(coupon_id) + 1 id from discount_coupon";
			$rs1 = mysql_fetch_array(mysql_query($sql));
			$copid = $rs1['id'];
			$sql = "select CONVERT(SUBSTR(coupon_code,7,4),int) jum from discount_coupon where coupon_code like '".$cek."%'";
			$rs1 = mysql_query($sql);
			$arr_cek = array();
			while($dt1 = mysql_fetch_array($rs1)){
				$arr_cek[] = (int)$dt1['jum'];
			}
			//print_r($arr_cek);exit;
			
			do {   
				$n = rand(1,9999);

			} while(in_array($n, $arr_cek));
			
			$jumid = (int)$kup * 10000 + $n;
			$kupon = 'TR'.$jumid;
			
			$tgl = date('Y-m-d', strtotime(date('Y-m-d'). ' + 30 days'));
			$sql ="INSERT INTO discount_coupon (coupon_id,coupon_code,enabled,expired_date,discount,unlimited,remark_id,remark_en,
					min_qty,min_sales,status_double_promo,image,coupon_mekanisme,qty_quota)
					VALUES ('".$copid."','".$kupon."','1','".$tgl." 23:59:59',NULL,'0','TRADE-IN','TRADE-IN','1','0','0','',
					'tradein_modena_nonmodena|".$diskon."',NULL);";
			mysql_query($sql);
			
			$sql ="UPDATE tradein_konsumen SET tradein_coupon_code= '".$kupon."',
						tradein_verifikasi= '1',
						tradein_verifikasi_tanggal= '".date("Y-m-d H:i:s")."',
						tradein_alasan= NULL
					where tradein_regid = '".$_REQUEST['id']."';";
			mysql_query($sql);
			
			$_REQUEST['tgl_pakai'] = $tgl;
			$_REQUEST['t_pocer'] = $kupon;
			$_REQUEST['tipe']=3;
			$message=new message;
			$message->__set("lang", "lang/".$lang.".php");
			$message->__set("email_template", "template/email_noname_tradein.html");
			$message->__set("content_template", "lang/".$lang."_tradein_user_setuju.html");
			$message->__set("email_subject", "MODENA Trade In Registration (Approved)");
			$message->__set("email", $kon['tradein_email']);
			$message->__set("req", $_REQUEST);
			//$message->__set("mode", "preview");
			echo $message->tradein_email();
			// echo $sql;
			
			echo "<script>alert(\"Persetujuan telah disimpan dan email telah dikirim kepada konsumen\");</script>";
		}else{
			
			$sql ="UPDATE tradein_konsumen SET tradein_coupon_code= NULL,
						tradein_verifikasi= '1',
						tradein_verifikasi_tanggal= '".date("Y-m-d H:i:s")."',
						tradein_alasan= '".$_REQUEST['alasan']."'
					where tradein_regid = '".$_REQUEST['id']."';";
			mysql_query($sql) or die();
			
			
			$_REQUEST['tipe']=4;
			$message=new message;
			$message->__set("lang", "lang/".$lang.".php");
			$message->__set("email_template", "template/email_noname.html");
			$message->__set("content_template", "lang/".$lang."_tradein_user_tolak.html");
			$message->__set("email_subject", "MODENA Trade In Registration (Not Approved)");
			$message->__set("email", $kon['tradein_email']);
			$message->__set("req", $_REQUEST);
			// $message->__set("mode", "preview");
			echo $message->tradein_email();
			// echo $sql;exit;
			
			echo "<script>alert(\"Persetujuan telah disimpan dan email telah dikirim kepada konsumen\");</script>";
		}
		
		lom:
		exit;
	}


modal:
//################################ functions ##############################


$arr_file_title_keyword_desc = explode("\n", file_get_contents(__DIR__ . "/../nonproduk_sitemap.xml"));
foreach(  $arr_file_title_keyword_desc as $file_title_keyword_desc ){
	list( $file, $title, $keyword, $desc ) = explode("|", $file_title_keyword_desc);
	$arr_page_title[ trim($file) ] = ucwords(trim($title));
	$arr_page_keyword[ trim($file) ] = trim($keyword);
	$arr_page_mdescription[ trim($file) ] = trim($desc);
}

	
if( array_key_exists($page, $arr_page_title) )
	$page_title = " - " . $arr_page_title[$page];

elseif( $page == "category.php" ){
    //$sql = "select `name` nama_kategori, `description` deskripsi from category where categoryid = '". main::formatting_query_string(@$_REQUEST["categoryid"]) ."'";
    $sql = "select c.`name` nama_kategori, c.`description` deskripsi from category a left outer join (select min(sortorder) sortorder, parentcategoryid from category where `ENABLEd` = 1 group by parentcategoryid) b on a.categoryid = b.parentcategoryid left outer join category c on b.parentcategoryid = c.parentcategoryid and b.sortorder = c.sortorder and c.enabled = 1 where a.categoryid = '". main::formatting_query_string(@$_REQUEST["categoryid"]) ."'";
    if( @$_REQUEST["sub_categoryid"] != "" )
	    $sql = "select a.`Name` nama_kategori, b.`Name` nama_subkategori, a.categoryid, b.description deskripsi from category a inner join category b on a.CategoryID = b.ParentCategoryID where b.CategoryID = '". main::formatting_query_string($_REQUEST["sub_categoryid"]) ."'";
	$data_title = mysql_fetch_array( mysql_query($sql) );
	$page_title = " - " . ucwords(strtolower($data_title["nama_kategori"])) /*. ( in_array($data_title["categoryid"], array(1,2,3)) ? " Appliances" : " Professional" )*/ .  ( @$data_title["nama_subkategori"] != "" ? " > " . ucwords(strtolower($data_title["nama_subkategori"])) : "" );
	$arr_page_keyword[ $page ] = implode(",", array($data_title["nama_kategori"], $data_title["nama_subkategori"]));
	$arr_page_mdescription[ $page ] = "MODENA Indonesia Products > " . implode(" > ", array(ucwords($data_title["nama_kategori"]), ucwords($data_title["nama_subkategori"])));
	if( trim($data_title["deskripsi"]) != ""  ) $arr_page_mdescription[ $page ] = str_replace("\r\n","",trim($data_title["deskripsi"]));
}elseif($page == "product.php"){
	$sql = "select *, a.`Name` nama_kategori, b.`Name` nama_subkategori, c.`name` nama_product, c.`description` deskripsi  from category a inner join category b on a.CategoryID = b.ParentCategoryID inner join product c on b.CategoryID = c.categoryid where c.productid = '". main::formatting_query_string($_REQUEST["productid"]) ."'";
	$data_title = mysql_fetch_array( mysql_query($sql) );
	$page_title = " - " . ucwords(strtolower($data_title["nama_subkategori"])) /*. ( in_array($data_title["categoryid"], array(1,2,3)) ? " Appliances" : " Professional" )*/ . " > " . $data_title["nama_product"];
	$arr_page_keyword[ $page ] = implode(",", array($data_title["nama_kategori"], $data_title["nama_subkategori"],$data_title["nama_product"]));
	$arr_page_mdescription[ $page ] = "MODENA Indonesia Products > " . implode(" > ", array(ucwords($data_title["nama_kategori"]), ucwords($data_title["nama_subkategori"]),$data_title["nama_product"]));
	if( trim($data_title["deskripsi"]) != "" )  $arr_page_mdescription[ $page ] = str_replace("\r\n","",trim($data_title["deskripsi"]));
}

$string_meta_tag_keyword = $arr_page_keyword[ $page ];
$string_meta_tag_description = $arr_page_mdescription[ $page ];

?>