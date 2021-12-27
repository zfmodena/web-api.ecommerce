<?
// utk yang ambil dari database active digital, harus include sbb

function parent_kategori_lama($id){
    $return = array();
    $sql = "select categoryid id, concat(case when categoryid in (135,136) then 'professional ' else '' end, name) name, parentcategoryid parent_id from category where categoryid = '".main::formatting_query_string($id)."' and enabled=1";
    $rs = mysql_query($sql);
    if(mysql_num_rows($rs)> 0 ){
        $data = mysql_fetch_array($rs);
        $return = array("id" => $data["id"], "parent_id" => $data["parent_id"], "kategori" => $data["name"]);
    }
    return $return;
}

function formatting_parent_kategori_lama($id){
    $arr_parent_kategori = $arr_parent_nama_kategori = $arr_parent_id_kategori = array();
    $counter = 1;
    while(true == true){
        if(@$pid == "") $pid = $id;
        else $pid = $temp_pid;
        
        $data_parent_kategori = parent_kategori_lama($pid);
        if( count( $data_parent_kategori ) <= 0 ) break;
        
        $temp_pid = $data_parent_kategori["parent_id"];
        $arr_parent_kategori[] = $data_parent_kategori + array("urutan"=>$counter);
        $arr_parent_nama_kategori[] = $data_parent_kategori["kategori"];
        $arr_parent_id_kategori[] = $data_parent_kategori["id"];
        $counter++;
    }
    
    $arr_sql_pk = array();
    foreach($arr_parent_kategori as $arr_pk)
        $arr_sql_pk[] = "select '". main::formatting_query_string($arr_pk["parent_id"]) ."' parent_id, '". main::formatting_query_string($arr_pk["kategori"]) ."' kategori , '". main::formatting_query_string($arr_pk["id"]) ."' id";
    
    $sql_pk = implode(" union ", $arr_sql_pk);
    
    krsort($arr_parent_nama_kategori);
    krsort($arr_parent_id_kategori);
    $parent_id_kategori = implode("|", $arr_parent_id_kategori);
    $parent_nama_kategori = implode("|", $arr_parent_nama_kategori);
    
    return array("root_id_kategori" => $parent_id_kategori, "root_nama_kategori" => $parent_nama_kategori);
}

function fitur_produk($pid){
    $sql = "select name_id, name_en, replace(Value_id,'\n','') Value_id, replace(value_en,'\n','') Value_en from productproperty a inner join productpropertyschema b on a.ProductPropertySchemaID = b.productpropertyschemaid where ProductID = '". main::formatting_query_string($pid) ."' and Value_id != '' and value_en != ''";
    $rs = mysql_query($sql);
    while($data = mysql_fetch_array($rs)){
        $arr_data["en"][] = str_replace(" : ", " ", $data["Value_en"]);
        $arr_data["id"][] = str_replace(" : ", " ", $data["Value_id"]);
    }
    $str_data_en = implode(";", $arr_data["en"]);
    $str_data_id = implode(";", $arr_data["id"]);
    return array("fitur_en" => $str_data_en, "fitur_id" => $str_data_id);
}

function product_link_lama($pid){
    $sql = "select b.parentcategoryid , b.categoryid from product a inner join category b on a.categoryid = b.categoryid where a.productid = '". main::formatting_query_string($pid) ."'";
    $rs = mysql_query($sql);
    $return = "";
    if( mysql_num_rows($rs) > 0 ){
        $data = mysql_fetch_array($rs);
        $return = "https://www.modena.co.id/detail-". $data["parentcategoryid"] ."-". $data["categoryid"] ."-". $pid .".php";
    }
    return $return;
}


$json = array();
$parent_category = formatting_parent_kategori_lama($_REQUEST["id"]);
$json["root_kategori"] = $parent_category;

$category = "a.parentcategoryid is null";
if( @$_REQUEST["id"] != "" )
    $category = "a.parentcategoryid = '". main::formatting_query_string($_REQUEST["id"]) ."'";

