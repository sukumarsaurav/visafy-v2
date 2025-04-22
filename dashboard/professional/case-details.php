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

// Check if case ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: cases.php");
    exit;
}

$case_id = (int)$_GET['id'];

// Verify this professional has access to this case
$stmt = $conn->prepare("SELECT ca.*, u.name as client_name, u.email as client_email, 
                      v.name as visa_type_name, v.description as visa_type_description
                      FROM case_applications ca 
                      JOIN users u ON ca.client_id = u.id 
                      JOIN visa_types v ON ca.visa_type_id = v.id 
                      WHERE ca.id = ? AND ca.professional_id = ?");
$stmt->bind_param("ii", $case_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // No access or case doesn't exist
    header("Location: cases.php?error=noaccess");
    exit;
}

$case_data = $result->fetch_assoc();
$stmt->close();

// Process add note form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note_content = htmlspecialchars(trim($_POST['note_content']));
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    
    if (empty($note_content)) {
        $error_message = "Note content cannot be empty";
    } else {
        $stmt = $conn->prepare("INSERT INTO case_notes (case_id, user_id, user_type, content, is_private) 
                                VALUES (?, ?, 'professional', ?, ?)");
        $stmt->bind_param("iisi", $case_id, $user_id, $note_content, $is_private);
        
        if ($stmt->execute()) {
            $success_message = "Note added successfully!";
        } else {
            $error_message = "Error adding note: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch case notes
$notes = [];
$stmt = $conn->prepare("SELECT cn.*, u.name as user_name, u.email as user_email 
                      FROM case_notes cn 
                      JOIN users u ON cn.user_id = u.id 
                      WHERE cn.case_id = ? 
                      ORDER BY cn.created_at DESC");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notes[] = $row;
}
$stmt->close();

// Fetch documents
$documents = [];
$stmt = $conn->prepare("SELECT d.*, dt.name as document_type_name 
                      FROM documents d 
                      JOIN document_types dt ON d.document_type_id = dt.id 
                      WHERE d.case_id = ? 
                      ORDER BY d.uploaded_at DESC");
$stmt->bind_param("i", $case_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $documents[] = $row;
}
$stmt->close();

// Page title
$page_title = "Case Details | Visafy";
include '../includes/header.php';
?>

<link rel="stylesheet" href="../../assets/css/consultant.css">

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Case Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="cases.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Cases
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

            <!-- Case Overview -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Case Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="mb-1 text-muted small">Reference Number</p>
                                <h5><?php echo htmlspecialchars($case_data['reference_number']); ?></h5>
                            </div>
                            <div class="mb-3">
                                <p class="mb-1 text-muted small">Status</p>
                                <?php
                                $status_badge = '';
                                switch ($case_data['status']) {
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
                                <h5><span class="badge bg-<?php echo $status_badge; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $case_data['status'])); ?>
                                </span></h5>
                            </div>
                            <div class="mb-3">
                                <p class="mb-1 text-muted small">Visa Type</p>
                                <h5><?php echo htmlspecialchars($case_data['visa_type_name']); ?></h5>
                                <p class="small text-muted"><?php echo htmlspecialchars($case_data['visa_type_description']); ?></p>
                            </div>
                            <div class="mb-3">
                                <p class="mb-1 text-muted small">Created</p>
                                <p><?php echo date('F j, Y', strtotime($case_data['created_at'])); ?></p>
                            </div>
                            <div>
                                <p class="mb-1 text-muted small">Last Updated</p>
                                <p><?php echo date('F j, Y', strtotime($case_data['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Client Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="mb-1 text-muted small">Name</p>
                                <h5><?php echo htmlspecialchars($case_data['client_name']); ?></h5>
                            </div>
                            <div class="mb-3">
                                <p class="mb-1 text-muted small">Email</p>
                                <p><a href="mailto:<?php echo htmlspecialchars($case_data['client_email']); ?>"><?php echo htmlspecialchars($case_data['client_email']); ?></a></p>
                            </div>
                            <div class="mt-4">
                                <h6>Quick Actions</h6>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addNoteModal">
                                        <i class="bi bi-plus-circle"></i> Add Note
                                    </button>
                                    <a href="documents.php?case_id=<?php echo $case_id; ?>" class="btn btn-outline-info">
                                        <i class="bi bi-file-earmark-arrow-up"></i> Upload Document
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#updateStatusModalDetail">
                                        <i class="bi bi-arrow-repeat"></i> Update Status
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs for Notes and Documents -->
            <ul class="nav nav-tabs mb-4" id="caseTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab" aria-controls="notes" aria-selected="true">Notes</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">Documents</button>
                </li>
            </ul>

            <div class="tab-content" id="caseTabContent">
                <!-- Notes Tab -->
                <div class="tab-pane fade show active" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <?php if (empty($notes)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-chat-square-text text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-3 mb-0 text-muted">No notes yet. Add the first note to this case.</p>
                                </div>
                            <?php else: ?>
                                <div class="timeline">
                                    <?php foreach ($notes as $note): ?>
                                        <div class="timeline-item">
                                            <div class="card mb-3 <?php echo $note['is_private'] ? 'bg-light' : ''; ?>">
                                                <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($note['user_name']); ?></strong>
                                                        <span class="text-muted ms-2"><?php echo $note['user_type']; ?></span>
                                                        <?php if ($note['is_private']): ?>
                                                            <span class="badge bg-warning ms-2">Private</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($note['created_at'])); ?></small>
                                                </div>
                                                <div class="card-body">
                                                    <p class="card-text"><?php echo nl2br(htmlspecialchars($note['content'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Documents Tab -->
                <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <?php if (empty($documents)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-file-earmark-text text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-3 mb-0 text-muted">No documents yet. Upload the first document for this case.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Document</th>
                                                <th>Type</th>
                                                <th>Uploaded By</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($documents as $document): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <i class="bi bi-file-earmark-text me-2 text-primary" style="font-size: 1.5rem;"></i>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($document['name']); ?></strong>
                                                                <?php if (!empty($document['description'])): ?>
                                                                    <p class="mb-0 small text-muted"><?php echo htmlspecialchars($document['description']); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($document['document_type_name']); ?></td>
                                                    <td>
                                                        <?php if ($document['professional_id'] == $user_id): ?>
                                                            <span class="badge bg-info">You</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Client</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo date('M j, Y', strtotime($document['uploaded_at'])); ?></td>
                                                    <td>
                                                        <a href="../../<?php echo htmlspecialchars($document['file_path']); ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
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
        </main>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addNoteModalLabel">Add Note to Case</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="note_content" class="form-label">Note Content</label>
                        <textarea class="form-control" id="note_content" name="note_content" rows="5" required></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_private" name="is_private">
                        <label class="form-check-label" for="is_private">
                            Make this note private (only visible to professionals)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_note" class="btn btn-primary">Add Note</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModalDetail" tabindex="-1" aria-labelledby="updateStatusModalDetailLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalDetailLabel">
                    Update Status: <?php echo htmlspecialchars($case_data['reference_number']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="case_id" value="<?php echo $case_id; ?>">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">New Status</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="">Select Status</option>
                            <option value="new" <?php echo $case_data['status'] == 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="in_progress" <?php echo $case_data['status'] == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="pending_documents" <?php echo $case_data['status'] == 'pending_documents' ? 'selected' : ''; ?>>Pending Documents</option>
                            <option value="review" <?php echo $case_data['status'] == 'review' ? 'selected' : ''; ?>>Under Review</option>
                            <option value="approved" <?php echo $case_data['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="rejected" <?php echo $case_data['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Add Note (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Add a note about this status change"></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_private_status" name="is_private">
                        <label class="form-check-label" for="is_private_status">
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

<style>
    .timeline {
        position: relative;
        padding: 1rem 0;
    }
    
    .timeline-item {
        position: relative;
        padding-left: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .timeline-item:before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .timeline-item:after {
        content: "";
        position: absolute;
        left: -6px;
        top: 0;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background-color: #007bff;
    }
</style>

<?php include '../includes/footer.php'; ?> 