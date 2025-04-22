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
$user = getUserById($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard - Visafy</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        :root {
            --primary-color: #042167;
            --secondary-color: #eaaa34;
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--primary-color);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h3 {
            margin-bottom: 10px;
            font-size: 20px;
        }

        .sidebar-user {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .user-name {
            font-weight: 600;
        }

        .user-type {
            font-size: 14px;
            opacity: 0.8;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .menu-item:hover, .menu-item.active {
            background-color: rgba(234, 170, 52, 0.1);
            border-left-color: var(--secondary-color);
        }

        .menu-item i {
            margin-right: 10px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .content-header h2 {
            color: var(--primary-color);
        }

        .dashboard-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
        }

        .dashboard-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            align-items: center;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background-color: rgba(234, 170, 52, 0.1);
            display: flex;
            justify-content: center;
            align-items: center;
            margin-right: 15px;
        }

        .stat-icon i {
            font-size: 24px;
            color: var(--secondary-color);
        }

        .stat-details h4 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .stat-details p {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .recent-activity ul {
            list-style: none;
        }

        .activity-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }

        .activity-item i {
            margin-right: 10px;
            color: var(--secondary-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-time {
            font-size: 12px;
            color: #999;
        }

        .btn {
            background-color: var(--secondary-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: var(--primary-color);
        }

        .logout-btn {
            margin-top: auto;
            padding: 12px 20px;
            text-align: center;
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: #dc3545;
            color: white;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .toggle-sidebar {
                display: block;
            }
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
                <div class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </div>
                <!-- <div class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>My Applications</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Eligibility Check</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Appointments</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-file-invoice"></i>
                    <span>Documents</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-comment-alt"></i>
                    <span>Messages</span>
                </div>
                <div class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </div> -->
                <a href="../../logout.php" class="menu-item logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h2>Applicant Dashboard</h2>
                <div class="header-actions">
                    <button class="btn">Start New Application</button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="card-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-details">
                        <h4>Applications</h4>
                        <p>0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-details">
                        <h4>Upcoming Appointments</h4>
                        <p>0</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                    <div class="stat-details">
                        <h4>Pending Documents</h4>
                        <p>0</p>
                    </div>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="dashboard-card">
                <h3>Welcome to Visafy</h3>
                <p>Welcome to your applicant dashboard! From here, you can track your immigration applications, check eligibility for various programs, schedule appointments, and manage your documents.</p>
                <p style="margin-top: 10px;">To get started, click on "Start New Application" or explore the options in the sidebar.</p>
            </div>

            <!-- Recent Activity -->
            <div class="dashboard-card recent-activity">
                <h3>Recent Activity</h3>
                <p>No recent activity to display.</p>
                <!-- Activity items will appear here when you have some activity -->
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar for mobile view
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
