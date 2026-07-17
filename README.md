# Redeemed Gospel Church Website (PHP + Tailwind CDN)

## Run Locally
1. Start Apache and MySQL in XAMPP (or point to remote MySQL server).
2. Import [`database/schema.sql`](database/schema.sql).
3. Configure environment variables in [`.env`](.env) (loaded by [`includes/config.php`](includes/config.php)):
   - `RGC_USE_DB_AUTH=1`
   - `RGC_DB_HOST`
   - `RGC_DB_PORT`
   - `RGC_DB_NAME`
   - `RGC_DB_USER`
   - `RGC_DB_PASS`
   - Optional: `RGC_DB_SSL_CA`, `RGC_BASE_URL`, `RGC_DEBUG_SHOW_OTP=1`
   - Use [`.env.example`](.env.example) as template for production values.
4. Open site: `http://localhost/redeemed-gospel-church/`
5. Admin login: `http://localhost/redeemed-gospel-church/admin/login.php`

## Admin Account Lifecycle
- Register: `/admin/register.php`
- Activate account from generated activation link: `/admin/activate.php?...`
- Login now requires:
  - Username + password
  - Verification code (OTP)
- Brute-force protection:
  - Account lockout after repeated failed attempts
  - IP-based throttling and suspicious client blocks
- Security logs:
  - `/admin/security_logs.php` (super admin only)

## Data Storage
- The app now stores dynamic content in MySQL tables (no JSON mock/seed runtime data):
  - settings, sermons, events, ministries, projects, testimonials, gallery, comments, prayer requests, users, security logs
- Re-import [`database/schema.sql`](database/schema.sql) to create/update all required tables.
- Optional one-time migration from existing JSON files:
  - `php database/migrate_json_to_db.php`

## Test Mailer (No SMTP)
- Set in `.env`:
  - `RGC_MAIL_MODE=test`
  - `RGC_MAILBOX_KEY=your-secret`
- OTP emails are captured into DB table `outbound_emails`.
- View mailbox in browser:
  - `/redeemed-gospel-church/mailbox.php?key=your-secret`
- For production, switch to:
  - `RGC_MAIL_MODE=phpmail`

## Payments (Hardened Endpoints)
- `api/paypal_record.php` now verifies PayPal order server-side before marking donation as received.
- `api/mpesa_stk.php` now validates CSRF, donation ownership, and pending state before sending STK push.
- M-Pesa callback endpoint:
  - `api/mpesa_webhook.php`
  - Protect with `RGC_MPESA_WEBHOOK_KEY` and include same key in callback URL.

## Maintenance Mode
- Public visitors are redirected to maintenance page when enabled.
- Logged-in admins and super admins can still access admin pages to make updates.

## Implemented
- DB-backed admin auth with prepared statements
- Registration + activation link flow for admin/super admin
- OTP verification on login
- CSRF protection on POST forms
- Security logging and lockout controls
- Broadcast banner and maintenance controls
- Sermons, events, content modules, and prayer inbox
