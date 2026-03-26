<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$port = '3306';
$dbname = 'studytreff_db';
$username = 'root';
$password = '';

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $port = '3306';
    $password = '';
} else {
    $port = '8889';
    $password = 'root';
}

$mysqli = new mysqli($host . ':' . $port, $username, $password, $dbname);

if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}

$mysqli->set_charset("utf8");
?>