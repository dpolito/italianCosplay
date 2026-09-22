<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\TelegramNotificationService;
use Throwable;

class ErrorHandler
{
	private const FATAL_ERROR_TYPES = [
		E_ERROR,
		E_PARSE,
		E_CORE_ERROR,
		E_COMPILE_ERROR,
		E_USER_ERROR,
		E_RECOVERABLE_ERROR,
	];

	private static bool $handling = false;

	public static function register(): void
	{
		ini_set('display_errors', '0');
		ini_set('display_startup_errors', '0');
		ini_set('log_errors', '1');
		error_reporting(E_ALL);

		set_error_handler([self::class, 'handleError']);
		set_exception_handler([self::class, 'handleException']);
		register_shutdown_function([self::class, 'handleShutdown']);
	}

	public static function handleError(int $severity, string $message, string $file, int $line): bool
	{
		if ((error_reporting() & $severity) !== $severity) {
			return false;
		}

		$context = [
			'type' => self::getErrorType($severity),
			'message' => $message,
			'file' => $file,
			'line' => $line,
		];

		error_log(self::formatLogMessage($context));

		if (in_array($severity, self::FATAL_ERROR_TYPES, true)) {
			self::notifyTelegram($context);
			self::renderGenericError();
			exit;
		}

		return true;
	}

	public static function handleException(Throwable $exception): void
	{
		$context = [
			'type' => $exception::class,
			'message' => $exception->getMessage(),
			'file' => $exception->getFile(),
			'line' => $exception->getLine(),
			'trace' => self::limitText($exception->getTraceAsString(), 1200),
		];

		error_log(self::formatLogMessage($context));
		self::notifyTelegram($context);
		self::renderGenericError();
	}

	public static function handleShutdown(): void
	{
		$error = error_get_last();

		if ($error === null || !in_array((int) $error['type'], self::FATAL_ERROR_TYPES, true)) {
			return;
		}

		$context = [
			'type' => self::getErrorType((int) $error['type']),
			'message' => (string) $error['message'],
			'file' => (string) $error['file'],
			'line' => (int) $error['line'],
		];

		error_log(self::formatLogMessage($context));
		self::notifyTelegram($context);
		self::renderGenericError();
	}

	private static function notifyTelegram(array $context): void
	{
		if (self::$handling) {
			return;
		}

		self::$handling = true;

		try {
			$botToken = defined('TELEGRAM_BOT_TOKEN') ? TELEGRAM_BOT_TOKEN : '';
			$chatId = defined('TELEGRAM_CHAT_ID') ? TELEGRAM_CHAT_ID : '';

			if (!is_string($botToken) || !is_string($chatId) || trim($botToken) === '' || trim($chatId) === '') {
				return;
			}

			$service = new TelegramNotificationService($botToken, $chatId);
			$result = $service->sendMessage(self::buildTelegramMessage($context), [
				'parse_mode' => 'HTML',
				'disable_web_page_preview' => true,
			]);

			if (($result['success'] ?? false) !== true) {
				error_log('Telegram error notification failed: ' . (string) ($result['error'] ?? 'Unknown error'));
			}
		} catch (Throwable $exception) {
			error_log('Telegram error notification exception: ' . $exception->getMessage());
		} finally {
			self::$handling = false;
		}
	}

	private static function buildTelegramMessage(array $context): string
	{
		$method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		$host = $_SERVER['HTTP_HOST'] ?? php_uname('n');

		$lines = [
			'<b>ItalianCosplay error</b>',
			'<b>Host:</b> ' . self::escape($host),
			'<b>Request:</b> ' . self::escape(trim($method . ' ' . $uri)),
			'<b>Type:</b> ' . self::escape((string) ($context['type'] ?? 'Unknown')),
			'<b>Message:</b> ' . self::escape(self::limitText((string) ($context['message'] ?? ''), 900)),
			'<b>File:</b> ' . self::escape((string) ($context['file'] ?? '')),
			'<b>Line:</b> ' . self::escape((string) ($context['line'] ?? '')),
			'<b>Time:</b> ' . self::escape(date('Y-m-d H:i:s')),
		];

		if (!empty($context['trace'])) {
			$lines[] = '<b>Trace:</b> <pre>' . self::escape((string) $context['trace']) . '</pre>';
		}

		return self::limitText(implode("\n", $lines), 3900);
	}

	private static function renderGenericError(): void
	{
		if (PHP_SAPI === 'cli') {
			return;
		}

		if (!headers_sent()) {
			http_response_code(500);
			header('Content-Type: text/html; charset=UTF-8');
		}

		echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Errore temporaneo</title></head><body><main style="max-width:720px;margin:48px auto;padding:0 20px;font-family:system-ui,-apple-system,Segoe UI,sans-serif;line-height:1.5"><h1>Errore temporaneo</h1><p>Si è verificato un problema tecnico. Riprova tra qualche minuto.</p><p><a href="/">Torna alla home</a></p></main></body></html>';
	}

	private static function formatLogMessage(array $context): string
	{
		return sprintf(
			'Application error [%s]: %s in %s:%s',
			(string) ($context['type'] ?? 'Unknown'),
			(string) ($context['message'] ?? ''),
			(string) ($context['file'] ?? ''),
			(string) ($context['line'] ?? '')
		);
	}

	private static function getErrorType(int $type): string
	{
		return match ($type) {
			E_ERROR => 'E_ERROR',
			E_WARNING => 'E_WARNING',
			E_PARSE => 'E_PARSE',
			E_NOTICE => 'E_NOTICE',
			E_CORE_ERROR => 'E_CORE_ERROR',
			E_CORE_WARNING => 'E_CORE_WARNING',
			E_COMPILE_ERROR => 'E_COMPILE_ERROR',
			E_COMPILE_WARNING => 'E_COMPILE_WARNING',
			E_USER_ERROR => 'E_USER_ERROR',
			E_USER_WARNING => 'E_USER_WARNING',
			E_USER_NOTICE => 'E_USER_NOTICE',
			E_STRICT => 'E_STRICT',
			E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
			E_DEPRECATED => 'E_DEPRECATED',
			E_USER_DEPRECATED => 'E_USER_DEPRECATED',
			default => 'E_UNKNOWN',
		};
	}

	private static function escape(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
	}

	private static function limitText(string $value, int $maxLength): string
	{
		if (mb_strlen($value) <= $maxLength) {
			return $value;
		}

		return mb_substr($value, 0, $maxLength - 3) . '...';
	}
}
