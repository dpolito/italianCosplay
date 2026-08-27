---
name: advertising
description: Advertising and monetization rules for ItalianCosplay.it. Use when creating banner systems, sponsored content, campaigns, advertiser management, impressions tracking, clicks, payments and commercial features.
---

# ItalianCosplay Advertising Skill

## Purpose

This skill defines the advertising and monetization architecture of ItalianCosplay.it.

Advertising is a strategic business component of the platform. The goal is not only displaying banners. The goal is creating a professional visibility platform for:

- event organizers
- cosplay brands
- creators
- companies
- partners

---

## Business Philosophy

ItalianCosplay is a vertical portal. Advertising should create value for:

**Users** — by showing relevant products, useful events, quality partners.

**Advertisers** — by providing visibility, targeted audience, measurable results.

**Platform** — by generating revenue, sustainable growth, commercial opportunities.

---

## Advertising Principles

Every advertising feature must consider:

1. User experience.
2. Performance.
3. Transparency.
4. Measurement.
5. Scalability.
6. Future business models.

Avoid intrusive advertising.

---

## Advertising Architecture

The advertising system should be separated from the public content system.

Recommended domain concepts:

```
Advertiser → Campaign → Banner / Creative → Placement → Impressions → Clicks → Report
```

---

## Main Entities

### Advertiser

Represents a commercial customer: company, organizer, creator, or partner.

Possible data: company name, contact information, billing information, account status.

### Campaign

Represents an advertising order.

Possible fields: advertiser, name, start date, end date, budget, status, target placement.

### Banner / Creative

Represents the advertising material.

Possible fields: image, title, destination URL, dimensions, status.

### Placement

Defines where advertising appears.

Examples: homepage, event pages, blog articles, regional pages.

---

## Campaign Lifecycle

Campaigns should have explicit states.

```
Draft → Pending Approval → Paid → Active → Completed → Archived
```

Do not manage campaigns only with boolean fields.

**Bad:**

```sql
is_active
```

...for all states.

### Advertising Status

Statuses should represent business reality.

```
draft
pending_review
waiting_payment
active
paused
expired
rejected
```

---

## Advertising and Content Separation

Advertising must not compromise editorial quality.

Sponsored content should be clearly identified, transparent, useful.

Never create hidden advertising disguised as independent editorial content.

---

## Advertising Placements

Placements define where advertisements can appear. A placement should be a managed entity.

Examples:

```
homepage_top
homepage_middle
event_sidebar
event_featured
blog_top
blog_inline
regional_page_banner
```

### Placement Rules

Each placement should define: name, position, dimensions, visibility rules, availability, pricing model.

Avoid hardcoding banner positions inside templates.

**Bad:**

```php
if ($page == 'event') {
    showBanner();
}
```

**Good:**

```php
$advertisingService->getPlacement('event_sidebar');
```

---

## Banner Management

Banners should be managed independently.

A banner can have: image, title, alt text, target URL, campaign, status, expiration.

### Banner Validation

Uploaded advertising material must follow security rules.

Validate: file type, dimensions, size, destination URL.

Use the existing image processing system. Do not duplicate upload logic.

### Banner Formats

Support predefined formats: `728x90`, `300x250`, `300x600`, `970x250`.

Avoid allowing unlimited custom formats without validation.

### Responsive Advertising

Advertising must work on mobile devices.

Consider: responsive images, mobile-specific creatives, layout stability.

Avoid advertisements causing layout shifts.

---

## Advertising Targeting

Targeting can improve value.

**Geographic** — region, province, municipality. Useful for local events, regional businesses.

**Content Based** — e.g. show cosplay materials ads on costume guides / makeup articles; show event promotions on event pages.

**Audience Based** — future possibilities: user interests, saved events, preferences. Always respect privacy rules.

---

## Impression Tracking

Every banner display can generate an impression.

Track: campaign, banner, placement, timestamp, page context.

### Impression Rules

Avoid counting: automatic reload loops, bots when possible, repeated artificial refreshes.

Statistics must represent real visibility.

---

## Click Tracking

Clicks should be tracked separately.

Track: campaign, banner, placement, timestamp, destination.

### Click Security

Protect tracking endpoints.

Consider: validation, abuse prevention, bot filtering.

Do not allow fake clicks to manipulate reports.

---

## Statistics Model

Advertising statistics should support: daily aggregation, campaign reports, historical analysis.

Possible data: impressions, clicks, CTR, views, conversion metrics.

### CTR Calculation

```
CTR = clicks / impressions * 100
```

Example: 1000 impressions, 20 clicks → CTR = 2%

---

## Reporting

Advertisers need understandable reports.

Reports can include: campaign period, impressions, clicks, CTR, best placements, geographic performance.

### Analytics Architecture

Avoid calculating everything from raw events every time.

Consider: aggregation tables, scheduled jobs, caching.

### Cron Jobs

Background tasks can manage campaign activation, campaign expiration, statistics aggregation, report generation.

```
Daily cron → Update advertising statistics
```

---

## Advertising Orders

Advertising purchases should be treated as commercial orders. An order represents a customer's request for visibility.

Possible flow:

```
Customer → Select advertising package → Create order → Payment → Campaign activation
```

### Advertising Packages

The system may support predefined packages:

```
Basic Visibility
Featured Event
Homepage Promotion
Premium Campaign
Sponsored Article
```

Packages should be configurable. Avoid hardcoding prices inside controllers.

### Pricing System

Pricing should be managed separately.

Possible factors: placement, duration, expected impressions, seasonality, event importance.

Example:

```
Homepage banner + 30 days + high traffic period = calculated price
```

