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

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get professional data if exists
$stmt = $conn->prepare("SELECT * FROM professionals WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile_exists = $result->num_rows > 0;
$profile_data = $profile_exists ? $result->fetch_assoc() : null;
$stmt->close();

// Get predefined languages
$predefined_languages = [
    'English', 'French', 'Spanish', 'Mandarin', 'Hindi', 'Arabic', 
    'Portuguese', 'Bengali', 'Russian', 'Japanese', 'Punjabi', 
    'German', 'Korean', 'Turkish', 'Tamil', 'Italian', 'Urdu'
];

// Get predefined specializations
$predefined_specializations = [
    'Express Entry', 'Family Sponsorship', 'Student Visa', 'Work Permit',
    'Business Immigration', 'Provincial Nominee', 'Skilled Worker', 
    'Startup Visa', 'Refugee Claims', 'Citizenship Applications',
    'Humanitarian Cases', 'Appeals & Tribunals', 'LMIA Applications'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    if (empty($_POST['license_number']) || empty($_POST['years_experience']) || empty($_POST['phone'])) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Sanitize inputs
        $license_number = htmlspecialchars(trim($_POST['license_number']));
        $years_experience = (int)$_POST['years_experience'];
        $education = htmlspecialchars(trim($_POST['education']));
        
        // Handle specializations from tag input
        $specializations = isset($_POST['specialization_tags']) ? $_POST['specialization_tags'] : [];
        $specializations = implode(', ', array_map('trim', $specializations));
        
        $bio = htmlspecialchars(trim($_POST['bio']));
        $phone = htmlspecialchars(trim($_POST['phone']));
        $website = htmlspecialchars(trim($_POST['website']));
        
        // Handle languages from tag input
        $languages = isset($_POST['language_tags']) ? $_POST['language_tags'] : [];
        $languages = implode(', ', array_map('trim', $languages));
        
        $availability_status = htmlspecialchars(trim($_POST['availability_status']));
        $profile_completed = 1;
        
        // Handle profile image upload
        $profile_image = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = 'professional_' . $user_id . '_' . time() . '.' . $filetype;
                $upload_path = '../../uploads/professionals/' . $new_filename;
                
                // Make sure directory exists
                if (!file_exists('../../uploads/professionals/')) {
                    mkdir('../../uploads/professionals/', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $profile_image = 'uploads/professionals/' . $new_filename;
                }
            }
        }
        
        if ($profile_exists) {
            // Update existing profile
            $sql = "UPDATE professionals SET 
                    license_number = ?, 
                    years_experience = ?, 
                    education = ?, 
                    specializations = ?, 
                    bio = ?, 
                    phone = ?, 
                    website = ?, 
                    languages = ?, 
                    profile_completed = ?, 
                    availability_status = ?";
            
            $params = [$license_number, $years_experience, $education, $specializations, $bio, $phone, $website, $languages, $profile_completed, $availability_status];
            $types = "siisssssis";
            
            if ($profile_image) {
                // Check if profile_image column exists
                $column_check = $conn->query("SHOW COLUMNS FROM `professionals` LIKE 'profile_image'");
                if($column_check->num_rows > 0) {
                    $sql .= ", profile_image = ?";
                    $params[] = $profile_image;
                    $types .= "s";
                }
            }
            
            $sql .= " WHERE user_id = ?";
            $params[] = $user_id;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
        } else {
            // Create new profile
            $sql = "INSERT INTO professionals (
                    user_id, license_number, years_experience, education, 
                    specializations, bio, phone, website, languages, 
                    profile_completed, availability_status";
            
            $params = [$user_id, $license_number, $years_experience, $education, 
                       $specializations, $bio, $phone, $website, $languages, 
                       $profile_completed, $availability_status];
            $types = "isiisssssis";
            
            if ($profile_image) {
                // Check if profile_image column exists
                $column_check = $conn->query("SHOW COLUMNS FROM `professionals` LIKE 'profile_image'");
                if($column_check->num_rows > 0) {
                    $sql .= ", profile_image";
                    $params[] = $profile_image;
                    $types .= "s";
                }
            }
            
            $sql .= ") VALUES (" . str_repeat("?,", count($params) - 1) . "?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
        }
        
        if ($stmt->execute()) {
            $success_message = "Profile saved successfully!";
            
            // Refresh profile data
            $stmt = $conn->prepare("SELECT * FROM professionals WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $profile_exists = $result->num_rows > 0;
            $profile_data = $profile_exists ? $result->fetch_assoc() : null;
            $stmt->close();
        } else {
            $error_message = "Error saving profile: " . $stmt->error;
        }
    }
}

