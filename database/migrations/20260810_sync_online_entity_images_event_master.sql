ALTER TABLE entity_images
	MODIFY entity_type ENUM('event', 'event_master', 'blog_post', 'guest') NOT NULL;
