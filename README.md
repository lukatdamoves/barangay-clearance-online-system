# Barangay Clearance Online System 📄

**A web-based barangay clearance and business clearance system for Barangay Santo Cristo, Quezon City.**

Residents register, apply online, upload their documents, and get emailed when
their clearance is ready for pickup. Barangay staff review everything from one
dashboard, generate the official document from a template, and approve it.
No more paper forms, no more queues, no more encoding by hand.

This was my **Capstone 2** project for the degree of Bachelor of Science in
Information Technology.

<p align="center">
  <em>Capstone 1 was <a href="https://github.com/lukatdamoves/watchover">WatchOver</a> — a GPS-tracking smartwatch with a live dashboard.</em>
</p>

---

## ✨ Features

| Area | What it does |
|---|---|
| 📝 Online application | Full barangay clearance form with auto-calculated age, pre-filled name fields, and document upload |
| 🏢 Business clearance | Separate New / Renewal flow with DTI/SEC, CTC, TIN, lease contract, and land title uploads |
| 🔐 Account system | Registration, email verification with expiring 6-digit codes, bcrypt passwords, password reset |
| 🤖 Bot protection | Custom GD CAPTCHA on login and registration, plus IP-based cooldowns after failed attempts |
| 📧 Email notifications | Residents are emailed on submission, approval, and rejection — with a reason when rejected |
| 📄 Document generation | Official clearance DOCX filled from a Word template, with the applicant's photo embedded |
| 📊 Analytics | Monthly counts by purpose and business type, with a landscape PDF report |
| 🗂️ Admin dashboard | Search, sortable columns with persisted state, pagination, and auto-refresh every 10 seconds |
| 👤 Resident profile | Request history, status tracking, password change, and cancellation of unreviewed requests |

### What it replaced

Barangay Santo Cristo was running clearances entirely on paper — around 50
residents a day, each one filling out a form by hand, staff encoding it into a
spreadsheet, printing the clearance, and waiting for the Barangay Captain to be
free to sign it. Records lived in folders in a filing cabinet.

This system moves the whole thing online except the signature and the pickup.

---

## 🏗️ Architecture

```
 ┌──────────────────────────────┐
 │        Resident browser      │
 │   vanilla JS + hand-written  │
 │   CSS, no framework          │
 └───────────────┬──────────────┘
                 │  fetch() / form POST
                 ▼
 ┌──────────────────────────────┐        ┌──────────────────┐
 │   Apache + PHP 8.2           │───────▶│    MariaDB       │
 │                              │        │                  │
 │  structure/  page templates  │        │  users           │
 │  *.php       API endpoints   │        │  clearance_request│
 │  action/     JavaScript      │        │  business_request │
 │  design/     stylesheets     │        │  email_verifs     │
 │                              │        │  ip_cooldowns     │
 │  ┌────────────────────────┐  │        │  outputed_docx    │
 │  │ PHPMailer → Gmail SMTP │──┼──────▶ └──────────────────┘
 │  │ PhpWord   → DOCX       │  │
 │  │ Dompdf    → PDF report │  │
 │  └────────────────────────┘  │
 └───────────────┬──────────────┘
                 │  writes
                 ▼
       ┌──────────────────┐
       │ uploads/         │  photos, IDs, permits (denied by .htaccess)
       │ output_docx/     │  generated clearance documents
       └──────────────────┘
```

**The clearance lifecycle:**

```
Resident submits
      │
      ▼
  Staff reviews in the dashboard
      │
      ├──▶ Reject (with a reason) ──▶ resident gets an email
      │
      ▼
  Generate the DOCX from the template
      │
      ▼
  Status → "For Signature"   (Captain signs the printout)
      │
      ▼
  Status → "Approved"        ──▶ resident gets the pickup email
```

---

