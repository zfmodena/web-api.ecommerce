<?

class shoppingcart extends main{
	private $data=array();
	
	public function __set($name, $value){
		$this->data[$name] = $value;
	}
	
	public function __get($name){
		if(isset($this->data[$name]))return $this->data[$name];
	}

	public function __isset($name) {
        return isset($this->data[$name]);
    }
	
	public function __unset($name) {
        unset($this->data[$name]);
    }
	
/* ############################# metode umum ########################################*/

	public static function get_total_product_weight($arr_productid_quantity, $lang){
		include_once("cls_product.php");
		$product_weight=0;
		foreach($arr_productid_quantity as $key=>$value){
			$product_ship_estimation=new product;
			$product_ship_estimation->lang=$lang;
			$product_ship_estimation->productid="=".main::formatting_query_string($key);
			$product_ship_estimation->penable="='Y'";
			$rs_product_ship_estimation=$product_ship_estimation->product_list("'%'");
			$rs_product_ship_estimation_=mysql_fetch_array($rs_product_ship_estimation);
			$product_weight+=$rs_product_ship_estimation_["weight"]*$value;
		}
		return $product_weight;
	}
	
	public static function get_shipping_cost_per_weight($shippingid, $opt_parameter=NULL){
		if(func_num_args()<=1)$parameter="state_id=(select a.shipping_state from membersshipping a where a.shippingid='".main::formatting_query_string($shippingid)."')
			and region_id=(select a.shipping_region from membersshipping a where a.shippingid='".main::formatting_query_string($shippingid)."') and enabled='1';";
		else $parameter="state_id='".$shippingid."' 
			/*and state_id not in (select state_id from shipment_exception where upper(trim(region))=upper(trim('".$opt_parameter."')) and enabled=true)*/
			and upper(trim(region_id))=upper(trim('".$opt_parameter."')) and enabled='1';";
		$sql="select cost, percent from shipment_exception where ".$parameter; 
		$rs_shipping_cost=mysql_query($sql) or die();//("get_shipping_cost_per_weight query error.<br />".mysql_error());
		$rs_shipping_cost_=mysql_fetch_array($rs_shipping_cost);
		$return["cost"]=$rs_shipping_cost_["cost"];
		$return["percent"]=$rs_shipping_cost_["percent"];
		return $return;
	}

