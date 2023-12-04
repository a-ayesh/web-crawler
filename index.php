<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>

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

    // Load data from the database
    $data = json_decode(file_get_contents('data.json'), true);

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

    // Pagination
    $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $links_per_page = 10;
    $start_index = ($current_page - 1) * $links_per_page;

    // Get the seed URL from the form. if not provided, use Google as the default
    $_SESSION['url'] = isset($_SESSION['url']) ? $_SESSION['url'] : 'https://www.google.com/';
    $_SESSION['url'] = (isset($_POST['seedUrl']) && $_POST['seedUrl'] != '') ? $_POST['seedUrl'] : $_SESSION['url'];
    $url = $_SESSION['url'];
    $userAgent = 'CrawlBot';
    $urlQueue = new Queue();
    $robotsTxtContent;
    $response;
    $curl = curl_init();

    echo ("
    <script>
        document.getElementById('seedUrl').placeholder = '$url';
    </script>
    ");

    // Check if the URL has already been crawled
    if ($data == null) {
        $exists = false;
    } else {
        $exists = array_key_exists($url, $data);
    }

    // If not already crawled, get robots.txt file from the respective website, else get it from the database
    if (!$exists) {
        $parts = explode('/', $url);
        if (count($parts) >= 3) {
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
    } else {
        $robotsTxtContent = $data[$url]['robot'];
    }

    $robotRules = explode("Disallow: ", $robotsTxtContent);

    // Basic check to see if the URL is allowed to be crawled
    foreach ($robotRules as $rule) {
        if (strpos($url, $rule) !== false) {
            echo "<h2 class = 'col-12 text-center mb-3'>Crawling not allowed on this URL</h2>";
            exit();
        }
    }

    // If not already crawled, start crawling from the provided page, else get the content from the database
    if (!$exists) {
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_USERAGENT => $userAgent,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true
        ]);
        $response = curl_exec($curl);
    } else {
        $response = $data[$url]['content'];
    }

    // Parse the HTML content
    $dom = new DOMDocument();
    if (empty($response)) {
        echo "<h2 class = 'col-12 text-center mb-3'>Error: HTML content is empty</h2>";
        exit();
    }
    $dom->loadHTML($response);

    // Store the crawled URL in the database if not already present
    if (!$exists) {
        $urlToStore = $url;
        $contentToStore = $response;
        $rulesToStore = $robotsTxtContent;

        $data[$urlToStore] = [
            'url' => $urlToStore,
            'robot' => $robotsTxtContent,
            'content' => $contentToStore
        ];
        file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
    }

    // Create a DOMXPath object to query the DOM for all anchor tags
    $xpath = new DOMXPath($dom);
    $links = $xpath->query('//a');
    foreach ($links as $link) {
        $href = $link->getAttribute('href');

        if (str_starts_with($href, '/')) {
            $parts = explode('/', $url);
            $hostUrl = $parts[0] . '//' . $parts[2];
            $href = "$hostUrl" . $href;
        }

        $urlQueue->enqueue($href);
    }

    // Displaying meta description and title
    $metaDescriptionElement = $xpath->query('//meta[@name="description"]/@content')->item(0);
    $metaDescription = ($metaDescriptionElement) ? $metaDescriptionElement->nodeValue : 'Meta description not found';
    echo "<h2 class = 'col-12 text-center mb-3'>$metaDescription<h2>";
    echo "<hr class='separator'>";
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
                        <input type='text' id='stringSearch' name='stringSearch' class='form-control' placeholder='Filter'>
                        <button type='submit' class='btn btn-outline-secondary searchBtn'>
                            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-search' viewBox='0 0 16 16'>
                                <path d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
    ");

    // Waste away the first element (not a hyperlink)
    $url = $urlQueue->dequeue();
    $linkCount = 0;
    $output = '';

    // Check if a search string was provided
    $str = isset($_POST['stringSearch']) ? $_POST['stringSearch'] : null;
    $str = strtolower($str);
    $displayCounter = 0; // Keeps track of the number of links displayed
    
    // Display links containing the search string, if provided, else display all links
    echo "<div id = 'linksHeader' class = 'col-8 order-first'>";
    if ($str == null) {
        $linkCount = $urlQueue->size();
        while (!($urlQueue->isEmpty())) {
            // get the next URL from the queue and crawl it, then display its contents
            $url = $urlQueue->dequeue();
            $exists = array_key_exists($url, $data);
            if (!$exists) {
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_USERAGENT => $userAgent,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_RETURNTRANSFER => true
                ]);
                $response = curl_exec($curl);
            } else {
                $response = $data[$url]['content'];
            }
            // Parse the HTML content
            $dom = new DOMDocument();
            if (empty($response)) {
                $response = "<h2 class = 'col-12 text-center mb-3'>Error: HTML content is empty</h2>";
            }
            $dom->loadHTML($response);
            // store the crawled URL in the database if not already present
            if (!$exists) {
                $urlToStore = $url;
                $contentToStore = $response;
                $rulesToStore = $robotsTxtContent;

                $data[$urlToStore] = [
                    'url' => $urlToStore,
                    'robot' => $robotsTxtContent,
                    'content' => $contentToStore
                ];
                file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
            }
            $xpath = new DOMXPath($dom);
            $titleElement = $xpath->query('//title')->item(0);
            $title = ($titleElement) ? $titleElement->nodeValue : 'Crawling not allowed on this URL or HTML content is empty';
            $para = '';
            if (strpos($title, 'Moved')) {
                $title = 'Crawling not allowed on this URL or HTML content is empty';
            }
            if (!strpos($title, 'Crawling not allowed on this URL or HTML content is empty')) {
                $paraElement = $xpath->query('//p')->item(0);
                $para = ($paraElement) ? $paraElement->nodeValue : 'No content found';
            }
            if ($displayCounter >= $start_index && $displayCounter < ($start_index + $links_per_page)) {
                $output = ($output . "<li'>
                                        <h5>$title</h5>
                                        <form action='' method='post' class='mb-3'>
                                            <button type='submit' name='$url' value='$url'>$url</button>
                                        </form>
                                        <h6>$para</h6>
                                    </li>
                                    <hr>"
                );
            }
            $displayCounter++;
        }
        echo "<h4 class='mb-5 mt-1'>$linkCount Links found on this page:</h4>";
    } else {
        while (!($urlQueue->isEmpty())) {
            // get the next URL from the queue and crawl it, then display its contents
            $url = $urlQueue->dequeue();
            $exists = array_key_exists($url, $data);
            if (!$exists) {
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_CUSTOMREQUEST => 'GET',
                    CURLOPT_USERAGENT => $userAgent,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_RETURNTRANSFER => true
                ]);
                $response = curl_exec($curl);
            } else {
                $response = $data[$url]['content'];
            }
            // Parse the HTML content
            $dom = new DOMDocument();
            if (empty($response)) {
                $response = "<h2 class = 'col-12 text-center mb-3'>Error: HTML content is empty</h2>";
            }
            $dom->loadHTML($response);
            // store the crawled URL in the database if not already present
            if (!$exists) {
                $urlToStore = $url;
                $contentToStore = $response;
                $rulesToStore = $robotsTxtContent;

                $data[$urlToStore] = [
                    'url' => $urlToStore,
                    'robot' => $robotsTxtContent,
                    'content' => $contentToStore
                ];
                file_put_contents('data.json', json_encode($data, JSON_PRETTY_PRINT));
            }
            $xpath = new DOMXPath($dom);
            $titleElement = $xpath->query('//title')->item(0);
            $title = ($titleElement) ? $titleElement->nodeValue : 'Crawling not allowed on this URL or HTML content is empty';
            $para = '';
            if (strpos($title, 'Moved')) {
                $title = 'Crawling not allowed on this URL or HTML content is empty';
            }
            if (!strpos($title, 'Crawling not allowed on this URL or HTML content is empty')) {
                $paraElement = $xpath->query('//p');
                if ($paraElement) {
                    foreach ($paraElement as $p) {
                        $para = $p->nodeValue;
                        if (strpos($para, $str) !== false) {
                            $linkCount++;
                            if ($displayCounter >= $start_index && $displayCounter < ($start_index + $links_per_page)) {
                                $output = ($output . "<li'>
                                                        <h5>$title</h5>
                                                        <form action='' method='post' class='mb-3'>
                                                            <button type='submit' name='$url' value='$url'>$url</button>
                                                        </form>
                                                        <h6>$para</h6>
                                                    </li>
                                                    <hr>"
                                );
                            }
                        }
                    }
                } else {
                    $para = "No content found";
                    if ($displayCounter >= $start_index && $displayCounter < ($start_index + $links_per_page)) {
                        $output = ($output . "<li'>
                                                <h5>$title</h5>
                                                <form action='' method='post' class='mb-3'>
                                                    <button type='submit' name='$url' value='$url'>$url</button>
                                                </form>
                                                <h6>$para</h6>
                                            </li>
                                            <hr>"
                        );
                    }
                }
                $displayCounter++;
                if (strpos($url, $str) !== false) {
                    $linkCount++;
                    $output = $output . "<li><form action='index.php' method='post'><button type='submit' name='$url' value='$url'>$url</button></form></li>";
                }
            }
        }
        echo "<h4 class='mb-5 mt-1'>$linkCount Links found on this page matching '$str':</h4>";
    }
    echo ("
        </div>
    </div>
    <div class='row'>
        <div id='links' class='col-12'>
            <ul class='h6' id='anchor-buttons'>
                $output
            </ul>
        </div>
    </div>
    <div class='row'>
        <div class='pagination col-6'>");

    $total_pages = ceil($linkCount / $links_per_page);
    $end_index = min($start_index + $links_per_page, $linkCount);

    for ($i = 1; $i <= $total_pages; $i++) {
        $active_class = ($i == $current_page) ? 'active' : '';
        echo "<a class='page-link $active_class' href='?page=$i'>$i</a>";
    }
    echo (
        "</div>
        <div class='info col-6 text-end'>Displaying links " . $start_index + 1 . " to $end_index out of $linkCount</div>
    </div></div>"
    );

    // Crawl the next URL if clicked.
    if (!isset($_POST['stringSearch'])) {
        $nextUrl = array_values($_POST);
        foreach ($nextUrl as $temp) {
            $_SESSION['url'] = $temp;
            echo ("<script>location.href = location.href;</script>");
        }
    }

    echo "<hr class='separator'>";

    curl_close($curl);
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
</body>

</html>