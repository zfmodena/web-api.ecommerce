<?
require_once "cls_product.php";
/*
ekstensi untuk perhitungan diskon pada promo yang memerlukan PC
*/
class order_online_promo_ext extends order_online_promo{

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
	
	// 1. DISC 50% ON SECOND ITEM
	function calculate_discount(){		
		$app=order_online_promo_ext::array_product_price($this->arr_product, $this->ipc);
		$an=order_online_promo_ext::strip_array_by_index($this->arr_product, $app);
		$nd=floor(array_sum($an)/2); $c=0; $discount=0;
		foreach($app as $productid=>$price){
			if($c>=$nd) break;
			if($c+$an[$productid]>$nd)
				$discount+= ($nd-$c) * $price * 0.5;
			else $discount+= $an[$productid] * $price * 0.5;
			$c+=$an[$productid];
		}
		return $discount;
	}
	
	private static function strip_array_by_index($a, $ar){
		$return=array();
		foreach($a as $i=>$v)
			if(array_key_exists($i, $ar)) $return[$i]=$v;
		return $return;
	}
		
	private static function array_product_price($arr_product, $ipc){ /* menyusun ulang arr_product dengan exclude promo_per_item */
		$return=array();
		$p=new product;
		$p->productid=" in (".implode(",", array_keys($arr_product)).")";
		$p->penable="='Y'";
		$p->orderby="price";
		$rs=$p->product_list("'%'");
		while($d=mysql_fetch_array($rs)){
			$diskon_peritem=new order_online_promo;
			$diskon_peritem->price_off_peritem($d["productid"], $d["price"], $arr_product[$d["productid"]], "", $ipc);	
			if($diskon_peritem->discount_peritem()<=0) $return[$d["productid"]]=$d["price"];
		}
		return $return;
	}


}
?>