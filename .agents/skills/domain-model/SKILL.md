---
name: italiancosplay-domain-model
description: Domain model for ItalianCosplay.it. Use this skill to understand the business entities, relationships and responsibilities before implementing Models, Services, Controllers or database changes.
---

# ItalianCosplay Domain Model

## Purpose

This skill defines the business domain of ItalianCosplay.

Its goal is helping Codex understand:

- what every entity represents
- relationships between entities
- ownership of data
- business responsibilities

Before creating Models, Services, Controllers, Repositories, or Migrations, always understand the business entity involved.

Never implement features without first identifying the correct domain entity.

---

## Domain Philosophy

ItalianCosplay is not simply an event calendar. It is a cosplay platform composed of multiple connected domains.

**Current domains:** Users, Events, Blog, Images, Guests, Anime Database, Locations

**Future domains:** Marketplace, Advertising, Notifications, Calendar, Sponsorships

Every feature belongs to one domain. Avoid mixing responsibilities.

---

## Aggregate Roots

The primary aggregate roots are:

```
User
EventMaster
EventEdition
BlogPost
Guest
Character
Franchise
```

These entities own their respective business logic.

---

## Domain Relationships

```
User
├── Cosplays
├── Favorites
├── Notifications
└── Profile
```

```
EventMaster
├── EventEdition
├── Images
├── Guests
└── Rankings
```

```
BlogPost
├── Category
├── Tags
├── Images
└── Related Events
```

```
Character
└── Franchise
```

---

## User Domain

Entity: `User`

Represents a registered member of the platform.

A user may:

- authenticate
- manage profile
- upload avatar
- receive notifications
- create future marketplace listings
- follow events
- manage favorite cosplay content

User is responsible only for personal information. User does not contain event logic.

### User Responsibilities

**Owns:** profile, authentication, password, avatar, preferences, permissions

**Does NOT own:** events, blog, advertising — those belong to different domains.

---

## Event Domain

ItalianCosplay separates:

```
Permanent Event → EventMaster
```

from

```
Specific Edition → EventEdition
```

This separation is mandatory.

### EventMaster

Entity: `EventMaster`

Represents the permanent identity of a recurring event.

Examples: Lucca Comics & Games, Romics, Etna Comics, Milan Games Week.

An EventMaster exists independently of a specific year.

#### EventMaster Responsibilities

**Owns:** official name, slug, official description, website, official social profiles, permanent branding

**Does NOT own:** dates, location, yearly statistics, approval status — those belong to EventEdition.

### EventEdition

Entity: `EventEdition`

Represents one specific edition of an event.

Examples:

```
Lucca Comics 2026 → EventEdition
Lucca Comics 2027 → EventEdition
```

Each edition contains information that changes over time.

#### EventEdition Responsibilities

**Owns:** year, start date, end date, city, province, region, municipality, coordinates, approval, ranking, statistics, contest availability, paid visibility

**Does NOT own:** official website, permanent branding, historical identity

---

## Event Lifecycle

Typical workflow:

```
EventMaster → Create EventEdition → Approval → Publication → Ranking Updates → Archive
```

Every edition follows its own lifecycle.

---

## Event Images

Images belong to the edition.

```
EventEdition → entity_images → entity_type = event
```

Each edition may contain: cover image, gallery, promotional images.

---

## Event Guests

Guests belong to the edition.

```
EventEdition → EventGuest → Guest
```

A guest may participate in multiple editions.

---

## Event Ranking

Ranking belongs only to EventEdition.

Ranking fields include: `final_score`, `trending_score`, `event_size`.

Ranking changes over time. It must never be stored inside EventMaster.

---

## Event Statistics

Statistics belong to EventEdition.

Examples: views, popularity, trending, click-through rate.

Historical editions keep their own statistics.

---

## Event Location

Location belongs to EventEdition.

```
Region → Province → Municipality → Coordinates
```

Do not duplicate geographical information. Always use existing location entities.

---

## Event SEO

SEO exists on two levels.

**EventMaster:** permanent identity, canonical information

**EventEdition:** yearly page, dates, structured data, local SEO

Both entities contribute to search visibility.

---

## Event Business Rules

Every recurring event should reuse the existing EventMaster.

**Never create:**

```
Lucca Comics 2026 → New EventMaster
```

**Correct approach:**

```
EventMaster → New EventEdition
```

This preserves: SEO authority, historical continuity, analytics, future relationships.

---

## Event Services

Typical services include:

```
EventService
RankingService
ImageService
GeocodingService
StatisticsService
```

Business logic belongs inside services. Models represent data. Controllers coordinate requests.

---

## Blog Domain

Entity: `BlogPost`

The blog is a strategic SEO asset. It is not only a news section.

Its objectives are:

- increase organic traffic
- educate the community
- support events
- create internal linking
- improve search visibility

### BlogPost Responsibilities

**Owns:** title, slug, excerpt, content, category, publication status, publication date, SEO metadata

**Does NOT own:** events, guests, franchises — it may reference them.

---

## Blog Categories

Entity: `BlogCategory`

Responsibilities: classify content, improve navigation, strengthen topical authority.

Categories are stable. Posts may change category.

---

## Blog Tags

Entity: `Tag`

