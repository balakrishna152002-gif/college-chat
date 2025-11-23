<?php
/**
 * get_users.php
 *
 * Fetches users and now includes a count of unread messages for each user.
 */

include 'db_connect.php';

if (!isset($_SESSION['user_uid'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$sortBy = $_GET['sortBy'] ?? 'name';
$searchTerm = $_GET['search'] ?? '';
$currentUserUid = $_SESSION['user_uid'];

// NEW: Get the current user's college from the session
$currentUserCollege = $_SESSION['user_college'] ?? null;

// If for some reason college isn't in session, or it's null, we can exit or handle it.
// For now, let's assume it's always set after login/registration.
if (empty($currentUserCollege)) {
    echo json_encode(['success' => false, 'message' => 'Current user college not found in session.']);
    exit;
}

// Start with basic filtering: exclude current user and filter by college
$whereClause = "WHERE u.uid != ? AND u.college = ?";
$params = [$currentUserUid, $currentUserCollege];
$paramTypes = "ss"; // For currentUserUid (s) and currentUserCollege (s)

if (!empty($searchTerm)) {
    $whereClause .= " AND (u.fullName LIKE ? OR u.rollNumber LIKE ?)";
    $likeTerm = "%" . $searchTerm . "%";
    $params[] = $likeTerm;
    $params[] = $likeTerm;
    $paramTypes .= "ss";
}

switch ($sortBy) {
    case 'year':
        $orderByClause = "ORDER BY FIELD(year, '1', '2', '3', '4', 'Alumni'), fullName ASC";
        break;
    case 'course':
        $orderByClause = "ORDER BY course ASC, FIELD(year, '1', '2', '3', '4', 'Alumni'), fullName ASC";
        break;
    default:
        $orderByClause = "ORDER BY fullName ASC";
        break;
}

// This subquery counts messages from another user (u.uid) to the current user
// where the timestamp is greater than the current user's last_read_timestamp for that conversation.
$query = "
    SELECT 
        u.uid, u.fullName, u.course, u.year,
        (SELECT COUNT(*) 
         FROM messages m 
         WHERE m.sender_uid = u.uid AND m.receiver_uid = ? 
         AND m.timestamp > COALESCE((SELECT rr.last_read_timestamp FROM read_receipts rr WHERE rr.user_uid = ? AND rr.conversation_id = u.uid), '1970-01-01')) 
        AS unread_count
    FROM users u
    " . $whereClause . " " . $orderByClause;

// Add current user's UID to the beginning of the params array for the subqueries
// The existing $params array already contains $currentUserUid and $currentUserCollege.
// We only need to unshift $currentUserUid twice for the subquery placeholders.
array_unshift($params, $currentUserUid, $currentUserUid);
$paramTypes = "ss" . $paramTypes; // 'ss' for the two unshifted currentUserUids, followed by existing paramTypes.

$stmt = $conn->prepare($query);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode(['success' => true, 'users' => $users]);

$stmt->close();
$conn->close();
?>
