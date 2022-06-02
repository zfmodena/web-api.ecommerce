<?

define("MYSQL_ASSOC", MYSQLI_ASSOC);

function mysql_connect($server, $username, $password, $database){
	return mysqli_connect($server, $username, $password, $database);
}

function mysql_query($sql){
	return mysqli_query($GLOBALS["mysqli_connection"], $sql);
}

function mysql_fetch_array($result){
	return mysqli_fetch_array($result, MYSQL_ASSOC);
}

function mysql_num_rows($result){
	return mysqli_num_rows($result);
}	

function mysql_data_seek($result, $row){
	return mysqli_data_seek($result, $row);
}
function mysql_error(){
	return mysqli_error( $GLOBALS["mysqli_connection"] );
}

?>