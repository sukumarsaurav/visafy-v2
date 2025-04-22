<?php
// Set default page title if not set
$page_title = isset($page_title) ? $page_title : "Visayfy | Canadian Immigration Consultancy";

// Check if base_url is already set from the including file
if (!isset($base_url)) {
    // Determine base URL dynamically based on the current script's location
    $current_dir = dirname($_SERVER['PHP_SELF']);
    $base_url = '';

    // If we're in a subdirectory
    if (strpos($current_dir, '/visa-types') !== false || 
        strpos($current_dir, '/blog') !== false || 
        strpos($current_dir, '/resources') !== false ||
        strpos($current_dir, '/assessment-calculator') !== false) {
        $base_url = '..';
    } else if (strpos($current_dir, '/immigration-news') !== false) {
        $base_url = ''; // Root-relative for virtual directory
    } else {
        $base_url = '.';
    }
}

// Define base path - default to use base_url if not explicitly set
$base = isset($base_path) ? $base_path : $base_url;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Visafy Immigration Consultancy'; ?></title>
    <meta name="description" content="Expert Canadian immigration consultancy services for study permits, work permits, express entry, and more.">
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo $base; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Lora:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css">
    
    <!-- Swiper CSS for Sliders -->
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css">
    
    <!-- AOS Animation CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <!-- Move JS libraries to the end of head to ensure they load before other scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/styles.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/animations.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/header.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/resources.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/assessment-drawer.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/news.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/faq.css">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/consultant.css">
        
    <!-- Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <!-- Load utils.js before other scripts -->
    <script src="<?php echo $base; ?>/assets/js/utils.js"></script>

    <!-- Your custom scripts should come after utils.js -->
    <script src="<?php echo $base; ?>/assets/js/main.js" defer></script>
    <script src="<?php echo $base; ?>/assets/js/resources.js" defer></script>
</head>
<body>
    <!-- Top Navbar -->
    <div class="top-navbar">
        <div class="container">
            <div class="top-navbar-content">
                <div class="contact-info-top-bar">
                    <a href="mailto:info@visafy.io" class="top-bar-link"><i class="fas fa-envelope"></i> info@visafy.io</a>
                    <a href="tel:+16472267436" class="top-bar-link"><i class="fas fa-phone"></i> +1 (647) 226-7436</a>
                </div>
                <div class="member-login-top-bar">
                    <a href="/login.php" class="login-btn-top"><i class="fas fa-user"></i>Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer Overlay -->
    <div class="drawer-overlay"></div>
    
    <!-- Side Drawer -->
    <div class="side-drawer">
        <div class="drawer-header">
            <a href="<?php echo $base; ?>/index.php" class="drawer-logo">
                <img src="<?php echo $base; ?>/assets/images/logo-Visafy-light.png" alt="Visafy Logo" class="mobile-logo">
            </a>
            <button class="drawer-close"><i class="fas fa-times"></i></button>
        </div>
        <nav class="drawer-nav">
            <div class="drawer-item" data-target="visa-submenu">
                Visa Services <i class="fas fa-chevron-down"></i>
            </div>
            <div class="drawer-submenu" id="visa-submenu">
                <a href="<?php echo $base; ?>/visa-types/Study-Permit.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Study Permit</div>
                    <div class="drawer-submenu-description">Information for international students looking to study in Canada</div>
                </a>
                <a href="<?php echo $base; ?>/visa-types/Work-Permit.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Work Permit</div>
                    <div class="drawer-submenu-description">Guidance for those seeking employment opportunities in Canada</div>
                </a>
                <a href="<?php echo $base; ?>/visa-types/Express-Entry-visa.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Express Entry</div>
                    <div class="drawer-submenu-description">Fast-track immigration for skilled workers and professionals</div>
                </a>
                <a href="<?php echo $base; ?>/visa-types/Family-Sponsorship.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Family Sponsorship</div>
                    <div class="drawer-submenu-description">Reunite with your family members in Canada</div>
                </a>
                <a href="<?php echo $base; ?>/visa-types/Provincial-Nominee.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Provincial Nominee</div>
                    <div class="drawer-submenu-description">Immigration programs tailored to provincial needs</div>
                </a>
                <a href="<?php echo $base; ?>/visa-types/faq.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Visitor Visa</div>
                    <div class="drawer-submenu-description">Visit Canada for tourism, business, or family visits</div>
                </a>
            </div>
            

            
            <div class="drawer-item" data-target="resources-submenu">
                Resources <i class="fas fa-chevron-down"></i>
            </div>
            <div class="drawer-submenu" id="resources-submenu">
                <a href="<?php echo $base; ?>/resources/immigration-news.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Immigration News</div>
                    <div class="drawer-submenu-description">Latest updates on Canadian immigration policies</div>
                </a>
                <a href="<?php echo $base; ?>/resources/guides-tutorials.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Guides & Tutorials</div>
                    <div class="drawer-submenu-description">Step-by-step instructions for immigration processes</div>
                </a>
                <a href="<?php echo $base; ?>/resources/faq.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">FAQ</div>
                    <div class="drawer-submenu-description">Answers to commonly asked immigration questions</div>
                </a>
                <a href="<?php echo $base; ?>/resources/blog.php" class="drawer-submenu-item">
                    <div class="drawer-submenu-title">Blog</div>
                    <div class="drawer-submenu-description">Articles and insights on Canadian immigration</div>
                </a>
            </div>
            
            <a href="<?php echo $base; ?>/contact.php" class="drawer-item">Contact</a>
            
            <div class="drawer-cta">
                <a href="<?php echo $base; ?>/contact.php" class="btn btn-primary">Book Consultation</a>
            </div>
        </nav>
    </div>

    <!-- Header Section -->
    <header class="header">
        <div class="container header-container">
            <!-- Logo -->
            <div class="logo">
                <a href="/index.php">
                    <img src="/assets/images/logo-Visafy-light.png" alt="Visafy Logo" class="desktop-logo">
                </a>
            </div>
            
            <!-- Right Side Navigation and Button -->
            <div class="header-right">
                <nav class="main-nav">
                    <ul class="nav-menu">
                    <li class="nav-item"><a href="<?php echo $base; ?>/about-us.php">About Us</a></li>
                    <li class="nav-item"><a href="<?php echo $base; ?>/services.php">Services</a></li>
                    <li class="nav-item"><a href="<?php echo $base; ?>/become-member.php">Become Partner</a></li> 
                    <li class="nav-item"><a href="<?php echo $base; ?>/eligibility-test.php">Eligibility Check</a></li> 
                        
                    
                    </ul>
                </nav>
                
                <div class="consultation-btn">
                    <a href="/consultant.php" class="btn btn-primary">Book Consultation</a>
                </div>
                
                <button class="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

</body>
</html> 