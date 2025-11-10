# Field Reference

Complete reference for all field types, properties, and validation.

> **📌 Note:** Field names are auto-generated as kebab-case from labels and must be unique per PID.

## Table of Contents

- [Common Properties](#-common-properties)
- [Text Input Fields](#-text-input-fields)
  - [Text](#text) | [Textarea](#textarea) | [Email](#email) | [Telephone](#telephone) | [Password](#password) | [URL](#url) | [Number](#number) | [Search](#search)
- [Selection Fields](#-selection-fields)
  - [Select](#select) | [Radio Buttons](#radio-buttons) | [Checkbox](#checkbox) | [Multi Select](#multi-select) | [Multi Checkbox](#multi-checkbox) | [Country Select](#country-select)
- [DateTime Fields](#-datetime-fields)
  - [Date](#date) | [Date Time Local](#date-time-local) | [Time](#time) | [Month](#month) | [Week](#week)
- [Special Fields](#-special-fields)
  - [File Upload](#file-upload) | [Range](#range) | [Color](#color) | [Hidden](#hidden) | [Reset](#reset)
- [Advanced Fields](#-advanced-fields)
  - [Select with Text Field Override](#select-with-text-field-override) | [Repeatable Field Group](#repeatable-field-group)
- [Content Elements](#-content-elements)
  - [Divider](#divider) | [Header](#header) | [RTE Content](#rte-content) | [Content Element](#content-element)
- [Field Validation](#-field-validation)
  - [Validation Overview](#validation-overview)
  - [NotEmptyValidator](#notemptyvalidator) | [HTMLPatternValidator](#htmlpatternvalidator) | [StringLengthValidator](#stringlengthvalidator) | [MultipleOfInRangeValidator](#multipleofinrangevalidator) | [DateTimeRangeValidator](#datetimerangevalidator) | [HTMLAcceptValidator](#htmlacceptvalidator) | [FileSizeValidator](#filesizevalidator) | [EmailAddressValidator](#emailaddressvalidator) | [UrlValidator](#urlvalidator) | [EqualValidator](#equalvalidator) | [InArrayValidator](#inarrayvalidator) | [SubsetArrayValidator](#subsetarrayvalidator) | [CountValidator](#countvalidator) | [FileUploadValidator](#fileuploadvalidator)

---

## 📋 Common Properties

Properties available on most or all field types.

### General

| Property | Type | Description | Validation |
|----------|------|-------------|------------|
| **Label** | Text | Field title shown to user | |
| **Name** | Slug | Field identifier (auto-generated kebab-case, unique per PID) | |
| **Type** | Select | Field type (see types below) | |
| **Default Value** | Text | Pre-filled value | |
| **Required** ✓ | Checkbox | Field must be filled | [Not Empty](#notemptyvalidator) |
| **Description** | Text | Help text below field | |
| **Placeholder** | Text | Placeholder text (text inputs only) | |

### Options (Advanced Tab)

| Property | Type | Description | Validation |
|----------|------|-------------|------------|
| **Disabled** | Checkbox | Field not editable, value not submitted | |
| **Readonly** | Checkbox | Field not editable, value submitted | |
| **Multiple** | Checkbox | Allow multiple values (select, file) | |
| **Regular Expression Pattern** ✓ | Text | Regex validation | [Pattern](#htmlpatternvalidator) |
| **Maximum length** ✓ | Number | Maximum characters | [String Length](#stringlengthvalidator) |
| **Minimum** ✓ | Number/Text | Min value (number/range), min date (datetime), min file size in kB (file), min selections (multi-select) | [Range](#multipleofinrangevalidator) / [DateTime Range](#datetimerangevalidator) / [File Size](#filesizevalidator) / [Count](#countvalidator) |
| **Maximum** ✓ | Number/Text | Max value (number/range), max date (datetime), max file size in kB (file), max selections (multi-select) | [Range](#multipleofinrangevalidator) / [DateTime Range](#datetimerangevalidator) / [File Size](#filesizevalidator) / [Count](#countvalidator) |
| **Increment Step** ✓ | Number | Value increments (number, range, time) | [Step](#multipleofinrangevalidator) |
| **Accepted File Types** ✓ | Text | Allowed file types (`.pdf`, `image/*`, MIME types) | [Accept](#htmlacceptvalidator) |
| **Datalist** | Textarea | Suggestions (one per line) | |
| **Autocomplete** | Select | Browser autocomplete type (`email`, `tel`, `name`, etc.) | |
| **Autocomplete Modifier** | Select | Autocomplete prefix (`shipping`, `billing`) | |
| **Confirmation input** ✓ | Checkbox | Create confirmation field (suffix: `__CONFIRM`) | [Equal](#equalvalidator) |

### Appearance

| Property | Type | Description |
|----------|------|-------------|
| **Layout** | Select | Field layout (default: "Default") |
| **Label Layout** | Select | "Default" or "Hidden" |
| **Width** | Number | Percentage (20-100, e.g., 50 for half width) |
| **CSS Class** | Text | Custom CSS classes (space-separated) |
| **Custom Validation Message** | Text | Override default error message |
| **Rich-Text Label** | RTE | RTE label override |

### Condition

| Property | Type | Description |
|----------|------|-------------|
| **Server-side Display Condition** | Text | Expression Language condition ([Conditions Guide](Conditions.md)) |
| **Client-side Display Condition** | Text | Subscript condition ([Conditions Guide](Conditions.md)) |

> **💡 Best Practice:** Set both server and client conditions for security and UX. See [Conditions Guide](Conditions.md).

---

## 🔤 Text Input Fields

### Text

Single-line text input.

**Additional Properties:** Placeholder
**Validation:** Required, Pattern, Maximum length

---

### Textarea

Multi-line text input.

**Additional Properties:** Placeholder
**Validation:** Required, Pattern, Maximum length

---

### Email

Email input with automatic validation.

**Additional Properties:** Placeholder, Multiple (comma-separated emails), Confirmation input
**Validation:** ✓ Required, Pattern, Maximum length, [Email format](#emailaddressvalidator)

---

### Telephone

Phone number input.

**Additional Properties:** Placeholder
**Validation:** Required, Pattern, Maximum length

---

### Password

Masked password input.

**Additional Properties:** Placeholder, Confirmation input
**Processing:** Values hashed with `password_hash()` before storage
**Validation:** Required, Pattern, Maximum length

---

### URL

URL input with automatic validation.

**Additional Properties:** Placeholder
**Validation:** ✓ Required, Pattern, Maximum length, [URL format](#urlvalidator)

---

### Number

Numeric input with step validation.

**Additional Properties:** Minimum, Maximum, Increment Step, Datalist
**Processing:** Converted to int/float
**Validation:** ✓ Required, [Min/max range, step offset](#multipleofinrangevalidator)

---

### Search

Search input field.

**Additional Properties:** Placeholder
**Validation:** Required, Pattern, Maximum length

---

## ✅ Selection Fields

All selection fields (except Country Select) require **Field Options** ([Editor Guide](EditorGuide.md#field-options)).

### Select

Dropdown selection.

**Additional Properties:** None (uses Field Options)
**Validation:** ✓ Required, [Value in options](#inarrayvalidator)

---

### Radio Buttons

Radio button group.

**Additional Properties:** None (uses Field Options)
**Validation:** ✓ Required, [Value in options](#inarrayvalidator)

---

### Checkbox

Single checkbox.

**Additional Properties:** Default Value (checked state: `1` or `0`)
**Validation:** Required

---

### Multi Select

Multiple selection dropdown.

**Additional Properties:** Minimum (selections), Maximum (selections)
**Validation:** ✓ Required, [Min/max count](#countvalidator), [Values in options](#subsetarrayvalidator)

---

### Multi Checkbox

Checkbox group.

**Additional Properties:** Minimum (selections), Maximum (selections)
**Validation:** ✓ Required, [Min/max count](#countvalidator), [Values in options](#subsetarrayvalidator)

---

### Country Select

Pre-populated country dropdown.

**Additional Properties:** Datalist (filter to specific ISO 3166-1 alpha-2 codes, one per line)
**Validation:** Required

---

## 📅 DateTime Fields

### Date

Date picker.

**Additional Properties:** Minimum (date), Maximum (date), Autocomplete, Autocomplete Modifier, Datalist
**Validation:** ✓ Required, [Min/max range](#datetimerangevalidator)

---

### Date Time Local

Date and time picker.

**Additional Properties:** Minimum (datetime), Maximum (datetime), Autocomplete, Autocomplete Modifier, Datalist
**Validation:** ✓ Required, [Min/max range](#datetimerangevalidator)

---

### Time

Time picker.

**Additional Properties:** Minimum (time), Maximum (time), Increment Step, Autocomplete, Autocomplete Modifier, Datalist
**Validation:** ✓ Required, [Min/max range](#datetimerangevalidator)

---

### Month

Month/year picker.

**Additional Properties:** Minimum, Maximum, Autocomplete, Autocomplete Modifier, Datalist
**Validation:** ✓ Required, [Min/max range](#datetimerangevalidator)

---

### Week

Week picker.

**Additional Properties:** Minimum, Maximum, Autocomplete, Autocomplete Modifier, Datalist
**Validation:** ✓ Required, [Min/max range](#datetimerangevalidator)

---

## 🎯 Special Fields

### File Upload

File upload with validation.

**Additional Properties:**
- **Accepted File Types** ✓ - File types (`.pdf`, `image/*`, MIME types)
- **Multiple** - Allow multiple files
- **Minimum** ✓ - Min file size in kilobytes
- **Maximum** ✓ - Max file size in kilobytes

**Processing:** Files saved to session-specific folder
**Validation:** ✓ Required, [Upload errors](#fileuploadvalidator), [Accept types (MIME + extension)](#htmlacceptvalidator), [File size](#filesizevalidator)

---

### Range

Slider input.

**Additional Properties:** Minimum, Maximum, Increment Step, Datalist
**Processing:** Converted to int/float
**Validation:** ✓ Required, [Min/max range, step offset](#multipleofinrangevalidator)

---

### Color

Color picker.

**Additional Properties:** Default Value (hex color)
**Validation:** Required

---

### Hidden

Hidden field (not visible to user).

**Additional Properties:** Default Value
**Validation:** None

---

### Reset

Reset button (clears form).

**Additional Properties:** None
**Validation:** None

---

## 🔧 Advanced Fields

### Select with Text Field Override

Select with "Other" text input option.

**Additional Properties:** Regular Expression Pattern, Maximum length (for text input), Minimum/Maximum (for multi-select variant)
**Requires:** Field Options
**Validation:** ✓ Required, [Pattern/maxlength on text input](#htmlpatternvalidator), [Values in options](#inarrayvalidator)

---

### Repeatable Field Group

Container for dynamic fieldsets. See [Repeatable Container Guide](RepeatableContainer.md).

**Additional Properties:**
- **Minimum** - Min number of fieldsets
- **Maximum** - Max number of fieldsets
- **Fields** - Nested fields (inline relation)

**Validation:** ✓ Each fieldset validated independently
**Display Conditions:** Use `[__INDEX]` placeholder for nested field conditions ([Repeatable Container Guide](RepeatableContainer.md))

---

## 📄 Content Elements

Not form inputs - used for layout and content.

### Divider

Horizontal line separator.

**Additional Properties:** None

---

### Header

Heading element.

**Additional Properties:** Label (heading text)

---

### RTE Content

Rich text content block.

**Additional Properties:** Label, Description (RTE content)

---

### Content Element

TYPO3 content element reference.

**Additional Properties:** Default Value (element selector)

---

## ✓ Field Validation

Shape automatically adds validators based on field properties and types.

### Validation Overview

| Property/Type | Validator | Description | Class |
|---------------|-----------|-------------|-------|
| **Required** | NotEmptyValidator | Field must have value | [NotEmptyValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **Pattern** | HTMLPatternValidator | Regex pattern matching | [HTMLPatternValidator](../Classes/Form/Validation/Validator/HTMLPatternValidator.php) |
| **Maximum length** | StringLengthValidator | Max characters | [StringLengthValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **Min/Max/Step** (Number, Range) | MultipleOfInRangeValidator | Range and step validation | [MultipleOfInRangeValidator](../Classes/Form/Validation/Validator/MultipleOfInRangeValidator.php) |
| **Min/Max** (DateTime) | DateTimeRangeValidator | Date/time range | [DateTimeRangeValidator](../Classes/Form/Validation/Validator/DateTimeRangeValidator.php) |
| **Min/Max** (Multi-select) | CountValidator | Selection count | [CountValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **Accepted File Types** | HTMLAcceptValidator | File type (MIME + ext) | [HTMLAcceptValidator](../Classes/Form/Validation/Validator/HTMLAcceptValidator.php) |
| **Min/Max** (File) | FileSizeValidator | File size in kB | [FileSizeValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **Type: Email** | EmailAddressValidator | Email format | [EmailAddressValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **Type: URL** | UrlValidator | URL format | [UrlValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **Confirmation input** | EqualValidator | Fields match | [EqualValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **Field Options** (Select, Radio) | InArrayValidator | Value in options | [InArrayValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **Field Options** (Multi) | SubsetArrayValidator | Values in options | [SubsetArrayValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |
| **File Upload** | FileUploadValidator | Upload errors | [FileUploadValidator](../Classes/Form/Validation/ValueValidationConfigurator.php) |

All validators follow HTML5 constraint validation behavior.

---

### NotEmptyValidator

Applied when **Required** is checked.

**Validation:**
- Checks field has non-empty value
- Strings trimmed before check
- Checkboxes must be checked
- Arrays must have at least one element

**Custom Message:** Use "Custom Validation Message" property

---

### HTMLPatternValidator

Applied when **Regular Expression Pattern** is set.

**Validation:**
- Full regex syntax support
- Automatically anchored (adds `^` and `$`)
- Unicode support
- Case-sensitive by default

**Examples:**

**5 Digits (ZIP Code):**
```
Pattern: [0-9]{5}
Message: Please enter a valid ZIP code (5 digits)
```

**Phone Number (International):**
```
Pattern: [\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}
Message: Please enter a valid phone number
```

**Alphabetic (Latin):**
```
Pattern: [A-Za-zÀ-ÖØ-öø-ÿĀ-ſ]+
Message: Only letters allowed
```

**Strong Password:**
```
Pattern: (?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}
Message: Password must be 8+ characters with uppercase, lowercase, number, and special character
```

---

### StringLengthValidator

Applied when **Maximum length** is set.

**Validation:**
- Checks string length doesn't exceed maximum
- Counts characters (not bytes)
- Applied to: Text, Textarea, Email, Tel, Password, URL, Search

---

### MultipleOfInRangeValidator

Applied to **Number** and **Range** fields when Min/Max/Step are set.

**Validation:**
- Checks value is within min/max range
- Checks value is valid multiple of step
- Handles floating-point precision
- Step validation includes offset (e.g., min=1, step=2 allows 1, 3, 5, 7...)

**Example:**
```
Type: Number
Minimum: 0
Maximum: 100
Increment Step: 5
→ Allows: 0, 5, 10, 15, ..., 100
→ Rejects: 3, 7, 101
```

---

### DateTimeRangeValidator

Applied to datetime fields when Min/Max are set.

**Validation:**
- Checks date/time is within range
- Format-specific (date, datetime-local, time, month, week)
- Handles timezone for datetime-local

**Example:**
```
Type: Date
Minimum: 2024-01-01
Maximum: 2024-12-31
→ Only allows dates in 2024
```

---

### HTMLAcceptValidator

Applied to **File Upload** when **Accepted File Types** is set.

**Validation:**
- Validates MIME type (server-detected, not just extension)
- Validates file extension
- Supports wildcards (`image/*`, `video/*`, `audio/*`)
- Replicates browser `accept` attribute behavior

**Examples:**

**PDFs only:**
```
Accepted File Types: .pdf
→ Allows: application/pdf with .pdf extension
```

**Images:**
```
Accepted File Types: image/*
→ Allows: image/jpeg, image/png, image/gif, image/svg+xml, etc.
```

**Mixed:**
```
Accepted File Types: image/*,.pdf,.doc,.docx
→ Allows: All images, PDFs, and Word documents
```

**Security:** MIME type is server-detected and validated, not just extension checking.

---

### FileSizeValidator

Applied to **File Upload** when Min/Max are set.

**Validation:**
- Checks file size in kilobytes
- Applied per file (for multiple uploads)

**Example:**
```
Minimum: 10      (10 kB minimum)
Maximum: 5120    (5 MB maximum)
```

**Reference:**
- 1 MB = 1024 kB
- 5 MB = 5120 kB
- 10 MB = 10240 kB

---

### EmailAddressValidator

Applied automatically to **Email** field type.

**Validation:**
- Validates email format
- Supports international domain names
- Allows multiple emails if **Multiple** is checked (comma-separated)

---

### UrlValidator

Applied automatically to **URL** field type.

**Validation:**
- Validates URL format
- Requires scheme (`http://`, `https://`, etc.)
- Validates domain structure

---

### EqualValidator

Applied when **Confirmation input** is checked.

**Validation:**
- Creates second field with `__CONFIRM` suffix
- Validates both fields have identical values
- Case-sensitive comparison

**Example:**
```
Field: email-address
Confirmation Field: email-address__CONFIRM
```

**Applied to:** Text, Email, Password

---

### InArrayValidator

Applied to **Select** and **Radio** fields with Field Options.

**Validation:**
- Checks submitted value exists in Field Options
- Prevents tampering with values

---

### SubsetArrayValidator

Applied to **Multi Select** and **Multi Checkbox** fields with Field Options.

**Validation:**
- Checks all submitted values exist in Field Options
- Validates array subset relationship

---

### CountValidator

Applied to **Multi Select** and **Multi Checkbox** when Min/Max are set.

**Validation:**
- Checks number of selected options
- Minimum: at least N options selected
- Maximum: no more than N options selected

**Example:**
```
Type: Multi Checkbox
Minimum: 2
Maximum: 5
→ User must select 2-5 options
```

---

### FileUploadValidator

Applied automatically to **File Upload** field.

**Validation:**
- Checks PHP upload errors
- Validates `$_FILES` structure
- Ensures file was actually uploaded

---

### Custom Validators

Developers can add custom validators via events. See [Developer Guide](DeveloperGuide.md#custom-validators).

---

## 🔗 Next Steps

- [Editor Guide](EditorGuide.md) - Using fields in forms
- [Repeatable Container](RepeatableContainer.md) - Dynamic fieldsets
- [Conditions](Conditions.md) - Display conditions
- [Developer Guide](DeveloperGuide.md) - Custom validators
