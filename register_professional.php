<?php
// This file should be included at the end of login.php after successful registration of a professional

// Create professional record for newly registered professional users
function create_professional_profile($user_id, $conn) {
    // Check if this is a professional user
    $stmt = $conn->prepare("SELECT id, user_type, name FROM users WHERE id = ? AND user_type = 'professional'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if a professional record already exists
        $stmt = $conn->prepare("SELECT id FROM professionals WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            // Create a default professional record
            $license_number = "PENDING-" . rand(10000, 99999);
            $years_experience = 0;
            $default_bio = "Professional profile pending completion.";
            $is_verified = 0; // Not verified by default
            
            $stmt = $conn->prepare("INSERT INTO professionals (
                user_id, license_number, years_experience, education, specializations, 
                bio, phone, languages, profile_completed, is_verified
            ) VALUES (?, ?, ?, '', '', ?, '', '', 0, ?)");
            
            $stmt->bind_param("isisi", $user_id, $license_number, $years_experience, $default_bio, $is_verified);
            
            if ($stmt->execute()) {
                // Success - professional record created
                $professional_id = $conn->insert_id;
                
                // Create default consultation fees
                $default_fees = [
                    ['video', 150.00],
                    ['phone', 125.00],
                    ['inperson', 175.00]
                ];
                
                foreach ($default_fees as $fee) {
                    $stmt = $conn->prepare("INSERT INTO consultation_fees (professional_id, consultation_type, fee) VALUES (?, ?, ?)");
                    $stmt->bind_param("isd", $user_id, $fee[0], $fee[1]);
                    $stmt->execute();
                }
                
                return true;
            }
        }
    }
    
    return false;
}

// Add this function call at the end of the login.php registration success section
// After: if ($stmt->execute()) {
//     $success = ($user_type === 'professional') 
//         ? "Registration successful! Your account is pending admin approval."
//         : "Registration successful! You can now login.";
//     
//     // For professional users, create a professional profile
//     if ($user_type === 'professional') {
//         $new_user_id = $conn->insert_id;
//         create_professional_profile($new_user_id, $conn);
//     }
// }
?> 