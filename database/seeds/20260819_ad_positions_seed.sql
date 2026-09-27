-- ItalianCosplay.it advertising positions seed
-- Scope:
-- - national list pages
-- - regional list pages
-- - provincial list pages
-- - homepage and blog placements
-- No dedicated municipality/common page placements are created on purpose.

INSERT INTO ad_positions
	(name, code, page, description, width, height, mobile_width, mobile_height, max_slots, rotation_type, base_price, currency, min_days, max_days, estimated_monthly_impressions, average_ctr, is_sponsored_rel_required, is_active, sort_order, created_at, updated_at)
VALUES
	(
		'Homepage Hero Banner',
		'homepage_top',
		'homepage',
		'Banner principale in cima alla homepage, subito dopo l hero.',
		970, 250, 320, 100,
		1, 'fixed', 180.00, 'EUR',
		7, 60, 120000, 1.20,
		1, 1, 10, NOW(), NULL
	),
	(
		'Homepage Middle Banner',
		'homepage_middle',
		'homepage',
		'Banner inserito tra i blocchi principali della homepage.',
		728, 90, 320, 100,
		2, 'random', 120.00, 'EUR',
		7, 60, 90000, 1.00,
		1, 1, 20, NOW(), NULL
	),
	(
		'Homepage Bottom Banner',
		'homepage_bottom',
		'homepage',
		'Banner prima del footer della homepage.',
		728, 90, 320, 100,
		2, 'random', 90.00, 'EUR',
		7, 60, 60000, 0.90,
		1, 1, 30, NOW(), NULL
	),
	(
		'Eventi Italia Top',
		'events_national_top',
		'events_national',
		'Banner per la lista nazionale degli eventi, visibile solo quando non si filtra per regione o provincia.',
		970, 250, 320, 100,
		1, 'fixed', 220.00, 'EUR',
		7, 60, 150000, 1.40,
		1, 1, 40, NOW(), NULL
	),
	(
		'Eventi Italia In-List',
		'events_national_inline',
		'events_national',
		'Banner inline nella lista nazionale degli eventi, dopo il primo blocco di card.',
		728, 90, 320, 100,
		2, 'random', 160.00, 'EUR',
		7, 60, 110000, 1.10,
		1, 1, 50, NOW(), NULL
	),
	(
		'Eventi Regione Top',
		'events_region_top',
		'events_region',
		'Banner dedicato alle liste eventi per regione, ad esempio Toscana.',
		970, 250, 320, 100,
		1, 'fixed', 150.00, 'EUR',
		7, 60, 70000, 1.10,
		1, 1, 60, NOW(), NULL
	),
	(
		'Eventi Regione In-List',
		'events_region_inline',
		'events_region',
		'Banner inline nelle liste regionali dopo il primo blocco di risultati.',
		728, 90, 320, 100,
		2, 'random', 110.00, 'EUR',
		7, 60, 50000, 0.95,
		1, 1, 70, NOW(), NULL
	),
	(
		'Eventi Provincia Top',
		'events_province_top',
		'events_province',
		'Banner dedicato alle liste eventi per provincia.',
		970, 250, 320, 100,
		1, 'fixed', 110.00, 'EUR',
		7, 60, 40000, 0.90,
		1, 1, 80, NOW(), NULL
	),
	(
		'Eventi Provincia In-List',
		'events_province_inline',
		'events_province',
		'Banner inline nelle liste provinciali.',
		728, 90, 320, 100,
		1, 'random', 80.00, 'EUR',
		7, 60, 30000, 0.80,
		1, 1, 90, NOW(), NULL
	),
	(
		'Blog Top',
		'blog_top',
		'blog',
		'Banner in cima alla lista blog.',
		970, 250, 320, 100,
		1, 'fixed', 140.00, 'EUR',
		7, 60, 80000, 1.00,
		1, 1, 100, NOW(), NULL
	),
	(
		'Blog Inline',
		'blog_inline',
		'blog',
		'Banner inline nella lista blog o negli articoli lunghi.',
		728, 90, 320, 100,
		2, 'random', 100.00, 'EUR',
		7, 60, 65000, 0.90,
		1, 1, 110, NOW(), NULL
	),
	(
		'Blog Bottom',
		'blog_bottom',
		'blog',
		'Banner prima del footer o a fine articolo.',
		728, 90, 320, 100,
		2, 'random', 75.00, 'EUR',
		7, 60, 45000, 0.75,
		1, 1, 120, NOW(), NULL
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
	SELECT 'homepage_top' AS code, 7 AS duration_days, 180.00 AS price
	UNION ALL SELECT 'homepage_top', 15, 340.00
	UNION ALL SELECT 'homepage_top', 30, 620.00
	UNION ALL SELECT 'homepage_top', 60, 1120.00
	UNION ALL SELECT 'homepage_middle', 7, 120.00
	UNION ALL SELECT 'homepage_middle', 15, 225.00
	UNION ALL SELECT 'homepage_middle', 30, 410.00
	UNION ALL SELECT 'homepage_middle', 60, 760.00
	UNION ALL SELECT 'homepage_bottom', 7, 90.00
	UNION ALL SELECT 'homepage_bottom', 15, 170.00
	UNION ALL SELECT 'homepage_bottom', 30, 310.00
	UNION ALL SELECT 'homepage_bottom', 60, 560.00
	UNION ALL SELECT 'events_national_top', 7, 220.00
	UNION ALL SELECT 'events_national_top', 15, 415.00
	UNION ALL SELECT 'events_national_top', 30, 760.00
	UNION ALL SELECT 'events_national_top', 60, 1380.00
	UNION ALL SELECT 'events_national_inline', 7, 160.00
	UNION ALL SELECT 'events_national_inline', 15, 300.00
	UNION ALL SELECT 'events_national_inline', 30, 550.00
	UNION ALL SELECT 'events_national_inline', 60, 1000.00
	UNION ALL SELECT 'events_region_top', 7, 150.00
	UNION ALL SELECT 'events_region_top', 15, 285.00
	UNION ALL SELECT 'events_region_top', 30, 520.00
	UNION ALL SELECT 'events_region_top', 60, 940.00
	UNION ALL SELECT 'events_region_inline', 7, 110.00
	UNION ALL SELECT 'events_region_inline', 15, 210.00
	UNION ALL SELECT 'events_region_inline', 30, 380.00
	UNION ALL SELECT 'events_region_inline', 60, 690.00
	UNION ALL SELECT 'events_province_top', 7, 110.00
	UNION ALL SELECT 'events_province_top', 15, 210.00
	UNION ALL SELECT 'events_province_top', 30, 380.00
	UNION ALL SELECT 'events_province_top', 60, 690.00
	UNION ALL SELECT 'events_province_inline', 7, 80.00
	UNION ALL SELECT 'events_province_inline', 15, 150.00
	UNION ALL SELECT 'events_province_inline', 30, 270.00
	UNION ALL SELECT 'events_province_inline', 60, 490.00
	UNION ALL SELECT 'blog_top', 7, 140.00
	UNION ALL SELECT 'blog_top', 15, 265.00
	UNION ALL SELECT 'blog_top', 30, 480.00
	UNION ALL SELECT 'blog_top', 60, 880.00
	UNION ALL SELECT 'blog_inline', 7, 100.00
	UNION ALL SELECT 'blog_inline', 15, 190.00
	UNION ALL SELECT 'blog_inline', 30, 345.00
	UNION ALL SELECT 'blog_inline', 60, 630.00
	UNION ALL SELECT 'blog_bottom', 7, 75.00
	UNION ALL SELECT 'blog_bottom', 15, 140.00
	UNION ALL SELECT 'blog_bottom', 30, 255.00
	UNION ALL SELECT 'blog_bottom', 60, 460.00
) AS price_data ON price_data.code = p.code
ON DUPLICATE KEY UPDATE
	price = VALUES(price),
	currency = VALUES(currency),
	is_active = VALUES(is_active),
	updated_at = NOW();
