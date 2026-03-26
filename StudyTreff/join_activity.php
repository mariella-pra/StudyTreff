<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$activity_id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? 'join';

if (!$activity_id) {
    header("Location: browse.php");
    exit;
}

$activitySql = "SELECT * FROM activities WHERE id = ?";
$stmt = $mysqli->prepare($activitySql);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$activity) {
    header("Location: browse.php");
    exit;
}

if ($activity['user_id'] == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot join/leave your own activity!";
    header("Location: browse.php");
    exit;
}

if ($action == 'join') {
    $countSql = "SELECT COUNT(*) as count FROM activity_participants WHERE activity_id = ?";
    $countStmt = $mysqli->prepare($countSql);
    $countStmt->bind_param("i", $activity_id);
    $countStmt->execute();
    $result = $countStmt->get_result()->fetch_assoc();
    $current_participants = $result['count'];
    $countStmt->close();

    $checkSql = "SELECT id FROM activity_participants WHERE activity_id = ? AND user_id = ?";
    $checkStmt = $mysqli->prepare($checkSql);
    $checkStmt->bind_param("ii", $activity_id, $_SESSION['user_id']);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['error'] = "You have already joined this activity!";
    } elseif ($activity['max_participants'] && $current_participants >= $activity['max_participants']) {
        $_SESSION['error'] = "This activity is full!";
    } else {
        $joinSql = "INSERT INTO activity_participants (activity_id, user_id) VALUES (?, ?)";
        $joinStmt = $mysqli->prepare($joinSql);
        $joinStmt->bind_param("ii", $activity_id, $_SESSION['user_id']);

        if ($joinStmt->execute()) {
            $_SESSION['success'] = "Successfully joined the activity!";
        } else {
            $_SESSION['error'] = "Failed to join activity.";
        }
        $joinStmt->close();
    }
    $checkStmt->close();

} elseif ($action == 'leave') {
    $leaveSql = "DELETE FROM activity_participants WHERE activity_id = ? AND user_id = ?";
    $leaveStmt = $mysqli->prepare($leaveSql);
    $leaveStmt->bind_param("ii", $activity_id, $_SESSION['user_id']);

    if ($leaveStmt->execute()) {
        $_SESSION['success'] = "Successfully left the activity!";
    } else {
        $_SESSION['error'] = "Failed to leave activity.";
    }
    $leaveStmt->close();
}

header("Location: index.php");
exit;
?>