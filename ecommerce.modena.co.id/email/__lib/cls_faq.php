<?

class faq extends main{
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

	public function get_faq_category(){
		$sql="select faq_category_id, faq_category_id_parent, 
			faq_category_lang_id, faq_category_lang_en, 
			faq_category_desc_id, faq_category_desc_en, 
			enabled, sortorder
			from faq_category where faq_category_id is not null ";
		if(isset($this->faq_category_id))$sql.=" and faq_category_id='".main::formatting_query_string($this->faq_category_id)."' ";	
		if(isset($this->faq_category_id_parent))$sql.=" and faq_category_id_parent ".$this->faq_category_id_parent." ";	
		if(isset($this->faq_category_src))
			$sql.=" and (faq_category_lang_id like '%".main::formatting_query_string($this->faq_category_src)."%' or 
				faq_category_lang_en like '%".main::formatting_query_string($this->faq_category_src)."%' or
				faq_category_desc_id like '%".main::formatting_query_string($this->faq_category_src)."%' or
				faq_category_desc_en like '%".main::formatting_query_string($this->faq_category_src)."%')";	
		if(isset($this->enabled))$sql.=" and enabled ".main::formatting_query_string($this->enabled)." ";
		$sql.="order by sortorder";
		$return=mysql_query($sql) or die();//("get_faq_category query error.<br />".mysql_error());
		return $return;
	}
	
	public function get_faq(){
		$sql="select faq_id, faq_category_id, 
			faq_lang_id, faq_lang_en, 
			faq_answer_id, faq_answer_en, 
			enabled, sortorder 
			from faq where faq_id is not null ";
		if(isset($this->faq_id))$sql.="and faq_id='".main::formatting_query_string($this->faq_id)."'";
		if(isset($this->faq_category_id))$sql.="and faq_category_id ".main::formatting_query_string($this->faq_category_id)." ";
		if(isset($this->faq_src))
			$sql.="and (faq_lang_id like '%".main::formatting_query_string($this->faq_src)."%' or
				faq_lang_en like '%".main::formatting_query_string($this->faq_src)."%' or
				faq_answer_id like '%".main::formatting_query_string($this->faq_src)."%' or
				faq_answer_en like '%".main::formatting_query_string($this->faq_src)."%')";
		if(isset($this->enabled))$sql.="and enabled ".main::formatting_query_string($this->enabled)."";
		$sql.=" order by sortorder";
		$return=mysql_query($sql) or die();//("get_faq query error.<br />".mysql_error());
		return $return;
	}
	
	private static function get_faq_sub2_category($rs_faq_sub_category_,$selected_faq_sub_sub_category_id,$lang){
		$faq_sub2_category=new faq;
		$faq_sub2_category->faq_category_id_parent="=".$rs_faq_sub_category_;
		$faq_sub2_category->enabled="=true";
		$rs_faq_sub2_category=$faq_sub2_category->get_faq_category();
		if(mysql_num_rows($rs_faq_sub2_category)>0){
			$s_faq_sub_category="<table cellpadding=0 cellspacing=0 border=0 width=100% style=\"padding-bottom:5px; padding-top:5px\">";
			$counter=1;
			while($rs_faq_sub2_category_=mysql_fetch_array($rs_faq_sub2_category)){
				$s_faq_sub_category.="<tr><td width=10px valign=top align=left style=\"padding-bottom:5px\">&bull;</td><td>";
				if($counter==1&&$selected_faq_sub_sub_category_id=="")$selected_faq_sub_sub_category_id=$rs_faq_sub2_category_["faq_category_id"];
				if(@$selected_faq_sub_sub_category_id!=$rs_faq_sub2_category_["faq_category_id"])
					$s_faq_sub_category.="<a href=\"javascript:__submit('faqs.php#".main::friendlyURL($rs_faq_sub2_category_["faq_category_desc_".$GLOBALS["lang"]])."', 'id=".$rs_faq_sub2_category_["faq_category_id"]."')\" 
					class=link_white_nochange_hover_orange>".$rs_faq_sub2_category_["faq_category_lang_".$lang]."</a>";
				else $s_faq_sub_category.=$rs_faq_sub2_category_["faq_category_lang_".$lang]."";							
				$s_faq_sub_category.="</td></tr>";
				$counter++;
			}$s_faq_sub_category.="</table>";
		}
		$return[0]=@$s_faq_sub_category;
		$return[1]=$selected_faq_sub_sub_category_id;
		return $return;
	}
	
	public static function get_faq_sub_category($selected_faq_category_id, $selected_faq_sub_category_id, $selected_faq_sub_sub_category_id, $show_sfsci, $lang){
		if($selected_faq_sub_sub_category_id!="") $selected_faq_sub_sub_category_id__=faq::get_faq_descendant_id("=".$selected_faq_sub_sub_category_id,"");
		if(@$selected_faq_sub_sub_category_id__!=""){
			$arr_selected_faq_sub_sub_category_id__=explode(",",$selected_faq_sub_sub_category_id__);
			$selected_faq_sub_sub_category_id=$arr_selected_faq_sub_sub_category_id__[0];
		}
		
		$faq_sub_category=new faq;
		$faq_sub_category->faq_category_id_parent="='".$selected_faq_category_id."'";
		$faq_sub_category->enabled="=true";
		$rs_faq_sub_category=$faq_sub_category->get_faq_category();
		$s_faq_sub_category="<table cellpadding=0 cellspacing=0 border=0 width=100% style=\"padding-bottom:5px; padding-top:5px\">";
		$counter=1;
		while($rs_faq_sub_category_=mysql_fetch_array($rs_faq_sub_category)){
			$s_faq_sub_category.="<tr><td width=10px valign=top align=left style=\"padding-bottom:5px\">&bull;</td><td>";
			
			if($selected_faq_sub_category_id==""&&$counter==1){
				$first_faq_sub_category_id=$rs_faq_sub_category_["faq_category_id"];
				$default_faq_sub_category_id=$rs_faq_sub_category_["faq_category_id"];
				if($show_sfsci){
					$s_faq_sub_category.=$rs_faq_sub_category_["faq_category_lang_".$lang];
					//sub-sub kategori faq, jika ada
					$s_faq_sub2_category=faq::get_faq_sub2_category($rs_faq_sub_category_["faq_category_id"],$selected_faq_sub_sub_category_id,$lang);
					$s_faq_sub_category.=$s_faq_sub2_category[0];
					$default_faq_sub2_category_id=$s_faq_sub2_category[1];
					
				}else
					$s_faq_sub_category.="<a href=\"javascript:__submit('faqs.php#".main::friendlyURL($rs_faq_sub_category_["faq_category_desc_".$GLOBALS["lang"]])."','fid=".$selected_faq_category_id."|".$rs_faq_sub_category_["faq_category_id"]."+id=')\" class=link_white_nochange_hover_orange>"
						.$rs_faq_sub_category_["faq_category_lang_".$lang]."</a>";
			}else{
				if($counter==1)$first_faq_sub_category_id=$rs_faq_sub_category_["faq_category_id"];
				if($selected_faq_sub_category_id==$rs_faq_sub_category_["faq_category_id"]){
					$default_faq_sub_category_id=$rs_faq_sub_category_["faq_category_id"];
					if($show_sfsci){
						$s_faq_sub_category.=$rs_faq_sub_category_["faq_category_lang_".$lang];
						$s_faq_sub2_category=faq::get_faq_sub2_category($rs_faq_sub_category_["faq_category_id"],$selected_faq_sub_sub_category_id,$lang);
						$s_faq_sub_category.=$s_faq_sub2_category[0];
						$default_faq_sub2_category_id=$s_faq_sub2_category[1];
					}else
						$s_faq_sub_category.="<a href=\"javascript:__submit('faqs.php#".main::friendlyURL($rs_faq_sub_category_["faq_category_desc_".$GLOBALS["lang"]])."','fid=".$selected_faq_category_id."|".$rs_faq_sub_category_["faq_category_id"]."+id=')\" class=link_white_nochange_hover_orange>"
							.$rs_faq_sub_category_["faq_category_lang_".$lang]."</a>";
				}else
					$s_faq_sub_category.="<a href=\"javascript:__submit('faqs.php#".main::friendlyURL($rs_faq_sub_category_["faq_category_desc_".$GLOBALS["lang"]])."','fid=".$selected_faq_category_id."|".$rs_faq_sub_category_["faq_category_id"]."+id=')\" class=link_white_nochange_hover_orange>"
						.$rs_faq_sub_category_["faq_category_lang_".$lang]."</a>";
			}
					
			$s_faq_sub_category.="</td></tr>";
			$counter++;
		}
		if(@$default_faq_sub_category_id=="")$default_faq_sub_category_id=$first_faq_sub_category_id;
		$s_faq_sub_category.="</table>";
		$return[0]=$s_faq_sub_category;
		$return[1]=$default_faq_sub_category_id;
		$return[2]=@$default_faq_sub2_category_id;
		return $return;
	}
	
	public static function get_faq_root($faq_category_id, $faq_category, $lang){
		$sql="select faq_category_id_parent, faq_category_lang_id, faq_category_lang_en 
			from faq_category where enabled=true and faq_category_id='".main::formatting_query_string($faq_category_id)."';";
		$rs=mysql_query($sql) or die();//("get_faq_root query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0){
			$rs_=mysql_fetch_array($rs);
			return faq::get_faq_root($rs_["faq_category_id_parent"], $rs_["faq_category_lang_".$lang]." :: ".$faq_category, $lang);
		}else{
			return trim(substr($faq_category, 0, strlen($faq_category)-3));
		}	
	}

	public static function get_faq_root_id($faq_category_id, $faq_category){
		$sql="select faq_category_id_parent
			from faq_category where enabled=true and faq_category_id='".main::formatting_query_string($faq_category_id)."';";
		$rs=mysql_query($sql) or die();//("get_faq_root_id query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0){
			$rs_=mysql_fetch_array($rs);
			if($rs_["faq_category_id_parent"]!="") return faq::get_faq_root_id($rs_["faq_category_id_parent"], $rs_["faq_category_id_parent"]."|".$faq_category);
			else return trim(substr($faq_category, 0, strlen($faq_category)));
		}else{
			return trim(substr($faq_category, 0, strlen($faq_category)));
		}	
	}
	
	public static function get_faq_descendant_id($faq_category_id, $faq_category){
		$sql="select faq_category_id
			from faq_category where enabled=true and faq_category_id_parent ".main::formatting_query_string($faq_category_id)." order by sortorder;";
		$rs=mysql_query($sql) or die();//("get_faq_descendant_id query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0){
			$tmp="";
			while($rs_=mysql_fetch_array($rs))
				$tmp.=$rs_["faq_category_id"].",";				
			return faq::get_faq_descendant_id("in (".substr($tmp,0,strlen($tmp)-1).")", $tmp.$rs_["faq_category_id"]);
		}else{
			return trim(substr($faq_category, 0, strlen($faq_category)-1));
		}		
	}
	
// UTK DI SECURE
	public static function get_use_number_of_category($categoryid, $mode /*category|faq*/){
		if($mode=="category")
			$sql="select 1 from faq_category where faq_category_id_parent='".main::formatting_query_string($categoryid)."';";
		elseif($mode=="faq")
			$sql="select 1 from faq where faq_category_id='".main::formatting_query_string($categoryid)."';";
		$rs=mysql_query($sql) or die();//("get_use_of_category query error.<br />".mysql_error());
		return mysql_num_rows($rs);
	}
	

}

?>