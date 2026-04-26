# Profiles and Dashboard

## Dashboard (`/index.php`)

- The dashboard greets the current user by full name when available, otherwise by email.
- It shows profile completion percentage.
- It displays quick actions to search, message, and edit the profile.
- It shows previews of selected goals and exercises.
- It shows activity counts for matches, total messages inside matched conversations, likes sent, and likes received.

## Profile completion

Profile completion is based on whether the following are present:

- given name
- family name
- gender
- location
- date of birth
- bio/description
- preferred sessions
- at least one goal
- at least one exercise

## Profile page (`/profile.php`)

- Users can view their own profile or another user's profile.
- Another user's profile is opened by POSTing an `id` value to `/profile.php`.
- If a user record is not found, the page redirects to `/404.html`.
- The page shows email, location, gender, date of birth, preferred sessions, goals, and exercises.
- Own profile view includes actions to edit the profile, search for partners, and open messages.
- Viewing someone else's profile shows navigation back to search and back to the user's own profile.

## Edit profile (`/edit-profile.php`)

- Only logged-in users can edit their own profile.
- Given name is required.
- Gender must be one of the allowed values if provided.
- Location must be a valid location id if provided.
- Date of birth must be a valid `Y-m-d` date if provided.
- Users can add or remove multiple goals and exercises.
- Updating the profile also replaces the user's goal and exercise associations.
- Changes are wrapped in a database transaction.

## Complete profile (`/edit-profile.php`)

Users can 'complete' their profile by filling in the fields in the edit profile page accessed from the profile page (`/profile.php`)
