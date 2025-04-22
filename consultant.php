<?php
session_start();
require_once 'config/database.php';

// Get search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$specialty_filter = isset($_GET['specialty']) ? $_GET['specialty'] : '';

// Base query to get professionals - remove the verification requirement
$query = "SELECT p.*, u.name, u.email 
          FROM professionals p 
          JOIN users u ON p.user_id = u.id 
          WHERE u.user_type = 'professional' 
          AND u.status = 'active' 
          AND p.is_verified = 1";

$params = [];
$types = "";

// Add search conditions
if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR p.specializations LIKE ? OR p.bio LIKE ?)";
    $search_param = "%$search%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

// Add specialty filter if selected
if (!empty($specialty_filter)) {
    $query .= " AND p.specializations LIKE ?";
    $specialty_param = "%$specialty_filter%";
    array_push($params, $specialty_param);
    $types .= "s";
}

$query .= " ORDER BY p.is_featured DESC, p.rating DESC";

// Execute the query with parameters if needed
if (!empty($params)) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

// Fetch all consultants
$consultants = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $consultants[] = $row;
    }
}

// Get consultation fees for each professional
$consultation_fees = [];
$fee_query = "SELECT professional_id, consultation_type, fee FROM consultation_fees";
$fee_result = $conn->query($fee_query);

if ($fee_result && $fee_result->num_rows > 0) {
    while ($row = $fee_result->fetch_assoc()) {
        $consultation_fees[$row['professional_id']][$row['consultation_type']] = $row['fee'];
    }
}

// Get unique specializations for filter options
$specializations = [];
$spec_query = "SELECT DISTINCT specializations FROM professionals WHERE is_verified = 1";
$spec_result = $conn->query($spec_query);

if ($spec_result && $spec_result->num_rows > 0) {
    while ($row = $spec_result->fetch_assoc()) {
        $specs = explode(',', $row['specializations']);
        foreach ($specs as $spec) {
            $spec = trim($spec);
            if (!empty($spec) && !in_array($spec, $specializations)) {
                $specializations[] = $spec;
            }
        }
    }
    sort($specializations);
}

$page_title = "Find a Consultant | Visafy";
include('includes/header.php');
?>

