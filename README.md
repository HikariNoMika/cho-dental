# Dental Clinic Records & PhilHealth Claim Forms

A Laravel application for managing dental clinic records and generating auto-filled
PhilHealth claim forms. It keeps patient, dentist, visit, and dental-service data in
one place, then fills the official DOCX templates so staff can review, print, or
download the completed forms.

> **Documentation**
> - [Template placeholder reference](docs/template-placeholders.md) — every
>   `{{placeholder}}` the generator understands.
> - [Architecture notes](docs/architecture.md) — request flow, data model, and
>   extension points.

## Features

- **Patients** — members and dependents, PhilHealth PIN, contact number, address,
  birth date, relationship to a member, and an optional guardian signatory for
  patients aged 13 and below.
- **Dentists / providers** — name, credentials (default `DMD`), PhilHealth
  Accreditation Number (PAN), and optional signature image.
- **Visits** — service date, working/final diagnosis, disposition, and referral
  code.
- **Dental services** — tooth number and service code per visit, ready to be
  cloned into Claim Form 2 table rows.
- **Claim forms** — auto-filled Referral Form, Annex E page 1 (Claim Form 1), and
  Annex E page 2 (Claim Form 2).
- **Printing and downloads** — in-browser DOCX preview/print, individual DOCX
  downloads, and a ZIP containing all three forms.
- **Referral codes** — generated once per visit in `CHO-MMDDYYYY-NN` format and
  reused on reprints.
- **Template management** — upload replacement DOCX templates from the UI, with
  automatic timestamped backups.
- **Clinic administration** — manage dental aides and application users.
- **Authentication** — Laravel Breeze (login, registration, password reset,
  profile) with a Tailwind CSS interface and dark mode.

## Tech stack

| Layer | Technology |
| --- | --- |
| Framework | Laravel 12 (PHP 8.2+) |
| Database | SQLite by default; MySQL/PostgreSQL supported by Laravel |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| DOCX filling | `phpoffice/phpword` |
| DOCX preview | `docx-preview` |
| PDF conversion (optional) | LibreOffice headless |
| PDF merging (optional) | `iio/libmergepdf` |
| Authentication | Laravel Breeze |

## How it works

1. Staff record a patient and a dentist, and a visit links the two.
2. The claim forms page requests one or all three forms for that visit.
3. `App\Services\ClaimPacketGenerator` loads the matching DOCX template, replaces
   the placeholders (names, PIN, addresses, diagnoses, services, signature, etc.),
   and saves a filled DOCX.
4. Staff print the DOCX in the browser or download it for printing in Microsoft
   Word.
5. When a form is generated for the first time, the visit receives a referral code.
   The code stays attached to the visit so reprints are consistent.

The generator can also convert filled DOCX files to PDF with LibreOffice and merge
them into one packet. That pipeline exists in the service layer but is not currently
exposed as a web route; the UI offers DOCX download and browser printing.

## Requirements

