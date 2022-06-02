<?

/*
fungsi untuk mengirimkan email order pembelian.
parameter dibutuhkan dengan metode GET
1. custemail
2. order_no
*/

include_once "../../lib/var.php";
include_once "../../lib/cls_main.php";
include_once "../../lib/cls_order.php";
$lang="id";
include_once "../../lang/$lang.php";

try{
	$order=new order;
	$order->lang="../../lang/".$lang.".php";
	$order->arr_par_ordercustomer=$arr_par_ordercustomer;
	$order->custemail=$_REQUEST["custemail"];
	$order->order_no=$_REQUEST["order_no"];
	$order->message_no=$message->message_no;
	$order->order_print_template="../../template/order_print.html";
	$order->email_template="../../template/email.html";
	$order->email_subject=$lang_email_order_subject;
	//$order->print_order("email", "customer");
	$order->print_order("email", "customer");
}catch(Exception $e){echo $e->getMessage();}
		
?>