<?

if($_REQUEST["c"]=="change_state"){
		include_once("lang/".$lang.".php");	
		
		if(@$_REQUEST["mode"]!="view")	$_SESSION["shipping_state"]=$_REQUEST["state_id"];
		if($_SESSION["shipping_state"] == "") $_SESSION["shipping_region"] = "";
		$s_city="s_city";
		if(isset($_REQUEST["m"])){
			if($_REQUEST["m"]!="t_state")$s_city="s_city1";
		}else $s_city = $_REQUEST["m_mai"]; // untuk perubahan state & region di member account information
		
		echo "<script>";		
		echo "var selection=parent.document.getElementById('".$s_city."');";
		echo "try{var textbox=parent.document.getElementById('t_city');}catch(e){}";
		echo "try{var sel_qty=parent.document.getElementById('qty_'+ parent.document.getElementById('productid').value);
			sel_qty.options.length=0;sel_qty.disabled=true;}catch(e){}";
		$rs_region=main::shipment_exception($_REQUEST["state_id"], "");
		if(mysql_num_rows($rs_region)>0 && $_REQUEST["state_id"] != ""){
			echo "selection.style.visibility='visible';selection.style.position='static';";
			echo "selection.style.disabled=false;";
			echo "try{textbox.style.visibility='hidden';textbox.style.position='absolute'; textbox.value='';}catch(e){}";
			echo "selection.options.length=0;";
			echo "var option=document.createElement('option');
					option.text='".$lang_select_region."';
					option.value='';
					try{selection.add(option,selection.options[null]);}
					catch (e){selection.add(option,null);}";
			while($region=mysql_fetch_array($rs_region)){
				echo "var option=document.createElement('option');
					option.text='".ucwords(strtolower($region["region"]))."';
					option.value='".$region["region_id"]."';
					". (@$_REQUEST["region_id"] == $region["region_id"] ? "option.selected = true;" : "") ."
					try{selection.add(option,selection.options[null]);}
					catch (e){selection.add(option,null);}";
			}
		}else{				
			echo "selection.style.disabled=true;selection.style.position='absolute'; selection.options.length=0;";
			echo "selection.setAttribute('class', 'font-grey-01-11');";
			echo "selection.setAttribute('style', 'font-size:11px !important; width:270px');";				
		}
		echo "</script>";
		exit;	

}elseif($_REQUEST["c"]=="change_region"){
	include_once("lang/".$lang.".php");	
	
	$_SESSION["shipping_region"]=$_REQUEST["region_id"];
	unset($arr_par);
	$arr_par["c"] = "kebetot";
	$arr_par["propinsi"] = $_SESSION["shipping_state"];
	$arr_par["kota"] = $_SESSION["shipping_region"];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, __API__ . "gudang");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$server_output = curl_exec ($ch);
	$server_output = json_decode($server_output, true);		
	$_SESSION["gudang"] = $server_output["gudang"];
	header("location:cart.php");
	
}elseif($_REQUEST["c"] == "reset_cart"){
	foreach( $_SESSION["arr_order_no"] as $tipe => $order_no ){
		$prefiks_tabel = "";
		if( $tipe == "culinaria_order_no" ) $prefiks_tabel = "culinaria_";
		$sql = "delete from ". $prefiks_tabel . "orderproduct where order_id = (select order_id from ". $prefiks_tabel . "ordercustomer where order_no = '". main::formatting_query_string($order_no) ."' )";
		mysql_query($sql);
		$sql = "delete from ". $prefiks_tabel . "ordercustomer where order_no = '". main::formatting_query_string($order_no) ."' ";
		mysql_query($sql);
	}
	
	$sql = "delete from culinaria_orderproduct where parent_order_no in ('". main::formatting_query_string( implode( "',", array_values( $_SESSION["arr_order_no"] ) ) ) ."')";
	mysql_query($sql);
	session_unset();
	header("location:cart.php");

}elseif( $_REQUEST["c"] == "qty_produk" ){
	foreach( $_SESSION["shopping_cart"] as $productid=> $qty ){
		if( $productid == $_REQUEST["productid"] ){
			$qty = $qty + ( $_REQUEST["sc"] ==  "1" ? 1 : -1 );
			$qty = $qty < 0 ? 0 : $qty;
			$_SESSION["shopping_cart"][$productid] = $qty;
		}
	}
	header("location:cart.php");
	
}elseif( $_REQUEST["c"] == "kode_promo" || $_REQUEST["c"] == "kode_promo_culinaria" ){
	$product=new product;
	$product->lang=$lang;
	$product->productid=" in (".  implode(",", array_keys($_SESSION["shopping_cart"])).") ";
	$product->penable="='Y'";
	$product->distinct=true;
	$rs_product=$product->product_list("'%'");
	while( $data = mysql_fetch_array($rs_product) )
		$arr_item[ $data["kode_tanpaformat"] ] = array( "productid" =>  $data["productid"], "nama" => $data["name"], "harga" => $data["price"], "qty" => $_SESSION["shopping_cart"][ $data["productid"] ] );
	
	unset($arr_par);
	$rand = rand(0,100000);
	$arr_par["c"] = "kebetot";
	$arr_par["appid"] = "WEB";
	$arr_par["rand"] = $rand;
	$arr_par["order_id"] = $_SESSION["fg_order_no"];
	$arr_par["auth"] = sha1(__KEY__ . $rand . $arr_par["order_id"] );
	$arr_par["dealer_id"] = __IDCUST_FG__;
	$arr_par["kode_campaign"] = @$_REQUEST["kode_promo"];
	
	foreach( array_keys($arr_item) as $index => $kode_produk ) {
		$arr_par["item"][$index]["item_id"] = $kode_produk;
		$arr_par["item"][$index]["harga"] = $arr_item[$kode_produk]["harga"];
		$arr_par["item"][$index]["qty"] = $arr_item[$kode_produk]["qty"];
		$arr_par["item"][$index]["gudang"] = $_SESSION["gudang"];
	}
	
	// API RDM, utk kode promo per item
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, __API__ . "rdm");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, __CURL_TIMEOUT); 
	curl_setopt($ch, CURLOPT_TIMEOUT, __CURL_TIMEOUT);
	//$server_output = curl_exec ($ch);
	//$server_output_diskon = json_decode($server_output, true);		
	
	
}elseif( $_REQUEST["c"] == "tradein" ){
	$_SESSION["tradein"][] = $_REQUEST["productid"];
	header("location:cart.php");
	
}elseif( $_REQUEST["c"] == "cancel_tradein" ){
	foreach( $_SESSION["tradein"] as $index => $productid ){
		if( $productid == $_REQUEST["productid"] ) {
			unset($_SESSION["tradein"][$index]);
			break;
		}
	}
	
	header("location:cart.php");
	
}

?>
