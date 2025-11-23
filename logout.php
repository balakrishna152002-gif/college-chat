<?php
/**
 * logout.php
 *
 * Handles user logout by destroying the current session.
 */

include 'db_connect.php';

// Destroy all data registered to a session
session_destroy();

echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
?>
