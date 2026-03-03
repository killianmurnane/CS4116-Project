<html>
    <head>
        <!-- CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="/css/base.css" />
        <!-- Other -->
        <title>GymDate</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <?php $userId = $_GET['userId'] ?? null; ?>
    </head>
    <body>
        <div class="container">
            <aside class="sidebar">
                <h2 class="sidebar-title">GymDate</h2>
                <nav class="menu" aria-label="Main menu">
                    <a class="menu-item" href="/">Home</a>
                    <a class="menu-item" href="/message.php">Messages</a>
                    <a class="menu-item" href="/search.php">Search</a>
                    <a class="menu-item active" href="#">Profile</a>
                </nav>
            </aside>

            <main class="content">
                <section class="top-rectangle p-4">
                    <div class="w-100 d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-secondary" style="width: 72px; height: 72px;"></div>
                        <div>
                            <h3 class="mb-1">Your Profile <?php echo $userId
                              ? '#' . htmlspecialchars($userId)
                              : ''; ?></h3>
                            <p class="text-muted mb-0">Intermediate lifter • 4 workouts/week • Goal: Build strength</p>
                        </div>
                    </div>
                </section>

                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">About</h5>
                        <p class="mb-2">Focused on compound lifts and consistent progression.</p>
                        <p class="mb-3">Looking for accountability partners for evening sessions.</p>
                        <h6 class="mb-2">Preferred Sessions</h6>
                        <ul>
                            <li>Mon/Wed/Fri - Strength blocks</li>
                            <li>Saturday - Recovery cardio</li>
                        </ul>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Recent Activity</h5>
                        <div class="w-100 d-flex flex-column gap-2 mb-3">
                            <div class="border rounded p-2 bg-white">Completed: Push Day (90 min)</div>
                            <div class="border rounded p-2 bg-white">Matched with 2 new partners</div>
                            <div class="border rounded p-2 bg-white">Sent 4 messages this week</div>
                        </div>
                        <button class="btn btn-dark mt-auto" type="button">Edit Profile</button>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>