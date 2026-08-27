---
name: notifications
description: Notification system rules for ItalianCosplay.it. Use when implementing emails, user notifications, reminders, verification flows, newsletters, alerts and communication features.
---

# ItalianCosplay Notifications Skill

## Purpose

This skill defines rules for communication systems inside ItalianCosplay.it.

Notifications are a strategic component of user engagement. The goal is not only sending messages. The goal is creating useful communication between:

- platform
- cosplayers
- event organizers
- advertisers
- community members

---

## Notification Philosophy

Notifications must provide value.

Every notification should answer:

- Why is the user receiving this?
- Is this useful?
- Can the user control it?

Avoid: spam, unnecessary messages, excessive frequency.

---

## Notification Principles

Every notification feature must consider:

1. User experience.
2. Privacy.
3. Consent.
4. Reliability.
5. Scalability.
6. Business objectives.

---

## Notification Types

The system should support different channels.

```
Email → In-App Notification → Push Notification (future)
```

---

## Notification Architecture

Notifications should use a centralized system.

Recommended flow:

```
Application Event → Notification Service → Queue / Scheduler → Delivery Channel → User
```

---

## Notification Service

Use a dedicated service: `NotificationService`.

Responsibilities: create notifications, choose channel, manage templates, track delivery, handle failures.

### Do Not Send Notifications Directly

**Avoid:**

```
Controller
  └── sendEmail()
```

Bad because: difficult to maintain, duplicates logic, difficult to test.

**Prefer:**

```
Controller → Business Service → NotificationService → Mailer
```

---

## Email System

Emails are a primary communication channel.

The system should support: transactional emails, user communications, marketing emails.

### Transactional Emails

Examples: account creation, email verification, password reset, event approval, security alerts.

These are operational messages.

### Marketing Emails

Examples: newsletters, event recommendations, sponsored communications.

Require: consent, unsubscribe management, GDPR compliance.

### Email Templates

Templates should be separated from business logic.

```
views/emails/
├── account/
├── events/
└── marketing/
```

### Email Rules

Emails should have: clear subject, readable layout, mobile compatibility, valid links.

### Mailer Service

Use centralized mail handling: `Mailer`.

Responsibilities: SMTP connection, sending, error handling, logging.

---

## User Account Notifications

User account actions may require notifications.

Examples: registration completed, email verification, password reset, password changed, suspicious login.

### Email Verification

Email verification should follow a secure flow.

```
User Registration → Generate Token → Send Verification Email → User Confirms → Activate Account
```

### Verification Token Rules

Tokens must: be random, expire, be single use, not contain sensitive information.

### Password Reset

Password reset notifications are security sensitive.

```
User Requests Reset → Generate Temporary Token → Send Email → Validate Token → Set New Password
```

### Password Reset Rules

Never send: current passwords, password hints, sensitive data.

Only send secure recovery links.

### Security Notifications

Users may receive alerts for: password changes, email changes, account actions.

---

## Event Notifications

Events are the main engagement feature.

Possible notifications: new event published, event updated, event approved, event cancelled, reminder before event date.

### Event Approval Flow

For user-submitted events:

```
User Creates Event → Admin Review → Approval Decision → Notification Sent
```

### Event Reminder System

Users can save events.

Possible reminders: one week before, one day before, custom reminder.

### Reminder Architecture

Use scheduled tasks.

```
Cron → Find Upcoming Events → Find Interested Users → Create Notifications → Send Messages
```

### Saved Events

Notifications may depend on user preferences.

Example: a user saved `Lucca Comics 2026`. The system can notify about date changes, location changes, important updates.

---

## Notification Preferences

Users must control communications.

Possible settings:

```
Email notifications
Event reminders
Newsletter
Marketing communications
Partner offers
```

### Preference Storage

Do not hardcode notification preferences. Use dedicated settings: `user_notification_preferences`.

### User Control

Users should be able to: enable notifications, disable notifications, unsubscribe, manage frequency.

### Frequency Management

Avoid notification fatigue.

Consider: daily limits, grouped notifications, digest emails.

---

## Notification Queue

For scalable delivery use queues or scheduled processing.

```
Notification Created → Pending Queue → Worker → Sent
```

### Notification Status

Use explicit states: `pending`, `processing`, `sent`, `failed`, `cancelled`.

### Failed Notifications

Failed delivery should be handled.

Consider: retries, error logging, fallback strategy.

### Notification Logs

Track delivery history.

Useful data: `notification_id`, `user_id`, `channel`, `status`, `sent_at`, `error`.

### Do Not Store Sensitive Content

Notification logs should avoid storing unnecessary personal information.

---

## Newsletter System

ItalianCosplay may support newsletters.

Newsletter goals: inform users about events, share useful content, increase returning visitors, promote community growth.

### Newsletter Principles

Newsletters must provide value.

Avoid: excessive frequency, generic messages, irrelevant promotions.

