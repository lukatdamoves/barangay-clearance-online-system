# API Reference

Every HTTP endpoint in the Barangay Clearance Online System, what it accepts,
what it returns, and whether it actually checks who you are.

I wrote this by reading the source, not from memory, so the response shapes
below are what the code actually emits — including the ugly parts.

---

## Response conventions

There are three different conventions in this codebase, which is a mess but
it's what's there:

| Style | Used by | Shape |
|---|---|---|
| Bare string token | `login.php` | `success`, `admin`, `invalid`, `cooldown\|60`, `verify_pending`, `server_error` |
| JSON envelope | `register.php`, `change_password.php`, `check_session.php`, all `fetch_*.php` / `get_*.php` | `{"status": "success"\|"error"\|"exists", "message": "..."}` |
| HTML fragment | `adminpage.php`, `analyticspage.php`, `previewpage.php` | Server-rendered rows / full pages |

The bare-string convention on `login.php` is the one that surprised me most
when I came back to this after a few months — there's no `status` key, the
whole response body *is* the status. `action/login.js` branches on it.

---

## Authentication model

Sessions only. No JWTs, no API keys, no remember-me tokens.

On successful login, `login.php` writes these into `$_SESSION`:

```
user_id, firstname, middlename, lastname, namesuffix, email
```

**Admin is `user_id == 1`.** That's it. There is no `role` column anywhere in
the schema. Every privileged endpoint does a literal `$_SESSION['user_id'] != 1`
check. I've flagged this in [security-audit.md](security-audit.md) because it
means you can never have a second admin without a schema change.

---

## Public endpoints

