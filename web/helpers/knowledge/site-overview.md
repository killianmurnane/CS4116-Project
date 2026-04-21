# Site Overview

GymDate is a student gym partner matching website.

## Main purpose

- Help users find training partners.
- Let matched users message each other.
- Let users maintain a fitness profile with goals, exercises, and availability.

## Main pages

- `/login.php`: login and registration.
- `/index.php`: logged-in dashboard and activity summary.
- `/search.php`: discover users and apply filters.
- `/profile.php`: view a profile.
- `/edit-profile.php`: update the current user's profile.
- `/message.php`: see matches and exchange messages.
- `/admin.php`: admin-only account and content management.

## Navigation rules

- Most pages require login.
- Admin page requires the user to have admin role.
- Logged-out users are redirected to the login page if they try to access protected pages.

## Core data shown across the site

- User account email and role.
- Profile data such as name, gender, date of birth, location, bio, and preferred sessions.
- Goal and exercise tags.
- Likes, matches, and messages.
