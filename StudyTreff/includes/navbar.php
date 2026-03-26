<?php
$loggedIn = isset($_SESSION['user_id']);

$profilePic = 'images/default.png';
$profileLink = '#';

if ($loggedIn) {
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

    if (isset($mysqli)) {
        $sql = "SELECT profile_pic FROM users WHERE id = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $stmt->bind_result($pic);
            $stmt->fetch();
            $stmt->close();

            if ($pic) {
                $profilePic = $pic;
            }
        }
    }
    $profileLink = "profile.php";
}
?>

<nav class="navbar navbar-expand-lg navbar-dark custom-navbar">
    <div class="container">
        <a class="navbar-brand fw-bold me-auto" href="index.php">StudyTreff</a>

        <div class="navbar-nav mx-auto">
            <a class="nav-link" href="index.php">Home</a>
            <a class="nav-link" href="browse.php">Browse</a>
        </div>

        <div class="navbar-nav ms-auto">
            <?php if ($loggedIn): ?>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $profilePic ?>" alt="Profile" class="rounded-circle custom-profile-img">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a class="dropdown-item" href="admin_dashboard.php">Admin Dashboard</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="<?= $profileLink ?>">My Profile</a></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a class="nav-link" href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>