<?

class order extends main{
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

	public function order_list_backoffice($memberemail, $order_number, $order_status){
		$sql="select order_no,memberid, ";
		foreach($GLOBALS["arr_par_ordercustomer"] as $value=>$ref_table){
			if($value!="order_date"&&$value!="shipping_date"	&& $value!="state"){ //pengecualian utk kolom date karena akan diformat
				$this->parameter="\$this->".$value;
				eval("\$this->parameter=\"$this->parameter\";");
				if(isset($this->parameter)&&$this->parameter!="")$sql.=$value.",";
			}else{ 
				if($value=="order_date")$sql.="date_format(order_date,'%d %M %Y, %H:%i:%S') order_date, order_date order_date_non_formatted, ";
				if($value=="shipping_date")$sql.="date_format(shipping_date,'%d %M %Y') shipping_date,";
				if($value=="state")$sql.="a.".$value.",";
			}
		}
		$sql.=
			"x.total_harga_produk - 
			(case when coupon_discount>1 then coupon_discount else	(x.total_harga_produk * coupon_discount) end) + 
			shippingcost order_amount, 
			case 	
				when z.trxstatus='success' or trxstatus='00' then 'Lunas'
				else 'Belum Lunas'
			end payment_status, z.approvalcode
			from ordercustomer a 
			inner join (select order_id, sum(product_pricepromo*quantity) total_harga_produk from orderproduct group by order_id) x
				on a.order_id=x.order_id
			#registered_login# a.order_id is not null ";
		if($order_status!="")$sql.=" and order_status".$order_status." ";
		if($order_number!="")$sql.=" and order_no='".main::formatting_query_string($order_number)."' ";
		if($memberemail!="")$sql.=" and memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') ";
					
		if($this->search_order_number){
			$sql=str_replace("#registered_login#"," inner join doku z on a.order_no=z.transidmerchant where",$sql);
			$sql.=" and order_no like '%".main::formatting_query_string($this->search_order_number)."%' ";
		}else $sql=str_replace("#registered_login#","inner join shipment_exception b on a.shipping_address_city=b.region /*inner join shipment_state c
			on a.shipping_address_state=c.state*/ inner join branch_service d on b.region_id=d.service_region_id and b.state_id=d.service_state_id
			inner join branch e on d.branch_region_id=e.region_id and d.branch_state_id=e.state_id 
			inner join doku z on a.order_no=z.transidmerchant 
			where 
			(e.email_admin='".main::formatting_query_string(@$_SESSION["login"])."' or e.email_wh='".main::formatting_query_string(@$_SESSION["login"])."') 
			and",$sql);
		if($this->search_customer_name)$sql.=" and memberid in (select memberid from membersdata where name like '%".main::formatting_query_string($this->search_customer_name)."%') ";
		if($this->search_order_date)$sql.=" and date_format(cast(order_date as date),'%d %M %Y') like '%".main::formatting_query_string($this->search_order_date)."%' ";
		if($this->search_order_date_start)$sql.=" and order_date>='".$this->search_order_date_start."' ";
		if($this->search_order_date_end)$sql.=" and order_date<='".$this->search_order_date_end."' ";
		if($this->additional_parameter)$sql.=$this->additional_parameter;
		if(isset($this->search_payment_status)&&$this->search_payment_status){
			$sql.=" and (trxstatus='success' or trxstatus='00') ";
		}elseif(isset($this->search_payment_status)&&!$this->search_payment_status){
			$sql.=" and (trxstatus<>'success' and trxstatus<>'00') ";
		}
		if(isset($this->orderby))$sql.=" order by ".main::formatting_query_string($this->orderby);
		$rs_order_list=mysql_query($sql) or die();//("order_list_backoffice query error.<br />".mysql_error());
		return $rs_order_list;
	}
	
	public function order_list_backoffice_accpac($order_status){
		$sql="select order_no,";		
		$sql.="
			date_format(order_date,'%d %M %Y, %H:%i:%S') order_date, order_date order_date_non_formatted, 
			date_format(shipping_date,'%d %M %Y') shipping_date,
			x.total_harga_produk - 
			(case when coupon_discount>1 then coupon_discount else	(x.total_harga_produk * coupon_discount) end) + 
			shippingcost order_amount, order_status, custemail		
			from ordercustomer a 
			inner join (select order_id, sum(product_pricepromo*quantity) total_harga_produk from orderproduct group by order_id) x
				on a.order_id=x.order_id
			where a.order_id is not null ";
		if($order_status!="")$sql.=" and order_status".$order_status." ";
					
		if($this->search_order_date_start)$sql.=" and order_date>='".$this->search_order_date_start." 00:00:00' ";
		if($this->search_order_date_end)$sql.=" and order_date<='".$this->search_order_date_end." 23:59:59' ";
		if(isset($this->orderby))$sql.=" order by ".main::formatting_query_string($this->orderby);
		$rs_order_list=mysql_query($sql) or die();//("order_list_backoffice query error.<br />".mysql_error());
		return $rs_order_list;
	}

	public function order_list($memberemail, $order_number, $order_status, $arr_par){
		$sql="select order_no,memberid, ";
		foreach($arr_par as $value=>$ref_table){
			if($value!="order_date"&&$value!="shipping_date"){ //pengecualian utk kolom date karena akan diformat
				$this->parameter="\$this->".$value;
				eval("\$this->parameter=\"$this->parameter\";");
				if(isset($this->parameter)&&$this->parameter!="")$sql.=$value.",";
			}else{ 
				if($value=="order_date")$sql.="date_format(order_date,'%d %M %Y, %H:%i:%S') order_date, order_date order_date_non_formatted, ";
				if($value=="shipping_date")$sql.="date_format(shipping_date,'%d %M %Y') shipping_date,";
			}
		}
		$sql.="(select sum(product_pricepromo*quantity) from orderproduct where order_id=a.order_id)-
			(case 
			when coupon_discount>1 then coupon_discount
			else	((select sum(product_pricepromo*quantity) from orderproduct where order_id=a.order_id)*coupon_discount) end)+
			ifnull(shippingcost,0) order_amount, 
			case 
				when order_no in (select transidmerchant from doku where trxstatus='success' or trxstatus='00') then 'Lunas' 
				when order_no in (select transidmerchant from doku where trxstatus<>'success' and trxstatus<>'00') then '<span style=\"color:red\">Belum Lunas</span>'
				else '<span style=\"color:red\">Belum Lunas</span>'
			end payment_status, ipg_promo ";
		$sql.="from ordercustomer a where order_id is not null ";
		if($order_status!="")$sql.=" and order_status".$order_status." ";
		if($order_number!="")$sql.=" and order_no='".main::formatting_query_string($order_number)."' ";
		//if($memberemail!="")$sql.=" and memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') ";
		// utk pencarian di kontrol panel
		if($this->search_order_number)$sql.=" and order_no like '%".main::formatting_query_string($this->search_order_number)."%' ";
		if($this->search_customer_name)$sql.=" and memberid in (select memberid from membersdata where name like '%".main::formatting_query_string($this->search_customer_name)."%') ";
		if($this->search_order_date)$sql.=" and date_format(cast(order_date as date),'%d %M %Y') like '%".main::formatting_query_string($this->search_order_date)."%' ";
		if(isset($this->search_payment_status)&&$this->search_payment_status){
			$sql.=" and order_no in (select transidmerchant from doku where trxstatus='success' or trxstatus='00') ";
		}elseif(isset($this->search_payment_status)&&!$this->search_payment_status){
			$sql.=" and order_no in (select transidmerchant from doku where trxstatus<>'success' and trxstatus<>'00') ";
		}
		// end pencarian di kontrol panel
		if(isset($this->orderby))$sql.=" order by ".main::formatting_query_string($this->orderby);
		$rs_order_list=mysql_query($sql) or die();//("order_list query error.<br />".mysql_error());
		return $rs_order_list;
	}

	public function order_list_for_accpac_sync($memberemail, $order_number, $order_status, $arr_par){
		$sql="select order_no,memberid, ";
		foreach($arr_par as $value=>$ref_table){
			if($value!="order_date"&&$value!="shipping_date"){ //pengecualian utk kolom date karena akan diformat
				$this->parameter="\$this->".$value;
				eval("\$this->parameter=\"$this->parameter\";");
				if(isset($this->parameter)&&$this->parameter!="")$sql.=$value.",";
			}else{ 
				if($value=="order_date")$sql.="date_format(order_date,'%d %M %Y, %H:%i:%S') order_date, order_date order_date_non_formatted, ";
				if($value=="shipping_date")$sql.="date_format(shipping_date,'%d %M %Y') shipping_date,";
			}
		}
		$sql.="
			ifnull(shippingcost, 0) ongkir, 
			round(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) 
			end) diskon_header,
			case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount
			end diskon_ipg,
			round(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) 
			end +
			case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount
			end) diskon_total, 
			round((
			case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) 
			end +
			case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
					(
					case 
						when coupon_discount>1 then coupon_discount
						else	(b.item_priceamount * coupon_discount) 
					end
					) 
				) * ipg_promo_discount
				end
			) / 
			(
				b.item_priceamount 
			) * 100) diskon_total_persen
			/*(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) 
			end +
			case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount
			end) / 
			(
			b.item_priceamount -
			(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) end) -
			(case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount end) 
			) * 100 diskon_total_persen *//* dari (diskon_header + diskon_ipg) / order_amount_tanpa_ongkir * 100 */,
			round(b.item_priceamount -
			(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) end) -
			(case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount end) +
			ifnull(shippingcost,0)) order_amount, 
			round(b.item_priceamount -
			(case 
				when coupon_discount>1 then coupon_discount
				else	(b.item_priceamount * coupon_discount) end) -
			(case 
				when ipg_promo_discount > 1 then ipg_promo_discount
				else (
					b.item_priceamount -
						(case 
							when coupon_discount>1 then coupon_discount
							else	(b.item_priceamount * coupon_discount) end) +
						ifnull(shippingcost,0)
					) * ipg_promo_discount end))
			order_amount_tanpa_ongkir ";
		$sql.="
			from ordercustomer a 
			inner join (select order_id, sum(product_pricepromo*quantity) item_priceamount from orderproduct group by order_id) b 
				on a.order_id = b.order_id
			where 
			 a.order_id is not null ";
		if($order_status!="")$sql.=" and order_status".$order_status." ";
		if($order_number!="")$sql.=" and order_no='".main::formatting_query_string($order_number)."' ";
		if($memberemail!="")$sql.=" and memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') ";
		// utk pencarian di kontrol panel
		if($this->search_order_number)$sql.=" and order_no like '%".main::formatting_query_string($this->search_order_number)."%' ";
		if($this->search_customer_name)$sql.=" and memberid in (select memberid from membersdata where name like '%".main::formatting_query_string($this->search_customer_name)."%') ";
		if($this->search_order_date)$sql.=" and date_format(cast(order_date as date),'%d %M %Y') like '%".main::formatting_query_string($this->search_order_date)."%' ";
		if(isset($this->search_payment_status)&&$this->search_payment_status){
			$sql.=" and order_no in (select transidmerchant from doku where trxstatus='success' or trxstatus='00') ";
		}elseif(isset($this->search_payment_status)&&!$this->search_payment_status){
			$sql.=" and order_no in (select transidmerchant from doku where trxstatus<>'success' and trxstatus<>'00') ";
		}
		// end pencarian di kontrol panel
		if(isset($this->orderby))$sql.=" order by ".main::formatting_query_string($this->orderby);
		$rs_order_list=mysql_query($sql) or die();//("order_list query error.<br />".mysql_error());
		return $rs_order_list;
	}
	
	public static function order_product_brief($order_number, $num_data){
		return;
		$sql="select b.name, a.quantity from orderproduct a, product b where a.product_id=b.productid 
			and a.order_id=(select order_id from ordercustomer where order_no='".main::formatting_query_string($order_number)."')";
		$rs_order_product_brief=mysql_query($sql) or die();//("order_product_brief error.<br />".mysql_error());
		$counter=1;
		while($rs_order_product_brief_=mysql_fetch_array($rs_order_product_brief)){
			if($counter==$num_data)break;
			$product_name=preg_split("/ - /", $rs_order_product_brief_["name"]);
			if(isset($return))$return.=", (".$rs_order_product_brief_["quantity"].") ".(@$product_name[1]!=""?$product_name[1]:$rs_order_product_brief_["name"]);
			else $return="(".$rs_order_product_brief_["quantity"].") ".(@$product_name[1]!=""?$product_name[1]:$rs_order_product_brief_["name"]);
			$counter++;
		}return $return;
	}
	
	public static function order_product($order_number, $customer_email){
		$sql="select a.nama_produk name, a.product_id, a.quantity, a.product_price, a.product_promo, a.discount, a.product_pricepromo, (a.product_pricepromo*a.quantity) total_amount, a.tradein, a.promo_per_item_id, a.note, a.preorder, a.gudang, a.sku,
			d.categoryid, d.name categoryname, e.categoryid parentcategoryid, e.name parentcategoryname
			from 
			/*orderproduct a, ordercustomer b, product c, category d, category e 
			where a.order_id=b.order_id and a.product_id=c.productid and c.categoryid=d.categoryid and d.parentcategoryid = e.categoryid and*/
			orderproduct a inner join ordercustomer b on a.order_id=b.order_id 
			left outer join product c on a.product_id=c.productid
			left outer join category d on c.categoryid=d.categoryid
			left outer join category e on d.parentcategoryid = e.categoryid 
			where
			b.order_no='".main::formatting_query_string($order_number)."' /*and
			b.memberid=(select memberid from membersdata where email='".$customer_email."')*/
			";
		$rs_order_product=mysql_query($sql) or die();//("order_product query error.<br />".mysql_error());
		return $rs_order_product;
	}

	public static function order_product_for_accpac_sync($order_number){
		$sql="select c.name, a.product_id, d.kode, 
			a.quantity, a.product_price, ifnull(a.product_promo, 0) product_promo, (ifnull(a.product_promo, 0) / a.product_price) * 100 product_promo_percent, 
			a.discount, a.product_pricepromo, 
			(a.product_pricepromo*a.quantity) total_amount, a.tradein, a.promo_per_item_id, e.remark_id note, g.categoryid
			from orderproduct a 
			inner join ordercustomer b on a.order_id=b.order_id
			inner join product c on a.product_id=c.productid 
			inner join __kode_produk_accpac d on c.productid=d.productid
			left outer join category f on c.categoryid = f.categoryid
			left outer join category g on f.parentcategoryid = g.categoryid
			left outer join promo_per_item e on a.promo_per_item_id = e.promo_per_item_id
			where 
			b.order_no='".main::formatting_query_string($order_number)."' 
			";
		$rs_order_product=mysql_query($sql) or die();//("order_product query error.<br />".mysql_error());
		return $rs_order_product;
	}
	
	public static function get_order_progress_detail($order_number, $order_status){
		$sql="select date_format(a.progress_date,'%d %M %Y, %H:%i:%S') progress_date, a.progress_message_no, 
			date_format(a.shipping_date,'%d %M %Y') shipping_date, a.shipping_no, a.shipping_by, date_format(a.est_installation_date,'%d %M %Y') est_installation_date, 
			date_format(a.received_date,'%d %M %Y') received_date, a.received_no, a.received_by 
			from order_progress a left outer join message b on a.progress_message_no=b.message_no 
			inner join (select max(progress_date) progress_date, order_no, progress_type from order_progress group by order_no, progress_type) c 
				on a.progress_date=c.progress_date and a.progress_type=c.progress_type and a.order_no=c.order_no 
			where a.order_no='".main::formatting_query_string($order_number)."' and a.progress_type='".main::formatting_query_string($order_status)."';";
		$rs=mysql_query($sql) or die();//("get_order_progress_detail query error.<br />".mysql_error());
		return $rs;
	}
	
	public static function get_order_payment_detail($order_number){
		$sql="select starttime, finishtime, date_format(starttime,'%d %M %Y, %H:%i:%S') starttime_formatted, date_format(finishtime,'%d %M %Y, %H:%i:%S') finishtime_formatted,
			trxstatus, totalamount, transidmerchant, response_code, creditcard, bank, approvalcode from doku where transidmerchant='".$order_number."';";
        $sql = "select starttime, finishtime, date_format(starttime,'%d %M %Y, %H:%i:%S') starttime_formatted, date_format(finishtime,'%d %M %Y, %H:%i:%S') finishtime_formatted, 
                trxstatus, totalamount, transidmerchant, response_code, creditcard, bank, approvalcode from (
                    SELECT 
                        b.transaction_time starttime, case when (payment_type = 'credit_card' and channel_response_code = '00') or (payment_type = 'gopay' and status_code = '200') then b.transaction_time when payment_type = 'bank_transfer' then b.settlement_time else null end  finishtime, 
                        b.transaction_status trxstatus, b.amount totalamount, a.modena_order_no transidmerchant, b.status_code response_code, b.masked_card creditcard, upper(b.payment_type) bank, b.status_code approvalcode 
                    FROM modena_db.orders a, modena_db.transactions b 
                    where a.modena_order_no = '". main::formatting_query_string($order_number) ."' /*b.purchase_id = '60E7A352A982F-1625793362'*/ and a.id = b.order_id 
                    order by b.transaction_time desc limit 1
                ) a";
		$rs=mysql_query($sql) or die();//("get_order_payment_detail query error.<br />".mysql_error());
		return $rs;
	}
	
	private static function get_email_address($state_id, $region_id){ // <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<----------------------------------- tambahkan email_finance
		$sql="select email_admin, email_wh, email_finance, email_service from branch a, branch_service b, shipment_exception c, shipment_state d 
			where a.state_id=b.branch_state_id and a.region_id=b.branch_region_id and 
			b.service_state_id=c.state_id and b.service_region_id=c.region_id and c.state_id=d.state_id and 
			(b.service_state_id='".$state_id."' or d.state='".$state_id."') and (b.service_region_id='".$region_id."' or c.region='".$region_id."') and a.enabled='1' and b.enabled='1';";
		$rs=mysql_query($sql) or die();//("get_email_address query error.<br />".mysql_error());
		$data=mysql_fetch_array($rs);
		return $data;
	}
	
	static function get_promo_detail($promoid){
		$sql="select remark_en, remark_id, remark_opposite_en, remark_opposite_id from promo_per_item where promo_per_item_id='".$promoid."';";
		$rs_result=mysql_query($sql) or die();//("get_promo_detail error.<br />".mysql_error());
		$dresult=mysql_fetch_array($rs_result);
		$result["remark_en"]=$dresult["remark_en"];
		$result["remark_id"]=$dresult["remark_id"];
		$result["remark_opposite_en"]=$dresult["remark_opposite_en"];
		$result["remark_opposite_id"]=$dresult["remark_opposite_id"];
		return $result;		
	}
	
	public function print_order($mode /*print | email | preview*/, $target /*customer | * */){		
		/* parameter harus ada : lang, arr_parameter_order, custemail, order_no, order_status, order_print_template, email_subject, discount */
		if(!isset($this->lang))throw new Exception("Language not set");
		if(!isset($this->arr_par_ordercustomer))throw new Exception("Main Parameter not set");
		//if(!isset($this->custemail))throw new Exception("Customer email not set");
		if(!isset($this->order_no))throw new Exception("Order Number not set");
//		if(!isset($this->order_status))throw new Exception("Order Status not set");
		if(!isset($this->order_print_template))throw new Exception("Order Print Template not set");
		if(!isset($this->email_subject))throw new Exception("Email Subject not set");

		if(!isset($this->custemail) || $this->custemail == ""){
			$oc = new order;
			$oc->custemail = "cari customer email";
			$rs_oc = $oc->order_list("", $this->order_no, "", $GLOBALS["arr_par_ordercustomer"]);
			$data_order = mysql_fetch_array($rs_oc);

			$this->custemail = $data_order["custemail"];
			if($this->custemail == "")	throw new Exception("Customer email not set");
		}
		
		if($mode=="email")
			if(!isset($this->email_template))throw new Exception("Email Template not set");					
		include $this->lang;				
//		include $this->discount_object;

		$arr_lang=preg_split("/\//", $this->lang);
		$lang=explode(".",$arr_lang[count($arr_lang)-1]);

		$order_content=file_get_contents($this->order_print_template);	
		if($mode=="email")$order_content=str_replace("#order_print_header#", "", $order_content);
		else $order_content=str_replace("#order_print_header#", (isset($this->order_print_header)?file_get_contents($this->order_print_header):""), $order_content);
		$order_content=str_replace("#order_no#", $this->order_no, $order_content);
		
		$order_content=str_replace("#lang_product#", $lang_product, $order_content);
		$order_content=str_replace("#lang_unitprice#", $lang_unitprice, $order_content);
		$order_content=str_replace("#lang_afterdisc#", $lang_afterdisc, $order_content);
		$order_content=str_replace("#lang_quantity#", $lang_quantity, $order_content);

		$cek_ada_sp_acs = false;
		$cek_ada_fg= false;
		$subtotal=0;$s_order_content="";
		$rs_product_list=order::order_product($this->order_no, $this->custemail);	
		$cek_ada_stiker_di_cart = false;
		$hitungan = 1;
		while($rs_product_list_=mysql_fetch_array($rs_product_list)){

			$cek_ada_stiker = false;
			if( in_array($rs_product_list_["parentcategoryid"], $GLOBALS["arr_kategori_sp_acs"]) ) $cek_ada_sp_acs = true;
			if( in_array($rs_product_list_["parentcategoryid"], $GLOBALS["arr_kategori_fg"]) ) $cek_ada_fg = true;
			if( in_array($rs_product_list_["categoryid"], $GLOBALS["arr_kategori_stiker"]) ) {$cek_ada_stiker = true;$cek_ada_stiker_di_cart = true;}
			
			if($rs_product_list_["product_promo"]>0){	
				$s_product_price="Rp<span style=\"text-decoration:line-through\">".number_format($rs_product_list_["product_price"])."</span><br />".
				($rs_product_list_["product_promo"]==$rs_product_list_["product_price"]	?"<strong>FREE</strong>":"Rp".number_format(	$rs_product_list_["product_pricepromo"]));
			}else{			
				$s_product_price="Rp".number_format($rs_product_list_["product_price"]);
			}

			$subtotal+=$rs_product_list_["total_amount"];
            
            $data_no_order_accpac = $this->curl_data->{$rs_product_list_["gudang"]};
			if($rs_product_list_["tradein"]=="1")$tradein_exists=true;
			$free_gift=order::get_promo_detail($rs_product_list_["promo_per_item_id"]);
			$s_order_content.="<tr>
					<td align=\"center\" id=\"column_product_list\">$hitungan<!--<img src=\"". $GLOBALS["url_path"] ."/images/".(file_exists(__DIR__."/../images/product/tn".$rs_product_list_["name"].".png")?"product/tn".$rs_product_list_["name"].".png":"product.png")."\" border=\"0\" width=\"100px\" />--></td>
					<td valign=\"top\" id=\"column_product_list\"><strong>"
					. $rs_product_list_["name"] . ($cek_ada_stiker ? "<br />" . self::dapatkan_list_stiker($this->order_no, $rs_product_list_["product_id"]) : "") 					
					. ($rs_product_list_["preorder"]=="1"?" <span style=\"background-color:yellow\">[PREORDER]</span>":"")				
					. ($target != "customer" ? "<br />SKU : ". $rs_product_list_["sku"] ."<br />GUDANG : ". $rs_product_list_["gudang"] . ( $data_no_order_accpac != "" ? "<br />Split order : " . $data_no_order_accpac : "" ) : "" )
					. ($rs_product_list_["tradein"]=="1"?"<br />Trade-in *":"")
					. ($free_gift["remark_".$lang[0]]!=""?"<br /><span style=\"text-decoration:underline\">".$free_gift["remark_".$lang[0]] . "</span>" :"")
					. ($rs_product_list_["note"]!=""?"<br /><span style=\"text-decoration:underline\">".$rs_product_list_["note"]."</span>":"")
					. ($rs_product_list_["preorder"]=="1" && $target != "customer" ? "<div style=\"text-align:left; margin-top:7px; margin-bottom:7px; line-height:17px\">Note : data Accpac tertunda proses preorder. Mohon tunggu email notifikasi preorder selesai.</div>":"")
					. "</strong></td>
					<!--<td valign=\"top\" id=\"column_product_list\">Rp<span style=\"text-decoration:line-through\">".number_format($rs_product_list_["product_price"]/(1-$rs_product_list_["discount"]))."</td>
					<td valign=\"top\" id=\"column_product_list\">".($rs_product_list_["discount"]*100)."%</td>-->
					<td valign=\"top\" id=\"column_product_list\">".$s_product_price."</td>
					<td valign=\"top\" id=\"column_product_list\">".$rs_product_list_["quantity"]."</td>
					<td valign=\"top\" id=\"column_product_list\"><strong>Rp".number_format($rs_product_list_["total_amount"])."</strong></td>
				  </tr>";
			$hitungan++;
		}		
		$order_content=str_replace("#order_content#", $s_order_content, $order_content);

		$order_list=new order;
		foreach($this->arr_par_ordercustomer as $value=>$ref_table)
			$order_list->__set($value, $value);
		$rs_order_list=$order_list->order_list($this->custemail, $this->order_no, "", $this->arr_par_ordercustomer);
		$rs_order_list_=mysql_fetch_array($rs_order_list);
		
		/*
		Urutan diskon : 1. diskon promo, smart promo ; 2. diskon kupon ; 3. diskon ipg
		*/
		$additional_discount=0;
		if($rs_order_list_["additional_discount_code"]!=""){
			$additional_discount=$subtotal*$rs_order_list_["additional_discount"];
			$subtotal_temp=$subtotal-$additional_discount;
		}else $subtotal_temp=$subtotal;
		
		$coupon_discount=($rs_order_list_["coupon_discount"]>1?
			$rs_order_list_["coupon_discount"]:
			$rs_order_list_["coupon_discount"]*$subtotal_temp);	
		
		$s_discount_1=
			($rs_order_list_["additional_discount_code"]!=""?
				"Rp".number_format($additional_discount)." <sup>[1]</sup><br />" : "");
		if($s_discount_1!="")$s_note_discount_1="<sup>[1]</sup> ".$rs_order_list_["additional_discount_code"].($rs_order_list_["additional_discount"]*100>0?", ".($rs_order_list_["additional_discount"]*100)."%":"")." <strong>".($rs_order_list_["coupon_code"]!=""?"(".$rs_order_list_["coupon_code"].")":"")."</strong><br />";
		
		$s_discount_2=	
			($coupon_discount>0?
				"Rp".number_format($coupon_discount)." <sup>[".(@$s_discount_1!=""?"2":"1")."]</sup><br />" : "");
		if($s_discount_2!="" || $rs_order_list_["coupon_code"] != "")$s_note_discount_2="<sup>[".(@$s_discount_1!=""?"2":"1")."]</sup> ".$lang_promo_code_discount.", ".
							($rs_order_list_["coupon_discount"]>1?
								"Rp".number_format($rs_order_list_["coupon_discount"])."</span>":
								($rs_order_list_["coupon_discount"]*100)."%</span>"
							) . " - " . $rs_order_list_["coupon_code"];		
		
		include_once "cls_ipgpromo.php";
		$ipgpromo_discount=0;
		if($rs_order_list_["ipg_promo"]!="" && $rs_order_list_["ipg_promo"]!="0"){
			$ipgpromo=ipgpromo::ipgpromo_calc($rs_order_list_["ipg_promo"], $subtotal-$additional_discount-$coupon_discount);
			if($ipgpromo[0]>0){
				$ipgpromo_discount=$ipgpromo[0];
				$s_discount_3="Rp".number_format($ipgpromo[0])." <sup>[".(@$s_discount_1!=""? (@$s_discount_2!=""?"3":"2") : (@$s_discount_2!=""?"2":"1"))."]</sup>";				
			}
			$s_note_discount_3=" <sup>[".(@$s_discount_1!=""? (@$s_discount_2!=""?"3":"2") : (@$s_discount_2!=""?"2":"1"))."]</sup>".$ipgpromo[2]["remark_".$GLOBALS["lang"]];
		}
		
		$discount=$additional_discount+$coupon_discount+$ipgpromo_discount;
		$s_discount=$s_discount_1.$s_discount_2.@$s_discount_3;		

		$s_tradein_note="<span style=\"font-weight:bold\" >*. ".str_replace("#href#","#",$GLOBALS["lang_toc_tradein"])."</span>";		
		$s_note_discount=@$s_note_discount_1.@$s_note_discount_2.(isset($tradein_exists)&&$tradein_exists?$s_tradein_note:"").@$s_note_discount_3;
		
		$order_content=str_replace("#subtotal#", number_format($subtotal), $order_content);
		$order_content=str_replace("#lang_discount#", $lang_discount, $order_content);
		$order_content=str_replace("#discount#", $s_discount, $order_content);
		$order_content=str_replace("#lang_shipping_cost#", $lang_shipping_cost, $order_content);
		$order_content=str_replace("#shipping_cost#", ($rs_order_list_["shippingcost"]>0?"Rp".number_format($rs_order_list_["shippingcost"]):"FREE")	, $order_content);
		$order_content=str_replace("#grandtotal#", number_format($subtotal-$discount+$rs_order_list_["shippingcost"]), $order_content);
		$order_content=str_replace("#discount_note#", $s_note_discount, $order_content);
		
		$order_content=str_replace("#lang_billing_confirmation_title#", "<span style=\"text-decoration:underline\">".strtoupper($lang_billing_confirmation_title)."</span><br />", $order_content);
		$order_content=str_replace("#billing_info#", "<strong>".$rs_order_list_["billing_first_name"]." ".$rs_order_list_["billing_last_name"]." <br />
			:: ".$rs_order_list_["billing_address"].", ".$rs_order_list_["billing_address_city"].", ".$rs_order_list_["billing_address_state"].", ".$rs_order_list_["billing_address_country"]." ".$rs_order_list_["billing_address_postcode"]."<br />
			:: ".$rs_order_list_["billing_phone_no"]." | ".$rs_order_list_["billing_handphone_no"]."
			</strong>", $order_content);
	
		$note_for_shipping_date = 	str_replace("#maximum_shipping_time#", $GLOBALS["maximum_shipping_time"], 
									str_replace("#minimum_shipping_time#", $GLOBALS["minimum_shipping_time"], 
									str_replace("#day_no#", $GLOBALS["default_shipping_time"],$lang_shipping_date_note)));
	
		$order_content=str_replace("#lang_shipping_confirmation_title#", "<span style=\"text-decoration:underline\">".strtoupper($lang_shipping_confirmation_title)."</span><br />", $order_content);
		$order_content=str_replace("#shipping_info#", "<strong>".$rs_order_list_["receiver_name_for_shipping"]." <br />
			:: ".$rs_order_list_["shipping_address"].", ".$rs_order_list_["shipping_address_city"].", ".$rs_order_list_["shipping_address_state"].", ".$rs_order_list_["shipping_address_country"]." ".$rs_order_list_["shipping_address_postcode"]."<br />
			:: ".$rs_order_list_["shipping_phone_no"]." | ".$rs_order_list_["shipping_handphone_no"]."
			</strong><br /><span>
			".$lang_shipping_date.": ".$rs_order_list_["shipping_date"]."<br />
			". ($rs_order_list_["shipping_date"] != "" ? "" : $note_for_shipping_date . "<br />") ."
			<!--".$lang_installation_service.": ".($rs_order_list_["shipping_installation_option"]?$lang_installation_required_email:$lang_installation_not_required)."<br />-->
			".$lang_note.": ".$rs_order_list_["shipping_note"]."<br />
			". $lang_installation_option ."</span>"
			, $order_content);
		
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
		/*$rs_payment_info=order::get_order_payment_detail($this->order_no);
		$payment_info=mysql_fetch_array($rs_payment_info);

		$s_payment_info=$lang_payment_date.": ".$payment_info["finishtime"]."<br />".
			$lang_payment_status.": <strong>".(strtoupper($payment_info["approvalcode"])!=200?$lang_payment_status_notok:$lang_payment_status_ok)."</strong><br />".
			$lang_payment_creditcard.": ".$payment_info["bank"]."<br />".
			$lang_payment_cardno.": ".$payment_info["creditcard"]."<br />";
			*/
		$s_payment_info = "Pembayaran Berhasil";
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

		if($mode=="email"){
			$subject = $this->email_subject.$this->order_no;
			if($target=="customer"){
				$to  = $this->custemail; // note the comma ::: $this->custemail
				$recipient_header_to=$this->custemail;
				$from=SHOWROOM_EMAIL;
//			}elseif($target=="customercare@modena.co.id"){
			}else{
				if($target!=""){
					$to["To"]=$target;//CUSTOMERCARE_EMAIL;//"customercare@modena.co.id";				
					$to["Bcc"]=SUPPORT_EMAIL;
					$recipient_header_to=$target;//CUSTOMERCARE_EMAIL;
					$from=SUPPORT_EMAIL;				
				}else{
					$adm_wh_email=order::get_email_address($rs_order_list_["shipping_address_state"], $rs_order_list_["shipping_address_city"]);
					$salesadmin_email=@$this->admin_cabang!=""?$this->admin_cabang:@$adm_wh_email["email_admin"];
					$warehouse_email=@$this->warehouse_cabang!=""?$this->warehouse_cabang:@$adm_wh_email["email_wh"];
					$finance_email=@$this->finance_cabang!=""?$this->finance_cabang:FINANCE_EMAIL;

					$salesadmin_email .= $cek_ada_sp_acs ? ", ". $adm_wh_email["email_service"] : "";
					
					if($rs_order_list_["order_status"]==1){//order diterima & sudah dibayar
						$to["To"]=($salesadmin_email!=""?$salesadmin_email:SALESADMIN_EMAIL).",".$finance_email;//"sales.admin@modena.co.id, finance@modena.co.id";				NTAR KLO DOKU DAH AKTIF
	//					$to["To"]=SHOWROOM_EMAIL;		// 																																INFAKTIFKAN KLO DOKU DAH AKTIF					
						$to["Cc"]=CUSTOMERCARE_EMAIL.",".SHOWROOM_EMAIL;																							//NTAR KLO DOKU DAH AKTIF
						//$to["Cc"]=CUSTOMERCARE_EMAIL;//"customercare@modena.co.id, showroom@modena.co.id";									INAKTIFKAN KLO DOKU DAH AKTIF										
						$recipient_header_to=($salesadmin_email!=""?$salesadmin_email:SALESADMIN_EMAIL).",".$finance_email;																					//NTAR KLO DOKU DAH AKTIF
	//					$recipient_header_to=SHOWROOM_EMAIL;		// 																											INFAKTIFKAN KLO DOKU DAH AKTIF					
						$recipient_header_cc=CUSTOMERCARE_EMAIL.",".SHOWROOM_EMAIL;																		//NTAR KLO DOKU DAH AKTIF
						//$recipient_header_cc=CUSTOMERCARE_EMAIL;			// 																									INFAKTIFKAN KLO DOKU DAH AKTIF
						$from=SUPPORT_EMAIL;
					}elseif($rs_order_list_["order_status"]==2){//order dikonfirmasi dan siap dikirimkan
						$to["To"]=($warehouse_email!=""?$warehouse_email:WAREHOUSE_EMAIL).",".($salesadmin_email!=""?$salesadmin_email:SALESADMIN_EMAIL).",".$finance_email;//"warehouse@modena.co.id";								
						$to["Cc"]=CUSTOMERCARE_EMAIL.",".SHOWROOM_EMAIL;	
						if (($cek_ada_fg) or (!is_numeric($this->order_no))) {
						
						$recipient_header_to=($warehouse_email!=""?$warehouse_email:WAREHOUSE_EMAIL).",".($salesadmin_email!=""?$salesadmin_email:SALESADMIN_EMAIL).",".$finance_email;
						} else {
						$recipient_header_to=$adm_wh_email["email_service"] .",".$finance_email;
						}
						$recipient_header_cc=CUSTOMERCARE_EMAIL.",".SHOWROOM_EMAIL;									
						$from=SUPPORT_EMAIL;
					}elseif($rs_order_list_["order_status"]==3){
						$subject="[modena.co.id] Order dalam proses pengiriman :: ".$this->order_no;
						$to["To"]=($warehouse_email!=""?$warehouse_email:WAREHOUSE_EMAIL);
						$to["Cc"]=CUSTOMERCARE_EMAIL.",".SHOWROOM_EMAIL;
						$recipient_header_to=($warehouse_email!=""?$warehouse_email:WAREHOUSE_EMAIL);
						$recipient_header_cc=CUSTOMERCARE_EMAIL.",".SHOWROOM_EMAIL;				
						$from=WAREHOUSE_EMAIL;					
					}elseif($rs_order_list_["order_status"]==4){//order dalam proses pengiriman atau order sudah diselesaikan
						$subject="[modena.co.id] Order sudah dikirimkan :: ".$this->order_no;
						$to["To"]=CUSTOMERCARE_EMAIL.",".SHOWROOM_EMAIL;
						$to["Cc"]=($salesadmin_email!=""?$salesadmin_email:SALESADMIN_EMAIL).",".$finance_email;
						$recipient_header_to=CUSTOMERCARE_EMAIL.",".SHOWROOM_EMAIL;				
						$recipient_header_cc=($salesadmin_email!=""?$salesadmin_email:SALESADMIN_EMAIL).",".$finance_email;
						$from=($warehouse_email!=""?$warehouse_email:WAREHOUSE_EMAIL);
					}	
				}
				$to["Bcc"]="support.it@modena.com";
				if( $cek_ada_stiker_di_cart ) $to["To"] .= ",munarko.sentana@modena.com";
			}

			// mail service 
			/*unset($arr_par);
			$arr_par["appName"] = "ECOMMERCE";
			if( isset($to["To"]) && $to["To"] != "" ){
				$arrTo = explode(",",$to["To"]);
				foreach( $arrTo as $index => $email ){
					if( trim($email) == "" ) continue;
					list( $name, $domain ) = explode("@", $email);
					$arr_par["to[$index][name]"] = preg_replace("[^a-zA-Z0-9 -]", " ", $name);
					$arr_par["to[$index][email]"] = trim($email);
				}
			}
			if( isset($to["Cc"]) && $to["Cc"] != "" ){
				$arrTo = explode(",",$to["Cc"]);
				foreach( $arrTo as $index => $email ){
					if( trim($email) == "" ) continue;
					list( $name, $domain ) = explode("@", $email);
					$arr_par["cc[$index][name]"] = preg_replace("[^a-zA-Z0-9 -]", " ", $name);
					$arr_par["cc[$index][email]"] = trim($email);
				}
			}
			$arr_par["bcc[0][name]"] = "support.it@modena.com";
			$arr_par["bcc[0][email]"] = "support.it@modena.com";
			//$arr_par["to"] = [0 => ["name" => "zaenal", "email" => "zaenal.fanani@modena.com"] ];
			$arr_par["subject"] = $subject;
			$arr_par["body"] = utf8_encode($message);
			$arr_par["isHtml"] = true;
			//if($lampiran != "") $arr_par["attachment"] = new CURLFile($lampiran);
			//print_r($arr_par);exit;
			//echo json_encode($arr_par);exit;
			$ch = curl_init();			
			curl_setopt($ch, CURLOPT_URL, "http://172.16.1.12:30101/mail/send" );
			//curl_setopt($ch, CURLOPT_URL, "http://192.168.3.86:28060/mail/send" );
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $arr_par);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,  $GLOBALS["curl_connection_timeout"]); 
			curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS["curl_timeout"]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$server_output = curl_exec ($ch);
			print_r($server_output);
			return false;
			*/
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
	
	static function dapatkan_list_stiker($order_no, $stickerid){
		$productid = "";
		foreach($GLOBALS["product_custom"] as $key_productid=>$arr_detailproduct){
			if($arr_detailproduct["stickerid"] == $stickerid){
				$productid = $key_productid;
				break;
			}
		}
		if($productid == "") return "";
		
		$string_stiker = "";
		$sql="select * from desain_detail where order_no='".main::formatting_query_string($order_no)."' and product_id = '". main::formatting_query_string($productid) ."';";
		$rs=mysql_query($sql) or die();//("get_order_payment_detail query error.<br />".mysql_error());
		$counter = 1;
		while($data = mysql_fetch_array($rs)){
			$file_ori = preg_replace("/".preg_quote("stick_", "/")."/", "ori_", $data["file_desain"], 1);
			$file_merge = str_replace(".png","_merge.png",$data["file_desain"]);
			$arr_stiker[] = "
			<div style=\"margin-bottom:15px\">
			<img src=\"". $GLOBALS["url_path"] ."/sticker-thumbnail.php?f=". $file_merge ."&c=x\" style=\"height:200px\" /><br />
			<a href=\"". $GLOBALS["url_path"] ."/upload/". $file_ori ."\">Sticker ". $counter  ."</a> | 
			<a href=\"". $GLOBALS["url_path"] ."/upload/". $file_merge ."\">Map Layout ". $counter  ."</a><br />Finishing : ". $data["keterangan"] ."
			</div>";
			$counter++;
		}
		if( is_array($arr_stiker) ) $string_stiker = implode("<br />", $arr_stiker);
		return $string_stiker;
	}
	
}
	

?>