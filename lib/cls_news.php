<?

class news extends main{
	private $data=array();
	
	public function __set($name, $value){
		$this->data[$name] = $value;
	}
	
	public function __get($name){
		if(isset($this->data[$name]))return $this->data[$name];
	}

	public function __isset($name) {
        return isset($this->data[$name]);
    }
	
	public function __unset($name) {
        unset($this->data[$name]);
    }
	
/* ############################# metode umum ########################################*/
	public function get_news($newsid){
		$sql="select a.newsid, a.newstitle, a.newspreview, a.newscontent, a.newstype, 
			replace(date_format(cast(a.newsdate as date),'%d %M %Y'),'Pebruari','Februari') formatted_newsdate,
			cast(a.newsdate as date) newsdate, year(a.newsdate) newsyear, month(a.newsdate) newsmonth, a.enabled 
			from news a where a.newsid like '%' ";
		if($newsid!="")$sql.=" and a.newsid='".main::formatting_query_string($newsid)."' ";
		if(isset($this->newstype))$sql.=" and a.newstype ".$this->newstype;
		if(isset($this->enabled))$sql.=" and a.enabled=".$this->enabled;
		if(isset($this->search))$sql.=" and 
			(a.newstitle like '%".main::formatting_query_string($this->search)."%' or 
			a.newspreview like '%".main::formatting_query_string($this->search)."%' or 
			a.newscontent like '%".main::formatting_query_string($this->search)."%') ";
		if(isset($this->newsyear)){
			if($this->newsyear!="")$sql.=" and year(a.newsdate)='".main::formatting_query_string($this->newsyear)."'";
		}//else $sql.=" and year(a.newsdate)=(select max(year(newsdate)) from news where newstype=a.newstype and enabled=a.enabled) ";
		
		if(isset($this->newsmonth)){
			if($this->newsmonth!="")$sql.=" and month(newsdate)='".main::formatting_query_string($this->newsmonth)."'";
		}//else $sql.=" and month(a.newsdate)=(select max(month(b.newsdate)) from news b
		//	where b.newstype=a.newstype and year(b.newsdate)=(select max(year(newsdate)) from news where newstype=b.newstype and enabled=true) and b.enabled=a.enabled) ";
		
		if(isset($this->orderby))$sql.=" order by ".main::formatting_query_string($this->orderby);
		else $sql.=" order by newsdate desc";
		$rs=mysql_query($sql) or die();//("get_news error.<br />".mysql_error());
		return $rs;
	}
	
	public static function get_news_period($newstype, $newsyear, $enabled, $limit){
		$sql="select distinct month(newsdate) newsmonth, year(newsdate) newsyear from news a where 
			a.newstype='".main::formatting_query_string($newstype)."' ";
		if($newsyear!="")$sql.=" and year(a.newsdate) ".$newsyear;// db inject risk
//		else  $sql.=" and year(a.newsdate)=(select max(year(newsdate)) from news where newstype=a.newstype and enabled=a.enabled) ";
		if($enabled!="")$sql.=" and a.enabled=".$enabled;
		$sql.=" order by newsdate desc limit ".main::formatting_query_string($limit);
		$rs=mysql_query($sql) or die();//("get_news_month error.<br />".mysql_error());
		return $rs;
	}
	
	public static function get_news_year($newstype, $newsyear, $enabled, $limit){
		$sql="select distinct year(newsdate) newsyear from news a where a.newstype='".main::formatting_query_string($newstype)."' ";
		if($newsyear!="")$sql.=" and year(a.newsdate) ".$newsyear;// db inject risk
//		else  $sql.=" and year(a.newsdate)=(select max(year(newsdate)) from news where newstype=a.newstype and enabled=a.enabled) ";
		if($enabled!="")$sql.=" and a.enabled=".$enabled;
		$sql.=" order by newsdate desc limit ".main::formatting_query_string($limit);
		$rs=mysql_query($sql) or die();//("get_news_year error.<br />".mysql_error());
		return $rs;
	}
	
	public static function get_news_image($newsid){
		$sql="select newsimageid, imageorderid, filename from newsimage where newsid='".main::formatting_query_string($newsid)."' order by imageorderid;";
		$rs=mysql_query($sql) or die();//("get_news_image error.<br />".mysql_error());
		return $rs;
	}
	
	public static function get_news_detail($newsid){
		$news=new news;
		$news->enabled="true";		
		$news->newsyear="";$news->newsmonth="";
		$rs_news=$news->get_news($newsid);
		if(mysql_num_rows($rs_news)>0){		
			$s_news_content=array();//0:title, 1:tanggal, 2:content, 3:type
			$news_content=mysql_fetch_array($rs_news);
			$s_news_content[0]=$news_content["newstitle"];
			$s_news_content[1]=$news_content["formatted_newsdate"];
			$s_news_content[2]=$news_content["newscontent"];
			$rs_news_image=news::get_news_image($newsid);
			while($rs_news_image_=mysql_fetch_array($rs_news_image))
				$s_news_content[2]=str_replace("[img".$rs_news_image_["imageorderid"]."]", "<img src=\"images/news/".$rs_news_image_["filename"]."\" border=\"0\" width=\"100%\" />", $s_news_content[2]);
			$s_news_content[3]=$news_content["newstype"];
			return $s_news_content	;		
		}else return "";
	}

}

?>