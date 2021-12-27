<?
require_once "cls_discount.ext.php";
class order_online_promo extends main{

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

/*
fungsi read data diskon dari database
*/
	static function discount_list_groupped($parentcategoryid, $datestart="", $dateend="", $theme=""){
		if(func_num_args()==3){
			$s_column=" a.remark_en, a.remark_id ";
			$s_parameter=" and a.datestart='".$datestart."' and a.dateend='".$dateend."' group by a.remark_en, a.remark_id order by a.remark_id";
		}elseif(func_num_args()==4){
			$s_column="b.categoryid, c.name categoryname, b.productid, b.name";
			$s_parameter=" and a.datestart='".$datestart."' and a.dateend='".$dateend."' and (a.remark_id='".$theme."' or a.remark_en='".$theme."') order by c.sortorder, b.sortorder_group,b.sortorder";
		}else{
			$s_column="datestart,date_format(a.datestart, '%d %M %Y') datestart_formatted, 
			dateend,date_format(a.dateend, '%d %M %Y') dateend_formatted";
			$s_parameter="group by datestart, dateend order by datestart desc";
		}
		$sql="select  ".$s_column."
			from promo_per_item a, product b, category c, category d 
			where a.productid=b.productid and b.categoryid=c.categoryid and c.parentcategoryid=d.categoryid and d.categoryid='".main::formatting_query_string($parentcategoryid)."' ";
		$sql.=" and datestart<=current_date and dateend>=current_date ".$s_parameter." ";
		$result=mysql_query($sql) or die();//("discount_list_groupped error.<br />".mysql_error());
		return $result;
	}
	
	static function discount_list_excl_ipc($parentcategoryid="", $datestart="", $dateend="", $sort=""){
		$sql="select 
			a.productid,
			datestart,date_format(a.datestart, '%d %M %Y') datestart_formatted,
			dateend,date_format(a.dateend, '%d %M %Y') dateend_formatted,
			remark_en,
			remark_id,
			remark_opposite_en,
			remark_opposite_id,
			promocode_safe, b.price, b.name, c.name categoryname, c.categoryid, d.name parentcategoryname, d.categoryid parentcategoryid
			from promo_per_item a, product b, category c, category d 
			where a.productid=b.productid and b.categoryid=c.categoryid and c.parentcategoryid=d.categoryid and 
			a.promo_per_item_id not in (select promo_per_item_id from coupon_promo_per_item) ";
		if($parentcategoryid!="")$sql.=" and d.categoryid='".main::formatting_query_string($parentcategoryid)."' ";
		if($datestart!="" && $dateend!="")$sql.=" and a.datestart='".main::formatting_query_string($datestart)."' and a.dateend='".main::formatting_query_string($dateend)."' ";
		$sql.=" and datestart<=current_date and dateend>=current_date order by datestart desc";
		if($sort!="")		$sql.=$sort;
		else 					$sql.=", c.sortorder, b.sortorder_group, b.sortorder";
		$result=mysql_query($sql) or die();//("discount_list error.<br />".mysql_error());
		return $result;		
	}
			
	static function discount_list($parentcategoryid="", $datestart="", $dateend="", $sort="", $free_parameter=""){
		$sql="select 
			a.productid,
			datestart,date_format(a.datestart, '%d %M %Y') datestart_formatted,
			dateend,date_format(a.dateend, '%d %M %Y') dateend_formatted,
			remark_en,
			remark_id,
			remark_opposite_en,
			remark_opposite_id,
			promocode_safe, b.price, b.name, c.name categoryname, c.categoryid, d.name parentcategoryname, d.categoryid parentcategoryid
			from promo_per_item a, product b, category c, category d 
			where a.productid=b.productid and b.categoryid=c.categoryid and c.parentcategoryid=d.categoryid ";
		if($parentcategoryid!="")$sql.=" and d.categoryid='".main::formatting_query_string($parentcategoryid)."' ";
		if($datestart!="" && $dateend!="")$sql.=" and a.datestart='".main::formatting_query_string($datestart)."' and a.dateend='".main::formatting_query_string($dateend)."' ";
		if($free_parameter != "") $sql .= $free_parameter;
		$sql.=" and datestart<=current_date and dateend>=current_date order by datestart desc";
		if($sort!="")		$sql.=$sort;
		else 					$sql.=", c.sortorder, b.sortorder_group, b.sortorder";
		$result=mysql_query($sql) or die();//("discount_list error.<br />".mysql_error());
		return $result;
	}

