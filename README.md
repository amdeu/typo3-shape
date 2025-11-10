# shape: TYPO3 Form Extension

Shape is a TYPO3 extension for building and managing web forms using a TCA/database-driven approach. It offers a wide range of field types, validation options, and features like multi-step forms, repeatable fields, display conditions, double opt-in, and more.

## Why another form extension?

Shape aims to be an alternative to ext:form and other form builders by focusing on:

1. **TCA/Database approach** - ext:form yaml configuration is extremely powerful but can be overwhelming. Shape uses TCA and the Core Record system for its form models. Integrators only need to manipulate the well-known TCA to add custom properties and types or adapt the builder interface. No PHP class extension or configuration files needed.
   Localization behavior configurable via TCA.
3. **Powerful editor experience** - Minimal TypoScript configuration by design. Almost all included features are configurable via the backend interface. Integrators and knowledgeable editors can build complex forms without developer assistance.
3. **Feature completeness** - Included: multi-step forms, display conditions (client and server-side), rich-text labels, double opt-in, repeatable field groups, smart server-side validation, all standard HTML field types, stylable validation messages, flexible finishers, spam protection and more.
2. **HTML5 standards** - 
Aims to include all types of HTML5 form controls and their form-related attributes. Field properties mirror HTML validation attributes (`required`, `pattern`, `maxlength`, `accept`, `min`, `step` etc.) enabling native browser validation with automatic server-side replication.

4. **Easy extensibility** 
    - **Templates** - Fluid template overrides
   - **TCA** - TCA as single source of truth for form elements
   - **Events** - Extend or override core behavior (runtime creation, rendering, validation, value serialization and processing, finisher execution, condition resolution, etc.) with event listeners
   - **Custom Finishers** - Extend AbstractFinisher to implement custom post-submission actions

## Key Features

Configure via backend form builder:
- **30+ field types** - All standard HTML5 types plus advanced fields
- **Multi-step forms** - Navigate between pages with session state
- **Modular finishers** - Freely combine finishers like send email, redirect, save submission, save to database etc.
- **Conditions** - Conditional field display (client and server-side), conditional finisher execution
- **Double opt-in** - Email verification flow with finisher re-execution
- **Repeatable fields** - Dynamically add fieldsets (e.g., multiple addresses)
- **HTML5 validation** - Server validators replicate browser behavior

Extend base functionality:
- **Record-based form model** - Easy customization and automatic data processing of form elements
- **Event-driven architecture** - Extend (and disable) core processes via event listeners
- **Planned: Form Presets** - Save form structures as reusable templates to easily reuse and share across projects

## Requirements

- TYPO3 v13.4 or higher
- PHP 8.2 or higher
- Composer


## Installation

```bash
composer require amdeu/typo3-shape
```

Update database schema via **Admin Tools → Maintenance → Analyze Database Structure** or CLI:

```bash
vendor/bin/typo3 database:updateschema
```

See [Getting Started](Documentation/GettingStarted.md) for details.

## Documentation

### Getting Started
- **[Getting Started](Documentation/GettingStarted.md)** - Installation and first form
- **[Editor Guide](Documentation/EditorGuide.md)** - Building forms in the backend

### Reference
- **[Field Reference](Documentation/FieldReference.md)** - Field types, properties, and validation
- **[Finishers Reference](Documentation/Finishers.md)** - All finishers and their settings

### Feature Guides
- **[Repeatable Field Groups](Documentation/RepeatableContainer.md)** - Dynamic fieldsets
- **[Display Conditions](Documentation/Conditions.md)** - Show/hide fields based on values

### Advanced
- **[Customization Guide](Documentation/CustomizationGuide.md)** - TypoScript, templates, events, and custom finishers

## License

MIT License - see [LICENSE](LICENSE)
