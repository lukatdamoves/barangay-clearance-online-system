# Security Audit

**Status: findings only. Nothing in this document has been fixed in the code.**

I went through the API surface and the data layer looking for problems. I found
some. This is the honest list, worst first, with file and line references so
you can go look yourself.

I'm publishing this repo as a portfolio piece, and I'd rather show that I can
find my own bugs than pretend they aren't there. A reviewer who reads this and
then reads the code will see I'm not exaggerating.

---

## Severity summary

Fourteen findings, worst first.

| # | Finding | Severity | Where |
|---|---|---|---|
| 1 | Plaintext Gmail app password in source | 🔴 Critical | 7 files |
| 2 | Four admin endpoints with no authorization | 🔴 Critical | 4 files |
| 3 | Unrestricted file upload → potential RCE | 🔴 Critical | `submit_form.php` |
| 4 | SQL injection | 🟠 High | `register.php:33` |
| 5 | Stored XSS in admin panel | 🟠 High | `admin.js`, 3 PHP files |
| 6 | No CSRF protection anywhere | 🟠 High | all POST endpoints |
| 7 | Weak escaping in PDF export | 🟠 High | `export_pdf.php` |
| 8 | Hardcoded auth-bypass token | 🟡 Medium | `uploads/image.php:3` |
| 9 | Plaintext passwords written to error log | 🟡 Medium | `change_password.php` |
| 10 | Error disclosure in production | 🟡 Medium | 4 files |
| 11 | No session regeneration on login | 🟡 Medium | `login.php` |
| 12 | Rate-limit counter lives in the session | 🟡 Medium | `login.php` |
| 13 | Enum/type mismatches | 🟢 Low | schema + `brgyCLRform.php` |
| 14 | Uncaught `mysqli` exception leaks absolute paths | 🟡 Medium | `db_connect.php` |

---

## 1. Plaintext Gmail app password — 🔴 Critical

**Files:** `register.php:130-131`, `register.php:216-217`, `submit_form.php:88-89`,
`business_submit_form.php:86-87`, `approve_clearance.php:66-67`,
`update_status.php:81-82`, `forgot_password.php:97-98`

```php
// as it now stands in the repo — placeholders
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password-here';
```

Seven copies of the same SMTP credential, hardcoded, committed to a public
repository. This was a Gmail **app password**, which means it granted full
send-as access to that mailbox.

**Impact:** anyone who has ever cloned this repo could send mail as that
account. It sat in a public repository for a long time, so it has to be treated
as compromised.

> **Status: redacted.** The live values have been replaced with the
> placeholders above and the app password has been rotated. The credential no
> longer appears anywhere in the tracked source. I've left the finding in this
> document because the *pattern* — hardcoded secrets in seven places, no `.env`
> — is still there and still worth knowing about.

**What was actually wrong here, beyond the leak:** the same two lines were
duplicated across seven files. That's not a typo, it's a design decision that
made a one-value change a seven-file change. The fix isn't only "use `.env`",
it's "have exactly one place that sends mail."

**Fix:** move the values into environment variables read from a `.env` that is
gitignored. There's a `.env.example` in the repo root as a starting point.

---

## 2. Missing authorization on admin endpoints — 🔴 Critical

These four endpoints change or disclose data and check nothing.

### `approve_clearance.php`
Calls `session_start()` and then never looks at it.

```php
$status  = $_POST['status']  ?? '';
$form_id = $_POST['form_id'] ?? '';
$type    = $_POST['type']    ?? '';
$user_id = $_POST['user_id'] ?? '';
// ... no check that $_SESSION['user_id'] == 1
```

Anyone can POST `status=Approved&form_id=1&type=clearance&user_id=1` and mark
any request approved, firing the approval email to the resident.

### `update_status.php`
Doesn't even call `session_start()`. No authentication of any kind. Anyone can
set any request to `Rejected` with an arbitrary reason and trigger the
rejection email.

### `delete_clearance.php`
No session, no auth. Anyone can hard-delete any clearance or business request
by ID, and it cascades to `outputed_docx`.

