# Tutorial: Create and Use Newsletter Templates

Templates make recurring newsletters faster and more consistent. A template stores a default subject, Markdown body, message options, and visual overrides.

## 1. Set the global design first

Go to **Components → Punga Mail → Options → Mail design**.

Set the design that should be acceptable for most mail:

- body heading;
- browser-view default;
- content width and backgrounds;
- text, heading, link, and footer colors;
- font and base size;
- content padding;
- logo and width;
- footer explanation;
- optional custom CSS.

Templates should override only what is different. Blank template fields inherit the global design.

## 2. Create a template

Go to **Templates** and select **New**. The editor follows Joomla's main-content plus right-sidebar pattern. Use **Mail content** for the reusable subject/body, **Design** for visual overrides, and the right sidebar for message behavior such as heading, browser view, and Reply-To.

Enter:

- **Title**: an internal name such as `Monthly news`;
- **Email subject**: a useful default, if the subject is predictable;
- **Newsletter body (Markdown)**: the recurring structure.

Example:

```markdown
Hello {recipient},

Here is our monthly update.

{new_content}

Questions? Reply to this email.
```

Use `{new_content}` where selected website items should appear. Without it, selecting items in a newsletter or digest will not add them to the body.

Use `{recipient}` only where personalization reads naturally. It becomes the Joomla display name when available and falls back to the email address.

## Content item layouts are managed separately

Templates control the reusable **message** (subject, body and design), but they no longer define how each `{new_content}` item is formatted. Use **Punga Mail → Content layouts** for that. There you can define one Default item layout and optional layouts for individual registered content types. Punga Mail shows the placeholders available for each type, including safe fields discovered directly from its registered database table.

This separation means the same Article/Event/Web Link layout is used consistently by ordinary Newsletters and Automatic Newsletters without adding more controls to each Template.

## 3. Choose message behavior

### Mail body heading

- **Inherit** uses Component Options.
- **Custom** uses the Custom heading field.
- **Use site name** deliberately uses Joomla's current site name.
- **No heading** omits the heading entirely.

The body heading is not the email subject, template title, or sender name.

### Browser view

Choose Inherit, Enabled, or Disabled. Enabled causes sent newsletters based on the template to include a link to their immutable browser snapshot unless the newsletter overrides it.

### Reply-To

Choose Inherit, Custom, or None. With Custom, enter a valid email and optional display name.

## 4. Add only necessary style overrides

Override individual properties only when this template needs a distinct presentation—for example, an Events template may use a different link color and logo.

Leave all other fields blank. This preserves the inheritance chain:

**Component Options → Template → Newsletter**

Custom CSS is appended to the global CSS. Keep it simple and test in real mail clients.

## 5. Preview and save

Select **Preview** and inspect the HTML and plain-text result. The template preview demonstrates structure and design; actual selected content appears only after the template is copied into a newsletter or used by a digest.

Use **Save & Close** when satisfied.

## 6. Apply it to a newsletter

Create or open a Draft newsletter:

1. choose the template;
2. select **Apply template**;
3. review every copied value;
4. customize the subject/body and newsletter-level options;
5. save the draft.

Applying is a copy operation. It does not create a live relationship. This protects drafts and sent history from future template edits.

If you apply another template later, copied values may replace draft changes. Save or copy anything important first.

## 7. Use it for a digest

Create a digest and choose the template under Basics. A digest uses the template when it runs, so later template changes affect future digest runs but never newsletters already generated.

For a digest template:

- include `{new_content}`;
- avoid date-specific prose unless the text works every run;
- let the digest Subject pattern add `{date}` or `{site_name}` where needed;
- use an access-appropriate audience/content design;
- keep Empty digest set to Skip for automatic sending.

## 8. Maintain templates safely

- Give templates descriptive administrator titles.
- Preview after changing design or CSS.
- Send a test newsletter before relying on a changed template for auto-send.
- Trash obsolete templates instead of immediately deleting them.
- A sent newsletter remains unchanged when its original template changes.
- A generated Draft already contains copied values; reapply only when you want to overwrite them.

For all template and design fields, see [Punga Mail Administrator Guide](USER_GUIDE.md).

