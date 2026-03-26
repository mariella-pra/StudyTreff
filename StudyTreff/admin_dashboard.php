<?php
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target_id = $_POST['target_id'] ?? 0;
    $target_type = $_POST['target_type'] ?? '';

    if ($action === 'delete_user' && $target_id && $target_type === 'user') {
        $sql = "DELETE FROM users WHERE id = ? AND role != 'admin'";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $target_id);
        $stmt->execute();

        $logSql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) VALUES (?, ?, ?, ?, ?)";
        $logStmt = $mysqli->prepare($logSql);
        $desc = "Deleted user ID: $target_id";
        $logStmt->bind_param("issis", $_SESSION['user_id'], $action, $target_type, $target_id, $desc);
        $logStmt->execute();
        $logStmt->close();

        $_SESSION['success'] = "User deleted successfully!";

    } elseif ($action === 'delete_post' && $target_id) {
        if ($target_type === 'location') {
            $sql = "DELETE FROM locations WHERE id = ?";
        } elseif ($target_type === 'activity') {
            $sql = "DELETE FROM activities WHERE id = ?";
        } elseif ($target_type === 'review') {
            $sql = "DELETE FROM reviews WHERE id = ?";
        }

        if (isset($sql)) {
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $target_id);
            $stmt->execute();

            $logSql = "INSERT INTO admin_logs (admin_id, action_type, target_type, target_id, description) VALUES (?, ?, ?, ?, ?)";
            $logStmt = $mysqli->prepare($logSql);
            $desc = "Deleted $target_type ID: $target_id";
            $logStmt->bind_param("issis", $_SESSION['user_id'], $action, $target_type, $target_id, $desc);
            $logStmt->execute();
            $logStmt->close();

            $_SESSION['success'] = ucfirst($target_type) . " deleted successfully!";
        }
    }

    header("Location: admin_dashboard.php");
    exit;
}

$stats = [];

$userStats = $mysqli->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
        SUM(CASE WHEN is_banned = 1 THEN 1 ELSE 0 END) as banned_count
    FROM users
")->fetch_assoc();

