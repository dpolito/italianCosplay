<?php

namespace App\Helpers;

use DateTimeImmutable;
use DateTimeInterface;

class MonthHelper
{
	private const MONTHS = [
		1  => 'gennaio',
		2  => 'febbraio',
		3  => 'marzo',
		4  => 'aprile',
		5  => 'maggio',
		6  => 'giugno',
		7  => 'luglio',
		8  => 'agosto',
		9  => 'settembre',
		10 => 'ottobre',
		11 => 'novembre',
		12 => 'dicembre',
	];


	/**
	 * Converte uno slug SEO tipo:
	 * luglio-2026
	 *
	 * in:
	 * [
	 *   month => 7,
	 *   year => 2026,
	 *   label => Luglio 2026
	 * ]
	 */
	public static function parseSlug(string $slug): ?array
	{
		if (!preg_match('/^([a-zà-ù]+)-(\d{4})$/i', $slug, $matches)) {
			return null;
		}

		$monthName = strtolower($matches[1]);
		$year = (int) $matches[2];

		$month = array_search($monthName, self::MONTHS, true);

		if ($month === false) {
			return null;
		}

		return [
			'month' => (int) $month,
			'year' => $year,
			'label' => self::getLabel((int)$month, $year),
		];
	}


	/**
	 * Genera slug SEO:
	 *
	 * 7, 2026
	 *
	 * diventa:
	 *
	 * luglio-2026
	 */
	public static function generateSlug(int $month, int $year): string
	{
		if (!isset(self::MONTHS[$month])) {
			throw new \InvalidArgumentException('Mese non valido');
		}

		return self::MONTHS[$month] . '-' . $year;
	}


	/**
	 * Restituisce:
	 *
	 * Luglio 2026
	 */
	public static function getLabel(int $month, int $year): string
	{
		if (!isset(self::MONTHS[$month])) {
			throw new \InvalidArgumentException('Mese non valido');
		}

		return ucfirst(self::MONTHS[$month]) . ' ' . $year;
	}


	/**
	 * Primo giorno del mese
	 */
	public static function getStartDate(int $month, int $year): DateTimeImmutable
	{
		return new DateTimeImmutable(
			sprintf('%04d-%02d-01', $year, $month)
		);
	}


	/**
	 * Ultimo giorno del mese
	 */
	public static function getEndDate(int $month, int $year): DateTimeImmutable
	{
		return self::getStartDate($month, $year)
			->modify('last day of this month');
	}


	/**
	 * Restituisce il mese corrente
	 */
	public static function current(): array
	{
		$now = new DateTimeImmutable();

		return [
			'month' => (int)$now->format('n'),
			'year' => (int)$now->format('Y'),
			'label' => self::getLabel(
				(int)$now->format('n'),
				(int)$now->format('Y')
			),
		];
	}


	/**
	 * Genera lista mesi futuri
	 *
	 * Esempio:
	 * luglio 2026
	 * agosto 2026
	 * ...
	 */
	public static function getFutureMonths(int $limit = 12): array
	{
		$months = [];

		$date = new DateTimeImmutable('first day of this month');

		for ($i = 0; $i < $limit; $i++) {

			$month = (int)$date->format('n');
			$year = (int)$date->format('Y');

			$months[] = [
				'month' => $month,
				'year' => $year,
				'label' => self::getLabel($month, $year),
				'slug' => self::generateSlug($month, $year),
			];

			$date = $date->modify('+1 month');
		}

		return $months;
	}


	/**
	 * Verifica se un mese è passato
	 */
	public static function isPast(int $month, int $year): bool
	{
		$end = self::getEndDate($month, $year);
		$today = new DateTimeImmutable('today');

		return $end < $today;
	}
}
