<?

if( @$_REQUEST["c"] != "" ){
    if( file_exists($_REQUEST["c"] . ".php") )
        include_once $_REQUEST["c"] . ".php";
        
}

?>