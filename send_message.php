<?php
include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) { exit; }

$data = json_decode(file_get_contents('php://input'), true);
$currentUserUid = $_SESSION['user_uid'];
$receiverUid = $data['receiver_uid'] ?? '';
$messageText = trim($data['message_text'] ?? '');
$filePath = $data['file_path'] ?? null;
$originalFileName = $data['original_file_name'] ?? null;
$replyToId = $data['reply_to_message_id'] ?? null;

if (empty($receiverUid) || (empty($messageText) && empty($filePath))) { exit; }

$stmt = $conn->prepare("INSERT INTO messages (sender_uid, receiver_uid, message_text, file_path, original_file_name, reply_to_message_id) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssi", $currentUserUid, $receiverUid, $messageText, $filePath, $originalFileName, $replyToId);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => true]);
?>
