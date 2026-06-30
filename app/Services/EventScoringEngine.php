<?php
namespace App\Services;

class EventScoringEngine
{
	public function calculate(array $event, int $views7d): int
	{
		$score = 0;
		$now = time();

		// 📈 TREND (stabile)
		$score += sqrt($views7d) * 10;

		// 🏆 IMPORTANZA EVENTO
		$score += ($event['event_size'] ?? 0) * 20;

		// 🆕 EVENTO RECENTE
		if (!empty($event['created_at']) &&
			strtotime($event['created_at']) > strtotime('-30 days')) {
			$score += 15;
		}

		// 🔥 EVENTO IN CORSO
		if ($this->isRunning($event)) {
			$score += 35;
		}

		// 📅 EVENTO IMMINENTE
		$score += $this->imminentBoost($event, $now);

		// ⛔ EVENTO FINITO
		if ($this->isFinished($event)) {
			$score -= 20;
		}

		return max(0, (int)$score);
	}

	private function isRunning(array $event): bool
	{
		if (empty($event['data_inizio']) || empty($event['data_fine'])) {
			return false;
		}

		$now = time();

		return strtotime($event['data_inizio']) <= $now
			&& strtotime($event['data_fine']) >= $now;
	}

	private function isFinished(array $event): bool
	{
		return !empty($event['data_fine']) &&
			strtotime($event['data_fine']) < time();
	}

	private function imminentBoost(array $event, int $now): int
	{
		if (empty($event['data_inizio'])) return 0;

		$days = (strtotime($event['data_inizio']) - $now) / 86400;

		if ($days < 0 || $days > 7) return 0;

		return (int)(10 - $days);
	}
}
