<?
class sql extends main {

	/*
	fungsi untuk membentuk xml data dari record set
	parameter : 
		$sqlobject : untuk opsi mysql = connection object, untuk sqlserver = NULL
		$rs : recordset
		$mode : 1 = mysql, 2 = sql server
	return : (string) xml
	*/
	private static function generate_xml_data($sqlobject, $rs, $mode=1 /* 1:mysql, 2:sql server */){
		//header('Content-Type: text/xml');
		$return="<?xml version=\"1.0\" encoding=\"ISO-8859-1\" ?>
			<results>";
		if($mode==1){
			$arr_column=array();
			for($x=0; $x<$sqlobject->field_count; $x++)	$arr_column[count($arr_column)]=$rs->fetch_field_direct($x)->name;
			while($data=$rs->fetch_array(MYSQLI_NUM)){
				$return.="<result>";
				foreach($arr_column as $key=>$index)
					$return.="<$index>".main::remove_character($data[$key], array("&"=>"&amp;"))."</$index>";
				$return.="</result>";
			}
			$rs->close();
		}
		elseif($mode==2){
			if($rs===false)
				$return.="<result><status>Fail</status></result>";
			else{
				$arr_column=array();
				foreach(sqlsrv_field_metadata($rs) as $fieldMetadata) {
					foreach($fieldMetadata as $name=>$value)
					   if(strtoupper($name)=="NAME")	$arr_column[]=$value;
				}
				if(sqlsrv_num_rows($rs)>0){
					while($data=sqlsrv_fetch_array($rs)){
						$return.="<result>";
						foreach($arr_column as $field)
							$return.="<$field>".main::remove_character(trim($data[$field]), array("&"=>"&amp;"))."</$field>";
						$return.="</result>";
					}
				}else{
					$return.="<result><status>0 Data</status></result>";
				}
			}
		}
		$return.="</results>";
		return $return;
	}
	
	/*
	fungsi untuk membentuk json data dari record set
	parameter : 
		$sqlobject : untuk opsi mysql = connection object, untuk sqlserver = NULL
		$rs : recordset
		$mode : 1 = mysql, 2 = sql server	
	return : (string) json
	*/
	private static function generate_json_data($sqlobject, $rs, $mode=1 /* 1:mysql, 2:sql server */){

		$return = array();
		
		if($mode==1){
			$arr_column=array();
			for($x=0; $x<$sqlobject->field_count; $x++)	$arr_column[count($arr_column)]=$rs->fetch_field_direct($x)->name;
			
			while($data=$rs->fetch_array(MYSQLI_NUM)){

				foreach($arr_column as $key=>$index)		$arr_data[ $index ] = $data[$key];

				$return[] = $arr_data;
			}

			$rs->close();
		}
		elseif($mode==2){
			if($rs!==false){
				$arr_column=array();
				foreach(sqlsrv_field_metadata($rs) as $fieldMetadata) {
					foreach($fieldMetadata as $name=>$value)
					   if(strtoupper($name)=="NAME")	$arr_column[]=$value;
				}
				if(sqlsrv_num_rows($rs)>0){
					while($data=sqlsrv_fetch_array($rs)){
						
						foreach($arr_column as $field)		$arr_data[ $field ] = $data[$field];
						$return[] = $arr_data;
						
					}
				}
			}
		}

		return json_encode( $return );
	}

	static function fetch_array( $rs ){
		return mysqli_fetch_array( $rs );
	}
	
	static function num_rows( $rs ){
		return mysqli_num_rows( $rs );
	}
	
	static function execute($sql, $is_recordset_return=true){
		/* MYSQL */
		$mysqli = new mysqli($GLOBALS["server"], $GLOBALS["username"], $GLOBALS["password"], $GLOBALS["database"]);
		if (mysqli_connect_errno()) return "Terjadi kegagalan koneksi ke database";    
		$rs=$mysqli->query($sql) or die(mysqli_error($mysqli));
		if( $is_recordset_return )
			return $rs;
		else
			return self::generate_json_data(NULL, $rs, 2);
		//$mysqli->close();
		

		/* SQL SERVER 		
		sqlsrv_configure("WarningsReturnAsErrors", 0);
		$rs=sqlsrv_query($GLOBALS["conn"],$sql, NULL, array("scrollable" => SQLSRV_CURSOR_STATIC)	) ;//or die("gagal kueri : ");
		if($rs===false)	throw new Exception(print_r(sqlsrv_errors() + array("statement" => $sql) ));
		if($is_recordset_return)			
			return $rs;
		else
			return self::generate_json_data(NULL, $rs, 2);
		*/
	}
	
	/*
	fungsi untuk menyusun parameter sql
	parameter : array ("kolom" => array("operator" => "nilai") )
	return : (string) sql parameter
	*/
	static function sql_parameter($arr_parameter, $separator = " and "){
		
		foreach( $arr_parameter as $kolom=>$arr_operator_nilai ){
			list($operator, $nilai) = $arr_operator_nilai;
			$aparameter[] =  $kolom . $operator . $nilai;
		}
		return implode($separator, $aparameter);
		
	}
	
	static function sql_sort($arr_sort){
		
		$ssort = ""; $asort = array();
		if( count($arr_sort) > 0 ) $ssort = " order by ";
		foreach( $arr_sort as $kolom=>$sort ){
			$asort[] =  $kolom . " " . $sort;
		}
		return $ssort . (is_array($asort) && count($asort) > 0 ? implode(",", $asort) : "");
		
	}
	
	static function __update($table, $update_kolom, $parameter){
		
		$sql = "update $table set ";
		foreach( $update_kolom as $kolom => $nilai )
			$arr_update[] = $kolom . " = '". main::formatting_query_string( $nilai ) ."'";
		
		$sql .= implode(",", $arr_update) . " where " . sql::sql_parameter( $parameter );
		
		sql::execute( $sql );
		
	}

	static function __insert( $table, $kolom_nilai, $return_nilai_id = false ){
		
		$sql = "insert into $table(#kolom#) values(#nilai#);";
		foreach( $kolom_nilai as $kolom => $nilai ){
			$arr_kolom[] = $kolom;
			$arr_nilai[] = "'" . main::formatting_query_string( $nilai ) . "'";
			$par_return_nilai_id[] = $kolom . " =  '". main::formatting_query_string( $nilai ) ."' ";
		}
		
		$arr_rpl["#kolom#"] = implode(",", $arr_kolom);
		$arr_rpl["#nilai#"] = implode(",", $arr_nilai);
		
		$sql = str_replace( array_keys($arr_rpl), array_values( $arr_rpl ), $sql );
		
		sql::execute( $sql );
		
		if( $return_nilai_id !== false ){
			$sql = "select ". $return_nilai_id ." from ". $table ." where  " . implode(" and ", $par_return_nilai_id) . " order by ". $return_nilai_id ." desc limit 1";
			$rs = sql::fetch_array( sql::execute( $sql ) );
			return $rs[ $return_nilai_id ];
		}
		
	}
}
?>