<?php
/**
 * get_groups.php
 *
 * Fetches groups and the current user's role within each group.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$currentUserUid = $_SESSION['user_uid'];

// Fetch groups and the user's role in that group
$stmt = $conn->prepare("
    SELECT 
        g.group_uid, g.group_name, gmem.role as user_role,
        (SELECT COUNT(*) 
         FROM group_messages gm 
         WHERE gm.group_uid = g.group_uid AND gm.sender_uid != ?
         AND gm.timestamp > COALESCE((SELECT rr.last_read_timestamp FROM read_receipts rr WHERE rr.user_uid = ? AND rr.conversation_id = g.group_uid), '1970-01-01'))
        AS unread_count
    FROM `groups` g
    JOIN `group_members` gmem ON g.group_uid = gmem.group_uid
    WHERE gmem.user_uid = ?
    ORDER BY g.group_name ASC
");
$stmt->bind_param("sss", $currentUserUid, $currentUserUid, $currentUserUid);
$stmt->execute();
$result = $stmt->get_result();

$groups = [];
while ($row = $result->fetch_assoc()) {
    $groups[] = $row;
}

echo json_encode(['success' => true, 'groups' => $groups]);

$stmt->close();
$conn->close();
?>