// Page title
$page_title = "Professional Profile | Visafy";
include '../includes/header.php';
?>

<style>
.tag-container {
    display: flex;
    flex-wrap: wrap;
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    gap: 8px;
    min-height: 40px;
    background-color: #fff;
}

.tag {
    display: flex;
    align-items: center;
    background-color: #e7f3ff;
    border: 1px solid #0d6efd;
    color: #0d6efd;
    border-radius: 16px;
    padding: 4px 12px;
    font-size: 14px;
}

.tag .remove-tag {
    margin-left: 6px;
    cursor: pointer;
    font-weight: bold;
}

.tag-suggestions {
    display: none;
    position: absolute;
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    width: 100%;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.tag-suggestion {
    padding: 8px 16px;
    cursor: pointer;
}

.tag-suggestion:hover {
    background-color: #f8f9fa;
}

.add-tag-button {
    display: flex;
    align-items: center;
    color: #6c757d;
    padding: 4px 8px;
    background: none;
    border: 1px dashed #ced4da;
    border-radius: 16px;
    cursor: pointer;
    font-size: 14px;
}

.add-tag-button i {
    margin-right: 4px;
}

.profile-image-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    margin-bottom: 16px;
    border: 2px solid #e0e0e5;
}
</style>

<div class="container-fluid">
    <div class="row">
        <?php include '../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Professional Profile</h1>
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

            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Your Professional Profile</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <?php if ($profile_exists && !empty($profile_data['profile_image'])): ?>
                                    <img id="profile-preview" src="<?php echo '../../' . $profile_data['profile_image']; ?>" alt="Profile Image" class="profile-image-preview">
                                <?php else: ?>
                                    <img id="profile-preview" src="../../assets/images/profile-placeholder.png" alt="Profile Placeholder" class="profile-image-preview">
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Profile Image</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*">
                                    <div class="form-text">Recommended size: 300x300 pixels</div>
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($user_data['name']); ?>" disabled>
                                        <div class="form-text">To change your name, update your account settings</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="license_number" class="form-label">License Number*</label>
                                        <input type="text" class="form-control" id="license_number" name="license_number" required value="<?php echo $profile_exists ? htmlspecialchars($profile_data['license_number']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="years_experience" class="form-label">Years of Experience*</label>
                                        <input type="number" class="form-control" id="years_experience" name="years_experience" min="0" max="50" required value="<?php echo $profile_exists ? htmlspecialchars($profile_data['years_experience']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number*</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required value="<?php echo $profile_exists ? htmlspecialchars($profile_data['phone']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="website" class="form-label">Website</label>
                                        <input type="url" class="form-control" id="website" name="website" value="<?php echo $profile_exists ? htmlspecialchars($profile_data['website']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="education" class="form-label">Education</label>
                            <textarea class="form-control" id="education" name="education" rows="2"><?php echo $profile_exists ? htmlspecialchars($profile_data['education']) : ''; ?></textarea>
                            <div class="form-text">List your educational qualifications (degrees, universities, etc.)</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Specializations</label>
                            <div class="position-relative">
                                <div id="specialization-container" class="tag-container">
                                    <?php 
                                    if ($profile_exists && !empty($profile_data['specializations'])) {
                                        $specializations = explode(',', $profile_data['specializations']);
                                        foreach ($specializations as $spec) {
                                            $spec = trim($spec);
                                            if (!empty($spec)) {
                                                echo '<div class="tag"><span>' . htmlspecialchars($spec) . '</span><span class="remove-tag">×</span><input type="hidden" name="specialization_tags[]" value="' . htmlspecialchars($spec) . '"></div>';
                                            }
                                        }
                                    }
                                    ?>
                                    <button type="button" id="add-specialization" class="add-tag-button"><i class="bi bi-plus-circle"></i> Add</button>
                                </div>
                                <div id="specialization-suggestions" class="tag-suggestions">
                                    <?php foreach ($predefined_specializations as $spec): ?>
                                        <div class="tag-suggestion"><?php echo htmlspecialchars($spec); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-text">Select or enter your areas of expertise</div>
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Professional Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo $profile_exists ? htmlspecialchars($profile_data['bio']) : ''; ?></textarea>
                            <div class="form-text">Tell potential clients about yourself and your experience (up to 500 words)</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Languages Spoken</label>
                            <div class="position-relative">
                                <div id="language-container" class="tag-container">
                                    <?php 
                                    if ($profile_exists && !empty($profile_data['languages'])) {
                                        $languages = explode(',', $profile_data['languages']);
                                        foreach ($languages as $lang) {
                                            $lang = trim($lang);
                                            if (!empty($lang)) {
                                                echo '<div class="tag"><span>' . htmlspecialchars($lang) . '</span><span class="remove-tag">×</span><input type="hidden" name="language_tags[]" value="' . htmlspecialchars($lang) . '"></div>';
                                            }
                                        }
                                    }
                                    ?>
                                    <button type="button" id="add-language" class="add-tag-button"><i class="bi bi-plus-circle"></i> Add</button>
                                </div>
                                <div id="language-suggestions" class="tag-suggestions">
                                    <?php foreach ($predefined_languages as $language): ?>
                                        <div class="tag-suggestion"><?php echo htmlspecialchars($language); ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-text">Select or enter languages you speak</div>
                        </div>

                        <div class="mb-3">
                            <label for="availability_status" class="form-label">Availability Status</label>
                            <select class="form-select" id="availability_status" name="availability_status">
                                <option value="available" <?php echo ($profile_exists && $profile_data['availability_status'] == 'available') ? 'selected' : ''; ?>>Available (Accepting new clients)</option>
                                <option value="busy" <?php echo ($profile_exists && $profile_data['availability_status'] == 'busy') ? 'selected' : ''; ?>>Busy (Limited availability)</option>
                                <option value="unavailable" <?php echo ($profile_exists && $profile_data['availability_status'] == 'unavailable') ? 'selected' : ''; ?>>Unavailable (Not accepting new clients)</option>
                            </select>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($profile_exists): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Verification Status</h5>
                </div>
                <div class="card-body">
                    <?php if ($profile_data['is_verified']): ?>
                        <div class="alert alert-success mb-0">
                            <i class="bi bi-check-circle-fill me-2"></i> Your profile has been verified by Visafy. Clients will see a verified badge on your profile.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> Your profile is pending verification. Our team will review your credentials and update your status.
                        </div>
                        <p>To expedite verification, please ensure your license number is correct and consider uploading supporting documents.</p>
                        <a href="documents.php" class="btn btn-outline-primary">Upload Verification Documents</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
// Profile image preview
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('profile-preview').src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Tag input functionality for Specializations
function initTagInput(containerId, suggestionsId, addButtonId, predefinedOptions) {
    const container = document.getElementById(containerId);
    const suggestions = document.getElementById(suggestionsId);
    const addButton = document.getElementById(addButtonId);
    
    // Show suggestions when clicking the add button
    addButton.addEventListener('click', function() {
        suggestions.style.display = 'block';
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!container.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.style.display = 'none';
        }
    });
    
    // Add a tag when clicking a suggestion
    Array.from(suggestions.getElementsByClassName('tag-suggestion')).forEach(suggestion => {
        suggestion.addEventListener('click', function() {
            const tagText = this.textContent.trim();
            const existingTags = Array.from(container.getElementsByClassName('tag'))
                .map(tag => tag.querySelector('span').textContent.trim());
            
            if (!existingTags.includes(tagText)) {
                addTag(container, tagText, addButton);
            }
            suggestions.style.display = 'none';
        });
    });
    
    // Remove tag when clicking the X
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-tag')) {
            e.target.parentElement.remove();
        }
    });
    
    // Allow adding custom tags by pressing Enter in the input field
    container.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && document.activeElement.classList.contains('tag-input')) {
            e.preventDefault();
            const input = document.activeElement;
            const tagText = input.value.trim();
            
            if (tagText) {
                const parent = input.parentElement;
                parent.remove();
                addTag(container, tagText, addButton);
            }
        }
    });
}

function addTag(container, text, addButton) {
    // Create tag element
    const tag = document.createElement('div');
    tag.className = 'tag';
    
    // Create text span
    const tagText = document.createElement('span');
    tagText.textContent = text;
    
    // Create remove button
    const removeBtn = document.createElement('span');
    removeBtn.className = 'remove-tag';
    removeBtn.textContent = '×';
    
    // Create hidden input to store value
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = container.id === 'specialization-container' ? 'specialization_tags[]' : 'language_tags[]';
    hiddenInput.value = text;
    
    // Append elements
    tag.appendChild(tagText);
    tag.appendChild(removeBtn);
    tag.appendChild(hiddenInput);
    
    // Insert before add button
    container.insertBefore(tag, addButton);
}

// Initialize tag inputs
document.addEventListener('DOMContentLoaded', function() {
    initTagInput('specialization-container', 'specialization-suggestions', 'add-specialization');
    initTagInput('language-container', 'language-suggestions', 'add-language');
});
</script>

<?php include '../includes/footer.php'; ?> 