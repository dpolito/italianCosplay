<?php

declare(strict_types=1);

namespace App\Services;

class TelegramNotificationService
{
	private string $botToken;
	private string $chatId;

	public function __construct(string $botToken, string $chatId)
	{
		$this->botToken = trim($botToken);
		$this->chatId = trim($chatId);
	}

	public function sendMessage(string $message, ?array $options = null): array
	{
		$message = trim($message);

		if ($this->botToken === '' || $this->chatId === '' || $message === '') {
			return [
				'success' => false,
				'error' => 'Missing required configuration or message content.'
			];
		}

		$url = sprintf(
			'https://api.telegram.org/bot%s/sendMessage',
			rawurlencode($this->botToken)
		);

		$payload = [
			'chat_id' => $this->chatId,
			'text' => $message,
			'disable_web_page_preview' => $options['disable_web_page_preview'] ?? true,
		];

		$parseMode = trim((string) ($options['parse_mode'] ?? 'HTML'));
		if ($parseMode !== '') {
			$payload['parse_mode'] = $parseMode;
		}

		if (isset($options['disable_notification'])) {
			$payload['disable_notification'] = (bool) $options['disable_notification'];
		}

		$ch = curl_init();

		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
			],
			CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
			CURLOPT_TIMEOUT => 15,
		]);

		$response = curl_exec($ch);
		$curlError = curl_error($ch);
		$curlErrorCode = curl_errno($ch);
		$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($response === false) {
			return [
				'success' => false,
				'error' => $curlError !== '' ? $curlError : 'Telegram API request failed.',
				'curl_errno' => $curlErrorCode,
			];
		}

		$data = json_decode($response, true);

		if ($httpCode >= 200 && $httpCode < 300 && ($data['ok'] ?? false) === true) {
			return [
				'success' => true,
				'http_code' => $httpCode,
				'response' => $data,
			];
		}

		return [
			'success' => false,
			'http_code' => $httpCode,
			'error' => $data['description'] ?? 'Telegram API returned an error.',
			'response' => $data ?? $response,
		];
	}
}
