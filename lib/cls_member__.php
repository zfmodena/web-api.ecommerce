<?

class member extends main{
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
	public function member_data($memberid){
		/* parameter : email, password, enabled, orderby */
		$sql="select a.memberid, a.name, a.email, a.password, replace(a.address,'\r\n','') address, /*a.homecity,*/ a.homeregion, c.region homecity, a.homestate, b.state, a.homepostcode, a.homecountry, a.phone, a.handphone, a.milis, date_format(a.dateofbirth, '%d %M %Y') dateofbirth, case a.download_auth when true then 1 else 0 end download_auth, a.membercode
			from membersdata a left outer join shipment_state b on a.homestate=b.state_id left outer join shipment_exception c on a.homeregion=c.region_id where ";
		if($memberid!="")$sql.="a.memberid like '".$memberid."' ";
		if(isset($this->email))$sql.=" and a.email ".$this->email;
		if(isset($this->password))$sql.=" and a.password ".$this->password;
		if(isset($this->enabled))$sql.=" and a.enabled ".$this->enabled;
		if(isset($this->membercode))$sql.=" and a.membercode ".$this->membercode;
		if(isset($this->additional_parameter))$sql.=$this->additional_parameter;
		if(isset($this->orderby))$sql.=" order by ".$this->orderby;
		$result=mysql_query($sql) or die();//("member query error.<br />".mysql_error());
		return $result;
	}
	
	public static function mailing_list_member_data($email, $src){
		$sql="select email from enewsmember where email='".main::formatting_query_string($email)."' ";
		if($src!="")$sql.="and email like '%".$src."%' ";
		$result=mysql_query($sql) or die();//("mailing_list_member_data error.<br />".mysql_error());
		return $result;
	}
	
	public static function member_data_information($memberemail, $information){
		$member=new member;
		$member->enabled="=true";
		$member->email="='".$memberemail."'";
		$rs_member=$member->member_data("%");
		$rs_member_=mysql_fetch_array($rs_member, MYSQL_ASSOC);
		$return=$rs_member_[$information];
		return $return;	
	}
	
	public static function member_data_information_orig($s_enc, $information, $enabled, $enc_type /* 0: md5 | 1:sha1*/){
		$return="";
		$member=new member;
		$member->enabled="=".$enabled;
		$rs_member=$member->member_data("%");
		while($rs_member_=mysql_fetch_array($rs_member)){
			if($enc_type==0)	{
				if($s_enc==md5($rs_member_[$information])){$return=$rs_member_[$information];break;}
			}elseif($enc_type==1){
				if($s_enc==sha1($rs_member_[$information])){$return=$rs_member_[$information];break;}
			}
		}		
		return $return;			
	}
	
	public function member_data_billing($billingid){
		/* parameter : email, enabled, orderby */
		$sql="select a.memberid,a.billingid,a.billing_first_name,a.billing_last_name,
			a.billing_address,a.billing_city,a.billing_state,a.billing_state state,a.billing_postcode, a.billing_country,
			a.billing_phone,a.billing_handphone,
			a.modified_date,a.enabled from membersbilling a/*, shipment_state b*/ where a.billingid is not null /*a.billing_state=b.state_id*/ ";
		if($billingid!="")$sql.="and a.billingid like '".main::formatting_query_string($billingid)."' ";
		if(isset($this->email))$sql.="and memberid=(select memberid from membersdata where email='".main::formatting_query_string($this->email)."') ";
		if(isset($this->enabled))$sql.="and a.enabled ".main::formatting_query_string($this->enabled);
		if(isset($this->orderby))$sql.=" order by ".main::formatting_query_string($this->orderby);
		$result=mysql_query($sql) or die();//("member billing query error.<br />".mysql_error());
		return $result;
	}
	
	public static function __entri_member_data_billing($memberemail){
		$sql="insert into membersbilling(memberid) select memberid from membersdata where email='".main::formatting_query_string($memberemail)."';";
		mysql_query($sql) or die();//("member billing insert error.<br />".mysql_error());
		$sql="select max(billingid) billingid from membersbilling where memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."');";
		$rs_member_id=mysql_query($sql) or die();//("member billing id query error.<br />".mysql_error());
		$rs_member_id_=mysql_fetch_array($rs_member_id, MYSQL_ASSOC);
		return $rs_member_id_["billingid"];
	}
	
	public function member_data_shipping($shippingid){
		/* parameter : email, enabled, orderby */
		$sql="select a.memberid,a.shippingid,a.shipping_name,
			a.shipping_address,a.shipping_region, c.region shipping_city,a.shipping_state,b.state,a.shipping_postcode, a.shipping_country,
			a.shipping_phone,a.shipping_handphone, 
			a.modified_date,a.enabled from membersshipping a, shipment_state b, shipment_exception c where a.shipping_state=b.state_id and a.shipping_region=c.region_id ";
		if($shippingid!="")$sql.="and a.shippingid like '".main::formatting_query_string($shippingid)."' ";
		if(isset($this->email))$sql.="and memberid=(select memberid from membersdata where email='".main::formatting_query_string($this->email)."') ";
		if(isset($this->enabled))$sql.="and a.enabled ".main::formatting_query_string($this->enabled);
		if(isset($this->orderby))$sql.=" order by ".main::formatting_query_string($this->orderby);
		$result=mysql_query($sql) or die();//("member shipping query error.<br />".mysql_error());
		return $result;
	}
	
