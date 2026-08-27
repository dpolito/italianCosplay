---
name: frontend-tailwind
description: Frontend development rules for ItalianCosplay.it using Tailwind CSS, HTML views and Vanilla JavaScript. Use when creating pages, components, layouts, forms or UI elements.
---

# ItalianCosplay Frontend Tailwind Skill

## Purpose

This skill defines frontend standards for ItalianCosplay.it.

The frontend must be:

- modern
- responsive
- accessible
- fast
- SEO friendly
- consistent

The interface must support:

- cosplay events discovery
- blog reading
- community interaction
- commercial visibility
- future marketplace features

---

## Frontend Stack

The project uses:

- HTML
- Tailwind CSS
- Vanilla JavaScript

Do not introduce React, Vue, Angular, or other frontend frameworks unless explicitly approved.

---

## Rendering System

The project uses:

- native PHP views
- reusable PHP components
- server-side rendering

Do not use Twig, template engines, or duplicated HTML structures.

---

## Mobile First Approach

All interfaces must be designed mobile-first.

Start from **mobile**, then improve for **tablet**, **desktop**, and **large screens**.

Example:

```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
```

### Responsive Design

Every component must work on smartphone, tablet, and desktop.

Avoid layouts that only work on large screens. Consider:

- touch interactions
- readable text
- spacing
- navigation usability

---

## Tailwind Usage

Use Tailwind utility classes.

Example:

```html
<div class="rounded-xl bg-white p-6 shadow">
```

Avoid writing custom CSS when Tailwind utilities are enough.

### Custom CSS

Custom CSS is allowed only for:

- reusable patterns
- complex components
- animations
- browser-specific fixes

Avoid creating large CSS files that duplicate Tailwind.

---

## Design Consistency

All pages should share common design patterns.

Reuse: buttons, cards, badges, forms, alerts, navigation, breadcrumbs.

Do not create a new style for every page.

### Component Thinking

Even without a frontend framework, build reusable components.

```
views/components/
├── button.php
├── card.php
├── badge.php
├── breadcrumb.php
└── pagination.php
```

Avoid copying the same HTML in multiple views.

### Layout Structure

Common layouts should be separated.

```
views/layouts/
├── app.php
├── dashboard.php
└── admin.php
```

Pages should use layouts instead of duplicating header, navigation, and footer.

---

## HTML Structure

Use semantic HTML.

**Prefer:**

```html
<header>
<nav>
<main>
<section>
<article>
<footer>
```

**Avoid:**

```html
<div id="everything">
```

...for entire page structures.

### Accessibility Basics

Accessibility is required. Consider:

- semantic elements
- keyboard navigation
- contrast
- labels
- focus states
- screen readers

---

## UI Component Standards

ItalianCosplay should use consistent UI components.

Common components: buttons, cards, badges, alerts, modals, dropdowns, tabs, pagination, breadcrumbs.

Before creating a new component:

1. Search existing components.
2. Reuse if possible.
3. Extend existing components when appropriate.

Avoid creating visually similar components with different markup.

---

## Buttons

Buttons must have:

- clear purpose
- accessible labels
- visible states

Consider states: default, hover, focus, disabled, loading.

Example:

```html
<button class="rounded-lg px-4 py-2">
    Salva evento
</button>
```

Avoid buttons without clear actions.

---

## Cards

Cards are a primary UI pattern, used for events, blog articles, advertisers, and marketplace items.

Cards should include:

- image when relevant
- title
- summary
- main action

Example structure:

```html
<article>
    <!-- image -->
    <!-- title -->
    <!-- description -->
    <!-- CTA -->
</article>
```

### Event Cards

Event cards should prioritize discovery. Consider displaying:

- event image
- event name
- date
- location
- region
- event type

Example layout:

```
[image]
Lucca Comics & Games 2026
28 Ottobre - 1 Novembre
Lucca, Toscana
Scopri evento
```

### Blog Cards

Blog cards should prioritize reading. Include:

- cover image
- title
- excerpt
- category
- publication date

Avoid showing excessive information.

---

## Forms

Forms must be simple, readable, validated, and accessible.

Every field requires: label, input, validation feedback.

**Good:**

```html
<label for="title">
Titolo evento
</label>

<input id="title">
```

**Avoid:**

```html
<input placeholder="Titolo">
```

...without label.

### Form Layout

Use logical grouping.

```html
<fieldset>
    <!-- Event information -->
</fieldset>

<fieldset>
    <!-- Location -->
</fieldset>

<fieldset>
    <!-- Images -->
</fieldset>
```

Large forms should not become overwhelming.

### Error Messages

Errors must be visible and understandable.

**Good:** `Inserisci una data valida per l'evento.`

**Avoid:** `Error 500` for validation errors.

---

## Image Handling

Images are important for ItalianCosplay.

Frontend must consider:

- responsive images
- lazy loading
- aspect ratio
- placeholders

Example:

```html
<img
 src="event.webp"
 loading="lazy"
 alt="Lucca Comics cosplay area"
/>
```

### Image Accessibility

Every meaningful image requires an alt attribute.

**Good:** `alt="Cosplayer durante Lucca Comics 2026"`

**Avoid:** `alt="image"` or empty alt for important images.

---

## JavaScript Rules

Use Vanilla JavaScript only. JavaScript should enhance the experience.

Do not make essential content depend entirely on JavaScript.

### JavaScript Organization

Avoid inline JavaScript.

**Bad:**

```html
<button onclick="openMenu()">
```

**Good:**

```js
button.addEventListener(
    'click',
    openMenu
);
```

### JavaScript Structure

Organize scripts clearly.

