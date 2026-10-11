# Deployment Guide

How to get this running locally, and what has to change before it goes
anywhere near the internet.

---

## Part 1 — Local development (XAMPP)

### Prerequisites

- XAMPP with **PHP 8.2+** and **MariaDB 10.4+** (any recent XAMPP has both)
- [Composer](https://getcomposer.org/)
- PHP extensions: `gd` (CAPTCHA), `fileinfo`, `mbstring`, `openssl`, `curl`

### 1. Clone and install dependencies

```bash
git clone https://github.com/lukatdamoves/brgy_santo_cristo_2.0.git
cd brgy_santo_cristo_2.0/brgy_santo_cristo
composer install
```

`vendor/` is gitignored, so this step is mandatory. The app will white-screen
without it.

### 2. Create the database

```bash
# from XAMPP's mysql bin, or wherever your mysql client is
mysql -u root -e "CREATE DATABASE barangay_clearance CHARACTER SET utf8mb4;"
mysql -u root barangay_clearance < ../database/schema.sql
```

The schema ships with a small set of **obviously fake** demo rows so you have
something to look at.

| Account | Password | Role |
|---|---|---|
| `admin@example.com` | `demo1234` | admin (`user_id` 1) |
| `juan.delacruz@example.com` | `resident1234` | resident |

The names, emails, and photos in the demo data are synthetic. No real
personal information is in this repo.

### 3. Configure the database connection

Edit `brgy_santo_cristo/db_connect.php`:

```php
$servername = "localhost";
$username   = "root";
$password   = "";                              // your MySQL password
$dbname     = "barangay_clearance";            // matches the CREATE DATABASE above
```

The database name in `db_connect.php` is a placeholder (`barangay_clearance`).
If you're running locally, keep it or change it — just make sure it matches the
`CREATE DATABASE` you ran above.

### 4. Serve it

Drop the repo into `C:\xampp\htdocs\` and start Apache + MySQL, then visit:

```
http://localhost/brgy_santo_cristo_2.0/
```

The root `index.php` redirects into `brgy_santo_cristo/structure/homepage.php`.

> The app uses **web-root-absolute** paths in a few places
> (`header("Location: /brgy_santo_cristo/structure/homepage.php")`). That means
> the `brgy_santo_cristo` folder must sit directly under the web root — it
> won't work correctly in a deeper subdirectory. This is documented as a
> limitation; I never fixed it.

### 5. Create the upload directories

```bash
mkdir -p brgy_santo_cristo/uploads brgy_santo_cristo/output_docx
```

Both are gitignored but the `.htaccess` in `uploads/` is tracked, so the deny
rule comes with the repo. Make sure Apache is actually reading it —
`AllowOverride All` has to be set for the directory.

---

## Part 2 — Email

Every notification goes through Gmail SMTP. The source currently ships with
**placeholders** — it will not send mail until you put real values in.

1. Generate a [Google App Password](https://myaccount.google.com/apppasswords)
   (requires 2FA on the account). Use that, not your normal Gmail password.
2. Replace the placeholders in all seven locations:

| File | Lines |
|---|---|
| `register.php` | 130-131, 216-217 |
| `submit_form.php` | 88-89 |
| `business_submit_form.php` | 86-87 |
| `approve_clearance.php` | 66-67 |
| `update_status.php` | 81-82 |
| `forgot_password.php` | 97-98 |

You're looking for these two lines in each:

```php
$mail->Username = 'your-email@gmail.com';        // ← your address
$mail->Password = 'your-app-password-here';      // ← the app password
$mail->setFrom('your-email@gmail.com', 'Barangay Santo Cristo Online Clearance');
```

Better still, read them from the environment so they never touch source:

```php
$mail->Username = getenv('SMTP_USERNAME');
$mail->Password = getenv('SMTP_PASSWORD');
```

There's a `.env.example` at the repo root as a starting point.

> Gmail's SMTP has a sending limit (roughly 500 recipients/day). For a barangay
> that's plenty. If you ever outgrow it, move to a transactional provider.

---

## Part 3 — Production checklist

### Before you go live

- [ ] **Put real SMTP credentials in** — the source ships with placeholders and
      sends no mail until you do
- [ ] **Change the database name and credentials** in `db_connect.php`
- [ ] **Set a strong password on the admin account** and change its email
- [ ] **Fix the four unauthenticated endpoints** — `approve_clearance.php`,
      `update_status.php`, `delete_clearance.php`, `generate_docx.php`. See
      [security-audit.md](security-audit.md) finding #2.
- [ ] **Add upload validation** — extension allowlist, MIME check, size cap
- [ ] **Turn off `display_errors`** in `captcha.php` and
      `fetch_analyticsdata.php`
- [ ] **Remove the debug `error_log()` calls** in `update_status.php` and
      `change_password.php`
- [ ] **Remove the hardcoded token** in `uploads/image.php`
- [ ] **Add CSRF tokens** to every POST form
- [ ] Serve over **HTTPS**
- [ ] Set `uploads/` and `output_docx/` to be writable by the web server, and
      confirm `.htaccess` is being applied

### Apache

Minimum config for the app to work:

```apache
<VirtualHost *:80>
    ServerName clearance.example.com
    DocumentRoot /var/www/brgy_santo_cristo_2.0

    <Directory /var/www/brgy_santo_cristo_2.0>
        AllowOverride All      # required for uploads/.htaccess
        Require all granted
    </Directory>

    ErrorLog  ${APACHE_LOG_DIR}/clearance-error.log
    CustomLog ${APACHE_LOG_DIR}/clearance-access.log combined
</VirtualHost>
```

`AllowOverride All` matters — without it the `uploads/.htaccess` deny rule is
ignored and every resident's ID photo becomes publicly readable by URL.

### PHP settings

```ini
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
upload_max_filesize = 8M
post_max_size = 10M
max_file_uploads = 10
```

### Database

```sql
CREATE DATABASE barangay_clearance CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'clearance_app'@'localhost' IDENTIFIED BY 'a-real-password';
GRANT SELECT, INSERT, UPDATE, DELETE ON barangay_clearance.* TO 'clearance_app'@'localhost';
FLUSH PRIVILEGES;
```

Don't run the app as `root`.

---

## Part 4 — Shared hosting

This is how the original was deployed (InfinityFree), and it's why the code is
shaped the way it is — no shell access, no Composer on the server, so `vendor/`
gets uploaded over FTP.

1. Upload everything **except** `.git/` to `htdocs/`.
2. Run `composer install` locally, then upload the resulting `vendor/` folder.
3. Create the MySQL database in the hosting panel. **Most shared hosts prefix
   the database name with your account ID** — whatever name you're given, that's
   what goes in `db_connect.php`.
4. Import `database/schema.sql` through phpMyAdmin.
5. Create `uploads/` and `output_docx/` and make sure they're writable.
6. Update the SMTP credentials.

### Things that bite you on shared hosting

- **`vendor/` must be uploaded.** There's no SSH to run Composer.
- **`.htaccess` support varies.** Confirm the upload deny rule actually works
  by requesting an uploaded image directly — you should get a 403.
- **No cron.** Anything scheduled has to be triggered by page loads.
- **Email limits are stricter** than Gmail's own.

---

## Part 5 — Backups

The database is the only thing with real value in it — the DOCX files can be
regenerated, and the uploads can be re-collected from residents if the worst
happens.

```bash
mysqldump -u root barangay_clearance | gzip > clearance-$(date +%F).sql.gz
```

Back up `uploads/` too if you care about not re-asking people for their IDs.

---

## Known deployment limitations

- **Web-root-absolute paths.** `/brgy_santo_cristo/structure/...` appears in
  `index.php`, `logout.php`, `submit_form.php`, and
  `business_submit_form.php`. The app must live at the web root.
- **Admin is `user_id == 1`.** No role system, so no second admin, and you
  can't renumber or delete the first one.
- **Session-based rate limiting.** The failed-login counter lives in the
  session, so clearing cookies resets it.
- **`vendor/` isn't in git.** Any deployment needs a `composer install` step.