---

## Commercial Workflow

Recommended lifecycle:

```
Quote / Selection → Order Created → Payment Pending → Payment Confirmed → Campaign Active → Campaign Completed → Report Available
```

---

## Payment Integration

Payment systems must be isolated.

Possible providers: PayPal, Stripe, other payment gateways.

Do not mix payment logic with advertising logic.

### Payment Security

Never store: credit card numbers, CVV, payment credentials.

Store only: transaction ID, provider, status, timestamps.

### Payment Status

Use explicit states:

```
pending
processing
paid
failed
refunded
cancelled
```

---

## Invoice and Billing Data

Billing information requires protection.

Possible data: company name, VAT number, address, billing contacts.

Consider: encryption where appropriate, restricted access, GDPR requirements.

---

## Advertiser Account

Advertisers may have dedicated accounts.

Possible features: company profile, campaigns, invoices, reports, payments.

### Advertiser Dashboard

A future advertiser area can include:

**Overview** — active campaigns, total impressions, total clicks, CTR, remaining time.

**Campaign Management** — advertisers may view campaigns, upload creatives, download reports, request new promotions.

### Advertiser Permissions

Advertisers should only access their own data.

Never expose: other customers' campaigns, commercial statistics, internal pricing.

---

## Commercial Data Security

Protect: campaign budgets, revenue data, customer information, reports.

Use: authentication, authorization, audit logs.

---

## Advertising Admin Backoffice

Administrators need tools for: campaign approval, banner review, customer management, reporting, payments.

### Advertising Approval

Commercial content may require review.

Check: image quality, destination URL, compliance, relevance.

### Sponsored Content

Sponsored articles or promotions must be transparent. Include appropriate labeling.

Examples: `Sponsored content`, `Partner article`, `Advertisement`.

---

## Advertising and SEO

Advertising must not damage SEO.

Avoid: excessive ads above content, intrusive layouts, slow loading banners, hidden sponsored links.

### Advertising Performance

Monitor: page speed, layout stability, user engagement.

Advertising revenue should not destroy organic growth.

---

## Advertising Database Design

Advertising data should be separated into dedicated tables.

Possible structure:

```
advertisers
  └── advertising_campaigns
        └── advertising_banners
              └── advertising_placements
                    └── advertising_impressions
                    └── advertising_clicks
```

The exact schema can evolve. The important principle: advertising must have its own domain model.

### Suggested Entities

**Advertiser** — `id`, `company_name`, contact information, `status`, `created_at`.

**Campaign** — `advertiser_id`, `name`, `start_date`, `end_date`, `budget`, `status`.

**Banner** — `campaign_id`, `image`, `title`, `target_url`, `status`.

**Placement** — `name`, `page_type`, `position`, `dimensions`, `pricing`.

**Impression** — `campaign_id`, `banner_id`, `placement_id`, `date`, `metadata`.

**Click** — `campaign_id`, `banner_id`, `placement_id`, `date`, `metadata`.

---

## Advertising Services

Business logic should use dedicated services.

Examples:

- `AdvertisingCampaignService`
- `AdvertisingBannerService`
- `AdvertisingTrackingService`
- `AdvertisingReportService`
- `AdvertisingPricingService`

Avoid putting advertising logic inside controllers, views, or event models.

---

## Event Integration

Events are a primary advertising opportunity.

Possible placements: featured events, sponsored visibility, organizer promotions, regional highlights.

```
Event Page → Advertising Placement → Relevant Campaign
```

---

## Blog Integration

Blog content can support advertising.

Examples: sponsored guides, partner articles, relevant banners.

Advertising must remain contextually relevant.

---

## API Considerations

Future APIs may support advertiser dashboards, mobile apps, external integrations.

API responses must respect authentication, permissions, data privacy.

---

## Advertising Caching

High traffic advertising areas should consider caching.

Examples: active campaigns, banner selection, placement configuration.

Avoid querying all campaigns on every page request.

### Rotation System

Multiple campaigns may compete for the same placement.

A rotation system can consider: priority, expiration, campaign status, targeting, frequency limits.

### Frequency Control

Avoid showing the same advertisement excessively.

Possible controls: impressions per user, daily limits, rotation rules.

---

## Fraud Prevention

Advertising statistics should consider abuse prevention.

Possible protections: bot filtering, duplicate click detection, abnormal traffic detection.

Do not allow statistics manipulation.

---

## Advertising Testing

Before publishing campaigns verify:

### Technical

- Banner loads correctly.
- Links work.
- Tracking works.
- Images are optimized.

### Business

- Campaign dates are correct.
- Placement is correct.
- Customer data is protected.

### User Experience

- Advertising is not intrusive.
- Pages remain fast.
- Content remains readable.

---

## Advertising Checklist

Before completing an advertising feature verify:

### Architecture

- Is the advertising domain separated?
- Are services used correctly?

### Security

- Are permissions checked?
- Are payments protected?
- Are customer data protected?

### Tracking

- Are impressions counted correctly?
- Are clicks validated?
- Are reports accurate?

### UX

- Are ads useful?
- Are layouts stable?
- Is mobile experience good?

### SEO

- Does advertising preserve organic visibility?
- Are sponsored contents transparent?

### Business

- Can this scale?
- Can it support future products?

---

## Final Advertising Philosophy

ItalianCosplay advertising should become a professional visibility platform.

The goal is not selling empty banner space. The goal is connecting cosplay community, event organizers, brands, creators, companies.

A successful advertising system provides:

- measurable value
- transparent results
- sustainable revenue
- a better ecosystem for everyone

Advertising is not an external feature. It is part of the platform strategy.