Tags are lightweight relationships. Use them only when they improve: navigation, search, related content.

Avoid excessive tagging.

---

## Related Content

A BlogPost may reference: `EventEdition`, `Guest`, `Character`, `Franchise`.

Relationships improve: SEO, UX, internal linking.

Avoid duplicated information.

---

## Guest Domain

Entity: `Guest`

Represents people participating in events.

Examples: cosplay guests, judges, hosts, special guests.

A Guest exists independently of any event.

### Guest Responsibilities

**Owns:** name, slug, biography, avatar, social links, website

**Does NOT own:** events — relationships are managed through `EventGuest`.

---

## Character Domain

Entity: `Character`

Represents a cosplay character.

Examples: Monkey D. Luffy, Frieren, Marin Kitagawa.

A Character belongs to exactly one Franchise.

---

## Franchise Domain

Entity: `Franchise`

Represents the origin of characters.

Examples: One Piece, Naruto, Genshin Impact, Demon Slayer.

A Franchise owns many Characters.

---

## AniList Integration

AniList is an external provider.

Its purpose is: importing franchises, importing characters, reducing manual work.

AniList data is synchronized. Business logic must remain inside ItalianCosplay.

Never depend directly on AniList APIs during page rendering.

---

## Image Domain

Entity: `EntityImage`

ItalianCosplay uses a polymorphic image system.

Images are attached through `entity_type` and `entity_id`.

Supported entities currently include: `event`, `blog_post`, `guest`.

Future entities may be added without changing the overall architecture.

### Image Responsibilities

**An image owns:** file, dimensions, preset, alt text, primary flag

The business entity owns the relationship. Image processing belongs to `ImageService`.

---

## Location Domain

Entities: `Region`, `Province`, `Municipality`

Locations are shared resources. They should never contain event-specific logic.

Events reference locations. They do not duplicate them.

---

## Permission Domain

Entities: `Role`, `Permission`

A Role groups Permissions. A User receives one Role.

Business logic should verify permissions instead of hardcoding role names whenever possible.

---

## Future Domains

ItalianCosplay is designed to grow.

The domain model must support future modules without requiring major architectural changes.

Planned domains include:

```
Advertising
Marketplace
Notifications
Calendar
Tickets
Sponsors
Companies
```

Each new domain should remain independent while integrating naturally with existing entities.

### Advertising Domain

Future aggregate root: `AdvertisingCampaign`

Responsibilities: campaign configuration, advertiser, banner assignment, budget, visibility period, reporting.

Advertising is an independent business domain. It must never be coupled directly to Event or Blog models. Relationships should remain loose.

### Marketplace Domain

Future aggregate root: `MarketplaceItem`

Responsibilities: listing, seller, category, images, price, status.

Marketplace logic must remain isolated from user authentication logic.

### Notification Domain

Future aggregate root: `Notification`

Responsibilities: message, recipient, channel, delivery status, read status.

Notifications are generated by business events. They are not business events themselves.

### Calendar Domain

Future aggregate root: `CalendarEntry`

Examples: saved event, reminder, Google Calendar export, ICS export.

Calendar data belongs to the user. It does not modify the Event domain.

---

## Service Layer Philosophy

Business rules belong inside Services.

```
Controller → Service → Model → Database
```

Avoid placing business logic inside Controllers, Views, or database helpers.

---

## Model Responsibilities

Models represent business entities.

Models may: retrieve data, persist data, expose entity behavior.

Models should not contain: HTML, routing, presentation logic.

---

## Controller Responsibilities

Controllers coordinate requests.

Controllers: validate input, call Services, prepare responses, choose Views.

Controllers should remain thin.

---

## View Responsibilities

Views render data.

Views may contain: HTML, Tailwind CSS, minimal PHP rendering logic.

Views must never: execute SQL, call external APIs, implement business rules.

---

## Repository Philosophy

Repositories are optional.

Use them only when they simplify: complex queries, reusable database access, large aggregates.

Do not introduce repositories simply to follow a pattern. Avoid unnecessary abstraction.

---

## Dependency Direction

Always follow this dependency flow:

```
View → Controller → Service → Model / Repository → Database
```

Never reverse the dependency direction.

---

## Domain Evolution

When implementing new features:

1. Identify the domain.
2. Identify the aggregate root.
3. Identify the entity owner.
4. Identify relationships.
5. Identify affected services.
6. Evaluate SEO impact.
7. Evaluate performance.
8. Evaluate security.
9. Evaluate future monetization.

Only then write code.

---

## Domain Checklist

Before implementing a feature ask:

- Which domain owns this feature?
- Which entity is responsible?
- Does a similar entity already exist?
- Can an existing service be reused?
- Is the data duplicated?
- Is the architecture still coherent?
- Is SEO preserved?
- Is backward compatibility preserved?

---

## ItalianCosplay Domain Philosophy

ItalianCosplay is not just a website. It is a modular platform dedicated to the cosplay ecosystem.

Every module should integrate naturally with the others while remaining independent.

The architecture must support long-term growth, maintainability, SEO, performance and future commercial services.

Business rules must remain clear, entities must have well-defined responsibilities, and every implementation should strengthen the overall consistency of the platform instead of introducing isolated solutions.
