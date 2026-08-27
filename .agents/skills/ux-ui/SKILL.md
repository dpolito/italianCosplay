---
name: italiancosplay-ux-ui
description: UX and UI rules for ItalianCosplay.it. Use this skill when creating or modifying pages, components, interfaces, dashboards and user interactions to maintain a consistent, accessible and conversion-oriented user experience.
---

# ItalianCosplay UX/UI Skill

## Purpose

This skill defines the user experience and interface principles of ItalianCosplay.it.

The objective is creating a platform that is:

- intuitive
- fast
- accessible
- mobile-first
- visually consistent
- optimized for engagement and conversions

Every interface decision must consider:

1. User experience
2. Navigation clarity
3. SEO visibility
4. Accessibility
5. Performance
6. Business goals

---

## UX Philosophy

ItalianCosplay is not only an information website. It is a platform for:

- discovering cosplay events
- reading cosplay content
- connecting users
- helping organizers gain visibility
- creating commercial opportunities

Every page must have a clear purpose. The user must always understand:

- where they are
- what they can do
- what the next action is

---

## Mobile First Approach

The primary design target is mobile.

```
Mobile → Tablet → Desktop
```

Never design desktop first and adapt later.

Every component must work correctly on smartphones, tablets, and desktop screens.

---

## Design System Principles

The interface must be consistent.

Reuse existing: components, spacing, typography, buttons, cards, forms, alerts, navigation patterns.

Do not create new visual patterns without evaluating existing ones.

---

## Layout Structure

Public pages should generally follow:

```
Header → Breadcrumb → Main content → Related content → CTA → Footer
```

The exact order can change depending on the page objective.

---

## Header

The header must provide: logo, primary navigation, search access, user access, mobile menu.

Navigation must prioritize: Events, Blog, Community features, Future marketplace.

---

## Breadcrumbs

Breadcrumbs are mandatory on deep pages.

Example:

```
Home > Eventi > Toscana > Lucca Comics 2026
```

Benefits: navigation, SEO, user orientation.

---

## Cards

Cards are a primary UI pattern.

Used for: events, articles, guests, characters, advertisements.

Standard structure:

```
Image → Title → Metadata → Short description → Action
```

Cards must: have consistent spacing, support mobile layout, maintain visual hierarchy.

---

## Event Page UX

Event pages are a core feature.

The user goal is: understand the event, decide whether to attend, save/share the event.

Recommended structure:

```
Breadcrumb
  → Event title
  → Cover image
  → Date and location
  → Primary CTA
  → Description
  → Map
  → Guests
  → Gallery
  → Related events
  → Related articles
```

### Event Primary Actions

Important actions should be visible.

Examples: add to calendar, visit official website, share event, report missing information.

Do not hide important actions inside secondary menus.

---

## Blog UX

The blog must support SEO discovery.

Article pages should include:

```
Title
  → Cover image
  → Metadata
  → Content
  → FAQ (when relevant)
  → Related articles
  → Related events
  → CTA
```

---

## Internal Linking UX

Navigation between content is important.

Users should naturally move between:

```
Blog article → Event → Region → Related events
```

and:

```
Event → Articles → Characters → Guides
```

Internal linking improves: UX, SEO, engagement.

---

## Forms UX

All forms must follow consistent patterns.

Structure:

```
Label → Input → Help text → Validation message
```

Rules: clear labels, useful placeholders, immediate validation feedback, understandable errors.

### Form Errors

Errors must: explain the problem, suggest the solution, remain visible.

**Bad:** `Errore.`

**Good:** `Inserisci una data valida per l'evento.`

---

## Buttons and CTA

Buttons must communicate actions clearly.

**Primary actions:** strong visual priority. Example: `Aggiungi evento`

**Secondary actions:** less emphasis. Example: `Annulla`

Avoid unclear labels like `Clicca qui`.

---

## Empty States

Every empty section must have a useful state.

Example — no events:

```
Non abbiamo ancora eventi in questa zona.
Segnala un evento
```

Never show empty blocks without explanation.

---

## Loading States

For dynamic content use: loading indicators, skeleton states, disabled buttons during submission.

Avoid interfaces that appear frozen.

---

## Feedback Messages

Use consistent feedback.

**Success:** `Operazione completata.`

**Warning:** `Controlla i dati inseriti.`

**Error:** `Si è verificato un problema. Riprova più tardi.`

---

## Dashboard UX

Admin and user dashboards must follow a consistent structure.

Recommended layout:

```
Sidebar → Header → Page title → Breadcrumb → Main actions → Content cards/tables
```

### Dashboard Tables

Tables must provide: responsive behavior, sorting when useful, pagination, clear actions.

Mobile tables should transform into usable cards when necessary.

---

## Accessibility Requirements

Every interface must respect accessibility principles.

Required: semantic HTML, correct heading hierarchy, keyboard navigation, visible focus states, alt text for images, aria labels when needed, sufficient color contrast.

---

## Images UX

Images must: have correct dimensions, avoid layout shifts, use appropriate presets, include alt text.

Use: thumbnails for lists, medium images for cards, large images for detail pages.

---

## Performance UX

Avoid: unnecessary JavaScript, heavy animations, blocking resources, oversized images.

Prioritize: Core Web Vitals, fast rendering, perceived speed.

---

## Responsive Rules

Components must adapt naturally.

Avoid: horizontal scrolling, tiny buttons, unreadable text, desktop-only interactions.

Touch targets should be comfortable on mobile.

---

## Ads UX

Advertising is a business objective but must respect users.

Ads must: integrate naturally, not interrupt navigation, maintain visual quality, clearly separate sponsored content.

Do not create layouts where ads dominate the content.

---

## Conversion Principles

Every important page should have a purpose.

**Event page:**

```
Discover event → Visit website → Save event
```

**Blog page:**

```
Read article → Explore related content → Discover events
```

**Commercial page:**

```
Understand service → Contact / purchase
```

---

## UX Review Checklist

Before completing a UI task verify:

- ✓ Mobile-first
- ✓ Consistent components
- ✓ Clear hierarchy
- ✓ Accessible
- ✓ Fast loading
- ✓ Clear CTA
- ✓ Good empty states
- ✓ Good error handling
- ✓ Internal navigation considered
- ✓ SEO-friendly structure
- ✓ Compatible with Tailwind approach

---

## Final UX Philosophy

ItalianCosplay should feel like a modern digital platform.

Users should easily discover content, interact with events, participate in the community and naturally move toward valuable services.

Every interface should reduce friction and increase usefulness.
