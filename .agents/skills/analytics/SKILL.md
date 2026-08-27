---
name: analytics
description: Analytics and statistics rules for ItalianCosplay.it. Use when implementing visitor tracking, event statistics, blog analytics, dashboards, reports, GDPR-aware tracking and business metrics.
---

# ItalianCosplay Analytics Skill

## Purpose

This skill defines rules for analytics and statistics systems inside ItalianCosplay.it.

The goal is not only counting visitors. Analytics must support:

- product decisions
- SEO optimization
- content strategy
- event visibility
- advertising reports
- business growth

---

## Analytics Philosophy

Statistics must answer business questions.

Examples:

- Which events attract more users?
- Which blog articles generate traffic?
- Which regions have more interest?
- Which advertising campaigns perform better?

Avoid collecting data without a purpose.

---

## Analytics Principles

Every analytics feature must consider:

1. Privacy.
2. GDPR compliance.
3. Data minimization.
4. Performance.
5. Accuracy.
6. Business value.

---

## First Party Analytics

ItalianCosplay should prefer first-party analytics.

Advantages: greater control, better GDPR management, integration with internal data, advertiser reporting.

---

## Data Categories

Separate different types of data.

**Traffic Data** — page views, sessions, visits, referrer, device information.

**Content Data** — event views, blog article views, search activity.

**Business Data** — campaign impressions, banner clicks, conversions.

---

## Tracking Architecture

Tracking should be centralized.

```
Visitor → Tracking Endpoint → Analytics Service → Database → Dashboard / Reports
```

---

## Tracking Service

Use a dedicated service: `AnalyticsService`.

Responsibilities: record views, identify content, aggregate statistics, generate reports.

### Do Not Track in Views

Avoid putting complex tracking logic directly in templates.

**Bad:**

```php
INSERT INTO statistics...
```

...inside views.

**Good:**

```
View → Tracking Component → AnalyticsService
```

---

## Page View Tracking

Track important public pages: event pages, blog articles, category pages, landing pages.

### Content Tracking

Important content should have measurable performance.

**Event:** `event_id`, `views`, `unique_views`

**Blog:** `post_id`, `views`, `unique_views`

### Internal Entity Statistics

Statistics related to entities should remain connected to entities.

```
events + event_statistics
```

...or aggregated fields when appropriate.

Avoid creating unnecessary generic tables for every metric.

---

## Performance Rules

Tracking must not slow down visitors.

Consider: asynchronous requests, batching, queues, aggregation.

Avoid expensive database writes on every request when traffic grows.

---

## Visitor Identification

Visitor identification must respect privacy. Avoid storing unnecessary personal data.

Possible identifiers: anonymous session ID, hashed identifiers, temporary tokens.

### Unique Visitors

Unique visitor calculation should be carefully designed.

Possible approaches: anonymous visitor identifier, session analysis, aggregated daily counters.

The chosen method must be documented.

---

## Sessions

A session represents a user's activity period.

Possible data: `session_id`, `start_time`, `last_activity`, `pages_viewed`, `referrer`.

### Session Rules

Sessions should:

- expire automatically
- avoid storing personal information
- support traffic analysis

---

## Referrer Tracking

Referrer data helps understand acquisition channels.

Possible sources: Direct, Search Engine, Social Network, External Website, Campaign.

### Search Traffic

When possible identify: search engine source, landing page, organic entry points.

Do not store sensitive search information.

### Campaign Tracking

Advertising and marketing campaigns can use tracking parameters: `utm_source`, `utm_medium`, `utm_campaign`.

Campaign tracking must integrate with advertising reports and analytics dashboards.

---

## Bot and Spam Filtering

Analytics should avoid polluted data.

Consider filtering: known bots, automated crawlers, suspicious traffic.

Do not block legitimate search engines.

### Traffic Quality

Raw visits are not enough.

Consider: engagement, duration, interactions, repeated visits.

---

## Event Analytics

Events require dedicated statistics.

Possible metrics: views, unique visitors, calendar additions, favorites, map interactions, external website clicks.

### Event Performance

Useful questions:

- Which events receive more attention?
- Which regions have more demand?
- Which events should receive promotion?

---

## Blog Analytics

Blog metrics: views, unique visitors, search impressions, clicks, related event clicks.

### Content Attribution

Understand how content contributes to goals.

```
Blog article → Event page visit → Calendar addition
```

---

## User Interaction Analytics

Track meaningful actions: save event, add calendar, click website, share content.

Avoid tracking every insignificant action.

---

## Privacy by Design

Analytics must follow privacy principles.

Collect only: necessary data, useful information, defined purposes.

### GDPR Considerations

Evaluate: legal basis, consent requirements, retention period, user rights.

### Cookie Strategy

If cookies are used, consider: consent management, blocking before consent when required, documentation.

### Cookie-less Analytics

Prefer when possible: anonymous tracking, server-side aggregation, minimal identifiers.

### Data Retention

Define retention periods.

**Short term:** raw events.

**Long term:** aggregated statistics.

Do not keep unlimited raw data without purpose.

---

## Analytics Database Design

Analytics data should be designed for scalability.

