# Customization Guide (WIP)

Guide for customizing Shape through templates, TCA, TypoScript, event listeners and modules.

## Table of Contents

- [TypoScript Configuration](#typoscript-configuration)
- [Template Customization](#template-customization)
- [Frontend JavaScript](#frontend-javascript)
- [Template Variables](#template-variables)
- [ViewHelpers](#viewhelpers)
- [TCA Extension](#tca-extension)
- [Database Tables](#database-tables)
- [PSR-14 Events](#psr-14-events)

---

## TypoScript Configuration

### Template Paths

```typoscript
plugin.tx_shape {
    view {
        templateRootPaths.10 = EXT:my_site/Resources/Private/Shape/Templates/
        partialRootPaths.10 = EXT:my_site/Resources/Private/Shape/Partials/
        layoutRootPaths.10 = EXT:my_site/Resources/Private/Shape/Layouts/
    }
}
```

### Spam Protection

Configure spam protection settings:

```typoscript
plugin.tx_shape {
    settings {
        spamProtection {
            honeypot {
                enabled = 1
                fieldName = __email
            }
            focusPass {
                enabled = 0
                fieldName = __focus_pass
                value = human
            }
        }
    }
}
```

Focus Pass creates a hidden field that must be filled to pass spam checks. Will be filled automatically with JavaScript after a short duration if user focuses the form.

### Client-Side Features

Each of these maps to a boolean attribute on the `<shape-form>` element (see [Frontend JavaScript](#frontend-javascript)) and can be disabled per-site:

```typoscript
plugin.tx_shape {
    settings {
        clientFeatures {
            conditionalFields.enabled = 1
            validationMessages.enabled = 1
            stylableValidation.enabled = 1
        }
    }
}
```

- **conditionalFields** - client-side `js_display_condition` evaluation
- **validationMessages** - custom message text for native HTML5 validation (`Custom validation message` field property)
- **stylableValidation** - replaces the native validation bubble with a styled error element next to the field

---

## Template Customization

### Template Structure

```
Resources/Private/
├── Layouts/
│   └── Default.html                               # Main layout wrapper
├── Templates/
│   ├── Form.html                                  # Main form template
│   ├── FormLazyLoader.html                        # Lazy loading container
│   ├── Finished.html                              # Success page
│   ├── ConsentForm.html                           # Double opt-in confirm/decline page (email link target)
│   └── Module/
│       ├── SendEmail/Default.html                 # Default email template
│       ├── EmailConsentMail.html                  # Double opt-in confirmation email
│       ├── ShowContentElements.html               # Content elements display
│       └── ShowText.html                          # Show text finish page
└── Partials/
    ├── Form.html                                  # Form container
    ├── FormPage.html                              # Page wrapper
    ├── FieldList.html                             # Fields iterator
    ├── Field.html                                 # Field wrapper
    ├── Navigation.html                            # Multi-step navigation
    ├── Messages.html                              # Form-level messages
    ├── Assets.html                                # CSS/JS assets
    ├── SpamProtection.html                        # Honeypot and focus pass fields
    ├── Control/
    │   ├── Label.html                             # Field label
    │   ├── HeaderLabel.html                       # Header-style label
    │   ├── Description.html                       # Field description
    │   ├── Errors.html                            # Validation errors
    │   ├── Required.html                          # Required indicator
    │   └── GroupedOptions.html                    # Option groups for select
    ├── Icon/
    │   └── Close.html                             # Close icon SVG
    └── Field/
        ├── Text.html                              # Text input
        ├── Email.html                             # Email input
        ├── Password.html                          # Password input
        ├── Tel.html                               # Telephone input
        ├── Url.html                               # URL input
        ... (other field types)
```

> **💡 Tip:** Only override what you need. TYPO3 falls back to Shape's default templates.

### Example: Custom Email Template

`Templates/Module/SendEmail/Custom.html`:

```html
<!DOCTYPE html>
<html>
<body>
    <h2>New Submission</h2>

    <f:for each="{formValues}" key="name" as="value">
        <f:if condition="{value}">
            <p><strong>{name}:</strong> {value}</p>
        </f:if>
    </f:for>
</body>
</html>
```

Register in `ext_localconf.php`:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['shape']['modules']['sendEmail']['templates']['Module/SendEmail/Custom'] = [
    'label' => 'Custom Email Layout',
    'format' => \TYPO3\CMS\Core\Mail\FluidEmail::FORMAT_BOTH,
];
```

---

## Frontend JavaScript

Client-side behavior (display conditions, focus pass, validation messages, repeatable containers) is implemented as native [Custom Elements](https://developer.mozilla.org/en-US/docs/Web/API/Web_components/Using_custom_elements) - light DOM only, no Shadow DOM, so existing CSS and `querySelector`-based customization keep working unchanged. Everything is loaded from a single entry point, `Resources/Public/JavaScript/Elements/shape-form.js`, which imports the other element definitions - there's nothing else to register separately.

Each `<shape-field>` manages its own display condition, validation message, and stylable-validation behavior independently in its own `connectedCallback()`/`disconnectedCallback()`, gated by the matching boolean attribute on its ancestor `<shape-form>`. This means a field reacts correctly the moment it enters the DOM, however it got there - present in the initial page render, or cloned into a `<shape-repeatable-item>` - with no coordination needed from `<shape-form>` or any custom event. `<shape-form>` itself only owns focus pass, which has no single owning field.

### Elements

| Element                         | Wraps                                                  | API                                                                                                                          |
|----------------------------------|---------------------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------|
| `<shape-form>`                  | The plugin's `<form>`                                  | `namespace` attribute; `conditional-fields`/`focus-pass`/`validation-messages`/`stylable-validation` boolean attributes (`"1"`/`"0"`), driven by the settings above |
| `<shape-field>`                 | One field's control, label, and error message          | `name`/`condition` attributes; `toggleVisibility(isVisible)` method; `errorElement` getter                                  |
| `<shape-repeatable-container>`  | A repeatable field's `<template>` and current fieldsets | `id`/`iteration` attributes; owns the add-button handling                                                                    |
| `<shape-repeatable-item>`       | One repeatable fieldset                                | `index` attribute; rewrites `[__INDEX]` placeholders in its own nested `shape-field[condition]`/`name`/`id` attributes on connect |

### Conventions

- **`data-*` stays only on native elements** (`<input>`, `<select>`, `<textarea>`, `<button>`, `<p>`) - an arbitrary non-namespaced attribute on a standard element risks colliding with a future HTML spec attribute. Everything on the custom elements above is a plain, real attribute instead - we own that tag vocabulary, so there's no collision risk.
- **No class-name lookups from JS.** CSS classes (built from `{blockClass}`) are a styling concern only; JS never uses them to find elements. Marker attributes (`data-shape-error`, `data-shape-repeatable-add`, `data-shape-repeatable-remove`, `data-shape-control`) are used instead, even where the value itself is unused.

---

## Template Variables

### Main Template (`Form.html`)

The following variables are assigned by `FormRuntime::renderPage()` and available in `Form.html`:

| Variable                  | Type                        | Description                                                      |
|---------------------------|-----------------------------|------------------------------------------------------------------|
| `{session}`               | `FormSession`               | Session object containing form state                             |
| `{serializedSession}`     | `string`                    | HMAC-signed serialized session for hidden field                  |
| `{namespace}`             | `string`                    | Form namespace (kebab-case form name)                            |
| `{action}`                | `string`                    | Controller action (always `'run'`)                               |
| `{plugin}`                | `Core\Domain\Record`        | Plugin content element record                                    |
| `{form}`                  | `FormInterface`             | Form record with all properties and methods                      |
| `{settings}`              | `array`                     | TypoScript plugin settings                                       |
| `{messages}`              | `FormMessage[]`              | Form-level messages (errors, warnings, notices)                  |
| `{spamReasons}`           | `array\|null`               | Spam detection reasons if form failed spam check                 |
| `{currentPage}`           | `FormPageRecord`            | Current page record                                              |
| `{pageIndex}`             | `int`                       | Current page index (1-based)                                     |
| `{isFirstPage}`           | `bool`                      | True if on first page                                            |
| `{isLastPage}`            | `bool`                      | True if on last page                                             |
| `{backStepPageIndex}`     | `int\|null`                 | Previous page index for back button, null if on first page       |
| `{forwardStepPageIndex}`  | `int\|null`                 | Next page index for forward button, null if on last page         |

> **💡 Note:** Additional variables can be added via `BeforeFormRenderEvent`.

### Field Partials

| Variable               | Type          | Description                                      |
|------------------------|---------------|--------------------------------------------------|
| `{field}`              | `FieldRecord` | Field record with all properties                 |
| `{field.name}`         | `string`      | Field identifier (kebab-case)                    |
| `{field.type}`         | `string`      | Field type (text, email, select, etc.)           |
| `{field.label}`        | `string`      | Field label                                      |
| `{field.value}`        | `mixed`       | Current field value from session                 |
| `{field.placeholder}`  | `string`      | Placeholder text                                 |
| `{field.required}`     | `bool`        | Whether field is required                        |
| `{field.*}`            | `mixed`       | Any TCA property (pattern, min, max, etc.)      |


---

## ViewHelpers

### `shape:field.attributes`

Generates HTML attributes from field properties.

**Usage:**
```html
<f:variable name="attrs" value="{field -> shape:field.attributes()}"/>
<f:form.textfield additionalAttributes="{attrs}" />
```

**Generated attributes:**
- `name` - Field name
- `id` - Field ID
- `required` - If field is required
- `placeholder` - Placeholder text
- `pattern` - Validation pattern
- `maxlength` - Maximum length
- `min`, `max`, `step` - Number/range constraints
- `autocomplete` - Autocomplete hint
- `disabled`, `readonly` - Input state
- And more based on field type

### `shape:trimExplode`

Splits and trims lines (useful for datalist).

**Usage:**
```html
<f:for each="{field.datalist -> shape:trimExplode()}" as="item">
    <option value="{item}"/>
</f:for>
```

---

## TCA Extension

### Adding Custom Field Properties

`Configuration/TCA/Overrides/tx_shape_field.php`:

```php
<?php
defined('TYPO3') or die();

// Add custom column
$GLOBALS['TCA']['tx_shape_field']['columns']['data_tracking'] = [
    'label' => 'Data Tracking ID',
    'config' => [
        'type' => 'input',
        'size' => 30,
    ],
];

// Add to appearance palette (shows for all field types)
$GLOBALS['TCA']['tx_shape_field']['palettes']['appearance']['showitem'] .=
    ', --linebreak--, data_tracking';
```

### Using Custom Properties in Templates

```html
<f:if condition="{field.data_tracking}">
    <input data-tracking="{field.data_tracking}" />
</f:if>
```

> **📌 Note:** Custom properties are automatically accessible via `{field.property_name}`.

---

## Database Tables

### Core Tables

| Table                         | Description                          |
|-------------------------------|--------------------------------------|
| `tx_shape_form`               | Form containers                      |
| `tx_shape_form_page`          | Pages (multi-step)                   |
| `tx_shape_field`              | Form fields                          |
| `tx_shape_field_option`       | Options for select/radio/checkbox    |
| `tx_shape_module_configuration` | Post-submission actions            |
| `tx_shape_form_submission`    | Submitted data                       |
| `tx_shape_email_consent`      | Double opt-in tracking               |

### Relationships

```
Form (1:n) Pages (1:n) Fields (1:n) Options
Form (1:n) Modules
Form (1:n) Submissions
Form (1:n) Double Opt-in records
Field (1:n) Fields (nested, for repeatable-container)
```

---

## PSR-14 Events

### Event Overview

| Event                                                                                                                       | When                                        | Purpose                                     |
|-----------------------------------------------------------------------------------------------------------------------------|---------------------------------------------|---------------------------------------------|
| [`FormRuntimeCreationEvent`](../Classes/Form/FormRuntimeCreationEvent.php)                                                 | After runtime created                       | Customize runtime (e.g. modify form models) |
| [`BeforeFormRenderEvent`](../Classes/Form/Rendering/BeforeFormRenderEvent.php)                                             | Before template render                      | Add view variables                          |
| [`ValueValidationEvent`](../Classes/Form/Validation/ValueValidationEvent.php)                                              | On field validation                         | Add validators / set validation result      |
| [`ValueProcessingEvent`](../Classes/Form/Processing/ValueProcessingEvent.php)                                              | After validation, before module execution   | Transform values                            |
| [`ValueSerializationEvent`](../Classes/Form/Serialization/ValueSerializationEvent.php)                                     | Before session storage                      | Serialize complex values                    |
| [`FieldConditionResolutionEvent`](../Classes/Form/Condition/FieldConditionResolutionEvent.php)                             | Evaluating field conditions                 | Override condition result                   |
| [`ModuleConditionResolutionEvent`](../Classes/Form/Module/ModuleConditionResolutionEvent.php)                              | Deciding whether a module is wired up at all | Veto a module regardless of its `condition` expression |
| [`FormFinishEvent`](../Classes/Form/FormFinishEvent.php)                                                                   | Form finish (all built-in modules react here) | Set a response, add finished template variables, stop remaining modules |
| [`SpamAnalysisEvent`](../Classes/Form/SpamProtection/SpamAnalysisEvent.php)                                                | Before validation                           | Add spam detection                          |
| [`ExpressionResolverCreationEvent`](../Classes/Form/Condition/ExpressionResolverCreationEvent.php)                         | Expression engine setup                     | Customize expresssion resolver variables    |

[Listening to events](https://docs.typo3.org/permalink/t3coreapi:extension-development-event-listener)

---

## 🔗 Next Steps

- [Field Reference](FieldReference.md) - All field types and properties
- [Modules Reference](Modules.md) - All modules and their settings
- [Conditions](Conditions.md) - Display condition syntax
