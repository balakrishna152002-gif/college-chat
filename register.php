<?php
/**
 * register.php
 *
 * Handles new user registration with course, year, and unique roll number validation.
 */

include 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);

$fullName = $data['fullName'] ?? '';
$rollNumber = $data['rollNumber'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$course = $data['course'] ?? '';
$year = $data['year'] ?? '';
$college = $data['college'] ?? ''; // NEW: Get the college from the request

if (empty($fullName) || empty($rollNumber) || empty($email) || empty($password) || empty($course) || empty($year) || empty($college)) { // UPDATED: Include college in validation
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Validate roll number pattern on the server as well
if (!preg_match('/^\d{2}[a-zA-Z]{3}\d{3}$/', $rollNumber)) {
    echo json_encode(['success' => false, 'message' => 'Invalid Roll Number format. Example: 21jki225']);
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'An account with this email already exists.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Check if roll number already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE rollNumber = ?");
$stmt->bind_param("s", $rollNumber);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'An account with this roll number already exists.']);
    $stmt->close();
    $conn->close();
    exit;
}
$stmt->close();

// Hash the password and generate a UID
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$uid = 'user_' . uniqid();

$stmt = $conn->prepare("INSERT INTO users (uid, fullName, rollNumber, email, password, course, year, college) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
// UPDATED: Added 's' for string type and '$college' variable
$stmt->bind_param("ssssssss", $uid, $fullName, $rollNumber, $email, $hashed_password, $course, $year, $college);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Registration successful. You can now log in.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}

$stmt->close();
$conn->close();
?>
