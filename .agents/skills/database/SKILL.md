---
name: database
description: Database design and SQL rules for ItalianCosplay.it. Use when creating tables, migrations, queries, indexes, relationships or optimizing database operations.
---

# ItalianCosplay Database Skill

## Purpose

This skill defines database standards for ItalianCosplay.it.

The database must remain:

- reliable
- scalable
- maintainable
- secure
- optimized for SEO-oriented content growth

Database decisions must consider:

- current functionality
- future features
- traffic growth
- reporting needs
- advertising analytics
- data integrity

---

## Database Technology

The project uses:

- MySQL
- InnoDB engine
- UTF-8 compatible charset
- PDO for database access

Do not introduce:

- different database engines
- ORM systems
- database abstraction layers without approval

---

## Database Naming Convention

All database identifiers must be written in English.

Use snake_case for tables and columns.

**Good:**

```sql
events
event_images
blog_posts
created_at
updated_at
```

**Avoid:**

```sql
eventi
immagini_evento
data_creazione
```

### Table Naming

Tables should use plural names.

Examples:

- `users`
- `events`
- `blog_posts`
- `advertisements`

Avoid singular table names: `user`, `event`, `post`.

### Column Naming

Use descriptive snake_case names.

**Good:**

```
created_at
updated_at
published_at
is_active
user_id
event_id
```

**Avoid:**

```
date
status1
x
value
```

---

## Primary Keys

Every main table must have a primary key.

Standard:

```sql
id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
```

Example:

```sql
CREATE TABLE events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
);
```

Use consistent primary key naming — `id`, not `event_id`, for the primary table identifier.

---

## Data Types

Choose data types according to the expected data.

**IDs:** `BIGINT UNSIGNED`

**Boolean values:** use `TINYINT(1)`

```sql
is_active TINYINT(1) NOT NULL DEFAULT 1
```

**Dates:** use `DATE` for calendar dates

```sql
event_date DATE
```

**Timestamps:** use `DATETIME`

```sql
created_at DATETIME
```

### Timestamps

Common tables should include:

```sql
created_at DATETIME NOT NULL
updated_at DATETIME NULL
```

When useful, add for soft delete functionality:

```sql
deleted_at DATETIME NULL
```

---

## Soft Delete

For important entities prefer logical deletion.

```sql
deleted_at DATETIME NULL
```

...instead of immediately removing records.

Suitable for:

- users
- events
- blog posts
- advertising campaigns

Physical deletion should be reserved for specific cases.

---

## Foreign Keys

Use foreign keys where relationships are important.

```sql
event_id BIGINT UNSIGNED NOT NULL,
FOREIGN KEY (event_id) REFERENCES events(id)
```

Foreign keys protect data integrity.

---

## Database Relationships

Design relationships explicitly.

### One To One

```
users
  │
  └── profiles
```

Use when an entity has exactly one related record.

### One To Many

Most common relationship.

```
events
  │
  └── event_images
```

Implementation:

```sql
event_images
-------------
id
event_id
image_path
```

### Many To Many

Use junction tables.

```
events ── event_categories ── categories
```

Avoid storing IDs as comma-separated values.

**Bad:**

```
category_ids = '1,2,3'
```

**Good:**

```sql
event_categories
-----------------
event_id
category_id
```

---

## ItalianCosplay Main Entities

Core entities include:

### Users

Responsible for:

- accounts
- authentication
- roles
- permissions

### Events

One of the main entities. Consider:

- event information
- dates
- location
- images
- social links
- website
- approval state
- SEO fields
- statistics

### Event Images

Images should be separated from the main entity.

```sql
event_images
-------------
id
event_id
filename
path
type
position
created_at
```

Avoid storing multiple images in a single column.

### Blog Posts

Blog content is a major SEO asset. Consider:

- title
- slug
- excerpt
- content
- category
- author
- publication status
- SEO metadata
- timestamps

### Advertising System

Advertising features must support future monetization.

Consider entities such as:

- campaigns
- advertisers
- banners
- placements
- payments
- impressions
- clicks

Design for reporting and analytics.

---

## Index Rules

Indexes are essential for performance.

Create indexes for:

- foreign keys
- frequent searches
- filtering fields
- sorting fields
- unique values

Example:

```sql
CREATE INDEX idx_events_slug
ON events(slug);
```

### Unique Indexes

Use unique indexes for values that must not be duplicated.

```sql
slug VARCHAR(255) UNIQUE
```

Suitable for:

- usernames
- emails
- slugs
- external identifiers

### Composite Indexes

Use composite indexes for common query patterns.

Query:

```sql
WHERE region_id = ?
AND published = 1
ORDER BY created_at DESC
```

Possible index:

```sql
(region_id, published, created_at)
```

Design indexes based on real queries.

---

## Query Optimization

Never write queries without considering performance.

**Avoid:**

```sql
SELECT *
FROM events;
```

**Prefer:**

```sql
SELECT
    id,
    title,
    slug
FROM events;
```

### Pagination

Large datasets must use pagination.

Avoid loading:

```sql
LIMIT 100000
```

Use:

- `LIMIT`
- `OFFSET`
- cursor pagination when needed

### Query Rules

All queries must:

- select required fields only
- use prepared statements
- use indexes when possible
- avoid unnecessary joins
- avoid duplicate queries

### Aggregations

Statistics queries require attention. For views, impressions, clicks, and rankings, consider:

- indexes
- aggregation tables
- scheduled calculations

Do not calculate expensive statistics on every page request.

---

## Database Migrations

Every structural database change must have a migration.

```
database/
└── migrations/
    ├── 001_create_users.sql
    ├── 002_create_events.sql
    └── 003_add_event_images.sql
```

