CREATE DATABASE IF NOT EXISTS web_crawler;

USE web_crawler;

CREATE TABLE IF NOT EXISTS crawled_urls (
    url VARCHAR(255) PRIMARY KEY,
    robot TEXT,
    content MEDIUMTEXT NOT NULL
);