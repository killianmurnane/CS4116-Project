# Search, Likes, and Matches

## Search page (`/search.php`)

- Search is only available to logged-in users.
- Results are paginated with a limit of 5 users per page.
- `Load More` increments the page offset and keeps current filters.
- If no filters are applied, the site shows recent users with profiles.
- Banned users are excluded from search results.
- The current logged-in user is excluded from search results.

## Available filters

- first name search
- goal
- location
- gender
- exercise
- minimum age
- maximum age

## Result card behavior

Each result can show:

- name or fallback user id
- age (calculated from date of birth)
- location
- short description preview
- `View Profile` button
- a heart button to like the user
- a `Message` button if the users are already matched
- a check icon if the current user already liked that profile

## Like behavior

- Likes are submitted to `/helpers/like.php` with `liked_user_id`.
- Duplicate likes are rejected and return `like_exists`.
- If the other user has already liked back, the system creates a match and returns a match success state.
- If a like succeeds without mutual interest, the UI reports a normal like success state.

## Match rules

- A match is created when two users like each other.
- Matches are stored as rows containing `user1_id` and `user2_id`.
- Search uses existing matches to show a direct `Message` button for matched users.
