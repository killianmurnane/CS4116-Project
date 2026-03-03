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
                    <a class="menu-item active" href="#">Messages</a>
                    <a class="menu-item" href="/search.php">Search</a>
                    <a class="menu-item" href="/profile.php">Profile</a>
                </nav>
            </aside>

            <main class="content">
                <section class="top-rectangle p-4">
                    <div class="w-100">
                        <h3 class="mb-2">Messages</h3>
                        <p class="text-muted mb-3">Catch up with your gym partners and schedule your next session.</p>
                        <div class="d-flex gap-2">
                            <input class="form-control" type="text" placeholder="Search conversations..." />
                            <button class="btn btn-dark" type="button">New Chat</button>
                        </div>
                    </div>
                </section>

                <section class="bottom-split">
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Conversations</h5>
                        <div class="list-group w-100">
                            <a href="#" class="list-group-item list-group-item-action active" aria-current="true">Alex - Leg Day Plan</a>
                            <a href="#" class="list-group-item list-group-item-action">Sam - Spotter Needed</a>
                            <a href="#" class="list-group-item list-group-item-action">Chris - Weekend Run</a>
                            <a href="#" class="list-group-item list-group-item-action">Jordan - Meal Prep Tips</a>
                        </div>
                    </div>
                    <div class="split-panel p-4 d-flex flex-column align-items-start justify-content-start">
                        <h5 class="mb-3">Chat Preview</h5>
                        <div class="w-100 d-flex flex-column gap-2 mb-3">
                            <div class="bg-light border rounded p-2 w-75">Alex: Up for squats at 7pm?</div>
                            <div class="bg-dark text-white rounded p-2 w-75 align-self-end">You: Yep, I’ll be there!</div>
                            <div class="bg-light border rounded p-2 w-75">Alex: Perfect, I’ll book the rack.</div>
                        </div>
                        <div class="input-group mt-auto">
                            <input class="form-control" type="text" placeholder="Type a message..." />
                            <button class="btn btn-dark" type="button">Send</button>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>