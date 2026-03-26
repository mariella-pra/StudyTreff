<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$userId = $_SESSION['user_id'];
$message = '';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $age = $_POST['age'] ?? null;
    $field = $_POST['field'] ?? null;
    $interests = $_POST['interests'] ?? null;

    $profile_pic = $user['profile_pic'];

    if (!empty($_FILES['profile_pic']['name'])) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $fileType = mime_content_type($_FILES['profile_pic']['tmp_name']);

        if (in_array($fileType, $allowedTypes)) {
            $fileName = time() . '_' . $_FILES['profile_pic']['name'];
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFile)) {
                $profile_pic = $targetFile;
            }
        } else {
            $message = 'Error: Nur JPG, JPEG oder PNG Dateien sind erlaubt!';
        }
    }

    $sql = "UPDATE users SET name = ?, username = ?, email = ?, age = ?, field = ?, interests = ?, profile_pic = ? WHERE id = ?";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        die("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param("sssisssi", $name, $username, $email, $age, $field, $interests, $profile_pic, $userId);

    if ($stmt->execute()) {
        $message = 'Profile updated successfully!';
    } else {
        $message = 'Error updating profile: ' . $stmt->error;
    }

    $stmt->close();

    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - StudyTreff</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>

<body data-bs-theme="dark">
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title mb-4">Edit Profile</h1>

                        <?php if ($message): ?>
                            <div class="alert alert-success"><?= $message ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="text-center mb-5">
                                <div class="mb-3">
                                    <?php if ($user['profile_pic']): ?>
                                        <img src="<?= $user['profile_pic'] ?>"
                                            class="rounded-circle border border-4 border-primary mb-3"
                                            style="width: 200px; height: 200px; object-fit: cover;"
                                            alt="Current Profile Picture" id="profilePreview">
                                    <?php else: ?>
                                        <div class="rounded-circle border border-4 border-primary d-flex align-items-center justify-content-center mb-3 bg-gradient"
                                            style="width: 200px; height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); margin: 0 auto;">
                                            <span class="text-white display-1">👤</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3 w-50 mx-auto">
                                    <label class="form-label fw-bold">Change Profile Picture</label>
                                    <input type="file" name="profile_pic" class="form-control bg-dark text-light"
                                        accept="image/jpeg, image/jpg, image/png">
                                    <small class="text-muted">JPG, PNG</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Name *</label>
                                        <input type="text" name="name" class="form-control bg-dark text-light"
                                            value="<?= $user['name'] ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Username *</label>
                                        <input type="text" name="username" class="form-control bg-dark text-light"
                                            value="<?= $user['username'] ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email *</label>
                                        <input type="email" name="email" class="form-control bg-dark text-light"
                                            value="<?= $user['email'] ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Age</label>
                                        <input type="number" name="age" class="form-control bg-dark text-light"
                                            value="<?= $user['age'] ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Field of Study</label>
                                <input type="text" name="field" class="form-control bg-dark text-light"
                                    value="<?= $user['field'] ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Interests</label>
                                <textarea name="interests" class="form-control bg-dark text-light" rows="4"
                                    placeholder="Enter your interests"><?= $user['interests'] ?></textarea>
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <a href="profile.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>