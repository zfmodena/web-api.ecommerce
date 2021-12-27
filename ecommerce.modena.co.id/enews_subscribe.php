<?
include_once "../lib/var.php";
include_once "lib/cls_main.php";

// cek email
if( trim(@$_REQUEST["email"]) == "" ) die("E0");

// otorisasi key
$auth = sha1(__KEY__ . sha1(@$_REQUEST["rand"]) . str_replace("@", "", trim(@$_REQUEST["email"]) ) ) ;
//die($auth);
if( $auth != @$_REQUEST["auth"] ) die("E1");

define("__STATUS__", "subscribed");

$data = array(
    'email'     => $_REQUEST["email"],
    'status'    => __STATUS__,
    'firstname' => isset($_REQUEST["nama"]) && $_REQUEST["nama"] != "" ? $_REQUEST["nama"] : $_REQUEST["email"]
);

include_once "lib/class.verifyEmail.php";

$vmail = new verifyEmail();
$vmail->setStreamTimeoutWait(20);
$vmail->Debug= FALSE;
$vmail->Debugoutput= 'html';
$vmail->setEmailFrom('support@modena.co.id');

if ($vmail->check($_REQUEST["email"])) {

    include "enews_mailchimp.php";
    //include "enews_mailkitchen.php";
    
}else
    $data["status"] = "invalid email";
    
$json = json_encode($data);
header("Content-Type: application/json");
echo $json;

?>