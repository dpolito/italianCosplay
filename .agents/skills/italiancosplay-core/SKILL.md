---
name: italiancosplay-core
description: Core development rules for ItalianCosplay.it. Use this skill for every task related to this project to understand architecture, coding standards, business goals, SEO requirements and development workflow.
---

# ItalianCosplay Core Skill

## Project Identity

ItalianCosplay.it is an Italian vertical portal dedicated to the cosplay ecosystem.

The platform provides services for cosplayers including:

- cosplay events directory
- editorial blog about cosplay
- community features
- future marketplace services
- commercial visibility and advertising solutions

The primary business goal is generating value through:

- organic traffic
- SEO visibility
- advertising sales
- sponsored visibility
- commercial services for companies, organizers and creators

Every technical decision must consider:

1. SEO impact
2. User experience
3. Performance
4. Security
5. Future monetization opportunities
6. Code maintainability

---

## Development Stack

### Backend

- PHP 8.2+
- Custom MVC OOP framework
- PDO database access
- Dependency Injection

### Database

- MySQL
- InnoDB
- UTF-8 compatible charset

### Frontend

- HTML
- Tailwind CSS
- Vanilla JavaScript

### Server

- Apache
- Docker environment

### Template Engine

Do not use Twig. Views must use native PHP templates.

---

## Application Architecture

Main structure:

```
app/
├── config/
├── Controllers/
├── Core/
├── Helpers/
├── Models/
├── Services/
├── Repositories/
├── Views/
└── Middleware/
```

Follow MVC principles.

---

## Mandatory Development Workflow

Before creating or modifying code:

1. Analyze the existing project structure.
2. Search for existing implementations.
3. Reuse existing code whenever possible.
4. Avoid creating duplicated files or duplicated logic.
5. Identify affected files.
6. Check if database changes are required.
7. Evaluate:
    - SEO impact
    - Security implications
    - Performance impact
    - UX impact
    - Monetization opportunities

Only then implement the requested change.

If essential information is missing, ask questions before writing code. Do not make assumptions about unknown business rules.

---

## Coding Language Rules

All code must be written in English. This includes:

- class names
- methods
- variables
- database tables
- database columns
- comments

Website content remains Italian:

- blog articles
- event descriptions
- SEO texts
- user-facing content

Existing mixed-language code should be progressively normalized during refactoring. Do not introduce new Italian names in code.

---

## PHP Standards

Always use:

```php
declare(strict_types=1);
```

---

## Naming Convention

### Classes

Use PascalCase.

Examples:

- `EventController`
- `BlogPostService`
- `ImageRepository`

### Methods

Use camelCase.

Examples:

- `getUpcomingEvents()`
- `createEvent()`
- `updateProfile()`

### Variables

Use camelCase.

Examples:

- `$eventId`
- `$imagePath`
- `$userProfile`

### Constants

Use UPPER_CASE.

Examples:

- `MAX_UPLOAD_SIZE`
- `DEFAULT_IMAGE_PATH`

---

## Controllers

Controllers must:

- receive requests
- validate input
- call services
- return responses or views

Controllers must not:

- contain business logic
- contain SQL queries
- contain HTML markup

Controllers should remain lightweight.

---

## Services

Services contain application logic.

Examples:

- `EventService`
- `BlogService`
- `ImageService`
- `SeoService`
- `RankingService`
- `AdvertisementService`

Complex operations belong here. Services are responsible for coordinating:

- models
- repositories
- external services
- business rules

---

## Models and Repositories

Do not force unnecessary abstraction. Use the simplest architecture that keeps the project maintainable.

**Simple CRUD operations** can use:

```
Controller → Service → Model → Database
```

**Complex operations** can use:

```
Controller → Service → Repository → Database
```

Repositories should be introduced for:

- complex queries
- statistics
- ranking systems
- advanced searches
- reporting
- advertising analytics
- heavy database operations

Avoid creating empty repository layers without a real purpose.

---

## Database Rules

Database access must use:

- PDO
- prepared statements
- parameter binding

Never:

- concatenate SQL with user input
- place SQL queries inside views

Avoid:

```sql
SELECT * FROM events
```

Prefer explicit fields:

```sql
SELECT id, title, slug FROM events
```

Database queries must be optimized and reviewed for:

- indexes
- execution time
- unnecessary joins
- duplicated queries

### Database Migration

New database changes must be tracked. Use:

```
database/
├── migrations/
└── seeds/
```

Migration examples:

```
001_create_users_table.sql
002_create_events_table.sql
003_create_event_images.sql
```

Do not make untracked structural database changes.

Every database modification should consider:

- backwards compatibility
- data integrity
- migration safety

---

## Core Components

Current core structure:

