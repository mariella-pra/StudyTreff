<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$name = $_POST['name'];
$username = $_POST['username'];
$email = $_POST['email'];
$password = password_hash($_POST['password'], PASSWORD_DEFAULT);
$age = $_POST['age'] ?? null;
$field = $_POST['field'] ?? null;
$interests = $_POST['interests'] ?? null;

$profile_pic = null;
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0777, true);

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileType = mime_content_type($_FILES['profile_pic']['tmp_name']);

    if (in_array($fileType, $allowedTypes)) {
        $tmpName = $_FILES['profile_pic']['tmp_name'];
        $filename = time() . '_' . basename($_FILES['profile_pic']['name']);
        $profile_pic = $uploadDir . $filename;

        move_uploaded_file($tmpName, $profile_pic);
    } else {
        $_SESSION['error'] = "Nur JPG, JPEG oder PNG Dateien sind erlaubt für Profilbilder!";
        header("Location: index.php");
        exit;
    }
}

$sql = "INSERT INTO users (name, username, email, password, age, field, interests, profile_pic, role) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user')";

$stmt = $mysqli->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . $mysqli->error);
}

$stmt->bind_param(
    "ssssisss",
    $name,
    $username,
    $email,
    $password,
    $age,
    $field,
    $interests,
    $profile_pic
);

if ($stmt->execute()) {
    $_SESSION['success'] = "User erfolgreich erstellt! Du kannst dich jetzt einloggen.";
    header("Location: index.php");
    exit;
} else {
    $_SESSION['error'] = "Fehler: " . htmlspecialchars($stmt->error);
    header("Location: index.php");
    exit;
}

$stmt->close();
?>