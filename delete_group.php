<?php
/**
 * delete_group.php
 *
 * Deletes a group. Only the group's admin can perform this action.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$groupUid = $data['group_uid'] ?? '';
$currentUserUid = $_SESSION['user_uid'];

if (empty($groupUid)) {
    echo json_encode(['success' => false, 'message' => 'Group ID is required.']);
    exit;
}

// Verify if the current user is an admin of this specific group
$auth_stmt = $conn->prepare("SELECT role FROM group_members WHERE group_uid = ? AND user_uid = ?");
$auth_stmt->bind_param("ss", $groupUid, $currentUserUid);
$auth_stmt->execute();
$auth_result = $auth_stmt->get_result();
if ($auth_result->num_rows == 0 || $auth_result->fetch_assoc()['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Permission denied. Only group admins can delete the group.']);
    $auth_stmt->close();
    $conn->close();
    exit;
}
$auth_stmt->close();


// If authorized, proceed with deletion
$conn->begin_transaction();
try {
    $conn->prepare("DELETE FROM `group_messages` WHERE group_uid = ?")->execute([$groupUid]);
    $conn->prepare("DELETE FROM `group_members` WHERE group_uid = ?")->execute([$groupUid]);
    $conn->prepare("DELETE FROM `groups` WHERE group_uid = ?")->execute([$groupUid]);
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Group deleted successfully.']);
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to delete group.']);
}

$conn->close();
?>
