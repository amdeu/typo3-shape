# Modules Reference

Modules are the extension point for form actions - saving data, sending emails, redirecting, showing a custom finish page. A module is a small class registered against a type identifier; a form's **Modules** relation is a list of configured instances of these types, each with its own settings, an optional `condition`, and a position that determines execution order.

## How Modules Work

A module is a class implementing [`ModuleInterface`](../Classes/Form/Module/ModuleInterface.php) that declares, per public method, which form-lifecycle event it wants to react to via a `#[AsModuleEventListener]` attribute. [`ModuleInvoker`](../Classes/Form/Module/ModuleInvoker.php) builds this event → method mapping once per class (via reflection, cached) and routes the matching PSR-14 event to every module configured on the form whenever that event fires.

Every built-in module in this reference declares only an `onFormFinish` method, reacting to **form finish** (after successful submission). A module isn't restricted to that, though - it can react to validation, rendering, value processing, or any other point in the form lifecycle; see [Custom Modules](#custom-modules) for the full list of events and what a module can do with each.

## Table of Contents

- [How Modules Work](#how-modules-work)
- [Configuration](#configuration)
  - [Template Variables](#template-variables)
  - [Module Conditions](#module-conditions)
- [Send Email](#-send-email)
- [Save Submission](#-save-submission)
- [Save to Database](#-save-to-database)
- [Double Opt-in](#-double-opt-in)
- [Redirect](#-redirect)
- [Show Content](#-show-content)
- [Show Text](#-show-text)
- [Module Execution Order](#module-execution-order)
- [Custom Modules](#custom-modules)
  - [Implementing a Module](#implementing-a-module)
  - [Capabilities](#capabilities)
  - [Events](#events)
  - [Advanced: Vetoing a Module Entirely](#advanced-vetoing-a-module-entirely)
  - [Registering the Type](#registering-the-type)

## Configuration

**Form record → Modules tab → Create new**

All modules have:
- **Title** - Internal identifier
- **Type** - Module type (see below)
- **Condition** - Optional condition expression ([Conditions Guide](Conditions.md))
- **Settings** - Type-specific configuration

### Template Variables

Many module settings support `{{ variable }}` syntax to dynamically insert form values. The template variable parser provides several powerful features for accessing and formatting data.
> **📌 Note:** While the included modules provide only the form values to the TemplateVariableParser, custom modules could expose other variables to its settings.

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
- **HTML escaping:** Values inserted into a module's `body` field (Send Email, Double Opt-in) are HTML-escaped before the surrounding rich-text content is rendered, so a submitted `<script>` doesn't inject markup into the email. Other settings (subject, addresses, database columns) are inserted as-is, since escaping would corrupt plain-text/data values there.

> **📌 Note:** Use actual field names as defined in the field records.

### Module Conditions

Execute modules conditionally using Expression Language:

```
value("newsletter") == "yes"
isConsentApproved()
isConsentDismissed()
isBeforeConsent()
```

A module's condition is re-evaluated every time that module is about to react to an event — not once when the form is first built — so it correctly sees state that only exists later, such as the consent status when a form is re-finished after a user clicks a consent link.

See [Conditions Guide](Conditions.md) for server-side condition details.

---

## 📧 Send Email

Sends an email with form values.

### Settings

#### Mail

| Setting            | Description                                                       |
|--------------------|---------------------------------------------------------------------|
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

Saves form values to a custom database table. In most cases, a custom module and validation logic should be implemented instead.

### Settings

| Setting                        | Description                                                                        |
|--------------------------------|--------------------------------------------------------------------------------------|
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

## ✅ Double Opt-in

Sends a verification email with an approval link. Modules configured after this one can be re-run after the user confirms.

### Settings

#### Consent

| Setting                                      | Description                                                                                                                                                                                                                                                                    |
|----------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Consent Storage Page** ✱                   | Page where consent records are stored                                                                                                                                                                                                                                          |
| **Consent Validation Plugin Page** ✱         | Page containing the "Shape Double Opt-in Validation" plugin (handles approval/dismissal links)                                                                                                                                                                                |
| **Expiration Time in Seconds**               | How long the verification link is valid (default: 86400 = 24 hours)                                                                                                                                                                                                           |
| **Split Module Execution**                   | **Recommended**<br>When enabled: modules before this run immediately, modules after this run only after approval<br>When disabled: all modules run immediately AND after approval, fine-tune module execution with conditions like `isBeforeConsent()` and `isConsentApproved()` |
| **Delete Consent Record after Confirmation** | Remove consent record from database after approval/dismissal                                                                                                                                                                                                                  |

#### Mail

| Setting                        | Description                                                        |
|---------------------------------|----------------------------------------------------------------------|
| **Recipient Email Address** ✱  | User's email address. **Supports template variables.**             |
| **Subject** ✱                  | Verification email subject. **Supports template variables.**       |
| **Body** ✱                     | Verification email body (RTE). Approval link is appended. **Supports template variables.** |
| **Reply-to Email Address**     | Optional reply-to address. **Supports template variables.**        |

#### Sender

| Setting                  | Description                    |
|--------------------------|----------------------------------|
| **Sender Email Address** | Falls back to system default   |
| **Sender Name**          | Falls back to system default   |

### Workflow

1. User submits form
2. Double Opt-in module sends verification email
3. If Split Module Execution enabled: stops subsequent modules from running on this request
4. User clicks approval link in email
5. Consent marked as approved, the form is re-finished
6. Subsequent modules execute (or modules with an `isConsentApproved()` condition)

### Module Conditions

Use these conditions on other modules:

- `isConsentApproved()` - Execute only after user approves
- `isConsentDismissed()` - Execute only if user dismisses
- `isBeforeConsent()` - Execute only before consent confirmation

### Example: Newsletter with Verification

**Module 1: Double Opt-in**
```
Type: Double Opt-in
Recipient Email Address: {{email-address}}
Subject: Please confirm your newsletter subscription
Body: ...
Expiration Time in Seconds: 172800  (48 hours)
Split Module Execution: Yes
```

**Module 2: Save to Newsletter Table**
```
Type: Save to Database
(Runs only after approval because Split Module Execution is enabled)
Table Name: tx_myext_newsletter
Columns:
  email → {{email-address}}
  confirmed → 1
```

**Module 3: Send Welcome Email**
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
|---------------------|--------------------------------------------------------------------------------------------------------------|
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

## 📄 Show Content

Displays content elements instead of redirect after submission.

### Settings

| Setting                 | Description                                      |
|--------------------------|-----------------------------------------------------|
| **Content Elements** ✱  | Select one or more content elements to display   |

### Behavior

- Selected content elements rendered in place of form
- User is still redirected after form submission to respect Post/Redirect/Get pattern
- Content elements can contain thank-you message, related information, etc.

### Example

```
Type: Show Content
Content Elements: Thank You Message (ID: 789), Related Products (ID: 790)
```

---

## 📝 Show Text

Displays a rich-text message instead of redirect after submission.

### Settings

| Setting          | Description                                                          |
|-------------------|--------------------------------------------------------------------|
| **Bodytext** ✱   | Rich-text content shown on the finish page. **Supports template variables** (HTML-escaped before insertion). |

### Example

```
Type: Show Text
Bodytext: Thank you, {{first-name}}! We'll be in touch shortly.
```

---

## Module Execution Order

Modules execute in the order they appear in the form record.

**Important:** Double Opt-in with "Split Module Execution" enabled stops subsequent modules from running until the user confirms.

### Example Flow

```
Form Submission
  ↓
1. Double Opt-in (Split enabled)
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
2. Double Opt-in (Split disabled)
  ↓
3. Send Thank You Email (with condition: isConsentApproved(), not executed yet)
  ↓

User Clicks Approval Link
  ↓
3. Send Thank You Email (executed)
```

---

## Custom Modules

### Implementing a Module

Developers can create custom modules by implementing [`ModuleInterface`](../Classes/Form/Module/ModuleInterface.php), typically by extending [`AbstractModule`](../Classes/Form/Module/AbstractModule.php) and declaring the events it reacts to with `#[AsModuleEventListener]`:

```php
<?php

namespace MyVendor\MyExt\Form\Module;

use Amdeu\Shape\Form;
use Amdeu\Shape\Form\Module\AbstractModule;
use Amdeu\Shape\Form\Module\AsModuleEventListener;

class LogSubmissionModule extends AbstractModule
{
    protected array $settings = [
        'message' => '',
    ];

    #[AsModuleEventListener]
    public function onFormFinish(Form\FormFinishEvent $event): void
    {
        $this->logger->info($this->parseWithValues($this->settings['message']));
    }
}
```

A module isn't limited to `FormFinishEvent` — any public method tagged `#[AsModuleEventListener]` with exactly one typed parameter matching a form event class is routed automatically. A module can declare as many of these methods as it needs, one per event.

### Capabilities

`AbstractModule` gives every module:

- **`$this->settings`** - declare your own defaults as a protected property; values configured via the module's FlexForm are merged over them automatically before any event fires (`configure()` → `overrideSettings()`, using `ArrayUtility::mergeRecursiveWithOverrule`).
- **`parseWithValues(string $string, bool $escapeHtml = false): string`** - resolves `{{ field-name }}` placeholders against the current form values (the same parser used by all built-in modules' settings, see [Template Variables](#template-variables) above). Pass `escapeHtml: true` whenever the result is rendered as HTML without further escaping (e.g. via `f:format.html()`) - the built-in email/text modules do this for their `body`/`bodytext` fields specifically, and nowhere else, since HTML-escaping a database column or an email address would corrupt it rather than protect it.
- **`getRequest()`, `getPlugin()`, `getForm()`, `getFormValues()`, `getPluginSettings()`, `getView()`** - accessors onto the current [`FormRuntime`](../Classes/Form/FormRuntime.php) (also reachable directly as `$this->runtime`).
- **`$this->logger`** (PSR-3, auto-injected) and **`getLogContext(array $additionalContext = []): array`** - a minimal context array (currently just the form's uid) to keep log entries correlatable without leaking form data into logs by default.
- **`$this->configuration`** - the raw [`ModuleConfigurationInterface`](../Classes/Form/Model/ModuleConfigurationInterface.php) record (title, condition, identifier), if you need something `$this->settings` doesn't expose.

### Events

| Event | Fires | What a module can do |
|-------|-------|------------------------|
| [`FormRuntimeCreationEvent`](../Classes/Form/FormRuntimeCreationEvent.php) | Once, right after the runtime, all configured modules, and field session values all exist | One-time setup that needs the fully-assembled runtime |
| [`ExpressionResolverCreationEvent`](../Classes/Form/Condition/ExpressionResolverCreationEvent.php) | Every time an expression resolver is built (field conditions, module conditions, finish conditions, ...) | `addVariables()` to expose custom variables/functions to condition expressions across the whole form. Never fired for a module's own `condition` check on this event specifically, to avoid needing a resolver to build a resolver |
| [`BeforeFormRenderEvent`](../Classes/Form/Rendering/BeforeFormRenderEvent.php) | Every time a page renders | `addVariables()` to inject extra Fluid view variables |
| [`FieldConditionResolutionEvent`](../Classes/Form/Condition/FieldConditionResolutionEvent.php) | Resolving one field's `display_condition` | Set `$event->result` to override the outcome for that field |
| [`ValueValidationEvent`](../Classes/Form/Validation/ValueValidationEvent.php) | Validating one field's submitted value | `addValidator()` to add an Extbase validator to the chain, or set `$event->result` directly to fully replace validation for that field |
| [`ValueSerializationEvent`](../Classes/Form/Serialization/ValueSerializationEvent.php) | Before a field's value is written to session storage | Set `$event->serializedValue` to transform the value (e.g. a custom field type with a non-scalar runtime value) |
| [`ValueProcessingEvent`](../Classes/Form/Processing/ValueProcessingEvent.php) | After validation, before finish-time modules run | Set `$event->processedValue` to transform the final value (e.g. hashing, normalization) |
| [`SpamAnalysisEvent`](../Classes/Form/SpamProtection/SpamAnalysisEvent.php) | Once per submission, before validation | Add entries to `$event->spamReasons` to flag the submission as spam |
| [`FormFinishEvent`](../Classes/Form/FormFinishEvent.php) | Once, when the form finishes successfully | Set `$event->response` (a PSR-7 response that short-circuits the rest of finishing), set `$event->finishedTemplate`/`addFinishedVariables()` (custom finish page), call `$event->stopPropagation()` (skip remaining modules), or read `$event->getConditionVariables()` (extra variables available to *this* dispatch's condition evaluation, e.g. `consentStatus` when re-finishing after a consent link) |

General PSR-14 mechanics (how `#[AsEventListener]` differs from `#[AsModuleEventListener]`, event ordering, etc.) are covered in [Customization Guide → PSR-14 Events](CustomizationGuide.md#psr-14-events).

### Advanced: Vetoing a Module Entirely

Separate from a module's own `condition` field, [`ModuleConditionResolutionEvent`](../Classes/Form/Module/ModuleConditionResolutionEvent.php) fires once per configured module while the form runtime is being built - *before* that module is instantiated. Because of that timing, it's handled by a plain PSR-14 listener (`#[AsEventListener]`, not `#[AsModuleEventListener]` on the module itself - the module doesn't exist yet), which can set `$event->result = false` to exclude a module from the form entirely, regardless of its `condition`.

This is how the double opt-in flow works: [`ConsentModuleOnFinishHandler`](../Classes/Form/Consent/ConsentModuleOnFinishHandler.php) uses it to skip the Double Opt-in module (and everything configured before it) when re-finishing a form after a consent link is clicked. Most custom modules won't need this - it's for vetoing based on context that has nothing to do with the module's own settings, not a general-purpose condition mechanism (use `condition` for that).

### Registering the Type

`Configuration/TCA/Overrides/tx_shape_module_configuration.php` adds the type to the TCA select and wires up its FlexForm:

```php
<?php
use Amdeu\Shape\Utility\TcaUtility as Util;

Util::addModuleType(
    'Log Submission',
    'logSubmission',
    'content-elements-mailform',
    'FILE:EXT:my_ext/Configuration/FlexForms/Module/LogSubmissionModule.xml'
);
```

The last argument accepts an optional `columnsOverrides` array too, e.g. to enable language synchronization on the settings field.

`ext_localconf.php` registers the identifier with `ModuleRegistry`, which is what actually resolves `'logSubmission'` back to the class at runtime:

```php
<?php
use Amdeu\Shape\Form\Module\ModuleRegistry;

ModuleRegistry::register('logSubmission', \MyVendor\MyExt\Form\Module\LogSubmissionModule::class);
```
---

## 🔗 Related

- [Conditions](Conditions.md) - Module condition syntax
- [Editor Guide](EditorGuide.md) - Building forms
- [Customization Guide](CustomizationGuide.md)