	public static function __entri_member_data_shipping($memberemail){
		$sql="insert into membersshipping(memberid) select memberid from membersdata where email='".main::formatting_query_string($memberemail)."';";
		mysql_query($sql) or die();//("member shipping insert error.<br />".mysql_error());
		$sql="select max(shippingid) shippingid from membersshipping where memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."');";
		$rs_member_id=mysql_query($sql) or die();//("member shipping id query error.<br />".mysql_error());
		$rs_member_id_=mysql_fetch_array($rs_member_id, MYSQL_ASSOC);
		return $rs_member_id_["shippingid"];
	}
	
	public static function check_shipping_address($memberemail, $shipping_state, $shipping_region){
		$sql="select 1 from membersshipping a/*, branch_service b where a.shipping_state=b.service_state_id and a.shipping_region=b.service_region_id and*/ where 
			memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') 
			and shipping_state='".$shipping_state."' and shipping_region='".$shipping_region."';";
		$rs=mysql_query($sql) or die();//("check_shipping_address query error.<br />".mysql_error());
		if(mysql_num_rows($rs)>0)		return true;
		else 											return false;
	}
	
	public static function registered_product($memberemail, $membersproductid, $order_no="", $mode=""){
		$sql="select membersproductid, product, serialnumber, purchaseat, date_format(cast(purchasedate as date), '%d %M %Y') purchasedate 
			from membersproduct where memberid=(select memberid from membersdata where email='".main::formatting_query_string($memberemail)."') ";
		if($membersproductid!="")$sql.=" and membersproductid='".main::formatting_query_string($membersproductid)."'";
		if($order_no!="")$sql.=" and purchaseat like '%order no. ".$order_no."%' ";
		if($mode=="")$sql.=" and purchasedate is not null ";
		$rs=mysql_query($sql) or die();//("registered_product query error.<br />".mysql_error());
		return $rs;
	}
	
	public function member_email($target /*customer | * */){
		/* parameter harus ada : lang, custemail, email_template, email_subject */
		if(!isset($this->lang))throw new Exception("Language not set");
		if(!isset($this->custemail))throw new Exception("Customer email not set");
		if(!isset($this->email_template))throw new Exception("Email Template not set");			
		if(!isset($this->email_subject))throw new Exception("Email Subject not set");
		
		include $this->lang;		
		
		$message=file_get_contents($this->email_template);	
		$member_name=member::member_data_information($this->custemail, "name");
		if($target=="customer")$message=str_replace("#greetingname#", $member_name, $message);
		else $message=str_replace("#greetingname#", "MODENA Customercare", $message);
		if(isset($this->t_message_en)&&$this->t_message_en!="")@$s_content=$this->t_message_en;
		if(isset($this->t_message_id)&&$this->t_message_id!="")@$s_content.=$this->t_message_id;			
		$message=str_replace("#content#", $s_content, $message);
		$message=str_replace("#lang_email_info_en#", $lang_email_info_contactus, $message);
		$message=str_replace("#lang_email_footer#", "", $message);
		$message=str_replace("#horizontal_line#", "<hr width=100% size=1 />", $message);

		$subject = $this->email_subject;
		if($target=="customer"){
			$to["To"]=$this->custemail; // note the comma ::: $this->custemail
			$recipient_header_to=$this->custemail;
			$from=CUSTOMERCARE_EMAIL;
		}else{
			$to["To"]=CUSTOMERCARE_EMAIL;//"customercare@modena.co.id";				
			$to["Bcc"]=SUPPORT_EMAIL.",".MANAGEMENT_EMAIL;
			$recipient_header_to=CUSTOMERCARE_EMAIL;
			$from=SUPPORT_EMAIL;
		}

		ini_set("include_path",INCLUDE_PATH);
		require_once "pear/Mail.php";
		require_once "pear/mime.php";		
		
		$mime=new Mail_mime(array('eol' => CRLF));
		$mime->setTXTBody(strip_tags($message));
		$mime->setHTMLBody($message);	
		
		$headers = array ('From' => $from,
			'To' => $recipient_header_to, 
			'Subject' => $subject);		
		$smtp=Mail::factory('smtp',
			array ('host' => SMTP_HOST,
			'auth' => SMTP_AUTH,
			'username' => SMTP_USERNAME,
			'password' => SMTP_PASSWORD));		
						
		$body=$mime->get();
		$headers_=$mime->headers($headers);		

		$mail = $smtp->send($to, $headers_, $body);
		$pear=new PEAR;
		if ($pear->isError($mail))echo "Email Error. " . $mail->getMessage();
		restore_include_path ();
		//mail($to, $subject, $message, $headers);		
		//return $message;
	}
	
	public static function get_last_product_registration($memberemail){
		$sql="select a.product, a.serialnumber, a.purchaseat, cast(a.purchasedate as date) purchasedate, date_format(a.purchasedate, '%d %M %Y') purchasedate_formatted, 
			b.name, b.email, b.address, d.region homecity, c.state, b.homepostcode, b.phone, b.handphone
		 	from membersproduct a inner join membersdata b on a.memberid=b.memberid 
			left outer join shipment_exception d on b.homestate = d.state_id and b.homeregion = d.region_id
			left outer join shipment_state c on c.state_id = d.state_id
			where b.email='".main::formatting_query_string($_SESSION["email"])."'
			order by membersproductid desc limit 1;";
		$rs=mysql_query($sql) or die();//("get_last_product_registration query error.<br />".mysql_error());
		return $rs;
	}

}

?>