<?php
 $targetfolder = "../upload/surat-pernyataan-new/";
 $targetfolder = $targetfolder . basename( $_FILES['file']['name']) ;
$file_type=$_FILES['file']['type'];

 if(move_uploaded_file($_FILES['file']['tmp_name'], $targetfolder))
 {
    echo "File Berhasil di Upload  file ". basename( $_FILES['file']['name']). " is uploaded";
    //Jalankan perintah insert ke database
 }
 else {
    echo "Not uploaded because of error #".$_FILES["file"]["error"];
 }

?>