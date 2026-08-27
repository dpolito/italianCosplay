---
name: php
description: PHP coding standards for ItalianCosplay.it. Use when creating or modifying PHP classes, controllers, models, services, repositories, helpers or core components.
---

# ItalianCosplay PHP Skill

## Purpose

This skill defines PHP coding standards for ItalianCosplay.it.

The goal is to produce:

- readable code
- maintainable code
- secure code
- predictable code
- scalable object-oriented PHP

All PHP code must follow these rules unless an existing project constraint requires otherwise.

---

## PHP Version

The project uses:

```
PHP 8.2+
```

Do not use deprecated PHP features.

Prefer modern PHP features:

- typed properties
- constructor property promotion
- enums when useful
- readonly properties when appropriate
- match expressions
- null coalescing operator
- strict comparisons

---

## Strict Types

Every PHP file must start with:

```php
<?php

declare(strict_types=1);
```

Strict typing avoids unexpected conversions and improves reliability.

---

## Language Rules

All PHP code must be written in English. This includes:

- classes
- methods
- variables
- properties
- constants
- database-related names
- comments

**Good:**

```php
class EventService
{
    private string $eventTitle;
}
```

**Avoid:**

```php
class ServizioEvento
{
    private string $titoloEvento;
}
```

Website content remains Italian.

---

## Naming Conventions

### Classes

Use PascalCase.

Examples:

- `EventController`
- `BlogPostService`
- `ImageManager`
- `UserRepository`

Class names must describe responsibility clearly. Avoid generic names (`Manager`, `Helper`, `Handler`, `Processor`) unless the responsibility is clear.

### Methods

Use camelCase.

Examples:

- `getEventBySlug()`
- `createBlogPost()`
- `updateProfile()`
- `deleteImage()`

Methods should describe actions.

Prefer `findPublishedEvents()` over `events()`.

### Variables

Use camelCase.

Examples:

- `$eventId`
- `$userEmail`
- `$imagePath`
- `$publishedPosts`

Avoid `$event_id`, `$user_email`.

### Constants

Use uppercase with underscores.

Examples:

- `MAX_UPLOAD_SIZE`
- `DEFAULT_IMAGE_WIDTH`
- `CACHE_DURATION`

---

## Class Structure

Preferred class order:

1. Namespace
2. Imports
3. Class declaration
4. Constants
5. Properties
6. Constructor
7. Public methods
8. Protected methods
9. Private methods

Example:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;

class EventService
{
    private const CACHE_TIME = 3600;

    public function __construct(
        private EventRepository $repository
    ) {
    }

    public function findEvent(int $id): ?Event
    {
        return $this->repository->find($id);
    }

    private function validate(): bool
    {
        return true;
    }
}
```

---

## Type Declarations

Always define:

- parameter types
- return types
- property types

Avoid untyped code.

**Bad:**

```php
public function save($data)
{
}
```

**Good:**

```php
public function save(array $data): bool
{
}
```

---

## Constructor Injection

Dependencies must be injected through constructors.

**Preferred:**

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

**Avoid** creating dependencies inside methods:

```php
class EventService
{
    public function create(): void
    {
        $repository = new EventRepository();
    }
}
```

Benefits:

- lower coupling
- easier maintenance
- easier testing
- clearer dependencies

---

## Properties

Properties must always have a type.

**Good:**

```php
private string $title;

private ?int $categoryId = null;

private array $images = [];
```

**Avoid:**

```php
private $title;
```

---

## Constructor Property Promotion

Use constructor property promotion when appropriate.

**Preferred:**

```php
public function __construct(
    private EventRepository $repository
) {
}
```

**Avoid** unnecessary declarations:

```php
private EventRepository $repository;

public function __construct(EventRepository $repository)
{
    $this->repository = $repository;
}
```

...unless additional constructor logic is required.

---

## Readonly Properties

Use readonly properties when values should not change after initialization.

Example:

```php
class EventDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $slug
    ) {
    }
}
```

Do not use readonly where mutation is part of normal behavior.

---

## Nullable Values

Handle nullable values explicitly.

**Good:**

```php
public function find(int $id): ?Event
{
    return $this->repository->find($id);
}
```

**Avoid** hiding missing values:

```php
public function find(int $id): Event
{
    return null;
}
```

---

## Null Handling

Use the null coalescing operator when appropriate.

Example:

```php
$title = $data['title'] ?? '';
```

Avoid repetitive checks:

```php
if (isset($data['title'])) {
    $title = $data['title'];
} else {
    $title = '';
}
```

---

## Strict Comparisons

Always use strict comparisons.

**Good:**

```php
if ($status === 'published') {
}
```

**Avoid:**

```php
if ($status == 'published') {
}
```

---

## Match Expressions

Prefer `match` when it improves readability.

Example:

```php
$message = match ($status) {
    'active' => 'Active user',
    'blocked' => 'Blocked user',
    default => 'Unknown status',
};
```

Avoid long if/elseif chains when a match is clearer.

---

## Exception Handling

Use exceptions for exceptional situations.

Example:

```php
throw new RuntimeException(
    'Unable to save event'
);
```

Exceptions should describe the problem clearly. Avoid silent failures.

**Bad:**

```php
return false;
```

...without explanation.

### Exception Types

Use meaningful exception classes when needed.

Examples:

- `InvalidArgumentException`
- `RuntimeException`
- `LogicException`

For complex domains consider custom exceptions:

- `EventNotFoundException`
- `UploadException`
- `AuthorizationException`

### Error Handling Rules

Never:

- hide database errors
- ignore exceptions
- expose sensitive errors to users

Errors should be:

- logged internally
- handled gracefully
- shown safely to users

---

## Date and Time

Use `DateTimeImmutable` instead of `DateTime` when possible.

Example:

```php
$createdAt = new DateTimeImmutable();
```

Avoid mutable date objects.

### Date Formatting

Keep formatting responsibility outside business logic.

**Good:**

```php
$dateFormatter->format($event->getDate());
```

**Avoid:**

```php
$event->date->format('d/m/Y');
```

...inside services or models when presentation is involved.

---

## Arrays and Data Transfer

Use clear array structures.

Avoid ambiguous arrays:

```php
$data = [
    'x' => 1,
    'y' => 2
];
```

Prefer descriptive keys:

```php
$data = [
    'title' => 'Example',
    'slug' => 'example-event'
];
```

For complex structures consider DTO objects.

---

## PHPDoc

Use PHPDoc when it adds value.

Useful for:

- complex arrays
- public APIs
- non-obvious logic

Example:

```php
/**
 * @return array<int, Event>
 */
