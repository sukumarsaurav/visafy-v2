<?php
session_start();
require_once 'config/database.php';
$pageTitle = "Consultant Profile | Visafy";
include 'includes/header.php';
?>

<style>
    body {
        background-color: #f8f9fa;
    }
    
    .consultant-profile-card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: none;
        background-color: #fff;
        margin-bottom: 20px;
    }
    
    .profile-header {
        display: flex;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #f1f1f1;
    }
    
    .consultant-profile-photo {
        margin-right: 20px;
    }
    
    .consultant-profile-photo img {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #fff;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .consultant-info h1 {
        font-size: 24px;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .rating-experience {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .rating-experience .rating {
        display: flex;
        align-items: center;
        color: #6c757d;
        margin-right: 15px;
    }
    
    .rating-experience .rating .stars {
        color: #ffc107;
        margin-right: 5px;
    }
    
    .license-number {
        font-size: 14px;
        color: #6c757d;
    }
    
    .section-card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: none;
        background-color: #fff;
        margin-bottom: 20px;
        padding: 20px;
    }
    
    .section-title {
        font-size: 18px;
        font-weight: 600;
        margin-bottom: 15px;
        color: #212529;
    }
    
    .specialty-badge, .language-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        margin-right: 8px;
        margin-bottom: 8px;
        font-size: 14px;
        font-weight: 500;
    }
    
    .specialty-badge {
        background-color: #e7f1ff;
        color: #0d6efd;
    }
    
    .language-badge {
        background-color: #f1f1f1;
        color: #495057;
    }
    
    .review-item {
        border-bottom: 1px solid #f1f1f1;
        padding-bottom: 15px;
        margin-bottom: 15px;
    }
    
    .review-item:last-child {
        border-bottom: none;
        margin-bottom: 0;
    }
    
    .review-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
    }
    
    .reviewer-name {
        font-weight: 600;
    }
    
    .review-date {
        font-size: 14px;
        color: #6c757d;
    }
    
    .review-stars {
        color: #ffc107;
    }
    
    .review-text {
        font-size: 14px;
        color: #495057;
    }
    
    /* Booking Form Styles */
    .booking-card {
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        border: none;
        background-color: #fff;
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .booking-card .card-header {
        font-weight: 600;
        background-color: #0d6efd;
        color: white;
        border-radius: 0;
        padding: 15px 20px;
        border-bottom: none;
        text-align: center;
    }
    
    .booking-card .card-header h2 {
        font-size: 22px;
    }
    
    .booking-card .card-body {
        padding: 25px;
    }
    
    .consultation-type {
        margin-bottom: 20px;
    }
    
    .consultation-option {
        display: flex;
        justify-content: space-between;
        margin-bottom: 12px;
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
        transition: all 0.2s ease;
        cursor: pointer;
    }
    
    .consultation-option:hover {
        background-color: #f8f9fa;
    }
    
    .consultation-option.active {
        background-color: #e7f3ff;
        border-color: #0d6efd;
    }
    
    .consultation-option input[type="radio"] {
        accent-color: #0d6efd;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .consultation-option label {
        font-size: 15px;
        margin-bottom: 0;
        cursor: pointer;
    }
    
    .consultation-price {
        font-weight: 600;
        color: #0d6efd;
    }
    
    .consultation-option.active .consultation-price {
        color: #0d6efd;
        font-weight: 700;
    }
    
    .consultation-fees {
        padding-top: 20px;
        border-top: 1px solid #f1f1f1;
        font-size: 14px;
    }
    
    .fee-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        padding: 8px;
        background-color: #f8f9fa;
        border-radius: 6px;
    }
    
    .book-btn {
        display: inline-block;
        padding: 12px 30px;
        font-weight: 500;
        font-size: 16px;
        border: none;
        background-color: #0d6efd;
        transition: all 0.3s ease;
        border-radius: 30px;
    }
    
    .book-btn:hover {
        background-color: #0b5ed7;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .date-input {
        margin-bottom: 20px;
    }
    
    .date-input input, .time-slots select {
        padding: 10px;
        height: auto;
    }
    
    .message-input {
        resize: none;
        height: 120px;
        padding: 12px;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>

<?php
// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get consultant ID from URL
$consultant_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Debug parameter
if (isset($_GET['debug']) && $_GET['debug'] == 1) {
    echo "Consultant ID: " . $consultant_id . "<br>";
    echo "Connection status: " . ($conn ? "Connected" : "Not connected") . "<br>";
}

// Redirect if no valid ID provided
if ($consultant_id <= 0) {
    header("Location: consultant.php");
    exit();
}

// Fetch consultant details
$stmt = $conn->prepare("SELECT p.*, u.email, u.name FROM professionals p 
                        JOIN users u ON p.user_id = u.id
                        WHERE p.id = ? AND p.is_verified = 1");

if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("i", $consultant_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Consultant not found or not active
    header("Location: consultant.php");
    exit();
}

$consultant = $result->fetch_assoc();

// Get specializations and languages from the comma-separated values in the database
$specializations = !empty($consultant['specializations']) ? explode(',', $consultant['specializations']) : [];
$languages = !empty($consultant['languages']) ? explode(',', $consultant['languages']) : [];

// Fetch reviews
$stmt = $conn->prepare("SELECT r.*, u.name FROM reviews r
                        JOIN users u ON r.user_id = u.id
                        WHERE r.professional_id = ? ORDER BY r.created_at DESC");

if (!$stmt) {
    die("Review query preparation failed: " . $conn->error);
}

$stmt->bind_param("i", $consultant['user_id']);
$stmt->execute();
$reviewsResult = $stmt->get_result();
$reviews = [];
$totalRating = 0;
$reviewCount = 0;

if (!$reviewsResult) {
    echo "Error fetching reviews: " . $stmt->error;
} else {
    while ($row = $reviewsResult->fetch_assoc()) {
        $reviews[] = $row;
        $totalRating += $row['rating'];
        $reviewCount++;
    }
}

$avgRating = $reviewCount > 0 ? round($totalRating / $reviewCount, 1) : 0;

// Get consultation fees
$stmt = $conn->prepare("SELECT consultation_type, fee FROM consultation_fees WHERE professional_id = ?");

if (!$stmt) {
    die("Fees query preparation failed: " . $conn->error);
}

$stmt->bind_param("i", $consultant['user_id']);
$stmt->execute();
$feesResult = $stmt->get_result();
$consultationFees = [];

if (!$feesResult) {
    echo "Error fetching consultation fees: " . $stmt->error;
} else {
    while ($row = $feesResult->fetch_assoc()) {
        $consultationFees[$row['consultation_type']] = $row['fee'];
    }
}

// Format profile image path
$profileImage = !empty($consultant['profile_image']) ? $base . '/' . $consultant['profile_image'] : $base . '/assets/images/logo-Visafy-light.png';

// Make sure consultant name is never null
$consultantName = !empty($consultant['name']) ? $consultant['name'] : 'Consultant';

// Handle booking form submission
$bookingSuccess = false;
$bookingError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_consultation'])) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $bookingError = 'Please login to book a consultation.';
    } else {
        $userId = $_SESSION['user_id'];
        $professionalId = $consultant['user_id']; // Use the professional's user_id
        $date = $_POST['consultation_date'];
        $time = $_POST['consultation_time'];
        $message = $conn->real_escape_string($_POST['consultation_message']);
        $type = $conn->real_escape_string($_POST['consultation_type']);
        
        // Get fee for selected consultation type
        $consultationFee = isset($consultationFees[$type]) ? $consultationFees[$type] : 0;
        
        try {
            // Create a time slot entry first
            $stmt = $conn->prepare("INSERT INTO time_slots (professional_id, date, start_time, end_time, is_video_available, is_phone_available, is_inperson_available, is_booked) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception("Time slot query preparation failed: " . $conn->error);
            }
            
            // Calculate end time (assume 30 minute slots)
            $startTime = $time . ":00";
            $endTime = date('H:i:s', strtotime($startTime . ' + 30 minutes'));
            
            // Set availability flags based on consultation type
            $isVideoAvailable = ($type == 'video') ? 1 : 0;
            $isPhoneAvailable = ($type == 'phone') ? 1 : 0;
            $isInPersonAvailable = ($type == 'inperson') ? 1 : 0;
            $isBooked = 1; // This slot will be marked as booked
            
            // Debug the parameters
            if (isset($_GET['debug']) && $_GET['debug'] == 1) {
                echo "professionalId: $professionalId, date: $date, startTime: $startTime, endTime: $endTime<br>";
                echo "isVideo: $isVideoAvailable, isPhone: $isPhoneAvailable, isInPerson: $isInPersonAvailable, isBooked: $isBooked<br>";
            }
            
            // "i" = integer, "s" = string, 8 parameters total
            $stmt->bind_param("isssiiii", $professionalId, $date, $startTime, $endTime, $isVideoAvailable, $isPhoneAvailable, $isInPersonAvailable, $isBooked);
            
            if ($stmt->execute()) {
                $timeSlotId = $conn->insert_id;
                
                // Now insert the booking
                $stmt = $conn->prepare("INSERT INTO bookings (professional_id, client_id, time_slot_id, consultation_type, status, price, created_at) 
                                        VALUES (?, ?, ?, ?, 'pending', ?, NOW())");
                
                if (!$stmt) {
                    throw new Exception("Booking query preparation failed: " . $conn->error);
                }
                
                $stmt->bind_param("iiisd", $professionalId, $userId, $timeSlotId, $type, $consultationFee);
                
                if ($stmt->execute()) {
                    $bookingSuccess = true;
                } else {
                    throw new Exception("Failed to insert booking: " . $stmt->error);
                }
            } else {
                throw new Exception("Failed to create time slot: " . $stmt->error);
            }
        } catch (Exception $e) {
            $bookingError = $e->getMessage();
            if (isset($_GET['debug']) && $_GET['debug'] == 1) {
                echo "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="container mt-4 mb-5">
    <!-- Profile Header - Full Width -->
    <div class="consultant-profile-card mb-4">
        <div class="profile-header">
            <div class="consultant-profile-photo">
                <img src="<?php echo $profileImage; ?>" alt="<?php echo htmlspecialchars($consultantName); ?>">
            </div>
            <div class="consultant-info">
                <h1><?php echo htmlspecialchars($consultantName); ?></h1>
                <div class="rating-experience">
                    <div class="rating">
                        <div class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo ($i <= round($avgRating)) ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span><?php echo $avgRating; ?> (<?php echo $reviewCount; ?> reviews)</span>
                    </div>
                    <div class="experience">
                        <i class="fas fa-briefcase"></i>
                        <span><?php echo htmlspecialchars($consultant['years_experience']); ?> years experience</span>
                    </div>
                </div>
                <div class="license-number">
                    <strong>License Number:</strong> <?php echo htmlspecialchars($consultant['license_number']); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Consultant Information (Full Width) -->
    <div class="row">
        <div class="col-12">
            <!-- Two-column layout for specialties and languages -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <!-- Specialties Section -->
                    <div class="section-card">
                        <h2 class="section-title">Specialties</h2>
                        <div class="specialties-container">
                            <?php 
                            foreach ($specializations as $spec): 
                                $spec = trim($spec);
                                if (!empty($spec)):
                            ?>
                                <span class="specialty-badge"><?php echo htmlspecialchars($spec); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Languages Section -->
                    <div class="section-card">
                        <h2 class="section-title">Languages</h2>
                        <div class="languages-container">
                            <?php 
                            foreach ($languages as $lang): 
                                $lang = trim($lang);
                                if (!empty($lang)):
                            ?>
                                <span class="language-badge"><?php echo htmlspecialchars($lang); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- About Section -->
            <div class="section-card mb-4">
                <h2 class="section-title">About</h2>
                <div class="about-content">
                    <?php echo nl2br(htmlspecialchars($consultant['bio'])); ?>
                </div>
            </div>
            
            <!-- Reviews Section -->
            <div class="section-card mb-4">
                <h2 class="section-title">Reviews (<?php echo $reviewCount; ?>)</h2>
                <?php if (empty($reviews)): ?>
                    <p>No reviews yet.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div>
                                    <span class="reviewer-name"><?php echo htmlspecialchars(!empty($review['name']) ? $review['name'] : 'Anonymous'); ?></span>
                                    <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                </div>
                                <div class="review-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo ($i <= $review['rating']) ? '' : '-o'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="review-text">
                                <?php echo nl2br(htmlspecialchars(!empty($review['comment']) ? $review['comment'] : '')); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Booking Form (Full Width) -->
            <div class="booking-card">
                <div class="card-header">
                    <h2 class="m-0">Book Consultation</h2>
                </div>
                <div class="card-body">
                    <?php if ($bookingSuccess): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle me-2"></i>Booking Successful!</h5>
                            <p>Your consultation request has been sent. The consultant will contact you soon to confirm the appointment.</p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($bookingError)): ?>
                            <div class="alert alert-danger"><?php echo $bookingError; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="consultation-type">
                                        <label class="fw-bold mb-3">Consultation Type</label>
                                        
                                        <?php if (isset($consultationFees['video'])): ?>
                                        <div class="consultation-option">
                                            <div class="d-flex align-items-center">
                                                <input type="radio" name="consultation_type" id="video_call" value="video" class="me-2" required>
                                                <label for="video_call" class="w-100">Video Call</label>
                                            </div>
                                            <div class="consultation-price">$<?php echo number_format($consultationFees['video'], 2); ?></div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($consultationFees['phone'])): ?>
                                        <div class="consultation-option">
                                            <div class="d-flex align-items-center">
                                                <input type="radio" name="consultation_type" id="phone_call" value="phone" class="me-2" required>
                                                <label for="phone_call" class="w-100">Phone Call</label>
                                            </div>
                                            <div class="consultation-price">$<?php echo number_format($consultationFees['phone'], 2); ?></div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($consultationFees['inperson'])): ?>
                                        <div class="consultation-option">
                                            <div class="d-flex align-items-center">
                                                <input type="radio" name="consultation_type" id="in_person" value="inperson" class="me-2" required>
                                                <label for="in_person" class="w-100">In Person</label>
                                            </div>
                                            <div class="consultation-price">$<?php echo number_format($consultationFees['inperson'], 2); ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="col-lg-8">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="consultation_date" class="fw-bold mb-2">Select Date</label>
                                            <input type="date" class="form-control" id="consultation_date" name="consultation_date" min="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="fw-bold mb-2">Select Time</label>
                                            <select class="form-select" id="consultation_time" name="consultation_time" required>
                                                <option value="">Select a time</option>
                                                <?php
                                                // Generate time slots from 9 AM to 5 PM
                                                for ($hour = 9; $hour <= 17; $hour++) {
                                                    $time = sprintf("%02d:00", $hour);
                                                    echo "<option value=\"$time\">$time</option>";
                                                    
                                                    // Add half-hour slots
                                                    if ($hour < 17) {
                                                        $halfHour = sprintf("%02d:30", $hour);
                                                        echo "<option value=\"$halfHour\">$halfHour</option>";
                                                    }
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-12 mb-3">
                                            <label for="consultation_message" class="fw-bold mb-2">Message</label>
                                            <textarea class="form-control message-input" id="consultation_message" name="consultation_message" placeholder="Briefly describe what you'd like to discuss" required></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <button type="submit" name="book_consultation" class="btn btn-primary book-btn">
                                    Book Consultation
                                </button>
                            </div>
                            
                            <?php if (!empty($consultationFees)): ?>
                            <div class="consultation-fees mt-4">
                                <div class="fw-bold mb-2">Consultation Fees:</div>
                                <div class="row">
                                    <?php foreach ($consultationFees as $type => $fee): ?>
                                    <div class="col-md-4">
                                        <div class="fee-item">
                                            <span><?php echo ucfirst($type); ?>:</span>
                                            <span>$<?php echo number_format($fee, 2); ?></span>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-2 text-muted small">
                                    You will receive payment instructions after the consultant confirms your booking.
                                </div>
                            </div>
                            <?php endif; ?>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Enhance consultation option selection
        const consultationOptions = document.querySelectorAll('.consultation-option');
        const consultationRadios = document.querySelectorAll('input[name="consultation_type"]');
        
        // Add click event to the entire option div
        consultationOptions.forEach(option => {
            option.addEventListener('click', function() {
                // Find the radio input inside this option and select it
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Add active class to the selected option
                consultationOptions.forEach(opt => opt.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Add change event to radio buttons to style the parent element
        consultationRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                consultationOptions.forEach(opt => opt.classList.remove('active'));
                if (this.checked) {
                    this.closest('.consultation-option').classList.add('active');
                }
            });
            
            // Initialize with any pre-selected option
            if (radio.checked) {
                radio.closest('.consultation-option').classList.add('active');
            }
        });
    });
</script> 