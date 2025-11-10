# Integrator Guide

Guide for customizing Shape templates and extending TCA.

## 🎨 Template Overrides

### TypoScript Configuration

```typoscript
plugin.tx_shape {
    view {
        templateRootPaths.10 = EXT:my_site/Resources/Private/Shape/Templates/
        partialRootPaths.10 = EXT:my_site/Resources/Private/Shape/Partials/
        layoutRootPaths.10 = EXT:my_site/Resources/Private/Shape/Layouts/
    }
}
```

Higher numbers (10, 20) override lower numbers.

### Template Structure

```
Resources/Private/
├── Layouts/
│   └── Default.html
├── Templates/
│   ├── Form.html
│   ├── Finished.html
│   ├── ConsentVerification.html
│   └── Finisher/
│       ├── SendEmail/Default.html
│       └── EmailConsent.html
└── Partials/
    ├── Form.html
    ├── FormPage.html
    ├── FieldList.html
    ├── Field.html
    ├── Navigation.html
    ├── Control/
    │   ├── Label.html
    │   ├── Description.html
    │   └── Errors.html
    └── Field/
        ├── Text.html
        ├── Email.html
        ├── Select.html
        └── ... (all field types)
```

> **💡 Tip:** Only override what you need. TYPO3 falls back to Shape's originals.

### Template Variables

**Main Template (`Form.html`):**
- `{form}` - FormRecord
- `{runtime}` - FormRuntime
- `{currentPage}` - Current FormPageRecord
- `{pages}` - All pages
- `{isLastPage}` - Boolean
- `{isFirstPage}` - Boolean

**Field Partials:**
- `{field}` - FieldRecord
- `{field.name}` - Field identifier
- `{field.type}` - Field type
- `{field.label}` - Label text
- `{field.value}` - Current value
- Access any TCA property: `{field.placeholder}`, `{field.required}`, etc.

### ViewHelpers

**`shape:field.attributes`** - Generates HTML attributes:
```html
<f:variable name="attrs" value="{field -> shape:field.attributes()}"/>
<f:form.textfield additionalAttributes="{attrs}" />
```

**`shape:trimExplode`** - Split and trim lines:
```html
<f:for each="{field.datalist -> shape:trimExplode()}" as="item">
    <option value="{item}"/>
</f:for>
```

## 🔧 TCA Extension

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

### Using Custom Properties

In templates:
```html
<f:if condition="{field.data_tracking}">
    <input data-tracking="{field.data_tracking}" />
</f:if>
```

> **📌 Note:** Custom properties are automatically accessible via `{field.propertyName}`. No PHP class modifications needed - TCA is the single source of truth.

## 🎭 Styling

### CSS Class Structure

Shape uses BEM with block class `yf` (customizable):

```css
.yf { }                        /* Form */
.yf__field { }                 /* Field container */
.yf__text-field { }            /* Text field container */
.yf__label { }                 /* Label */
.yf__control { }               /* Input */
.yf__text-control { }          /* Text input */
.yf__errors { }                /* Error container */
.yf__navigation { }            /* Navigation */
.yf__button { }                /* Button */

/* Modifiers */
.-width-half { }               /* Half width */
.-layout-inline { }            /* Inline layout */
.--hidden { }                  /* Hidden by condition */
```

### Custom CSS Classes

Fields can have custom classes:
```
Field CSS Class: highlight premium-field
```

Results in:
```html
<div class="yf__field yf__text-field highlight premium-field">
```

### Width System

CSS custom properties:
```css
.yf__field {
    width: var(--yf-field-width, 100%);
}

.-width-half { --yf-field-width: 50%; }
.-width-third { --yf-field-width: 33.33%; }
```

## 🌐 Multi-Language

Shape uses TYPO3's standard localization system.

**Translatable:**
- Form, page, field, option records
- Labels, descriptions, placeholders
- Validation messages

**Not translatable:**
- Field names (identifiers)
- Field types
- Validation rules (pattern, min, max)

Translate via **Page → Localize** or List module.

## 📝 Custom Examples

### Example: Custom Field Wrapper

Override `Partials/Field.html`:

```html
<div class="yf__field yf__{field.type}-field"
     data-field="{field.name}"
     data-type="{field.type}">

    {content -> f:format.raw()}

    <f:render partial="Control/Errors" arguments="{_all}"/>
</div>
```

### Example: Progress Bar

Override `Partials/Form.html` to add progress:

```html
<f:if condition="{pages -> f:count()} > 1">
    <div class="progress-bar">
        <f:variable name="progress" value="{(runtime.currentPageIndex + 1) / (pages -> f:count()) * 100}" />
        <div class="progress-fill" style="width: {progress}%"></div>
    </div>
</f:if>

<f:render partial="FormPage" arguments="{_all}" />
```

### Example: Custom Email Template

`Templates/Finisher/SendEmail/Custom.html`:

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
$GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['shape']['finishers']['sendEmail']['templates']['custom'] = [
    'label' => 'Custom Email Layout',
    'template' => 'EXT:my_site/Resources/Private/Templates/Email/Custom.html',
];
```

## ⚙️ TypoScript Settings

Minimal configuration by design:

```typoscript
plugin.tx_shape {
    settings {
        sessionLifetime = 3600
        storagePid = 123
    }
}
```

## 🔗 Next Steps

- [Developer Guide](DeveloperGuide.md) - Custom validators and finishers
- [Field Reference](FieldReference.md) - All field properties
- [Editor Guide](EditorGuide.md) - Using the fields