```
Core/
├── Controller.php
├── Database.php
├── Mailer.php
├── Middleware.php
├── Router.php
└── Session.php
```

Reuse existing components before creating new ones. Do not duplicate core functionality.

---

## Dependency Injection

Use dependency injection.

**Preferred:**

```php
public function __construct(
    private EventService $eventService
) {
}
```

**Avoid** creating dependencies manually inside classes:

```php
// Avoid this inside controllers or services
$this->service = new EventService();
```

---

## Frontend Rules

Use:

- Tailwind CSS
- semantic HTML
- mobile-first approach
- reusable components
- accessible interfaces

Do not introduce:

- Bootstrap
- jQuery
- unnecessary frontend frameworks

Frontend implementation must consider:

- Core Web Vitals
- loading speed
- accessibility
- responsive design

---

## Security Requirements

Every feature must consider:

- CSRF protection
- output escaping
- input validation
- prepared statements
- authentication checks
- authorization checks
- secure file uploads
- `password_hash()`
- `password_verify()`

Never trust:

- user input
- uploaded files
- URL parameters
- external API responses

---

## Image Management

Images must be managed through dedicated services. Consider:

- file validation
- MIME validation
- size limits
- resizing
- WebP conversion
- thumbnails
- optimization
- lazy loading
- responsive images

Do not duplicate image processing logic in controllers.

---

## SEO Requirements

SEO is a core functionality, not an optional improvement.

Every public page must consider:

- SEO title
- meta description
- canonical URL
- Open Graph metadata
- Schema.org structured data
- breadcrumbs
- SEO-friendly URLs
- internal linking

Every new page or feature must evaluate:

- search intent
- keyword positioning
- content quality
- related pages
- indexing strategy

Avoid creating pages without SEO purpose.

---

## Structured Data

Use Schema.org when appropriate. Possible schemas:

- `Event`
- `BlogPosting`
- `BreadcrumbList`
- `Organization`
- `ImageObject`
- `FAQPage`
- `WebSite`

Structured data must always match visible page content. Never add fake or misleading structured data.

---

## Event Domain Rules

Events are one of the core entities of ItalianCosplay.

Event features should consider:

- event information
- location
- dates
- images
- social links
- website
- guests
- contests
- categories
- geographic relationships
- SEO optimization

Event pages should consider:

- related events
- related blog content
- internal linking
- user engagement
- advertising opportunities

---

## Blog Domain Rules

The blog is an SEO growth channel.

Every article should consider:

- search intent
- optimized title
- meta description
- excerpt
- headings structure
- internal linking
- related content
- FAQ when useful
- `BlogPosting` schema

The blog should support:

- event pages
- cosplay guides
- informational content
- organic traffic growth

---

## Business and Monetization

ItalianCosplay is designed as a commercial portal.

When creating new features evaluate:

- advertising opportunities
- sponsored visibility
- analytics requirements
- conversion opportunities
- traffic growth potential
- commercial packages

Features should consider how they can create value for:

- event organizers
- companies
- creators
- cosplay professionals

Do not design features only from a technical perspective. Consider the business impact.

---

## Development Workflow — Final Checklist

Before completing any task verify:

### Architecture

- Is MVC respected?
- Is existing code reused?
- Are responsibilities correctly separated?

### Code Quality

- Is the code written in English?
- Are naming conventions respected?
- Are types defined?
- Is the solution maintainable?

### Security

- Is user input validated?
- Is output escaped?
- Are permissions checked?
- Are uploads secure?

### Database

- Are queries optimized?
- Are migrations required?
- Are indexes needed?

### SEO

- Does the feature improve or maintain SEO quality?
- Are metadata and structured data considered?
- Are internal links possible?

### Performance

- Are unnecessary queries avoided?
- Is caching useful?
- Are assets optimized?

### Business

- Does this feature increase portal value?
- Can it support future monetization?

---

## Refactoring Rules

When working on existing code:

- improve gradually
- avoid unnecessary rewrites
- preserve existing functionality
- remove duplication when safe
- normalize mixed-language code progressively

Do not perform large destructive refactors without explicit approval.

---

## Missing Information Rule

If requirements are incomplete, ask questions before implementing.

Examples:

- unclear business rules
- missing database relationships
- unknown user permissions
- unclear expected behavior

Do not invent functionality.

---

## Final Development Philosophy

Build ItalianCosplay as a long-term scalable product.

**Prefer:**

- clean architecture
- maintainable solutions
- reusable components
- progressive improvements
- simple solutions when possible

**Avoid:**

- quick hacks
- duplicated code
- unnecessary complexity
- premature abstractions
- solutions that block future growth

Every implementation should help ItalianCosplay become:

- technically solid
- SEO competitive
- commercially valuable
- easy to maintain
