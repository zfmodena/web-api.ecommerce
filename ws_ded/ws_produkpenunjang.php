<?

$arr_data = array();

$_POST = $_REQUEST;
$lang = "id";
if( in_array( @$_REQUEST["lg"], array("id", "en")) ) $lang = $_REQUEST["lg"];

$arr_lang_key["id"] = array(1=>"dimensi", "peruntukan", "fitur");
$arr_lang_key["en"] = array(1=>"dimension", "compatible with", "features");

if( @$_REQUEST["produk_penunjang"] != "" ){
	$sql = "select DISTINCT trim(a.name) name, upper(trim(a.name)) nama_produk, a.price, trim(c.value_id) value_id, trim(c.value_en) value_en, d.name_en, d.name_id, d.productpropertyschemaid 
					from product a, category b, productproperty c, productpropertyschema d 
					where a.categoryid = b.categoryid and b.parentcategoryid = 174 and c.ProductPropertySchemaID = d.productpropertyschemaid
					and a.productid = c.productid  
					and a.penable = 'Y' and upper(trim(a.name)) = '". strtoupper(trim( $_REQUEST["produk_penunjang"] )) ."' 
				";

	$rs = mysql_query( $sql ) or die( mysql_error() );
	while( $data = mysql_fetch_array( $rs ) ){
		if( in_array( $data["productpropertyschemaid"], array(446, 447, 448) ) ) // dimensi produk 
			$arr_data[ $arr_lang_key[ $lang ][1] ][] = $data;
		elseif( in_array( $data["productpropertyschemaid"], array(338, 992) ) ) // peruntukan
			$arr_data[ $arr_lang_key[ $lang ][2] ][] = $data;
		else
			$arr_data[ $arr_lang_key[ $lang ][3] ][] = $data;
	}
		
	echo json_encode( $arr_data );
	exit;
}

$sql = "select MembersProductID, Product, SerialNumber, PurchaseAt,  cast(PurchaseDate as date) PurchaseDate, datediff(CURRENT_DATE, cast(PurchaseDate as date)) usia_produk 
	from membersproduct where MemberID = '". main::formatting_query_string( $_REQUEST["memberid"] ) ."' " ;

if( isset( $_REQUEST["membersproductid"] ) ){
	if( is_array($_REQUEST["membersproductid"]) && count( $_REQUEST["membersproductid"] ) > 0 )
		$sql .= " and membersproductid in (". implode(" , ", $_REQUEST["membersproductid"]) .") ";
	else
		$sql .= " and membersproductid = '". $_REQUEST["membersproductid"] ."' ";
}
//echo $sql;
$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) )
	$arr_par["barcode"][] = $data["SerialNumber"];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, __AIR_URL__ . "index.php?c=load_multiple_barcode");
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//print_r($arr_par);exit();
$server_output = curl_exec ($ch);
$server_output = json_decode($server_output, true);
$arr_nama_produk = array();

if( is_array($server_output) && count( $server_output ) > 0 ) {
	
	foreach( $server_output as $mesdb_barcode ){
		
		foreach( $arr_par["barcode"] as $membersproductserialnumber ){
			if( strtolower(trim($mesdb_barcode["BARCODESN"])) ==  strtolower(trim( substr($membersproductserialnumber, 0, strlen( trim($mesdb_barcode["BARCODESN"]) ) ) )) ){
				$arr_nama_produk[ $membersproductserialnumber ] = $mesdb_barcode["SPLMODEL"];
				continue;
			}
		}
	}
}
//print_r($arr_nama_produk);	
$arr_breakdown_nama_produk = array();
foreach( $arr_nama_produk as $nama_produk ){
	$arr_ = preg_split( "/(\s|\/)/", $nama_produk );
	$arr_breakdown_nama_produk[] = "cast(c.value_id as binary ) like '%" . $arr_[0] . "%'";
	if( isset( $arr_[1] ) )  $arr_breakdown_nama_produk[] = "cast(c.value_id as binary ) like '%". $arr_[1] . "%'";
}

$arr_nama_produk_penunjang = array();
$sql = "select a.productid, trim(a.name) name, upper(trim(a.name)) nama_produk, a.price, c.value_id, c.value_en from product a, category b, productproperty c 
				where a.categoryid = b.categoryid and b.parentcategoryid = 174
				and a.productid = c.productid and c.productpropertyschemaid in (338, 992) and a.penable = 'Y'
				and (". implode(" or ", $arr_breakdown_nama_produk) .") ";

$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
while( $data = mysql_fetch_array( $rs ) ){
	if( !in_array( $data["nama_produk"], $arr_nama_produk_penunjang ) ){
		$arr_nama_produk_penunjang[] = $data["nama_produk"];
		$arr_data[] = array("nama_produk_asli"=>$data["name"],
					"nama_produk"=>$data["nama_produk"], 
					"harga"=>$data["price"],
					"penunjang_untuk" => $data["value_id"]
				);
	}
}
	
Skip_All:				
echo json_encode( $arr_data );

?>