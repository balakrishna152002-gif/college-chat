<?php
/**
 * remove_group_member.php
 *
 * Removes a specified member from a group.
 * This action can only be performed by a group admin.
 */

include 'db_connect.php';

// Verify user is logged in
if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

// Get data from the frontend request
$data = json_decode(file_get_contents('php://input'), true);
$groupUid = $data['group_uid'] ?? '';
$memberUidToRemove = $data['member_uid'] ?? '';
$currentUserUid = $_SESSION['user_uid'];

// Validate input
if (empty($groupUid) || empty($memberUidToRemove)) {
    echo json_encode(['success' => false, 'message' => 'Group and Member ID are required.']);
    exit;
}

// A group admin cannot remove themselves from the group.
if ($memberUidToRemove === $currentUserUid) {
    echo json_encode(['success' => false, 'message' => 'Group admin cannot remove themselves. To leave, delete the group.']);
    exit;
}

// Security Check: Verify if the current user is an admin of this specific group
$auth_stmt = $conn->prepare("SELECT role FROM group_members WHERE group_uid = ? AND user_uid = ?");
$auth_stmt->bind_param("ss", $groupUid, $currentUserUid);
$auth_stmt->execute();
$auth_result = $auth_stmt->get_result();

if ($auth_result->num_rows == 0 || $auth_result->fetch_assoc()['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only group admins can remove members.']);
    $auth_stmt->close();
    $conn->close();
    exit;
}
$auth_stmt->close();

// If authorized, proceed with removing the member
$remove_stmt = $conn->prepare("DELETE FROM group_members WHERE group_uid = ? AND user_uid = ?");
$remove_stmt->bind_param("ss", $groupUid, $memberUidToRemove);

if ($remove_stmt->execute()) {
    if ($remove_stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Member removed successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Member not found in the group.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove member.']);
}

$remove_stmt->close();
$conn->close();
?>
