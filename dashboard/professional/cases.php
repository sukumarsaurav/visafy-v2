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

// Process status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $case_id = $_POST['case_id'] ?? 0;
    $new_status = $_POST['new_status'] ?? '';
    $notes = htmlspecialchars(trim($_POST['notes'] ?? ''));
    
    if (empty($case_id) || empty($new_status)) {
        $error_message = "Invalid request";
    } else {
        // Update case status
        $stmt = $conn->prepare("UPDATE case_applications SET status = ? WHERE id = ? AND professional_id = ?");
        $stmt->bind_param("sii", $new_status, $case_id, $user_id);
        
        if ($stmt->execute()) {
            // Add note if provided
            if (!empty($notes)) {
                $is_private = isset($_POST['is_private']) ? 1 : 0;
                $stmt = $conn->prepare("INSERT INTO case_notes (case_id, user_id, user_type, content, is_private) VALUES (?, ?, 'professional', ?, ?)");
                $stmt->bind_param("iisi", $case_id, $user_id, $notes, $is_private);
                $stmt->execute();
            }
            
            $success_message = "Case status updated successfully!";
        } else {
            $error_message = "Error updating case status: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Get case filter
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build the query
$query = "SELECT ca.*, u.name as client_name, v.name as visa_type_name 
          FROM case_applications ca 
          JOIN users u ON ca.client_id = u.id 
          JOIN visa_types v ON ca.visa_type_id = v.id 
          WHERE ca.professional_id = ? ";

$params = [$user_id];
$types = "i";

if ($status_filter != 'all') {
    $query .= "AND ca.status = ? ";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $search_term = "%$search%";
    $query .= "AND (ca.reference_number LIKE ? OR u.name LIKE ? OR v.name LIKE ?) ";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$query .= "ORDER BY ca.updated_at DESC";

// Fetch cases
$cases = [];
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $cases[] = $row;
}
$stmt->close();

// Get case counts by status
$case_counts = [];
$status_types = ['new', 'in_progress', 'pending_documents', 'review', 'approved', 'rejected'];

foreach ($status_types as $status) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM case_applications WHERE professional_id = ? AND status = ?");
    $stmt->bind_param("is", $user_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $case_counts[$status] = $count;
    $stmt->close();
}

$total_cases = array_sum($case_counts);

// Page title
$page_title = "Case Management | Visafy";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../../assets/css/consultant.css">

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Case Management</h1>
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
                    Please <a href="profile.php">complete your professional profile</a> before managing cases.
                </div>
            <?php else: ?>
                <!-- Case Stats -->
                <div class="row mb-4">
                    <div class="col-12 col-md-6 col-lg-2 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-1">Total Cases</h6>
                                <h2 class="mb-0"><?php echo $total_cases; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6 col-lg-2 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-1">New</h6>
                                <h2 class="mb-0 text-primary"><?php echo $case_counts['new']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6 col-lg-2 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-1">In Progress</h6>
                                <h2 class="mb-0 text-info"><?php echo $case_counts['in_progress']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6 col-lg-2 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-1">Pending Docs</h6>
                                <h2 class="mb-0 text-warning"><?php echo $case_counts['pending_documents']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-6 col-lg-2 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-1">Review</h6>
                                <h2 class="mb-0 text-secondary"><?php echo $case_counts['review']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-6 col-lg-2 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-1">Completed</h6>
                                <h2 class="mb-0 text-success"><?php echo $case_counts['approved'] + $case_counts['rejected']; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-body">
                        <form action="" method="GET" class="row g-3">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control border-0 bg-light" name="search" placeholder="Search by reference, client or visa type" value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-5">
                                <select class="form-select bg-light border-0" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="new" <?php echo $status_filter == 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="in_progress" <?php echo $status_filter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="pending_documents" <?php echo $status_filter == 'pending_documents' ? 'selected' : ''; ?>>Pending Documents</option>
                                    <option value="review" <?php echo $status_filter == 'review' ? 'selected' : ''; ?>>Under Review</option>
                                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Cases List -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Case Applications</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($cases)): ?>
                            <div class="text-center py-5">
                                <i class="bi bi-folder text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-3 mb-0 text-muted">No cases found matching your criteria</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Reference #</th>
                                            <th>Client</th>
                                            <th>Visa Type</th>
                                            <th>Status</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cases as $case): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($case['reference_number']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($case['client_name']); ?></td>
                                                <td><?php echo htmlspecialchars($case['visa_type_name']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_badge = '';
                                                    switch ($case['status']) {
                                                        case 'new':
                                                            $status_badge = 'primary';
                                                            break;
                                                        case 'in_progress':
                                                            $status_badge = 'info';
                                                            break;
                                                        case 'pending_documents':
                                                            $status_badge = 'warning';
                                                            break;
                                                        case 'review':
                                                            $status_badge = 'secondary';
                                                            break;
                                                        case 'approved':
                                                            $status_badge = 'success';
                                                            break;
                                                        case 'rejected':
                                                            $status_badge = 'danger';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_badge; ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $case['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($case['updated_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="case-details.php?id=<?php echo $case['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $case['id']; ?>">
                                                            <i class="bi bi-arrow-repeat"></i> Update
                                                        </button>
                                                    </div>

                                                    <!-- Status Update Modal -->
                                                    <div class="modal fade" id="updateStatusModal<?php echo $case['id']; ?>" tabindex="-1" aria-labelledby="updateStatusModalLabel<?php echo $case['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="updateStatusModalLabel<?php echo $case['id']; ?>">
                                                                        Update Status: <?php echo htmlspecialchars($case['reference_number']); ?>
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <form action="" method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="case_id" value="<?php echo $case['id']; ?>">
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="new_status<?php echo $case['id']; ?>" class="form-label">New Status</label>
                                                                            <select class="form-select" id="new_status<?php echo $case['id']; ?>" name="new_status" required>
                                                                                <option value="">Select Status</option>
                                                                                <option value="new" <?php echo $case['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                                                                                <option value="in_progress" <?php echo $case['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                                <option value="pending_documents" <?php echo $case['status'] == 'pending_documents' ? 'selected' : ''; ?>>Pending Documents</option>
                                                                                <option value="review" <?php echo $case['status'] == 'review' ? 'selected' : ''; ?>>Under Review</option>
                                                                                <option value="approved" <?php echo $case['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                                                <option value="rejected" <?php echo $case['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                            </select>
                                                                        </div>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="notes<?php echo $case['id']; ?>" class="form-label">Add Note (Optional)</label>
                                                                            <textarea class="form-control" id="notes<?php echo $case['id']; ?>" name="notes" rows="3" placeholder="Add a note about this status change"></textarea>
                                                                        </div>
                                                                        
                                                                        <div class="form-check mb-3">
                                                                            <input class="form-check-input" type="checkbox" id="is_private<?php echo $case['id']; ?>" name="is_private">
                                                                            <label class="form-check-label" for="is_private<?php echo $case['id']; ?>">
                                                                                Make this note private (only visible to professionals)
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 