<style>
    /* Main container styles */
    .consultants-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
        background-color: #f8f9fc;
        border-radius: 12px;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }
    
    /* Search section styles */
    .search-section {
        margin-bottom: 30px;
    }
    
    .search-box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .search-box input {
        width: 100%;
        padding: 15px 20px;
        padding-left: 45px;
        border-radius: 30px;
        border: 1px solid #e0e0e5;
        font-size: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s;
    }
    
    .search-box input:focus {
        outline: none;
        border-color: #4c7bf3;
        box-shadow: 0 2px 15px rgba(76, 123, 243, 0.15);
    }
    
    .search-box i {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #666;
    }
    
    /* Filter buttons */
    .specialty-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 30px;
    }
    
    .specialty-filter-btn {
        padding: 8px 16px;
        border-radius: 20px;
        border: 1px solid #e0e0e5;
        background-color: white;
        font-size: 14px;
        color: #333;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .specialty-filter-btn:hover {
        background-color: #f0f0f5;
    }
    
    .specialty-filter-btn.active {
        background-color: #4c7bf3;
        color: white;
        border-color: #4c7bf3;
    }
    
    /* Consultant cards grid */
    .consultants-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
    }
    
    /* Individual consultant card */
    .consultant-card {
        background-color: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        transition: all 0.3s;
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        display: block;
    }
    
    .consultant-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    }
    
    .consultant-header {
        padding: 25px;
        display: flex;
        align-items: center;
    }
    
    .consultant-photo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 15px;
        background-color: #f0f0f5;
        border: 1px solid #e0e0e5;
    }
    
    .consultant-info h3 {
        margin: 0 0 5px 0;
        font-size: 18px;
        color: #333;
    }
    
    .consultant-rating {
        color: #ffc107;
        font-size: 14px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
    }
    
    .consultant-rating span {
        color: #666;
        margin-left: 5px;
    }
    
    .consultant-specialties {
        padding: 0 25px 15px;
    }
    
    .consultant-specialties .badge {
        display: inline-block;
        background-color: #e7f1ff;
        color: #4c7bf3;
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 13px;
        margin-right: 8px;
        margin-bottom: 8px;
        font-weight: 500;
    }
    
    .consultant-description {
        padding: 0 25px 20px;
        font-size: 14px;
        color: #666;
        line-height: 1.5;
    }
    
    .consultant-footer {
        padding: 15px 25px;
        border-top: 1px solid #f0f0f5;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .consultant-experience {
        display: flex;
        align-items: center;
        color: #666;
        font-size: 14px;
    }
    
    .consultant-experience i {
        margin-right: 8px;
        color: #4c7bf3;
    }
    
    .view-profile-btn {
        display: inline-flex;
        align-items: center;
        color: #4c7bf3;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .view-profile-btn i {
        margin-left: 5px;
        transition: transform 0.2s;
    }
    
    .view-profile-btn:hover {
        color: #3a5ec8;
    }
    
    .view-profile-btn:hover i {
        transform: translateX(3px);
    }
    
    @media (max-width: 768px) {
        .consultants-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="consultants-container">
    <div class="search-section">
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="consultant-search" placeholder="Search consultants by name, specialty, or location..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        
        <div class="specialty-filters">
            <button class="specialty-filter-btn <?php echo empty($specialty_filter) ? 'active' : ''; ?>" data-specialty="">All</button>
            <?php if (in_array('Express Entry', $specializations)): ?>
                <button class="specialty-filter-btn <?php echo $specialty_filter === 'Express Entry' ? 'active' : ''; ?>" data-specialty="Express Entry">Express Entry</button>
            <?php endif; ?>
            <?php if (in_array('Work Permit', $specializations)): ?>
                <button class="specialty-filter-btn <?php echo $specialty_filter === 'Work Permit' ? 'active' : ''; ?>" data-specialty="Work Permit">Work Permit</button>
            <?php endif; ?>
            <?php if (in_array('Student Visa', $specializations)): ?>
                <button class="specialty-filter-btn <?php echo $specialty_filter === 'Student Visa' ? 'active' : ''; ?>" data-specialty="Student Visa">Student Visa</button>
            <?php endif; ?>
            <?php if (in_array('Family Sponsorship', $specializations)): ?>
                <button class="specialty-filter-btn <?php echo $specialty_filter === 'Family Sponsorship' ? 'active' : ''; ?>" data-specialty="Family Sponsorship">Family Sponsorship</button>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="consultants-grid">
        <?php foreach ($consultants as $consultant): ?>
            <a href="consultant-profile.php?id=<?php echo $consultant['id']; ?>" class="consultant-card">
                <div class="consultant-header">
                    <img src="<?php echo !empty($consultant['profile_image']) ? htmlspecialchars($consultant['profile_image']) : 'assets/images/logo-Visafy-light.png'; ?>" alt="<?php echo htmlspecialchars($consultant['name']); ?>" class="consultant-photo">
                    <div class="consultant-info">
                        <h3><?php echo htmlspecialchars($consultant['name']); ?></h3>
                        <div class="consultant-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($consultant['rating'] !== null && $i <= round($consultant['rating'])): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                            <span>
                                <?php 
                                if ($consultant['rating'] !== null) {
                                    echo number_format($consultant['rating'], 1) . ' (' . $consultant['reviews_count'] . ' reviews)';
                                } else {
                                    echo 'No ratings yet';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="consultant-specialties">
                    <?php 
                    $specs = explode(',', $consultant['specializations']); 
                    $displayedSpecs = 0;
                    foreach ($specs as $spec): 
                        $spec = trim($spec);
                        if (!empty($spec) && $displayedSpecs < 2):
                            $displayedSpecs++;
                    ?>
                        <span class="badge"><?php echo htmlspecialchars($spec); ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <div class="consultant-description">
                    <?php 
                    // Display a shortened version of bio (if available)
                    if (!empty($consultant['bio'])) {
                        echo htmlspecialchars(substr($consultant['bio'], 0, 100) . (strlen($consultant['bio']) > 100 ? '...' : ''));
                    } else {
                        echo 'Experienced immigration consultant helping applicants with their visa applications.';
                    }
                    ?>
                </div>
                
                <div class="consultant-footer">
                    <div class="consultant-experience">
                        <i class="fas fa-briefcase"></i>
                        <span><?php echo htmlspecialchars($consultant['years_experience']); ?> years experience</span>
                    </div>
                    <div class="view-profile-btn">
                        View Profile <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle specialty filter buttons
    const filterButtons = document.querySelectorAll('.specialty-filter-btn');
    const searchInput = document.getElementById('consultant-search');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const specialty = this.getAttribute('data-specialty');
            window.location.href = 'consultant.php' + (specialty ? '?specialty=' + encodeURIComponent(specialty) : '') + 
                                   (searchInput.value ? (specialty ? '&' : '?') + 'search=' + encodeURIComponent(searchInput.value) : '');
        });
    });
    
    // Handle search input
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const specialty = new URLSearchParams(window.location.search).get('specialty') || '';
            window.location.href = 'consultant.php?search=' + encodeURIComponent(this.value) + 
                                   (specialty ? '&specialty=' + encodeURIComponent(specialty) : '');
        }
    });
});
</script>

<?php include('includes/footer.php'); ?>