	static function discount_list_fair($parentcategoryid="", $datestart="", $dateend="", $sort="", $free_parameter=""){
		$sql="select 
			a.productid,
			datestart,date_format(a.datestart, '%d %M %Y') datestart_formatted,
			dateend,date_format(a.dateend, '%d %M %Y') dateend_formatted,
			remark_en,
			remark_id,
			remark_opposite_en,
			remark_opposite_id,
			promocode_safe, b.price, b.name, c.name categoryname, c.categoryid, d.name parentcategoryname, d.categoryid parentcategoryid
			from promo_per_item a, product b, category c, category d 
			where a.productid=b.productid and b.categoryid=c.categoryid and c.parentcategoryid=d.categoryid ";
		if($parentcategoryid!="")$sql.=" and d.categoryid='".main::formatting_query_string($parentcategoryid)."' ";
		if($datestart!="" && $dateend!="")$sql.=" and a.datestart='".main::formatting_query_string($datestart)."' and a.dateend='".main::formatting_query_string($dateend)."' ";
		if($free_parameter != "") $sql .= $free_parameter;
		$sql.=" and datestart<=current_date and dateend>=current_date order by datestart desc";
		if($sort!="")		$sql.=$sort;
		else 					$sql.=", c.sortorder, b.sortorder_group, b.sortorder";

		// echo $sql;exit();
		$result=mysql_query($sql) or die();//("discount_list error.<br />".mysql_error());
		return $result;
	}
	
	static function discount_product_relations($productid){
		$sql="select productid, diskon, min_qty from promo_per_item_related where promo_per_item_id in (select promo_per_item_id from promo_per_item where productid='".$productid."' 
			and datestart<=current_date and dateend>=current_date);";
		$result=mysql_query($sql) or die();//("discount_product_relations error.<br />".mysql_error());
		return $result;
	}
	
	static function discount_product_source_excl_ipc($productid=""){
		$sql="select a.productid, a.promo_per_item_id from promo_per_item a, promo_per_item_related b 
			where a.promo_per_item_id=b.promo_per_item_id ".($productid!=""?"and a.productid='".$productid."' ":"")."
			and a.datestart<=current_date and a.dateend>=current_date 
			and a.promo_per_item_id not in (select promo_per_item_id from coupon_promo_per_item) ";
		$result=mysql_query($sql) or die();//("discount_product_relations error.<br />".mysql_error());
		return $result;		
	}
	
	static function discount_product_source($productid=""){
		$sql="select a.productid, a.promo_per_item_id from promo_per_item a, promo_per_item_related b 
			where a.promo_per_item_id=b.promo_per_item_id ".($productid!=""?"and b.productid='".$productid."' ":"")."
			and a.datestart<=current_date and a.dateend>=current_date;";
		$result=mysql_query($sql) or die();//("discount_product_relations error.<br />".mysql_error());
		return $result;	
	}
	
	static function discount_product_single($productid){
		$sql="select remark_en, remark_id, remark_opposite_en, remark_opposite_id from promo_per_item where productid='".$productid."' 
			and datestart<=current_date and dateend>=current_date;";
		$rs_result=mysql_query($sql) or die();//("discount_product_single error.<br />".mysql_error());
		$dresult=mysql_fetch_array($rs_result);
		$result["remark_en"]=$dresult["remark_en"];
		$result["remark_id"]=$dresult["remark_id"];
		$result["remark_opposite_en"]=$dresult["remark_opposite_en"];
		$result["remark_opposite_id"]=$dresult["remark_opposite_id"];
		return $result;		
	}

