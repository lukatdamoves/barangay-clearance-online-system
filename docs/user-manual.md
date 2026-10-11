# User Manual

Written for two audiences: residents applying for a clearance, and the barangay
staff processing them. If you're a developer, the
[system guide](system-guide.md) is the one you want.

---

# Part 1 — For Residents

## What you can do here

Apply for a barangay clearance or a business clearance without going to the
barangay hall until it's time to pick up the signed document.

## Getting an account

1. Go to the site and click **Login**, then switch to the **Register** tab.
2. Fill in your first name, middle name, last name, suffix (if you have one),
   email address, and a password.
3. Your password needs to be **at least 8 characters**.
4. Enter the CAPTCHA — the jumbled letters in the image. Capital and lowercase
   both work.
5. Submit.

You'll get a **6-digit code by email**. It expires in **5 minutes**, so check
your inbox right away.

- Type the code in to finish registering.
- Didn't get it? **Resend** sends a fresh code.
- Wrong email address? **Cancel** deletes the account so you can start over.

> Until you verify, you can't log in. If you try, you'll just be shown the
> verification box again.

## Logging in

Email + password + CAPTCHA.

After **3 wrong attempts** you'll be locked out for **60 seconds**. That's
deliberate — it stops people from guessing passwords. Just wait it out.

## Applying for a Barangay Clearance

1. Log in.
2. Go to **Services → Barangay Clearance**.
3. Fill out the form:
   - Your name is already filled in and **can't be changed** — a clearance has
     to be in the account holder's name.
   - Contact number and full address.
   - Date of birth — your **age is calculated automatically** and must be 18 or
     over.
   - Sex, civil status, birthplace, nationality.
   - "Resident since" — roughly when you started living in the barangay.
   - **Purpose** — pick from the list: Employment, School Requirement,
     Residency, Good Moral / No Pending Case, Senior Citizen / Solo Parent /
     PWD, Postal.
4. Upload a **photo of yourself** and a **photo of a valid ID**.
5. Submit.

You'll land on a preview page showing exactly what was saved. If something
looks wrong, contact the barangay office — there's no edit button.

## Applying for a Business Clearance

1. Log in.
2. Go to **Services → Business Clearance**.
3. Choose **New** or **Renewal**.
4. Fill in the business details: name, address, telephone, kind of business.
5. Fill in the owner details: name, contact, address.
6. Fill in the registration details: DTI/SEC registration number, CTC number
   and when/where it was issued, TIN.
7. Upload the required documents:
   - **Always:** DTI/SEC registration, lease of contract
   - **Renewal only:** land title, old barangay clearance, old mayor's permit
8. Submit.

## Checking your application

**Profile → Request History** shows everything you've submitted with its
current status:

| Status | Meaning |
|---|---|
| *(blank)* | Submitted, not yet reviewed |
| `For Signature` | Approved by staff, waiting for the Barangay Captain's signature |
| `Approved` | Done — go collect it |
| `Rejected` | Not approved. Check your email for the reason. |

If a request is still unreviewed, there's a **Cancel** button to withdraw it.
Once it's been actioned you can't cancel it yourself.

## Changing your password

**Profile → Change Password.** You'll need your current password, then the new
one twice. Minimum 8 characters.

Forgot it entirely? Use **Forgot Password** on the login page — it emails you a
code and walks you through setting a new one.

---

# Part 2 — For Barangay Staff

You need the admin account to see any of this. Logging in as admin drops you
straight onto the dashboard instead of the resident homepage.

## The dashboard

Four tabs across the top:

- **Clearance Request** — new individual applications
- **Business Request** — new business applications
- **Approved Clearances** — everything already approved
- **Rejected Requests** — everything rejected

The list **refreshes itself every 10 seconds**, so you can leave it open on the
counter and new submissions appear on their own.

### Finding things

- **Search box** — filters by name or email as you type.
- **Sort** — click **Name**, **Date**, or **Status** to sort. Click again to
  flip the direction. Your choice is remembered if you reload.
- **Page size** — change how many rows show at once.
- **Pages** — arrows at the bottom.

### What each button does

**View**
Opens the full application in a popup — all the personal details, the purpose,
and the uploaded documents. This is where you check that the ID and the photo
actually match the person.

**Download / Generate**
Fills the official clearance template with the applicant's details and
downloads the `.docx` file. This is what gets printed and signed.

You can only generate a document **once** per application. If it's already been
generated, the button won't let you do it again — that's on purpose, so two
people don't print two different versions of the same clearance.

**Approve**
Sets the status to `Approved` and immediately emails the resident telling them
to come to the barangay hall and collect their document.

Use this when the document has actually been signed and is ready for pickup.

**Change Status**
A dropdown with two options:

- **For Signature** — the request has been reviewed and the document is
  generated, but the Barangay Captain hasn't signed it yet. This is the normal
  "in progress" state.
- **Rejected** — you'll be asked for a **reason**, and that reason is included
  in the email sent to the resident.

Write a real reason. "Rejected" with no explanation generates a support phone
call.

**Delete**
Permanently removes the application. This also deletes any document that was
generated from it.

Use it for duplicates, test entries, and submissions that should never have
been accepted. **There is no undo.**

## The analytics page

Pick a month and year, optionally filter by purpose or business type, and you
get:

- **Total Registration** — accounts created that month
- **Total Clearance** — individual clearance requests, broken down by purpose
- **Total Business Clearance** — business requests, split New vs Renewal

Plus a table of the matching records, and an **Export to PDF** button that
produces a landscape report with both tables and the applicant photos embedded.

Useful for monthly reports to the Barangay Captain.

> Straight talk: I called it "Analytics" and it's three numbers and a table.
> There are no charts. That's on me.

## The day-to-day workflow

```
Resident submits
      │
      ▼
  ┌─────────────┐   no     ┌──────────────────────┐
  │  Complete?  │─────────▶│  Reject with reason  │
  └─────────────┘          └──────────────────────┘
      │ yes                          │
      ▼                              ▼
  View the application         Email goes to resident
      │
      ▼
  Generate / Download the DOCX
      │
      ▼
  Change Status → "For Signature"
      │
      ▼
  Print, get the Captain's signature
      │
      ▼
  Change Status → "Approved"  ──▶  Resident gets the pickup email
```

## Troubleshooting

**"I approved it but the resident says they didn't get an email."**
The approval email is sent at the same moment the status is written. If the
mail server is down, the status still updates but the email doesn't — the code
logs the failure and keeps going. Check the status actually changed, then
resend manually.

**"The Generate button isn't working."**
That application already has a document. There's a one-document-per-application
rule. If you genuinely need a fresh copy, delete the request and have the
resident resubmit.

**"A resident uploaded the wrong photo."**
There's no way to replace a single upload. Delete the request and have them
submit again.

**"Someone's name is spelled wrong."**
Name fields come from their account, and the account name is what gets printed
on the clearance. Fix it at the account level, not the request level.
