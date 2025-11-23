<?php
/**
 * check_session.php
 *
 * Checks for an active session and returns user data including is_admin status.
 */

include 'db_connect.php';

if (isset($_SESSION['user_uid'])) {
    // Return the user's details from the session, including admin status
    echo json_encode([
        'success' => true,
        'user' => [
            'uid' => $_SESSION['user_uid'],
            'fullName' => $_SESSION['user_fullName'],
            'course' => $_SESSION['user_course'],
            'year' => $_SESSION['user_year'],
            'is_admin' => $_SESSION['is_admin'] ?? false
        ]
    ]);
} else {
    echo json_encode(['success' => false]);
}
?>
