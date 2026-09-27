---
name: deploy-publish-safety
description: Use when preparing Git, FTP/SFTP, Aruba, production deploys, or deploy scripts for ItalianCosplay.it to distinguish repository files from files that may be published to the remote server.
---

# Deploy Publish Safety

Use this skill for ItalianCosplay.it whenever the task involves:

- FTP/SFTP or Aruba publication;
- production deploys;
- deploy previews;
- deploy script changes;
- deciding which Git changes should be uploaded to the remote server.

## Core Rule

Do not assume that every committed file must be uploaded to production.

The repository contains operational, testing, documentation, and local-development files that are useful in Git but should not be published to the web server via FTP/SFTP.

## Never Upload To Production Via FTP/SFTP

Exclude these paths from production file upload unless the user explicitly gives a different instruction after seeing the risk:

- `.env`
- `.env.example`
- `.git/`
- `.gitignore`
- `AGENTS.md`
- `.agents/`
- `.codex/`
- `.continue/`
- `.playwright-cli/`
- `.traeignore`
- `composer.json`
- `composer.lock`
- `docs/`
- `phpunit.xml.dist`
- `scripts/`
- `tests/`
- `node_modules/`
- `app/config/database.php`
- `public_assets/uploads/`
- `storage/logs/`
- `storage/cache/`
- root-level SQL dumps such as `*.sql`

Migration and seed files are versioned in Git, but do not apply or upload them to production casually. Database changes require a separate migration decision and confirmation.

## Aruba Composer Constraint

Aruba production cannot run Composer commands.

This changes how `vendor/` is handled:

- keep `vendor/` out of Git;
- create or update `vendor/` locally with `composer install --no-dev` or the project-approved production install command;
- upload `vendor/` to Aruba when dependencies change or during first server setup;
- do not rely on the remote server to run `composer install`;
- do not upload PHPUnit/dev-only dependencies if the production package was built with `--no-dev`.

Treat `vendor/` as a runtime dependency bundle, not as source code. It is not part of normal Git diff publishing, but production must have a valid `vendor/autoload.php` and all required package files.

Before uploading `vendor/`, show that this is a dependency-bundle upload and ask for explicit confirmation, because it can contain many files.

## Deploy Credentials

Deploy credentials and target settings may be stored in the local `.env`, but must never be committed or printed.

Expected local variables include:

```bash
IC_DEPLOY_METHOD=sftp
IC_DEPLOY_HOST=...
IC_DEPLOY_USER=...
IC_DEPLOY_PATH=...
IC_PRODUCTION_URL=https://www.italiancosplay.it
```

When checking configuration, report only whether values are present, not their contents.

## First Deploy Marker

If no `deploy-*` tag exists, do not compare from the initial commit by default. That can make the deploy preview include the whole repository.

Require an explicit base reference for the first deploy preview, for example:

```bash
IC_DEPLOY_BASE_REF=HEAD~1 IC_DEPLOY_DRY_RUN=1 ./scripts/deploy.sh "Preview deploy"
```

Create a `deploy-YYYYMMDD-HHMMSS` tag only after upload and production smoke tests both succeed.

## Required Behavior Before Upload

Before any production upload:

1. Run local checks, at least `composer test`.
2. Confirm Git push has succeeded.
3. Generate the deploy file list after applying production exclusions.
4. Show the exact files to upload/delete.
5. Ask for explicit confirmation.

If Composer dependencies changed, handle the `vendor/` upload as a separate explicit step after building the production dependency bundle locally.

If the filtered deploy list is empty, do not connect to FTP/SFTP.

## Production Smoke Tests

Production smoke tests must be read-only. They may use safe GET requests for public pages, but must not:

- register users;
- create or edit events;
- change agenda/favorites;
- access admin write actions;
- run migrations.
