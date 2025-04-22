<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : "Dashboard | Visafy"; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <style>
        :root {
            --primary-color: #042167;
            --secondary-color: #eaaa34;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #031b54;
            border-color: #031b54;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .bg-secondary {
            background-color: var(--secondary-color) !important;
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            color: #fff;
        }
        
        .btn-secondary:hover {
            background-color: #d9901b;
            border-color: #d9901b;
            color: #fff;
        }
        
        .text-secondary {
            color: var(--secondary-color) !important;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: var(--primary-color);
            color: #fff;
        }
        
        .sidebar-heading {
            font-size: .75rem;
            text-transform: uppercase;
        }
        
        .sidebar .nav-link {
            font-weight: 500;
            color: rgba(255, 255, 255, .75);
            padding: .5rem 1rem;
        }
        
        .sidebar .nav-link:hover {
            color: #fff;
        }
        
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(234, 170, 52, 0.2);
            border-left: 4px solid var(--secondary-color);
        }
        
        .sidebar .nav-link i {
            margin-right: .5rem;
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-primary flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="../../index.php">Visafy</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="form-control form-control-dark w-100 bg-primary text-white border-0">
            <?php echo isset($page_title) ? $page_title : "Dashboard"; ?>
        </div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="../../logout.php">Sign out</a>
            </div>
        </div>
    </header>
</body>
</html> 