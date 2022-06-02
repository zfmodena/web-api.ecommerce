<?

class message extends main{
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
	public function __insert_message(){
		$message_no="null";$message_type="null";$message_subject="null";$message="null";$order_no="null";$member_id="(select memberid from membersdata where email='#email#')";
		$name="null";$email="null";$phone="null";$category="null";$message_ref_no="null";
		if(isset($this->message_no))$message_no="'".main::formatting_query_string($this->message_no)."'";
		if(isset($this->message_type))$message_type="'".main::formatting_query_string($this->message_type)."'";
		if(isset($this->message_subject))$message_subject="'".main::formatting_query_string($this->message_subject)."'";
		if(isset($this->message))$message="'".main::formatting_query_string($this->message)."'";
		if(isset($this->order_no))$order_no="'".main::formatting_query_string($this->order_no)."'";
		if(isset($this->member_id))$member_id="'".main::formatting_query_string($this->member_id)."'";
		if(isset($this->name))$name="'".main::formatting_query_string($this->name)."'";
		if(isset($this->email)){
			$email="'".main::formatting_query_string($this->email)."'";
			$member_id=str_replace("#email#", main::formatting_query_string($this->email), $member_id);
		}
		if(isset($this->phone))$phone="'".main::formatting_query_string($this->phone)."'";
		if(isset($this->category))$category="'".main::formatting_query_string($this->category)."'";
		if(isset($this->message_ref_no))$message_ref_no="'".main::formatting_query_string($this->message_ref_no)."'";
		$sql="insert into message(message_no, message_type, message_subject, message, order_no, member_id, name, email, phone, category, message_ref_no) 
		values(".$message_no.", ".$message_type.", ".$message_subject.", ".$message.", ".$order_no.", ".$member_id.", ".$name.", ".$email.", ".$phone.", ".$category.", ".$message_ref_no.");";
		mysql_query($sql) or die();//("__insert_message error.<br />".mysql_error());
	}
	
	public static function new_message_ticket(){
		$sql="select concat('MM',day(now()),month(now()),right(year(now()),2),'.',right(rand(),3)) as message_no;";
		$rs_message_number=mysql_query($sql) or die();//("new_message_ticket query error.<br />".mysql_error());
		$rs_message_number_=mysql_fetch_array($rs_message_number);
		$check_message_number=mysql_query("select 1 from message where message_no='".$rs_message_number_["message_no"]."';");
		if(mysql_num_rows($check_message_number)>0)
			message::new_message_ticket();
		else
			return $rs_message_number_["message_no"];	
	}
	
	public function get_message(){		
		$sql="select case message_type when 1 then '#customercare_email#' else '#member_email#' end message_type, 
			message_no, date_format(message_date,'%d %M %Y, %H:%i:%S') message_date, message_date message_date_asli, message_subject, message, order_no, member_id, email, phone, category, message_ref_no from message where 
			member_id like '%' ";
		if(isset($this->memberemail)&&$this->memberemail!="")$sql.=" and member_id=(select memberid from membersdata where email='".main::formatting_query_string($this->memberemail)."') ";
		if(isset($this->order_number)&&$this->order_number!="")$sql.=" and order_no='".main::formatting_query_string($this->order_number)."' ";
		if(isset($this->message_no)&&$this->message_no!="")$sql.=" and message_no='".main::formatting_query_string($this->message_no)."' ";
		if(isset($this->message_ref_no)&&$this->message_ref_no!="")$sql.=" and message_ref_no='".main::formatting_query_string($this->message_ref_no)."' ";
		if(isset($this->message_status))
			if($this->message_status==0)$sql.=" and message_no not in (select message_ref_no from message) ";
			elseif($this->message_status==1)$sql.=" and message_no in (select message_ref_no from message) ";
		if(isset($this->message_starter_only)&&$this->message_starter_only)$sql.=" and (message_ref_no is null or message_ref_no='') ";
		$sql.=" order by message_date_asli desc;";
		$rs=mysql_query($sql) or die();//("get_message query error.<br />".mysql_error());
		return $rs;
	}

