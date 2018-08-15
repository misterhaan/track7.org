<?php
/**
 * generic rss feed class.
 * @author misterhaan
 *
 */
class t7feed {
	const MAX_RESULTS = 16;
	private $xml;

	/**
	 * starts the xml for a feed, with some information about the entire feed.
	 *
	 * @param string $title title of the feed.
	 * @param string $url url where the contents of the feed can be found.  default is the website front page.
	 * @param string $description a description of the feed.
	 * @param string $copyright the copyright of the contents of the feed.
	 * @param string $lang the language of the feed.  default is 'en-us'.
	 */
	public function t7feed($title, $url = '', $description = '', $copyright = '', $lang = 'en-us') {
		header('Content-Type: application/rss+xml; charset=utf-8');
		$this->xml = new XMLWriter();
		$this->xml->openMemory();
		$this->xml->startDocument('1.0', 'utf-8');
		$this->xml->startElement('rss');
			$this->xml->writeAttribute('version', '2.0');
			$this->xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
			$this->xml->startElement('channel');
				$this->xml->writeElement('title', $title);
				if(substr($url, 0, 1) != '/')
					$url = '/' . $url;
				$this->xml->writeElement('link', t7format::FullUrl($url));
				$this->xml->startElement('atom:link');
					$this->xml->writeAttribute('href', t7format::FullUrl($_SERVER['REQUEST_URI']));
					$this->xml->writeAttribute('rel', 'self');
					$this->xml->writeAttribute('self', 'application/rss+xml');
				$this->xml->endElement();
				if($description)
					$this->xml->writeElement('description', $description);
				if($lang)
					$this->xml->writeElement('language', $lang);
				if($copyright)
					$this->xml->writeElement('copyright', $copyright);
				$this->xml->writeElement('generator', 't7feed, PHP/' . phpversion());
				$this->xml->writeElement('docs', 'http://blogs.law.harvard.edu/tech/rss');
	}

	/**
	 * adds an item to the feed.  all parameters are optional, but either
	 * $description or $title must be provided.
	 *
	 * @param string $description item description.
	 * @param string $title item title.
	 * @param string $url url where the content can be found on the main website.
	 * @param integer $date unix timestamp for the item.
	 * @param string $id unique identifier for the item.
	 * @param bool $idLink whether the id is a link.  default is not a link.
	 */
	public function AddItem($description = '', $title = '', $url = '', $date = '', $id = '', $idLink = false) {
				$this->xml->startElement('item');
					if($title)
						$this->xml->writeElement('title', $title);
					if($url) {
						if(substr($url, 0, 1) != '/')
							$url = '/' . $url;
						$this->xml->writeElement('link', t7format::FullUrl($url));
					}
					if($description) {
						$this->xml->startElement('description');
							$this->xml->writeCdata($description);
						$this->xml->endElement();  // description
					}
					if($date)
						$this->xml->writeElement('pubDate', gmdate('r', $date));
					if($id)
						if($idLink)
							$this->xml->writeElement('guid', t7format::FullUrl($id));
						else {
							$this->xml->startElement('guid');
								$this->xml->writeAttribute('isPermalink', 'false');
								$this->xml->writeRaw($id);  // TODO:  make sure this gets translated
							$this->xml->endElement();
						}
				$this->xml->endElement();  // item
	}

	/**
	 * closes and writes out the xml for a feed.
	 */
	public function End() {
			$this->xml->endElement();  // channel
		$this->xml->endElement();  // rss
		$this->xml->endDocument();
		die($this->xml->outputMemory());
	}
}
