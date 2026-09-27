# Testing and Deploy

## Prerequisiti

- PHP 8.2+ con estensioni richieste da `composer.json`.
- Composer installato.
- Docker/Docker Compose per l'ambiente locale.
- Credenziali Aruba configurate solo tramite variabili ambiente locali.

## Ambiente testing

I test non devono usare il database di produzione. Se configuri test con database reale, usa un database dedicato con nome chiaramente di test, ad esempio `italiancosplay_test`.

Variabili consigliate:

```bash
APP_ENV=testing
DB_HOST=127.0.0.1
DB_NAME=italiancosplay_test
DB_USER=root
DB_PASSWORD=root
IC_TEST_BASE_URL=http://localhost:8080
```

Se `IC_TEST_BASE_URL` non è impostata, i test HTTP vengono saltati. Le route pubbliche usate dallo smoke test seguono l'app attuale: `/register` è la registrazione pubblica esistente.

## Comandi

```bash
composer test
composer smoke
IC_SMOKE_BASE_URL=https://www.italiancosplay.it composer smoke
```

`composer test` esegue:

1. lint PHP su tutti i file PHP rilevanti;
2. PHPUnit.

## Credenziali Aruba

Non inserire mai credenziali in Git. Usa variabili ambiente locali:

```bash
IC_DEPLOY_METHOD=sftp
IC_DEPLOY_HOST=...
IC_DEPLOY_USER=...
IC_DEPLOY_PATH=/percorso/remoto
IC_PRODUCTION_URL=https://www.italiancosplay.it
```

Per provare senza modificare produzione:

```bash
IC_DEPLOY_DRY_RUN=1 ./scripts/deploy.sh "Descrizione modifica"
```

Al primo deploy, se non esiste ancora un tag `deploy-*`, imposta esplicitamente la base da confrontare:

```bash
IC_DEPLOY_BASE_REF=HEAD~1 IC_DEPLOY_DRY_RUN=1 ./scripts/deploy.sh "Preview deploy"
```

## Deploy

```bash
./scripts/deploy.sh "Descrizione modifica"
```

Il workflow è fail-safe:

```text
lint -> PHPUnit -> commit -> push -> riepilogo diff -> conferma -> upload -> smoke production -> tag deploy
```

Se uno step fallisce, gli step successivi non vengono eseguiti.

## Git non significa FTP

Non tutti i file committati devono essere caricati su Aruba via FTP/SFTP.

Questi file servono al repository, alla pipeline o allo sviluppo locale e non devono essere pubblicati sul server remoto:

- `.env`
- `.env.example`
- `.gitignore`
- `.agents/`
- `.codex/`
- `.continue/`
- `.playwright-cli/`
- `.traeignore`
- `composer.json`
- `composer.lock`
- `docs/`
- `phpunit.xml.dist`
- `scripts/`
- `tests/`
- `vendor/`
- `node_modules/`
- `app/config/database.php`

Le variabili `IC_DEPLOY_*` possono stare nel `.env` locale, ma lo script deve usarle senza stamparle e senza caricare il `.env` sul server.

Se il filtro dei file deployabili restituisce una lista vuota, non bisogna aprire una connessione FTP/SFTP: non c'è nulla da pubblicare.

## Esclusioni

Sono esclusi dal deploy:

- `.env`
- `.env.example`
- `.git`
- `.gitignore`
- `vendor`
- `node_modules`
- `docs`
- `phpunit.xml.dist`
- `scripts`
- `tests`
- `app/config/database.php`
- `public_assets/uploads`
- `storage/logs`
- `storage/cache`
- dump SQL

## Migration

Le migration in `database/migrations` vengono rilevate e segnalate prima del deploy. La prima versione dello script non applica migration distruttive o automatiche in produzione. Devono essere valutate e applicate con conferma separata.

## Rollback

Il deploy viene marcato solo dopo upload e smoke test riusciti con un tag `deploy-YYYYMMDD-HHMMSS`.

Se l'upload fallisce a metà, non viene creato il tag: il deploy successivo riparte dall'ultimo tag valido. Se uno smoke test fallisce, verificare i file caricati e ripristinare dal commit/tag precedente. Il rollback database è separato e non deve essere considerato automatico.

## Troubleshooting

- `.env` non caricato su Aruba: verifica che `vendor/autoload.php` esista in produzione e che `app/bootstrap/env.php` sia deployato. `safeLoad()` non fallisce se `.env` manca, quindi le variabili server restano valide.
- PHPUnit non parte: esegui `composer install`.
- Smoke locale fallisce: avvia Docker con `docker compose up -d` e verifica `http://localhost:8080`.
