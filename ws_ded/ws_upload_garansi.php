<?

if( in_array($_REQUEST["sc"], array("lihat_foto", "lihat_foto_pernyataan", "lihat_pdf_pernyataan", "lihat_foto_ktp", "lihat_foto_selfie")) )
    header("location:https://ws.modena.co.id/ws_upload_garansi.php?sc=". $_REQUEST["sc"] ."&k=" . $_REQUEST["k"]);

$arr_filename = glob( __DIR__ . "/../upload/garansi/*". $_REQUEST["k"] ."*.jpg");
/* dipindahkan ke folder ws1, utk yg mengandung imagecreatefromjpeg */
if( $_REQUEST["sc"] == "lihat_foto" ){
	
	foreach ( $arr_filename as $filename ) {
		header("Content-Type: image/jpg");
		$img = imagecreatefromjpeg( $filename );
		imagejpeg( $img );
		imagedestroy( $img );
		exit;
	}
}elseif ($_REQUEST["sc"] == "lihat_foto_pernyataan") {
	$arr_filename = glob( __DIR__ . "/../upload/suratpernyataan/*". $_REQUEST["k"] ."*.png");
	foreach ( $arr_filename as $filename ) {
		header("Content-Type: image/jpg");
		$img = imagecreatefromjpeg( $filename );
		imagejpeg( $img );
		imagedestroy( $img );
		exit;
	}

}elseif ($_REQUEST["sc"] == "lihat_pdf_pernyataan") {
	$arr_filename = glob( __DIR__ . "/../upload/suratpernyataan/*". $_REQUEST["k"] ."*.pdf");
	foreach ( $arr_filename as $filename ) {
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($filename));
        header('Accept-Ranges: bytes');
        @readfile($filename);
		exit;
	}

}elseif ($_REQUEST["sc"] == "lihat_foto_ktp") {
	$arr_filename = glob( __DIR__ . "/../upload/ktp/*". $_REQUEST["k"] ."*.jpg");
	foreach ( $arr_filename as $filename ) {
		header("Content-Type: image/jpg");
		$img = imagecreatefromjpeg( $filename );
		imagejpeg( $img );
		imagedestroy( $img );
		exit;
	}

}elseif ($_REQUEST["sc"] == "lihat_foto_selfie") {
	$arr_filename = glob( __DIR__ . "/../upload/selfie/*". $_REQUEST["k"] ."*.jpg");
	foreach ( $arr_filename as $filename ) {
		header("Content-Type: image/jpg");
		$img = imagecreatefromjpeg( $filename );
		imagejpeg( $img );
		imagedestroy( $img );
		exit;
	}

}elseif ($_REQUEST["sc"] == "surat_pernyataan") {
	$new_image_name = urldecode($_FILES["file"]["name"]).".jpg";
	move_uploaded_file($_FILES["file"]["tmp_name"], "upload/suratpernyataan/".$new_image_name);
	echo json_encode( array( "nama_image" => $new_image_name ) ) ;
	exit();

}elseif ($_REQUEST["sc"] == "surat_pernyataan_new") {
	$new_image_name = $_FILES["file"]["name"].".pdf";
	move_uploaded_file($_FILES["file"]["tmp_name"], "upload/suratpernyataan/".$new_image_name);
	echo json_encode( array( "nama_image" => $new_image_name ) ) ;
	exit();

}elseif ($_REQUEST["sc"] == "ktp") {
	$new_image_name = urldecode($_FILES["file"]["name"]).".jpg";
	move_uploaded_file($_FILES["file"]["tmp_name"], "upload/ktp/".$new_image_name);
	echo json_encode( array( "nama_image" => $new_image_name ) ) ;
	exit();
	
}elseif ($_REQUEST["sc"] == "selfie") {
	$new_image_name = urldecode($_FILES["file"]["name"]).".jpg";
	move_uploaded_file($_FILES["file"]["tmp_name"], "upload/selfie/".$new_image_name);
	echo json_encode( array( "nama_image" => $new_image_name ) ) ;
	exit();

}elseif( $_REQUEST["sc"] == "nama_image" ){
	
	echo json_encode( array( "nama_image" => @basename( $arr_filename[0]) ) ) ;
	exit;
}elseif( $_REQUEST["sc"] == "nama_image_pernyataan" ){
	$arr_filename = glob( __DIR__ . "/../upload/suratpernyataan/*". $_REQUEST["k"] ."*.jpg");
	echo json_encode( array( "nama_image" => @basename( $arr_filename[0]) ) ) ;
	exit;
}else{

    $new_image_name = urldecode($_FILES["file"]["name"]).".jpg";
    move_uploaded_file($_FILES["file"]["tmp_name"], "upload/garansi/".$new_image_name);
    
    // kirim email ke CS dan konsumen
    $arr_par["tm"] = str_replace("_", ",", $_FILES["file"]["name"]);
    $arr_par["kirim_customer"] = "yes";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,  $url_path . "/ws_ded/ws_kirim_email_registrasi_produk.php");
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($arr_par));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $server_output = curl_exec ($ch);
}

?>