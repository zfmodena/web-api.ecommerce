<?
class main{
	private $data=array();
	
	public function __set($name, $value){
		$this->data[$name] = $value;
	}
	
	public function __get($name){
		return $this->data[$name];
	}

	public function __isset($name) {
        return isset($this->data[$name]);
    }
	
	public function __unset($name) {
        unset($this->data[$name]);
    }

/* ############################# metode umum ########################################*/

	public static function insert_code($rand, $code){
		$len_rand=strlen($rand);
		$len_code=strlen($code);
		$pos_rand=rand(0, $len_code-$len_rand);
		$len_pos_rand=strlen($pos_rand);
		$first_code=substr($code, 0, $pos_rand);
		$last_code=substr($code, $pos_rand);
		$new_code=$first_code.$rand.$last_code.$pos_rand.$len_pos_rand;
		return $new_code;
	}
	public static function remove_code($rand, $code){
		$len_pos_rand=substr($code, strlen($code)-1);
		$pos_rand=substr($code, strlen($code)-$len_pos_rand-1, $len_pos_rand);
		$first_code=substr($code, 0, $pos_rand);
		$last_code=substr($code, strlen($first_code)+strlen($rand));
		$return=$first_code.$last_code;
		return substr($return, 0, strlen($return)-$len_pos_rand-1);
	}

	public static function fake_url($source, $original_parameter){
		$return="";
		$arr_source=preg_split("//", $source);
		$num_par=rand(1,4);$pos_original_parameter=rand(0, $num_par);
		for($x=0; $x<=$num_par; $x++){
			if($x==$pos_original_parameter)$return.=$original_parameter."+";
			$par=rand(0, count($arr_source)-1);
			if($x%2==0)
				if(strtolower(substr($arr_source[$par],0, 1))=="c")continue;
				else $return.=substr($arr_source[$par],0, 1)."=".substr(md5($arr_source[$par]),0,rand(1, strlen(md5($arr_source[$par]))))."+";
			else 
				if(strtolower(substr($arr_source[$par],0, 1))=="c")continue;
				else $return.=substr($arr_source[$par],0, 1)."=".substr(crypt($arr_source[$par]),0,rand(1,strlen(crypt($arr_source[$par]))))."+";
		}return $return;
	}
	
	/*
	$cid=category id
	$sc=sub category name
	$p=product id name
	*/
	public static function sufix_url_with_productid($cid, $sc="", $p=""){
		$arr_cid=array(1=>"cooking", "cleaning", "cooling");
		$sufix="#".
			$arr_cid[$cid]."-".
			main::friendlyURL($sc).
			($p!=""?"-".main::friendlyURL($p):"");
		return $sufix;
	}
	
	static function friendlyURL($string){
		$string = preg_replace("`\[.*\]`U","",$string);
		$string = preg_replace('`&(amp;)?#?[a-z0-9]+;`i','-',$string);
		$string = htmlentities($string, ENT_COMPAT, 'utf-8');
		$string = preg_replace( "`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i","\\1", $string );
		$string = preg_replace( array("`[^a-z0-9]`i","`[-]+`") , "-", $string);
		return strtolower(trim($string, '-'));
	}
	
	public function string_from_array($arr, $mode /*mode: value||key*/, $separator){		
		$s_return="";
		if($separator=="")$separator=",";
		foreach($arr as $key=>$value){
			if($mode=="value"){$s_return.=$value."".$separator;}
			elseif($mode=="key"){$s_return.=$key."".$separator;}
		}return substr($s_return, 0, strlen($s_return)-strlen($separator));
	}

	public static function get_array_index($arr, $search, $mode){
		$return="";$counter=0;
		foreach($arr as $key=>$value){
			if($mode=="key"){$diff=$key;}
			elseif($mode=="value"){$diff=$value;}
			if($diff==$search){$return=$counter;break;}
			$counter++;
		}return $return;
	}	
	
	public static function date_database_formatting($arr_month, $date){
		$arr_date=preg_split("/ /", $date);
		$month = main::get_array_index($arr_month, $arr_date[1], "value")+1;
		$month = $month < 10 ? "0" . $month : $month;
		return $arr_date[2]."-".$month."-".$arr_date[0];
	}
	
