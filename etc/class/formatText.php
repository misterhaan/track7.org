<?php
require_once 'Parsedown.php';

class FormatText {
	private static ?HeaderlessParsedown $parsedown = null;

	public static function CleanID(string $id): string {
		return preg_replace('/[^a-z0-9\\-_]*/g', '', preg_replace('/ /g', '-', strtolower($id)));
	}

	public static function Markdown(string $markdown): string {
		if (!self::$parsedown) {
			self::$parsedown = new HeaderlessParsedown();
			self::$parsedown->setMarkupEscaped(true);
		}
		return self::$parsedown->parse($markdown);
	}

	public static function Preview(string $html): Preview {
		$paragraphs = array_filter(explode('</p>', $html));
		return new Preview(array_shift($paragraphs) . '</p>', count($paragraphs) > 0);
	}
}

class Preview {
	public string $Text;
	public bool $HasMore;

	public function __construct(string $text, bool $hasMore) {
		$this->Text = $text;
		$this->HasMore = $hasMore;
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
