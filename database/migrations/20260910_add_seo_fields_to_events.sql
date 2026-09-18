ALTER TABLE events
	ADD COLUMN seo_title VARCHAR(255) NULL AFTER slug,
	ADD COLUMN seo_description VARCHAR(500) NULL AFTER seo_title;
