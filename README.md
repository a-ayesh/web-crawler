# Web Crawler with PHP

## Overview

This PHP script implements a basic web crawler that allows you to start crawling from a seed URL, respecting robots.txt rules, and storing crawled data in a JSON file for future reference. The script uses cURL for making HTTP requests, DOMDocument and DOMXPath for parsing HTML, and Bootstrap for styling the user interface.

- A <b>URL Queue</b> is implemented to keep track of processed URLs.
- Provided URLs are <b>crawled</b> in the order received.
- Provided URLs are <b>parsed</b> in the order received.
- URLs are <b>extracted</b> from the base URL.
- <b>Depth Limit</b> is set to just 1, as the user can search the next depth-level by clicking a URL.
- Relevant information is <b>outputted</b> to the webpage.
- Filter can be used to <b>search</b> or <b>restrict</b> specific URLs.
- <b>Robots.txt</b> are adhered to.
- <b>Errors are handled</b> gracefully.
- <b>Persistent storage</b> implemented via .json file
 
## Features

### Web Crawler Functionality:
        Retrieve a seed URL from a form and start crawling from that point.
        Use cURL to fetch web pages, DOMDocument to parse HTML, and DOMXPath to query the parsed HTML.
        Follow links on the page, enqueue them, and continue crawling.

### Robots.txt Handling:
        Respect rules specified in the robots.txt file.
        Retrieve and process the robots.txt file for each host before crawling.

### Data Storage:
        Store crawled data, including URLs, robots.txt content, and HTML content, in a JSON file (data.json).
        Avoid recrawling previously visited URLs.

### Pagination and Display:
        Implement pagination to display a limited number of links per page.
        Provide a search functionality to filter links based on a search string.
        Display link titles, URLs, and excerpts from the content.

## Implementation and Concepts

### Queue Data Structure:
        Use a simple PHP class (Queue) to implement a queue data structure for managing URLs during crawling.

### Session Handling:
        Use PHP sessions to store and maintain the current seed URL across requests.

### Error Handling:
        Set libxml_use_internal_errors(true) to ignore harmless error messages during XML parsing.

### Bootstrap Integration:
        Integrate the Bootstrap framework for styling the HTML interface.

## Technologies Used

### PHP: 
    Server-side scripting language used for web development.
### cURL: 
    Library for making HTTP requests.
### DOMDocument and DOMXPath: 
    PHP classes for parsing and querying XML/HTML.
### Bootstrap: 
    Front-end framework for styling and layout.

## Setting It Up

### Dependencies:
        Ensure that you have PHP installed on your server.
        Make sure cURL is enabled in your PHP configuration.

### File Structure:
        Ensure that the data.json file is writable by the web server.
        Include the Bootstrap library in your project or link it from a CDN.

### Execution:
        Place the PHP file on your web server.
        Access the file through a web browser.
        Enter a seed URL in the form and start crawling.

### Additional Configuration:
        Adjust file paths and permissions as needed.
        Customize the UI or styling based on your preferences.
