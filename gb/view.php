<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/etc/class/environment.php';

class Guestbook extends Responder {
	function __construct() {
		$book = isset($_GET['book']) ? trim($_GET['book']) : '';
		if (!$book)
			self::DetailedError('no guestbook to view!');

		$db = self::RequireDatabase();
		try {
			$select = $db->prepare('select id, header, footer from track7_t7data.gbbooks where name=? limit 1');
			$select->bind_param('s', $book);
			$select->execute();
			$select->bind_result($id, $header, $footer);
			if (!$select->fetch())
				self::DetailedError("could not find a guestbook named $book in the database");
			$select->close();
		} catch (mysqli_sql_exception $mse) {
			self::DetailedError(DetailedException::FromMysqliException('error looking up guestbook', $mse));
		}
		echo $header;
		try {
			$select = $db->prepare('select entry from track7_t7data.gbentries where bookid=? order by id desc');
			$select->bind_param('i', $id);
			$select->execute();
			$select->bind_result($entry);
			$any = false;
			while ($select->fetch()) {
				$any = true;
				echo $entry;
			}
			if (!$any)
				echo '<p>there are no entries in this guestbook yet.</p>';
		} catch (mysqli_sql_exception $mse) {
			self::DetailedError(DetailedException::FromMysqliException('error looking up guestbook entries', $mse));
		}
		echo $footer;
	}

	/**
	 * Show a detailed error.  Details are only showed to administrators.
	 * @param DetailedException|string $error Exception with details or non-detailed error message
	 * @param ?string $detail Extra detail for administrators.  Not used when $error is a DetailedException
	 */
	protected static function DetailedError(mixed $error, string $detail = null): void {
		if (self::HasAdminSecurity())
			if ($error instanceof DetailedException)
				die($error->getDetailedMessage());
			elseif ($detail)
				die("$error:  $detail");
			else
				die($error);
		elseif ($error instanceof DetailedException)
			die($error->getMessage());
		else
			die($error . '.');
	}
}
new Guestbook();
