<?

include "db_active.v2.php";

if( !isset($_REQUEST["jumlah_suggest"]) || $_REQUEST["jumlah_suggest"] == "" ) die("kurang parameter jumlah_suggest(integer)");

$sql = "select `id` id_produk from products where published = 1";
$rs = mysql_query($sql);

$arr_random = array();

while( true==true ){
    $rand = rand( 0, mysql_num_rows($rs) );
    if( !in_array($rand, $arr_random) ) $arr_random[] = $rand;
    if( count($arr_random) == $_REQUEST["jumlah_suggest"] ) break;
}

while( $data = mysql_fetch_array($rs, MYSQL_ASSOC) )
    $arr_product[] = $data["id_produk"];

foreach($arr_random as $x=>$rand)
    $id_produk[ "id_produk[". $x ."]" ] = $arr_product[ $rand ];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://ecommerce.modena.com/?c=kategori&" . http_build_query($id_produk) );
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, null);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, __CURL_TIMEOUT); 
curl_setopt($ch, CURLOPT_TIMEOUT, __CURL_TIMEOUT);
curl_setopt($ch, CURLOPT_FAILONERROR, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$server_output = curl_exec ($ch);
header("Content-Type: application/json");
echo $server_output;


?>