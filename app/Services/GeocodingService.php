<?php
namespace App\Services;
class GeocodingService
{
	public function geocode(string $address): ?array
	{
		if (empty($address)) {
			return null;
		}
		$address = $this->cleanAddress($address);
		$url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
				'q' => $address,
				'format' => 'json',
				'limit' => 1
			]);

		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_USERAGENT => 'ItalianCosplayGeocoder/1.0 (contact: admin@yourdomain.it)'
		]);

		$response = curl_exec($ch);
		curl_close($ch);

		if (!$response) {
			return null;
		}

		$data = json_decode($response, true);

		if (empty($data[0])) {
			return null;
		}

		return [
			'lat' => $data[0]['lat'],
			'lng' => $data[0]['lon'],
			'display_name' => $data[0]['display_name'] ?? null
		];
	}
	public function reverseGeocode(float $lat, float $lng): ?array
	{
		$url = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query([
				'format' => 'json',
				'lat' => $lat,
				'lon' => $lng,
				'zoom' => 18,
				'addressdetails' => 1
			]);

		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT => 10,
			CURLOPT_USERAGENT => 'ItalianCosplayGeocoder/1.0'
		]);

		$response = curl_exec($ch);
		curl_close($ch);

		if (!$response) {
			return null;
		}

		$data = json_decode($response, true);

		if (empty($data['address'])) {
			return null;
		}

		$addr = $data['address'];

		return [
			'comune' => $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? null,
			'provincia' => $addr['county'] ?? null,
			'regione' => $addr['state'] ?? null,
			'cap' => $addr['postcode'] ?? null,
			'raw' => $addr
		];
	}


	private function cleanAddress(string $address): string
	{
		// rimuove CAP
		$address = preg_replace('/\b\d{5}\b/', '', $address);

		// rimuove doppie virgole/spazi
		$address = preg_replace('/\s+/', ' ', $address);
		$address = preg_replace('/,+/', ',', $address);

		return trim($address, " ,");
	}
}