### Newsletter Consent

Marketing emails require explicit consent.

Store: consent status, consent date, source, withdrawal date.

### Unsubscribe Management

Every marketing email must provide: unsubscribe option, preference management link.

Unsubscribing must be simple.

### Newsletter Segmentation

Future segmentation may include:

```
Users interested in events → Regional events → Local recommendations
```

or:

```
Users interested in cosplay guides → Content newsletter
```

### Newsletter Scheduling

Newsletter sending should not block the application.

Use: queues, scheduled jobs, batch sending.

### Email Sending Limits

Consider: provider limits, sending speed, bounce management.

Avoid sending thousands of emails in one request.

### Marketing Automation

Future possibilities: welcome emails, event suggestions, inactive user reactivation.

Automation must always respect: consent, user preferences, frequency limits.

---

## Advertiser Notifications

Advertising customers may receive notifications.

Examples: campaign approved, payment confirmed, campaign started, campaign completed, report available.

### Commercial Communication

Commercial emails must be clear.

Include: company information, purpose, relevant links.

### Campaign Workflow Notifications

```
Campaign Created → Waiting Payment → Payment Confirmed → Campaign Active → Campaign Finished
```

Each important state change can trigger communication.

---

## Admin Notifications

Administrators need operational alerts.

Examples: new event submitted, event reported by user, payment issue, failed email delivery, system warning.

### Admin Notification Priority

Use priorities: `Low`, `Normal`, `High`, `Critical`.

### Internal Notifications

Not all notifications require email.

Examples: dashboard alerts, moderation tasks, system warnings.

---

## Notification Analytics

Measure notification effectiveness.

Possible metrics: sent, delivered, opened, clicked, unsubscribed.

### Email Analytics

Respect privacy rules.

Useful aggregated data: delivery rate, bounce rate, click rate.

Avoid invasive tracking.

---

## Notification and GDPR

Notifications must respect: purpose limitation, consent, data minimization, retention policies.

### Data Retention

Define how long to keep: delivery logs, failed messages, consent history.

---

## Notification Security

Protect: email addresses, tokens, notification content.

Never expose notification data to unauthorized users.

### Templates Security

Email templates must escape dynamic data.

Avoid: HTML injection, unsafe user content rendering.

---

## Notification Database Design

Notification data should have a dedicated structure.

Avoid storing notification logic inside unrelated tables.

### Notification Entity

Possible structure:

```
notifications
├── id
├── user_id
├── type
├── channel
├── status
├── title
├── content
├── created_at
└── sent_at
```

### Notification Types

Use identifiable notification types.

Examples: `account_verified`, `password_changed`, `event_approved`, `event_updated`, `event_reminder`, `newsletter`, `campaign_status_changed`.

Avoid generic messages without classification.

### Notification Preferences Table

Possible structure:

```
user_notification_preferences
├── user_id
├── email_events
├── email_newsletter
├── email_marketing
└── updated_at
```

### Email Templates Table

For dynamic systems, possible structure:

```
notification_templates
├── key
├── subject
├── body
├── channel
└── status
```

---

## Notification Services

Use dedicated services:

- `NotificationService`
- `EmailNotificationService`
- `NewsletterService`
- `ReminderService`

### Service Responsibilities

**NotificationService** — notification creation, user preferences, routing.

**EmailNotificationService** — email delivery, templates, mail provider integration.

**ReminderService** — scheduled reminders, event dates, saved content.

**NewsletterService** — subscribers, campaigns, batch sending.

---

## MVC Integration

Controllers should coordinate actions.

**Wrong:**

```
Controller → create notification → send email → update status
```

**Correct:**

```
Controller → Business Service → NotificationService → Mailer
```

---

## Cron Jobs

Notifications often require scheduled tasks.

**Daily:** send event reminders

**Hourly:** process pending notifications

**Weekly:** prepare newsletter

### Queue Processing

For large volumes:

```
Create Notification → Queue → Worker → Delivery
```

---

## Testing Notifications

Before releasing verify:

### Account

- verification email
- password reset
- security alerts

### Events

- approval
- updates
- reminders

### Marketing

- unsubscribe
- preferences
- consent

### Commercial

- campaign updates
- reports

---

## Notification Checklist

Before implementing a notification feature verify:

### Architecture

- Is NotificationService used?
- Is logic separated from controllers?

### User Experience

- Is the notification useful?
- Can users control it?

### Security

- Are tokens protected?
- Is personal data safe?

### GDPR

- Is consent handled?
- Is unsubscribe available?

### Performance

- Are emails asynchronous when needed?
- Are batch operations used?

---

## Final Notification Philosophy

ItalianCosplay notifications should create a relationship between the platform and its community.

The objective is not sending more messages. The objective is sending:

- the right message
- to the right user
- at the right moment

A professional notification system transforms occasional visitors into an active community.
