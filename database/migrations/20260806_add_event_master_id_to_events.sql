ALTER TABLE events
	ADD COLUMN event_master_id INT NULL AFTER tipo_evento_id,
	ADD INDEX idx_events_event_master_id (event_master_id),
	ADD CONSTRAINT fk_events_event_master
		FOREIGN KEY (event_master_id) REFERENCES events_master(id)
		ON DELETE SET NULL
		ON UPDATE CASCADE;
