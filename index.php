<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Crawler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="./style.css" rel="stylesheet" type="text/css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>

<body>
    <h1 class="col-12 text-center text-nowrap mb-4">👾 Web Crawler 👾</h1>
    <form action="index.php" method="post" class="col-6 mx-auto">
        <div class="input-group mb-3">
            <input type="text" id="seedUrl" name="seedUrl" class="form-control" placeholder="Enter seed URL">
            <button type="submit" id="crawlBtn" class="btn btn-outline-secondary searchBtn">Crawl</button>
        </div>
    </form>

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
            return null;
        }

        public function peek()
        {
            if (!$this->isEmpty()) {
                return $this->queue[0];
            }
            return null;
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

    // Get the seed URL from the form. if not provided, use Google as the default
    $url = (isset($_POST['seedUrl']) && $_POST['seedUrl'] != '') ? $_POST['seedUrl'] : 'https://www.google.com/';
    $userAgent = 'CrawlBot';
    $urlQueue = new Queue();
    $curl = curl_init();

    // Get robots.txt file from the respective website
    $parts = explode('/', $url);
    if (count($parts) >= 4) {
        $hostUrl = $parts[0] . '//' . $parts[2];
    } else {
        echo "<h2 class = 'col-12 text-center mb-3'>Enter a valid URL</h2>";
        exit();
    }
    curl_setopt_array($curl, [
        CURLOPT_URL => "$hostUrl/robots.txt",
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_USERAGENT => $userAgent,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_RETURNTRANSFER => true
    ]);

    // Analyze and create an array of processed rules for the crawler to follow
    $robotsTxtContent = curl_exec($curl);
    $robotsTxtContent = str_replace('Allow: ', 'Disallow: ', $robotsTxtContent);
    $robotsTxtContent = str_replace("\n", '', $robotsTxtContent);
    $robotsTxtContent = str_replace('*', $userAgent, $robotsTxtContent);
    $robotRules = explode("Disallow: ", $robotsTxtContent);

    // Basic check to see if the URL is allowed to be crawled
    foreach ($robotRules as $rule) {
        if (strpos($url, $rule) !== false) {
            echo "<h2 class = 'col-12 text-center mb-3'>Crawling not allowed on this URL</h2>";
            exit();
        }
    }

    // Start crawling from the provided page
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

    // Create a DOMXPath object to query the DOM for all anchor tags
    $xpath = new DOMXPath($dom);
    $links = $xpath->query('//a');
    foreach ($links as $link) {
        $href = $link->getAttribute('href');

        if (str_starts_with($href, '/')) {
            $href = "$hostUrl" . $href;
        }

        $urlQueue->enqueue($href);
    }

    // Displaying meta description and title
    $metaDescriptionElement = $xpath->query('//meta[@name="description"]/@content')->item(0);
    $metaDescription = ($metaDescriptionElement) ? $metaDescriptionElement->nodeValue : 'Meta description not found';
    echo "<h2 class = 'col-12 text-center mb-3'>$metaDescription<h2>";
    echo "<hr>";
    $titleElement = $xpath->query('//title')->item(0);
    $title = ($titleElement) ? $titleElement->nodeValue : 'Title not found';
    echo "<h3 class = 'col-12 text-center mb-3'>$title<h3>";

    // Creating string query form
    echo ("
    <div class='container'>
        <div class='row'>
            <div id='search' class='col-4 text-end order-last'>
                <form action='index.php' method='post'>
                    <div class='input-group'>
                        <input type='text' id='stringSearch' name='stringSearch' class='form-control' placeholder='Search'>
                        <button type='submit' class='btn btn-outline-secondary searchBtn'>
                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-search' viewBox='0 0 16 16'>
                                <path d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
    ");

    // Clone urlQueue for outputting and waste away the first element (not a hyperlink)
    $temp = clone $urlQueue;
    $url = $temp->dequeue();
    $linkCount = 0;
    $output = '';

    // Check if a search string was provided
    $str = isset($_POST['stringSearch']) ? $_POST['stringSearch'] : null;
    $str = strtolower($str);

    // Display links containing the search string, if provided
    echo "<div id = 'linksHeader' class = 'col-8 order-first'>";
    if ($str == null) {
        $linkCount = $temp->size();
        while (!($temp->isEmpty())) {
            $url = $temp->dequeue();
            $output = $output . "<li><a href=$url>$url</a></li>";
        }
        echo "<h4 class='mb-5 mt-1'>$linkCount Links found on this page:</h4>";
    } else {
        while (!($temp->isEmpty())) {
            $url = $temp->dequeue();
            if (strpos($url, $str) !== false) {
                $linkCount++;
                $output = $output . "<li><a href=$url>$url</a></li>";
            }
        }
        echo "<h4 class='mb-5 mt-1'>$linkCount Links found on this page matching '$str':</h4>";
    }
    echo ("
        </div>
    </div>
    <div class='row'>
        <div id='links' class='col-12'>
            <ul class='h6'>
                $output
            </ul>
        </div>
    </div>
    </div>");

    curl_close($curl);
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
</body>

</html>