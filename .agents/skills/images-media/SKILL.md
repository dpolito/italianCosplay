---
name: images-media
description: Image and media management rules for ItalianCosplay.it. Use when implementing uploads, image processing, galleries, avatars, blog images, event images, banners and media storage systems.
---

# ItalianCosplay Images and Media Skill

## Purpose

This skill defines rules for managing images and media files inside ItalianCosplay.it.

Images are a strategic asset of the platform, used for:

- event discovery
- SEO
- social sharing
- user profiles
- editorial content
- advertising

Every media implementation must consider:

- performance
- security
- SEO
- storage scalability
- user experience

---

## Media Philosophy

Images must not be treated as simple uploaded files.

A media asset requires:

- validation
- processing
- optimization
- storage management
- metadata

Avoid creating isolated upload systems. All image processing should use shared services.

---

## Centralized Image Service

The application should use a dedicated image service: `ImageService`.

Responsibilities: validation, resizing, conversion, thumbnails, storage, deletion.

---

## Upload Flow

Every image upload follows:

```
User Upload → Validation → Security Checks → Image Processing → Conversion → Generate Variants → Storage → Database Reference
```

---

## Supported Formats

Allowed formats: JPEG, PNG, WebP.

Avoid storing unnecessary formats.

Preferred output: **WebP**, because it provides lower size, better performance, and good browser support.

---

## Upload Validation

Every upload must validate:

### File Type

Do not trust extensions. Validate MIME type and file content.

### File Size

Define limits depending on context.

- Avatar: small limit
- Event gallery: higher limit
- Banner: specific dimensions

### Image Dimensions

Validate dimensions when required.

- Avatar: square crop
- Banner: predefined formats

---

## Security Rules

Never trust uploaded files.

Protect against: malicious files, invalid MIME types, path traversal, dangerous filenames.

---

## File Names

Never store user-provided filenames directly.

**Bad:** `Mario_Cosplay!!.jpg`

**Good:** `random-generated-name.webp`

---

## Storage Structure

Media storage should be organized.

```
public_assets/
└── uploads/
    ├── events/
    ├── blog/
    ├── avatars/
    └── banners/
```

Avoid storing everything in one directory.

---

## Database References

Database should store metadata, not binary files.

**Avoid:**

```sql
image BLOB
```

**Prefer:**

```
image path
filename
type
size
metadata
```

### Entity Media Relations

Use dedicated relationships.

```
events
  └── event_images
```

or

```
blog_posts
  └── blog_images
```

Do not store multiple images in comma-separated fields.

---

## Image Variants

Generate different versions: `original`, `large`, `medium`, `thumbnail`.

Usage:

- `large` → detail page
- `medium` → cards
- `thumbnail` → lists

---

## Event Image Management

Events are one of the main image consumers.

Event images should support: multiple images, ordering, primary image, captions, deletion.

### Event Gallery Structure

Recommended relationship:

```
Event
  └── EventImages
```

Each image should have: `event_id`, file path, position, type, metadata.

---

## Primary Image

Every important entity can have a primary image, used for cards, social previews, Schema.org, search results.

The primary image should always be defined when possible.

### Gallery Ordering

Gallery order should be managed explicitly.

Do not rely on upload date, filename, or database ID. Use a `sort_order` field.

---

## Image Captions and Alt Text

Images should support accessibility and SEO metadata.

Possible fields: alt text, caption, description.

### SEO Image Rules

Every public image should consider: meaningful filename, alt attribute, correct dimensions, optimized format.

**Avoid:**

```html
<img src="image123.webp">
```

**Prefer:**

```html
<img alt="Cosplayer durante Lucca Comics 2026">
```

---

## Open Graph Images

Important pages should define social images (events, blog posts, landing pages).

Requirements: correct dimensions, optimized file, stable URL.

---

## Lazy Loading

Images below the first viewport should use lazy loading.

```html
<img loading="lazy">
```

Do not lazy load the main hero image unnecessarily.

---

## Responsive Images

When useful, use responsive images with `srcset` to allow browsers to choose the correct size.

---

## Performance Rules

Images must not negatively affect Core Web Vitals, page speed, or mobile experience.

Consider: compression, dimensions, caching, lazy loading.

---

## Avatar Management

User avatars require specific handling.

Features: upload, crop, resize, replacement, deletion.

### Avatar Processing

Recommended flow:

