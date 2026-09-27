-- ItalianCosplay.it advertising positions seed
-- Monthly and weekend event listing placements.

INSERT INTO ad_positions
	(name, code, page, description, width, height, mobile_width, mobile_height, max_slots, rotation_type, base_price, currency, min_days, max_days, estimated_monthly_impressions, average_ctr, is_sponsored_rel_required, is_active, sort_order, created_at, updated_at)
VALUES
	(
		'Eventi Mese Top',
		'events_month_top',
		'events_month',
		'Banner principale nella pagina eventi del mese, sopra l elenco card.',
		970, 250, 320, 100,
		1, 'fixed', 140.00, 'EUR',
		7, 60, 65000, 1.00,
		1, 1, 10, NOW(), NULL
	),
	(
		'Eventi Mese Inline',
		'events_month_inline',
		'events_month',
		'Banner inline nella pagina eventi del mese, tra i blocchi di contenuto.',
		728, 90, 320, 100,
		2, 'random', 100.00, 'EUR',
		7, 60, 45000, 0.85,
		1, 1, 20, NOW(), NULL
	),
	(
		'Eventi Mese Bottom',
		'events_month_bottom',
		'events_month',
		'Banner di chiusura nella pagina eventi del mese, prima della FAQ.',
		728, 90, 320, 100,
		2, 'random', 75.00, 'EUR',
		7, 60, 30000, 0.70,
		1, 1, 30, NOW(), NULL
	),
	(
		'Eventi Weekend Top',
		'events_weekend_top',
		'events_weekend',
		'Banner principale nella pagina eventi del weekend, sopra il riepilogo.',
		970, 250, 320, 100,
		1, 'fixed', 150.00, 'EUR',
		7, 60, 70000, 1.05,
		1, 1, 40, NOW(), NULL
	),
	(
		'Eventi Weekend Inline',
		'events_weekend_inline',
		'events_weekend',
		'Banner inline nella pagina eventi del weekend, vicino alla griglia eventi.',
		728, 90, 320, 100,
		2, 'random', 110.00, 'EUR',
		7, 60, 50000, 0.90,
		1, 1, 50, NOW(), NULL
	),
	(
		'Eventi Weekend Bottom',
		'events_weekend_bottom',
		'events_weekend',
		'Banner finale della pagina weekend, prima del contenuto lungo o footer.',
		728, 90, 320, 100,
		2, 'random', 80.00, 'EUR',
		7, 60, 32000, 0.75,
		1, 1, 60, NOW(), NULL
	),
	(
		'Eventi Mese Specifico Top',
		'events_month_specific_top',
		'events_month_specific',
		'Banner dedicato alle pagine del mese specifico, ad esempio ottobre o dicembre.',
		970, 250, 320, 100,
		1, 'fixed', 125.00, 'EUR',
		7, 60, 55000, 0.95,
		1, 1, 70, NOW(), NULL
	),
	(
		'Eventi Mese Specifico Inline',
		'events_month_specific_inline',
		'events_month_specific',
		'Banner inline nelle pagine del mese specifico.',
		728, 90, 320, 100,
		2, 'random', 90.00, 'EUR',
		7, 60, 40000, 0.80,
		1, 1, 80, NOW(), NULL
	),
	(
		'Eventi Weekend Specifico Top',
		'events_weekend_specific_top',
		'events_weekend_specific',
		'Banner dedicato alle pagine weekend specifiche.',
		970, 250, 320, 100,
		1, 'fixed', 130.00, 'EUR',
		7, 60, 60000, 1.00,
		1, 1, 90, NOW(), NULL
	),
	(
		'Eventi Weekend Specifico Inline',
		'events_weekend_specific_inline',
		'events_weekend_specific',
		'Banner inline nelle pagine weekend specifiche.',
		728, 90, 320, 100,
		2, 'random', 95.00, 'EUR',
		7, 60, 42000, 0.85,
		1, 1, 100, NOW(), NULL
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
	SELECT 'events_month_top' AS code, 7 AS duration_days, 140.00 AS price
	UNION ALL SELECT 'events_month_top', 15, 265.00
	UNION ALL SELECT 'events_month_top', 30, 480.00
	UNION ALL SELECT 'events_month_top', 60, 880.00
	UNION ALL SELECT 'events_month_inline', 7, 100.00
	UNION ALL SELECT 'events_month_inline', 15, 190.00
	UNION ALL SELECT 'events_month_inline', 30, 345.00
	UNION ALL SELECT 'events_month_inline', 60, 630.00
	UNION ALL SELECT 'events_month_bottom', 7, 75.00
	UNION ALL SELECT 'events_month_bottom', 15, 140.00
	UNION ALL SELECT 'events_month_bottom', 30, 255.00
	UNION ALL SELECT 'events_month_bottom', 60, 460.00
	UNION ALL SELECT 'events_weekend_top', 7, 150.00
	UNION ALL SELECT 'events_weekend_top', 15, 285.00
	UNION ALL SELECT 'events_weekend_top', 30, 520.00
	UNION ALL SELECT 'events_weekend_top', 60, 940.00
	UNION ALL SELECT 'events_weekend_inline', 7, 110.00
	UNION ALL SELECT 'events_weekend_inline', 15, 210.00
	UNION ALL SELECT 'events_weekend_inline', 30, 380.00
	UNION ALL SELECT 'events_weekend_inline', 60, 690.00
	UNION ALL SELECT 'events_weekend_bottom', 7, 80.00
	UNION ALL SELECT 'events_weekend_bottom', 15, 150.00
	UNION ALL SELECT 'events_weekend_bottom', 30, 270.00
	UNION ALL SELECT 'events_weekend_bottom', 60, 490.00
	UNION ALL SELECT 'events_month_specific_top', 7, 125.00
	UNION ALL SELECT 'events_month_specific_top', 15, 240.00
	UNION ALL SELECT 'events_month_specific_top', 30, 440.00
	UNION ALL SELECT 'events_month_specific_top', 60, 800.00
	UNION ALL SELECT 'events_month_specific_inline', 7, 90.00
	UNION ALL SELECT 'events_month_specific_inline', 15, 170.00
	UNION ALL SELECT 'events_month_specific_inline', 30, 310.00
	UNION ALL SELECT 'events_month_specific_inline', 60, 560.00
	UNION ALL SELECT 'events_weekend_specific_top', 7, 130.00
	UNION ALL SELECT 'events_weekend_specific_top', 15, 250.00
	UNION ALL SELECT 'events_weekend_specific_top', 30, 460.00
	UNION ALL SELECT 'events_weekend_specific_top', 60, 840.00
	UNION ALL SELECT 'events_weekend_specific_inline', 7, 95.00
	UNION ALL SELECT 'events_weekend_specific_inline', 15, 180.00
	UNION ALL SELECT 'events_weekend_specific_inline', 30, 325.00
	UNION ALL SELECT 'events_weekend_specific_inline', 60, 590.00
) AS price_data ON price_data.code = p.code
ON DUPLICATE KEY UPDATE
	price = VALUES(price),
	currency = VALUES(currency),
	is_active = VALUES(is_active),
	updated_at = NOW();
