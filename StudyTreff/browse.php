<?php
include 'db.php'; 

$loggedIn   = isset($_SESSION['user_id']);
$typeFilter = $_GET['type']   ?? '';
$ratingFilter = $_GET['rating'] ?? '';

$locationSql = "SELECT l.*, 'location' as post_type, u.username as user_name 
                FROM locations l 
                LEFT JOIN users u ON l.user_id = u.id 
                WHERE 1=1";

$locationParams = [];
$locationTypes = "";

if (in_array($typeFilter, ['cafe', 'bar', 'restaurant', 'study', 'club'])) {
    $locationSql .= " AND l.type = ?";
    $locationParams[] = $typeFilter;
    $locationTypes .= "s";
} elseif ($typeFilter == '') {
} else {
    $locationSql .= " AND 1=0";
}

if ($ratingFilter !== '') {
    $locationSql .= " AND l.avg_rating >= ?";
    $locationParams[] = (float)$ratingFilter;
    $locationTypes .= "d";
}

$locationStmt = $mysqli->prepare($locationSql);

if (!$locationStmt && !empty($locationParams)) {
    die("Prepare failed: " . $mysqli->error);
}

if (!empty($locationParams)) {
    $locationStmt->bind_param($locationTypes, ...$locationParams);
}

$locationStmt->execute();
$locationResult = $locationStmt->get_result();
$locations = [];
while ($row = $locationResult->fetch_assoc()) {
    $locations[] = $row;
}
$locationStmt->close();

$activitySql = "SELECT a.*, 'activity' as post_type, u.username as user_name 
                FROM activities a 
                LEFT JOIN users u ON a.user_id = u.id 
                WHERE 1=1";
$activityParams = [];
$activityTypes = "";

if ($typeFilter == 'activity') {
    $activitySql .= " AND a.type = 'activity'";
} elseif ($typeFilter == 'event') {
    $activitySql .= " AND a.type = 'event'";
} elseif ($typeFilter == '') {
    $activitySql .= " AND (a.type = 'activity' OR a.type = 'event')";
} else {
    $activitySql .= " AND 1=0";
}

$activityStmt = $mysqli->prepare($activitySql);
if (!$activityStmt && !empty($activityParams)) {
    die("Prepare failed: " . $mysqli->error);
}

if (!empty($activityParams)) {
    $activityStmt->bind_param($activityTypes, ...$activityParams);
}

$activityStmt->execute();
$activityResult = $activityStmt->get_result();
$activities = [];
while ($row = $activityResult->fetch_assoc()) {
    $activities[] = $row;
}
$activityStmt->close();

$allPosts = array_merge($locations, $activities);

usort($allPosts, function($a, $b) {
    return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
});

foreach ($allPosts as &$post) {
    $post['unique_id'] = ($post['post_type'] == 'location' ? 'loc' : 'act') . $post['id'];
}
unset($post);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse Locations - StudyTreff</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/style.css">
</head>
<body data-bs-theme="dark">
<?php include 'includes/navbar.php'; ?>
<?php include 'includes/post_button.php'; ?>

<div class="container text-center mt-5">
    <h1>Browse Spots / Events / Activities</h1>
    <p class="lead">Find your favorite student spots 👩‍🎓👨‍🎓</p>
