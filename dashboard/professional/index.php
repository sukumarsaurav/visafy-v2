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
$profile_exists = false;
$profile_data = null;
$total_clients = 0;
$notifications = [];

// Get professional data
try {
    $stmt = $conn->prepare("SELECT * FROM professionals WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $professional_result = $stmt->get_result();
    $profile_exists = $professional_result->num_rows > 0;
    $profile_data = $profile_exists ? $professional_result->fetch_assoc() : null;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    // Handle missing professionals table
    error_log("Error accessing professionals table: " . $e->getMessage());
}

// Get user data
try {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    // Should not happen as users table must exist for login
    error_log("Error accessing users table: " . $e->getMessage());
    header("Location: ../../login.php");
    exit;
}

// Check if professional has 'pending' status
$isPending = isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'pending';

// Get total clients
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as total_clients FROM professional_clients WHERE professional_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $total_clients = $stmt->get_result()->fetch_assoc()['total_clients'] ?? 0;
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    // Handle missing professional_clients table
    error_log("Error accessing professional_clients table: " . $e->getMessage());
    $total_clients = 0;
}

// Get latest notifications
try {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notifications_result = $stmt->get_result();
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    // Handle missing notifications table
    error_log("Error accessing notifications table: " . $e->getMessage());
}

// Helper function to get profile image
function get_profile_image($user_id) {
    global $profile_data;
    
    if ($profile_data && !empty($profile_data['profile_image'])) {
        return '../../' . $profile_data['profile_image'];
    }
    
    return 'https://via.placeholder.com/64?text=User';
}

// Page title
$page_title = "Professional Dashboard | Visafy";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Professional Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="profile.php" class="btn btn-sm btn-outline-primary">Update Profile</a>
                        <a href="clients.php" class="btn btn-sm btn-outline-secondary">View Clients</a>
                    </div>
                </div>
            </div>

            <div class="alert alert-warning">
                <strong>Database Setup Required!</strong> Some database tables are missing. Please run the database setup script to create all required tables. Contact the administrator for assistance.
            </div>

            <?php if ($isPending): ?>
            <div class="alert alert-warning">
                <strong>Account Pending Approval!</strong> Your account is currently being reviewed by our administrators. While waiting for approval, you can complete your profile details to speed up the verification process.
            </div>
            <?php endif; ?>

            <?php if (!$profile_exists): ?>
            <div class="alert alert-warning">
                <strong>Welcome to Visafy!</strong> Please complete your professional profile to start accepting clients.
                <a href="profile.php" class="btn btn-warning btn-sm ms-3">Complete Profile</a>
            </div>
            <?php endif; ?>

            <div class="row">
                <!-- Profile Summary Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">Profile Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <img src="<?php echo get_profile_image($user_id); ?>" alt="Profile Image" class="rounded-circle me-3" style="width: 64px; height: 64px;">
                                <div>
                                    <h5 class="mb-0"><?php echo htmlspecialchars($user_data['name']); ?></h5>
                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                    <?php if ($profile_exists && isset($profile_data['is_verified']) && $profile_data['is_verified']): ?>
                                    <span class="badge bg-success">Verified</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($profile_exists): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Experience:</strong> <?php echo htmlspecialchars($profile_data['years_experience'] ?? 'N/A'); ?> years</p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile_data['phone'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Clients:</strong> <?php echo $total_clients; ?></p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-<?php echo isset($profile_data['availability_status']) ? ($profile_data['availability_status'] == 'available' ? 'success' : ($profile_data['availability_status'] == 'busy' ? 'warning' : 'danger')) : 'secondary'; ?>">
                                            <?php echo isset($profile_data['availability_status']) ? ucfirst($profile_data['availability_status']) : 'Unknown'; ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center my-4">
                                <p>Complete your profile to see your summary</p>
                                <a href="profile.php" class="btn btn-primary">Create Profile</a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats Card -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white">
                            <h5 class="card-title mb-0">Quick Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="display-4"><?php echo $total_clients; ?></h3>
                                            <p class="text-muted mb-0">Total Clients</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="display-4"><?php echo $profile_exists ? ($profile_data['reviews_count'] ?? 0) : 0; ?></h3>
                                            <p class="text-muted mb-0">Reviews</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="display-4"><?php echo $profile_exists ? ($profile_data['rating'] ?? '0.0') : '0.0'; ?></h3>
                                            <p class="text-muted mb-0">Rating</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h3 class="display-4"><?php echo $profile_exists ? (isset($profile_data['is_featured']) && $profile_data['is_featured'] ? 'Yes' : 'No') : 'No'; ?></h3>
                                            <p class="text-muted mb-0">Featured</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Activity -->
                <div class="col-md-8 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="card-title mb-0">Recent Notifications</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($notifications) > 0): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($notifications as $notification): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($notification['created_at'])); ?></small>
                                            </div>
                                            <p class="mb-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center my-4">
                                    <p>No recent notifications</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <a href="notifications.php" class="btn btn-sm btn-outline-secondary">View All Notifications</a>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-dark text-white">
                            <h5 class="card-title mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-3">
                                <a href="profile.php" class="btn btn-primary">Update Profile</a>
                                <a href="clients.php" class="btn btn-success">Manage Clients</a>
                                <a href="availability.php" class="btn btn-info">Set Availability</a>
                                <a href="messages.php" class="btn btn-warning">Check Messages</a>
                                <a href="documents.php" class="btn btn-secondary">Upload Documents</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
