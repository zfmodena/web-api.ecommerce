<?
class ipgpromo extends main{
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
	static function get_order_ipg_promo($order_no){
		$sql="select ipg_promo from ordercustomer where order_no='".main::formatting_query_string($order_no)."';";
		$result=mysql_query($sql) or die("get_order_ipg_promo error<br />".mysql_error());
		if(mysql_num_rows($result)>0){
			$return=mysql_fetch_array($result);
			return $return["ipg_promo"];
		}else return 0;
	}
	
	static function get_valid_ipgpromo($id="", $bin="", $type=""/*1=diskon; 2=cicilan; 3=VA*/){
		$sql="select a.ipg_promo_id,
			a.ipg_promo_datestart,
			a.ipg_promo_dateend,
			a.remark_id,
			a.remark_en,
			a.diskon, a.cicilan, a.mallid, a.chainmerchant, a.tipe #add_value# from ipg_promo a #add_parameter# where a.ipg_promo_datestart<=current_date and a.ipg_promo_dateend>=current_date";
		if($id!="")$sql.=" and a.ipg_promo_id='".main::formatting_query_string($id)."' ";
		if($bin!=""){
			$sql=str_replace("#add_value#", ", b.ipg_promo_bin", $sql);
			$sql=str_replace("#add_parameter#"," inner join ipg_promo_bin b on a.ipg_promo_id=b.ipg_promo_id 
				and b.ipg_promo_bin='".main::formatting_query_string($bin)."' ", $sql);
		}
		if($type!="")$sql.=" and a.tipe='".main::formatting_query_string($type)."' ";
		$sql=str_replace("#add_value#", "", $sql);
		$sql=str_replace("#add_parameter#", "", $sql);
		$result=mysql_query($sql) or die();
		return $result;
	}
	
	static function ipgpromo_calc($id, $amount){
		$ipgpromo=mysql_fetch_array(ipgpromo::get_valid_ipgpromo($id)) or ipgpromo::ipgpromo_calc_zero($amount);
		$disc=$ipgpromo["diskon"]<1?	$amount*$ipgpromo["diskon"] : $ipgpromo["diskon"];
		$new_amount=$amount-$disc;
		$arr_ret=array($disc, $new_amount, 
			array("tipe"=>@$ipgpromo["tipe"], "remark_id"=>$ipgpromo["remark_id"], "remark_en"=>$ipgpromo["remark_en"],
				"cicilan"=>$ipgpromo["cicilan"], "mallid" => $ipgpromo["mallid"], "chainmerchant" => $ipgpromo["chainmerchant"]));
		return $arr_ret;		
	}
	
	protected static function ipgpromo_calc_zero($amount){
		return array(0, $amount, array());
	}
	
	static function dst($arr_request){
		$tmp="";
		foreach($arr_get as $key=>$value)$tmp.=$key."=".$value."|";
		$sql="insert into dst values('".main::formatting_query_string($tmp)."')";
		mysql_query($sql);
	}

}
?>