### `GET captcha.php`
Generates the CAPTCHA PNG. 100×40 canvas, 5 characters drawn from
`ABCDEFGHJKLMNPQRSTUVWXYZ23456789` (no `I`, `O`, `0`, or `1` — I removed the
ambiguous glyphs after users kept complaining they couldn't read them). The
answer goes into `$_SESSION['captcha']`.

Returns `image/png`.

> Note: `login.php` compares the submitted code case-**sensitively** while
> `register.php` compares case-**insensitively**. Inconsistent, documented in
> the audit.

### `POST login.php`
| Field | Notes |
|---|---|
| `email` | |
| `password` | |
| `captcha` | checked against `$_SESSION['captcha']` |
| `type` | `login` or `forgot_password` — selects which cooldown bucket applies |

Returns one of:

| Response | Meaning |
|---|---|
| `success` | logged in, resident |
| `admin` | logged in, `user_id == 1` |
| `invalid` | wrong email or password |
| `cooldown\|<seconds>` | rate-limited, seconds remaining |
| `verify_pending` | email not yet verified |
| `server_error` | statement failed |

### `POST register.php`
Multi-purpose endpoint driven by `mode`.

**`mode=register`** (default) — fields: `firstname`, `middlename`, `lastname`,
`namesuffix`, `email`, `password`, `captcha`.

- Password must be ≥ 8 characters
- Hashed with `password_hash($password, PASSWORD_DEFAULT)` (bcrypt)
- Emails a 6-digit code, valid **5 minutes**, stored in `email_verifications`

**`mode=verify`** — fields: `email`, `code`.
**`mode=cancel`** — fields: `email`. Deletes the user row *and* the code.
**`mode=clear_session`** — fields: `email`. Just unsets the session key.
**`resend_email=<email>`** — issues a fresh code.

Returns the JSON envelope.

### `POST forgot_password.php`
Three-step reset, all in one file, no DB token — the code lives in the session.

| `action` | Fields | Behaviour |
|---|---|---|
| `send_code` | `email` | 6-digit code → `$_SESSION['code']` → email |
| `verify_code` | `email`, `code` | compares against session |
| `change_password` | `email`, `password` | re-hashes and updates |

After 3 wrong codes, writes a **3-minute** cooldown into `ip_cooldowns`.

### `GET check_session.php`
```json
{"loggedIn": true}
```
Used by `action/homefunction.js` to decide whether "Apply" goes to the services
page or the login page.

### `GET logout.php`
`session_unset()` + `session_destroy()`, then redirects to the homepage.

---

## Resident endpoints (require `$_SESSION['user_id']`)

### `POST submit_form.php`
Individual barangay clearance application. Also does a **multipart file upload**
of two images.

| Field | Notes |
|---|---|
| `firstName`, `middleName`, `lastName`, `namesuffix` | name is pre-filled and `readonly` on the form, but still POSTed |
| `contact`, `address` | |
| `date` | submission date |
| `age` | calculated client-side, ≥ 18 enforced |
| `sex`, `civil-status` | `civil-status` posts **lowercase** — see audit finding |
| `birthDate`, `birthPlace`, `nationality`, `sinceWhen`, `purpose`, `additional-info` | |
| `applicantPhoto` | file |
| `validIDPhoto` | file |

On success: emails a confirmation, then `302` to
`previewpage.php?id=<inserted_id>&type=clearance`.

### `POST business_submit_form.php`
Business clearance application. Same shape, plus `applicationType` (`New` /
`Renewal`) which reveals the extra renewal-only upload fields.

Uploads up to five files: `dtiSecRegistration`, `leaseOfContract`, `landTitle`,
`oldBarangayClearance`, `oldMayorsPermit`. Filenames get a
`time() . '_' . uniqid() . '_'` prefix here, unlike `submit_form.php`.

On success: `302` to `previewbusiness.php?form_id=<id>&type=business`.

### `POST change_password.php`
| Field | Notes |
|---|---|
| `current_password` | verified with `password_verify()` |
| `new_password` | ≥ 8 chars, re-hashed |

Returns the JSON envelope.

### `GET fetch_application.php`
Returns the caller's own submissions, joined against `outputed_docx` so the UI
knows which ones already have a generated document.

### `GET fetch_profilehistory.php`
Request history for the profile page. Also handles a `delete` action that
removes a request — but only if its status isn't already `For Signature`,
`Approved`, or `Rejected`, so residents can only cancel fresh submissions.

### `GET previewpage.php` / `GET previewbusiness.php`
Read-only preview. Owner-scoped: you can only see your own, unless you're
`user_id == 1`. Accepts `?embed=1` to render bare for the admin iframe modal.

### `GET uploads/image.php`
Serves uploaded files. Enforces ownership by `LIKE`-matching the filename
against `clearance_request.applicant_photo` / `valid_id_photo` and the five
business columns, plus a blanket allow for `user_id == 1`.

Direct access to the raw files is blocked by `uploads/.htaccess`.

---

## Admin endpoints

### `GET adminpage.php`
Dashboard. Four tabs — Clearance Request, Business Request, Approved
Clearances, Rejected Requests — all rendered client-side from the JSON feeds
below by `action/admin.js`. Auto-refreshes every 10 seconds.

### `GET get_clearance_requests.php`
`JOIN users`, `LEFT JOIN outputed_docx` on `clearance_request`.

### `GET get_business_requests.php`
Same shape against `business_request`.

### `GET analyticspage.php`
Month picker, two multi-select filters, three count cards, paginated table,
Export to PDF.

### `GET fetch_analyticsdata.php`
Drives the analytics page. Accepts `month`, `year`, `clearanceFilters[]`,
`businessFilters[]`, and a name search. Builds the `IN (...)` clause with
dynamically-bound placeholders, which is the one place I did it properly.

### `GET export_pdf.php`
Admin-only Dompdf report. Builds HTML with inline CSS, embeds each applicant
photo as a base64 data URI, streams `analytics_report.pdf`.

### `POST generate_docx.php`
Fills `docx_templates/clearance_template.docx` or `business_template.docx`
via PhpWord's `TemplateProcessor`, writes to `output_docx/{type}_{id}.docx`,
and records a row in `outputed_docx`. Refuses to generate twice for the same
request.

| Field | Notes |
|---|---|
| `type` | `clearance` or `business` |
| `form_id` | |
| `user_id` | |

### `POST approve_clearance.php`
Sets status to `Approved` and emails the resident that they can collect their
document.

### `POST update_status.php`
Sets an arbitrary status. `Rejected` triggers an email that includes a
free-text reason.

### `POST delete_clearance.php`
Hard-deletes the request. Cascades to `outputed_docx`.

---

## Auth status at a glance

This is the table I'd want if I were picking this project up cold.

| Endpoint | Session | Owner-scoped | Admin-gated |
|---|---|---|---|
| `captcha.php` | — | — | — |
| `login.php`, `register.php`, `forgot_password.php` | — | — | — |
| `check_session.php`, `logout.php` | ✅ | — | — |
| `change_password.php` | ✅ | ✅ | — |
| `submit_form.php`, `business_submit_form.php` | ✅ | ✅ | — |
| `fetch_application.php`, `fetch_profilehistory.php` | ✅ | ✅ | — |
| `previewpage.php`, `previewbusiness.php` | ✅ | ✅ | — |
| `uploads/image.php` | ✅ | ✅ | ✅ (allow-all) |
| `adminpage.php`, `analyticspage.php` | ✅ | — | ✅ |
| `get_clearance_requests.php`, `get_business_requests.php` | ✅ | — | ✅ |
| `fetch_analyticsdata.php`, `export_pdf.php` | ✅ | — | ✅ |
| **`approve_clearance.php`** | ✅ | — | 🔴 **none** |
| **`update_status.php`** | 🔴 **none** | — | 🔴 **none** |
| **`delete_clearance.php`** | 🔴 **none** | — | 🔴 **none** |
| **`generate_docx.php`** | 🔴 **none** | — | 🔴 **none** |

Four endpoints that mutate or disclose data have no authorization check at all.
That's the headline finding of [security-audit.md](security-audit.md) — I'm
documenting it, not fixing it in this pass.

---

## What I'd change if I picked this back up

- Pick **one** response convention. I'd go JSON envelope everywhere and
  migrate `login.php` off bare strings.
- Add a `role` column and replace the `user_id == 1` checks with a real
  `require_admin()` include.
- Add CSRF tokens to every POST.
- Add the missing auth checks on the four endpoints above.
- Validate uploads server-side: extension allowlist, MIME sniff, size cap,
  and store outside the web root.
