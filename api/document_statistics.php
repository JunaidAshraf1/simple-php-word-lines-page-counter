<?php


$curl = curl_init();

$documentId = "ecaf9d54f470fd1213201590_57";
$public_key = "e44a261d-02f0-4b60-a644-e2bc2e974813";
$private_key = "2_7J64eVfqRT8FV9SzcVwUJvAyZ";

curl_setopt_array($curl, array(
CURLOPT_URL => "https://smartcat.com/api/integration/v1/document/statistics?documentId=$documentId",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => "",
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 0,
CURLOPT_FOLLOWLOCATION => true,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => "GET",
CURLOPT_HTTPHEADER => array(
"Content-Type: application/json",
"Authorization: Basic ".base64_encode($public_key.":".$private_key)
),
));
$response = curl_exec($curl);
curl_close($curl);
echo $response;
$decoded_response = json_decode($response,true);
//print_r($decoded_response);
//print_r($decoded_response[0]["documents"]);



?>