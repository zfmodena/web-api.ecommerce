<?
include "var.php";
$GLOBALS["random"] = rand(0,1000);
$arr_productid_kode_accpac = "ES0015/0-0803-S08";
	$arr_par = array(
				"c" 		=> "sl",
				"sc" 	=> "cs", 
				"rand"	=> $GLOBALS["random"],
				"auth"   => sha1($GLOBALS["ftp_address"].$GLOBALS["random"].$GLOBALS["ftp_username"]),
				
				"i"		=> $arr_productid_kode_accpac,
				"p"		=> 13, 
				"k"		=> 275
			);

			$server_output = 0;
			// buat sambungan ke satrio
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $GLOBALS["auto_stock_url"]);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $GLOBALS["curl_connection_timeout"]); 
			curl_setopt($ch, CURLOPT_TIMEOUT, $GLOBALS["curl_timeout"]); 

			$server_output = curl_exec ($ch);
			$server_output = json_decode($server_output, true);
			print_r($server_output);
?>