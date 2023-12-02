<?php

// Ignore harmless error messages
libxml_use_internal_errors(true);

$curl = curl_init();
$userAgent = 'GitHubBot';

// Get robots.txt file from github
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://github.com/robots.txt',
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_USERAGENT => $userAgent,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_RETURNTRANSFER => true
]);

// Analyze and create an array of processed rules for the crawler to follow
$robotsTxtContent = curl_exec($curl);
$robotsTxtContent = str_replace('Allow: /*?tab=achievements&achievement=*','', $robotsTxtContent);
$robotsTxtContent = str_replace("\n",'', $robotsTxtContent);
$robotsTxtContent = str_replace('*',$userAgent, $robotsTxtContent);
$robotRules = explode("Disallow: ", $robotsTxtContent);


// Start crawling from the trending page
$url = 'https://github.com/trending';
curl_setopt_array($curl, [
    CURLOPT_URL => $url,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_USERAGENT => $userAgent,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_RETURNTRANSFER => true
]);

// Get the response and parse it into a DOM object
$response = curl_exec($curl);
$dom = new DOMDocument();
$dom->loadHTML($response);
//echo $response;

// Create a DOMXPath object to query the DOM for all anchor tags
$xpath = new DOMXPath($dom);
$links = $xpath->query('//a');
foreach ($links as $link) {
    $href = $link->getAttribute('href');
    
    if ($href[0] == '/') {
        $href = 'https://github.com' . $href;
    }
    
    echo $href . "\n"; 
}

// // Query for the node containing the weekend title
// $weekendTitleNode = $xpath->query('/html/body/div[2]/main/div/div[3]/section/div/div[1]/div/div[2]/div');
// $weekendTitle = $weekendTitleNode->item(0)->nodeValue;
// echo $weekendTitle . "\n";

// // Query for the node containing the movie titles
// for ($i = 1; $i <= 10; $i++) {
//     $movieTitleNode = $xpath->query("/html/body/div[2]/main/div/div[3]/section/div/div[2]/div/ul/li[$i]/div[2]/div/div/div/a/h3");
//     $movieTitle = $movieTitleNode->item(0)->nodeValue;
//     echo $movieTitle . "\n";
// }
curl_close($curl);

?>