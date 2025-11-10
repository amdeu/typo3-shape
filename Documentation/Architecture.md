# Architecture

Technical overview of Shape's design and implementation.

## 🏗️ Overview

Shape follows an event-driven MVC architecture with database-driven forms.

```
Controller → FormRuntime → Processing Pipeline → Events → Domain Models → Database
```

## 📊 Database Schema

### Core Tables

- **tx_shape_form** - Form containers
- **tx_shape_form_page** - Pages (multi-step)
- **tx_shape_field** - Form fields
- **tx_shape_field_option** - Options for select/radio/checkbox
- **tx_shape_finisher** - Post-submission actions
- **tx_shape_form_submission** - Submitted data
- **tx_shape_email_consent** - Double opt-in tracking

### Relationships

```
Form (1:n) Pages (1:n) Fields (1:n) Options
Form (1:n) Finishers
Form (1:n) Submissions
Form (1:n) Email Consents
Field (1:n) Fields (nested, for repeatable-container)
```

## 🔄 Request Flow

### Initial Load

```
Request → FormController::indexAction()
       → FormRuntimeFactory::createFromRequest()
       → Create/Restore FormSession
       → Load Form from database
       → Dispatch FormRuntimeCreationEvent
       → Dispatch BeforeFormRenderEvent
       → Render form template
```

### Multi-Step Navigation

```
POST → FormController::handleFormSubmission()
    → FormRuntime::validatePage()
       ├→ For each field: ValueValidationEvent
       ├→ Listeners add validators
       └→ Execute validators
    → If valid: Store in session, advance page
    → If invalid: Re-render with errors
```

### Final Submission

```
POST (last page) → FormRuntime::validateForm()
                 → FormRuntime::processValues()
                    └→ ValueProcessingEvent for each field
                 → FormRuntime::serializeValues()
                    └→ ValueSerializationEvent for complex fields
                 → FormRuntime::executeFinishers()
                    ├→ BeforeFinisherCreationEvent
                    ├→ Create finisher instance
                    ├→ Check conditions
                    └→ Execute
                 → Redirect or show success
```

### Email Consent Flow

```
EmailConsentFinisher → Create consent record
                    → Serialize FormSession (HMAC-signed)
                    → Send verification email
                    → Cancel subsequent finishers (optional)

User clicks link → ConsentController::approveAction()
                → Verify hash
                → Deserialize session
                → Recreate FormRuntime
                → Re-execute finishers (with consent context)
```

## 🔌 Event System

### Event Dispatch Points

| Event | When | Purpose |
|-------|------|---------|
| `FormRuntimeCreationEvent` | After runtime created | Customize runtime |
| `BeforeFormRenderEvent` | Before template render | Add view variables |
| `ValueValidationEvent` | Before field validation | Add validators |
| `ValueProcessingEvent` | After validation | Transform values |
| `ValueSerializationEvent` | Before session storage | Serialize complex values |
| `FieldConditionResolutionEvent` | Evaluating conditions | Add variables |
| `FinisherConditionResolutionEvent` | Finisher conditions | Add variables |
| `BeforeFinisherCreationEvent` | Before finisher instantiation | Override class |
| `SpamAnalysisEvent` | Before processing | Spam detection |

All events dispatched via PSR-14 `EventDispatcher`.

## 🔍 Validation Pipeline

```
FieldValueValidator::validate($field, $value)
  ↓
Create ConjunctionValidator
  ↓
Dispatch ValueValidationEvent
  ├→ ValueValidationConfigurator adds validators based on field properties:
  │  ├→ RequiredValidator (if required)
  │  ├→ HTMLPatternValidator (if pattern set)
  │  ├→ MaxLengthValidator (if maxlength set)
  │  ├→ MultipleOfInRangeValidator (for number/range)
  │  ├→ HTMLAcceptValidator (for file)
  │  └→ EmailValidator, UrlValidator, etc.
  └→ Custom listeners add additional validators
  ↓
Execute all validators (AND logic)
  ↓
Return Result
```

### HTML5-Compliant Validators

- **HTMLPatternValidator** - Replicates `pattern` attribute with anchors
- **HTMLAcceptValidator** - Validates file types like browsers do
- **MultipleOfInRangeValidator** - Replicates `step` validation with offset

## 💾 Session Management

### Storage

Sessions stored in TYPO3 frontend session:
```php
$GLOBALS['TSFE']->fe_user->getKey('ses', 'tx_shape_' . $formUid)
```

### Serialization

HMAC-signed for security:
```php
[
    'data' => base64_encode(serialize($sessionData)),
    'hmac' => hash_hmac('sha256', $data, $encryptionKey)
]
```

Prevents tampering and enables secure consent restoration.

## 🎨 Template System

### Structure

- **Layouts** - Page wrapper
- **Templates** - Main views (Form, Finished, ConsentVerification)
- **Partials** - Reusable components (Field, Navigation, etc.)

### Variables

Template variables come from:
- `FormController` (assigns to view)
- `FormRuntime` (context data)
- `BeforeFormRenderEvent` (custom additions)

### ViewHelpers

- `shape:field.attributes` - Generate HTML attributes from field properties
- `shape:trimExplode` - Split and trim strings

## 🔧 Extension Points

### For Integrators

1. **TCA** - Add field properties
2. **Templates** - Override Fluid templates
3. **TypoScript** - Minimal config (paths, PIDs)

### For Developers

1. **Events** - Listen to 10+ PSR-14 events
2. **Validators** - Extend `AbstractValidator`
3. **Finishers** - Extend `AbstractFinisher`
4. **ViewHelpers** - Create custom helpers

## 🔐 Security

- **CSRF** - TYPO3's built-in protection
- **Session** - HMAC-signed, constant-time verification
- **Input** - All values validated
- **SQL** - Doctrine DBAL with parameter binding
- **XSS** - Fluid escaping by default
- **Files** - MIME type validation, size limits

## 📦 Key Classes

### Controllers

- `FormController` - Form rendering, submission, navigation
- `ConsentController` - Email consent verification

### Runtime

- `FormRuntime` - Central context with form state
- `FormSession` - Session data with serialization
- `FormRuntimeFactory` - Creates runtime instances

### Models

- `FormRecord` - Form container
- `FormPageRecord` - Page with fields
- `FieldRecord` - Field with properties
- `FinisherConfiguration` - Finisher settings

### Processing

- `FieldValueValidator` - Validation orchestration
- `FieldValueProcessor` - Value transformation
- `FieldValueSerializer` - Session serialization

### Finishers

- `AbstractFinisher` - Base class
- `SendEmailFinisher`, `SaveSubmissionFinisher`, etc.

All classes in `Classes/Form/` directory.

## 📚 Further Reading

- [Developer Guide](DeveloperGuide.md) - Extend with custom code
- [Integrator Guide](IntegratorGuide.md) - Customize templates
- Source code in `Classes/` directory
