<?php
session_start();
include('admin/includes/db_connection.php');

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: consultant.php');
    exit;
}

// Get form data
$consultation_type = sanitize($_POST['consultation_type']);
$consultation_date = sanitize($_POST['consultation_date']);
$consultation_time = sanitize($_POST['consultation_time']);
$first_name = sanitize($_POST['first_name']);
$last_name = sanitize($_POST['last_name']);
$email = sanitize($_POST['email']);
$phone = sanitize($_POST['phone']);
$immigration_status = sanitize($_POST['immigration_status']);
$service_interest = sanitize($_POST['service_interest']);
$additional_info = sanitize($_POST['additional_info'] ?? '');

// Combine date and time into appointment datetime
$appointment_datetime = date('Y-m-d H:i:s', strtotime("$consultation_date $consultation_time"));

// Calculate appointment price based on consultation type
$payment_amount = 0;
switch ($consultation_type) {
    case 'Video Consultation':
        $payment_amount = 150.00;
        break;
    case 'Phone Consultation':
        $payment_amount = 120.00;
        break;
    case 'In-Person Consultation':
        $payment_amount = 200.00;
        break;
}

// Check if customer already exists
$sql = "SELECT id FROM customers WHERE email = '$email'";
$result = executeQuery($sql);

if ($result && $result->num_rows > 0) {
    // Update existing customer
    $customer = $result->fetch_assoc();
    $customer_id = $customer['id'];
    
    $sql = "UPDATE customers SET 
            first_name = '$first_name',
            last_name = '$last_name',
            phone = '$phone',
            immigration_status = '$immigration_status',
            updated_at = NOW()
            WHERE id = $customer_id";
    executeQuery($sql);
} else {
    // Insert new customer
    $sql = "INSERT INTO customers (first_name, last_name, email, phone, immigration_status)
            VALUES ('$first_name', '$last_name', '$email', '$phone', '$immigration_status')";
    executeQuery($sql);
    $customer_id = $conn->insert_id;
}

// Insert appointment
$sql = "INSERT INTO appointments 
        (consultation_type, appointment_datetime, first_name, last_name, email, phone, 
        immigration_purpose, special_requests, status, payment_status, payment_amount)
        VALUES 
        ('$consultation_type', '$appointment_datetime', '$first_name', '$last_name', '$email', '$phone',
        '$service_interest', '$additional_info', 'pending', 'unpaid', $payment_amount)";

if (executeQuery($sql)) {
    $appointment_id = $conn->insert_id;
    
    // Store appointment ID in session for confirmation page
    $_SESSION['booking_id'] = $appointment_id;
    
    // Redirect to confirmation page
    header("Location: booking_confirmation.php?id=$appointment_id");
    exit;
} else {
    // If there was an error, redirect back with error message
    $_SESSION['booking_error'] = "There was an error processing your booking. Please try again.";
    header('Location: consultant.php');
    exit;
}
?> 