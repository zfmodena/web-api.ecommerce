<?

class product extends main{
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
	
	public function product_category(){
		/* parameter : $arr_par */
		$arr_par=array("categoryid", "parentcategoryid", "name", "enabled", "description", "sortorder");
		$sql="select ".$this->string_from_array($arr_par, "value", ",")." from category";
		foreach($arr_par as $value){			
			if(isset($this->data[$value])){
				if(!isset($where)){$sql.=" where ";$where="set";}
				$sql.=$value."=".main::formatting_query_string($this->data[$value])." and ";
			}
		}
		$sql=substr($sql, 0, strlen($sql)-4);
		if(isset($this->data["orderby"]))$sql.=" order by ".main::formatting_query_string($this->data["orderby"]);
		$result=mysql_query($sql) or die();//("product_category query error.<br />".mysql_error());
		return $result;
	}
	
	public function product_misc($categoryid){
		/* parameter : spec_id_parent, enabled, orderby */
		$sql="select a.spec_id, /*a.spec_name_".$this->lang."*/ a.spec_name_en, a.spec_name_id 
			from misc_spec a, misc_spec_category b where a.spec_id=b.spec_id and b.category_id='".main::formatting_query_string($categoryid)."' ";
		if(isset($this->data["spec_id_parent"]))$sql.=" and a.spec_id_parent ".$this->data["spec_id_parent"];
		if(isset($this->data["enabled"]))$sql.=" and b.enabled ".$this->data["enabled"];
		if(isset($this->data["orderby"]))$sql.=" order by ".main::formatting_query_string($this->data["orderby"]);
		$result=mysql_query($sql) or die();//("product_misc query error.<br />".mysql_error());
		return $result;
	}
	
	private static function is_assoc($array) {
		return (bool)count(array_filter(array_keys($array), 'is_string'));
	}
	
	private static function remove_character($string, $arr_remove){
		if(product::is_assoc($arr_remove))
			foreach ($arr_remove as $remove=>$removed)		$string=str_replace($remove, $removed, $string);
		else
			foreach($arr_remove as $remove) 				$string=str_replace($remove, "", $string);
		return $string;
	}
	
	public function product_list($categoryid, $table_target_of_product=""){
		/* paramater : distinct, lang, spec_id, productid, penable, orderby, product_price */
		if(isset($this->data["distinct"]))$sql="select distinct d.sortorder sortorder_category, a.productid, a.sortorder, a.sortorder_group, a.categoryid, a.name, a.description, a.isnewproduct, a.price, a.penable, a.weight, d.parentcategoryid, e.categoryid parent_parentcategoryid, d.name parentcategoryname, e.name parent_parentcategoryname, f.kode, replace(replace(f.kode, '-', ''), '/', '') kode_tanpaformat";
		else $sql="select a.productid, a.sortorder, a.sortorder_group, a.categoryid, a.name, a.description, a.isnewproduct, a.price, a.penable, a.weight, d.parentcategoryid, e.categoryid parent_parentcategoryid, d.name parentcategoryname, e.name parent_parentcategoryname,
			c.spec_id_parent, c.spec_id, /*c.spec_name_".(isset($this->data["lang"])?$this->data["lang"]:"")."*/ c.spec_name_en, c.spec_name_id";
		$sql.=" from product".$table_target_of_product." a left outer join misc_spec_product b on a.productid=b.product_id 
			left outer join misc_spec c on b.spec_id=c.spec_id 
			inner join category d on a.categoryid=d.categoryid
			left outer join category e on d.parentcategoryid=e.categoryid
			left outer join ( select max(productid) productid, kode from __kode_produk_accpac where kode != '' group by kode ) f on a.productid = f.productid
			where a.categoryid like ".$categoryid." ";
		if(isset($this->data["spec_id"])){
			foreach($this->data["spec_id"] as $key=>$value){
				$arr_value=preg_split("/,/", $value);
				$sql.=" and (";
				foreach($arr_value as $arr_value_value){
					$sql.="a.productid in (select product_id from misc_spec_product where spec_id=".$arr_value_value." and enabled=true) or ";
				}
				$sql=substr($sql,0,strlen($sql)-3).") ";
			}
		}
		if(isset($this->data["product_price"]))$sql.=" and ".$this->data["product_price"];
		if(isset($this->data["penable"]))$sql.=" and a.penable ".$this->data["penable"];
		if(isset($this->data["productid"]))$sql.=" and a.productid ".$this->data["productid"];
		
		if(isset($this->data["categoryid"]))$sql.=" and a.categoryid = '". main::formatting_query_string($this->data["categoryid"]) . "'";
		if(isset($this->data["parent_categoryid"]))$sql.=" and d.parentcategoryid = '". main::formatting_query_string($this->data["parent_categoryid"]) . "'";
		
		/* PRE-ORDER PRODUCT - INDEN (BIT) : true | false */
		if(isset($this->data["preorder_product"])){			
			if($this->data["preorder_product"])		$sql.=" and a.isnewproduct ='1'";
			else									$sql.=" and a.isnewproduct ='0'";
		}else{
			/*$sql.=	in_array(	$GLOBALS["page"], 
								array("category.php", "category_productdetail.php")
							)?
						" and (a.isnewproduct ='0' or a.isnewproduct is null) ":"";*/
		}
		
		//pencarian
		if(isset($this->data["search_name"]))$sql.=" and a.name like ".$this->data["search_name"];

		if(isset($this->data["orderby"]) && isset($this->data["productid"])){
			$arr_productid=explode(",", product::remove_character($this->data["productid"], array("in", "(", ")", " ")));
			if(trim($this->data["orderby"])=="name" && count(@$arr_productid)>0)
				$sql.=" order by field(productid, ".implode(",", $arr_productid).") ";
			else
				$sql.=" order by ".main::formatting_query_string($this->data["orderby"]);
		}elseif(isset($this->data["orderby"])) $sql.=" order by ".main::formatting_query_string($this->data["orderby"]);
		$result=mysql_query($sql) or die();//("product query error.<br />".mysql_error());
		return $result;		
	}
	