```
Upload → Validate → Square Crop → Resize → WebP Conversion → Save
```

### Avatar Security

Avatar uploads must verify ownership, limit size, validate formats.

Never allow users to access arbitrary storage paths.

---

## Banner Images

Advertising banners require stricter rules.

Validate: dimensions, format, destination, quality.

### Banner Optimization

Advertising images should load quickly, preserve quality, avoid layout shifts.

Consider: predefined sizes, responsive versions.

---

## Blog Images

Blog images should support: featured image, article gallery, inline images.

### Article Image Rules

Every important article should consider: featured image, Open Graph image, descriptive alt text.

---

## Media Replacement

Replacing images should be handled carefully.

Consider: cached URLs, indexed images, social sharing.

Avoid deleting files immediately if references may exist.

---

## Media Deletion

Before deleting media, verify:

- no entity references it
- no public URL depends on it
- no cache requires it

Prefer soft deletion and cleanup jobs.

### Media Cleanup

Unused files can be removed through scheduled tasks.

```
Cron → Find unused media → Review → Delete safely
```

---

## CDN Future Compatibility

Storage design should allow future migration.

```
Local Storage → Object Storage → CDN
```

Do not couple application logic to physical paths.

---

## ImageService Architecture

All image operations should use a centralized service: `ImageService`.

The service manages: upload validation, image processing, conversion, resizing, storage, deletion.

### ImageService Responsibilities

The service can provide methods such as:

- `upload()`
- `process()`
- `resize()`
- `convertToWebp()`
- `generateVariants()`
- `delete()`

### Separation of Responsibilities

Controllers should not process images.

**Bad:**

```
Controller
  ├── move uploaded file
  ├── resize image
  └── convert WebP
```

**Good:**

```
Controller → ImageService → Storage
```

---

## Storage Layer

Media storage should be abstracted.

Possible future implementations: local filesystem, object storage, CDN.

The application should not depend directly on storage details.

---

## Image Naming Convention

Generated filenames should be unique, predictable, safe.

**Good:** `event-lucca-comics-2026-a8f92.webp`

**Avoid:** `IMG_001_FINAL_NEW.jpg`

### Directory Convention

Recommended structure:

```
uploads/
├── events/
│   └── 2026/
│       └── image.webp
├── blog/
│   └── article-slug/
│       └── image.webp
├── avatars/
│   └── user-id/
│       └── avatar.webp
└── banners/
    └── campaign-id/
        └── banner.webp
```

---

## Database Media Metadata

Media tables should store useful metadata.

Possible fields: `id`, `entity_type`, `entity_id`, `filename`, `path`, `mime_type`, `size`, `width`, `height`, `alt_text`, `created_at`.

### Generic Media System

A generic media system can be considered when multiple entities need images.

```
media
  └── media_relations
        └── entities
```

Evaluate carefully. Do not introduce unnecessary abstraction. Prefer simple solutions when the domain does not require complexity.

---

## Media APIs

Future APIs may expose image URLs, responsive variants, metadata.

API responses must: validate permissions, avoid exposing private files, use stable URLs.

### Private Media

Some media may not be public (unpublished blog images, advertiser files, private documents).

Protect private storage separately.

---

## Image Processing Libraries

Use reliable server-side processing, e.g. PHP GD or compatible image libraries.

The chosen implementation must support: resize, crop, conversion, quality control.

### Image Quality

Balance visual quality, file size, loading speed.

Avoid: unnecessary high resolution, excessive compression.

---

## Accessibility Requirements

Every public image should consider accessibility.

Requirements: meaningful alt text, avoid decorative images without purpose, captions when useful.

---

## Media Cache

Generated images should support caching.

Consider: immutable filenames, browser caching, CDN caching.

---

## Media Security Checklist

Before completing a media feature verify:

### Upload

- Are files validated?
- Are filenames safe?
- Are permissions correct?

### Processing

- Are variants generated?
- Is WebP used where appropriate?

### Storage

- Are paths protected?
- Can storage migrate in future?

### SEO

- Are filenames meaningful?
- Are alt texts available?

### Performance

- Are images optimized?
- Is lazy loading applied correctly?

---

## Final Media Philosophy

Images are one of the strongest assets of ItalianCosplay.it.

A professional media system must transform uploads into optimized digital assets. Every image should be:

- secure
- fast
- accessible
- SEO friendly
- ready for future growth

Do not build simple upload forms. Build a media infrastructure.
