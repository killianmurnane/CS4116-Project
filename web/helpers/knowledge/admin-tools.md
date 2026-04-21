# Admin Tools

## Access

- `/admin.php` is restricted to admin users only.
- Non-admin users are redirected away from the admin page.

## User search and selection

- Admins can search users by email or full name.
- The user list includes role/type, email, and location when available.
- The selected user record is shown in the detail panel.

## Admin actions

Admins can:

- change another user's role to `admin`, `standard`, or `banned`
- edit another user's profile fields
- clear a user's profile description
- delete messages sent by the selected user

## Restrictions and details

- Admins cannot change their own account role from this action form.
- Profile updates validate location ids and accept optional fields such as family name, gender, date of birth, description, and preferred sessions.
- The admin view shows recent messages sent by the selected user, limited to 12 messages.
- Success and failure states are shown as flash/error alerts on the page.
