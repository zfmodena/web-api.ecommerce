<?

class search extends main{
	private $data=array();
	
	public function __set($name, $value){
		$this->data[$name] = $value;
	}
	
	public function __get($name){
		return $this->data[$name];
	}

	public function __isset($name) {
        return isset($this->data[$name]);
    }
	
	public function __unset($name) {
        unset($this->data[$name]);
    }

/* ############################# metode umum ########################################*/

	private static function __generate_temp_table(){
		$rand=rand();
		$check_table=mysql_query("show tables like 'tmp_".$rand."';") or die();//("__generate_temp_table error.<br />".mysql_error());
		if(mysql_num_rows($check_table)<=0){
			$sql="create table tmp_".$rand." (id int auto_increment key, path varchar(500), content text, type int)";
			mysql_query($sql) or die();//("__generate_temp_table error.<br />".mysql_error());
			return "tmp_".$rand;
		}else search::__generate_temp_table();
	}
	
	private static function __destroy_temp_table($rand){
		mysql_query("drop table ".$rand) or die();//(mysql_error());
	}
	
	public static function more_link($arr_search, $search, $counter){
		$ret[0]="<tr><td align=left style=\"line-height:17px; padding-bottom:10px\" id=\"a_".$arr_search."\">
			<strong><a href=\"javascript:__load_result_search('".$arr_search."','".$search."',".$counter.")\" class=link_white_nochange_hover_orange>MORE</a></strong></td></tr>";
		$ret[1]="<strong><a href=\"javascript:__load_result_search('".$arr_search."','".$search."',".$counter.")\" class=link_white_nochange_hover_orange>MORE</a></strong>";
		return $ret;
	}	

// ################ PRODUCT ############################################################
	public static function __search_product($lang_code, $src){
		$sql="select distinct c.categoryid, c.name categoryname, 
			b.categoryid sub_categoryid, b.name sub_categoryname, a.productid, a.name from product a, category b, category c, 
			productproperty d, category_productpropertyschema e, productpropertyschema f
			where a.categoryid=b.categoryid and b.parentcategoryid=c.categoryid and 
			a.productid=d.productid and b.categoryid=e.categoryid and e.productpropertyschemaid=f.productpropertyschemaid and
			b.enabled=true and c.enabled=true and a.penable='Y' and (
				a.name like '%".main::formatting_query_string($src)."%' or
				b.name like '%".main::formatting_query_string($src)."%' or
				c.name like '%".main::formatting_query_string($src)."%' or
				d.value_".$lang_code." like '%".main::formatting_query_string($src)."%' or
				f.name_".$lang_code." like '%".main::formatting_query_string($src)."%'
			)";
		$rs=mysql_query($sql) or die();//(mysql_error());
//		$rs=mysql_query("call __search_product('".$lang_code."', '".str_replace("\'","\\\\\'",$src)."');") or die();//("__search_product query error.<br />".mysql_error());
		return $rs;
	}
	
	public static function search_product_subsection($rs, $counter, $data_per_batch){
		$ret="";$x=0;
		mysql_data_seek($rs, $counter-1);
		while($p=mysql_fetch_array($rs)){					
			$path="detail";
			$par=$p["categoryid"]."-".$p["sub_categoryid"]."-".$p["productid"].".php";
			$caption=strtoupper($p["categoryname"]." :: ".$p["sub_categoryname"]." :: ".$p["name"]);
			$ret.="<tr><td style=\"padding-bottom:10px\"><a href=\"".$path."-".$par."\" class=link_white_nochange_hover_orange>".
				$caption."</a></td></tr>";
			$a_ret[$x][0]=$path;
			$a_ret[$x][1]=$par;
			$a_ret[$x][2]=$caption;
			$x++;			
			
			if($counter%$data_per_batch==0)break;
			$counter++;
		}
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=$a_ret;
		$return[3]=mysql_num_rows($rs);
		return $return;
	}
		
