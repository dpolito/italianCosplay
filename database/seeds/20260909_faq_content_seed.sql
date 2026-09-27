-- ItalianCosplay.it FAQ content seed
-- Requires: database/migrations/20260909_create_faq_tables.sql
-- Idempotent: existing categories and questions are not duplicated or overwritten.

START TRANSACTION;

INSERT INTO `faq_categories`
  (`name`, `slug`, `description`, `feature_flag_key`, `sort_order`, `is_active`, `created_at`)
VALUES
  ('Account e accesso', 'account-accesso', 'Accesso, password e gestione delle credenziali.', NULL, 10, 1, NOW()),
  ('Registrazione', 'registrazione', 'Creazione di un nuovo account ItalianCosplay.', 'enable_user_registration', 20, 1, NOW()),
  ('Eventi cosplay', 'eventi-cosplay', 'Ricerca, consultazione e segnalazione degli eventi.', 'enable_events', 30, 1, NOW()),
  ('Preferiti', 'preferiti', 'Contenuti salvati nella propria area personale.', 'enable_favorites', 40, 1, NOW()),
  ('Agenda personale', 'agenda-personale', 'Partecipazione e interesse per gli eventi.', 'enable_personal_agenda', 50, 1, NOW()),
  ('Portfolio cosplay', 'portfolio-cosplay', 'Gestione dei propri cosplay e associazione agli eventi.', 'enable_cosplay_portfolio', 60, 1, NOW()),
  ('Profilo pubblico', 'profilo-pubblico', 'Visibilità del profilo e informazioni mostrate alla community.', 'enable_public_profiles', 70, 1, NOW()),
  ('Notifiche', 'notifiche', 'Consultazione e gestione delle notifiche personali.', 'enable_notifications', 80, 1, NOW()),
  ('Guest', 'guest', 'Ricerca degli ospiti presenti agli eventi cosplay.', 'enable_guest_directory', 90, 1, NOW()),
  ('Blog', 'blog', 'Articoli, guide e approfondimenti editoriali.', 'enable_blog', 100, 1, NOW()),
  ('Advertising', 'advertising', 'Campagne, banner e statistiche pubblicitarie.', 'enable_advertising', 110, 1, NOW())
ON DUPLICATE KEY UPDATE `slug` = VALUES(`slug`);

INSERT INTO `faq_items`
  (`category_id`, `question`, `answer`, `feature_flag_key`, `sort_order`, `is_active`, `created_at`)
SELECT
  category.`id`, seed.`question`, seed.`answer`, NULL, seed.`sort_order`, 1, NOW()
