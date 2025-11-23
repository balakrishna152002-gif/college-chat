<?php
/**
 * login.php
 *
 * Handles user login and includes the is_admin flag in the session.
 */

include 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$login_credential = $data['login_credential'] ?? '';
$password = $data['password'] ?? '';

if (empty($login_credential) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Login credential and password are required.']);
    exit;
}

// Fetch the user, including the is_admin and college columns
$stmt = $conn->prepare("SELECT uid, fullName, email, password, course, year, college, is_admin FROM users WHERE email = ? OR rollNumber = ?");
$stmt->bind_param("ss", $login_credential, $login_credential);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password'])) {
        // Create the session with all user data
        $_SESSION['user_uid'] = $user['uid'];
        $_SESSION['user_fullName'] = $user['fullName'];
        $_SESSION['user_course'] = $user['course'];
        $_SESSION['user_year'] = $user['year'];
        $_SESSION['user_college'] = $user['college']; // NEW: Store user's college in session
        $_SESSION['is_admin'] = (bool)$user['is_admin'];
        
        unset($user['password']);

        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
}

$stmt->close();
$conn->close();
?>