/*
fungsi lain-lain
*/		
	private $pc=array(), $datestart=array(), $dateend=array();
	private function get_pc_decker_ini_information(){		
		$ini = parse_ini_file("pc.decker.conf.ini", true);
		for($x=1; $x<=$ini["num_promo"]; $x++){
			$this->pc[$x]=$ini["promo_$x"]["need_pc"];
			$this->datestart[$x]=mktime($ini["datestart_$x"]["hour"], $ini["datestart_$x"]["minute"], $ini["datestart_$x"]["second"], 
				$ini["datestart_$x"]["month"], $ini["datestart_$x"]["day"], $ini["datestart_$x"]["year"]);
			$this->dateend[$x]=mktime($ini["dateend_$x"]["hour"], $ini["dateend_$x"]["minute"], $ini["dateend_$x"]["second"], 
				$ini["dateend_$x"]["month"], $ini["dateend_$x"]["day"], $ini["dateend_$x"]["year"]);
		}
	}

	private static function between_interval_date($datetime_start, $datetime_end){
		$today=mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
		$interval_start = $today-$datetime_start;
		$interval_end= $datetime_end-$today;
		if($interval_start>=0 && $interval_end>=0) 	return true;
		else																	return false;
	}
	
	private static function check_quantity_order($arr_product){
		$counter=0;
		foreach($arr_product as $product=>$quantity) $counter+=$quantity;
		return $counter;
	}

