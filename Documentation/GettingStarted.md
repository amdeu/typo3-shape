# Getting Started

## 📋 Requirements

- TYPO3 v13.4 or higher
- PHP 8.2 or higher
- Composer

## 📦 Installation

### Install via Composer

```bash
composer require amdeu/typo3-shape
```

### Update Database Schema

**Backend:**
1. **Admin Tools → Maintenance → Analyze Database Structure**
2. Apply all changes

**CLI:**
```bash
vendor/bin/typo3 database:updateschema
```

## 🚀 Your First Form

> **💡 Tip:** Create a new sysfolder for each form to make sure the list view gives you a clean overview of one form and its related records (pages, fields, finishers).

### Step 1: Create a Form

1. **List** module → Navigate to a sysfolder (or create a new one)
2. **Create new record → Shape: Form**
3. Fill in **Title** (e.g., "Contact Form")
4. The **Name** is auto-generated from the label (like page slugs) but can be manually edited

> **📌 Note:** Form names must be unique across the Site (like page slugs). They use kebab-case (e.g., `contact-form`).

### Step 2: Add a Page

Even single-page forms need one page.

In the form record → **Pages** field → **Create new** 

> **📌 Note:** Page type defaults to "Page". For multi-step forms, any page can be of type "Summary" to show a readonly summary of all previous pages.

### Step 3: Add Fields

In the page record → **Fields** field → **Create new**

**Name Field:**
```
Label: Your Name
Type: Text
Required: Yes
```

The **Name** is auto-generated as kebab-case from the label (e.g., `your-name`) but can be edited.

**Email Field:**
```
Label: Email Address
Type: Email
Required: Yes
```

**Message Field:**
```
Label: Message
Type: Textarea
Required: Yes
```

> **💡 Note:** Field names are unique within the PID and use kebab-case (e.g., `email-address`, `your-name`).

### Step 4: Add a Finisher

1. In the form record → **Finishers** tab → **Create new**
2. Select **Type** from the dropdown

Examples:

**Save Submission:**
```
Type: Save Submission
Settings: Submission Storage Page: (select your form folder)
```

**Send Email:**
```
Type: Send Email
Subject: New Contact Form Submission
Body: Message from {{your-name}} ({{email-address}}): {{message}}
Recipient Email Addresses: your@email.com
Sender Email Address: (falls back to system default)
```

> **💡 Tip:** Use `{{field-name}}` syntax to insert field values.

### Step 5: Create a Frontend Plugin
1. Go to the **Page** module
2. Select the page where you want to display the form (or create a new one)
3. **Create new content element → Form elements → Shape Form**
4. In the Plugin Options, select your form in the **Form** record selector

Your form is now ready to use!

## 🔗 Next Steps

- [Editor Guide](EditorGuide.md) - Learn all field types and features
- [Field Reference](FieldReference.md) - Complete field property reference
- [Integrator Guide](IntegratorGuide.md) - Customize templates
