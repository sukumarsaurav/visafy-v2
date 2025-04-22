<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get booking ID from URL
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch booking details
$query = "SELECT b.*, t.date, t.start_time, t.end_time, 
          p.id AS professional_id, p.phone, p.bio,
          u.name AS professional_name, u.email AS professional_email,
          (SELECT name FROM users WHERE id = b.client_id) AS client_name
          FROM bookings b
          JOIN time_slots t ON b.time_slot_id = t.id
          JOIN professionals p ON b.professional_id = p.user_id
          JOIN users u ON p.user_id = u.id
          WHERE b.id = ? AND b.client_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header('Location: dashboard/applicant/index.php');
    exit;
}

$page_title = "Booking Confirmation | Visafy";
include('includes/header.php');
?>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <div class="mb-4">
                <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
            </div>
            <h1 class="mb-3">Booking Confirmed!</h1>
            <p class="lead">Your consultation has been successfully booked.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">Booking Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5>Consultation Information</h5>
                                <p>
                                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['date'])); ?><br>
                                    <strong>Time:</strong> <?php echo date('g:i A', strtotime($booking['start_time'])); ?> - <?php echo date('g:i A', strtotime($booking['end_time'])); ?><br>
                                    <strong>Type:</strong> <?php echo ucfirst($booking['consultation_type']); ?> Consultation<br>
                                    <strong>Fee:</strong> C$<?php echo number_format($booking['price'], 2); ?><br>
                                    <strong>Status:</strong> <span class="badge bg-warning"><?php echo ucfirst($booking['status']); ?></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5>Consultant Information</h5>
                                <p>
                                    <strong>Name:</strong> <?php echo htmlspecialchars($booking['professional_name']); ?><br>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($booking['professional_email']); ?><br>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h5 class="alert-heading">What's Next?</h5>
                            <p>Your booking is currently <strong>pending confirmation</strong> from the consultant. Once confirmed, you will receive an email with instructions on how to prepare for your consultation.</p>
                            <p class="mb-0">If you have any questions or need to make changes to your booking, please contact your consultant directly.</p>
                        </div>
                    </div>
                </div>
                
                <div class="text-center">
                    <a href="dashboard/applicant/index.php" class="btn btn-primary">Go to Dashboard</a>
                    <a href="consultant.php" class="btn btn-outline-secondary ms-2">Find More Consultants</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include('includes/footer.php'); ?> 