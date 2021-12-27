<?
include_once "sql.php";

class culinaria_program extends sql{

	public static function daftar_culinaria_program( $arr_parameter = array(), $limit = "" ){
		$sql = "select *, month(program_awal) bulan_awal, day(program_awal) tanggal_awal, year(program_awal) tahun_awal,
					month(program_akhir) bulan_akhir, day(program_akhir) tanggal_akhir, year(program_akhir) tahun_akhir, datediff(program_awal, CURRENT_TIMESTAMP) hari_tersisa
					from culinaria_program ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		try{		return sql::execute( $sql  . " order by program_awal " . ( $limit != "" ? "limit " . $limit : "" ) );	}
		catch(Exception $e){$e->getMessage();}
	}
	
	public static function daftar_culinaria_program_image(  $arr_parameter = array(), $limit = "" ){
		$sql = "select * from culinaria_program_image ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		try{		return sql::execute( $sql  . " order by image_urutan asc " . ( $limit != "" ? "limit " . $limit : "" ) );	}
		catch(Exception $e){$e->getMessage();}
	}
	
	public static function culinaria_program_detail( $arr_parameter = array() ){
		$sql = "select * from culinaria_program_detail a left outer join culinaria_detail_kode b on a.detail_kode = b.detail_kode ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		try{		return sql::execute( $sql  . " order by b.detail_urutan asc " );	}
		catch(Exception $e){$e->getMessage();}
	}
	
	public static function culinaria_program_detail_kode( $arr_parameter = array() ){
		$sql = "select * from culinaria_detail_kode ";
		
		if ( count($arr_parameter) > 0 )
			$sql .= " where " . sql::sql_parameter( $arr_parameter );

		try{		return sql::execute( $sql  . " order by detail_urutan asc " );	}
		catch(Exception $e){$e->getMessage();}
	}
	
	public static function box_program($template, $template_content, $mode = "", $program_id_excluded = "", $show_padding = true){
				
		$simg = "";
		$counter = 1;
		
		if( $mode == "ADMIN" ){
						
			$arr_parameter = array();
			$arr_input["t_judul"] = "program_judul_id";
			$arr_input["t_chef"] = "program_chef";
			$arr_input["t_tanggal_awal"] = "program_awal";
			$arr_input["s_aktif"] = "program_aktif";
			$arr_default["s_aktif"] = 1;
			
			foreach( $arr_input as $index_input => $db_kolom )
				$arr_parameter[ $db_kolom ] = array(" like ", "'%" . ( @$_REQUEST[ $index_input ] != "" ? @$_REQUEST[ $index_input ] : @$arr_default[ $index_input ] ) . "%'" );
				
			 $operator_status_berlaku_kelas = @$_REQUEST["s_status"] == "" || @$_REQUEST["s_status"] == 1 ? ">" : "<=";
			 $arr_parameter[ "program_awal" ] = array($operator_status_berlaku_kelas , "current_date" );
			
			$rs_program = self::daftar_culinaria_program( $arr_parameter );
			while( $program = mysqli_fetch_array( $rs_program ) ){
				
				$box = self::render_box_program( $counter, $program, $template, $template_content, $show_padding );					
				$simg .= $box[0];
				$counter = $box[1];
				
				$counter++;
			}
		
		}elseif( $mode == "" ){
			
			$arr_parameter["/*program_tanggal*/"] = array("", " ( program_awal >= CURRENT_TIMESTAMP /*or (program_awal <= CURRENT_TIMESTAMP and program_akhir >= CURRENT_TIMESTAMP)*/ )");
			$arr_parameter["program_aktif"] = array("=", 1);
			$rs_program = self::daftar_culinaria_program( $arr_parameter, 8 );
			while( $program = mysqli_fetch_array( $rs_program ) ){
				
				$box = self::render_box_program( $counter, $program, $template, $template_content, $show_padding );					
				$simg .= $box[0];
				$counter = $box[1];
				
				$counter++;
			}
			
		}else{
			
			$jumlah_program_lain_diminati = 4;
			
			$arr_parameter["/*program_tanggal*/"] = array("", " ( program_awal >= CURRENT_TIMESTAMP /*or (program_awal <= CURRENT_TIMESTAMP and program_akhir >= CURRENT_TIMESTAMP)*/ )");
			$arr_parameter["program_aktif"] = array("=", 1);
			$rs_program = self::daftar_culinaria_program( $arr_parameter, 8 );
			while( $program = mysqli_fetch_array( $rs_program ) )	$arr_program[] = $program;
			$hitungan = 1;
			$arr_rand = array();

			while(true === true){
				
				$rand = rand( 0, count($arr_program) - 1 );
				
				if( !in_array( $rand, $arr_rand ) && array_key_exists( $rand, $arr_program ) && !in_array( $arr_program[ $rand ]["program_id"], $program_id_excluded ) ){
					$arr_rand[] = $rand;
					$box = self::render_box_program( $counter, $arr_program[ $rand ], $template, $template_content, $show_padding, 0 );
					$simg .= $box[0];
					$counter = $box[1]; 
					$counter++;
					$hitungan++;
				}
				
				if( count( $arr_rand ) >= $jumlah_program_lain_diminati || $hitungan > $jumlah_program_lain_diminati || count( $arr_rand ) >= count($arr_program) - 1 ) break;
				
			}
			
			if( $jumlah_program_lain_diminati == 4 )
			$simg .= "<style>
						.gallery-container, .gallery-container div.content{width:236px !important; height:236 !important;}
						.gallery-container div.content div{padding-left:7px !important; padding-right:8px !important; height:100%; width:227px !important}
						h4{font-size:19px; font-weight:300}
						h5{font-size:15px; font-weight:500}
						.content-footer{ top:177px; right:8px; border:none 1px black; opacity: 1  !important; filter: alpha(opacity=100) !important;}
						.h4-tanggal_awal{margin-top:3px}
						.h5-judul{margin:27px 0px -27px 0px}
						.h5-harga{margin:7px 0px -31px 0px}
						</style>";
				
		}
		
		return $simg;
	}
	
