-- ItalianCosplay.it advertising positions seed
-- Event detail page and blog/article placements.

INSERT INTO ad_positions
	(name, code, page, description, width, height, mobile_width, mobile_height, max_slots, rotation_type, base_price, currency, min_days, max_days, estimated_monthly_impressions, average_ctr, is_sponsored_rel_required, is_active, sort_order, created_at, updated_at)
VALUES
	(
		'Event Header Sidebar',
		'event_header_sidebar',
		'event_detail',
		'Banner nella colonna laterale della scheda evento, accanto alla hero su desktop.',
		300, 600, 320, 100,
		1, 'fixed', 240.00, 'EUR',
		7, 60, 85000, 1.45,
		1, 1, 10, NOW(), NULL
	),
	(
		'Event After Description',
		'event_after_description',
		'event_detail',
		'Banner subito dopo la descrizione principale della scheda evento.',
		728, 90, 320, 100,
		2, 'random', 180.00, 'EUR',
		7, 60, 65000, 1.20,
		1, 1, 20, NOW(), NULL
	),
	(
		'Event Related Bottom',
		'event_related_bottom',
		'event_detail',
		'Banner prima degli eventi correlati e delle sezioni di chiusura.',
		728, 90, 320, 100,
		2, 'random', 150.00, 'EUR',
		7, 60, 45000, 1.00,
		1, 1, 30, NOW(), NULL
	),
	(
		'Blog Article Top',
		'blog_article_top',
		'blog_article',
		'Banner nella parte alta dell articolo, dopo hero e metadati.',
		970, 250, 320, 100,
		1, 'fixed', 180.00, 'EUR',
		7, 60, 70000, 1.10,
		1, 1, 40, NOW(), NULL
	),
	(
		'Blog Article Inline',
		'blog_article_inline',
		'blog_article',
		'Banner inline nel corpo dell articolo, dopo una o due sezioni di contenuto.',
		728, 90, 320, 100,
		2, 'random', 200.00, 'EUR',
		7, 60, 90000, 1.30,
		1, 1, 50, NOW(), NULL
	),
	(
		'Blog Article Bottom',
		'blog_article_bottom',
		'blog_article',
		'Banner in chiusura articolo, prima dei contenuti correlati o del footer.',
		728, 90, 320, 100,
		2, 'random', 120.00, 'EUR',
		7, 60, 50000, 0.85,
		1, 1, 60, NOW(), NULL
	),
	(
		'Blog Listing Top',
		'blog_listing_top',
		'blog',
		'Banner in cima alla lista articoli del blog.',
		970, 250, 320, 100,
		1, 'fixed', 150.00, 'EUR',
		7, 60, 60000, 0.95,
		1, 1, 70, NOW(), NULL
	),
	(
		'Blog Listing Inline',
		'blog_listing_inline',
		'blog',
		'Banner inline nella griglia o tra i primi articoli del blog.',
		728, 90, 320, 100,
		2, 'random', 110.00, 'EUR',
		7, 60, 55000, 0.90,
		1, 1, 80, NOW(), NULL
	),
	(
		'Blog Listing Bottom',
		'blog_listing_bottom',
		'blog',
		'Banner prima della paginazione o del footer del blog.',
		728, 90, 320, 100,
		2, 'random', 80.00, 'EUR',
		7, 60, 35000, 0.75,
		1, 1, 90, NOW(), NULL
	)
ON DUPLICATE KEY UPDATE
	name = VALUES(name),
	page = VALUES(page),
	description = VALUES(description),
	width = VALUES(width),
	height = VALUES(height),
	mobile_width = VALUES(mobile_width),
	mobile_height = VALUES(mobile_height),
	max_slots = VALUES(max_slots),
	rotation_type = VALUES(rotation_type),
	base_price = VALUES(base_price),
	currency = VALUES(currency),
	min_days = VALUES(min_days),
	max_days = VALUES(max_days),
	estimated_monthly_impressions = VALUES(estimated_monthly_impressions),
	average_ctr = VALUES(average_ctr),
	is_sponsored_rel_required = VALUES(is_sponsored_rel_required),
	is_active = VALUES(is_active),
	sort_order = VALUES(sort_order),
	updated_at = NOW();

INSERT INTO ad_position_prices (position_id, duration_days, price, currency, is_active, created_at, updated_at)
SELECT p.id, price_data.duration_days, price_data.price, 'EUR', 1, NOW(), NULL
FROM ad_positions p
JOIN (
	SELECT 'event_header_sidebar' AS code, 7 AS duration_days, 240.00 AS price
	UNION ALL SELECT 'event_header_sidebar', 15, 455.00
	UNION ALL SELECT 'event_header_sidebar', 30, 830.00
	UNION ALL SELECT 'event_header_sidebar', 60, 1510.00
	UNION ALL SELECT 'event_after_description', 7, 180.00
	UNION ALL SELECT 'event_after_description', 15, 340.00
	UNION ALL SELECT 'event_after_description', 30, 620.00
	UNION ALL SELECT 'event_after_description', 60, 1120.00
	UNION ALL SELECT 'event_related_bottom', 7, 150.00
	UNION ALL SELECT 'event_related_bottom', 15, 285.00
	UNION ALL SELECT 'event_related_bottom', 30, 520.00
	UNION ALL SELECT 'event_related_bottom', 60, 940.00
	UNION ALL SELECT 'blog_article_top', 7, 180.00
	UNION ALL SELECT 'blog_article_top', 15, 340.00
	UNION ALL SELECT 'blog_article_top', 30, 620.00
	UNION ALL SELECT 'blog_article_top', 60, 1120.00
	UNION ALL SELECT 'blog_article_inline', 7, 200.00
	UNION ALL SELECT 'blog_article_inline', 15, 380.00
	UNION ALL SELECT 'blog_article_inline', 30, 690.00
	UNION ALL SELECT 'blog_article_inline', 60, 1250.00
	UNION ALL SELECT 'blog_article_bottom', 7, 120.00
	UNION ALL SELECT 'blog_article_bottom', 15, 225.00
	UNION ALL SELECT 'blog_article_bottom', 30, 410.00
	UNION ALL SELECT 'blog_article_bottom', 60, 760.00
	UNION ALL SELECT 'blog_listing_top', 7, 150.00
	UNION ALL SELECT 'blog_listing_top', 15, 285.00
	UNION ALL SELECT 'blog_listing_top', 30, 520.00
	UNION ALL SELECT 'blog_listing_top', 60, 940.00
	UNION ALL SELECT 'blog_listing_inline', 7, 110.00
	UNION ALL SELECT 'blog_listing_inline', 15, 210.00
	UNION ALL SELECT 'blog_listing_inline', 30, 380.00
	UNION ALL SELECT 'blog_listing_inline', 60, 690.00
	UNION ALL SELECT 'blog_listing_bottom', 7, 80.00
	UNION ALL SELECT 'blog_listing_bottom', 15, 150.00
	UNION ALL SELECT 'blog_listing_bottom', 30, 270.00
	UNION ALL SELECT 'blog_listing_bottom', 60, 490.00
) AS price_data ON price_data.code = p.code
ON DUPLICATE KEY UPDATE
	price = VALUES(price),
	currency = VALUES(currency),
	is_active = VALUES(is_active),
	updated_at = NOW();
