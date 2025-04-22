<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an applicant
if (!isLoggedIn() || !isUserType('applicant')) {
    header("Location: ../../login.php");
    exit;
}

// Get user data
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Check if case ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$case_id = (int)$_GET['id'];

// Verify this case belongs to the applicant
$stmt = $conn->prepare("
    SELECT ca.*, vt.name as visa_type, p.name as professional_name
    FROM case_applications ca
    JOIN visa_types vt ON ca.visa_type_id = vt.id
    LEFT JOIN users p ON ca.professional_id = p.id
    WHERE ca.id = ? AND ca.client_id = ?
");
$stmt->bind_param("ii", $case_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: index.php");
    exit;
}

$case = $result->fetch_assoc();

// Get documents for this case
$stmt = $conn->prepare("
    SELECT d.*, dt.name as document_type 
    FROM documents d
    JOIN document_types dt ON d.document_type_id = dt.id
    WHERE d.case_id = ?
    ORDER BY d.uploaded_at DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$documents = $stmt->get_result();
$documents_count = $documents->num_rows;

// Get notes for this case (excluding private notes)
$stmt = $conn->prepare("
    SELECT cn.*, 
           CASE 
               WHEN cn.user_type = 'professional' THEN p.name
               WHEN cn.user_type = 'client' THEN u.name
               ELSE 'System'
           END as author_name
    FROM case_notes cn
    LEFT JOIN users p ON cn.user_id = p.id AND cn.user_type = 'professional'
    LEFT JOIN users u ON cn.user_id = u.id AND cn.user_type = 'client'
    WHERE cn.case_id = ? AND (cn.is_private = 0 OR cn.is_private IS NULL)
    ORDER BY cn.created_at DESC
");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$notes = $stmt->get_result();
$notes_count = $notes->num_rows;

// Process note submission if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note_content = htmlspecialchars(trim($_POST['note_content']));
    
    if (!empty($note_content)) {
        $stmt = $conn->prepare("
            INSERT INTO case_notes (case_id, user_id, content, created_at, is_private, user_type)
            VALUES (?, ?, ?, NOW(), 0, 'client')
        ");
        $stmt->bind_param("iis", $case_id, $user_id, $note_content);
        
        if ($stmt->execute()) {
            // Redirect to refresh the page
            header("Location: case-view.php?id=" . $case_id . "&success=note_added");
            exit;
        } else {
            $error_message = "Failed to add note. Please try again.";
        }
    } else {
        $error_message = "Note content cannot be empty.";
    }
}

// Helper function to get appropriate status badge class
function getCaseStatusBadgeClass($status) {
    switch ($status) {
        case 'new':
            return 'bg-primary';
        case 'in_progress':
            return 'bg-info';
        case 'pending_documents':
            return 'bg-warning';
        case 'review':
            return 'bg-secondary';
        case 'approved':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Format case status for display
function formatCaseStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details - Visafy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        .case-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .document-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .document-icon {
            font-size: 24px;
            margin-right: 15px;
            color: var(--secondary-color);
        }
        
        .note-card {
            margin-bottom: 15px;
            border-left: 4px solid var(--secondary-color);
        }
        
        .professional-note {
            border-left-color: var(--primary-color);
        }
        
        .client-note {
            border-left-color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>Visafy</h3>
                <div class="sidebar-user">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <p class="user-name"><?php echo htmlspecialchars($user['name']); ?></p>
                    <p class="user-type">Applicant</p>
                </div>
            </div>
            <div class="sidebar-menu">
                <a href="index.php" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="applications.php" class="menu-item active">
                    <i class="fas fa-file-alt"></i>
                    <span>My Applications</span>
                </a>
                <a href="eligibility.php" class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Eligibility Check</span>
                </a>
                <a href="appointments.php" class="menu-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </a>
                <a href="documents.php" class="menu-item">
                    <i class="fas fa-file-invoice"></i>
                    <span>Documents</span>
                </a>
                <a href="messages.php" class="menu-item">
                    <i class="fas fa-comment-alt"></i>
                    <span>Messages</span>
                </a>
                <a href="profile.php" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="../../logout.php" class="menu-item logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h2>Case Details</h2>
                <div class="header-actions">
                    <a href="applications.php" class="btn">
                        <i class="fas fa-arrow-left"></i> Back to Applications
                    </a>
                </div>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'note_added'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> Your note has been added to the case.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Case Overview -->
            <div class="dashboard-card">
                <div class="case-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?php echo htmlspecialchars($case['visa_type']); ?></h3>
                        <span class="badge <?php echo getCaseStatusBadgeClass($case['status']); ?> fs-6">
                            <?php echo formatCaseStatus($case['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>Case Information</h5>
                        <table class="table">
                            <tr>
                                <th>Reference Number:</th>
                                <td><?php echo htmlspecialchars($case['reference_number']); ?></td>
                            </tr>
                            <tr>
                                <th>Application Date:</th>
                                <td><?php echo date('F j, Y', strtotime($case['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?php echo date('F j, Y', strtotime($case['updated_at'])); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Professional Assigned</h5>
                        <?php if (!empty($case['professional_name'])): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="mb-0"><?php echo htmlspecialchars($case['professional_name']); ?></h6>
                                    <a href="messages.php?professional_id=<?php echo $case['professional_id']; ?>" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="fas fa-comment"></i> Message Professional
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No professional has been assigned to your case yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Documents Section -->
                <div class="col-md-6 mb-4">
                    <div class="dashboard-card">
                        <h3>Documents</h3>
                        <?php if ($documents_count > 0): ?>
                            <div class="documents-list">
                                <?php while ($doc = $documents->fetch_assoc()): ?>
                                    <div class="document-item">
                                        <div class="document-icon">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="document-details flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($doc['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($doc['document_type']); ?> • 
                                                Uploaded on <?php echo date('M j, Y', strtotime($doc['uploaded_at'])); ?>
                                            </small>
                                        </div>
                                        <a href="../../uploads/documents/<?php echo $doc['file_path']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No documents have been uploaded for this case.</p>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <a href="upload-document.php?case_id=<?php echo $case_id; ?>" class="btn">
                                <i class="fas fa-upload"></i> Upload Document
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Case Notes Section -->
                <div class="col-md-6 mb-4">
                    <div class="dashboard-card">
                        <h3>Case Notes</h3>
                        
                        <form method="POST" class="mb-4">
                            <div class="mb-3">
                                <label for="note_content" class="form-label">Add a Note</label>
                                <textarea class="form-control" id="note_content" name="note_content" rows="3" placeholder="Add a note to your case..."></textarea>
                            </div>
                            <button type="submit" name="add_note" class="btn">Submit Note</button>
                        </form>
                        
                        <hr>
                        
                        <?php if ($notes_count > 0): ?>
                            <div class="notes-list">
                                <?php while ($note = $notes->fetch_assoc()): ?>
                                    <div class="card note-card <?php echo $note['user_type'] === 'professional' ? 'professional-note' : 'client-note'; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-subtitle mb-1 <?php echo $note['user_type'] === 'professional' ? 'text-primary' : 'text-secondary'; ?>">
                                                    <?php echo htmlspecialchars($note['author_name']); ?>
                                                    <span class="text-muted fs-6">(<?php echo ucfirst($note['user_type']); ?>)</span>
                                                </h6>
                                                <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($note['created_at'])); ?></small>
                                            </div>
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars($note['content'])); ?></p>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No notes available for this case.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar for mobile view
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html> 