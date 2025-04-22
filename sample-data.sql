-- Sample users table data (needed for foreign key relationships)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `user_type`, `status`, `created_at`) VALUES
(10, 'John Doe', 'john.doe@visafy.com', '$2y$10$ExampleHashedPasswordForJohnDoe123456789', 'professional', 'active', CURRENT_TIMESTAMP),
(11, 'Sarah Smith', 'sarah.smith@visafy.com', '$2y$10$ExampleHashedPasswordForSarahSmith12345678', 'professional', 'active', CURRENT_TIMESTAMP),
(12, 'Michael Brown', 'michael.brown@visafy.com', '$2y$10$ExampleHashedPasswordForMichaelBrown123456', 'professional', 'active', CURRENT_TIMESTAMP),
(13, 'Emma Wilson', 'emma.wilson@visafy.com', '$2y$10$ExampleHashedPasswordForEmmaWilson12345678', 'professional', 'active', CURRENT_TIMESTAMP),
(14, 'David Johnson', 'david@example.com', '$2y$10$ExampleHashedPasswordForDavidJohnson12345678', 'applicant', 'active', CURRENT_TIMESTAMP),
(15, 'Lisa Chen', 'lisa@example.com', '$2y$10$ExampleHashedPasswordForLisaChen123456789012', 'applicant', 'active', CURRENT_TIMESTAMP),
(16, 'Ahmed Khan', 'ahmed@example.com', '$2y$10$ExampleHashedPasswordForAhmedKhan123456789012', 'applicant', 'active', CURRENT_TIMESTAMP),
(17, 'Maria Rodriguez', 'maria@example.com', '$2y$10$ExampleHashedPasswordForMariaRodriguez12345', 'applicant', 'active', CURRENT_TIMESTAMP);

-- Sample data for professionals table (matches actual database structure)
INSERT INTO `professionals` (`user_id`, `license_number`, `years_experience`, `education`, `specializations`, `bio`, `phone`, `website`, `languages`, `profile_completed`, `rating`, `reviews_count`, `is_verified`, `is_featured`, `availability_status`) VALUES
(10, 'RCIC123456', 8, 'Master of Laws (LLM), University of Toronto', 'Express Entry,Family Sponsorship', 'Experienced immigration consultant with a strong track record in family sponsorship cases.', '+1 (416) 555-1234', 'https://john-doe-immigration.com', 'English,French,Hindi', 1, 4.8, 25, 1, 1, 'available'),
(11, 'RCIC789012', 12, 'Bachelor of Laws (LLB), McGill University', 'Student Visa,Work Permit', 'Specialized in student visa applications and work permit processing.', '+1 (514) 555-5678', 'https://sarah-smith-immigration.com', 'English,Mandarin', 1, 4.9, 32, 1, 0, 'available'),
(12, 'RCIC345678', 5, 'Bachelor of Arts in International Relations, University of British Columbia', 'Business Immigration,Provincial Nominee', 'Expert in business immigration and provincial nominee programs.', '+1 (604) 555-9012', 'https://michael-brown-immigration.com', 'English,Spanish', 1, 4.6, 18, 1, 0, 'available'),
(13, 'RCIC901234', 15, 'Master of Business Administration (MBA), York University', 'Express Entry,Family Sponsorship,Student Visa', 'Comprehensive experience in various immigration pathways.', '+1 (416) 555-3456', 'https://emma-wilson-immigration.com', 'English,French,Arabic', 1, 4.7, 45, 1, 1, 'available');

-- Create consultation_fees table
-- This table is not in the original database-setup.sql but can be added to support different fees per consultation type
CREATE TABLE IF NOT EXISTS `consultation_fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `professional_id` int(11) NOT NULL,
  `consultation_type` enum('video','phone','inperson') NOT NULL,
  `fee` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `professional_consultation_type` (`professional_id`, `consultation_type`),
  CONSTRAINT `consultation_fees_professional_fk` FOREIGN KEY (`professional_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Sample data for consultation_fees table
INSERT INTO `consultation_fees` (`professional_id`, `consultation_type`, `fee`) VALUES
-- John Doe (ID: 10)
(10, 'video', 150.00),
(10, 'phone', 125.00),
(10, 'inperson', 175.00),

-- Sarah Smith (ID: 11)
(11, 'video', 175.00),
(11, 'phone', 150.00),
(11, 'inperson', 200.00),