## 🧰 Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.2, procedural, `mysqli` with prepared statements |
| Database | MariaDB 10.4 / MySQL 5.7+ |
| Server | Apache (XAMPP-style stack) |
| Email | PHPMailer 6.9 over Gmail SMTP |
| Documents | PhpWord 1.3 `TemplateProcessor` (DOCX), Dompdf 3.1 (PDF) |
| CAPTCHA | Custom GD implementation — no third-party service |
| Frontend | Vanilla JavaScript, hand-written CSS |
| Charts | *none* — see [Known Limitations](#-known-limitations) |

---

## 🚀 Getting Started

### Prerequisites

- XAMPP (or any Apache + PHP 8.2 + MariaDB stack) — works out of the box
- [Composer](https://getcomposer.org/)
- PHP extensions: `gd`, `fileinfo`, `mbstring`, `openssl`, `curl`

### Setup

```bash
# 1. Clone
git clone https://github.com/lukatdamoves/brgy_santo_cristo_2.0.git
cd brgy_santo_cristo_2.0/brgy_santo_cristo

# 2. Install PHP dependencies (vendor/ is gitignored)
composer install

# 3. Create the database and import the schema
mysql -u root -e "CREATE DATABASE barangay_clearance CHARACTER SET utf8mb4;"
mysql -u root barangay_clearance < ../database/schema.sql

# 4. Point the app at it — edit db_connect.php
#    $dbname = "barangay_clearance";

# 5. Serve it (drop the repo in htdocs, or use PHP's built-in server)
php -S localhost:8000
```

The root `index.php` redirects into `brgy_santo_cristo/structure/homepage.php`.

> **Heads up:** the app uses web-root-absolute paths in a few places, so the
> `brgy_santo_cristo` folder needs to sit directly under your web root.

### Demo accounts

The schema ships with synthetic demo data:

| Account | Password | Role |
|---|---|---|
| `admin@example.com` | `demo1234` | admin (logs into the dashboard) |
| `juan.delacruz@example.com` | `resident1234` | resident |

> The demo rows are fake — synthetic names, `example.com` addresses, and
> placeholder file paths. No real personal information is in this repository.

Full local and production setup: **[docs/deployment-guide.md](docs/deployment-guide.md)**

---

## 📁 Repository Structure

```
brgy_santo_cristo_2.0/
├── index.php                    # entry redirect
├── database/
│   └── schema.sql               # schema + synthetic demo data
├── docs/
│   ├── system-guide.md          # architecture, flows, database
│   ├── api-reference.md         # every endpoint + auth status
│   ├── security-audit.md        # findings, documented not fixed
│   ├── user-manual.md           # resident + barangay staff manual
│   └── deployment-guide.md      # local, shared hosting, production
├── .gitignore
├── .env.example
└── brgy_santo_cristo/           # the application
    ├── *.php                    # API endpoints
    ├── structure/               # page templates
    ├── action/                  # JavaScript
    ├── design/                  # stylesheets
    ├── image/                   # logo, default avatar
    ├── docx_templates/          # the official .docx templates
    ├── uploads/                 # gitignored (resident documents)
    ├── output_docx/             # gitignored (generated documents)
    └── vendor/                  # gitignored (composer install)
```

---

## 📖 Documentation

- **[System Guide](docs/system-guide.md)** — architecture, every feature, auth
  flows, DOCX/PDF generation, database schema, and what I'd do differently
- **[API Reference](docs/api-reference.md)** — all 25+ endpoints with
  parameters, response shapes, and an auth-status table
- **[Security Audit](docs/security-audit.md)** — a real vulnerability review,
  worst-first, with file and line references
- **[User Manual](docs/user-manual.md)** — for residents and for barangay staff
- **[Deployment Guide](docs/deployment-guide.md)** — XAMPP, shared hosting,
  and a production go-live checklist

---

## ⚠️ Known Limitations

I'd rather list these than have someone find them and wonder if I knew.

**Security — see [docs/security-audit.md](docs/security-audit.md) for the full
report with file and line references:**

- 🔴 **SMTP credentials are hardcoded in seven files.** The source now ships
  with placeholders, so nothing leaks — but the *pattern* is still there: the
  same two lines duplicated across seven files, with no `.env`. If you want to
  run this with working email, see
  [the deployment guide](docs/deployment-guide.md#part-2--email).
- 🔴 **Four admin endpoints have no authorization check at all** —
  `approve_clearance.php`, `update_status.php`, `delete_clearance.php`, and
  `generate_docx.php`. Anyone can approve, reject, delete, or download a
  clearance without logging in.
- 🔴 **File uploads aren't validated.** No extension, MIME, or size check, and
  the `uploads/.htaccess` deny rule doesn't cover `.php`.
- 🟠 **No CSRF protection anywhere.** Every POST is forgeable.
- 🟠 **One unparameterized SQL query** in `register.php:33`.
- 🟡 **Admin is `user_id == 1`** — no role column, so no second admin and no
  way to renumber the first one.

**Functional:**

- **"Analytics" has no charts.** Three count cards and a table. I named it
  ambitiously and never went back.
- **Web-root-absolute paths.** The app must be deployed at the web root.
- **No edit after submit.** Wrong photo or typo means delete and resubmit.
- **No online payment or delivery.** Approved residents still collect the
  signed copy in person.
- **Session-based rate limiting.** Clearing cookies resets the failed-login
  counter.
- **`tecnickcom/tcpdf` is in `composer.json` but unused** — I switched to
  Dompdf and never cleaned it up.

**This is an academic project.** It was built to satisfy a capstone
requirement, not to be deployed as-is. Please don't run it in production
without working through the audit first.

---

## 👥 Team

Capstone project — BSIT, Information and Communications Technology Program,
Gardner College Diliman.

- **Ignacio, James Matthew M.**
- **Cervantes, Christine Sharmae E.**
- **Limjoco, Mico Wesley J.**
- **Hambangan, Roland S.**
- **Nodalo, Kenneth Louis C.**

With thanks to **Barangay Captain Mac Navarro**, **Mr. Javy Baltazar**
(IT Supervisor), and **Kagawad Zaldy Nepomuceno** of Barangay Santo Cristo,
and to our adviser **Mr. Jarry D. Mario**.

---

## 📄 License

Developed for academic purposes as a capstone requirement. All rights reserved
by the project team.
