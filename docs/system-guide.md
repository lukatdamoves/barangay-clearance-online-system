# Barangay Clearance Online System — System Guide

Technical documentation for the whole thing. I wrote this so that if I ever
come back to this project after a year, I don't have to reverse-engineer my own
decisions.

---

## What this is

Barangay Clearance Online System is a web app that replaces the manual
paper-and-queue process of getting a barangay clearance in Barangay Santo
Cristo, Quezon City.

Residents register, verify their email, fill out an application form, upload a
photo and a valid ID, and submit. Barangay staff review the request in an admin
dashboard, generate the official DOCX from a template, and mark it approved.
The resident gets an email at every step and goes to the hall to collect the
physical signed copy.

There are two services: **Barangay Clearance** (for individuals) and
**Business Clearance** (for business permits and renewals).

This is Capstone 2. It's the follow-up to
[WatchOver](https://github.com/lukatdamoves/watchover), my Capstone 1 project.

---

## Why version 2 exists

The first version worked, but it had real problems once people actually used
it. Going into 2.0 I wanted to fix four things:

| Problem in v1 | What I did in 2.0 |
|---|---|
| Dates were being displayed and stored wrong, causing confusion about pickup schedules | Proper date handling throughout, PH timezone defaults on the forms |
| Admins had no way to organize or prioritize requests | Sortable columns with persisted sort state, pagination, page-size selector |
| No bot protection — spam submissions and credential stuffing | CAPTCHA on login and registration, IP-based cooldowns |
| No visibility into what was happening in the system | An analytics page with counts and a PDF report |

I also fixed a pile of small bugs along the way. The full list is in the
capstone manuscript; the short version is that 2.0 was about reliability and
making the admin's job less miserable.

---

## Architecture

```
  ┌──────────────────────────────┐
  │        Resident browser      │
  │   vanilla JS + hand CSS      │
  └───────────────┬──────────────┘
                  │  fetch() / form POST
                  ▼
  ┌──────────────────────────────┐        ┌──────────────────┐
  │   Apache (XAMPP) + PHP 8.2   │───────▶│  MariaDB 10.4    │
  │                              │        │                  │
  │  structure/  → pages         │        │  users           │
  │  *.php       → endpoints     │        │  clearance_request│
  │  action/     → JS            │        │  business_request │
  │  design/     → CSS           │        │  email_verifs     │
  │                              │        │  ip_cooldowns     │
  │  ┌────────────────────────┐  │        │  outputed_docx    │
  │  │ PHPMailer  → Gmail SMTP│──┼──────▶ └──────────────────┘
  │  │ PhpWord    → DOCX      │  │
  │  │ Dompdf     → PDF       │  │
  │  └────────────────────────┘  │
  └───────────────┬──────────────┘
                  │  writes
                  ▼
        ┌──────────────────┐
        │ uploads/         │  photos, IDs, permits (blocked by .htaccess)
        │ output_docx/     │  generated clearance documents
        └──────────────────┘
```

It's a classic server-rendered PHP app. No build step, no framework, no SPA.
Pages in `structure/` are PHP templates that render HTML; the JS in `action/`
handles the interactive bits by calling the PHP endpoints at the app root and
swapping content in.

I kept it deliberately old-school because the deployment target is shared
hosting, and "upload it over FTP and it works" was a hard requirement.

---

## Tech stack

| Layer | Choice | Why |
|---|---|---|
| Runtime | PHP 8.2 | Shared hosting default, nothing to install |
| Database | MariaDB 10.4 | Ships with XAMPP |
| Server | Apache | `.htaccess`-based config |
| DB access | `mysqli` + prepared statements | No ORM overhead, no config |
| Email | PHPMailer 6.9 over Gmail SMTP | Free, reliable, well documented |
| DOCX | PhpWord 1.3 `TemplateProcessor` | Fills `.docx` templates without Word |
| PDF | Dompdf 3.1 | HTML→PDF, so I could reuse my CSS |
| CAPTCHA | Custom GD implementation | No third-party dependency or API key |
| Frontend | Vanilla JS, hand-written CSS | No build tooling |
| Charts | *none* | See "Analytics" below |

Dependencies are declared in `brgy_santo_cristo/composer.json` and installed
with `composer install`. `vendor/` is gitignored — don't commit it.

> **Note:** `composer.json` also requires `tecnickcom/tcpdf`, but I ended up
> using Dompdf for the PDF export instead. TCPDF is unused. I should remove it
> from `composer.json` but haven't.

---

## Features

### Resident side

**Registration with email verification**
Name, middle name, last name, suffix, email, password, and a CAPTCHA. Password
must be at least 8 characters and is hashed with bcrypt. On submit, a 6-digit
code is emailed and stored in `email_verifications` with a 5-minute expiry.

While a verification row exists for an email, **login is blocked** —
`login.php` checks `email_verifications` first and returns `verify_pending`,
and the login page renders the verification modal automatically. You can
resend the code or cancel, and cancelling deletes the user row entirely.

**Login**
Email + password + CAPTCHA. Three failed attempts trigger a 60-second cooldown
written to `ip_cooldowns`. A successful login clears the cooldown.

**Barangay Clearance application**
Name fields are pre-filled from the account and `readonly` — you can't submit a
clearance in someone else's name. Age is calculated client-side from the birth
date and must be 18+. Uploads an applicant photo and a valid ID photo. Purpose
is a dropdown (Employment, School Requirement, Residency, Good Moral / No
Pending Case, Senior Citizen / Solo Parent / PWD, Postal).

**Business Clearance application**
New or Renewal. Renewal reveals three extra upload fields: old barangay
clearance, old mayor's permit, and land title. Business name, address,
telephone, owner details, kind of business, DTI/SEC registration number, CTC
number, TIN.

**Preview**
After submitting you land on a read-only preview of exactly what was stored.
Useful as a "did my form actually go through?" confirmation.

**Profile**
Change password (requires the current one), and a request history table with a
Cancel button for requests that haven't been actioned yet.

### Admin side

**Dashboard** (`adminpage.php`)
Four tabs: Clearance Request, Business Request, Approved Clearances, Rejected
Requests.

- Search by name or email
- Sortable Name / Date / Status columns, with sort direction persisted in
  `localStorage` so it survives a refresh
- Pagination with a selectable page size
- **Auto-refresh every 10 seconds** — new requests show up without a reload

Row actions:

| Action | What it does |
|---|---|
| **View** | Opens the full request in an iframe modal |
| **Generate / Download** | Fills the DOCX template, saves it, downloads it |
| **Approve** | Sets status to `Approved` and emails the resident |
| **Delete** | Hard-deletes the request (cascades to the generated doc) |
| **Change Status** | `For Signature` / `Rejected`; rejecting prompts for a reason which is emailed |

**Analytics** (`analyticspage.php`)
Month picker, two multi-select filter groups (7 clearance purposes; New/Renewal
business), name search, three count cards — Total Registration, Total Clearance,
Total Business Clearance — a combined data table, pagination, and Export to PDF.

**On "charts":** I want to be straight about this. The page is called
Analytics and there are **no graphs in it**. It's three integer cards and a
table. When I named it I was thinking bigger than I delivered, and I never went
back. If you're reading this repo as a portfolio piece, that's the gap.

---

## Authentication flow

```
Register ──▶ INSERT users ──▶ INSERT email_verifications ──▶ email code
                                                              │
                                    login blocked ◀───────────┘ (row exists)
                                                              │
                              verify code ──▶ DELETE email_verifications
                                                              │
                                          login now allowed ◀─┘
```

**Sessions.** `session_start()` on every entry point, then:

```php
$_SESSION['user_id']     = $user['user_id'];
$_SESSION['firstname']   = $user['firstname'];
$_SESSION['middlename']  = $user['middlename'];
$_SESSION['lastname']    = $user['lastname'];
$_SESSION['namesuffix']  = $user['namesuffix'];
$_SESSION['email']       = $user['email'];
```

**Authorization.** There is no role system. Admin is `user_id == 1`, checked
literally in every guard:

```php
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    die("Unauthorized access.");
}
```

I know this is wrong. It means you can't add a second admin without a schema
migration, and it means the admin account can never be deleted or renumbered.
It's documented in [security-audit.md](security-audit.md) and it's the first
thing I'd change.

**Password reset.** Three steps in `forgot_password.php`, all keyed off
`$_SESSION['code']` — no token in the database. `send_code` → `verify_code` →
`change_password`. Three wrong codes buys you a 3-minute cooldown.

**CAPTCHA.** GD-drawn, 100×40, 5 characters from
`ABCDEFGHJKLMNPQRSTUVWXYZ23456789`. I excluded `I`, `O`, `0`, and `1` because
users kept misreading them and emailing me about it.

---

## File uploads

Both forms write into `uploads/`.

`submit_form.php` does this:

```php
$applicant_photo = "uploads/" . basename($_FILES["applicantPhoto"]["name"]);
move_uploaded_file($_FILES["applicantPhoto"]["tmp_name"], $applicant_photo);
```

`business_submit_form.php` uses a helper that prefixes
`time() . '_' . uniqid() . '_'`, so business uploads at least get unique names.

**Serving.** Direct access to files in `uploads/` is denied by
`uploads/.htaccess`:

```apache
<FilesMatch "\.(jpg|jpeg|png|gif|pdf)$">
    Deny from all
</FilesMatch>
<Files image.php>
    Allow from all
</Files>
```

Everything goes through `uploads/image.php`, which checks that the requesting
user owns a row referencing that filename, or is `user_id == 1`.

There is no server-side validation of type or size. That's a real problem and
it's finding #3 in the audit.

---

## DOCX generation

This is the part I'm most pleased with.

PhpWord's `TemplateProcessor` takes a `.docx` file with `${placeholder}` tokens
and replaces them. The templates live in `docx_templates/`:

- `clearance_template.docx` — the individual barangay clearance
- `business_template.docx` — the business clearance

`generate_docx.php` picks the table and ID column based on `type`, loads the
row, and calls `setValue()` for each field:

```php
$template->setValue('date_submitted', $data['date_submitted']);
$template->setValue('first_name',     $data['first_name']);
$template->setValue('middle_name',    $data['middle_name']);
// ...
$template->setImageValue('applicant_photo', [
    'path'   => $data['applicant_photo'],
    'width'  => 153,
    'height' => 171,
]);
```

The applicant photo is embedded as a real image in the document, sized to
roughly an ID-photo box.

Output goes to `output_docx/{type}_{id}.docx` and a row is written to
`outputed_docx` so the same clearance can't be generated twice — that table is
also what makes the "already generated" state show up in the admin list.

The templates were made in Word by hand, which meant the barangay hall could
keep editing their own official form layout without touching my code. That was
the whole point of using a template file instead of generating XML.

---

## PDF export

`export_pdf.php` builds an HTML string with inline CSS and hands it to Dompdf:

```php
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'landscape');
$dompdf->render();
$dompdf->stream("analytics_report.pdf", ["Attachment" => false]);
```

Applicant photos are inlined as base64 data URIs because `uploads/` is
`.htaccess`-denied and Dompdf fetches over HTTP.

Filters come in as GET params — `month`, `year`, `clearanceFilters[]`,
`businessFilters[]`. The `IN (...)` clause is built with `addslashes()` instead
of a prepared statement, which is finding #7 in the audit.

---

## Database

Six tables. Full DDL in [`../database/schema.sql`](../database/schema.sql).

```
users ──┬──< clearance_request ──< outputed_docx
        └──< business_request  ──┘

email_verifications   (email, code, created_at)   -- no FK
ip_cooldowns          (ip_address, expires_at, type)
```

**`users`** — `user_id`, `firstname`, `middlename`, `lastname`, `namesuffix`
(enum), `email` (unique), `password` (bcrypt), `date_created`.

**`clearance_request`** — the full individual application: names, contact,
address, `date_submitted`, `age`, `sex`, `civil_status` (enum), `birth_date`,
`birth_place`, `nationality`, `since_when`, `purpose`, `additional_info`,
`applicant_photo`, `valid_id_photo`, `status` (varchar).

**`business_request`** — `form_id`, `application_type` (enum New/Renewal),
business and owner details, `dti_sec_reg_no`, `ctc_no`, `issued_on`,
`issued_at`, `tin_no`, five upload paths, `status` (enum).

**`email_verifications`** — `email` (unique), `code` (6 digits),
`created_at`. A row here blocks login.

**`ip_cooldowns`** — `ip_address` (PK), `cooldown_expires_at` (unix),
`type` (`login` or `forgot_password`).

**`outputed_docx`** — `user_id`, `type`, `file_path`, `created_at`,
`business_id` / `clearance_id`, both `ON DELETE CASCADE`.

Two things I'd change: `clearance_request.status` is `varchar(255)` while
`business_request.status` is an enum — same concept, two types. And `age` is
stored rather than derived from `birth_date`, which means it goes stale.

---

## Repository structure

```
brgy_santo_cristo_2.0/
├── index.php                    # redirects into the app
├── database/
│   └── schema.sql               # schema + synthetic demo data
├── docs/
│   ├── system-guide.md          # you are here
│   ├── api-reference.md         # every endpoint + auth status
│   ├── security-audit.md        # findings, unfixed
│   ├── user-manual.md           # resident + admin manual
│   └── deployment-guide.md      # local + production setup
├── .gitignore
├── .env.example
└── brgy_santo_cristo/           # the application
    ├── *.php                    # API endpoints (login, register, submit, ...)
    ├── structure/               # page templates
    ├── action/                  # JavaScript
    ├── design/                  # CSS
    ├── image/                   # logo, default avatar
    ├── docx_templates/          # the .docx templates
    ├── uploads/                 # gitignored, .htaccess + image.php tracked
    ├── output_docx/             # gitignored, generated at runtime
    └── vendor/                  # gitignored, composer install
```

The folder layout is `structure/action/design` because it's how I think about
the app — markup, behaviour, presentation. It's not MVC. It's not even
particularly good separation. It's consistent, which counts for something.

---

## Things I'd do differently

I'm including this because a portfolio that only shows wins is a lie.

1. **A role column.** `user_id == 1` was fine for a demo and is a dead end
   beyond it.
2. **Server-side validation on everything.** I leaned on `required` and
   client-side JS for most of it.
3. **One response convention.** Three different ones right now.
4. **Actual charts**, if I was going to call the page Analytics.
5. **`.env` from day one.** Hardcoding the SMTP credentials is the single
   worst decision in this codebase.
6. **Not committing `vendor/`.** The repo was 250 MB for a long time.

---

## Related

- **Capstone 1:** [WatchOver](https://github.com/lukatdamoves/watchover) —
  GPS-tracking smartwatch with a live dashboard. Hardware + MQTT + web push,
  a completely different problem from this one.
