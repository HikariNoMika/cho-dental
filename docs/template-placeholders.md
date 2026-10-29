# Claim Form Template Reference

This reference lists every placeholder understood by
`App\Services\ClaimPacketGenerator`, where each value comes from, and how to prepare
a DOCX template for the application.

## Template files

| Key | Required filename | Used for |
| --- | --- | --- |
| `referral` | `Referral_Form System.docx` | Referral transaction form |
| `claim-1` | `PhilHealth_Claim_Form_1 System.docx` | Annex E page 1 |
| `claim-2` | `PhilHealth_Claim_Form_2 System.docx` | Annex E page 2 |

The files live in `storage/app/templates` by default (`CLAIM_TEMPLATES_PATH`).
Filenames are case-sensitive on Linux and must match exactly. You can replace them
from **Templates** in the sidebar; the application keeps a
`<filename>.bak-YYYYMMDDHHMMSS` backup of the previous file.

## Placeholder syntax

The generator switches PHPWord to double-curly-brace macros, so placeholders must
look like this:

```text
{{patient_name}}
```

Rules:

- Use **two opening and two closing braces**.
- Do **not** put spaces inside the braces. `{{ patient_name }}` will not match.
- Placeholder names are **case-sensitive**: `P_Lastname` and `P_LASTNAME` are
  different placeholders.
- Type each placeholder in one continuous run. Formatting part of the placeholder
  (for example, bolding only `name`) can split it in the DOCX XML and prevent
  replacement.
- Placeholders may appear in body paragraphs or table cells.
- If a placeholder is absent from the template, its value is silently ignored, so
  you can use the same generator with simplified forms.

Values are inserted as plain text. Names and selected fields (sex, address,
disposition, relationship) are converted to uppercase before insertion; diagnosis
text is inserted as stored.

## Value formats

| Format | Example | Notes |
| --- | --- | --- |
| Full name | `MARIA SANTOS DELA CRUZ JR.` | First, middle, last, extension; uppercase; extra spaces collapsed |
| Provider display name | `JOSE PROTACIO RIZAL, DMD` | Full name plus `providers.credentials` |
| Date | `10-24-2026` | `m-d-Y` |
| PhilHealth PIN | `12-345678901-2` | 12 digits formatted as `99-999999999-9` |
| Checkbox | `☑` / `☐` | Checked / unchecked Unicode box |
| Service date | `10-24-2026` | From `visits.service_date`; falls back to the visit creation date |
| Today | `10-24-2026` | Current date when the form is generated |

## Referral Form

| Placeholder | Source | Example |
| --- | --- | --- |
| `referral_code` | `visits.referral_code` | `CHO-10242026-01` |
| `date_today` | Visit service date | `10-24-2026` |
| `patient_name` | Patient full name | `MARIA SANTOS DELA CRUZ JR.` |
| `age` | Age in whole years from the birth date | `37` |
| `sex` | Patient sex, uppercased | `FEMALE` |
| `opd_assigned` | Dentist display name | `JOSE PROTACIO RIZAL, DMD` |
| `op_designation` | `CLAIM_PROVIDER_DESIGNATION` | `Dentist` |
| `encoder_name` | Signed-in user's full name | `ANA REYES` |
| `e_designation` | Signed-in user's designation | `Encoder` |
| `claim_code` | `visits.claim_code` | `OPH01` |
| `signature` | Provider signature image | *(image)* |
| `D_signature` | Provider signature image | *(image)* |
| `provider_signature` | Provider signature image | *(image)* |

Notes:

- `encoder_name` uses the authenticated user's name first, then
  `CLAIM_ENCODER_NAME`, then the provider name as a last resort.
- `e_designation` uses the authenticated user's `designation` first, then
  `CLAIM_PROVIDER_DESIGNATION`.
- The signature image is inserted only when the provider has a resolvable
  `signature_path` and the template contains at least one of the three signature
  placeholders. The first matching placeholder is used. Images are placed at
  70 × 25 units with aspect ratio preserved.

## Claim Form 1 (Annex E page 1)

Part I always describes the **PhilHealth member**. If the patient is a dependent,
the generator uses the linked member patient for Part I. When the member is not
registered as a patient, the manual member name and PIN fields are used instead.
Part II is filled with the dependent's details when the template supports it.

### Member / patient fields

| Placeholder | Source | Notes |
| --- | --- | --- |
| `philid` | Member's PhilHealth PIN | Formatted `99-999999999-9` |
| `bdate` | Member's birth date | `m-d-Y` |
| `p_barangay` | Member's barangay | Uppercased |
| `p_city` | Member's city/municipality | Uppercased |
| `p_province` | Member's province | Uppercased |
| `gender` | Member's sex | Uppercased |
| `date_now` | Current date | `m-d-Y` |
| `claim_code` | `visits.claim_code` | `OPH01` |
| `chk_member_yes` | Patient is a member | `☑` / `☐` |
| `chk_member_no` | Patient is a dependent | `☑` / `☐` |
| `chk_Member` | Patient is a member | Same as `chk_member_yes` |
| `chk_member` | Patient is a member | Same as `chk_member_yes` |

### Patient / member name placeholders (`P_` prefix)

Both casings are provided so different template versions work. Use the casing that
matches your template — do not include both in the same field.

| Placeholder(s) | Value |
| --- | --- |
| `P_Lastname` / `P_LASTNAME` | Last name |
| `P_firstname` / `P_FIRSTNAME` | First name |
| `P_middlename` / `P_MIDDLENAME` | Middle name |
| `P_ext` / `P_EXT` | Name extension |
| `P_FULLNAME` / `P_fullname` | Full name |

All values are uppercased. For a dependent, the `P_` values are the **member's**
name, not the dependent's.

