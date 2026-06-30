<?php
namespace App\Helpers;
use DateTime;

class DateHelper
{
	public static function meseInItaliano(int $mese): string
	{
		$mesi = [
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
			12 => 'dicembre'
		];

		return $mesi[$mese] ?? '';
	}

	public static function formatEventoPeriodo(string $dataInizio, ?string $dataFine = null): string
	{
		$inizio = new DateTime($dataInizio);
		$fine   = !empty($dataFine) ? new DateTime($dataFine) : null;

		if ($fine && $inizio->format('Y-m-d') !== $fine->format('Y-m-d')) {

			if (
				$inizio->format('m') === $fine->format('m') &&
				$inizio->format('Y') === $fine->format('Y')
			) {

				return $inizio->format('j') . '-' .
					$fine->format('j') . ' ' .
					self::meseInItaliano((int)$inizio->format('n'));
			}

			return $inizio->format('j') . ' ' .
				self::meseInItaliano((int)$inizio->format('n')) .
				' - ' .
				$fine->format('j') . ' ' .
				self::meseInItaliano((int)$fine->format('n'));
		}

		return $inizio->format('j') . ' ' .
			self::meseInItaliano((int)$inizio->format('n'));
	}
}
