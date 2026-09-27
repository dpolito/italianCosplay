---
name: audit-log-required
description: Use when creating or changing any ItalianCosplay feature that performs a meaningful action, especially controller, service, or admin workflows. This skill reminds the agent to add the related technical audit log call at the same time as the feature implementation.
---

# Audit Log Required

## Purpose

Use this skill whenever you add or modify a feature that changes data, state, permissions, or content.

## Rule

Do not consider a feature complete until you have checked whether it needs an audit log entry.

If the feature has a meaningful action, add the log in the same implementation pass.

For ItalianCosplay, this is mandatory for:

- public feature flows
- admin actions
- moderation actions
- uploads
- create / update / delete flows
- authentication and password reset flows
- any workflow that changes persisted state

Typical cases:

- create / update / delete flows
- uploads
- approvals / rejections
- authentication and password reset flows
- admin moderation actions
- profile or portfolio changes

## Workflow

1. Identify the business action being introduced.
2. Decide the matching `action_type`.
3. Log the actor, target entity, entity id, timestamp context, and relevant payload.
4. Include `success` and failure details when the action can fail.
5. Use the central audit logging service and the shared `AuditLogActionType` constants.
6. If the feature has no audit log yet, add it before closing the task.

## Minimum check

Before closing the task, ask:

- Is there a technical trace for this action?
- Can I tell who did it, when, and on which entity?
- Are success and failure both covered where needed?
- Is the log central, consistent, and using the shared action type constants?

If the answer is no, add the log before stopping.
