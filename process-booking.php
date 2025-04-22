<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Check if there's a pending booking
if (!isset($_SESSION['pending_booking'])) {
    header('Location: consultant.php');
    exit;
}

$booking = $_SESSION['pending_booking'];
$client_id = $_SESSION['user_id'];
$professional_id = $booking['professional_id'];
$time_slot_id = $booking['time_slot_id'];
$consultation_type = $booking['consultation_type'];
$price = $booking['price'];

// Verify that the time slot is still available
$query = "SELECT id FROM time_slots WHERE id = ? AND is_booked = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $time_slot_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Time slot is no longer available
    $_SESSION['booking_error'] = "Sorry, this time slot is no longer available. Please choose another time.";
    unset($_SESSION['pending_booking']);
    header('Location: consultant-profile.php?id=' . $professional_id);
    exit;
}

// Process the booking
$query = "INSERT INTO bookings (professional_id, client_id, time_slot_id, consultation_type, status, price) 
         VALUES (?, ?, ?, ?, 'pending', ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param('iiiss', $professional_id, $client_id, $time_slot_id, $consultation_type, $price);

if ($stmt->execute()) {
    // Update time slot availability
    $query = "UPDATE time_slots SET is_booked = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $time_slot_id);
    $stmt->execute();
    
    // Get the booking ID
    $booking_id = $conn->insert_id;
    
    // Clear the pending booking from session
    unset($_SESSION['pending_booking']);
    
    // Redirect to booking confirmation
    header('Location: booking-confirmation.php?id=' . $booking_id);
    exit;
} else {
    // Booking failed
    $_SESSION['booking_error'] = "Failed to create booking. Please try again.";
    header('Location: consultant-profile.php?id=' . $professional_id);
    exit;
}
?> 