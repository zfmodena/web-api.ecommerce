<?

/*
fungsi untuk mengirimkan email order pembelian.
parameter dibutuhkan dengan metode GET
1. custemail
2. order_no
*/

include_once "lib/var.php";
//error_reporting(E_ALL);
include_once "lib/cls_main.php";
include_once "lib/cls_order.php";
$lang="id";
include_once "lang/$lang.php";

include_once "lib/cls_culinaria_program.php";
include_once "lib/cls_culinaria_cart.php";
include_once "lib/cls_culinaria_cart_log.php";	
include_once "lang/". $lang ."_culinaria.php";

try{
	$order=new culinaria_cart;
	$order->lang="lang/".$lang.".php";
	$order->arr_par_ordercustomer=$arr_par_ordercustomer;
	$order->custemail=$_REQUEST["custemail"];
	$order->order_no=$_REQUEST["order_no"];
	//$order->message_no=$message->message_no;
	$order->order_print_template="template/culinaria_order_print.html";
	$order->template_data_order = "culinaria-cart-item-email.html";
	$order->email_template="template/email.html";
	$order->email_subject=$lang_email_order_subject;
	$order->t_message_en="Order ini diberikan status : 
			<span style=\"font-weight:bold; background-color:green; color:white; padding:3px\">&nbsp;&nbsp;PASS&nbsp;&nbsp;</span>";
	//$order->print_order("email", "customer");
	$order->print_order("email", "");
}catch(Exception $e){echo $e->getMessage();}
		
?>