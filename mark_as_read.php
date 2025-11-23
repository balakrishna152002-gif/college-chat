<?php
/**
 * mark_as_read.php
 *
 * Marks a conversation as read for the current user by updating their last_read_timestamp.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$currentUserUid = $_SESSION['user_uid'];
$conversationId = $data['conversation_id'] ?? '';

if (empty($conversationId)) {
    echo json_encode(['success' => false, 'message' => 'Conversation ID is required.']);
    exit;
}

// Use INSERT ... ON DUPLICATE KEY UPDATE to either create a new record or update the existing one.
$stmt = $conn->prepare("
    INSERT INTO read_receipts (user_uid, conversation_id, last_read_timestamp) 
    VALUES (?, ?, NOW()) 
    ON DUPLICATE KEY UPDATE last_read_timestamp = NOW()
");

$stmt->bind_param("ss", $currentUserUid, $conversationId);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to mark as read.']);
}

$stmt->close();
$conn->close();
?>
