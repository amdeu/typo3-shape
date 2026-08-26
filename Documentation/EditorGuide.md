# Editor Guide

Guide for building forms in the TYPO3 backend.

## 🏗️ Form Structure

Every form consists of:
- **Form** - Container with name and settings
- **Pages** - At least one required (even for single-page forms)
- **Fields** - Form inputs
- **Modules** - Actions after submission

## ✏️ Creating Forms

### Form Record

**List** module → **Create new → Shape: Form**

General properties:
- **Label** - Form title
- **Name** - Identifier (auto-generated from label, kebab-case, globally unique)

> **📌 Note:** Form names must be unique across the installation and use kebab-case (e.g., `contact-form`, `newsletter-signup`).

> **💡 Tip:** Create a new sysfolder for each form to keep records organized.

### Pages

Forms need at least one page.

**Form record → Pages field → Create new**

General Properties:
- **Header** - Page header
- **Type** - "Page" (default) or "Summary" (review page)
- **Button Labels** - Back/Next/Submit button text

> **📌 Multi-Step Forms:** Create multiple pages. Users navigate with Next/Back buttons. Session stores values between steps.

### Fields

**Page record → Fields field → Create new**

General:
- **Label** - Field title
- **Name** - Field identifier (auto-generated from label, kebab-case, unique per PID)
- **Type** - Field type (see [Field Reference](FieldReference.md))
- **Default Value** - Pre-filled value
- **Required** - Whether field must be filled
- **Description** - Help text below field
- **Placeholder** - Placeholder text

Appearance:
- **Layout** - Display variant (baseline only "default")
- **Label Layout** - Default or Hidden
- **Width** - Percentage (e.g., 50 for half width)
- **CSS Class** - Custom classes for styling
- **RTE Label** - Rich text label override
- **Custom validation message** - Override error message

Options:
- **Disabled** - Not editable, value not submitted
- **Readonly** - Not editable, value submitted
- Many type specific options (see [Field Reference](FieldReference.md))

Condition:
- **Server-side Display Condition** - Condition expression in Symfony Expression Language, useful for multi-page forms and field variants
- **Client-side Display Condition** - Condition expression in subscript, useful for conditions based on field values on the same page

### Field Options

For select, radio, checkbox fields.

**Field record → Options field → Create new**

Properties:
- **Label** - Text shown to user
- **Value** - Submitted value
- **Selected** - Pre-selected by default
- **Starts Group** - For option groups

## 📝 Field Types

### Text Inputs
- **Text** - Single-line text
- **Textarea** - Multi-line text
- **Email** - Email with validation
- **Tel** - Phone number
- **Password** - Masked input
- **URL** - URL with validation
- **Number** - Numeric input
- **Search** - Search field

### Selection
- **Single Select** - Dropdown
- **Radio** - Radio buttons
- **Checkbox** - Single checkbox
- **Multi Select** - Multiple selection dropdown
- **Multi Checkbox** - Checkbox group
- **Country Select** - Pre-populated countries

### DateTime
- **Date** - Date picker
- **Date and time** - Date and time
- **Time** - Time picker
- **Month** - Month/year
- **Week** - Week picker

### Special
- **File** - File upload
- **Range** - Slider
- **Color** - Color picker
- **Hidden** - Hidden field
- **Reset** - Reset button

### Advanced
- **Combined (Select & Text)** - Select with "Other" text input
- **Repeatable Container** - Dynamic fieldsets

### Content
- **Divider** - Horizontal line
- **Header** - Heading
- **RTE** - Rich text
- **Content Element** - TYPO3 content element

See [Field Reference](FieldReference.md) for complete property documentation.

## ✅ Validation

Validation is based on field types and type-specific options. See [Field Reference](FieldReference.md#-field-validation) for complete validation documentation.

## 👁️ Display Conditions

Show/hide fields and pages based on values or context.

See [Conditions Guide](Conditions.md) for complete syntax reference and examples.

**Quick Examples:**

```
value("subscribe")                               // Field has value
value("country") == "US"                         // Field equals value
value("age") >= 18 && value("age") <= 65        // Range check
formData("[family-members][__INDEX][age]") < 18 // Repeatable field
```

## 🔁 Repeatable Fields

Allow users to dynamically add multiple fieldsets (addresses, contacts, etc.).

See [Repeatable Container Guide](RepeatableContainer.md) for complete documentation.

**Quick Start:**
1. Create field with **Type: Repeatable Field Group**
2. Set **Minimum/Maximum** (optional limits)
3. Add nested fields in **Fields** tab

## 🏁 Modules

Modules add behavior to the form - saving data, sending emails, redirecting users, and more. They can act at any point as a visitor works through the form, though the built-in modules below all act once the form is successfully submitted.

See [Modules Reference](Modules.md) for complete documentation of all modules and their settings.

**Available Modules:**
- **Save Submission** - Save to database
- **Send Email** - Email with form values
- **Email Consent** - Double opt-in verification
- **Save to Database** - Custom table storage
- **Redirect** - Redirect to page/URL
- **Show Content Elements** - Display content
- **Show Text** - Display a rich-text message

## 🔗 Next Steps

- [Field Reference](FieldReference.md) - Complete field documentation
- [Modules Reference](Modules.md) - Post-submission actions
- [Conditions Guide](Conditions.md) - Display condition syntax
- [Repeatable Container Guide](RepeatableContainer.md) - Dynamic fieldsets

