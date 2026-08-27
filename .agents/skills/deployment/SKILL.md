---
name: deployment
description: Deployment and infrastructure rules for ItalianCosplay.it. Use when working on Docker, servers, Apache, PHP configuration, database deployment, backups, cron jobs, production environments and DevOps tasks.
---

# ItalianCosplay Deployment Skill

## Purpose

This skill defines deployment and infrastructure standards for ItalianCosplay.it.

The project must be designed to run reliably across:

- local development
- testing environments
- staging
- production

---

## Infrastructure Philosophy

Infrastructure decisions must prioritize:

- reliability
- security
- maintainability
- scalability
- simplicity

Avoid unnecessary complexity. Do not introduce technologies without a clear benefit.

---

## Technology Stack

Production environment is based on:

**Application:** PHP 8.2+, custom MVC OOP architecture

**Database:** MySQL / MariaDB

**Web Server:** Apache

**Frontend Build:** Tailwind CSS

**Containerization:** Docker

---

## Environment Separation

Maintain separated environments.

```
Development → Staging → Production
```

Each environment must have: separate configuration, separate database, separate credentials.

---

## Configuration Management

Configuration files must not contain secrets in production.

**Avoid:**

```php
$password = "mypassword";
```

...inside tracked files.

### Environment Variables

Sensitive configuration should use environment variables.

Examples: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_ENV`, `APP_DEBUG`.

### Configuration Structure

Current configuration:

```
config/
├── app.php
├── database.php
└── api.php
```

Future improvements can introduce a `.env` environment loader without breaking existing architecture.

### Application Environment

The application should know its environment: `development`, `staging`, `production`.

### Debug Mode

Debugging must be environment based.

**Development:** enabled

**Production:** disabled

Never expose: stack traces, SQL errors, sensitive paths.

---

## Docker Usage

Docker is the standard local development environment.

Typical services: `php`, `apache`, `mysql`, `phpmyadmin`.

### Docker Principles

Containers should be reproducible, documented, easy to rebuild.

Avoid relying on manual server changes.

### Docker Compose

Docker Compose should define services, networks, volumes, environment variables.

Example:

```yaml
services:
  php:
  mysql:
  phpmyadmin:
