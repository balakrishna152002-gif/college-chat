<?php
/**
 * create_group.php
 *
 * Creates a group and assigns the creator as the group admin.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$groupName = trim($data['groupName'] ?? '');
$memberUids = $data['members'] ?? [];
$currentUserUid = $_SESSION['user_uid'];

if (empty($groupName) || empty($memberUids)) {
    echo json_encode(['success' => false, 'message' => 'Group name and at least one member are required.']);
    exit;
}

$groupUid = 'group_' . uniqid();

$conn->begin_transaction();

try {
    // 1. Insert the new group into the `groups` table
    $stmt1 = $conn->prepare("INSERT INTO `groups` (group_uid, group_name, created_by) VALUES (?, ?, ?)");
    $stmt1->bind_param("sss", $groupUid, $groupName, $currentUserUid);
    $stmt1->execute();
    $stmt1->close();

    // 2. Insert the creator as the 'admin'
    $stmt2 = $conn->prepare("INSERT INTO `group_members` (group_uid, user_uid, role) VALUES (?, ?, 'admin')");
    $stmt2->bind_param("ss", $groupUid, $currentUserUid);
    $stmt2->execute();
    $stmt2->close();

    // 3. Insert other members with the 'member' role
    $stmt3 = $conn->prepare("INSERT INTO `group_members` (group_uid, user_uid, role) VALUES (?, ?, 'member')");
    foreach ($memberUids as $memberUid) {
        // Ensure we don't re-insert the creator
        if ($memberUid !== $currentUserUid) {
            $stmt3->bind_param("ss", $groupUid, $memberUid);
            $stmt3->execute();
        }
    }
    $stmt3->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Group created successfully.']);

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Failed to create group.']);
}

$conn->close();
?>