$sql = "select a.categoryid id_kategori, concat(case when a.categoryid in (135,136) then 'professional ' else '' end, a.name) nama_kategori 
        from category a where " . $category . " and enabled=1 ";
$rs = mysql_query($sql) or die(mysql_error());

while($data = mysql_fetch_array($rs, MYSQL_ASSOC))
    $json["kategori"][] = $data;

// loading kategori
if( !isset($_REQUEST["grup"]) || ( isset($_REQUEST["grup"]) && $_REQUEST["grup"] == "" ) )
    $sql = "select distinct '' id_produk, '' nama_awal, '' nama_akhir, '' nama_lengkap, '' harga, '' sku, 0 diskon, '' keterangan_diskon, SUBSTRING_INDEX(name,'-',1) grup
    from product a inner join __kode_produk_accpac b on a.productid = b.productid 
    left outer join promo_per_item c on b.productid = c.productid and CURRENT_TIMESTAMP >= c.datestart and CURRENT_TIMESTAMP <= c.dateend 
    left outer join promo_per_item_related d on c.promo_per_item_id = d.promo_per_item_id and d.min_qty <= 1 
    where categoryid = '". main::formatting_query_string($_REQUEST["id"]) ."' and penable = 'Y'";
else
$sql = "select a.productid id_produk, '' nama_awal, '' nama_akhir, name nama_lengkap, price harga, replace(replace(b.kode,'/',''),'-','') sku, ifnull(d.diskon,0) diskon, c.remark_id keterangan_diskon, weight berat
    from product a inner join __kode_produk_accpac b on a.productid = b.productid 
    left outer join promo_per_item c on b.productid = c.productid and CURRENT_TIMESTAMP >= c.datestart and CURRENT_TIMESTAMP <= c.dateend 
    left outer join promo_per_item_related d on c.promo_per_item_id = d.promo_per_item_id and d.min_qty <= 1 
    where categoryid = '". main::formatting_query_string($_REQUEST["id"]) ."' and penable = 'Y' and a.name like '". main::formatting_query_string($_REQUEST["grup"]) ."%'";
$rs = mysql_query($sql) or die();
if( mysql_num_rows($rs) > 0 ){

    // reset data root_kategori dan pisahkan kategori dari root_kategori
    $arr_id_kategori = explode("|", $parent_category["root_id_kategori"]);
    $arr_nama_kategori = explode("|", $parent_category["root_nama_kategori"]);
    
    $json["kategori"][] = array("id_kategori" => $arr_id_kategori[ count($arr_id_kategori)-1 ], "nama_kategori" => $arr_nama_kategori[ count($arr_nama_kategori)-1 ]);
    unset($arr_id_kategori[ count($arr_id_kategori)-1 ], $arr_nama_kategori[ count($arr_nama_kategori)-1 ]);
    $json["root_kategori"]["root_id_kategori"] = implode("|", $arr_id_kategori);
    $json["root_kategori"]["root_nama_kategori"] = implode("|", $arr_nama_kategori);
    
    while($data = mysql_fetch_array($rs, MYSQL_ASSOC)){
        list($nama_awal, $nama_akhir) = explode("-", $data["nama_lengkap"]);
        $data["nama_awal"] = trim($nama_awal);
        $data["nama_akhir"] = trim($nama_akhir);
        $arr_fitur = fitur_produk( $data["id_produk"] );
        $data["fitur_id"] = $arr_fitur["fitur_id"];
        $data["fitur_en"] = $arr_fitur["fitur_en"];
        $data["size"] = "";
        $data["warna_en"] = "";
        $data["warna_id"] = "";
        $data["deskripsi_en"] = "";
        $data["deskripsi_id"] = "";
        $data["link"] = product_link_lama( $data["id_produk"] );
        $temp_data = $data;
        foreach( $temp_data as $key=>$value ){
            $data[$key] = iconv('UTF-8', 'UTF-8//IGNORE', $value);
        }
        $json["produk"][] = $data;
    }
}

//print_r($json);exit;
$json = json_encode($json,  JSON_UNESCAPED_UNICODE);
header("Content-Type: application/json");
echo $json;

?>