	public static function date_from_jquery_to_string($arr_month, $date){
		$arr_date=explode("/", $date);
		return @$arr_date[0]." ".@$arr_month[(int)$arr_date[1] - 1]." ".@$arr_date[2];		
	}
	
	public static function formatting_query_string($string){
		$string=str_replace("'", "", $string);// amankan karakter ' :database
		$string=str_replace("*", "", $string);// amankan karakter ' :database
		$string=str_replace("<", "&lt;", $string);// amankan karakter-2 berpotensi xss
		$string=str_replace(">", "&gt;", $string);
		$string=str_replace("&lt;br /&gt;", "<br />", $string);//pulihkan hanya untuk html break
		$string=str_replace("&lt;strong&gt;", "<strong>", $string);$string=str_replace("&lt;/strong&gt;", "</strong>", $string);//pulihkan hanya untuk html strong
		$string=str_replace("&lt;hr width=100% size=1 /&gt;", "<hr width=100% size=1 />", $string);//pulihkan hanya untuk html horizontal line
		//$string=str_replace("=", "&#61;", $string);
		//$string=str_replace("\"", "&quot;", $string);		
		return $string;
	}
	
	public static function formatting_javascript_string($string){
		$string=str_replace("'", "\\'", $string);// amankan karakter ' :javascript
		$string=str_replace("<", "&lt;", $string);// amankan karakter-2 berpotensi xss
		$string=str_replace(">", "&gt;", $string);
		$string=str_replace("&lt;br /&gt;", "<br />", $string);//pulihkan hanya untuk html break
		$string=str_replace("&lt;strong&gt;", "<strong>", $string);$string=str_replace("&lt;/strong&gt;", "</strong>", $string);//pulihkan hanya untuk html strong
		$string=str_replace("&lt;hr width=100% size=1 /&gt;", "<hr width=100% size=1 />", $string);//pulihkan hanya untuk html horizontal line
		//$string=str_replace("=", "&#61;", $string);
		//$string=str_replace("\"", "&quot;", $string);		
		return $string;	
	}
	
	public static function formatting_output_html_string($string){
		$string = str_ireplace("<br />", "\r\n", $string); 
		return $string;
	}

	public static function price_range($arr_price){
		$return = $checkbox_value = array();
		if(count($arr_price)<=0){$return[1]="N/A";return array("control"=>$return, "checkbox_value"=>$checkbox_value);}
		$default_range_price=1000000;$max_row=5;
		$range_price=$default_range_price;
		$row=round($arr_price[count($arr_price)-1]/$range_price, 0);
		if($row>$max_row){
			$row=$max_row;
			$range_price=(round(($arr_price[count($arr_price)-1]/$max_row)/$default_range_price)*$default_range_price);
		}else{
			if($row==1)$row+=2;
		}
		/*$price_checkbox="<input type=\"checkbox\" name=\"cb_price_#cbname#\" id=\"cb_price_#cbname#\" value=\"#cbvalue#\"  
			onClick=\"javascript:greylayer('');__submit('','c=product_list')\" #checked# >|
			<label for=\"cb_price_#cbname#\" #style#>#cblabel#</label>";*/
		$price_checkbox="<input type=\"checkbox\" name=\"cb_price_#cbname#\" id=\"cb_price_#cbname#\" value=\"#cbvalue#\" #checked# />|
			<label for=\"img_cb_price_#cbname#\" #style#>#cblabel#</label>";
		$s_price_checkbox="";				

		for($x_price=1; $x_price<=$row; $x_price++){
			if($x_price==1)$price="< #curr#".($x_price*$range_price);
			elseif($x_price==$row)$price="> #curr#".(($x_price-1)*$range_price);
			else $price="#curr# between ".(($x_price-1)*$range_price)." - #curr#".($x_price*$range_price);

			$checkbox_value[$x_price] = str_replace("#curr#", "", str_replace("-", "and", $price));
			
			$s_price_checkbox_=str_replace("#cbname#", $x_price, $price_checkbox);
			$s_price_checkbox_=str_replace("#cbvalue#", $checkbox_value[$x_price], $s_price_checkbox_);
			$s_price_checkbox_=str_replace("#cblabel#", str_replace(" between ", "", str_replace("#curr#", "Rp", $price)), $s_price_checkbox_);
			$s_price_checkbox_=str_replace("Rp".($x_price-1)*$range_price, "Rp".number_format(($x_price-1)*$range_price), $s_price_checkbox_);
			$s_price_checkbox_=str_replace("Rp".$x_price*$range_price, "Rp".number_format($x_price*$range_price), $s_price_checkbox_);	
			$return[$x_price]=$s_price_checkbox_;			
		}
		return array("control"=>$return, "checkbox_value"=>$checkbox_value);
	}

