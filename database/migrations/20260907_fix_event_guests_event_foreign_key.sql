-- event_guests must reference the current events table, not the legacy backup.
-- Remove only orphaned relations before recreating the constraint.
DELETE eg
FROM event_guests eg
LEFT JOIN events e ON e.id = eg.event_id
WHERE e.id IS NULL;

ALTER TABLE event_guests
    DROP FOREIGN KEY fk_event_guests_event,
    ADD CONSTRAINT fk_event_guests_event_current
        FOREIGN KEY (event_id) REFERENCES events(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE;
