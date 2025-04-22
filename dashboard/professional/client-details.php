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

// Check if client ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: clients.php");
    exit;
}

$client_id = (int)$_GET['id'];

// Verify this client relationship belongs to the professional
$stmt = $conn->prepare("
    SELECT pc.*, u.name, u.email, u.phone, u.created_at as user_since, 
           COALESCE(ca.status, 'Not Started') as case_status
    FROM professional_clients pc
    JOIN users u ON pc.client_id = u.id
    LEFT JOIN case_applications ca ON ca.client_id = pc.client_id AND ca.professional_id = pc.professional_id
    WHERE pc.professional_id = ? AND pc.client_id = ?
");
$stmt->bind_param("ii", $user_id, $client_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: clients.php");
    exit;
}

$client = $result->fetch_assoc();

// Get client profile information
$stmt = $conn->prepare("
    SELECT * FROM client_profiles WHERE user_id = ?
");
$stmt->bind_param("i", $client_id);
$stmt->execute();
$profile_result = $stmt->get_result();
$profile = $profile_result->num_rows > 0 ? $profile_result->fetch_assoc() : null;

// Get documents
$stmt = $conn->prepare("
    SELECT d.*, dt.name as document_type 
    FROM documents d
    JOIN document_types dt ON d.document_type_id = dt.id
    WHERE d.client_id = ? AND d.professional_id = ?
    ORDER BY d.uploaded_at DESC
");
$stmt->bind_param("ii", $client_id, $user_id);
$stmt->execute();
$documents = $stmt->get_result();
$documents_count = $documents->num_rows;

// Get messages
$stmt = $conn->prepare("
    SELECT * FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY sent_at DESC
    LIMIT 5
");
$stmt->bind_param("iiii", $user_id, $client_id, $client_id, $user_id);
$stmt->execute();
$messages = $stmt->get_result();
$messages_count = $messages->num_rows;

// Get cases/applications
$stmt = $conn->prepare("
    SELECT ca.*, vt.name as visa_type 
    FROM case_applications ca
    JOIN visa_types vt ON ca.visa_type_id = vt.id
    WHERE ca.client_id = ? AND ca.professional_id = ?
    ORDER BY ca.updated_at DESC
");
$stmt->bind_param("ii", $client_id, $user_id);
$stmt->execute();
$cases = $stmt->get_result();
$cases_count = $cases->num_rows;

// Handle status update if applicable
if (isset($_POST['update_status']) && isset($_POST['relationship_status'])) {
    $new_status = $_POST['relationship_status'];
    $valid_statuses = ['active', 'completed', 'archived'];
    
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE professional_clients SET status = ?, updated_at = NOW() WHERE professional_id = ? AND client_id = ?");
        $stmt->bind_param("sii", $new_status, $user_id, $client_id);
        
        if ($stmt->execute()) {
            $success_message = "Client status updated successfully.";
            // Update client variable for display
            $client['status'] = $new_status;
        } else {
            $error_message = "Error updating client status.";
        }
    } else {
        $error_message = "Invalid status selected.";
    }
}

// Page title
$page_title = "Client Details | Visafy";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Client Details: <?php echo htmlspecialchars($client['name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="clients.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Clients
                    </a>
                    <a href="messages.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-chat-dots"></i> Message Client
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

            <div class="row">
                <!-- Client Overview -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Client Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <?php if ($profile && !empty($profile['profile_image'])): ?>
                                    <img src="../../uploads/profiles/<?php echo htmlspecialchars($profile['profile_image']); ?>" class="rounded-circle img-thumbnail" style="width: 120px; height: 120px; object-fit: cover;" alt="Profile Image">
                                <?php else: ?>
                                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto" style="width: 120px; height: 120px;">
                                        <i class="bi bi-person" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <h5 class="mt-3"><?php echo htmlspecialchars($client['name']); ?></h5>
                                <span class="badge <?php echo getStatusBadgeClass($client['status']); ?>"><?php echo ucfirst($client['status']); ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <form method="POST" action="">
                                    <div class="input-group">
                                        <select name="relationship_status" class="form-select form-select-sm">
                                            <option value="active" <?php echo $client['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="completed" <?php echo $client['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                            <option value="archived" <?php echo $client['status'] == 'archived' ? 'selected' : ''; ?>>Archived</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-outline-secondary">Update</button>
                                    </div>
                                </form>
                            </div>
                            
                            <hr>
                            
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-envelope me-2"></i> Email</span>
                                    <span class="text-truncate" style="max-width: 150px;"><?php echo htmlspecialchars($client['email']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-telephone me-2"></i> Phone</span>
                                    <span><?php echo !empty($client['phone']) ? htmlspecialchars($client['phone']) : 'Not provided'; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-calendar-check me-2"></i> Client Since</span>
                                    <span><?php echo date('M d, Y', strtotime($client['created_at'])); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-briefcase me-2"></i> Case Status</span>
                                    <span class="badge <?php echo getCaseStatusBadgeClass($client['case_status']); ?>">
                                        <?php echo $client['case_status']; ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Profile Details -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Profile Details</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($profile): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Nationality</label>
                                            <p><?php echo htmlspecialchars($profile['nationality'] ?? 'Not provided'); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Date of Birth</label>
                                            <p><?php echo !empty($profile['date_of_birth']) ? date('M d, Y', strtotime($profile['date_of_birth'])) : 'Not provided'; ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Address</label>
                                            <p><?php echo nl2br(htmlspecialchars($profile['address'] ?? 'Not provided')); ?></p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Current Immigration Status</label>
                                            <p><?php echo htmlspecialchars($profile['current_status'] ?? 'Not provided'); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Employment Status</label>
                                            <p><?php echo htmlspecialchars($profile['employment_status'] ?? 'Not provided'); ?></p>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted">Education</label>
                                            <p><?php echo htmlspecialchars($profile['education'] ?? 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label text-muted">Notes</label>
                                    <p><?php echo nl2br(htmlspecialchars($profile['notes'] ?? 'No notes available.')); ?></p>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    This client has not completed their profile yet. Please ask them to update their profile information.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Cases/Applications -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Case Applications</h5>
                            <a href="case-new.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-light">
                                <i class="bi bi-plus"></i> New Case
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if ($cases_count > 0): ?>
                                <div class="list-group">
                                    <?php while ($case = $cases->fetch_assoc()): ?>
                                        <a href="case-details.php?id=<?php echo $case['id']; ?>" class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($case['visa_type']); ?></h6>
                                                <small class="text-muted"><?php echo date('M d, Y', strtotime($case['created_at'])); ?></small>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="badge <?php echo getCaseStatusBadgeClass($case['status']); ?>"><?php echo $case['status']; ?></span>
                                                <small>Last updated: <?php echo date('M d, Y', strtotime($case['updated_at'])); ?></small>
                                            </div>
                                        </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No cases or applications have been started for this client.</p>
                                <div class="text-center mt-3">
                                    <a href="case-new.php?client_id=<?php echo $client_id; ?>" class="btn btn-success">
                                        <i class="bi bi-plus-circle"></i> Start New Case
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Documents -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Documents</h5>
                            <a href="documents.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-light">
                                <i class="bi bi-file-earmark"></i> Manage Documents
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if ($documents_count > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Document</th>
                                                <th>Type</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($doc = $documents->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($doc['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></td>
                                                    <td>
                                                        <a href="../../uploads/documents/<?php echo $doc['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No documents have been uploaded for this client.</p>
                                <div class="text-center mt-3">
                                    <a href="documents.php?client_id=<?php echo $client_id; ?>" class="btn btn-danger">
                                        <i class="bi bi-upload"></i> Upload Documents
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Messages -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Recent Messages</h5>
                    <a href="messages.php?client_id=<?php echo $client_id; ?>" class="btn btn-sm btn-light">
                        <i class="bi bi-chat"></i> View All Messages
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($messages_count > 0): ?>
                        <div class="list-group">
                            <?php while ($message = $messages->fetch_assoc()): 
                                $is_sender = $message['sender_id'] == $user_id;
                            ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo $is_sender ? 'You' : htmlspecialchars($client['name']); ?></h6>
                                        <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($message['sent_at'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars(substr($message['content'], 0, 100)) . (strlen($message['content']) > 100 ? '...' : ''); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No messages exchanged with this client yet.</p>
                        <div class="text-center mt-3">
                            <a href="messages.php?client_id=<?php echo $client_id; ?>" class="btn btn-primary">
                                <i class="bi bi-chat-text"></i> Start Conversation
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Helper functions
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'active':
            return 'bg-success';
        case 'pending':
            return 'bg-warning text-dark';
        case 'completed':
            return 'bg-info';
        case 'rejected':
            return 'bg-danger';
        case 'archived':
            return 'bg-secondary';
        default:
            return 'bg-secondary';
    }
}

function getCaseStatusBadgeClass($status) {
    switch ($status) {
        case 'Complete':
        case 'Approved':
            return 'bg-success';
        case 'In Progress':
            return 'bg-primary';
        case 'Under Review':
            return 'bg-info';
        case 'Pending':
            return 'bg-warning text-dark';
        case 'Rejected':
        case 'Denied':
            return 'bg-danger';
        case 'Not Started':
            return 'bg-secondary';
        default:
            return 'bg-secondary';
    }
}
?>

<?php include '../includes/footer.php'; ?> 