	public function product_spec_parent($categoryid){
		$sql="select b.productpropertyschemaid, b.name_en, b.name_id, b.multiple from category_productpropertyschema a, productpropertyschema b where a.productpropertyschemaid=b.productpropertyschemaid and
			categoryid=".$categoryid." and a.productpropertyschemaid in (select productpropertyschemaid from productpropertyschema 
			where parent_productpropertyschemaid is null)";
		$sql.=" order by a.sortorder;";
		$result=mysql_query($sql) or die();//("product_spec_parent query error.<br />".mysql_error());
		return $result;
	}
	
	public function product_spec_detail($categoryid, $productid, $parent_productpropertyschemaid){
		$sql="select b.name_en, b.name_id, b.multiple, replace(a.value_en, '%2B', '+') value_en, replace(a.value_id, '%2B', '+') value_id, c.sortorder
			from productproperty a, productpropertyschema b, category_productpropertyschema c 
			where a.productpropertyschemaid=b.productpropertyschemaid and b.productpropertyschemaid=c.productpropertyschemaid and
			a.productid=".$productid." and c.categoryid=".$categoryid." and
			b.parent_productpropertyschemaid=".$parent_productpropertyschemaid."";
		$sql.=" order by c.sortorder;";
		$result=mysql_query($sql) or die();//("product_spec_detail query error.<br />".mysql_error());
		return $result;		
	}
	
	public function product_spec($productid){
		/* parameter : productpropertyschemaid, lang */
		$sql="select a.productid, a.productpropertyschemaid, /*a.value_".$this->lang.", b.name_".$this->lang." */ 
			case when ifnull(a.value_en,'')='' then '-' when a.value_en='' then '-' else replace(a.value_en, '%2B', '+') end value_en, 
			case when ifnull(a.value_id,'')='' then '-' when a.value_id='' then '-' else replace(a.value_id, '%2B', '+') end value_id, 
			b.name_en, b.name_id, b.multiple
			from productproperty a left outer join productpropertyschema b on a.productpropertyschemaid=b.productpropertyschemaid
			inner join product c on a.productid=c.productid
			left outer /*inner*/ join category_productpropertyschema d on c.categoryid=d.categoryid and b.productpropertyschemaid=d.productpropertyschemaid
			where a.productid=".main::formatting_query_string($productid)." ";
		if(isset($this->data["productpropertyschemaid"]))$sql.=" and a.productpropertyschemaid ".$this->data["productpropertyschemaid"];
		$sql.=" order by d.sortorder;";
		$result=mysql_query($sql) or die();//("product_spec query error.<br />".mysql_error());
		return $result;
	}
	
	public function category_spec($categoryid){
		/* parameter : lang, orderby */
		$sql="select a.categoryid, a.productpropertyschemaid, a.sortorder, b.parent_productpropertyschemaid, /*b.name_".$this->lang."*/ b.name_en, b.name_id, b.multiple
			from category_productpropertyschema a inner join productpropertyschema b on a.productpropertyschemaid=b.productpropertyschemaid
			where categoryid like ".main::formatting_query_string($categoryid)." ";
		if(isset($this->data["parent_productpropertyschemaid"]))$sql.=" and parent_productpropertyschemaid=".main::formatting_query_string($this->data["parent_productpropertyschemaid"]);	
		else $sql.=" and parent_productpropertyschemaid is null ";	
		if(isset($this->data["orderby"]))$sql.=" order by ".main::formatting_query_string($this->data["orderby"]);
		$result=mysql_query($sql) or die();//("category_spec query error.<br />".mysql_error());
		return $result;	
	}
	