### Dentist / provider placeholders (`D_` prefix)

| Placeholder | Value |
| --- | --- |
| `D_Lastname`, `D_LASTNAME` | Provider last name |
| `D_firstname`, `D_FIRSTNAME` | Provider first name |
| `D_middlename`, `D_MIDDLENAME` | Provider middle name |
| `D_ext`, `D_EXT` | Provider name extension |
| `D_FULLNAME`, `D_fullname` | Full name plus credentials |
| `D_PIN` | PhilHealth Accreditation Number (`providers.pan`) |
| `signature`, `D_signature`, `provider_signature` | Signature image |

### Part II — dependent details

These placeholders are blank when the patient is the member. They are filled only
when the patient is a dependent.

| Placeholder | Value |
| --- | --- |
| `DEP_LASTNAME` | Dependent last name |
| `DEP_FIRSTNAME` | Dependent first name |
| `DEP_MIDDLENAME` | Dependent middle name |
| `DEP_EXT` | Dependent name extension |
| `DEP_FULLNAME` | Dependent full name |
| `DEP_PIN` | Dependent PhilHealth PIN, formatted |
| `DEP_BDATE` | Dependent birth date |
| `DEP_SEX` | Dependent sex |
| `RELATIONSHIP` | Relationship, uppercased |
| `chk_child` | Checked when relationship is `CHILD` |
| `chk_parent` | Checked when relationship is `PARENT` |
| `chk_spouse` | Checked when relationship is `SPOUSE` |
| `chk_sex_male` | Checked when the dependent's sex is `MALE` |
| `chk_sex_female` | Checked when the dependent's sex is `FEMALE` |

### PIN digit boxes

Use `pin_1` through `pin_12` to fill one digit per box:

```text
{{pin_1}}{{pin_2}}{{pin_3}}{{pin_4}}{{pin_5}}{{pin_6}}
{{pin_7}}{{pin_8}}{{pin_9}}{{pin_10}}{{pin_11}}{{pin_12}}
```

For a dependent, the digits come from the dependent's own PIN. When the patient is
the member, these boxes are left blank.

## Claim Form 2 (Annex E page 2)

| Placeholder | Source | Notes |
| --- | --- | --- |
| `P_fullname` | Patient full name, or guardian name when selected | Uppercased; used on the signature line |
| `D_fullname` | Provider full name plus credentials | Uppercased |
| `date_now` | Current date | `m-d-Y` |
| `claim_code` | `visits.claim_code` | Optional |
| `referral_code` | `visits.referral_code` | Optional |
| `service_date` | Visit service date | Optional |
| `date_provision` | Visit service date | Optional |
| `working_dx` | `visits.working_dx` | Optional |
| `final_dx` | `visits.final_dx` | Optional |
| `disposition` | `visits.disposition` | Uppercased; optional |
| `signature`, `D_signature`, `provider_signature` | Signature image | Optional |

### Dental service table

Add these two placeholders to a single table row:

```text
| Tooth No. | Service |
| {{tooth_number}} | {{dental_service}} |
```

The generator clones that row once per `dental_services` record and fills:

- `tooth_number` — `dental_services.tooth_number`
- `dental_service` — `dental_services.service_code`

If the visit has no services, one blank row is emitted so the table structure stays
valid. Row cloning only runs when **both** placeholders exist in the template.

> **Bundled template note:** the bundled Claim Form 2 template currently contains
> only `{{P_fullname}}`, `{{D_fullname}}`, and `{{date_now}}`. The other
> placeholders, including `{{claim_code}}`, and the service table are supported by
> the generator but only take effect after you add them to the DOCX.

## Bundled template inventory

For quick verification, these are the placeholders present in the bundled files:

| Template | Placeholders |
| --- | --- |
| Referral Form | `age`, `date_today`, `e_designation`, `encoder_name`, `op_designation`, `opd_assigned`, `patient_name`, `referral_code`, `sex` |
| Claim Form 1 | `D_EXT`, `D_FIRSTNAME`, `D_FULLNAME`, `D_LASTNAME`, `D_MIDDLENAME`, `D_PIN`, `P_Lastname`, `P_ext`, `P_firstname`, `P_middlename`, `bdate`, `date_now`, `gender`, `p_barangay`, `p_city`, `p_province`, `philid` |
| Claim Form 2 | `D_fullname`, `P_fullname`, `date_now` |

## Inspecting a template

To list the placeholders in a DOCX without opening Word, use **Find** in Microsoft
Word and search for `{{`. Alternatively, unzip the DOCX (a `.docx` is a ZIP archive)
and search `word/document.xml` for the double braces. Change the filename to inspect
another template.

## Testing a new template

1. Upload the DOCX on the **Templates** page, or replace the file in
   `storage/app/templates`.
2. Open a patient with a complete visit and click **Claim Forms**.
3. Download the individual form and confirm every field is populated.
4. Check dependent-specific fields with a dependent patient.
5. Check the signature placement if the provider has a signature image.
6. Reprint an old visit to confirm the referral code does not change.

## Common problems

| Symptom | Likely cause |
| --- | --- |
| A field is blank | Placeholder name misspelled, wrong casing, or spaces inside the braces |
| Placeholder text remains in the output | The placeholder is split across runs or not recognized by PHPWord |
| Wrong person's name in Part I | The patient is a dependent; `P_` fields intentionally show the member |
| PIN digits are blank | The patient is the member; `pin_1`–`pin_12` are filled only for dependents |
| Provider PAN is missing | The template has no `{{D_PIN}}` placeholder, or the provider's PAN is empty |
| Dental service rows are not cloned | `{{tooth_number}}` and `{{dental_service}}` are missing from the row |
| Signature is missing | `signature_path` does not resolve to a readable image, or the template has no signature placeholder |
