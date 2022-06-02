<?

class chat extends main{
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
	public static function __insertdata($sessionid, $memberemail, $sender, $message){
		$memberid="null";
		if($memberemail!="")$memberid="(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."')";
		$sql="insert into chat(sessionid, memberid, sender, message) 
			values('".main::formatting_query_string($sessionid)."', ".$memberid.", '".main::formatting_query_string($sender)."', '".main::formatting_query_string($message)."')";
		mysql_query($sql) or die();//("__insertdata error.<br />".mysql_error());
	}
	
	public static function get_last_message($sessionid){
		$sql="select sender, time, message from chat where sessionid='".main::formatting_query_string($sessionid)."' order by time desc limit 1;";
		$rs=mysql_query($sql) or die();//("get_last_message error.<br />".mysql_error());
		return $rs;
	}
	
	public static function load_all_message($sessionid){
		$sql="select sender, time, message from chat where sessionid='".main::formatting_query_string($sessionid)."' order by time;";
		$rs=mysql_query($sql) or die();//("load_all_message error.<br />".mysql_error());
		return $rs;
	}
	
	public static function __insertuser($sessionid, $user_type, $user_name){
		$sql="insert into chat_user(sessionid, user_type, user_name) 
			values('".main::formatting_query_string($sessionid)."', ".main::formatting_query_string($user_type).", '".main::formatting_query_string($user_name)."');";
		mysql_query($sql) or die();//("__insertuser error.<br />".mysql_error());		
	}
	
	public static function get_conversation_mate($sessionid, $user_type){		
		$sql="select sessionid, user_name, user_type, time, response from chat_user where user_type=".main::formatting_query_string($user_type)." ";
		if($sessionid!="")
			$sql.="and sessionid='".main::formatting_query_string($sessionid)."' ";
		else
			$sql.="and sessionid like '%' ";
		$rs=mysql_query($sql) or die();//("get_conversation_mate query error.<br />".mysql_error());
		return $rs;
	}
	
	public static function __terminate_session($sessionid, $user_type){
		$sql="delete from chat_user where user_type=".main::formatting_query_string($user_type)." and sessionid='".main::formatting_query_string($sessionid)."';";
		mysql_query($sql) or die();//("__terminate_session error.<br />".mysql_error());		
	}
	
	public static function __updateresponse($sessionid, $user_type, $response){
		$sql="update chat_user set response=".main::formatting_query_string($response)." 
			where sessionid='".main::formatting_query_string($sessionid)."' and user_type=".main::formatting_query_string($user_type).";";
		mysql_query($sql) or die();//("__updateresponse error.<br />".mysql_error());		
	}

}

?>