	public static function check_product_misc_spec_exists($productid, $spec_id, $enabled){
		$sql="select 1 from misc_spec_product where product_id='".main::formatting_query_string($productid)."' and spec_id='".main::formatting_query_string($spec_id)."' 
			and enabled=".main::formatting_query_string($enabled).";";
		$rs=mysql_query($sql) or die();//("check_product_misc_spec_exists query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0)return true;
		else return false;
	}
	
// PRODUCT CODE	
	public static function product_serialnumber($productid){		
		$sql="select productid, serialnumberid, serialnumber, active from serialnumber where productid='".main::formatting_query_string($productid)."';";
		$rs=mysql_query($sql) or die();//("product_serialnumber query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0){
			while($rs_=mysql_fetch_array($rs))@$return.=$rs_["serialnumber"].",";
			return substr($return,0,strlen($return)-1);
		}else return "";
	}
	
	public static function product_kodeaccpac($productid){
		$sql="select productid, kode from __kode_produk_accpac where productid='".main::formatting_query_string($productid)."';";
		$rs=mysql_query($sql) or die();//("product_kodeaccpac query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0){
			while($rs_=mysql_fetch_array($rs))@$return.=$rs_["kode"].",";
			return substr($return,0,strlen($return)-1);		
		}else return "";
	}
	
// PRODUCT INVENTORY
	public static function last_stock($productid, $state_id="", $region_id=""){
		$sql="select date_format(a.maxdate,'%d %M %Y') maxdate, b.productid, b.quantity from 
			(select max(date) maxdate, productid, state_id, region_id from productinventory where type='1' group by productid, state_id, region_id) a, productinventory b 
			where a.productid=b.productid and a.state_id=b.state_id and a.region_id=b.region_id and a.maxdate=b.date and  
			b.type='1' and b.productid='".main::formatting_query_string($productid)."'
			and a.state_id='".$state_id."' and a.region_id='".$region_id."';";
		$rs=mysql_query($sql) or die();//("last_stock query error.<br />".mysql_error());
		return mysql_fetch_array($rs);
	}
	
	public static function sum_quantity_purchased($productid, $state_id="", $region_id=""){
		$sql="select sum(a.quantity) quantity_purchased, a.productid from productinventory a where 
			date>=(select max(date) from productinventory where productid=a.productid and state_id=a.state_id and region_id=a.region_id and type='1') 
			and a.type='0' and a.productid='".main::formatting_query_string($productid)."' and a.state_id='".$state_id."' and a.region_id='".$region_id."';";
		$rs=mysql_query($sql) or die();//("sum_quantity_purchased query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0){
			$rs_=mysql_fetch_array($rs);
			if($rs_["quantity_purchased"]!="")return $rs_["quantity_purchased"];
			else return 0;
		}else return 0;
	}
	
	public static function __insert_product_inventory($productid, $value){
		$sql="select 1 from productinventory a where 
			date>=(select max(date) from productinventory where productid =a.productid and type='1') and a.type='0' and a.productid='".main::formatting_query_string($productid)."';";
		if(mysql_num_rows(mysql_query($sql))<=0){// check apa sudah ada penjualan ato belum. klo blm ada penjualan, update stok lama
			$sql="select 1 from productinventory where productid='".$productid."';";
			if(mysql_num_rows(mysql_query($sql))<=0) $sql="insert into productinventory(productid, quantity, type) 
				values('".main::formatting_query_string($productid)."', '".main::formatting_query_string($value)."', '1');";
			else	$sql="update productinventory a, (select max(date) maxdate, productid from productinventory where type='1' group by productid) b 
				set a.quantity='".main::formatting_query_string($value)."' 
				where a.type='1' and a.productid=b.productid and a.date=b.maxdate and a.productid='".main::formatting_query_string($productid)."';";
		}else	$sql="insert into productinventory(productid, quantity, type) values('".main::formatting_query_string($productid)."', '".main::formatting_query_string($value)."', '1');";
		mysql_query($sql) or die();//("__insert_product_inventory error.<br />".mysql_error());
	}
	
	public static function product_stock($productid){
		$sql="select productid, date_format(date,'%d %M %Y') date_formatted, date, quantity from productinventory where productid='".main::formatting_query_string($productid)."' and type='1' order by date;";
		$rs=mysql_query($sql) or die();//("product_stock query error.<br />".mysql_error());
		return $rs;
	}
	
	public static function product_sale($productid, $date){
		$sql="select date from productinventory where type='1' and productid='".main::formatting_query_string($productid)."' and 
			date>'".main::formatting_query_string($date)."' order by date limit 1;";
		$rs=mysql_query($sql) or die();//(mysql_error());
		if(mysql_num_rows($rs)>0){
			$rs_=mysql_fetch_array($rs);
			$dateend="'".$rs_["date"]."'";
		}else $dateend="now()";
		$sql="select productid, date_format(date,'%d %M %Y') date, quantity, order_no from productinventory where 
			productid='".main::formatting_query_string($productid)."' and type='0' 
			and date between '".main::formatting_query_string($date)."' and ".$dateend." order by date;";
		$rs_product_sale=mysql_query($sql) or die();//("product_sale error.<br />".mysql_error());
		return $rs_product_sale;
	}
	
