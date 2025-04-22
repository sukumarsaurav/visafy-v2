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

// Handle note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $noteContent = filter_input(INPUT_POST, 'note_content', FILTER_SANITIZE_STRING);
    $isPrivate = isset($_POST['is_private']) ? 1 : 0;
    
    if (empty($noteContent)) {
        $errorMessage = "Note content cannot be empty.";
    } else {
        try {
            // Insert the note
            $insertStmt = $pdo->prepare("
                INSERT INTO case_notes (case_id, user_id, content, created_at, is_private, user_type)
                VALUES (?, ?, ?, NOW(), ?, 'professional')
            ");
            
            $result = $insertStmt->execute([
                $caseId,
                $professionalId,
                $noteContent,
                $isPrivate
            ]);
            
            if ($result) {
                $successMessage = "Note added successfully.";
                // Clear form data
                unset($noteContent);
            } else {
                $errorMessage = "Failed to add note. Please try again.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Database error: " . $e->getMessage();
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
                <h1 class="h2">Add Note to Case</h1>
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
                            <h5 class="mb-0">Add Note for Case #<?php echo $caseId; ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="note_content" class="form-label">Note Content *</label>
                                    <textarea class="form-control" id="note_content" name="note_content" rows="5" required><?php echo isset($noteContent) ? htmlspecialchars($noteContent) : ''; ?></textarea>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="is_private" name="is_private" value="1">
                                    <label class="form-check-label" for="is_private">
                                        Private Note (only visible to professionals)
                                    </label>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">Add Note</button>
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