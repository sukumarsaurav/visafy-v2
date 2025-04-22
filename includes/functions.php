<?php
/**
 * Helper functions for Visafy application
 */

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is of a specific type
 * 
 * @param string $type User type to check
 * @return bool True if user is of specified type, false otherwise
 */
function isUserType($type) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $type;
}

/**
 * Redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /login.php");
        exit;
    }
}

/**
 * Redirect to appropriate dashboard based on user type
 */
function redirectToDashboard() {
    if (!isLoggedIn()) {
        return;
    }
    
    switch($_SESSION['user_type']) {
        case 'applicant':
            header("Location: /dashboard/applicant/index.php");
            break;
        case 'employer':
            header("Location: /dashboard/employer/index.php");
            break;
        case 'professional':
            header("Location: /dashboard/professional/index.php");
            break;
        default:
            header("Location: /login.php");
    }
    exit;
}

/**
 * Get user details by ID
 * 
 * @param int $user_id User ID
 * @return array|null User data or null if not found
 */
function getUserById($user_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, name, email, user_type, status, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    return $result->fetch_assoc();
}

/**
 * Check if current user has permission for specific action
 * 
 * @param string $action Action to check permission for
 * @return bool True if user has permission, false otherwise
 */
function hasPermission($action) {
    if (!isLoggedIn()) {
        return false;
    }
    
    // Define permissions based on user type
    $permissions = [
        'applicant' => ['update_profile', 'submit_application', 'view_application'],
        'employer' => ['update_profile', 'post_job', 'view_candidates'],
        'professional' => ['update_profile', 'view_clients', 'process_application']
    ];
    
    $userType = $_SESSION['user_type'];
    
    return in_array($action, $permissions[$userType] ?? []);
}

/**
 * Sanitize input data
 * 
 * @param string $data Data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Display flash message
 * 
 * @param string $type Message type (success, error, info)
 * @param string $message Message content
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get flash message and clear it from session
 * 
 * @return array|null Flash message or null if no message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}
?>
