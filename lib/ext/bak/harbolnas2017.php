<?

class ekstensi_perhitungan_diskon{	
	var 	$tanggal_awal = "2017-06-2",
			$tanggal_akhir = "2017-06-4";
			/*$tanggal_awal = "2017-05-09",
			$tanggal_akhir = "2017-05-14";*/
	
	var 	$arr_tanggal_promo = array( 2,3,4);
	
	var 	$arr_2 = array(382),
			//$arr_23 = array(727,1007),
			$arr_3 = array(478,512),
			$arr_4 = array(885);
			/*$arr_12 = array(1034),
			$arr_13 = array(653);*/
			
	var		$tgl_2 = "2017-06-02",
			//$tgl_23 = "2017-05-23",
			$tgl_3 = "2017-06-03",
			$tgl_4 = "2017-06-04";/*,
			$tgl_12 = "2016-12-12",
			$tgl_13 = "2016-12-13";*/
			
	var 	$maksimal_terjual = 10;
	
	var		$diskon_baru = 0.6;
}

function ekstensi_perhitungan_diskon($productid){

	$ekstensi_diskon = new ekstensi_perhitungan_diskon;
	
	for( $x = 0; $x < count( $ekstensi_diskon->arr_tanggal_promo ); $x++ ){
		$arr_product = $ekstensi_diskon->{ "arr_" .  $ekstensi_diskon->arr_tanggal_promo[ $x ] };
		
		if( in_array( $productid, $arr_product ) ) {
			$ekstensi_diskon->tanggal_awal = 
			$ekstensi_diskon->tanggal_akhir = $ekstensi_diskon->{ "tgl_" .  $ekstensi_diskon->arr_tanggal_promo[ $x ] };
		}
	}

	$sql = "
	select case  when CURRENT_DATE() = '". $ekstensi_diskon->tanggal_awal ."' /*and CURRENT_DATE() <= '". $ekstensi_diskon->tanggal_akhir ."'*/ then  
		CASE CURRENT_DATE() 

			when '2017-06-02' then 2
			when '2017-06-03' then 3
			when '2017-06-04' then 4
							
			
		end
	else 0 end ekstensi_diskon
	";
	//echo $sql;
	$rs_ekstensi_perhitungan_diskon = mysql_query($sql);
	if( mysql_num_rows( $rs_ekstensi_perhitungan_diskon ) > 0 ){
		
		$ekstensi_perhitungan_diskon = mysql_fetch_array( $rs_ekstensi_perhitungan_diskon );	
		/*print_r($ekstensi_perhitungan_diskon);
		echo $ekstensi_perhitungan_diskon["ekstensi_diskon"];*/
		if( $ekstensi_perhitungan_diskon["ekstensi_diskon"] != 0 ){
		
			$arr_ekstensi_diskon = $ekstensi_diskon->{ "arr_" .  $ekstensi_perhitungan_diskon["ekstensi_diskon"] };

			if( in_array( $productid, $arr_ekstensi_diskon ) ){
				
				$sql = "select ifnull(sum(b.quantity), 0) jumlah_terjual from ordercustomer a, orderproduct b 
						where a.order_id = b.order_id and a.order_no REGEXP('^[0-9]') and order_status >=2 and b.product_id = ". main::formatting_query_string( $productid ) ." 
						and a.order_date >= '". $ekstensi_diskon->tanggal_awal ."' and a.order_date < DATE_ADD(a.order_date,INTERVAL 1 DAY)";
				
				$rs_jumlah_terjual = mysql_query( $sql );
				
				if( mysql_num_rows( $rs_jumlah_terjual ) > 0 ){
				
					$jumlah_terjual = mysql_fetch_array( $rs_jumlah_terjual );

					if( $jumlah_terjual["jumlah_terjual"] < $ekstensi_diskon->maksimal_terjual )
						return $ekstensi_diskon->diskon_baru;

				}else return $ekstensi_diskon->diskon_baru;
				
			}
			
		}
		
	}

	return 0;
}

?>