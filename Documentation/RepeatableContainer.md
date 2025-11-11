# Repeatable Field Groups

Repeatable Field Groups allow users to dynamically add multiple sets of fields (e.g., addresses, contacts, family members).

## Creating a Repeatable Field Group

1. Create a field with **Type: Repeatable Field Group**
2. Configure minimum/maximum number of fieldsets (optional)
3. Add nested fields in the **Fields** tab

### Example: Contact Persons

**Repeatable Container:**
```
Label: Contact Persons
Type: Repeatable Field Group
Minimum: 1
Maximum: 5
```

**Nested Fields:**
```
Field 1:
  Label: Full Name
  Type: Text
  Required: Yes

Field 2:
  Label: Email Address
  Type: Email
  Required: Yes

Field 3:
  Label: Phone Number
  Type: Telephone

Field 4:
  Label: Role
  Type: Select
  Options: Primary Contact, Secondary Contact, Technical Contact
```

## Frontend Behavior

Users see:
- **Add** button to create fieldsets
- **Remove** button on each fieldset after adding

## Field Naming

Values are submitted as nested arrays:

```
contact-persons[0][full-name] = "John Doe"
contact-persons[0][email-address] = "john@example.com"
contact-persons[1][full-name] = "Jane Smith"
contact-persons[1][email-address] = "jane@example.com"
```

Access in finishers or templates:

```php
$contacts = $formValues['contact-persons']; // Array of arrays

foreach ($contacts as $index => $contact) {
    $name = $contact['full-name'];
    $email = $contact['email-address'];
}
```

## Min/Max Fieldsets

Control how many fieldsets users can add:

- **Minimum** - Initial number of fieldsets rendered, cannot remove below this number
- **Maximum** - Upper limit, "Add" button hidden when reached

## Validation

Each fieldset is validated independently:
- Required fields validated per fieldset
- Validation errors shown within each fieldset
- Form cannot be submitted if any fieldset has errors

## Display Conditions

Use `[__INDEX]` placeholder for conditions on nested fields:

**Client-side conditional display based on value in same fieldset:**
```
formData("[family-members][__INDEX][age]") < 18
```

The `[__INDEX]` is replaced with the actual index (0, 1, 2, etc.) for each fieldset.

**More Examples:**

```
// Show field if relation is "Child"
formData("[family-members][__INDEX][relation]") == "Child"

// Show field if name is not empty
formData("[family-members][__INDEX][name]")

// Multiple conditions
formData("[family-members][__INDEX][relation]") == "Child" && formData("[family-members][__INDEX][age]") < 18
```

**Check fieldset count in subsequent page (server-side):**
```
value("family-members")[4]  // True if 5 or more fieldsets exist
```

> **📌 Note:** Repeatable containers cannot be nested.

## 🔗 Related

- [Field Reference](FieldReference.md) - Field types and properties
- [Conditions](Conditions.md) - Display condition syntax
- [Editor Guide](EditorGuide.md) - Building forms
