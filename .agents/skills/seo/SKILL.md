---
name: seo
description: SEO rules for ItalianCosplay.it. Use when creating pages, events, blog posts, URLs, metadata, structured data, sitemaps and content optimization.
---

# ItalianCosplay SEO Skill

## Purpose

This skill defines SEO standards for ItalianCosplay.it.

SEO is a core product requirement. Every public page must be designed to:

- rank in search engines
- provide useful information
- improve organic traffic
- increase user engagement
- support business growth

SEO decisions must consider:

- search intent
- user experience
- content quality
- technical optimization
- internal linking
- structured data

---

## SEO First Approach

Before creating a public feature evaluate:

1. Can this page receive organic traffic?
2. What search intent does it satisfy?
3. What keywords are relevant?
4. How does it connect with existing content?
5. Does it improve the portal ecosystem?

Do not create indexable pages without a clear SEO purpose.

---

## URL Structure

URLs must be:

- readable
- stable
- descriptive
- SEO friendly

Use lowercase, hyphens, meaningful words.

**Good:**

```
/eventi-cosplay/lucca-comics-2026
/blog/come-realizzare-un-costume-cosplay
```

**Avoid:**

```
/event?id=123
/page.php?id=456
```

---

## Slug Rules

Slugs must:

- contain relevant keywords
- avoid unnecessary words
- remain stable over time

Example — title: `Lucca Comics & Games 2026`

**Good slug:**

```
lucca-comics-games-2026
```

**Avoid:**

```
lucca-comics-games-edizione-2026-super-evento-toscana
```

---

## Canonical URLs

Every indexable page must define a canonical URL.

Canonical prevents:

- duplicate content
- parameter duplication
- pagination confusion

Example:

```html
<link rel="canonical" href="https://www.italiancosplay.it/eventi-cosplay/lucca-comics-2026">
```

---

## Meta Title

Every page must have a unique title.

Recommended:

- around 50-60 characters
- include primary keyword
- include brand when useful

**Examples:**

- Event: `Lucca Comics & Games 2026: evento cosplay e programma`
- Blog: `Come iniziare a fare cosplay: guida completa`

Avoid generic titles: `Home`, `Evento`, `Articolo`.

---

## Meta Description

Every indexable page must have a unique meta description.

Requirements:

- describe page content
- include relevant keywords naturally
- encourage clicks
- avoid keyword stuffing

Recommended length: **140-160 characters**

Example:

> Scopri Lucca Comics & Games 2026: date, ospiti, cosplay, contest e tutte le informazioni utili per partecipare.

---

## Open Graph

Public pages should include Open Graph metadata.

Required:

```html
<meta property="og:title">
<meta property="og:description">
<meta property="og:image">
<meta property="og:url">
<meta property="og:type">
```

Use optimized images.

---

## Structured Data

Structured data is mandatory where appropriate.

Use Schema.org markup to help search engines understand content.

Structured data must:

- match visible page content
- contain valid information
- be updated dynamically
- avoid fake or incomplete data

Never add structured data only to manipulate search results.

### Event Schema

Event pages should use the `Event` Schema.org type.

Include when available:

- `name`
- `description`
- `startDate`
- `endDate`
- `location`
- `image`
- `url`
- `organizer`
- `offers`
- `eventStatus`

Example structure:

```json
{
  "@context": "https://schema.org",
  "@type": "Event",
  "name": "Lucca Comics & Games 2026",
  "startDate": "2026-10-28",
  "endDate": "2026-11-01"
}
```

Only include information actually available.

### Blog Schema

Blog articles should use `BlogPosting`.

Include:

- `headline`
- `description`
- `image`
- `author`
- `datePublished`
- `dateModified`
- `mainEntityOfPage`

Example:

```json
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "Come iniziare a fare cosplay",
  "datePublished": "2026-01-01"
}
```

### Breadcrumb Schema

Use breadcrumbs for hierarchical pages.

**Event:**

```
Home > Eventi Cosplay > Toscana > Lucca Comics 2026
```

**Blog:**

```
Home > Blog > Guide Cosplay > Articolo
```

Use `BreadcrumbList`.

### Organization Schema

The website should provide organization information.

Use `Organization` for:

- brand identity
- logo
- official website
- social profiles

---

## Image SEO

Images are important for ItalianCosplay.

Every important image should have:

- descriptive filename
- alt attribute
- optimized format
- correct dimensions

**Good:** `lucca-comics-cosplay-2026.jpg`

**Avoid:** `IMG_12345.jpg`

### Image Optimization

Images should use:

- WebP when possible
- responsive sizes
- compression
- lazy loading

Avoid loading large images unnecessarily.

---

## Sitemap

The sitemap must include important public URLs.

Consider:

- event pages
- blog articles
- category pages
- regional pages
- geographic landing pages

Do not include:

- admin pages
- private pages
- duplicate URLs
- noindex pages

### Sitemap Strategy

For large content volumes prefer separated sitemaps.

Example:

