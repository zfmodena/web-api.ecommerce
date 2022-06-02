<?
include_once "../mainclass.php";

/* kurs mata uang menggunakan dari masterweb.net 
echo "<script>parent.greylayer('template/waiting.html')</script>";
include_once "mwn.php";
goto SkipAll;*/

/* konfigurasi sumber kurs */
$arr_currency=array(
	1=>	"download.finance.yahoo.com", 
		"themoneyconverter.com", 
		"usd.fx-exchange.com", 
		"bloomberg.com"
);

$seq=1;
$arr_curr_result[]=@$_REQUEST["acr"];
	
if(@$_REQUEST["seq"]!=""){
	$seq=(@$_REQUEST["seq"]!=""?$_REQUEST["seq"]:1)+1;
	if(file_exists($arr_currency[$seq].".php"))
		include_once $arr_currency[$seq].".php";
}else{
	if(file_exists($arr_currency[1].".php"))
		include_once $arr_currency[1].".php";
}		

if($seq<=count($arr_currency)){
	header("location:$page?seq=$seq&acr=".implode(",", $arr_curr_result));
	exit;
}

$curr_result=trim(implode(",", $arr_curr_result));
$curr_result=substr($curr_result,0,1)==","?substr($curr_result,1,strlen($curr_result)):$curr_result;
$arr_curr_result=explode(",", $curr_result);
sort($arr_curr_result, SORT_NUMERIC);

SkipAll:
// kurs terkecil dikurangi dengan 15%, sbg pencegahan agar tidak lebih rendah dari kurs-nya Paypal
echo "
<script>
parent.SetCurrencyConversion('".($arr_curr_result[0]*0.85)."');
parent.TINY.box.hide();
</script>
";

?>