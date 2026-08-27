---
name: security
description: Security rules for ItalianCosplay.it. Use when creating authentication, authorization, forms, uploads, sessions, APIs, user data handling or any feature involving security.
---

# ItalianCosplay Security Skill

## Purpose

This skill defines security requirements for ItalianCosplay.it.

Security is mandatory for every feature. The application must protect:

- user accounts
- personal data
- administrative functions
- uploaded files
- business data
- advertising systems
- payment-related information

Security must be considered from the beginning of development, not added later.

---

## Security Principles

Always follow:

- least privilege
- validate all input
- escape all output
- never trust client data
- fail securely
- keep sensitive information protected

---

## Authentication

Authentication answers: **"Who is the user?"**

The system must use secure authentication flows.

Requirements:

- password hashing
- session management
- account status checks
- login protection
- logout handling

---

## Password Management

Passwords must never be stored in plain text.

Always use `password_hash()` for storing passwords, and verify with `password_verify()`.

Example:

```php
$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);
```

### Password Rules

Never:

- log passwords
- send passwords by email
- store temporary plain text copies
- expose password hashes

Password reset systems must use:

- secure random tokens
- expiration times
- one-time usage

---

## User Sessions

Sessions must be managed securely.

Requirements:

- regenerate session ID after login
- destroy sessions on logout
- avoid storing unnecessary data
- protect session cookies

Recommended cookie settings:

- `HttpOnly`
- `Secure` when using HTTPS
- `SameSite` protection

### Session Data

Do not store sensitive information unnecessarily.

Avoid storing:

- passwords
- payment data
- private personal information

Store only:

- user identifier
- authentication state
- required permissions

---

## Authorization

Authorization answers: **"What can the user do?"**

Authentication is not authorization. Always verify permissions before protected actions.

Example: a logged-in user is not automatically allowed to:

- edit another user's content
- access administration
- manage advertising campaigns

### Roles and Permissions

Use a role/permission system.

**Roles:** `admin`, `editor`, `organizer`, `user`

**Permissions:** `events.create`, `events.update`, `events.delete`, `users.manage`, `ads.manage`

Avoid checking only usernames or hardcoded users.

**Bad:**

```php
if ($user->email === 'admin@example.com')
```

**Good:**

```php
if ($user->hasPermission('events.delete'))
```

### Middleware Security

Protected routes should use middleware.

Examples:

- `AuthenticationMiddleware`
- `AuthorizationMiddleware`
- `CsrfMiddleware`

Flow:

```
Request → Middleware → Controller
```

---

## CSRF Protection

All state-changing requests must be protected against CSRF.

Protect:

- POST requests
- PUT requests
- DELETE requests
- forms that modify data

Examples: login, profile update, event creation, image upload, advertising management.

### CSRF Token Rules

Forms must include a CSRF token.

Example:

```php
<input 
    type="hidden" 
    name="csrf_token" 
    value="<?= $csrfToken ?>"
>
```

The server must verify the token before processing the request. Never trust tokens generated only by JavaScript.

---

## Input Validation

All external input is untrusted.

Validate:

- form data
- URL parameters
- API payloads
- uploaded files
- cookies

Validation must happen server-side. Frontend validation is only for user experience.

### Validation Rules

**Strings** — check length, allowed characters, format:

```php
if (strlen($title) > 255) {
    throw new InvalidArgumentException();
}
```

**Numbers** — validate type, range, allowed values:

```php
$eventId = filter_var(
    $id,
    FILTER_VALIDATE_INT
);
```

**Dates** — validate correct format, logical values, allowed ranges. Example: an event end date cannot be before the start date.

---

## Output Escaping

Prevent Cross-Site Scripting (XSS). All dynamic content rendered in HTML must be escaped.

Example:

```php
<?= htmlspecialchars(
    $title,
    ENT_QUOTES,
    'UTF-8'
) ?>
```

### HTML Content

Some content may require HTML support (blog articles, event descriptions).

HTML content must be sanitized before saving or displaying. Never directly render uncontrolled HTML.

---

## SQL Injection Prevention

SQL injection is prevented by PDO, prepared statements, and parameter binding.

**Good:**

```php
$stmt = $pdo->prepare(
    'SELECT * FROM users WHERE email = :email'
);

$stmt->execute([
    'email' => $email
]);
```

**Bad:**

```php
$sql = "
SELECT *
FROM users
WHERE email = '$email'
";
```

### Database Error Handling

Never expose database errors to users.

**Bad:**

```php
echo $exception->getMessage();
```

Production behavior:

- log internally
- show generic error message

---

## File Upload Security

File uploads require strict validation.

Validate:

- upload error
- file size
- MIME type
- extension
- image dimensions

Never trust:

- original filename
- extension alone
- browser-provided MIME type

### Image Upload Rules

