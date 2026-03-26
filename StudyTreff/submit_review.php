<?php
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: browse.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$location_id = $_POST['location_id'] ?? 0;
$rating = $_POST['rating'] ?? 0;
$comment = $_POST['comment'] ?? '';
$photoPath = null;

if (!$location_id || $rating < 1 || $rating > 5 || empty($comment)) {
    $_SESSION['error'] = "Please provide a valid rating and comment.";
    header("Location: browse.php");
    exit;
}

$checkStmt = $mysqli->prepare("SELECT id FROM reviews WHERE user_id = ? AND location_id = ?");
$checkStmt->bind_param("ii", $user_id, $location_id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $checkStmt->close();
    $_SESSION['error'] = "You've already reviewed this location!";
    header("Location: browse.php");
    exit;
}
$checkStmt->close();

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/reviews/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0777, true);

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileType = mime_content_type($_FILES['photo']['tmp_name']);

    if (in_array($fileType, $allowedTypes)) {
        $filename = time() . '_' . uniqid() . '_' . basename($_FILES['photo']['name']);
        $photoPath = $uploadDir . $filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
    }
}

try {
    $sql = "INSERT INTO reviews (user_id, location_id, rating, comment, photo) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iiiss", $user_id, $location_id, $rating, $comment, $photoPath);
    $stmt->execute();
    $stmt->close();

    $updateSql = "UPDATE locations l 
                  SET l.avg_rating = (
                      SELECT AVG(r.rating) 
                      FROM reviews r 
                      WHERE r.location_id = l.id
                  ),
                  l.reviews_count = (
                      SELECT COUNT(*) 
                      FROM reviews r 
                      WHERE r.location_id = l.id
                  )
                  WHERE l.id = ?";
    $updateStmt = $mysqli->prepare($updateSql);
    $updateStmt->bind_param("i", $location_id);
    $updateStmt->execute();
    $updateStmt->close();

    $_SESSION['success'] = "Review submitted successfully!";
    header("Location: browse.php");
    exit;

} catch (Exception $e) {
    $_SESSION['error'] = "Error submitting review: " . $e->getMessage();
    header("Location: browse.php");
    exit;
}
?>