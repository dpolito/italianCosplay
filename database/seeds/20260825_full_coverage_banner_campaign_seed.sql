-- ItalianCosplay.it advertising coverage seed
-- Creates one banner campaign for every public ad position.
-- Date range: 2026-09-01 to 2026-12-31

INSERT INTO ad_banners
	(user_id, title, description, image_path, logo_path, target_url, alt_text, sponsor_name, facebook_url, instagram_url, tiktok_url, creative_type, type, status, is_active, created_at, updated_at)
SELECT
	10,
	'Spazio pubblicitario ItalianCosplay',
	'Banner dimostrativo per la copertura delle posizioni pubblicitarie del portale.',
	'/public_assets/uploads/banners/banner_pubblicitario.jpeg',
	NULL,
	'https://www.italiancosplay.it/',
	'Spazio pubblicitario disponibile su ItalianCosplay.it',
	'ItalianCosplay Media',
	NULL,
	NULL,
	NULL,
	'image',
	'sponsor',
	'approved',
	1,
	NOW(),
	NULL
FROM users u
WHERE u.id = 10
	AND NOT EXISTS (
		SELECT 1
		FROM ad_banners b
		WHERE b.user_id = 10
		  AND b.title = 'Spazio pubblicitario ItalianCosplay'
		  AND b.image_path = '/public_assets/uploads/banners/banner_pubblicitario.jpeg'
	);

INSERT INTO ad_campaigns
	(user_id, position_id, banner_id, target_type, target_value, start_date, end_date, price, currency, status, approval_status, notes, admin_notes, created_at, updated_at)
SELECT
	10,
	p.id,
	b.id,
	'national',
	NULL,
	'2026-08-01',
	'2026-12-31',
	ROUND(p.base_price * 4, 2),
	'EUR',
	'active',
	'approved',
	'Campagna seed di copertura completa per le posizioni banner pubbliche.',
	'Campagna idempotente dal 01/09/2026 al 31/12/2026, associata all\'utente 10.',
	NOW(),
	NULL
FROM ad_positions p
INNER JOIN ad_banners b
	ON b.user_id = 10
	AND b.title = 'Spazio pubblicitario ItalianCosplay'
	AND b.image_path = '/public_assets/uploads/banners/banner_pubblicitario.jpeg'
WHERE p.is_active = 1
	AND p.code IN (
		'homepage_top',
		'homepage_middle',
		'homepage_bottom',
		'events_national_top',
		'events_national_inline',
		'events_region_top',
		'events_region_inline',
		'events_province_top',
		'events_province_inline',
		'blog_top',
		'blog_inline',
		'blog_bottom',
		'event_header_sidebar',
		'event_after_description',
		'event_related_bottom',
		'blog_article_top',
		'blog_article_inline',
		'blog_article_bottom',
		'blog_listing_top',
		'blog_listing_inline',
		'blog_listing_bottom',
		'events_month_top',
		'events_month_inline',
		'events_month_bottom',
		'events_weekend_top',
		'events_weekend_inline',
		'events_weekend_bottom',
		'events_month_specific_top',
		'events_month_specific_inline',
		'events_weekend_specific_top',
		'events_weekend_specific_inline'
	)
	AND NOT EXISTS (
		SELECT 1
		FROM ad_campaigns c
		WHERE c.position_id = p.id
		  AND c.banner_id = b.id
		  AND c.start_date = '2026-09-01'
		  AND c.end_date = '2026-12-31'
	);
