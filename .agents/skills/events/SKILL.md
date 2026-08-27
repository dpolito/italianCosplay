---
name: events
description: Event domain rules for ItalianCosplay.it. Use when creating, modifying or managing cosplay events, event pages, event workflows, locations, images, rankings, SEO pages and event-related features.
---

# ItalianCosplay Events Skill

## Purpose

This skill defines all rules related to the event domain of ItalianCosplay.it.

Events are the primary entity of the platform. An event is not only database data. An event represents:

- an SEO landing page
- a discovery experience
- a community resource
- a potential commercial opportunity

Every event feature must consider:

- user experience
- SEO
- data quality
- scalability
- future monetization

---

## Event Domain Principles

Before creating event functionality evaluate:

1. Does it improve event discovery?
2. Does it improve data quality?
3. Does it improve SEO visibility?
4. Does it help organizers or users?
5. Can it support future commercial features?

Do not create features that only add technical complexity.

---

## Event Entity

The main event entity represents a cosplay event.

An event can contain:

- title
- slug
- description
- dates
- location
- organizer
- website
- social links
- images
- event type
- approval state
- SEO metadata
- statistics

---

## Event Lifecycle

Events should have a controlled lifecycle.

```
Draft → Pending Review → Approved → Published → Completed → Archived
```

The exact implementation may evolve. The important principle: an event must have a clear status.

### Event Status

Status should represent business state.

```
draft
pending
approved
rejected
published
archived
```

Avoid using ambiguous boolean fields for complex workflows.

**Bad:** using `is_active` for everything.

---

## Event Dates

Events are time-based content.

Support:

- start date
- end date
- optional opening hours
- future editions

Validate:

- end date cannot be before start date
- dates must be realistic
- expired events must remain historically available

---

## Event Editions

Recurring events should be handled carefully.

```
Lucca Comics & Games
  ├── 2025 edition
  ├── 2026 edition
  └── 2027 edition
```

Do not duplicate the event identity unnecessarily. Consider separating event master from event edition.

### Event Master Concept

When useful:

```
events_master
  └── event_editions
```

**Master contains:** permanent identity, organizer, official website, brand information.

**Edition contains:** year, dates, specific information, images, statistics.

---

## Event Slug

Every public event requires an SEO-friendly slug.

Rules: lowercase, readable, stable, unique.

**Good:** `lucca-comics-games-2026`

**Avoid:** `event-123`

### Event URL

Public URLs should be descriptive.

**Preferred:** `/eventi-cosplay/lucca-comics-games-2026`

**Avoid:** `/event?id=123`

---

## Event Description

Descriptions must provide real value.

Include when available:

- event overview
- activities
- cosplay information
- contests
- guests
- useful visitor information

Avoid: copied organizer text, empty descriptions, generic placeholders.

---

## Event Images

Images are a core part of event discovery.

Use a dedicated relationship:

```
events
  └── event_images
```

Do not store multiple images in one database field.

---

## Event Location

Location is a fundamental part of event discovery.

Events should support geographic information. Required concepts:

- region
- province
- municipality
- address when available
- latitude
- longitude

### Geographic Structure

Use normalized geographic data.

```
Region
  └── Province
        └── Municipality
              └── Event
```

Avoid storing only free text locations.

**Bad:**

```
Toscana, Firenze, Italia
```

**Good:**

```
region_id
province_id
municipality_id
```

### Location SEO

Geographic data enables SEO landing pages.

Examples:

```
/eventi-cosplay/toscana
/eventi-cosplay/firenze
/eventi-cosplay/lombardia
```

Only create pages with real value. Avoid empty geographic archives.

### Coordinates

Events can contain latitude and longitude.

Coordinates are useful for maps, proximity searches, route features, future recommendations.

Validate coordinates before storing.

### Map Integration

Maps should improve user experience. Possible uses: event location preview, nearby events, directions.

Do not make maps mandatory when data is unavailable.

---

## Event Categories

Events should support classification.

Examples:

- Comic Convention
- Anime & Manga
- Gaming
- Fantasy
- Local Cosplay Meeting
- Competition
- Festival

Categories should help navigation, filtering, SEO, and recommendations.

### Event Features

Events can contain additional attributes.

Examples: `has_cosplay_contest`, `has_guests`, `is_paid`, `event_size`.

Use structured fields instead of only text.

---

## Event Organizer

Organizer information is valuable.

Consider: organizer name, website, social profiles, contact information when allowed.

Avoid duplicating organizer data for every edition when possible.

### Social Links

Support official social profiles: Facebook, Instagram, TikTok, X, YouTube.

Validate URLs. Do not store arbitrary unsafe links.

### Event Website

The official website is an important reference.

Rules: store canonical URL, validate format, display clearly.

---

## Event Schema.org

Public event pages must support `Event` Schema.org markup.

Include: `name`, `description`, `startDate`, `endDate`, `location`, `image`, `url`, `organizer`.