$locationStats = $mysqli->query("
    SELECT 
        COUNT(*) as total_locations,
        AVG(avg_rating) as avg_rating_all,
        SUM(reviews_count) as total_reviews
    FROM locations
")->fetch_assoc();

$activityStats = $mysqli->query("
    SELECT 
        COUNT(*) as total_activities,
        SUM(CASE WHEN type = 'activity' THEN 1 ELSE 0 END) as activity_count,
        SUM(CASE WHEN type = 'event' THEN 1 ELSE 0 END) as event_count
    FROM activities
")->fetch_assoc();

$users = $mysqli->query("
    SELECT u.*, 
           (SELECT COUNT(*) FROM locations WHERE user_id = u.id) as location_count,
           (SELECT COUNT(*) FROM activities WHERE user_id = u.id) as activity_count,
           (SELECT COUNT(*) FROM reviews WHERE user_id = u.id) as review_count
    FROM users u
    WHERE u.id != {$_SESSION['user_id']}
    ORDER BY u.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$recentActivities = $mysqli->query("
    SELECT a.*, u.username 
    FROM activities a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

$recentLocations = $mysqli->query("
    SELECT l.*, u.username 
    FROM locations l 
    LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudyTreff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body data-bs-theme="dark">
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <h1 class="mb-4">👑 Admin Dashboard</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= htmlspecialchars($userStats['total_users']) ?></div>
                    <div class="stat-label">Total Users</div>
                    <small>Admins: <?= htmlspecialchars($userStats['admin_count']) ?> | Users:
                        <?= htmlspecialchars($userStats['user_count']) ?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= htmlspecialchars($locationStats['total_locations']) ?></div>
                    <div class="stat-label">Locations</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= htmlspecialchars($activityStats['total_activities']) ?></div>
                    <div class="stat-label">Activities/Events</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?= htmlspecialchars($locationStats['total_reviews'] ?? 0) ?></div>
                    <div class="stat-label">Total Reviews</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">👥 User Management</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Posts</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>@<?= htmlspecialchars($user['username']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span
                                                    class="badge bg-<?= $user['role'] == 'admin' ? 'danger' : 'primary' ?>">
                                                    <?= htmlspecialchars($user['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    L:<?= htmlspecialchars($user['location_count']) ?>
                                                    A:<?= htmlspecialchars($user['activity_count']) ?>
                                                    R:<?= htmlspecialchars($user['review_count']) ?>
                                                </small>
                                            </td>
                                            <td class="action-buttons">
                                                <?php if ($user['role'] != 'admin'): ?>
                                                    <form method="POST" action="admin_dashboard.php" class="d-inline">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="target_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="target_type" value="user">
                                                        <button type="submit" class="btn btn-danger btn-sm"
                                                            onclick="return confirm('Delete user @<?= htmlspecialchars($user['username']) ?>? This will remove ALL their content!')">
                                                            Delete
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">Protected</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">⚡ Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <button class="btn btn-outline-warning w-100" data-bs-toggle="modal"
                                    data-bs-target="#viewLogsModal">
                                    📋 View Admin Logs
                                </button>
                            </div>
                            <div class="col-md-6">
                                <a href="index.php" class="btn btn-outline-success w-100">
                                    🏠 Return to Site
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-white bg-opacity-10 rounded-3">
                            <h6>👤 Your Admin Account</h6>
                            <p class="mb-1">Logged in as:
                                <strong>@<?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">📍 Recent Locations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Rating</th>
                                        <th>User</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentLocations as $location): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($location['name'], 0, 20)) ?></td>
                                            <td><span
                                                    class="badge bg-primary"><?= htmlspecialchars($location['type']) ?></span>
                                            </td>
                                            <td><?= htmlspecialchars(number_format($location['avg_rating'], 1)) ?> ⭐</td>
                                            <td>@<?= htmlspecialchars($location['username'] ?? 'Deleted User') ?></td>
                                            <td class="action-buttons">
                                                <form method="POST" action="admin_dashboard.php" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_post">
                                                    <input type="hidden" name="target_id" value="<?= $location['id'] ?>">
                                                    <input type="hidden" name="target_type" value="location">
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Delete location \"
                                                        <?= htmlspecialchars(addslashes($location['name'])) ?>\"?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">🎯 Recent Activities/Events</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <tr>
                                            <td><?= htmlspecialchars(substr($activity['name'], 0, 20)) ?>...</td>
                                            <td>
                                                <span
                                                    class="badge bg-<?= htmlspecialchars($activity['type'] == 'activity' ? 'success' : 'warning') ?>">
                                                    <?= htmlspecialchars($activity['type']) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars(date('d.m.Y', strtotime($activity['date']))) ?></td>
                                            <td>@<?= htmlspecialchars($activity['username'] ?? 'Deleted User') ?></td>
                                            <td class="action-buttons">
                                                <form method="POST" action="admin_dashboard.php" class="d-inline">
                                                    <input type="hidden" name="action" value="delete_post">
                                                    <input type="hidden" name="target_id" value="<?= $activity['id'] ?>">
                                                    <input type="hidden" name="target_type" value="activity">
                                                    <button type="submit" class="btn btn-danger btn-sm"
                                                        onclick="return confirm('Delete <?= htmlspecialchars($activity['type']) ?> \"
                                                        <?= htmlspecialchars(addslashes($activity['name'])) ?>\"?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <div class="modal fade" id="viewLogsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📋 Admin Action Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $logs = $mysqli->query("
                    SELECT 
                        al.*, 
                        COALESCE(u.username, 'Deleted User') as admin_name
                    FROM admin_logs al
                    LEFT JOIN users u ON al.admin_id = u.id
                    ORDER BY al.created_at DESC
                    LIMIT 50
                ")->fetch_all(MYSQLI_ASSOC);
                    ?>

                    <?php if (empty($logs)): ?>
                        <p class="text-center text-muted">No admin actions logged yet.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Admin</th>
                                        <th>Action</th>
                                        <th>Target</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><small><?= htmlspecialchars(date('H:i d.m', strtotime($log['created_at'] ?? ''))) ?></small>
                                            </td>
                                            <td>@<?= htmlspecialchars($log['admin_name'] ?? 'Deleted User') ?></td>
                                            <td><span
                                                    class="badge bg-<?= $log['action_type'] == 'delete_user' ? 'danger' : 'warning' ?>"><?= htmlspecialchars($log['action_type'] ?? 'unknown') ?></span>
                                            </td>
                                            <td><small><?= htmlspecialchars($log['target_type'] ?? 'unknown') ?>
                                                    #<?= htmlspecialchars($log['target_id'] ?? '?') ?></small>
                                            </td>
                                            <td><small><?= htmlspecialchars($log['description'] ?? 'User deleted') ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="searchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">🔎 Search User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="GET" action="admin_dashboard.php">
                        <div class="mb-3">
                            <label class="form-label">Search by Username or Email</label>
                            <input type="text" class="form-control" name="search"
                                placeholder="Enter username or email...">
                        </div>
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>