	public function search_product_section($counter, $data_per_batch){
		if(!isset($this->lang_code))throw new Exception("Language code not set");
		if(!isset($this->arr_search_label))throw new Exception("Search label not set");
		if(!isset($this->arr_search))throw new Exception("Search not set");
		if(!isset($this->search))throw new Exception("Search parameter not set");
		$this->search=main::formatting_query_string($this->search);
		
		$ret="<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">";
	   	$rs=search::__search_product($this->lang_code, $this->search);		   
		if(mysql_num_rows($rs)>0){
			$ret.="<tr>
						<td valign=\"top\" style=\"padding-bottom:10px; padding-top:10px\"><strong>".strtoupper($this->arr_search_label)."</strong> :: </td>
						</tr><tr><td style=\"padding-left:20px\"><table cellpadding=0 cellspacing=0 width=100% border=0 id=\"table_".$this->arr_search."\">";
			$tmp=search::search_product_subsection($rs, $counter, $data_per_batch);
			$ret.=$tmp[0];
			$counter=$tmp[1];
			$more_link=search::more_link($this->arr_search, $this->search, ($counter+1));
			if(mysql_num_rows($rs)>$counter)$ret.=$more_link[0];
			$ret.="</table></td></tr>";
		}
		$ret.="</table>";
		return $ret;
	}
//######################### END PRODUCT ##################################################	

//########################## ABOUT US ####################################################

	public static function __load_aboutus_branch($table,$lang_code,$search){
		if($lang_code=="id")$branch="Cabang";
		elseif($lang_code=="en")$branch="Branch";
		$sql="insert into ".$table."(content, type) 
			select concat('".$branch." :: ',right(area,length(area)-4),'<br />',content), 1 
			from distributors where typeid=3 and groups='Branches' and enabled=true and 
			(area like '%".main::formatting_query_string($search)."%' or content like '%".main::formatting_query_string($search)."%');";
		mysql_query($sql) or die();//("__load_aboutus_branch error.<br />".mysql_error());
	}
	
	public static function __load_aboutus($table, $aboutus_path, $folder_aboutus){
		$sql="insert into ".$table."(path, content, type)
			values('aboutus.php?mode=".$aboutus_path."+os=true', '".strtoupper(str_replace("_", " ", $aboutus_path))." :: ".str_replace("'","\'",str_replace("\r\n", " ", strip_tags(file_get_contents($folder_aboutus))))."',1);";
		mysql_query($sql) or die();//("__load_aboutus error.<br />".mysql_error());
	}
	
	public static function __load_all_aboutus($table, $src){
		$sql="select path, content from ".$table." where content like '%".main::formatting_query_string($src)."%';";
		$return=mysql_query($sql) or die();//("__load_all_aboutus query error.<br />".mysql_error());
		return $return;
	}

	public static function search_aboutus_subsection($rs, $counter, $data_per_batch){
		$ret="";$x=0;
		mysql_data_seek($rs, $counter-1);
		while($p=mysql_fetch_array($rs)){					
			$caption=($p["path"]!=""?substr($p["content"],0,300)."...":$p["content"]);
			$ret.="<tr><td style=\"padding-bottom:10px\">".
				$caption.($p["path"]!=""?" <a href=\"".$p["path"]."\" class=link_white_nochange_hover_orange>MORE</a></td></tr>":"");
			$a_ret[$x][0]=$p["path"];
			$a_ret[$x][1]="";
			$a_ret[$x][2]=$caption;
			$x++;			
			
			if($counter%$data_per_batch==0)break;
			$counter++;
		}
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=$a_ret;
		return $return;
	}

