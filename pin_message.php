<?php
/**
 * pin_message.php
 *
 * Toggles the pinned status of a message.
 * Now correctly handles both pinning and unpinning and enforces a 3-message limit for ALL chats.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) { exit; }

$data = json_decode(file_get_contents('php://input'), true);
$messageId = $data['message_id'] ?? 0;
$messageType = $data['message_type'] ?? ''; // 'direct' or 'group'
$isPinned = $data['is_pinned'] ?? null; // true to pin, false to unpin
$currentUserUid = $_SESSION['user_uid'];

if (empty($messageId) || empty($messageType) || !isset($isPinned)) {
    echo json_encode(['success' => false, 'message' => 'Message ID, type, and pin status are required.']);
    exit;
}

$tableName = ($messageType === 'direct') ? 'messages' : 'group_messages';
$pinStatus = $isPinned ? 1 : 0; // Convert boolean to integer for database

// --- Enforce 3-message limit for pinning (for both direct and group chats) ---
if ($pinStatus == 1) { // Only count if trying to pin
    $countQuery = "";
    $countParams = [];
    $countParamTypes = "";
    $chatEntityIdentifier = null; // Stores group_uid or the other user's UID for direct chat

    if ($messageType === 'direct') {
        // 1. Get the sender_uid and receiver_uid of the message being pinned
        $stmt_get_uids = $conn->prepare("SELECT sender_uid, receiver_uid FROM messages WHERE id = ?");
        $stmt_get_uids->bind_param("i", $messageId);
        $stmt_get_uids->execute();
        $messageUids = $stmt_get_uids->get_result()->fetch_assoc();
        $stmt_get_uids->close();

        if ($messageUids) {
            $user1 = $messageUids['sender_uid'];
            $user2 = $messageUids['receiver_uid'];

            // 2. Count currently pinned messages in this specific direct conversation
            $countQuery = "SELECT COUNT(*) FROM messages WHERE is_pinned = 1 AND ((sender_uid = ? AND receiver_uid = ?) OR (sender_uid = ? AND receiver_uid = ?))";
            $countParams = [$user1, $user2, $user2, $user1];
            $countParamTypes = "ssss";
        } else {
            echo json_encode(['success' => false, 'message' => 'Direct message not found for limit check.']);
            exit;
        }

    } elseif ($messageType === 'group') {
        // 1. Get the group_uid of the message being pinned
        $stmt_get_group = $conn->prepare("SELECT group_uid FROM group_messages WHERE id = ?");
        $stmt_get_group->bind_param("i", $messageId);
        $stmt_get_group->execute();
        $groupInfo = $stmt_get_group->get_result()->fetch_assoc();
        $stmt_get_group->close();

        if ($groupInfo) {
            $chatEntityIdentifier = $groupInfo['group_uid'];
            // 2. Count currently pinned messages in this group
            $countQuery = "SELECT COUNT(*) FROM group_messages WHERE is_pinned = 1 AND group_uid = ?";
            $countParams = [$chatEntityIdentifier];
            $countParamTypes = "s";
        } else {
            echo json_encode(['success' => false, 'message' => 'Group message not found for limit check.']);
            exit;
        }
    }

    // Execute the count query if a query was prepared
    if (!empty($countQuery)) {
        $countStmt = $conn->prepare($countQuery);
        if (!$countStmt) {
            error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Failed to prepare count query.']);
            exit;
        }
        $countStmt->bind_param($countParamTypes, ...$countParams);
        $countStmt->execute();
        $countResult = $countStmt->get_result()->fetch_row()[0];
        $countStmt->close();

        if ($countResult >= 3) {
            echo json_encode(['success' => false, 'message' => 'You can only pin up to 3 messages in this chat.']);
            exit;
        }
    }
}
// --- End of limit enforcement ---

// Proceed with UPDATE query if allowed or unpinning
$stmt = $conn->prepare("UPDATE `$tableName` SET is_pinned = ? WHERE id = ?");
$stmt->bind_param("ii", $pinStatus, $messageId);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Message pin status updated.']);
} else {
    // This could mean the message doesn't exist or the status is already set.
    // Return false with a specific message to help debugging if it's not success.
    // For general operation, a success implies no change needed or successful.
    echo json_encode(['success' => false, 'message' => 'Failed to update pin status or status already up to date.']);
}

$stmt->close();
$conn->close();
?>