### `generate_docx.php`
No session, no auth. Anyone can generate and download the DOCX for any
`user_id` + `form_id` combination — which means downloading a document
containing someone else's name, address, birth date, and photo.

**Impact:** unauthenticated read of resident PII, unauthenticated destruction of
records, and unauthenticated email-triggering.

**Fix:** every one of these needs the same guard the other admin endpoints have:

```php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    http_response_code(403);
    exit('Unauthorized');
}
```

---

## 3. Unrestricted file upload — 🔴 Critical

**`submit_form.php:62-67`**

```php
$applicant_photo = "uploads/" . basename($_FILES["applicantPhoto"]["name"]);
move_uploaded_file($_FILES["applicantPhoto"]["tmp_name"], $applicant_photo);

$valid_id_photo = "uploads/" . basename($_FILES["validIDPhoto"]["name"]);
move_uploaded_file($_FILES["validIDPhoto"]["tmp_name"], $valid_id_photo);
```

Four problems in eight lines:

1. **No extension validation.** The `accept="image/*"` on the form is
   client-side and trivially bypassed with curl.
2. **No MIME validation.** Nothing checks the actual file content.
3. **No size limit.** Nothing stops a multi-gigabyte upload.
4. **`basename()` only** — no uniquifying. Two users uploading `photo.jpg`
   overwrite each other. (`business_submit_form.php` at least prefixes
   `time() . '_' . uniqid() . '_'`.)

Now combine #1 with the `.htaccess`:

```apache
<FilesMatch "\.(jpg|jpeg|png|gif|pdf)$">
    Deny from all
</FilesMatch>
```

The deny list covers image extensions but **not `.php`**. So an uploaded
`shell.php` sits in a web-reachable directory with no deny rule, and requesting
it directly executes it. That's remote code execution.

**Impact:** full server compromise.

**Fix:** allowlist `jpg/jpeg/png` by both extension *and* MIME sniff, cap the
size, generate the filename server-side, and either add `php` to the deny list
or move uploads outside the document root entirely.

---

## 4. SQL injection — 🟠 High

**`register.php:33`**

```php
$conn->query("DELETE FROM email_verifications WHERE email = '$email'");
```

`$email` comes straight from `$_POST['email']` and is interpolated directly
into the query. Every other query in the file uses `prepare()` / `bind_param()`
— this one doesn't. It's a one-line slip, but it's a real one.

Payload: `email=x' OR '1'='1` in a `mode=verify` request.

**Fix:** `$stmt = $conn->prepare("DELETE FROM email_verifications WHERE email = ?");`

---

## 5. Stored XSS — 🟠 High

### Server-side
**`structure/homepage.php:52`** and **`structure/previewpage.php:70`** /
**`previewbusiness.php:69`** echo session data with no escaping:

```php
<?php echo $_SESSION['firstname']; ?> <?php echo $_SESSION['lastname']; ?>
```

Ironically the mobile-menu copy of the same line on `homepage.php:91` *is*
escaped with `htmlspecialchars()`. I just missed the desktop one.

### Client-side
**`action/admin.js`** renders request data with `innerHTML` and no escaping, in
at least three places (`:260-281`, `:369-393`, `:480-503`):

```js
row.innerHTML = `<td>${request.name}</td><td>${request.email}</td>...`;
```

Names are sanitized client-side at registration (`validateNameInput` in
`login.js:352`), so this looks safe — but client-side validation is not a
security boundary. A direct POST to `submit_form.php` with
`firstName=<img src=x onerror=...>` is stored verbatim and fires in the admin
dashboard.

Credit where due: **`action/analytics.js` does have an `escapeHtml()` helper**
and uses it consistently. I just didn't carry that pattern over to `admin.js`.

**Fix:** `htmlspecialchars()` on every echo of user data, and an `escapeHtml()`
in `admin.js` matching the one in `analytics.js`.

---

## 6. No CSRF protection — 🟠 High

There is not a single CSRF token, nonce, or Origin check in the entire
codebase. Every state-changing POST is forgeable from another site:

