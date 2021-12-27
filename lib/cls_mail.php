<?

class sendmail{
	static function send($to, $from, $subject, $body){
		require_once "pear/Mail.php";
		require_once "pear/mime.php";

		$mime=new Mail_mime(array('eol' => CRLF));
		 
		 $mime->setTXTBody(strip_tags($body));
		$mime->setHTMLBody($body);
		 
		 $headers = array ('From' => $from,
		   'To' => $to,
		   'Subject' => $subject);
			 $smtp=Mail::factory('smtp',
		   array ('host' => SMTP_HOST,
			 'auth' => SMTP_AUTH,
			 'username' => SMTP_USERNAME,
			 'password' => SMTP_PASSWORD));
		
		$body = $mime->get();
		$hdrs = $mime->headers($headers);
		
		 $mail = $smtp->send($to, $hdrs, $body);
		
		$pear=new PEAR;
		 if ($pear->isError($mail)) {
		   echo("<p>" . $mail->getMessage() . "</p>");
		   echo "error";
		  } else {
		   echo("<p>Message successfully sent!</p>");
		  }		
		
	}
}

?>