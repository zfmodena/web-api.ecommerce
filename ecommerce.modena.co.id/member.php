<?

if( isset($_REQUEST["ovr"]) && $_REQUEST["ovr"] != "" ) goto Tetap_pakai_yang_baru;

// temporary sblm active aktif
//include "../lib/var.php";
//include "../lib/cls_main.php";
//include "member_lama.php";
//exit;

Tetap_pakai_yang_baru:

// entri apabila tidak ada sender ataupun email di database website
// tahap pertama, cek sender (HP) apabila ada return 1 (existing member)
// tahap kedua, cek email (tidak ada sender di tahap pertama) apabila ada return 1 (existing member)
// apabila di tahap pertama dan kedua tidak ada, maka entri baru : return 0 (new entry)

include "db_active.v2.php";

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

    $sql = "select 1 from users where 
        CONCAT(REPLACE(LEFT(phone, INSTR(phone, '0')), '0', ''), SUBSTRING(phone, INSTR(phone, '0') + 1)) = '". main::formatting_query_string($_REQUEST["sender"]) ."'
    ";

    $rs = mysql_query($sql);
    if(mysql_num_rows($rs) > 0 )
        $json = array(status => "1", "keterangan" => "existing member");
    else{
        // cek email
        $sql = "select 1 from users where email = '". main::formatting_query_string($_REQUEST["customer_email"]) ."' ";
        $rs_email = mysql_query($sql);
            
        if( mysql_num_rows( $rs_email ) > 0 ){
            $json = array(status => "1", "keterangan" => "existing member");
            
        }else{
            $sql = "insert into users(name, email, phone, password) 
                values(
                    '". main::formatting_query_string($_REQUEST["customer_name"]) ."',
                    '". main::formatting_query_string($_REQUEST["customer_email"]) ."',
                    '". main::formatting_query_string($_REQUEST["sender"]) ."',
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