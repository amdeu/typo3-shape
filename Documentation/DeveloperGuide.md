# Developer Guide

Guide for extending Shape with custom validators, finishers, and event listeners.

## 🎯 Event System

Shape provides PSR-14 events for extension without modifying core code.

### Registering Event Listeners

`Configuration/Services.yaml`:

```yaml
services:
  MyVendor\MyExt\EventListener\CustomValidationListener:
    tags:
      - name: event.listener
        identifier: 'my-ext/custom-validation'
        event: Amdeu\Shape\Form\Validation\ValueValidationEvent
```

## ✅ Custom Validators

### Creating a Validator

Extend `TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator`:

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

### Applying via Event

Listen to `ValueValidationEvent`:

```php
<?php
namespace MyVendor\MyExt\EventListener;

use Amdeu\Shape\Form\Validation\ValueValidationEvent;
use MyVendor\MyExt\Validation\Validator\PostalCodeValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CustomValidationListener
{
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
}
```

> **📌 Note:** Validators execute in conjunction (AND logic). All must pass.

## 🔄 Value Processing

Transform values after validation with `ValueProcessingEvent`.

```php
<?php
namespace MyVendor\MyExt\EventListener;

use Amdeu\Shape\Form\Processing\ValueProcessingEvent;

final class ValueProcessingListener
{
    public function __invoke(ValueProcessingEvent $event): void
    {
        // Normalize phone numbers
        if ($event->field->getType() === 'tel') {
            $event->value = preg_replace('/[^0-9]/', '', $event->value);
        }

        // Convert to cents
        if ($event->field->getName() === 'amount') {
            $event->value = (int)($event->value * 100);
        }
    }
}
```

## 💾 Value Serialization

Handle session storage for complex values with `ValueSerializationEvent`.

```php
<?php
namespace MyVendor\MyExt\EventListener;

use Amdeu\Shape\Form\Serialization\ValueSerializationEvent;

final class ValueSerializationListener
{
    public function __invoke(ValueSerializationEvent $event): void
    {
        if ($event->field->getType() === 'custom_complex') {
            $event->serializedValue = json_encode($event->value);
            $event->stopPropagation();
        }
    }
}
```

## 📤 Custom Finishers

### Creating a Finisher

Extend `Amdeu\Shape\Form\Finisher\AbstractFinisher`:

```php
<?php
namespace MyVendor\MyExt\Form\Finisher;

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

### Registering a Finisher

**1. Add to TCA**

`Configuration/TCA/Overrides/tx_shape_finisher.php`:

```php
<?php
defined('TYPO3') or die();

// Add to type dropdown
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
    'tx_shape_finisher',
    'type',
    [
        'label' => 'API Integration',
        'value' => 'api',
        'icon' => 'actions-cloud-upload',
    ]
);

// Configure FlexForm for settings
$GLOBALS['TCA']['tx_shape_finisher']['types']['api'] = [
    'showitem' => 'type, name, settings',
    'columnsOverrides' => [
        'settings' => [
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:my_ext/Configuration/FlexForms/Finisher/Api.xml',
                ],
            ],
        ],
    ],
];
```

**2. Register Service**

`Configuration/Services.yaml`:

```yaml
services:
  MyVendor\MyExt\Form\Finisher\ApiFinisher:
    public: true
    shared: false
```

**3. Hook Creation**

```php
<?php
namespace MyVendor\MyExt\EventListener;

use Amdeu\Shape\Form\Finisher\BeforeFinisherCreationEvent;
use MyVendor\MyExt\Form\Finisher\ApiFinisher;

final class FinisherCreationListener
{
    public function __invoke(BeforeFinisherCreationEvent $event): void
    {
        if ($event->finisherConfiguration->getType() === 'api') {
            $event->finisherClass = ApiFinisher::class;
        }
    }
}
```

**4. Create FlexForm**

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

## 🎭 Display Conditions

Add custom variables to condition expressions.

```php
<?php
namespace MyVendor\MyExt\EventListener;

use Amdeu\Shape\Form\Condition\FieldConditionResolutionEvent;

final class CustomConditionListener
{
    public function __invoke(FieldConditionResolutionEvent $event): void
    {
        $resolver = $event->resolver;

        // Add custom variables
        $resolver->setVariable('currentHour', (int)date('H'));

        // Add user data
        if ($GLOBALS['TSFE']->fe_user->user) {
            $resolver->setVariable('isLoggedIn', true);
        }
    }
}
```

Use in conditions:
```
currentHour >= 9 && currentHour <= 17
isLoggedIn == true
```

## 🛡️ Spam Protection

Add custom spam detection via `SpamAnalysisEvent`.

```php
<?php
namespace MyVendor\MyExt\EventListener;

use Amdeu\Shape\Form\SpamProtection\SpamAnalysisEvent;

final class SpamProtectionListener
{
    public function __invoke(SpamAnalysisEvent $event): void
    {
        $formValues = $event->runtime->session->values;

        // Check honeypot
        if (!empty($formValues['website'])) {
            $event->markAsSpam('Honeypot filled');
            return;
        }

        // Check submission speed
        $sessionStart = $event->runtime->session->createdAt;
        if ((time() - $sessionStart) < 3) {
            $event->markAsSpam('Too fast');
            return;
        }

        // Check spam keywords
        $message = $formValues['message'] ?? '';
        if (stripos($message, 'viagra') !== false) {
            $event->markAsSpam('Spam keyword');
        }
    }
}
```

## 📊 Available Events

| Event                                                                    | Purpose |
|--------------------------------------------------------------------------|---------|
| [FormRuntimeCreationEvent](../Classes/Form/FormRuntimeCreationEvent.php) | Customize runtime after creation | 
| `BeforeFormRenderEvent`                                                  | Modify view variables |
| `ValueValidationEvent`                                                   | Add custom validators |
| `ValueProcessingEvent`                                                   | Transform values |
| `ValueSerializationEvent`                                                | Serialize for session |
| `FieldConditionResolutionEvent`                                          | Add condition variables |
| `FinisherConditionResolutionEvent`                                       | Add finisher condition variables |
| `BeforeFinisherCreationEvent`                                            | Override finisher class |
| `SpamAnalysisEvent`                                                      | Add spam detection |
| `ExpressionResolverCreationEvent`                                        | Customize expression engine |

See the `Classes/Form/` directory for event class locations and properties.

## 🔍 Accessing Form Data

In event listeners and finishers:

```php
// Field information
$field->getName()
$field->getType()
$field->getProperty('custom_property')

// Form values
$runtime->session->values
$runtime->session->values['field-name']

// Form structure
$runtime->form->getName()
$runtime->form->getPages()
$runtime->currentPageIndex

// Request
$runtime->request->getQueryParams()
$runtime->request->getParsedBody()
```

## 🔗 Next Steps

- [Architecture](Architecture.md) - Technical deep-dive
- [Integrator Guide](IntegratorGuide.md) - Template customization
- [Field Reference](FieldReference.md) - Field properties
