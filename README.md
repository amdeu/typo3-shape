# shape: TYPO3 Form Extension

Shape is a TYPO3 extension for building and managing web forms using a TCA/Record-based approach. It offers a wide range of field types, validation options, and features like multi-step forms, repeatable fields, display conditions, double opt-in, and more.

## Motivation

Apart from it being a fun project, shape offers a slightly different approach to form building compared to ext:form:

### 🗄️ TCA/Record-Based Architecture

Form models are TYPO3 [Records](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Database/DatabaseRecords/RecordObjects.html#record_objects) configured via TCA.

**Benefits:**
- Modify TCA to add custom properties, change field types, and adapt the backend interface
- TCA defines both the editor UI and the data model in one place, no PHP class extension or configuration files needed
- Records automatically resolve relations (file references, inline records, foreign references)
- Full TCA field type ecosystem available (select, group, inline, file, etc.)
- Localization behavior configurable via TCA (language synchronization)
- Integrators are familiar with TCA, ext:form's YAML configuration definitions can be overwhelming

**Trade-off:** Less flexible and powerful than file-based systems like ext:form (no variants, no YAML-level configuration). Better suited for integrators who prefer working with TCA/Records over configuration files.

> **Note:** The architecture supports alternative form model implementations


### ✨ Editor-Focused Interface

Minimal TypoScript by design. Almost all features configurable via backend by default. Knowledgeable editors can build complex forms independently.

### 🌐 HTML5 Form Standards

Field types represent standard HTML form controls and their attributes. Configure native browser validation (via `required`, `pattern`, `maxlength`, `accept`, `min`, `step`, etc.) - the same properties are automatically enforced server-side.

## Features

### 🏗️ Form Building
- **📝 30+ field types** - All standard HTML5 types plus advanced fields
- **📄 Multi-step forms** - Navigate between pages with session state management
- **🔁 Repeatable field groups** - Dynamically add/remove fieldsets (e.g., multiple addresses, contacts)
- **🎨 Appearance options** - Rich-text labels, layouts, field widths, CSS classes, stylable HTML5 validation, custom error messages

[//]: # (- **📦 Form Presets** - Reusable form blueprints for quick setup of common scenarios &#40;contact forms, signup forms, etc.&#41; &#40;WIP&#41;)

### 🔍 Validation & Conditions
- **📋 Field-level validation** - Define dual client- and server-side validation with field properties 
- **✅ HTML5 validation** - Server validators automatically replicate browser behavior
- **🔬 Custom validators** - Add validators via PSR-14 events
- **👁️ Display conditions** - Show/hide fields based on values or context (client and server-side)

### 🏁 Modules
- **📧 Send emails** - Template-based emails
- **✉️ Double Opt-in** - Email verification flow with module re-execution
- **💾 Save submissions** - Store form data in database with JSON values
- **🗄️ Database integration** - Insert/update custom tables
- **➡️ Redirects** - Dynamic redirects after submission
- **📜 Show content** - Display content elements after submission
- **🧩 Modular actions** - Freely combine and configure modules

### 🛡️ Security & Spam Protection
- **🔒 HMAC-signed sessions** - Secure session persistence between form pages
- **🍯 Honeypot** - Invisible field spam trap
- **👆 Focus Pass** - JavaScript-based bot detection via focus events
- **🤖 Google reCAPTCHA** - Bot protection via reCAPTCHA (WIP)
- **🔌 Custom spam detection** - Extend via SpamAnalysisEvent

### 🔧 Extensibility

Beyond TCA customization, Shape provides standard extension points:

- **📄 Fluid templates** - Override any template, partial, or layout
- **📡 PSR-14 events** - 10+ events for validation, processing, rendering, module execution, etc.
- **🏁 Custom modules** - Extend AbstractModule to implement post-submission actions
- **🛠️ Custom implementations** - Swap out core services via DI configuration

## 📋 Requirements

- TYPO3 v13.4 or higher
- PHP 8.2 or higher
- Composer


## 📦 Installation

> **⚠️ Beta Status:** This extension is in beta and does not have a testing suite yet. While it is being used in production, please test thoroughly in your specific environment before deploying to production sites.

```bash
composer require amdeu/typo3-shape
```

Update database schema via **Admin Tools → Maintenance → Analyze Database Structure** or CLI:

```bash
vendor/bin/typo3 database:updateschema
```

See [Getting Started](Documentation/GettingStarted.md) for details.

## 📚 Documentation

### Getting Started
- **[Getting Started](Documentation/GettingStarted.md)** - Installation and first form
- **[Editor Guide](Documentation/EditorGuide.md)** - Building forms in the backend

### Reference
- **[Field Reference](Documentation/FieldReference.md)** - Field types, properties, and validation
- **[Modules Reference](Documentation/Modules.md)** - All modules and their settings

### Feature Guides
- **[Repeatable Field Groups](Documentation/RepeatableContainer.md)** - Dynamic fieldsets
- **[Display Conditions](Documentation/Conditions.md)** - Show/hide fields based on values

### Advanced
- **[Customization Guide](Documentation/CustomizationGuide.md)** - TypoScript, templates, events, and custom modules

## License

MIT License - see [LICENSE](LICENSE)
