---
name: architecture
description: Architecture rules for ItalianCosplay.it custom MVC OOP framework. Use when creating or modifying controllers, models, services, repositories, core components or application structure.
---

# ItalianCosplay Architecture Skill

## Purpose

This skill defines the architectural rules of ItalianCosplay.it.

The project uses a custom MVC OOP architecture designed to remain lightweight, maintainable and scalable.

The goal is not to reproduce a full framework but to keep a clear separation between responsibilities.

---

## Main Architecture Pattern

ItalianCosplay follows:

```
Request → Router → Controller → Service → Model / Repository → Database
```

Each layer has a specific responsibility. Do not bypass layers without a valid reason.

---

## Application Layers

### Router

Responsible for:

- matching URLs
- selecting controllers
- defining HTTP methods
- handling route parameters

Router must not contain:

- business logic
- database queries
- HTML rendering

Example:

```php
$router->get(
    '/event/{slug}',
    [EventController::class, 'show']
);
```

### Controllers

Controllers are the application entry points.

Responsibilities:

- receive HTTP requests
- validate request data
- authorize actions
- call services
- prepare data for views
- return responses

Controllers must remain thin. A controller should coordinate, not implement business rules.

Controllers must not:

- contain SQL queries
- directly manipulate complex business logic
- process images
- send emails directly
- contain large conditional workflows

**Bad:**

```php
public function create()
{
    // validate
    // resize image
    // save database
    // send email
}
```

**Good:**

```php
public function create(Request $request)
{
    $this->eventService->create($request->all());
}
```

### Services

Services contain business logic. A service represents an application capability.

Examples:

- `EventService`
- `BlogService`
- `ImageService`
- `SeoService`
- `RankingService`
- `AdvertisementService`

Services are responsible for:

- workflows
- business rules
- coordination between components
- complex operations

A service can use:

- Models
- Repositories
- Helpers
- External APIs

Example:

```php
class EventService
{
    public function __construct(
        private EventRepository $repository,
        private ImageService $imageService
    ) {
    }
}
```

### Models

Models represent application entities and database-related operations.

Models can contain:

- entity properties
- simple CRUD operations
- entity-specific database logic

Models should not contain:

- HTML
- presentation logic
- unrelated business workflows

Example:

```php
class Event extends Model
{
    public function findBySlug(string $slug): ?array
    {
        // database operation
    }
}
```

### Repositories

Repositories are optional and should be introduced only when useful.

Use repositories for:

- complex queries
- multiple joins
- reporting
- statistics
- ranking
- search engines
- filtering systems

Do not create repositories only to add another layer. Prefer meaningful abstraction.

---

## Core Components

The Core layer contains the fundamental components of the framework.

Current structure:

```
Core/
├── Controller.php
├── Database.php
├── Mailer.php
├── Middleware.php
├── Router.php
└── Session.php
```

Core components must remain generic. Do not place project-specific business logic inside Core.

---

## Database Layer

Database access must be centralized.

The Database component is responsible for:

- PDO connection management
- transactions
- query execution
- connection handling

Rules:

- use prepared statements
- never expose raw connections everywhere
- avoid duplicated connection logic

Example:

```php
Database::getInstance();
```

---

## Dependency Injection

Dependency Injection is the preferred way to manage dependencies.

Classes should receive required dependencies through constructors.

**Preferred:**

```php
class EventController
{
    public function __construct(
        private EventService $eventService
    ) {
    }
}
```

**Avoid:**

```php
class EventController
{
    public function __construct()
    {
        $this->service = new EventService();
    }
}
```

Benefits:

- easier testing
- lower coupling
- easier maintenance
- clearer responsibilities

---

## Request Flow

The standard request lifecycle:

```
User Request
    ↓
Apache
    ↓
Public Entry Point
    ↓
Router
    ↓
Middleware
    ↓
Controller
    ↓
Service
    ↓
Model / Repository
    ↓
Database
    ↓
Controller
    ↓
View
    ↓
Response
```

Every layer should only handle its own responsibility.

---

## Views

Views are responsible only for presentation.

Views can contain:

- HTML
- PHP template logic
- loops
- conditional display logic
- escaped variables

Views must not contain:

- SQL queries
- database access
- business logic
- API calls

**Bad:**

```php
$result = $db->query(
    "SELECT * FROM events"
);
```

**Good:**

```php
foreach ($events as $event):
```

### View Organization

Use feature-based organization.

Example:

```
Views/
├── events/
│   ├── index.php
│   ├── show.php
│   └── form.php
└── blog/
    ├── index.php
    ├── show.php
    └── form.php
```

Views should be reusable and easy to maintain.

---

## Helpers

Helpers contain small reusable utility functions.

Examples:

```
Helpers/
├── UrlHelper.php
├── DateHelper.php
└── SecurityHelper.php
```

Helpers should not contain:

- business logic
- database operations
- large workflows

If a helper grows too much, move the logic into a Service.

---

## Middleware

Middleware handles cross-cutting application concerns.

Examples:

- authentication
- authorization
- CSRF verification
- request filtering
- logging

