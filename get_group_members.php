<?php
/**
 * get_group_members.php
 *
 * Fetches all members of a specific group, ordered by role (admin first).
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$groupUid = $_GET['group_uid'] ?? '';

if (empty($groupUid)) {
    echo json_encode(['success' => false, 'message' => 'Group ID is required.']);
    exit;
}

// Optional: You could add a check here to ensure the current user is part of the group
// before allowing them to see the member list.

// Prepare a statement to get all users in the group, along with their details and group role.
$stmt = $conn->prepare("
    SELECT u.fullName, u.course, u.year, gm.role
    FROM users u
    JOIN group_members gm ON u.uid = gm.user_uid
    WHERE gm.group_uid = ?
    ORDER BY gm.role DESC, u.fullName ASC
");
$stmt->bind_param("s", $groupUid);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

echo json_encode(['success' => true, 'members' => $members]);

$stmt->close();
$conn->close();
?>
