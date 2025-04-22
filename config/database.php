<?php
// Database connection parameters
$db_host = '193.203.184.121';
$db_user = 'u911550082_visafy';
$db_pass = 'Milk@sdk14';
$db_name = 'u911550082_visafy';

// Create database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}