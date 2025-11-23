<?php
/**
 * db_connect.php
 *
 * Handles the connection to the MySQL database and session start.
 * This file should be included by all other API endpoints.
 */

// --- IMPORTANT: Update with your database credentials ---
$db_host = 'localhost'; // Usually 'localhost' for XAMPP/WAMP
$db_user = 'root';      // Default username for XAMPP/WAMP
$db_pass = '';          // Default password for XAMPP/WAMP is empty
$db_name = 'college_chat';

// Create a new MySQLi connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check if the connection was successful
if ($conn->connect_error) {
    // Terminate script and show error if connection fails
    die("Connection failed: " . $conn->connect_error);
}

// Set headers to ensure responses are treated as JSON
header('Content-Type: application/json');

// Start a new session or resume the existing one
// This is necessary for managing user login state with $_SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>