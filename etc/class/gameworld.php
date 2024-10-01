<?php
require_once 'environment.php';
require_once 'formatDate.php';

class Gameworld {
	public string $ID;
	public TimeTagData $Instant;
	public string $Title;
	public string $Engine;
	public string $Description;
	public int $DMZX;

	public function __construct(CurrentUser $user, string $id, int $instant, string $title, string $engine, string $description, int $dmzx) {
		$this->ID = $id;
		if ($instant)
			$this->Instant = new TimeTagData($user, 'smart', $instant, FormatDate::Long);
		$this->Title = $title;
		$this->Engine = $engine;
		$this->Description = $description;
		$this->DMZX = $dmzx;
	}

	public static function List(mysqli $db, CurrentUser $user): array {
		$worlds = [];
		try {
			$select = $db->prepare('select w.id, unix_timestamp(p.instant), p.title, w.engine, w.description, w.dmzx from gameworld as w left join post as p on p.id=w.post order by instant desc');
			$select->execute();
			$select->bind_result($id, $instant, $title, $engine, $description, $dmzx);
			while ($select->fetch())
				$worlds[] = new self($user, $id, $instant, $title, $engine, $description, +$dmzx);
			return $worlds;
		} catch (mysqli_sql_exception $mse) {
			throw DetailedException::FromMysqliException('error looking up gameworlds', $mse);
		}
	}
}
