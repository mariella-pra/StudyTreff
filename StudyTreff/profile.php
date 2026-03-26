<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$loggedIn = true;
$userId = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    die("User not found");
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - StudyTreff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body data-bs-theme="dark">
    <?php include 'includes/navbar.php'; ?>

    <<div class="container mt-5">
        <h1 class="mb-4">Your Profile</h1>

        <div class="row">
            <div class="col-md-4 text-center">
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if ($user['profile_pic']): ?>
                            <img src="<?= $user['profile_pic'] ?>"
                                class="rounded-circle border border-4 border-primary mb-3"
                                style="width: 200px; height: 200px; object-fit: cover;" alt="Profile Picture">
                        <?php else: ?>
                            <div class="rounded-circle border border-4 border-primary d-flex align-items-center justify-content-center mb-3 bg-gradient"
                                style="width: 200px; height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0 auto;">
                                <span class="text-white display-1">👤</span>
                            </div>
                        <?php endif; ?>

                        <h4 class="mb-0">@<?= $user['username'] ?></h4>
                        <p class="text-muted"><?= $user['name'] ?></p>

                        <span class="badge bg-<?= $user['role'] == 'admin' ? 'warning' : 'primary' ?> mb-3">
                            <?= $user['role'] ?>
                        </span>

                        <div class="mt-3">
                            <a href="edit_profile.php" class="btn btn-primary w-100">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Profile Information</h5>

                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="fw-bold text-secondary">Email</span>
                                    <span><?= $user['email'] ?></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="fw-bold text-secondary">Age</span>
                                    <span><?= $user['age'] ?: 'Not specified' ?></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between border-bottom pb-2">
                                    <span class="fw-bold text-secondary">Field of Study</span>
                                    <span><?= $user['field'] ?: 'Not specified' ?></span>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex flex-column border-bottom pb-2">
                                    <span class="fw-bold text-secondary mb-1">Interests</span>
                                    <span><?= $user['interests'] ? nl2br($user['interests']) : 'Not specified' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>