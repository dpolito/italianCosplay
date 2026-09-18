# Posizioni Advertising

Questo documento mappa le posizioni pubblicitarie pubbliche di ItalianCosplay.it in base al loro ambito di pagina, alle regole di rendering e ai seed di riferimento.

## Regole Di Rendering

- Un banner viene renderizzato solo quando esiste una campagna attiva per la posizione richiesta.
- Se non esiste una campagna attiva, il frontend non mostra nulla.
- Le pagine dei comuni non hanno posizioni pubblicitarie dedicate.
- Le liste eventi nazionali, regionali e provinciali usano codici posizione diversi.
- Anche le pagine del mese e del weekend hanno posizioni dedicate, comprese le landing specifiche del mese e del weekend.

## Flusso Di Rendering Condiviso

- `App\Helpers\AdPlacement::render($positionCode, $page)` viene usato nei template pubblici.
- `App\Services\AdRotationService::getBannerForPosition()` risolve la campagna attiva.
- `App\Repositories\AdCampaignRepository::findActiveByPosition()` filtra le campagne attive, approvate e valide nel periodo.
- `App\Helpers\AdPlacement` restituisce una stringa vuota quando non è disponibile alcuna campagna.

## Gruppi Di Posizioni

### Homepage

- `homepage_top`
- `homepage_middle`
- `homepage_bottom`

Seed file:
- [database/seeds/20260819_ad_positions_seed.sql](/Users/domenicopolito/Desktop/Domini/domenico/italianCosplay/database/seeds/20260819_ad_positions_seed.sql)

### Liste Eventi

- `events_national_top`
- `events_national_inline`
- `events_region_top`
- `events_region_inline`
- `events_province_top`
- `events_province_inline`

Regola di rendering:
- La lista nazionale usa le posizioni nazionali.
- La lista regionale usa le posizioni regionali.
- La lista provinciale usa le posizioni provinciali.
- La lista comunale non renderizza posizioni pubblicitarie.

Seed file:
- [database/seeds/20260819_ad_positions_seed.sql](/Users/domenicopolito/Desktop/Domini/domenico/italianCosplay/database/seeds/20260819_ad_positions_seed.sql)

### Scheda Evento

- `event_header_sidebar`
- `event_after_description`
- `event_related_bottom`

Seed file:
- [database/seeds/20260819_event_blog_ad_positions_seed.sql](/Users/domenicopolito/Desktop/Domini/domenico/italianCosplay/database/seeds/20260819_event_blog_ad_positions_seed.sql)

### Lista Blog E Articolo

- `blog_top`
- `blog_inline`
- `blog_bottom`
- `blog_listing_top`
- `blog_listing_inline`
- `blog_listing_bottom`
- `blog_article_top`
- `blog_article_inline`
- `blog_article_bottom`

Seed file:
- [database/seeds/20260819_ad_positions_seed.sql](/Users/domenicopolito/Desktop/Domini/domenico/italianCosplay/database/seeds/20260819_ad_positions_seed.sql)
- [database/seeds/20260819_event_blog_ad_positions_seed.sql](/Users/domenicopolito/Desktop/Domini/domenico/italianCosplay/database/seeds/20260819_event_blog_ad_positions_seed.sql)

### Pagine Mese

- `events_month_top`
- `events_month_inline`
- `events_month_bottom`
- `events_month_specific_top`
- `events_month_specific_inline`
- `events_month_specific_bottom`

Usate da:
- `app/views/events/mese.php`
- `app/views/events/mese-specifico.php`

Seed file:
- [database/seeds/20260819_month_weekend_ad_positions_seed.sql](/Users/domenicopolito/Desktop/Domini/domenico/italianCosplay/database/seeds/20260819_month_weekend_ad_positions_seed.sql)

### Pagine Weekend

- `events_weekend_top`
- `events_weekend_inline`
- `events_weekend_bottom`
- `events_weekend_specific_top`
- `events_weekend_specific_inline`
- `events_weekend_specific_bottom`

Usate da:
- `app/views/events/weekend.php`
- `app/views/events/weekend-specifico.php`

Seed file:
- [database/seeds/20260819_month_weekend_ad_positions_seed.sql](/Users/domenicopolito/Desktop/Domini/domenico/italianCosplay/database/seeds/20260819_month_weekend_ad_positions_seed.sql)

## Mappatura Pagina -> Codici

| Pagina | Codici |
|---|---|
| Homepage | `homepage_top`, `homepage_middle`, `homepage_bottom` |
| Lista eventi nazionale | `events_national_top`, `events_national_inline` |
| Lista eventi regionale | `events_region_top`, `events_region_inline` |
| Lista eventi provinciale | `events_province_top`, `events_province_inline` |
| Lista eventi comunale | nessuno |
| Scheda evento | `event_header_sidebar`, `event_after_description`, `event_related_bottom` |
| Lista blog | `blog_listing_top`, `blog_listing_inline`, `blog_listing_bottom` |
| Articolo blog | `blog_article_top`, `blog_article_inline`, `blog_article_bottom` |
| Calendario mensile | `events_month_top`, `events_month_inline`, `events_month_bottom` |
| Pagina mese specifico | `events_month_specific_top`, `events_month_specific_inline`, `events_month_specific_bottom` |
| Calendario weekend | `events_weekend_top`, `events_weekend_inline`, `events_weekend_bottom` |
| Pagina weekend specifico | `events_weekend_specific_top`, `events_weekend_specific_inline`, `events_weekend_specific_bottom` |

## Note

- Tutte le righe dei seed usano `ON DUPLICATE KEY UPDATE`, quindi il seed può essere rilanciato in sicurezza.
- Se aggiungi una nuova pagina pubblica con potenziale monetizzazione, definisci prima il codice della posizione e poi aggiungi l'hook di rendering nel template.
- Mantieni le pagine dei comuni senza banner dedicati, salvo una ragione commerciale molto specifica.