</div>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- filter -->
        <div class="col-md-3 mb-4">
            <div class="card shadow">
                <div class="card-body">
                    <h5 class="card-title mb-3">Filters</h5>
                    <form method="GET" action="browse.php">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Type</label>
                            <select class="form-select" name="type" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="cafe"       <?= (htmlspecialchars($typeFilter) == 'cafe')       ? 'selected' : ''; ?>>Cafe</option>
                                <option value="bar"        <?= (htmlspecialchars($typeFilter) == 'bar')        ? 'selected' : ''; ?>>Bar</option>
                                <option value="restaurant" <?= (htmlspecialchars($typeFilter) == 'restaurant') ? 'selected' : ''; ?>>Restaurant</option>
                                <option value="club"       <?= (htmlspecialchars($typeFilter) == 'club')       ? 'selected' : ''; ?>>Club</option>
                                <option value="study"      <?= (htmlspecialchars($typeFilter) == 'study')      ? 'selected' : ''; ?>>Study Spot</option>
                                <option value="activity"   <?= (htmlspecialchars($typeFilter) == 'activity')   ? 'selected' : ''; ?>>Activity</option>
                                <option value="event"      <?= (htmlspecialchars($typeFilter) == 'event')      ? 'selected' : ''; ?>>Event</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold d-block">Minimum Rating</label>
                            
                            <select class="form-select" name="rating" onchange="this.form.submit()">
                                <option value="">All ratings</option>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>" <?= ($ratingFilter == $i) ? 'selected' : '' ?>>
                                        <?= str_repeat('⭐', $i) ?><?= str_repeat('☆', 5 - $i) ?> (<?= $i ?>+ stars)
                                    </option>
                                <?php endfor; ?>
                            </select>
                            
                            <?php if ($ratingFilter): ?>
                                <small class="text-muted mt-1 d-block">
                                    Showing locations with <?= htmlspecialchars($ratingFilter) ?>+ star rating
                                </small>
                            <?php endif; ?>
                        </div>

                        <a href="browse.php" class="btn btn-secondary w-100 mt-2">Reset Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="d-flex flex-wrap justify-content-center">
                <?php if (!empty($allPosts)): ?>
                    <?php foreach ($allPosts as $post): ?>
                        <div class="card m-3 card-browse">
                            <?php if ($post['photo']): ?>
                                <img class="card-img-top" src="<?= htmlspecialchars($post['photo']) ?>" alt="<?= htmlspecialchars($post['name']) ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h3 class="card-title mb-0"><?= htmlspecialchars($post['name']) ?></h3>
                                    <?php if (!empty($post['user_name'])): ?>
                                        <span class="user-badge">@<?= htmlspecialchars($post['user_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($post['post_type'] == 'location'): ?>
                                    <span class="badge bg-primary"><?= htmlspecialchars($post['type']) ?></span>
                                    <p class="text-warning mb-2 mt-2">⭐ <?= number_format(htmlspecialchars($post['avg_rating']), 1) ?></p>
                                <?php else: ?>
                                    <span class="badge bg-<?= htmlspecialchars($post['type']) == 'activity' ? 'success' : 'warning' ?>">
                                        <?= ucfirst(htmlspecialchars($post['type'])) ?>
                                    </span>
                                    <?php
                                    $quickCountSql = "SELECT COUNT(*) as count FROM activity_participants WHERE activity_id = ?";
                                    $quickStmt = $mysqli->prepare($quickCountSql);
                                    $quickStmt->bind_param("i", $post['id']);
                                    $quickStmt->execute();
                                    $quickResult = $quickStmt->get_result()->fetch_assoc();
                                    $quickStmt->close();
                                    $participant_count = $quickResult['count'] ?? 0;
                                    ?>
                                    <p class="text-info mb-2 mt-2">
                                        📅 <?= date('d.m.Y H:i', strtotime($post['date'])) ?>
                                        | 👥 <?= $participant_count ?>
                                        <?php if ($post['max_participants']): ?>
                                            / <?= $post['max_participants'] ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <p class="mt-2"><?= $post['description'] ?></p>
                                
                                <?php if ($loggedIn): ?>
                                    <button type="button" class="btn btn-outline-primary" 
                                            data-bs-toggle="modal" data-bs-target="#postDetails<?= htmlspecialchars($post['unique_id']) ?>">
                                        View Details
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="modal fade" id="postDetails<?= htmlspecialchars($post['unique_id']) ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?= htmlspecialchars($post['name']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <?php if ($post['photo']): ?>
                                                <div class="col-md-6">
                                                    <img src="<?= htmlspecialchars($post['photo']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($post['name']) ?>">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="<?= htmlspecialchars($post['photo']) ? 'col-md-6' : 'col-12' ?>">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h3 class="text-primary"><?= htmlspecialchars($post['name']) ?></h3>
                                                    <?php if (!empty($post['user_name'])): ?>
                                                        <span class="user-badge">@<?= htmlspecialchars($post['user_name']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <?php if (htmlspecialchars($post['post_type']) == 'location'): ?>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($post['type']) ?></span>
                                                    
                                                    <div class="mt-3">
                                                        <h5>⭐ Rating: <span class="text-warning"><?= number_format($post['avg_rating'], 1) ?></span>/5</h5>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="badge bg-<?= htmlspecialchars($post['type']) == 'activity' ? 'success' : 'warning' ?>">
                                                        <?= ucfirst(htmlspecialchars($post['type'])) ?>
                                                    </span>
                                                    
                                                    <div class="mt-3">
                                                        <h5>📅 Date: <span class="text-info"><?= date('d.m.Y H:i', strtotime(htmlspecialchars($post['date']))) ?></span></h5>
                                                    </div>
                                                    
                                                    <div class="mt-3">
                                                        <h6>Location:</h6>
                                                        <p class="text-muted"><?= htmlspecialchars($post['location']) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6>Description:</h6>
                                                <p class="lead"><?= htmlspecialchars($post['description']) ?></p>
                                            </div>
                                        </div>
                                        
                                        <?php if (htmlspecialchars($post['post_type']) == 'location'): ?>
                                            <div class="row mt-4">
                                                <div class="col-12">
                                                    <?php if ($loggedIn): ?>
                                                        <h6>Add Your Review:</h6>
                                                        <form action="submit_review.php" method="POST" enctype="multipart/form-data" class="mt-3">
                                                            <input type="hidden" name="location_id" value="<?= htmlspecialchars($post['id']) ?>">
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Your Rating</label>
                                                                <select class="form-select" name="rating" required>
                                                                    <option value="">Select rating...</option>
                                                                    <option value="5">5 ⭐ (Excellent)</option>
                                                                    <option value="4">4 ⭐ (Good)</option>
                                                                    <option value="3">3 ⭐ (Average)</option>
                                                                    <option value="2">2 ⭐ (Poor)</option>
                                                                    <option value="1">1 ⭐ (Terrible)</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Your Comment</label>
                                                                <textarea class="form-control" name="comment" rows="2" placeholder="Share your experience..." required></textarea>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Upload Photo (Optional)</label>
                                                                <input type="file" class="form-control" name="photo" accept="image/jpeg, image/jpg, image/png">
                                                            </div>
                                                            
                                                            <button type="submit" class="btn btn-primary btn-sm">Submit Review</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <div class="alert alert-info py-2">
                                                            <small>Please <a href="login.php" class="alert-link">login</a> to leave a review.</small>
                                                        </div>
                                                    <?php endif; ?>
                                                    <h6 class="mt-4">Reviews:</h6>
                                                    <?php
                                                    $reviewSql = "SELECT r.*, u.username, u.profile_pic 
                                                                 FROM reviews r 
                                                                 LEFT JOIN users u ON r.user_id = u.id 
                                                                 WHERE r.location_id = ? 
                                                                 ORDER BY r.created_at DESC";
                                                    $reviewStmt = $mysqli->prepare($reviewSql);
                                                    $reviewStmt->bind_param("i", $post['id']);
                                                    $reviewStmt->execute();
                                                    $locationReviews = $reviewStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                                    $reviewStmt->close();
                                                    ?>
                                                    
                                                    <?php if (empty($locationReviews)): ?>
                                                    <p class="text-muted"><small>No reviews yet. Be the first to review!</small></p>
                                                <?php else: ?>
                                                    <?php foreach ($locationReviews as $review): ?>
                                                        <div class="card mb-2 review-card">
                                                            <div class="card-body p-3">
                                                                <div class="d-flex justify-content-between">
                                                                    <div>
                                                                        <strong>@<?= htmlspecialchars($review['username'] ?? 'Anonymous') ?></strong>
                                                                        <div class="text-warning">
                                                                            <?= str_repeat('★', $review['rating']) ?><?= str_repeat('☆', 5 - $review['rating']) ?>
                                                                            
                                                                            <small class="text-muted ms-2">(<?= htmlspecialchars($review['rating'] ?? 0) ?>/5)</small>
                                                                        </div>
                                                                    </div>
                                                                    <small class="text-muted">
                                                                        
                                                                        <?= htmlspecialchars(date('d.m.Y', strtotime($review['created_at'] ?? ''))) ?>
                                                                    </small>
                                                                </div>
                                                                <p class="mb-1 mt-2"><?= nl2br(htmlspecialchars($review['comment'] ?? '')) ?></p>
                                                                
                                                                <?php if (!empty($review['photo'])): ?>
                                                                    
                                                                    <img src="<?= htmlspecialchars($review['photo']) ?>" class="img-fluid rounded mt-2 w-50">
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($post['post_type'] == 'activity'): ?>
                                            <div class="row mt-4">
                                                <div class="col-12">
                                                    <div class="card review-card">
                                                        <div class="card-body">
                                                            <h6>Participation:</h6>
                                                            
                                                            <?php
                                                            $participantSql = "SELECT 
                                                                                COUNT(*) as participant_count,
                                                                                GROUP_CONCAT(u.username) as participant_names
                                                                               FROM activity_participants ap
                                                                               LEFT JOIN users u ON ap.user_id = u.id
                                                                               WHERE ap.activity_id = ?";
                                                            $participantStmt = $mysqli->prepare($participantSql);
                                                            $participantStmt->bind_param("i", $post['id']);
                                                            $participantStmt->execute();
                                                            $participantResult = $participantStmt->get_result()->fetch_assoc();
                                                            $participantStmt->close();
                                                            
                                                            $participant_count = $participantResult['participant_count'] ?? 0;
                                                            $participant_names = $participantResult['participant_names'] ?? '';
                                                            
                                                            $userJoined = false;
                                                            if ($loggedIn) {
                                                                $checkJoinSql = "SELECT id FROM activity_participants WHERE activity_id = ? AND user_id = ?";
                                                                $checkStmt = $mysqli->prepare($checkJoinSql);
                                                                $checkStmt->bind_param("ii", $post['id'], $_SESSION['user_id']);
                                                                $checkStmt->execute();
                                                                $checkStmt->store_result();
                                                                $userJoined = $checkStmt->num_rows > 0;
                                                                $checkStmt->close();
                                                            }
                                                            
                                                            $isFull = $post['max_participants'] && $participant_count >= $post['max_participants'];
                                                            ?>
                                                            
                                                            <div class="mb-3">
                                                                <p class="mb-1">
                                                                    <strong>👥 Participants:</strong> 
                                                                    <?= $participant_count ?>
                                                                    <?php if ($post['max_participants']): ?>
                                                                        / <?= htmlspecialchars($post['max_participants']) ?> max
                                                                    <?php endif; ?>
                                                                </p>
                                                                
                                                                <?php if ($participant_names): ?>
                                                                    <small class="text-muted">
                                                                        Joined: <?= str_replace(',', ', ', $participant_names) ?>
                                                                    </small>
                                                                <?php else: ?>
                                                                    <small class="text-muted">No participants yet</small>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <?php if ($loggedIn): ?>
                                                                <?php if ($post['user_id'] != $_SESSION['user_id']): ?>
                                                                    <?php if ($userJoined): ?>
                                                                        <a href="join_activity.php?id=<?= $post['id'] ?>&action=leave" 
                                                                           class="btn btn-warning btn-sm">
                                                                            👋 Leave Activity
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <?php if ($isFull): ?>
                                                                            <button class="btn btn-secondary btn-sm" disabled>
                                                                                ❌ Activity Full
                                                                            </button>
                                                                        <?php else: ?>
                                                                            <a href="join_activity.php?id=<?= $post['id'] ?>&action=join" 
                                                                               class="btn btn-success btn-sm">
                                                                                ✅ Join Activity
                                                                            </a>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                <?php else: ?>
                                                                    <small class="text-info">👑 You created this activity</small>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <div class="alert alert-info py-2">
                                                                    <small>Please <a href="login.php" class="alert-link">login</a> to join this activity</small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center w-100">
                        <p class="text-muted">No posts found with these filters.</p>
                        <a href="browse.php" class="btn btn-primary">Show All Posts</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
                </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>