```
public/assets/js/
├── app.js
├── dashboard.js
└── events.js
```

Avoid one huge JavaScript file containing everything.

### AJAX Requests

AJAX can be used for dynamic actions, uploads, filters, and interactive features.

Requirements:

- CSRF protection
- server validation
- proper error handling

### Loading States

Async operations should communicate status (saving, uploading, searching).

Provide:

- loading indicator
- success feedback
- error feedback

### Modals and Dynamic UI

Modals should:

- be keyboard accessible
- trap focus when needed
- have close actions

Avoid JavaScript UI that blocks navigation.

---

## Dashboard UI

Dashboard pages should prioritize clarity, efficiency, and data visibility.

Common patterns: sidebar navigation, cards, tables, forms, notifications.

### Admin UI

Admin interfaces should prioritize productivity, information density, and safety.

Consider:

- confirmation dialogs
- permission visibility
- clear destructive actions

---

## SEO Friendly Frontend

Frontend implementation must support SEO.

Every public page should consider:

- semantic HTML
- crawlable content
- correct heading structure
- internal links
- metadata rendering

Avoid:

- content hidden only behind JavaScript
- infinite scroll without crawlable alternatives
- empty HTML shells

---

## Page Structure

Public pages should follow a consistent structure.

Example:

```html
<body>

<header>
    <!-- Navigation -->
</header>

<main>
    <!-- Breadcrumb -->
    <!-- Page content -->
</main>

<footer>
</footer>

</body>
```

### Breadcrumbs

Breadcrumbs improve navigation, usability, and SEO.

Use them on: events, blog articles, categories, location pages, dashboard sections.

Example:

```
Home > Eventi Cosplay > Toscana > Lucca Comics 2026
```

---

## Performance Rules

Frontend performance is a priority.

Consider:

- loading speed
- Core Web Vitals
- mobile performance
- asset size

### CSS Performance

Avoid:

- unnecessary custom CSS
- duplicated utilities
- unused styles

Prefer:

- Tailwind optimization
- reusable components
- clean markup

### JavaScript Performance

Avoid:

- large scripts loaded everywhere
- unnecessary DOM manipulation
- blocking operations

Prefer:

- loading scripts only where needed
- event delegation
- efficient selectors

### Asset Loading

Optimize images, CSS, JavaScript, fonts.

Use lazy loading, compression, caching.

### Responsive Images

Use appropriate image sizes. Avoid loading a desktop-sized image on mobile.

Consider: thumbnails, medium images, large images.

Example:

```html
<img
 src="event-thumb.webp"
 loading="lazy"
 alt="Evento cosplay"
/>
```

---

## Accessibility Advanced Rules

Accessibility must be considered during development.

### Keyboard Navigation

All interactive elements must work with keyboard. Check menus, dialogs, forms, buttons.

### Focus States

Interactive elements must have visible focus. Avoid removing focus outlines without replacement.

### Forms

Forms must include labels, error messages, and correct input types.

Example: use `<input type="email">` instead of `<input type="text">` for email fields.

### Color Usage

Do not rely only on color to communicate information.

**Bad:** using red for error / green for success without text or icons.

---

## Tables

Tables should be used for structured data (admin lists, reports, statistics).

Requirements:

- responsive behavior
- clear headers
- readable columns

On mobile consider: horizontal scrolling, card conversion.

---

## Empty States

Every list page should handle empty states (no events found, no blog articles, no campaigns).

Avoid blank pages.

**Good:**

```
Nessun evento disponibile.
Torna presto per nuovi aggiornamenti.
```

---

## Loading and Feedback

Users should understand system status. Provide feedback for saving, deleting, uploading, searching.

Examples: toast notifications, inline messages, loading indicators.

---

## Dark Mode

Dark mode should not be introduced unless designed consistently. Do not create partial dark mode implementations.

---

## Browser Compatibility

Frontend should work on modern browsers: Chrome, Firefox, Safari, Edge, mobile browsers.

Avoid unnecessary experimental APIs.

---

## Tailwind Best Practices

Prefer readable class groups.

Example:

```html
<div
 class="
 rounded-xl
 bg-white
 p-6
 shadow
 "
>
```

Avoid extremely long unreadable class strings.

When components become complex: create reusable PHP components, extract patterns.

---

## Frontend Anti-Patterns

Avoid the following.

### Inline Styles

**Bad:**

```html
<div style="color:red">
```

Use Tailwind.

### Duplicate Markup

**Bad:** copying the same card HTML into many views.

Create components.

### JavaScript Dependent Pages

**Bad:** loading all content only through AJAX.

Important content should exist in HTML.

### Huge Components

Avoid pages containing thousands of lines of HTML. Split into layouts, components, partials.

---

## Frontend Checklist

Before completing frontend work verify:

### UI

- Is the design consistent?
- Is it mobile-first?
- Are components reusable?

### Accessibility

- Are labels present?
- Does keyboard navigation work?
- Are focus states visible?

### SEO

- Is HTML semantic?
- Are headings correct?
- Is content crawlable?

### Performance

- Are assets optimized?
- Are images handled correctly?
- Is JavaScript limited?

### Business

- Does the interface improve user engagement?
- Can it support future monetization?

---

## Final Frontend Philosophy

ItalianCosplay frontend should feel like a professional platform.

**Prefer:**

- clean interfaces
- reusable components
- fast experiences
- accessible design
- SEO-friendly HTML

**Avoid:**

- inconsistent pages
- unnecessary complexity
- heavy frontend frameworks
- poor mobile experiences

The frontend is not only visual. It is part of SEO, usability and business growth.