```

---

## Apache Configuration

Apache is the production web server.

Configuration should support: clean URLs, security, performance, PHP execution.

### URL Rewriting

The application uses friendly URLs. Apache should support `mod_rewrite`.

Example:

```
/eventi-cosplay/lucca-comics-2026 → index.php route handler
```

### Document Root

The public web root should expose only public files.

```
/public
├── index.php
├── assets/
└── uploads/
```

Avoid exposing app files, configuration, private resources.

### Directory Protection

Sensitive directories must not be publicly accessible.

Protect: `/app`, `/config`, `/storage`, `/logs`.

### Apache Security Headers

Consider: Content Security Policy, X-Frame-Options, X-Content-Type-Options, Referrer Policy.

Headers should improve security without breaking functionality.

---

## PHP Configuration

PHP configuration must match application requirements.

Required: PHP 8.2+, required extensions, correct memory limits, upload limits.

### PHP Extensions

Typical requirements: PDO, PDO MySQL, GD, mbstring, openssl, fileinfo, json, curl.

### PHP Production Settings

Production should use:

```ini
display_errors = Off
log_errors = On
```

Errors should be logged, not displayed.

### PHP Upload Configuration

Image-heavy features require appropriate limits.

Review: `upload_max_filesize`, `post_max_size`, `max_execution_time`, `memory_limit`.

Values should match event galleries, blog images, banners.

---

## Database Deployment

Database changes must be controlled. Avoid manual changes directly in production.

### Database Migration System

Introduce migrations to version database structure, reproduce environments, track changes.

Example:

```
database/migrations/
├── 001_create_users.php
└── 002_create_events.php
```

### Migration Rules

Migrations should be ordered, reversible when possible, documented.

Never modify old migrations already executed. Create new migrations.

---

## Database Backup

Backups are mandatory.

Protect: user data, event data, blog content, commercial data.

### Backup Strategy

**Daily:** database backup

**Weekly:** full verification

### Backup Storage

Do not store only one local copy.

Consider: separate disk, remote storage, encrypted backup.

### Restore Testing

A backup is useful only if it can be restored.

Periodically verify: database restoration, file restoration, application startup.

---

## Media Storage Deployment

Uploads require special attention.

Possible structure:

```
public_assets/uploads/
├── events/
├── blog/
├── avatars/
└── banners/
```

### Media Backup

Media files are business assets. Backup: images, documents, generated files.

---

## Cron Jobs

Scheduled tasks should be documented.

Examples: ranking update, statistics aggregation, scheduled publishing, cleanup tasks, notifications.

### Cron Philosophy

Cron jobs must: be idempotent, log execution, handle failures.

---

## Deployment Workflow

Deployments should follow a controlled process.

```
Development → Code Review → Testing → Staging → Production
```

### Before Deployment

Before deploying verify: code changes, database changes, configuration changes, asset changes, migration requirements.

### Production Deployment

Production deployment should be predictable.

```
Backup → Maintenance preparation → Deploy code → Run migrations → Clear cache → Verify application → Monitor logs
```

---

## Git Workflow

Version control is required.

Use Git for: source code, migrations, configuration templates, documentation.

### Git Rules

Never commit: passwords, API keys, production credentials, private files.

### Environment Files

Use templates.

Example: `.env.example` contains required variables, placeholders, documentation.

Real files (`.env`) must remain private.

### Branch Strategy

Use a simple workflow.

Example: `main → production`, or `main / develop / feature/*`.

Choose based on project size.

---

## Feature Development

New features should:

- use existing architecture
- follow skills
- include database changes if needed
- avoid unnecessary refactoring

---

## Dependency Management

External dependencies must be evaluated.

Before adding a package consider: maintenance, security, project impact, necessity.

Avoid unnecessary libraries.

---

## Updates

Updates should be planned.

Before updating: create backup, test in staging, verify compatibility.

### PHP Version Updates

PHP upgrades require testing.

Check: deprecated functions, extensions, framework compatibility, database compatibility.

### Database Updates

Database upgrades require attention.

Verify: SQL compatibility, indexes, query performance, migrations.

---

## Cache Management

Cache strategy should be explicit.

Possible cached data: routes, configuration, queries, rendered fragments.

### Cache Clearing

After deployment clear: application cache, generated assets when needed.

Avoid deleting useful persistent data.

---

## Asset Deployment

Frontend assets require controlled deployment.

Consider: Tailwind compilation, cache busting, asset versioning.

---

## Monitoring

Production should provide visibility.

Monitor: uptime, errors, slow requests, database health.

### Application Logs

Logs should help diagnose issues.

Include: errors, warnings, important actions.

Avoid logging: passwords, tokens, sensitive user data.

### Log Rotation

Logs must not grow indefinitely.

Configure: rotation, retention, cleanup.

### Error Handling

Production errors must: be logged, not expose technical details, provide user-friendly messages.

---

## Server Security

Production servers must follow security best practices.

Consider: regular updates, limited access, firewall rules, secure credentials.

### Access Management

Server access should be restricted.

Use: individual accounts, SSH keys, minimum privileges.

Avoid: shared accounts, shared passwords.

### Secrets Management

Sensitive information includes: database passwords, API keys, payment credentials, email credentials.

Never expose secrets in: source code, Git repositories, public directories.

### File Permissions

Production files must have correct permissions.

Avoid: writable application directories, unnecessary public access.

Writable locations should be limited to: uploads, cache, logs.

### Database Security

Database access should be restricted.

Consider: dedicated users, minimum privileges, protected credentials, network restrictions.

---

## HTTPS

Production must use HTTPS.

Required for: user authentication, payments, privacy, SEO.

### SSL Management

Certificates should be: automatically renewed, monitored, tested.

---

## Performance Optimization

Deployment decisions affect performance.

Consider: PHP OPcache, database indexes, HTTP caching, compression.

### PHP OPcache

Production PHP should use OPcache.

Benefits: faster execution, lower CPU usage.

### Database Performance

Monitor: slow queries, missing indexes, large tables.

Especially: events, analytics, statistics, logs.

### Media Performance

Images require: optimized formats, caching, correct dimensions.

Avoid serving original high-resolution images unnecessarily.

---

## Disaster Recovery

A recovery plan is required.

Protect: source code, database, uploaded media, configuration.

### Recovery Priority

Recommended order:

```
Database → Application Code → Media Files → Configuration → Services
```

### Recovery Testing

Periodically verify: backups, restore procedures, application startup.

---

## Maintenance Mode

For important operations consider maintenance mode.

Examples: major migrations, infrastructure changes, emergency fixes.

---

## Health Checks

Application health checks can verify: PHP availability, database connection, required services.

---

## Deployment Checklist

Before every production deployment:

### Code

- Is the code tested?
- Are existing conventions respected?
- Are new dependencies justified?

### Database

- Are migrations ready?
- Is a backup available?
- Are indexes correct?

### Configuration

- Are environment variables correct?
- Are secrets protected?
- Is debug disabled?

### Security

- Are permissions correct?
- Are sensitive files protected?
- Is HTTPS active?

### Performance

- Are assets optimized?
- Is caching correct?
- Are images optimized?

### Verification

After deployment:

- Check homepage.
- Check authentication.
- Check database operations.
- Check uploads.
- Check logs.
- Check scheduled jobs.

---

## Final Deployment Philosophy

ItalianCosplay must be deployable as a professional platform.

Deployment is not just copying files. It is a controlled process that guarantees:

- reliability
- security
- performance
- data protection
- business continuity

A good deployment system allows the platform to grow safely.