	public function search_aboutus_section($counter, $data_per_batch){
		if(!isset($this->lang_code))throw new Exception("Language code not set");	
		if(!isset($this->arr_search_label))throw new Exception("Search label not set");
		if(!isset($this->arr_search))throw new Exception("Search not set");
		if(!isset($this->search))throw new Exception("Search parameter not set");
		$this->search=main::formatting_query_string($this->search);
		if(!isset($this->arr_aboutus))throw new Exception("Array about us not set");
		if(!isset($this->folder_aboutus))throw new Exception("Folder about us not set");

		$ret="<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">";
		$table=search::__generate_temp_table();
	   	search::__load_aboutus_branch($table,$this->lang_code,$this->search);
		for($x=0; $x<count($this->arr_aboutus); $x++){
			search::__load_aboutus($table, $this->arr_aboutus[$x], $this->folder_aboutus.$this->lang_code."_aboutus_".$this->arr_aboutus[$x].".php");
		}
		$rs=search::__load_all_aboutus($table, $this->search);
		if(mysql_num_rows($rs)>0){
			$ret.="<tr>
						<td valign=\"top\"style=\"padding-bottom:10px; padding-top:10px\"><strong>".strtoupper($this->arr_search_label)."</strong> :: </td>
						</tr><tr><td style=\"padding-left:20px\"><table cellpadding=0 cellspacing=0 width=100% border=0 id=\"table_".$this->arr_search."\">";
			$tmp=search::search_aboutus_subsection($rs, $counter, $data_per_batch);
			$ret.=$tmp[0];
			$counter=$tmp[1];
			$more_link=search::more_link($this->arr_search, $this->search, ($counter+1));
			if(mysql_num_rows($rs)>$counter)$ret.=$more_link[0];
			$ret.="</table></td></tr>";
		}
		$ret.="</table>";
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=(isset($tmp[2])?$tmp[2]:0);
		$return[3]=mysql_num_rows($rs);
		search::__destroy_temp_table($table);		
		return $return;			
	}

//########################## END ABOUT US #################################################

//########################## FAQ's #######################################################

	public static function __search_faq($table, $faq_class, $lang_code, $src){
		include_once $faq_class;	
		$sql="select faq_category_id, faq_lang_".$lang_code.", faq_answer_".$lang_code." from 
			faq where enabled=true and (faq_lang_".$lang_code." like '%".main::formatting_query_string($src)."%' or faq_answer_".$lang_code." like '%".main::formatting_query_string($src)."%');";
		$rs=mysql_query($sql) or die();//("__search_faq query error.<br />".mysql_error());
		while($s=mysql_fetch_array($rs)){
			$faq_root=faq::get_faq_root($s["faq_category_id"],"",$lang_code);
			$content=$faq_root."<br />Q : ".$s["faq_lang_".$lang_code]."<br />"."A : ".$s["faq_answer_".$lang_code];
			$sql="insert into ".$table."(path, content, type) values('','".main::formatting_query_string($content)."','1');";
			mysql_query($sql) or die();//(mysql_error());
		}
		$rs=mysql_query("select * from ".$table.";") or die();//(mysql_error());
		return $rs;
	}

	public static function search_faq_subsection($rs, $counter, $data_per_batch){
		$ret="";$x=0;
		mysql_data_seek($rs, $counter-1);
		while($p=mysql_fetch_array($rs)){					
			$path="faq.php";
			$par="fid=+id=";
			$caption=$p["content"];
			$ret.="<tr><td style=\"padding-bottom:10px;\">".$caption."</td></tr>";
			$a_ret[$x][0]=$path;
			$a_ret[$x][1]=$par;
			$a_ret[$x][2]=$caption;
			$x++;			
			
			if($counter%$data_per_batch==0)break;
			$counter++;
		}
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=$a_ret;
		$return[3]=mysql_num_rows($rs);
		return $return;
	}

