# Display Conditions

Show or hide fields and pages based on user input or context.

## Table of Contents

- [Server-Side vs Client-Side](#server-side-vs-client-side)
- [Basic Syntax](#basic-syntax)
- [Server-Side Only](#server-side-only)
- [Client-Side Only](#client-side-only)
- [Repeatable Field Groups](#repeatable-field-groups)
- [Conditional Pages](#conditional-pages)
- [Common Examples](#common-examples)
- [Custom Variables](#custom-variables)

## Server-Side vs Client-Side

**Server-Side (`display_condition`):**
- Evaluated before rendering page
- Symfony Expression Language syntax ([docs](https://symfony.com/doc/current/components/expression_language.html))
- Access to context (request, frontend user, site)
- Use for and multi-page forms or forms loaded uncached via lazy loading with different variants

**Client-Side (`js_display_condition`):**
- Interpreted in browser with JavaScript
- Subscript syntax ([docs](https://github.com/dy/subscript))
- Access only to form values
- Use for conditions based on fields on the same page for instant feedback

## Basic Syntax

### Field Has Value
```
value("field-name")
```

### Field Equals Value
```
value("country") == "US"
value("age") >= 18
value("plan") != "basic"
```

### Multiple Conditions
```
value("country") == "US" && value("age") >= 18
value("country") == "US" || value("country") == "CA"
```

### Numeric Comparisons
```
value("quantity") < 10
value("price") > 100
value("age") >= 18 && value("age") <= 65
```

### Array Contains
```
value("country") in ["US", "CA", "MX"]
```

## Server-Side Only

Server-side conditions have access to additional context:

### Variables Available

| Variable              | Type                         | Description                      |
|-----------------------|------------------------------|----------------------------------|
| `value("field-name")` | Function                     | Get field value                  |
| `request`             | `RequestWrapper`             | Current request                  |
| `site`                | `SiteInterface`              | Current site                     |
| `frontendUser`        | `FrontendUserAuthentication` | Logged-in user                   |
| `formRuntime`         | `FormRuntime`                | Shape runtime                    |
| `formValues`          | `array`                      | All form values                  |
| `stepType`            | `string`                     | Type of current form page record |
### Request Parameters
```
traverse(request.getQueryParams(), "utm_source") == "newsletter"
traverse(request.getQueryParams(), "promo") == "summer2024"
```

### Site
```
site.getIdentifier() == "my-site"
```

### Frontend User
```
frontendUser.isLoggedIn()
frontendUser.user.usergroup in [1, 2, 3]
```

### Page Context
```
stepType == "form"  // Not on summary page
```

## Client-Side Only

### Subscript Functions

**value()** - Get field value:
```
value("subscribe")
value("country") == "US"
```

**formData()** - Access nested fields (for repeatable containers):
```
formData("[family-members][__INDEX][age]") < 18
formData("[contacts][0][email]")
```

## Repeatable Field Groups

Use `[__INDEX]` placeholder for nested field conditions (client-side):

```
formData("[family-members][__INDEX][age]") < 18
formData("[family-members][__INDEX][relation]") == "Child"
formData("[family-members][__INDEX][name]")
```

Server-side check if fieldset count meets threshold:
```
value("family-members")[4]  // True if 5 or more fieldsets
```

See [Repeatable Container Guide](RepeatableContainer.md) for more details.

## Common Examples

### Show Email if Contact Requested
```
Server: value("contact-me") == "yes"
Client: value("contact-me") == "yes"
```

### Show Company Fields for Business Users
```
Server: value("user-type") == "business"
Client: value("user-type") == "business"
```

### Show State Field Only for US
```
Server: value("country") == "US"
Client: value("country") == "US"
```

### Age-Restricted Field
```
Server: value("age") >= 18
Client: value("age") >= 18"
```

### Show Based on URL Parameter (Server Only)
```
Server: traverse(request.getQueryParams(), "promo") == "summer2024"
```

### Show Only for Logged-In Users (Server Only)
```
Server: frontendUser.isLoggedIn()
```

## Custom Variables

Developers can add custom variables via events. See [Customization Guide](CustomizationGuide.md#event-overview).

## 🔗 Related

- [Repeatable Container](RepeatableContainer.md) - Using `[__INDEX]` placeholder
- [Editor Guide](EditorGuide.md) - Building forms with conditions
- [Customization Guide](CustomizationGuide.md#event-overview) - Custom condition variables
