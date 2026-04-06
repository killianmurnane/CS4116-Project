<!DOCTYPE html>
<?php
require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';
requireLogin();
?>
<html>
    <head>
        <!-- CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="css/base.css" />
        <!-- Other -->
        <title>GymDate</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    </head>
    <body>
        <div class="container">
            <aside class="sidebar">
                <h2 class="sidebar-title">GymDate</h2>
                <nav class="menu" aria-label="Main menu">
                    <a class="menu-item" href="/">Home</a>
                    <a class="menu-item" href="/message.php">Messages</a>
                    <a class="menu-item active" href="#">Search</a>
                    <a class="menu-item" href="/profile.php">Profile</a>
                    <a class="menu-item" href="/helpers/auth.php?action=logout">Logout</a>
                </nav>
            </aside>

            <main class="content">
                <section class="top-rectangle p-4">
                    <div class="w-100">
                        <h3 class="mb-2">Find Training Partners</h3>
                        <p class="text-muted mb-3">Filter by goals, experience, and preferred gym time.</p>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <select class="form-select">
                                    <option selected>Goal: Any</option>
                                    <option>Strength</option>
                                    <option>Weight Loss</option>
                                    <option>Cardio</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select">
                                    <option selected>Level: Any</option>
                                    <option>Beginner</option>
                                    <option>Intermediate</option>
                                    <option>Advanced</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-dark w-100" type="button">Search</button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Suggested Matches</h5>
                        <div class="w-100 d-flex flex-column gap-2">
                            <div class="border rounded p-3 bg-white">
                                <strong>Emma</strong>
                                <div class="text-muted">Powerlifting • 3 km away • Evenings</div>
                            </div>
                            <div class="border rounded p-3 bg-white">
                                <strong>Ryan</strong>
                                <div class="text-muted">Hypertrophy • 1.5 km away • Mornings</div>
                            </div>
                            <div class="border rounded p-3 bg-white">
                                <strong>Nina</strong>
                                <div class="text-muted">Cardio + Mobility • 5 km away • Weekends</div>
                            </div>
                        </div>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Search Snapshot</h5>
                        <ul class="mb-3">
                            <li>12 potential partners found</li>
                            <li>Most active time: 6:00pm - 8:00pm</li>
                            <li>Top goal match: Strength</li>
                        </ul>
                        <button class="btn btn-outline-dark mt-auto" type="button">Load More Profiles</button>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>