	public function search_faq_section($counter, $data_per_batch){
		if(!isset($this->lang_code))throw new Exception("Language code not set");
		if(!isset($this->arr_search_label))throw new Exception("Search label not set");
		if(!isset($this->arr_search))throw new Exception("Search not set");
		if(!isset($this->search))throw new Exception("Search parameter not set");
		$this->search=main::formatting_query_string($this->search);
		if(!isset($this->faq_class))throw new Exception("FAQ's class not set");

		
		$ret="<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">";
		$table=search::__generate_temp_table();
	   	$rs=search::__search_faq($table, $this->faq_class, $this->lang_code, $this->search);		   
		if(mysql_num_rows($rs)>0){
			$ret.="<tr>
						<td valign=\"top\" style=\"padding-bottom:10px; padding-top:10px\"><strong>".strtoupper($this->arr_search_label)."</strong> :: </td>
						</tr><tr><td style=\"padding-left:20px\"><table cellpadding=0 cellspacing=0 width=100% border=0 id=\"table_".$this->arr_search."\">";
			$tmp=search::search_faq_subsection($rs, $counter, $data_per_batch);
			$ret.=$tmp[0];
			$counter=$tmp[1];
			$more_link=search::more_link($this->arr_search, $this->search, ($counter+1));
			if(mysql_num_rows($rs)>$counter)$ret.=$more_link[0];
			$ret.="</table></td></tr>";
		}
		$ret.="</table>";
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=(isset($tmp[2])?$tmp[2]:0);
		$return[3]=mysql_num_rows($rs);		
		search::__destroy_temp_table($table);		
		return $return;
	}
// ########################## END FAQ's ####################################################

// ########################## NEWS, PROMOS #################################################

	public static function __search_news($src){
		$sql="select newsid, newstitle, date_format(newsdate,'%d %M %Y') newsdate, 
			case newstype when 1 then 'news' when 2 then 'events' when 3 then 'promotions' when 4 then 'enewsletter' end newstype
			from news where enabled=true and 
			(newstitle like '%".main::formatting_query_string($src)."%' or newscontent like '%".main::formatting_query_string($src)."%' or 
			newstype like '%".main::formatting_query_string($src)."%' or date_format(newsdate, '%d %M %Y') like '%".main::formatting_query_string($src)."%') order by newsdate desc;";
		$rs=mysql_query($sql) or die();//("__search_news query error.<br />".mysql_error());
		return $rs;
	}

	public static function search_news_subsection($rs, $counter, $data_per_batch){
		$ret="";$x=0;
		mysql_data_seek($rs, $counter-1);
		while($p=mysql_fetch_array($rs)){			
			$xpath=$p["newstype"].".php";
			if($p["newstype"]!="enewsletter")$xpath="news.php";							
			$path="";
			$par="";
			$caption="<a href=\"".$xpath."?mode=".$p["newstype"]."&newsid=".$p["newsid"]."&os=true\" class=link_white_nochange_hover_orange>".
				strtoupper($p["newstype"])." - ".$p["newsdate"]."</a><br />".$p["newstitle"];
			$ret.="<tr><td style=\"padding-bottom:10px;\">".$caption." ".$path."</td></tr>";
			$a_ret[$x][0]=$path;
			$a_ret[$x][1]=$par;
			$a_ret[$x][2]=$caption;
			$x++;			
			
			if($counter%$data_per_batch==0)break;
			$counter++;
		}
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=$a_ret;
		$return[3]=mysql_num_rows($rs);
		return $return;
	}

	public function search_news_section($counter, $data_per_batch){
		if(!isset($this->arr_search_label))throw new Exception("Search label not set");
		if(!isset($this->arr_search))throw new Exception("Search not set");
		if(!isset($this->search))throw new Exception("Search parameter not set");
		$this->search=main::formatting_query_string($this->search);

		
		$ret="<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">";
	   	$rs=search::__search_news($this->search);		   
		if(mysql_num_rows($rs)>0){
			$ret.="<tr>
						<td valign=\"top\" style=\"padding-bottom:10px; padding-top:10px\"><strong>".strtoupper($this->arr_search_label)."</strong> :: </td>
						</tr><tr><td style=\"padding-left:20px\"><table cellpadding=0 cellspacing=0 width=100% border=0 id=\"table_".$this->arr_search."\">";
			$tmp=search::search_news_subsection($rs, $counter, $data_per_batch);
			$ret.=$tmp[0];
			$counter=$tmp[1];
			$more_link=search::more_link($this->arr_search, $this->search, ($counter+1));
			if(mysql_num_rows($rs)>$counter)$ret.=$more_link[0];
			$ret.="</table></td></tr>";
		}
		$ret.="</table>";
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=(isset($tmp[2])?$tmp[2]:0);
		$return[3]=mysql_num_rows($rs);		
		return $return;
	}

//########################### END NEWS, PROMOS #############################################

//########################### OUR STORE ###################################################