### Event Schema Rules

Structured data must:

- match visible information
- use valid dates
- use real locations
- avoid fake values

Never create Schema.org data only for ranking purposes.

---

## Event SEO Page

An event page should be a complete landing page.

Recommended sections:

1. Title
2. Hero image
3. Event information
4. Description
5. Location
6. Dates
7. Gallery
8. Social links
9. Official website
10. Related events
11. Related blog articles

---

## Event Ranking System

Events can use ranking algorithms to improve discovery.

Ranking can consider: popularity, views, freshness, completeness, event importance.

### Ranking Principles

Ranking must:

- improve user discovery
- avoid manipulation
- remain explainable

Do not create hidden ranking factors that cannot be maintained.

### Event Score Example

Possible factors:

```
base_score
+ views
+ recent activity
+ event size
+ content completeness
```

The algorithm can evolve.

---

## Event Feed

The platform can generate dynamic feeds.

Examples: `/eventi-cosplay-weekend`, `/eventi-cosplay-mese`

Feeds should prioritize relevance, geographic diversity, freshness.

### Feed Diversification

Avoid showing only the biggest events.

Consider: different regions, different event types, smaller local events.

The goal is discovery.

---

## Event Statistics

Useful metrics: page views, unique visitors, clicks, favorites, calendar additions.

Statistics should support ranking, analytics, and future advertising products.

---

## Event Administration

Event management must follow backoffice standards.

Administrative operations: create, edit, review, approve, reject, archive.

Use: `EventController`, `EventService`, `EventRepository` / `Model`.

Do not put event business logic directly inside controllers.

---

## Event Approval Workflow

Events may come from administrators, organizers, community submissions, or external sources.

All non-trusted sources should follow a review workflow.

```
Submitted → Pending Review → Approved → Published
```

---

## Event Validation Before Publishing

Before publishing verify:

### Basic Information

- title exists
- description is meaningful
- dates are valid
- location is complete

### SEO

- slug exists
- metadata is available
- canonical URL is correct

### Media

- image exists when possible
- images are optimized

---

## Event Editing

Editing an event should preserve SEO value.

Consider: slug changes, redirects, historical URLs, indexed pages.

Do not change public URLs without evaluating SEO impact.

---

## Event Deletion

Avoid permanent deletion unless necessary.

Prefer: soft delete, archive, hidden status.

Historical event information can have SEO value.

---

## Event Import and Scraping

External event imports must be treated as untrusted data.

Before saving:

- validate fields
- normalize data
- check duplicates
- verify dates
- verify location

### Duplicate Detection

Avoid duplicate events.

Possible matching criteria: similar title, same dates, same location, same organizer.

Duplicates should be reviewed.

### Event Scraper Rules

Scrapers should:

- respect external websites
- handle failures
- log errors
- avoid importing bad data automatically

Imported data should not bypass validation.

---

## Event Images Processing

Images should use the centralized image service.

Processing pipeline:

```
Upload → Validation → Resize → WebP conversion → Generate variants → Storage
```

Possible variants: `thumb`, `medium`, `large`.

### Event Gallery

Gallery management should support: multiple images, ordering, primary image, deletion.

The primary image is used for: cards, social sharing, Schema.org.

---

## Personal Calendar

Future users may save events.

Possible features: add to calendar, remove from calendar, upcoming events reminder.

Calendar actions require authentication.

### User Interaction

Possible future features: favorites, attendance indication, notifications, reviews.

Every interaction should consider privacy, abuse prevention, moderation.

---

## Related Content

Events should connect with other content.

```
Lucca Comics 2026
  ├── Related events
  ├── Cosplay guides
  ├── Blog articles
  └── Organizer content
```

### Event and Blog Integration

Blog content can increase event visibility.

Example: a blog article "Come prepararsi a Lucca Comics" links to the Lucca Comics event page. The event page links back to cosplay guides and related news.

---

## Event Monetization Opportunities

Events can support future business features.

Possible opportunities: featured events, sponsored visibility, organizer profiles, promoted listings, advertising placements.

Commercial features must not damage user experience.

---

## Event Performance Considerations

Event pages must remain fast.

Consider: optimized images, cached data, efficient queries, pagination for galleries, lazy loading.

---

## Event Checklist

Before completing an event feature verify:

### Data

- Is event information structured?
- Are locations normalized?
- Are dates validated?

### SEO

- Is the URL friendly?
- Is Schema.org correct?
- Are related contents linked?

### Security

- Are permissions checked?
- Are uploads validated?
- Are inputs sanitized?

### UX

- Is discovery easy?
- Is mobile experience good?

### Business

- Can this support future visibility products?

---

## Final Event Philosophy

Events are the heart of ItalianCosplay.it.

Every event should become:

- a useful resource for cosplayers
- a quality landing page for search engines
- a connection point between community and organizers
- a potential business asset

Do not treat events as simple database records. Treat them as the main content product of the platform.