Allowed formats should be explicitly defined: `jpg`, `jpeg`, `png`, `webp`.

Reject:

- executable files
- unknown formats
- oversized files

### File Naming

Never store files using the original filename.

**Bad:** `my-photo.jpg`

**Use generated names**, e.g. `a83bd92f7c.webp`

Benefits:

- avoids collisions
- prevents path attacks
- hides original information

### Upload Storage

Uploaded files must be stored outside direct application logic. Use dedicated services:

```php
$imageService->upload($file);
```

Do not handle uploads inside controllers.

### Image Processing

Images should be processed before storage. Consider:

- resizing
- WebP conversion
- metadata removal
- optimization

Do not trust uploaded image content.

### Path Traversal Protection

Never allow users to control file paths.

**Bad:**

```php
$file = $_POST['path'];
```

**Good:**

```php
$file = $storage->generateFilename();
```

---

## API Security

External APIs and internal APIs must validate:

- authentication
- permissions
- payload structure
- rate limits when needed

Never expose administrative actions without authorization.

### API Tokens

Never store API keys:

- inside source code
- inside public files
- inside version control

Use environment variables and secure configuration.

### JSON Input

When processing JSON, validate JSON format, required fields, and data types.

Example:

```php
$data = json_decode(
    $json,
    true,
    512,
    JSON_THROW_ON_ERROR
);
```

---

## GDPR and Personal Data Protection

ItalianCosplay handles user data and must respect GDPR principles.

Security must consider:

- data minimization
- privacy by design
- retention policies
- user rights
- secure deletion

### Personal Data Rules

Store only data required for the service. Avoid collecting unnecessary information.

Examples of personal data:

- email addresses
- names
- profile information
- billing information
- uploaded personal content

### Account Deletion

Account deletion must be handled carefully. Consider:

- anonymization
- removing personal identifiers
- preserving required legal records
- cascading related data safely

Do not simply disable the account without evaluating stored data.

---

## Sensitive Information

Never expose:

- passwords
- authentication tokens
- private user data
- payment information
- internal identifiers

---

## Logging Security

Logging is useful for debugging, security monitoring, and auditing.

Never log:

- passwords
- API keys
- session tokens
- payment data
- complete personal records

**Good:**

```
User login failed for account ID 123
```

**Bad:**

```
Login failed password=myPassword123
```

---

## Error Handling

Production errors must not reveal internal details.

Never expose:

- database structure
- file paths
- stack traces
- configuration values

Users should receive safe messages. Developers should have detailed logs.

---

## Administrative Security

Administrative areas require additional protection.

Requirements:

- authentication
- authorization
- permission checks
- CSRF protection
- audit logging where appropriate

Never rely only on:

- hidden links
- frontend restrictions
- URL obscurity

### Admin Actions

Sensitive actions should require appropriate permissions.

Examples: deleting users, approving events, managing advertisements, changing configuration, accessing reports.

---

## Brute Force Protection

Authentication systems should consider:

- login attempt limits
- temporary blocking
- monitoring suspicious activity

Do not permanently block legitimate users without recovery options.

---

## Password Reset Security

Password reset systems must:

- generate cryptographically secure tokens
- expire tokens
- allow single usage
- invalidate old tokens

Never use predictable reset URLs.

---

## Advertising Security

The advertising system requires additional protection.

Protect: campaigns, banners, payments, statistics, advertiser accounts.

Verify: ownership, permissions, campaign status, payment status.

### Payment Security

Payment data must never be stored directly.

Do not store:

- credit card numbers
- CVV
- payment credentials

Use trusted payment providers. Store only:

- transaction identifiers
- payment status
- required billing references

---

## Security Headers

The application should consider:

- Content Security Policy
- X-Content-Type-Options
- X-Frame-Options
- Referrer-Policy
- Strict-Transport-Security when HTTPS is enabled

---

## Dependency Security

Before adding dependencies, evaluate:

- security history
- maintenance status
- vulnerabilities
- necessity

Keep dependencies updated.

---

## Security Review Checklist

Before releasing a feature verify:

### Authentication

- Are users correctly authenticated?
- Are sessions secure?

### Authorization

- Are permissions checked?
- Can users access only allowed resources?

### Input

- Is input validated?
- Is malicious data rejected?

### Output

- Is content escaped?
- Is HTML sanitized?

### Database

- Are prepared statements used?
- Are sensitive values protected?

### Uploads

- Are files validated?
- Are filenames generated safely?

### Privacy

- Is personal data minimized?
- Are deletion rules considered?

### Business

- Are advertising and payment features protected?

---

## Final Security Philosophy

Security must be part of every ItalianCosplay feature.

**Prefer:**

- secure defaults
- explicit permissions
- validated data
- protected resources
- minimal data storage

**Avoid:**

- trusting users
- hidden security
- quick fixes
- storing unnecessary information

A professional portal requires security from the first line of code.