Separate: raw tracking data, aggregated statistics, business reports.

### Raw Analytics Data

Raw data contains individual events.

```
analytics_events
  ├── page_view
  ├── click
  ├── interaction
  └── session_event
```

Possible fields: `id`, `event_type`, `entity_type`, `entity_id`, `session_id`, `timestamp`, `metadata`.

### Aggregated Statistics

Do not calculate all statistics from raw data every time. Use aggregation tables.

Examples: `event_statistics_daily`, `blog_statistics_daily`, `advertising_statistics_daily`.

### Daily Aggregation

A scheduled process can transform raw data.

```
Raw Events → Daily Aggregation Job → Statistics Tables → Dashboards
```

### Statistics Tables

**Event Statistics:** `event_id`, `date`, `views`, `unique_views`, `clicks`, `calendar_additions`

**Blog Statistics:** `post_id`, `date`, `views`, `unique_views`, `organic_visits`

**Advertising Statistics:** `campaign_id`, `date`, `impressions`, `clicks`, `ctr`

### Indexing Requirements

Analytics tables can grow quickly. Always evaluate: indexes, partitioning, cleanup strategy.

Useful indexes: `entity_id`, `date`, `event_type`, `session_id`.

---

## Dashboard Analytics

Dashboards should show useful information. Avoid showing only vanity metrics.

### Admin Dashboard

Possible metrics: total users, total events, upcoming events, blog traffic, advertising performance.

### Event Organizer Reports

Future organizer reports can include: event page views, user interest, geographic audience, clicks to official website, calendar additions.

### Advertising Reports

Commercial customers need measurable results.

Reports can include: campaign, period, impressions, clicks, CTR, audience information.

### Report Generation

Reports should not slow down normal pages. Use background jobs, cached reports, scheduled generation.

### Export Functions

Future reports may support CSV, PDF, dashboards.

Exports must respect: permissions, privacy, data ownership.

---

## Cron Jobs

Analytics should use scheduled tasks.

**Daily:** aggregate statistics

**Weekly:** generate reports

**Monthly:** archive old data

### Real Time Statistics

Real-time dashboards should be introduced only when necessary.

Prefer near real-time, periodic updates. Avoid unnecessary infrastructure.

---

## Analytics Services

Use dedicated services:

- `AnalyticsService`
- `TrackingService`
- `StatisticsAggregationService`
- `ReportService`

### Analytics Separation

Do not mix analytics logic, advertising logic, and content logic.

**Wrong:**

```
EventController calculates statistics
```

**Correct:**

```
EventController → EventService → AnalyticsService
```

---

## Analytics API

Future APIs may expose statistics, reports, dashboards.

Protect with: authentication, authorization, rate limiting.

---

## SEO Analytics Integration

Analytics should support SEO decisions.

Useful information: landing pages, organic traffic, search performance, content growth.

### Search Performance

Monitor: impressions, clicks, CTR, average position, indexed pages.

Use this information to improve events, blog articles, category pages.

### Content SEO Analysis

Analytics can identify high performing articles, declining pages, missing content opportunities.

```
Article receives impressions → Low CTR → Improve title and description
```

### Event SEO Analysis

Events can be evaluated by search traffic, page views, user interactions.

Examples: most searched events, most visited regions, most requested categories.

---

## Core Web Vitals Monitoring

Performance affects user experience and SEO.

Monitor: Largest Contentful Paint, Interaction to Next Paint, Cumulative Layout Shift.

### Performance Analytics

Identify: slow pages, heavy resources, problematic images, slow queries.

---

## Technical Monitoring

Analytics can include technical metrics: errors, failed requests, slow responses, application warnings.

### Error Tracking

Important errors should be tracked: PHP exceptions, database failures, API errors.

Do not expose errors to users.

### Logging Strategy

Logs should include: timestamp, severity, context, technical information.

Avoid storing: passwords, private data, unnecessary personal information.

---

## Business Intelligence

Analytics should support strategic decisions.

**Content Strategy:** *"What topics generate traffic?"*

**Event Strategy:** *"Which events attract more users?"*

**Advertising Strategy:** *"Which placements provide more value?"*

**Product Strategy:** *"Which features are used most?"*

---

## Analytics Anti-Patterns

Avoid the following.

### Collecting Everything

More data does not always mean more value.

### Vanity Metrics

Example: "Total page views" without context.

### Slow Tracking

Analytics must not slow down the website.

### Mixing Domains

Do not put business logic inside tracking code.

---

## Analytics Checklist

Before completing an analytics feature verify:

### Privacy

- Is data collection justified?
- Is personal data minimized?
- Are retention rules defined?

### Architecture

- Is tracking centralized?
- Are services used correctly?

### Performance

- Does tracking affect page speed?
- Are writes optimized?

### Data Quality

- Are bots filtered?
- Are statistics meaningful?

### Business

- Can this produce useful insights?
- Can it support future reports?

---

## Final Analytics Philosophy

ItalianCosplay analytics should transform activity into knowledge.

The objective is not collecting numbers. The objective is understanding:

- the community
- event popularity
- content value
- commercial opportunities

A professional analytics system allows ItalianCosplay to grow based on real data.
