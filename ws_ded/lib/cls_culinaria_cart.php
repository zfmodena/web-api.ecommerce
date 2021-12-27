<?
class culinaria_cart extends culinaria_program{

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
		while( $data_order = sql::fetch_array( $rs_data_order ) ){
			
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

	static function browse_culinaria_cart_jumlah_kursi( $arr_parameter ){
		$sql = "select a.*, b.jumlah_kursi from culinaria_ordercustomer a left outer join 
			( select order_id, product_id, sum(quantity) jumlah_kursi from culinaria_orderproduct group by order_id, product_id ) b on a.order_id = b.order_id ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		try{		return sql::execute( $sql );	}
		catch(Exception $e){$e->getMessage();}

	}
	
}
?>