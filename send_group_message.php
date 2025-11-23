<?php
include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) { exit; }

$data = json_decode(file_get_contents('php://input'), true);
$currentUserUid = $_SESSION['user_uid'];
$groupUid = $data['group_uid'] ?? '';
$messageText = trim($data['message_text'] ?? '');
$filePath = $data['file_path'] ?? null;
$originalFileName = $data['original_file_name'] ?? null;
$replyToMessageId = $data['reply_to_message_id'] ?? null; // Added for reply

if (empty($groupUid) || (empty($messageText) && empty($filePath))) { exit; }

$conn->begin_transaction(); // Start transaction for atomicity

try {
   $stmt = $conn->prepare("INSERT INTO group_messages (group_uid, sender_uid, message_text, file_path, original_file_name, reply_to_message_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $groupUid, $currentUserUid, $messageText, $filePath, $originalFileName, $replyToMessageId);
    $stmt->execute();
    $newMessageId = $stmt->insert_id; // Get the ID of the newly inserted message
    $stmt->close();

    // Handle @mentions
    if (!empty($messageText)) {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $messageText, $matches);
        $mentionedUsernames = array_unique($matches[1]);

        if (!empty($mentionedUsernames)) {
            // Fetch UIDs for the mentioned usernames/rollNumbers/emails
            $placeholders = implode(',', array_fill(0, count($mentionedUsernames), '?'));
            $user_stmt = $conn->prepare("SELECT uid FROM users WHERE fullName IN ($placeholders) OR rollNumber IN ($placeholders) OR email IN ($placeholders)");
            $paramTypes = str_repeat('s', count($mentionedUsernames) * 3);
            $user_stmt->bind_param($paramTypes, ...array_merge($mentionedUsernames, $mentionedUsernames, $mentionedUsernames));
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $mentionedUids = [];
            while ($row = $user_result->fetch_assoc()) {
                $mentionedUids[] = $row['uid'];
            }
            $user_stmt->close();

            if (!empty($mentionedUids)) {
                $mention_stmt = $conn->prepare("INSERT INTO mentions (message_id, message_type, mentioned_user_uid) VALUES (?, 'group', ?)");
            foreach ($mentionedUids as $mUid) {
                $mention_stmt->bind_param("is", $newMessageId, $mUid);
                $mention_stmt->execute();
            }
            $mention_stmt->close();
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    error_log("Error sending group message: " . $exception->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to send message.']);
}

$conn->close();
?>