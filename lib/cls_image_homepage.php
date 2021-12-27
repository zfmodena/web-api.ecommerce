<?

class image_homepage extends main{
	
	private $halaman;
	private $template_home_image_cell;
	private $default_template_home_image_cell = "<td style=\"#space_v# letter-spacing:0.75px; vertical-align:top\">
					<a href=\"#url#\">
						<div style=\"width:316px; height:256px; overflow:hidden; margin-bottom:0px; color:#FFF; position:relative\">
							<img src=\"#HOMEPAGE_IMAGE_PATH#.jpg\" alt=\"#label#\"
								style=\"height:100%; border:none;\" />
								<div style=\"width:316px; height:256px; text-align:center;color:#FFF; position:absolute; display: table-cell; vertical-align: middle; top:50%; left:0px\">#label#</div>
						</div>					
					</a>
				</td>",
			$jumlah_kolom = 3;
	public $homepage_script_tambahan;
	
	function __construct($jenis = 0, $template_home_image_cell = ""){
		$this->halaman = $GLOBALS["page"];
		
		$this->template_home_image_cell = $this->default_template_home_image_cell;
		if( $template_home_image_cell != "" ) $this->template_home_image_cell = $template_home_image_cell;		
		
		$this->dapatkan_data_image($jenis);
	}
	
	private function dapatkan_data_image($jenis = 0){
		$sql = "select path, label_headline, label_subheadline, label_warna, script_tambahan, url from image_homepage 
				where 
				halaman = '". main::formatting_query_string($this->halaman) ."' and 
				jenis = '". main::formatting_query_string($jenis) ."' and
				current_date() >= berlaku_awal and current_date() <= berlaku_akhir order by urutan";
		$rs = mysql_query($sql) or die();
		if( mysql_num_rows($rs) > 0 ){
			$row = $cell= 1;
			while( $data = mysql_fetch_array($rs) ){

				$arr_data["#halaman#"] = $this->halaman;
				$arr_data["#image#"] = $data["path"];					
				$image_path = str_replace(array_keys($arr_data), array_values($arr_data), HOMEPAGE_IMAGE_PATH) . ".jpg";
				
				$this->homepage_script_tambahan = $data["script_tambahan"];
			
				// image cell
				if($jenis == 1){
				
					if( file_exists($image_path) ) 
						$GLOBALS["arr_imagecell_homepage"][$this->halaman][ $row ][ $cell ] = 
							array("label" => $data["label_headline"] , "url" => $data["url"], "path" => $data["path"]);
					$cell++;
					if($cell > $this->jumlah_kolom) {
						$cell = 1;
						$row++;
					}
				
				// image promo
				}elseif($jenis == 2){

					$arr_data["#halaman#"] = $_SESSION["homepage"];
					$arr_data["#image#"] = $data["path"];					
					$image_path = str_replace(array_keys($arr_data), array_values($arr_data), HOMEPAGE_IMAGE_PATH) . ".jpg";
					
					// cek mobile version, khusus untuk mode tampilan akses dari mobile
					if(@$GLOBALS["is_mobile_active"]){
						$mobile_img = str_replace(".jpg", ".mobile.jpg", $image_path);
						if(file_exists( $mobile_img )) $image_path = $mobile_img;
					}
					
					if(file_exists( $image_path )){
						$GLOBALS["arr_imagepromo_homepage"][ $_SESSION["homepage"] ][$row - 1]["#home_banner#"] = $image_path;
						$GLOBALS["arr_imagepromo_homepage"][ $_SESSION["homepage"] ][$row - 1]["#banner_url#"] = $data["url"];
						if($data["url"] != "") $GLOBALS["arr_imagepromo_homepage"][ $_SESSION["homepage"] ][$row - 1]["#banner_cursor#"] = "pointer";
						else $GLOBALS["arr_imagepromo_homepage"][ $_SESSION["homepage"] ][$row - 1]["#banner_cursor#"] = "default";
						$GLOBALS["arr_imagepromo_homepage"][ $_SESSION["homepage"] ][$row - 1]["#font_color#"] = $data["label_warna"];
						$GLOBALS["arr_imagepromo_homepage"][ $_SESSION["homepage"] ][$row - 1]["#string_headline#"] = $data["label_headline"];
						$GLOBALS["arr_imagepromo_homepage"][ $_SESSION["homepage"] ][$row - 1]["#string_subheadline#"] = $data["label_subheadline"];		
						$row++;
						//break;
					}
				
				// image headline
				}else{
					// cek mobile version, khusus untuk mode tampilan akses dari mobile
					if(@$GLOBALS["is_mobile_active"]){
						$mobile_img = str_replace(".jpg", ".mobile.jpg", $image_path);
						if(file_exists( $mobile_img )) $image_path = $mobile_img;
					}
					
					if(file_exists( $image_path )){
						$GLOBALS["arr_imageheadline_homepage"][$this->halaman][$row - 1]["#home_banner#"] = $image_path;
						$GLOBALS["arr_imageheadline_homepage"][$this->halaman][$row - 1]["#banner_url#"] = $data["url"];
						if($data["url"] != "") $GLOBALS["arr_imageheadline_homepage"][$this->halaman][$row - 1]["#banner_cursor#"] = "pointer";
						else $GLOBALS["arr_imageheadline_homepage"][$this->halaman][$row - 1]["#banner_cursor#"] = "default";
						$GLOBALS["arr_imageheadline_homepage"][$this->halaman][$row - 1]["#font_color#"] = $data["label_warna"];
						$GLOBALS["arr_imageheadline_homepage"][$this->halaman][$row - 1]["#string_headline#"] = $data["label_headline"];
						$GLOBALS["arr_imageheadline_homepage"][$this->halaman][$row - 1]["#string_subheadline#"] = $data["label_subheadline"];			
						$row++;
						//break;
					}
				}				
			}
		}
		return true;
	}
	
	// fungsi untuk pembentukan image cell di homepage
	function home_image_cell($arr_label){
		
		if(!is_array($arr_label)) return "";

		$space_v = "padding-bottom:4px;";
		$space_h = "<td style=\"width:4px; $space_v\"></td>";
		if( $this->template_home_image_cell != $this->default_template_home_image_cell ) 
			$space_h = "<div style=\"$space_v\"></div>";		
		
		$this->template_home_image_cell = str_replace("#HOMEPAGE_IMAGE_PATH#", HOMEPAGE_IMAGE_PATH, $this->template_home_image_cell);			
		
		$row = count(array_keys($arr_label));	
		
		for($x = 1; $x <= $row; $x++){
			@$string_temp .= "<tr>";
			$col = count(array_keys($arr_label[$x]));
			for($y = 1; $y <= $col; $y++){
				
				@$string_temp .= str_replace(
												array( "#halaman#", "#image#", "#space_v#", "#label#", "#url#" ), 
												array( $this->halaman, $arr_label[$x][$y]["path"], ($x < $row ? $space_v : ""), strtoupper($arr_label[$x][$y]["label"]), $arr_label[$x][$y]["url"] ), 
												$this->template_home_image_cell
											) . 
									($y < $col ? $space_h : "");
			}
			@$string_temp .= "</tr>";
		}
		return $string_temp;
	}
}

?>