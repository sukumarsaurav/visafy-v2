<?php
// Debug info for testing
// echo "<pre>Request URI: " . $_SERVER['REQUEST_URI'] . "</pre>";

// Extract the slug from the URL
$request_uri = $_SERVER['REQUEST_URI'];
$path_parts = explode('/', trim($request_uri, '/'));

// If we're accessing a news article
if (count($path_parts) >= 2 && $path_parts[0] === 'immigration-news') {
    $slug = $path_parts[1];
    
    // For debugging
    // echo "<pre>Slug found: " . $slug . "</pre>";
    
    // Include the news detail page
    include('news-detail.php');
    exit;
} else {
    // For debugging
    // echo "<pre>No slug found in: " . print_r($path_parts, true) . "</pre>";
    
    // Otherwise, redirect to the news listing page
    header('Location: resources/immigration-news.php');
    exit;
}
?> 