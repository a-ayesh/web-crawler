<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Crawler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="./style.css" rel="stylesheet" type="text/css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
</head>

<body>
    <h1 class = "col-12 text-center text-nowrap mb-3">👾 Web Crawler 👾</h1>
    <p>TO DO: User input seed url<br>TO DO: Clean up code<br>TO DO: Highlight search results</p>
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
    <button type='submit' id='searchBtn' class='btn btn-outline-secondary'>Submit</button>
    </div>
    </form>
    </div>
    ");

    // Waste away the first element (not a hyperlink)
    $temp = clone $urlQueue;
    $url = $temp->dequeue();
    // makes sure that the form is submitted when the page is loaded
    $str = isset($_POST['stringSearch']) ? $_POST['stringSearch'] : null;
    $str = strtolower($str);

    // Display links containing the search string, if provided
    echo "<div id = 'links_header' class = 'col-8 order-first'>";
    $counter = 0;
    if ($str == null) {
        $counter = $temp->size();
        echo "<h4 class='mb-5 mt-1'>$counter Links found on this page:</h4>";
        // while (!($temp->isEmpty())) {
        //     $url = $temp->dequeue();
        //     echo "<a href=$url>$url</a><br>";
        // }
    } else {
        while (!($temp->isEmpty())) {
            $url = $temp->dequeue();
            if (strpos($url, $str) !== false) {
                $counter++;
            }
        }
        echo "<h4 class='mb-5 mt-1'>$counter Links found on this page matching '$str':</h4>";
        // while (!($temp->isEmpty())) {
        //     $url = $temp->dequeue();
        //     if (strpos($url, $str) !== false) {
        //         echo "<a href=$url>$url</a><br>";
        //     }
        // }
    }
    echo "</div></div>"; //</div></div>";
    echo "<div class='row'>";
    echo "<div id='links' class='col-12'>";
    echo "<ul class='h6'>";
    $temp = clone $urlQueue;
    $temp->dequeue();
    if ($str == null) {
        while (!($temp->isEmpty())) {
            echo "<li>";
            $url = $temp->dequeue();
            echo "<a href=$url>$url</a>";
            echo "</li>";
        }
    } else {
        while (!($temp->isEmpty())) {
            $url = $temp->dequeue();
            if (strpos($url, $str) !== false) {
                echo "<li>";
                echo "<a href=$url>$url</a>";
                echo "</li>";
            }
        }
    }
    echo "</ul></div></div></div>";

    curl_close($curl);
    ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
</body>

</html>