-- Michael Brown (ID: 12)
(12, 'video', 125.00),
(12, 'phone', 100.00),
(12, 'inperson', 150.00),

-- Emma Wilson (ID: 13)
(13, 'video', 200.00),
(13, 'phone', 175.00),
(13, 'inperson', 225.00);

-- Sample data for time_slots table (matches schema)
INSERT INTO `time_slots` (`professional_id`, `date`, `start_time`, `end_time`, `is_video_available`, `is_phone_available`, `is_inperson_available`, `is_booked`) VALUES
(10, '2024-04-15', '09:00:00', '10:00:00', 1, 1, 1, 0),
(10, '2024-04-15', '10:30:00', '11:30:00', 1, 1, 1, 0),
(10, '2024-04-15', '14:00:00', '15:00:00', 1, 1, 1, 0),
(11, '2024-04-15', '09:00:00', '10:00:00', 1, 1, 1, 0),
(11, '2024-04-15', '11:00:00', '12:00:00', 1, 1, 1, 0),
(12, '2024-04-15', '13:00:00', '14:00:00', 1, 1, 1, 0),
(12, '2024-04-15', '15:00:00', '16:00:00', 1, 1, 1, 0),
(13, '2024-04-15', '10:00:00', '11:00:00', 1, 1, 1, 0),
(13, '2024-04-15', '14:00:00', '15:00:00', 1, 1, 1, 0);

-- Sample data for consultant_availability table (matches schema)
INSERT INTO `consultant_availability` (`professional_id`, `date`, `is_video_available`, `is_phone_available`, `is_inperson_available`) VALUES
(10, '2024-04-15', 1, 1, 1),
(11, '2024-04-15', 1, 1, 1),
(12, '2024-04-15', 1, 1, 1),
(13, '2024-04-15', 1, 1, 1);

-- Sample data for bookings table (matches schema)
INSERT INTO `bookings` (`professional_id`, `client_id`, `time_slot_id`, `consultation_type`, `status`, `price`) VALUES
(10, 14, 1, 'video', 'completed', 150.00),
(11, 15, 4, 'phone', 'completed', 175.00),
(12, 16, 6, 'inperson', 'pending', 125.00),
(13, 17, 8, 'video', 'confirmed', 200.00);

-- Sample data for case_applications table (matches schema)
INSERT INTO `case_applications` (`client_id`, `professional_id`, `visa_type_id`, `reference_number`, `status`, `notes`) VALUES
(14, 10, 1, 'EE2024001', 'in_progress', 'Initial assessment completed'),
(15, 11, 3, 'ST2024001', 'pending_documents', 'Waiting for IELTS results'),
(16, 12, 5, 'BI2024001', 'review', 'Business plan under review'),
(17, 13, 2, 'FS2024001', 'new', 'New family sponsorship case');

-- Sample data for documents table (matches schema)
INSERT INTO `documents` (`case_id`, `client_id`, `professional_id`, `document_type_id`, `name`, `file_path`, `description`) VALUES
(1, 14, 10, 1, 'Passport Copy', 'uploads/documents/passport_ee2024001.pdf', 'Valid passport copy'),
(1, 14, 10, 2, 'Education Certificate', 'uploads/documents/education_ee2024001.pdf', 'Bachelor degree certificate'),
(2, 15, 11, 3, 'Employment Letter', 'uploads/documents/employment_st2024001.pdf', 'Current employment verification'),
(3, 16, 12, 4, 'Business Plan', 'uploads/documents/business_bi2024001.pdf', 'Detailed business proposal');

-- Sample data for case_notes table (matches schema)
INSERT INTO `case_notes` (`case_id`, `user_id`, `user_type`, `content`, `is_private`) VALUES
(1, 10, 'professional', 'Client has strong language scores and work experience', 0),
(1, 14, 'client', 'Submitted all required documents', 0),
(2, 11, 'professional', 'Waiting for IELTS results to proceed', 0),
(3, 12, 'professional', 'Business plan needs more financial projections', 1);

-- Sample data for notifications table (matches schema)
INSERT INTO `notifications` (`user_id`, `title`, `message`, `is_read`) VALUES
(14, 'Document Uploaded', 'Your passport copy has been uploaded successfully', 0),
(15, 'Appointment Reminder', 'Your consultation is scheduled for tomorrow at 10:00 AM', 0),
(16, 'Case Update', 'Your business plan is under review', 0),
(17, 'New Message', 'You have received a new message from your consultant', 0); 