- PHP **8.2+** with the usual Laravel extensions (`mbstring`, `openssl`, `pdo`,
  `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `zip`)
- Composer 2
- Node.js 18+ and npm
- SQLite (default) or a supported database server
- LibreOffice — only needed if you call the PDF/merge methods
- Microsoft Word — recommended for final printing and exact form layout

## Installation

```bash
# 1. Install PHP and JavaScript dependencies
composer install
npm install

# 2. Create the environment file
cp .env.example .env          # Windows PowerShell: copy .env.example .env

# 3. Generate the application key
php artisan key:generate

# 4. Create the SQLite database file (skip if you use MySQL/PostgreSQL)
touch database/database.sqlite   # Windows PowerShell:
                                 # New-Item -ItemType File -Path database/database.sqlite -Force

# 5. Run migrations and seed data
php artisan migrate --seed

# 6. Build frontend assets
npm run build

# 7. Start the development server
php artisan serve
```

Open <http://localhost:8000> and sign in with the seeded administrator account below.

### Logging in

The login page is at <http://localhost:8000/login>. Authentication is provided by
Laravel Breeze; after signing in, staff land on the dashboard and can create more
accounts under **Users**.

`php artisan migrate --seed` runs both seeders and creates the administrator:

| Account | Email | Password |
| --- | --- | --- |
| Administrator | `admin@dental.local` | `Admin@12345` |

The demo seeder also creates one dentist (Jose Rizal, PAN `P12034310`) and one
patient (Maria Santos Dela Cruz) with a visit and a dental service. To seed only
the administrator:

```bash
php artisan db:seed --class=AdminUserSeeder
```

Change the default password and the `ADMIN_*` values in `.env` before using the
application outside a local environment.

Public registration is enabled at `/register` by default. Disable it in
`routes/auth.php` if accounts should only be created by an administrator.

### Development mode

`composer dev` starts the PHP server, queue listener, log viewer, and Vite together:

```bash
composer dev
```

Or run the pieces separately:

```bash
php artisan serve
npm run dev
```

## Claim form templates

The three DOCX templates live in `storage/app/templates` by default:

| Form | Required filename |
| --- | --- |
| Referral Form | `Referral_Form System.docx` |
| Claim Form 1 (Annex E page 1) | `PhilHealth_Claim_Form_1 System.docx` |
| Claim Form 2 (Annex E page 2) | `PhilHealth_Claim_Form_2 System.docx` |

The filenames are fixed in `ClaimPacketGenerator`; keep them unchanged. If the
files are missing, copy the DOCX files from the project root or upload them from
**Templates** in the sidebar.

Uploading through **Templates** validates that each file is a DOCX up to 10 MB and
creates a backup named `<filename>.bak-YYYYMMDDHHMMSS` before replacing an existing
file. Existing generated forms are not changed.

See [docs/template-placeholders.md](docs/template-placeholders.md) for the
placeholder names each form supports and formatting rules.

## Configuration

Claim generation settings live in `config/claims.php` and can be overridden in
`.env`:

| Variable | Default | Purpose |
| --- | --- | --- |
| `CLAIM_TEMPLATES_PATH` | `storage/app/templates` | Absolute path to the directory containing the three DOCX templates |
| `CLAIM_PRINTS_PATH` | `storage/app/prints` | Absolute path where generated DOCX/ZIP/PDF files are stored |
| `LIBREOFFICE_BINARY` | `soffice` | LibreOffice executable used for PDF conversion |
| `CLAIM_REFERRAL_PREFIX` | `CHO` | Prefix for generated referral codes |
| `CLAIM_PROVIDER_DESIGNATION` | `Dentist` | Designation printed for providers; also the fallback encoder designation |
| `CLAIM_ENCODER_NAME` | *(empty)* | Fallback encoder name when no user is authenticated |
| `ADMIN_NAME` | `Administrator` | Seeded administrator display name |
| `ADMIN_FIRST_NAME` | `System` | Seeded administrator first name |
| `ADMIN_LAST_NAME` | `Administrator` | Seeded administrator last name |
| `ADMIN_DESIGNATION` | `Administrator` | Seeded administrator designation |
| `ADMIN_EMAIL` | `admin@dental.local` | Seeded administrator email |
| `ADMIN_PASSWORD` | `Admin@12345` | Seeded administrator password |

On Windows, set the LibreOffice path with escaped backslashes:

```dotenv
LIBREOFFICE_BINARY="C:\\Program Files\\LibreOffice\\program\\soffice.exe"
```

`soffice` on its own only works when the executable is on the `PATH` of the user
running PHP.

The dental claim codes are configured in `config/claims.php` under `visit_codes`.
The keys are the selectable codes (`OPH01`, `OPH01A`, `OPH01B`, `OPH02`, `OPH02A`,
`OPH02B`) and the values are the 1st/2nd service number used for the yearly status.

## Usage

### 1. Add a dentist

Go to **Dentists → Add Dentist**. The PAN is required and is used for the
provider's accreditation number. The signature path is optional; when set, the
image is inserted at the first available signature placeholder.

### 2. Add a patient

Go to **Patients → Add Patient**:

- Check **Member** for a PhilHealth member. Enter the 12-digit PIN in
  `99-999999999-9` format; the application stores it in a normalized format.
- Leave **Member** unchecked for a dependent. Enter the member's first, middle,
  last name, extension, and PIN separately in the **Member / Principal** section.
  If the member is not registered as a patient, those details are saved with the
  dependent. If the name or PIN matches an existing member, the dependent links
  to that record automatically.
- Set the relationship (`CHILD`, `SPOUSE`, or `PARENT`) to drive the Part II
  checkboxes on Claim Form 1.
- The address defaults to Region XII (SOCCSKSARGEN), South Cotabato, and
  Koronadal City. Region, province, and city accept typed values or datalist
  suggestions; barangay is limited to the Koronadal City list. Add an optional
  **Contact Number** for follow-ups.
- The **Claim Form Signature** dropdown is available on every patient form. Choose
  **Patient** when there is no guardian involved. For patients aged 13 and below,
  choose **Guardian / Authorized Representative** to show and require the
  guardian's details; that name is printed on Claim Form 2.

When a patient is created and no visit exists yet, the application automatically
records the first visit using the first dentist in the database and tags it
`OPH01`. If there are no dentists yet, the visit is skipped; add a dentist first.

Use the **Search patients** box on the Patients page to filter by name, PIN (with
or without dashes), contact number, or barangay. Multiple words are matched
individually, so `juan cruz` finds `Juan Dela Cruz`.

### 3. Record a visit

A patient normally has two dental services per year. Open a patient and click
**Add Visit** (also available from the patient list):

- Choose the **Service Date** and **Dentist**.
- Choose the **Claim Code**. The first service uses the `OPH01` series
  (`OPH01`, `OPH01A`, `OPH01B`) and the second uses the `OPH02` series
  (`OPH02`, `OPH02A`, `OPH02B`). The form suggests the next code based on the
  visits already recorded this year.
- If the patient already has two visits this year, the screen shows a warning so
  you can double-check before adding a third.

The Patients list has a **Visits** column for the current year. It shows
`No visit`, `1st visit`, or `2nd visit`, the first visit date, and the latest
claim code. The patient detail page shows the same yearly summary above the visit
history.

### 4. Generate claim forms

From the **Patients** list, click **Forms** next to a patient to open the most recent
visit. From a patient's detail page, each visit row has its own **Claim Forms**
button. The visit's claim code and 1st/2nd visit label are shown at the top so you
can confirm which service variant is being printed.

The claim forms page shows:

- **Print** — renders the filled DOCX in a new window with `docx-preview` and opens
  the browser print dialog. Allow popups for this site.
- **Download DOCX** — downloads the individual filled form.
- **Download All Three Forms (.zip)** — downloads the referral form and both claim
  forms.

The referral code is assigned when a form is first generated. It appears on the
claim forms page after you reload it following the first download, and remains
unchanged afterwards.

### 5. Manage users and templates

- **Users** — create staff accounts, set designations, and reset passwords. The
  signed-in user's name and designation are used as the encoder fields on the
  referral form.
- **Dental Aides** — maintain the aide directory.
- **Templates** — replace the DOCX templates. Always download a test form after an
  upload.

## Routes

| Method | URI | Name | Description |
| --- | --- | --- | --- |
| GET | `/` | — | Landing page |
| GET/POST | `/login` | `login` | Sign in |
| GET/POST | `/register` | `register` | Public registration |
| POST | `/logout` | `logout` | Sign out |
| GET | `/dashboard` | `dashboard` | Dashboard (requires auth) |
| GET | `/patients` | `patients.index` | Patient list |
| GET | `/patients/create` | `patients.create` | Add patient |
| GET | `/patients/{patient}` | `patients.show` | Patient detail and visits |
| GET | `/patients/{patient}/visits/create` | `patients.visits.create` | Record another visit |
| POST | `/patients/{patient}/visits` | `patients.visits.store` | Save a visit |
| GET | `/visits/{visit}/edit` | `visits.edit` | Edit a visit |
| PUT | `/visits/{visit}` | `visits.update` | Update a visit |
| GET | `/claims/{visit}/forms` | `claims.forms` | Claim form download page |
| GET | `/claims/{visit}/forms/{form}` | `claims.forms.download` | Download `referral`, `claim-1`, or `claim-2` |
| GET | `/claims/{visit}/forms.zip` | `claims.forms.zip` | Download all three DOCX files |
| GET | `/claims/{visit}/print` | `claims.print` | Legacy URL; redirects to the forms page |
| GET | `/templates` | `templates.index` | Template status and upload form |
| PUT | `/templates` | `templates.update` | Replace uploaded templates |
| GET | `/providers`, `/dental-aides`, `/users` | — | CRUD screens for the remaining records |
| GET | `/profile` | `profile.edit` | Profile settings |

All application routes except the landing and authentication pages require a
signed-in user. There are no role restrictions: every authenticated user can manage
all records.

## Data model

```
users                 Application accounts (encoder name/designation)
patients              Member or dependent; member_id points to the member patient
providers             Dentists; pan + optional signature_path
visits                Links a patient and a provider; holds claim_code, diagnoses, and referral_code
dental_services       Tooth number and service code for a visit
dental_aides          Aide directory
```

Relationships:

- A patient belongs to an optional member patient (`patients.member_id`), and a
  member has many dependents.
- A patient has many visits.
- A provider has many visits.
- A visit has many dental services.
- Deleting a patient cascades to their visits; deleting a provider is restricted
  while visits reference it.

## Project structure

```
app/
  Http/Controllers/          CRUD controllers, claim form controller, auth controllers
  Models/                    Patient, Provider, Visit, DentalService, DentalAide, User
  Services/
    ClaimPacketGenerator.php Fills templates, generates referral codes, optional PDF merge
config/claims.php            Template paths, LibreOffice, referral prefix, admin defaults
database/migrations/         Schema for patients, providers, visits, services, aides, users
database/seeders/            Admin account and demo claim data
docs/                        Template reference and architecture notes
resources/views/             Blade screens (patients, providers, claims, templates, users)
resources/js/app.js          Alpine setup and DOCX print preview
routes/web.php               Application routes
storage/app/templates/       DOCX templates
storage/app/prints/          Generated forms, archives, and PDFs
tests/Feature/               CRUD, authentication, profile, and claim form tests
```

## Generated files

- Individual forms and archives:
  `storage/app/prints/docx/{referral-code}/`
- Merged PDF packets (when `generate()` is called):
  `storage/app/prints/claim-packet-{referral-code}.pdf`
- Temporary conversion files:
  `storage/app/tmp/claims/` (removed after the packet is built)

Downloads are generated on demand. The files remain on disk until removed
manually.

## Testing

```bash
php artisan test
```

The feature tests cover patient/member linking, CRUD screens, authentication, and
claim form downloads. The claim form test reads the real templates from
`storage/app/templates`, so keep the three DOCX files in place when running the
suite.

## Troubleshooting

**“Claim template not found”**
: The DOCX files are missing from `storage/app/templates`, or their filenames do
not match the required names. Copy them from the project root or upload them on
the **Templates** page.

**Placeholders appear as blank spaces or remain as `{{ name }}`**
: Placeholder names are case-sensitive and must use double braces. Check the
[template reference](docs/template-placeholders.md). If a placeholder is split
across formatting runs in Word, retype it in a single run.

**LibreOffice conversion fails**
: Set `LIBREOFFICE_BINARY` to the full path of `soffice.exe` and make sure the web
server user can execute it. This only affects the optional PDF/merge methods.

**Cannot delete a dentist**
: Visits reference providers with a restricted delete. Remove the provider's
visits first, or reassign them.

**Print window is blank**
: Allow popups for the site. If the preview still fails, download the DOCX and
print it from Microsoft Word for the exact layout.

**Permission errors writing generated files**
: Ensure `storage/` and `bootstrap/cache/` are writable by the web server user.

**Admin login does not work**
: Run `php artisan db:seed --class=AdminUserSeeder` and check the `ADMIN_*` values
in `.env`.

## Known limitations

- Visits do not have a dedicated CRUD screen yet; the application creates a first
  visit when a patient is added, and additional visits are created through code or
  the database.
- Merged PDF generation is implemented in `ClaimPacketGenerator` but has no web
  route.
- Registration is enabled by default and there are no roles or permissions.
  Disable public registration in `routes/auth.php` for production if accounts
  should be created only by an administrator.
- Claim Form 2's bundled template contains only three placeholders
  (`P_fullname`, `D_fullname`, `date_now`). Diagnosis, service, and disposition
  placeholders become active when added to the template.

## License

This application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT),
consistent with the Laravel framework.