### Migration Rules

Migrations must be:

- ordered
- repeatable
- documented
- safe

Never modify an already executed migration. Create a new migration instead.

**Bad:** modifying `003_add_event_images.sql` after production.

**Good:** creating `004_update_event_images_structure.sql`.

### Migration Content

A migration should contain:

- table creation
- column changes
- indexes
- constraints

Example:

```sql
ALTER TABLE events
ADD COLUMN approved_at DATETIME NULL;
```

### Seed Data

Use seeds only for:

- default configuration
- required system data
- development data

Do not put user content in seeds.

---

## Transactions

Use transactions when multiple database operations must be atomic.

Examples:

- creating an event with images
- creating an advertising order
- processing payments

Example:

```sql
BEGIN;

INSERT INTO campaigns (...);
INSERT INTO banners (...);

COMMIT;
```

If something fails:

```sql
ROLLBACK;
```

---

## Database Security

Database security is mandatory.

Always:

- use prepared statements
- validate input before saving
- limit database user permissions
- protect sensitive information
- avoid exposing database errors

Never:

- concatenate user input into SQL
- store passwords in plain text
- store unnecessary personal data

---

## Password Storage

Passwords must never be stored directly.

Use PHP password functions: `password_hash()` and `password_verify()`.

The database should only contain password hashes.

```sql
password_hash VARCHAR(255) NOT NULL
```

---

## Personal Data and GDPR

ItalianCosplay handles user data and must follow GDPR principles.

Database design should consider:

- data minimization
- retention policies
- deletion requests
- anonymization
- audit requirements

Sensitive personal information should not be stored unless necessary.

### User Data

For user-related tables consider:

- `created_at`
- `updated_at`
- `deleted_at`
- verification status
- consent tracking when required

Example:

```sql
users
-----
id
email
password_hash
is_active
created_at
updated_at
deleted_at
```

### Soft Deletion and GDPR

Soft deletion is useful for operational recovery.

However, GDPR deletion requests may require:

- anonymization
- irreversible removal
- removal of personal identifiers

Do not keep personal data indefinitely without a purpose.

---

## Charset and Collation

All new tables must use `utf8mb4`.

```sql
DEFAULT CHARACTER SET utf8mb4
```

Use a consistent collation. Avoid mixing incompatible collations.

Before changing collations evaluate:

- existing data
- indexes
- query compatibility
- production impact

---

## Text Fields

Choose text types appropriately.

Use `VARCHAR` for:

- titles
- names
- slugs
- short values

Use `TEXT` for:

- articles
- descriptions
- long content

Avoid excessive `VARCHAR` lengths without reason.

---

## JSON Fields

JSON columns can be used when appropriate.

Good use cases:

- flexible metadata
- external API responses
- configuration data

Avoid using JSON to replace relational design.

**Bad:**

```sql
events
------
categories JSON
```

...when categories need filtering and relationships.

**Good:** `event_categories` with relational structure.

---

## SEO Database Considerations

Public SEO content should support:

- stable slugs
- unique URLs
- metadata
- indexing strategy
- internal relationships

**Blog posts:**

```sql
blog_posts
----------
id
title
slug
meta_title
meta_description
published_at
```

**Events:**

```sql
events
------
id
name
slug
start_date
end_date
approved
```

---

## Statistics and Analytics

Traffic statistics should not overload main tables.

For views, clicks, impressions, and rankings, consider dedicated tables or aggregation systems.

Avoid updating large counters on every request when traffic grows.

---

## Advertising Data Model Considerations

The advertising system must support:

- advertisers
- campaigns
- placements
- banners
- impressions
- clicks
- reports

Example relationship:

```
advertisers
    │
    └── campaigns
            │
            └── banners
                    │
                    └── statistics
```

Design tables to allow:

- reporting
- billing
- performance analysis

---

## Backup Strategy

Production databases require:

- regular backups
- tested restoration procedures
- protected backup storage

A backup is valid only if restoration has been tested.

---

## Database Performance Review

Before releasing database changes verify:

### Queries

- Are queries using indexes?
- Are joins necessary?
- Are selected columns required?

### Tables

- Are relationships correct?
- Are indexes appropriate?
- Are data types correct?

### Growth

- Will the table handle millions of rows?
- Are statistics separated?
- Is pagination available?

---

## Database Anti-Patterns

Avoid the following.

### Generic Columns

**Bad:**

```
data1
data2
value
```

Use meaningful names.

### Storing Multiple Values

**Bad:**

```
tags = 'cosplay,anime,manga'
```

...when relationships are needed. Use separate tables.

### Missing Indexes

**Bad:**

```sql
WHERE slug = ?
```

...without an index.

### Over Normalization

Do not create unnecessary tables that make simple operations complicated. Use balanced design.

### Under Normalization

Do not duplicate data everywhere.

**Bad:**

```
event_region_name
event_province_name
```

...stored in multiple places.

---

## Final Database Checklist

Before completing database work verify:

### Structure

- Are tables correctly designed?
- Are relationships clear?
- Are names consistent?

### Security

- Are sensitive values protected?
- Are queries safe?
- Is GDPR considered?

### Performance

- Are indexes correct?
- Are queries optimized?
- Can the data scale?

### Maintenance

- Is there a migration?
- Is the change documented?
- Can the schema evolve?

### Business

- Does the structure support future ItalianCosplay features?
- Does it support analytics and monetization?

---

## Database Philosophy

The ItalianCosplay database must evolve as a professional platform.

**Prefer:**

- clear structures
- predictable relationships
- optimized queries
- safe migrations
- scalable solutions

**Avoid:**

- quick database hacks
- duplicated data
- untracked changes
- designs that limit future growth