	public function __insert_contactus(){
		$contactus_no="null";$contactus_type="null";$member_id="null";
		$name="null";$email="null";$alamat="null";$kota="null";$state="null";$country="null";$postcode="null";
		$phone="null";$handphone="null";$contactus_subject="null";$contactus="null";;$contactus_ref_no="null";
				
		if(isset($this->contactus_no))$contactus_no="'".main::formatting_query_string($this->contactus_no)."'";
		if(isset($this->contactus_type))$contactus_type="'".main::formatting_query_string($this->contactus_type)."'";
		if(isset($this->member_email))$member_id="(select memberid from membersdata where email='".main::formatting_query_string($this->member_email)."')";
		if(isset($this->name))$name="'".main::formatting_query_string($this->name)."'";
		if(isset($this->email))$email="'".main::formatting_query_string($this->email)."'";
		if(isset($this->alamat))$alamat="'".main::formatting_query_string($this->alamat)."'";
		if(isset($this->kota))$kota="'".main::formatting_query_string($this->kota)."'";
		if(isset($this->state)){
			$a_state=preg_split("/\|/", $this->state);
			$state="'".main::formatting_query_string($a_state[0])."'";
		}
		if(isset($this->country))$country="'".main::formatting_query_string($this->country)."'";
		if(isset($this->postcode))$postcode="'".main::formatting_query_string($this->postcode)."'";
		if(isset($this->phone))$phone="'".main::formatting_query_string($this->phone)."'";
		if(isset($this->handphone))$handphone="'".main::formatting_query_string($this->handphone)."'";
		if(isset($this->contactus_subject))$contactus_subject="'".main::formatting_query_string($this->contactus_subject)."'";
		if(isset($this->contactus))$contactus="'".main::formatting_query_string($this->contactus)."'";
		if(isset($this->contactus_ref_no))$contactus_ref_no="'".main::formatting_query_string($this->contactus_ref_no)."'";
		
		$sql="insert into `contact us`(id, type, memberid, 
			nama, email, alamat, kota, state, country, postcode, 
			telepon, handphone, subject, isi, idref) 
		values(".$contactus_no.", ".$contactus_type.", ".$member_id.", 
			".$name.", ".$email.", ".$alamat.", ".$kota.", ".$state.", ".$country.", ".$postcode.", 
			".$phone.", ".$handphone.", ".$contactus_subject.", ".$contactus.", ".$contactus_ref_no.");";
		mysql_query($sql) or die();//("__insert_contactus error.<br />".mysql_error());	
	}

	public static function new_contactus_ticket(){
		$sql="select concat('MCU',day(now()),month(now()),right(year(now()),2),'.',right(rand(),3)) as contactus_no;";
		$rs_contactus_number=mysql_query($sql) or die();//("new_contactus_ticket query error.<br />".mysql_error());
		$rs_contactus_number_=mysql_fetch_array($rs_contactus_number);
		$check_contactus_number=mysql_query("select 1 from `contact us` where id='".$rs_contactus_number_["contactus_no"]."';");
		if(mysql_num_rows($check_contactus_number)>0)
			message::new_contactus_ticket();
		else
			return $rs_contactus_number_["contactus_no"];	
	}
	
	public function get_contactus(){		
		$sql="select date_format(a.tanggal,'%d %M %Y, %H:%i:%S') tanggal, a.id, case when exists(select 1 from `contact us` where idref=a.id) then '#replied#' else '#notreplied#' end contactus_status,
			case a.type when 1 then '#customercare_email#' else '#member_email#' end type, 
			a.memberid, a.nama, a.email, a.alamat, a.kota, b.state, a.country, a.postcode, a.telepon, a.handphone, a.subject, a.isi, a.idref 
			from `contact us` a left outer join shipment_state b on a.state=b.state_id where a.id like '%' ";
		if(isset($this->memberemail)&&$this->memberemail!="")$sql.=" and 
			(a.memberid=(select memberid from membersdata where email='".main::formatting_query_string($this->memberemail)."') 
				or exists (select 1 from `contact us` where email='".$this->memberemail."' and id=a.idref)) ";
		if(isset($this->contactus_type))$sql.=" and a.type='".main::formatting_query_string($this->contactus_type)."'";
		if(isset($this->contactus_email)&&$this->contactus_email!="")$sql.=" and a.email ".$this->contactus_email." ";
		if(isset($this->contactus_no)&&$this->contactus_no!="")$sql.=" and a.id='".main::formatting_query_string($this->contactus_no)."' ";
		if(isset($this->contactus_ref_no)&&$this->contactus_ref_no!="")$sql.=" and a.idref='".main::formatting_query_string($this->contactus_ref_no)."' ";
		if(isset($this->contactus_status)&&$this->contactus_status!="")
			if($this->contactus_status==0)$sql.=" and not exists (select 1 from `contact us`where idref=a.id) ";
			elseif($this->contactus_status==1)$sql.=" and exists (select 1 from `contact us`where idref=a.id) ";
		if(isset($this->contactus_starter_only)&&$this->contactus_starter_only)$sql.=" and (a.idref is null or a.idref='') ";
		if(isset($this->contactus_search_nama)&&$this->contactus_search_nama!="")$sql.=" and a.nama like '%".main::formatting_query_string($this->contactus_search_nama)."%' ";
		if(isset($this->contactus_search_email)&&$this->contactus_search_email!="")$sql.=" and a.email  like '%".main::formatting_query_string($this->contactus_search_email)."%' ";
		if(isset($this->contactus_search_tanggal)&&$this->contactus_search_tanggal!="")$sql.=" and date_format(a.tanggal,'%d %M %Y') like '%".main::formatting_query_string($this->contactus_search_tanggal)."%' ";
		if(isset($this->contactus_search_subject)&&$this->contactus_search_subject!="")$sql.=" and a.subject  like '%".main::formatting_query_string($this->contactus_search_subject)."%' ";
		$sql.=" order by a.tanggal desc;";
		$rs=mysql_query($sql) or die();//("get_contactus query error.<br />".mysql_error());
		return $rs;
	}

	public function contactus_email($target /*customer | * */){
		if(!isset($this->lang))throw new Exception("Language not set");
		if(!isset($this->email_template))throw new Exception("Email Template not set");
		if(!isset($this->content_template))throw new Exception("Content Template not set");
		if(!isset($this->email_subject))throw new Exception("Email Subject not set");
		
		include $this->lang;		
		
		$message=file_get_contents($this->email_template);	
		$content=file_get_contents($this->content_template);	
		
		if($target=="customer")$message=str_replace("#greetingname#", $this->nama, $message);
		else $message=str_replace("#greetingname#", "MODENA Customercare", $message);
		
		$content=str_replace("#id#", $this->contactus_no, $content);
		$content=str_replace("#contactus#", $this->contactus, $content);
		$content=str_replace("#nama#", $this->nama, $content);
		$content=str_replace("#email#", $this->email, $content);
		if(isset($this->alamat)&&$this->alamat!="")$content=str_replace("#alamat#", $this->alamat, $content);
		if(isset($this->phone)&&$this->phone!="")$content=str_replace("#phone#", $this->phone, $content);
		
		$message=str_replace("#content#", $content, $message);
		$message=str_replace("#lang_email_info_en#", $lang_email_info_contactus, $message);
		$message=str_replace("#lang_email_footer#", "", $message);
		$message=str_replace("#horizontal_line#", "<hr width=100% size=1 />", $message);

		$subject = $this->email_subject;
		if($target=="customer"){
			$to["To"]= $this->email; // note the comma ::: $this->email
			$recipient_header_to=$this->email;
			$from=CUSTOMERCARE_EMAIL;
		}else{			
			$to["To"]=CUSTOMERCARE_EMAIL;//"customercare@modena.co.id";
			if(isset($this->internal_rcpt_to) && $this->internal_rcpt_to!="") $to["To"]=$this->internal_rcpt_to;
			if($target=="service_request"){
				$to["Bcc"]=SUPPORT_EMAIL.",".MANAGEMENT_EMAIL;
				}
			else{
				$to["Bcc"]=SUPPORT_EMAIL;
				}
			$recipient_header_to=CUSTOMERCARE_EMAIL;
			$from=SUPPORT_EMAIL;
		}

		if(isset($this->mode)&&$this->mode=="preview")return $message;

		ini_set("include_path",INCLUDE_PATH);
		require_once "pear/Mail.php";
		require_once "pear/mime.php";		
				
		$mime=new Mail_mime(array('eol' => CRLF));
		$mime->setTXTBody(strip_tags($message));
		$mime->setHTMLBody($message);	
		if(isset($this->lampiran))
			foreach($this->lampiran as $key)$mime->addAttachment(INCLUDE_PATH.$key);

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
	}
	
	
	public function tradein_email(){
		if(!isset($this->lang))throw new Exception("Language not set");
		if(!isset($this->email_template))throw new Exception("Email Template not set");
		if(!isset($this->content_template))throw new Exception("Content Template not set");
		if(!isset($this->email_subject))throw new Exception("Email Subject not set");
		
		include $this->lang;		
		
		$message=file_get_contents($this->email_template);	
		$content=file_get_contents($this->content_template);	
		
		// if($target=="customer")$message=str_replace("#greetingname#", $this->nama, $message);
		// else $message=str_replace("#greetingname#", "MODENA Customercare", $message);
		
		// $content=str_replace("#id#", $this->contactus_no, $content);
		// $content=str_replace("#contactus#", $this->contactus, $content);
		$propinsi = mysql_fetch_array(main::shipment_state($this->req['t_state']));
		$kota = mysql_fetch_array(main::shipment_exception($this->req['t_state'],$this->req['s_city']));
		//echo $propinsi['state']." -> ".$kota['region'];exit;
		
		$content=str_replace("#noreg#", "REG".$this->req['id'], $content);
		$content=str_replace("#nama#", $this->req['t_name'], $content);
		$content=str_replace("#email#", $this->req['t_email'], $content);
		$content=str_replace("#alamat#", $this->req['t_address'].", ".$kota['region'].", ".$propinsi['state']." ".$this->req['t_postalcode'], $content);
		$content=str_replace("#handphone#", $this->req['t_telephone'], $content);
		$content=str_replace("#voucher#", $this->req['t_pocer'], $content);
		
		$rs_al = mysql_query("select * from tradein_alasan where alasan_id='". main::formatting_query_string($this->req['alasan']) ."'");
		$alasan = mysql_fetch_array($rs_al);
		$content=str_replace("#alasan#", ($this->req['lang']=="id"?$alasan['kalimat']:$alasan['kalimat_en']), $content);

		// $content=str_replace("#linkno#", $url_path."/tradein_register.php?c=tradein_konfirm&id=".$this->req['id']."&setuju=0", $content);
		
		$brand = "Non MODENA";
		$dis = "30%";
		if($this->req['t_brand']=="1") {
			$brand = "MODENA";
			$dis = "35%";
		}
		$content=str_replace("#brand#", $brand, $content);
		$content=str_replace("#diskon#", $dis, $content);
		
		$all_produk = "";
		if($this->req['tipe'] == 2 or $this->req['lang']=="id"){
			$info = "Untuk keterangan lebih lanjut, silahkan hubungi MODENA Call Center 15.007.15 pada jam kerja kami: Senin-Jumat 07:00 s/d 19:00 WIB, Sabtu 07:00 s/d 17:00 WIB dan Minggu 09:00 s/d 17:00 WIB.";
			
		$string_produk = "<tr>
						<td width=\"30\">#no#.</td><td width=\"150\" style=\"border-bottom:solid thin #CCC\">Kategori</td><td style=\"border-bottom:solid thin #CCC\">#kategori#</td>	
					</tr>
					<tr>
						<td></td><td style=\"border-bottom:solid thin #CCC\">Usia</td><td style=\"border-bottom:solid thin #CCC\">#usia#</td>	
					</tr>
					<tr>
						<td></td><td style=\"border-bottom:solid thin #CCC\">Foto</td><td style=\"border-bottom:solid thin #CCC\">#foto#</td>	
					</tr>
					<tr>
						<td ></td><td style=\"border-bottom:solid thin #CCC\">Permintaan Bongkar</td><td style=\"border-bottom:solid thin #CCC\">#bongkar#</td>	
					</tr>
					<tr>
						<td style=\"border-bottom:solid thin #CCC\"></td><td style=\"border-bottom:solid thin #CCC\">
						Permintaan Instalasi</td><td style=\"border-bottom:solid thin #CCC\">#instal#</td>	
					</tr>".(($this->req['tipe'] == 2)?"
					<tr>
						<td style=\"border-bottom:solid thin #CCC\" colspan=\"3\">
							<ul>
								<li><a href=\"#linkyes#\">Disetujui</a></li>
							</ul>     
							<ul>
								#linkno#
							</ul>        
						</td>	
					</tr>":"");
					
			$string_produk2 = "<tr>
								<td width=\"30\">#no#.</td><td width=\"150\" style=\"border-bottom:solid thin #CCC\">Kategori</td><td style=\"border-bottom:solid thin #CCC\">#kategori#</td>	
							</tr>
							<tr>
								<td></td><td style=\"border-bottom:solid thin #CCC\">Usia</td><td style=\"border-bottom:solid thin #CCC\">#usia#</td>	
							</tr>
							<tr>
								<td style=\"border-bottom:solid thin #CCC\"></td><td style=\"border-bottom:solid thin #CCC\">Foto</td><td style=\"border-bottom:solid thin #CCC\">#foto#</td>	
							</tr>".(($this->req['tipe'] == 2)?"
					<tr>
						<td style=\"border-bottom:solid thin #CCC\" colspan=\"3\">
							<ul>
								<li><a href=\"#linkyes#\">Disetujui</a></li>
							</ul>     
							<ul>
								#linkno#
							</ul>        
						</td>	
					</tr>":"");
		}else{
			$info = "For more information, please contact MODENA Call Center 15.007.15 during our office hours: Monday-Friday 07:00-19:00 WIB, Saturday 07:00-17:00 WIB and Sunday 09:00-17:00 WIB.";
			
			$string_produk = "<tr>
						<td width=\"30\">#no#.</td><td width=\"150\" style=\"border-bottom:solid thin #CCC\">Kategori</td><td style=\"border-bottom:solid thin #CCC\">#kategori#</td>	
					</tr>
					<tr>
						<td></td><td style=\"border-bottom:solid thin #CCC\">Age Of Product</td><td style=\"border-bottom:solid thin #CCC\">#usia#</td>	
					</tr>
					<tr>
						<td></td><td style=\"border-bottom:solid thin #CCC\">Photo</td><td style=\"border-bottom:solid thin #CCC\">#foto#</td>	
					</tr>
					<tr>
						<td ></td><td style=\"border-bottom:solid thin #CCC\">Disassemble Request</td><td style=\"border-bottom:solid thin #CCC\">#bongkar#</td>	
					</tr>
					<tr>
						<td style=\"border-bottom:solid thin #CCC\"></td><td style=\"border-bottom:solid thin #CCC\">
						Installation Request</td><td style=\"border-bottom:solid thin #CCC\">#instal#</td>	
					</tr>".(($this->req['tipe'] == 2)?"
					<tr>
						<td style=\"border-bottom:solid thin #CCC\" colspan=\"3\">
							<ul>
								<li><a href=\"#linkyes#\">Disetujui</a></li>
							</ul>     
							<ul>
								#linkno#
							</ul>         
						</td>	
					</tr>":"");
					
			$string_produk2 = "<tr>
								<td width=\"30\">#no#.</td><td width=\"150\" style=\"border-bottom:solid thin #CCC\">Kategori</td><td style=\"border-bottom:solid thin #CCC\">#kategori#</td>		
							</tr>
							<tr>
								<td></td><td style=\"border-bottom:solid thin #CCC\">Age Of Product</td><td style=\"border-bottom:solid thin #CCC\">#usia#</td>	
							</tr>
							<tr>
								<td style=\"border-bottom:solid thin #CCC\"></td><td style=\"border-bottom:solid thin #CCC\">Photo</td><td style=\"border-bottom:solid thin #CCC\">#foto#</td>	
							</tr>".(($this->req['tipe'] == 2)?"
					<tr>
						<td style=\"border-bottom:solid thin #CCC\" colspan=\"3\">
							<ul>
								<li><a href=\"#linkyes#\">Disetujui</a></li>
							</ul>     
							<ul>
								#linkno#
							</ul>         
						</td>	
					</tr>":"");
		}
		
		if($this->req['tipe'] == 3 or $this->req['tipe'] == 4) {
			$tambah = "<tr>
						<td style=\"border-bottom:solid thin #CCC\"></td><td style=\"border-bottom:solid thin #CCC\" colspan=\"2\"><strong>#alas#</strong></td>
				      </tr>";
			$string_produk .= $tambah;
			$string_produk2 .= $tambah;
		}
		
		$pilihan="";
		$os = array(12,15,26,7,126);
		$no=1;
		for($i=1;$i<$this->req['produk'];$i++){		
			$content_produk = $string_produk;
			if(!in_array($this->req['t_cat_'.$i],$os)){
				$content_produk = $string_produk2;
			}
			$content_produk=str_replace("#no#", $no, $content_produk);
			// $content_produk=str_replace("#kategori#", $this->req['h_cat_'.$i], $content_produk);
			
			$rs_kat = main::tradein_category($this->req['t_cat_'.$i]);
			$kat = mysql_fetch_array($rs_kat);
			$content_produk=str_replace("#kategori#", ucwords(strtolower($kat['Name'])), $content_produk);
			$content_produk=str_replace("#usia#", $this->req['t_umur_'.$i]." ".(($this->req['tipe'] == 2 or $this->req['lang']=="id")?"Tahun":"Years"), $content_produk);
			$content_produk=str_replace("#jumlah#", $this->req['t_jumlah_'.$i]." Unit", $content_produk);
			$content_produk=str_replace("#foto#", " <img src=\"".$this->req['nama_foto_'.$i]."\" width=\"300\" height=\"300\" style=\"padding-top: 10px;padding-bottom: 10px;\"> ", $content_produk);
			
			$bongkar = ($this->req['tipe'] == 2 or $this->req['lang']=="id")?"Tidak":"No";
			$instal = ($this->req['tipe'] == 2 or $this->req['lang']=="id")?"Tidak":"No";
			if($this->req['c_bongkar_'.$i]==true) $bongkar = ($this->req['tipe'] == 2 or $this->req['lang']=="id")?"Ya":"Yes";
			if($this->req['c_instal_'.$i]==true) $instal = ($this->req['tipe'] == 2 or $this->req['lang']=="id")?"Ya":"Yes";
			$content_produk=str_replace("#bongkar#", $bongkar, $content_produk);
			$content_produk=str_replace("#instal#", $instal, $content_produk);
			
			$content_produk=str_replace("#linkyes#", "https://www.modena.co.id/tradein_register.php?c=tradein_konfirm&id=".$this->req['id']."&cat=".$this->req['t_cat_'.$i]."&setuju=1&lg=".$this->req['lang'], $content_produk);
			$linkno="";
			$rs_al = mysql_query("select * from tradein_alasan order by alasan_id");
			while($alasan = mysql_fetch_array($rs_al)){
				$linkno .= "<li><a href=\"https://www.modena.co.id/tradein_register.php?c=tradein_konfirm&id=".$this->req['id']."&cat=".$this->req['t_cat_'.$i]."&setuju=0&alasan=".$alasan['alasan_id']."&lg=".$this->req['lang']."\">Tidak Disetujui, karena ".$alasan['kalimat']."</a></li>";
			}
			$content_produk=str_replace("#linkno#", $linkno, $content_produk);
			
			
			if($this->req['t_alasan_'.$i]==0){
				$pilihan .= $this->req['t_cat_'.$i].",";
				// if($this->req['tipe'] == 3) {
					// $content_produk = "";
					// $no--;
				// }
				$content_produk=str_replace("#alas#", ($this->req['lang']=="id"?"Disetujui":"Approved"), $content_produk);
			}else{
				$rs_al = mysql_query("select * from tradein_alasan where alasan_id='". main::formatting_query_string($this->req['t_alasan_'.$i])."'");
				$alasan = mysql_fetch_array($rs_al);
				$content_produk=str_replace("#alas#", "<span style=\"color:red;\">".($this->req['lang']=="id"?"Tidak disetujui, karena ".$alasan['kalimat']:"Not Approved, because ".$alasan['kalimat_en'])."</span>", $content_produk);
			}
			
			
			
			$all_produk .= $content_produk;
			$no++;
		}

		$content=str_replace("#produk#", $all_produk, $content);
		
		$matrix="";
		$i=1;
		$dan = ($this->req['tipe'] == 2 or $this->req['lang']=="id")?"dan":"and";
		$pilihan = substr($pilihan,0,strlen($pilihan)-1);
		$sql ="select distinct b.ParentCategoryID, b.CategoryID, a.tradein_to_categoryid, b.Name from tradein_matrix a
				inner join category b on a.tradein_to_categoryid = b.CategoryID
				where a.tradein_from_categoryid IN (".$pilihan.") order by b.ParentCategoryID";
		$rs_mat = mysql_query($sql);
		$allRows = mysql_num_rows($rs_mat);
		while($mat = mysql_fetch_array($rs_mat)){
			$rs_kat = main::tradein_category($mat['tradein_to_categoryid']);
			$kat = mysql_fetch_array($rs_kat);
			
			if ($allRows == $i) {
				$matrix = substr($matrix,0,strlen($matrix)-2);
				if($i==1)
					$matrix .= "<a href=\"https://www.modena.co.id/category-".$mat['ParentCategoryID']."-".$mat['CategoryID']."-clearcompare.php?sc=".$mat['Name']."\">".ucwords(strtolower($kat['Name']))."</a>";
				else
					$matrix .= " ".$dan." <a href=\"https://www.modena.co.id/category-".$mat['ParentCategoryID']."-".$mat['CategoryID']."-clearcompare.php?sc=".$mat['Name']."\">".ucwords(strtolower($kat['Name']))."</a>";
			} else {
				$matrix .= "<a href=\"https://www.modena.co.id/category-".$mat['ParentCategoryID']."-".$mat['CategoryID']."-clearcompare.php?sc=".$mat['Name']."\">".ucwords(strtolower($kat['Name']))."</a>, ";
			}
			$i++;
					
		}
		
		$content=str_replace("#matrix#", $matrix, $content);
		
		$arr_month=array(1=>"Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember");
		$time = strtotime($this->req['tgl_pakai']);
		if($this->req['lang']=="id"){
			$newformat = date('d',$time)." ".$arr_month[date('n',$time)]." ".date('Y',$time);
		}else{
			$newformat = date('F d, Y',$time);
		}
		$content=str_replace("#tgl_pakai#", $newformat, $content);
		
		if(isset($this->alamat)&&$this->alamat!="")$content=str_replace("#alamat#", $this->alamat, $content);
		if(isset($this->phone)&&$this->phone!="")$content=str_replace("#phone#", $this->phone, $content);
		
		$message=str_replace("#info#", $info, $message);
		$message=str_replace("#content#", $content, $message);
		$message=str_replace("#lang_email_info_en#", $lang_email_info_contactus, $message);
		$message=str_replace("#lang_email_footer#", "", $message);
		$message=str_replace("#horizontal_line#", "<hr width=100% size=1 />", $message);

		$email_tradein = "abdul.gofur@modena.com,tradein@modena.co.id";
		$subject = $this->email_subject;
		if($this->req['tipe']==2){
		    //$to["To"]= "fauzi.atmaja@modena.com";
			$to["To"]= $email_tradein;
			$recipient_header_to=$email_tradein;
			$from=SUPPORT_EMAIL;
			$to["Bcc"]="fauzi.atmaja@modena.com,rian.mulyana@modena.com,support@modena.co.id,njizhar@modena.com";
		}else if($this->req['tipe']==3 or $this->req['tipe']==4){
		    //$to["To"]= "fauzi.atmaja@modena.com";
			$to["To"]= $this->email;
			$recipient_header_to=$this->email;
			$from=SUPPORT_EMAIL;
			$to["Bcc"]="fauzi.atmaja@modena.com,support@modena.co.id,".$email_tradein;
		}else{		
		    //$to["To"]= "fauzi.atmaja@modena.com";
			$to["To"]= $this->email;
			$recipient_header_to=$this->email;
			$from=SUPPORT_EMAIL;
			$to["Bcc"]="fauzi.atmaja@modena.com,support@modena.co.id";
		}


		if(isset($this->mode)&&$this->mode=="preview")return $message;

		ini_set("include_path",INCLUDE_PATH);
		require_once "pear/Mail.php";
		require_once "pear/mime.php";		
				
		$mime=new Mail_mime(array('eol' => CRLF));
		$mime->setTXTBody(strip_tags($message));
		$mime->setHTMLBody($message);	
		if(isset($this->lampiran))
			foreach($this->lampiran as $key)$mime->addAttachment(INCLUDE_PATH.$key);

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
	}
	
	public function stiker_offline_email(){
		if(!isset($this->lang))throw new Exception("Language not set");
		if(!isset($this->email_template))throw new Exception("Email Template not set");
		if(!isset($this->content_template))throw new Exception("Content Template not set");
		if(!isset($this->email_subject))throw new Exception("Email Subject not set");
		
		include $this->lang;		
		
		$message=file_get_contents($this->email_template);	
		$content=file_get_contents($this->content_template);	
		
		$content=str_replace("#stiker_id#", $this->id, $content);
		$content=str_replace("#order_id#", $this->req['order_id'], $content);
		$content=str_replace("#nama#", $this->req['nama_kirim'], $content);
		$content=str_replace("#email#", $this->req['email'], $content);
		$content=str_replace("#alamat#", $this->req['alamat_kirim'].", ".$this->req['kota_kirim'].", ".$this->req['propinsi_kirim'], $content);
		$content=str_replace("#handphone#", $this->req['telp_kirim']." / ".$this->req['hp_kirim'], $content);
		
		
		$string_produk = "<tr>
						<td style=\"border:solid thin #CCC;padding:3px\" width=\"10%\">#no#.</td>
						<td style=\"border:solid thin #CCC;padding:3px\" width=\"30%\">#nama_produk#</td>	
						<td style=\"border:solid thin #CCC;padding:3px\" width=\"40%\">#thumbnail#</td>	
						<!--<td style=\"border:solid thin #CCC;padding:3px\" width=\"20%\">#finish#</td>-->
					</tr>";
		
		$listp = $GLOBALS["product_custom"];
		$all_produk = "";
		$no=1;
		$sql="SELECT a.*, b.name from desain_detail a
				inner join product b on a.product_id = b.productid where a.order_no ='".$this->id."'";
		$sql = "SELECT a.product_id, b.name, count(a.product_seq) jumlah from desain_detail a inner join product b on a.product_id = b.productid where a.order_no ='". main::formatting_query_string($this->id) ."' group by a.product_id, b.name";
		$rs_kon = mysql_query($sql) or die();
		while($kon = mysql_fetch_array($rs_kon)){
			$content_produk = $string_produk;
			$pid = $kon['product_id'];
			$sid = $listp[$pid]['stickerid'];
			$content_produk=str_replace("#no#", $no, $content_produk);
			$content_produk=str_replace("#nama_produk#", $kon['name'] . " - ". $kon["jumlah"] ." Unit", $content_produk);
			$content_produk=str_replace("#thumbnail#", order::dapatkan_list_stiker($this->id,$sid), $content_produk);
			//$content_produk=str_replace("#finish#", $kon['keterangan'], $content_produk);
			
			$all_produk .= $content_produk;
			$no++;
			
		}
		
		$content=str_replace("#produk#", $all_produk, $content);
		
		$message=str_replace("#info#", $info, $message);
		$message=str_replace("#content#", $content, $message);
		$message=str_replace("#lang_email_info_en#", $lang_email_info_contactus, $message);
		$message=str_replace("#lang_email_footer#", "", $message);
		$message=str_replace("#horizontal_line#", "<hr width=100% size=1 />", $message);
		
		$subject = $this->email_subject;
		// $to["To"]= "fauzi.atmaja@modena.co.id";
		$to["To"] = $this->email;
		$recipient_header_to=$this->email;
		$from=SUPPORT_EMAIL;
		$to["Bcc"]="fauzi.atmaja@modena.co.id,dedi.supatman@modena.co.id,".SUPPORT_EMAIL;


		if(isset($this->mode)&&$this->mode=="preview")return $message;

		ini_set("include_path",INCLUDE_PATH);
		require_once "pear/Mail.php";
		require_once "pear/mime.php";		
				
		$mime=new Mail_mime(array('eol' => CRLF));
		$mime->setTXTBody(strip_tags($message));
		$mime->setHTMLBody($message);	
		if(isset($this->lampiran))
			foreach($this->lampiran as $key)$mime->addAttachment(INCLUDE_PATH.$key);

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
	}

}

?>