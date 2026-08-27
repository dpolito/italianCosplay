<?php

declare(strict_types=1);

namespace App\Helpers;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class WeekendHelper
{
	private const MONTHS = [
		1 => 'gennaio',
		2 => 'febbraio',
		3 => 'marzo',
		4 => 'aprile',
		5 => 'maggio',
		6 => 'giugno',
		7 => 'luglio',
		8 => 'agosto',
		9 => 'settembre',
		10 => 'ottobre',
		11 => 'novembre',
		12 => 'dicembre',
	];

	/**
	 * Genera lo slug SEO del weekend
	 *
	 * Esempio:
	 * 11-12-luglio-2026
	 */
	public static function generateSlug(
		DateTimeInterface $start,
		DateTimeInterface $end
	): string {
		return sprintf(
			'%d-%d-%s-%d',
			(int) $start->format('j'),
			(int) $end->format('j'),
			self::MONTHS[(int) $start->format('n')],
			(int) $start->format('Y')
		);
	}


	/**
	 * Converte uno slug weekend in date
	 *
	 * Esempio:
	 * 11-12-luglio-2026
	 *
	 * ritorna:
	 * [
	 *   'start' => DateTimeImmutable,
	 *   'end'   => DateTimeImmutable
	 * ]
	 */
	public static function parseSlug(string $slug): ?array
	{
		$pattern = '/^(\d{1,2})-(\d{1,2})-([a-z]+)-(\d{4})$/i';

		if (!preg_match($pattern, $slug, $matches)) {
			return null;
		}

		$dayStart = (int) $matches[1];
		$dayEnd = (int) $matches[2];
		$monthName = strtolower($matches[3]);
		$year = (int) $matches[4];


		$month = array_search(
			$monthName,
			self::MONTHS,
			true
		);

		if (!$month) {
			return null;
		}


		try {

			$start = new DateTimeImmutable(
				sprintf(
					'%04d-%02d-%02d',
					$year,
					$month,
					$dayStart
				)
			);

			$end = new DateTimeImmutable(
				sprintf(
					'%04d-%02d-%02d',
					$year,
					$month,
					$dayEnd
				)
			);

		} catch (Exception) {
			return null;
		}


		if (!self::isValidWeekend($start, $end)) {
			return null;
		}


		return [
			'start' => $start,
			'end' => $end
		];
	}


	/**
	 * Verifica che il range sia sabato/domenica
	 */
	public static function isValidWeekend(
		DateTimeInterface $start,
		DateTimeInterface $end
	): bool {

		return
			$start->format('N') === '6'
			&&
			$end->format('N') === '7'
			&&
			$start->diff($end)->days === 1;
	}


	/**
	 * Restituisce il weekend corrente o il prossimo disponibile
	 */
	public static function getCurrentWeekend(
		?DateTimeImmutable $date = null
	): array {

		$date ??= new DateTimeImmutable();

		$day = (int) $date->format('N');


		// sabato
		if ($day === 6) {

			return [
				'start' => $date->setTime(0, 0),
				'end' => $date->modify('+1 day')->setTime(0, 0)
			];
		}


		// domenica
		if ($day === 7) {

			return [
				'start' => $date->modify('-1 day')->setTime(0, 0),
				'end' => $date->setTime(0, 0)
			];
		}


		// lun-ven -> prossimo sabato
		$days = 6 - $day;

		return [
			'start' => $date->modify("+{$days} days")->setTime(0, 0),
			'end' => $date->modify("+" . ($days + 1) . " days")->setTime(0, 0)
		];
	}


	/**
	 * Weekend precedente
	 */
	public static function getPreviousWeekend(
		DateTimeImmutable $start
	): array {

		$previousStart = $start->modify('-7 days');

		return [
			'start' => $previousStart,
			'end' => $previousStart->modify('+1 day')
		];
	}


	/**
	 * Weekend successivo
	 */
	public static function getNextWeekend(
		DateTimeImmutable $start
	): array {

		$nextStart = $start->modify('+7 days');

		return [
			'start' => $nextStart,
			'end' => $nextStart->modify('+1 day')
		];
	}


	/**
	 * Restituisce lo slug del weekend precedente
	 */
	public static function getPreviousSlug(
		DateTimeImmutable $start
	): string {

		$weekend = self::getPreviousWeekend($start);

		return self::generateSlug(
			$weekend['start'],
			$weekend['end']
		);
	}


	/**
	 * Restituisce lo slug del weekend successivo
	 */
	public static function getNextSlug(
		DateTimeImmutable $start
	): string {

		$weekend = self::getNextWeekend($start);

		return self::generateSlug(
			$weekend['start'],
			$weekend['end']
		);
	}

	/**
	 * Restituisce tutti i weekend presenti in un mese
	 *
	 * @return array<int, array{
	 *     start: DateTimeImmutable,
	 *     end: DateTimeImmutable,
	 *     label: string,
	 *     slug: string
	 * }>
	 */
	public static function getWeekendsOfMonth(int $year, int $month): array
	{
		$weekends = [];

		$startOfMonth = new DateTimeImmutable(
			sprintf('%04d-%02d-01', $year, $month)
		);

		$endOfMonth = $startOfMonth->modify('last day of this month');

		// Primo sabato del mese
		$current = $startOfMonth;

		if ((int)$current->format('N') !== 6) {
			$current = $current->modify('next saturday');
		}

		while ($current <= $endOfMonth) {

			$endWeekend = $current->modify('+1 day');

			$weekends[] = [
				'start' => $current,
				'end' => $endWeekend,
				'label' => sprintf(
					'%s-%s %s %s',
					$current->format('d'),
					$endWeekend->format('d'),
					self::MONTHS[(int) $endWeekend->format('n')],
					$year
				),
				'slug' => self::generateSlug(
					$current,
					$endWeekend
				),
			];

			$current = $current->modify('+7 days');
		}

		return $weekends;
	}
}
