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

// Fetch all notes for this case (as a professional, can see all notes including private ones)
$notesStmt = $pdo->prepare("
    SELECT cn.*, 
           CASE 
               WHEN cn.user_type = 'client' THEN c.full_name
               WHEN cn.user_type = 'professional' THEN p.full_name
               WHEN cn.user_type = 'admin' THEN a.username
               ELSE 'Unknown'
           END as author_name
    FROM case_notes cn
    LEFT JOIN clients c ON cn.user_id = c.id AND cn.user_type = 'client'
    LEFT JOIN professionals p ON cn.user_id = p.id AND cn.user_type = 'professional'
    LEFT JOIN admins a ON cn.user_id = a.id AND cn.user_type = 'admin'
    WHERE cn.case_id = ?
    ORDER BY cn.created_at DESC
");
$notesStmt->execute([$caseId]);
$notes = $notesStmt->fetchAll(PDO::FETCH_ASSOC);

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Case Notes</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="case-details.php?id=<?php echo $caseId; ?>" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Case
                    </a>
                    <a href="add-note.php?case_id=<?php echo $caseId; ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg"></i> Add New Note
                    </a>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Notes for Case #<?php echo $caseId; ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($notes)): ?>
                        <div class="alert alert-info">
                            No notes have been added to this case yet.
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($notes as $note): ?>
                                <div class="note-card mb-4 <?php echo $note['is_private'] ? 'border-warning' : ''; ?>">
                                    <div class="card">
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($note['author_name']); ?></strong>
                                                <span class="text-muted ms-2">
                                                    (<?php echo ucfirst($note['user_type']); ?>)
                                                </span>
                                                <?php if ($note['is_private']): ?>
                                                    <span class="badge bg-warning text-dark ms-2">Private</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo date('F j, Y, g:i a', strtotime($note['created_at'])); ?>
                                            </div>
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
        </main>
    </div>
</div>

<style>
.timeline {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
}

.note-card {
    position: relative;
}

.note-card.border-warning .card {
    border-left: 4px solid #ffc107;
}
</style>

<?php include '../includes/footer.php'; ?> 