-- =====================================================================
--  Barangay Clearance Online System
--  Schema + synthetic demo data
-- =====================================================================
--  Target: MySQL 5.7+ / MariaDB 10.4+
--
--  This dump contains SCHEMA ONLY plus a small set of OBVIOUSLY FAKE
--  demo rows so the app is browsable after a fresh install.
--
--  It deliberately contains NO real personal information. No real
--  names, no real email addresses, no real phone numbers, no real
--  addresses, and no real password hashes from any live deployment.
--
--  Import with:
--      mysql -u root -e "CREATE DATABASE barangay_clearance CHARACTER SET utf8mb4;"
--      mysql -u root barangay_clearance < database/schema.sql
--
--  Demo accounts (see README.md):
--      admin@example.com      / demo1234      (user_id 1 = admin)
--      juan.delacruz@example.com / resident1234
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
--  Table: users
--  One row per registered account. Admin is user_id = 1 (see the
--  "Authorization model" note in docs/system-guide.md).
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(100) NOT NULL,
  `middlename` varchar(255) NOT NULL,
  `lastname` varchar(100) NOT NULL,
  `namesuffix` enum('Jr.','Sr.','I','II','III','IV','V') DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_created` date NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`user_id`, `firstname`, `middlename`, `lastname`, `namesuffix`, `email`, `password`, `date_created`) VALUES
(1, 'Demo', 'Admin', 'Account', NULL, 'admin@example.com', '$2y$10$pymuLKIZkwrS2MxMI8rrvOHu.p1VZoVh6DC5UdyzB3t3jmiD.Z1C6', '2025-01-11'),
(2, 'Juan', 'Santos', 'Dela Cruz', NULL, 'juan.delacruz@example.com', '$2y$10$qXcZQC9rpbF/rgVC0nLEheYZIHSfRg/pWDwQgP8kQfztMFhzKCTO.', '2025-01-15'),
(3, 'Maria', 'Reyes', 'Santos', NULL, 'maria.santos@example.com', '$2y$10$qXcZQC9rpbF/rgVC0nLEheYZIHSfRg/pWDwQgP8kQfztMFhzKCTO.', '2025-02-03'),
(4, 'Pedro', 'Lim', 'Bautista', 'Jr.', 'pedro.bautista@example.com', '$2y$10$qXcZQC9rpbF/rgVC0nLEheYZIHSfRg/pWDwQgP8kQfztMFhzKCTO.', '2025-03-20');

-- ---------------------------------------------------------------------
--  Table: clearance_request
--  Individual barangay clearance applications.
--  NOTE: `status` is varchar(255) here but enum in `business_request`.
--        That inconsistency is tracked in docs/security-audit.md.
-- ---------------------------------------------------------------------
CREATE TABLE `clearance_request` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `name_suffix` varchar(10) DEFAULT NULL,
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `date_submitted` date NOT NULL,
  `age` int(12) DEFAULT NULL,
  `sex` varchar(100) NOT NULL,
  `civil_status` enum('Single','Married','Widowed','Divorced','Separated','Annulled') NOT NULL,
  `birth_date` date NOT NULL,
  `birth_place` varchar(100) NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `since_when` varchar(255) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `additional_info` text DEFAULT NULL,
  `applicant_photo` varchar(255) NOT NULL,
  `valid_id_photo` varchar(255) NOT NULL,
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_clearance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `clearance_request`
(`id`, `user_id`, `first_name`, `middle_name`, `last_name`, `name_suffix`, `contact`, `address`, `date_submitted`, `age`, `sex`, `civil_status`, `birth_date`, `birth_place`, `nationality`, `since_when`, `purpose`, `additional_info`, `applicant_photo`, `valid_id_photo`, `status`) VALUES
(1, 2, 'Juan', 'Santos', 'Dela Cruz', '', '09171234567', '12 Sample Street, Barangay Santo Cristo, Quezon City', '2025-04-01', 28, 'Male', 'Single', '1997-04-01', 'Quezon City', 'Filipino', '1997-04', 'Employment', '', 'uploads/demo_applicant_1.jpg', 'uploads/demo_validid_1.jpg', 'Approved'),
(2, 3, 'Maria', 'Reyes', 'Santos', '', '09281234567', '45 Demo Avenue, Barangay Santo Cristo, Quezon City', '2025-04-05', 34, 'Female', 'Married', '1991-04-05', 'Quezon City', 'Filipino', '1991-04', 'School Requirement', '', 'uploads/demo_applicant_2.jpg', 'uploads/demo_validid_2.jpg', 'For Signature'),
(3, 4, 'Pedro', 'Lim', 'Bautista', 'Jr.', '09391234567', '78 Placeholder Road, Barangay Santo Cristo, Quezon City', '2025-04-10', 41, 'Male', 'Single', '1984-04-10', 'Quezon City', 'Filipino', '1984-04', 'Residency', 'Demo row only.', 'uploads/demo_applicant_3.jpg', 'uploads/demo_validid_3.jpg', NULL);

-- ---------------------------------------------------------------------
--  Table: business_request
--  Business clearance / permit applications.
-- ---------------------------------------------------------------------
CREATE TABLE `business_request` (
  `form_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `application_type` enum('New','Renewal') NOT NULL,
  `date_submitted` date NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_address` varchar(255) NOT NULL,
  `business_telephone` varchar(50) NOT NULL,
  `owner_name` varchar(255) NOT NULL,
  `owner_contact` varchar(50) NOT NULL,
  `owner_address` varchar(255) NOT NULL,
  `kind_of_business` varchar(255) NOT NULL,
  `dti_sec_reg_no` varchar(100) NOT NULL,
  `ctc_no` varchar(100) NOT NULL,
  `issued_on` date NOT NULL,
  `issued_at` varchar(255) NOT NULL,
  `tin_no` varchar(100) NOT NULL,
  `additional_info` varchar(255) NOT NULL,
  `dti_sec_registration` varchar(255) NOT NULL,
  `lease_of_contract` varchar(255) NOT NULL,
  `land_title` varchar(255) DEFAULT NULL,
  `old_barangay_clearance` varchar(255) DEFAULT NULL,
  `old_mayors_permit` varchar(255) DEFAULT NULL,
  `status` enum('For Signature','Approved','Rejected') DEFAULT NULL,
  PRIMARY KEY (`form_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_business_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
    ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `business_request`
