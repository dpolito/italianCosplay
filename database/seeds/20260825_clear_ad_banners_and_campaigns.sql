-- ItalianCosplay.it advertising cleanup seed
-- Svuota le campagne e i banner pubblicitari prima del seed di copertura.
-- Eseguire prima di 20260825_full_coverage_banner_campaign_seed.sql.

START TRANSACTION;

-- Le tabelle di tracking collegate alle campagne vengono svuotate dai CASCADE
-- definiti nello schema advertising. Prenotazioni e ordini restano presenti.
DELETE FROM ad_campaigns;
DELETE FROM ad_banners;

COMMIT;
