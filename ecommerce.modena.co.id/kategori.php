<?

if( isset($_REQUEST["ovr"]) && $_REQUEST["ovr"] != "" ) goto Tetap_pakai_yang_baru;

// temporary sblm active aktif
//include "../lib/var.php";
//include "../lib/cls_main.php";
//include "kategori_lama.php";
//exit;

Tetap_pakai_yang_baru:

// utk yang ambil dari database active digital, harus include sbb
include "db_active.v2.php";

function parent_kategori($id){
    $return = array();
    $sql = "select id, name, parent_id from categories where id = '".main::formatting_query_string($id)."'";
    $rs = mysql_query($sql);
    if(mysql_num_rows($rs)> 0 ){
        $data = mysql_fetch_array($rs, MYSQL_ASSOC);
        $return = array("id" => $data["id"], "parent_id" => $data["parent_id"], "kategori" => $data["name"]);
    }
    return $return;
}

function formatting_parent_kategori($id){
    $arr_parent_kategori = $arr_parent_nama_kategori = $arr_parent_id_kategori = array();
    $counter = 1;
    while(true == true){
        if(@$pid == "") $pid = $id;
        else $pid = $temp_pid;
        
        $data_parent_kategori = parent_kategori($pid);
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

function product_link($pid, $mode = ""){
    //$link = "https://development.modena.com/products/" . $pid; 
    //if( $mode == "category" )
        $link = "https://www.modena.com/id/" . $pid; 
    return $link;
}

function category_link($parent_category, $id_kategori){
    $url_category_id = explode("|", $parent_category["root_id_kategori"]);
    $url_category_nama = explode("|", $parent_category["root_nama_kategori"]);
    $url_category = product_link( $url_category_nama[0] == "home-appliance" ? ( $url_category_nama[1] != "" ? $GLOBALS["arr_main_category"][ $url_category_id[1]]["slug"] : $data["nama_kategori"] ) : $url_category_nama[0], "category" );
    
    if( count($url_category_nama) > 1 ){
        if( $url_category_nama[0] == "home-appliance" ){
            if( count($url_category_nama) > 2 ){
                $index_category = 1;    
                if( count($url_category_nama) > 3 ) $index_category = 2;    
                //$url_category .= "?category=" . $url_category_id[count($url_category_id)- $index_category] . "&subcategory=" . $id_kategori;
            }//else
                //$url_category .= "?category=" . $id_kategori;
        }else{
            $index_category = 1;
            //if( count($url_category_nama) > 2 ) $index_category = 2;
            //$url_category .= "?category=" . $url_category_id[count($url_category_id)-$index_category] . "&subcategory=" . $id_kategori;    
        }
    }else{
        //if( $url_category_nama[0] != "home-appliance" )
            //$url_category .= "?category=" . $id_kategori;    
    }
    $url_category_slug[] = $url_category;
    $url_category_slug[] = $url_category_nama[count($url_category_nama)-$index_category];
    $url_category_slug[] = $id_kategori == "modena" ? "appliances" : $id_kategori;
    $url_category = implode("/", $url_category_slug);
    return "https://" . str_replace(array("https://","///", "//"), array("","/", "/"), $url_category);
    //return $url_category;    
}

$json = array();
$parent_category = formatting_parent_kategori($_REQUEST["id"]);
$json["root_kategori"] = $parent_category;

$arr_main_category = array(
        2 => array("slug"=>"appliances", "label" => "MODENA"), 
        24 => array("slug"=>"gourmet", "label" => "MODENA Gourmet"), 
        36 => array("slug"=>"professional", "label" => "MODENA Professional")
    );

//$category = "a.parent_id is null";
$category = " a.id in (". implode(",", array_keys($arr_main_category)) .") ";
$category_orderby = " order by FIELD(ID,". implode(",", array_keys($arr_main_category)) .")";
if( @$_REQUEST["id"] != "" )
    $category = "a.parent_id = '". main::formatting_query_string($_REQUEST["id"]) ."' ";

$sql = "select a.id id_kategori, a.name nama_kategori 
        from categories a where " . $category . $category_orderby;
$rs = mysql_query($sql) or die(mysql_error());

while($data = mysql_fetch_array($rs, MYSQL_ASSOC)){
    $url_category = category_link($parent_category, $data["nama_kategori"]);
    //if( $parent_category["root_id_kategori"] != "" )
        $data["link"] = $url_category;
    $json["kategori"][] = $data;
}

// loading kategori
$show_url_category = false;
if( 
    (!isset($_REQUEST["grup"]) || ( isset($_REQUEST["grup"]) && $_REQUEST["grup"] == "" )) &&   
        ( 
            (!isset($_REQUEST["cari"]) || ( isset($_REQUEST["cari"]) && $_REQUEST["cari"] == "" )) && 
            (!isset($_REQUEST["id_produk"]) || ( isset($_REQUEST["id_produk"]) && $_REQUEST["id_produk"] == "" )) 
        ) 
    ){
    $sql = "select distinct /*'' id_produk, '' nama_awal, '' nama_akhir, '' nama_lengkap, 0 harga, '' fitur_id, 
        '' fitur_en, '' size, '' warna_en, '' warna_id, 
        '' deskripsi_en, '' deskripsi_id, '' sku, 0 diskon, '' keterangan_diskon,*/ a.title grup /*, '' foto, '' slug*/
        from products a left outer join discounts b on a.discount_id = b.id where a.category_id = '". main::formatting_query_string($_REQUEST["id"]) ."' and a.published = 1";
    $show_url_category = true;
}else{
    $sql = "select a.category_id, a.id id_produk, a.title nama_awal, a.second_title nama_akhir, concat(a.title, ' - ', second_title) nama_lengkap, a.price harga, replace(replace(replace(replace(a.features, '</div><div>', ';'),'<div>',''),'</div>',''),'<br>','') fitur_id, 
        replace(replace(replace(replace(a.features, '</div><div>', ';'),'<div>',''),'</div>',''),'<br>','') fitur_en, replace(replace(replace(replace(lower(a.sizing), '</div><div>', ';'),'<div>',''),'</div>',''),'<br>','') size, '' warna_en, '' warna_id, 
        a.description deskripsi_en, a.description deskripsi_id, replace(replace(sku,'/',''),'-','') sku, ifnull(case when b.type = 'percent' then b.nominal/100 else b.nominal end, 0) diskon, b.title keterangan_diskon, weight berat, 
        concat('storage/',c.path,'/',c.file_name) foto, a.slug
        from products a left outer join discounts b on a.discount_id = b.id left outer join media c on a.id = c.mediable_id inner join (select mediable_id, min(id) id_media from media where mediable_type like '%Product' and content_type is null group by mediable_id) d on c.mediable_id = d.mediable_id and c.id = d.id_media
        where c.mediable_type like '%Product' and c.content_type is null  and a.published = 1 
        ";
    $show_url_category = true;
    if( isset($_REQUEST["cari"]) && $_REQUEST["cari"] != "" ) 
        $sql .= " and ( replace(a.title,' ','') like '%". main::formatting_query_string(str_replace(" ","",$_REQUEST["cari"])) ."%' or replace(a.second_title, ' ','') like '%". main::formatting_query_string(str_replace(" ","",$_REQUEST["cari"])) ."%' ) ";
    elseif( isset($_REQUEST["id_produk"]) && $_REQUEST["id_produk"] != "" ) {
        if( is_array($_REQUEST["id_produk"]) && count($_REQUEST["id_produk"]) > 0 ){
            $sql .= " and a.id in (". implode(",", main::formatting_query_string( $_REQUEST["id_produk"] ) ) .")";
            
        }else
            $sql .= " and a.id = '". main::formatting_query_string( $_REQUEST["id_produk"] ) ."'";
    }else
        $sql .= " and a.category_id = '". main::formatting_query_string($_REQUEST["id"]) ."' and a.title = '". main::formatting_query_string($_REQUEST["grup"]) ."' ";
}

$rs = mysql_query($sql) or die();
if( mysql_num_rows($rs) > 0 ){
    
    // reset data root_kategori dan pisahkan kategori dari root_kategori
    $arr_id_kategori = explode("|", $parent_category["root_id_kategori"]);
    $arr_nama_kategori = explode("|", $parent_category["root_nama_kategori"]);
    $category_link = category_link($parent_category, $arr_nama_kategori[ count($arr_nama_kategori)-1 ]);
    
    $json["kategori"][] = array("id_kategori" => $arr_id_kategori[ count($arr_id_kategori)-1 ], "nama_kategori" => $arr_nama_kategori[ count($arr_nama_kategori)-1 ]) + ($show_url_category ? array("link" => $category_link) : array(  ));
    unset($arr_id_kategori[ count($arr_id_kategori)-1 ], $arr_nama_kategori[ count($arr_nama_kategori)-1 ]);
    $json["root_kategori"]["root_id_kategori"] = implode("|", $arr_id_kategori);
    $json["root_kategori"]["root_nama_kategori"] = implode("|", $arr_nama_kategori);
    
    if( (isset($_REQUEST["cari"]) && $_REQUEST["cari"] != "") || (isset($_REQUEST["id_produk"]) && $_REQUEST["id_produk"] != "")  ) 
        unset($json["kategori"], $json["root_kategori"]);
    
    while($data = mysql_fetch_array($rs, MYSQL_ASSOC)){
        
        if( (isset($_REQUEST["cari"]) && $_REQUEST["cari"] != "") || (isset($_REQUEST["id_produk"]) && $_REQUEST["id_produk"] != "") ) {
            $parent_category = formatting_parent_kategori($data["category_id"]);
            $data["root_kategori"]["root_id_kategori"] = $parent_category["root_id_kategori"];
            $data["root_kategori"]["root_nama_kategori"] = $parent_category["root_nama_kategori"];
        }
        unset( $data["category_id"] );
        
        $temp_data = $data;
        foreach( $temp_data as $key=>$value ){
                $data[$key] = !is_array($value) ? iconv('UTF-8', 'UTF-8//IGNORE', $value) : $value;
        }
        if( isset($data["foto"]) ){
            $data["foto"] = "https://www.modena.com/id/public/" . $data["foto"];
            //die(category_link($parent_category, $arr_nama_kategori[ count($arr_nama_kategori) ]));
            $data["link"] = $category_link . "/" . $data["slug"]; //product_link( $data["slug"] );
        }
        $json["produk"][] = $data;
    }
}

//print_r($json);exit;
$json = json_encode($json,  JSON_UNESCAPED_UNICODE);
header("Content-Type: application/json");
echo $json;

?>