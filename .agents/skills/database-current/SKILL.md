---
name: italiancosplay-database-current
description: Current database reference for ItalianCosplay.it. Use this skill to understand the existing database structure, entities, relationships, legacy constraints and migration rules before creating or modifying database-related code.
---

# ItalianCosplay Current Database Skill

## Purpose

This document describes the current database architecture of ItalianCosplay.it.

The database is the source of truth for:

- existing entities
- relationships
- naming conventions
- legacy decisions
- future migrations

Before creating Models, Repositories, Services, Controllers, Views, Queries, or Migrations, always verify this database reference.

Do not create duplicate structures without evaluating existing tables first.

---

## Current Database Stack

**Database:** MySQL 8+, InnoDB, utf8mb4

**Main development target:** PHP 8.2+, PDO, custom MVC OOP architecture

---

## Database Modification Rules

### Migration First

All schema changes must be performed through migrations. Do not manually modify production tables.

Example:

```
database/migrations/
├── 001_create_notifications.sql
├── 002_add_event_fields.sql
└── 003_rename_blog_fields.sql
```

---

## Naming Rules

The current database contains mixed naming.

**Example legacy fields:**

```
titolo
descrizione
data_inizio
categoria_id
```

**New development must use English naming:**

```
title
description
start_date
category_id
```

---

## Important Rule

Never rename existing tables or columns directly.

Before changing `titolo` to `title`, create a migration plan.

The migration must consider:

- existing queries
- Models
- Services
- Controllers
- Views
- SEO URLs
- existing data

---

## Database Domain Overview

ItalianCosplay database is organized into these main domains:

```
Users
Events
Content
Media
Anime Database
Location
Permissions
Future Commercial Modules
```

---

## Events Domain

Events are the main entity of ItalianCosplay.

The system uses two levels:

```
EventMaster
  └── EventEdition
```

### EventMaster

Table: `events_master`

Purpose: represents the permanent identity of an event.

Example: `Lucca Comics & Games` → `events_master`

Contains: event name, official website, social profiles, permanent information.

### EventEdition

Table: `events`

Purpose: represents a specific edition/year.

Example:

```
Lucca Comics & Games
  ├── 2025 edition
  ├── 2026 edition
  └── 2027 edition
```

Contains: dates, location, approval status, ranking, views, edition specific information.

### Event Rules

Do not duplicate recurring information.

**Wrong:**

```
events
├── website
├── facebook
└── instagram
```

...duplicated every year.

**Correct:**

```
events_master
├── website
├── facebook
└── instagram

events
├── date
├── location
└── edition data
```

### Event Related Tables

**Images:** `entity_images` with `entity_type = event`

**Guests:** `event_guests`

```
events
  └── event_guests
        └── guests
```

**Views:** `event_views` — used for popularity, ranking, analytics.

### Event Ranking

Events contain: `final_score`, `trending_score`, `event_size`, `last_score_update`.

These fields are used for: feed ordering, trending events, visibility ranking.

---

## Blog Domain

Main table: `blog_posts`

Current fields include:

```
titolo
contenuto
categoria_id
slug
excerpt
meta_title
meta_description
published_at
status
```

### Blog Refactoring Direction

The target naming is English.

| Current | Future |
|---|---|
| `titolo` | `title` |
| `contenuto` | `content` |
| `categoria_id` | `category_id` |

### Blog SEO Rules

Do not remove: `slug`, `meta_title`, `meta_description`, `published_at`, `status`.

Blog content is a major SEO asset.

### Blog Relations

Blog posts can connect with: `blog_categories`, `blog_post_tags`, `blog_post_views`, `events`.

---

## Media System

ItalianCosplay currently uses `entity_images` as the main image association system.

### Entity Images Structure

```
entity_images
├── entity_type
├── entity_id
├── file_name
├── path
├── preset
├── width
├── height
├── file_size
└── is_primary
```

### Supported Entity Types

**Current:** `event`, `blog_post`, `guest`

**Future possible:** `user`, `advertiser`, `marketplace_item`

### Media Rules

Use `entity_images` for existing image workflows.

Do not replace this system without an explicit migration plan. New image requirements must first evaluate if the existing system can be extended.

---

## Location System

Geographical structure:

```
regioni
  └── province
        └── comuni
```

Events use: `regione_id`, `provincia_id`, `comune_id`.

### Location Rules

Do not duplicate geographical data inside entities. Use relations.

---

## Users Domain

Main table: `users`

Contains: authentication, profile, role information, preferences.

### Permission System

Tables: `users`, `user_roles`, `permissions`, `role_permissions`

Architecture:

```
User
  └── Role
        └── Permissions
```

### User Cosplay System

Table: `user_cosplays`

```
users
  └── user_cosplays
        └── characters
```

---

## Anime Database

Tables: `franchise_types`, `franchises`, `characters`, `character_aliases`

External synchronization: `anilist_anime`, `anilist_characters`, `anilist_anime_character`, `anilist_sync`

---

## Database Charset Rules

Existing database contains different collations.

**Legacy examples:** `utf8mb4_general_ci`, `utf8mb3`

**New tables must use:** `utf8mb4` with `utf8mb4_0900_ai_ci`

---

## Future Modules

The database will expand with:

### Advertising

Future tables: `ad_campaigns`, `ad_positions`, `ad_banners`, `ad_clicks`, `ad_impressions`, `ad_payments`

### Notifications

Future tables: `notifications`, `notification_preferences`, `notification_logs`

### Marketplace

Future tables: `marketplace_items`, `seller_profiles`, `orders`, `payments`

### Calendar

Future tables: `calendar_events`, `user_event_favorites`, `reminders`

---

## Before Creating a New Table

Always ask:

1. Does this entity already exist?
2. Can an existing table be extended?
3. Is a migration required?
4. Does this impact SEO?
5. Does this impact GDPR?
6. Does this impact future monetization?

---

## Database Design Philosophy

ItalianCosplay database must support:

- large event catalog
- SEO growth
- user community
- advertising business
- future marketplace
- scalable services

**Prefer:**

- simple structures
- clear relations
- migration based evolution
- backward compatibility

**Avoid:**

- duplicated data
- unnecessary tables
- premature complexity
