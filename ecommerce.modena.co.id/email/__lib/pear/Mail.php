<?

require "PHPMailer.php";

class Mail_mime{
	public $setTXTBody, $setHTMLBody; 
	public $addAttachment = array();
	
	function __construct( $array ){
		
	}
	
	function setTXTBody( $data ){
		$this->setTXTBody = $data;
	}
	
	function setHTMLBody( $data ){
		$this->setHTMLBody = $data;
	}
	
	function addAttachment( $data ){
		$this->addAttachment[] = $data;
	}
	
	/*public function __set($property, $value) {
		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
    }*/
		
	function get(){
		return array(
				"txt" => $this->setTXTBody,
				"html" => $this->setHTMLBody,
				"attachment" => $this->addAttachment
			);
	}
	
	function headers( $arr_from_to_subject ){
		return $arr_from_to_subject;
	}
	
}

class Mail{
	
	function __construct(){
		
	}
	
	function send( $to, $arr_from_to_subject, $body ){
		//require_once "class.smtp.php";
		//require_once "PHPMailer.php";		

		$phpmailer = new PHPMailer();
		$phpmailer->SMTPDebug=false;
		$phpmailer->Debugoutput="html";
		$phpmailer->Host=SMTP_HOST;
		$phpmailer->Port=SMTP_PORT;
		$phpmailer->SMTPSecure="tls";
		$phpmailer->SMTPAuth=SMTP_AUTH;
		$phpmailer->Username=SMTP_USERNAME;
		$phpmailer->Password=SMTP_PASSWORD;
		$phpmailer->Mailer="smtp";
		$phpmailer->SMTPOptions=array(
			'ssl' => array(
				'verify_peer' => false,
				'verify_peer_name' => false,
				'allow_self_signed' => true
			)
		);
		
		$parameter_from = SUPPORT_EMAIL;
		if( @$arr_from_to_subject["From"] != "" ) $parameter_from = $arr_from_to_subject["From"];
		$phpmailer->SetFrom($parameter_from, $parameter_from);
		$phpmailer->Subject = $arr_from_to_subject["Subject"];
		$phpmailer->MsgHTML($body["html"]);
		if( is_array( $body["attachment"] ) && count( $body["attachment"] ) > 0 ){
		    foreach( $body["attachment"] as $attachment )
		        $phpmailer->addAttachment( $attachment );      
		}

		$target = is_array($to) && array_key_exists("To", $to) ? $to["To"] : $to;
		$arr_target = explode(",", $target);
		foreach ($arr_target as $email) 	$phpmailer->AddAddress( trim($email) );
		
		$targetBcc = $to["Bcc"];
		if($targetBcc == "") $targetBcc = SUPPORT_EMAIL;
		$arr_targetBcc = explode(",", $targetBcc);
		foreach ($arr_targetBcc as $email) 	$phpmailer->addBCC( trim($email) );

		$phpmailer->Send() or die( $phpmailer->ErrorInfo );
		
	}
	
	static function factory( $factory, $array ){
		
		return new Mail();
	}
}

class PEAR{
	
	function isError($object){
		
	}
	
	function getMessage(){
		
	}
	
}

?>