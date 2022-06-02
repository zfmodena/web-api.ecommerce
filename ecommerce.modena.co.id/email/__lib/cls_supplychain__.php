<?

class supplychain extends main{
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
	public function get_supplychain($type, $supplychainid){
		$sql="select distributorsid, typeid, content, enabled, name, area, groups from distributors where distributorsid is not null ";
		if($type!="")$sql.=" and typeid='".main::formatting_query_string($type)."' ";
		if($supplychainid!="")$sql.=" and distributorsid='".main::formatting_query_string($supplychainid)."' ";
		if(isset($this->area))$sql.=" and area='".main::formatting_query_string($this->area)."' ";
		if(isset($this->groups))$sql.=" and groups='".main::formatting_query_string($this->groups)."' ";
		if(isset($this->orderby))$sql.=" order by ".$this->orderby;
		$rs=mysql_query($sql) or die();//("get_supplychain error.<br />".mysql_error());
		return $rs;
	}
	
	public static function get_supplychaingroups($type, $enabled){
		$sql="select distinct groups from distributors where typeid='".main::formatting_query_string($type)."' ";
		if($enabled!="")$sql.=" and enabled=".main::formatting_query_string($enabled)." ";
		$sql.=" order by groups;";
		$rs=mysql_query($sql) or die();//("get_supplychaingroups error.<br />".mysql_error());
		return $rs;
	}
	
	public static function get_supplychain_area($type, $groups, $enabled, $orderby){
		$sql="select distinct area from distributors where typeid='".main::formatting_query_string($type)."' ";
		if($groups!="")$sql.=" and groups ".$groups;
		if($enabled!="")$sql.=" and enabled=".main::formatting_query_string($enabled)." ";
		if($orderby!="")$sql.=" order by ".$orderby;
		$rs=mysql_query($sql) or die();//("get_supplychain_area_groups error.<br />".mysql_error());
		return $rs;
	}
	
	public static function get_supplychain_content($type, $groups, $area, $enabled, $orderby){
		$sql="select distributorsid, content, name  from distributors where typeid='".main::formatting_query_string($type)."' ";
		if($groups!="")$sql.=" and groups='".main::formatting_query_string($groups)."' ";
		if($area!="")$sql.=" and area ".$area;
		if($enabled!="")$sql.=" and enabled=".main::formatting_query_string($enabled)." ";
		if($orderby)$sql.=" order by ".$orderby;
		$rs=mysql_query($sql) or die();//("get_supplychain_content error.<br />".mysql_error());
		return $rs;
	}

}

?>