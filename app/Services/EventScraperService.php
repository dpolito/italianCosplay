<?php
namespace App\Services;

use DOMDocument;
use DOMXPath;

class EventScraperService
{
	private GeocodingService $geocodingService;

	public function __construct(){
		$this->geocodingService = new GeocodingService();
	}

	public function scrapeCosplayersItalia(string $url): array
	{
		// ======================
		// 1. CURL FETCH HTML
		// ======================
		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT => 15,
			CURLOPT_USERAGENT => 'Mozilla/5.0 (EventScraperBot)'
		]);

		$html = curl_exec($ch);
		curl_close($ch);

		if (!$html) {
			return ['error' => 'Impossibile recuperare la pagina'];
		}

		// ======================
		// 2. DOM PARSING
		// ======================
		libxml_use_internal_errors(true);

		$dom = new DOMDocument();
		$dom->loadHTML($html);

		$xpath = new DOMXPath($dom);

		// ======================
		// 3. TITLE
		// ======================
		$titolo = self::getText($xpath, "//h1");

		// ======================
		// DATE (DAL / AL) ELEMENTOR SAFE PARSING
		// ======================

		$dataDal = null;
		$dataAl = null;

		// prendo tutti i blocchi icon-box
		$boxes = $xpath->query("//div[contains(@class,'elementor-icon-box-content')]");

		foreach ($boxes as $box) {

			$labelNodes = (new DOMXPath($dom))->query(".//p[contains(@class,'elementor-icon-box-title')]//span", $box);
			$valueNodes = (new DOMXPath($dom))->query(".//p[contains(@class,'elementor-icon-box-description')]", $box);

			if ($labelNodes->length && $valueNodes->length) {

				$label = trim($labelNodes->item(0)->textContent);
				$value = trim($valueNodes->item(0)->textContent);

				// DAL
				if (stripos($label, 'Dal') !== false) {
					$dataDal = self::convertItalianDate($value);
				}

				// AL
				if (stripos($label, 'Al') !== false) {
					$dataAl = self::convertItalianDate($value);
				}
			}
		}

		// ======================
		// 5. ADDRESS (label "Indirizzo Fiera Comics")
		// ======================
		$localita = null;

		$nodes = $xpath->query("//*[contains(text(),'Indirizzo Fiera Comics')]/following::*[1]");
		if ($nodes->length > 0) {
			$localita = trim($nodes->item(0)->textContent);
		}

		$geo = $this->geocodingService->geocode($localita);

		$lat = $geo['lat'] ?? null;
		$lng = $geo['lng'] ?? null;



		// ======================
		// 6. RETURN STRUCTURED DATA
		// ======================
		return [
			'titolo' => trim($titolo),
			'data_dal' => $dataDal,
			'data_al' => $dataAl,
			'localita' => $localita,
			'latitudine' => $lat,
			'longitudine' => $lng
		];
	}
	private static function convertItalianDate($date)
	{
		$months = [
			'Gennaio' => 'January',
			'Febbraio' => 'February',
			'Marzo' => 'March',
			'Aprile' => 'April',
			'Maggio' => 'May',
			'Giugno' => 'June',
			'Luglio' => 'July',
			'Agosto' => 'August',
			'Settembre' => 'September',
			'Ottobre' => 'October',
			'Novembre' => 'November',
			'Dicembre' => 'December',
		];

		$date = str_replace(array_keys($months), array_values($months), $date);

		return date('Y-m-d', strtotime($date));
	}

	private static function getText(DOMXPath $xpath, string $query): ?string
	{
		$nodes = $xpath->query($query);
		return $nodes->length ? trim($nodes->item(0)->textContent) : null;
	}
}
