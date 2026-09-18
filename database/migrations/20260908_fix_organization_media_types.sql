ALTER TABLE entity_images
    MODIFY COLUMN entity_type ENUM(
        'event',
        'event_master',
        'blog_post',
        'guest',
        'organization_logo',
        'organization_cover'
    ) NOT NULL;
