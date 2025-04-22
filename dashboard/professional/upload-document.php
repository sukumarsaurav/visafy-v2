<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if professional is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'professional') {
    header('Location: ../login.php');
    exit;
}

$professionalId = $_SESSION['user_id'];
$caseId = isset($_GET['case_id']) ? intval($_GET['case_id']) : 0;

if ($caseId === 0) {
    header('Location: dashboard.php');
    exit;
}

// Verify this professional has access to this case
$stmt = $pdo->prepare("
    SELECT ca.* 
    FROM case_applications ca
    JOIN professional_clients pc ON ca.client_id = pc.client_id
    WHERE ca.id = ? AND pc.professional_id = ?
");
$stmt->execute([$caseId, $professionalId]);
$case = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$case) {
    header('Location: dashboard.php');
    exit;
}

$errorMessage = '';
$successMessage = '';

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    
    if (empty($title)) {
        $errorMessage = "Document title is required.";
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = "Please select a valid document to upload.";
    } else {
        $uploadedFile = $_FILES['document'];
        $fileName = basename($uploadedFile['name']);
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Check file size (limit to 10MB)
        $maxFileSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($uploadedFile['size'] > $maxFileSize) {
            $errorMessage = "File size exceeds the limit of 10MB.";
        } else {
            // Generate unique filename
            $newFileName = uniqid('doc_') . '.' . $fileType;
            $uploadPath = '../../uploads/documents/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            
            $destination = $uploadPath . $newFileName;
            
            if (move_uploaded_file($uploadedFile['tmp_name'], $destination)) {
                // Add to database
                $insertStmt = $pdo->prepare("
                    INSERT INTO documents (case_id, title, description, file_path, file_type, upload_date, uploaded_by)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)
                ");
                
                $result = $insertStmt->execute([
                    $caseId,
                    $title,
                    $description,
                    $newFileName,
                    $fileType,
                    $professionalId
                ]);
                
                if ($result) {
                    $successMessage = "Document uploaded successfully.";
                    // Clear form data
                    unset($title, $description);
                } else {
                    $errorMessage = "Failed to save document information in the database.";
                }
            } else {
                $errorMessage = "Failed to upload document. Please try again.";
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Upload Document</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="case-details.php?id=<?php echo $caseId; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Case
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $errorMessage; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $successMessage; ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Upload Document for Case #<?php echo $caseId; ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Document Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required 
                                           value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="document" class="form-label">Select Document *</label>
                                    <input type="file" class="form-control" id="document" name="document" required>
                                    <div class="form-text">
                                        Allowed file types: PDF, DOC, DOCX, JPG, PNG. Maximum file size: 10MB.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Upload Document</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 