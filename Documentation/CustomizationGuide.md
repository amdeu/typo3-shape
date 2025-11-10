# Customization Guide

Guide for customizing Shape through templates, TCA, TypoScript, event listeners and finishers.

## Table of Contents

- [TypoScript Configuration](#typoscript-configuration)
- [Template Customization](#template-customization)
- [Template Variables](#template-variables)
- [ViewHelpers](#viewhelpers)
- [TCA Extension](#tca-extension)
- [Database Tables](#database-tables)
- [PSR-14 Events](#psr-14-events)
- [Customization Examples](#customization-examples)

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
│   ├── ConsentVerification.html                   # Email consent validation page
│   └── Finisher/
│       ├── SendEmail/Default.html                 # Default email template
│       ├── EmailConsent.html                      # Consent verification email
│       └── ShowContentElements.html               # Content elements display
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
| `{messages}`              | `array`                     | Form-level messages (errors, warnings, notices)                  |
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

> **📌 Note:** Custom properties are automatically accessible via `{field.property_name}`. No PHP class modifications needed - TCA is the single source of truth.

---

## Database Tables

### Core Tables

| Table                         | Description                          |
|-------------------------------|--------------------------------------|
| `tx_shape_form`               | Form containers                      |
| `tx_shape_form_page`          | Pages (multi-step)                   |
| `tx_shape_field`              | Form fields                          |
| `tx_shape_field_option`       | Options for select/radio/checkbox    |
| `tx_shape_finisher`           | Post-submission actions              |
| `tx_shape_form_submission`    | Submitted data                       |
| `tx_shape_email_consent`      | Double opt-in tracking               |

### Relationships

```
Form (1:n) Pages (1:n) Fields (1:n) Options
Form (1:n) Finishers
Form (1:n) Submissions
Form (1:n) Email Consents
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
| [`ValueProcessingEvent`](../Classes/Form/Processing/ValueProcessingEvent.php)                                              | After validation, before finisher execution | Transform values                            |
| [`ValueSerializationEvent`](../Classes/Form/Serialization/ValueSerializationEvent.php)                                     | Before session storage                      | Serialize complex values                    |
| [`FieldConditionResolutionEvent`](../Classes/Form/Condition/FieldConditionResolutionEvent.php)                             | Evaluating field conditions                 | Override condition result                   |
| [`FinisherConditionResolutionEvent`](../Classes/Form/Condition/FinisherConditionResolutionEvent.php)                       | Evaluating finisher conditions              | Override condition result                   |
| [`BeforeFinisherCreationEvent`](../Classes/Form/Finisher/BeforeFinisherCreationEvent.php)                                  | Before finisher instantiation               | Modify finisher class and settings          |
| [`SpamAnalysisEvent`](../Classes/Form/SpamProtection/SpamAnalysisEvent.php)                                                | Before validation                           | Add spam detection                          |
| [`ExpressionResolverCreationEvent`](../Classes/Form/Condition/ExpressionResolverCreationEvent.php)                         | Expression engine setup                     | Customize expression engine                 |

[Listening to events](https://docs.typo3.org/permalink/t3coreapi:extension-development-event-listener)

---

## Customization Examples

### Custom Validator

**1. Create Validator**

`Classes/Validation/Validator/PostalCodeValidator.php`:

```php
<?php
namespace MyVendor\MyExt\Validation\Validator;

use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

class PostalCodeValidator extends AbstractValidator
{
    protected function isValid(mixed $value): void
    {
        if (!is_string($value)) {
            return;
        }

        if (!preg_match('/^\d{5}$/', $value)) {
            $this->addError(
                'Please enter a valid postal code (5 digits)',
                1234567890
            );
        }
    }
}
```

**2. Apply via Event Listener**

`Classes/EventListener/CustomValidationListener.php`:

```php
<?php
namespace MyVendor\MyExt\Shape;

use TYPO3\CMS\Core\Attribute\AsEventListener; 
use Amdeu\Shape\Form\Validation\ValueValidationEvent;
use MyVendor\MyExt\Validation\Validator\PostalCodeValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CustomValidationListener
{
    #[AsEventListener]
    public function __invoke(ValueValidationEvent $event): void
    {
        $field = $event->field;

        // Apply to specific field
        if ($field->getName() === 'postal-code') {
            $validator = GeneralUtility::makeInstance(PostalCodeValidator::class);
            $event->addValidator($validator);
        }

        // Or based on custom TCA property
        if ($field->getProperty('validate_postal')) {
            $validator = GeneralUtility::makeInstance(PostalCodeValidator::class);
            $event->addValidator($validator);
        }        
    }
    
    // or completely custom validation logic
     #[AsEventListener(before: 'Amdeu\Shape\Form\Validation\ValueValidationConfigurator']
    public function __invoke(ValueValidationEvent $event): void
    {
        if ($event->field->getType() !== 'custom-field-type') {
            return;
        }
        $event->result = $this->customFieldValidation($event); 
    }
}
```

> **📌 Note:** Validators execute in conjunction (AND logic). All must pass.

---

### Value Processing

Transform values after validation.

`Classes/EventListener/ValueProcessingListener.php`:

```php
<?php
namespace MyVendor\MyExt\Shape;

use Amdeu\Shape\Form\Processing\ValueProcessingEvent;

final class ValueProcessingListener
{
    public function __invoke(ValueProcessingEvent $event): void
    {
        // Convert currency to cents
        if ($event->field->getName() === 'amount') {
            $event->value = (int)($event->value * 100);
        }
    }
}
```

---

### Value Serialization

Handle session storage for complex values.

`Classes/EventListener/ValueSerializationListener.php`:

```php
<?php
namespace MyVendor\MyExt\Shape;

use Amdeu\Shape\Form\Serialization\ValueSerializationEvent;

final class ValueSerializationListener
{
    public function __invoke(ValueSerializationEvent $event): void
    {
        if ($event->field->getType() === 'custom_complex') {
            $event->serializedValue = json_encode($event->value);
        }
    }
}
```

---

### Custom Display Condition Variables

Add custom variables to condition expressions.

`Classes/EventListener/CustomConditionListener.php`:

```php
<?php
namespace MyVendor\MyExt\Shape;

use Amdeu\Shape\Form\Condition\FieldConditionResolutionEvent;

final class CustomConditionListener
{
    public function __invoke(FieldConditionResolutionEvent $event): void
    {
       $event->result = $this->customConditionEvaluation($event);
    }
}
```

---

### Spam Protection

Add custom spam detection.

`Classes/EventListener/SpamProtectionListener.php`:

```php
<?php
namespace MyVendor\MyExt\Shape;

use Amdeu\Shape\Form\SpamProtection\SpamAnalysisEvent;

final class SpamProtectionListener
{
    public function __invoke(SpamAnalysisEvent $event): void
    {
        $formValues = $event->runtime->session->values;

        // Check spam keywords
        $message = $formValues['message'] ?? '';
        if (stripos($message, 'viagra') !== false) {
            $event->spamReasons['keyword'] = [
                'message' => 'Spam keyword detected',
                'keyword' => 'viagra'
            ]
        }
    }
}
```

---

### Custom Finisher

**1. Create Finisher Class**

`Classes/Form/Finisher/ApiFinisher.php`:

```php
<?php
namespace MyVendor\MyExt\Shape\Finisher;

use Amdeu\Shape\Form\Finisher\AbstractFinisher;

class ApiFinisher extends AbstractFinisher
{
    protected array $settings = [
        'apiUrl' => '',
        'apiKey' => '',
    ];

    public function executeInternal(): void
    {
        $formValues = $this->getFormValues();

        // Access settings
        $url = $this->settings['apiUrl'];
        $key = $this->settings['apiKey'];

        // Parse template variables in settings
        $parsedUrl = $this->parseWithValues($url);

        // Your logic
        $this->sendToApi($formValues, $parsedUrl, $key);

        // Log
        $this->logger->info('API finisher executed', [
            'form' => $this->getForm()->getName(),
        ]);
    }

    private function sendToApi(array $data, string $url, string $key): void
    {
        // API integration
    }
}
```
See [AbstractFinisher](../Classes/Form/Finisher/AbstractFinisher.php):
- `settings`: Finisher settings from FlexForm
- `logger`: PSR-3 logger instance
- `executeInternal()`: Main execution method to override
- `getRuntime()`: Get form runtime
- `getRequest()`: Get current request
- `getPlugin()`: Get plugin record
- `getForm()`: Get form model
- `getFormValues()`: Get all submitted form values
- `getPluginSettings()`: Get merged plugin TypoScript settings
- `getView()`: Get Fluid view instance
- `validate()`: Option for finishers to abort all finisher execution by returning error Result
- `addValidationError()`: validate() helper
- `parseWithValues()`: Parse string and replace `{field-name}` with submitted values
- `getLogContext()`: Common log context array, override to add more context


**2. Add to TCA**

`Configuration/TCA/Overrides/tx_shape_finisher.php`:

```php
<?php
defined('TYPO3') or die();

use MyVendor\MyExt\Shape\Finisher\ApiFinisher;

// Add to type dropdown
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tx_shape_finisher',
    'type',
    [
        'label' => 'API Integration',
        'value' =>  ApiFinisher::class,
        'icon' => 'actions-cloud-upload',
    ]
);

// Configure FlexForm
$GLOBALS['TCA']['tx_shape_finisher']['columns']['settings']['config']['ds'][ApiFinisher::class] =
    'FILE:EXT:my_ext/Configuration/FlexForms/Finisher/Api.xml';
```

**5. Create FlexForm**

`Configuration/FlexForms/Finisher/Api.xml`:

```xml
<?xml version="1.0" encoding="utf-8"?>
<T3DataStructure>
    <sheets>
        <sDEF>
            <ROOT>
                <sheetTitle>Settings</sheetTitle>
                <type>array</type>
                <el>
                    <settings.apiUrl>
                        <label>API URL</label>
                        <config>
                            <type>input</type>
                            <eval>trim,required</eval>
                        </config>
                    </settings.apiUrl>
                    <settings.apiKey>
                        <label>API Key</label>
                        <config>
                            <type>input</type>
                            <eval>trim,required</eval>
                        </config>
                    </settings.apiKey>
                </el>
            </ROOT>
        </sDEF>
    </sheets>
</T3DataStructure>
```

---

## 🔗 Next Steps

- [Field Reference](FieldReference.md) - All field types and properties
- [Finishers Reference](Finishers.md) - All finishers and their settings
- [Conditions](Conditions.md) - Display condition syntax
