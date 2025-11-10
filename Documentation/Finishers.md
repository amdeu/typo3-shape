# Finishers Reference

Finishers are actions executed after successful form submission. They process form data, send emails, save to database, or redirect users.

## Table of Contents

- [Configuration](#configuration)
  - [Template Variables](#template-variables)
  - [Finisher Conditions](#finisher-conditions)
- [Send Email](#-send-email)
- [Save Submission](#-save-submission)
- [Save to Database](#-save-to-database)
- [Email Consent (Double Opt-In)](#-email-consent-double-opt-in)
- [Redirect](#-redirect)
- [Show Content Elements](#-show-content-elements)
- [Finisher Execution Order](#finisher-execution-order)
- [Custom Finishers](#custom-finishers)

## Configuration

**Form record → Finishers tab → Create new**

All finishers have:
- **Title** - Internal identifier
- **Type** - Finisher class (see below)
- **Condition** - Optional condition expression ([Conditions Guide](Conditions.md))
- **Settings** - Type-specific configuration

### Template Variables

Many finisher settings support `{{field-name}}` syntax to insert form values:

```
Subject: New Contact from {{first-name}} {{last-name}}
Recipient: {{email-address}}
URL: https://example.com/thanks?ref={{reference-code}}
```

> **📌 Note:** Use actual field names in kebab-case.

### Finisher Conditions

Execute finishers conditionally using Expression Language:

```
value("newsletter") == "yes"
isConsentApproved()
isConsentDismissed()
isBeforeConsent()
```

See [Conditions Guide](Conditions.md) for full syntax.

---

## 📧 Send Email

Sends an email with form values.

### Settings

#### Mail Tab

**Template** ✱
Email template selection (configurable via ext_localconf.php)

**Subject** ✱
Email subject line. Supports `{{field-name}}` variables.

**Body** ✱
Email body content (RTE-enabled). Supports `{{field-name}}` variables.

**Attach Uploads**
Checkbox to attach uploaded files to email

#### Recipients Tab

**Recipient Email Addresses** ✱
Comma-separated list of recipient emails. Supports `{{field-name}}` variables.
Example: `admin@example.com, {{contact-email}}`

**CC Recipient Email Addresses**
CC recipients (optional)

**BCC Recipient Email Addresses**
BCC recipients (optional)

**Reply-to Email Addresses**
Reply-to address (optional). Supports `{{field-name}}`.

#### Sender Tab

**Sender Email Address**
Sender email. Falls back to `$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']`

**Sender Name**
Sender name. Falls back to `$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']`

> **⚠️ Security:** Don't use user-submitted email as sender address. Use `noreply@yourdomain.com` and set Reply-to to `{{user-email}}` if needed.

### Example: Contact Form Email

```
Type: Send Email
Subject: New Contact Form Submission from {{name}}
Body:
  Name: {{name}}
  Email: {{email-address}}
  Message: {{message}}
Recipient Email Addresses: contact@example.com
Reply-to Email Addresses: {{email-address}}
Sender Email Address: noreply@example.com
Attach Uploads: Yes
```

---

## 💾 Save Submission

Saves form submission to `tx_shape_form_submission` table.

### Settings

**Submission Storage Page** ✱
Page where submission records are stored

**Exclude fields from saved data**
Comma-separated field names to exclude (e.g., `password,credit-card`)

**Save User Agent and IP-Address**
Checkbox to save user's IP and browser info

**Connect to original language form**
For multi-language sites: connect submissions to original language form record instead of translated version

### Submission Record

Saved data includes:
- Form reference
- Plugin reference
- Frontend user (if logged in)
- Site language
- Form values (JSON)
- User IP and User Agent (if enabled)
- Timestamp

### Example

```
Type: Save Submission
Submission Storage Page: Forms Folder (ID: 123)
Exclude fields: password-confirm
Save User Agent and IP-Address: Yes
```

---

## 💿 Save to Database

Saves form values to a custom database table.

### Settings

**Table Name** ✱
Target database table (e.g., `tx_myext_contact`, `fe_users`)

**Record Storage Page**
PID where record is stored

**Update Row where Column ...**
Column name for UPDATE queries (optional, for updating existing records)

**... equals Value**
Value to match for UPDATE queries. Supports `{{field-name}}`.

**Columns**
Repeatable section for field mapping:
- **Name** - Database column name
- **Value** - Form field name or static value. Supports `{{field-name}}`.

### Insert vs Update

**Insert new record:**
```
Table Name: tx_myext_newsletter
Columns:
  email → {{email-address}}
  first_name → {{first-name}}
  optin_date → {{__TIMESTAMP__}}
```

**Update existing record:**
```
Table Name: fe_users
Update Row where Column: uid
... equals Value: {{fe_user_uid}}
Columns:
  address → {{street-address}}
  zip → {{postal-code}}
```

### Special Values

- `{{__TIMESTAMP__}}` - Current Unix timestamp
- `{{field-name}}` - Form field value
- Static values - Direct input (e.g., `1`, `active`, `pending`)

### Example: Newsletter Subscription

```
Type: Save to Database
Table Name: tx_myext_newsletter
Record Storage Page: Newsletter Data (ID: 456)
Columns:
  email → {{email-address}}
  name → {{first-name}} {{last-name}}
  consent_date → {{__TIMESTAMP__}}
  status → active
```

---

## ✅ Email Consent (Double Opt-In)

Sends verification email with approval link. Subsequent finishers can be re-executed after user confirms.

### Settings

#### Consent Tab

**Consent Storage Page** ✱
Page where consent records are stored

**Consent Validation Plugin Page** ✱
Page containing the "Shape Email Consent Validation" plugin (handles approval/dismissal links)

**Expiration Time in Seconds**
How long the verification link is valid (default: 86400 = 24 hours)

**Split Finisher Execution**
When enabled: finishers before this run immediately, finishers after run only after approval
When disabled: all finishers run immediately, those with `isConsentApproved()` condition run again after approval

**Delete Consent Record after Confirmation**
Remove consent record from database after approval/dismissal

#### Mail Tab

**Recipient Email Address** ✱
User's email address. Use `{{email-field-name}}`.

**Subject** ✱
Verification email subject

**Body** ✱
Verification email body (RTE). Must include approval link. Use template or custom HTML with `{{approvalUrl}}` variable.

**Reply-to Email Address**
Optional reply-to address

#### Sender Tab

**Sender Email Address**
Falls back to system default

**Sender Name**
Falls back to system default

### Workflow

1. User submits form
2. Email Consent Finisher sends verification email
3. If Split Finisher Execution enabled: stops subsequent finishers
4. User clicks approval link in email
5. Consent marked as approved
6. Subsequent finishers execute (or finishers with `isConsentApproved()` condition)

### Finisher Conditions

Use these conditions in other finishers:

- `isConsentApproved()` - Execute only after user approves
- `isConsentDismissed()` - Execute only if user dismisses
- `isBeforeConsent()` - Execute only before consent confirmation

### Example: Newsletter with Verification

**Finisher 1: Email Consent**
```
Type: Email Consent
Recipient Email Address: {{email-address}}
Subject: Please confirm your newsletter subscription
Body: [Use default template or custom with {{approvalUrl}}]
Expiration Time in Seconds: 172800  (48 hours)
Split Finisher Execution: Yes
```

**Finisher 2: Save to Newsletter Table**
```
Type: Save to Database
(Runs only after approval because Split Finisher Execution is enabled)
Table Name: tx_myext_newsletter
Columns:
  email → {{email-address}}
  confirmed → 1
```

**Finisher 3: Send Welcome Email**
```
Type: Send Email
(Runs only after approval)
Subject: Welcome to our newsletter!
Recipient Email Addresses: {{email-address}}
```

**Finisher 4: Log Dismissal** (Optional)
```
Type: Save to Database
Condition: isConsentDismissed()
Table Name: tx_myext_newsletter_dismissed
```

---

## 🔀 Redirect

Redirects user to a page or URL after form submission.

### Settings

**Redirect URL** ✱
Target page or URL. Supports `{{field-name}}` in URL parameters.

Link browser allows selection of:
- Internal pages
- External URLs
- Parameters

### Examples

**Redirect to thank-you page:**
```
Type: Redirect
Redirect URL: t3://page?uid=123
```

**Redirect with query parameters:**
```
Type: Redirect
Redirect URL: t3://page?uid=456&ref={{reference-code}}&email={{email-address}}
```

**External URL:**
```
Type: Redirect
Redirect URL: https://example.com/thanks
```

---

## 📄 Show Content Elements

Displays content elements instead of redirect after submission.

### Settings

**Content Elements** ✱
Select one or more content elements to display

### Behavior

- Selected content elements rendered in place of form
- No redirect occurs
- User stays on same page
- Content elements can contain thank-you message, related information, etc.

### Example

```
Type: Show Content Elements
Content Elements: Thank You Message (ID: 789), Related Products (ID: 790)
```

---

## Finisher Execution Order

Finishers execute in the order they appear in the form record.

**Important:** Email Consent with "Split Finisher Execution" enabled stops subsequent finishers until user confirms.

### Example Flow

```
Form Submission
  ↓
1. Email Consent (Split enabled)
  ↓ [STOPS HERE]
User Clicks Approval Link
  ↓
2. Save Submission
  ↓
3. Send Email
  ↓
4. Redirect
```

### Without Split Execution

```
Form Submission
  ↓
1. Save Submission (with condition: isBeforeConsent())
  ↓
2. Email Consent (Split disabled)
  ↓
3. Send Thank You Email (with condition: isConsentApproved())

User Clicks Approval Link
  ↓
3. Send Thank You Email (executed again)
```

---

## Custom Finishers

Developers can create custom finishers by extending `AbstractFinisher`. See [Developer Guide](DeveloperGuide.md#custom-finishers).

---

## 🔗 Related

- [Conditions](Conditions.md) - Finisher condition syntax
- [Editor Guide](EditorGuide.md) - Building forms
- [Developer Guide](DeveloperGuide.md) - Custom finishers
