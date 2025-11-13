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

### {{ }} Template Variables

Many finisher settings support `{{ variable }}` syntax to dynamically insert form values. The template variable parser provides several powerful features for accessing and formatting data.
> **📌 Note:** While the included finishers provide only the form values to the TemplateVariableParser, custom finishers could expose other variables to its settings.

#### Basic Syntax

**Simple variable replacement:**
```
Subject: New Contact from {{first-name}} {{last-name}}
Recipient: {{email-address}}
URL: https://example.com/thanks?ref={{reference-code}}
```

**Object property access:**
```
{{objectVariable.property}}
```
The parser tries getter methods first (e.g., `getProperty()`), then falls back to property access.

#### Array Operations

**Array to comma-separated list:**
```
Selected options: {{selected-items[]}}
```
Converts array `['Option A', 'Option B', 'Option C']` to `"Option A, Option B, Option C"`

**Array property extraction:**
```
Email addresses: {{contacts[].email}}
Names: {{family-members[].name}}
```
Extracts a specific property from each array element and joins with commas.

**Nested array access:**
```
{{user.addresses[].city}}
```

#### Examples

**Contact form email subject:**
```
New inquiry from {{first-name}} {{last-name}} about {{inquiry-topics[]}}
```

**Email recipient with multiple contacts:**
```
{{primary-contact}}, {{additional-contacts[].email}}
```
Result: `admin@example.com, user1@example.com, user2@example.com`

**Redirect URL with multiple parameters:**
```
https://example.com/thanks?name={{name}}&interests={{interests[]}}
```

#### Behavior Notes

- **Whitespace:** Spaces around variable names are ignored: `{{ field-name }}` = `{{field-name}}`
- **Missing values:** If variable doesn't exist, the placeholder remains unchanged: `{{missing-field}}`
- **Arrays without []:** Arrays without the `[]` operator remain as placeholder: `{{array-field}}`
- **Null values:** Null values are skipped in array operations
- **Nested arrays:** Nested arrays are skipped in array operations
- **Empty results:** Empty arrays produce empty string: `{{empty-array[]}}` → `""`

> **📌 Note:** Use actual field names as defined in the field records.

### Finisher Conditions

Execute finishers conditionally using Expression Language:

```
value("newsletter") == "yes"
isConsentApproved()
isConsentDismissed()
isBeforeConsent()
```

See [Conditions Guide](Conditions.md) for server-side condition details.

---

## 📧 Send Email

Sends an email with form values.

### Settings

#### Mail

| Setting            | Description                                                       |
|--------------------|-------------------------------------------------------------------|
| **Template** ✱     | Email template selection (configurable via ext_localconf.php)     |
| **Subject** ✱      | Email subject line. **Supports template variables.**              |
| **Body** ✱         | Email body content (RTE-enabled). **Supports template variables.** |
| **Attach Uploads** | Checkbox to attach uploaded files to email                        |

#### Recipients

| Setting                          | Description                                                                                                                                      |
|----------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| **Recipient Email Addresses** ✱  | Comma-separated list of recipient emails. **Supports template variables.** Example: `admin@example.com, {{contact-email}}, {{members[].email}}` |
| **CC Recipient Email Addresses** | CC recipients. **Supports template variables.**                                                                                                  |
| **BCC Recipient Email Addresses** | BCC recipients. **Supports template variables.**                                                                                                 |
| **Reply-to Email Addresses**     | Reply-to address. **Supports template variables.**                                                                                               |

#### Sender

| Setting                  | Description                                                                  |
|--------------------------|------------------------------------------------------------------------------|
| **Sender Email Address** | Sender email. Falls back to `$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']` |
| **Sender Name**          | Sender name. Falls back to `$GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']` |

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

| Setting                                 | Description                                                                                              |
|-----------------------------------------|----------------------------------------------------------------------------------------------------------|
| **Submission Storage Page** ✱           | Page where submission records are stored                                                                 |
| **Exclude fields from saved data**      | Comma-separated field names to exclude (e.g., `password,credit-card`)                                    |
| **Save User Agent and IP-Address**      | Checkbox to save user's IP and browser info                                                              |
| **Connect to original language form**   | For multi-language sites: connect submissions to original language form record instead of translated version |

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

