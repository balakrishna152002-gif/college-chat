<?php
/**
 * delete_message.php
 *
 * Deletes a message based on the specified scope ('me' or 'everyone').
 * 'everyone' flags the message as deleted, 'me' hides it for the user.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$currentUserUid = $_SESSION['user_uid'];
$messageId = $data['message_id'] ?? 0;
$messageType = $data['message_type'] ?? '';
$deleteScope = $data['scope'] ?? 'me';

if (empty($messageId) || empty($messageType)) {
    echo json_encode(['success' => false, 'message' => 'Message ID and type are required.']);
    exit;
}

$tableName = ($messageType === 'direct') ? 'messages' : 'group_messages';

if ($deleteScope === 'everyone') {
    // For 'everyone', we don't delete the row. We update it to a deleted state.
    // This preserves the message ID but removes the content for all users.
    $stmt = $conn->prepare("
        UPDATE `$tableName` 
        SET message_text = NULL, file_path = NULL, original_file_name = NULL, is_deleted = 1 
        WHERE id = ? AND sender_uid = ?
    ");
    $stmt->bind_param("is", $messageId, $currentUserUid);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Message deleted for everyone.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this message.']);
    }
    $stmt->close();

} else { // 'delete for me'
    // This logic remains the same. It adds a record to hide the message only for the current user.
    $stmt = $conn->prepare("INSERT IGNORE INTO deleted_messages (message_id, message_type, user_uid) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $messageId, $messageType, $currentUserUid);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Message deleted for you.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'An unexpected error occurred.']);
    }
    $stmt->close();
}

$conn->close();
?>
