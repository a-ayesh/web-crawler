<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Crawler</title>
</head>

<body>
    <h1>GitHub Crawler</h1>
    <?php
    // Ignore harmless error messages
    libxml_use_internal_errors(true);

    // Defining queue data structure
    class Queue
    {
        private $queue;

        public function __construct()
        {
            $this->queue = [];
        }

        public function enqueue($item)
        {
            array_push($this->queue, $item);
        }

        public function dequeue()
        {
            if (!$this->isEmpty()) {
                return array_shift($this->queue);
            }
            return null; // or throw an exception for an empty queue
        }

        public function peek()
        {
            if (!$this->isEmpty()) {
                return $this->queue[0];
            }
            return null; // or throw an exception for an empty queue
        }

        public function isEmpty()
        {
            return empty($this->queue);
        }

        public function size()
        {
            return count($this->queue);
        }
    }

    $curl = curl_init();
    $userAgent = 'GitHubBot';
    $urlQueue = new Queue();

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
    $robotsTxtContent = str_replace('Allow: /*?tab=achievements&achievement=*', '', $robotsTxtContent);
    $robotsTxtContent = str_replace("\n", '', $robotsTxtContent);
    $robotsTxtContent = str_replace('*', $userAgent, $robotsTxtContent);
    $robotRules = explode("Disallow: ", $robotsTxtContent);


    // Start crawling from the trending page
    $urlQueue->enqueue('https://github.com/trending');
    curl_setopt_array($curl, [
        CURLOPT_URL => $urlQueue->dequeue(),
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => true
    ]);

    // Get the response and parse it into a DOM object
    $response = curl_exec($curl);
    $dom = new DOMDocument();
    $dom->loadHTML($response);

    // Create a DOMXPath object to query the DOM for all anchor tags
    $xpath = new DOMXPath($dom);
    $links = $xpath->query('//a');
    foreach ($links as $link) {
        $href = $link->getAttribute('href');

        if (str_starts_with($href, '/')) {
            $href = 'https://github.com' . $href;
        }

        $urlQueue->enqueue($href);
    }

    $metaDescriptionElement = $xpath->query('//meta[@name="description"]/@content')->item(0);
    $metaDescription = ($metaDescriptionElement) ? $metaDescriptionElement->nodeValue : 'Meta description not found';
    echo "<h2>$metaDescription<h2>";

    $titleElement = $xpath->query('//title')->item(0);
    $title = ($titleElement) ? $titleElement->nodeValue : 'Title not found';
    echo "<h3>$title<h3>";

    echo "<h4>Links found on this page:</h4>";

    $temp = clone $urlQueue;
    while (!($temp->isEmpty())) {
        $url = $temp->dequeue();
        echo "<a href=$url>$url</a><br>";
    }

    curl_close($curl);
    ?>
</body>

</html>