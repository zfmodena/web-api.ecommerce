<?

$url = 'http://www.bloomberg.com/quote/USDIDR:CUR';
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, $url);
$result = curl_exec($ch);
echo $result;
curl_close($ch);
echo 
"
<script>
location.href='".$page."?seq=".$seq."&acr=".implode(",", $arr_curr_result).",'+price['IDR:CUR'];
</script>
";
exit;
?>