	public static function shipment_state($state_id){		
		settype($state_id, "integer");
		$sql="select state_id, /*concat('Prov. ', state)*/ state, sortorder, cost, enabled from shipment_state where enabled='1' ";
		if($state_id!="")$sql.="and state_id='".main::formatting_query_string($state_id)."' ";
		$sql.="order by sortorder";
		$return=mysql_query($sql) or die();//("shipment_state query error.<br />".mysql_error());
		return $return;
	}	
	
	public static function shipment_exception($state_id, $region){
		$sql="select b.state_id, b.region_id, b.region from shipment_exception b where b.enabled='1' ";
		if($state_id!="")$sql.="and b.state_id='".main::formatting_query_string($state_id)."' ";
		if($region!="")$sql.="and (b.region_id='".main::formatting_query_string($region)."' or b.region='".main::formatting_query_string($region)."')";
		$sql.="order by b.sortorder";
		$return=mysql_query($sql) or die();//("shipment_exception query error.<br />".mysql_error());		
		return $return;	
	}

/*	
	public static function shipment_state_include_region_options($sel /*state|region, $lang_other_city, $propinsi_free_shipping){
		$s_return="";
		$sql="select state_id, state, cost from shipment_state where enabled='1' order by sortorder;";
		$rs_state=mysql_query($sql) or die();//("shipment_state_include_region I error.<br />".mysql_error());
		$arr_state=preg_split("/\|/", $sel);
		$s_region="";
		while($state=mysql_fetch_array($rs_state)){
			$s_return.="<a href=\"".$state["state_id"]."|".$state["cost"]."|\" class=\"link_white_nochange_hover_grey\">".$state["state"]."</a><br />";
			$sql="select region_id, region from shipment_exception where state_id='".$state["state_id"]."' and enabled='1' order by sortorder;";
			$rs_region=mysql_query($sql) or die();//("shipment_state_include_region II error.<br />".mysql_error());
			$selected="";
			while($region=mysql_fetch_array($rs_region)){
				if($state["state_id"]==$arr_state[0]&&$region["region_id"]==$arr_state[1]){
					$selected="selected";
					$s_region=$region["region"];
				}
/*				$s_return.="&nbsp;&nbsp;
					<a href=\"".$state["state_id"]."|0|".$region["region_id"]."\" ".$selected." class=\"link_white_nochange_hover_grey\">&raquo; ".$region["region"]."</a><br />";
			}
		}
		$a_return[0]=$s_return;
		$a_return[1]=$s_region;
		return $a_return;
	}
*/		
	private static function discount_coupon($code, $enabled, $not_expired, $lang = "id"){/* return nilai diskon */		
		$return_discount=0;
		$mekanisme_khusus = false;
		$status = "";
		$status_double_promo = 0;
		$sql="select coupon_code, discount, unlimited, min_qty, min_sales, coupon_mekanisme, remark_". $lang ." status, status_double_promo
			from discount_coupon where  left(coupon_code, 3) <> 'CUL' and coupon_code='".main::formatting_query_string($code)."' and enabled='".$enabled."' ";
		if($not_expired)$sql.=" and expired_date>=now();";
		$rs_discount_coupon=mysql_query($sql) or die(mysql_error());//("discount_coupon query error".mysql_error());
		if(mysql_num_rows($rs_discount_coupon)>0){
			$rs_discount_coupon_=mysql_fetch_array($rs_discount_coupon);
			//cek limited ato unlimited
			if($rs_discount_coupon_["unlimited"]!="1"){
				//cek apakah kode sudah dpergunakan untuk pembayaran (order_status>0)
				$rs_check_coupon=mysql_query("select 1 from ordercustomer where concat(left(coupon_code, ". strlen(trim($rs_discount_coupon_["coupon_code"])) ." ),'-') = '".trim($rs_discount_coupon_["coupon_code"])."-' 
				    and (order_status>'0' or ( order_status <0 and date_add(order_date, interval ".$GLOBALS["payment_va_expiration"]." minute) >= CURRENT_TIMESTAMP ) );") or die();//("check discount query error.<br />".mysql_error());
				if(mysql_num_rows($rs_check_coupon)>0)$return_discount=0;
				else $return_discount=$rs_discount_coupon_["discount"];
			}else $return_discount=$rs_discount_coupon_["discount"];
			// override dengan mekanisme kupon
			list($nama_mekanisme, $arr_parameter) = explode("|", $rs_discount_coupon_["coupon_mekanisme"]);
			if( $nama_mekanisme != "" && file_exists( __DIR__ . "/cp/" . $nama_mekanisme . ".php" ) ){
				if($mode != "") return $nama_mekanisme;
				include_once __DIR__ . "/cp/" . $nama_mekanisme . ".php";
				$return_discount = call_user_func ( $nama_mekanisme, $arr_parameter );				
				$mekanisme_khusus = true;
			}
			//cek min qty dan min sales value
			$arr_rs=array($rs_discount_coupon_["min_qty"], $rs_discount_coupon_["min_sales"]);
			$arr_val=array(array_sum($_SESSION["shopping_cart"]),$GLOBALS["discount_subtotal"]);
			for($x=0; $x<count($arr_rs); $x++)
				if($arr_rs[$x]!="" && $arr_rs[$x]>0 && $arr_rs[$x]>$arr_val[$x])	$return_discount=0;
			//convert diskon yg kurang dari 0, berarti diskon promo_all_item
			if($return_discount<0){
				//return $return_discount;
			}				
			$status = $rs_discount_coupon_["status"];
			$status_double_promo = $rs_discount_coupon_["status_double_promo"];
		}

		if($return_discount!=0 && !$mekanisme_khusus){
			require_once "cls_discount.php";
			foreach($_SESSION["shopping_cart"] as $productid=>$qty) {//if(order_online_promo::check_ipc_per_item($productid)) return -1;	
				$rs=order_online_promo::isnt_ipc_per_item($productid);
				if(mysql_num_rows($rs)>0){	
					$d=mysql_fetch_array($rs);
					if($d["promocode_safe"]!="1" )	return -1;
				}else{
					//if(mysql_num_rows(order_online_promo::discount_product_source($productid))>0) return -1;
					if( $status_double_promo != 1 )	return -1;
				}
			}
		}

		return array("diskon" => $return_discount, "status" => $status);
	}
	
	private static function culinaria_discount_coupon($code, $enabled, $not_expired, $lang = "id"){/* return nilai diskon */		
		$return_discount=0;
		$mekanisme_khusus = false;
		$status = "";
		$status_double_promo = 0;
		$sql="select coupon_code, discount, unlimited, min_qty, min_sales, coupon_mekanisme, remark_". $lang ." status, status_double_promo
			from discount_coupon where  left(coupon_code, 3) = 'CUL' and coupon_code='".main::formatting_query_string($code)."' and enabled='".$enabled."' ";
		if($not_expired)$sql.=" and expired_date>=now();";
		$rs_discount_coupon=mysql_query($sql) or die(mysql_error());//("discount_coupon query error".mysql_error());
		if(mysql_num_rows($rs_discount_coupon)>0){
			$rs_discount_coupon_=mysql_fetch_array($rs_discount_coupon);
			//cek limited ato unlimited
			if($rs_discount_coupon_["unlimited"]!="1"){
				//cek apakah kode sudah dpergunakan untuk pembayaran (order_status>0)
				$rs_check_coupon=mysql_query("select 1 from culinaria_ordercustomer where concat(left(coupon_code, ". strlen(trim($rs_discount_coupon_["coupon_code"])) ." ),'-') = '".trim($rs_discount_coupon_["coupon_code"])."-' 
				    and (order_status>'0' or ( order_status <0 and date_add(order_date, interval ".$GLOBALS["payment_va_expiration"]." minute) >= CURRENT_TIMESTAMP ) );") or die();//("check discount query error.<br />".mysql_error());
				if(mysql_num_rows($rs_check_coupon)>0)$return_discount=0;
				else $return_discount=$rs_discount_coupon_["discount"];
			}else $return_discount=$rs_discount_coupon_["discount"];
			// override dengan mekanisme kupon
			list($nama_mekanisme, $arr_parameter) = explode("|", $rs_discount_coupon_["coupon_mekanisme"]);
			if( $nama_mekanisme != "" && file_exists( __DIR__ . "/cp/" . $nama_mekanisme . ".php" ) ){
				if($mode != "") return $nama_mekanisme;
				include_once __DIR__ . "/cp/" . $nama_mekanisme . ".php";
				$return_discount = call_user_func ( $nama_mekanisme, $arr_parameter );				
				$mekanisme_khusus = true;
			}
			//cek min qty dan min sales value
			$arr_rs=array($rs_discount_coupon_["min_qty"], $rs_discount_coupon_["min_sales"]);
			$arr_val=array(array_sum($_SESSION["shopping_cart"]),$GLOBALS["discount_subtotal"]);
			for($x=0; $x<count($arr_rs); $x++)
				if($arr_rs[$x]!="" && $arr_rs[$x]>0 && $arr_rs[$x]>$arr_val[$x])	$return_discount=0;
			//convert diskon yg kurang dari 0, berarti diskon promo_all_item
			if($return_discount<0){
				//return $return_discount;
			}				
			$status = $rs_discount_coupon_["status"];
			$status_double_promo = $rs_discount_coupon_["status_double_promo"];
		}

		if($return_discount!=0 && !$mekanisme_khusus){
			require_once "cls_discount.php";
			foreach($_SESSION["culinaria_shopping_cart"] as $productid=>$qty) {//if(order_online_promo::check_ipc_per_item($productid)) return -1;	
				$rs=order_online_promo::isnt_ipc_per_item($productid);
				if(mysql_num_rows($rs)>0){	
					$d=mysql_fetch_array($rs);
					if($d["promocode_safe"]!="1" )	return -1;
				}else{
					//if(mysql_num_rows(order_online_promo::discount_product_source($productid))>0) return -1;
					if( $status_double_promo != 1 )	return -1;
				}
			}
		}

		return array("diskon" => $return_discount, "status" => $status);
	}
	
	public static function counting_discount($code, $subtotal, $lang){
		$nilai_status_diskon = main::discount_coupon($code, "1", true, $lang);
		$discount_coupon=$nilai_status_diskon["diskon"];
		if($discount_coupon>1)	$discount_price=$discount_coupon;			
		else					$discount_price=$subtotal*$discount_coupon;		
		$discount_grandtotal=$subtotal-$discount_price;
		
		// cek kode promo karyawan
		if( $discount_coupon > 0 )
			require_once "cls_coupon_code_karyawan.php";
		
		$return=array($discount_coupon, $discount_price, $discount_grandtotal, $nilai_status_diskon["status"]);
		return $return;
	}
	
	public static function culinaria_counting_discount($code, $subtotal, $lang){
		$nilai_status_diskon = main::culinaria_discount_coupon($code, "1", true, $lang);
		$discount_coupon=$nilai_status_diskon["diskon"];
		if($discount_coupon>1)	$discount_price=$discount_coupon;			
		else					$discount_price=$subtotal*$discount_coupon;		
		$discount_grandtotal=$subtotal-$discount_price;
		
		// cek kode promo karyawan
		//if( $discount_coupon > 0 )
		//	require_once "cls_coupon_code_karyawan.php";
		
		$return=array($discount_coupon, $discount_price, $discount_grandtotal, $nilai_status_diskon["status"]);
		return $return;
	}

	static function __set_option($arr, $opt_selected="", $mode=""){
		$s_option="";
		foreach($arr as $key=>$value){
			$selected="";
			if(!is_array($value)){
				if($mode=="")	{if($opt_selected==$key)$selected="selected";}
				else{if($opt_selected==$value)$selected="selected";}
				if($mode=="")	$s_option.="<option value=\"".$key."\" ".$selected.">".ucwords(strtolower($value))."</option>";
				else $s_option.="<option value=\"".$value."\"".$selected.">".ucwords(strtolower($value))."</option>";
			}else{				
				foreach($value as $key1=>$value1){
					$selected="";
					if($mode=="")	{if($opt_selected==$key1)$selected="selected";}
					else{if($opt_selected==$value1)$selected="selected";}
					
					if($mode=="")	$s_option.="<option value=\"".$key1."\" ".$selected.">".ucwords(strtolower($value1))."</option>";
					else $s_option.="<option value=\"".$value1."\"".$selected.">".ucwords(strtolower($value1))."</option>";
				}
			}
		}
		return $s_option;
	}
	
	public static function __delete_all_pending_product_inventory_purchased($hour_interval){
		$sql="delete from ordercustomer where 
			order_no is null or order_no = '' ";
		mysql_query($sql) or die();//("__delete_all_pending_product_inventory_purchased error".mysql_error());
		return;
		$sql="delete from productinventory where 
			type=0 and order_no is not null and 
			order_no in (select order_no from ordercustomer where order_status=0) and 
			order_no in (select transidmerchant from doku where date_add(starttime, interval ".$hour_interval.")<=now())";
		mysql_query($sql) or die();//("__delete_all_pending_product_inventory_purchased error".mysql_error());
	}
	
	public static function __delete_all_pending_doku($hour_interval){
		$sql="delete from doku where date_add(starttime, interval ".$hour_interval.")<=now();";
		mysql_query($sql) or die();//("__delete_all_pending_doku error".mysql_error());
	}
	
	public static function selection_box_options($arr_options, $selected_key){
		$s_return="";
		foreach($arr_options as $key=>$value){
			$selected="";
			if($key==$selected_key)$selected="selected";
			$s_return.="<option value=\"".$key."\" ".$selected.">-. ".$value."</option>";
		}return $s_return;
	}

	public static function check_email_address($email) {
		return preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/iU', $email) ? true : false;
	}
	
	public static function reload_page($arr_request,$url="", $target="_self"){
		echo "<html><body onload=\"javascript:form1.submit()\">";
		echo "<form name=\"form1\" id=\"form1\" method=\"post\" action=\"".$url."\" target=\"". $target ."\">";
		foreach($arr_request as $key=>$value)
			echo "<input type=\"hidden\" name=\"".$key."\" id=\"".$key."\" value=\"".$value."\" />";
		echo "</form>";
		echo "</body></html>";
	}

/* ajax */	
	static function __callback(
		$callback = "", 
		$msg = "", 
		$is_direct = false /*false : diparsing dulu ke js function generated by fungsi dibawah, sblm ditampilin ke user; true : langsung ditampilin ke user*/
	){
		if(!$is_direct){
			if($callback == "") throw new Exception('js callback function not set!');
			$tinybox = "TINY.box.show({image:'". __DIR__ ."/../images/loading.gif',boxid:'',width:33,height:33,fixed:true,maskid:'greymask',maskopacity:40,close:false});";
			$return = "<script>try{window.onload=function(){". $tinybox . $callback .";callback('". $msg ."');}}catch(err){alert(err)}</script>";
		}else	$return = $msg;
		return $return;
	}
	
	static function initiating_callback($obj, $msg){
		$cb = "callback";
		if(@$_REQUEST[$cb] != ""){						
			$obj->view->assign($cb, self::__callback($_REQUEST[$cb], $msg));
			$obj->view->display('loading_view');
		}else	echo self::__callback("", $msg, true);
	}
	
// ##############################################################################################

/*
fungsi check internet koneksi ke pusat
*/
	public static function check_internet_connection($target_url){
		return __ECOMMERCE_ACTIVATION__; // UNCOMMENT UNTUK AKTIFASI SHOPPING CART
		//return false; // UNCOMMENT UNTUK INAKTIFASI SHOPPING CART
/*		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $target_url."?c=check");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FAILONERROR, TRUE);
		$df=curl_exec($ch);
		if(@$df!="OK"){*/
			//cek alternatif kedua pake perbedaan waktu tabel product_inventory_ dengan now, selisih interval diasumsikan maksimal 4,5 menit masih OK
			$sql="select case when now() - interval 300 second <= a.date then 1 else 0 end status
				from product_inventory_ a order by date desc limit 1;";
			$rs_check = mysql_query($sql) or die("");
			if(mysql_num_rows($rs_check) <= 0){
				main::send_email("","Error __kode_produk_accpac","Error product_inventory_ rows=0.<br />Pls check __kode_produk_accpac, kemungkinan terjadi duplikasi data!");
				return false;
			}else{
				$rs=mysql_fetch_array($rs_check);
				if($rs["status"]!=1){
					main::send_email("","INTERNET ERROR","<strong>KONEKSI INTERNET DARI CBN KE SATRIO ERROR, dan juga data master stok terupdate lebih dari maksimal 4,5 menit.</strong>");
					return false;
				}else return true;
			}
/*		}else return true;
		curl_close($ch);*/
	}

/*
fungsi send email error
*/
	public static function send_email($target=SUPPORT_EMAIL,$subject="",$content=""){
		$message=file_get_contents(EMAIL_TEMPLATE);	

		$message=str_replace("#greetingname#", $target, $message);
		$message=str_replace("#content#", $content, $message);
		$message=str_replace("#lang_email_info_en#", "", $message);
		$message=str_replace("#lang_email_footer#", "", $message);
		$message=str_replace("#horizontal_line#", "<hr width=100% size=1 />", $message);

		if($target=="")$target=SUPPORT_EMAIL;
		$subject = $subject;
		$to["To"]=$target;
		$to["Bcc"]=SUPPORT_EMAIL;
		$recipient_header_to=$target;
		$from=SUPPORT_EMAIL;

		ini_set("include_path",INCLUDE_PATH);
		require_once "pear/Mail.php";
		require_once "pear/mime.php";		
		
		$mime=new Mail_mime(array('eol' => CRLF));
		$mime->setTXTBody(strip_tags($message));
		$mime->setHTMLBody($message);	
		
		$headers = array ('From' => $from,
			'To' => $recipient_header_to, 
			'Subject' => $subject);		
		$smtp=Mail::factory('smtp',
			array ('host' => SMTP_HOST,
			'auth' => SMTP_AUTH,
			'username' => SMTP_USERNAME,
			'password' => SMTP_PASSWORD));		
						
		$body=$mime->get();
		$headers_=$mime->headers($headers);		

		$mail = $smtp->send($to, $headers_, $body);
		$pear=new PEAR;
		if ($pear->isError($mail))echo "Email Error. " . $mail->getMessage();
		restore_include_path ();
		//mail($to, $subject, $message, $headers);		
		//return $message;
	}
	
	public static function tradein_category($id=''){		
		$sql="Select * from (
		select a.CategoryID, a.parentid, CASE WHEN a.parentid <= 3 THEN CONCAT(x.`Name`,' - ', a.`Name`) ELSE CONCAT('Professional ', x.`Name`, ' - ', a.`Name`) END `Name` from (
			select distinct a.CategoryID, a.`Name`, IFNULL(a3.CategoryID,a2.CategoryID) parentid from category a
					inner join tradein_matrix b on a.CategoryID = b.tradein_from_categoryid 
					LEFT JOIN category a2 on a.ParentCategoryID = a2.CategoryID
					LEFT JOIN category a3 on a2.ParentCategoryID = a3.CategoryID 
			) a
			INNER JOIN category x on a.parentid = x.CategoryID
		) a ";
		if($id <> '') $sql .= "Where a.CategoryID='".$id."' ";
		$sql .= "ORDER BY a.parentid";
		$return=mysql_query($sql) or die();//("shipment_state query error.<br />".mysql_error());
		return $return;
	}	
}
?>
