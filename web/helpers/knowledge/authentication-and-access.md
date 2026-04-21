# Authentication and Access

## Login flow

- Users log in with email and password.
- Passwords are verified against a stored password hash.
- If credentials are valid, the app stores session values including user id, email, type, and given name.
- Admin users are redirected to `/admin.php` after login.
- Standard users are redirected to `/index.php` after login.

## Registration flow

- Registration requires email, first name, last name, gender, date of birth, location, password, and password confirmation.
- Email must be valid.
- Gender must be one of: male, female, or other.
- Password must be at least 8 characters.
- Password and confirmation must match.
- User must be at least 18 years old.
- Registration fails if the email already exists.
- Successful registration creates both a user account and a profile row.

## Account restrictions

- Banned users cannot log in successfully.
- Logged-out users trying to access protected pages are redirected to `/login.php?error=unauthorized`.
- Only admins can access `/admin.php`.

## Logout

- Logging out clears the session and destroys the session cookie, then redirects to `/login.php`.