/*
fungsi check data di tabel discount_coupon
*/
	private static function check_ipc_limitation($ipc){
		$sql="select unlimited from discount_coupon where coupon_code='".main::formatting_query_string($ipc)."' and enabled='1' and now()<expired_date;";
		$rs=mysql_query($sql) or die();
		if(mysql_num_rows($rs)>0){
			$d=mysql_fetch_array($rs);
			$sql="select 1 from ordercustomer where coupon_code='".main::formatting_query_string($ipc)."' and order_status>'0';";
			$rsx=mysql_query($sql) or die();
			if(mysql_num_rows($rsx)>0 && $d["unlimited"]=="0")		return false;	
			else																						return true;
		}else return false;
	}

	static function check_ipc($ipc, $mode = ""){
		if(!order_online_promo::check_ipc_limitation($ipc)) 	return false;		
		$sql="select discount, coupon_mekanisme from discount_coupon where coupon_code='".main::formatting_query_string($ipc)."' and now()<expired_date;";
		$rs=mysql_query($sql) or die();
		$d=mysql_fetch_array($rs);
		if(mysql_num_rows($rs)>0){
			list($nama_mekanisme, $arr_parameter) = explode("|", $d["coupon_mekanisme"]);
			if( $nama_mekanisme != "" && file_exists( __DIR__ . "/cp/" . $nama_mekanisme . ".php" ) ){
				if($mode != "") return $nama_mekanisme;
				include_once __DIR__ . "/cp/" . $nama_mekanisme . ".php";
				return call_user_func ( $nama_mekanisme, $arr_parameter );
			}else
				return $d["discount"];
		}		
		else											return false;
	}
	
	private static function get_ipc_constraint($ipc){
		$sql="select min_qty, min_sales, qty_quota from discount_coupon where coupon_code='".main::formatting_query_string($ipc)."';";
		$rs=mysql_query($sql) or die();
		$d=mysql_fetch_array($rs);
		if(mysql_num_rows($rs)>0)		return array($d["min_qty"], 	$d["min_sales"], $d["qty_quota"]);
		else											return array(0, 						0);
	}
	
	private static function get_ipc_quota_usage($ipc){
	    $ipc = trim($ipc);
		$sql = "select b.product_id, sum(quantity) quantity from ordercustomer a inner join 
			(
				select product_id, order_id, sum(quantity) quantity from orderproduct group by product_id, order_id
			) b on a.order_id = b.order_id
			inner join discount_coupon_usage_per_item c on b.order_id = c.order_id and b.product_id = c.product_id and left(a.coupon_code, length('". main::formatting_query_string($ipc) ."')) = c.coupon_code
			where	left(a.coupon_code, length('". main::formatting_query_string($ipc) ."')) = '". main::formatting_query_string($ipc) ."' and 
				(
				    (a.order_status = 0 and TIMESTAMPDIFF(MINUTE, a.order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+15 and a.memberid not in (select memberid from membersdata where email='". main::formatting_query_string($_SESSION["email"]) ."')) 
				    or a.order_status >= 1
				    or (a.order_status = -1 and TIMESTAMPDIFF(MINUTE, a.order_date, CURRENT_TIMESTAMP) < ". $GLOBALS["payment_va_expiration"] ."+15)
				)
				 group by b.product_id";
		$rs=mysql_query($sql) or die();
		$return = array();
		if(mysql_num_rows($rs)>0){
			while($data = mysql_fetch_array($rs)){
				$return[ $data["product_id"] ] = $data["quantity"];
			}
		} 
		return $return;
	}
	
	private static function compare_sales_ipc_constraint($arr_product, $grand_total, $ipc){
		$return=true;
		$constraint=order_online_promo::get_ipc_constraint($ipc);
		$arr_val=array(order_online_promo::check_quantity_order($arr_product),$grand_total);
		for($x=0; $x<count($constraint); $x++)
				if($constraint[$x]!="" && $constraint[$x]>0 && $constraint[$x]>$arr_val[$x])	$return=false;
		return $return;
	}
	
	static function is_ipc_all_item($ipc){
		$sql="select 1 from discount_coupon a, coupon_promo_per_item b, promo_per_item c 
			where a.coupon_id=b.coupon_id and b.promo_per_item_id=c.promo_per_item_id and current_date between c.datestart and c.dateend
			and a.coupon_code='".main::formatting_query_string($ipc)."' and b.aktif='1';";
		$rs=mysql_query($sql) or die();
		if(mysql_num_rows($rs)>0)		return false;
		else											return true;		
	}
	
	private static function check_ipc_per_item_list($rs, $ipc){
		$return=false;
		while($d=mysql_fetch_array($rs)){
			$sql="select 1 from discount_coupon where coupon_id='".main::formatting_query_string($d["coupon_id"])."' and coupon_code='".main::formatting_query_string($ipc)."';";
			$rsx=mysql_query($sql) or die();
			if(mysql_num_rows($rsx)>0)		{$return=true;}
		}
		return $return;
	}
	
	static function isnt_ipc_per_item($productid){
		$sql="select a.coupon_id, a.promo_per_item_id, b.promocode_safe from coupon_promo_per_item a, promo_per_item b where a.promo_per_item_id=b.promo_per_item_id and a.aktif='1' and
			(b.productid='".main::formatting_query_string($productid)."' 
				or b.promo_per_item_id in(select promo_per_item_id from promo_per_item_related where promo_per_item_id=b.promo_per_item_id and productid='".main::formatting_query_string($productid)."'))
			and coupon_id in (select coupon_id from discount_coupon)
			and current_date between b.datestart and b.dateend;";
		$rs=mysql_query($sql) or die();
		return $rs;
/*		if(mysql_num_rows($rs)>0)		return false;
		else 											return true;
*/	
	}
	
	static function check_ipc_per_item($productid, $ipc=""){		
/*		$sql="select coupon_id from coupon_promo_per_item a, promo_per_item b where a.promo_per_item_id=b.promo_per_item_id and a.aktif='1' and
			(b.productid='".main::formatting_query_string($productid)."' 
				or b.promo_per_item_id in(select promo_per_item_id from promo_per_item_related where promo_per_item_id=b.promo_per_item_id and productid='".main::formatting_query_string($productid)."'))
			and coupon_id in (select coupon_id from discount_coupon);";
		$rs=mysql_query($sql) or die();
		if(mysql_num_rows($rs)>0){*/
		$rs=order_online_promo::isnt_ipc_per_item($productid);
		if(mysql_num_rows($rs)>0){
			if(!order_online_promo::check_ipc_limitation($ipc)) return false;
			if($ipc=="") return false;
			// cek kuota
			$quota = mysql_fetch_array(mysql_query("select qty_quota from discount_coupon where coupon_code = '". main::formatting_query_string($ipc) ."'"));
			$arr_quota_usage = self::get_ipc_quota_usage($ipc);
			if( $quota["qty_quota"] < @$arr_quota_usage[$productid] + $_SESSION["shopping_cart"][$productid] ) return false;
			return order_online_promo::check_ipc_per_item_list($rs, $ipc);
		}else return true; // promo_per_item yang tidak membutuhkan PC
	}

/*
fungsi diskon ALL ITEM
*/	
	private static function is_valid_date($ipc=""){
		$opc=new order_online_promo;
		$opc->get_pc_decker_ini_information();	
		
		//cek promo all item dengan PC dulu
		$cipc=order_online_promo::check_ipc($ipc);
		if($cipc===false)	goto SkipPC;
		foreach($opc->pc as $type=>$need_pc)			
			if(order_online_promo::between_interval_date($opc->datestart[$type], $opc->dateend[$type]) && $need_pc==1)
				 return abs($cipc);
				 
		SkipPC:
		//cek promo all item tanpa PC		
		foreach($opc->pc as $type=>$need_pc){
			if(order_online_promo::between_interval_date($opc->datestart[$type], $opc->dateend[$type]) && $need_pc==0)
				return $type;
		}
		return false;		
	}
				
	public static function price_off($arr_product, $grand_total, $v_mode=""/*disc_grand_total | disc | %disc*/, $ipc=""){
		$discount=0;		
		if(!order_online_promo::is_ipc_all_item($ipc))		goto Skip; //cek promo_per_item		
		
		$promo_all_item=order_online_promo::is_valid_date($ipc);
		if(!$promo_all_item)														goto Skip;

		$opc=new order_online_promo;
		$opc->get_pc_decker_ini_information();	
		$type=order_online_promo::check_ipc($ipc);
		$passc=order_online_promo::compare_sales_ipc_constraint($arr_product, $grand_total, $ipc);
		if($opc->pc[$promo_all_item]==1 && $type!==false && $passc)	goto C2;
		elseif($opc->pc[$promo_all_item]==0)											goto C1; //mekanisme tanpa PC
		else																									goto Skip;
		
		C2:// mekanisme utk promo all item dengan PC, sesuaikan dengan abs(type) sebagai tipe diskon
		if(abs($type)==2){
		
			$od=new order_online_promo_ext;
			$od->arr_product=$arr_product;
			$od->grand_total=$grand_total;
			$od->v_mode=$v_mode;
			$od->ipc=$ipc;
			$discount=$od->calculate_discount();
/*			if(order_online_promo::check_quantity_order($arr_product)==1)
				$discount=500000;
			elseif(order_online_promo::check_quantity_order($arr_product)==2)
				$discount=1000000;
			elseif(order_online_promo::check_quantity_order($arr_product)>=3)
				$discount=1500000;*/
				
		}
		goto Skip;
		
		C1:// mekanisme utk promo all item tanpa PC
		$discount=0;
		goto Skip;
			
		Skip:
		if($discount<=1){
			$disc_gt=$grand_total-($grand_total*$discount);
			$disc=($grand_total*$discount);
		}else{
			$disc_gt=$grand_total-$discount;
			$disc=$discount;
		}

		if($v_mode=="disc_gt")
			return $disc_gt;
		elseif($v_mode=="disc")
			return $disc;
		else
			return $discount;
	}
	
	public static function thediscount($detail=false, $ipc=""){
		if(!order_online_promo::is_ipc_all_item($ipc)) return;
		
		$promo_all_item=order_online_promo::is_valid_date($ipc);
		if(!$promo_all_item){}//tidak ada promo
		else{
			if($promo_all_item==1){//mekanisme tanpa PC
				if($detail)
					return file_exists("lang/".$GLOBALS["lang"]."_discount_note.html")?
						file_get_contents("lang/".$GLOBALS["lang"]."_discount_note.html"):
						"";
				else{
					$sales_amount=order_online_promo::price_off($_SESSION["shopping_cart"],$GLOBALS["subtotal"],"disc_gt", @$_REQUEST["t_discount_coupon"]);
					$arr_gift=array(
						array("min"=>2000000, "max"=>2999999, "gift"=>"free gift DOMO IRONER DN 1001 (X'MAS DIRECT GIFT)"),
						array("min"=>3000000, "max"=>3999999, "gift"=>"free gift DOMO IRONER DN 1200 (X'MAS DIRECT GIFT)"),
						array("min"=>4000000, "max"=>4999999, "gift"=>"free gift DOMO BLENDER DB 1701 (X'MAS DIRECT GIFT)"),
						array("min"=>5000000, "max"=>5999999, "gift"=>"get free gift DOMO JUICER DJ 3032 (X'MAS DIRECT GIFT)"),
						array("min"=>60000000, "max"=>6999999, "gift"=>"get free gift DOMO BLENDER DB 1502 (X'MAS DIRECT GIFT)"),
						array("min"=>7000000, "max"=>0, "gift"=>"free gift DOMO BLENDER DB 1502 + DOMO IRONER DN 1200 (X'MAS DIRECT GIFT)")
					);
					foreach($arr_gift as $gift)
						if	(
								($sales_amount>=$gift["min"] && $sales_amount<=$gift["max"]) ||
								$sales_amount>=$gift["min"] && $gift["max"]==0
							) return $gift["gift"];
				}
			}elseif($promo_all_item==2){			
				if($detail)
					return "<div style=\"margin:0px\"><strong>ONLINE PURCHASE PROMO (Valid 2-30 September 2013):</strong> 
						<ul>
							<li>Buy 2 get 50% off, on second item</li>
						</ul></div>";
				else	return "Online purchase promo [".$ipc."]";				
			}
		}
	}
	
/*
fungsi diskon PER ITEM
*/
	static function check_product_in_shoppingcart($productid, $quantity, $promo_per_item_id, $mode=""){
		include_once "ext/harbolnas2016.php";
		$ekstensi_perhitungan_diskon = ekstensi_perhitungan_diskon($productid);
		if( $ekstensi_perhitungan_diskon != 0 ) return $ekstensi_perhitungan_diskon;
		
		$sql="select * from promo_per_item_related where promo_per_item_id='".main::formatting_query_string($promo_per_item_id)."' 
			and productid=".main::formatting_query_string($productid).";";
		$rs=mysql_query($sql) or die();//("check_product_in_shoppingcart query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0){
			$diskon_produk=mysql_fetch_array($rs);
			if($mode==""){				
				if($diskon_produk["min_qty"]<=$quantity)
					return $diskon_produk["diskon"];
				else return 0;
			}else{
				$sql="select 1 from promo_per_item a where a.promo_per_item_id='".main::formatting_query_string($promo_per_item_id)."' 
					and a.productid='".main::formatting_query_string($productid)."' and not exists(select 1 from promo_per_item_related where promo_per_item_id=a.promo_per_item_id and productid<>a.productid);";
				$rs=mysql_query($sql) or die();//("check_product_in_shoppingcart query error.<br />".mysql_error());
				if(mysql_num_rows($rs)<=0)
					return 0;
				else
					return $diskon_produk["diskon"];
			}
		}else return 0;
	}
	
	static function calc_disc_peritem($productid, $price, $quantity, $promo_per_item_id){
		$diskon=order_online_promo::check_product_in_shoppingcart($productid, $quantity, $promo_per_item_id);
		if($diskon>0)
			return $diskon;
		else{
			return 0;
		}
	}
	
	static function maximum_productpromo_qty($productid){
		$sql="select a.productid productid_disc_src
		 	from promo_per_item a, promo_per_item_related b where a.promo_per_item_id=b.promo_per_item_id and 
			(a.productid='".main::formatting_query_string($productid)."' or b.productid='".main::formatting_query_string($productid)."') and
			current_date between a.datestart and a.dateend order by a.datestart desc;";
		$rs=mysql_query($sql) or die();//("maximum_productpromo_qty query error.<br />".mysql_error());
		$maxproduct_qty=0; 
		while($s=mysql_fetch_array($rs))
				if(@array_key_exists($s["productid_disc_src"], @$_SESSION["shopping_cart"]) && $s["productid_disc_src"]!=$productid){
					$maxproduct_qty+=$_SESSION["shopping_cart"][$s["productid_disc_src"]];
				}
		return $maxproduct_qty;
	}
	
	static function remark_disc_peritem($productid, $mode=""){
		$sql_parameter = "";
		//if( @$_REQUEST["t_discount_coupon"] != "" ) 
		//	$sql_parameter = " and a.promo_per_item_id in (select y.promo_per_item_id from discount_coupon x inner join coupon_promo_per_item y on x.coupon_id = y.coupon_id and x.coupon_code = '". main::formatting_query_string( $_REQUEST["t_discount_coupon"] ) ."' )";
		$sql="select a.promo_per_item_id, a.productid productid_disc_src, a.remark_en, a.remark_id, a.remark_opposite_en, a.remark_opposite_id, a.promocode_safe,
			b.productid, b.min_qty, b.diskon
		 	from promo_per_item a, promo_per_item_related b where a.promo_per_item_id=b.promo_per_item_id and 
			(a.productid='".main::formatting_query_string($productid)."' or b.productid='".main::formatting_query_string($productid)."') and
			current_date between a.datestart and a.dateend ". $sql_parameter ." order by a.datestart desc;";
		$rs=mysql_query($sql) or die();//("remark_disc_peritem query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0){			
			while($s=mysql_fetch_array($rs)){
				if((@array_key_exists($s["productid_disc_src"], @$_SESSION["shopping_cart"]) || $mode!="") && $s["productid_disc_src"]==$productid){
					$ret[0]=$s["promo_per_item_id"];
					$ret[1]=$s["remark_".$GLOBALS["lang"]];
					$ret[2]=$s["promocode_safe"];
					$ret[3]=$s["productid_disc_src"];			
					$ret[4]=$s["productid"];
					$ret[5]=$s["diskon"];
					if($mode=="" && $s["diskon"]==1)$ret[4]=$s["productid"];
				}else{
				
					if($productid==$s["productid"]){
						if(@array_key_exists($s["productid_disc_src"], @$_SESSION["shopping_cart"]) && @array_key_exists($s["productid"], @$_SESSION["shopping_cart"])
							&& @$_SESSION["shopping_cart"][$s["productid_disc_src"]]>0 && @$_SESSION["shopping_cart"][$s["productid"]]>0 ){
							if($s["diskon"]==1){
								$ret[1]=	"FREE";
								$break=true;
								goto skip;								
							}else{
								$ret[0]=$s["promo_per_item_id"];
								$ret[1]="&nbsp;";//$s["remark_".$GLOBALS["lang"]];
								$ret[2]=$s["promocode_safe"];
								$ret[3]=$s["productid_disc_src"];										
							}
						}elseif(@array_key_exists($s["productid"], @$_SESSION["shopping_cart"]) && @!array_key_exists($s["productid_disc_src"], @$_SESSION["shopping_cart"])){
							$rs_productsrc=order_online_promo::discount_product_source($s["productid"]);				
							while($productsrc=mysql_fetch_array($rs_productsrc)){							
								if(@array_key_exists($productsrc["productid"], @$_SESSION["shopping_cart"])){$ret[1]="&nbsp;";break;}
								else	$ret[1]=	$s["remark_opposite_".$GLOBALS["lang"]];
							}
						}
					}elseif($productid==$s["productid_disc_src"])
							$ret[1]=	$s["remark_".$GLOBALS["lang"]];					

					
					skip:
					$ret[3]=$s["productid"];
					$ret[4]=$s["productid_disc_src"];			
					$ret[5]=$s["diskon"];
					if(isset($break))break;
				}
			}return $ret ;
		}else return "";			
	}

	private 
		$disc_gt_peritem=0, $disc_peritem="", 
		$discount_peritem=0, $is_promocode_safe=false, 
		$product_relation_1="", $product_relation_2="", $promo_per_item_id="";
		
	function disc_gt_peritem(){
		return $this->disc_gt_peritem;
	}
	
	function disc_peritem(){
		return $this->disc_peritem;
	}
	
	function discount_peritem(){
		return $this->discount_peritem;
	}
	
	function is_promocode_safe(){
		return $this->is_promocode_safe;
	}
	
	function product_relation($relation){
		switch($relation){
		case 1:		return $this->product_relation_1;
		case 2:		return $this->product_relation_2;
		}
	}
	
	function promo_per_item_id(){
		return $this->promo_per_item_id;
	}
	
	function price_off_peritem($productid, $price, $quantity, $mode="", $ipc=""){
		$cipc=order_online_promo::check_ipc_per_item($productid, $ipc);
		if(!$cipc){
			$this->disc_gt_peritem=$price*$quantity;
			
			$this->disc_peritem="";
	
			$this->discount_peritem=0;
			
			$this->is_promocode_safe=true;
	
			$this->product_relation_1="";
			$this->product_relation_2="";
			
			$this->promo_per_item_id="";
			return;
		}else{
			$is_promocode_safe=true;
		}

		$discount_remark=order_online_promo::remark_disc_peritem($productid, $mode);
		$discount=0;
		if(is_array($discount_remark) && @$discount_remark[1]!="")			$discount=order_online_promo::calc_disc_peritem($productid, $price, $quantity, @$discount_remark[0]);

		if( $discount>0 & $ipc != "" ){
			$sql = "insert into discount_coupon_usage_per_item (order_id, product_id, coupon_code)
				select a.order_id,
					'". main::formatting_query_string($productid) ."', '". main::formatting_query_string($ipc) ."'
				from ordercustomer a where 
				order_no = '". main::formatting_query_string($_SESSION["order_no"]) ."' 
				and not exists(select 1 from discount_coupon_usage_per_item where order_id = a.order_id and product_id = '". main::formatting_query_string($productid) ."')";
			mysql_query($sql);
		}
		
		$disc=($price*$discount);
		$disc_gt=($price-$disc)*$quantity;				

		$this->disc_gt_peritem=$disc_gt;
		
		$this->disc_peritem=(@$discount_remark[1]!="" && $discount_remark[3]==$productid?	$discount_remark[1]	:	"");

		$this->discount_peritem=$discount;
		
		$this->is_promocode_safe=isset($is_promocode_safe)?$is_promocode_safe:@$discount_remark[2];

		$this->product_relation_1=@$discount_remark[3];
		$this->product_relation_2=@$discount_remark[4];
		
		$this->promo_per_item_id=@$discount_remark[0];
	}

