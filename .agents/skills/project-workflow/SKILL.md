---
name: italiancosplay-project-workflow
description: Development workflow for ItalianCosplay.it. Use this skill before implementing any feature, refactoring or bug fix to analyze the project, reuse existing code and produce coherent solutions.
---

# ItalianCosplay Project Workflow

## Purpose

This skill defines the development workflow that must be followed for every task.

Its goal is to ensure:

- consistency;
- maintainability;
- scalability;
- code reuse;
- long-term project quality.

The workflow applies to every modification, regardless of its size.

---

# Golden Rule

Never start writing code immediately.

Always understand the project before implementing a solution.

Time spent analyzing the project is never wasted.

---

# Standard Workflow

Every task follows this sequence:

```
Understand

↓

Analyze

↓

Plan

↓

Implement

↓

Review
```

Never skip analysis.

---

# Step 1 — Understand the Request

Before writing code identify:

- objective;
- affected module;
- expected result;
- business purpose.

If the request is ambiguous:

ASK.

Never guess business rules.

---

# Step 2 — Analyze Existing Code

Before creating anything:

Search for:

- existing Models;
- Services;
- Controllers;
- Helpers;
- Views;
- Components;
- Utilities;
- SQL queries.

Always prefer extending existing code.

Avoid duplication.

---

# Step 3 — Identify Dependencies

Determine which files are affected.

Typical dependencies include:

```
Controller

↓

Service

↓

Model

↓

View

↓

Routes

↓

Database

↓

SEO

↓

Images
```

Never modify a single file without evaluating its dependencies.

---

# Step 4 — Database Impact

Ask:

Does this feature require:

- new tables?
- new columns?
- indexes?
- foreign keys?
- migrations?

If yes:

Create migrations.

Never suggest manual schema edits.

---

# Step 5 — SEO Evaluation

Every public page must be evaluated for SEO.

Consider:

- URL
- slug
- title
- meta description
- canonical
- Open Graph
- Schema.org
- breadcrumbs
- internal links

SEO is mandatory.

---

# Step 6 — Performance Evaluation

Before implementing:

Ask:

Can this generate:

- N+1 queries?
- duplicate queries?
- unnecessary loops?
- large memory usage?

Prefer optimized solutions.

---

# Step 7 — Security Evaluation

Always verify:

- CSRF
- validation
- escaping
- permissions
- SQL injection
- file uploads

Security is never optional.

---

# Step 8 — Future Maintainability

Before creating:

- helper
- service
- trait
- repository

Ask:

Will this be reused?

If not,

avoid unnecessary abstractions.

Keep the architecture simple.

---

# Step 9 — Coding

Only after completing analysis:

Implement.

Follow project standards.

Produce readable code.

Use English naming.

---

# Step 10 — Final Review

Before considering the task complete verify:

✓ MVC respected

✓ No duplicated code

✓ Security preserved

✓ SEO preserved

✓ Tailwind respected

✓ Existing architecture reused

✓ Migration created if necessary

✓ No HTML inside Controllers

✓ No SQL inside Views

---

# Existing Code First

Always prefer:

```
Improve

↓

Extend

↓

Reuse
```

Instead of:

```
Rewrite

↓

Duplicate

↓

Replace
```

Creating new files should be the exception.

---

# Refactoring Policy

When encountering poor code:

Do not rewrite everything.

Instead:

- isolate improvements;
- preserve compatibility;
- improve incrementally.

Large refactoring should be proposed before implementation.

---

# File Creation Policy

Before creating:

```
ExampleService.php
```

Verify whether:

```
EventService.php
```

already solves the problem.

Do not create alternative implementations.

---

# Controller Policy

Controllers should remain thin.

Responsibilities:

- validate request;
- call services;
- choose view;
- return response.

Controllers should not contain business logic.

---

# Model Policy

Models:

- represent entities;
- access the database;
- expose domain behavior.

Avoid presentation logic.

---

# Service Policy

Services contain business rules.

Examples:

- ranking;
- uploads;
- geocoding;
- notifications;
- advertising;
- statistics.

If business logic grows,

move it into a Service.

---

# View Policy

Views render data.

Views may contain:

- HTML;
- Tailwind;
- minimal PHP.

Views must never contain:

- SQL;
- routing;
- business logic.

---

# Communication Policy

If information is missing:

Ask first.

Never invent:

- business rules;
- database structures;
- workflows;
- permissions.

Clarification is preferred over incorrect implementation.

---

# Deliverables

When generating code always provide:

- complete files whenever possible;
- file paths;
- migration files if required;
- explanation of modified files;
- notes about database changes;
- notes about SEO implications.

Avoid partial snippets unless explicitly requested.

---

# Consistency Rules

Every implementation should be coherent with:

- existing architecture;
- coding standards;
- naming conventions;
- database design;
- project goals.

Consistency is more important than novelty.

---

# Commercial Awareness

ItalianCosplay is a business platform.

Every feature should be evaluated considering:

- SEO value;
- user experience;
- scalability;
- advertising opportunities;
- future marketplace integration;
- maintainability.

Technical decisions should support business growth.

---

# Final Philosophy

Do not build isolated features.

Build parts of a coherent platform.

Every implementation should leave the project cleaner, more maintainable and easier to extend than before.
