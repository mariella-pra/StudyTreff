<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $_SESSION['error'] = "Bitte Email und Passwort ausfüllen.";
        header("Location: index.php");
        exit;
    }

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        $_SESSION['error'] = "Datenbankfehler. Bitte versuche es später erneut.";
        header("Location: index.php");
        exit;
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
        $_SESSION['role'] = $user['role'];

        $_SESSION['success'] = "Erfolgreich eingeloggt! Willkommen zurück, " . $user['name'] . "!";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['error'] = "Falsche Email oder Passwort!";
        header("Location: index.php");
        exit;
    }

} else {
    header("Location: index.php");
    exit;
}
?>