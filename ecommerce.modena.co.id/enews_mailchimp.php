<?php


function syncMailchimp($data) {
    $apiKey = '8bcbc5794d8e04d1fca039fd1d18c9d5-us10';
    $listId = '27f3b303a9'; //Modena Customer 1

    $memberId = md5(strtolower($data['email']));
    $dataCenter = substr($apiKey,strpos($apiKey,'-')+1);
    $url = 'https://' . $dataCenter . '.api.mailchimp.com/3.0/lists/' . $listId . '/members/' . $memberId;

    $json = json_encode(array(
        'email_address' => $data['email'],
        'status'        => $data['status'], // "subscribed","unsubscribed","cleaned","pending"
     'merge_fields'  => array(
            'FNAME'     => $data['firstname']/*,   
            'LNAME'     => $data['lastname']*/
        )
    ));

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_USERPWD, 'user:' . $apiKey);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);                                                                                                                 

    $result = curl_exec($ch);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $httpCode;
}

syncMailchimp($data);

?>