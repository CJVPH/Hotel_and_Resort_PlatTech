# Google OAuth Integration

## Overview
This project includes Google OAuth 2.0 authentication, allowing users to sign in with their Google accounts.

## Configuration

### Google OAuth Credentials
- **Client ID**: `829858353753-5er1pes529q7rugedqvrpjfgekqmf5c5.apps.googleusercontent.com`
- **Client Secret**: `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`

### Google Console Setup
1. Go to [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Find your OAuth 2.0 Client ID
3. Add the correct **Authorized redirect URI**:
   - Format: `http://localhost/[your-project-folder]/redirect.php`
   - Example: `http://localhost/hotel_and_resort/redirect.php`

## Files

### Core Files
- `config/google_oauth.php` - OAuth configuration class
- `redirect.php` - OAuth callback handler
- `login.php` - Login page with Google button
- `register.php` - Register page with Google button

### Admin Files
- `admin/google_oauth_status.php` - OAuth monitoring dashboard

## How It Works

1. User clicks "Continue with Google" button
2. Redirected to Google for authentication
3. User authorizes the application
4. Google redirects back to `redirect.php` with authorization code
5. System exchanges code for access token
6. Retrieves user profile from Google
7. Creates new account or logs in existing user
8. Redirects to home page

## Database

The `users` table includes a `google_id` column to link Google accounts.

## Troubleshooting

### "Not Found" Error
- Ensure the redirect URI in Google Console matches your project URL exactly
- Check that `redirect.php` exists in your project root

### OAuth Not Working
- Verify Google Console credentials are correct
- Check that redirect URI is properly configured
- Ensure database has `google_id` column in users table

## Security

- Uses official Google OAuth 2.0 flow
- Secure token exchange
- Random passwords generated for Google-only accounts
- Session-based authentication