	private static function render_box_program( $counter, $program, $template, $template_content, $show_padding, $counter_show_default = 4 ){
		// sisa kursi
		include_once "cls_culinaria_cart.php";
		$rs_sisa_kursi = culinaria_cart::sisa_kursi_program( array("c.program_id" => array("=", "'". main::formatting_query_string( $program["program_id"] ) ."'") ) );
		$sisa_kursi = sql::sql_fetch_array( $rs_sisa_kursi );
		
		$folder = "";
		$arr_month = $GLOBALS["arr_month"];
		$lang = $GLOBALS["lang"];
		
		unset( $arr_parameter );
		$arr_parameter["program_id"] = array("=", "'". main::formatting_query_string( $program["program_id"] ) ."'");
		$arr_parameter["image_homeicon"] = array("=", "1");
		$rs_program_image = self::daftar_culinaria_program_image( $arr_parameter );
		$program_image = mysqli_fetch_array( $rs_program_image );
		
		$tmp_default  = "";
		if( $counter == $counter_show_default && $show_padding ){
			$tmp_default = $template;
			$tmp_default = str_replace("#path#", $folder . "default.jpg", $tmp_default);
			$tmp_default = str_replace("class=\"content\"", "class=\"content-none\"", $tmp_default);
			$tmp_default = str_replace("#idnya#", "default", $tmp_default);
			$tmp_default = str_replace("#div-middle#", "padding-left:4px", $tmp_default); 
			$counter = $counter_show_default + 1;
		}

		$tmp = $template;
		$string_template_content = $template_content;
		
		$ar_template_content["#tanggal_awal#"] = $arr_month[ $program["bulan_awal"]-1 ] . ", " . $program["tanggal_awal"];
		$ar_template_content["#kelas_memasak#"] = "<h5 class=\"h5-judul\">" . $program["program_judul_" . $lang] . "</h5><h4>". $program["program_chef"] ."</h4>" ;
		$ar_template_content["#harga#"] = "<h5 class=\"h5-harga\">Rp" . number_format( $program["program_harga"] ) . "</h5>" ;
		$ar_template_content["#hari#"] = $program["hari_tersisa"] ;
		$ar_template_content["#idnya#"] = $program["program_id"] ;
		$ar_template_content["#jumlah_peserta#"] = culinaria_cart::tabel_data_peserta( $program["program_id"], @file_get_contents(__DIR__ . "\..\secure\culinaria-program-peserta.php.html") )["jumlah_kursi_terpakai"];
		$ar_template_content["#disabled#"] = $sisa_kursi["sisa_kursi"] <= 0 ? "disabled=\"true\"" : "" ;
		$ar_template_content["#label_button#"] = $sisa_kursi["sisa_kursi"] <= 0 ? strtoupper( $GLOBALS["lang_sold"]) : strtoupper( $GLOBALS["lang_register"] ) ;
		
		$string_template_content = str_replace( array_keys( $ar_template_content ), array_values( $ar_template_content ), $string_template_content );

		$as = array("#path#", "#div-middle#", "#program-content#");	
		$style = "";
		
		if($counter_show_default == 4){
			if( $show_padding ) $style = ( ($counter - 1 ) % 3 == 0 ? "padding-right:4px" : ( $counter % 3 == 0 ? "padding-left:4px" : "" )  ) ;	
		}else{
			if( $show_padding ) $style = ( $counter  % 4 == 0 ? "" : "padding-right:4px" ) ;	
		}
		
		$ar = array($folder . $program_image["image_url"], $style, $string_template_content);
		
		$simg = str_replace($as, $ar, $tmp . $tmp_default);
		return array($simg, $counter);
	}

	static function insert_culinaria_program_detail( $arr_col ){
		$sql = "insert into culinaria_program_detail (". implode(",", array_keys( $arr_col )) .") values(". implode(",", array_values( $arr_col )) .");";
		return sql::execute($sql);
	}
	
	static function update_culinaria_program_detail( $arr_set, $arr_parameter ){
		$sql = "update culinaria_program_detail set ". self::sql_parameter( $arr_set, "," ) ." where ". self::sql_parameter( $arr_parameter ) .";";
		return sql::execute($sql);
	}
	
	/* $arr_col : array() : [nama kolom]=>[nilai kolom] */
	static function insert_culinaria_program( $arr_col ){
		$sql = "insert into culinaria_program (". implode(",", array_keys( $arr_col )) .") values(". implode(",", array_values( $arr_col )) .");";
		return sql::execute($sql);
	}
	
	static function update_culinaria_program( $arr_set, $arr_parameter ){
		$sql = "update culinaria_program set ". self::sql_parameter( $arr_set, "," ) ." where ". self::sql_parameter( $arr_parameter ) .";";
		return sql::execute($sql);
	}
	
	static function insert_culinaria_program_image( $arr_col ){
		$sql = "insert into culinaria_program_image (". implode(",", array_keys( $arr_col )) .") values(". implode(",", array_values( $arr_col )) .");";
		return sql::execute($sql);
	}
	
	static function delete_culinaria_program_image( $arr_parameter ){
		$sql = "delete from culinaria_program_image where ". self::sql_parameter( $arr_parameter ) .";";
		return sql::execute($sql);
	}

}

?>
