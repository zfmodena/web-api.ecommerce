<?

include "../lib/var.php";
include "../lib/cls_main.php";

$username_recovery = "modenadotcom";
$password_recovery = "KopiKapalApi17";
$database_recovery = "modenado_modena";

if( $phpversion_major >= 7 )
    $mysqli_connection = mysql_connect($server_recovery, $username_recovery, $password_recovery, $database_recovery) or die("");//("Could not connect :  ". mysql_error());
else{
/* aktifkan line berikut utk PHP 5.6 */
    mysql_connect($server_recovery, $username_recovery, $password_recovery) or die("");//("Could not connect :  ". mysql_error());
    mysql_select_db($database_recovery) or die("");// ("Error connect to database : ". mysql_error());
}
mysql_query("SET SESSION sql_mode = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';");


?>