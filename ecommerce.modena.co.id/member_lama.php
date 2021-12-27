<?

// entri apabila tidak ada sender ataupun email di database website
// tahap pertama, cek sender (HP) apabila ada return 1 (existing member)
// tahap kedua, cek email (tidak ada sender di tahap pertama) apabila ada return 1 (existing member)
// apabila di tahap pertama dan kedua tidak ada, maka entri baru : return 0 (new entry)

if( isset($_REQUEST["sender"]) && $_REQUEST["sender"] != "" ){
    $_REQUEST["sender"] = str_replace(array("+", " "),"", $_REQUEST["sender"]);

    // hilangkan kode negara
    $kode_negara = file_get_contents("kode_negara.json");
    $arr_kode_negara = json_decode($kode_negara);
    foreach( $arr_kode_negara as $json_kode_negara ){
        $kode_negara = str_replace("+","", $json_kode_negara->dial_code);
        if( substr( $_REQUEST["sender"] , 0, strlen($kode_negara)) == $kode_negara) {
            $_REQUEST["sender"] = str_replace("+" . $kode_negara, "", "+" . $_REQUEST["sender"]);
            break;
        }
    }

    $sql = "select 1 from membersdata where 
        CONCAT(REPLACE(LEFT(phone, INSTR(phone, '0')), '0', ''), SUBSTRING(phone, INSTR(phone, '0') + 1)) = '". main::formatting_query_string($_REQUEST["sender"]) ."' or
        CONCAT(REPLACE(LEFT(phone, INSTR(handphone, '0')), '0', ''), SUBSTRING(handphone, INSTR(handphone, '0') + 1)) = '". main::formatting_query_string($_REQUEST["sender"]) ."' 
    ";

    $rs = mysql_query($sql);
    if(mysql_num_rows($rs) > 1 )
        $json = array(status => "1", "keterangan" => "existing member");
    else{
        // cek email
        $sql = "select 1 from membersdata where email = '". main::formatting_query_string($_REQUEST["customer_email"]) ."' ";
        $rs_email = mysql_query($sql);
        
        if( mysql_num_rows( $rs_email ) > 1 )
            $json = array(status => "1", "keterangan" => "existing member");
            
        else{
            $sql = "insert into membersdata(name, email, phone, handphone, password) 
                values(
                    '". mail::formatting_query_string($_REQUEST["customer_name"]) ."',
                    '". mail::formatting_query_string($_REQUEST["customer_email"]) ."',
                    '". mail::formatting_query_string($_REQUEST["sender"]) ."',
                    '". mail::formatting_query_string($_REQUEST["sender"]) ."',
                    LEFT(UUID(), 10)
                )";
            //mysql_query($sql);
            // kirim email registrasi
            $json = array(status => "0", "keterangan" => "new entry");
        }
    }
    $json = json_encode($json,  JSON_UNESCAPED_UNICODE);
    header("Content-Type: application/json");
    echo $json;

}

?>