<?

class career extends main{
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

	public function get_career(){
		$sql="select posisiid, date_format(tanggalposting, '%d %M %Y') tanggalposting, posisi, departemen, lokasi, deskripsi, persyaratan, 
			date_format(tanggalakhir, '%d %M %Y') tanggalakhir, aktif from karir where posisiid is not null ";
		if(isset($this->aktif))$sql.=" and aktif ".$this->aktif;
		if(isset($this->posisiid)&&$this->posisiid!="")$sql.=" and posisiid='".main::formatting_query_string($this->posisiid) ."'";
		if(isset($this->not_expired)&&$this->not_expired)$sql.=" and tanggalakhir>=now() ";
		if(isset($this->src)&&$this->src!="")
			$sql.="and (posisi like '%".main::formatting_query_string($this->src)."%' or departemen like '%".main::formatting_query_string($this->src)."%' or 
				lokasi like '%".main::formatting_query_string($this->src)."%' or deskripsi like '%".main::formatting_query_string($this->src)."%' or persyaratan like '%".main::formatting_query_string($this->src)."%' or 
				date_format(tanggalposting, '%d %M %Y') like '%%' or date_format(tanggalakhir, '%d %M %Y') like '%".main::formatting_query_string($this->src)."%')";
		$return=mysql_query($sql." order by date_format(tanggalposting, '%d %M %Y %H:%i:%s') asc") or die("<!--  $sql-->");//("get_career query error.<br />".mysql_error());
		return $return;
	}
	
	public static function get_applicant_personal_information($id, $src){
		$sql="select pelamarid,date_format(tanggallamaran,'%d %M %Y') tanggallamaran_formatted, tanggallamaran,case ifnull(b.posisi, '') when '' then a.posisiid else b.posisi end posisi,
			nama,date_format(tanggallahir,'%d %M %Y') tanggallahir_formatted, tanggallahir,email,
			alamatdomisili,kotadomisili,propinsidomisili propinsidomisiliid,c.state propinsidomisili,
			telepondomisili,alamattetap,kotatetap,propinsitetap propinsitetapid,d.state propinsitetap,
			telepontetap,hp,gaji,keahlianteknis,a.aktif, a.keterangan
			from karir_data_pribadi a left outer join karir b on a.posisiid=b.posisiid 
			left outer join shipment_state c on a.propinsidomisili=c.state_id
			inner join shipment_state d on a.propinsitetap=d.state_id
			where a.pelamarid is not null ";
			if($id!="")$sql.="and pelamarid='".$id."' ";
			if($src!="")$sql.="and (
				date_format(tanggallamaran,'%d %M %Y') like '%".$src."%' or posisi like '%".$src."%' or
				nama like '%".$src."%' or date_format(tanggallahir,'%d %M %Y') like '%".$src."%' or email like '%".$src."%' or
				alamatdomisili like '%".$src."%' or kotadomisili like '%".$src."%' or c.state like '%".$src."%' or
				telepondomisili like '%".$src."%' or alamattetap like '%".$src."%' or kotatetap like '%".$src."%' or d.state like '%".$src."%' or
				telepontetap like '%".$src."%' or hp like '%".$src."%' or gaji like '%".$src."%' or keahlianteknis like '%".$src."%')";
		$return=mysql_query($sql) or die();//("get_applicant_personal_information query error.<br />".mysql_error());
		return $return;
	}
	
	public static function get_applicant_job_experience($id, $src){
		$sql="select pelamarid,pengalamanid,namaperusahaan,bidangperusahaan,alamatperusahaan,teleponperusahaan,
			date_format(bekerjadari,'%M %Y') bekerjadari,date_format(bekerjasampai,'%M %Y') bekerjasampai,
			jabatanawal,jabatanakhir,gajiterakhir,uraianpekerjaan,aktif from karir_data_pengalaman where pengalamanid is not null ";
		if($id!="")$sql.="and pelamarid='".$id."' ";
		if($src!="")$sql.="and (
			namaperusahaan like '%".$src."%' or bidangperusahaan like '%".$src."%' or alamatperusahaan like '%".$src."%' or teleponperusahaan like '%".$src."%' or
			date_format(bekerjadari, '%M %Y') like '%".$src."%' or date_format(bekerjasampai, '%M %Y') like '%".$src."%' or jabatanawal like '%".$src."%' or jabatanakhir like '%".$src."%' or
			gajiterakhir like '%".$src."%' or uraianpekerjaan like '%".$src."%')";		
		$sql.=" order by pengalamanid";
		$return=mysql_query($sql) or die();//("get_applicant_job_experience query error.<br />".mysql_error());
		return $return;
	}
	
	public static function get_applicant_background_education($id, $src){
		$sql="select pelamarid,pendidikanid,date_format(belajardari,'%M %Y') belajardari,date_format(belajarsampai,'%M %Y') belajarsampai,
			tingkatpendidikan,namainstitusi,kotainstitusi,jurusan,kelulusan,case kelulusan when true then 'Lulus' else 'Tidak Lulus' end kelulusan_formatted,aktif
			from karir_data_pendidikan where pendidikanid is not null ";
		if($id!="")$sql.="and pelamarid='".$id."' ";
		if($src!="")$sql.="and (			
			date_format(belajardari, '%M %Y') like '%".$src."%' or date_format(belajarsampai, '%M %Y') like '%".$src."%' or tingkatpendidikan like '%".$src."%' or namainstitusi like '%".$src."%' or
			kotainstitusi like '%".$src."%' or jurusan like '%".$src."%')";		
		$sql.=" order by pendidikanid";			
		$return=mysql_query($sql) or die();//("get_applicant_background_education query error.<br />".mysql_error());
		return $return;
	}
	
	public static function get_applicant_training_certification($id, $src){
		$sql="select pelamarid,pelatihanid,pelatihan,date_format(pelatihandari,'%M %Y') pelatihandari,date_format(pelatihansampai,'%M %Y') pelatihansampai,
			case sertifikat when true then 'Bersertifikat' else 'Tidak bersertifikat' end sertifikat_formatted,aktif 
			from karir_data_pelatihan where pelatihanid is not null ";
		if($id!="")$sql.="and pelamarid='".$id."' ";
		if($src!="")$sql.="and (			
			date_format(pelatihandari, '%M %Y') like '%".$src."%' or date_format(pelatihansampai, '%M %Y') like '%".$src."%' or pelatihan like '%".$src."%')";		
		$sql.=" order by pelatihanid";			
		$return=mysql_query($sql) or die();//("get_applicant_training_certification query error.<br />".mysql_error());
		return $return;
	}

	public static function get_applicant_bahasa($id, $src){
		$sql="select pelamarid,bahasaid,bahasa,mendengar,berbicara,membaca,menulis,aktif 
			from karir_data_bahasa where bahasaid is not null ";
		if($id!="")$sql.="and pelamarid='".$id."' ";
		if($src!="")$sql.="and (			
			bahasa like '%".$src."%')";		
		$sql.=" order by bahasaid";			
		$return=mysql_query($sql) or die();//("get_applicant_bahasa query error.<br />".mysql_error());
		return $return;
	}

	public static function get_applicant_reference($id, $src){
		$sql="select pelamarid,referensiid,nama,alamat,teleponhp,perusahaanpekerjaan,hubungan,aktif
			from karir_data_referensi where referensiid is not null ";
		if($id!="")$sql.="and pelamarid='".$id."' ";
		if($src!="")$sql.="and (			
			nama like '%".$src."%' or alamat like '%".$src."%' or teleponhp like '%".$src."%' or perusahaanpekerjaan like '%".$src."%' or hubungan like '%".$src."%')";		
		$return=mysql_query($sql) or die();//("get_applicant_reference query error.<br />".mysql_error());
		return $return;
	}

	public function send_email_application(){	
		$id="";
		if(!isset($this->lang))throw new Exception("language not set");include $this->lang;
		if(!isset($this->email_recipient))throw new Exception("Email Recipient not set");		
		if(!isset($this->email_subject))throw new Exception("Email Subject not set");
		if(!isset($this->applicant_id))throw new Exception("Applicant Id not set");
		
		if(!isset($this->email_content))throw new Exception("Email content not set");
		if(!isset($this->app_main_content))throw new Exception("Application main content not set");
		if(!isset($this->app_job_experience))throw new Exception("Application job experience not set");
		if(!isset($this->app_background_education))throw new Exception("Application background education not set");
		if(!isset($this->app_training_certification))throw new Exception("Application training & certification not set");
		if(!isset($this->app_bahasa))throw new Exception("Application bahasa not set");

		$app_main_content=file_get_contents($this->app_main_content);
		$app_job_experience=file_get_contents($this->app_job_experience);
		$app_background_education=file_get_contents($this->app_background_education);
		$app_training_certification=file_get_contents($this->app_training_certification);
		$app_bahasa=file_get_contents($this->app_bahasa);
		
		$id=main::formatting_query_string($this->applicant_id);
		$personal_information=mysql_fetch_array(career::get_applicant_personal_information($id,""));		
		$app_main_content=str_replace("#nama#", $personal_information["nama"], $app_main_content);	$app_nama=$personal_information["nama"];
		$app_main_content=str_replace("#email#", $personal_information["email"], $app_main_content);		$app_email=$personal_information["email"];
		$app_main_content=str_replace("#dob#", $personal_information["tanggallahir_formatted"], $app_main_content);
		$app_main_content=str_replace("#alamattetap#", $personal_information["alamattetap"].", ".$personal_information["kotatetap"].", ".$personal_information["propinsitetap"], $app_main_content);
		$app_main_content=str_replace("#telepontetap#", $personal_information["telepontetap"], $app_main_content);
		$app_main_content=str_replace("#alamatdomisili#", $personal_information["alamatdomisili"].", ".$personal_information["kotadomisili"].", ".$personal_information["propinsidomisili"], $app_main_content);
		$app_main_content=str_replace("#telepondomisili#", $personal_information["telepondomisili"], $app_main_content);
		$app_main_content=str_replace("#handphone#", $personal_information["hp"], $app_main_content);
		$app_main_content=str_replace("#posisi#", $personal_information["posisi"], $app_main_content);
		$posisi_dilamar=$personal_information["posisi"];
		$app_main_content=str_replace("#gaji#", number_format($personal_information["gaji"]), $app_main_content);
		$app_main_content=str_replace("#deskripsi#", $personal_information["keahlianteknis"], $app_main_content);

		$s_="";$counter=1;
		$rs_=career::get_applicant_job_experience($id, "");
		if(mysql_num_rows($rs_)<=0)$s_="Tidak ada pengalaman kerja";
		else
			while($rs=mysql_fetch_array($rs_)){
				$s_.="<strong>#".$counter."</strong> ".file_get_contents($this->app_job_experience);
				$s_=str_replace("#periode#", $rs["bekerjadari"]."-".$rs["bekerjasampai"], $s_);
				$s_=str_replace("#namaperusahaan#", $rs["namaperusahaan"], $s_);
				$s_=str_replace("#alamatperusahaan#", $rs["alamatperusahaan"], $s_);
				$s_=str_replace("#teleponperusahaan#", $rs["teleponperusahaan"], $s_);
				$s_=str_replace("#jabatanawal#", $rs["jabatanawal"], $s_);
				$s_=str_replace("#jabatanakhir#", $rs["jabatanakhir"], $s_);
				$s_=str_replace("#gajiterakhir#", number_format($rs["gajiterakhir"]), $s_);
				$s_=str_replace("#uraianpekerjaan#", $rs["uraianpekerjaan"], $s_);				
				$counter++;
			}
		$app_main_content=str_replace("#pengalamankerja#", $s_, $app_main_content);
		
		$s_="";$counter=1;
		$rs_=career::get_applicant_background_education($id, "");
		if(mysql_num_rows($rs_)<=0)$s_="Tidak ada latar belakang pendidikan";
		else
			while($rs=mysql_fetch_array($rs_)){
				$s_.="<strong>#".$counter."</strong> ".file_get_contents($this->app_background_education);
				$s_=str_replace("#periode#", $rs["belajardari"]."-".$rs["belajarsampai"], $s_);
				$s_=str_replace("#tingkatpendidikan#", $arr_education_level[$rs["tingkatpendidikan"]], $s_);
				$s_=str_replace("#namainstitusi#", $rs["namainstitusi"], $s_);
				$s_=str_replace("#bidangjurusan#", $rs["jurusan"], $s_);
				$s_=str_replace("#kelulusan#", $rs["kelulusan_formatted"], $s_);
				$counter++;
			}
		$app_main_content=str_replace("#backgroundpendidikan#", $s_, $app_main_content);
		
		$s_="";$counter=1;
		$rs_=career::get_applicant_training_certification($id, "");
		if(mysql_num_rows($rs_)<=0)$s_="Tidak ada training dan sertifikasi";
		else
			while($rs=mysql_fetch_array($rs_)){
				$s_.="<strong>#".$counter."</strong> ".file_get_contents($this->app_training_certification);
				$s_=str_replace("#periode#", $rs["pelatihandari"]."-".$rs["pelatihansampai"], $s_);
				$s_=str_replace("#subyek#", $rs["pelatihan"], $s_);
				$s_=str_replace("#sertifikasi#", $rs["sertifikat_formatted"], $s_);
				$counter++;
			}
		$app_main_content=str_replace("#pelatihansertifikasi#", $s_, $app_main_content);

		$s_="";$counter=1;
		$rs_=career::get_applicant_bahasa($id, "");
		if(mysql_num_rows($rs_)<=0)$s_="Tidak ada penguasaan bahasa";
		else
			while($rs=mysql_fetch_array($rs_)){
				$s_.="<strong>#".$counter."</strong> ".file_get_contents($this->app_bahasa);
				$s_=str_replace("#bahasa#", $rs["bahasa"], $s_);
				$s_=str_replace("#mendengar#", $arr_language_capabilities[$rs["mendengar"]], $s_);
				$s_=str_replace("#berbicara#", $arr_language_capabilities[$rs["berbicara"]], $s_);
				$s_=str_replace("#membaca#", $arr_language_capabilities[$rs["membaca"]], $s_);
				$s_=str_replace("#menulis#", $arr_language_capabilities[$rs["menulis"]], $s_);
				$counter++;
			}
		$app_main_content=str_replace("#bahasa#", $s_, $app_main_content);

		$personal_information=mysql_fetch_array(career::get_applicant_reference($id,""));		
		$app_main_content=str_replace("#ref_nama#", $personal_information["nama"], $app_main_content);
		$app_main_content=str_replace("#ref_alamat#", $personal_information["alamat"], $app_main_content);
		$app_main_content=str_replace("#ref_telepon#", $personal_information["teleponhp"], $app_main_content);
		$app_main_content=str_replace("#ref_nama_perusahaan#", $personal_information["perusahaanpekerjaan"], $app_main_content);
		$app_main_content=str_replace("#ref_hubungan#", $personal_information["hubungan"], $app_main_content);
		
		$email_content=file_get_contents($this->email_content);
		$email_content=str_replace("#greetingname#", "HRD Staf", $email_content);
		$email_content=str_replace("#content#", $app_main_content, $email_content);
		$email_content=str_replace("#lang_email_info_en#", "", $email_content);
		$email_content=str_replace("#lang_email_footer#", "", $email_content);

		$to["To"]=$this->email_recipient;//$this->email_recipient;
		$to["Bcc"]	=SUPPORT_EMAIL;
		$recipient_header_to=$this->email_recipient;
		$from=SUPPORT_EMAIL;
		$subject=$this->email_subject." :: ".$posisi_dilamar;
		$message=$email_content;
		
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
		//mail($this->email_recipient, $this->email_subject, $email_content, $headers);

	}

}

?>