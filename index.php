<?php

// Ignore harmless error messages
libxml_use_internal_errors(true);

$curl = curl_init();

// Set cURL options
$requestType = 'GET';
$url = 'https://www.imdb.com/chart/boxoffice/';
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_CUSTOMREQUEST => $requestType,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($curl);
curl_close($curl);

$dom = new DOMDocument();
$dom->loadHTML($response);
$xpath = new DOMXPath($dom);

// Query for the node containing the weekend title
$weekendTitleNode = $xpath->query('/html/body/div[2]/main/div/div[3]/section/div/div[1]/div/div[2]/div');
$firstItem = $weekendTitleNode->item(0);

// Must equal 'Weekend of November 24-26'
echo $firstItem->nodeValue;

?>