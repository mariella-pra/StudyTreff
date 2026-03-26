<?php if ($loggedIn): ?>
    <button type="button"
        class="btn btn-primary rounded-circle position-fixed d-flex justify-content-center align-items-center"
        style="width:60px; height:60px; bottom:20px; right:20px; font-size:2rem; z-index:9999;" data-bs-toggle="modal"
        data-bs-target="#addLocationModal">+</button>

    <div class="modal fade" id="addLocationModal" tabindex="-1" aria-labelledby="addLocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLocationModalLabel">Neue Location / Activity / Event posten</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <ul class="nav nav-tabs" id="postTypeTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="location-tab" data-bs-toggle="tab" data-bs-target="#location"
                            type="button" role="tab" aria-controls="location" aria-selected="true">
                            Location
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity"
                            type="button" role="tab" aria-controls="activity" aria-selected="false">
                            Activity
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="event-tab" data-bs-toggle="tab" data-bs-target="#event" type="button"
                            role="tab" aria-controls="event" aria-selected="false">
                            Event
                        </button>
                    </li>
                </ul>

                <div class="modal-body">
                    <div class="tab-content mt-3">
                        <!-- LOCATION TAB -->
                        <div class="tab-pane fade show active" id="location" role="tabpanel" aria-labelledby="location-tab">
                            <form action="add_post.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ort</label>
                                    <input type="text" class="form-control" name="location" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Beschreibung</label>
                                    <textarea class="form-control" name="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="type" class="form-label">Location Type</label>
                                    <select class="form-select" name="type" required>
                                        <option value="">Wähle...</option>
                                        <option value="cafe">Cafe</option>
                                        <option value="bar">Bar</option>
                                        <option value="restaurant">Restaurant</option>
                                        <option value="club">Club</option>
                                        <option value="study">Study Spot</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <select class="form-select" name="rating" required>
                                        <option value="">Wähle...</option>
                                        <option value="5">5 ⭐</option>
                                        <option value="4">4 ⭐</option>
                                        <option value="3">3 ⭐</option>
                                        <option value="2">2 ⭐</option>
                                        <option value="1">1 ⭐</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Foto hochladen</label>
                                    <input type="file" class="form-control" name="photo">
                                </div>
                                <input type="hidden" name="postType" value="location">
                                <button type="submit" class="btn btn-primary">Location posten</button>
                            </form>
                        </div>

                        <!-- ACTIVITY TAB -->
                        <div class="tab-pane fade" id="activity" role="tabpanel" aria-labelledby="activity-tab">
                            <form action="add_post.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ort</label>
                                    <input type="text" class="form-control" name="location" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Beschreibung</label>
                                    <textarea class="form-control" name="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Datum</label>
                                    <input type="datetime-local" class="form-control" name="date" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Max Participants (Optional)</label>
                                    <input type="number" class="form-control" name="max_participants" min="1"
                                        placeholder="Leave empty for unlimited">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Foto hochladen</label>
                                    <input type="file" class="form-control" name="photo">
                                </div>
                                <input type="hidden" name="postType" value="activity">
                                <button type="submit" class="btn btn-primary">Activity posten</button>
                            </form>
                        </div>

                        <!-- EVENT TAB -->
                        <div class="tab-pane fade" id="event" role="tabpanel" aria-labelledby="event-tab">
                            <form action="add_post.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ort</label>
                                    <input type="text" class="form-control" name="location" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Beschreibung</label>
                                    <textarea class="form-control" name="description" rows="3" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Datum</label>
                                    <input type="datetime-local" class="form-control" name="date" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Max Participants (Optional)</label>
                                    <input type="number" class="form-control" name="max_participants" min="1"
                                        placeholder="Leave empty for unlimited">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Foto hochladen</label>
                                    <input type="file" class="form-control" name="photo">
                                </div>
                                <input type="hidden" name="postType" value="event">
                                <button type="submit" class="btn btn-primary">Event posten</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>