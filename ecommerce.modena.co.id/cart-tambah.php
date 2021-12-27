<?

include_once "lib/var.php";
include_once "lib/cls_main.php";
include_once "lib/cls_shoppingcart.php";
include_once "lib/cls_product.php";

if( @$_REQUEST["c"] == "add" ){
	if( array_key_exists($_REQUEST["productid"], $_SESSION["shopping_cart"]) )	die("<script>alert('Produk sudah di keranjang!'); location.href='cart-tambah.php'</script>");
	$_SESSION["shopping_cart"][$_REQUEST["productid"]] = 1;
	shoppingcart::__insert_order($_SESSION["email"]);
	header("location:cart.php");
	exit;
}elseif( @$_REQUEST["c"] == "add_culinaria" ){
	if( array_key_exists($_REQUEST["program_id"], $_SESSION["culinaria_shopping_cart"]) )	die("<script>alert('Produk sudah di keranjang!'); location.href='cart-tambah.php'</script>");
	$_SESSION["culinaria_shopping_cart"][$_REQUEST["program_id"]] = 1;
	shoppingcart::__insert_order($_SESSION["email"]);
	header("location:cart.php");
	exit;
}

function load_product_map($array_kategori_utama_produk){
	$product_content="";
	foreach($array_kategori_utama_produk as $x){
		$parent_category=new product;
		$parent_category->categoryid=$x;
		$rs_parent_category=$parent_category->product_category();
		$rs_parent_category_=mysql_fetch_array($rs_parent_category);
		$product_content.="<li><strong><a href=\"category.php?categoryid=".$x."&c=clearcompare".
			main::sufix_url_with_productid($x)."
			\" class=\"link_white_nochange_hover_orange\">".strtoupper($rs_parent_category_["name"])."</a></strong>";
		
		$category=new product;
		$category->parentcategoryid=$rs_parent_category_["categoryid"];
		$category->orderby="sortorder";
		$category->enabled="true";
		$rs_category=$category->product_category();
		$product_content.="<ul>";
		while($rs_category_=mysql_fetch_array($rs_category)){		
			$product=new product;
			$product->distinct="ok";
			$product->penable="='Y'";
			$product->orderby="sortorder_group, sortorder";
			$rs_product=$product->product_list($rs_category_["categoryid"]);
			if(mysql_num_rows($rs_product)>0){
				$product_content.="<li><strong><a href=\"category-".$x."-".$rs_category_["categoryid"]."-clearcompare.php".
					main::sufix_url_with_productid($x, $rs_category_["name"])
					."\" class=\"link_white_nochange_hover_orange\">".strtoupper($rs_category_["name"])."</a></strong><ul><li style=list-style:circle>";
				unset($new_product_group);
				while($rs_product_=mysql_fetch_array($rs_product)){
					if(isset($new_product_group)&&$new_product_group<$rs_product_["sortorder_group"])$product_content.="</li><li style=list-style:circle>";
					$product_content.="<a href=\"cart-tambah.php?c=add&productid=".$rs_product_["productid"]."\" class=\"link_white_nochange_hover_orange\">".strtoupper($rs_product_["name"])."</a> :: ";
					$new_product_group=$rs_product_["sortorder_group"];
				}
				$product_content.="</li></ul></li>";
			}		
		}$product_content.="</ul></li>";
	}
	return $product_content;
}		

echo "<ul>". load_product_map( array(1, 3, 2,) ) ."</ul>";

// load culinaria
$sql = "select * from culinaria_program where program_awal > current_timestamp";
$rs_culinaria = mysql_query($sql);
while($culinaria = mysql_fetch_array($rs_culinaria)){
	@$string_culinaria .= "<li><a href=\"cart-tambah.php?c=add_culinaria&program_id=". $culinaria["program_id"] ."\">". $culinaria["program_judul_id"] ."</a></li>";
}
echo "<ul>". $string_culinaria ."</ul>";

?>