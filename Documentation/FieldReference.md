# Field Reference

Complete reference for all field types, properties, and validation.

## Table of Contents

- [Field Properties](#-field-properties)
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

## 📋 Field Properties

All properties available in the field editor.

✱ = required field,
✓ = enables validation

### General

| Property          | Description                                                   | Validation                      |
|-------------------|---------------------------------------------------------------|---------------------------------|
| **Label** ✱       | Field title shown to user                                     |                                 |
| **Name** ✱        | Field identifier (auto-generated kebab-case, unique per PID)  |                                 |
| **Type** ✱        | Field type (see types below)                                  |                                 |
| **Default Value** | Pre-filled value                                              |                                 |
| **Required** ✓    | Field must be filled                                          | [Not Empty](#notemptyvalidator) |
| **Description**   | Help text below field                                         |                                 |
| **Placeholder**   | Placeholder text (text inputs only)                           |                                 |

### Advanced

| Property                         | Description                                                                                               | Validation                                                                                                                                       |
|----------------------------------|-----------------------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------|
| **Disabled**                     | Field not editable, value not submitted                                                                   |                                                                                                                                                  |
| **Readonly**                     | Field not editable, value submitted                                                                       |                                                                                                                                                  |
| **Multiple**                     | Allow multiple values (select, file)                                                                      |                                                                                                                                                  |
| **Regular Expression Pattern** ✓ | Regex validation                                                                                          | [Pattern](#htmlpatternvalidator)                                                                                                                 |
| **Maximum length** ✓             | Maximum characters                                                                                        | [String Length](#stringlengthvalidator)                                                                                                          |
| **Minimum** ✓                    | Min value (number/range), min date (datetime), min file size in kB (file), min selections (multi-select) | [Range](#multipleofinrangevalidator) / [DateTime Range](#datetimerangevalidator) / [File Size](#filesizevalidator) / [Count](#countvalidator)   |
| **Maximum** ✓                    | Max value (number/range), max date (datetime), max file size in kB (file), max selections (multi-select) | [Range](#multipleofinrangevalidator) / [DateTime Range](#datetimerangevalidator) / [File Size](#filesizevalidator) / [Count](#countvalidator)   |
| **Increment Step** ✓             | Value increments (number, range, time)                                                                    | [Step](#multipleofinrangevalidator)                                                                                                              |
| **Accepted File Types** ✓        | Allowed file types (`.pdf`, `image/*`, MIME types)                                                        | [Accept](#htmlacceptvalidator)                                                                                                                   |
| **Datalist**                     | Suggestions (one per line)                                                                                |                                                                                                                                                  |
| **Autocomplete**                 | Browser autocomplete type (`email`, `tel`, `name`, etc.)                                                  |                                                                                                                                                  |
| **Autocomplete Modifier**        | Autocomplete prefix (`shipping`, `billing`)                                                               |                                                                                                                                                  |
| **Confirmation input** ✓         | Create confirmation field                                                                                 | [Equal](#equalvalidator)                                                                                                                         |

### Appearance

| Property                      | Description                                  |
|-------------------------------|----------------------------------------------|
| **Layout**                    | Field layout (default: "Default")            |
| **Label Layout**              | "Default" or "Hidden"                        |
| **Width**                     | Percentage (20-100, e.g., 50 for half width) |
| **CSS Class**                 | Custom CSS classes (space-separated)         |
| **Custom Validation Message** | Override default error message               |
| **Rich-Text Label**           | RTE label override                           |

### Condition

| Property                          | Description                                                     |
|-----------------------------------|-----------------------------------------------------------------|
| **Server-side Display Condition** | Expression Language condition ([Conditions Guide](Conditions.md)) |
| **Client-side Display Condition** | Subscript condition ([Conditions Guide](Conditions.md))         |


---

## 🔤 Text Input Fields

### Text

Single-line text input.

**Additional Properties:**
- Placeholder
- Disabled
- Readonly
- Regular Expression Pattern ✓
- Maximum length ✓
- Autocomplete Modifier
- Autocomplete
- Datalist

**Validations:** [Required](#notemptyvalidator), [Pattern](#htmlpatternvalidator), [Maximum length](#stringlengthvalidator)

---

### Textarea

Multi-line text input.

**Additional Properties:**
- Placeholder
- Disabled
- Readonly
- Regular Expression Pattern ✓
- Maximum length ✓
- Autocomplete Modifier
- Autocomplete
- Datalist

**Validations:** [Required](#notemptyvalidator), [Pattern](#htmlpatternvalidator), [Maximum length](#stringlengthvalidator)

---

### Email

Email input with automatic validation.

**Additional Properties:**
- Placeholder
- Multiple (comma-separated emails)
- Disabled
- Readonly
- Regular Expression Pattern ✓
- Maximum length ✓
- Autocomplete Modifier
- Autocomplete (defaults to `email`)
- Datalist

**Validations:** [Required](#notemptyvalidator), [Pattern](#htmlpatternvalidator), [Maximum length](#stringlengthvalidator), [Email format](#emailaddressvalidator)

---

### Telephone

Phone number input.

**Additional Properties:**
- Placeholder
- Disabled
- Readonly
- Regular Expression Pattern ✓
- Maximum length ✓
- Autocomplete Modifier
- Autocomplete (defaults to `tel`)
- Datalist

**Validations:** [Required](#notemptyvalidator), [Pattern](#htmlpatternvalidator), [Maximum length](#stringlengthvalidator)

---

### Password

Masked password input.

**Additional Properties:**
- Placeholder
- Confirmation input ✓
- Disabled
- Readonly
- Regular Expression Pattern ✓
- Maximum length ✓
- Autocomplete Modifier
- Autocomplete (defaults to `new-password`)
- Datalist

**Validations:** [Required](#notemptyvalidator), [Pattern](#htmlpatternvalidator), [Maximum length](#stringlengthvalidator), [Confirmation match](#equalvalidator)

**Processing:** Value encrypted with TYPO3 password hashing

---

### URL

URL input with automatic validation.

**Additional Properties:**
- Placeholder
- Disabled
- Readonly
- Regular Expression Pattern ✓
- Maximum length ✓
- Autocomplete Modifier
- Autocomplete (defaults to `url`)
- Datalist

**Validations:** [Required](#notemptyvalidator), [Pattern](#htmlpatternvalidator), [Maximum length](#stringlengthvalidator), [URL format](#urlvalidator)

---

### Number

Numeric input with step validation.

**Additional Properties:**
- Placeholder
- Disabled
- Readonly
- Minimum ✓
- Maximum ✓
- Increment Step ✓
- Datalist

**Validations:** [Required](#notemptyvalidator), [Min/max range, step offset](#multipleofinrangevalidator)

**Processing:** Converted to int/float

---

### Search

Search input field.

**Additional Properties:**
- Placeholder
- Disabled
- Readonly
- Regular Expression Pattern ✓
- Maximum length ✓
- Autocomplete Modifier
- Autocomplete
- Datalist

**Validations:** [Required](#notemptyvalidator), [Pattern](#htmlpatternvalidator), [Maximum length](#stringlengthvalidator)

---

## ✅ Selection Fields

All selection fields (except Country Select) require **Field Options** ([Editor Guide](EditorGuide.md#field-options)).

### Select

Dropdown selection.

**Additional Properties:**
- Field Options ✓
- Disabled
- Readonly

**Validations:** [Required](#notemptyvalidator), [Value in options](#inarrayvalidator)

---

### Radio Buttons

Radio button group.

**Additional Properties:**
- Field Options ✓
- Disabled
- Readonly

**Validations:** [Required](#notemptyvalidator), [Value in options](#inarrayvalidator)

---

### Checkbox

Single checkbox.

**Additional Properties:**
- Default Value (checked state: `1` or `0`)
- Disabled
- Readonly

**Validations:** [Required](#notemptyvalidator)

---

### Multi Select

Multiple selection dropdown.

**Additional Properties:**
- Field Options ✓
- Disabled
- Readonly
- Minimum (number of selections) ✓
- Maximum (number of selections) ✓

**Validations:** [Required](#notemptyvalidator), [Min/max count](#countvalidator), [Values in options](#subsetarrayvalidator)

---

### Multi Checkbox

Checkbox group.

**Additional Properties:**
- Field Options ✓
- Disabled
- Readonly
- Minimum (number of selections) ✓
- Maximum (number of selections) ✓

**Validations:** [Required](#notemptyvalidator), [Min/max count](#countvalidator), [Values in options](#subsetarrayvalidator)

---

### Country Select

Pre-populated country dropdown.

**Additional Properties:**
- Disabled
- Readonly
- Datalist (filter to specific ISO 3166-1 alpha-2 codes, one per line)

**Validations:** [Required](#notemptyvalidator)

---

## 📅 DateTime Fields

### Date

Date picker.

**Additional Properties:**
- Disabled
- Readonly
- Minimum ✓
- Maximum ✓
- Autocomplete Modifier
- Autocomplete
- Datalist

**Validations:** [Required](#notemptyvalidator), [Min/max range](#datetimerangevalidator)

---

### Date Time Local

Date and time picker.

**Additional Properties:**
- Disabled
- Readonly
- Minimum ✓
- Maximum ✓
- Autocomplete Modifier
- Autocomplete
- Datalist

**Validations:** [Required](#notemptyvalidator), [Min/max range](#datetimerangevalidator)

---

### Time

Time picker.

**Additional Properties:**
- Disabled
- Readonly
- Minimum ✓
- Maximum ✓
- Autocomplete Modifier
- Autocomplete
- Datalist

**Validations:** [Required](#notemptyvalidator), [Min/max range](#datetimerangevalidator)

---

### Month

Month/year picker.

**Additional Properties:**
- Disabled
- Readonly
- Minimum ✓
- Maximum ✓
- Autocomplete Modifier
- Autocomplete
- Datalist

**Validations:** [Required](#notemptyvalidator), [Min/max range](#datetimerangevalidator)

---

### Week

Week picker.

**Additional Properties:**
- Disabled
- Readonly
- Minimum ✓
- Maximum ✓
- Autocomplete Modifier
- Autocomplete
- Datalist

**Validations:** [Required](#notemptyvalidator), [Min/max range](#datetimerangevalidator)

---

## 🎯 Special Fields

### File Upload

File upload with validation.

**Additional Properties:**
- Disabled
- Readonly
- Multiple
- Accepted File Types ✓ (`.pdf`, `image/*`, MIME types)
- Minimum ✓ (file size in kilobytes)
- Maximum ✓ (file size in kilobytes)

**Validations:** [Required](#notemptyvalidator), [Upload errors](#fileuploadvalidator), [Accept types (MIME + extension)](#htmlacceptvalidator), [File size](#filesizevalidator)

**Processing:** Files saved to session-specific folder

---

### Range

Slider input.

**Additional Properties:**
- Disabled
- Readonly
- Minimum ✓
- Maximum ✓
- Increment Step ✓
- Datalist

**Validations:** [Required](#notemptyvalidator), [Min/max range, step offset](#multipleofinrangevalidator)

**Processing:** Converted to int/float

---

### Color

Color picker.

**Additional Properties:**
- Default Value (hex color)
- Disabled
- Readonly

**Validations:** [Required](#notemptyvalidator)

---

### Hidden

Hidden field (not visible to user).

---

### Reset

Reset button (clears form).

---

## 🔧 Advanced Fields

### Select with Text Field Override

Select with "Other" text input option.

**Additional Properties:**
- Field Options ✓
- Disabled
- Readonly
- Regular Expression Pattern ✓ (for text input)
- Maximum length ✓ (for text input)

**Validations:** [Required](#notemptyvalidator), [Pattern](#htmlpatternvalidator), [Maximum length](#stringlengthvalidator), [Values in options](#inarrayvalidator)

---

### Repeatable Field Group

Container for dynamic fieldsets. See [Repeatable Container Guide](RepeatableContainer.md).

**Additional Properties:**
- Minimum ✓ (number of fieldsets)
- Maximum ✓ (number of fieldsets)
- Fields (nested fields via inline relation)

**Validations:** Nested fields validated like normal fields, plus [Min/max count](#countvalidator) for fieldsets.

**Processing:** Nested fields are processed like normal fields

---

## 📄 Content Elements

Not form inputs - used for layout and content.

### Divider

Horizontal line separator.

---

### Header

Heading element.

**Additional Properties:**
- Label (heading text)

---

### RTE Content

Rich text content block.

**Additional Properties:**
- Description (RTE content)

---

### Content Element

TYPO3 content element reference.

**Additional Properties:**
- Default Value (element selector)

---

## ✓ Field Validation

Shape automatically adds validators based on field properties and types.

### Validation Overview

| Property/Type                       | Description               | Client-side      | Server-side                                                                            |
|-------------------------------------|---------------------------|------------------|----------------------------------------------------------------------------------------|
| **Required**                        | Field must have value     | HTML5 validation | [NotEmptyValidator](../Classes/Form/Validation/ValueValidationConfigurator.php)        |
| **Pattern**                         | Regex pattern matching    | HTML5 validation | [HTMLPatternValidator](../Classes/Form/Validator/HTMLPatternValidator.php)             |
| **Maximum length**                  | Max characters            | HTML5 validation | [StringLengthValidator](../Classes/Form/Validation/ValueValidationConfigurator.php)    |
| **Min/Max/Step** (Number, Range)    | Range and step validation | HTML5 validation | [MultipleOfInRangeValidator](../Classes/Form/Validator/MultipleOfInRangeValidator.php) |
| **Min/Max** (DateTime)              | Date/time range           | HTML5 validation | [DateTimeRangeValidator](../Classes/Form/Validator/DateTimeRangeValidator.php)         |
| **Min/Max** (Multi-select/checkbox) | Selection count           |                  | [CountValidator](../Classes/Form/Validator/CountValidator.php)                         |
| **Accepted File Types**             | File type (MIME + ext)    | HTML5 validation | [HTMLAcceptValidator](../Classes/Form/Validator/HTMLAcceptValidator.php)               |
| **Min/Max** (File)                  | File size in kB           | HTML5 validation | [FileSizeValidator](../Classes/Form/Validation/ValueValidationConfigurator.php)        |
| **Type: Email**                     | Email format              | HTML5 validation | [EmailAddressValidator](../Classes/Form/Validation/ValueValidationConfigurator.php)    |
| **Type: URL**                       | URL format                | HTML5 validation | [UrlValidator](../Classes/Form/Validation/ValueValidationConfigurator.php)             |
| **Confirmation input**              | Fields match              |                  | [EqualValidator](../Classes/Form/Validator/EqualValidator.php)                         |
| **Field Options** (Select, Radio)   | Value in options          | Restricted       | [InArrayValidator](../Classes/Form/Validator/InArrayValidator.php)                     |
| **Field Options** (Multi)           | Values in options         | Restricted       | [SubsetArrayValidator](../Classes/Form/Validator/SubsetArrayValidator.php)             |
| **Type: File Upload**               | Upload errors             |                  | [FileUploadValidator](../Classes/Form/Validator/FileUploadValidator.php)               |


---

### NotEmptyValidator

Applied when **Required** is checked.

**Validation:**
- Checks field has non-empty value
- Strings trimmed before check
- Checkboxes must be checked
- Arrays must have at least one element

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

---

### EqualValidator

Applied when **Confirmation input** is checked.

**Validation:**
- Creates second field with `__CONFIRM` suffix
- Validates both fields have identical values
- Case-sensitive comparison

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

Developers can add custom validators via events. See [Customization Guide](CustomizationGuide.md#event-overview).

---

## 🔗 Next Steps

- [Editor Guide](EditorGuide.md) - Using fields in forms
- [Repeatable Container](RepeatableContainer.md) - Dynamic fieldsets
- [Conditions](Conditions.md) - Display conditions
- [Customization Guide](CustomizationGuide.md)
