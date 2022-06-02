<?
/* CONFIG: Enable debug mode. This means we'll log requests into
'ipn.log' in the same directory.
// Especially useful if you encounter network errors or other
intermittent problems with IPN (validation).*/
// Set this to 0 once you go live or don't require logging.
define("DEBUG", 1);

// Set to 0 once you're ready to go live
define("USE_SANDBOX", 0);
if(USE_SANDBOX == true) {
    $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
} else {
    $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
}

define("LOG_FILE", "lib/paypal/ipn.log");

// konfigurasi utk HTML form post
define("BUSINESS", "finance@modena.co.id");
define("ITEM_NAME", "MODENA INDONESIA - INVOICE NO.");
define("CONFIRMATION_URL", "http://www.modena.co.id/confirmation.php?");
define("IPN_URL", "http://www.modena.co.id/paypal_ipn_handler.php");
?>