public function getUpcomingEvents(): array
{
}
```

Do not add PHPDoc that only repeats obvious type declarations.

---

## Input Validation

All external data must be considered unsafe.

Validate:

- GET parameters
- POST data
- JSON payloads
- uploaded files
- API responses

Validation must happen before processing data.

Example:

```php
$email = filter_var(
    $input['email'],
    FILTER_VALIDATE_EMAIL
);
```

Do not trust client-side validation. Frontend validation improves UX but never replaces server validation.

---

## Output Escaping

Always escape user-generated content before rendering.

Example:

```php
<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
```

Never print raw user input. Avoid:

```php
<?= $userContent ?>
```

...unless the content has been explicitly sanitized.

---

## Database Usage

Database operations must follow project rules.

Always use:

- PDO
- prepared statements
- parameter binding

**Good:**

```php
$stmt = $pdo->prepare(
    'SELECT id, title FROM events WHERE slug = :slug'
);

$stmt->execute([
    'slug' => $slug
]);
```

**Avoid:**

```php
$sql = "SELECT * FROM events WHERE slug = '$slug'";
```

### Transactions

Use database transactions when multiple operations must succeed together.

Example:

```php
$pdo->beginTransaction();

try {
    // operations

    $pdo->commit();
} catch (Throwable $exception) {
    $pdo->rollBack();

    throw $exception;
}
```

Use transactions for:

- complex saves
- financial operations
- multi-table updates

---

## File Upload Security

File uploads require validation.

Always check:

- file size
- MIME type
- extension
- upload errors
- generated filename
- storage path

Never trust:

- original filename
- extension alone
- client MIME type

Uploaded files must be processed through dedicated services.

Example:

```php
$imageService->processUpload($file);
```

Do not implement upload logic inside controllers.

---

## Password Handling

Never store plain text passwords.

Always use `password_hash()` and `password_verify()`.

Example:

```php
$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);
```

---

## Security Sensitive Data

Never log or expose:

- passwords
- authentication tokens
- API keys
- private configuration values

---

## Dependency Management

Avoid adding external packages without a real requirement.

Prefer:

- native PHP
- existing project components
- custom lightweight solutions

Before adding a dependency evaluate:

- maintenance
- security
- long-term impact
- project complexity

---

## Performance Rules

Write efficient PHP code.

Consider:

- avoiding unnecessary database queries
- avoiding repeated calculations
- using pagination
- caching expensive operations
- optimizing loops

Avoid premature optimization. Optimize real bottlenecks.

### Memory Management

Avoid loading unnecessary data.

**Bad:**

```php
$events = Event::all();
```

...when only a small list is needed.

**Prefer:**

```php
$events = Event::paginate(20);
```

---

## Code Readability

Prefer readable code over clever code.

**Good:**

```php
$isPublished = $event->status === 'published';
```

**Avoid:**

```php
$p = $e->s === 'published';
```

### Methods

Methods should:

- do one thing
- have clear names
- remain reasonably small

Avoid methods that validate, save, send emails, process images, and redirect — all together. Split responsibilities.

### Static Usage

Avoid excessive static methods. Prefer object-oriented design.

**Good:**

```php
$imageService->convertToWebp($file);
```

**Avoid:**

```php
Image::convert($file);
```

...unless the static design is intentional.

---

## Final PHP Checklist

Before completing PHP code verify:

### Standards

- Does the file use `strict_types`?
- Are types declared?
- Are names in English?

### Architecture

- Is responsibility in the correct layer?
- Are dependencies injected?

### Security

- Is input validated?
- Is output escaped?
- Are uploads protected?

### Database

- Are prepared statements used?
- Are queries optimized?

### Maintainability

- Is duplication avoided?
- Is the code readable?
- Is complexity justified?

### Performance

- Are unnecessary operations avoided?
- Is the solution scalable?

---

## PHP Development Philosophy

Write PHP that can be maintained for years.

**Prefer:**

- explicit code
- strong typing
- clear responsibilities
- predictable behavior
- secure implementations

**Avoid:**

- shortcuts
- hidden magic
- duplicated logic
- fragile solutions

Every PHP implementation should support ItalianCosplay as a long-term professional platform.
