<?php
include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) { exit; }

$currentUserUid = $_SESSION['user_uid'];
$groupUid = $_GET['group_uid'] ?? '';

if (empty($groupUid)) { exit; }

$stmt = $conn->prepare("
    SELECT
        gm.id, gm.sender_uid, u.fullName as sender_name, gm.message_text, gm.file_path, gm.original_file_name, gm.is_deleted, gm.timestamp,
        gm.reply_to_message_id,
        gm.is_pinned, -- ADDED: Now fetching is_pinned status
        replied.message_text as replied_text,
        replied.file_path as replied_file_path,
        replied.original_file_name as replied_original_file_name,
        replied_user.fullName as replied_sender_name
    FROM `group_messages` gm
    JOIN `users` u ON gm.sender_uid = u.uid
    LEFT JOIN `group_messages` replied ON gm.reply_to_message_id = replied.id
    LEFT JOIN `users` replied_user ON replied.sender_uid = replied_user.uid
    LEFT JOIN `deleted_messages` dm ON gm.id = dm.message_id AND dm.message_type = 'group' AND dm.user_uid = ?
    WHERE gm.group_uid = ?
    AND dm.id IS NULL
    ORDER BY gm.is_pinned DESC, gm.timestamp ASC -- ORDER BY: Pinned messages first, then by time
");
$stmt->bind_param("ss", $currentUserUid, $groupUid);
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