	public static function __search_store($arr_store, $src){
		$sql="select case typeid 
			when 4 then concat(groups,'_',name,'_',area,'_',content) 
			when 5 then concat(name,'_',content,'_',area,'_',groups)
			when 7 then concat(groups,'_',area,'_',content)
			when 8 then concat(name,'_',content,'_',area)
			when 9 then name
			end content
			from distributors where enabled=true and typeid in (".$arr_store.") and (
				content like '%".main::formatting_query_string($src)."%' or name like '%".main::formatting_query_string($src)."%' or 
				area like '%".main::formatting_query_string($src)."%' or groups like '%".main::formatting_query_string($src)."%'
			);";
		$rs=mysql_query($sql) or die();//("__search_store query error.<br />".mysql_error());
		return $rs;
	}

	public static function search_store_subsection($rs, $counter, $data_per_batch){
		$ret="";$x=0;
		mysql_data_seek($rs, $counter-1);
		while($p=mysql_fetch_array($rs)){			
			$path="";
			$par="";
			$caption="";$sp=preg_split("/_/",$p["content"]);
			for($spx=0; $spx<count($sp); $spx++){
				if(!stristr($sp[$spx],"|"))
					$caption.=$sp[$spx]."<br />";
				else{
					$sp_=preg_split("/\|/",$sp[$spx]);
					$caption.=$sp_[count($sp_)-1]."<br />";
				}
			}
			
			$ret.="<tr><td style=\"padding-bottom:10px;\">".$caption."</td></tr>";
			$a_ret[$x][0]=$path;
			$a_ret[$x][1]=$par;
			$a_ret[$x][2]=$caption;
			$x++;			
			
			if($counter%$data_per_batch==0)break;
			$counter++;
		}
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=$a_ret;
		$return[3]=mysql_num_rows($rs);
		return $return;
	}

	public function search_store_section($counter, $data_per_batch){
		if(!isset($this->arr_search_label))throw new Exception("Search label not set");
		if(!isset($this->arr_search))throw new Exception("Search not set");
		if(!isset($this->search))throw new Exception("Search parameter not set");
		$this->search=main::formatting_query_string($this->search);
		if(!isset($this->arr_store))throw new Exception("Array store not set");
		
		$ret="<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" width=\"100%\">";
	   	$rs=search::__search_store($this->string_from_array($this->arr_store,"key",","), $this->search);		   
		if(mysql_num_rows($rs)>0){
			$ret.="<tr>
						<td valign=\"top\" style=\"padding-bottom:10px; padding-top:10px\"><strong>".strtoupper($this->arr_search_label)."</strong> :: </td>
						</tr><tr><td style=\"padding-left:20px\"><table cellpadding=0 cellspacing=0 width=100% border=0 id=\"table_".$this->arr_search."\">";
			$tmp=search::search_store_subsection($rs, $counter, $data_per_batch);
			$ret.=$tmp[0];
			$counter=$tmp[1];
			$more_link=search::more_link($this->arr_search, $this->search, ($counter+1));
			if(mysql_num_rows($rs)>$counter)$ret.=$more_link[0];
			$ret.="</table></td></tr>";
		}
		$ret.="</table>";
		$return[0]=$ret;
		$return[1]=$counter;
		$return[2]=(isset($tmp[2])?$tmp[2]:0);
		$return[3]=mysql_num_rows($rs);		
		return $return;
	}
//########################### END OUR STORE ################################################

}

?>
