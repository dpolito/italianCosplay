<?php

class Session
{
	public static function start()
	{
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
	}

	public static function set($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	public static function get($key, $default = null)
	{
		return $_SESSION[$key] ?? $default;
	}

	public static function has($key)
	{
		return isset($_SESSION[$key]);
	}

	public static function remove($key)
	{
		if (self::has($key)) {
			unset($_SESSION[$key]);
		}
	}

	public static function destroy()
	{
		session_unset();
		session_destroy();
	}

	// Metodi per messaggi flash
	public static function setFlash($key, $message)
	{
		self::set('flash_' . $key, $message);
	}

	public static function getFlash($key)
	{
		$flashKey = 'flash_' . $key;
		$message = self::get($flashKey);
		self::remove($flashKey); // Rimuovi il messaggio dopo averlo letto
		return $message;
	}

	public static function hasFlash($key)
	{
		return self::has('flash_' . $key);
	}
}