Middleware should execute before controllers.

Example:

```
Request → AuthenticationMiddleware → Controller
```

---

## Authentication and Authorization

Authentication determines: **"Who is the user?"**

Authorization determines: **"What can the user do?"**

Keep these concepts separated.

Examples:

**Authentication:**

- session validation
- login state

**Authorization:**

- roles
- permissions
- ownership checks

---

## Error Handling

Errors must be handled consistently.

Prefer exceptions:

```php
throw new RuntimeException(
    'Unable to save event'
);
```

Avoid silent failures. Avoid:

```php
return false;
```

without clear handling.

---

## Logging

Important operations should be traceable.

Consider logging:

- errors
- failed operations
- security events
- external API failures

Never log:

- passwords
- tokens
- sensitive personal data

---

## Configuration

Configuration files are located in:

```
config/
├── api.php
├── app.php
└── database.php
```

Configuration should not be duplicated in code. Sensitive values should progressively move to environment variables when appropriate.

---

## External Services

External integrations must be isolated.

Examples:

```
Services/
├── GeocodingService.php
├── MailService.php
├── PaymentService.php
└── AnalyticsService.php
```

Do not call external APIs directly from controllers.

---

## File and Class Organization

Follow consistent naming and organization.

**Controllers:**

```
Controllers/
├── EventController.php
├── BlogController.php
└── UserController.php
```

**Services:**

```
Services/
├── EventService.php
├── ImageService.php
└── SeoService.php
```

**Repositories:**

```
Repositories/
├── EventRepository.php
└── BlogRepository.php
```

**Models:**

```
Models/
├── Event.php
├── BlogPost.php
└── User.php
```

Use singular names for entities.

**Good:**

```
Event.php
BlogPost.php
User.php
```

**Avoid:**

```
Events.php
Users.php
```

---

## Creating New Features

Before implementing a new feature:

1. Understand the business requirement.
2. Check if similar functionality already exists.
3. Identify affected components.
4. Decide the correct architectural layer.
5. Verify database requirements.
6. Consider SEO impact.
7. Consider security requirements.
8. Consider future scalability.

Do not immediately create files without analyzing the existing architecture.

---

## Adding New Controllers

Create a controller only when a new user-facing or administrative area requires it.

A controller should:

- handle requests
- call services
- return views or responses

A controller should not become a large application class. If logic grows, move it into services.

---

## Adding New Services

Create a service when:

- business logic exists
- multiple components must coordinate
- a workflow requires multiple steps
- external integrations are involved

Example — creating an event:

```
EventController → EventService → EventRepository → Database
```

The controller should not manage the entire workflow.

---

## Adding New Models

Create a model when:

- a new entity exists
- database representation is needed
- entity behavior is required

Models should represent domain objects. Avoid creating models only as empty database wrappers.

---

## Adding New Database Tables

Before creating tables, evaluate:

- entity relationships
- indexes
- foreign keys
- future queries
- SEO requirements
- scalability

Use migrations. Never create database changes without tracking them.

---

## Refactoring Rules

When improving existing code:

**Prefer:**

- small incremental changes
- preserving existing functionality
- improving readability
- removing duplication
- increasing consistency

**Avoid:**

- rewriting the whole application without need
- introducing unnecessary frameworks
- changing architecture without approval

---

## Existing Code Compatibility

ItalianCosplay contains legacy code and mixed conventions.

When modifying existing code:

- respect current functionality
- improve progressively
- avoid breaking existing features
- normalize naming when touching related areas

Do not rename large parts of the system without a migration strategy.

---

## Architectural Anti-Patterns

Avoid the following.

### Fat Controllers

**Bad:**

```
Controller
├── validation
├── SQL
├── business rules
├── image processing
└── emails
```

Move responsibilities into proper layers.

### God Services

Avoid services containing unrelated features.

**Bad:**

```
ApplicationService (with everything inside)
```

**Prefer:**

```
EventService
BlogService
ImageService
```

### Duplicate Logic

Before creating new code, search existing implementations. Do not create:

- duplicate image processing
- duplicate validation
- duplicate authentication
- duplicate SEO generation

### Database Abuse

Avoid:

- SQL inside views
- database calls everywhere
- unoptimized queries
- missing indexes

### Premature Abstraction

Do not create complex systems before they are needed. Prefer simple solutions that can evolve.

---

## Architecture Review Checklist

Before completing architectural changes verify:

### Structure

- Is the correct layer being used?
- Are responsibilities separated?

### Maintainability

- Is the code understandable?
- Is duplication avoided?

### Scalability

- Can the feature grow?
- Are future requirements considered?

### Security

- Are permissions handled?
- Are inputs validated?

### Performance

- Are database operations efficient?
- Are unnecessary operations avoided?

### Business

- Does this support ItalianCosplay growth?
- Does it improve user or commercial value?

---

## Final Architecture Principle

ItalianCosplay should remain:

- simple enough to maintain
- structured enough to scale
- flexible enough to evolve

Use architecture as a tool, not as unnecessary complexity.

The best solution is the simplest solution that remains clean, secure and scalable.
