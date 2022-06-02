<?

class culinaria_cart extends culinaria_program{
	
	static function culinaria_nomor_order_baru(){
		$sql="select concat('',day(now()),month(now()),right(year(now()),2),'',right(rand(),3)) as order_number;";
		$rs_order_number=mysql_query($sql) or die();//("get_new_order_number query error.<br />".mysql_error());
		$rs_order_number_=mysql_fetch_array($rs_order_number);
		
		$check_order_number_oc=mysql_query("select 1 from ordercustomer where order_no='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		$check_order_number_oc_log=mysql_query("select 1 from ordercustomer_log where order_no='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		$check_order_number_doku=mysql_query("select 1 from doku where transidmerchant='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		$check_order_number_oc_culinaria=mysql_query("select 1 from culinaria_ordercustomer where order_no='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		$check_order_number_oc_log_culinaria=mysql_query("select 1 from culinaria_ordercustomer_log where order_no='".main::formatting_query_string($rs_order_number_["order_number"])."';");
		
		if(mysql_num_rows($check_order_number_oc)>0 || mysql_num_rows($check_order_number_doku)>0 || mysql_num_rows($check_order_number_oc_log)>0 || 
			mysql_num_rows($check_order_number_oc_culinaria) > 0 || mysql_num_rows( $check_order_number_oc_log_culinaria) > 0 )
			self::culinaria_nomor_order_baru();
		else{
			return $rs_order_number_["order_number"];
		}	
	}
	
	static function insert_culinaria_cart( $arr_col ){
		$sql = "insert into culinaria_ordercustomer (". implode(",", array_keys( $arr_col )) .") values(". implode(",", array_values( $arr_col )) .");";
		return sql::execute($sql);
	}
	
	static function update_culinaria_cart( $arr_set, $arr_parameter ){
		$sql = "update culinaria_ordercustomer set ". self::sql_parameter( $arr_set, "," ) ." where ". self::sql_parameter( $arr_parameter ) .";";
		return sql::execute($sql);
	}
	
	static function insert_culinaria_cart_item( $arr_col ){
		$sql = "insert into culinaria_orderproduct (". implode(",", array_keys( $arr_col )) .") values(". implode(",", array_values( $arr_col )) .");";
		return sql::execute($sql);
	}
	
	static function update_culinaria_cart_item( $arr_set, $arr_parameter ){
		$sql = "update culinaria_orderproduct set ". self::sql_parameter( $arr_set, "," ) ." where ". self::sql_parameter( $arr_parameter ) .";";
		return sql::execute($sql);
	}
	
	static function delete_culinaria_cart_item( $arr_parameter ){
		$sql = "delete from culinaria_orderproduct where ". self::sql_parameter( $arr_parameter ) .";";
		return sql::execute($sql);
	}

	
	static function browse_culinaria_cart( $arr_parameter, $arr_order = array() ){
		$sql = "select *, date_format(order_date,'%d %M %Y, %H:%i:%S') order_date, order_date order_date_non_formatted from culinaria_ordercustomer ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		$sql .= sql::sql_sort(  $arr_order );
		
		try{		return sql::execute( $sql );	}
		catch(Exception $e){$e->getMessage();}

	}
	
	static function browse_culinaria_cart_jumlah_kursi( $arr_parameter ){
		$sql = "select a.*, b.jumlah_kursi from culinaria_ordercustomer a left outer join 
			( select order_id, product_id, sum(quantity) jumlah_kursi from culinaria_orderproduct group by order_id, product_id ) b on a.order_id = b.order_id ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		try{		return sql::execute( $sql );	}
		catch(Exception $e){$e->getMessage();}

	}
	
	static function browse_culinaria_cart_item( $arr_parameter ){
		$sql = "select  
		    a.product_id, a.quantity, a.discount, a.product_promo, a.product_pricepromo, a.tradein, a.promo_per_item_id, a.note, a.gudang, a.sku, a.nama_produk, a.berat, a.shipment_delay,
		    a.product_price program_harga, (a.product_price*a.quantity) total_amount, b.* 
			from culinaria_orderproduct a left outer join culinaria_ordercustomer b on a.order_id = b.order_id
			left outer join culinaria_program c  on a.product_id = c.program_id ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		try{		return sql::execute( $sql );	}
		catch(Exception $e){$e->getMessage();}
	}
	
	static function sisa_kursi_program( $arr_parameter ){
		$sql = "select 
				ifnull(a.kursi_terpakai, 0) kursi_terpakai,
				c.program_id, c.program_kelas_ukuran,  c.program_kelas_ukuran - ifnull(a.kursi_terpakai, 0) sisa_kursi
			from culinaria_program c left outer join 
			(
				select sum(a.quantity) kursi_terpakai, a.product_id from 
				culinaria_orderproduct a inner join culinaria_ordercustomer b on a.order_id = b.order_id 
				where ( 
					b.order_status > 0 /*order sudah dibayar*/ or (
						b.order_status <= 0 and 
							exists(select 1 from doku where transidmerchant = b.order_no and payment_channel not in ('5', '05') and TIMESTAMPDIFF(minute, starttime, CURRENT_TIMESTAMP) <= 60 )  ) 
							/*order kartu kredit masih dalam proses dikasih waktu maksimal 1 jam*/ or ( 
						b.order_status <= 0 and 
							exists(select 1 from doku where transidmerchant = b.order_no and payment_channel in ('5', '05') and TIMESTAMPDIFF(minute, starttime, CURRENT_TIMESTAMP) <= ". $GLOBALS["payment_va_expiration"] ." ) 
							) /* order menunggu dibayar dengan kartu debit dikasih waktu maksimal sesuai setting di lib/var.php */ 
						)
				group by a.product_id
			) a on c.program_id = a.product_id ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		try{		return sql::execute( $sql );	}
		catch(Exception $e){$e->getMessage();}
	}
	
	private static function discount_coupon($code, $enabled, $not_expired, $subtotal){/* return nilai diskon */	

		$return_discount=0;
		$sql="select coupon_code, discount, /*case unlimited when true then 1 else 0 end*/ unlimited, min_qty, min_sales, image,coupon_mekanisme from discount_coupon where left(coupon_code, 3) = 'CUL' and coupon_code='".main::formatting_query_string($code)."' and enabled='".$enabled."' ";
		if($not_expired)$sql.=" and expired_date>=now();";
		$rs_discount_coupon=mysql_query($sql) or die(mysql_error());//("discount_coupon query error".mysql_error());
		if(mysql_num_rows($rs_discount_coupon)>0){
			$rs_discount_coupon_=mysql_fetch_array($rs_discount_coupon);
			$coupon_mekanisme = "coupon/".$rs_discount_coupon_["coupon_mekanisme"];
			
			if(file_exists($coupon_mekanisme)){					
				include_once $coupon_mekanisme;
				return $return_discount;
			}
			
			//cek limited ato unlimited
			if($rs_discount_coupon_["unlimited"]!="1"){
				//cek apakah kode sudah dpergunakan untuk pembayaran (order_status>0)
				$rs_check_coupon=mysql_query("select 1 from ordercustomer where coupon_code='".$rs_discount_coupon_["coupon_code"]."' and order_status>'0';") or die();//("check discount query error.<br />".mysql_error());
				if(mysql_num_rows($rs_check_coupon)>0)$return_discount=0;
				else $return_discount=$rs_discount_coupon_["discount"];
				
				//cek apakah kode sudah dpergunakan untuk pembayaran (order_status>0) - CULINARIA
				$rs_check_coupon=mysql_query("select 1 from culinaria_ordercustomer where coupon_code='".$rs_discount_coupon_["coupon_code"]."' and order_status>'0';") or die();//("check discount query error.<br />".mysql_error());
				if(mysql_num_rows($rs_check_coupon)>0)$return_discount=0;
				else $return_discount=$rs_discount_coupon_["discount"];
			}else $return_discount=$rs_discount_coupon_["discount"];
			//cek min qty dan min sales value
			$arr_rs=array($rs_discount_coupon_["min_qty"], $rs_discount_coupon_["min_sales"]);
			$arr_val=array(array_sum($_SESSION["culinaria_order"]),@$GLOBALS["discount_subtotal"]);
			for($x=0; $x<count($arr_rs); $x++)
				if($arr_rs[$x]!="" && $arr_rs[$x]>0 && $arr_rs[$x]>$arr_val[$x])	$return_discount=0;
			//convert diskon yg kurang dari 0, berarti diskon promo_all_item
			if($return_discount<0){
				//return $return_discount;
			}				
		}

		return $return_discount;
	}
	
	public static function counting_discount($code, $subtotal){
		$discount_coupon=self::discount_coupon($code, "1", true, $subtotal);
		if($discount_coupon>1)	$discount_price=$discount_coupon;			
		else					$discount_price=$subtotal*$discount_coupon;		
		$discount_grandtotal=$subtotal-$discount_price;
		$return=array($discount_coupon, $discount_price, $discount_grandtotal);
		return $return;
	}
	
	static function entri_data_order( $id, $billingid = "" ){
		unset($arr_parameter);
		$arr_parameter["memberid"] = array("=", "(select memberid from membersdata where email = '". main::formatting_query_string( $_SESSION["email"] ) ."')");
		$arr_parameter["order_status"] = array("=", 0);
		$rs_cek_data_order = self::browse_culinaria_cart( $arr_parameter );

		if( sql::sql_num_rows( $rs_cek_data_order ) <= 0 ){

			$arr_col["order_no"] = "'" . main::formatting_query_string( self::culinaria_nomor_order_baru() ) . "'";
			$arr_col["order_status"] = "0";
			$arr_col["order_date"] = "CURRENT_TIMESTAMP";
			$arr_col["custemail"] = "'" . main::formatting_query_string( $_SESSION["email"] ) . "'";

			self::insert_culinaria_cart( $arr_col );
			
			$arr_col["order_no"] = str_replace("'", "", $arr_col["order_no"]);
						
		} else		
			$arr_col = sql::sql_fetch_array( $rs_cek_data_order );						
		
		$sql_set = "(select #kolom# from 
				membersdata a, shipment_state b, shipment_exception c 
				where a.homestate = b.state_id and a.homestate = c.state_id and a.homeregion = c.region_id and a.email= '". main::formatting_query_string( $_SESSION["email"] ) ."')";
						
		unset( $arr_parameter );
		$arr_parameter["order_no"] = array("=", "'" . main::formatting_query_string( $arr_col["order_no"] ) . "'" );
		$arr_parameter["order_status"] = array("=", 0 );
		$arr_set["memberid"] = array("=", "(select memberid from membersdata where email='". main::formatting_query_string( $_SESSION["email"] ) ."')");
		$arr_set["custname"] = array("=", "(select name from membersdata where email='". main::formatting_query_string( $_SESSION["email"] ) ."')");
		$arr_set["address"] = array("=", "(select address from membersdata where email='". main::formatting_query_string( $_SESSION["email"] ) ."')");
		$arr_set["city"] = array("=", str_replace("#kolom#", "region", $sql_set));
		$arr_set["state"] = array("=", str_replace("#kolom#", "state", $sql_set));
		$arr_set["postcode"] = array("=", "(select postcode from membersdata where email='". main::formatting_query_string( $_SESSION["email"] ) ."')");
		$arr_set["country"] = array("=", "(select country from membersdata where email='". main::formatting_query_string( $_SESSION["email"] ) ."')");
		$arr_set["phone_no"] = array("=", "(select phone from membersdata where email='". main::formatting_query_string( $_SESSION["email"] ) ."')");
		$arr_set["handphone_no"] = array("=", "(select handphone from membersdata where email='". main::formatting_query_string( $_SESSION["email"] ) ."')");

		$arr_billing_col = array(
			"billing_first_name" => $arr_set["custname"][1], 
			"billing_last_name"=>"''", 
			"billing_address" => $arr_set["address"][1], 
			"billing_address_city" => $arr_set["city"][1], 
			"billing_address_state" => $arr_set["state"][1], 
			"billing_address_postcode" => $arr_set["postcode"][1], 
			"billing_address_country" => $arr_set["country"][1], 
			"billing_phone_no" => $arr_set["phone_no"][1], 
			"billing_handphone_no" => $arr_set["handphone_no"][1]
			);
		$arr_billing_membersdata = array(
			"billing_first_name" => "billing_first_name", 
			"billing_last_name"=>"billing_last_name", 
			"billing_address" => "billing_address", 
			"billing_address_city" => "billing_city", 
			"billing_address_state" => "billing_state", 
			"billing_address_postcode" => "billing_postcode", 
			"billing_address_country" => "billing_country", 
			"billing_phone_no" => "billing_phone", 
			"billing_handphone_no" => "billing_handphone"
			);

		if( $billingid != "" ){
			foreach($arr_billing_col as $kolom=>$nilai )
				$arr_billing_col[ $kolom ] = "(select ". $arr_billing_membersdata[$kolom] ." from membersbilling where billingid = '". main::formatting_query_string( $billingid ) ."')";
		}
		
		foreach($arr_billing_col as $kolom=>$nilai )
			$arr_set[ $kolom ] = array("=", $nilai );

		// cek di doku, klo sudah ada di doku berarti harus bikin order no baru karena sudah mencoba utk pembayaran tapi gagal
		$check_order_number_doku=mysql_query("select 1 from doku where transidmerchant='".main::formatting_query_string($arr_col["order_no"])."';");
		if( mysql_num_rows( $check_order_number_doku ) > 0 )	{			
			$arr_col["order_no"] = self::culinaria_nomor_order_baru();
			$arr_set["order_no"] = array("=", "'" . $arr_col["order_no"] . "'");
		}
			
		self::update_culinaria_cart( $arr_set, $arr_parameter );			
		
		unset($arr_parameter);
		$arr_parameter["order_no"] = array("=", $arr_col["order_no"] );
		$rs_cek_data_order = self::browse_culinaria_cart( $arr_parameter );
		
		$data_order = sql::sql_fetch_array( $rs_cek_data_order );
		
		$_SESSION["order_no"] = $data_order["order_no"];
		
		unset( $arr_parameter );
		$arr_parameter["a.order_id"] = array("=", "'" . main::formatting_query_string( $data_order["order_id"] ) . "'" );
		$arr_parameter["a.product_id"] = array("=", "'" . main::formatting_query_string( $id ) . "'" );
		$rs_data_order = culinaria_cart::browse_culinaria_cart_item( $arr_parameter );
		
		if( self::sql_num_rows( $rs_data_order ) <= 0 ){
			unset( $arr_col );
			$arr_col["order_id"] = $data_order["order_id"];
			$arr_col["product_id"] = "'". main::formatting_query_string( $id ) ."'";
			$arr_col["quantity"] = "'". main::formatting_query_string( $_SESSION["culinaria_order"][ $id ] ) ."'";
			self::insert_culinaria_cart_item( $arr_col );
		}
		
		unset( $arr_set, $arr_parameter );
		$arr_set["quantity"] = array("=", "'". main::formatting_query_string( $_SESSION["culinaria_order"][ $id ] ) ."'");
		$arr_set["product_price"] = array("=", "(select program_harga from culinaria_program where program_id = '". main::formatting_query_string( $id ) ."')");
		$arr_parameter["order_id"] = array("=", "'" . main::formatting_query_string( $data_order["order_id"] ) . "' and order_id in (select order_id from culinaria_ordercustomer where order_id = '" . main::formatting_query_string( $data_order["order_id"] ) . "' and order_status=0) ");
		$arr_parameter["product_id"] = array("=", "'" . main::formatting_query_string( $id ) . "'");
		self::update_culinaria_cart_item( $arr_set, $arr_parameter );
	}
	
	static function daftar_cart_program( $template_data_order, $read_only = false, $billingid = "" ){
		unset($arr_parameter);
		
		if( @$_SESSION["email"] != "" ){
				
			$arr_parameter["b.memberid"] = array("=", "(select memberid from membersdata where email = '". main::formatting_query_string( $_SESSION["email"] ) ."')");
			
			if( @$_SESSION["order_no"] != "" )
				$arr_parameter["b.order_no"] = array("=", "'". main::formatting_query_string($_SESSION["order_no"]) ."'");
			else
				$arr_parameter["b.order_status"] = array("=", 0);
			
			$arr_parameter["c.program_awal"] = array(">", "current_timestamp");
			$rs_data_order = culinaria_cart::browse_culinaria_cart_item( $arr_parameter );

			while( $data_order = sql::sql_fetch_array( $rs_data_order ) ){
				$arr_data_order[ $data_order["program_id"] ] = $data_order;
				$arr_program_id[ $data_order["program_id"] ] = $data_order["program_id"];
				$_SESSION["culinaria_order"][ $data_order["program_id"] ] = @$_SESSION["culinaria_order"][ $data_order["program_id"] ] != "" ? $_SESSION["culinaria_order"][ $data_order["program_id"] ] : $data_order["quantity"];
			}		
			
		}

		unset( $arr_parameter );
		$arr_parameter["program_id"] = array(" in ", "(". implode( ",", array_keys( $_SESSION["culinaria_order"] ) ) .")");
		$arr_parameter["program_awal"] = array(">", "current_timestamp");
		$rs_data_order = culinaria_program::daftar_culinaria_program( $arr_parameter );

		while( $data_order = sql::sql_fetch_array( $rs_data_order ) ){
			$arr_data_order[ $data_order["program_id"] ] = $data_order;
			$arr_program_id[ $data_order["program_id"] ] = $data_order["program_id"];
			if( @$_SESSION["email"] != "" ) culinaria_cart::entri_data_order( $data_order["program_id"], $billingid );
		}

			
		$rs_data_program_kursi_terpakai = culinaria_cart::sisa_kursi_program( array("c.program_id" => array(" in ", "(". implode(",", array_values($arr_program_id)) .")") ) );
		while( $data_program_kursi_terpakai = sql::sql_fetch_array( $rs_data_program_kursi_terpakai ) )
			$arr_program_kursi_terpakai[ $data_program_kursi_terpakai["program_id"] ] = $data_program_kursi_terpakai["kursi_terpakai"];

		$subtotal = 0;
		$arr_data_order_src = array("#nomor#", "#program_id#", "#nama_program#", "#harga#", "#opsi_select#", "#sub_total#");
		$counter = 1;

		foreach( $arr_data_order as $index => $data_order ){
			
			$program_kursi_terpakai = @$arr_program_kursi_terpakai[$data_order["program_id"]] != "" && is_numeric( $arr_program_kursi_terpakai[$data_order["program_id"]] ) 
				? $arr_program_kursi_terpakai[$data_order["program_id"]] : 0;
			$program_kursi_tersisa = $data_order["program_kelas_ukuran"] - $program_kursi_terpakai;
							
			$_SESSION["culinaria_order"][$data_order["program_id"]] = 
				$program_kursi_tersisa <= 0 ? 0 : 
					( $program_kursi_tersisa < $_SESSION["culinaria_order"][$data_order["program_id"]] ? $program_kursi_tersisa : $_SESSION["culinaria_order"][$data_order["program_id"]] );
					
			$program_subtotal = $data_order["program_harga"] * $_SESSION["culinaria_order"][$data_order["program_id"]];

			$arr_data_order_rpl = array( 
				$counter, 
				$data_order["program_id"], 
				$data_order["program_judul_". $GLOBALS["lang"]], 
				number_format( $data_order["program_harga"] ),
				(
					!$read_only ? 
					"<select name=\"qty_". $data_order["program_id"] ."\" id=\"qty_". $data_order["program_id"] ."\" 
						onchange=\"change_qty(this)\">" . main::__set_option( range( $program_kursi_tersisa> 0 ? 1 : 0, $program_kursi_tersisa), $_SESSION["culinaria_order"][$data_order["program_id"]], "xx" ) . "</select>" 
						: $_SESSION["culinaria_order"][$data_order["program_id"]]
				),
				number_format( $program_subtotal )
			);
			@$string_data_order .= str_replace( $arr_data_order_src, $arr_data_order_rpl, $template_data_order );
			
			if( @$data_order["order_id"] != "" ){
				unset( $arr_set, $arr_parameter );
				$arr_set["quantity"] = array("=", $_SESSION["culinaria_order"][$data_order["program_id"]] );
				$arr_parameter["order_id"] = array("=", "'" . main::formatting_query_string( $data_order["order_id"] ) . "'");
				$arr_parameter["product_id"] = array("=", "'" . main::formatting_query_string( $data_order["program_id"] ) . "'");
				culinaria_cart::update_culinaria_cart_item( $arr_set, $arr_parameter );
			}
			
			$subtotal += $program_subtotal; 
			$counter++;
		}
		
		return array("arr_data_order"=>$arr_data_order, "string_data_order" => $string_data_order, "subtotal" => $subtotal );
	}
	
	static function hitung_nilai_diskon_kupon( $arr_data_order, $coupon_code, $mode = "" ){
		
		$subtotal = 0;
		foreach( $arr_data_order as $index => $data_order ){
			
			if( $mode != "" ){
				
				$order_id = $data_order["order_id"];
				$subtotal += $data_order["program_harga"] * $data_order["quantity"];
				
			}else
				
				$subtotal += $data_order["program_harga"] * $_SESSION["culinaria_order"][$data_order["program_id"]];
			
		}
		
		if( $mode != "" ){
			
			unset( $arr_parameter );
			$arr_parameter["order_id"] = array("=", "'". main::formatting_query_string( $order_id ) ."'");
			$data_order = sql::sql_fetch_array( self::browse_culinaria_cart( $arr_parameter ) );
			$arr_discount[0] = $data_order["coupon_discount"] / $subtotal;
			$arr_discount[1] = $data_order["coupon_discount"];
			$arr_discount[2] = $subtotal - $data_order["coupon_discount"];
			
		}else
			$arr_discount = self::counting_discount( $coupon_code, $subtotal );
		
		$persen_diskon=$arr_discount[0];
		$nilai_diskon=$arr_discount[1];
		$total_setelah_diskon=$arr_discount[2];
		
		include_once "cls_shoppingcart.php";
		
		if($persen_diskon==0){
			$s_discount="<br />".$GLOBALS["lang_error_discount_coupon"];
			$add_error=shoppingcart::get_remark_coupon_code($coupon_code);
			$s_discount=str_replace("#add_error#", "", $s_discount);
			$s_column_discount="0 <sup>[1]</sup>";
			$s_note_coupon_code="<br /><sup>[1]</sup> ".$GLOBALS["lang_promo_code_discount"].", ".(0*100)."";
		}else {
			$s_column_discount=number_format($nilai_diskon)." <sup>[1]</sup>";
			$s_note_coupon_code="<br /><sup>[1]</sup> ".$GLOBALS["lang_promo_code_discount"].", ".($persen_diskon*100)."%";
		}
		
		return array
			(
				"subtotal" => $subtotal,
				"persen_diskon" => $persen_diskon,
				"nilai_diskon" => $nilai_diskon,
				"total_setelah_diskon" => $total_setelah_diskon,
				"s_discount" => @$s_discount,
				"s_column_discount" => $s_column_discount,
				"s_note_coupon_code" => $s_note_coupon_code				
			);
		
	}
	
	public function print_order($mode /*print | email | preview*/, $target /*customer | * */){				
		if(!isset($this->order_no))throw new Exception("Order Number not set");
		if(!isset($this->order_print_template))throw new Exception("Order Print Template not set");
		if(!isset($this->email_subject))throw new Exception("Email Subject not set");

		$arr_parameter["order_no"] = array("=", "'". main::formatting_query_string( $this->order_no ) ."'");
		$rs_cek_data_order = self::browse_culinaria_cart( $arr_parameter );
		$rs_order_list_ = self::sql_fetch_array( $rs_cek_data_order );
		
		if($mode=="email")
			if(!isset($this->email_template))throw new Exception("Email Template not set");		
		
		include $this->lang;				
		include "lang/". $GLOBALS["lang"] ."_culinaria.php";

		$order_content=file_get_contents($this->order_print_template);	
		if($mode=="email")$order_content=str_replace("#order_print_header#", "", $order_content);
		else $order_content=str_replace("#order_print_header#", (isset($this->order_print_header)?file_get_contents($this->order_print_header):""), $order_content);
		$order_content=str_replace("#order_no#", $this->order_no, $order_content);
		
		$template_data_order = file_get_contents("culinaria-cart-item-readonly.html");
		if( isset( $this->template_data_order ) ) $template_data_order = file_get_contents( $this->template_data_order );

		unset( $arr_parameter );

		$subtotal = 0;
		$counter = 1;
		$arr_data_order_src = array("#nomor#", "#program_id#", "#nama_program#", "#harga#", "#opsi_select#", "#sub_total#");

		$arr_parameter["b.order_no"] = array("=", "'". main::formatting_query_string( $this->order_no ) ."'");
		$rs_data_order = culinaria_cart::browse_culinaria_cart_item( $arr_parameter );

		while( $data_order = sql::sql_fetch_array( $rs_data_order ) ){
			//print_r($data_order);
			$arr_data_order[ $data_order["program_id"] ] = $data_order;
			$program_subtotal = $data_order["program_harga"] * $data_order["quantity"];
			
			$arr_data_order_rpl = array( 
				$counter, 
				$data_order["product_id"], 
				$data_order["nama_produk"], 
				number_format( $data_order["program_harga"] ),
				$data_order["quantity"],
				number_format( $program_subtotal )
			);

			@$string_data_order .= str_replace( $arr_data_order_src, $arr_data_order_rpl, $template_data_order );
			
			$subtotal += $program_subtotal; 
			$counter++;
		}

		$order_content=str_replace("#lang_product#", $lang_kelas, $order_content);
		$order_content=str_replace("#lang_unitprice#", $lang_price, $order_content);
		$order_content=str_replace("#lang_afterdisc#", $lang_price, $order_content);
		$order_content=str_replace("#lang_quantity#", $lang_quantity, $order_content);
		$order_content=str_replace("#order_content#", $string_data_order, $order_content);
		
		$data_diskon = culinaria_cart::hitung_nilai_diskon_kupon( $arr_data_order, $rs_order_list_["coupon_code"], "baca dari db" );
	
		 $persen_diskon = $data_diskon["persen_diskon"];
		$coupon_discount = $data_diskon["nilai_diskon"];
		$sub_grandtotal = $data_diskon["total_setelah_diskon"];
		$s_discount_2 = "Rp" . ( $coupon_discount > 0 ? $data_diskon["s_column_discount"] : 0 );
		$s_note_discount_2 = $coupon_discount > 0 ? $data_diskon["s_note_coupon_code"] : "";
		$shipping_cost = 0;

		include_once "cls_ipgpromo.php";
		$ipgpromo_discount=0;
		if($rs_order_list_["ipg_promo"]!="" && $rs_order_list_["ipg_promo"]!="0"){
			$ipgpromo=ipgpromo::ipgpromo_calc($rs_order_list_["ipg_promo"], $subtotal-$coupon_discount);
			if($ipgpromo[0]>0){
				$ipgpromo_discount=$ipgpromo[0];
				$s_discount_3="Rp".number_format($ipgpromo[0])." <sup>[".(@$s_discount_1!=""? (@$s_discount_2!=""?"3":"2") : (@$s_discount_2!=""?"2":"1"))."]</sup>";				
			}
			//$s_note_discount_3=" <sup>[".(@$s_discount_1!=""? (@$s_discount_2!=""?"3":"2") : (@$s_discount_2!=""?"2":"1"))."]</sup>".$ipgpromo[2]["remark_".$GLOBALS["lang"]];
		}
		
		$discount=$coupon_discount+$ipgpromo_discount;
		$s_discount=$s_discount_2.@$s_discount_3;		

		$s_note_discount=@$s_note_discount_2.@$s_note_discount_3;
		
		$order_content=str_replace("#subtotal#", number_format($subtotal), $order_content);
		$order_content=str_replace("#lang_discount#", $lang_discount, $order_content);
		$order_content=str_replace("#discount#", $s_discount, $order_content);
		$order_content=str_replace("#grandtotal#", number_format($subtotal-$discount), $order_content);
		$order_content=str_replace("#discount_note#", $s_note_discount, $order_content);
		
		$order_content=str_replace("#lang_billing_confirmation_title#", "<span style=\"text-decoration:underline\">".strtoupper($lang_billing_confirmation_title)."</span><br />", $order_content);
		$order_content=str_replace("#billing_info#", "<strong>".$rs_order_list_["billing_first_name"]." ".$rs_order_list_["billing_last_name"]." <br />
			:: ".$rs_order_list_["billing_address"].", ".$rs_order_list_["billing_address_city"].", ".$rs_order_list_["billing_address_state"].", ".$rs_order_list_["billing_address_country"]." ".$rs_order_list_["billing_address_postcode"]."<br />
			:: ".$rs_order_list_["billing_phone_no"]." | ".$rs_order_list_["billing_handphone_no"]."
			</strong>", $order_content);
			
		// pembayaran semi online dengan VA
		include_once "cls_shoppingcart.php";
		$doku_check=new shoppingcart;
		$doku_check->transidmerchant=$this->order_no;
		$arr_par=array(
			"payment_channel"=>"select", 
			"paymentcode"=>"select", 
			"response_code"=>"select",
			"totalamount"=>"select",
			"finishtime"=>"select");
		$rs_doku_check=$doku_check->doku_data($arr_par);
		if(mysql_num_rows($rs_doku_check)>0){		
			$s_payment_va_info="";
			$va=mysql_fetch_array($rs_doku_check);
			if(in_array($va["payment_channel"], array("05", "5")) && !in_array($va["response_code"], array($GLOBALS["payment_status_code_doku_ok"], "0000"))){
				$is_va=true;
				$lang_payment_result_confirmation_va_complete=str_replace("#payment_code#",$va["paymentcode"],$lang_payment_result_confirmation_va_complete.@file_get_contents("lang/".$GLOBALS["lang"]."_va_payment_manual.html"));
				$lang_payment_result_confirmation_va_complete=str_replace("#amount#",number_format($va["totalamount"]),$lang_payment_result_confirmation_va_complete);
				$lang_payment_result_confirmation_va_complete=str_replace("#time#",
					date('j F Y H:i', 
						mktime(
							date('H',strtotime($va["finishtime"])),date('i',strtotime($va["finishtime"]))+$GLOBALS["payment_va_expiration"], date('s',strtotime($va["finishtime"])), 
							date('m',strtotime($va["finishtime"])),date('d',strtotime($va["finishtime"])), date('Y',strtotime($va["finishtime"]))
						)
					)
					,$lang_payment_result_confirmation_va_complete);
				$lang_payment_result_confirmation_va_complete=str_replace("#orderno#",$this->order_no,$lang_payment_result_confirmation_va_complete);
				$lang_payment_result_confirmation_va_complete=str_replace("#url_path#",$GLOBALS["url_path"]."member_account.php?mode=order_tracking&sub_mode=submitted_order",$lang_payment_result_confirmation_va_complete)."<br />";
			}
		}
		// end pembayaran semi online dengan VA
		$rs_payment_info=order::get_order_payment_detail($this->order_no);
		$payment_info=mysql_fetch_array($rs_payment_info);
		$s_payment_info=$lang_payment_date.": ".$payment_info["finishtime"]."<br />".
			$lang_payment_status.": <strong>".(strtoupper($payment_info["trxstatus"])!=strtoupper($GLOBALS["payment_status_text_ok"])?$lang_payment_status_notok:$lang_payment_status_ok)."</strong><br />".
			$lang_payment_creditcard.": ".$payment_info["bank"]."<br />".
			$lang_payment_cardno.": ".$payment_info["creditcard"]."<br />";
		if(isset($is_va) && $is_va)	$s_payment_info.=$lang_payment_result_confirmation_va_complete;
		$order_content=str_replace("#lang_payment_confirmation_title#", "<span style=\"text-decoration:underline\">".strtoupper($lang_payment_confirmation_title)."</span><br />", $order_content);
		$order_content=str_replace("#payment_info#", $s_payment_info, $order_content);	
		
		$message=file_get_contents($this->email_template);	
		if($target=="customer")$message=str_replace("#greetingname#", $rs_order_list_["custname"], $message);
		else $message=str_replace("#greetingname#", "MODENA Customercare", $message);
		if(isset($this->message_no)&&$this->message_no!="")@$s_content.="Ref. ".$this->message_no."<br />";
		if(isset($this->t_message_en)&&$this->t_message_en!="")@$s_content.=$this->t_message_en."<hr width=100% size=1 /><br />";
		if(isset($this->t_message_id)&&$this->t_message_id!="")@$s_content.=$this->t_message_id."<hr width=100% size=1 /><br />";			
		if(isset($this->prev_message)&&$this->prev_message!="")@$s_content.=$this->prev_message."<hr width=100% size=1 /><br />";			
		@$s_content.=$order_content;
		$message=str_replace("#content#", $s_content, $message);
		$message=str_replace("#lang_email_info_en#", $lang_email_info, $message);
		$message=str_replace("#lang_email_footer#", "", $message);
		$message=str_replace("#horizontal_line#", "<hr width=100% size=1 />", $message);
//die($message);
		if($mode=="email"){
			$subject = $this->email_subject.$this->order_no;
			
			if($target=="customer"){
				$to  = $this->custemail; // note the comma ::: $this->custemail
				$recipient_header_to=$this->custemail;
				$from=CULINARIA_EMAIL;

			}else{
				$to["To"]=CULINARIA_EMAIL . "," . FINANCE_EMAIL;//"customercare@modena.co.id";				
				$to["Bcc"]=SUPPORT_EMAIL;
				$recipient_header_to=CULINARIA_EMAIL . "," . FINANCE_EMAIL;//CUSTOMERCARE_EMAIL;
				$from=SUPPORT_EMAIL;				
			}

			ini_set("include_path",INCLUDE_PATH);
			require_once "pear/Mail.php";
			require_once "pear/mime.php";		
		
			$mime=new Mail_mime(array('eol' => CRLF));
			$mime->setTXTBody(strip_tags($message));
			$mime->setHTMLBody($message);	
			
			$headers = array ('From' => $from,
				'To' => $recipient_header_to, 
				'Subject' => $subject);		
			if(isset($recipient_header_cc) && $recipient_header_cc!="") $headers["Cc"]=$recipient_header_cc;
			
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
		}elseif($mode=="preview") return $message;
		elseif($mode=="print") return $order_content;
	}
	
	public static function tabel_data_peserta( $program_id, $template ){
		unset( $arr_parameter );
		$arr_parameter["b.product_id"] = array(" = ", "'". main::formatting_query_string( $program_id ) ."'");
		$arr_parameter["a.order_id"] = array(" in ", "(select order_id from culinaria_orderproduct where product_id = ". $arr_parameter["b.product_id"][1] .")");		
		$arr_parameter["a.order_status"] = array(">", 0);
		$rs_data_order = self::browse_culinaria_cart_jumlah_kursi( $arr_parameter );

		$arr_data = array
			(
			"Tanggal"=>"order_date", "No Invoice"=>"order_no", 
			"Nama"=>"custname", "Email"=>"custemail", "Alamat"=>"address", 
			"Kota"=>"city", "Propinsi"=>"state", 
			"Telepon"=>"phone_no", "Handphone"=>"handphone_no",
			"Kursi"=> "jumlah_kursi"
			);

		$s_data = "<tr><td>No</td>";

		foreach( $arr_data as $judul_kolom=>$index_kolom )
			$s_data .= "<td>". $judul_kolom ."</td>";

		$s_data .= "<td>&nbsp;</td></tr>";
		$counter = 1;
		$kursi_terpakai = 0;
		while( $data_order = sql::sql_fetch_array( $rs_data_order ) ){
			
			$kursi_terpakai += $data_order["jumlah_kursi"];
			$s_data .= "<tr>";	
			$s_data .= "<td>$counter</td>";
			
			foreach( $arr_data as $index )
				$s_data .= "<td>". $data_order[ $index ] ."</td>";
				
			$s_data .= "<td><input type=\"button\" id=\"b_". $data_order["order_no"] ."\" value=\"DETAIL\" onclick=\"__print_order_culinaria('". $data_order["order_no"] ."')\" style=\"width:37px\" /></td></tr>";
			$counter++;
			
		}
		
		$arr["#tabel_data#"] = $s_data;
		$arr["#jumlah_kursi_terpakai#"] = $kursi_terpakai;
		
		return array("tabel_data_peserta" => str_replace( array_keys($arr), array_values($arr), $template ), "jumlah_kursi_terpakai" => $kursi_terpakai );
	}
	
}

?>