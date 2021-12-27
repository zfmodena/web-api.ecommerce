<?

$login = 'social@modena.com';
$password = 'modena328';
$listnama_website = "Potential Leads Website";

$arr_folder_newsletter["global"] = "Testing Website";
$arr_folder_newsletter["vietnam"] = "Vietnam Website Customer";
$arr_folder_newsletter["kamboja"] = "Cambodia Website Customer";
$arr_folder_newsletter["rusia"] = "Russia Website Customer";

if( @$_REQUEST["country"] != "" && array_key_exists($_REQUEST["country"], $arr_folder_newsletter) )
    $listnama_website = $arr_folder_newsletter[ $_REQUEST["country"] ];
$listid_website = "";

try {
 
	$ws = new SoapClient(
			"http://webservices.mailkitchen.com/server.wsdl",
			array('trace' => 1, 'soap_version'   => SOAP_1_2)
	);
	$token = $ws->Authenticate($login, $password);
 
	$arr_list = $ws->GetSubscriberLists();
	foreach($arr_list as $listinfo){
	    if( $listinfo["name"] == $listnama_website ){
	        $listid_website = $listinfo["id"];
	        break;
	    }   
	}
	
	$datas = array (
		'header'	=> array ("email"),
		'datas'		=> array (
			0 => array( $_REQUEST["email"] )
		)
	);
	$report = $ws->ImportMember(array($listid_website), $datas);
	$data["status"] = $report[0]["nbDoublon"] == 1 ? "duplicate" : __STATUS__;
 
}
catch (SoapFault $exception) { 
	header('Content-Type: text/plain; charset: utf-8');
	echo $exception->faultcode . ' : ' . $exception->getMessage();
	$data["status"] = "failed : " . $exception->faultcode . ' : ' . $exception->getMessage();
}

?>