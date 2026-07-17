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

## cPanel Deployment Setup
1. Create the repository in cPanel Git Version Control and make sure [`.cpanel.yml`](.cpanel.yml) is committed and pushed.
2. In cPanel `MySQL Databases`, create:
   - Database: `tektxbzg_mchungi`
   - Database user: `tektxbzg_mchungi`
   - Assign the user to the database with all privileges.
3. Import [`database/schema.sql`](database/schema.sql) in `phpMyAdmin`.
   - The schema includes `CREATE DATABASE` and `USE` statements for supported hosts.
   - If your host blocks `CREATE DATABASE` during import, create the database in cPanel first, then re-import the schema.
4. Copy [`.env.example`](.env.example) to `.env` on the server and set the real values, especially:
   - `RGC_DB_PASS`
   - `RGC_DONATE_PAYSTACK_URL`
   - `RGC_ADMIN_REG_KEY`
   - `RGC_SUPER_ADMIN_REG_KEY`
5. Confirm your site URL is `https://tektrend.online`.
6. Open `https://tektrend.online/admin/register.php` to create the first admin account.

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

## Mail Setup
- Set in `.env`:
  - `RGC_MAIL_MODE=test`
  - `RGC_MAILBOX_KEY=your-secret`
- OTP emails are captured into DB table `outbound_emails`.
- View mailbox in browser:
  - `/redeemed-gospel-church/mailbox.php?key=your-secret`
- For production with SMTP, set:
  - `RGC_MAIL_MODE=smtp`
  - `RGC_MAIL_FROM=you@yourdomain.com`
  - `RGC_MAIL_FROM_NAME=Redeemed Gospel Church`
  - `RGC_SMTP_HOST=mail.yourdomain.com`
  - `RGC_SMTP_PORT=587`
  - `RGC_SMTP_USERNAME=you@yourdomain.com`
  - `RGC_SMTP_PASSWORD=your-email-password`
  - `RGC_SMTP_ENCRYPTION=tls`
- Supported mail modes:
  - `test` = store emails in DB mailbox only
  - `phpmail` = use PHP `mail()`
  - `smtp` = send through authenticated SMTP

## Payments
- Public giving now supports:
  - Church Paybill `122766`
  - Account references: `Offering`, `Tithe`, `Donation`
  - Paystack via external payment link
  - Bank detail request via WhatsApp or email
  - Cash / in-person giving
- Configure these in `.env`:
  - `RGC_DONATE_PAYSTACK_URL`
  - `RGC_DONATE_PAYBILL_NUMBER`
  - `RGC_DONATE_CONTACT_WHATSAPP`
  - `RGC_DONATE_CONTACT_EMAIL`

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