(`form_id`, `user_id`, `application_type`, `date_submitted`, `business_name`, `business_address`, `business_telephone`, `owner_name`, `owner_contact`, `owner_address`, `kind_of_business`, `dti_sec_reg_no`, `ctc_no`, `issued_on`, `issued_at`, `tin_no`, `additional_info`, `dti_sec_registration`, `lease_of_contract`, `land_title`, `old_barangay_clearance`, `old_mayors_permit`, `status`) VALUES
(1, 2, 'New', '2025-04-02', 'Sample Sari-Sari Store', '12 Sample Street, Barangay Santo Cristo, Quezon City', '09171234567', 'Juan Santos Dela Cruz', '09171234567', '12 Sample Street, Barangay Santo Cristo, Quezon City', 'Retail', '000-0000-000000-00', '0000000', '2025-04-02', 'Barangay Santo Cristo', '000-000-000-000', '', 'uploads/demo_dti_1.jpg', 'uploads/demo_lease_1.jpg', NULL, NULL, NULL, 'Approved'),
(2, 3, 'Renewal', '2025-04-07', 'Demo Carinderia', '45 Demo Avenue, Barangay Santo Cristo, Quezon City', '09281234567', 'Maria Reyes Santos', '09281234567', '45 Demo Avenue, Barangay Santo Cristo, Quezon City', 'Food and Drink', '000-0000-000000-00', '0000000', '2025-04-07', 'Barangay Santo Cristo', '000-000-000-000', '', 'uploads/demo_dti_2.jpg', 'uploads/demo_lease_2.jpg', 'uploads/demo_landtitle_2.jpg', 'uploads/demo_oldclearance_2.jpg', 'uploads/demo_oldpermit_2.jpg', NULL);

-- ---------------------------------------------------------------------
--  Table: email_verifications
--  Pending email-verification codes. A row here BLOCKS login
--  (login.php returns "verify_pending") until verified or cancelled.
-- ---------------------------------------------------------------------
CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `code` varchar(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email` (`email`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (intentionally empty - no pending verifications in a clean install)

-- ---------------------------------------------------------------------
--  Table: ip_cooldowns
--  Rate-limit buckets written after repeated failed logins or
--  failed reset codes. `cooldown_expires_at` is a unix timestamp.
-- ---------------------------------------------------------------------
CREATE TABLE `ip_cooldowns` (
  `ip_address` varchar(45) NOT NULL,
  `cooldown_expires_at` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'login',
  PRIMARY KEY (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (intentionally empty)

-- ---------------------------------------------------------------------
--  Table: outputed_docx
--  One row per generated document, so a clearance can only be
--  generated once. Cascades on request deletion.
-- ---------------------------------------------------------------------
CREATE TABLE `outputed_docx` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `business_id` int(11) DEFAULT NULL,
  `clearance_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `fk_clearance_id` (`clearance_id`),
  KEY `fk_business_id` (`business_id`),
  CONSTRAINT `fk_business_id` FOREIGN KEY (`business_id`) REFERENCES `business_request` (`form_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_clearance_id` FOREIGN KEY (`clearance_id`) REFERENCES `clearance_request` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (intentionally empty - documents are generated at runtime)

COMMIT;
