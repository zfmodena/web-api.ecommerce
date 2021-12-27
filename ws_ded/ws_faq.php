<?

$arr_data = array();

$lang = in_array(@$_REQUEST["lg"], array("id", "en") ) ? $_REQUEST["lg"] : "id";

$arr_sql_src_ = array();
$arr_sql_src = array("faq_lang_$lang", "faq_answer_$lang", "b.faq_category_lang_$lang", "b.faq_category_desc_$lang", "c.faq_category_lang_$lang", "c.faq_category_desc_$lang", "a.tag");
$status_tidak_ditemukan["id"] = "Data tidak ditemukan. Mohon gunakan kata kunci yang lain.";
$status_tidak_ditemukan["en"] = "Data not found. Please use another keyword.";


$arr_tag_q = explode(" ", $_REQUEST["q"]);
foreach( $arr_sql_src as $sql_src )	{
	foreach( $arr_tag_q as $q )
		$arr_sql_src_[] = $sql_src . " like '%". main::formatting_query_string( $q ) ."%' ";
}
$string_sql_parameter = " ( " . implode(" or ", $arr_sql_src_) . " ) and c.faq_category_id not in (5) ";

if( @$_REQUEST["faq_id"] != "" )
	$string_sql_parameter = " a.faq_id = '". main::formatting_query_string($_REQUEST["faq_id"]) ."' ";


$sql = "select a.*, b.* from faq a inner join faq_category b on a.faq_category_id = b.faq_category_id 
	inner join faq_category c on b.faq_category_id_parent = c.faq_category_id where " . $string_sql_parameter ;
			
$rs = mysql_query( $sql ) or die( mysql_error() );
if( mysql_num_rows( $rs ) > 0 )
	while( $data = mysql_fetch_array( $rs ) )	$arr_data[] = $data;
else
	$arr_data[] = array("status" => $status_tidak_ditemukan[ $lang ]);

echo json_encode( $arr_data );

?>