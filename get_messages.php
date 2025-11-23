<?php
include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) { exit; }

$currentUserUid = $_SESSION['user_uid'];
$selectedUserUid = $_GET['selected_user_uid'] ?? '';

if (empty($selectedUserUid)) { exit; }

$stmt = $conn->prepare("
    SELECT
        m.id, m.sender_uid, m.message_text, m.file_path, m.original_file_name, m.is_deleted, m.timestamp,
        m.reply_to_message_id,
        m.is_pinned, -- ADDED: Now fetching is_pinned status
        replied.message_text as replied_text,
        replied.file_path as replied_file_path,
        replied.original_file_name as replied_original_file_name,
        replied_user.fullName as replied_sender_name
    FROM messages m
    LEFT JOIN messages replied ON m.reply_to_message_id = replied.id
    LEFT JOIN users replied_user ON replied.sender_uid = replied_user.uid
    LEFT JOIN deleted_messages dm ON m.id = dm.message_id AND dm.message_type = 'direct' AND dm.user_uid = ?
    WHERE ((m.sender_uid = ? AND m.receiver_uid = ?) OR (m.sender_uid = ? AND m.receiver_uid = ?))
    AND dm.id IS NULL
    ORDER BY m.is_pinned DESC, m.timestamp ASC -- ORDER BY: Pinned messages first, then by time
");
$stmt->bind_param("sssss", $currentUserUid, $currentUserUid, $selectedUserUid, $selectedUserUid, $currentUserUid);
$stmt->execute();
$result = $stmt->get_result();

$allFetchedMessages = [];
while ($row = $result->fetch_assoc()) {
    $allFetchedMessages[] = $row;
}

$pinnedMessages = [];
$regularMessages = [];

foreach ($allFetchedMessages as $msg) {
    if ($msg['is_pinned'] == 1) {
        $pinnedMessages[] = $msg;
    } else {
        $regularMessages[] = $msg;
    }
}

// Return separate arrays for frontend to handle display
echo json_encode([
    'success' => true,
    'pinned_messages' => $pinnedMessages,
    'messages' => $regularMessages
]);
$stmt->close();
$conn->close();
?>