FROM (
  SELECT 'account-accesso' AS category_slug, 10 AS sort_order,
    'Come accedo al mio account?' AS question,
    'Apri la pagina Accedi dal menu principale, inserisci l’indirizzo email e la password associati al tuo account e conferma. Dopo l’accesso verrai indirizzato alla tua dashboard personale.' AS answer
  UNION ALL SELECT 'account-accesso', 20,
    'Ho dimenticato la password: come posso recuperarla?',
    'Dalla pagina di accesso scegli il recupero password e inserisci l’indirizzo email del tuo account. Riceverai un collegamento personale per impostare una nuova password. Per sicurezza il collegamento è temporaneo e può essere utilizzato una sola volta.'
  UNION ALL SELECT 'account-accesso', 30,
    'Come posso cambiare la password mentre sono collegato?',
    'Apri la Dashboard e scegli Cambio Password. Inserisci i dati richiesti e salva. Usa una password nuova, lunga e non condivisa con altri servizi.'

  UNION ALL SELECT 'registrazione', 10,
    'Come creo un account su ItalianCosplay?',
    'Seleziona Registrati nel menu, compila i campi obbligatori e accetta le informative richieste. Dopo l’invio segui le indicazioni mostrate dal sito per completare la creazione dell’account.'
  UNION ALL SELECT 'registrazione', 20,
    'La registrazione a ItalianCosplay è gratuita?',
    'Sì, la creazione dell’account e l’uso delle funzionalità personali di base sono gratuiti. Eventuali servizi commerciali, come gli spazi pubblicitari, sono separati e mostrano chiaramente i relativi costi.'

  UNION ALL SELECT 'eventi-cosplay', 10,
    'Come trovo un evento cosplay?',
    'Apri Eventi Cosplay Italia dal menu principale. Puoi consultare il calendario e usare i filtri geografici per restringere i risultati per regione, provincia o comune. Aprendo una scheda trovi date, luogo e informazioni disponibili.'
  UNION ALL SELECT 'eventi-cosplay', 20,
    'Come segnalo un evento che non è presente?',
    'Apri Segnala il tuo evento Cosplay dal menu, compila il modulo con dati verificabili e invialo. La segnalazione viene controllata prima della pubblicazione, quindi non compare necessariamente subito nel calendario.'
  UNION ALL SELECT 'eventi-cosplay', 30,
    'Qual è la differenza tra evento master ed edizione?',
    'L’evento master rappresenta l’identità stabile di una manifestazione, mentre ogni edizione contiene le informazioni specifiche di un anno, come date e località. In questo modo puoi consultare insieme lo storico e le edizioni future dello stesso evento.'
  UNION ALL SELECT 'eventi-cosplay', 40,
    'Le informazioni sugli eventi sono ufficiali?',
    'ItalianCosplay raccoglie informazioni da organizzatori, segnalazioni e fonti pubbliche. Prima di partire controlla sempre eventuali variazioni di programma, orari e biglietti anche sui canali ufficiali dell’organizzatore collegati alla scheda.'

  UNION ALL SELECT 'preferiti', 10,
    'Come salvo un contenuto nei preferiti?',
    'Quando il pulsante dei preferiti è disponibile, apri il contenuto e seleziona Salva tra i preferiti. Devi aver effettuato l’accesso. Il contenuto sarà aggiunto alla sezione Preferiti della tua dashboard.'
  UNION ALL SELECT 'preferiti', 20,
    'Dove trovo gli elementi che ho salvato?',
    'Accedi alla Dashboard e apri Preferiti. Qui puoi ritrovare i contenuti salvati, come eventi, guest, articoli o località supportate dalla funzione.'
  UNION ALL SELECT 'preferiti', 30,
    'Come rimuovo un elemento dai preferiti?',
    'Seleziona nuovamente il pulsante del preferito dalla scheda del contenuto oppure usa l’azione disponibile nella sezione Preferiti della dashboard. La rimozione riguarda soltanto il tuo account.'

  UNION ALL SELECT 'agenda-personale', 10,
    'Come aggiungo un evento alla mia agenda?',
    'Apri la scheda dell’evento ed entra nella sezione Agenda personale. Dopo aver effettuato l’accesso puoi scegliere Mi interessa, Ci vado oppure Forse vado.'
  UNION ALL SELECT 'agenda-personale', 20,
    'Dove vedo gli eventi della mia agenda?',
    'Apri la Dashboard e seleziona I miei eventi. Troverai gli eventi associati al tuo account con lo stato di partecipazione scelto.'
  UNION ALL SELECT 'agenda-personale', 30,
    'Posso cambiare o rimuovere lo stato di partecipazione?',
    'Sì. Torna nella scheda dell’evento e scegli un altro stato oppure usa l’azione di rimozione disponibile. L’agenda è personale e non modifica le informazioni pubbliche dell’evento.'

  UNION ALL SELECT 'portfolio-cosplay', 10,
    'Come aggiungo un cosplay al mio portfolio?',
    'Accedi alla Dashboard e apri Portfolio cosplay. Usa il modulo di inserimento per scegliere il personaggio o indicare un nome personalizzato, aggiungere le informazioni richieste e salvare.'
  UNION ALL SELECT 'portfolio-cosplay', 20,
    'Posso scegliere quali cosplay mostrare pubblicamente?',
    'Sì. Nella gestione del portfolio puoi modificare la visibilità dei singoli cosplay. Gli elementi non pubblici restano disponibili nella tua area personale ma non vengono mostrati agli altri utenti.'
  UNION ALL SELECT 'portfolio-cosplay', 30,
    'Come indico quale cosplay porterò a un evento?',
    'Apri la scheda dell’evento e usa la sezione Porterò questo cosplay. Seleziona uno o più elementi già presenti nel tuo portfolio e indica se li porterai oppure se sei ancora indeciso.'

  UNION ALL SELECT 'profilo-pubblico', 10,
    'Come modifico le informazioni del mio profilo?',
    'Accedi alla Dashboard e apri Profilo. Puoi aggiornare le informazioni disponibili, come bio, social e località, quindi salvare le modifiche.'
  UNION ALL SELECT 'profilo-pubblico', 20,
    'Come cambio avatar e immagine di copertina?',
    'Nella Dashboard usa le sezioni Cambio avatar e Cambio Cover. Carica un’immagine adatta e completa le eventuali regolazioni richieste prima di salvare.'
  UNION ALL SELECT 'profilo-pubblico', 30,
    'Come controllo ciò che gli altri utenti possono vedere?',
    'Apri Impostazioni nella Dashboard. Da questa sezione puoi gestire le preferenze di visibilità previste per il profilo. Controlla sempre l’anteprima pubblica dopo una modifica importante.'

  UNION ALL SELECT 'notifiche', 10,
    'Dove trovo le mie notifiche?',
    'Dopo aver effettuato l’accesso, apri Notifiche dalla Dashboard oppure usa l’icona della campanella quando disponibile. Il contatore indica le notifiche non ancora lette.'
  UNION ALL SELECT 'notifiche', 20,
    'Come segno le notifiche come lette?',
    'Apri la sezione Notifiche e usa l’azione disponibile sulla singola notifica. Puoi anche segnare come lette tutte le notifiche in una volta quando l’opzione è presente.'
  UNION ALL SELECT 'notifiche', 30,
    'Posso eliminare una notifica?',
    'Sì. Dalla sezione Notifiche usa l’azione Elimina sulla comunicazione che non vuoi più conservare. L’eliminazione non annulla l’eventuale operazione a cui la notifica si riferisce.'

  UNION ALL SELECT 'guest', 10,
    'Come trovo un guest?',
    'Apri la directory Guest e consulta le persone presenti. Selezionando un nome puoi vedere la scheda dedicata e le informazioni pubblicate.'
  UNION ALL SELECT 'guest', 20,
    'Dove vedo gli eventi collegati a un guest?',
    'Apri la scheda del guest. Quando sono disponibili collegamenti verificati, la pagina mostra gli eventi ai quali il guest è associato.'

  UNION ALL SELECT 'blog', 10,
    'Che cosa trovo nel blog di ItalianCosplay?',
    'Il blog raccoglie guide, approfondimenti e contenuti editoriali dedicati al cosplay e agli eventi italiani. Puoi aprire un articolo dalla pagina Blog oppure esplorare una categoria tematica.'
  UNION ALL SELECT 'blog', 20,
    'Posso salvare un articolo per leggerlo più tardi?',
    'Quando la funzione Preferiti è attiva e il relativo pulsante è presente nell’articolo, puoi salvarlo dopo aver effettuato l’accesso e ritrovarlo nella tua Dashboard.'

  UNION ALL SELECT 'advertising', 10,
    'Come creo una campagna pubblicitaria?',
    'Accedi alla Dashboard, apri Advertising e scegli la creazione di una nuova campagna. Configura lo spazio, il periodo e i dati richiesti, quindi controlla il riepilogo prima di procedere.'
  UNION ALL SELECT 'advertising', 20,
    'Come carico o modifico un banner?',
    'Dalla sezione Advertising della Dashboard apri Banner. Puoi creare un nuovo banner oppure modificare quelli esistenti, rispettando formato e dimensioni indicati nel modulo.'
  UNION ALL SELECT 'advertising', 30,
    'Dove controllo i risultati di una campagna?',
    'Apri Advertising e consulta le statistiche generali oppure la pagina statistiche della singola campagna. I dati disponibili possono includere impression, clic e indicatori utili a valutare l’andamento.'
) AS seed
INNER JOIN `faq_categories` AS category
  ON category.`slug` = seed.`category_slug`
  AND category.`deleted_at` IS NULL
LEFT JOIN `faq_items` AS existing
  ON existing.`category_id` = category.`id`
  AND existing.`question` = seed.`question`
  AND existing.`deleted_at` IS NULL
WHERE existing.`id` IS NULL;

COMMIT;
