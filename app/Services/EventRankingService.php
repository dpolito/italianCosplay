<?php
namespace App\Services;

use App\Core\Database;
use PDO;

class EventRankingService
{
	protected $db;
	public function __construct()
	{
		$this->db = Database::getInstance()->getConnection();
	}

	/**
	 * Calcola il punteggio finale di un evento
	 */
	public function calculateFinalScore(array $event, int $views7d): int
	{
		$score = 0;
		$now = time();

		// 🔥 TREND (logaritmico leggero invece che lineare)
		$score += (int) (sqrt($views7d) * 10);

		// 🏆 IMPORTANZA EVENTO (peso forte ma stabile)
		$score += pow(($event['event_size'] ?? 0), 1.4) * 10;

		// 🆕 NUOVO EVENTO (boost ma non dominante)
		if (!empty($event['created_at']) &&
			strtotime($event['created_at']) > strtotime('-30 days')) {
			$score += 15;
		}

		// 🔥 EVENTO IN CORSO (molto importante)
		if (
			!empty($event['data_inizio']) &&
			!empty($event['data_fine']) &&
			$now >= strtotime($event['data_inizio']) &&
			$now <= strtotime($event['data_fine'])
		) {
			$score += 35;
		}

		// 📅 EVENTO IMMINENTE (ridotto e più realistico)
		if (!empty($event['data_inizio'])) {
			$daysToStart = (strtotime($event['data_inizio']) - $now) / 86400;

			if ($daysToStart >= 0 && $daysToStart <= 7) {
				$score += (int)(15 - ($daysToStart * 2));
			}
		}

		// ⛔ EVENTO FINITO (penalità)
		if (!empty($event['data_fine']) && $now > strtotime($event['data_fine'])) {
			$score -= 20;
		}

		return max(0, $score);
	}

	/**
	 * Recupera le views degli ultimi 7 giorni per tutti gli eventi
	 */
	public function getViewsLast7Days(): array
	{
		$sql = "
            SELECT event_id, SUM(views) as views_7d
            FROM event_views
            WHERE view_date >= CURDATE() - INTERVAL 7 DAY
            GROUP BY event_id
        ";

		$stmt = $this->db->query($sql);
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$map = [];

		foreach ($rows as $row) {
			$map[$row['event_id']] = (int)$row['views_7d'];
		}

		return $map;
	}

	/**
	 * Recupera tutti gli eventi attivi (non scaduti)
	 */
	public function getActiveEvents(): array
	{
		$sql = "
            SELECT *
            FROM events
            WHERE approvato = 1
            AND data_fine >= CURDATE()
        ";

		$stmt = $this->db->query($sql);
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * Aggiorna il final_score per tutti gli eventi
	 */
	public function updateAllScores(): void
	{
		$events = $this->getActiveEvents();
		$viewsMap = $this->getViewsLast7Days();

		$updateStmt = $this->db->prepare("
            UPDATE events 
            SET final_score = :score 
            WHERE id = :id
        ");

		foreach ($events as $event) {

			$views7d = $viewsMap[$event['id']] ?? 0;

			$score = $this->calculateFinalScore($event, $views7d);

			$updateStmt->execute([
				':score' => $score,
				':id' => $event['id']
			]);
		}
	}

	/**
	 * Aggiorna score di un singolo evento (utile per debug/test)
	 */
	public function updateSingleEvent(int $eventId): void
	{
		$stmt = $this->db->prepare("SELECT * FROM events WHERE id = :id");
		$stmt->execute([':id' => $eventId]);

		$event = $stmt->fetch(PDO::FETCH_ASSOC);

		if (!$event) {
			return;
		}

		$viewsMap = $this->getViewsLast7Days();
		$views7d = $viewsMap[$eventId] ?? 0;

		$score = $this->calculateFinalScore($event, $views7d);

		$updateStmt = $this->db->prepare("
            UPDATE events 
            SET final_score = :score 
            WHERE id = :id
        ");

		$updateStmt->execute([
			':score' => $score,
			':id' => $eventId
		]);
	}
}
