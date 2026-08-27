<?php
// app/config/app.php
// Qui puoi definire costanti o configurazioni globali dell'applicazione
// Esempio:
if (!defined('APP_NAME')) {
	define('APP_NAME', 'Italian Cosplay Events');
}

if (!defined('BASE_URL')) {
	define('BASE_URL', 'https://www.italiancosplay.it/');
}

if (!defined('TELEGRAM_BOT_TOKEN')) {
	define('TELEGRAM_BOT_TOKEN', '');
}

if (!defined('TELEGRAM_CHAT_ID')) {
	define('TELEGRAM_CHAT_ID', '');
}

if (!function_exists('dd')) {
	function dd(...$vars) {
		echo '<style>
            .dump {
                background: #1e1e1e;
                color: #dcdcdc;
                padding: 12px;
                margin: 10px 0;
                border-radius: 6px;
                font-family: Consolas, monospace;
                font-size: 14px;
                line-height: 1.4;
                white-space: pre-wrap;
            }
            .dump .type { color: #569cd6; }
            .dump .key  { color: #9cdcfe; }
            .dump .num  { color: #b5cea8; }
            .dump .str  { color: #ce9178; }
            .dump .bool { color: #4ec9b0; }
            .dump .null { color: #808080; }
        </style>';

		foreach ($vars as $var) {
			echo "<div class='dump'>" . highlight_var($var) . "</div>";
		}
		die;
	}

	function highlight_var($var, $depth = 0) {
		if (is_array($var)) {
			$out = "array(" . count($var) . ") [\n";
			foreach ($var as $key => $value) {
				$out .= str_repeat("    ", $depth + 1)
					. "<span class='key'>[" . htmlspecialchars((string)$key) . "]</span> => "
					. highlight_var($value, $depth + 1) . "\n";
			}
			return $out . str_repeat("    ", $depth) . "]";
		} elseif (is_object($var)) {
			$class = get_class($var);
			$out = "object($class) {\n";
			foreach ((array)$var as $key => $value) {
				$out .= str_repeat("    ", $depth + 1)
					. "<span class='key'>[" . htmlspecialchars((string)$key) . "]</span> => "
					. highlight_var($value, $depth + 1) . "\n";
			}
			return $out . str_repeat("    ", $depth) . "}";
		} elseif (is_string($var)) {
			return "<span class='str'>\"".htmlspecialchars($var)."\"</span>";
		} elseif (is_int($var) || is_float($var)) {
			return "<span class='num'>$var</span>";
		} elseif (is_bool($var)) {
			return "<span class='bool'>" . ($var ? 'true' : 'false') . "</span>";
		} elseif (is_null($var)) {
			return "<span class='null'>null</span>";
		} else {
			return "<span class='type'>".gettype($var)."</span>";
		}
	}
}
