---
name: dashboard-backoffice
description: Dashboard and backoffice development rules for ItalianCosplay.it. Use when creating user dashboards, admin panels, CRUD interfaces, management screens, moderation tools or internal workflows.
---

# ItalianCosplay Dashboard and Backoffice Skill

## Purpose

This skill defines standards for private areas of ItalianCosplay.it.

The application contains different experiences:

**Public Area** — focused on SEO, content discovery, user engagement, event exploration.

**Dashboard Area** — focused on user management, personal features, user actions.

**Backoffice Area** — focused on administration, moderation, content management, business operations.

Do not mix public and private UX patterns.

---

## Main Principles

Dashboards and backoffice interfaces must be:

- efficient
- clear
- secure
- consistent
- scalable

The priority is not visual impact. The priority is:

- completing tasks quickly
- reducing errors
- making information understandable

---

## Architecture Rules

Dashboard and backoffice must follow the same MVC architecture.

Flow:

```
Request → Middleware → Controller → Service → Repository / Model → Database
```

Controllers must not contain:

- SQL
- business rules
- image processing
- complex workflows

---

## Authentication Requirement

All private areas require authentication.

Protected routes must use:

- authentication middleware
- session validation
- permission checks

Never protect only through interface elements.

**Bad:**

```html
<a style="display:none">
    Admin
</a>
```

**Good:** use `AuthorizationMiddleware`.

---

## User Dashboard

The user dashboard is designed for registered users.

Typical features:

- profile management
- avatar
- account settings
- personal content
- notifications
- preferences

### User Experience Rules

Dashboard pages should provide:

- clear navigation
- visible status
- understandable actions
- feedback after operations

Every action should have a success state, error state, and loading state when needed.

### Dashboard Layout

Use a dedicated layout.

```
views/layouts/
└── dashboard.php
```

Common elements: sidebar, header, user menu, content area, notifications.

Avoid duplicating dashboard HTML in every page.

---

## Admin Backoffice

The backoffice manages the platform.

Typical modules: events, blog, users, images, categories, reports, advertising.

### Admin UX Principles

Administrative interfaces should optimize speed, productivity, and accuracy.

Prefer: tables, filters, bulk actions, quick edit, clear statuses.

Avoid: unnecessary animations, excessive decoration, confusing layouts.

---

## CRUD Standards

CRUD operations must follow a consistent workflow: Create, Read, Update, Delete.

Before creating a CRUD module evaluate:

1. Does the entity already exist?
2. Can existing services be reused?
3. Are permissions required?
4. Is approval workflow needed?
5. Is soft delete appropriate?

### CRUD Architecture

Recommended flow:

```
Controller → Service → Repository → Database
```

Example — creating an event:

```
EventAdminController → EventService → EventRepository → Database
```

---

## Controllers Rules

Admin controllers should:

- receive requests
- validate permissions
- validate input
- call services
- prepare responses

Controllers should not:

- contain SQL
- manipulate images
- contain large business logic

---

## Services Rules

Services contain business workflows.

Examples: event approval, user activation, image processing, advertising campaign activation.

Example:

```php
$eventService->approveEvent($eventId);
```

Avoid putting workflows directly inside controllers.

---

## Tables in Backoffice

Tables are a primary pattern for administration.

Use tables for: events, users, blog posts, campaigns, reports.

Tables should include:

- clear columns
- sorting when useful
- pagination
- filters
- actions

### Responsive Tables

Administrative tables must work on mobile.

Possible approaches: horizontal scrolling, responsive cards, simplified columns.

Do not create desktop-only admin interfaces.

### Table Actions

Actions must be clear: `View`, `Edit`, `Approve`, `Delete`.

Destructive actions require confirmation.

---

## Status Management

Use visual status indicators.

Examples: `Approved`, `Pending`, `Rejected`, `Draft`, `Archived`.

Statuses should be understandable, consistent, and filterable.

---

## Search and Filters

Large datasets require filtering.

**Events:** date, region, province, approval status, type.

**Users:** role, status, registration date.

**Blog:** category, publication status.

### Pagination

Never load unlimited records. Use pagination, limits, optimized queries.

Example:

```
Events 1-50 of 2500
```

### Bulk Actions

For large management tasks consider bulk actions.

**Events:** approve multiple events, change status, archive.

**Users:** activate, deactivate.

Bulk actions must:

- require permission
- show confirmation
- report results

---

## Event Management Workflow

Events are a core entity.

### Creation

Information: title, description, dates, location, images, social links, website, event type.

### Approval Workflow

Events should support states:

```
Draft → Pending Review → Approved → Published → Archived
```

### Event Moderation

