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

$postType = $_POST['postType'];
$name = $_POST['name'];
$location = $_POST['location'];
$description = $_POST['description'];
$date = $_POST['date'] ?? null;
$type = $_POST['type'] ?? null;
$rating = $_POST['rating'] ?? null;

if (!$name || !$location || !$description) {
    die("Error: Name, Ort und Beschreibung sind erforderlich");
}

if ($postType == 'location') {
    if (!$type || !$rating) {
        die("Error: Type und Rating sind erforderlich für Locations");
    }
} else {
    if (!$date) {
        die("Error: Datum ist erforderlich für Activities und Events");
    }
}

$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0777, true);

    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    $fileType = mime_content_type($_FILES['photo']['tmp_name']);

    if (in_array($fileType, $allowedTypes)) {
        $filename = time() . '_' . basename($_FILES['photo']['name']);
        $photoPath = $uploadDir . $filename;
        move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath);
    } else {
        die("Error: Nur JPG, JPEG oder PNG Dateien sind erlaubt!");
    }
}

try {
    if ($postType == 'location') {
        $sql = "INSERT INTO locations (name, type, description, photo, user_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $mysqli->error);
        }

        $stmt->bind_param("ssssi", $name, $type, $description, $photoPath, $_SESSION['user_id']);
        $stmt->execute();
        $location_id = $stmt->insert_id;
        $stmt->close();

        $user_id = $_SESSION['user_id'];
        $sql = "INSERT INTO reviews (user_id, location_id, rating, comment, photo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $mysqli->error);
        }

        $stmt->bind_param("iiiss", $user_id, $location_id, $rating, $description, $photoPath);
        $stmt->execute();
        $stmt->close();

        $sql = "UPDATE locations l SET l.avg_rating = (SELECT AVG(r.rating) FROM reviews r WHERE r.location_id = l.id) WHERE l.id = ?";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $mysqli->error);
        }

        $stmt->bind_param("i", $location_id);
        $stmt->execute();
        $stmt->close();

    } else {
        if (!$date) {
            die("Error: Datum ist erforderlich für Activities und Events");
        }

        $max_participants = !empty($_POST['max_participants']) ? (int) $_POST['max_participants'] : null;

        $sql = "INSERT INTO activities (name, location, description, date, type, photo, user_id, max_participants) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $mysqli->error);
        }

        $stmt->bind_param("ssssssis", $name, $location, $description, $date, $postType, $photoPath, $_SESSION['user_id'], $max_participants);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: browse.php");
    exit;

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>