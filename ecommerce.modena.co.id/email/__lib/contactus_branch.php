<?
include_once "lib/cls_supplychain.php";

$arr_location=array(2=>"Indonesia", 1=>"Italy");
$location=$arr_location[2];
if(@$_REQUEST["location"]!="" && in_array($_REQUEST["location"], $arr_location))$location=$_REQUEST["location"];

// menu samping
$s_branch_group="";
foreach($arr_location as $key=>$value){				
		if($key==2){			
			$rs_group_branch=supplychain::get_supplychain_area(3, "='branches'", "true", "area");
			$selected_branch="";$counter=1;
			if(@$_REQUEST["branch"]!="")$selected_branch=$_REQUEST["branch"];
			$check_branch=supplychain::get_supplychain_content(3, "branches","='".main::formatting_query_string($selected_branch)."'", "true", "content");
			if(mysql_num_rows($check_branch)<=0)$selected_branch="";

			while($group_branch=mysql_fetch_array($rs_group_branch)){			
			
				$string_group_branch = ucwords(strtolower(substr($group_branch["area"],3,strlen($group_branch["area"]))));
				if( trim(strtoupper($string_group_branch)) == "DKI JAKARTA" )
					$string_group_branch = "DKI Jakarta";					
				
				if($counter==1){
					$default_branch=$group_branch["area"];
					if(($selected_branch=="" || strtoupper(trim($group_branch["area"]))==strtoupper(trim($selected_branch))) && $location==$value ){
						$selected_branch=$group_branch["area"];
						$s_branch_group.="<li><span class=\"font-grey-01-12\">". $string_group_branch ."</span></li>";
					}else{
						$s_branch_group.="<li><a href=\"javascript:__submit('contactus.php','location=".$value."+branch=".$group_branch["area"]."')\">".
							$string_group_branch ."</a></li>";									
					}
				}else{
					if(strtoupper(trim($group_branch["area"]))==strtoupper(trim($selected_branch)) && $location==$value){
						$s_branch_group.="<li><span class=\"font-grey-01-12\">". $string_group_branch ."</span></li>";					
						$selected_branch=$group_branch["area"];
					}else
						$s_branch_group.="<li><a href=\"javascript:__submit('contactus.php','location=".$value."+branch=".$group_branch["area"]."')\">".
							$string_group_branch ."</a></li>";
				}$counter++;
			}
		}else
			$s_branch_group .= "<li style=\"padding-top:27px\"><a href=\"javascript:__submit('', 'location=".$value."')\">". ucwords(strtolower($value)) ."</a></li>";		
}

// konten
$branch=($selected_branch!=""?$selected_branch:$default_branch);
$supplychain=new supplychain;
if(array_search($location, $arr_location)==2){
	$col1 .= "<span>HEAD OFFICE</span><br>
		Jln. Prof. Dr. Satrio C-4 No. 13<br />Jakarta 12950, Indonesia 
		<!----<br>Tel  : (021) 2996-9500<br>Fax : (021) 2996-9583--><br>
		<a href=\"javascript:showmap('0')\" >Map</a><br /><br />
		<!---<strong>Nationwide Call Center & Customer Care<br />15.007.15</strong><br />--><br />
		<!--Nationwide Call Center (pulsa lokal)<br />0807.1.MODENA (663362)<br /><br />-->";
	$col1 .=  $lang_contactus_note_for_online_sales;
	$supplychain->area=$branch;
}
$rs_supplychain=$supplychain->get_supplychain(array_search($location, $arr_location), "");
while($rs_supplychain_=mysql_fetch_array($rs_supplychain))
	if($rs_supplychain_["content"] != "")
		$col1 .=  $rs_supplychain_["content"] . 
			( $rs_supplychain_["distributorsid"] != 4 ? "<br /><a href=\"javascript:showmap('".$rs_supplychain_["distributorsid"]."')\" >Map</a><br /><br />" : "" ) ;

if(array_search($location, $arr_location)==2){	
	if ($branch=="01. DKI JAKARTA") {
	$col2 .= "";
	} else {
	$col2 .=  "<span>BRANCH<br />". ucwords(strtoupper(substr($branch,3,strlen($branch)))) ."</span>";
		$rs_area_branch=supplychain::get_supplychain_content(3, "branches","='".main::formatting_query_string($branch)."'", "true", "content");
		while($area_branch=mysql_fetch_array($rs_area_branch))
			$col2 .=  "<br />". $area_branch["content"] . "<br /><a href=\"javascript:showmap('".$area_branch["distributorsid"]."')\" >Map</a>" ."<br />";
	};
	}
?>
<table width="100%" border="0" cellspacing="0" cellpadding="0" height="190px" class="font-grey-02-12" >
  <tr>
    <td width="156px" valign="top" id="menu_contactus_branch">
		<ul><?=$s_branch_group?></ul>
	</td>
    <td valign="top" id="content_contactus_branch">		
		<table width="624px" cellpadding="0" cellspacing="0" align="right" border="0" class="font-grey-02-12" >
			<tr>
				<td valign="top" style="width:300px"><?=$col1?></td>
				<td valign="top" style="padding-left:13px"><?=$col2?></td>
			<tr>
		</table>
	</td>
  </tr>
</table>
<script>
function showmap(id){
	window.open('map.php?map='+id,'map','width=700, height=600');void(0)
}
</script>
<style>
#menu_contactus_branch, #content_contactus_branch{
	padding-top:27px;
}
#menu_contactus_branch li, #content_contactus_branch table td{
	list-style-type:none;
	line-height:27px;
	letter-spacing:0.05em;
}
#content_contactus_branch table td span, #content_contactus_branch table td strong{
	font-weight:900;
}
#content_contactus_branch table td a{
	text-decoration:underline;
}
</style>