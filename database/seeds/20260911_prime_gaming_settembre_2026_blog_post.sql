-- ItalianCosplay.it blog seed: Prime Gaming settembre 2026
-- Idempotent: the post is inserted only if the slug does not already exist.

START TRANSACTION;

INSERT INTO `blog_categories`
  (`name`, `slug`, `description`, `seo_title`, `seo_description`)
VALUES
  (
    'Anime Manga Videogiochi',
    'anime-manga-videogiochi',
    'News, guide e approfondimenti su anime, manga, videogiochi e cultura pop collegata al cosplay.',
    'Anime, manga e videogiochi: news e guide cosplay',
    'Approfondimenti su anime, manga, videogiochi e cultura pop per cosplayer, appassionati e community nerd.'
  )
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`);

SET @category_id := (
  SELECT `id`
  FROM `blog_categories`
  WHERE `slug` = 'anime-manga-videogiochi'
  LIMIT 1
);

SET @author_id := COALESCE(
  (SELECT `id` FROM `users` ORDER BY `id` ASC LIMIT 1),
  1
);

INSERT INTO `blog_posts`
  (
    `titolo`,
    `slug`,
    `excerpt`,
    `contenuto`,
    `meta_title`,
    `meta_description`,
    `categoria_id`,
    `related_event_id`,
    `status`,
    `user_id`,
    `published_at`
  )
SELECT
  'Prime Gaming settembre 2026: tutti i giochi gratis del mese',
  'prime-gaming-settembre-2026-giochi-gratis',
  'La lista aggiornata dei giochi gratis Prime Gaming di settembre 2026, con date di disponibilità, piattaforme di riscatto e titoli Luna inclusi con Prime.',
  '<p>Amazon ha confermato i giochi inclusi con Prime Gaming per settembre 2026. Anche questo mese gli abbonati Prime possono riscattare una selezione di giochi PC da tenere nella propria libreria, con codici e attivazioni su Epic Games Store, GOG, Microsoft, Legacy Games e Amazon Games App.</p>

<p>La line-up di settembre punta su nomi molto riconoscibili, soprattutto per chi ama FPS e avventure narrative: tra i titoli principali ci sono <strong>DOOM Eternal</strong>, <strong>High on Life</strong>, <strong>DOOM + DOOM II</strong> e <strong>Botany Manor</strong>. Di seguito trovi la lista completa con le date di disponibilità.</p>

<h2>Prime Gaming settembre 2026: giochi gratis da riscattare</h2>

<p>Questi sono i giochi PC riscattabili nel corso del mese dagli abbonati Amazon Prime:</p>

<ul>
  <li><strong>War Hospital</strong> - disponibile dal 3 settembre 2026 su Epic Games Store</li>
  <li><strong>Just Die Already</strong> - disponibile dal 3 settembre 2026 su Epic Games Store</li>
  <li><strong>Drop Duchy</strong> - disponibile dal 10 settembre 2026 su Epic Games Store</li>
  <li><strong>DOOM + DOOM II</strong> - disponibile dal 10 settembre 2026 con codice GOG</li>
  <li><strong>Botany Manor</strong> - disponibile dal 10 settembre 2026 su Epic Games Store</li>
  <li><strong>DOOM Eternal</strong> - disponibile dal 17 settembre 2026 con codice Microsoft</li>
  <li><strong>Rims Racing</strong> - disponibile dal 17 settembre 2026 su Epic Games Store</li>
  <li><strong>Wall World 2</strong> - disponibile dal 17 settembre 2026 su Amazon Games App</li>
  <li><strong>Hue</strong> - disponibile dal 24 settembre 2026 su Epic Games Store</li>
  <li><strong>The Da Vinci Cryptex</strong> - disponibile dal 24 settembre 2026 con codice Legacy Games</li>
  <li><strong>High on Life</strong> - disponibile dal 24 settembre 2026 su Epic Games Store</li>
</ul>

<h2>I giochi più interessanti di settembre</h2>

<h3>DOOM Eternal</h3>
<p>Il nome più forte della selezione è senza dubbio <strong>DOOM Eternal</strong>, in arrivo dal 17 settembre con codice Microsoft. È uno dei titoli più adatti a chi cerca un FPS veloce, aggressivo e molto spettacolare.</p>

<h3>High on Life</h3>
<p><strong>High on Life</strong> arriva il 24 settembre su Epic Games Store. È una scelta più particolare: uno sparatutto narrativo con tono comico, armi parlanti e un gusto molto riconoscibile. Non è il classico riempitivo da catalogo, quindi vale la pena riscattarlo se ti interessano giochi fuori dagli schemi.</p>

<h3>DOOM + DOOM II</h3>
<p>Dal 10 settembre è disponibile anche <strong>DOOM + DOOM II</strong> tramite codice GOG. È un recupero perfetto per chi vuole tenere in libreria due classici fondamentali della storia degli FPS, utili anche per riscoprire l''immaginario da cui nasce buona parte dell''estetica action moderna.</p>

<h3>Botany Manor</h3>
<p><strong>Botany Manor</strong>, riscattabile dal 10 settembre su Epic Games Store, cambia completamente ritmo: è un puzzle game più rilassato e atmosferico, indicato se cerchi qualcosa di breve, curato e meno frenetico rispetto ai titoli d''azione del mese.</p>

<h2>Giochi Luna inclusi con Prime a settembre 2026</h2>

<p>Oltre ai giochi PC da riscattare, Prime include anche una selezione di titoli giocabili tramite Amazon Luna Standard. A settembre 2026 entrano o vengono evidenziati:</p>

<ul>
  <li><strong>Star Wars: Outlaws</strong></li>
  <li><strong>Avatar: Frontiers of Pandora</strong></li>
  <li><strong>King of Tokyo</strong></li>
  <li><strong>It''s Quiz Time: Guinness World Records</strong></li>
  <li><strong>Bowling Mayhem</strong> - in arrivo prossimamente</li>
  <li><strong>Cranium Planet</strong> - in arrivo prossimamente</li>
</ul>

<p>La differenza è importante: i giochi Prime Gaming riscattati tramite store esterni restano collegati alla libreria dell''account usato per il riscatto, mentre i titoli Luna sono giocabili in streaming finché restano disponibili nel catalogo e finché il servizio è incluso nel proprio abbonamento.</p>

<h2>Conviene riscattare i giochi Prime Gaming di settembre 2026?</h2>

<p>Sì, soprattutto per <strong>DOOM Eternal</strong>, <strong>High on Life</strong>, <strong>Botany Manor</strong> e <strong>DOOM + DOOM II</strong>. La selezione non è enorme, ma ha un buon equilibrio tra sparatutto, puzzle, gestionale/strategia e giochi più leggeri.</p>

<p>Il consiglio pratico è riscattare subito i titoli già disponibili e segnarsi le date del 17 e del 24 settembre, perché sono quelle con i giochi più forti della seconda metà del mese.</p>

<h2>Calendario rapido dei riscatti</h2>

<ul>
  <li><strong>3 settembre:</strong> War Hospital, Just Die Already</li>
  <li><strong>10 settembre:</strong> Drop Duchy, DOOM + DOOM II, Botany Manor</li>
  <li><strong>17 settembre:</strong> DOOM Eternal, Rims Racing, Wall World 2</li>
  <li><strong>24 settembre:</strong> Hue, The Da Vinci Cryptex, High on Life</li>
</ul>

<h2>FAQ sui giochi Prime Gaming di settembre 2026</h2>

<h3>Quali sono i giochi Prime Gaming gratis di settembre 2026?</h3>
<p>I giochi sono War Hospital, Just Die Already, Drop Duchy, DOOM + DOOM II, Botany Manor, DOOM Eternal, Rims Racing, Wall World 2, Hue, The Da Vinci Cryptex e High on Life.</p>

<h3>DOOM Eternal è incluso con Prime Gaming a settembre 2026?</h3>
<p>Sì. DOOM Eternal è disponibile dal 17 settembre 2026 tramite codice Microsoft per gli abbonati Amazon Prime.</p>

<h3>I giochi riscattati con Prime Gaming restano per sempre?</h3>
<p>In genere sì: i giochi riscattati tramite codice o store esterni restano associati all''account usato per il riscatto. I titoli giocati via Luna, invece, seguono la disponibilità del catalogo streaming.</p>

<h3>Dove si riscattano i giochi Prime Gaming?</h3>
<p>I giochi si riscattano dalla pagina Prime Gaming o dall''area Free Games with Prime di Amazon Luna. Alcuni titoli richiedono account esterni come Epic Games Store, GOG, Microsoft, Legacy Games o Amazon Games App.</p>

<p>Se segui anche eventi, fiere e cultura pop in Italia, puoi continuare a esplorare il <a href="/blog">blog di ItalianCosplay</a> oppure consultare il calendario degli <a href="/eventi-cosplay">eventi cosplay in Italia</a>.</p>',
  'Prime Gaming settembre 2026: giochi gratis',
  'Scopri tutti i giochi gratis Prime Gaming di settembre 2026: date, piattaforme di riscatto, DOOM Eternal, High on Life e titoli Luna.',
  @category_id,
  NULL,
  'published',
  @author_id,
  '2026-09-11'
WHERE NOT EXISTS (
  SELECT 1
  FROM `blog_posts`
  WHERE `slug` = 'prime-gaming-settembre-2026-giochi-gratis'
  LIMIT 1
);

COMMIT;
