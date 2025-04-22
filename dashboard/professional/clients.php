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
$active_clients = [];
$active_clients_count = 0;
$pending_clients = [];
$pending_clients_count = 0;
$archived_clients = [];
$archived_clients_count = 0;

// Handle actions (accept, reject, archive)
if (isset($_GET['action']) && isset($_GET['client_id'])) {
    $error_message = "This feature is not available until the database tables are created. Please run the database setup script.";
}

try {
    // Check if the professional_clients table exists
    $result = $conn->query("SHOW TABLES LIKE 'professional_clients'");
    
    if ($result->num_rows > 0) {
        // Get active clients
        $stmt = $conn->prepare("
            SELECT pc.*, u.name, u.email, u.created_at as user_since
            FROM professional_clients pc
            JOIN users u ON pc.client_id = u.id
            WHERE pc.professional_id = ? AND pc.status = 'active'
            ORDER BY pc.updated_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $active_clients_result = $stmt->get_result();
        $active_clients_count = $active_clients_result->num_rows;
        
        while ($row = $active_clients_result->fetch_assoc()) {
            $active_clients[] = $row;
        }
        $stmt->close();

        // Get pending client requests
        $stmt = $conn->prepare("
            SELECT pc.*, u.name, u.email, u.created_at as user_since
            FROM professional_clients pc
            JOIN users u ON pc.client_id = u.id
            WHERE pc.professional_id = ? AND pc.status = 'pending'
            ORDER BY pc.created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $pending_clients_result = $stmt->get_result();
        $pending_clients_count = $pending_clients_result->num_rows;
        
        while ($row = $pending_clients_result->fetch_assoc()) {
            $pending_clients[] = $row;
        }
        $stmt->close();

        // Get archived clients
        $stmt = $conn->prepare("
            SELECT pc.*, u.name, u.email, u.created_at as user_since
            FROM professional_clients pc
            JOIN users u ON pc.client_id = u.id
            WHERE pc.professional_id = ? AND pc.status IN ('archived', 'completed')
            ORDER BY pc.updated_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $archived_clients_result = $stmt->get_result();
        $archived_clients_count = $archived_clients_result->num_rows;
        
        while ($row = $archived_clients_result->fetch_assoc()) {
            $archived_clients[] = $row;
        }
        $stmt->close();
    } else {
        $error_message = "Professional-client relationships table does not exist. Please run the database setup script.";
    }
} catch (mysqli_sql_exception $e) {
    $error_message = "Database error: " . $e->getMessage();
}

// Page title
$page_title = "Client Management | Visafy";
include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Client Management</h1>
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

            <!-- Database Setup Information -->
            <?php if (empty($active_clients) && empty($pending_clients) && empty($archived_clients)): ?>
                <div class="alert alert-info mb-4">
                    <h5><i class="bi bi-info-circle"></i> Database Setup Required</h5>
                    <p>The client management system requires database tables that don't exist yet. Please run the database setup script to create the necessary tables:</p>
                    <pre>mysql -u [username] -p [database_name] < database-setup.sql</pre>
                    <p>Contact your administrator for assistance with database setup.</p>
                </div>
            <?php endif; ?>

            <!-- Client Requests Section -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Client Requests</h5>
                    <span class="badge bg-dark"><?php echo $pending_clients_count; ?> pending</span>
                </div>
                <div class="card-body">
                    <?php if ($pending_clients_count > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Client Name</th>
                                        <th>Email</th>
                                        <th>Request Date</th>
                                        <th>Message</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_clients as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['name']); ?></td>
                                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                                            <td>
                                                <?php if (!empty($client['initial_message'])): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $client['client_id']; ?>">
                                                        View Message
                                                    </button>
                                                    
                                                    <!-- Message Modal -->
                                                    <div class="modal fade" id="messageModal<?php echo $client['client_id']; ?>" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="messageModalLabel">Message from <?php echo htmlspecialchars($client['name']); ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <?php echo nl2br(htmlspecialchars($client['initial_message'])); ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <small class="text-muted">No message</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="clients.php?action=accept&client_id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Accept this client?')">Accept</a>
                                                    <a href="clients.php?action=reject&client_id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this client?')">Reject</a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No pending client requests at this time.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Active Clients Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Active Clients</h5>
                    <span class="badge bg-light text-dark"><?php echo $active_clients_count; ?> active</span>
                </div>
                <div class="card-body">
                    <?php if ($active_clients_count > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Client Name</th>
                                        <th>Email</th>
                                        <th>Since</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_clients as $client): ?>
                                        <tr>
                                            <td>
                                                <a href="client-details.php?id=<?php echo $client['client_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($client['name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($client['created_at'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($client['updated_at'])); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        <li><a class="dropdown-item" href="client-details.php?id=<?php echo $client['client_id']; ?>">View Details</a></li>
                                                        <li><a class="dropdown-item" href="messages.php?client_id=<?php echo $client['client_id']; ?>">Send Message</a></li>
                                                        <li><a class="dropdown-item" href="documents.php?client_id=<?php echo $client['client_id']; ?>">Manage Documents</a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="clients.php?action=archive&client_id=<?php echo $client['client_id']; ?>" onclick="return confirm('Archive this client relationship?')">Archive</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No active clients at this time.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Archived Clients Section -->
            <div class="card mb-4">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Archived Clients</h5>
                    <span class="badge bg-light text-dark"><?php echo $archived_clients_count; ?> archived</span>
                </div>
                <div class="card-body">
                    <?php if ($archived_clients_count > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Client Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($archived_clients as $client): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($client['name']); ?></td>
                                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $client['status'] == 'completed' ? 'bg-success' : 'bg-secondary'; ?>">
                                                    <?php echo ucfirst($client['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($client['updated_at'])); ?></td>
                                            <td>
                                                <a href="client-details.php?id=<?php echo $client['client_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                    View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center">No archived clients at this time.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 