Before publishing evaluate:

- completeness
- duplicate events
- correct location
- valid dates
- image quality

---

## Image Management

Images require dedicated management.

Features: upload, preview, ordering, deletion, optimization.

Use existing image services. Do not duplicate upload logic.

### Image Actions

Administrative image tools should support:

- replace image
- remove image
- reorder gallery
- set primary image

---

## Confirmation Actions

Actions that can cause data loss require confirmation.

Examples: delete event, remove image, deactivate user.

Avoid accidental destructive operations.

---

## Notifications

After operations provide feedback.

**Success:** `Evento approvato correttamente.`

**Error:** `Impossibile salvare l'evento.`

Messages should be clear for operators.

---

## Backoffice Security

The backoffice contains sensitive operations.

Every action must verify:

- authentication
- authorization
- permissions
- CSRF protection

Never assume that an administrator route is safe only because it is hidden.

### Permission System

Use permissions instead of hardcoded role checks.

**Good:**

```php
$user->hasPermission('events.approve');
```

**Avoid:**

```php
if ($user->role === 'admin')
```

...for every action.

Permissions should represent actions. Examples:

- `events.create`
- `events.update`
- `events.delete`
- `events.approve`
- `users.manage`
- `blog.publish`
- `ads.manage`

### Role Management

Roles group permissions.

```
Super Admin
  └── all permissions

Editor
  ├── blog.manage
  └── events.review

Organizer
  ├── events.create
  └── events.update

User
  └── personal profile
```

Avoid creating too many roles without a real need.

---

## Audit Logging

Important administrative actions should be tracked.

Examples: event approval, user deletion, permission changes, advertising changes, configuration updates.

Store: user, action, affected entity, timestamp, optional metadata.

Example:

```
Admin user 15 approved event 234
```

---

## Dashboard Statistics

Statistics dashboards should not slow down the application.

Avoid calculating expensive metrics on every page load. Prefer cached values, scheduled calculations, aggregation tables.

Example dashboard cards:

| Metric | Value |
|---|---|
| Events | 1,245 |
| Users | 8,500 |
| Blog views | 120,000 |
| Advertising impressions | 500,000 |

---

## Reporting

Reports should be designed for decisions.

**Events:** most viewed events, regional distribution, approval status.

**Blog:** top articles, traffic sources.

**Advertising:** impressions, clicks, CTR, campaign performance.

---

## Advertising Management Area

Future advertising features require dedicated management.

Consider: advertisers, campaigns, banners, placements, payments, statistics.

### Advertising Workflow

```
Advertiser creates campaign
        ↓
Payment received
        ↓
Campaign approved
        ↓
Banner published
        ↓
Statistics collected
        ↓
Report generated
```

Each step should have a clear status.

### Financial Operations

Financial actions require extra care.

Protect: invoices, payments, customer data, campaign values.

Never allow unauthorized users to view commercial information.

---

## File Management

Backoffice file managers must:

- validate permissions
- restrict allowed actions
- protect paths
- validate uploads

Never allow administrators to bypass security checks.

---

## Configuration Management

Sensitive configuration should not be editable by normal administrators.

Examples: database settings, API keys, security settings.

Separate operational settings from technical configuration.

---

## Backoffice Performance

Administrative interfaces should remain fast with large datasets.

Consider: pagination, indexed queries, lazy loading, filters, search.

Avoid loading thousands of rows, unnecessary relations, huge images.

---

## Backoffice Anti-Patterns

Avoid the following.

### Duplicate Business Logic

**Bad:** `EventController` contains approval logic.

**Good:** `EventService` contains approval workflow.

### Direct Database Access in Views

**Bad:** `SELECT * FROM events` inside templates.

### Permission Checks Only in UI

**Bad:** hiding buttons without protecting actions.

### Huge Admin Pages

Avoid pages containing thousands of lines, unrelated functionality, multiple workflows. Split into modules.

---

## Backoffice Checklist

Before completing a dashboard/backoffice feature verify:

### Architecture

- Is MVC respected?
- Are services reused?

### Security

- Are permissions checked?
- Is CSRF enabled?
- Are actions protected?

### UX

- Is the workflow clear?
- Are errors understandable?
- Are confirmations present?

### Data

- Are queries optimized?
- Is pagination implemented?

### Business

- Does this support future growth?
- Can this feature evolve into a professional tool?

---

## Final Dashboard Philosophy

ItalianCosplay backoffice should be a professional operating platform.

It must allow administrators and partners to:

- manage content
- moderate contributions
- analyze data
- operate efficiently
- support future monetization

The best backoffice is not the most complex one. It is the one that makes daily work simple, safe and reliable.
