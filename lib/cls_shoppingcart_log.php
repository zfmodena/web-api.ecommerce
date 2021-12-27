<?php
class shoppingcart_log extends shoppingcart{
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
fungsi untuk mendapatkan order_id
(string) $order_number
(bool) $is_log=true
*/	
	private static function get_order_id($order_number, $is_log=true){
		$sql="select order_id from ordercustomer".($is_log?"_log":"")."
			where order_no='".main::formatting_query_string($order_number)."' order by order_date desc;";
		$rs=mysql_query($sql) or die();
		if(mysql_num_rows($rs)>0){
			$data=mysql_fetch_array($rs);
			return $data["order_id"];
		} return "";
	}

/*
fungsi utk mendapatkan kolom dari suatu table
$table_name (string)
return :: resultset
*/	
	private static function get_column_description($table_name){
// 		column yang tidak diikutkan dalam transaksi copy-paste table transaksi <-> log
		$exceptional_column=array(
			"ordercustomer"=>array("order_id"), 
			"orderproduct"=>array("order_id")
		);	
		
		$sql="	select column_name, column_type
				from information_schema.columns
				where table_name =  '".main::formatting_query_string($table_name)."' and table_schema='".$GLOBALS["database"]."';";
		$rs=mysql_query($sql) or die();
		while($column=mysql_fetch_array($rs)){
			if(!in_array($column["column_name"], $exceptional_column[$table_name]))
				$return[]="`".$column["column_name"]."`";			
		}
		return $return;
	}
	
/*
fungsi utk hapus order log sebelum entri ke transaksi
*/
	private static function __delete_ordercustomer($order_number){
		$order_id=shoppingcart_log::get_order_id($order_number, false);
		$sql="delete from ordercustomer
			where order_id='".main::formatting_query_string($order_id)."';";
		mysql_query($sql) or die();		
		return $order_id;
	}

/*
fungsi utk hapus order produk sebelum entri ke transaksi
*/
	private static function __delete_orderproduct($order_id){		
		$sql="delete from orderproduct where order_id='".main::formatting_query_string($order_id)."';";
		mysql_query($sql) or die();				
	}

/*
fungsi dipergunakan pada saat sebelum pengiriman data ke IPG Doku, untuk membuat log order
return :: (string) order_id dipergunakan untuk entri di tabel orderproduct_log
*/
	public static function __insert_order_log($order_number){		
		$all_column=implode(",", shoppingcart_log::get_column_description("ordercustomer"));

		$sql="insert into ordercustomer_log($all_column) select $all_column from ordercustomer 
			where order_no='".main::formatting_query_string($order_number)."';";
		mysql_query($sql) or die();		
		return shoppingcart_log::get_order_id($order_number);
	}

/*
fungsi dipergunakan pada saat sebelum pengiriman data ke IPG Doku, untuk membuat log order product
*/
	public static function __insert_product_log($order_id_log, $order_number){
		$all_column=implode(",", shoppingcart_log::get_column_description("orderproduct"));
		
		$sql="insert into orderproduct_log select $order_id_log, $all_column from orderproduct where order_id=(
				select order_id from ordercustomer where 
					order_no='".main::formatting_query_string($order_number)."'
			)";
		mysql_query($sql) or die();
	}

/*
fungsi dipergunakan pada saat notifikasi oleh EDU, apabila data order tidak ditemukan di tabel order (transaksi)
return :: (string) order_id dipergunakan untuk entri di tabel orderproduct
*/
	public static function __copy_order($order_number){
		// pastikan tidak ada nomor invoice yang sama di transaksi, delete di ordercustomer + orderproduct
		$order_id=shoppingcart_log::__delete_ordercustomer($order_number);
		shoppingcart_log::__delete_orderproduct($order_id);
		
		$all_column=implode(",", shoppingcart_log::get_column_description("ordercustomer"));
		
		$sql="insert into ordercustomer($all_column) select $all_column from ordercustomer_log 
			where order_no='".main::formatting_query_string($order_number)."' order by order_id desc limit 1;";
		mysql_query($sql) or die();//"insert order product error.<br />".mysql_error());
		return shoppingcart_log::get_order_id($order_number, false);
	}

/*
fungsi dipergunakan pada saat notifikasi oleh EDU, apabila data order tidak ditemukan di tabel order product (transaksi)
*/
	public static function __copy_product($order_id, $order_number){
		$all_column=implode(",", shoppingcart_log::get_column_description("orderproduct"));
		
		$sql="insert into orderproduct select $order_id, $all_column from orderproduct_log where order_id=(
				select order_id from ordercustomer_log where 
					order_no='".main::formatting_query_string($order_number)."' order by order_id desc limit 1
			)";
		mysql_query($sql) or die();//"insert order product error.<br />".mysql_error());
	}

}
?>