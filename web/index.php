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
                    <a class="menu-item active" href="#">Home</a>
                    <a class="menu-item" href="/message.php">Messages</a>
                    <a class="menu-item" href="/search.php">Search</a>
                    <a class="menu-item" href="/profile.php">Profile</a>
                    <a class="menu-item" href="/helpers/auth.php?action=logout">Logout</a>
                </nav>
            </aside>

            <main class="content">
                <section class="top-rectangle p-4">
                    <div class="w-100">
                        <h3 class="mb-2">Welcome back!</h3>
                        <p class="text-muted mb-3">Plan your sessions, connect with partners, and stay consistent this week.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-dark" href="/search.php">Find Partners</a>
                            <a class="btn btn-outline-dark" href="/message.php">Open Messages</a>
                            <a class="btn btn-outline-dark" href="/profile.php">View Profile</a>
                        </div>
                    </div>
                </section>
                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Upcoming Workouts</h5>
                        <ul class="mb-0">
                            <li>Mon - Upper Body (7:00pm)</li>
                            <li>Wed - Lower Body (6:30pm)</li>
                            <li>Sat - Cardio & Mobility (10:00am)</li>
                        </ul>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Weekly Snapshot</h5>
                        <div class="w-100 d-flex flex-column gap-2">
                            <div class="border rounded p-2 bg-white">Workouts completed: <strong>3</strong></div>
                            <div class="border rounded p-2 bg-white">New matches: <strong>2</strong></div>
                            <div class="border rounded p-2 bg-white">Messages sent: <strong>11</strong></div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>