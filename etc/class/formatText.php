<?php
require_once 'Parsedown.php';

class FormatText {
	private static ?HeaderlessParsedown $parsedown = null;

	public static function Markdown(string $markdown): string {
		if (!self::$parsedown) {
			self::$parsedown = new HeaderlessParsedown();
			self::$parsedown->setMarkupEscaped(true);
		}
		return self::$parsedown->parse($markdown);
	}
}

/**
 * Parsedown class with headers disabled.
 */
class HeaderlessParsedown extends Parsedown {
	protected function blockHeader($Line) {
		return;
	}
	protected function blockSetextHeader($Line, array $Block = NULL) {
		return;
	}
}