Saves form values to a custom database table. In most cases, a custom finisher and validation logic should be implemented instead.

### Settings

| Setting                        | Description                                                                        |
|--------------------------------|------------------------------------------------------------------------------------|
| **Table Name** ✱               | Target database table (e.g., `tx_myext_contact`, `fe_users`)                      |
| **Record Storage Page**        | PID where record is stored                                                         |
| **Update Row where Column ...** | Column name for UPDATE queries (optional, for updating existing records)           |
| **... equals Value**           | Value to match for UPDATE queries. **Supports template variables.**                |
| **Columns**                    | Repeatable section for field mapping:<br>• **Name** - Database column name<br>• **Value** - Form field name or static value. **Supports template variables.** |

### Insert vs Update

**Insert new record:**
```
Table Name: tx_myext_newsletter
Columns:
  email → {{email-address}}
  first_name → {{first-name}}
```


### Example: Newsletter Subscription

```
Type: Save to Database
Table Name: tx_myext_newsletter
Record Storage Page: Newsletter Data (ID: 456)
Columns:
  email → {{email-address}}
  name → {{first-name}} {{last-name}}
```

---

## ✅ Email Consent (Double Opt-In)

Sends verification email with approval link. Subsequent finishers can be re-executed after user confirms.

### Settings

#### Consent

| Setting                                      | Description                                                                                                                                                                                                                                                                          |
|----------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Consent Storage Page** ✱                   | Page where consent records are stored                                                                                                                                                                                                                                                |
| **Consent Validation Plugin Page** ✱         | Page containing the "Shape Email Consent Validation" plugin (handles approval/dismissal links)                                                                                                                                                                                       |
| **Expiration Time in Seconds**               | How long the verification link is valid (default: 86400 = 24 hours)                                                                                                                                                                                                                  |
| **Split Finisher Execution**                 | **Recommended**<br>When enabled: finishers before this run immediately, finishers after run only after approval<br>When disabled: all finishers run immediately AND after approval, fine-tune finisher execution with conditions like `isBeforeConsent()` and `isConsentApproved()` |
| **Delete Consent Record after Confirmation** | Remove consent record from database after approval/dismissal                                                                                                                                                                                                                         |

#### Mail

| Setting                        | Description                                                        |
|--------------------------------|--------------------------------------------------------------------|
| **Recipient Email Address** ✱  | User's email address. **Supports template variables.**             |
| **Subject** ✱                  | Verification email subject. **Supports template variables.**       |
| **Body** ✱                     | Verification email body (RTE). Approval link is appended. **Supports template variables.** |
| **Reply-to Email Address**     | Optional reply-to address. **Supports template variables.**        |

#### Sender

| Setting                  | Description                    |
|--------------------------|--------------------------------|
| **Sender Email Address** | Falls back to system default   |
| **Sender Name**          | Falls back to system default   |

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
Body: ...
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

---

## 🔀 Redirect

Redirects user to a page or URL after form submission.

### Settings

| Setting            | Description                                                                                                |
|--------------------|------------------------------------------------------------------------------------------------------------|
| **Redirect URL** ✱ | Target page or URL.<br>Link browser allows selection of:<br>• Internal pages<br>• External URLs<br>• Parameters |

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

| Setting                 | Description                                      |
|-------------------------|--------------------------------------------------|
| **Content Elements** ✱  | Select one or more content elements to display   |

### Behavior

- Selected content elements rendered in place of form
- User is still redirected after form submission to respect Post/Redirect/Get pattern 
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
3. Send Thank You Email (with condition: isConsentApproved(), not executed yet)
  ↓

User Clicks Approval Link
  ↓
3. Send Thank You Email (executed)
```

---

## Custom Finishers

Developers can create custom finishers by extending [`AbstractFinisher`](../Classes/Form/Finisher/AbstractFinisher.php).

---

## 🔗 Related

- [Conditions](Conditions.md) - Finisher condition syntax
- [Editor Guide](EditorGuide.md) - Building forms
- [Customization Guide](CustomizationGuide.md)