```
sitemap.xml
sitemap-events.xml
sitemap-blog.xml
```

Each sitemap should remain manageable.

---

## Robots.txt

`robots.txt` must:

- allow important content
- block private areas
- reference sitemap

Example:

```
User-agent: *
Allow: /

Disallow: /admin/

Sitemap: https://www.italiancosplay.it/sitemap.xml
```

---

## Indexing Rules

Index:

- valuable event pages
- useful blog content
- relevant category pages
- location pages with real content

Avoid indexing:

- empty pages
- search results
- internal filters
- duplicate pages
- temporary pages

### Pagination SEO

Pagination must be handled carefully. Avoid creating thousands of thin pages.

Consider:

- canonical strategy
- useful navigation
- sufficient content

### Thin Content Prevention

Do not create pages with:

- only a title
- duplicated descriptions
- automatic text without value

Every indexable page should provide useful information.

---

## Event SEO Rules

Event pages are strategic landing pages.

Each event page should include:

- unique description
- dates
- location
- images
- useful information
- related events
- related articles

Avoid duplicate descriptions copied from organizers.

---

## Location SEO

Geographic pages can generate organic traffic.

Examples:

```
/eventi-cosplay/toscana
/eventi-cosplay/firenze
/eventi-cosplay/lombardia
```

Only create location pages when they contain useful content. Avoid empty regional pages.

---

## Blog SEO Strategy

The blog is a primary organic growth channel.

Every article must consider:

- search intent
- keyword relevance
- content quality
- internal linking
- user engagement
- conversion opportunities

The blog should support the entire ItalianCosplay ecosystem.

Example — a cosplay tutorial can link to:

- related events
- cosplay categories
- relevant guides
- marketplace services in the future

---

## Content Structure

Blog articles should use a clear hierarchy.

```html
<h1>Main title</h1>
<h2>Main sections</h2>
<h3>Subsections</h3>
```

Rules:

- only one H1
- logical heading order
- headings must describe content

Avoid headings created only for keywords.

---

## Internal Linking

Internal linking is mandatory. Every important page should connect to related content.

Consider links between:

- events
- blog articles
- categories
- locations
- guides

**Event page:**

```
Event
 ├── related events
 ├── cosplay guides
 └── blog articles
```

**Blog article:**

```
Article
 ├── related events
 ├── categories
 └── other guides
```

### Anchor Text Rules

Use descriptive anchor text.

**Good:** `Scopri gli eventi cosplay in Toscana`

**Avoid:** `clicca qui`

Internal links should help both users and search engines.

---

## FAQ SEO

FAQ sections can be used when useful.

Use FAQ schema only when:

- questions are visible on the page
- answers are real
- content helps users

Do not create artificial FAQs only for SEO.

---

## Core Web Vitals

SEO includes performance.

**Largest Contentful Paint (LCP)** — optimize:

- images
- server response
- critical resources

**Cumulative Layout Shift (CLS)** — avoid:

- images without dimensions
- dynamic content shifting
- unstable layouts

**Interaction to Next Paint (INP)** — optimize:

- JavaScript execution
- unnecessary listeners
- heavy scripts

---

## Frontend SEO Requirements

HTML must be:

- semantic
- accessible
- crawlable

Use:

- proper headings
- alt attributes
- meaningful links
- structured content

Avoid:

- important content only inside JavaScript
- hidden SEO text
- keyword stuffing

---

## Search Engine Guidelines

Never use:

- hidden text
- artificial keyword repetition
- doorway pages
- copied content
- misleading structured data

SEO must improve user experience.

---

## AI Generated Content Rules

AI can help create content, but content must be reviewed.

Generated content should be:

- accurate
- useful
- unique
- aligned with ItalianCosplay users

Avoid:

- mass publishing low-quality articles
- duplicate AI content
- generic descriptions

Quality is more important than publishing frequency.

---

## SEO Monitoring

Important metrics:

- organic traffic
- impressions
- clicks
- CTR
- ranking positions
- indexed pages
- crawl errors

Use data to improve content strategy.

### Search Console Considerations

When creating new content evaluate:

- indexing possibility
- canonical correctness
- structured data validity
- coverage issues

Errors should be investigated, not ignored.

---

## SEO Checklist

Before publishing a page verify:

### Technical SEO

- Is the URL SEO friendly?
- Is canonical correct?
- Are metadata fields populated?
- Is Schema.org valid?

### Content SEO

- Does the page satisfy search intent?
- Is content unique?
- Are headings structured?
- Are internal links present?

### Performance

- Are images optimized?
- Is loading fast?
- Is mobile experience good?

### Business

- Can this page generate traffic?
- Can it support other ItalianCosplay services?
- Can it contribute to future monetization?

---

## Final SEO Philosophy

ItalianCosplay should become a trusted search destination for cosplay in Italy.

Every page should have a purpose:

- attract users
- answer questions
- connect content
- increase engagement
- support business growth

SEO is not only optimization. SEO is the foundation of the portal growth strategy.
