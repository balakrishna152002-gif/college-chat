<?php
/**
 * typing_status.php
 *
 * Manages the "is typing" indicator status for conversations.
 * This script has two functions based on the request method:
 * POST: To set that the current user is typing to someone.
 * GET: To check if a specific user is typing to the current user.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$currentUserUid = $_SESSION['user_uid'];

// Use a separate session variable for typing to avoid conflicts/locking.
if (!isset($_SESSION['typing_status'])) {
    $_SESSION['typing_status'] = [];
}

// POST request: The current user is reporting their typing status.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    // The recipient of the typing notification.
    $recipientId = $data['recipient_uid'] ?? null;
    $isTyping = (bool)($data['is_typing'] ?? false);

    if ($recipientId) {
        // We store the typing status against the recipient's ID,
        // with the current user (the typer) as the key.
        $_SESSION['typing_status'][$recipientId][$currentUserUid] = $isTyping ? time() : 0;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Recipient ID required.']);
    }
    exit;
}

// GET request: The current user is checking if someone is typing to them.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // The user we are checking on (the one we are chatting with).
    $otherUserId = $_GET['other_user_id'] ?? null;
    $isTyping = false;

    // Check if there is a typing status for the current user from the other user.
    if ($otherUserId && isset($_SESSION['typing_status'][$currentUserUid][$otherUserId])) {
        // A user is considered "typing" if their last activity was within the last 3 seconds.
        if (time() - $_SESSION['typing_status'][$currentUserUid][$otherUserId] < 3) {
            $isTyping = true;
        }
    }

    echo json_encode(['success' => true, 'is_typing' => $isTyping]);
    exit;
}
?>
