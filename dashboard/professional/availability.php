<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is a professional
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || $_SESSION['user_type'] != 'professional') {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get professional data
$stmt = $conn->prepare("SELECT id FROM professionals WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    $error_message = "Please complete your professional profile first";
    $professional_id = 0;
} else {
    $professional_data = $result->fetch_assoc();
    $professional_id = $professional_data['id'];
}
$stmt->close();

// Process form submission for availability
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_availability'])) {
    $date = $_POST['date'] ?? '';
    $is_video = isset($_POST['is_video']) ? 1 : 0;
    $is_phone = isset($_POST['is_phone']) ? 1 : 0;
    $is_inperson = isset($_POST['is_inperson']) ? 1 : 0;
    
    if (empty($date)) {
        $error_message = "Please select a date";
    } else {
        // Check if date already exists
        $stmt = $conn->prepare("SELECT id FROM consultant_availability WHERE professional_id = ? AND date = ?");
        $stmt->bind_param("is", $professional_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE consultant_availability SET 
                is_video_available = ?, 
                is_phone_available = ?, 
                is_inperson_available = ? 
                WHERE professional_id = ? AND date = ?");
            $stmt->bind_param("iiiss", $is_video, $is_phone, $is_inperson, $professional_id, $date);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO consultant_availability 
                (professional_id, date, is_video_available, is_phone_available, is_inperson_available) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiii", $professional_id, $date, $is_video, $is_phone, $is_inperson);
        }
        
        if ($stmt->execute()) {
            $success_message = "Availability updated successfully!";
        } else {
            $error_message = "Error updating availability: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Process form submission for time slots
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_timeslot'])) {
    $slot_date = $_POST['slot_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $slot_is_video = isset($_POST['slot_is_video']) ? 1 : 0;
    $slot_is_phone = isset($_POST['slot_is_phone']) ? 1 : 0;
    $slot_is_inperson = isset($_POST['slot_is_inperson']) ? 1 : 0;
    
    if (empty($slot_date) || empty($start_time) || empty($end_time)) {
        $error_message = "Please fill in all required time slot fields";
    } else {
        // Check if slot already exists
        $stmt = $conn->prepare("SELECT id FROM time_slots WHERE professional_id = ? AND date = ? AND start_time = ?");
        $stmt->bind_param("iss", $professional_id, $slot_date, $start_time);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE time_slots SET 
                end_time = ?,
                is_video_available = ?, 
                is_phone_available = ?, 
                is_inperson_available = ? 
                WHERE professional_id = ? AND date = ? AND start_time = ?");
            $stmt->bind_param("siiiiss", $end_time, $slot_is_video, $slot_is_phone, $slot_is_inperson, $professional_id, $slot_date, $start_time);
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO time_slots 
                (professional_id, date, start_time, end_time, is_video_available, is_phone_available, is_inperson_available) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiiii", $professional_id, $slot_date, $start_time, $end_time, $slot_is_video, $slot_is_phone, $slot_is_inperson);
        }
        
        if ($stmt->execute()) {
            $success_message = "Time slot added successfully!";
        } else {
            $error_message = "Error adding time slot: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Delete time slot
if (isset($_GET['delete_slot']) && is_numeric($_GET['delete_slot'])) {
    $slot_id = (int)$_GET['delete_slot'];
    
    // Verify this slot belongs to the professional
    $stmt = $conn->prepare("SELECT id FROM time_slots WHERE id = ? AND professional_id = ?");
    $stmt->bind_param("ii", $slot_id, $professional_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM time_slots WHERE id = ?");
        $stmt->bind_param("i", $slot_id);
        if ($stmt->execute()) {
            $success_message = "Time slot deleted successfully!";
        } else {
            $error_message = "Error deleting time slot";
        }
    }
    $stmt->close();
}

// Fetch upcoming availability
$availability = [];
$stmt = $conn->prepare("SELECT * FROM consultant_availability 
                      WHERE professional_id = ? AND date >= CURDATE() 
                      ORDER BY date ASC");
$stmt->bind_param("i", $professional_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $availability[] = $row;
}
$stmt->close();

// Fetch upcoming time slots
$time_slots = [];
$stmt = $conn->prepare("SELECT ts.*, b.id as booking_id, u.name as client_name 
                      FROM time_slots ts 
                      LEFT JOIN bookings b ON ts.id = b.time_slot_id 
                      LEFT JOIN users u ON b.client_id = u.id
                      WHERE ts.professional_id = ? AND ts.date >= CURDATE() 
                      ORDER BY ts.date ASC, ts.start_time ASC");
$stmt->bind_param("i", $professional_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $time_slots[] = $row;
}
$stmt->close();

// Page title
$page_title = "Manage Availability | Visafy";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../../assets/css/consultant.css">

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Availability</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($professional_id == 0): ?>
                <div class="alert alert-warning">
                    Please <a href="profile.php">complete your professional profile</a> before setting your availability.
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Set Daily Availability</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <p class="form-label">Consultation Types Available</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_video" name="is_video" checked>
                                            <label class="form-check-label" for="is_video">
                                                <i class="bi bi-camera-video"></i> Video Consultation
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_phone" name="is_phone" checked>
                                            <label class="form-check-label" for="is_phone">
                                                <i class="bi bi-telephone"></i> Phone Consultation
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_inperson" name="is_inperson">
                                            <label class="form-check-label" for="is_inperson">
                                                <i class="bi bi-person-badge"></i> In-Person Consultation
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="submit_availability" class="btn btn-primary">Save Availability</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Add Time Slot</h5>
                            </div>
                            <div class="card-body">
                                <form action="" method="POST">
                                    <div class="mb-3">
                                        <label for="slot_date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="slot_date" name="slot_date" min="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <label for="start_time" class="form-label">Start Time</label>
                                            <input type="time" class="form-control" id="start_time" name="start_time" required>
                                        </div>
                                        <div class="col-6">
                                            <label for="end_time" class="form-label">End Time</label>
                                            <input type="time" class="form-control" id="end_time" name="end_time" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <p class="form-label">Available Consultation Types</p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="slot_is_video" name="slot_is_video" checked>
                                            <label class="form-check-label" for="slot_is_video">
                                                <i class="bi bi-camera-video"></i> Video Consultation
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="slot_is_phone" name="slot_is_phone" checked>
                                            <label class="form-check-label" for="slot_is_phone">
                                                <i class="bi bi-telephone"></i> Phone Consultation
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="slot_is_inperson" name="slot_is_inperson">
                                            <label class="form-check-label" for="slot_is_inperson">
                                                <i class="bi bi-person-badge"></i> In-Person Consultation
                                            </label>
                                        </div>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="submit_timeslot" class="btn btn-primary">Add Time Slot</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Availability Calendar -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Your Availability</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($availability)): ?>
                                    <p class="text-center text-muted">You haven't set any availability yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Video</th>
                                                    <th>Phone</th>
                                                    <th>In-Person</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($availability as $day): ?>
                                                    <tr>
                                                        <td><?php echo date('F j, Y (l)', strtotime($day['date'])); ?></td>
                                                        <td>
                                                            <?php if ($day['is_video_available']): ?>
                                                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Available</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><i class="bi bi-x-lg"></i> Unavailable</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($day['is_phone_available']): ?>
                                                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Available</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><i class="bi bi-x-lg"></i> Unavailable</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($day['is_inperson_available']): ?>
                                                                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Available</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary"><i class="bi bi-x-lg"></i> Unavailable</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Time Slots -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Your Time Slots</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($time_slots)): ?>
                                    <p class="text-center text-muted">You haven't created any time slots yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Available Types</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($time_slots as $slot): ?>
                                                    <tr>
                                                        <td><?php echo date('F j, Y', strtotime($slot['date'])); ?></td>
                                                        <td>
                                                            <?php 
                                                                echo date('g:i A', strtotime($slot['start_time'])) . ' - ' . 
                                                                     date('g:i A', strtotime($slot['end_time'])); 
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($slot['is_video_available']): ?>
                                                                <span class="badge bg-info text-dark"><i class="bi bi-camera-video"></i> Video</span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($slot['is_phone_available']): ?>
                                                                <span class="badge bg-info text-dark"><i class="bi bi-telephone"></i> Phone</span>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($slot['is_inperson_available']): ?>
                                                                <span class="badge bg-info text-dark"><i class="bi bi-person-badge"></i> In-Person</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($slot['is_booked']): ?>
                                                                <span class="badge bg-danger">Booked</span>
                                                                <?php if (!empty($slot['client_name'])): ?>
                                                                    <small>by <?php echo htmlspecialchars($slot['client_name']); ?></small>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="badge bg-success">Available</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if (!$slot['is_booked']): ?>
                                                                <a href="?delete_slot=<?php echo $slot['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this time slot?');">
                                                                    <i class="bi bi-trash"></i> Delete
                                                                </a>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-secondary" disabled>
                                                                    <i class="bi bi-lock"></i> Booked
                                                                </button>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
    // Set minimum time for end time based on start time
    document.getElementById('start_time').addEventListener('change', function() {
        document.getElementById('end_time').min = this.value;
    });
</script>

<?php include '../includes/footer.php'; ?> 