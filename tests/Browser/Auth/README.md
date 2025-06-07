# Authentication Dusk Tests

This directory contains end-to-end tests for authentication features using Laravel Dusk.

## Test Files

- **RegistrationTest.php**: Tests for user registration functionality
- **LoginTest.php**: Tests for user login functionality
- **LogoutTest.php**: Tests for user logout functionality
- **PasswordResetTest.php**: Tests for password reset functionality
- **EmailVerificationTest.php**: Tests for email verification functionality

## Running the Tests

To run all Dusk tests:

```bash
php artisan dusk
```

To run only authentication tests:

```bash
php artisan dusk --group=auth
```

## Test Coverage

### Registration Tests
- Viewing registration page
- Registering with valid credentials
- Validation for invalid email
- Validation for password confirmation mismatch

### Login Tests
- Viewing login page
- Logging in with correct credentials
- Validation for incorrect email
- Validation for incorrect password
- Redirection to intended page after login

### Logout Tests
- Logging out as an authenticated user
- Redirection to login for logged out users accessing protected routes

### Password Reset Tests
- Viewing forgot password page
- Requesting password reset link
- Behavior for non-existent email
- Resetting password with valid token
- Validation for invalid token

### Email Verification Tests
- Verification notice for unverified users
- No verification notice for verified users
- Requesting a new verification link
- Verifying email with valid verification link

## Notes

- These tests assume a specific UI structure. You may need to adjust selectors like `#user-menu-button` and button text like `Log in` to match your application's UI.
- Some tests create test users with specific email addresses. Make sure these don't conflict with existing users in your database.
- The email verification test uses `Event::fake()` to verify that the `Verified` event is dispatched, which is a combination of browser testing and event testing.