	public static function get_shipping_cost($total_sales, $total_weight, $shippingid, $opt_parameter=NULL){
		if(func_num_args()<=3)	$cost=shoppingcart::get_shipping_cost_per_weight($shippingid)	;	
		else									$cost=shoppingcart::get_shipping_cost_per_weight($shippingid, $opt_parameter)	;
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

	private static function get_new_order_number($mode="ONLINE" /* ONLINE || SHOWROOM */){
		$sql="select concat('#prefix#',day(now()),month(now()),right(year(now()),2),'',right(rand(),3)) as order_number;";
		if($mode!="ONLINE") 	$sql=str_replace("#prefix#", @$_SESSION["showroom_inisial"], $sql);
		else					$sql=str_replace("#prefix#", "", $sql);
		$rs_order_number=mysql_query($sql) or die();//("get_new_order_number query error.<br />".mysql_error());
		$rs_order_number_=mysql_fetch_array($rs_order_number);
		
		$check_order_number_oc=mysql_query("select 1 from ordercustomer where order_no='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		$check_order_number_oc_log=mysql_query("select 1 from ordercustomer_log where order_no='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		$check_order_number_doku=mysql_query("select 1 from doku where transidmerchant='".main::formatting_query_string($rs_order_number_["order_number"])."';");		
		$check_order_number_oc_culinaria=mysql_query("select 1 from culinaria_ordercustomer where order_no='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		$check_order_number_oc_log_culinaria=mysql_query("select 1 from culinaria_ordercustomer_log where order_no='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		
		if(mysql_num_rows($check_order_number_oc)>0 || mysql_num_rows($check_order_number_doku)>0 || mysql_num_rows($check_order_number_oc_log)>0  || 
			mysql_num_rows($check_order_number_oc_culinaria) > 0 || mysql_num_rows( $check_order_number_oc_log_culinaria) > 0 )
			shoppingcart::get_new_order_number($mode);
		else{
			return $rs_order_number_["order_number"];
		}	
	}
	
	public static function rename_aset_database_sticker( $order_no, $order_no_baru ){
        // header
        $sql = "update desain_header set order_no = '". main::formatting_query_string($order_no_baru) ."' where order_no = '". main::formatting_query_string($order_no) ."'";
        mysql_query($sql);
    
        // detail
        $sql = "update desain_detail set order_no = '". main::formatting_query_string($order_no_baru) ."', file_desain = replace(file_desain, '". main::formatting_query_string($order_no) ."', '". main::formatting_query_string($order_no_baru) ."') 
            where order_no = '". main::formatting_query_string($order_no) ."'";
        mysql_query($sql);
    
        // file
        $arr_prefiks_file = array("ori_", "stick_", "thumb_stick_");
	    foreach( $arr_prefiks_file as $prefiks_file ){
	        $arr_file = glob( __DIR__ . "/../upload/" . $prefiks_file . $order_no . "*" );
	        foreach( $arr_file as $file )
	            rename( $file, str_replace( $order_no, $order_no_baru, $file ) );
	    }
	}
	
	public static function set_order_no($memberemail, $order_no, $mode="ONLINE" /* ONLINE || SHOWROOM */){ // khusus untuk order dengan status=0
		$sql="select 1 from doku where transidmerchant='".$order_no."';";
		$rs_trxid=mysql_query($sql) or die();//die("get_order_number 2 query error.<br />".mysql_error());
		if(mysql_num_rows($rs_trxid)>0){ 				
		    $order_no_lama = $order_no;
			$order_no=shoppingcart::get_new_order_number($mode);

			// cek apa ada item stiker, klo ada : jalankan prosedur utk rename semua aset stiker ke order_no yang baru
		   $rs_list_orderproduct = self::get_product($memberemail, $order_no_lama); 
		   while( $list_orderproduct = mysql_fetch_array($rs_list_orderproduct) )
		        $arr_list_orderproduct[ $list_orderproduct["product_id"] ] = $list_orderproduct;
		   if( main::cek_item_stiker( $arr_list_orderproduct ) )
		       self::rename_aset_database_sticker($order_no_lama, $order_no);
			
			// blm dibayar lunas, walopun ada di tabel doku	-> tabel ordercustomer akan mengupdate, sedangkan tabel doku akan menambah record baru, KHUSUS UTK BLM LUNAS AKAN DIBUATKAN MEKANISME UTK AUTO UPDATE ASSET STIKER.
			$asql[0]="update ordercustomer a, membersdata b, doku c set a.order_no='$order_no' where 
					a.memberid=b.memberid and b.email='".main::formatting_query_string($memberemail)."'
					and a.order_no=c.transidmerchant
					and a.order_status=0 and c.response_code<>'".$GLOBALS["payment_status_code_doku_ok"]."' 
					#order_source#;";
			// sudah dibayar lunas, sehingga ordercustomer.order_status diupdate=0
			$asql[1]="update ordercustomer a, membersdata b, doku c set a.order_status='2' where 
					a.memberid=b.memberid and b.email='".main::formatting_query_string($memberemail)."'
					and a.order_no=c.transidmerchant
					and a.order_status=0 and c.response_code='".$GLOBALS["payment_status_code_doku_ok"]."'
					#order_source#;";			
			foreach($asql as $sql){
				if($mode!="ONLINE") 	$sql=str_replace("#order_source#", "and a.order_no like '".@$_SESSION["showroom_inisial"]."%'", $sql);
				else					$sql=str_replace("#order_source#", "and a.order_no REGEXP '^[0-9]'", $sql);
				mysql_query($sql) or die();			
			}
		}return $order_no;
	}
	
	public static function get_order_number($memberemail, $status, $mode="ONLINE" /* ONLINE || SHOWROOM */){		
		$sql="select order_id, order_no from ordercustomer where memberid=
				(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') 
				and order_status=".main::formatting_query_string($status);
		if($mode!="ONLINE") $sql.=" and order_no like '".@$_SESSION["showroom_inisial"]."%'";
		else 				$sql.=" and order_no REGEXP '^[0-9]'";
		$rs_order_no=mysql_query($sql) or die();//die("get_order_number query error.<br />".mysql_error());
		if($status==0){
			$rs_order_no_=mysql_fetch_array($rs_order_no);
			if($rs_order_no_["order_no"]=="")	$order_no=shoppingcart::get_new_order_number($mode);
			else 								$order_no=$rs_order_no_["order_no"];
			$return=shoppingcart::set_order_no($memberemail, $order_no, $mode);
		}else{
			$return=array();
			while($rs_order_no_=mysql_fetch_array($rs_order_no))$return[$rs_order_no_["order_id"]]=$rs_order_no_["order_no"];
		}return $return;
	}
	
	public static function check_previous_order(){
		$sql="select b.order_no, b.order_date, b.order_id from orderproduct a, ordercustomer b, membersdata c, doku d where
			a.order_id=b.order_id and b.memberid=c.memberid and b.order_no=d.transidmerchant and (b.order_status>=1 or d.response_code='".$GLOBALS["payment_status_code_doku_ok"]."')
			and b.memberid=(select memberid from membersdata where email='".main::formatting_query_string(@$_SESSION["email"])."') 
			and a.product_id in (".main::formatting_query_string(implode(",", array_keys($_SESSION["shopping_cart"]))).") 
			and not exists(select 1 from orderproduct where order_id=(select order_id from ordercustomer where order_no='".main::formatting_query_string(@$_SESSION["order_no"])."'))
			order by order_date desc limit 1;";
		$rs_check=mysql_query($sql) or die();
		if(mysql_num_rows($rs_check)>0 && mysql_num_rows($rs_check)>0==@$_SESSION["shopping_cart"])
			return array(1=>true, mysql_fetch_array($rs_check));
		else return array(1=>false);
	}

	public static function __insert_order($memberemail, $mode="ONLINE" /* ONLINE || SHOWROOM */){
		// cari order dengan order_status=0, belum dibayar
		if($mode!="ONLINE")	$order_number=shoppingcart::get_order_number($memberemail, 0, @$_SESSION["showroom_inisial"]);
		else				$order_number=shoppingcart::get_order_number($memberemail, 0);
		//$order_number=$existing_order!=""?$existing_order:shoppingcart::get_new_order_number();
		$sql="insert into ordercustomer(order_no, memberid) select '".main::formatting_query_string($order_number)."', memberid from membersdata a 
			where email='".main::formatting_query_string($memberemail)."' and 
			not exists(select 1 from ordercustomer where memberid=a.memberid and order_status=0 #order_source#);";
		if($mode!="ONLINE") 	$sql=str_replace("#order_source#", "and order_no like '".@$_SESSION["showroom_inisial"]."%'", $sql);
		else					$sql=str_replace("#order_source#", "and order_no REGEXP '^[0-9]'", $sql);
		mysql_query($sql) or die();//("insert order error.<br />".mysql_error());	
		self::__insert_product($memberemail, $_SESSION["shopping_cart"], $GLOBALS["diskon"], $mode);
		return $order_number;
	}
	
	public static function __delete_order($order_number){/* hanya menghapus order yang belum dibayar, klo sudah dibayar very forbidden to delete */
		$sql="delete from ordercustomer where order_no='".main::formatting_query_string($order_number)."' and order_status=0;";
		mysql_query($sql) or die();//("__delete_order error.<br />".mysql_error());
	}
	
	public static function get_remark_coupon_code($code){
		$sql="select remark_".$GLOBALS["lang"]." remark from discount_coupon where coupon_code='".main::formatting_query_string($code)."';";
		$rs=mysql_query($sql) or die();//("get_remark_coupon_code error.<br />".mysql_error());
		$data=mysql_fetch_array($rs);
		return $data["remark"];	
	}
	
	public function __update_order($memberemail, $order_number, $billingid, $shippingid, $arr_par){
		$sql="update ordercustomer a inner join membersdata b on a.memberid=b.memberid 
			left outer join shipment_state c on b.homestate=c.state_id 
			left outer join shipment_exception h on b.homeregion=h.region_id
			inner join membersbilling d on b.memberid=d.memberid
			left outer join shipment_state e on d.billing_state=e.state_id
			inner join membersshipping f on b.memberid=f.memberid
			left outer join shipment_state g on f.shipping_state=g.state_id 
			left outer join shipment_exception i on f.shipping_region=i.region_id
			set ";
		$coupon_code_remark=shoppingcart::get_remark_coupon_code($this->coupon_code);
		if(isset($this->additional_discount_code))$sql.="additional_discount_code=".$this->additional_discount_code.",";
		if(isset($this->additional_discount))$sql.="additional_discount=".$this->additional_discount.",";
		if(isset($this->coupon_code))$sql.="coupon_code=concat(".$this->coupon_code.", '".($coupon_code_remark!=""?" - ".$coupon_code_remark:"")."'),";
		if(isset($this->coupon_discount))$sql.="coupon_discount=".$this->coupon_discount.",";
		if(isset($this->shippingcost))$sql.="shippingcost=".$this->shippingcost.",";
		if(isset($this->shipping_date))$sql.="shipping_date=".$this->shipping_date.",";
		if(isset($this->installation_option))$sql.="shipping_installation_option=".($this->installation_option!=""?"true":"false").",";
		if(isset($this->shipping_note))$sql.="shipping_note=".$this->shipping_note.",";
		if(isset($this->ipg_promo)){$sql.="ipg_promo=".$this->ipg_promo.",";}
		else{$sql.="ipg_promo=0,";}
		if(isset($this->ipg_promo_discount))$sql.="ipg_promo_discount=".$this->ipg_promo_discount.",";
		foreach($arr_par as $value=>$ref_table){
			if($ref_table!=""){
				$this->parameter="\$this->".$value;
				eval("\$this->parameter=\"$this->parameter\";");
				if(isset($this->parameter)&&$this->parameter!="")$sql.="a.".$value."=".main::formatting_query_string($this->parameter).",";
			}
		}$sql=substr($sql,0,strlen($sql)-1);
		$sql.=" where 
			a.memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') 
			and a.order_no='".main::formatting_query_string($order_number)."' 
			and d.billingid='".main::formatting_query_string($billingid)."' 
			and f.shippingid='".main::formatting_query_string($shippingid)."' ";
		if(isset($this->order_status))$sql.=" and a.order_status='".main::formatting_query_string($this->order_status)."' ";
		mysql_query($sql) or die();//("__update_order error.<br />".mysql_error());
	}
	
	public static function __delete_all_product($memberemail, $status, $mode="ONLINE" /* ONLINE || SHOWROOM */){
		$sql="delete from orderproduct where order_id=
			(select order_id from ordercustomer where memberid=
				(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') 
				and order_status=".main::formatting_query_string($status)." 
				#order_source#
			)";
		if($mode!="ONLINE") $sql=str_replace("#order_source#", "and order_no like '".@$_SESSION["showroom_inisial"]."%'", $sql);
		else				$sql=str_replace("#order_source#", "and order_no REGEXP '^[0-9]'", $sql);
		mysql_query($sql) or die();//("__delete_all_product error.<br />".mysql_error());
	}
		
	private static function is_product_exists($memberemail, $productid, $status, $mode="ONLINE" /* ONLINE || SHOWROOM */){/* cek produk di orderproduct, dalam 1 order tidak boleh ada 2 product_id di tabel orderproduct */
		$sql="select 1 from orderproduct a, product b where a.product_id=b.productid and a.order_id=
			(select order_id from ordercustomer where memberid=
				(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') 
				and order_status=".main::formatting_query_string($status)."
				#order_source#
			) 
			and a.product_id=".main::formatting_query_string($productid);
		if($mode!="ONLINE") $sql=str_replace("#order_source#", "and order_no like '".@$_SESSION["showroom_inisial"]."%'", $sql);
		else				$sql=str_replace("#order_source#", "and order_no REGEXP '^[0-9]'", $sql);
		$rs_check=mysql_query($sql) or die();//("is_product_exists query error.<br />".mysql_error());
		if(mysql_num_rows($rs_check)>0)return true;
		else return false;
	}
	
	public static function is_valid_product_id($productid){
		$sql="select 1 from product where productid='".main::formatting_query_string($productid)."' and penable='Y';";
		$rs_check=mysql_query($sql) or die();//("is_valid_product_id query error.<br />".mysql_error());
		if(mysql_num_rows($rs_check)>0)return true;
		else return false;		
	}
	
	private static function is_free_gift($productid){
		$sql="select a.productid from promo_per_item a, promo_per_item_related b  where a.promo_per_item_id=b.promo_per_item_id 
			and b.diskon=1 and current_date >= a.datestart and current_date <= a.dateend 
			and b.productid='".main::formatting_query_string($productid)."';";
		$rs=mysql_query($sql) or die();//("is_free_gift query eror.<br />".mysql_error());			
		while($data=mysql_fetch_array($rs)){
			if(array_key_exists($data["productid"], @$_SESSION["shopping_cart"]))return true;
		}return false;
	}
	
	public static function is_preorder_item($productid){
		$sql="select 1 from product  where productid='".main::formatting_query_string($productid)."' and isnewproduct='1' and penable='Y';";
		$rs=mysql_query($sql) or die();//("is_free_gift query eror.<br />".mysql_error());			
		if(mysql_num_rows($rs)>0)	return true;
		else 						return false;
	}
	
	public static function arr_product_available_quantity( $arr_productid, $branch_state="", $branch_region="" ){
		// default isi return array, semua productid dikasih nilai stok = -1
			foreach( $arr_productid as $productid => $quantity ){
				if(shoppingcart::is_free_gift($productid) || shoppingcart::is_preorder_item($productid))
					$arr_productid_stok[ $productid ] = $quantity;//$GLOBALS["shoppingcart_buffer_stock"];
				else
					$arr_productid_stok[ $productid ] = -1;
			}

			// klo num func arg = 2, maka harus dicari dulu propinsi dan kota lokasi member shipping ybs
			if(func_num_args()<=2){ //$branch_state juga berfungsi sebagai member shipping id
				$sql = "select shipping_region, shipping_state from membersshipping where shippingid = ". main::formatting_query_string($branch_state) ."";
				$rs = mysql_query($sql) or die();
				if( mysql_num_rows($rs) > 0 ){
					$data = mysql_fetch_array($rs);
					$propinsi = $data["shipping_state"];
					$kota = $data["shipping_region"];
				}else return -1;
			}else{
				$propinsi = $branch_state;
				$kota = $branch_region;
			}

			
			foreach( $arr_productid as $productid => $quantity ){								
				// dapatkan kode produk accpac
				$sql = "select kode from __kode_produk_accpac where productid = '". main::formatting_query_string($productid) ."' and kode != ''";
				$rs = mysql_query($sql) or die();
				if( mysql_num_rows($rs) > 0 ){
					$data = mysql_fetch_array($rs);
					$kode_product_accpac = $data["kode"];
				}else $kode_product_accpac = -1;
				
				$arr_productid_kode_accpac[ $productid ] = trim( $kode_product_accpac );
			
			}
			
			// buat array parameter 
			$arr_par = array(
				"c" 		=> "sl",
				"sc" 	=> "csa", 
				"rand"	=> $GLOBALS["random"],
				"auth"   => sha1($GLOBALS["ftp_address"].$GLOBALS["random"].$GLOBALS["ftp_username"]),
				
				"i"		=> $arr_productid_kode_accpac,
				"p"		=> $propinsi, 
				"k"		=> $kota
			);

			$server_output = 0;
			// buat sambungan ke satrio
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $GLOBALS["auto_stock_url"]);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $GLOBALS["curl_connection_timeout"]); 
			curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS["curl_timeout"]); 

			$server_output = curl_exec ($ch);
			$server_output = json_decode($server_output, true);

			$curl_error = curl_errno($ch);
			/*if($curl_error != 0)
				main::send_email(SUPPORT_EMAIL, 
					"[modena.co.id] :: Error penarikan data stok  real-time untuk item kode ". $productid ." (propinsi : ". $propinsi .", kota : ". $kota .") dari ACCPAC", 
					"Mas bro, 
					tolong segera dicek ada error <strong>penarikan data stok real-time untuk item kode ". $productid ." (propinsi : ". $propinsi .", kota : ". $kota .") dari ACCPAC</strong>.<br />
					Lokasi di halaman shopping cart<br />
					Kode errornya adalah : " . $curl_error);*/
			
			curl_close ($ch);
			
			// cari kuantitas item terbooking, walaupun belum dibayar namun dengan rentang waktu kurang dari 5 jam.. sebagai pengurang data stok accpac yang masih diproses di CBN
			// diberikan waktu 5 jam dari mulai proses di doku agar tetap diperhitungkan sebagai booking di CBN
			// sedangkan dari doku ngasih waktu 4 jam
			// basis waktu diganti menjadi menit, dengam ambil data dari global variabel + 15 menit utk ekstensi
			if(@$_SESSION["email"] != "")
				$sqladd = "and 
					memberid not in (select memberid from membersdata where email='". main::formatting_query_string($_SESSION["email"]) ."')";
				
			$quantity_booking_order_total = array();
			if( is_array($server_output) && count( $server_output ) > 0 ){
				$sql = "
					select sum(c.kuantitas_order_item_sedang_proses) kuantitas_order_item_sedang_proses, c.product_id from 
					ordercustomer a, 
						(select sum(quantity) kuantitas_order_item_sedang_proses, order_id, product_id from orderproduct group by order_id, product_id) c 
					where 
					a.order_id = c.order_id and
					TIMESTAMPDIFF(MINUTE, a.order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+5  and 
					order_status < 1 and c.product_id in (". implode( ",", array_keys($server_output) ) .")
					". @$sqladd ." group by c.product_id
				"; 
				$rs = mysql_query($sql) or die();
				if( mysql_num_rows($rs) > 0 ){
					
					while( $data = mysql_fetch_array($rs) )
						$quantity_booking_order_total[ $data["product_id"] ] = $data["kuantitas_order_item_sedang_proses"];
				
				}
			}
			
			if( is_array($server_output) && count( $server_output ) > 0 ){
				foreach( $server_output as $product_id => $stok ){
					// hanya utk produk yg bukan free gift / pre order item di mekanisme di atas
					if( $arr_productid_stok[ $product_id ] <= -1 ){
						$arr_productid_stok[ $product_id ] = $stok - @$quantity_booking_order_total[ $product_id ] - $arr_productid[ $product_id ];
					}
				}
			}
			
			// cek utk item stiker
			foreach( $arr_productid_stok as $product_id => $stok ){
			    foreach( $GLOBALS["product_custom"] as $custom_product_id => $arr_custom_product_id ){
			        if( $product_id == $arr_custom_product_id["stickerid"] ){
			            $arr_productid_stok[ $product_id ] = $arr_productid_stok[$custom_product_id];
			            break;
			        }  
			    }
			    
			}

			return $arr_productid_stok;
			//return (int)$server_output - $quantity_booking_order_total - $quantity;
			
	}
	
	
	public static function product_available_quantity($productid, $quantity, $branch_state="", $branch_region=""){//return 0;
		if(shoppingcart::is_free_gift($productid) || shoppingcart::is_preorder_item($productid))		return $quantity;//$GLOBALS["shoppingcart_buffer_stock"];

		// terapkan mekanisme baru untuk dapatkan stok secara realtime		
		
		// klo num func arg = 3, maka harus dicari dulu propinsi dan kota lokasi member shipping ybs
		if(func_num_args()<=3){ //$branch_state juga berfungsi sebagai member shipping id
			$sql = "select shipping_region, shipping_state from membersshipping where shippingid = '". main::formatting_query_string($branch_state) ."'";
			$rs = mysql_query($sql) or die();
			if( mysql_num_rows($rs) > 0 ){
				$data = mysql_fetch_array($rs);
				$propinsi = $data["shipping_state"];
				$kota = $data["shipping_region"];
			}else return -1;
		}else{
			$propinsi = $branch_state;
			$kota = $branch_region;
		}
		
		// dapatkan kode produk accpac
		$sql = "select kode from __kode_produk_accpac where productid = '". main::formatting_query_string($productid) ."'";
		$rs = mysql_query($sql) or die();
		if( mysql_num_rows($rs) > 0 ){
			$data = mysql_fetch_array($rs);
			$kode_product_accpac = $data["kode"];
		}else return -1;
		
		// buat array parameter 
		$arr_par = array(
			"c" 		=> "sl",
			"sc" 	=> "cs", 
			"rand"	=> $GLOBALS["random"],
			"auth"   => sha1($GLOBALS["ftp_address"].$GLOBALS["random"].$GLOBALS["ftp_username"]),
			
			"i"		=> $kode_product_accpac,
			"p"		=> $propinsi, 
			"k"		=> $kota
		);
		
		$server_output = 0;
		// buat sambungan ke satrio
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $GLOBALS["auto_stock_url"]);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $GLOBALS["curl_connection_timeout"]); 
		curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS["curl_timeout"]); 
		
		$server_output = curl_exec ($ch);

		$curl_error = curl_errno($ch);
		/*if($curl_error != 0)
			main::send_email(SUPPORT_EMAIL, 
				"[modena.co.id] :: Error penarikan data stok  real-time untuk item kode ". $productid ." (propinsi : ". $propinsi .", kota : ". $kota .") dari ACCPAC", 
				"Mas bro, 
				tolong segera dicek ada error <strong>penarikan data stok real-time untuk item kode ". $productid ." (propinsi : ". $propinsi .", kota : ". $kota .") dari ACCPAC</strong>.<br />
				Lokasi di halaman shopping cart<br />
				Kode errornya adalah : " . $curl_error);*/
		
		curl_close ($ch);
		
		// cari kuantitas item terbooking, walaupun belum dibayar namun dengan rentang waktu kurang dari 5 jam.. sebagai pengurang data stok accpac yang masih diproses di CBN
		// diberikan waktu 5 jam dari mulai proses di doku agar tetap diperhitungkan sebagai booking di CBN
		// sedangkan dari doku ngasih waktu 4 jam
		// basis waktu diganti menjadi menit, dengam ambil data dari global variabel + 15 menit utk ekstensi
		if(@$_SESSION["email"] != "")
			$sqladd = "and 
				memberid not in (select memberid from membersdata where email='". main::formatting_query_string($_SESSION["email"]) ."')";
		$sql = "
			select sum(c.kuantitas_order_item_sedang_proses) kuantitas_order_item_sedang_proses from 
			ordercustomer a, 
				(select sum(quantity) kuantitas_order_item_sedang_proses, order_id, product_id from orderproduct group by order_id, product_id) c 
			where 
			a.order_id = c.order_id and
			TIMESTAMPDIFF(MINUTE, a.order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+5  and  
			order_status < 1 and c.product_id = '". main::formatting_query_string($productid) ."' 
			". @$sqladd ."
		"; 
		$rs = mysql_query($sql) or die();
		if( mysql_num_rows($rs) > 0 ){
			$data = mysql_fetch_array($rs);
			$quantity_booking_order_total = $data["kuantitas_order_item_sedang_proses"];
		}else $quantity_booking_order_total = 0;

		return (int)$server_output - $quantity_booking_order_total - $quantity;
		
		// ###################################################################################################################
		
		shoppingcart::check_accpac_stock($productid, $branch_state, $branch_region);
		if(func_num_args()<=3)//$branch_state juga berfungsi sebagai member shipping id
		$sql="select a.quantity-ifnull(c.product_purchased_quantity, 0)-".main::formatting_query_string($quantity)." available_quantity from productinventory a inner join
			(select max(date) inventory_date, productid, state_id, region_id from productinventory where type=1 group by productid, state_id, region_id)  b 
				on a.productid=b.productid and a.date=b.inventory_date and a.state_id=b.state_id and a.region_id=b.region_id left outer join
			(select sum(a.quantity) product_purchased_quantity, a.productid from productinventory a where a.type=0  
				and a.date>=(select max(date) from productinventory where type=1 and productid=a.productid) group by a.productid) c on a.productid=c.productid left outer join
				branch_service d on a.state_id=d.branch_state_id and a.region_id=d.branch_region_id left outer join
				membersshipping e on d.service_state_id=e.shipping_state and d.service_region_id=e.shipping_region 
			where a.type=1 and a.productid='".main::formatting_query_string($productid)."' and e.shippingid='".$branch_state."';";
		else
		$sql="select a.quantity-ifnull(c.product_purchased_quantity, 0)-".main::formatting_query_string($quantity)." available_quantity from productinventory a inner join
			(select max(date) inventory_date, productid, state_id, region_id from productinventory where type=1 group by productid, state_id, region_id)  b 
				on a.productid=b.productid and a.date=b.inventory_date and a.state_id=b.state_id and a.region_id=b.region_id left outer join
			(select sum(a.quantity) product_purchased_quantity, a.productid from productinventory a where a.type=0  
				and a.date>=(select max(date) from productinventory where type=1 and productid=a.productid) group by a.productid) c on a.productid=c.productid left outer join
				branch_service d on a.state_id=d.branch_state_id and a.region_id=d.branch_region_id 
			where a.type=1 and a.productid='".main::formatting_query_string($productid)."' and d.service_state_id='".$branch_state."' and d.service_region_id='".$branch_region."';";
		$rs_check=mysql_query($sql) or die();//("product_available_quantity query error.<br />".mysql_error());
		$rs_check_=mysql_fetch_array($rs_check);		
		return ($rs_check_["available_quantity"]!=""?$rs_check_["available_quantity"]:-1);
	}
	
	private static function check_accpac_stock($productid, $branch_state, $branch_region=""){
		if($branch_region=="")//$branch_state juga berfungsi sebagai member shipping id
		$sql="select 1 from productinventory a, product_inventory_ b, branch_service c, membersshipping d 
			where a.productid=b.productid and a.state_id=b.state_id and a.region_id=b.region_id and a.audtdate=b.audtdate and a.audttime=b.audttime and 
			b.state_id=c.branch_state_id and b.region_id=c.branch_region_id and c.service_state_id=d.shipping_state and c.service_region_id=d.shipping_region and 
			a.productid='".$productid."' and d.shippingid='".$branch_state."';";		
		else
		$sql="select 1 from productinventory a, product_inventory_ b, branch_service c 
			where a.productid=b.productid and a.state_id=b.state_id and a.region_id=b.region_id and a.audtdate=b.audtdate and a.audttime=b.audttime and 
			b.state_id=c.branch_state_id and b.region_id=c.branch_region_id and 
			a.productid='".$productid."' and c.service_state_id='".$branch_state."' and c.service_region_id='".$branch_region."';";
		$rs=mysql_query($sql) or die();//("check_accpac_stock query error.<br />".mysql_error());
		if(mysql_num_rows($rs)<=0){//import data baru dari accpac
			if($branch_region=="")//$branch_state juga berfungsi sebagai member shipping id
			$sql="insert into productinventory(productid, quantity, type, state_id, region_id, audtdate, audttime) 
				select productid, quantity, '1', state_id, region_id, audtdate, audttime from product_inventory_ a, branch_service b, membersshipping c  
				where a.state_id=b.branch_state_id and a.region_id=b.branch_region_id and
				b.service_state_id=c.shipping_state and b.service_region_id=c.shipping_region and productid='".$productid."' and c.shippingid='".$branch_state."';";			
			else
			$sql="insert into productinventory(productid, quantity, type, state_id, region_id, audtdate, audttime) 
				select productid, quantity, '1', state_id, region_id, audtdate, audttime from product_inventory_ a, branch_service b
				where a.state_id=b.branch_state_id and a.region_id=b.branch_region_id and 
				productid='".$productid."' and b.service_state_id='".$branch_state."' and b.service_region_id='".$branch_region."';";
			mysql_query($sql) or die();//("import accpac stock error.<br />".mysql_error());
		}
	}
	
	public static function __insert_product($memberemail, $arr_product, $discount, $mode="ONLINE" /* ONLINE || SHOWROOM */){
		foreach($arr_product as $productid=>$quantity){
			if($productid=="")continue;
			$product_discount=0;
			if(@$GLOBALS["productid"]==$productid)$product_discount=@$GLOBALS["product_discount"]!=""?$GLOBALS["product_discount"]:0;
			if(@$GLOBALS["promo_per_item_id"]!=""){
				$col_promo_per_item_id=",promo_per_item_id";
				$val_promo_per_item_id="'".main::formatting_query_string($GLOBALS["promo_per_item_id"])."'";
			}
			if(!shoppingcart::is_product_exists($memberemail, $productid, 0, $mode)){
				$sql="insert into orderproduct(order_id, product_id, quantity, product_price, product_promo, product_pricepromo, discount, tradein ".@$col_promo_per_item_id.") 
					select order_id, '".main::formatting_query_string($productid)."', '".main::formatting_query_string($quantity)."', 
					(select price from product where productid='".main::formatting_query_string($productid)."'), '".$product_discount."', 
					(select price from product where productid='".main::formatting_query_string($productid)."')-'".$product_discount."', '".main::formatting_query_string($discount)."',
					'".(@in_array($productid, @$_SESSION["tradein"])?1:0)."'".
					(isset($val_promo_per_item_id)?",'".$val_promo_per_item_id."'":"")." from 
					ordercustomer where memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') and order_status=0 
					#order_source#";
			}else{
				$sql="update orderproduct a, ordercustomer b 
					set a.quantity=/*a.quantity+*/'".main::formatting_query_string($quantity)."', 
					product_price=(select price from product where productid='".main::formatting_query_string($productid)."'),
					product_promo='".$product_discount."',
					product_pricepromo=(select price from product where productid='".main::formatting_query_string($productid)."')-'".$product_discount."', 
					discount='".main::formatting_query_string($discount)."',
					tradein='".(@in_array($productid, @$_SESSION["tradein"])?1:0)."' ".
					(isset($col_promo_per_item_id)?$col_promo_per_item_id."=".$val_promo_per_item_id."":"")."
					where a.order_id=b.order_id and a.product_id='".main::formatting_query_string($productid)."' 
					and b.memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') and b.order_status=0
					#order_source#";
			}
			if($mode!="ONLINE") $sql=str_replace("#order_source#", "and order_no like '".@$_SESSION["showroom_inisial"]."%'", $sql);
			else				$sql=str_replace("#order_source#", "and order_no REGEXP '^[0-9]'", $sql);
			
			mysql_query($sql) or die();//("insert order product error.<br />".mysql_error());
			
			$s_update_order_date = "update ordercustomer set order_date = CURRENT_TIMESTAMP where 
											order_status=0 and 
											memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."')";
			mysql_query($s_update_order_date);
		}
	}
	
	public static function get_product($memberemail, $order_no, $mode="ONLINE" /* ONLINE || SHOWROOM */){
		$sql="select a.product_id, a.quantity from orderproduct a, product b where a.product_id=b.productid and a.order_id=
			(select order_id from ordercustomer where memberid=
				(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') #order_no#)";
		if($order_no!="")$sql=str_replace("#order_no#", "and order_id=(select order_id from ordercustomer where order_no='".main::formatting_query_string($order_no)."' #order_source#) ", $sql);
		else $sql=str_replace("#order_no#", "and order_status=0 #order_source# order by order_date desc limit 1", $sql);
		if($mode!="ONLINE")	$sql=str_replace("#order_source#", " and order_no like '".@$_SESSION["showroom_inisial"]."%'", $sql);
		else 				$sql=str_replace("#order_source#", "and order_no REGEXP '^[0-9]'", $sql);
		$rs_product=mysql_query($sql) or die();//("get_product query error.<br />".mysql_error());
		return $rs_product;
	}
	
	private static function is_product_exists_inventory($productid, $order_number){/* cek apakah produk sudah ada di inventory dengan order number tertentu */
		$sql="select 1 from productinventory where productid='".main::formatting_query_string($productid)."' and order_no='".main::formatting_query_string($order_number)."';";
		$rs_is_product_exists_inventory=mysql_query($sql) or die();//("is_product_exists_inventory query error.<br />".mysql_error());
		if(mysql_num_rows($rs_is_product_exists_inventory)>0)return true;
		else return false;
	}
	
	/* hapus semua produk di inventory dengan order number tertentu dan ordercustomer.order_status=0, 
	fungsinya utk reset seketika jumlah pembelian produk jika dalam proses pembelian terjadi kegagalan pembayaran (tidak perlu menunggu waktu reserved 1 jam) */
	public static function __delete_order_product_inventory($order_number){
		$sql="delete from productinventory where order_no='".main::formatting_query_string($order_number)."' and type=0 
			and order_no in (select order_no from ordercustomer where order_status=0) and order_no REGEXP '^[0-9]';";
		mysql_query($sql) or die();//("__delete_order_product_inventory error.<br />".mysql_error());
	}
	
	public static function __insert_product_inventory($arr_productid /* $_SESSION["shopping_cart"] */, $order_number){
		shoppingcart::__delete_order_product_inventory($order_number);
		foreach($arr_productid as $product_id=>$quantity){
/*			if(shoppingcart::is_product_exists_inventory($product_id, $order_number))
				$sql="update productinventory set quantity='".$quantity."' where productid='".$product_id."' and order_no='".$order_number."' and type=0;";
			else*/
			$sql="insert into productinventory(productid, quantity, type, order_no) 
				values('".main::formatting_query_string($product_id)."', '".main::formatting_query_string($quantity)."', '0', '".main::formatting_query_string($order_number)."');";
			mysql_query($sql) or die();//("__insert_product_inventory.<br />".mysql_error());
		}
	}
	
	public static function set_availability_dan_shipping_info($productid, $propinsi, $kota, $kuantitas, $total_harga, $par_kuantitas_tersedia=""){
		// cari kuantitas tersedia
		// kuantitas_tersedia merupakan kuantitas yang sudah dikurangi dengan variabel $kuantitas, so harus dikembalikan ke kuantitas semula
		if( $par_kuantitas_tersedia == "" )
			$kuantitas_tersedia = shoppingcart::product_available_quantity( $productid, $kuantitas, $propinsi, $kota );
		else
			$kuantitas_tersedia = $par_kuantitas_tersedia;
			
		$kuantitas_tersedia += $kuantitas;

		// filter kuantitas dibandingkan dengan kuantitas tersedia dan set keterangan
		$string_keterangan_stok_tidakcukup = "";
		if( $kuantitas_tersedia < $GLOBALS["shoppingcart_buffer_stock"] || $kuantitas_tersedia <= 0 ){
			$kuantitas = 0;
			//$_SESSION["shopping_cart"][$productid] = $kuantitas;
			if(@$_SESSION["shipping_state"] != "" && @$_SESSION["shipping_region"] != "")
				$string_keterangan_stok_tidakcukup = $GLOBALS["lang_data_empty_stock"];	
		}else
			$kuantitas =  $kuantitas > $kuantitas_tersedia ? $kuantitas_tersedia : $kuantitas;
		 
		 // shipping cost
		$product_weight=shoppingcart::get_total_product_weight(array($productid=>$kuantitas), $GLOBALS["lang"]);
		$shipping_cost=shoppingcart::get_shipping_cost($total_harga, $product_weight, $propinsi, $kota);	
		
		return array
		(
			"kuantitas" => $kuantitas,
			"kuantitas_tersedia" => $kuantitas_tersedia,
			"string_keterangan_stok_tidakcukup"	=> $string_keterangan_stok_tidakcukup,
			"shipping_cost"							=> $shipping_cost
		);		
	}
	
	public static function shipment_date_off(){
		$sql = "select *, DATE_FORMAT(date,'%c-%e-%Y') formatted_date
			from shipment_date_off where year(date) >= year(now()) and year(date) <=year(now()) + 1 and enabled = '1' ";
		$rs = mysql_query($sql) or die();
		return $rs;
	}

/* #################################### DOKU ################################################################ */	
	public function doku_data($arr_par){
		$sql="select ";
		foreach($arr_par as $value=>$mode)$sql.=$value.",";
		$sql=substr($sql,0,strlen($sql)-1)." from doku ";
		if(isset($this->transidmerchant))$sql.="where transidmerchant='".main::formatting_query_string($this->transidmerchant)."';";
		$rs_doku_data=mysql_query($sql) or die();//("doku_data query error.<br />".mysql_error());
		return $rs_doku_data;
	}

	public function __insert_doku_data($transidmerchant, $arr_par){
		$doku_data=new shoppingcart;
		$doku_data->transidmerchant=$transidmerchant;
		$rs_doku_data=$doku_data->doku_data($arr_par);
		if(mysql_num_rows($rs_doku_data)<=0){
			$trxstatus=$this->trxstatus;
			$totalamount=$this->totalamount;
			$sql="insert into doku(trxstatus, totalamount, transidmerchant) 
				values(".$trxstatus.", ".$totalamount.", '".main::formatting_query_string($transidmerchant)."');";
		}else{
			$sql="update doku set ";
			foreach($arr_par as $value=>$ref_table){
				$this->parameter="\$this->".$value;
				eval("\$this->parameter=\"$this->parameter\";");
				if(isset($this->parameter)&&$this->parameter!=""&&$this->parameter!="select")$sql.=$value."=".$this->parameter.",";
			}$sql=substr($sql,0, strlen($sql)-1)." where transidmerchant='".main::formatting_query_string($transidmerchant)."';";
		}
		mysql_query($sql) or die();//("__insert_doku_table error.<br />".mysql_error());
	}

	public static function __delete_doku_data($transidmerchant){
		$sql="delete from doku where transidmerchant='".main::formatting_query_string($transidmerchant)."';";
		mysql_query($sql) or die();//("__delete_doku_data error.<br />".mysql_error());
	}
	
}

?>