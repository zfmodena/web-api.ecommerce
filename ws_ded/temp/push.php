<?php
/*$to[]="efuDd_m22MU:APA91bHTpKsm_0k4Fgafgg5ffn3tPybcOvp1lPtfnzsU9SNOyQLS5288yX9gLi5nMgyN-O3WqsbpwaYtyPaWBEM2B6Bz9S9YibFm5GVSMSjnQoTJkt2we1NWBelNsXN_6-6p4QvXkNsW";
$to[] = "ciXqNoKUh5A:APA91bE5MJyajwmeg1nFi0FKR6lA_Iahyk-KlJ5wXFi44LxBK97Ox_5Llf64Tu-QKaVmfQo88RiQC5uTjjWe1MRYWfYu_Wt56-_-KpPpDSsBYgBqCVrgsm4bM4etoBEKI234RQofIOH8";
$to[] = "ee1C75SnF9U:APA91bH8QJegSYoTVm1kVWBx73VqRFYfrc4sN3QZEyyVTHJiI0Yqnd5MakvBem3l8ikmUaV40g4E_2ySsaCP2BLOD12qYGvCWzZrgtT6pgttbfkW11BWq2pENkxlMybAaTXbqOX62VfL";
$to[] = "c3ao1mXBick:APA91bFNMONyeDLUQD6FNKm0OC2fgaUvlYoZSinwAHc0M3DmkURXHkAf4ptun29tP35Mly2OO4-Q0WfpfDpRVON14EAqCP5LK5B-7OYFOCiyk0N2Y8wDkYS2TuhoKAMzZxlZRTebQShx";
$to[] = "e-niJtgC-pM:APA91bF1ESSpM1y9mZeT8sKbWMHureFLi3HWvV-TRmtX-pBLqWeEp0FL-2qQZ908PaV_CyGp7aJoWbxNiQP84rDu_xy2PCcQltdTU3FkhTEZmMROrvbcfn45W2MBAx5xlZc8oFEdMn9M";*/
$to[] = "eYHmuyehXGA:APA91bE9t5dZNsrqDdohbwS1jpS-Qp73AW26pgD-TER0-H5H7WuysDI2Gf7lkW0FLN45hLICFAyNijHT4x3PX7HcVyZXWc20ySb_YEg23JxJ4a9yOmtKvmGQXqvRsqAEF0pvKmwjEIvw";
$to[] = "erM1UcBd-DQ:APA91bGEFDZRqdRXC0UHpZKy0O1QEeZUPSTKxYbE-L1HX2aqVPNTSSdRGK_7hxU1nuuR1rCg9fNr9WdkByTX1Ezxz9DHd40pLngRyDxwxT7CJcUUSDc41W2a3pNkMKcVhvcEqV34nuHQ";
$title="Info Ciao MODENA";
$message="Informasi Servis Produk Anda";
sendPush($to,$title,$message);

function sendPush($to,$title,$message)
{
// API access key from Google API's Console
// replace API
//define( 'API_ACCESS_KEY', 'AAAAs-jSu2E:APA91bEuf8cIJuwm776DDsjrsE5P1fXlcFIxpSJAFnA9Z7m_9ShPOwOwdawWRDn3ZRTxgHvHdu13L4QzEv5b8WOz5G1czTAE0_yp_f1WJDdZQ8gScEKcX2NsUn1Wi8aIRmOo7z35GzBA');
define( 'API_ACCESS_KEY', 'AAAA73gp96E:APA91bHoLar-sJrTV0fD_kk4UteUjiX5YU3RuQubaDknv-0UWhFfWPZfiJIerosEFxFZ1pw4NlB7Q-fpA0zkOWFPhX3pAHEXnam0hUIrvPfReMfp4yTLWsouMRMUqIgUvKXPeXmGApTU');
$registrationIds = $to;
$msg = array
(
'message' => $message,
'title' => $title,
'vibrate' => 1,
'sound' => 1,
"coldstart"=>1,
"foreground"=>1,
"dismissed"=>1,
"priority"=>"high",
"path" => "index_detail.html|konsumen_id=1379!membersproductid=4333",
"click_action" =>"index_detail.html?konsumen_id=1379&membersproductid=4333"
/*  url : format URL|KEY=VALUE!KEY=VALUE*/
// you can also add images, additionalData
);
$fields = array
(
'registration_ids' => $registrationIds,
'data' => $msg
);
$headers = array
(
'Authorization: key=' . API_ACCESS_KEY,
'Content-Type: application/json'
);
$ch = curl_init();
curl_setopt( $ch,CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send' );
curl_setopt( $ch,CURLOPT_POST, true );
curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
$result = curl_exec($ch );
curl_close( $ch );
echo $result;
}
?>