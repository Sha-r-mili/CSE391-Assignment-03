<?php
/**
 * Car Workshop Appointment System
 * Database Configuration File
 * 
 * Student Name: [YOUR NAME HERE]
 * Student ID: [YOUR ID HERE]
 * Course: CSE 391 - Programming for the Internet
 * Assignment: 3 - PHP and MySQL Project
 */

// Database configuration
// FOR LOCAL TESTING (XAMPP):
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'workshop_appointment');

// FOR INFINITYFREE HOSTING:
// Replace these with your actual InfinityFree database credentials
define('DB_HOST', 'sql000.infinityfree.net');     // Your MySQL hostname
define('DB_USER', 'epiz_XXXXXXXX');               // Your MySQL username
define('DB_PASS', 'your_password_here');          // Your MySQL password
define('DB_NAME', 'epiz_XXXXXXXX_workshop');      // Your database name

/**
 * Create database connection
 * @return mysqli Database connection object
 */
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    return $conn;
}

/**
 * Sanitize user input to prevent XSS attacks
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if mechanic is available on given date
 * @param int $mechanic_id Mechanic ID
 * @param string $date Appointment date
 * @param mysqli $conn Database connection
 * @return bool True if available, false otherwise
 */
function checkMechanicAvailability($mechanic_id, $date, $conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count, m.max_appointments 
                           FROM appointments a 
                           JOIN mechanics m ON a.mechanic_id = m.mechanic_id 
                           WHERE a.mechanic_id = ? AND a.appointment_date = ? AND a.status != 'cancelled'
                           GROUP BY m.max_appointments");
    $stmt->bind_param("is", $mechanic_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        return true; // No appointments yet, mechanic is available
    }
    
    return $row['count'] < $row['max_appointments'];
}

/**
 * Check if client already has appointment on given date
 * @param string $phone Client phone number
 * @param string $date Appointment date
 * @param mysqli $conn Database connection
 * @return bool True if appointment exists, false otherwise
 */
function checkClientAppointment($phone, $date, $conn) {
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE client_phone = ? AND appointment_date = ? AND status != 'cancelled'");
    $stmt->bind_param("ss", $phone, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Get available mechanics for a specific date
 * @param string $date Appointment date
 * @param mysqli $conn Database connection
 * @return mysqli_result Result set of available mechanics
 */
function getAvailableMechanics($date, $conn) {
    $query = "SELECT m.mechanic_id, m.mechanic_name, m.max_appointments,
              COALESCE(COUNT(a.appointment_id), 0) as current_appointments,
              (m.max_appointments - COALESCE(COUNT(a.appointment_id), 0)) as free_slots
              FROM mechanics m
              LEFT JOIN appointments a ON m.mechanic_id = a.mechanic_id 
                  AND a.appointment_date = ? AND a.status != 'cancelled'
              GROUP BY m.mechanic_id, m.mechanic_name, m.max_appointments
              HAVING free_slots > 0
              ORDER BY free_slots DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    return $stmt->get_result();
}

// Start session for admin authentication
session_start();
?>