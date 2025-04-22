<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Check if user is a professional
if ($_SESSION['user_type'] != 'professional') {
    header("Location: ../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the professional has already completed their profile
$stmt = $conn->prepare("SELECT profile_completed FROM professionals WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// If professional record doesn't exist or profile not completed, redirect to profile page
if ($result->num_rows == 0 || $result->fetch_assoc()['profile_completed'] == 0) {
    header("Location: profile.php?msg=complete_profile");
    exit();
}

// Otherwise, return to the referring page or dashboard
if (isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: index.php");
}
exit();
?> 