- `approve_clearance.php`
- `update_status.php`
- `delete_clearance.php`
- `generate_docx.php`
- `change_password.php`
- `submit_form.php`
- `business_submit_form.php`

A logged-in admin who visits a malicious page can have requests approved,
deleted, or rejected without clicking anything.

**Fix:** per-session token, embedded in every form, verified on every POST.
Even a same-origin `Origin`/`Referer` check would close most of this.

---

## 7. Weak escaping in PDF export — 🟠 High

**`export_pdf.php:40-47`**

```php
function buildFilterCondition($column, $values) {
    $escaped = array_map('addslashes', $values);
    $in = "'" . implode("','", $escaped) . "'";
    return "$column IN ($in)";
}
```

`addslashes()` is not a SQL escaping function. It doesn't know about the
connection charset, doesn't handle multibyte character sets, and doesn't escape
`\` the way `mysql_real_escape_string` does. The values come from
`$_GET['clearanceFilters[]']` and `$_GET['businessFilters[]']`.

`month` and `year` *are* properly `intval()`'d, so those two are fine.

**Fix:** build a prepared statement with dynamic placeholders, exactly like
`fetch_analyticsdata.php` already does.

---

## 8. Hardcoded auth-bypass token — 🟡 Medium

**`uploads/image.php:3`**

```php
// as it now stands in the repo — placeholder
if (isset($_GET['token']) && $_GET['token'] === 'change-me-in-production') {
    // serve any file, no session check
}
```

A hardcoded bearer token in source control that unlocks **every uploaded file**
with no authentication. It exists so Dompdf can fetch images while rendering,
which is a legitimate problem — but this is the wrong solution to it.

> **Status: redacted.** The real token has been replaced with a placeholder.
> The structural problem remains: a static shared secret that grants
> unauthenticated access to every resident's uploaded ID and photo.

**Fix:** generate a short-lived signed URL, or have `export_pdf.php` read the
files from disk directly instead of over HTTP.

---

## 9. Plaintext passwords in the error log — 🟡 Medium

**`change_password.php:59-60`**

```php
error_log("Stored Hash: " . $hashed_password);
error_log("User Input: " . $current_password);
```

On a *failed* password change, the user's actual current password is written to
the server error log in cleartext. Server logs are usually readable by more
people than the database, and they get shipped to log aggregators.

**Fix:** delete these lines. They were debugging leftovers.

---

## 10. Error disclosure — 🟡 Medium

- **`captcha.php:2-3`** and **`fetch_analyticsdata.php:5-6`** set
  `display_errors=1` and `error_reporting(E_ALL)` — path disclosure and stack
  traces to anyone who triggers a warning.
- **`update_status.php:96, 105, 114, 119`** echoes raw `$stmt->error` and
  `$conn->error` to the client.
- **`change_password.php:39`** returns `$conn->error` inside the JSON envelope.
- **`submit_form.php:9-15`** has a stray debug `echo 'User is logged in. User ID: …'`
  that leaks into the response body before the redirect.

**Fix:** `display_errors=0` in production, log internally, return a generic
message.

---

## 11. No session regeneration — 🟡 Medium

**`login.php:86-91`** sets session variables but never calls
`session_regenerate_id(true)`. The session ID issued before login survives into
the authenticated session, which is the textbook setup for session fixation.

---

## 12. Rate-limit counter in the session — 🟡 Medium

**`login.php:104-113`**

```php
$attempt_key = "login_attempts_{$ip}_{$email}";
$_SESSION[$attempt_key] = isset($_SESSION[$attempt_key]) ? $_SESSION[$attempt_key] + 1 : 1;
```

The failed-attempt counter is stored in `$_SESSION`, so an attacker just drops
their cookie to reset it. The `ip_cooldowns` table it eventually writes to is
keyed on IP alone, so the cooldown also blocks everyone behind the same NAT —
which in a barangay hall is probably the whole office.

**Fix:** count in the database keyed on IP, not in the session.

---

## 13. Enum and type mismatches — 🟢 Low

**`structure/brgyCLRform.php:172-177`** posts lowercase civil status:

```html
<option value="single">Single</option>
<option value="married">Married</option>
```

But the column is `enum('Single','Married','Widowed','Divorced','Separated','Annulled')`
(`database/schema.sql`). Under MySQL/MariaDB strict mode the INSERT **fails
outright**; in non-strict mode it silently stores `''`. Either way the value is
wrong. The fix is to change the option values to match the enum.

Related: `clearance_request.status` is `varchar(255)` while
`business_request.status` is an `enum('For Signature','Approved','Rejected')`.
Two tables, two different types for the same concept. I'd make them consistent.

---

## 14. Uncaught `mysqli` exception leaks absolute paths — 🟡 Medium

**`db_connect.php:7-15`**

```php
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    exit(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}
```

The `if ($conn->connect_error)` check is **dead code**. Since PHP 8.1, `mysqli`
defaults to `MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT`, which means the
`new mysqli()` constructor **throws** `mysqli_sql_exception` on failure instead
of setting `connect_error`. The `exit()` on line 14 never runs.

What actually happens when MySQL is unreachable is an uncaught fatal error, and
with `display_errors` on that's dumped straight to the browser:

```
Fatal error: Uncaught mysqli_sql_exception: No connection could be made
because the target machine actively refused it in
C:\...\brgy_santo_cristo\db_connect.php:7
```

That's an **absolute server filesystem path** handed to anyone who can reach
the site while the database is down. Also relevant: the app's admin pages
`include` this file *before* the auth guard, so the error fires before any
session check — an unauthenticated request is enough to trigger it.

I found this one by accident: I stopped MySQL to test something unrelated and
every page started throwing.

**Fix:** wrap the connection in a try/catch and return a generic 500:

```php
mysqli_report(MYSQLI_REPORT_OFF);
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    error_log('DB connection failed: ' . $e->getMessage());
    exit('Service temporarily unavailable.');
}
```

---

## What's actually done right

It's not all bad — several things I got right on the first pass:

- **Password hashing** — `password_hash()` / `password_verify()` with bcrypt
  everywhere, never a custom scheme, never plaintext.
- **Prepared statements** — used in the large majority of queries. The SQLi in
  finding 4 is the exception, not the rule.
- **Ownership scoping** — `previewpage.php`, `previewbusiness.php`,
  `fetch_application.php` and `fetch_profilehistory.php` all correctly check
  that the row belongs to the caller.
- **Upload serving** — `uploads/.htaccess` + `uploads/image.php` ownership
  checks is the right *idea*, just with the gaps in findings 3 and 8.
- **CAPTCHA** — GD-generated, custom, no third-party dependency, and the
  alphabet excludes visually ambiguous characters.
- **`fetch_analyticsdata.php`** — the one place I built the `IN (...)` clause
  with dynamic bound placeholders instead of string interpolation.
- **`action/analytics.js`** — has and consistently uses `escapeHtml()`.

---

## Recommended order of work

1. **Move secrets out of source.** The live values are already gone from this
   repo, but the pattern is still there — two lines duplicated across seven
   files. One mailer class, one config read.
2. Add the admin guard to `approve_clearance.php`, `update_status.php`,
   `delete_clearance.php`, `generate_docx.php`.
3. Fix the upload validation in `submit_form.php`.
4. Fix the SQLi in `register.php:33`.
5. Wrap the DB connection in `db_connect.php` so a DB outage can't dump a
   stack trace to an unauthenticated visitor.
6. Add `escapeHtml()` to `admin.js`, `htmlspecialchars()` to the PHP echoes.
7. Add CSRF tokens.
8. Everything else.

Items 1–5 are the ones that actually matter for a deployment. Items 6–8 are
hardening.

---

## Note on scope

I wrote this audit as part of cleaning the repo up for republication. I
deliberately did **not** fix any of these in the same pass, because a code
change and a security review want different kinds of attention, and mixing
them means the review doesn't get a clean read.

The findings above are accurate as of the current commit. If you're viewing
this and the code has moved on, re-run the check.
