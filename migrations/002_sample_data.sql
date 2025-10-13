-- SafeKeep Sample Data
-- Insert demo users, categories, posts, and announcements for testing

USE `safekeep_db`;

-- Insert default categories
INSERT INTO `categories` (`name`, `description`, `icon`, `sort_order`) VALUES
('Electronics', 'Phones, laptops, tablets, chargers, headphones', 'fas fa-laptop', 1),
('Clothing', 'Jackets, sweaters, hats, gloves, shoes', 'fas fa-tshirt', 2),
('Books & Stationery', 'Textbooks, notebooks, pens, calculators', 'fas fa-book', 3),
('Personal Items', 'Wallets, keys, jewelry, glasses', 'fas fa-key', 4),
('Sports Equipment', 'Balls, rackets, gym clothes, water bottles', 'fas fa-football-ball', 5),
('Bags & Backpacks', 'School bags, purses, lunch boxes', 'fas fa-shopping-bag', 6),
('ID & Cards', 'Student IDs, credit cards, library cards', 'fas fa-id-card', 7),
('Other', 'Items that don\'t fit other categories', 'fas fa-question', 8);

-- Insert demo users
-- Password for all users is: SafeKeep2024!
-- This hash is for demonstration only - in production, use proper password hashing
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `is_active`, `email_verified`) VALUES
('Admin User', 'admin@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 1),
('John Smith', 'john.smith@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1, 1),
('Sarah Johnson', 'sarah.johnson@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1, 1),
('Mike Davis', 'mike.davis@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 0, 0),
('Emma Wilson', 'emma.wilson@school.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 1, 1);

-- Insert sample posts
INSERT INTO `posts` (`user_id`, `title`, `description`, `type`, `category`, `date_lost_found`, `location`, `status`, `approved_by`, `approved_at`) VALUES
(2, 'Lost iPhone 14 Pro', 'Black iPhone 14 Pro in a blue case. Lost near the library on Tuesday afternoon. Has a small crack on the screen. Very important - contains family photos!', 'lost', 'Electronics', '2024-10-10', 'Main Library - 2nd Floor', 'approved', 1, '2024-10-11 09:15:00'),
(3, 'Found Red Backpack', 'Found a red Jansport backpack in the student center. Contains textbooks and notebooks. No ID visible inside. Turned in to lost and found desk.', 'found', 'Bags & Backpacks', '2024-10-09', 'Student Center - Main Hall', 'approved', 1, '2024-10-10 14:30:00'),
(2, 'Lost Car Keys', 'Toyota key fob with a blue lanyard and gym membership card attached. Lost somewhere between parking lot C and the engineering building.', 'lost', 'Personal Items', '2024-10-11', 'Parking Lot C / Engineering Building', 'pending', NULL, NULL),
(5, 'Found Calculus Textbook', 'Found "Calculus: Early Transcendentals" textbook in classroom B-205. Has some highlighting and notes. Name "M. Rodriguez" written inside cover.', 'found', 'Books & Stationery', '2024-10-08', 'Building B - Room 205', 'approved', 1, '2024-10-09 11:45:00'),
(3, 'Lost Wireless Headphones', 'Sony WH-1000XM4 black wireless headphones. Lost in the gym locker room after basketball practice. In original carrying case.', 'lost', 'Electronics', '2024-10-07', 'Sports Complex - Locker Room', 'rejected', 1, '2024-10-08 16:20:00'),
(4, 'Found Student ID Card', 'Found student ID for "Alex Chen" near the cafeteria entrance. Card appears to be from this semester.', 'found', 'ID & Cards', '2024-10-12', 'Cafeteria - Main Entrance', 'pending', NULL, NULL);

-- Update some posts to show they've been resolved
UPDATE `posts` SET `is_resolved` = 1, `resolved_at` = '2024-10-12 10:30:00' WHERE `id` = 2;
UPDATE `posts` SET `is_resolved` = 1, `resolved_at` = '2024-10-11 15:45:00' WHERE `id` = 4;

-- Insert sample announcements
INSERT INTO `announcements` (`title`, `body`, `created_by`, `expires_at`) VALUES
('Lost & Found Policy Update', 'Reminder: All found items must be reported within 24 hours of discovery. Items not claimed within 30 days will be donated to charity. Please check the physical lost & found location in the Student Services office.', 1, '2024-11-15 23:59:59'),
('Extended Hours During Finals', 'The SafeKeep system and physical lost & found desk will have extended hours during finals week (Dec 10-17). Online submissions available 24/7, desk hours: Mon-Fri 7AM-9PM, Sat-Sun 10AM-6PM.', 1, '2024-12-20 23:59:59'),
('New Categories Added', 'We\'ve added new categories for better organization: "Lab Equipment" and "Art Supplies". Please recategorize your posts if needed. Contact admin for assistance.', 1, NULL);

-- Insert sample contact logs (simulating contact attempts)
INSERT INTO `contact_logs` (`post_id`, `sender_name`, `sender_email`, `sender_user_id`, `message`, `ip_address`, `email_sent`) VALUES
(1, 'Alex Thompson', 'alex.thompson@school.edu', NULL, 'Hi! I think this might be my phone. I lost it on Tuesday around the same time. Can we arrange to meet so I can verify it\'s mine?', '192.168.1.100', 1),
(2, 'Maria Rodriguez', 'maria.rodriguez@school.edu', NULL, 'This is my backpack! Thank you so much for finding it. I can pick it up anytime today. My student ID is in the front pocket.', '192.168.1.101', 1);

-- Insert sample audit logs (tracking admin actions)
INSERT INTO `audit_logs` (`admin_id`, `action`, `target_table`, `target_id`, `new_values`, `ip_address`) VALUES
(1, 'approve_post', 'posts', 1, '{"status":"approved","approved_by":1}', '192.168.1.50'),
(1, 'approve_post', 'posts', 2, '{"status":"approved","approved_by":1}', '192.168.1.50'),
(1, 'reject_post', 'posts', 5, '{"status":"rejected","rejection_reason":"Insufficient description"}', '192.168.1.50'),
(1, 'activate_user', 'users', 3, '{"is_active":1}', '192.168.1.50');