//PRODUCT SPS
	public static function product_sps($productid, $spsid="",$userview=true){
		$sql="select a.spsid, a.spspath, a.active, b.productid, b.sortorder, b.active,a.spsvid from sps a, spsproduct b where a.spsid=b.spsid and b.productid='".main::formatting_query_string($productid)."' ";
		if($spsid!="")	$sql.=" and a.spsid='".main::formatting_query_string($spsid)."' ";
		if($userview)	$sql.=" and a.active='1' and b.active='1' ";
		$sql.=" order by b.sortorder;";
		$rs=mysql_query($sql) or die();//("product_sps query error.<br />".mysql_error());
		return $rs;
	}
	
	public static function category_sps($categoryid, $userview=true){
		$sql="select a.spsid, a.spspath, a.active, b.categoryid, b.active from sps a, spscategory b where a.spsid=b.spsid and b.categoryid='".main::formatting_query_string($categoryid)."' ";
		if($userview)	$sql.=" and a.active='1' and b.active='1' ";
		$sql.=" order by b.sortorder;";
		$rs=mysql_query($sql) or die();//("category_sps query error.<br />".mysql_error());
		return $rs;
	}
	
	public static function sps($spsid="", $keyword=""){
		$sql="select spsid, spspath, active from sps where ".
			($spsid!=""? "spsid='".main::formatting_query_string($spsid)."'"	: "spspath like '%".main::formatting_query_string($keyword)."%' ")
			." order by spspath;";
		$rs=mysql_query($sql) or die();//("sps query error.<br />".mysql_error());
		return $rs;			
	}
		static function new_product($productid=""){
		$sql="select productid from product_new
			where datestart<=current_date and dateend>=current_date";
		$result=mysql_query($sql) or die();//("discount_product_relations error.<br />".mysql_error());
		return $result;		
	}
	static function product_image($product_name,$productid){
		//$productid="";
		include_once "lib/cls_discount.php";
		$product_img = "images/product/tn". $product_name .".png";
		list($product_series, $short_product_name) = explode("-", $product_name);
		$tmp_product_img = "images/product/2/" . trim($short_product_name) . ".png";
		if( file_exists( $tmp_product_img ) )	$product_img = $tmp_product_img;

		if ((in_array($productid,@$GLOBALS['arr_promo_productid'])) or ($_SERVER['SCRIPT_NAME']=='/modena_web/promo-month.php')) {
		if(file_exists("images/product/2/".trim($short_product_name).".png"))
			$file = "images/product/2/".trim($short_product_name).".png";
		else
			$file = "images/product/tn".$product_name.".png";
		
		//$product_img = "thumbp.php?p=" . $file;
		$product_img =$file;
		} 
		$productnew=self::new_product();
		if(mysql_num_rows($productnew)>0) {
			while($productnew_=mysql_fetch_array($productnew)){
			//echo "<!--" . $productid . "--"  . $productnew_['productid'] . " x -->";
			
				if (in_array($productid,array($productnew_['productid'])) || $productid === $productnew_['productid'] ){
					//echo "<!--" . $productnew_['productid'] . " x -->";
				if(file_exists("images/product/2/".trim($short_product_name).".png"))
					$file = "images/product/2/".trim($short_product_name).".png";
				else
					$file = "images/product/tn".$product_name.".png";
				
				$product_img = "thumbn.php?p=" . $file;
				
				}
			}
		}
		
		return $product_img;
		/*$product_img = "images/product/tn". $product_name .".png";
		list($product_series, $short_product_name) = explode("-", $product_name);
		$tmp_product_img = "images/product/2/" . trim($short_product_name) . ".png";
		if( file_exists( $tmp_product_img ) )	$product_img = $tmp_product_img;
		return $product_img;*/
	}
	
}

?>