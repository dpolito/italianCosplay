<?php

use App\Repositories\EventRepository;
use App\Services\EventScoringEngine;
use App\Services\EventSignalService;

$signal = new EventSignalService();
$scoring = new EventScoringEngine();
$repo = new EventRepository();

$views7d = $signal->getViews7d();
$events = $repo->getAll();

foreach ($events as $event) {

	$views = $views7d[$event['id']] ?? 0;

	$score = $scoring->calculate($event, $views);

	$repo->updateScore($event['id'], $score, $views);
}

echo "OK - scores aggiornati\n";