/*
fungsi trade-in
*/
	static function get_trade_in($productid){
		$sql="select discount from tradein
			where enabled='1' and productid='".main::formatting_query_string($productid)."';";
		$result=mysql_query($sql) or die();//("get_trade_in error.<br />".mysql_error());
		if(mysql_num_rows($result)>0){
			$discount=mysql_fetch_array($result);
			$return=$discount["discount"];
		}else $return=0;
		return $return;
	}
	
	static function get_trade_in_mekanisme_baru($t_discount_coupon, $arr_product, $arr_tradein_category, $arr_tradein_kuota, $arr_tradein_pemakaian_kuota){
		//return 0;
		// override perhitungan trade-in, sesuai program baru per Juni 2020.. dengan membaca dari mekanisme kode voucher
		$rs_diskon = self::tradein_data_registrasi( $t_discount_coupon );
		if( mysql_num_rows($rs_diskon) <= 0 ) return 0;
		$diskon = mysql_fetch_array( $rs_diskon );
		list($coupon_mekanisme, $persen_diskon) = explode("|", $diskon["coupon_mekanisme"]);
		include_once "cp/". $coupon_mekanisme .".php";		
		return call_user_func( $coupon_mekanisme, $persen_diskon, $arr_product, $arr_tradein_category, $arr_tradein_kuota, $arr_tradein_pemakaian_kuota );
	}
	
	static function isvalid_tradein_location( $state_id, $region_id=""){
		/* tradein khusus utk jabodetabek */
		if($state_id=="13" or in_array($region_id, array("244","246","250","251","267","268","271"))) 	return true;
		else					return false;
		/* tradein utk cabang  */
		if(func_num_args()>1)
			$sql="select 1 from branch a, membersshipping b where a.state_id=b.shipping_state and a.region_id=b.shipping_region 
				and b.shipping_state='".main::formatting_query_string($state_id)."' and b.shipping_region='".main::formatting_query_string($region_id)."';";
		else
			$sql="select 1 from branch where state_id='".main::formatting_query_string($state_id)."' and region_id='".main::formatting_query_string($region_id)."';";
		$result=mysql_query($sql) or die();//("get_tradein_location error.<br />".mysql_error());
		if(mysql_num_rows($result)>0) 	return true;
		else 												return false;
	}
	
	static function tradein_data_registrasi( $coupon_code ){
		$sql = "select a.*, b.*, c.*, d.*
		from tradein_konsumen a inner join tradein_produk b on a.tradein_regid = b.tradein_regid
		inner join discount_coupon c on a.tradein_coupon_code = c.coupon_code 
		inner join tradein_matrix d on b.tradein_categoryid = d.tradein_from_categoryid
		where c.expired_date > CURRENT_TIMESTAMP and c.enabled = 1 and a.tradein_coupon_code = '". main::formatting_query_string($coupon_code) ."'";
		$rs_tradein_registrasi = mysql_query($sql) or die();
		return $rs_tradein_registrasi;
	}
	
	static function tradein_matrix( $tradein_from_categoryid ){
		$sql = "select a.*, b.name tradein_from_category, c.name tradein_to_category 
		from tradein_matrix a inner join category b on a.tradein_from_categoryid = b.categoryid 
		inner join category c on a.tradein_to_categoryid = c.categoryid
		where a.tradein_from_categoryid = '". main::formatting_query_string($tradein_from_categoryid) ."'";
		$rs_tradein_matrix = mysql_query($sql) or die();
		return $rs_tradein_matrix;
	}
	
}

?>