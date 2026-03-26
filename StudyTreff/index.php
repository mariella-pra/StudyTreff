<?php
include 'db.php';
$loggedIn = isset($_SESSION['user_id']);

$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error']);
unset($_SESSION['success']);

$nextEvent = null;
if ($loggedIn) {
    $sql = "SELECT a.*, u.username as user_name 
           FROM activities a 
           LEFT JOIN users u ON a.user_id = u.id 
           WHERE a.type = 'event' AND a.date > NOW() 
           ORDER BY a.date ASC LIMIT 1";

    $stmt = $mysqli->prepare($sql);

    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $nextEvent = $result->fetch_assoc();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyTreff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body data-bs-theme="dark">
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/post_button.php'; ?>

    <div class="container text-center mt-5">
        <h1 class="logo-placeholder">StudyTreff🎓</h1>
        <p class="lead">Discover student-friendly spots and events 👩‍🎓👨‍🎓</p>
    </div>

    <?php if ($error): ?>
        <div class="container mt-3" style="max-width:700px;">
            <div class="alert alert-danger"><?= $error ?></div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="container mt-3" style="max-width:700px;">
            <div class="alert alert-success"><?= $success ?></div>
        </div>
    <?php endif; ?>

    <?php if (!$loggedIn): ?>
        <div class="container mt-5" style="max-width:700px;">
            <ul class="nav nav-tabs" id="authTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#login">Login</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#register">Register</button>
                </li>
            </ul>
            <div class="tab-content mt-3">
                <div class="tab-pane fade show active" id="login">
                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                </div>

                <div class="tab-pane fade" id="register">
                    <form action="register.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <button type="submit" class="btn btn-success">Register</button>
                    </form>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="container mt-5 text-center">
            <h2>Welcome back, <?= $_SESSION['user_name'] ?>!</h2>
            <p>You are logged in. Navigate to <a href="browse.php">Browse Locations</a> to discover new spots.</p>
        </div>

        <div class="container mt-5">
            <h2 class="text-center mb-4">Upcoming Events</h2>

            <?php
            $upcomingEvents = [];
            if ($loggedIn) {
                $sql = "SELECT a.*, u.username as user_name 
               FROM activities a 
               LEFT JOIN users u ON a.user_id = u.id 
               WHERE a.type = 'event' AND a.date > NOW() 
               ORDER BY a.date ASC 
               LIMIT 3";

                $stmt = $mysqli->prepare($sql);

                if ($stmt) {
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $upcomingEvents[] = $row;
                    }
                    $stmt->close();
                }
            }

            $eventCount = count($upcomingEvents);
            ?>

            <?php if (!empty($upcomingEvents)): ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 justify-content-center">
                    <?php foreach ($upcomingEvents as $event):
                        $countSql = "SELECT COUNT(*) as count FROM activity_participants WHERE activity_id = ?";
                        $countStmt = $mysqli->prepare($countSql);
                        $countStmt->bind_param("i", $event['id']);
                        $countStmt->execute();
                        $countResult = $countStmt->get_result()->fetch_assoc();
                        $countStmt->close();
                        $participant_count = $countResult['count'] ?? 0;

                        $userJoined = false;
                        if ($loggedIn) {
                            $checkSql = "SELECT id FROM activity_participants WHERE activity_id = ? AND user_id = ?";
                            $checkStmt = $mysqli->prepare($checkSql);
                            $checkStmt->bind_param("ii", $event['id'], $_SESSION['user_id']);
                            $checkStmt->execute();
                            $checkStmt->store_result();
                            $userJoined = $checkStmt->num_rows > 0;
                            $checkStmt->close();
                        }
                        ?>
                        <div class="col">
                            <div class="card h-100">
                                <?php if ($event['photo']): ?>
                                    <img src="<?= $event['photo'] ?>" class="card-img-top" alt="<?= $event['name'] ?>"
                                        style="height: 180px; object-fit: cover;">
                                <?php endif; ?>

                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-0"><?= $event['name'] ?></h5>
                                        <span class="badge bg-warning">Event</span>
                                    </div>

                                    <div class="mb-2">
                                        <p class="mb-1 text-info">
                                            👥 <strong><?= $participant_count ?></strong>
                                            <?php if ($event['max_participants']): ?>
                                                / <?= $event['max_participants'] ?> max
                                            <?php endif; ?>
                                        </p>

                                        <p class="mb-1"><small>📅 <?= date('d.m.Y H:i', strtotime($event['date'])) ?></small></p>
                                        <p class="mb-1"><small>📍 <?= $event['location'] ?></small></p>
                                        <?php if (!empty($event['user_name'])): ?>
                                            <p class="mb-2"><small>👤 @<?= $event['user_name'] ?></small></p>
                                        <?php endif; ?>
                                    </div>

                                    <p class="card-text flex-grow-1">
                                        <?=
                                            strlen($event['description']) > 100
                                            ? substr($event['description'], 0, 100) . '...'
                                            : $event['description']
                                            ?>
                                    </p>

                                    <div class="mt-auto pt-2">
                                        <?php if ($loggedIn && $event['user_id'] != $_SESSION['user_id']): ?>
                                            <?php if ($userJoined): ?>
                                                <a href="join_activity.php?id=<?= $event['id'] ?>&action=leave"
                                                    class="btn btn-warning btn-sm w-100">
                                                    👋 Leave Event
                                                </a>
                                            <?php else: ?>
                                                <?php if ($event['max_participants'] && $participant_count >= $event['max_participants']): ?>
                                                    <button class="btn btn-secondary btn-sm w-100" disabled>
                                                        ❌ Event Full
                                                    </button>
                                                <?php else: ?>
                                                    <a href="join_activity.php?id=<?= $event['id'] ?>&action=join"
                                                        class="btn btn-success btn-sm w-100">
                                                        ✅ Join Event
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php elseif ($loggedIn && $event['user_id'] == $_SESSION['user_id']): ?>
                                            <small class="text-info text-center d-block">👑 You created this event</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <p class="text-muted">Keine bevorstehenden Events gefunden.</p>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="browse.php?type=event" class="btn btn-outline-primary">
                    Alle Events anzeigen
                </a>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>